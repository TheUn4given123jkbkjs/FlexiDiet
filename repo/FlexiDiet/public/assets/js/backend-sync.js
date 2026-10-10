/**
 * FlexiDiet API integration layer.
 * Overrides the old demo handlers without rewriting the page modules.
 * appState is a render cache; PHP/MySQL is the single source of truth.
 */
const fdCalendarCache = Object.create(null);
let fdLoading = false;
function fdIsoDay() {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
}
function fdAPIError(error) {
  console.error('FlexiDiet API:', error);
  showToast(error.message || 'Thao tác không thành công', 'warning');
}
function fdMapMeal(entry) {
  return {
    id:Number(entry.id),date:entry.entry_date,meal:entry.meal_type,name:entry.name,
    cal:Number(entry.total_kcal),prot:Number(entry.total_protein_g),carb:Number(entry.total_carb_g),fat:Number(entry.total_fat_g),
    time:entry.created_at?.slice(11,16) || ''
  };
}
function fdMapWorkout(w) {
  return {id:Number(w.id),date:w.workout_date,name:w.exercise_name,code:w.exercise_code,
    minutes:Number(w.duration_min),burned:Number(w.raw_kcal),time:w.created_at?.slice(11,16) || ''};
}
function fdFillUser(profile) {
  Object.assign(appState.user, {
    name:profile.display_name,email:profile.email,gender:profile.sex,
    weight:Number(profile.weight_kg),height:Number(profile.height_cm),goal:profile.goal,
    targetWeight:Number(profile.target_weight_kg || 0)
  });
  const texts = {profileName:profile.display_name,profileEmail:profile.email,greetingName:`Xin chào, ${profile.display_name}`};
  for (const [id,value] of Object.entries(texts)) { const el = document.getElementById(id); if (el) el.textContent=value; }
  const vals = {'prof-weight':profile.weight_kg,'prof-height':profile.height_cm,'prof-goal':profile.goal,'prof-credit-policy':profile.credit_policy,'prof-target-weight':profile.target_weight_kg || ''};
  for (const [id,value] of Object.entries(vals)) {const el=document.getElementById(id);if (el) el.value=value;}
}
async function fdRefreshHeatmap(year) {
  const y = Number(year) || new Date().getFullYear();
  try {
    const entries = await FlexiAPI.request(`/workouts/heatmap?year=${y}`);
    fdCalendarCache[y] = entries;
    renderWorkoutHeatmap();
  } catch (error) { fdAPIError(error); }
}
// The yearly calendar must contain only real MySQL logs, never generated sample days.
workoutHeatmapState.showSample = false;
fdWorkoutActivityForYear = function(year) {
  const map = new Map();
  for (const row of fdCalendarCache[year] || []) map.set(row.date, {minutes:Number(row.minutes),sessions:Number(row.sessions),sample:false});
  return map;
};
const fdOldHeatmapRender = renderWorkoutHeatmap;
renderWorkoutHeatmap = function() {
  fdOldHeatmapRender();
  const note=document.getElementById('workoutHeatmapNote');
  if (note) note.textContent='Dữ liệu từ nhật ký luyện tập MySQL; các ngày trống nghĩa là chưa ghi buổi tập.';
  const timeNote=document.getElementById('workoutHeatmapTotalTimeNote');
  if (timeNote) timeNote.textContent='dữ liệu thực đã lưu';
};

async function fdRefreshDashboard() {
  if (fdLoading) return;
  fdLoading = true;
  try {
    const data = await FlexiAPI.request('/dashboard');
    const p = data.profile; fdFillUser(p);
    const b = data.budget;
    appState.energy = {
      target:b.target_kcal,consumed:b.consumed_kcal,burned:b.exercise_credit_kcal,remaining:b.remaining_kcal,
      rawBurned:b.exercise_raw_kcal, water:Number((b.water_ml/1000).toFixed(2)),waterTarget:2.5,steps:0
    };
    appState.user.bmr=b.bmr_kcal;
    appState.user.targetCalories=b.target_kcal;
    appState.foodLog=data.meals.map(fdMapMeal);
    appState.workoutLog=data.workouts.map(fdMapWorkout);
    appState.macros = {
      protein:b.protein_g,carbs:b.carb_g,fat:b.fat_g,
      targets:{protein:Math.round(b.target_kcal*0.3/4),carbs:Math.round(b.target_kcal*0.45/4),fat:Math.round(b.target_kcal*0.25/9)}
    };
    const preview=document.querySelector('#view-workout-log .box-head p');
    if (preview) preview.textContent=`Calo = MET × cân nặng (${appState.user.weight} kg) × thời gian (giờ). Ngân sách chỉ cộng theo chính sách đã chọn.`;
    updateEnergyDisplay();
    if (b.floor_applied) showToast('Ngân sách đang áp dụng mức sàn tối thiểu theo thiết kế ứng dụng.', 'info');
    return data;
  } catch (error) { fdAPIError(error); throw error; }
  finally { fdLoading=false; }
}
async function fdInitialLoad() {
  try {
    const auth = await FlexiAPI.request('/auth/me');
    if (auth?.user) {
      localStorage.setItem('flexidiet-user', JSON.stringify({
        id: auth.user.id,
        name: auth.user.display_name,
        email: auth.user.email,
        role: auth.user.role
      }));
    }
  } catch (e) {
    location.replace('index.html'); return;
  }
  try {
    await fdRefreshDashboard();
    await fdRefreshHeatmap(workoutHeatmapState.year);
    const yearControl=document.getElementById('workoutHeatmapYear');
    if (yearControl) yearControl.addEventListener('change',()=>fdRefreshHeatmap(yearControl.value));
    showToast('Đã kết nối tài khoản và dữ liệu MySQL.', 'success');
  } catch (error) { showToast('Không thể tải dữ liệu. Kiểm tra MySQL và thử tải lại trang.', 'warning'); }
}

// On app initialization old applyCurrentUser() is replaced; no localStorage identities.
applyCurrentUser = function() {};
logout = async function() {
  try {
    localStorage.removeItem('flexidiet-user');
    await FlexiAPI.request('/auth/logout', {method:'POST'});
    location.replace('index.html');
  }
  catch (error) { fdAPIError(error); }
};

async function fdSaveMeal(data) {
  try {
    const saved = await FlexiAPI.request('/meals', {method:'POST',data});
    await fdRefreshDashboard();
    showToast(`Đã lưu món "${saved.name}" vào MySQL.`, 'success');
    switchTab('dashboard');
    return true;
  } catch (error) { fdAPIError(error); return false; }
}
handleQuickFoodSubmit = async function(e) {
  e.preventDefault();
  const data = {
    name:document.getElementById('qfName').value.trim(),
    meal_type:document.getElementById('qfMeal').value,
    total_kcal:Number(document.getElementById('qfCal').value),
    total_protein_g:Number(document.getElementById('qfProt').value),
    total_carb_g:Number(document.getElementById('qfCarb').value),
    total_fat_g:Number(document.getElementById('qfFat').value),
  };
  const saved = await fdSaveMeal(data);
  if (saved) closeCreateQuickModal();
};
// Legacy call sites: suggestion cards and board quick-add route to the SAME persistence API.
addCardToKanban = async function(name,meal,cal,prot,carb,fat) {
  return fdSaveMeal({name,meal_type:meal,total_kcal:cal,total_protein_g:prot,total_carb_g:carb,total_fat_g:fat});
};
quickAddFood = function(name,cal,prot,carb,fat) { return addCardToKanban(name,'dinner',cal,prot,carb,fat); };

async function fdSaveWorkout(code,minutes,distance = null) {
  try {
    await FlexiAPI.request('/workouts',{method:'POST',data:{exercise_code:code,duration_min:minutes,distance_km:distance,calorie_source:'met'}});
    await fdRefreshDashboard();
    await fdRefreshHeatmap(workoutHeatmapState.year);
    showToast('Đã lưu buổi tập vào MySQL.', 'success');
    switchTab('dashboard');
    return true;
  } catch (error) { fdAPIError(error); return false; }
}
handleWorkoutSubmit = async function(e) {
  e.preventDefault();
  const code=document.getElementById('sportSelect').value;
  const mins=Number(document.getElementById('workoutDuration').value);
  const km=Number(document.getElementById('workoutDistance').value);
  await fdSaveWorkout(code,mins,km>0?km:null);
};
handleQuickWorkoutSubmit = async function(e) {
  e.preventDefault();
  const saved=await fdSaveWorkout(document.getElementById('qwSport').value, Number(document.getElementById('qwDuration').value));
  if (saved) closeCreateQuickModal();
};
addWaterQuick = async function(amount) {
  try {
    await FlexiAPI.request('/water',{method:'POST',data:{amount_ml:Math.round(amount*1000)}});
    await fdRefreshDashboard();
    showToast(`Đã lưu +${Math.round(amount*1000)} ml nước.`, 'success');
  } catch (error) { fdAPIError(error); }
};
updateProfileMetrics = async function() {
  try {
    await FlexiAPI.request('/profile', {method:'PATCH',data:{
      weight_kg:Number(document.getElementById('prof-weight').value),
      height_cm:Number(document.getElementById('prof-height').value),
      goal:document.getElementById('prof-goal').value,
      credit_policy:document.getElementById('prof-credit-policy').value,
      target_weight_kg:document.getElementById('prof-target-weight').value === '' ? null : Number(document.getElementById('prof-target-weight').value)
    }});
    await fdRefreshDashboard();
    showToast('Đã cập nhật hồ sơ và ngân sách hôm nay.', 'success');
  } catch (error) { fdAPIError(error); }
};
deleteActivity = async function(type,index) {
  const items=type==='food' ? appState.foodLog : appState.workoutLog;
  const item=items[index];
  if (!item?.id) return;
  try {
    await FlexiAPI.request(`/${type==='food'?'meals':'workouts'}/${item.id}`,{method:'DELETE'});
    await fdRefreshDashboard();
    if (type==='workout') await fdRefreshHeatmap(workoutHeatmapState.year);
    showToast('Đã xóa mục khỏi MySQL.', 'success');
  } catch (error) { fdAPIError(error); }
};
// Food scanning is NOT implemented yet. No fabricated AI predictions may be saved as AI.
handleImageUpload = function() { showToast('AI nhận diện ảnh chưa được kết nối. Hãy thêm món thủ công.', 'warning'); };
triggerAIAnalysis = function() { showToast('Phân tích mô tả AI chưa được kết nối backend.', 'warning'); };
loadFoodSample = function(key) {
  const sample=appState.foodPresets[key];
  if (!sample) return;
  currentScannedBase={...sample};
  renderAIResult(currentScannedBase);
  showToast('Đây là món dữ liệu mẫu; không phải kết quả AI thật.', 'info');
};
saveScannedFoodToLog = async function() {
  if (!currentScannedBase) return showToast('Chọn món mẫu hoặc ghi món thủ công.', 'warning');
  const factor=Number(document.getElementById('gramsSlider').value)/currentScannedBase.weight;
  await fdSaveMeal({name:currentScannedBase.name,meal_type:document.getElementById('resMealType').value,
    total_kcal:Math.round(currentScannedBase.cal*factor),total_protein_g:+(currentScannedBase.prot*factor).toFixed(1),
    total_carb_g:+(currentScannedBase.carb*factor).toFixed(1),total_fat_g:+(currentScannedBase.fat*factor).toFixed(1)});
};
// Search and save server-computed dishes, rather than trusting frontend nutrition numbers.
let fdSearchSequence=0;
async function fdCatalogSearch() {
  const output=document.getElementById('foodCatalogResults');
  const input=document.getElementById('foodCatalogSearch');
  if (!output || !input) return;
  const seq=++fdSearchSequence;
  output.textContent='Đang tìm món ăn...';
  try {
    const dishes=await FlexiAPI.request(`/foods?q=${encodeURIComponent(input.value.trim())}`);
    if (seq!==fdSearchSequence) return;
    output.replaceChildren();
    if (!dishes.length) {output.textContent='Không tìm thấy món có công thức dinh dưỡng. Có thể ghi thủ công ở nút Thêm món.';return;}
    for (const dish of dishes) {
      const row=document.createElement('div');
      row.style.cssText='display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:10px 0;border-bottom:1px solid var(--border,#ddd)';
      const info=document.createElement('div');info.style.flex='1';info.style.minWidth='170px';
      const name=document.createElement('strong');name.textContent=dish.name;
      const numbers=document.createElement('div');numbers.className='sub-muted';
      numbers.textContent=`${Math.round(Number(dish.kcal))} kcal/phần · P ${dish.protein_g}g · C ${dish.carb_g}g · F ${dish.fat_g}g`;
      info.append(name,numbers);
      const factor=document.createElement('input');factor.type='number';factor.min='0.1';factor.max='10';factor.step='0.5';factor.value='1';factor.title='Số phần';factor.setAttribute('aria-label',`Số phần ${dish.name}`);
      factor.style.cssText='width:70px;padding:8px;border:1px solid #aaa;border-radius:8px';
      const save=document.createElement('button');save.className='btn-jira-create';save.type='button';save.textContent='Thêm món';
      save.addEventListener('click',async()=>{
        save.disabled=true;
        try {await fdSaveMeal({dish_id:Number(dish.id),serving_factor:Number(factor.value),meal_type:document.getElementById('foodCatalogMeal').value});}
        finally {save.disabled=false;}
      });
      row.append(info,factor,save);output.append(row);
    }
  } catch(e) {output.textContent=e.message || 'Không thể tải danh mục món ăn.';}
}
function fdInitializeCatalog() {
  const input=document.getElementById('foodCatalogSearch');
  if (!input) return;
  let debounce;
  input.addEventListener('input',()=>{clearTimeout(debounce);debounce=setTimeout(fdCatalogSearch,250);});
  fdCatalogSearch();
}
document.addEventListener('flexidiet:pages-ready',fdInitializeCatalog);
document.addEventListener('flexidiet:pages-ready',fdInitialLoad);
