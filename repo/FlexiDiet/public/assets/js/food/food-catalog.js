/**
 * FLEXIDIET - FOOD CATALOG (food/food-catalog.js)
 * Subtab 2: 67 Vietnamese standard dishes & user saved custom dishes catalog.
 */

const FoodCatalog = (() => {
  let catalogDebounce = null;

  async function loadCatalog(query = '') {
    const grid = document.getElementById('foodHubDishesGrid');
    if (!grid) return;
    grid.innerHTML = '<div class="fh-loading-dishes"><i class="fa-solid fa-spinner fa-spin"></i> Đang tải danh mục món ăn...</div>';

    try {
      const dishes = await FlexiAPI.request(`/foods?q=${encodeURIComponent(query.trim())}`);
      FoodCore.state.catalogDishes = dishes || [];
      renderCatalogGrid();
    } catch (e) {
      grid.innerHTML = `<div class="fh-loading-dishes" style="color:#ef4444;"><i class="fa-solid fa-triangle-exclamation"></i> Không thể tải danh mục món: ${e.message}</div>`;
    }
  }

  function filterCatalogOrigin(origin) {
    FoodCore.state.catalogFilter = origin;
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

    let list = FoodCore.state.catalogDishes || [];
    if (FoodCore.state.catalogFilter === 'system') {
      list = list.filter(d => d.origin === 'system');
    } else if (FoodCore.state.catalogFilter === 'saved') {
      list = list.filter(d => d.origin === 'saved');
    }

    if (!list.length) {
      grid.innerHTML = '<div class="fh-loading-dishes">Không tìm thấy món ăn phù hợp. Bạn có thể bấm "+ Tạo Món Mới" để tự phối món!</div>';
      return;
    }

    const escape = FoodCore.escapeHtml;
    for (const dish of list) {
      const card = document.createElement('div');
      card.className = 'fh-dish-card';

      const isSaved = dish.origin === 'saved' || dish.origin === 'custom';
      const badgeHtml = isSaved
        ? '<span class="fh-dc-badge saved"><i class="fa-solid fa-bookmark"></i> Món Của Tôi</span>'
        : '<span class="fh-dc-badge system"><i class="fa-solid fa-check"></i> 67 Món Chuẩn</span>';

      card.innerHTML = `
        <div class="fh-dc-left">
          <div class="fh-dc-icon"><i class="fa-solid fa-bowl-rice"></i></div>
          <div class="fh-dc-info">
            <div class="fh-dc-title-row">
              <h4 class="fh-dc-title">${escape(dish.name)}</h4>
              ${badgeHtml}
            </div>
            <span class="fh-dc-serving"><i class="fa-solid fa-utensils"></i> Khẩu phần chuẩn: <strong>${escape(dish.serving_label || '1 phần')}</strong></span>
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
          ${isSaved ? `
          <button class="fh-dc-btn-delete" type="button" title="Xóa món khỏi Món của tôi" onclick="FoodHub.deleteDishFromCatalog(${dish.id})">
            <i class="fa-solid fa-trash-can"></i>
          </button>
          ` : ''}
        </div>
      `;
      grid.appendChild(card);
    }
  }

  async function deleteDishFromCatalog(dishId) {
    const dish = (FoodCore.state.catalogDishes || []).find(d => Number(d.id) === Number(dishId));
    const name = dish ? dish.name : 'món ăn này';
    if (!confirm(`Bạn có chắc chắn muốn xóa món "${name}" khỏi danh sách Món của tôi không?\nHành động này không thể hoàn tác.`)) {
      return;
    }

    try {
      await FlexiAPI.request(`/dishes/${dishId}`, { method: 'DELETE' });
      if (typeof showToast === 'function') {
        showToast(`Đã xóa món "${name}" thành công!`, 'success');
      }
      if (typeof window.refreshQuickFoodDishesCache === 'function') {
        window.refreshQuickFoodDishesCache();
      }

      if (FoodCore.state.composer.dishId === dishId) {
        FoodCore.state.composer.dishId = null;
        FoodCore.state.composer.name = '';
        FoodCore.state.composer.servingLabel = '1 phần';
        FoodCore.state.composer.origin = 'saved';
        FoodCore.state.composer.ingredients = [];
        if (window.FoodComposer && typeof window.FoodComposer.populateComposerUI === 'function') {
          window.FoodComposer.populateComposerUI();
        }
        const badge = document.getElementById('fhDraftBadge');
        if (badge) {
          badge.style.display = 'none';
          badge.textContent = '0';
        }
      }

      await loadCatalog();
    } catch (e) {
      if (typeof showToast === 'function') {
        showToast(`Lỗi khi xóa món: ${e.message}`, 'warning');
      }
    }
  }

  function initCatalogListeners() {
    const catSearch = document.getElementById('foodHubCatalogSearch');
    if (catSearch) {
      catSearch.addEventListener('input', () => {
        clearTimeout(catalogDebounce);
        catalogDebounce = setTimeout(() => loadCatalog(catSearch.value), 250);
      });
    }
  }

  return {
    loadCatalog,
    filterCatalogOrigin,
    renderCatalogGrid,
    deleteDishFromCatalog,
    initCatalogListeners
  };
})();

window.FoodCatalog = FoodCatalog;
