/**
 * FLEXIDIET - Application State Store & Orchestrator
 * Quản lý trạng thái ứng dụng trung tâm (appState), tính toán cân bằng năng lượng,
 * quản lý thông tin phiên làm việc người dùng và vòng đời ứng dụng.
 */

// Calendar dates use the browser's LOCAL day (not UTC), avoiding midnight shifts.
function localDayKey(date = new Date()) {
  const pad = value => String(value).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

// Khởi tạo thông tin người dùng từ phiên đăng nhập thực tế
let initialUser = null;
try { initialUser = JSON.parse(localStorage.getItem('flexidiet-user')); } catch (e) {}

// Application State (Single Source of Truth - Initial clean state)
const appState = {
  activeTab: 'dashboard',
  currentStep: 1,
  user: {
    name: (initialUser && initialUser.name) || 'Người dùng',
    email: (initialUser && initialUser.email) || '',
    role: (initialUser && initialUser.role) || 'member',
    gender: (initialUser && initialUser.gender) || 'male',
    age: (initialUser && initialUser.age) || 25,
    height: (initialUser && initialUser.height) || 170,
    weight: (initialUser && initialUser.weight) || 65.0,
    pal: 1.375, // Physical Activity Level
    goal: 'maintain',
    calorieOffset: 0,
    targetWeight: (initialUser && initialUser.targetWeight) || 65.0,
    bmr: 0,
    tdee: 0,
    targetCalories: 2000
  },
  energy: {
    target: 2000,
    consumed: 0,
    burned: 0,
    remaining: 2000,
    water: 0,
    waterTarget: 2.0,
    steps: 0
  },
  foodLog: [],
  workoutLog: [],
  macros: {
    protein: 0,
    carbs: 0,
    fat: 0,
    targets: { protein: 0, carbs: 0, fat: 0 }
  }
};

window.appState = appState;
window.localDayKey = localDayKey;

// ==============================================================================
// 1. ENERGY BALANCE & LOGGING
// ==============================================================================
function updateEnergyDisplay() {
  appState.energy.remaining = appState.energy.target + appState.energy.burned - appState.energy.consumed;

  if (typeof renderDashboard === 'function') renderDashboard();
  if (typeof renderActivityList === 'function') renderActivityList();
  if (typeof renderWorkoutHeatmap === 'function') renderWorkoutHeatmap();
  if (typeof renderWorkoutHistory === 'function') renderWorkoutHistory();
}

function addWaterQuick(amount) {
  appState.energy.water = Math.min(appState.energy.waterTarget, +(appState.energy.water + amount).toFixed(2));
  if (typeof renderDashboard === 'function') renderDashboard();
  if (typeof showToast === 'function') {
    showToast(`Đã ghi nhận +${amount * 1000}ml nước uống! 💧`, 'info');
  }
}

// Fallback helper khi ghi nhận bữa ăn cục bộ
function addCardToKanban(name, meal, cal, prot, carb, fat) {
  appState.foodLog.unshift({
    name,
    meal,
    cal,
    prot,
    carb,
    fat,
    time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
    date: localDayKey()
  });
  appState.macros.protein += prot;
  appState.macros.carbs += carb;
  appState.macros.fat += fat;
  appState.energy.consumed += cal;
  updateEnergyDisplay();
}

// ==============================================================================
// 2. USER SESSION & AUTH
// ==============================================================================
function applyCurrentUser() {
  let user = null;
  try { user = JSON.parse(localStorage.getItem('flexidiet-user')); } catch (e) {}

  const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };

  const displayName = (user && user.name) || appState.user.name || 'Người dùng';
  setText('greetingName', 'Xin chào, ' + displayName);
  if (!user || !user.name) return;

  appState.user.name = user.name;
  if (user.email) appState.user.email = user.email;

  setText('profileName', user.name);
  if (user.email) setText('profileEmail', user.email);
  setText('greetingName', 'Xin chào, ' + user.name);
  if (typeof renderDashboard === 'function') renderDashboard();

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

// ==============================================================================
// 3. APP LIFECYCLE INITIALIZATION
// ==============================================================================
document.addEventListener('flexidiet:pages-ready', () => {
  applyCurrentUser();
  const hash = window.location.hash.replace('#', '');
  if (typeof switchTab === 'function') {
    if (hash) {
      switchTab(hash);
    } else {
      switchTab('dashboard');
    }
  }
  updateEnergyDisplay();
  if (typeof calculateBurnedCalPreview === 'function') {
    calculateBurnedCalPreview();
  }
});

// Xuất ra window
window.updateEnergyDisplay = updateEnergyDisplay;
window.addWaterQuick = addWaterQuick;
window.addCardToKanban = addCardToKanban;
window.applyCurrentUser = applyCurrentUser;
window.logout = logout;
