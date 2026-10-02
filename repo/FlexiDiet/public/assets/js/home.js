const homeView = document.getElementById('home-view');
const signupPage = document.getElementById('signup-page');
const signinOverlay = document.getElementById('signin-overlay');

const APP_URL = 'app.html';
const USER_KEY = 'flexidiet-user';

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

// Đăng nhập / đăng ký thành công (demo): lưu người dùng rồi chuyển sang app.
function goToApp(name, email) {
  try { localStorage.setItem(USER_KEY, JSON.stringify({ name: name, email: email })); } catch (e) {}
  location.href = APP_URL;
}

document.getElementById('open-signup').addEventListener('click', showSignup);
document.getElementById('open-signup-2').addEventListener('click', showSignup);
document.getElementById('open-signup-3').addEventListener('click', showSignup);
document.getElementById('open-signin').addEventListener('click', openSignin);
document.getElementById('close-signin').addEventListener('click', closeSignin);
document.getElementById('back-home').addEventListener('click', showHome);
document.getElementById('to-signup').addEventListener('click', showSignup);
document.getElementById('open-forgot').addEventListener('click', showForgotPane);
document.getElementById('back-to-signin').addEventListener('click', showSigninPane);

// Demo: chưa có backend nên chỉ hiển thị thông báo đã gửi email.
// Khi có server, gọi API gửi mail đặt lại mật khẩu ở đây.
forgotForm.addEventListener('submit', (e) => {
  e.preventDefault();
  document.getElementById('fp-sent-email').textContent = document.getElementById('fp-email').value.trim();
  forgotForm.hidden = true;
  forgotSuccess.hidden = false;
});

document.getElementById('to-signin').addEventListener('click', () => { showHome(); openSignin(); });

document.getElementById('signin-form').addEventListener('submit', (e) => {
  e.preventDefault();
  const email = document.getElementById('si-email').value.trim();
  // Form đăng nhập không có tên: dùng tên đã lưu nếu cùng email, không thì lấy phần trước @
  let name = email.split('@')[0];
  try {
    const prev = JSON.parse(localStorage.getItem(USER_KEY));
    if (prev && prev.email === email && prev.name) name = prev.name;
  } catch (e) {}
  goToApp(name, email);
});

signinOverlay.addEventListener('click', (e) => { if (e.target === signinOverlay) closeSignin(); });
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeSignin(); });

// ==============================================================================
// ĐĂNG KÝ & ONBOARDING — Form 4 bước (Tài khoản → Chỉ số → Mục tiêu → Kế hoạch)
// ==============================================================================
const regState = { gender: 'male', goal: 'lose', calorieOffset: -500 };

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
  const age = parseInt(document.getElementById('ob-age').value, 10) || 24;
  const height = parseInt(document.getElementById('ob-height').value, 10) || 170;
  const weight = parseFloat(document.getElementById('ob-weight').value) || 60;
  const gender = regState.gender;

  // Công thức Mifflin-St Jeor
  let bmr = 10 * weight + 6.25 * height - 5 * age;
  bmr = gender === 'male' ? bmr + 5 : bmr - 161;
  bmr = Math.round(bmr);

  const pal = 1.375; // mặc định: vận động nhẹ
  const tdee = Math.round(bmr * pal);
  const target = Math.round(tdee + regState.calorieOffset);

  const setText = (id, html) => { const el = document.getElementById(id); if (el) el.innerHTML = html; };
  setText('res-bmr', `${bmr.toLocaleString()} <small>kcal</small>`);
  setText('res-tdee', `${tdee.toLocaleString()} <small>kcal</small>`);
  setText('res-target', `${target.toLocaleString()} <small>kcal/ngày</small>`);

  goToStep(4);
}

function finishOnboardingToDashboard() {
  const name = (document.getElementById('su-name').value || '').trim() || 'Bạn';
  const email = (document.getElementById('su-email').value || '').trim();
  goToApp(name, email);
}

// Video placeholder: nếu #guide-video có <source> thật thì tự ẩn placeholder
const guideVideo = document.getElementById('guide-video');
const videoPlaceholder = document.getElementById('video-placeholder');
if (guideVideo.querySelector('source') || guideVideo.getAttribute('src')) {
  guideVideo.style.display = 'block';
  videoPlaceholder.style.display = 'none';
}
