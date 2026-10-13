/* ==========================================================================
   FlexiDiet Admin Studio - Core Engine & Controller (admin.js)
   Quản trị dữ liệu, nạp khung mẫu từ admin-pages/, vẽ biểu đồ & Auth Guard
   ========================================================================== */

const $ = s => document.querySelector(s);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

// Global Admin State
let S = { scr: 'stats', per: '1w', obsPer: 'hour', kid: null, pid: null, pname: '', page: 1, q: '', fl: {}, data: null, demo: false, total: 0, perPage: 25 };

// In-Memory Template Cache for admin-pages/*.html
const templateCache = {};
async function getTemplate(name) {
  if (!templateCache[name]) {
    const res = await fetch(`admin-pages/${name}.html`, { cache: 'no-cache' });
    if (!res.ok) throw new Error(`Không thể nạp admin-pages/${name}.html (${res.status})`);
    templateCache[name] = await res.text();
  }
  return templateCache[name];
}

function toast(m) {
  const t = $('#toast');
  if (!t) return;
  t.innerHTML = `<i class="fa-solid fa-circle-check" style="color:#10b981;"></i> <span>${esc(m)}</span>`;
  t.classList.add('show');
  clearTimeout(toast.h);
  toast.h = setTimeout(() => t.classList.remove('show'), 3400);
}

const call = (p, o) => FlexiAPI.request(p, o);
const key = () => S.kid || S.scr;
const def = () => R[key()];
const base = () => def().p.replace(':pid', S.pid);
const item = id => def().item ? def().item(id) : base() + '/' + id;

// Responsive Drawer Logic
function toggleMobileSidebar() {
  const sidebar = document.getElementById('appSidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  const isOpen = sidebar.classList.toggle('mobile-open');
  backdrop.classList.toggle('show', isOpen);
  document.getElementById('sidebarToggle')?.setAttribute('aria-expanded', String(isOpen));
}

function closeMobileSidebar() {
  document.getElementById('appSidebar')?.classList.remove('mobile-open');
  document.getElementById('sidebarBackdrop')?.classList.remove('show');
  document.getElementById('sidebarToggle')?.setAttribute('aria-expanded', 'false');
}

window.addEventListener('resize', () => { if (window.innerWidth > 820) closeMobileSidebar(); });
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    closeMobileSidebar();
    document.querySelector('.md')?.remove();
  }
});

// Main Data Fetcher & Router
async function load() {
  const k = key(), d = R[k];
  $('#crumb').textContent = SC[S.scr][1] + (S.kid ? ' › ' + S.pname + ' › ' + d.t : '');
  document.querySelectorAll('[data-s]').forEach(b => b.classList.toggle('active', b.dataset.s === S.scr));

  try {
    if (!d) {
      let r;
      if (S.scr === 'obs') {
        const obsP = S.obsPer || 'hour';
        r = {
          m: await call('/admin/ai/metrics'),
          h: await call('/admin/ai/health').catch(() => null),
          t: await call('/admin/ai/throughput?period=' + obsP).catch(() => []),
          http: await call('/admin/ai/http-distribution?period=' + obsP).catch(() => ({ total: 0, items: [] }))
        };
      } else if (S.scr === 'stats') {
        r = await call('/admin/stats');
        r.growth = await call('/admin/stats/user-growth?period=' + S.per).catch(() => []);
        r.meals = await call('/admin/stats/meals?period=' + S.per).catch(() => []);
      } else {
        r = await call('/admin/ai/recognition-stats');
      }
      S.data = r;
      S.demo = false;
    } else {
      const p = new URLSearchParams();
      if (!S.kid) {
        p.set('page', S.page);
        if (S.q) p.set(d.qk || 'q', S.q);
        const f2 = { ...(d.ops.includes('t') ? { status: 'active' } : {}), ...S.fl };
        for (const x in f2) if (f2[x]) p.set(x, f2[x]);
      }
      const r = await call(base() + (p.toString() ? '?' + p : ''));
      S.data = Array.isArray(r) ? r : (r.items || []);
      S.total = r.total ?? S.data.length;
      S.perPage = r.per_page || 25;
      S.demo = false;
      if (k === 'users') {
        try { S.rs = await call('/admin/users/retention-stats'); } catch (e) { S.rs = null; }
      }
    }
  } catch (e) {
    S.demo = true;
    if (d) {
      const allRows = d.seed ? d.seed.filter(r => !S.kid || r.pid == S.pid) : [];
      let filtered = allRows;
      if (S.q) {
        const ql = S.q.toLowerCase();
        filtered = filtered.filter(r => Object.values(r).some(v => String(v).toLowerCase().includes(ql)));
      }
      if (S.fl.status) {
        filtered = filtered.filter(r => (S.fl.status === 'active' ? r.is_active === 1 || r.status === 'active' : S.fl.status === 'inactive' ? r.is_active === 0 : r.status === S.fl.status));
      }
      S.data = filtered;
      S.total = filtered.length;
    } else {
      S.data = DEMO[S.scr];
      if (S.scr === 'stats' && DEMO.stats_periods) {
        const pData = DEMO.stats_periods[S.per] || DEMO.stats_periods['1w'];
        S.data = {
          ...DEMO.stats,
          user_trends: pData.user_trends,
          meals: pData.meals,
          intake_by_goal: pData.intake_by_goal,
          burned_by_goal: pData.burned_by_goal
        };
      }
      S.total = 0;
    }
  }
  await render();
}

// Chart & Visual Formatting Helpers
const split = a => a.map(([v, c]) => `<i style="width:${v}%;background:${c};"></i>`).join('');

const disp = (f, r) => {
  const [k, , t, o] = f;
  if (t === 'select') {
    const x = o.find(y => String(y[0]) === String(r[k]));
    return x ? x[1] : (r[k] ?? '—');
  }
  if (t === 'ref') return r[k.replace('_id', '_name')] ?? r[k] ?? '—';
  if (t === 'img') return r[k] ? `<img class="th-img" src="${esc(r[k])}" alt="thumb">` : '—';
  if (k === 'role') {
    const isAdm = r[k] === 'admin';
    return `<span class="tag ${isAdm ? 'role-admin' : 'role-member'}"><i class="fa-solid fa-${isAdm ? 'shield-halved' : 'user'}"></i> ${isAdm ? 'Quản trị viên' : 'Thành viên'}</span>`;
  }
  if (k === 'status' && !t) {
    const isAct = r[k] === 'active';
    const isBan = r[k] === 'banned';
    return `<span class="tag ${isAct ? '' : 'off'}">${isAct ? 'Hoạt động' : isBan ? 'Bị khóa' : 'Rời bỏ'}</span>`;
  }
  return esc(r[k] ?? '—');
};

const bars = (a, k, f = v => v) => {
  const mx = Math.max(1, ...a.map(x => x[k] || 0));
  const isDense = a.length > 12;
  const gap = isDense ? '2px' : '8px';
  const fs = isDense ? '9px' : '10px';
  const barW = isDense ? '10px' : '24px';
  const maxBarPx = 105;
  return a.length ? `
    <div style="display:flex;align-items:flex-end;gap:${gap};height:160px;padding:8px 2px 2px;width:100%;box-sizing:border-box;overflow:hidden;">
      ${a.map((x, i) => {
        const lbl = isDense ? (i % 2 === 0 ? esc(x.label) : '<span style="opacity:0.35;">·</span>') : esc(x.label);
        const barH = Math.max(4, Math.round(((x[k] || 0) / mx) * maxBarPx));
        return `
        <div title="${esc(x.label)}: ${f(x[k])}" style="flex:1;min-width:0;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;cursor:pointer;">
          <span style="font-size:8.5px;font-weight:700;color:var(--fd-ink);margin-bottom:3px;line-height:1;min-height:10px;">${x[k] ? x[k] : ''}</span>
          <i style="display:block;width:100%;max-width:${barW};height:${barH}px;background:linear-gradient(180deg, #34d399, var(--fd-green));border-radius:4px 4px 0 0;transition:all 0.2s;"></i>
          <small style="font-size:${fs};color:var(--fd-subtle);margin-top:6px;white-space:nowrap;line-height:1;">${lbl}</small>
        </div>`;
      }).join('')}
    </div>` : `<div class="empty"><i class="fa-solid fa-chart-simple"></i> Chưa có dữ liệu</div>`;
};

const dualLine = a => {
  if (!a || a.length < 2) return '<div class="empty"><i class="fa-solid fa-chart-line"></i> Chưa đủ dữ liệu biểu đồ</div>';
  const mxAct = Math.max(1, ...a.map(x => x.active_users || x.count || 0));
  const mxNew = Math.max(1, ...a.map(x => x.new_users || x.count || 0));
  const W = 620, H = 160;

  const qAct = a.map((x, i) => [
    24 + i * (W - 48) / (a.length - 1),
    H - 26 - (((x.active_users ?? x.count) / mxAct) * (H - 58))
  ]);

  const qNew = a.map((x, i) => [
    24 + i * (W - 48) / (a.length - 1),
    H - 26 - (((x.new_users ?? x.count) / mxNew) * (H - 58))
  ]);

  const dPathAct = `M ${qAct[0][0]} ${H - 22} L ${qAct.map(z => z.join(' ')).join(' L ')} L ${qAct[qAct.length - 1][0]} ${H - 22} Z`;
  const dPathNew = `M ${qNew[0][0]} ${H - 22} L ${qNew.map(z => z.join(' ')).join(' L ')} L ${qNew[qNew.length - 1][0]} ${H - 22} Z`;

  return `
    <svg viewBox="0 0 ${W} ${H}" style="width:100%;height:auto;display:block;">
      <defs>
        <linearGradient id="actGrad" x1="0%" y1="0%" x2="0%" y2="100%">
          <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.22"/>
          <stop offset="100%" stop-color="#3b82f6" stop-opacity="0.01"/>
        </linearGradient>
        <linearGradient id="newGrad" x1="0%" y1="0%" x2="0%" y2="100%">
          <stop offset="0%" stop-color="#10b981" stop-opacity="0.28"/>
          <stop offset="100%" stop-color="#10b981" stop-opacity="0.02"/>
        </linearGradient>
      </defs>
      <path d="${dPathAct}" fill="url(#actGrad)"/>
      <path d="${dPathNew}" fill="url(#newGrad)"/>
      <polyline fill="none" stroke="#3b82f6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" points="${qAct.map(z => z.join(',')).join(' ')}"/>
      <polyline fill="none" stroke="var(--fd-green)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" points="${qNew.map(z => z.join(',')).join(' ')}"/>
      ${qAct.map((z, i) => `
        <circle cx="${z[0]}" cy="${z[1]}" r="4" fill="#fff" stroke="#3b82f6" stroke-width="2">
          <title>${esc(a[i].label)}: ${a[i].active_users ?? a[i].count} đang hoạt động</title>
        </circle>
        <text x="${z[0]}" y="${z[1] - 7}" text-anchor="middle" font-size="9.5" font-weight="700" fill="#2563eb">${a[i].active_users ?? a[i].count}</text>
      `).join('')}
      ${qNew.map((z, i) => `
        <circle cx="${z[0]}" cy="${z[1]}" r="4.5" fill="#fff" stroke="var(--fd-green)" stroke-width="2.5">
          <title>${esc(a[i].label)}: ${a[i].new_users ?? a[i].count} đăng ký mới</title>
        </circle>
        <text x="${z[0]}" y="${z[1] + 14}" text-anchor="middle" font-size="9.5" font-weight="700" fill="var(--fd-green)">${a[i].new_users ?? a[i].count}</text>
      `).join('')}
      <text x="24" y="${H - 5}" font-size="11" font-weight="600" fill="var(--fd-subtle)">${esc(a[0].label)}</text>
      <text x="${W - 24}" y="${H - 5}" text-anchor="end" font-size="11" font-weight="600" fill="var(--fd-subtle)">${esc(a[a.length - 1].label)}</text>
    </svg>`;
};

const goalBars = (list, maxVal, unit) => {
  if (!list || !list.length) return '<div class="empty"><i class="fa-solid fa-chart-bar"></i> Chưa có dữ liệu</div>';
  return `
    <div class="goal-bars-wrap">
      ${list.map(x => `
        <div class="goal-bar-row">
          <div class="goal-bar-label">
            <span class="goal-bar-icon" style="background:color-mix(in srgb, ${x.color} 15%, transparent);color:${x.color};">
              <i class="fa-solid fa-${x.icon}"></i>
            </span>
            <span class="goal-bar-text">${esc(x.label)}</span>
            <b class="goal-bar-val" style="color:${x.color};">${x.kcal.toLocaleString()} <small>${unit}</small></b>
          </div>
          <div class="goal-bar-track">
            <div class="goal-bar-fill" style="width:${Math.min(100, Math.round((x.kcal / maxVal) * 100))}%;background:${x.color};"></div>
          </div>
        </div>
      `).join('')}
    </div>`;
};

// ==========================================================================
// Template Renderers (Binding Dynamic Data into Loaded HTML Fragments)
// ==========================================================================

async function renderStats(v) {
  v.innerHTML = await getTemplate('stats');
  const d = S.data;
  if (!d) return;

  const demoEl = $('#demoBanner');
  if (demoEl) demoEl.style.display = S.demo ? 'flex' : 'none';

  // Bind 5 Stats Cards
  $('#valDau').textContent = d.dau != null ? d.dau : '—';
  $('#valWau').textContent = d.wau != null ? d.wau : '—';
  $('#valDishes').textContent = d.total_dishes != null ? d.total_dishes : '—';
  $('#valIngredients').textContent = d.total_ingredients != null ? d.total_ingredients : '—';
  $('#valMeals').textContent = d.total_meals != null ? d.total_meals : '—';

  // Bind Goal Achievement Rate
  const g = d.goal_rate || {};
  $('#valGoalWithin').textContent = (g.within ?? 0) + '%';
  $('#valGoalOver').textContent = (g.over ?? 0) + '%';
  $('#valGoalUnder').textContent = (g.under ?? 0) + '%';
  $('#goalSplitTrack').innerHTML = split([[g.within || 0, 'var(--fd-green)'], [g.over || 0, '#f97316'], [g.under || 0, '#3b82f6']]);

  // Set Active Period Tab
  document.querySelectorAll('#trendPeriodTabs [data-p]').forEach(b => {
    b.classList.toggle('active', b.dataset.p === S.per);
  });

  // Render the 4 Dynamic Charts
  $('#chartUserTrend').innerHTML = dualLine(d.user_trends || d.growth || []);
  $('#chartMealLogs').innerHTML = bars(d.meals || [], 'meals');
  $('#chartGoalIntake').innerHTML = goalBars(d.intake_by_goal || [], 2800, 'kcal/ngày');
  $('#chartGoalBurned').innerHTML = goalBars(d.burned_by_goal || [], 500, 'kcal/ngày');
}

async function renderObs(v) {
  v.innerHTML = await getTemplate('obs');
  const d = S.data;
  if (!d) return;

  const demoEl = $('#demoBanner');
  if (demoEl) demoEl.style.display = S.demo ? 'flex' : 'none';

  const m = d.m || {}, h = d.h || {}, val = x => x != null ? x : '—';
  $('#valVolumeToday').textContent = val(m.volume_today);
  $('#valVolumeSub').textContent = `Tuần: ${val(m.volume_week)} · Tháng: ${val(m.volume_month)}`;
  $('#valInFlight').textContent = val(m.in_flight);
  $('#valErrorRate').textContent = m.error_rate != null ? m.error_rate + '%' : '—';
  $('#valLatency').textContent = m.p50_ms != null ? `${m.p50_ms} / ${m.p95_ms} ms` : '—';

  $('#valServerStatus').textContent = val(h.status);
  $('#valServerUptime').textContent = 'Uptime: ' + val(h.uptime);
  $('#valCpuUsage').textContent = h.cpu != null ? h.cpu + '%' : '—';
  $('#valRamUsage').textContent = h.ram != null ? h.ram + '%' : '—';
  $('#valAiModel').textContent = val(h.model);

  const obsP = S.obsPer || 'hour';
  document.querySelectorAll('#obsPeriodTabs [data-p]').forEach(b => {
    b.classList.toggle('active', b.dataset.p === obsP);
  });

  const titleEl = $('#obsTrafficChartTitle');
  if (titleEl) {
    titleEl.textContent = obsP === 'hour'
      ? 'Số request nhận diện (00h–23h hôm nay)'
      : obsP === 'day'
      ? 'Số request nhận diện (7 ngày qua)'
      : 'Số request nhận diện (6 tháng qua)';
  }

  $('#chartThroughput').innerHTML = bars(d.t || [], 'count');

  // Render HTTP Status Distribution Split & List
  const httpData = d.http || { total: 0, items: [] };
  const items = httpData.items || [];
  const splitTrack = $('#obsHttpSplit');
  if (splitTrack) {
    if (items.length && httpData.total > 0) {
      splitTrack.innerHTML = items.filter(x => x.count > 0).map(x => `<i style="width:${x.pct}%;background:${x.color};" title="${esc(x.label)}: ${x.count} req (${x.pct}%)"></i>`).join('');
    } else {
      splitTrack.innerHTML = '<i style="width:100%;background:var(--fd-green);" title="200 OK: 100%"></i>';
    }
  }

  const listEl = $('#obsHttpList');
  if (listEl) {
    listEl.innerHTML = items.length ? items.map(x => `
      <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--fd-surface-card);border:1px solid var(--fd-line);border-radius:8px;font-size:12px;gap:8px;" title="${esc(x.desc)}">
        <div style="display:flex;align-items:center;gap:8px;min-width:0;">
          <span style="display:inline-block;width:9px;height:9px;border-radius:50%;background:${x.color};flex-shrink:0;"></span>
          <b style="color:var(--fd-ink);font-family:monospace;font-size:12.5px;">${esc(x.label)}</b>
        </div>
        <div style="display:flex;align-items:center;gap:12px;flex-shrink:0;">
          <span style="font-weight:700;color:var(--fd-ink);">${x.count.toLocaleString()} req</span>
          <b style="color:${x.color};min-width:44px;text-align:right;">${x.pct}%</b>
        </div>
      </div>
    `).join('') : '<div class="empty"><i class="fa-solid fa-check-double"></i> Chưa có dữ liệu mã phản hồi</div>';
  }
}

async function renderLearn(v) {
  v.innerHTML = await getTemplate('learn');
  const d = S.data;
  if (!d) return;

  const demoEl = $('#demoBanner');
  if (demoEl) demoEl.style.display = S.demo ? 'flex' : 'none';

  const labels = d.label_distribution || [];
  const mx = Math.max(1, ...labels.map(x => x.count || 0));
  const topList = d.top_dishes || [];
  const t0 = topList[0]?.count || 1;

  $('#valAcceptanceRate').textContent = d.acceptance_rate != null ? d.acceptance_rate + '%' : '—';
  $('#valTotalClasses').textContent = d.total_classes ?? labels.length;

  // 1. Top món nhận diện nhiều nhất
  const topEl = $('#topDishesList');
  if (topEl) {
    if (topList.length) {
      topEl.innerHTML = topList.map(x => `
        <div class="mt">
          <span title="${esc(x.dish_name || x.dish_code)}">${esc(x.dish_name || x.dish_code)}</span>
          <div><i style="width:${Math.max(4, Math.round((x.count / t0) * 100))}%;"></i></div>
          <b>${x.count.toLocaleString()} lượt</b>
        </div>
      `).join('');
    } else {
      topEl.innerHTML = '<div class="empty"><i class="fa-solid fa-trophy"></i> Chưa có dữ liệu nhận diện món ăn</div>';
    }
  }

  // 2. Heatmap phân bổ 67 nhãn món
  const mapEl = $('#labelDistributionMap');
  if (mapEl) {
    if (labels.length) {
      mapEl.innerHTML = labels.map(x => {
        const titleText = `${esc(x.dish_name || x.dish_code)} (${esc(x.dish_code)}): ${x.count} lần`;
        const op = x.count > 0 ? (0.28 + (x.count / mx) * 0.72).toFixed(2) : '0.12';
        return `<i title="${titleText}" style="opacity:${op};"></i>`;
      }).join('');
    } else {
      mapEl.innerHTML = '<div class="empty"><i class="fa-solid fa-tags"></i> Chưa có dữ liệu danh mục nhãn món</div>';
    }
  }

  const renderTableData = (head, rows, emptyMsg) => {
    if (!rows || !rows.length) {
      return `<div class="empty"><i class="fa-solid fa-circle-check" style="color:var(--fd-green);"></i> ${esc(emptyMsg)}</div>`;
    }
    return `
      <table>
        <tr>${head.map(x => `<th>${x}</th>`).join('')}</tr>
        ${rows.map(r => `<tr>${r.map(c => `<td>${esc(c)}</td>`).join('')}</tr>`).join('')}
      </table>`;
  };

  // 3. Cặp món AI đoán nhầm
  const confPairs = d.confusion_pairs || [];
  $('#confusionPairsContainer').innerHTML = renderTableData(
    ['AI dự đoán ban đầu', 'Người dùng sửa lại thành', 'Số lượt'],
    confPairs.map(x => [
      x.ai_name && x.ai_name !== x.ai ? `${x.ai_name} (${x.ai})` : x.ai,
      x.user_name && x.user_name !== x.user ? `${x.user_name} (${x.user})` : x.user,
      x.count + ' lần'
    ]),
    'Chưa ghi nhận phản hồi nhầm lẫn nhãn nào từ người dùng'
  );

  // 4. Cảnh báo độ tin cậy thấp (< 0.45)
  const lowConf = d.low_confidence || [];
  $('#lowConfidenceContainer').innerHTML = renderTableData(
    ['Món ăn', 'Confidence trung bình', 'Số lượt'],
    lowConf.map(x => [
      x.dish_name && x.dish_name !== x.dish_code ? `${x.dish_name} (${x.dish_code})` : x.dish_code,
      (x.avg_confidence * 100).toFixed(1) + '%',
      x.count + ' lần'
    ]),
    'Tất cả lượt nhận diện đều đạt độ tin cậy an toàn (≥ 0.45)'
  );
}

async function renderTable(v, d) {
  v.innerHTML = await getTemplate('table');

  const demoEl = $('#demoBanner');
  if (demoEl) demoEl.style.display = S.demo ? 'flex' : 'none';

  // Sub-resource back bar
  const subBar = $('#subResourceBar');
  if (subBar) {
    if (S.kid) {
      subBar.style.display = 'flex';
      $('#subResourceTitle').innerHTML = `<i class="fa-solid fa-folder-open"></i> ${esc(S.pname)} › ${esc(d.t)}`;
    } else {
      subBar.style.display = 'none';
    }
  }

  // Note rule
  const noteEl = $('#tableNote');
  if (noteEl) {
    if (d.note && !S.kid) {
      noteEl.style.display = 'block';
      $('#tableNoteText').textContent = d.note;
    } else {
      noteEl.style.display = 'none';
    }
  }

  // Retention stats for users
  const retEl = $('#retentionBar');
  if (retEl) {
    if (d.re && S.rs) {
      retEl.style.display = 'grid';
      $('#valRetentionPct').textContent = (S.rs.retained_pct ?? '—') + '%';
      $('#valChurnRiskPct').textContent = (S.rs.churn_risk_pct ?? '—') + '%';
    } else {
      retEl.style.display = 'none';
    }
  }

  // Search box
  const searchWrap = $('#searchBoxWrap');
  if (searchWrap) {
    if (S.kid) {
      searchWrap.style.display = 'none';
    } else {
      searchWrap.style.display = 'block';
      const qInp = $('#q');
      qInp.placeholder = d.ph || 'Tìm kiếm tên, mã...';
      qInp.value = S.q;
      qInp.oninput = () => {
        clearTimeout(qInp.t);
        qInp.t = setTimeout(() => {
          S.q = qInp.value.trim();
          S.page = 1;
          load();
        }, 300);
      };
    }
  }

  // Filter dropdowns
  const flContainer = $('#filterContainer');
  if (flContainer) {
    if (!S.kid && d.fl && d.fl.length) {
      flContainer.innerHTML = d.fl.map(([n, opts]) => `
        <select class="inp" data-f="${n}">
          ${opts.map(x => `<option value="${x[0]}" ${String(S.fl[n] ?? (n === 'status' && d.ops.includes('t') ? 'active' : '')) === String(x[0]) ? 'selected' : ''}>${x[1]}</option>`).join('')}
        </select>
      `).join('');
      flContainer.querySelectorAll('[data-f]').forEach(s => {
        s.onchange = () => {
          S.fl[s.dataset.f] = s.value;
          S.page = 1;
          load();
        };
      });
    } else {
      flContainer.innerHTML = '';
    }
  }

  // Action buttons
  if (d.re) $('#btnReEngage').style.display = 'inline-flex';
  if (d.ops.includes('a')) $('#btnAddRecord').style.display = 'inline-flex';

  // Table Body Rows
  const rows = S.data || [], o = d.ops;
  const tableEl = $('#dataTable'), emptyEl = $('#tableEmpty');
  if (rows.length) {
    tableEl.style.display = 'table';
    emptyEl.style.display = 'none';
    $('#tableHead').innerHTML = `
      <tr>
        ${d.f.map(f => `<th>${f[1]}</th>`).join('')}
        ${o.includes('t') ? '<th>Trạng thái</th>' : ''}
        <th style="text-align:right;">Thao tác</th>
      </tr>`;
    $('#tableBody').innerHTML = rows.map(r => `
      <tr>
        ${d.f.map(f => `<td>${disp(f, r)}</td>`).join('')}
        ${o.includes('t') ? `<td><span class="tag ${r.is_active ? '' : 'off'}"><i class="fa-solid fa-${r.is_active ? 'circle-check' : 'circle-xmark'}"></i> ${r.is_active ? 'Đang dùng' : 'Ngưng dùng'}</span></td>` : ''}
        <td class="ac">
          ${(d.kids || []).map(([k, l]) => `<button class="btn ghost sm" data-a="kid" data-k="${k}" data-id="${r.id}"><i class="fa-solid fa-layer-group"></i> ${l}</button>`).join('')}
          ${o.includes('e') ? `<button class="btn ghost sm" data-a="edit" data-id="${r.id}" title="Chỉnh sửa"><i class="fa-solid fa-pen-to-square"></i> Sửa</button>` : ''}
          ${o.includes('t') ? `<button class="btn ghost sm" data-a="tog" data-id="${r.id}" title="Chuyển trạng thái">${r.is_active ? '<i class="fa-solid fa-pause"></i> Tắt' : '<i class="fa-solid fa-play"></i> Bật'}</button>` : ''}
          ${o.includes('d') ? `<button class="btn ghost sm danger" data-a="del" data-id="${r.id}" title="Xóa"><i class="fa-solid fa-trash"></i></button>` : ''}
          ${o.includes('s') ? `<button class="btn ghost sm" data-a="lock" data-id="${r.id}">${r.status === 'banned' ? '<i class="fa-solid fa-lock-open"></i> Mở khóa' : '<i class="fa-solid fa-lock"></i> Khóa'}</button>` : ''}
          ${o.includes('r') ? `<button class="btn ghost sm" data-a="role" data-id="${r.id}">${r.role === 'admin' ? '<i class="fa-solid fa-user-minus"></i> Về Member' : '<i class="fa-solid fa-user-shield"></i> Lên Admin'}</button>` : ''}
        </td>
      </tr>
    `).join('');
  } else {
    tableEl.style.display = 'none';
    emptyEl.style.display = 'block';
  }

  // Pagination
  const pages = Math.max(1, Math.ceil(S.total / S.perPage));
  const pgBar = $('#paginationBar');
  if (pgBar) {
    if (!S.kid && pages > 1) {
      pgBar.style.display = 'flex';
      $('#pageInfoText').textContent = `Trang ${S.page} / ${pages} (${S.total} kết quả)`;
      const prev = $('#btnPrevPage'), next = $('#btnNextPage');
      prev.disabled = S.page <= 1;
      prev.style.opacity = S.page <= 1 ? '0.5' : '1';
      prev.style.pointerEvents = S.page <= 1 ? 'none' : 'auto';
      next.disabled = S.page >= pages;
      next.style.opacity = S.page >= pages ? '0.5' : '1';
      next.style.pointerEvents = S.page >= pages ? 'none' : 'auto';
    } else {
      pgBar.style.display = 'none';
    }
  }
}

// Master Render Router
async function render() {
  const v = $('#view');
  if (!v) return;
  const d = def();
  if (!d) {
    if (S.scr === 'stats') await renderStats(v);
    else if (S.scr === 'obs') await renderObs(v);
    else if (S.scr === 'learn') await renderLearn(v);
    return;
  }
  await renderTable(v, d);
}

// In-Memory Mutation Simulator for Demo Mode + Real API Handlers
async function mut(m, p, data, msg, id) {
  if (S.demo) {
    const k = key(), d = R[k];
    if (m === 'POST') {
      const newId = Date.now();
      const newItem = { id: newId, is_active: 1, ...data };
      if (S.kid && S.pid) newItem.pid = S.pid;
      if (d && d.seed) d.seed.unshift(newItem);
      if (Array.isArray(S.data)) S.data.unshift(newItem);
    } else if (m === 'PUT') {
      const targetId = id || (data && data.id);
      const row = Array.isArray(S.data) ? S.data.find(x => x.id == targetId) : null;
      if (row) Object.assign(row, data);
    } else if (m === 'PATCH') {
      const row = Array.isArray(S.data) ? S.data.find(x => x.id == id) : null;
      if (row) Object.assign(row, data);
    } else if (m === 'DELETE') {
      if (Array.isArray(S.data)) S.data = S.data.filter(x => x.id != id);
      if (d && d.seed) d.seed = d.seed.filter(x => x.id != id);
    }
    toast(msg);
    await render();
    return;
  }

  try {
    await call(p, { method: m, data });
    toast(msg);
    load();
  } catch (e) {
    toast(e.message);
  }
}

async function refOpts(src) {
  try {
    const r = await call('/admin/' + src + '?status=active&per_page=500');
    return (Array.isArray(r) ? r : r.items).map(x => [x.id, x.name]);
  } catch (e) {
    return (R[src]?.seed || []).map(x => [x.id, x.name]);
  }
}

async function labelOpts(cur) {
  let l;
  try {
    l = await call('/admin/ai/labels');
  } catch (e) {
    l = [
      { dish_code: 'pho_bo', name_full: 'Phở bò', used: 1 },
      { dish_code: 'com_tam_suon', name_full: 'Cơm tấm sườn', used: 1 },
      { dish_code: 'bun_bo_hue', name_full: 'Bún bò Huế', used: 1 },
      { dish_code: 'banh_mi', name_full: 'Bánh mì thịt', used: 1 },
      { dish_code: 'goi_cuon', name_full: 'Gỏi cuốn tôm thịt', used: 1 },
      { dish_code: 'cha_gio', name_full: 'Chả giò chiên', used: 0 },
      { dish_code: 'mi_quang', name_full: 'Mì Quảng', used: 0 },
      { dish_code: 'hu_tieu', name_full: 'Hủ tiếu Nam Vang', used: 0 }
    ];
  }
  return [['', '(Không gán nhãn AI)'], ...l.filter(x => !x.used || x.dish_code === cur).map(x => [x.dish_code, x.dish_code + ' — ' + (x.name_full || x.name || x.dish_code)])];
}

async function form(r) {
  const d = def();
  const fs = await Promise.all(d.f.map(async f => {
    if (f[2] === 'label') return [f[0], f[1], 'select', await labelOpts(r && r.dish_code)];
    if (f[2] === 'ref') return [f[0], f[1], 'select', await refOpts(f[3])];
    return f;
  }));

  const modalHtml = await getTemplate('modal');
  const tempDiv = document.createElement('div');
  tempDiv.innerHTML = modalHtml;
  const m = tempDiv.firstElementChild;

  m.querySelector('#modalTitle').innerHTML = `
    <i class="fa-solid fa-${r ? 'pen-to-square' : 'plus-circle'} text-green"></i> ${r ? 'Chỉnh sửa' : 'Thêm mới'}: ${esc(d.t)}
  `;

  m.querySelector('#modalFields').innerHTML = fs.map(([k, l, t, o]) => `
    <label>${l}${o && o.h ? ` <small>(${o.h})</small>` : ''}</label>
    ${t === 'select' ? `
      <select class="inp" name="${k}" style="width:100%;">
        ${o.map(x => `<option value="${esc(x[0])}" ${r && String(r[k]) === String(x[0]) ? 'selected' : ''}>${esc(x[1])}</option>`).join('')}
      </select>` : `
      <input class="inp" name="${k}" type="${t === 'number' ? 'number' : 'text'}" step="any" ${o && o.opt ? '' : 'required'} value="${esc(r ? r[k] : '')}" style="width:100%;">`}
  `).join('');

  document.body.appendChild(m);
  m.onclick = e => { if (e.target === m) m.remove(); };
  m.querySelector('#modalCancelBtn').onclick = () => m.remove();

  m.querySelector('#adminModalForm').onsubmit = ev => {
    ev.preventDefault();
    const v = {}, er = m.querySelector('#modalError');
    for (const [k, l, t, o] of fs) {
      let x = ev.target[k].value.trim();
      if (t === 'number') {
        x = Number(x);
        if (x < o.min || x > o.max) return (er.textContent = `${l} phải nằm trong khoảng ${o.min} – ${o.max}.`);
      } else if (t === 'select') {
        const found = o.find(y => String(y[0]) === x);
        x = found ? found[0] : x;
      } else if (o && o.re && !o.re.test(x)) {
        return (er.textContent = `${l} chỉ gồm ${o.h}.`);
      }
      v[k] = x;
    }
    const c = d.check && d.check(v);
    if (c) return (er.textContent = c);
    m.remove();
    r ? mut('PUT', item(r.id), v, 'Đã cập nhật thành công.', r.id) : mut('POST', base(), v, 'Đã thêm mới thành công.');
  };
}

// Global Event Listener for Admin Studio
document.addEventListener('click', ev => {
  const s = ev.target.closest('[data-s]');
  if (s) {
    S = { ...S, scr: s.dataset.s, kid: null, page: 1, q: '', fl: {}, rs: null };
    closeMobileSidebar();
    load();
    return;
  }
  const a = ev.target.closest('[data-a]');
  if (!a) return;
  const t = a.dataset.a, id = a.dataset.id, r = Array.isArray(S.data) ? S.data.find(x => x.id == id) : null;

  if (t === 'add') form();
  else if (t === 'edit') form(r);
  else if (t === 'per') { S.per = a.dataset.p; load(); }
  else if (t === 'obs-per') { S.obsPer = a.dataset.p; load(); }
  else if (t === 'kid') { S = { ...S, kid: a.dataset.k, pid: id, pname: r ? (r.name || r.name_vi || 'Mục #' + id) : 'Chi tiết', page: 1, q: '', fl: {} }; load(); }
  else if (t === 'back') { S = { ...S, kid: null, pid: null, page: 1 }; load(); }
  else if (t === 'pg') { S.page += Number(a.dataset.d); load(); }
  else if (t === 'tog') { mut('PATCH', item(id), { is_active: r.is_active ? 0 : 1 }, r.is_active ? 'Đã ngưng sử dụng.' : 'Đã kích hoạt lại.', id); }
  else if (t === 'del') { if (confirm('Bạn chắc chắn muốn xóa bản ghi này?')) mut('DELETE', item(id), null, 'Đã xóa bản ghi.', id); }
  else if (t === 'lock') {
    const nextSt = r.status === 'banned' ? 'active' : 'banned';
    if (confirm(r.status === 'banned' ? 'Mở khóa cho tài khoản này?' : 'Khóa truy cập của tài khoản này?')) {
      mut('PATCH', '/admin/users/' + id + '/status', { status: nextSt }, 'Đã cập nhật trạng thái người dùng.', id);
    }
  }
  else if (t === 'role') {
    const nextRole = r.role === 'admin' ? 'member' : 'admin';
    if (confirm('Thay đổi vai trò của ' + r.display_name + ' thành ' + (nextRole === 'admin' ? 'Quản trị viên' : 'Thành viên') + '?')) {
      mut('PATCH', '/admin/users/' + id + '/status', { role: nextRole }, 'Đã cập nhật vai trò tài khoản.', id);
    }
  }
  else if (t === 're') {
    const n = S.fl.inactive_days || 7;
    if (confirm('Tạo chiến dịch gửi email nhắc nhở cho tất cả người dùng offline ≥ ' + n + ' ngày?')) {
      mut('POST', '/admin/users/re-engage', { inactive_days: Number(n) }, 'Đã tạo chiến dịch gửi email nhắc nhở.');
    }
  }
  else if (t === 'export') {
    mut('POST', '/admin/ai/export-retrain-dataset', {}, 'Đã gửi yêu cầu nén và xuất tập dữ liệu.');
  }
});

// Render Sidebar Navigation
(function nav() {
  let g = '', h = '';
  for (const k in SC) {
    if (SC[k][0] !== g) {
      g = SC[k][0];
      h += `<div class="fd-nav-caption">${g.toUpperCase()}</div>`;
    }
    h += `<button class="app-nav-link" data-s="${k}"><i class="fa-solid fa-${SC[k][2]}"></i><span>${SC[k][1]}</span></button>`;
  }
  const navEl = $('#nav');
  if (navEl) navEl.innerHTML = h;

  // Mobile Bottom Navigation
  const mTabs = [
    ['stats', 'Tổng quan', 'gauge'],
    ['obs', 'AI Metric', 'heart-pulse'],
    ['dishes', 'Món ăn', 'bowl-food'],
    ['ingredients', 'Nguyên liệu', 'carrot'],
    ['users', 'Người dùng', 'users']
  ];
  const mnavEl = $('#mnav');
  if (mnavEl) {
    mnavEl.innerHTML = mTabs.map(([k, l, ico]) => `
      <button class="app-nav-link" data-s="${k}"><i class="fa-solid fa-${ico}"></i><span>${l}</span></button>
    `).join('') + `<button class="app-nav-link" onclick="toggleMobileSidebar()"><i class="fa-solid fa-bars"></i><span>Tất cả</span></button>`;
  }
})();

// Set Date in Topbar
(function initDate() {
  const d = new Date();
  const el = $('#todayHeader');
  if (el) el.textContent = d.toLocaleDateString('vi-VN', { weekday: 'short', day: '2-digit', month: '2-digit', year: 'numeric' });
})();

// Logout Admin Function
async function logoutAdmin() {
  if (confirm('Bạn chắc chắn muốn đăng xuất khỏi phiên Quản trị viên?')) {
    try {
      localStorage.removeItem('flexidiet-user');
      sessionStorage.clear();
      await FlexiAPI.request('/auth/logout', { method: 'POST' });
    } catch (e) {}
    location.replace('index.html');
  }
}

// Smart Admin Auth Guard: Chỉ tài khoản Admin mới được vào
(async () => {
  const isHttp = location.protocol.startsWith('http');
  if (isHttp) {
    try {
      const me = await call('/auth/me');
      const role = me.role || (me.user && me.user.role);
      if (role !== 'admin') {
        alert('Bạn không có quyền truy cập trang Quản trị! (Chỉ dành cho tài khoản Admin).');
        return location.replace('app.html');
      }
      load();
      return;
    } catch (e) {
      let savedUser = null;
      try { savedUser = JSON.parse(localStorage.getItem('flexidiet-user')); } catch (err) {}
      if (savedUser && savedUser.role === 'admin') {
        load();
        return;
      }
      if (e && e.status === 401) {
        alert('Vui lòng đăng nhập bằng tài khoản Quản trị viên (admin@flexidiet.vn) để truy cập trang này.');
        return location.replace('index.html');
      }
    }
  }

  // Fallback demo simulation
  load();
})();
