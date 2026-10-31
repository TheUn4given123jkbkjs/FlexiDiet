/**
 * Load all existing view modules exactly once.
 * Keeping inactive views mounted preserves the original onclick handlers and
 * in-memory draft form values; no frameworks or bundler are required.
 * Later these can be lazy-loaded behind a dedicated data/API layer.
 */
const PAGE_MODULES = [
  'dashboard', 'enews', 'enews-profile', 'food/index', 'workout', 'history', 'profile'
];
async function mountPageModules() {
  const target = document.getElementById('mainWorkspace');
  const results = await Promise.all(PAGE_MODULES.map(async path => {
    const response = await fetch(`pages/${path}.html`, {cache:'no-cache'});
    if (!response.ok) throw new Error(`Không tải được pages/${path}.html (${response.status})`);
    return {path, html:await response.text()};
  }));
  for (const {path, html} of results) {
    const fragment = document.createElement('template');
    fragment.innerHTML = html.trim();
    const view = fragment.content.firstElementChild;
    if (!view?.classList.contains('page-view')) throw new Error(`Module ${path} không phải .page-view`);
    target.appendChild(view);
  }
  document.getElementById('pagesLoading')?.remove();
  document.dispatchEvent(new Event('flexidiet:pages-ready'));
}
document.addEventListener('DOMContentLoaded', () => {
  mountPageModules().catch(error => {
    console.error(error);
    const place = document.getElementById('pagesLoading');
    if (place) {
      place.innerHTML = '<strong>Không thể tải giao diện.</strong><p>Chạy trang bằng HTTP server (ví dụ: <code>python -m http.server 8000 -d public</code>), không mở file HTML bằng file://.</p>';
      place.classList.add('fd-loading-error');
    }
  });
});
