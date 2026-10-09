/** Presentation only. Shared demo state remains in app.js. */
const fdNumber = number => new Intl.NumberFormat('vi-VN', {maximumFractionDigits:0}).format(number);
const fdLabels = {breakfast:'Bữa sáng', lunch:'Bữa trưa', dinner:'Bữa tối', snack:'Bữa phụ'};
const fdIcons = {breakfast:'fa-mug-hot',lunch:'fa-bowl-rice',dinner:'fa-moon',snack:'fa-apple-whole'};

function fdFillText(id, value) {
  const el = document.getElementById(id);
  if (el) el.textContent = value;
}
function renderDashboard() {
  if (!document.getElementById('view-dashboard')) return;
  const e = appState.energy;
  e.remaining = e.target + e.burned - e.consumed;
  const total = e.target + e.burned;
  const fraction = total > 0 ? (e.consumed / total) : 0;
  const percent = Math.round(fraction * 100);
  fdFillText('dashRemaining', fdNumber(e.remaining));
  fdFillText('dashTarget', fdNumber(e.target));
  fdFillText('dashConsumed', fdNumber(e.consumed));
  fdFillText('dashBurned', '+' + fdNumber(e.burned));
  fdFillText('dashPercent', percent + '%');
  fdFillText('dashStatFood', fdNumber(e.consumed));
  fdFillText('dashStatWorkout', fdNumber(e.burned));
  fdFillText('dashWater', String(e.water).replace('.', ','));
  fdFillText('dashEnergyHint', e.remaining >= 0 ? 'Bạn đang làm rất tốt. Tiếp tục lắng nghe cơ thể mình nhé!' : 'Bạn đã vượt ngân sách tham khảo hôm nay. Hãy cân bằng nhẹ nhàng nhé.');
  const ring = document.getElementById('dashRing');
  if (ring) ring.style.setProperty('--ring-pct', Math.min(100,Math.max(0,percent))+'%');
  const waterFill = document.getElementById('dashWaterFill');
  if (waterFill) waterFill.style.width = Math.min(100, e.water/e.waterTarget*100)+'%';
  fdFillText('dashboardGreeting', 'Chào ' + (appState.user.name || 'bạn').split(/\s+/).slice(-1)[0] + ', hôm nay thế nào?');
  const now = new Date();
  const date = now.toLocaleDateString('vi-VN',{weekday:'long',day:'2-digit',month:'2-digit',year:'numeric'});
  fdFillText('dashboardDate', date);
  fdFillText('todayHeader', now.toLocaleDateString('vi-VN',{day:'2-digit',month:'long',year:'numeric'}));
  renderDashboardMeals();
  renderDashboardMacros();
}
function renderDashboardMeals() {
  const container = document.getElementById('dashMeals');
  if (!container) return;
  container.replaceChildren();
  for (const meal of ['breakfast','lunch','dinner','snack']) {
    const entries = appState.foodLog.filter(item=>item.meal===meal);
    const count = entries.reduce((acc,item)=>acc+item.cal,0);
    const row = document.createElement('div');
    row.className = 'fd-meal-row';
    const icon = document.createElement('span');
    icon.className = 'fd-meal-icon ' + meal;
    const i = document.createElement('i'); i.className='fa-solid '+fdIcons[meal]; icon.appendChild(i);
    const info = document.createElement('div'); info.className='fd-meal-info';
    const title = document.createElement('strong'); title.textContent=fdLabels[meal];
    const desc = document.createElement('span');
    desc.textContent=entries.length ? entries.map(item=>item.name).join(' · ') : 'Chưa ghi nhận món ăn';
    info.append(title,desc);
    const side = document.createElement('div'); side.className='fd-meal-side';
    const amount = document.createElement('strong'); amount.textContent = count ? fdNumber(count)+' kcal' : '—';
    const time = document.createElement('small'); time.textContent = entries[0]?.time || 'Chưa có';
    side.append(amount,time);
    const button = document.createElement('button'); button.className='fd-meal-open';
    button.setAttribute('aria-label','Thêm món vào '+fdLabels[meal]);
    button.innerHTML='<i class="fa-solid fa-plus"></i>';
    button.addEventListener('click',()=> {switchCreateTab('food'); openCreateQuickModal(meal);});
    row.append(icon,info,side,button); container.append(row);
  }
}
function renderDashboardMacros() {
  const box = document.getElementById('dashMacros');
  if (!box) return;
  box.replaceChildren();
  for (const [key, label, color] of [['protein','Protein','#6C8BFF'],['carbs','Carbohydrate','#EEA854'],['fat','Chất béo','#86BC73']]) {
    const val = appState.macros[key];
    const goal = appState.macros.targets[key];
    const row = document.createElement('div'); row.className='fd-macro-item';
    row.innerHTML='<div class="fd-macro-meta"><span></span><strong></strong></div><div class="fd-macro-track"><div class="fd-macro-fill"></div></div>';
    row.querySelector('.fd-macro-meta span').textContent=label;
    row.querySelector('.fd-macro-meta strong').textContent=Math.round(val)+' / '+goal+' g';
    const fill = row.querySelector('.fd-macro-fill'); fill.style.width=Math.min(100,Math.round(val/goal*100))+'%'; fill.style.backgroundColor=color;
    box.appendChild(row);
  }
}

/** Render a coherent demo-only daily list from the same state as Dashboard. */
function renderActivityList() {
  const tbody = document.getElementById('listTableBody');
  if (!tbody) return;
  tbody.replaceChildren();
  const entries = [
    ...appState.foodLog.map((item,index)=>({type:'food',item,index})),
    ...appState.workoutLog.map((item,index)=>({type:'workout',item,index}))
  ];
  for (const {type,item,index} of entries) {
    const isFood = type === 'food';
    const row = document.createElement('tr');
    const cols = [
      isFood ? 'Món ăn' : 'Luyện tập', item.name,
      isFood ? fdLabels[item.meal]+' ('+item.time+')' : item.time,
      isFood ? '—' : item.minutes+' phút',
      (isFood?'':'−')+fdNumber(isFood?item.cal:item.burned),
      isFood?item.prot+' g':'—',isFood?item.carb+' g':'—',isFood?item.fat+' g':'—',
      isFood?'Đã ghi':'Hoàn thành'
    ];
    for (const [i,val] of cols.entries()) {
      const cell=document.createElement('td');
      cell.textContent=val;
      if (i===1 || i===4) cell.style.fontWeight='700';
      row.appendChild(cell);
    }
    const td=document.createElement('td');
    const del=document.createElement('button');
    del.type='button';del.className='btn-tbl-del';
    del.innerHTML='<i class="fa-solid fa-trash-can"></i>';
    del.setAttribute('aria-label','Xóa '+item.name);
    del.addEventListener('click',()=>deleteActivity(type,index));
    td.appendChild(del);row.appendChild(td);
    tbody.appendChild(row);
  }
  if (!entries.length) {
    const row=document.createElement('tr');const cell=document.createElement('td');
    cell.colSpan=10;cell.textContent='Chưa có hoạt động được ghi hôm nay.';
    row.appendChild(cell);tbody.appendChild(row);
  }
}
function deleteActivity(type,index) {
  const isFood=type==='food';
  const target=isFood?appState.foodLog:appState.workoutLog;
  const [item]=target.splice(index,1);
  if (!item) return;
  if (isFood) {
    appState.energy.consumed-=item.cal;
    appState.macros.protein=Math.max(0,appState.macros.protein-item.prot);
    appState.macros.carbs=Math.max(0,appState.macros.carbs-item.carb);
    appState.macros.fat=Math.max(0,appState.macros.fat-item.fat);
  } else appState.energy.burned-=item.burned;
  updateEnergyDisplay();
  if (typeof showToast==='function') showToast('Đã xóa '+item.name+' khỏi nhật ký demo.','info');
}
