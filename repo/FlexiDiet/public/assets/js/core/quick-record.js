/**
 * FLEXIDIET - Core Quick Record Modal
 * Quản lý popup "+ Ghi lại hoạt động" (#createQuickModal), tìm kiếm món ăn fuzzy,
 * tính toán calo theo khẩu phần, chuyển món sang Composer và gửi dữ liệu nhanh.
 */

let qfDishesCache = [];
let qfSelectedDish = null;

function removeVietnameseTones(str) {
  if (!str) return '';
  return str
    .toLowerCase()
    .replace(/[àáạảãâầấậẩẫăằắặẳẵ]/g, 'a')
    .replace(/[èéẹẻẽêềếệểễ]/g, 'e')
    .replace(/[ìíịỉĩ]/g, 'i')
    .replace(/[òóọỏõôồốộổỗơờớợởỡ]/g, 'o')
    .replace(/[ùúụủũưừứựửữ]/g, 'u')
    .replace(/[ỳýỵỷỹ]/g, 'y')
    .replace(/[đ]/g, 'd')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');
}

const QUICK_FOOD_ALIASES = {
  'pho': ['pho', 'phở', 'phở bò', 'pho bo', 'phở bò tái', 'pho bo tai', 'phở tái', 'pho tai', 'bánh phở', 'banh pho', 'phở gà', 'pho ga', 'phở tái nạm', 'pho tai nam'],
  'banh_mi': ['bánh mì', 'banh mi', 'bánh mì thịt', 'banh mi thit', 'bánh mì patê', 'banh mi pate', 'bánh mì trứng', 'banh mi trung'],
  'com': ['cơm', 'com', 'cơm trắng', 'com trang'],
  'bun': ['bún', 'bun', 'bún tươi', 'bun tuoi'],
  'cha': ['chả lụa', 'cha lua', 'giò lụa', 'gio lua'],
  'cha_gio': ['nem rán', 'nem ran', 'chả giò', 'cha gio'],
  'thit_kho': ['thịt kho tàu', 'thit kho tau', 'thịt kho hột vịt', 'thit kho hot vit'],
  'dau_hu': ['đậu phụ', 'dau phu', 'đậu hũ', 'dau hu'],
  'com_tam': ['cơm sườn', 'com suon', 'cơm tấm sườn', 'com tam suon']
};

function scoreQuickFoodMatch(dish, cleanQ, rawQ) {
  if (!cleanQ) return 100;
  const name = (dish.name || '').trim();
  const rawLower = name.toLowerCase();
  const cleanName = removeVietnameseTones(name);
  const dishCode = (dish.dish_code || '').toLowerCase();
  const nameNorm = (dish.name_norm || '').toLowerCase();

  // 1. Exact match
  if (rawLower === rawQ.toLowerCase() || cleanName === cleanQ) return 1000;
  if (dishCode === cleanQ || nameNorm === cleanQ) return 950;

  // 2. Starts with query
  if (rawLower.startsWith(rawQ.toLowerCase()) || cleanName.startsWith(cleanQ)) return 850;
  if (dishCode.startsWith(cleanQ) || nameNorm.startsWith(cleanQ)) return 800;

  // 3. Contains query
  if (rawLower.includes(rawQ.toLowerCase()) || cleanName.includes(cleanQ)) return 700;
  if (dishCode.includes(cleanQ) || nameNorm.includes(cleanQ)) return 650;

  // 4. Aliases
  const aliases = QUICK_FOOD_ALIASES[dishCode] || [];
  for (const alias of aliases) {
    const cleanAlias = removeVietnameseTones(alias);
    if (cleanAlias === cleanQ) return 920;
    if (cleanAlias.startsWith(cleanQ)) return 780;
    if (cleanAlias.includes(cleanQ) || cleanQ.includes(cleanAlias)) return 600;
  }

  // 5. Query contains dish name as standalone word
  const escapedName = cleanName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const wordBoundary = new RegExp('(?:^|\\s)' + escapedName + '(?:\\s|$)', 'i');
  if (cleanName.length >= 3 && wordBoundary.test(cleanQ)) return 550;

  // 6. Word-level token match
  const queryWords = cleanQ.split(/\s+/).filter(w => w.length > 1);
  if (queryWords.length > 1) {
    const combinedTokens = cleanName + ' ' + aliases.join(' ') + ' ' + (dish.serving_label || '');
    const cleanTokens = removeVietnameseTones(combinedTokens);
    if (queryWords.every(w => cleanTokens.includes(w))) return 500;
  }

  return 0;
}

function escapeQuickHtml(s) {
  return String(s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

async function loadQuickFoodDishesCache() {
  if (qfDishesCache.length > 0) return qfDishesCache;
  try {
    const list = await FlexiAPI.request('/foods');
    if (Array.isArray(list)) {
      qfDishesCache = list;
    }
  } catch (err) {
    console.warn('Could not fetch /foods for quick log:', err);
  }
  return qfDishesCache;
}

function refreshQuickFoodDishesCache() {
  qfDishesCache = [];
}

function openCreateQuickModalWithDish(dish, defaultMeal = null) {
  if (!dish) return;
  switchCreateTab('food');
  const modal = document.getElementById('createQuickModal');
  if (modal) {
    modal.classList.add('open');
    if (defaultMeal && document.getElementById('qfMeal')) {
      document.getElementById('qfMeal').value = defaultMeal;
    }
  }

  const portionSelect = document.getElementById('qfPortionRatio');
  if (portionSelect) portionSelect.value = '1';

  const normalizedDish = {
    id: Number(dish.id || dish.dish_id),
    name: dish.name,
    origin: dish.origin || 'system',
    serving_label: dish.serving_label || '1 phần',
    kcal: Number(dish.kcal ?? dish.calories ?? 0),
    protein_g: Number(dish.protein_g ?? dish.protein ?? 0),
    carb_g: Number(dish.carb_g ?? dish.carbs ?? 0),
    fat_g: Number(dish.fat_g ?? dish.fat ?? 0)
  };

  selectQuickFoodDish(normalizedDish);
  setTimeout(() => {
    document.getElementById('qfPortionRatio')?.focus();
  }, 100);
}

function openCreateQuickModal(defaultMeal, type = 'food') {
  switchCreateTab(type);
  const modal = document.getElementById('createQuickModal');
  if (modal) {
    modal.classList.add('open');
    if (defaultMeal && document.getElementById('qfMeal')) {
      document.getElementById('qfMeal').value = defaultMeal;
    }
  }
  if (type === 'food') {
    loadQuickFoodDishesCache();
    clearQuickFoodSelection();
    setTimeout(() => {
      document.getElementById('qfDishSearchInput')?.focus();
    }, 150);
  }
}

function closeCreateQuickModal() {
  const modal = document.getElementById('createQuickModal');
  if (modal) {
    modal.classList.remove('open');
  }
  const dropdown = document.getElementById('qfDishDropdown');
  if (dropdown) dropdown.style.display = 'none';
  clearQuickFoodSelection();
}

function handleCreateModalBackdrop(e) {
  if (e.target.id === 'createQuickModal') {
    closeCreateQuickModal();
  }
}

function switchCreateTab(type) {
  const btnFood = document.getElementById('ctTabFood');
  const btnWorkout = document.getElementById('ctTabWorkout');
  const formFood = document.getElementById('quickFoodForm');
  const formWorkout = document.getElementById('quickWorkoutForm');

  if (type === 'food') {
    btnFood?.classList.add('active');
    btnWorkout?.classList.remove('active');
    if (formFood) formFood.style.display = 'block';
    if (formWorkout) formWorkout.style.display = 'none';
    loadQuickFoodDishesCache();
  } else {
    btnWorkout?.classList.add('active');
    btnFood?.classList.remove('active');
    if (formWorkout) formWorkout.style.display = 'block';
    if (formFood) formFood.style.display = 'none';
  }
}

async function handleQuickFoodSearch(e) {
  const query = (e?.target?.value || '').trim();
  const dropdown = document.getElementById('qfDishDropdown');
  if (!dropdown) return;

  const dishes = await loadQuickFoodDishesCache();
  if (!dishes.length) {
    dropdown.innerHTML = '<div class="qf-dd-empty"><i class="fa-solid fa-spinner fa-spin"></i> Đang tải danh sách món ăn...</div>';
    dropdown.style.display = 'block';
    return;
  }

  let matches = dishes;
  if (query) {
    const cleanQ = removeVietnameseTones(query);
    matches = dishes
      .map(d => ({ dish: d, score: scoreQuickFoodMatch(d, cleanQ, query) }))
      .filter(x => x.score > 0)
      .sort((a, b) => {
        if (b.score !== a.score) return b.score - a.score;
        const aIsCustom = a.dish.origin !== 'system' ? 1 : 0;
        const bIsCustom = b.dish.origin !== 'system' ? 1 : 0;
        return bIsCustom - aIsCustom;
      })
      .map(x => x.dish);
  }

  const displayList = matches.slice(0, 15);

  if (!displayList.length) {
    dropdown.innerHTML = `<div class="qf-dd-empty">
      <i class="fa-solid fa-circle-info"></i> Không tìm thấy món "${escapeQuickHtml(query)}".
      <br><small>Bạn có thể mở <strong>Ăn uống (Food Hub)</strong> để tạo món mới!</small>
    </div>`;
    dropdown.style.display = 'block';
    return;
  }

  dropdown.replaceChildren();
  for (const dish of displayList) {
    const item = document.createElement('div');
    item.className = 'qf-dd-item';
    const isSaved = dish.origin === 'saved';
    const badgeText = isSaved ? 'Món Của Tôi' : '67 Món Chuẩn';
    const badgeClass = isSaved ? 'saved' : 'system';

    item.innerHTML = `
      <div class="qf-dd-title">
        <span>${escapeQuickHtml(dish.name)}</span>
        <span class="fh-dc-badge ${badgeClass}" style="font-size:10px; padding:1px 5px;">${badgeText}</span>
      </div>
      <div class="qf-dd-meta">
        <span><i class="fa-solid fa-fire"></i> ${Math.round(dish.kcal)} kcal</span>
        <small>(${escapeQuickHtml(dish.serving_label || '1 phần')})</small>
      </div>
    `;
    item.addEventListener('click', () => selectQuickFoodDish(dish));
    dropdown.appendChild(item);
  }
  dropdown.style.display = 'block';
}

function selectQuickFoodDish(dish) {
  qfSelectedDish = dish;
  const input = document.getElementById('qfDishSearchInput');
  const dropdown = document.getElementById('qfDishDropdown');
  const preview = document.getElementById('qfSelectedCard');
  const clearBtn = document.getElementById('qfSearchClearBtn');
  const redirectRow = document.getElementById('qfComposerRedirectRow');
  const submitBtn = document.getElementById('btnSubmitQuickFood');

  if (input) input.value = dish.name;
  if (dropdown) dropdown.style.display = 'none';
  if (clearBtn) clearBtn.style.display = 'block';

  const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = val; };
  setVal('qfSelectedDishId', dish.id);
  setVal('qfSelectedDishName', dish.name);
  setVal('qfBaseKcal', dish.kcal);
  setVal('qfBaseProt', dish.protein_g);
  setVal('qfBaseCarb', dish.carb_g);
  setVal('qfBaseFat', dish.fat_g);

  const titleEl = document.getElementById('qfPreviewTitle');
  const badgeEl = document.getElementById('qfPreviewBadge');
  const servingEl = document.getElementById('qfPreviewServing');

  if (titleEl) titleEl.textContent = dish.name;
  if (badgeEl) {
    const isSaved = dish.origin === 'saved';
    badgeEl.textContent = isSaved ? 'Món Của Tôi' : '67 Món Chuẩn';
    badgeEl.className = 'fh-dc-badge ' + (isSaved ? 'saved' : 'system');
  }
  if (servingEl) servingEl.innerHTML = `<i class="fa-solid fa-utensils"></i> Khẩu phần chuẩn: <strong>${escapeQuickHtml(dish.serving_label || '1 phần')}</strong>`;

  updateQuickFoodPortion();

  if (preview) preview.style.display = 'flex';
  if (redirectRow) redirectRow.style.display = 'flex';
  if (submitBtn) submitBtn.disabled = false;
}

function updateQuickFoodPortion() {
  if (!qfSelectedDish) return;
  const ratio = parseFloat(document.getElementById('qfPortionRatio')?.value) || 1.0;
  const baseKcal = parseFloat(document.getElementById('qfBaseKcal')?.value) || qfSelectedDish.kcal;
  const baseProt = parseFloat(document.getElementById('qfBaseProt')?.value) || qfSelectedDish.protein_g;
  const baseCarb = parseFloat(document.getElementById('qfBaseCarb')?.value) || qfSelectedDish.carb_g;
  const baseFat = parseFloat(document.getElementById('qfBaseFat')?.value) || qfSelectedDish.fat_g;

  const kcalEl = document.getElementById('qfPreviewKcal');
  const protEl = document.getElementById('qfPreviewProt');
  const carbEl = document.getElementById('qfPreviewCarb');
  const fatEl = document.getElementById('qfPreviewFat');

  if (kcalEl) kcalEl.textContent = Math.round(baseKcal * ratio);
  if (protEl) protEl.textContent = (baseProt * ratio).toFixed(1);
  if (carbEl) carbEl.textContent = (baseCarb * ratio).toFixed(1);
  if (fatEl) fatEl.textContent = (baseFat * ratio).toFixed(1);
}

function clearQuickFoodSelection(autoFocus = false) {
  qfSelectedDish = null;
  const input = document.getElementById('qfDishSearchInput');
  const clearBtn = document.getElementById('qfSearchClearBtn');
  const preview = document.getElementById('qfSelectedCard');
  const redirectRow = document.getElementById('qfComposerRedirectRow');
  const submitBtn = document.getElementById('btnSubmitQuickFood');

  if (input) {
    input.value = '';
    if (autoFocus) input.focus();
  }
  if (clearBtn) clearBtn.style.display = 'none';
  if (preview) preview.style.display = 'none';
  if (redirectRow) redirectRow.style.display = 'none';
  if (submitBtn) submitBtn.disabled = true;

  const idEl = document.getElementById('qfSelectedDishId');
  if (idEl) idEl.value = '';
  const nameEl = document.getElementById('qfSelectedDishName');
  if (nameEl) nameEl.value = '';
}

async function openSelectedDishInComposer() {
  const targetDishId = document.getElementById('qfSelectedDishId')?.value || (qfSelectedDish ? qfSelectedDish.id : null);
  const targetDishName = document.getElementById('qfSelectedDishName')?.value || (qfSelectedDish ? qfSelectedDish.name : null) || document.getElementById('qfDishSearchInput')?.value?.trim();
  const meal = document.getElementById('qfMeal')?.value || 'lunch';

  closeCreateQuickModal();
  if (typeof switchTab === 'function') {
    switchTab('food');
  }
  if (typeof switchFoodSubtab === 'function') {
    switchFoodSubtab('composer');
  } else if (window.FoodHub && typeof window.FoodHub.switchSubtab === 'function') {
    window.FoodHub.switchSubtab('composer');
  }

  if (targetDishId && window.FoodHub && typeof window.FoodHub.loadDishToComposer === 'function') {
    await window.FoodHub.loadDishToComposer(Number(targetDishId), meal);
  } else if (targetDishName && window.FoodHub && typeof window.FoodHub.loadDishByName === 'function') {
    await window.FoodHub.loadDishByName(targetDishName, meal);
  }

  setTimeout(() => {
    const composerBox = document.getElementById('paneFhComposer');
    if (composerBox) {
      composerBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }, 100);
}

// Đóng dropdown khi click ra ngoài ô tìm kiếm
document.addEventListener('click', (e) => {
  const dd = document.getElementById('qfDishDropdown');
  const input = document.getElementById('qfDishSearchInput');
  if (dd && input && !input.contains(e.target) && !dd.contains(e.target)) {
    dd.style.display = 'none';
  }
});

async function handleQuickFoodSubmit(e) {
  e.preventDefault();
  if (!qfSelectedDish) {
    if (typeof showToast === 'function') showToast('Vui lòng tìm và chọn món ăn từ danh sách!', 'warning');
    return;
  }

  const meal = document.getElementById('qfMeal')?.value || 'lunch';
  const ratio = parseFloat(document.getElementById('qfPortionRatio')?.value) || 1.0;
  const dishId = Number(document.getElementById('qfSelectedDishId')?.value) || qfSelectedDish.id;
  const name = qfSelectedDish.name;

  const baseKcal = parseFloat(document.getElementById('qfBaseKcal')?.value) || qfSelectedDish.kcal;
  const baseProt = parseFloat(document.getElementById('qfBaseProt')?.value) || qfSelectedDish.protein_g;
  const baseCarb = parseFloat(document.getElementById('qfBaseCarb')?.value) || qfSelectedDish.carb_g;
  const baseFat = parseFloat(document.getElementById('qfBaseFat')?.value) || qfSelectedDish.fat_g;

  const finalCal = Math.round(baseKcal * ratio);
  const finalProt = Math.round(baseProt * ratio * 10) / 10;
  const finalCarb = Math.round(baseCarb * ratio * 10) / 10;
  const finalFat = Math.round(baseFat * ratio * 10) / 10;

  const submitBtn = document.getElementById('btnSubmitQuickFood');
  if (submitBtn) submitBtn.disabled = true;

  try {
    await FlexiAPI.request('/meals', {
      method: 'POST',
      data: {
        dish_id: dishId,
        meal_type: meal,
        serving_factor: ratio
      }
    });

    if (window.appState) {
      const dayKey = typeof localDayKey === 'function' ? localDayKey() : new Date().toISOString().slice(0, 10);
      window.appState.foodLog.unshift({
        name,
        meal,
        cal: finalCal,
        prot: finalProt,
        carb: finalCarb,
        fat: finalFat,
        time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
        date: dayKey
      });
      window.appState.energy.consumed += finalCal;
      window.appState.macros.protein += finalProt;
      window.appState.macros.carbs += finalCarb;
      window.appState.macros.fat += finalFat;
    }

    if (typeof renderDashboard === 'function') renderDashboard();
    if (typeof updateEnergyDisplay === 'function') updateEnergyDisplay();
    if (typeof fdRefreshDashboard === 'function') fdRefreshDashboard();

    closeCreateQuickModal();
    const mealLabel = (typeof fdLabels !== 'undefined' && fdLabels[meal]) ? fdLabels[meal] : meal;
    if (typeof showToast === 'function') {
      showToast(`Đã ghi nhận món "${name}" (${finalCal} kcal) vào bữa ${mealLabel}!`, 'success');
    }
  } catch (err) {
    console.warn('Backend /meals save warning:', err);
    if (window.appState) {
      const dayKey = typeof localDayKey === 'function' ? localDayKey() : new Date().toISOString().slice(0, 10);
      window.appState.foodLog.unshift({
        name,
        meal,
        cal: finalCal,
        prot: finalProt,
        carb: finalCarb,
        fat: finalFat,
        time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
        date: dayKey
      });
      window.appState.energy.consumed += finalCal;
    }
    if (typeof renderDashboard === 'function') renderDashboard();
    closeCreateQuickModal();
    const mealLabel = (typeof fdLabels !== 'undefined' && fdLabels[meal]) ? fdLabels[meal] : meal;
    if (typeof showToast === 'function') {
      showToast(`Đã thêm món "${name}" (${finalCal} kcal) vào bữa ${mealLabel}!`, 'success');
    }
  } finally {
    if (submitBtn) submitBtn.disabled = false;
  }
}

function handleQuickWorkoutSubmit(e) {
  e.preventDefault();
  const sport = document.getElementById('qwSport').value;
  const duration = parseInt(document.getElementById('qwDuration').value) || 0;
  const burned = parseInt(document.getElementById('qwBurned').value) || 0;

  if (window.appState) {
    const dayKey = typeof localDayKey === 'function' ? localDayKey() : new Date().toISOString().slice(0, 10);
    window.appState.workoutLog.unshift({
      name: sport,
      minutes: duration,
      burned,
      time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
      date: dayKey
    });
    window.appState.energy.burned += burned;
  }
  if (typeof updateEnergyDisplay === 'function') updateEnergyDisplay();

  closeCreateQuickModal();
  if (typeof showToast === 'function') {
    showToast(`Đã ghi nhận buổi tập "${sport}" (+${burned} kcal)!`, 'success');
  }
  if (typeof switchTab === 'function') switchTab('dashboard');
}

// Xuất ra window cho các sự kiện HTML và các script khác
window.openCreateQuickModal = openCreateQuickModal;
window.openCreateQuickModalWithDish = openCreateQuickModalWithDish;
window.closeCreateQuickModal = closeCreateQuickModal;
window.handleCreateModalBackdrop = handleCreateModalBackdrop;
window.switchCreateTab = switchCreateTab;
window.handleQuickFoodSearch = handleQuickFoodSearch;
window.selectQuickFoodDish = selectQuickFoodDish;
window.updateQuickFoodPortion = updateQuickFoodPortion;
window.clearQuickFoodSelection = clearQuickFoodSelection;
window.openSelectedDishInComposer = openSelectedDishInComposer;
window.handleQuickFoodSubmit = handleQuickFoodSubmit;
window.handleQuickWorkoutSubmit = handleQuickWorkoutSubmit;
window.refreshQuickFoodDishesCache = refreshQuickFoodDishesCache;

