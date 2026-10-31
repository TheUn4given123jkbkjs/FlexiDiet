/**
 * FLEXIDIET - Workout Form & METs Calculator
 * Tính toán calo tiêu hao thời gian thực theo METs, form ghi nhận buổi tập,
 * quản lý hiển thị trường quãng đường theo môn thể thao và cập nhật chỉ số.
 */

// Danh sách các môn thể thao có đo đạc quãng đường (Chạy bộ, Đạp xe, Đi bộ)
const DISTANCE_SPORTS = ['running', 'cycling', 'walking'];

function updateDistanceVisibility() {
  const sportSelect = document.getElementById('sportSelect');
  const group = document.getElementById('workoutDistanceGroup');
  const distInput = document.getElementById('workoutDistance');
  if (!sportSelect || !group) return;

  const requiresDistance = DISTANCE_SPORTS.includes(sportSelect.value);
  group.style.display = requiresDistance ? 'flex' : 'none';

  if (!requiresDistance && distInput) {
    distInput.value = '';
  } else if (requiresDistance && distInput && !distInput.value) {
    distInput.value = '4.8';
  }
}

function updateMETPreview() {
  updateDistanceVisibility();
  calculateBurnedCalPreview();
}

function calculateBurnedCalPreview() {
  const sportSelect = document.getElementById('sportSelect');
  if (!sportSelect || !sportSelect.selectedOptions.length) return 0;

  const met = parseFloat(sportSelect.selectedOptions[0].getAttribute('data-met')) || 8.0;
  const durationMin = parseFloat(document.getElementById('workoutDuration')?.value) || 30;
  const weightKg = (window.appState && window.appState.user && window.appState.user.weight) || 65.0;

  // Công thức tiêu hao calo chuẩn: MET * Cân nặng (kg) * Thời gian (giờ)
  const burned = Math.round(met * weightKg * (durationMin / 60));
  const elPreview = document.getElementById('previewBurnedVal');
  if (elPreview) elPreview.innerText = burned;
  return burned;
}

function handleWorkoutSubmit(e) {
  e.preventDefault();
  const sportSelect = document.getElementById('sportSelect');
  if (!sportSelect || !sportSelect.selectedOptions.length) return;

  const sportCode = sportSelect.value;
  const sportName = sportSelect.selectedOptions[0].text.split('(')[0].trim();
  const duration = parseInt(document.getElementById('workoutDuration')?.value) || 30;
  const burned = calculateBurnedCalPreview();
  const isDistSport = DISTANCE_SPORTS.includes(sportCode);
  const distInput = document.getElementById('workoutDistance');
  const distance = isDistSport && distInput && Number(distInput.value) > 0 ? Number(distInput.value) : null;

  if (window.appState) {
    const dayKey = typeof localDayKey === 'function' ? localDayKey() : new Date().toISOString().slice(0, 10);
    window.appState.workoutLog.unshift({
      name: sportName,
      minutes: duration,
      distance: distance,
      burned,
      time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
      date: dayKey
    });
    window.appState.energy.burned += burned;
  }
  
  if (typeof updateEnergyDisplay === 'function') updateEnergyDisplay();
  if (typeof showToast === 'function') showToast(`Đã lưu buổi tập "${sportName}" (+${burned} kcal)!`, 'success');
  if (typeof switchTab === 'function') switchTab('dashboard');
}

function updateProfileMetrics() {
  const weight = parseFloat(document.getElementById('prof-weight')?.value) || (window.appState?.user?.weight ?? 65.0);
  const height = parseInt(document.getElementById('prof-height')?.value) || (window.appState?.user?.height ?? 170);
  
  if (window.appState && window.appState.user) {
    window.appState.user.weight = weight;
    window.appState.user.height = height;
  }

  if (typeof showToast === 'function') showToast('Đã lưu và cập nhật chỉ số cơ thể thành công!', 'success');
}

function changeWeightTimeRange(range, el) {
  document.querySelectorAll('.ct-btn').forEach(b => b.classList.remove('active'));
  if (el) el.classList.add('active');
  if (typeof showToast === 'function') showToast(`Đã lọc dữ liệu theo ${range}!`, 'info');
}

// Khởi tạo trạng thái form ban đầu khi trang tải xong
document.addEventListener('flexidiet:pages-ready', () => {
  updateDistanceVisibility();
});

// Xuất ra window cho các sự kiện HTML và các script khác
window.updateDistanceVisibility = updateDistanceVisibility;
window.updateMETPreview = updateMETPreview;
window.calculateBurnedCalPreview = calculateBurnedCalPreview;
window.handleWorkoutSubmit = handleWorkoutSubmit;
window.updateProfileMetrics = updateProfileMetrics;
window.changeWeightTimeRange = changeWeightTimeRange;
