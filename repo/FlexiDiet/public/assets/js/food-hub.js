/**
 * FLEXIDIET - FOOD HUB ORCHESTRATOR (food-hub.js)
 * Manages Subtabs, Catalog (67 Vietnamese Dishes & My Saved Dishes),
 * AI Vision Scanner (UC04), and Dish Customizer & Composer (UC05, UC12, UC13).
 */

const FoodHub = (() => {
  // In-memory composer state
  const state = {
    activeSubtab: 'scanner',
    catalogDishes: [],
    catalogFilter: 'all',
    suggestionsTag: 'all',
    currentDish: null,
    composer: {
      dishId: null,
      name: 'Phở Bò Tái Nạm',
      mealType: 'breakfast',
      servingLabel: '1 tô vừa',
      origin: 'system',
      ingredients: []
    }
  };

  // Preset mapping to system dish names
  const presetMap = {
    pho_bo: { name: 'Phở', meal: 'breakfast', fallbackId: 46 },
    com_tam: { name: 'Cơm tấm', meal: 'lunch', fallbackId: 27 },
    bun_cha: { name: 'Bún chả', meal: 'lunch', fallbackId: 14 },
    banh_mi: { name: 'Bánh mì', meal: 'breakfast', fallbackId: 5 },
    salad_ga: { name: 'Bánh canh', meal: 'dinner', fallbackId: 1 },
    goi_cuon: { name: 'Gỏi cuốn', meal: 'snack', fallbackId: 31 }
  };

  // 1. Subtab Navigation Switcher
  function switchSubtab(subtabName) {
    state.activeSubtab = subtabName;
    const tabs = {
      scanner: { btn: 'btnFhScanner', pane: 'paneFhScanner' },
      catalog: { btn: 'btnFhCatalog', pane: 'paneFhCatalog' },
      composer: { btn: 'btnFhComposer', pane: 'paneFhComposer' },
      suggestions: { btn: 'btnFhSuggestions', pane: 'paneFhSuggestions' }
    };

    for (const [key, item] of Object.entries(tabs)) {
      const btn = document.getElementById(item.btn);
      const pane = document.getElementById(item.pane);
      const isActive = key === subtabName;
      if (btn) {
        btn.classList.toggle('active', isActive);
        btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
      }
      if (pane) pane.classList.toggle('active', isActive);
    }

    if (subtabName === 'catalog' && !state.catalogDishes.length) {
      loadCatalog();
    }
    if (subtabName === 'suggestions') {
      loadSmartSuggestions(state.suggestionsTag || 'all');
    }
  }

  // 2. Catalog Traversal & Filter
  let catalogDebounce = null;
  async function loadCatalog(query = '') {
    const grid = document.getElementById('foodHubDishesGrid');
    if (!grid) return;
    grid.innerHTML = '<div class="fh-loading-dishes"><i class="fa-solid fa-spinner fa-spin"></i> Đang tải danh mục món ăn...</div>';

    try {
      const dishes = await FlexiAPI.request(`/foods?q=${encodeURIComponent(query.trim())}`);
      state.catalogDishes = dishes || [];
      renderCatalogGrid();
    } catch (e) {
      grid.innerHTML = `<div class="fh-loading-dishes" style="color:#ef4444;"><i class="fa-solid fa-triangle-exclamation"></i> Không thể tải danh mục món: ${e.message}</div>`;
    }
  }

  function filterCatalogOrigin(origin) {
    state.catalogFilter = origin;
    document.querySelectorAll('.cat-pill').forEach(btn => btn.classList.remove('active'));
    const btnMap = { all: 'catFilterAll', system: 'catFilterSystem', saved: 'catFilterSaved' };
    const activeBtn = document.getElementById(btnMap[origin] || 'catFilterAll');
    if (activeBtn) activeBtn.classList.add('active');
    renderCatalogGrid();
  }

  function renderCatalogGrid() {
    const grid = document.getElementById('foodHubDishesGrid');
    if (!grid) return;
    grid.replaceChildren();

    let list = state.catalogDishes;
    if (state.catalogFilter === 'system') list = list.filter(d => d.origin === 'system');
    else if (state.catalogFilter === 'saved') list = list.filter(d => d.origin === 'saved');

    if (!list.length) {
      grid.innerHTML = '<div class="fh-loading-dishes">Không tìm thấy món ăn phù hợp. Bạn có thể bấm "+ Tạo Món Mới" để tự phối món!</div>';
      return;
    }

    for (const dish of list) {
      const card = document.createElement('div');
      card.className = 'fh-dish-card';

      const isSaved = dish.origin === 'saved';
      const badgeHtml = isSaved
        ? '<span class="fh-dc-badge saved"><i class="fa-solid fa-bookmark"></i> Món Của Tôi</span>'
        : '<span class="fh-dc-badge system"><i class="fa-solid fa-check"></i> 67 Món Chuẩn</span>';

      card.innerHTML = `
        <div class="fh-dc-left">
          <div class="fh-dc-icon"><i class="fa-solid fa-bowl-rice"></i></div>
          <div class="fh-dc-info">
            <div class="fh-dc-title-row">
              <h4 class="fh-dc-title">${escapeHtml(dish.name)}</h4>
              ${badgeHtml}
            </div>
            <span class="fh-dc-serving"><i class="fa-solid fa-utensils"></i> Khẩu phần chuẩn: <strong>${escapeHtml(dish.serving_label || '1 phần')}</strong></span>
          </div>
        </div>
        <div class="fh-dc-macros">
          <span class="fh-dc-cal"><i class="fa-solid fa-fire"></i> ${Math.round(Number(dish.kcal))} <small>kcal</small></span>
          <span class="fh-dc-macro-item p">Protein: <strong>${dish.protein_g}g</strong></span>
          <span class="fh-dc-macro-item c">Carb: <strong>${dish.carb_g}g</strong></span>
          <span class="fh-dc-macro-item f">Fat: <strong>${dish.fat_g}g</strong></span>
        </div>
        <div class="fh-dc-actions">
          <button class="fh-dc-btn-customize" type="button" onclick="FoodHub.loadDishToComposer(${dish.id})">
            <i class="fa-solid fa-sliders"></i>
            <span class="btn-text-full">Tùy chỉnh &amp; Ghi món</span>
            <span class="btn-text-short">Tùy chỉnh</span>
          </button>
        </div>
      `;
      grid.appendChild(card);
    }
  }

  // 3. AI Scanner & Demo Preset Pickers
  async function loadPreset(presetKey) {
    const preset = presetMap[presetKey];
    if (!preset) return;

    // Trigger visual scanner laser
    const dropContent = document.getElementById('foodDropContent');
    const previewWrap = document.getElementById('foodPreviewWrap');
    const previewImg = document.getElementById('foodScannedPreview');
    const laser = document.getElementById('foodLaserScanner');

    const sampleImages = {
      pho_bo: 'https://images.unsplash.com/photo-1582878826629-29b7ad1cdc43?w=500&auto=format&fit=crop&q=80',
      com_tam: 'https://images.unsplash.com/photo-1544025162-d76694265947?w=500&auto=format&fit=crop&q=80',
      bun_cha: 'https://images.unsplash.com/photo-1559847844-5315695dadae?w=500&auto=format&fit=crop&q=80',
      banh_mi: 'https://images.unsplash.com/photo-1626804475297-41608ea09aeb?w=500&auto=format&fit=crop&q=80',
      salad_ga: 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=500&auto=format&fit=crop&q=80',
      goi_cuon: 'https://images.unsplash.com/photo-1534422298391-e4f8c172dddb?w=500&auto=format&fit=crop&q=80'
    };

    if (dropContent) dropContent.style.display = 'none';
    if (previewWrap) previewWrap.style.display = 'block';
    if (previewImg) previewImg.src = sampleImages[presetKey] || '';
    if (laser) laser.style.display = 'block';

    showToast(`AI Vision đang quét hình ảnh món ăn: ${preset.name}...`, 'info');

    // Laser effect 800ms -> Find dish in backend -> Load to Composer
    setTimeout(async () => {
      if (laser) laser.style.display = 'none';
      try {
        const matches = await FlexiAPI.request(`/foods?q=${encodeURIComponent(preset.name)}`);
        const targetDish = (matches && matches.length > 0) ? matches[0] : { id: preset.fallbackId };
        await loadDishToComposer(targetDish.id, preset.meal);
        showToast(`AI nhận diện thành công: "${targetDish.name || preset.name}"! Chuyển sang bảng tùy chỉnh.`, 'success');
      } catch (err) {
        showToast(`Không thể tải công thức: ${err.message}`, 'warning');
      }
    }, 850);
  }

  let lastRecognizedResult = null;

  async function handleImageUpload(e) {
    const file = e.target.files?.[0];
    if (!file) return;

    // Reset input value so selecting same file again fires event
    e.target.value = '';

    const dropContent = document.getElementById('foodDropContent');
    const previewWrap = document.getElementById('foodPreviewWrap');
    const previewImg = document.getElementById('foodScannedPreview');
    const laser = document.getElementById('foodLaserScanner');

    // Show preview immediately
    const reader = new FileReader();
    reader.onload = function(evt) {
      if (dropContent) dropContent.style.display = 'none';
      if (previewWrap) previewWrap.style.display = 'block';
      if (previewImg) previewImg.src = evt.target.result;
      if (laser) laser.style.display = 'block';
    };
    reader.readAsDataURL(file);

    // Hide previous result card and show guide card
    const guideCard = document.getElementById('foodScannerGuideCard');
    const resCard = document.getElementById('foodScannerResultCard');
    if (resCard) resCard.style.display = 'none';
    if (guideCard) guideCard.style.display = 'flex';

    showToast('AI Vision (YOLOv10m) đang phân tích hình ảnh...', 'info');

    try {
      const formData = new FormData();
      formData.append('image', file);
      const res = await FlexiAPI.request('/ai/recognize', { method: 'POST', data: formData });

      if (laser) laser.style.display = 'none';

      if (res && res.status === 'recognized' && res.dish) {
        displayRecognizedResult(res);
      } else {
        displayUnrecognizedFallback(res || {});
      }
    } catch (err) {
      if (laser) laser.style.display = 'none';
      displayUnrecognizedFallback({
        status: 'unrecognized',
        is_unknown: true,
        message: err.message || 'Không thể nhận diện được món ăn.'
      });
    }
  }

  function displayRecognizedResult(res) {
    lastRecognizedResult = res;

    const guideCard = document.getElementById('foodScannerGuideCard');
    const resCard = document.getElementById('foodScannerResultCard');
    if (guideCard) guideCard.style.display = 'none';
    if (resCard) resCard.style.display = 'flex';

    const topPred = res.top_prediction || {};
    const confPct = Math.round((topPred.confidence || 0.85) * 100);

    const tag = document.getElementById('foodScanStatusTag');
    if (tag) tag.innerHTML = `<i class="fa-solid fa-circle-check"></i> Đã nhận diện (${confPct}%)`;

    const lat = document.getElementById('foodScanLatencyTag');
    if (lat) lat.innerHTML = `<i class="fa-solid fa-bolt"></i> ${res.inference_ms || 240}ms`;

    const title = document.getElementById('foodScanDishTitle');
    if (title) title.textContent = res.dish.name;

    const sub = document.getElementById('foodScanServingSub');
    if (sub) sub.textContent = `Khẩu phần chuẩn: ${res.dish.serving_label || '1 phần'} · Công thức chuẩn VDD`;

    // Calculate macros from recipe ingredients if available
    let kcal = 0, prot = 0, carb = 0, fat = 0;
    if (res.recipe && Array.isArray(res.recipe.ingredients) && res.recipe.ingredients.length > 0) {
      for (const ing of res.recipe.ingredients) {
        const grams = Number(ing.grams) || 0;
        kcal += (grams * Number(ing.kcal_100g || 0)) / 100;
        prot += (grams * Number(ing.protein_100g || 0)) / 100;
        carb += (grams * Number(ing.carb_100g || 0)) / 100;
        fat += (grams * Number(ing.fat_100g || 0)) / 100;
      }
    } else {
      kcal = Number(res.dish.kcal) || 0;
      prot = Number(res.dish.protein_g) || 0;
      carb = Number(res.dish.carb_g) || 0;
      fat = Number(res.dish.fat_g) || 0;
    }

    const elCal = document.getElementById('foodScanCal');
    const elProt = document.getElementById('foodScanProt');
    const elCarb = document.getElementById('foodScanCarb');
    const elFat = document.getElementById('foodScanFat');
    if (elCal) elCal.textContent = Math.round(kcal);
    if (elProt) elProt.textContent = prot.toFixed(1);
    if (elCarb) elCarb.textContent = carb.toFixed(1);
    if (elFat) elFat.textContent = fat.toFixed(1);

    // Alternative predictions
    const altsWrap = document.getElementById('foodScanAltsWrap');
    const altsChips = document.getElementById('foodScanAltsChips');
    if (altsWrap && altsChips) {
      const otherPreds = (res.predictions || []).filter(p => p.dish_code !== topPred.dish_code);
      if (otherPreds.length > 0) {
        altsChips.innerHTML = otherPreds.map(p => `
          <button type="button" class="alt-chip" onclick="FoodHub.selectAlternativePrediction('${escapeHtml(p.dish_code)}')">
            <i class="fa-solid fa-utensils"></i> ${escapeHtml(p.name_vi)} (${Math.round(p.confidence * 100)}%)
          </button>
        `).join('');
        altsWrap.style.display = 'block';
      } else {
        altsWrap.style.display = 'none';
      }
    }

    showToast(`AI nhận diện thành công: "${res.dish.name}" (${confPct}%)!`, 'success');
  }

  function displayUnrecognizedFallback(res) {
    const modal = document.getElementById('fdAiUnrecognizedModal');
    if (modal) {
      const topPred = res.top_prediction;
      const lowPredBox = document.getElementById('aiLowPredBox');
      const lowPredText = document.getElementById('aiLowPredText');
      if (topPred && Number(topPred.confidence) > 0.05 && lowPredBox && lowPredText) {
        lowPredBox.style.display = 'block';
        lowPredText.textContent = `${topPred.name_vi || topPred.dish_code} (Độ tin cậy: ${Math.round(topPred.confidence * 100)}% - dưới ngưỡng chuẩn xác 40%)`;
      } else if (lowPredBox) {
        lowPredBox.style.display = 'none';
      }
      modal.style.display = 'flex';
    }
    showToast('Chưa nhận diện được món do món chưa có trong danh sách hoặc ảnh mờ.', 'warning');
  }

  function closeUnrecognizedModal() {
    const modal = document.getElementById('fdAiUnrecognizedModal');
    if (modal) modal.style.display = 'none';
  }

  function retryScannerImage() {
    closeUnrecognizedModal();
    resetScanner();
    const input = document.getElementById('foodHubImageInput');
    if (input) input.click();
  }

  function goToManualComposer() {
    closeUnrecognizedModal();
    startNewCustomDish();
  }

  function onResultGoComposer() {
    if (lastRecognizedResult && lastRecognizedResult.dish && lastRecognizedResult.dish.id) {
      loadDishToComposer(lastRecognizedResult.dish.id);
    } else {
      switchSubtab('composer');
    }
  }

  async function selectAlternativePrediction(dishCode) {
    showToast(`Đang nạp công thức cho món: ${dishCode}...`, 'info');
    try {
      const matches = await FlexiAPI.request(`/foods?q=${encodeURIComponent(dishCode)}`);
      if (matches && matches.length > 0) {
        await loadDishToComposer(matches[0].id);
      } else {
        showToast('Món chưa có sẵn trong danh mục.', 'warning');
      }
    } catch (e) {
      showToast(e.message, 'warning');
    }
  }

  function resetScanner() {
    lastRecognizedResult = null;
    const dropContent = document.getElementById('foodDropContent');
    const previewWrap = document.getElementById('foodPreviewWrap');
    const previewImg = document.getElementById('foodScannedPreview');
    const laser = document.getElementById('foodLaserScanner');
    const guideCard = document.getElementById('foodScannerGuideCard');
    const resCard = document.getElementById('foodScannerResultCard');

    if (dropContent) dropContent.style.display = 'block';
    if (previewWrap) previewWrap.style.display = 'none';
    if (previewImg) previewImg.src = '';
    if (laser) laser.style.display = 'none';
    if (resCard) resCard.style.display = 'none';
    if (guideCard) guideCard.style.display = 'flex';
  }

  async function triggerPromptAnalysis() {
    const input = document.getElementById('foodHubPromptInput');
    const prompt = input?.value.trim();
    if (!prompt) {
      showToast('Vui lòng nhập mô tả món ăn (VD: 1 tô phở bò tái nạm...)', 'warning');
      return;
    }
    showToast(`AI đang phân tích mô tả: "${prompt}"...`, 'info');
    try {
      const matches = await FlexiAPI.request(`/foods?q=${encodeURIComponent(prompt)}`);
      if (matches && matches.length > 0) {
        await loadDishToComposer(matches[0].id);
        showToast(`Đã tìm thấy công thức phù hợp: ${matches[0].name}!`, 'success');
      } else {
        loadPreset('salad_ga');
      }
    } catch {
      loadPreset('salad_ga');
    }
  }

  // 4. Composer Engine (UC05)
  async function loadDishToComposer(dishId, suggestedMeal = null) {
    try {
      const recipeData = await FlexiAPI.request(`/foods/${dishId}/recipe`);
      if (!recipeData || !recipeData.dish) throw new Error('Không có dữ liệu công thức.');

      const { dish, ingredients } = recipeData;
      state.composer.dishId = dish.id;
      state.composer.name = dish.name;
      state.composer.dishCode = dish.dish_code || null;
      state.composer.servingLabel = dish.serving_label || '1 phần';
      state.composer.origin = dish.origin || 'system';
      if (suggestedMeal) state.composer.mealType = suggestedMeal;

      state.composer.ingredients = ingredients.map(ing => {
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
      switchSubtab('composer');
      const badge = document.getElementById('fhDraftBadge');
      if (badge) {
        badge.style.display = 'inline-block';
        badge.textContent = state.composer.ingredients.length;
      }
    } catch (e) {
      showToast(`Không thể tải công thức món: ${e.message}`, 'warning');
    }
  }

  function startNewCustomDish() {
    if (typeof switchTab === 'function') switchTab('food');
    state.composer.dishId = null;
    state.composer.name = 'Món Ăn Tự Phối Của Tôi';
    state.composer.servingLabel = '1 phần';
    state.composer.origin = 'saved';
    state.composer.ingredients = [];

    populateComposerUI();
    switchSubtab('composer');
    toggleAddIngredientPanel(true);
    showToast('Bắt đầu tạo món mới: hãy tìm và thêm nguyên liệu bên dưới!', 'info');
  }

  function populateComposerUI() {
    const elName = document.getElementById('composerDishName');
    const elMeal = document.getElementById('composerMealType');
    const elLabel = document.getElementById('composerServingLabel');

    if (elName) elName.value = state.composer.name;
    if (elMeal) elMeal.value = state.composer.mealType;
    if (elLabel) elLabel.value = state.composer.servingLabel;

    renderComposerIngredients();
    recalculateComposerTotals();
  }

  function renderComposerIngredients() {
    const container = document.getElementById('composerIngredientsList');
    if (!container) return;
    container.replaceChildren();

    if (!state.composer.ingredients.length) {
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

    state.composer.ingredients.forEach((item, index) => {
      const row = document.createElement('div');
      row.className = 'fh-ing-row';

      const cal = Math.round((item.grams / 100) * item.kcal_100g);
      const prot = ((item.grams / 100) * item.protein_100g).toFixed(1);
      const carb = ((item.grams / 100) * item.carb_100g).toFixed(1);
      const fat = ((item.grams / 100) * item.fat_100g).toFixed(1);

      row.innerHTML = `
        <div class="fh-ing-meta">
          <div class="fh-ing-name-wrap">
            <span class="fh-ing-name">${escapeHtml(item.name)}</span>
            <span class="fh-ing-source">${escapeHtml(item.source)}</span>
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
            aria-label="Thanh trượt khối lượng cho ${escapeHtml(item.name)}"
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
            aria-label="Số gram ${escapeHtml(item.name)}"
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
    const item = state.composer.ingredients[index];
    if (!item) return;
    const grams = Number(val);
    item.grams = grams;

    const numInput = document.getElementById(`ingInput-${index}`);
    if (numInput) numInput.value = grams;

    updateIngredientMacroLabels(index, item);
    recalculateComposerTotals();
  }

  function handleGramInputChange(index, val) {
    const item = state.composer.ingredients[index];
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

    for (const item of state.composer.ingredients) {
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
    state.composer.ingredients.splice(index, 1);
    renderComposerIngredients();
    recalculateComposerTotals();
  }

  // 5. Add Extra Ingredient Searching
  let ingSearchDebounce = null;
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
          ? `<span style="font-size:11px; font-weight:normal; color:#0284c7; background:rgba(2,132,199,0.1); padding:1px 6px; border-radius:4px; margin-left:6px;"><i class="fa-solid fa-tags" style="font-size:9px;"></i> Khớp: ${escapeHtml(item.matched_alias)}</span>`
          : '';
        div.innerHTML = `
          <div class="fh-air-info">
            <strong>${escapeHtml(item.name)}${aliasBadge}</strong>
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
    // Add 100g default
    state.composer.ingredients.push({
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
    showToast(`Đã thêm "${raw.name}" vào công thức!`, 'success');
  }

  // 6. Final Save Actions (UC05 Snapshot & UC13 My Dishes)
  async function submitComposerToMealLog() {
    const elName = document.getElementById('composerDishName');
    const elMeal = document.getElementById('composerMealType');
    const name = elName ? elName.value.trim() : state.composer.name;
    const meal = elMeal ? elMeal.value : state.composer.mealType;

    if (!name) {
      showToast('Vui lòng nhập tên món ăn.', 'warning');
      return;
    }
    if (!state.composer.ingredients.length) {
      showToast('Món ăn phải có ít nhất 1 nguyên liệu.', 'warning');
      return;
    }

    const btn = document.getElementById('btnComposerSaveLog');
    if (btn) btn.disabled = true;

    try {
      const payload = {
        name,
        meal_type: meal,
        dish_id: state.composer.dishId || null,
        items: state.composer.ingredients.map(i => ({
          ingredient_id: i.ingredient_id,
          grams: i.grams
        }))
      };

      if (lastRecognizedResult && lastRecognizedResult.top_prediction) {
        payload.source_kind = 'ai';
        payload.ai_predicted_dish_code = lastRecognizedResult.top_prediction.dish_code;
        payload.ai_predicted_confidence = lastRecognizedResult.top_prediction.confidence;
        payload.ai_dish_code = state.composer.dishCode || (lastRecognizedResult.dish && lastRecognizedResult.dish.dish_code) || name;
      }

      // Calculate the same per-ingredient 0.1 kcal rounding as createMeal() in PHP.
      const estimatedKcal = Math.round(state.composer.ingredients.reduce((sum, i) =>
        sum + Math.round(Number(i.grams) / 100 * Number(i.kcal_100g) * 10) / 10, 0) * 10) / 10;
      const saved = await FlexiBudgetConfirm.saveMeal(payload, {name, estimatedKcal});
      if (!saved) return; // Explicit choice: do not eat/add the item.
      if (typeof fdRefreshDashboard === 'function') await fdRefreshDashboard();
      showToast(`Đã lưu "${saved.name}" vào nhật ký ${meal} dạng snapshot thành công!`, 'success');
      switchTab('dashboard');
    } catch (e) {
      showToast(`Lỗi khi lưu nhật ký: ${e.message}`, 'warning');
    } finally {
      if (btn) btn.disabled = false;
    }
  }

  async function submitComposerAsMyDish() {
    const elName = document.getElementById('composerDishName');
    const elLabel = document.getElementById('composerServingLabel');
    const name = elName ? elName.value.trim() : state.composer.name;
    const label = elLabel ? elLabel.value.trim() : '1 phần';

    if (!name) {
      showToast('Vui lòng đặt tên cho món ăn của bạn.', 'warning');
      return;
    }
    if (!state.composer.ingredients.length) {
      showToast('Món ăn phải có ít nhất 1 nguyên liệu.', 'warning');
      return;
    }

    const btn = document.getElementById('btnComposerSaveMyDish');
    if (btn) btn.disabled = true;

    try {
      const payload = {
        name,
        serving_label: label || '1 phần',
        ingredients: state.composer.ingredients.map(i => ({
          ingredient_id: i.ingredient_id,
          grams: i.grams
        }))
      };

      const created = await FlexiAPI.request('/dishes', { method: 'POST', data: payload });
      showToast(`Đã lưu "${created.name}" vào Món Của Tôi (UC13)!`, 'success');
      if (typeof window.refreshQuickFoodDishesCache === 'function') window.refreshQuickFoodDishesCache();
      await loadCatalog();
      switchSubtab('catalog');
    } catch (e) {
      showToast(`Lỗi khi lưu món cá nhân: ${e.message}`, 'warning');
    } finally {
      if (btn) btn.disabled = false;
    }
  }

  function escapeHtml(str) {
    return String(str || '').replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
  }


    // 6.5. Smart Food Suggestions (UC-Recommendation)
  async function loadSmartSuggestions(tag = 'all') {
    state.suggestionsTag = tag;
    const grid = document.getElementById('suggestionsGrid');
    if (!grid) return;

    grid.innerHTML = `
      <div class="fh-loading-dishes" style="grid-column: 1 / -1; text-align: center; padding: 40px 0;">
        <i class="fa-solid fa-spinner fa-spin"></i> Đang phân tích ngân sách và tính toán gợi ý món ăn tối ưu...
      </div>`;

    let suggestions = [];
    try {
      const res = await FlexiAPI.request('/foods/suggestions?tag=' + encodeURIComponent(tag));
      if (res && Array.isArray(res.suggestions)) {
        suggestions = res.suggestions;
      } else if (res && Array.isArray(res.data)) {
        suggestions = res.data;
      } else if (Array.isArray(res)) {
        suggestions = res;
      }
    } catch (e) {
      console.warn('Backend suggestions API unverified or session offline:', e);
    }

    // Fallback if empty (e.g. offline demo mode or cold start)
    if (!suggestions.length) {
      suggestions = [
        {
          dish_id: 1,
          name: 'Salad Ức Gà Áp Chảo Rau Củ Healthy',
          origin: 'system',
          kcal: 340,
          protein_g: 38,
          carb_g: 14,
          fat_g: 8,
          match_pct: 95,
          match_reason: 'Cung cấp 38g Protein nạc, xà lách giòn, sốt chanh leo ít béo thanh mát.',
          image_url: 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=500&auto=format&fit=crop&q=80'
        },
        {
          dish_id: 14,
          name: 'Bún Chả Hà Nội Nướng Than Hoa',
          origin: 'system',
          kcal: 510,
          protein_g: 28,
          carb_g: 64,
          fat_g: 16,
          match_pct: 88,
          match_reason: 'Chả nạc nướng thơm lừng, nước mắm dấm ớt chua ngọt thanh nhẹ kèm rau sống.',
          image_url: 'https://images.unsplash.com/photo-1559847844-5315695dadae?w=500&auto=format&fit=crop&q=80'
        },
        {
          dish_id: 31,
          name: 'Gỏi Cuốn Tôm Thịt Nạc (3 Cuốn)',
          origin: 'system',
          kcal: 280,
          protein_g: 22,
          carb_g: 34,
          fat_g: 6,
          match_pct: 91,
          match_reason: 'Tôm luộc tươi ngọt, thịt nạc, bún tươi và rau thơm cuốn bánh tráng mềm.',
          image_url: 'https://images.unsplash.com/photo-1534422298391-e4f8c172dddb?w=500&auto=format&fit=crop&q=80'
        },
        {
          dish_id: 27,
          name: 'Cơm Tấm Sườn Nướng Chả Trứng',
          origin: 'system',
          kcal: 580,
          protein_g: 32,
          carb_g: 75,
          fat_g: 15,
          match_pct: 84,
          match_reason: 'Sườn nướng mật ong đậm đà, chả trứng hấp mềm xốp, dưa chuột cà chua giải ngấy.',
          image_url: 'https://images.unsplash.com/photo-1544025162-d76694265947?w=500&auto=format&fit=crop&q=80'
        }
      ];
      if (tag === 'high_prot') {
        suggestions = suggestions.filter(d => (d.protein_g ?? d.protein ?? 0) >= 25);
      } else if (tag === 'low_cal') {
        suggestions = suggestions.filter(d => (d.kcal ?? d.calories ?? 0) <= 350);
      }
    }

    grid.replaceChildren();
    for (const dish of suggestions) {
      const card = document.createElement('div');
      card.className = 'suggest-card';
      card.setAttribute('data-dish-id', dish.dish_id);

      const isCustom = dish.origin === 'custom';
      const badgeClass = isCustom ? 'match-custom' : (dish.match_pct >= 85 ? 'match-high' : 'match-med');
      const badgeIcon = isCustom ? '<i class="fa-solid fa-star"></i> ' : '<i class="fa-solid fa-bullseye"></i> ';
      const badgeLabel = isCustom ? `Món Ruột (${dish.match_pct}%)` : `Khớp ${dish.match_pct}% Mục Tiêu`;
      const defaultImg = 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&auto=format&fit=crop&q=80';

      const cal = Math.round(dish.kcal ?? dish.calories ?? 0);
      const prot = Math.round(dish.protein_g ?? dish.protein ?? 0);
      const carb = Math.round(dish.carb_g ?? dish.carbs ?? 0);
      const fat = Math.round(dish.fat_g ?? dish.fat ?? 0);

      card.innerHTML = `
        <div class="sc-img-wrap">
          <img alt="${escapeHtml(dish.name)}" src="${escapeHtml(dish.image_url || defaultImg)}" onerror="this.src='${defaultImg}'" loading="lazy" />
          <span class="sc-badge ${badgeClass}">${badgeIcon}${escapeHtml(badgeLabel)}</span>
          <span class="sc-cook-time"><i class="fa-solid fa-clock"></i> 15-20 phút</span>
        </div>
        <div class="sc-body">
          <h4>${escapeHtml(dish.name)}</h4>
          <p class="sc-desc">${escapeHtml(dish.match_reason || '')}</p>
          <div class="sc-nutrition-chips">
            <span class="snc snc-cal"><i class="fa-solid fa-fire"></i> ${cal} kcal</span>
            <span class="snc snc-prot">P: ${prot}g</span>
            <span class="snc snc-carb">C: ${carb}g</span>
            <span class="snc snc-fat">F: ${fat}g</span>
          </div>
        </div>
        <div class="sc-footer">
          <button class="btn-jira-create btn-full btn-save-suggestion" type="button">
            <i class="fa-solid fa-plus"></i> Thêm Vào Thực Đơn Hôm Nay
          </button>
        </div>
      `;

      const btnSave = card.querySelector('.btn-save-suggestion');
      if (btnSave) {
        btnSave.addEventListener('click', () => saveSuggestedMeal(dish.dish_id, dish.name));
      }

      grid.appendChild(card);
    }
  }

  async function saveSuggestedMeal(dishId, name) {
    const hour = new Date().getHours();
    let mealType = 'lunch';
    if (hour < 10) mealType = 'breakfast';
    else if (hour < 14) mealType = 'lunch';
    else if (hour < 17) mealType = 'snack';
    else mealType = 'dinner';

    if (typeof fdSaveMeal === 'function') {
      await fdSaveMeal({ dish_id: Number(dishId), meal_type: mealType, serving_factor: 1.0 });
    } else {
      try {
        await FlexiAPI.request('/meals', {
          method: 'POST',
          data: { dish_id: Number(dishId), meal_type: mealType, serving_factor: 1.0 }
        });
        if (typeof showToast === 'function') showToast(`Đã thêm "${name}" vào thực đơn!`, 'success');
        if (typeof switchTab === 'function') switchTab('dashboard');
      } catch (e) {
        if (typeof showToast === 'function') showToast(`Không thể lưu món: ${e.message}`, 'warning');
      }
    }
  }

  // 7. Initialization & Event Listeners
  function init() {
    const catSearch = document.getElementById('foodHubCatalogSearch');
    if (catSearch) {
      catSearch.addEventListener('input', () => {
        clearTimeout(catalogDebounce);
        catalogDebounce = setTimeout(() => loadCatalog(catSearch.value), 250);
      });
    }

    const ingSearch = document.getElementById('searchNewIngredientInput');
    if (ingSearch) {
      ingSearch.addEventListener('input', () => {
        clearTimeout(ingSearchDebounce);
        ingSearchDebounce = setTimeout(() => searchNewIngredients(ingSearch.value), 250);
      });
    }

    // Escape key closes unrecognized modal
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeUnrecognizedModal();
    });

    // Default load catalog once page modules are ready
    loadCatalog();
    loadSmartSuggestions('all');
  }

  return {
    init,
    switchSubtab,
    loadCatalog,
    filterCatalogOrigin,
    loadPreset,
    handleImageUpload,
    onResultGoComposer,
    selectAlternativePrediction,
    resetScanner,
    closeUnrecognizedModal,
    retryScannerImage,
    goToManualComposer,
    triggerPromptAnalysis,
    loadDishToComposer,
    startNewCustomDish,
    handleSliderChange,
    handleGramInputChange,
    removeComposerIngredient,
    toggleAddIngredientPanel,
    submitComposerToMealLog,
    submitComposerAsMyDish,
    loadSmartSuggestions,
    saveSuggestedMeal
  };
})();

// Global proxies for inline HTML onclick handlers
function switchFoodSubtab(name) { FoodHub.switchSubtab(name); }
function filterCatalogOrigin(origin) { FoodHub.filterCatalogOrigin(origin); }
function loadFoodHubPreset(key) { FoodHub.loadPreset(key); }
function handleFoodHubImageUpload(e) { FoodHub.handleImageUpload(e); }
function triggerFoodHubPromptAnalysis() { FoodHub.triggerPromptAnalysis(); }
function startNewCustomDish() { FoodHub.startNewCustomDish(); }
function toggleAddIngredientPanel(force) { FoodHub.toggleAddIngredientPanel(force); }
function submitComposerToMealLog() { FoodHub.submitComposerToMealLog(); }
function submitComposerAsMyDish() { FoodHub.submitComposerAsMyDish(); }
function loadSmartSuggestions(tag) { FoodHub.loadSmartSuggestions(tag); }
function saveSuggestedMeal(dishId, name) { FoodHub.saveSuggestedMeal(dishId, name); }

document.addEventListener('flexidiet:pages-ready', () => {
  FoodHub.init();
});
