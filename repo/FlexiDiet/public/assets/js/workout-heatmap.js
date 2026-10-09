/**
 * FlexiDiet workout contribution calendar (frontend demo).
 * One source of truth for actual entries: appState.workoutLog.
 * Past days can optionally display deterministic SAMPLE data; the UI labels it.
 */
const workoutHeatmapState = { year: new Date().getFullYear(), showSample: true, selectedDate: localDayKey() };

function fdHeatmapDate(date) {
  return date.toLocaleDateString('vi-VN', {day: '2-digit', month: '2-digit', year: 'numeric'});
}

// Simulated days are stable on refresh and never count as actual workout entries.
function fdSampleWorkout(date) {
  const today = localDayKey();
  if (localDayKey(date) >= today) return null;
  const code = date.getFullYear() * 10000 + (date.getMonth() + 1) * 100 + date.getDate();
  const random = salt => {
    const value = Math.sin(code * 0.1413 + salt * 91.17) * 43758.5453;
    return value - Math.floor(value);
  };
  const weekday = date.getDay();
  if (random(1) < (weekday === 0 ? .62 : .39)) return null;
  const minutes = [20, 30, 40, 55, 65, 80, 95, 115][Math.floor(random(2) * 8)];
  return {minutes, sessions: minutes >= 90 ? 2 : 1, sample: true};
}

function fdWorkoutActivityForYear(year) {
  const map = new Map();
  const today = localDayKey();
  const cursor = new Date(year, 0, 1, 12);
  while (cursor.getFullYear() === year) {
    const key = localDayKey(cursor);
    if (workoutHeatmapState.showSample && key < today) {
      const sample = fdSampleWorkout(cursor);
      if (sample) map.set(key, sample);
    }
    cursor.setDate(cursor.getDate() + 1);
  }
  // Recorded workouts are not generated twice; old items without date are today's demo data.
  for (const workout of appState.workoutLog) {
    const key = workout.date || today;
    if (!key.startsWith(`${year}-`) || key > today) continue;
    const current = map.get(key) || {minutes: 0, sessions: 0, sample: false};
    map.set(key, {
      minutes: current.minutes + Math.max(0, Number(workout.minutes) || 0),
      sessions: current.sessions + 1,
      sample: Boolean(current.sample)
    });
  }
  return map;
}

function fdWorkoutLevel(minutes) {
  if (!minutes) return 0;
  if (minutes < 30) return 1;
  if (minutes < 60) return 2;
  if (minutes < 90) return 3;
  return 4;
}

function fdWorkoutLongestStreak(year, entries) {
  let max = 0, count = 0;
  const today = localDayKey();
  const cursor = new Date(year, 0, 1, 12);
  while (cursor.getFullYear() === year && localDayKey(cursor) <= today) {
    count = (entries.get(localDayKey(cursor))?.minutes || 0) > 0 ? count + 1 : 0;
    max = Math.max(max, count);
    cursor.setDate(cursor.getDate() + 1);
  }
  return max;
}

function fdWorkoutYearOptions() {
  const select = document.getElementById('workoutHeatmapYear');
  if (!select || select.options.length) return;
  const year = new Date().getFullYear();
  for (let n = year; n >= year - 2; n--) {
    const option = new Option(String(n), String(n));
    select.add(option);
  }
  select.value = String(workoutHeatmapState.year);
  select.addEventListener('change', () => {
    workoutHeatmapState.year = Number(select.value);
    workoutHeatmapState.selectedDate = `${select.value}-01-01`;
    renderWorkoutHeatmap();
  });
  const sampleSwitch = document.getElementById('workoutHeatmapSample');
  if (sampleSwitch) {
    sampleSwitch.checked = workoutHeatmapState.showSample;
    sampleSwitch.addEventListener('change', () => {
      workoutHeatmapState.showSample = sampleSwitch.checked;
      renderWorkoutHeatmap();
    });
  }
}

function renderWorkoutHeatmap() {
  const grid = document.getElementById('workoutHeatmapGrid');
  const months = document.getElementById('workoutHeatmapMonths');
  if (!grid || !months) return;
  fdWorkoutYearOptions();
  const year = workoutHeatmapState.year;
  const entries = fdWorkoutActivityForYear(year);
  const start = new Date(year, 0, 1, 12);
  const end = new Date(year, 11, 31, 12);
  const offset = start.getDay(); // Sunday-first, matching GitHub's week columns.
  const numDays = Math.round((end - start) / 86400000) + 1;
  const weekCount = Math.ceil((numDays + offset) / 7);
  const nowKey = localDayKey();
  grid.style.gridTemplateColumns = `repeat(${weekCount}, 13px)`;
  months.style.gridTemplateColumns = `repeat(${weekCount}, 13px)`;
  grid.replaceChildren();
  months.replaceChildren();
  grid.setAttribute('aria-label', `Lịch luyện tập năm ${year}`);

  const marker = document.createDocumentFragment();
  const monthNames = ['Th1','Th2','Th3','Th4','Th5','Th6','Th7','Th8','Th9','Th10','Th11','Th12'];
  for (let m = 0; m < 12; m++) {
    const first = new Date(year, m, 1, 12);
    const index = Math.floor((offset + Math.round((first - start) / 86400000)) / 7);
    const label = document.createElement('span');
    label.textContent = monthNames[m];
    label.style.gridColumn = `${index + 1} / span 3`;
    marker.appendChild(label);
  }
  months.appendChild(marker);

  const cells = document.createDocumentFragment();
  for (let index = 0; index < weekCount * 7; index++) {
    const dayIndex = index - offset;
    if (dayIndex < 0 || dayIndex >= numDays) {
      const placeholder = document.createElement('span');
      placeholder.className = 'fd-hm-placeholder';
      placeholder.setAttribute('aria-hidden', 'true');
      cells.appendChild(placeholder);
      continue;
    }
    const date = new Date(year, 0, 1 + dayIndex, 12);
    const key = localDayKey(date);
    const activity = entries.get(key);
    const minutes = activity?.minutes || 0;
    const sessions = activity?.sessions || 0;
    const future = key > nowKey;
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'fd-hm-day' + (future ? ' is-future' : '');
    button.dataset.level = String(fdWorkoutLevel(minutes));
    button.disabled = future;
    button.setAttribute('aria-label', `${fdHeatmapDate(date)}: ${minutes} phút tập, ${sessions} buổi${activity?.sample ? ' (có dữ liệu mẫu)' : ''}`);
    button.title = `${fdHeatmapDate(date)} • ${minutes ? minutes + ' phút tập' : 'Chưa tập'}${activity?.sample ? ' (minh họa)' : ''}`;
    button.setAttribute('aria-pressed', String(key === workoutHeatmapState.selectedDate));
    button.addEventListener('click', () => {
      workoutHeatmapState.selectedDate = key;
      renderWorkoutHeatmap();
    });
    cells.appendChild(button);
  }
  grid.appendChild(cells);

  const active = [...entries.values()].filter(item => item.minutes > 0);
  const totalMinutes = active.reduce((sum, item) => sum + item.minutes, 0);
  const number = new Intl.NumberFormat('vi-VN');
  const write = (id, value) => { const el = document.getElementById(id); if (el) el.textContent = value; };
  write('workoutHeatmapActiveDays', number.format(active.length));
  write('workoutHeatmapTotalTime', `${number.format(totalMinutes)} phút`);
  write('workoutHeatmapStreak', `${fdWorkoutLongestStreak(year, entries)} ngày`);
  write('workoutHeatmapTotalTimeNote', workoutHeatmapState.showSample ? 'bao gồm dữ liệu minh họa' : 'từ nhật ký hiện tại');
  const todayBurned = appState.workoutLog
    .filter(item => (item.date || nowKey) === nowKey)
    .reduce((sum, item) => sum + (Number(item.burned) || 0), 0);
  const todayBadge = document.getElementById('workoutTodayBurned');
  if (todayBadge) {
    todayBadge.replaceChildren();
    const icon = document.createElement('i');
    icon.className = 'fa-solid fa-fire';
    todayBadge.append(icon, ` Hôm nay đã đốt: +${number.format(todayBurned)} kcal`);
  }

  const selectedKey = workoutHeatmapState.selectedDate;
  const selected = entries.get(selectedKey);
  const selectedDate = new Date(selectedKey + 'T12:00:00');
  const detail = document.getElementById('workoutHeatmapSelection');
  if (detail) {
    const bold = document.createElement('strong');
    bold.textContent = fdHeatmapDate(selectedDate);
    const rest = document.createElement('span');
    rest.textContent = ` — ${selected?.minutes || 0} phút · ${selected?.sessions || 0} buổi${selected?.sample ? ' (có dữ liệu minh họa)' : ''}`;
    detail.replaceChildren(bold, rest);
  }
  const footnote = document.getElementById('workoutHeatmapNote');
  if (footnote) footnote.textContent = workoutHeatmapState.showSample
    ? 'Các ngày trước hôm nay có dữ liệu minh họa để xem thử biểu đồ. Buổi tập vừa ghi lấy từ nhật ký demo và sẽ mất khi tải lại trang.'
    : 'Đang chỉ xem các buổi tập có trong nhật ký demo hiện tại. Dữ liệu chưa được lưu vào database.';
}

function renderWorkoutHistory() {
  const list = document.getElementById('workoutHistoryList');
  if (!list) return;
  list.replaceChildren();
  if (!appState.workoutLog.length) {
    const empty = document.createElement('p');
    empty.className = 'sub-muted';
    empty.textContent = 'Chưa có buổi tập nào trong nhật ký hiện tại.';
    list.appendChild(empty);
    return;
  }
  appState.workoutLog.forEach((item, index) => {
    const row = document.createElement('div'); row.className = 'wh-item';
    const icon = document.createElement('div'); icon.className = 'wh-icon bg-purple';
    icon.innerHTML = '<i class="fa-solid fa-dumbbell"></i>';
    const meta = document.createElement('div'); meta.className = 'wh-meta';
    const name = document.createElement('h4'); name.textContent = item.name;
    const time = document.createElement('span'); time.className = 'wh-sub';
    time.textContent = `${item.date || localDayKey()} • ${item.time || ''} • ${item.minutes} phút`;
    meta.append(name, time);
    const cal = document.createElement('div'); cal.className = 'wh-cal text-purple';
    cal.textContent = `+${item.burned} kcal`;
    const remove = document.createElement('button');
    remove.type = 'button'; remove.className = 'fd-hm-delete';
    remove.title = 'Xóa buổi tập'; remove.setAttribute('aria-label', `Xóa ${item.name}`);
    remove.innerHTML = '<i class="fa-solid fa-trash-can"></i>';
    remove.addEventListener('click', () => deleteActivity('workout', index));
    row.append(icon, meta, cal, remove);
    list.appendChild(row);
  });
}
