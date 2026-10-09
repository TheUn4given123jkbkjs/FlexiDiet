/** One origin, PHP cookie session + CSRF token. No passwords/tokens in localStorage. */
const FlexiAPI = (() => {
  let csrfToken = '';
  async function request(path, {method = 'GET', data} = {}) {
    if (method !== 'GET' && !csrfToken) await csrf();
    const i = path.indexOf('?');
    const route = i < 0 ? path : path.slice(0, i);
    const query = i < 0 ? '' : '&' + path.slice(i + 1);
    const res = await fetch(`api/index.php?route=${encodeURIComponent(route)}${query}`, {
      method, credentials: 'same-origin', cache: 'no-store',
      headers: {'Accept':'application/json', ...(data ? {'Content-Type':'application/json'} : {}), ...(method !== 'GET' ? {'X-CSRF-Token':csrfToken} : {})},
      ...(data ? {body:JSON.stringify(data)} : {})
    });
    let json;
    try { json = await res.json(); } catch { throw new Error('Server không trả JSON. Kiểm tra đường dẫn PHP và cấu hình máy chủ.'); }
    if (!res.ok) throw new Error(json.error?.message || `HTTP ${res.status}`);
    if (json.data?.csrf_token) csrfToken = json.data.csrf_token;
    return json.data;
  }
  async function csrf() {
    const response = await request('/auth/csrf');
    csrfToken = response.csrf_token;
    return csrfToken;
  }
  return {request, csrf};
})();
