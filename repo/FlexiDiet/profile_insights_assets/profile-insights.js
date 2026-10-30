/** FlexiDiet profile insights. Only displays persisted MySQL data.
 * No Chart.js dependency; SVG charts work offline with XAMPP and on mobile. */
const fdInsightState = {weightDays:365, energyDays:7, request:0, result:null,
  selectedWeight:null, selectedEnergy:null};
const fdSvgNS='http://www.w3.org/2000/svg';
function fdN(value) {return Math.round(Number(value||0)*10)/10;}
function fdKcal(value) {return new Intl.NumberFormat('vi-VN',{maximumFractionDigits:1}).format(fdN(value))+' kcal';}
function fdKg(value) {return new Intl.NumberFormat('vi-VN',{minimumFractionDigits:1,maximumFractionDigits:2}).format(Number(value))+' kg';}
function fdDayLabel(date) {
  return new Intl.DateTimeFormat('vi-VN',{day:'2-digit',month:'2-digit',year:'numeric'}).format(new Date(date+'T12:00:00'));
}
function fdSvgEl(tag,attrs={},textContent=null) {
  const el=document.createElementNS(fdSvgNS,tag);
  for(const [key,val] of Object.entries(attrs)) el.setAttribute(key,String(val));
  if(textContent!==null) el.textContent=String(textContent);
  return el;
}
function fdSvgText(svg,x,y,label,cls='fd-axis-label',anchor='middle') {
  svg.appendChild(fdSvgEl('text',{x,y,class:cls,'text-anchor':anchor},label));
}
function fdRenderAxis(svg,{w,h,left=62,right=25,top=17,bottom=39,max=100,min=0}) {
  const plotH=h-top-bottom,plotW=w-left-right;
  for(let i=0;i<=4;i++){
    const y=top+plotH*i/4;
    svg.appendChild(fdSvgEl('line',{x1:left,y1:y,x2:w-right,y2:y,class:'fd-chart-grid'}));
    fdSvgText(svg,left-12,y+4,(max-(max-min)*i/4).toLocaleString('vi-VN',{maximumFractionDigits:1}),'fd-axis-label','end');
  }
  return {plotW,plotH,left,right,top,bottom,yOf:v=>top+(max-v)/(max-min)*plotH};
}
function fdNiceMax(max) {
  const val=Math.max(10,max*1.17),order=10**Math.floor(Math.log10(val));
  return Math.ceil(val/(order/2))*(order/2);
}
function fdProfileStatus(message,kind='info') {
  const el=document.getElementById('profileInsightsStatus');if(!el)return;
  el.textContent=message;el.dataset.kind=kind;
}
function fdFillBmi(profile) {
  document.getElementById('profileBmiValue').textContent=profile.bmi===null?'—':profile.bmi.toLocaleString('vi-VN',{maximumFractionDigits:1});
  document.getElementById('profileBmiCategory').textContent=profile.bmi_category;
  document.getElementById('profileBmiNote').textContent=profile.adult_bmi?'BMI tham khảo cho người trưởng thành; không đánh giá tỷ lệ cơ/mỡ.':'Nếu dưới 18 tuổi, BMI cần đánh giá theo tuổi và giới.';
  document.querySelector('.fd-bmi-track').hidden=!profile.adult_bmi;
  document.getElementById('profileBmiMarker').style.left=profile.bmi===null?'0%':`${Math.max(0,Math.min(100,(profile.bmi-12)/28*100))}%`;
  document.getElementById('profileWeightCurrent').textContent=profile.weight_kg?fdKg(profile.weight_kg):'—';
  document.getElementById('profileWeightGoal').textContent=profile.target_weight_kg?`Mục tiêu: ${fdKg(profile.target_weight_kg)}`:'Chưa thiết lập cân nặng mục tiêu';
}
function fdRenderWeight(rows,profile) {
  const svg=document.getElementById('profileWeightChart'),empty=document.getElementById('profileWeightEmpty');
  const detail=document.getElementById('profileWeightDetail'),trend=document.getElementById('profileWeightTrend');
  if(!svg)return;
  svg.replaceChildren();empty.hidden=rows.length>0;
  if(!rows.length){svg.hidden=true;detail.textContent='';trend.textContent='Chưa có mốc cân trong khoảng được chọn';return;}
  svg.hidden=false;
  const w=900,h=265,vals=rows.map(r=>r.kg),goal=profile.target_weight_kg;
  const all=goal?vals.concat([goal]):vals;
  let min=Math.floor(Math.min(...all)-1.5),max=Math.ceil(Math.max(...all)+1.5);
  if(max-min<4){const mid=(max+min)/2;min=mid-2;max=mid+2;}
  const a=fdRenderAxis(svg,{w,h,max,min});
  const first=Date.parse(rows[0].date+'T12:00:00'),last=Date.parse(rows[rows.length-1].date+'T12:00:00');
  const xOf=(date)=>a.left+(last===first?a.plotW/2:((Date.parse(date+'T12:00:00')-first)/(last-first))*a.plotW);
  if(goal){
    svg.appendChild(fdSvgEl('line',{x1:a.left,y1:a.yOf(goal),x2:w-a.right,y2:a.yOf(goal),class:'fd-weight-goal-line'}));
    fdSvgText(svg,w-a.right-4,Math.max(13,a.yOf(goal)-7),`Mục tiêu ${fdKg(goal)}`,'fd-goal-label','end');
  }
  if(rows.length>1) svg.appendChild(fdSvgEl('polyline',{points:rows.map(r=>`${xOf(r.date)},${a.yOf(r.kg)}`).join(' '),class:'fd-weight-polyline'}));
  const labels=rows.length===1?[rows[0]]:rows.filter((r,i)=>i===0 || i===rows.length-1 || i%Math.max(1,Math.floor((rows.length-1)/4))===0);
  const emitted=new Set();for(const r of labels){if(emitted.has(r.date))continue;emitted.add(r.date);fdSvgText(svg,xOf(r.date),h-12,fdDayLabel(r.date).slice(0,5),'fd-axis-label');}
  const selected=rows.find(r=>r.date===fdInsightState.selectedWeight?.date)||rows[rows.length-1];
  fdInsightState.selectedWeight=selected;
  for(const r of rows){
    const x=xOf(r.date),y=a.yOf(r.kg);
    const dot=fdSvgEl('circle',{cx:x,cy:y,r:selected.date===r.date?6.5:4.3,class:'fd-weight-dot','data-day':r.date,tabindex:0,role:'button','aria-label':`${fdDayLabel(r.date)}: ${fdKg(r.kg)}`});
    dot.addEventListener('click',()=>{fdInsightState.selectedWeight=r;fdRenderWeight(rows,profile);});
    dot.addEventListener('keydown',event=>{if(event.key==='Enter'||event.key===' '){event.preventDefault();fdInsightState.selectedWeight=r;fdRenderWeight(rows,profile);}});
    svg.appendChild(dot);
  }
  detail.textContent=`${fdDayLabel(selected.date)}: ${fdKg(selected.kg)}${goal?` · Chênh lệch mục tiêu ${fdKg(Math.abs(selected.kg-goal))}`:''}.`;
  const earliest=rows[0],latest=rows[rows.length-1],delta=fdN(latest.kg-earliest.kg);
  trend.textContent=rows.length===1?'Có 1 lần cân; hãy ghi nhận thêm để có đường xu hướng.':`Thay đổi ${delta>0?'+':''}${delta.toLocaleString('vi-VN')} kg qua ${rows.length} mốc cân.`;
}
function fdEnergyBuckets(rows, days) {
  if(days!==365) return rows.map(r=>({...r,label:fdDayLabel(r.date).slice(0,5),days_with_meals:r.has_meals?1:0}));
  const buckets=new Map();
  // The 365-day API window overlaps 13 calendar months at its edges.
  // Show exactly 12 monthly buckets, from the current month back 11 months.
  const current=rows[rows.length-1].date;
  const currentYear=Number(current.slice(0,4)), currentMonth=Number(current.slice(5,7));
  for(let i=11;i>=0;i--){
    const begin=new Date(currentYear,currentMonth-1-i,1);
    const month=`${begin.getFullYear()}-${String(begin.getMonth()+1).padStart(2,'0')}`;
    buckets.set(month,{date:month,label:`T${Number(month.slice(5))}/${month.slice(2,4)}`,days_with_meals:0,days_without_meals:0,intake_kcal:0,dynamic_budget_kcal:0,exercise_raw_kcal:0,exercise_credit_kcal:0,difference_kcal:null,has_meals:false,has_budget:false});
  }
  for(const row of rows){
    const month=row.date.slice(0,7);
    if(!buckets.has(month))continue;
    const r=buckets.get(month);
    r.exercise_raw_kcal+=Number(row.exercise_raw_kcal||0);
    if(row.has_meals && row.has_budget){
      r.has_meals=true;r.has_budget=true;r.days_with_meals++;
      r.intake_kcal+=Number(row.intake_kcal);
      r.dynamic_budget_kcal+=Number(row.dynamic_budget_kcal);
      r.exercise_credit_kcal+=Number(row.exercise_credit_kcal||0);
    } else r.days_without_meals++;
  }
  return [...buckets.values()].map(r=>({...r,difference_kcal:r.has_meals?fdN(r.dynamic_budget_kcal-r.intake_kcal):null}));
}
function fdEnergyDetails(row, yearView) {
  const box=document.getElementById('profileEnergyDetail');if(!box)return;
  box.replaceChildren();
  const heading=document.createElement('strong');
  heading.textContent=yearView?`Tháng ${Number(row.date.slice(5))}/${row.date.slice(0,4)}`:fdDayLabel(row.date);
  box.appendChild(heading);
  const para=document.createElement('p');
  if(!row.has_meals){para.textContent='Chưa ghi bữa ăn. Không thể xác định calo còn lại hoặc vượt ngân sách.';box.appendChild(para);return;}
  if(!row.has_budget){para.textContent='Có món ăn nhưng chưa có ngân sách của ngày này. Không thể tính chênh lệch.';box.appendChild(para);return;}
  para.textContent=`Đã ăn: ${fdKcal(row.intake_kcal)} · Ngân sách động: ${fdKcal(row.dynamic_budget_kcal)} · Tập luyện (ước tính): ${fdKcal(row.exercise_raw_kcal)}${yearView?` · ${row.days_with_meals} ngày có nhật ký ăn`:''}.`;
  box.appendChild(para);
  const result=document.createElement('div');result.className='fd-difference '+(row.difference_kcal>=0?'fd-under':'fd-over');
  result.textContent=row.difference_kcal>=0?`Còn ${fdKcal(row.difference_kcal)} trong ngân sách`:`Vượt ngân sách ${fdKcal(-row.difference_kcal)}`;
  box.appendChild(result);
  const note=document.createElement('small');
  note.textContent=yearView?'Ngân sách và calo ăn chỉ cộng ở những ngày có ghi bữa ăn.':`Calo tập được cộng vào ngân sách: ${fdKcal(row.exercise_credit_kcal)} (khác tổng calo tập thô).`;
  box.appendChild(note);
}
function fdRenderEnergy(rows,days){
  const svg=document.getElementById('profileEnergyChart'),empty=document.getElementById('profileEnergyEmpty');
  const summary=document.getElementById('profileEnergySummary');if(!svg)return;
  const buckets=fdEnergyBuckets(rows,days), complete=buckets.filter(r=>r.has_meals&&r.has_budget);
  svg.replaceChildren();empty.hidden=complete.length!==0;summary.replaceChildren();
  if(!complete.length){svg.hidden=true;document.getElementById('profileEnergyDetail').textContent='Chưa có đủ dữ liệu để so sánh. Hãy ghi một bữa ăn và xem ngân sách hôm nay.';return;}
  svg.hidden=false;
  const w=days===30?1200:900,h=290,left=68,right=22,top=26,bottom=47;
  svg.setAttribute('viewBox',`0 0 ${w} ${h}`);
  const max=fdNiceMax(Math.max(...complete.map(r=>Math.max(r.intake_kcal,r.dynamic_budget_kcal))));
  const a=fdRenderAxis(svg,{w,h,left,right,top,bottom,max,min:0});
  const n=buckets.length,slot=a.plotW/n,barWidth=Math.min(35,slot*.5);
  const plotted=[];
  for(let i=0;i<n;i++){
    const r=buckets[i],x=left+slot*(i+.5);
    if(r.has_meals&&r.has_budget){
      const y=a.yOf(r.intake_kcal);
      svg.appendChild(fdSvgEl('rect',{x:x-barWidth/2,y,width:barWidth,height:Math.max(0,top+a.plotH-y),rx:4,class:'fd-energy-bar'}));
      plotted.push({i,x,y:a.yOf(r.dynamic_budget_kcal),row:r});
      // Direct +/− badge for the short 7-day overview: no mental subtraction.
      if(days===7){
        const diff=fdN(r.difference_kcal),label=`${diff>=0?'+':''}${Math.round(diff).toLocaleString('vi-VN')}`;
        const labelY=Math.max(15,Math.min(y,a.yOf(r.dynamic_budget_kcal))-9);
        fdSvgText(svg,x,labelY,label,diff>=0?'fd-delta-positive':'fd-delta-negative');
      }
    } else {
      svg.appendChild(fdSvgEl('rect',{x:x-barWidth/2,y:top+a.plotH-3,width:barWidth,height:3,rx:1,class:'fd-missing-bar'}));
    }
    if(days!==30||i%3===0||i===n-1) fdSvgText(svg,x,h-15,r.label,'fd-axis-label');
    const hit=fdSvgEl('rect',{x:left+i*slot,y:top,width:slot,height:a.plotH+6,class:'fd-hit-area',tabindex:0,role:'button','aria-label':`Chi tiết ${r.label}`});
    hit.addEventListener('click',()=>{fdInsightState.selectedEnergy=r.date;fdRenderEnergy(rows,days);});
    hit.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();fdInsightState.selectedEnergy=r.date;fdRenderEnergy(rows,days);}});
    svg.appendChild(hit);
  }
  // Preserve gaps: never draw a line across days/months with no meal log.
  for(let i=1;i<plotted.length;i++){
    if(plotted[i].i-plotted[i-1].i===1){
      svg.appendChild(fdSvgEl('line',{x1:plotted[i-1].x,y1:plotted[i-1].y,x2:plotted[i].x,y2:plotted[i].y,class:'fd-energy-budget-line'}));
    }
  }
  for(const pt of plotted) svg.appendChild(fdSvgEl('circle',{cx:pt.x,cy:pt.y,r:4,class:'fd-energy-budget-dot'}));
  const under=complete.filter(r=>r.difference_kcal>=0).length;
  const over=complete.length-under;
  const delta=fdN(complete.reduce((sum,r)=>sum+r.difference_kcal,0));
  for(const [label,value] of [['Trong ngân sách',`${under} ${days===365?'tháng':'ngày'}`],['Vượt ngân sách',`${over} ${days===365?'tháng':'ngày'}`],['Chênh lệch có dữ liệu',`${delta>0?'+':''}${fdKcal(delta)}`]]){
    const block=document.createElement('div');
    const name=document.createElement('span');name.textContent=label;
    const strong=document.createElement('strong');strong.textContent=value;
    block.append(name,strong);summary.appendChild(block);
  }
  const selected=buckets.find(r=>r.date===fdInsightState.selectedEnergy) || complete[complete.length-1];
  fdInsightState.selectedEnergy=selected.date;
  fdEnergyDetails(selected,days===365);
}
async function fdLoadProfileInsights() {
  if(!document.getElementById('profileInsights'))return;
  const request=++fdInsightState.request;
  fdProfileStatus('Đang tải lịch sử cân nặng và năng lượng...');
  try{
    const params=`weight_days=${fdInsightState.weightDays}&energy_days=${fdInsightState.energyDays}`;
    const data=await FlexiAPI.request(`/profile/insights?${params}`);
    if(request!==fdInsightState.request)return;
    fdInsightState.result=data;
    fdFillBmi(data.profile);
    fdRenderWeight(data.weights,data.profile);
    fdRenderEnergy(data.energy,fdInsightState.energyDays);
    fdProfileStatus('Đã đồng bộ số liệu thực từ MySQL.','ok');
    const date=document.getElementById('profileWeightDate');
    if(date&&!date.max){const now=new Date();const iso=localDayKey(now);date.max=iso;date.value=iso;}
    const inp=document.getElementById('profileWeightInput');
    if(inp&&inp.value==='')inp.value=data.profile.weight_kg||'';
  }catch(e){if(request!==fdInsightState.request)return;fdProfileStatus(`Không tải được biểu đồ: ${e.message}`,'error');}
}
async function fdRecordWeight(event){
  event.preventDefault();const button=document.getElementById('profileRecordWeight');
  if(button.disabled)return;
  const date=document.getElementById('profileWeightDate').value;
  const kg=Number(document.getElementById('profileWeightInput').value);
  if(!date||!Number.isFinite(kg)||kg<20||kg>400){showToast('Hãy nhập ngày cân và cân nặng từ 20 đến 400 kg.','warning');return;}
  button.disabled=true;
  try{
    await FlexiAPI.request('/weight-logs',{method:'POST',data:{log_date:date,weight_kg:kg}});
    await fdRefreshDashboard();
    await fdLoadProfileInsights();
    showToast(`Đã ghi nhận ${fdKg(kg)} vào ${fdDayLabel(date)}.`,'success');
  }catch(e){showToast(e.message||'Không thể lưu cân nặng.','warning');}
  finally{button.disabled=false;}
}
document.addEventListener('flexidiet:pages-ready',()=>{
  const w=document.getElementById('profileWeightPeriod');
  const e=document.getElementById('profileEnergyPeriod');
  if(!w||!e)return;
  w.addEventListener('change',()=>{fdInsightState.weightDays=Number(w.value);fdInsightState.selectedWeight=null;fdLoadProfileInsights();});
  e.addEventListener('change',()=>{fdInsightState.energyDays=Number(e.value);fdInsightState.selectedEnergy=null;fdLoadProfileInsights();});
  document.getElementById('profileWeightForm').addEventListener('submit',fdRecordWeight);
  if(appState.activeTab==='profile')fdLoadProfileInsights();
});
