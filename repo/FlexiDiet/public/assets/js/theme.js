// Chuyển giao diện sáng/tối, dùng chung cho index.html và app.html.
// Chạy ở <head> để áp dụng theme đã lưu trước khi trang được vẽ (tránh nháy màu).
(function () {
  var KEY = 'flexidiet-theme';
  var root = document.documentElement;

  try {
    var saved = localStorage.getItem(KEY);
    if (saved === 'dark' || saved === 'light') root.setAttribute('data-theme', saved);
  } catch (e) {}

  function toggleTheme() {
    var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    try { localStorage.setItem(KEY, next); } catch (e) {}
    document.dispatchEvent(new CustomEvent('flexidiet:themechange', { detail: next }));
  }

  function syncThemeControls() {
    var isDark = root.getAttribute('data-theme') === 'dark';
    var profileToggle = document.getElementById('profile-theme-toggle');
    if (profileToggle) {
      profileToggle.checked = isDark;
      if (!profileToggle.dataset.themeBound) {
        profileToggle.addEventListener('change', toggleTheme);
        profileToggle.dataset.themeBound = 'true';
      }
    }

    var button = document.getElementById('theme-toggle');
    if (button) {
      button.setAttribute('aria-pressed', String(isDark));
      button.setAttribute('aria-label', isDark ? 'Chuyển sang giao diện sáng' : 'Chuyển sang giao diện tối');
      button.setAttribute('title', isDark ? 'Chuyển sang giao diện sáng' : 'Chuyển sang giao diện tối');
      var icon = button.querySelector('i');
      if (icon && !button.classList.contains('fd-theme-switch')) {
        icon.classList.toggle('fa-sun', isDark);
        icon.classList.toggle('fa-moon', !isDark);
      }
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('theme-toggle');
    if (btn) btn.addEventListener('click', toggleTheme);
    syncThemeControls();
  });

  document.addEventListener('flexidiet:themechange', syncThemeControls);
  document.addEventListener('flexidiet:pages-ready', syncThemeControls);
})();
