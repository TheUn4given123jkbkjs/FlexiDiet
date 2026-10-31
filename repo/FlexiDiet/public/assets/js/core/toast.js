/**
 * FLEXIDIET - Core Toast & UI Helpers
 * Quản lý thông báo toast góc màn hình và các tiện ích tương tác UI chung.
 */

function showToast(msg, type = 'info') {
  const container = document.getElementById('toastContainer');
  if (!container) return;

  const toast = document.createElement('div');
  toast.className = `toast-item ${type}`;
  const icon = document.createElement('i');
  icon.className = type === 'success'
    ? 'fa-solid fa-circle-check'
    : type === 'warning'
      ? 'fa-solid fa-triangle-exclamation'
      : type === 'danger'
        ? 'fa-solid fa-circle-xmark'
        : 'fa-solid fa-circle-info';

  const span = document.createElement('span');
  span.textContent = msg;
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

// Đóng dropdown khi click bên ngoài
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

// Gán vào window để đảm bảo tương thích với các sự kiện onclick trên HTML
window.showToast = showToast;
window.toggleDropdown = toggleDropdown;
window.toggleFullScreen = toggleFullScreen;
