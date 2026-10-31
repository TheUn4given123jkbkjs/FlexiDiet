/**
 * FLEXIDIET - APP (Board, Food/Workout Log, AI Scanner, Charts, Onboarding, Profile)
 */

// Calendar dates use the browser's LOCAL day (not UTC), avoiding midnight shifts.
function localDayKey(date = new Date()) {
  const pad = value => String(value).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

// Khởi tạo thông tin người dùng từ phiên đăng nhập
let initialUser = null;
try { initialUser = JSON.parse(localStorage.getItem('flexidiet-user')); } catch (e) {}

// Application State
const appState = {
  activeTab: 'dashboard',
  currentStep: 1,
  user: {
    name: (initialUser && initialUser.name) || 'Nguyễn Minh Anh',
    email: (initialUser && initialUser.email) || 'minhanh@gmail.com',
    role: (initialUser && initialUser.role) || 'member',
    gender: 'male',
    age: 24,
    height: 172,
    weight: 68.5,
    pal: 1.375, // Physical Activity Level
    goal: 'lose',
    calorieOffset: -500,
    targetWeight: 63.0,
    bmr: 1640,
    tdee: 2255,
    targetCalories: 1850
  },
  energy: {
    target: 1850,
    consumed: 1240,
    burned: 340,
    remaining: 950,
    water: 1.75,
    waterTarget: 2.5,
    steps: 7850
  },
  foodLog: [
    {name:'Phở bò tái nạm',meal:'breakfast',cal:485,prot:32.5,carb:68,fat:11.2,time:'07:30'},
    {name:'Cơm tấm sườn bì chả',meal:'lunch',cal:650,prot:34,carb:82,fat:21.5,time:'12:15'},
    {name:'Táo Gala & hạnh nhân',meal:'snack',cal:105,prot:2,carb:24,fat:1,time:'15:30'}
  ],
  workoutLog: [{name:'Chạy bộ ngoài trời',minutes:30,burned:290,time:'06:30',date:localDayKey()},{name:'Đi bộ nhẹ',minutes:15,burned:50,time:'17:00',date:localDayKey()}],
  macros: {protein:94,carbs:145,fat:36,targets:{protein:135,carbs:205,fat:52}},
  foodPresets: {
    pho_bo: {
      name: 'Phở Bò Tái Nạm (Bát Vừa)',
      img: 'https://images.unsplash.com/photo-1582878826629-29b7ad1cdc43?w=500&auto=format&fit=crop&q=80',
      weight: 450,
      cal: 485,
      prot: 32.5,
      carb: 68.0,
      fat: 11.2,
      conf: '98.5%'
    },
    com_tam: {
      name: 'Cơm Tấm Sườn Bì Chả Trứng',
      img: 'https://images.unsplash.com/photo-1544025162-d76694265947?w=500&auto=format&fit=crop&q=80',
      weight: 420,
      cal: 650,
      prot: 34.0,
      carb: 82.0,
      fat: 21.5,
      conf: '97.8%'
    },
    bun_cha: {
      name: 'Bún Chả Hà Nội Nướng Than Hoa',
      img: 'https://images.unsplash.com/photo-1559847844-5315695dadae?w=500&auto=format&fit=crop&q=80',
      weight: 380,
      cal: 510,
      prot: 28.0,
      carb: 64.0,
      fat: 16.0,
      conf: '96.9%'
    },
    banh_mi: {
      name: 'Bánh Mì Thịt Nướng Xá Xíu',
      img: 'https://images.unsplash.com/photo-1626804475297-41608ea09aeb?w=500&auto=format&fit=crop&q=80',
      weight: 220,
      cal: 430,
      prot: 21.0,
      carb: 54.0,
      fat: 14.5,
      conf: '99.1%'
    },
    salad_ga: {
      name: 'Salad Ức Gà Áp Chảo Rau Củ Healthy',
      img: 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=500&auto=format&fit=crop&q=80',
      weight: 300,
      cal: 340,
      prot: 38.0,
      carb: 14.0,
      fat: 8.0,
      conf: '99.4%'
    },
    goi_cuon: {
      name: 'Gỏi Cuốn Tôm Thịt Nước Tương Đậu Phộng',
      img: 'https://images.unsplash.com/photo-1534422298391-e4f8c172dddb?w=500&auto=format&fit=crop&q=80',
      weight: 250,
      cal: 280,
      prot: 22.0,
      carb: 34.0,
      fat: 6.0,
      conf: '97.2%'
    }
  }
};

let currentScannedBase = null;

// ==============================================================================
// 1. NAVIGATION TAB ROUTER (SWITCHING PAGES ON 1 MAIN PAGE)
// ==============================================================================
function setMobileSidebarOpen(open) {
  const sidebar = document.getElementById('appSidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  const toggle = document.getElementById('sidebarToggle');
  if (!sidebar || !backdrop) return;
  const isOpen = Boolean(open && window.innerWidth <= 820);
  sidebar.classList.toggle('mobile-open', isOpen);
  backdrop.classList.toggle('show', isOpen);
  document.body.classList.toggle('fd-drawer-open', isOpen);
  sidebar.setAttribute('aria-hidden', isOpen ? 'false' : (window.innerWidth <= 820 ? 'true' : 'false'));
  if (toggle) toggle.setAttribute('aria-expanded', String(isOpen));
}
function toggleMobileSidebar() {
  setMobileSidebarOpen(!document.getElementById('appSidebar')?.classList.contains('mobile-open'));
}
function closeMobileSidebar() {
  setMobileSidebarOpen(false);
}
window.addEventListener('resize', () => {
  if (window.innerWidth > 820) closeMobileSidebar();
});
document.addEventListener('keydown', event => {
  if (event.key === 'Escape' && document.getElementById('appSidebar')?.classList.contains('mobile-open')) {
    closeMobileSidebar();
    document.getElementById('sidebarToggle')?.focus();
  }
});

function switchTab(tabId) {
  closeMobileSidebar();
  // Legacy aliases
  if (tabId === 'food-log') tabId = 'food';
  if (tabId === 'workout-log') tabId = 'workout';
  if (tabId === 'list') tabId = 'history';
  if (!document.getElementById('view-' + tabId)) tabId = 'dashboard';
  appState.activeTab = tabId;
  document.querySelectorAll('.app-nav-link').forEach(link => {
    const active = link.dataset.target === tabId;
    link.classList.toggle('active', active);
    if (active) link.setAttribute('aria-current', 'page');
    else link.removeAttribute('aria-current');
  });
  document.querySelectorAll('.page-view').forEach(view => {
    view.classList.toggle('active', view.id === `view-${tabId}`);
  });
  const pageNames = {
    'dashboard':'Tổng quan', 'enews':'eNews', 'enews-profile':'Hồ sơ eNews',
    'food':'Ăn uống (Food Hub)', 'workout':'Luyện tập',
    'history':'Nhật ký chi tiết', 'profile':'Hồ sơ cá nhân'
  };
  const label = document.getElementById('currentPageLabel');
  if (label) label.textContent = pageNames[tabId] || 'Tổng quan';
  if (tabId === 'dashboard' && typeof renderDashboard === 'function') renderDashboard();
  if (tabId === 'history' && typeof renderActivityList === 'function') renderActivityList();
  if (tabId === 'profile' && typeof fdLoadProfileInsights === 'function') fdLoadProfileInsights();
  if (location.hash !== `#${tabId}`) history.pushState(null, '', `#${tabId}`);
  window.scrollTo({ top: 0, behavior: 'instant' });
}

function navigateToWorkoutLogging() {
  switchTab('workout');
  setTimeout(() => {
    const form = document.getElementById('workoutForm');
    if (form) {
      form.scrollIntoView({ behavior: 'smooth', block: 'center' });
      document.getElementById('sportSelect')?.focus();
    }
  }, 120);
}

// ==============================================================================
// 2. QUICK CREATE MODAL (+ CREATE BUTTON)
// ==============================================================================
// Quick Food Autocomplete State & Helpers
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

window.refreshQuickFoodDishesCache = () => {
  qfDishesCache = [];
};

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
window.openCreateQuickModalWithDish = openCreateQuickModalWithDish;

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
    if (!qfSelectedDish) {
      clearQuickFoodSelection();
    }
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
    btnFood.classList.add('active');
    btnWorkout.classList.remove('active');
    formFood.style.display = 'block';
    formWorkout.style.display = 'none';
    loadQuickFoodDishesCache();
  } else {
    btnWorkout.classList.add('active');
    btnFood.classList.remove('active');
    formWorkout.style.display = 'block';
    formFood.style.display = 'none';
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
      .sort((a, b) => b.score - a.score)
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

  document.getElementById('qfSelectedDishId').value = dish.id;
  document.getElementById('qfSelectedDishName').value = dish.name;
  document.getElementById('qfBaseKcal').value = dish.kcal;
  document.getElementById('qfBaseProt').value = dish.protein_g;
  document.getElementById('qfBaseCarb').value = dish.carb_g;
  document.getElementById('qfBaseFat').value = dish.fat_g;

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

function clearQuickFoodSelection() {
  qfSelectedDish = null;
  const input = document.getElementById('qfDishSearchInput');
  const clearBtn = document.getElementById('qfSearchClearBtn');
  const preview = document.getElementById('qfSelectedCard');
  const redirectRow = document.getElementById('qfComposerRedirectRow');
  const submitBtn = document.getElementById('btnSubmitQuickFood');

  if (input) { input.value = ''; input.focus(); }
  if (clearBtn) clearBtn.style.display = 'none';
  if (preview) preview.style.display = 'none';
  if (redirectRow) redirectRow.style.display = 'none';
  if (submitBtn) submitBtn.disabled = true;

  document.getElementById('qfSelectedDishId').value = '';
  document.getElementById('qfSelectedDishName').value = '';
}

async function openSelectedDishInComposer() {
  const dishId = document.getElementById('qfSelectedDishId')?.value || (qfSelectedDish ? qfSelectedDish.id : null);
  const dishName = document.getElementById('qfSelectedDishName')?.value || (qfSelectedDish ? qfSelectedDish.name : null) || document.getElementById('qfDishSearchInput')?.value?.trim();
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

  if (dishId && window.FoodHub && typeof window.FoodHub.loadDishToComposer === 'function') {
    await window.FoodHub.loadDishToComposer(Number(dishId), meal);
  } else if (dishName && window.FoodHub && typeof window.FoodHub.loadDishByName === 'function') {
    await window.FoodHub.loadDishByName(dishName, meal);
  }

  setTimeout(() => {
    const composerBox = document.getElementById('paneFhComposer');
    if (composerBox) {
      composerBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }, 100);
}
window.openSelectedDishInComposer = openSelectedDishInComposer;

// Close dropdown on outside click
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
    showToast('Vui lòng tìm và chọn món ăn từ danh sách!', 'warning');
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

    appState.foodLog.unshift({
      name,
      meal,
      cal: finalCal,
      prot: finalProt,
      carb: finalCarb,
      fat: finalFat,
      time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
      date: localDayKey()
    });
    appState.energy.consumed += finalCal;
    appState.macros.protein += finalProt;
    appState.macros.carbs += finalCarb;
    appState.macros.fat += finalFat;

    if (typeof renderDashboard === 'function') renderDashboard();
    if (typeof updateEnergyDisplay === 'function') updateEnergyDisplay();
    if (typeof fdRefreshDashboard === 'function') fdRefreshDashboard();

    closeCreateQuickModal();
    const mealLabel = (typeof fdLabels !== 'undefined' && fdLabels[meal]) ? fdLabels[meal] : meal;
    showToast(`Đã ghi nhận món "${name}" (${finalCal} kcal) vào bữa ${mealLabel}!`, 'success');
  } catch (err) {
    console.warn('Backend /meals save warning:', err);
    appState.foodLog.unshift({
      name,
      meal,
      cal: finalCal,
      prot: finalProt,
      carb: finalCarb,
      fat: finalFat,
      time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
      date: localDayKey()
    });
    appState.energy.consumed += finalCal;
    if (typeof renderDashboard === 'function') renderDashboard();
    closeCreateQuickModal();
    const mealLabel = (typeof fdLabels !== 'undefined' && fdLabels[meal]) ? fdLabels[meal] : meal;
    showToast(`Đã thêm món "${name}" (${finalCal} kcal) vào bữa ${mealLabel}!`, 'success');
  } finally {
    if (submitBtn) submitBtn.disabled = false;
  }
}

function handleQuickWorkoutSubmit(e) {
  e.preventDefault();
  const sport = document.getElementById('qwSport').value;
  const duration = parseInt(document.getElementById('qwDuration').value) || 0;
  const burned = parseInt(document.getElementById('qwBurned').value) || 0;

  appState.workoutLog.unshift({name:sport,minutes:duration,burned,time:new Date().toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'}),date:localDayKey()});
  appState.energy.burned += burned;
  updateEnergyDisplay();

  closeCreateQuickModal();
  showToast(`Đã ghi nhận buổi tập "${sport}" (+${burned} kcal)!`, 'success');
  switchTab('dashboard');
}

function addCardToKanban(name, meal, cal, prot, carb, fat) {
  const colContainer = document.getElementById(`cards-${meal}`);
  // Some meal types (e.g. snack) do not have a legacy Kanban column.
  // Food still must be saved in the shared demo state.

  const safeName = String(name).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const card = document.createElement('div');
  card.className = 'kanban-card';
  card.innerHTML = `
    <div class="card-title-row">
      <span class="card-item-title">${safeName}</span>
      <span class="card-key-tag">${meal.toUpperCase().slice(0, 3)}-${Math.floor(Math.random() * 90 + 10)}</span>
    </div>
    <p class="card-desc">Được thêm nhanh từ Quick Create Menu.</p>
    <div class="card-macros-pills">
      <span class="macro-mini cal"><i class="fa-solid fa-fire"></i> ${cal} kcal</span>
      <span class="macro-mini p">P: ${prot}g</span>
      <span class="macro-mini c">C: ${carb}g</span>
      <span class="macro-mini f">F: ${fat}g</span>
    </div>
    <div class="card-footer-meta">
      <div class="card-due-tag"><i class="fa-regular fa-clock"></i> Vừa xong</div>
      <div class="card-assignee-avatar orange"><i class="fa-solid fa-user"></i></div>
    </div>
  `;
  if (colContainer) colContainer.prepend(card);

  // Update Energy
  appState.foodLog.unshift({name,meal,cal,prot,carb,fat,time:new Date().toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'})});
  appState.macros.protein += prot;
  appState.macros.carbs += carb;
  appState.macros.fat += fat;
  appState.energy.consumed += cal;
  updateEnergyDisplay();

  // Update Column Count
  const countEl = document.getElementById(`count-${meal}`);
  if (countEl) {
    countEl.innerText = parseInt(countEl.innerText) + 1;
  }
}

// ==============================================================================
// 3. ENERGY & DASHBOARD RECALCULATION
// ==============================================================================
function updateEnergyDisplay() {
  appState.energy.remaining = appState.energy.target + appState.energy.burned - appState.energy.consumed;

  // Toolbar & Board Strips
  const elRemain = document.getElementById('boardRemainCal');
  const elTbRemain = document.getElementById('tbRemainingCal');
  const elConsumed = document.getElementById('boardConsumedCal');
  const elBurned = document.getElementById('boardBurnedCal');

  if (elRemain) elRemain.innerHTML = `${appState.energy.remaining} <small>kcal</small>`;
  if (elTbRemain) elTbRemain.innerText = appState.energy.remaining;
  if (elConsumed) elConsumed.innerHTML = `${appState.energy.consumed} <small>kcal</small>`;
  if (elBurned) elBurned.innerHTML = `+${appState.energy.burned} <small>kcal</small>`;

  // Summary widgets
  const elSumRemain = document.getElementById('sumRemain');
  const elSumConsumed = document.getElementById('sumConsumed');
  const elSumBurned = document.getElementById('sumBurned');
  if (elSumRemain) elSumRemain.innerText = `${appState.energy.remaining} kcal`;
  if (elSumConsumed) elSumConsumed.innerText = `-${appState.energy.consumed}`;
  if (elSumBurned) elSumBurned.innerText = `+${appState.energy.burned}`;
  if (typeof renderDashboard === 'function') renderDashboard();
  if (typeof renderActivityList === 'function') renderActivityList();
  if (typeof renderWorkoutHeatmap === 'function') renderWorkoutHeatmap();
  if (typeof renderWorkoutHistory === 'function') renderWorkoutHistory();
}

function addWaterQuick(amount) {
  appState.energy.water = Math.min(appState.energy.waterTarget, +(appState.energy.water + amount).toFixed(2));
  const el = document.getElementById('boardWaterVal');
  if (el) el.innerText = `${appState.energy.water} / ${appState.energy.waterTarget}L`;
  if (typeof renderDashboard === 'function') renderDashboard();
  showToast(`Đã ghi nhận +${amount * 1000}ml nước uống! 💧`, 'info');
}

// ==============================================================================
// 4. AI SCANNER & FOOD LOG
// ==============================================================================
function loadFoodSample(key) {
  const food = appState.foodPresets[key];
  if (!food) return;

  currentScannedBase = { ...food };

  // Show preview
  const dropContent = document.getElementById('dropAreaContent');
  const previewWrap = document.getElementById('imagePreviewWrap');
  const previewImg = document.getElementById('scannedImagePreview');
  const laser = document.getElementById('laserScanner');

  if (dropContent) dropContent.style.display = 'none';
  if (previewWrap) previewWrap.style.display = 'block';
  if (previewImg) previewImg.src = food.img;
  if (laser) laser.style.display = 'block';

  // Laser scan effect
  setTimeout(() => {
    if (laser) laser.style.display = 'none';
    renderAIResult(currentScannedBase);
    showToast(`AI Vision đã nhận diện thành công: ${food.name}!`, 'success');
  }, 900);
}

function handleImageUpload(event) {
  const file = event.target.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = function(e) {
    const dropContent = document.getElementById('dropAreaContent');
    const previewWrap = document.getElementById('imagePreviewWrap');
    const previewImg = document.getElementById('scannedImagePreview');
    const laser = document.getElementById('laserScanner');

    if (dropContent) dropContent.style.display = 'none';
    if (previewWrap) previewWrap.style.display = 'block';
    if (previewImg) previewImg.src = e.target.result;
    if (laser) laser.style.display = 'block';

    setTimeout(() => {
      if (laser) laser.style.display = 'none';
      loadFoodSample('pho_bo');
    }, 1200);
  };
  reader.readAsDataURL(file);
}

function triggerAIAnalysis() {
  const prompt = document.getElementById('foodPromptInput').value.trim();
  if (!prompt) {
    showToast('Vui lòng nhập mô tả món ăn hoặc chọn ảnh mẫu!', 'warning');
    return;
  }

  showToast(`AI đang phân tích: "${prompt}"...`, 'info');
  setTimeout(() => {
    loadFoodSample('salad_ga');
  }, 800);
}

function renderAIResult(food) {
  document.getElementById('resConfidence').innerText = food.conf;
  document.getElementById('resFoodName').innerText = food.name;
  document.getElementById('resCal').innerHTML = `${food.cal} <small>kcal</small>`;
  document.getElementById('resProt').innerHTML = `${food.prot} <small>g</small>`;
  document.getElementById('resCarb').innerHTML = `${food.carb} <small>g</small>`;
  document.getElementById('resFat').innerHTML = `${food.fat} <small>g</small>`;
  document.getElementById('gramsDisplay').innerText = `${food.weight} g`;
  document.getElementById('gramsSlider').value = food.weight;
}

function adjustGrams(val) {
  if (!currentScannedBase) return;
  const ratio = val / currentScannedBase.weight;
  document.getElementById('gramsDisplay').innerText = `${val} g`;
  document.getElementById('resCal').innerHTML = `${Math.round(currentScannedBase.cal * ratio)} <small>kcal</small>`;
  document.getElementById('resProt').innerHTML = `${(currentScannedBase.prot * ratio).toFixed(1)} <small>g</small>`;
  document.getElementById('resCarb').innerHTML = `${(currentScannedBase.carb * ratio).toFixed(1)} <small>g</small>`;
  document.getElementById('resFat').innerHTML = `${(currentScannedBase.fat * ratio).toFixed(1)} <small>g</small>`;
}

function saveScannedFoodToLog() {
  if (!currentScannedBase) {
    showToast('Vui lòng chọn hoặc quét một món ăn trước!', 'warning');
    return;
  }
  const mealType = document.getElementById('resMealType').value;
  const grams = parseInt(document.getElementById('gramsSlider').value);
  const ratio = grams / currentScannedBase.weight;
  const cal = Math.round(currentScannedBase.cal * ratio);
  const prot = +(currentScannedBase.prot * ratio).toFixed(1);
  const carb = +(currentScannedBase.carb * ratio).toFixed(1);
  const fat = +(currentScannedBase.fat * ratio).toFixed(1);

  addCardToKanban(currentScannedBase.name, mealType, cal, prot, carb, fat);
  showToast(`Đã lưu "${currentScannedBase.name}" vào nhật ký ${mealType}!`, 'success');
  switchTab('dashboard');
}

function quickAddFood(name, cal, prot, carb, fat) {
  addCardToKanban(name, 'dinner', cal, prot, carb, fat);
  showToast(`Đã thêm "${name}" vào thực đơn Bữa Tối!`, 'success');
  switchTab('dashboard');
}

// ==============================================================================
// 5. WORKOUT & METS
// ==============================================================================
function updateMETPreview() {
  calculateBurnedCalPreview();
}

function calculateBurnedCalPreview() {
  const sportSelect = document.getElementById('sportSelect');
  if (!sportSelect) return;
  const met = parseFloat(sportSelect.selectedOptions[0].getAttribute('data-met')) || 8.0;
  const durationMin = parseFloat(document.getElementById('workoutDuration').value) || 30;
  const weightKg = appState.user.weight || 68.5;

  // Calories Burned = MET * weight(kg) * duration(hours)
  const burned = Math.round(met * weightKg * (durationMin / 60));
  const elPreview = document.getElementById('previewBurnedVal');
  if (elPreview) elPreview.innerText = burned;
  return burned;
}

function handleWorkoutSubmit(e) {
  e.preventDefault();
  const sportSelect = document.getElementById('sportSelect');
  const sportName = sportSelect.selectedOptions[0].text.split('(')[0].trim();
  const duration = parseInt(document.getElementById('workoutDuration').value) || 30;
  const distance = parseFloat(document.getElementById('workoutDistance').value) || 0;
  const burned = calculateBurnedCalPreview();

  appState.workoutLog.unshift({name:sportName, minutes:duration, burned,time:new Date().toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'}),date:localDayKey()});
  // Update Energy
  appState.energy.burned += burned;
  updateEnergyDisplay();

  showToast(`Đã lưu buổi tập "${sportName}" (+${burned} kcal)!`, 'success');
  switchTab('dashboard');
}

// ==============================================================================
// 6. ĐĂNG KÝ & ONBOARDING (đã chuyển sang trang chủ index.html / register)
// ==============================================================================

// ==============================================================================
// 7. PROFILE
// ==============================================================================
function updateProfileMetrics() {
  const weight = parseFloat(document.getElementById('prof-weight').value) || 68.5;
  const height = parseInt(document.getElementById('prof-height').value) || 172;
  appState.user.weight = weight;
  appState.user.height = height;

  showToast('Đã lưu và cập nhật chỉ số cơ thể thành công!', 'success');
}

function changeWeightTimeRange(range, el) {
  document.querySelectorAll('.ct-btn').forEach(b => b.classList.remove('active'));
  if (el) el.classList.add('active');
  showToast(`Đã lọc dữ liệu theo ${range}!`, 'info');
}

// ==============================================================================
// 8. TOASTS & UI HELPERS
// ==============================================================================
function showToast(msg, type = 'info') {
  const container = document.getElementById('toastContainer');
  if (!container) return;

  const toast = document.createElement('div');
  toast.className = `toast-item ${type}`;
  const icon = document.createElement('i'); icon.className = 'fa-solid fa-circle-info';
  const span = document.createElement('span'); span.textContent = msg;
  toast.append(icon, span);
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100%)';
    toast.style.transition = 'all 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 3200);
}

function toggleDropdown(id) {
  const dropdown = document.getElementById(id);
  if (dropdown) dropdown.classList.toggle('show');
}

// Close dropdowns on click outside
window.addEventListener('click', (e) => {
  if (!e.target.closest('.dropdown-wrap')) {
    document.querySelectorAll('.dropdown-menu').forEach(d => d.classList.remove('show'));
  }
});

function toggleFullScreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen().catch(() => {});
  } else {
    document.exitFullscreen().catch(() => {});
  }
}

function filterBoardItems(val) {
  const query = val.toLowerCase();
  const cards = document.querySelectorAll('.kanban-card');
  cards.forEach(card => {
    const text = card.innerText.toLowerCase();
    card.style.display = text.includes(query) ? 'block' : 'none';
  });
}

function filterSuggestions(tag, el) {
  if (el) {
    document.querySelectorAll('.s-filter-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
  }
  if (typeof FoodHub !== 'undefined' && typeof FoodHub.loadSmartSuggestions === 'function') {
    FoodHub.loadSmartSuggestions(tag);
  }
}

// ==============================================================================
// 9. NGƯỜI DÙNG & THEME
// ==============================================================================
// Người dùng do trang chủ (index.html) lưu vào localStorage sau khi đăng nhập/đăng ký
function applyCurrentUser() {
  let user = null;
  try { user = JSON.parse(localStorage.getItem('flexidiet-user')); } catch (e) {}

  const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
  const setValue = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };

  // Lời chào ở đầu trang luôn hiển thị, kể cả khi chưa có user lưu sẵn (dùng tên mặc định)
  setText('greetingName', 'Xin chào, ' + appState.user.name);

  if (!user || !user.name) return;

  appState.user.name = user.name;
  if (user.email) appState.user.email = user.email;

  setText('profileName', user.name);
  if (user.email) setText('profileEmail', user.email);
  setText('greetingName', 'Xin chào, ' + user.name);
  if (typeof renderDashboard === 'function') renderDashboard();

  document.querySelectorAll('[title*="Minh Anh"]').forEach(el => {
    el.title = el.title.replace(/(Nguyễn )?Minh Anh/, () => user.name);
  });

  const isAdmin = (user && user.role === 'admin');
  const adminSec = document.getElementById('adminSidebarSection');
  const roleBadge = document.getElementById('profileRoleBadge');

  if (adminSec) adminSec.style.display = isAdmin ? 'block' : 'none';
  if (roleBadge) {
    if (isAdmin) {
      roleBadge.innerHTML = '<i class="fa-solid fa-shield-halved"></i> Quản trị viên hệ thống';
      roleBadge.style.background = '#fef3c7';
      roleBadge.style.color = '#b45309';
    } else {
      roleBadge.innerHTML = '<i class="fa-solid fa-user-check"></i> Thành viên FlexiDiet';
      roleBadge.style.background = '';
      roleBadge.style.color = '';
    }
  }
}

function logout() {
  try {
    localStorage.removeItem('flexidiet-user');
    sessionStorage.clear();
  } catch (e) {}
  window.location.replace('index.html');
}

// Listen to Hash Change for SPA navigation
window.addEventListener('hashchange', () => {
  const hash = window.location.hash.replace('#', '');
  if (hash) switchTab(hash);
});

// Initialize on Load
document.addEventListener('flexidiet:pages-ready', () => {
  applyCurrentUser();
  const hash = window.location.hash.replace('#', '');
  if (hash) {
    switchTab(hash);
  } else {
    switchTab('dashboard');
  }
  updateEnergyDisplay();
  calculateBurnedCalPreview();
});
  
