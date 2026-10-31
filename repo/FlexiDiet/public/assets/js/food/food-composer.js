/**
 * FLEXIDIET - FOOD COMPOSER & CUSTOM DISH BUILDER (food/food-composer.js)
 * Subtab 3: Interactive ingredient scaling sliders, live nutrition calculation, custom avatar, and dish saving.
 */

const FoodComposer = (() => {
  let ingSearchDebounce = null;

  async function loadDishToComposer(dishId, suggestedMeal = null) {
    if (typeof switchTab === 'function') switchTab('food');
    try {
      const recipeData = await FlexiAPI.request(`/foods/${dishId}/recipe`);
      if (!recipeData || !recipeData.dish) throw new Error('Không có dữ liệu công thức.');

      const { dish, ingredients } = recipeData;
      FoodCore.state.composer.dishId = dish.id;
      FoodCore.state.composer.name = dish.name;
      FoodCore.state.composer.dishCode = dish.dish_code || null;
      FoodCore.state.composer.servingLabel = dish.serving_label || '1 phần';
      FoodCore.state.composer.imageUrl = dish.image_url || null;
      FoodCore.state.composer.origin = dish.origin || 'system';
      if (suggestedMeal) FoodCore.state.composer.mealType = suggestedMeal;

      FoodCore.state.composer.ingredients = (ingredients || []).map(ing => {
        const grams = Number(ing.grams) || 100;
        const gSmall = Number(ing.grams_small) || Math.round(grams * 0.7);
        const gMed = Number(ing.grams_medium) || grams;
        const gLarge = Number(ing.grams_large) || Math.round(grams * 1.4);
        const maxSlider = Math.max(Math.round(gLarge * 1.5), Math.round(grams * 2.5), 200);

        return {
          ingredient_id: Number(ing.ingredient_id),
          name: ing.name,
          source: ing.source || 'vdd',
          grams,
          base_grams: grams,
          kcal_100g: Number(ing.kcal_100g),
          protein_100g: Number(ing.protein_100g),
          carb_100g: Number(ing.carb_100g),
          fat_100g: Number(ing.fat_100g),
          grams_small: gSmall,
          grams_medium: gMed,
          grams_large: gLarge,
          max_slider: maxSlider
        };
      });

      populateComposerUI();
      FoodCore.switchSubtab('composer');
      const badge = document.getElementById('fhDraftBadge');
      if (badge) {
        badge.style.display = 'inline-block';
        badge.textContent = FoodCore.state.composer.ingredients.length;
      }
    } catch (e) {
      if (typeof showToast === 'function') {
        showToast(`Không thể tải công thức món: ${e.message}`, 'warning');
      }
    }
  }

  async function loadDishByName(name, suggestedMeal = null) {
    if (!name || typeof name !== 'string') return;
    const clean = name.trim();
    if (!clean) return;
    if (typeof switchTab === 'function') switchTab('food');

    try {
      const matches = await FlexiAPI.request('/foods?q=' + encodeURIComponent(clean));
      if (matches && matches.length > 0) {
        const exact = matches.find(m => m.name.toLowerCase() === clean.toLowerCase()) || matches[0];
        await loadDishToComposer(exact.id, suggestedMeal);
      } else {
        FoodCore.state.composer.dishId = null;
        FoodCore.state.composer.name = clean;
        FoodCore.state.composer.servingLabel = '1 phần';
        FoodCore.state.composer.imageUrl = null;
        FoodCore.state.composer.origin = 'saved';
        FoodCore.state.composer.ingredients = [];
        if (suggestedMeal) FoodCore.state.composer.mealType = suggestedMeal;
        populateComposerUI();
        FoodCore.switchSubtab('composer');
        toggleAddIngredientPanel(true);
      }
    } catch (e) {
      console.warn('loadDishByName error:', e);
    }
  }

  function startNewCustomDish() {
    if (typeof switchTab === 'function') switchTab('food');
    FoodCore.state.composer.dishId = null;
    FoodCore.state.composer.name = '';
    FoodCore.state.composer.servingLabel = '1 phần';
    FoodCore.state.composer.imageUrl = null;
    FoodCore.state.composer.origin = 'saved';
    FoodCore.state.composer.ingredients = [];

    populateComposerUI();
    FoodCore.switchSubtab('composer');
    toggleAddIngredientPanel(true);
    if (typeof showToast === 'function') {
      showToast('Bắt đầu tạo món mới: hãy tìm và thêm nguyên liệu bên dưới!', 'info');
    }
  }

  function populateComposerUI() {
    const elName = document.getElementById('composerDishName');
    const elMeal = document.getElementById('composerMealType');
    const elLabel = document.getElementById('composerServingLabel');

    if (elName) elName.value = FoodCore.state.composer.name || '';
    if (elMeal) elMeal.value = FoodCore.state.composer.mealType || '';
    if (elLabel) {
      const val = FoodCore.state.composer.servingLabel || '1 phần';
      const exists = Array.from(elLabel.options).some(o => o.value === val);
      if (!exists && val) {
        const opt = document.createElement('option');
        opt.value = val;
        opt.textContent = val;
        elLabel.appendChild(opt);
      }
      elLabel.value = val;
    }

    const btnDeleteDish = document.getElementById('btnComposerDeleteDish');
    if (btnDeleteDish) {
      const isCustomExistingDish = Boolean(FoodCore.state.composer.dishId) &&
        (FoodCore.state.composer.origin === 'saved' || FoodCore.state.composer.origin === 'custom');
      btnDeleteDish.style.display = isCustomExistingDish ? 'inline-flex' : 'none';
    }

    renderComposerIngredients();
    recalculateComposerTotals();
    updateComposerAvatarUI();
  }

  function renderComposerIngredients() {
    const container = document.getElementById('composerIngredientsList');
    if (!container) return;
    container.replaceChildren();

    const escape = FoodCore.escapeHtml;
    if (!FoodCore.state.composer.ingredients.length) {
      container.innerHTML = `
        <div style="text-align:center;padding:30px;color:#64748b;background:#f8fafc;border-radius:10px;border:1px dashed #cbd5e1;">
          <p style="margin:0 0 10px;font-size:14px;"><i class="fa-solid fa-basket-shopping"></i> Món ăn chưa có nguyên liệu nào.</p>
          <button class="btn-jira-tool" type="button" onclick="FoodHub.toggleAddIngredientPanel(true)">
            <i class="fa-solid fa-plus"></i> Tìm &amp; Thêm nguyên liệu ngay
          </button>
        </div>
      `;
      return;
    }

    FoodCore.state.composer.ingredients.forEach((item, index) => {
      const row = document.createElement('div');
      row.className = 'fh-ing-row';

      const cal = Math.round((item.grams / 100) * item.kcal_100g);
      const prot = ((item.grams / 100) * item.protein_100g).toFixed(1);
      const carb = ((item.grams / 100) * item.carb_100g).toFixed(1);
      const fat = ((item.grams / 100) * item.fat_100g).toFixed(1);

      row.innerHTML = `
        <div class="fh-ing-meta">
          <div class="fh-ing-name-wrap">
            <span class="fh-ing-name">${escape(item.name)}</span>
            <span class="fh-ing-source">${escape(item.source)}</span>
          </div>
          <div class="fh-ing-unit-hint">
            Chuẩn: <strong>${item.base_grams}g</strong> (S: ${item.grams_small}g · M: ${item.grams_medium}g · L: ${item.grams_large}g)
          </div>
        </div>
        <div class="fh-ing-slider-zone">
          <div class="slider-labels-row">
            <span class="s">Nhỏ (S)</span>
            <span class="m">Vừa (M)</span>
            <span class="l">Lớn (L)</span>
          </div>
          <input 
            type="range" 
            class="range-zone-slider" 
            id="ingSlider-${index}"
            min="5" 
            max="${item.max_slider}" 
            step="5"
            value="${Math.min(item.grams, item.max_slider)}" 
            oninput="FoodHub.handleSliderChange(${index}, this.value)"
            aria-label="Thanh trượt khối lượng cho ${escape(item.name)}"
          />
        </div>
        <div class="fh-ing-input-wrap">
          <input 
            type="number" 
            id="ingInput-${index}"
            min="1" 
            max="2000" 
            step="1"
            value="${item.grams}" 
            oninput="FoodHub.handleGramInputChange(${index}, this.value)"
            aria-label="Số gram ${escape(item.name)}"
          />
          <span>g</span>
        </div>
        <div class="fh-ing-row-macros">
          <span class="fh-ing-row-cal" id="ingCal-${index}">${cal} kcal</span>
          <span id="ingMacro-${index}">P: ${prot}g · C: ${carb}g · F: ${fat}g</span>
        </div>
        <button class="btn-tbl-del" type="button" title="Xóa nguyên liệu" onclick="FoodHub.removeComposerIngredient(${index})">
          <i class="fa-solid fa-trash-can"></i>
        </button>
      `;

      container.appendChild(row);
    });
  }

  function handleSliderChange(index, val) {
    const item = FoodCore.state.composer.ingredients[index];
    if (!item) return;
    const grams = Number(val);
    item.grams = grams;

    const numInput = document.getElementById(`ingInput-${index}`);
    if (numInput) numInput.value = grams;

    updateIngredientMacroLabels(index, item);
    recalculateComposerTotals();
  }

  function handleGramInputChange(index, val) {
    const item = FoodCore.state.composer.ingredients[index];
    if (!item) return;
    let grams = Number(val);
    if (isNaN(grams) || grams < 1) grams = 1;
    if (grams > 2000) grams = 2000;
    item.grams = grams;

    const slider = document.getElementById(`ingSlider-${index}`);
    if (slider) {
      if (grams > item.max_slider) slider.value = item.max_slider;
      else slider.value = grams;
    }

    updateIngredientMacroLabels(index, item);
    recalculateComposerTotals();
  }

  function updateIngredientMacroLabels(index, item) {
    const calEl = document.getElementById(`ingCal-${index}`);
    const macroEl = document.getElementById(`ingMacro-${index}`);
    if (calEl) calEl.textContent = `${Math.round((item.grams / 100) * item.kcal_100g)} kcal`;
    if (macroEl) {
      const p = ((item.grams / 100) * item.protein_100g).toFixed(1);
      const c = ((item.grams / 100) * item.carb_100g).toFixed(1);
      const f = ((item.grams / 100) * item.fat_100g).toFixed(1);
      macroEl.textContent = `P: ${p}g · C: ${c}g · F: ${f}g`;
    }
  }

  function recalculateComposerTotals() {
    let totCal = 0, totProt = 0, totCarb = 0, totFat = 0, totWeight = 0;

    for (const item of FoodCore.state.composer.ingredients) {
      const g = item.grams || 0;
      totWeight += g;
      totCal += (g / 100) * item.kcal_100g;
      totProt += (g / 100) * item.protein_100g;
      totCarb += (g / 100) * item.carb_100g;
      totFat += (g / 100) * item.fat_100g;
    }

    const setText = (id, text) => {
      const el = document.getElementById(id);
      if (el) el.textContent = text;
    };

    setText('composerTotalCal', Math.round(totCal));
    setText('composerTotalProt', totProt.toFixed(1));
    setText('composerTotalCarb', totCarb.toFixed(1));
    setText('composerTotalFat', totFat.toFixed(1));
    setText('composerTotalWeight', Math.round(totWeight));
  }

  function removeComposerIngredient(index) {
    FoodCore.state.composer.ingredients.splice(index, 1);
    renderComposerIngredients();
    recalculateComposerTotals();
  }

  function toggleAddIngredientPanel(forceOpen = null) {
    const panel = document.getElementById('fhAddIngPanel');
    if (!panel) return;
    const shouldOpen = forceOpen !== null ? forceOpen : panel.style.display === 'none';
    panel.style.display = shouldOpen ? 'block' : 'none';
    if (shouldOpen) {
      const input = document.getElementById('searchNewIngredientInput');
      if (input) {
        input.value = '';
        input.focus();
        searchNewIngredients('');
      }
    }
  }

  async function searchNewIngredients(query) {
    const resBox = document.getElementById('fhAddIngResults');
    if (!resBox) return;
    resBox.innerHTML = '<div style="padding:10px;color:#64748b;"><i class="fa-solid fa-spinner fa-spin"></i> Đang tìm...</div>';

    const escape = FoodCore.escapeHtml;
    try {
      const items = await FlexiAPI.request(`/ingredients?q=${encodeURIComponent(query.trim())}`);
      resBox.replaceChildren();

      if (!items || !items.length) {
        resBox.innerHTML = '<div style="padding:10px;color:#64748b;">Không tìm thấy nguyên liệu phù hợp.</div>';
        return;
      }

      for (const item of items) {
        const div = document.createElement('div');
        div.className = 'fh-air-item';
        const aliasBadge = item.matched_alias && item.matched_alias.toLowerCase() !== item.name.toLowerCase()
          ? `<span style="font-size:11px; font-weight:normal; color:#0284c7; background:rgba(2,132,199,0.1); padding:1px 6px; border-radius:4px; margin-left:6px;"><i class="fa-solid fa-tags" style="font-size:9px;"></i> Khớp: ${escape(item.matched_alias)}</span>`
          : '';
        div.innerHTML = `
          <div class="fh-air-info">
            <strong>${escape(item.name)}${aliasBadge}</strong>
            <span>${Math.round(item.kcal_100g)} kcal / 100g · P: ${item.protein_100g}g · C: ${item.carb_100g}g · F: ${item.fat_100g}g</span>
          </div>
          <button class="btn-jira-tool" type="button" style="padding:4px 10px;font-size:12px;">
            <i class="fa-solid fa-plus"></i> Thêm
          </button>
        `;
        div.addEventListener('click', () => {
          addIngredientToComposer(item);
        });
        resBox.appendChild(div);
      }
    } catch (e) {
      resBox.innerHTML = `<div style="padding:10px;color:#ef4444;">Lỗi: ${e.message}</div>`;
    }
  }

  function addIngredientToComposer(raw) {
    FoodCore.state.composer.ingredients.push({
      ingredient_id: Number(raw.id),
      name: raw.name,
      source: raw.source || 'vdd',
      grams: 100,
      base_grams: 100,
      kcal_100g: Number(raw.kcal_100g),
      protein_100g: Number(raw.protein_100g),
      carb_100g: Number(raw.carb_100g),
      fat_100g: Number(raw.fat_100g),
      grams_small: 70,
      grams_medium: 100,
      grams_large: 150,
      max_slider: 250
    });

    renderComposerIngredients();
    recalculateComposerTotals();
    toggleAddIngredientPanel(false);
    if (typeof showToast === 'function') {
      showToast(`Đã thêm "${raw.name}" vào công thức!`, 'success');
    }
  }

  async function submitComposerToMealLog() {
    const elName = document.getElementById('composerDishName');
    const elMeal = document.getElementById('composerMealType');
    const name = elName ? elName.value.trim() : FoodCore.state.composer.name;
    const hour = new Date().getHours();
    const defaultMeal = hour < 10 ? 'breakfast' : (hour < 14 ? 'lunch' : (hour < 17 ? 'snack' : 'dinner'));
    const meal = (elMeal && elMeal.value) ? elMeal.value : (FoodCore.state.composer.mealType || defaultMeal);

    if (!name) {
      if (typeof showToast === 'function') showToast('Vui lòng nhập tên món ăn.', 'warning');
      return;
    }
    if (!FoodCore.state.composer.ingredients.length) {
      if (typeof showToast === 'function') showToast('Món ăn phải có ít nhất 1 nguyên liệu.', 'warning');
      return;
    }

    const btn = document.getElementById('btnComposerSaveLog');
    if (btn) btn.disabled = true;

    try {
      const payload = {
        name,
        meal_type: meal,
        dish_id: FoodCore.state.composer.dishId || null,
        items: FoodCore.state.composer.ingredients.map(i => ({
          ingredient_id: i.ingredient_id,
          grams: i.grams
        }))
      };

      const lastRec = window.FoodScanner ? window.FoodScanner.getLastRecognizedResult() : null;
      if (lastRec && lastRec.top_prediction) {
        payload.source_kind = 'ai';
        payload.ai_predicted_dish_code = lastRec.top_prediction.dish_code;
        payload.ai_predicted_confidence = lastRec.top_prediction.confidence;
        payload.ai_dish_code = FoodCore.state.composer.dishCode || (lastRec.dish && lastRec.dish.dish_code) || name;
      }

      const estimatedKcal = Math.round(FoodCore.state.composer.ingredients.reduce((sum, i) =>
        sum + Math.round(Number(i.grams) / 100 * Number(i.kcal_100g) * 10) / 10, 0) * 10) / 10;
      
      let saved = null;
      if (typeof FlexiBudgetConfirm !== 'undefined' && typeof FlexiBudgetConfirm.saveMeal === 'function') {
        saved = await FlexiBudgetConfirm.saveMeal(payload, {name, estimatedKcal});
      } else {
        saved = await FlexiAPI.request('/meals', { method: 'POST', data: payload });
      }

      if (!saved) return;
      if (typeof fdRefreshDashboard === 'function') await fdRefreshDashboard();
      if (typeof showToast === 'function') {
        showToast(`Đã lưu "${saved.name || name}" vào nhật ký ${meal} thành công!`, 'success');
      }
      if (typeof switchTab === 'function') switchTab('dashboard');
    } catch (e) {
      if (typeof showToast === 'function') {
        showToast(`Lỗi khi lưu nhật ký: ${e.message}`, 'warning');
      }
    } finally {
      if (btn) btn.disabled = false;
    }
  }

  async function submitComposerAsMyDish() {
    const elName = document.getElementById('composerDishName');
    const elLabel = document.getElementById('composerServingLabel');
    const name = elName ? elName.value.trim() : FoodCore.state.composer.name;
    const label = elLabel ? elLabel.value.trim() : '1 phần';

    if (!name) {
      if (typeof showToast === 'function') showToast('Vui lòng đặt tên cho món ăn của bạn.', 'warning');
      return;
    }
    if (!FoodCore.state.composer.ingredients.length) {
      if (typeof showToast === 'function') showToast('Món ăn phải có ít nhất 1 nguyên liệu.', 'warning');
      return;
    }

    const btn = document.getElementById('btnComposerSaveMyDish');
    if (btn) btn.disabled = true;

    try {
      const payload = {
        name,
        serving_label: label || '1 phần',
        image_url: FoodCore.state.composer.imageUrl || null,
        ingredients: FoodCore.state.composer.ingredients.map(i => ({
          ingredient_id: i.ingredient_id,
          grams: i.grams
        }))
      };

      const created = await FlexiAPI.request('/dishes', { method: 'POST', data: payload });
      if (typeof showToast === 'function') {
        showToast(`Đã lưu "${created.name}" vào Món Của Tôi (UC13)!`, 'success');
      }
      if (typeof window.refreshQuickFoodDishesCache === 'function') {
        window.refreshQuickFoodDishesCache();
      }
      if (window.FoodCatalog && typeof window.FoodCatalog.loadCatalog === 'function') {
        await window.FoodCatalog.loadCatalog();
      }
      FoodCore.switchSubtab('catalog');
    } catch (e) {
      if (typeof showToast === 'function') {
        showToast(`Lỗi khi lưu món cá nhân: ${e.message}`, 'warning');
      }
    } finally {
      if (btn) btn.disabled = false;
    }
  }

  async function deleteCurrentCustomDish() {
    const dishId = FoodCore.state.composer.dishId;
    const dishName = FoodCore.state.composer.name || 'Món ăn này';
    if (!dishId || !(FoodCore.state.composer.origin === 'saved' || FoodCore.state.composer.origin === 'custom')) {
      if (typeof showToast === 'function') {
        showToast('Chỉ có thể xóa món tự tạo của bạn, không thể xóa món chuẩn hệ thống.', 'warning');
      }
      return;
    }

    if (!confirm(`Bạn có chắc chắn muốn xóa "${dishName}" khỏi danh sách Món của tôi không?\nHành động này không thể hoàn tác.`)) {
      return;
    }

    try {
      await FlexiAPI.request(`/dishes/${dishId}`, { method: 'DELETE' });
      if (typeof showToast === 'function') {
        showToast(`Đã xóa món "${dishName}" thành công!`, 'success');
      }
      if (typeof window.refreshQuickFoodDishesCache === 'function') {
        window.refreshQuickFoodDishesCache();
      }

      FoodCore.state.composer.dishId = null;
      FoodCore.state.composer.name = '';
      FoodCore.state.composer.servingLabel = '1 phần';
      FoodCore.state.composer.origin = 'saved';
      FoodCore.state.composer.ingredients = [];
      populateComposerUI();
      const badge = document.getElementById('fhDraftBadge');
      if (badge) {
        badge.style.display = 'none';
        badge.textContent = '0';
      }

      if (window.FoodCatalog && typeof window.FoodCatalog.loadCatalog === 'function') {
        await window.FoodCatalog.loadCatalog();
      }
      FoodCore.switchSubtab('catalog');
    } catch (e) {
      if (typeof showToast === 'function') {
        showToast(`Lỗi khi xóa món: ${e.message}`, 'warning');
      }
    }
  }

  function resetComposer() {
    if (FoodCore.state.composer.ingredients.length > 0) {
      if (!confirm('Bạn có chắc chắn muốn làm mới và xóa tất cả nguyên liệu đang phối không?')) {
        return;
      }
    }
    FoodCore.state.composer.dishId = null;
    FoodCore.state.composer.name = '';
    FoodCore.state.composer.servingLabel = '1 phần';
    FoodCore.state.composer.imageUrl = null;
    FoodCore.state.composer.origin = 'saved';
    FoodCore.state.composer.ingredients = [];
    populateComposerUI();
    const badge = document.getElementById('fhDraftBadge');
    if (badge) {
      badge.style.display = 'none';
      badge.textContent = '0';
    }
    if (typeof showToast === 'function') {
      showToast('Đã làm mới bảng phối món.', 'info');
    }
  }

  function handleComposerAvatarChange(e) {
    const file = e.target.files && e.target.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) {
      if (typeof showToast === 'function') showToast('Vui lòng chọn tệp hình ảnh hợp lệ.', 'warning');
      return;
    }
    if (file.size > 2 * 1024 * 1024) {
      if (typeof showToast === 'function') showToast('Kích thước ảnh tối đa là 2MB.', 'warning');
      return;
    }
    const reader = new FileReader();
    reader.onload = (evt) => {
      FoodCore.state.composer.imageUrl = evt.target.result;
      updateComposerAvatarUI();
      if (typeof showToast === 'function') showToast('Đã chọn ảnh đại diện cho món ăn!', 'info');
    };
    reader.readAsDataURL(file);
  }

  function removeComposerAvatar() {
    FoodCore.state.composer.imageUrl = null;
    const input = document.getElementById('composerAvatarInput');
    if (input) input.value = '';
    updateComposerAvatarUI();
    if (typeof showToast === 'function') showToast('Đã gỡ ảnh đại diện.', 'info');
  }

  function updateComposerAvatarUI() {
    const preview = document.getElementById('composerAvatarPreview');
    const img = document.getElementById('composerAvatarImg');
    const hint = document.getElementById('composerAvatarHint');
    if (FoodCore.state.composer.imageUrl) {
      if (preview) preview.style.display = 'block';
      if (img) img.src = FoodCore.state.composer.imageUrl;
      if (hint) hint.textContent = 'Đã chọn ảnh đại diện riêng cho món.';
    } else {
      if (preview) preview.style.display = 'none';
      if (img) img.src = '';
      if (hint) hint.textContent = 'Tùy chọn — nếu không chọn, món ăn sẽ hiển thị dạng thẻ gọn không kèm ảnh minh họa.';
    }
  }

  function initComposerListeners() {
    const ingSearch = document.getElementById('searchNewIngredientInput');
    if (ingSearch) {
      ingSearch.addEventListener('input', () => {
        clearTimeout(ingSearchDebounce);
        ingSearchDebounce = setTimeout(() => searchNewIngredients(ingSearch.value), 250);
      });
    }

    const elName = document.getElementById('composerDishName');
    if (elName) {
      elName.addEventListener('input', () => {
        FoodCore.state.composer.name = elName.value;
      });
    }

    const elLabel = document.getElementById('composerServingLabel');
    if (elLabel) {
      elLabel.addEventListener('change', () => {
        FoodCore.state.composer.servingLabel = elLabel.value;
      });
    }
  }

  return {
    loadDishToComposer,
    loadDishByName,
    startNewCustomDish,
    populateComposerUI,
    renderComposerIngredients,
    handleSliderChange,
    handleGramInputChange,
    updateIngredientMacroLabels,
    recalculateComposerTotals,
    removeComposerIngredient,
    toggleAddIngredientPanel,
    searchNewIngredients,
    addIngredientToComposer,
    submitComposerToMealLog,
    submitComposerAsMyDish,
    deleteCurrentCustomDish,
    resetComposer,
    handleComposerAvatarChange,
    removeComposerAvatar,
    updateComposerAvatarUI,
    initComposerListeners
  };
})();

window.FoodComposer = FoodComposer;
