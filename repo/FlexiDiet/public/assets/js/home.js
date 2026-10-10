const homeView = document.getElementById('home-view');
const signupPage = document.getElementById('signup-page');
const signinOverlay = document.getElementById('signin-overlay');

const APP_URL = 'app.html';
// Authentication uses the PHP session cookie; not localStorage.

function showSignup() {
  signinOverlay.classList.remove('open');
  homeView.classList.add('hidden');
  signupPage.classList.add('open');
  goToStep(1);
  window.scrollTo(0, 0);
}
function showHome() {
  signupPage.classList.remove('open');
  signinOverlay.classList.remove('open');
  homeView.classList.remove('hidden');
}
function openSignin() { showSigninPane(); signinOverlay.classList.add('open'); }
function closeSignin() { signinOverlay.classList.remove('open'); }

// Modal đăng nhập có 2 màn: đăng nhập và quên mật khẩu
const signinPane = document.getElementById('signin-pane');
const forgotPane = document.getElementById('forgot-pane');
const forgotForm = document.getElementById('forgot-form');
const forgotSuccess = document.getElementById('forgot-success');
const modalTitle = document.getElementById('modal-title');

function showSigninPane() {
  modalTitle.textContent = 'Đăng nhập';
  forgotPane.hidden = true;
  signinPane.hidden = false;
}
function showForgotPane() {
  modalTitle.textContent = 'Quên mật khẩu';
  // Điền sẵn email nếu người dùng đã nhập ở form đăng nhập
  document.getElementById('fp-email').value = document.getElementById('si-email').value.trim();
  forgotForm.hidden = false;
  forgotSuccess.hidden = true;
  signinPane.hidden = true;
  forgotPane.hidden = false;
  document.getElementById('fp-email').focus();
}

// Chuyển trang chỉ sau khi server xác thực thành công.
function goToApp() { location.href = APP_URL; }
function showAuthError(error) { alert(error.message || 'Có lỗi xảy ra.'); }

document.getElementById('open-signup').addEventListener('click', showSignup);
document.getElementById('open-signup-2').addEventListener('click', showSignup);
document.getElementById('open-signup-3').addEventListener('click', showSignup);
document.getElementById('open-signin').addEventListener('click', openSignin);
document.getElementById('close-signin').addEventListener('click', closeSignin);
document.getElementById('back-home').addEventListener('click', showHome);
document.getElementById('to-signup').addEventListener('click', showSignup);
document.getElementById('open-forgot').addEventListener('click', showForgotPane);
document.getElementById('back-to-signin').addEventListener('click', showSigninPane);

// Chưa triển khai reset password: không giả thông báo đã gửi email.
forgotForm.addEventListener('submit', e => {
  e.preventDefault();
  alert('Tính năng khôi phục mật khẩu đang được phát triển. Vui lòng liên hệ quản trị viên để được hỗ trợ.');
});

document.getElementById('to-signin').addEventListener('click', () => { showHome(); openSignin(); });

document.getElementById('signin-form').addEventListener('submit', async e => {
  e.preventDefault();
  const submit = e.currentTarget.querySelector('[type="submit"]');
  if (submit) submit.disabled = true;
  try {
    const res = await FlexiAPI.request('/auth/login', {method:'POST', data:{
      email:document.getElementById('si-email').value.trim(),
      password:document.getElementById('si-pass').value
    }});
    if (res?.user) {
      localStorage.setItem('flexidiet-user', JSON.stringify({
        id: res.user.id,
        name: res.user.display_name,
        email: res.user.email,
        role: res.user.role
      }));
    }
    goToApp();
  } catch (error) { showAuthError(error); }
  finally { if (submit) submit.disabled = false; }
});

signinOverlay.addEventListener('click', (e) => { if (e.target === signinOverlay) closeSignin(); });
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeSignin(); });

// ==============================================================================
// ĐĂNG KÝ & ONBOARDING — Form 4 bước (Tài khoản → Chỉ số → Mục tiêu → Kế hoạch)
// ==============================================================================
const regState = { gender: 'male', goal: 'lose', calorieOffset: -0.15 };

function goToStep(step) {
  for (let i = 1; i <= 4; i++) {
    const pane = document.getElementById(`step-pane-${i}`);
    const node = document.getElementById(`step-node-${i}`);
    if (pane) pane.classList.toggle('active', i === step);
    if (node) node.classList.toggle('active', i <= step);
  }
}

function selectGender(gender) {
  regState.gender = gender;
  const male = document.getElementById('gender-male');
  const female = document.getElementById('gender-female');
  if (male) male.classList.toggle('active', gender === 'male');
  if (female) female.classList.toggle('active', gender === 'female');
}

function selectGoal(goal, offset, el) {
  regState.goal = goal;
  regState.calorieOffset = offset;
  document.querySelectorAll('.goal-card').forEach((c) => c.classList.remove('active'));
  if (el) el.classList.add('active');
}

function calculateAndShowStep4() {
  const birth = document.getElementById('ob-birth').value;
  const age = birth ? Math.floor((Date.now() - new Date(birth).getTime()) / 31556952000) : 24;
  const height = parseInt(document.getElementById('ob-height').value, 10) || 170;
  const weight = parseFloat(document.getElementById('ob-weight').value) || 60;
  const gender = regState.gender;

  // Công thức Mifflin-St Jeor
  let bmr = 10 * weight + 6.25 * height - 5 * age;
  bmr = gender === 'male' ? bmr + 5 : bmr - 161;
  bmr = Math.round(bmr);

  const tdee = Math.round(bmr * 1.2); // baseline theo SRS
  const floor = gender === 'male' ? 1500 : 1200;
  const target = Math.max(floor, Math.round(tdee * (1 + regState.calorieOffset)));

  const setText = (id, html) => { const el = document.getElementById(id); if (el) el.innerHTML = html; };
  setText('res-bmr', `${bmr.toLocaleString()} <small>kcal</small>`);
  setText('res-tdee', `${tdee.toLocaleString()} <small>kcal</small>`);
  setText('res-target', `${target.toLocaleString()} <small>kcal/ngày</small>`);

  goToStep(4);
}

async function finishOnboardingToDashboard() {
  const submit = document.querySelector('#step-pane-4 .btn-jira-create');
  if (submit) submit.disabled = true;
  try {
    const res = await FlexiAPI.request('/auth/register', {method:'POST', data:{
      display_name:document.getElementById('su-name').value.trim(),
      email:document.getElementById('su-email').value.trim(),
      password:document.getElementById('su-pass').value,
      sex:regState.gender,
      birth_date:document.getElementById('ob-birth').value,
      height_cm:Number(document.getElementById('ob-height').value),
      weight_kg:Number(document.getElementById('ob-weight').value),
      goal:regState.goal,
      weekly_workout_goal:3
    }});
    if (res?.user) {
      localStorage.setItem('flexidiet-user', JSON.stringify({
        id: res.user.id,
        name: res.user.display_name,
        email: res.user.email,
        role: res.user.role
      }));
    }
    goToApp();
  } catch(error) { showAuthError(error); }
  finally { if (submit) submit.disabled = false; }
}

// Video placeholder: nếu #guide-video có <source> thật thì tự ẩn placeholder
const guideVideo = document.getElementById('guide-video');
const videoPlaceholder = document.getElementById('video-placeholder');
if (guideVideo.querySelector('source') || guideVideo.getAttribute('src')) {
  guideVideo.style.display = 'block';
  videoPlaceholder.style.display = 'none';
}
