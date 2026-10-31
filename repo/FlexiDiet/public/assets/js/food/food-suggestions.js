/**
 * FLEXIDIET - SMART FOOD SUGGESTIONS (food/food-suggestions.js)
 * Subtab 4: Calorie budget & Macro multi-filter recommendations, ranking badges, and instant meal logging.
 */

const FoodSuggestions = (() => {
  async function loadSmartSuggestions(tag, meal, origin) {
    const sNutri = document.getElementById('filterNutriSelect');
    const sMeal = document.getElementById('filterMealSelect');
    const sOrigin = document.getElementById('filterOriginSelect');

    const activeNutri = tag !== undefined ? tag : (sNutri ? sNutri.value : (FoodCore.state.filterNutri || 'all'));
    const activeMeal = meal !== undefined ? meal : (sMeal ? sMeal.value : (FoodCore.state.filterMeal || 'all'));
    const activeOrigin = origin !== undefined ? origin : (sOrigin ? sOrigin.value : (FoodCore.state.filterOrigin || 'all'));

    FoodCore.state.filterNutri = activeNutri;
    FoodCore.state.filterMeal = activeMeal;
    FoodCore.state.filterOrigin = activeOrigin;

    if (sNutri && sNutri.value !== activeNutri) sNutri.value = activeNutri;
    if (sMeal && sMeal.value !== activeMeal) sMeal.value = activeMeal;
    if (sOrigin && sOrigin.value !== activeOrigin) sOrigin.value = activeOrigin;

    const grid = document.getElementById('suggestionsGrid');
    if (!grid) return;

    grid.innerHTML = `
      <div class="fh-loading-dishes" style="grid-column: 1 / -1; text-align: center; padding: 40px 0;">
        <i class="fa-solid fa-spinner fa-spin"></i> Đang phân tích ngân sách và tính toán gợi ý món ăn tối ưu...
      </div>`;

    let suggestions = [];
    try {
      const query = 'tag=' + encodeURIComponent(activeNutri) + '&meal=' + encodeURIComponent(activeMeal) + '&origin=' + encodeURIComponent(activeOrigin);
      const res = await FlexiAPI.request('/foods/suggestions?' + query);
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

    // Fallback if empty and in offline/demo mode
    if (!suggestions.length && (activeNutri === 'all' && activeMeal === 'all' && activeOrigin === 'all')) {
      suggestions = [
        { dish_id: 1, name: 'Salad Ức Gà Áp Chảo Rau Củ Healthy', origin: 'system', kcal: 340, protein_g: 38, carb_g: 14, fat_g: 8, match_pct: 95, match_reason: 'Cung cấp 38g Protein nạc, xà lách giòn, sốt chanh leo ít béo thanh mát.', image_url: 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=500&auto=format&fit=crop&q=80' },
        { dish_id: 14, name: 'Bún Chả Hà Nội Nướng Than Hoa', origin: 'system', kcal: 510, protein_g: 28, carb_g: 64, fat_g: 16, match_pct: 88, match_reason: 'Chả nạc nướng thơm lừng, nước mắm dấm ớt chua ngọt thanh nhẹ kèm rau sống.', image_url: 'https://images.unsplash.com/photo-1559847844-5315695dadae?w=500&auto=format&fit=crop&q=80' },
        { dish_id: 31, name: 'Gỏi Cuốn Tôm Thịt Nạc (3 Cuốn)', origin: 'system', kcal: 280, protein_g: 22, carb_g: 34, fat_g: 6, match_pct: 91, match_reason: 'Tôm luộc tươi ngọt, thịt nạc, bún tươi và rau thơm cuốn bánh tráng mềm.', image_url: 'https://images.unsplash.com/photo-1534422298391-e4f8c172dddb?w=500&auto=format&fit=crop&q=80' },
        { dish_id: 27, name: 'Cơm Tấm Sườn Nướng Chả Trứng', origin: 'system', kcal: 580, protein_g: 32, carb_g: 75, fat_g: 15, match_pct: 84, match_reason: 'Sườn nướng mật ong đậm đà, chả trứng hấp mềm xốp, dưa chuột cà chua giải ngấy.', image_url: 'https://images.unsplash.com/photo-1544025162-d76694265947?w=500&auto=format&fit=crop&q=80' }
      ];
    }

    const escape = FoodCore.escapeHtml;
    if (!suggestions.length) {
      let emptyMsg = 'Không tìm thấy món ăn nào thỏa mãn đồng thời các tiêu chí lọc đã chọn.';
      if (activeOrigin === 'custom' || activeOrigin === 'saved') {
        emptyMsg = 'Bạn chưa tạo món ăn cá nhân nào. Hãy chuyển sang tab "3. Tùy chỉnh & Phối món" để tự phối món ruột của riêng bạn!';
      } else if (activeNutri !== 'all' && activeMeal !== 'all') {
        emptyMsg = 'Không có món ăn nào đồng thời khớp cả mục tiêu dinh dưỡng và bữa ăn này. Bạn hãy thử nới lỏng một tiêu chí nhé!';
      }
      grid.innerHTML = `
        <div class="fh-loading-dishes" style="grid-column: 1 / -1; text-align: center; padding: 40px 0; color: var(--text-muted);">
          <i class="fa-solid fa-circle-info" style="font-size: 24px; margin-bottom: 8px;"></i>
          <p>${escape(emptyMsg)}</p>
        </div>`;
      return;
    }

    FoodCore.state.suggestions = suggestions;
    grid.replaceChildren();
    for (const dish of suggestions) {
      const card = document.createElement('div');
      card.className = 'suggest-card';
      card.setAttribute('data-dish-id', dish.dish_id);

      const isCustom = dish.origin === 'custom' || dish.origin === 'saved';
      const badgeClass = isCustom ? 'match-custom' : (dish.match_pct >= 85 ? 'match-high' : 'match-med');
      const badgeIcon = isCustom ? '<i class="fa-solid fa-star"></i> ' : '<i class="fa-solid fa-bullseye"></i> ';
      const badgeLabel = isCustom ? `Món Ruột (${dish.match_pct}%)` : `Khớp ${dish.match_pct}% Mục Tiêu`;
      const defaultImg = 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&auto=format&fit=crop&q=80';

      const cal = Math.round(dish.kcal ?? dish.calories ?? 0);
      const prot = Math.round(dish.protein_g ?? dish.protein ?? 0);
      const carb = Math.round(dish.carb_g ?? dish.carbs ?? 0);
      const fat = Math.round(dish.fat_g ?? dish.fat ?? 0);

      const hasImage = Boolean(dish.image_url);
      const imgSection = hasImage ? `
        <div class="sc-img-wrap">
          <img alt="${escape(dish.name)}" src="${escape(dish.image_url)}" onerror="this.src='${defaultImg}'" loading="lazy" />
          <span class="sc-badge ${badgeClass}">${badgeIcon}${escape(badgeLabel)}</span>
          <span class="sc-cook-time"><i class="fa-solid fa-clock"></i> 15-20 phút</span>
        </div>
      ` : `
        <div class="sc-no-img-header">
          <span class="sc-badge ${badgeClass}">${badgeIcon}${escape(badgeLabel)}</span>
          <span class="sc-no-img-tag"><i class="fa-solid fa-user-pen"></i> Món tự tạo</span>
        </div>
      `;

      card.innerHTML = `
        ${imgSection}
        <div class="sc-body">
          <h4>${escape(dish.name)}</h4>
          <p class="sc-desc">${escape(dish.match_reason || '')}</p>
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
        btnSave.addEventListener('click', () => openSuggestionInQuickModal(dish));
      }

      grid.appendChild(card);
    }
  }

  function applyMultiFilter() {
    loadSmartSuggestions();
  }

  function resetMultiFilter() {
    const sNutri = document.getElementById('filterNutriSelect');
    const sMeal = document.getElementById('filterMealSelect');
    const sOrigin = document.getElementById('filterOriginSelect');
    if (sNutri) sNutri.value = 'all';
    if (sMeal) sMeal.value = 'all';
    if (sOrigin) sOrigin.value = 'all';
    FoodCore.state.filterNutri = 'all';
    FoodCore.state.filterMeal = 'all';
    FoodCore.state.filterOrigin = 'all';
    loadSmartSuggestions('all', 'all', 'all');
  }

  function openSuggestionInQuickModal(dish) {
    if (!dish) return;
    let defaultMeal = 'lunch';
    const sMeal = document.getElementById('filterMealSelect')?.value;
    if (sMeal === 'breakfast' || sMeal === 'snack') {
      defaultMeal = sMeal;
    } else if (sMeal === 'main_meal') {
      defaultMeal = new Date().getHours() < 15 ? 'lunch' : 'dinner';
    } else {
      const hour = new Date().getHours();
      if (hour < 10) defaultMeal = 'breakfast';
      else if (hour < 14) defaultMeal = 'lunch';
      else if (hour < 17) defaultMeal = 'snack';
      else defaultMeal = 'dinner';
    }

    if (typeof openCreateQuickModalWithDish === 'function') {
      openCreateQuickModalWithDish(dish, defaultMeal);
    } else if (typeof openCreateQuickModal === 'function') {
      openCreateQuickModal(defaultMeal, 'food');
      if (typeof selectQuickFoodDish === 'function') {
        selectQuickFoodDish({
          id: Number(dish.id || dish.dish_id),
          name: dish.name,
          origin: dish.origin || 'system',
          serving_label: dish.serving_label || '1 phần',
          kcal: Number(dish.kcal ?? dish.calories ?? 0),
          protein_g: Number(dish.protein_g ?? dish.protein ?? 0),
          carb_g: Number(dish.carb_g ?? dish.carbs ?? 0),
          fat_g: Number(dish.fat_g ?? dish.fat ?? 0)
        });
      }
    } else {
      saveSuggestedMealDirect(dish.dish_id || dish.id, dish.name, defaultMeal);
    }
  }

  function saveSuggestedMeal(dishId, name) {
    const dish = (FoodCore.state.suggestions || []).find(d => Number(d.dish_id || d.id) === Number(dishId)) || { dish_id: dishId, name };
    openSuggestionInQuickModal(dish);
  }

  async function saveSuggestedMealDirect(dishId, name, mealType) {
    if (!mealType) {
      const hour = new Date().getHours();
      if (hour < 10) mealType = 'breakfast';
      else if (hour < 14) mealType = 'lunch';
      else if (hour < 17) mealType = 'snack';
      else mealType = 'dinner';
    }
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

  return {
    loadSmartSuggestions,
    applyMultiFilter,
    resetMultiFilter,
    openSuggestionInQuickModal,
    saveSuggestedMeal,
    saveSuggestedMealDirect
  };
})();

window.FoodSuggestions = FoodSuggestions;

