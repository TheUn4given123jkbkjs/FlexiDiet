/**
 * FLEXIDIET - APP (Board, Food/Workout Log, AI Scanner, Charts, Onboarding, Profile)
 */

// Application State
const appState = {
  activeTab: 'board',
  currentStep: 1,
  user: {
    name: 'Nguyễn Minh Anh',
    email: 'minhanh@gmail.com',
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
function switchTab(tabId) {
  if (!document.getElementById('view-' + tabId)) tabId = 'board';
  appState.activeTab = tabId;

  // Update Navigation Tab Active Classes
  const tabs = document.querySelectorAll('.jira-tab');
  tabs.forEach(tab => {
    if (tab.getAttribute('data-target') === tabId) {
      tab.classList.add('active');
    } else {
      tab.classList.remove('active');
    }
  });

  // Update Page View Visibility
  const views = document.querySelectorAll('.page-view');
  views.forEach(view => {
    if (view.id === `view-${tabId}`) {
      view.classList.add('active');
    } else {
      view.classList.remove('active');
    }
  });

  // Show/Hide Jira Sub-toolbar on specific pages (Hide on Landing for maximum focus)
  const subToolbar = document.getElementById('jiraSubToolbar');
  if (subToolbar) {
    if (tabId === 'home') {
      subToolbar.style.display = 'none';
    } else {
      subToolbar.style.display = 'block';
    }
  }

  // Scroll to top
  window.scrollTo({ top: 0, behavior: 'smooth' });

  // Update URL hash
  if (history.pushState) {
    history.pushState(null, null, `#${tabId}`);
  }
}

// ==============================================================================
// 2. QUICK CREATE MODAL (+ CREATE BUTTON)
// ==============================================================================
function openCreateQuickModal(defaultMeal) {
  const modal = document.getElementById('createQuickModal');
  if (modal) {
    modal.classList.add('open');
    if (defaultMeal && document.getElementById('qfMeal')) {
      document.getElementById('qfMeal').value = defaultMeal;
    }
  }
}

function closeCreateQuickModal() {
  const modal = document.getElementById('createQuickModal');
  if (modal) {
    modal.classList.remove('open');
  }
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
  } else {
    btnWorkout.classList.add('active');
    btnFood.classList.remove('active');
    formWorkout.style.display = 'block';
    formFood.style.display = 'none';
  }
}

function handleQuickFoodSubmit(e) {
  e.preventDefault();
  const name = document.getElementById('qfName').value;
  const meal = document.getElementById('qfMeal').value;
  const cal = parseInt(document.getElementById('qfCal').value) || 0;
  const prot = parseFloat(document.getElementById('qfProt').value) || 0;
  const carb = parseFloat(document.getElementById('qfCarb').value) || 0;
  const fat = parseFloat(document.getElementById('qfFat').value) || 0;

  addCardToKanban(name, meal, cal, prot, carb, fat);
  closeCreateQuickModal();
  showToast(`Đã thêm món "${name}" vào Kanban Board!`, 'success');
}

function handleQuickWorkoutSubmit(e) {
  e.preventDefault();
  const sport = document.getElementById('qwSport').value;
  const duration = parseInt(document.getElementById('qwDuration').value) || 0;
  const burned = parseInt(document.getElementById('qwBurned').value) || 0;

  appState.energy.burned += burned;
  updateEnergyDisplay();

  closeCreateQuickModal();
  showToast(`Đã ghi nhận buổi tập "${sport}" (+${burned} kcal)!`, 'success');
}

function addCardToKanban(name, meal, cal, prot, carb, fat) {
  const colContainer = document.getElementById(`cards-${meal}`);
  if (!colContainer) return;

  const card = document.createElement('div');
  card.className = 'kanban-card';
  card.innerHTML = `
    <div class="card-title-row">
      <span class="card-item-title">${name}</span>
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
  colContainer.prepend(card);

  // Update Energy
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
}

function addWaterQuick(amount) {
  appState.energy.water = Math.min(appState.energy.waterTarget, +(appState.energy.water + amount).toFixed(2));
  const el = document.getElementById('boardWaterVal');
  if (el) el.innerText = `${appState.energy.water} / ${appState.energy.waterTarget}L`;
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
  switchTab('board');
}

function quickAddFood(name, cal, prot, carb, fat) {
  addCardToKanban(name, 'dinner', cal, prot, carb, fat);
  showToast(`Đã thêm "${name}" vào thực đơn Bữa Tối!`, 'success');
  switchTab('board');
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

  // Add to History
  const historyList = document.getElementById('workoutHistoryList');
  if (historyList) {
    const item = document.createElement('div');
    item.className = 'wh-item';
    item.innerHTML = `
      <div class="wh-icon bg-purple"><i class="fa-solid fa-person-running"></i></div>
      <div class="wh-meta">
        <h4>${sportName}</h4>
        <span class="wh-sub">Hôm nay • ${duration} phút ${distance > 0 ? `• ${distance} km` : ''}</span>
      </div>
      <div class="wh-cal text-purple">+${burned} kcal</div>
    `;
    historyList.prepend(item);
  }

  // Update Energy
  appState.energy.burned += burned;
  updateEnergyDisplay();

  showToast(`Đã lưu buổi tập "${sportName}" (+${burned} kcal)!`, 'success');
  switchTab('board');
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
  toast.innerHTML = `<i class="fa-solid fa-circle-info"></i> <span>${msg}</span>`;
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
  const cards = document.querySelectorAll('.suggest-card');
  cards.forEach(c => {
    const tags = c.getAttribute('data-tags') || '';
    if (tag === 'all' || tags.includes(tag)) {
      c.style.display = 'flex';
    } else {
      c.style.display = 'none';
    }
  });
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

  document.querySelectorAll('[title*="Minh Anh"]').forEach(el => {
    el.title = el.title.replace(/(Nguyễn )?Minh Anh/, () => user.name);
  });
}

function logout() {
  try { localStorage.removeItem('flexidiet-user'); } catch (e) {}
  location.href = 'index.html';
}

// Listen to Hash Change for SPA navigation
window.addEventListener('hashchange', () => {
  const hash = window.location.hash.replace('#', '');
  if (hash) switchTab(hash);
});

// Initialize on Load
document.addEventListener('DOMContentLoaded', () => {
  applyCurrentUser();
  const hash = window.location.hash.replace('#', '');
  if (hash) {
    switchTab(hash);
  } else {
    switchTab('board');
  }
  calculateBurnedCalPreview();
});
  
