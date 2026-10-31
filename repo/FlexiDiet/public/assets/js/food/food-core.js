/**
 * FLEXIDIET - FOOD CORE ORCHESTRATOR (food/food-core.js)
 * Manages shared state, subtab switching, and common utility functions.
 */

const FoodCore = (() => {
  // Shared state container for Food Hub
  const state = {
    activeSubtab: 'scanner',
    catalogDishes: [],
    catalogFilter: 'all',
    filterNutri: 'all',
    filterMeal: 'all',
    filterOrigin: 'all',
    suggestionsTag: 'all',
    currentDish: null,
    composer: {
      dishId: null,
      name: '',
      mealType: 'breakfast',
      servingLabel: '1 phần',
      origin: 'saved',
      ingredients: []
    }
  };

  function escapeHtml(str) {
    return String(str || '').replace(/[&<>"']/g, c => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    }[c]));
  }

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

    if (subtabName === 'catalog' && (!state.catalogDishes || !state.catalogDishes.length)) {
      if (window.FoodCatalog && typeof window.FoodCatalog.loadCatalog === 'function') {
        window.FoodCatalog.loadCatalog();
      }
    }
    if (subtabName === 'suggestions') {
      if (window.FoodSuggestions && typeof window.FoodSuggestions.loadSmartSuggestions === 'function') {
        window.FoodSuggestions.loadSmartSuggestions(state.suggestionsTag || 'all');
      }
    }
    if (subtabName === 'composer') {
      if (window.FoodComposer && typeof window.FoodComposer.populateComposerUI === 'function') {
        window.FoodComposer.populateComposerUI();
      }
    }
  }

  return {
    state,
    escapeHtml,
    switchSubtab
  };
})();

window.FoodCore = FoodCore;
