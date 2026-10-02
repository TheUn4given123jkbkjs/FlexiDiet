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

  document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('theme-toggle');
    if (btn) btn.addEventListener('click', toggleTheme);
  });
})();
