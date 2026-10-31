/**
 * FLEXIDIET - Core Router & Sidebar Navigation
 * Điều hướng SPA tab, điều khiển drawer sidebar trên thiết bị di động và hash routing.
 */

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
  
  if (typeof appState !== 'undefined') {
    appState.activeTab = tabId;
  } else if (window.appState) {
    window.appState.activeTab = tabId;
  }

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
    'dashboard': 'Tổng quan',
    'enews': 'eNews',
    'enews-profile': 'Hồ sơ eNews',
    'food': 'Ăn uống (Food Hub)',
    'workout': 'Luyện tập',
    'history': 'Nhật ký chi tiết',
    'profile': 'Hồ sơ cá nhân'
  };

  const label = document.getElementById('currentPageLabel');
  if (label) label.textContent = pageNames[tabId] || 'Tổng quan';

  if (tabId === 'dashboard' && typeof renderDashboard === 'function') renderDashboard();
  if (tabId === 'history' && typeof renderActivityList === 'function') renderActivityList();
  if (tabId === 'profile' && typeof fdLoadProfileInsights === 'function') fdLoadProfileInsights();
  if (tabId === 'workout' && typeof updateDistanceVisibility === 'function') updateDistanceVisibility();

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

// Lắng nghe thay đổi hash trên URL
window.addEventListener('hashchange', () => {
  const hash = window.location.hash.replace('#', '');
  if (hash) switchTab(hash);
});

// Xuất ra window cho HTML onclick và các module khác
window.setMobileSidebarOpen = setMobileSidebarOpen;
window.toggleMobileSidebar = toggleMobileSidebar;
window.closeMobileSidebar = closeMobileSidebar;
window.switchTab = switchTab;
window.navigateToWorkoutLogging = navigateToWorkoutLogging;

