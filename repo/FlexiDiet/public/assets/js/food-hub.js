/**
 * FLEXIDIET - FOOD HUB FACADE & ENTRY POINT (food-hub.js)
 * Clean Modular Architecture Facade: Aggregates submodules from assets/js/food/
 * (food-core, food-scanner, food-catalog, food-composer, food-suggestions)
 * and maintains 100% backward compatibility for all HTML inline onclicks and app.js calls.
 */

const FoodHub = (() => {
  function init() {
    // 1. Initialize event listeners in submodules
    if (window.FoodCatalog && typeof window.FoodCatalog.initCatalogListeners === 'function') {
      window.FoodCatalog.initCatalogListeners();
    }
    if (window.FoodComposer && typeof window.FoodComposer.initComposerListeners === 'function') {
      window.FoodComposer.initComposerListeners();
    }

    // Escape key closes unrecognized modal
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && window.FoodScanner && typeof window.FoodScanner.closeUnrecognizedModal === 'function') {
        window.FoodScanner.closeUnrecognizedModal();
      }
    });

    // 2. Default loads once page view is mounted
    if (window.FoodCatalog && typeof window.FoodCatalog.loadCatalog === 'function') {
      window.FoodCatalog.loadCatalog();
    }
    if (window.FoodSuggestions && typeof window.FoodSuggestions.loadSmartSuggestions === 'function') {
      window.FoodSuggestions.loadSmartSuggestions('all');
    }
    if (window.FoodComposer && typeof window.FoodComposer.populateComposerUI === 'function') {
      window.FoodComposer.populateComposerUI();
    }
  }

  return {
    init,
    // Core & Subtab Routing
    get state() { return window.FoodCore ? window.FoodCore.state : {}; },
    switchSubtab: (name) => window.FoodCore?.switchSubtab(name),

    // Subtab 1: AI Scanner & Vision
    loadPreset: (key) => window.FoodScanner?.loadPreset(key),
    handleImageUpload: (e) => window.FoodScanner?.handleImageUpload(e),
    onResultGoComposer: () => window.FoodScanner?.onResultGoComposer(),
    selectAlternativePrediction: (code) => window.FoodScanner?.selectAlternativePrediction(code),
    resetScanner: () => window.FoodScanner?.resetScanner(),
    closeUnrecognizedModal: () => window.FoodScanner?.closeUnrecognizedModal(),
    retryScannerImage: () => window.FoodScanner?.retryScannerImage(),
    goToManualComposer: () => window.FoodScanner?.goToManualComposer(),
    triggerPromptAnalysis: () => window.FoodScanner?.triggerPromptAnalysis(),

    // Subtab 2: Catalog
    loadCatalog: (q) => window.FoodCatalog?.loadCatalog(q),
    filterCatalogOrigin: (origin) => window.FoodCatalog?.filterCatalogOrigin(origin),
    deleteDishFromCatalog: (id) => window.FoodCatalog?.deleteDishFromCatalog(id),

    // Subtab 3: Composer Engine
    loadDishToComposer: (id, meal) => window.FoodComposer?.loadDishToComposer(id, meal),
    loadDishByName: (name, meal) => window.FoodComposer?.loadDishByName(name, meal),
    startNewCustomDish: () => window.FoodComposer?.startNewCustomDish(),
    handleSliderChange: (idx, val) => window.FoodComposer?.handleSliderChange(idx, val),
    handleGramInputChange: (idx, val) => window.FoodComposer?.handleGramInputChange(idx, val),
    removeComposerIngredient: (idx) => window.FoodComposer?.removeComposerIngredient(idx),
    toggleAddIngredientPanel: (force) => window.FoodComposer?.toggleAddIngredientPanel(force),
    submitComposerToMealLog: () => window.FoodComposer?.submitComposerToMealLog(),
    submitComposerAsMyDish: () => window.FoodComposer?.submitComposerAsMyDish(),
    deleteCurrentCustomDish: () => window.FoodComposer?.deleteCurrentCustomDish(),
    resetComposer: () => window.FoodComposer?.resetComposer(),
    handleComposerAvatarChange: (e) => window.FoodComposer?.handleComposerAvatarChange(e),
    removeComposerAvatar: () => window.FoodComposer?.removeComposerAvatar(),

    // Subtab 4: Smart Suggestions
    loadSmartSuggestions: (tag, meal, origin) => window.FoodSuggestions?.loadSmartSuggestions(tag, meal, origin),
    saveSuggestedMeal: (id, name) => window.FoodSuggestions?.saveSuggestedMeal(id, name),
    applyMultiFilter: () => window.FoodSuggestions?.applyMultiFilter(),
    resetMultiFilter: () => window.FoodSuggestions?.resetMultiFilter()
  };
})();

window.FoodHub = FoodHub;

// Global proxies for inline HTML onclick handlers in pages/food/index.html
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
function handleComposerAvatarChange(e) { FoodHub.handleComposerAvatarChange(e); }
function removeComposerAvatar() { FoodHub.removeComposerAvatar(); }
function deleteCurrentCustomDish() { FoodHub.deleteCurrentCustomDish(); }
function deleteDishFromCatalog(dishId) { FoodHub.deleteDishFromCatalog(dishId); }
function resetComposer() { FoodHub.resetComposer(); }

// Lifecycle bootstrap: Initialize when template pages are mounted into DOM
document.addEventListener('flexidiet:pages-ready', () => {
  FoodHub.init();
});
