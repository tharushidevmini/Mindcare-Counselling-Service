async function checkEmail() {
  const email = document.getElementById('login-email').value.trim().toLowerCase();
  document.getElementById('error-msg').style.display = 'none';

  if (!email) { showError('Please enter your campus email.'); return; }
  if (!email.endsWith('@edu.lnbti.lk')) {
    showError('Please use your official campus email (@edu.lnbti.lk).'); return;
  }

  setBtnLoading(true, 'Checking...');
  const fd = new FormData();
  fd.append('email', email);

  try {
    const res  = await fetch(window.location.origin + '/mindcare_final/backend/auth/send_otp.php', { method:'POST', body:fd });
    const data = await res.json();
    if (data.error) { showError(data.error); setBtnLoading(false); return; }
    if (data.status === 'existing_user') {
      showStep('step-password');
      document.getElementById('user-email-display').textContent = email;
    } else {
      showStep('step-otp');
      document.getElementById('otp-email-display').textContent = email;
    }
  } catch {
    showError('Network error — make sure XAMPP is running.');
  }
  setBtnLoading(false);
}

async function doLogin() {
  const email    = document.getElementById('login-email').value.trim().toLowerCase();
  const password = document.getElementById('login-password').value.trim();
  const role     = document.getElementById('selected-role').value;
  if (!password) { showError('Please enter your password.'); return; }

  setBtnLoading(true, 'Signing in...');
  const fd = new FormData();
  fd.append('email', email);
  fd.append('password', password);
  fd.append('role', role);

  try {
    const res  = await fetch(window.location.origin + '/mindcare_final/backend/auth/login.php', { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) { redirectByRole(data.role); }
    else { showError(data.error || 'Login failed.'); }
  } catch {
    showError('Network error — make sure XAMPP is running.');
  }
  setBtnLoading(false);
}

function previewRegPhoto(event) {
  const file = event.target.files[0];
  if (!file) return;
  if (!['image/png', 'image/jpeg'].includes(file.type)) {
    showError('Please choose a JPG or PNG image.');
    event.target.value = '';
    return;
  }
  if (file.size > 2 * 1024 * 1024) {
    showError('Image must be under 2MB.');
    event.target.value = '';
    return;
  }
  const reader = new FileReader();
  reader.onload = e => {
    const img = document.getElementById('reg-photo-preview');
    img.src = e.target.result;
    img.style.display = 'block';
    document.getElementById('reg-photo-placeholder').style.display = 'none';
  };
  reader.readAsDataURL(file);
}

async function verifyOTP() {
  const email    = document.getElementById('login-email').value.trim().toLowerCase();
  const fullName = document.getElementById('reg-full-name').value.trim();
  const otp      = document.getElementById('otp-input').value.trim();
  const password = document.getElementById('new-password').value.trim();
  const confirm  = document.getElementById('confirm-password').value.trim();
  const role     = document.getElementById('selected-role').value;
  const photoFile = document.getElementById('reg-photo-input').files[0];

  if (!fullName)             { showError('Please enter your full name.'); return; }
  if (otp.length !== 6)     { showError('Enter the 6-digit OTP.'); return; }
  if (!password)            { showError('Create a password.'); return; }
  if (password !== confirm) { showError('Passwords do not match.'); return; }
  if (password.length < 6)  { showError('Password must be at least 6 characters.'); return; }

  setBtnLoading(true, 'Verifying...');
  const fd = new FormData();
  fd.append('email', email);
  fd.append('full_name', fullName);
  fd.append('otp', otp);
  fd.append('password', password);
  fd.append('role', role);
  if (photoFile) fd.append('photo', photoFile);

  try {
    const res  = await fetch(window.location.origin + '/mindcare_final/backend/auth/verify_otp.php', { method:'POST', body:fd });
    const data = await res.json();
    if (data.success && data.pending) {
      showPendingScreen(data.full_name);
    } else if (data.success) {
      redirectByRole(data.role);
    } else {
      showError(data.error || 'Verification failed.');
    }
  } catch {
    showError('Network error — make sure XAMPP is running.');
  }
  setBtnLoading(false);
}

function selectRole(role, btn) {
  document.getElementById('selected-role').value = role;
  document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  if (role === 'guardian') {
    window.location.href = '../pages/guardian/login.html'; return;
  }
}

function showStep(id) {
  ['step-email','step-password','step-otp','step-forgot','step-reset'].forEach(s => {
    document.getElementById(s).style.display = s === id ? 'block' : 'none';
  });
  document.getElementById('error-msg').style.display = 'none';
}

function showPendingScreen(name) {
  const container = document.getElementById('step-otp').parentElement;
  ['step-email','step-password','step-otp'].forEach(s => {
    const el = document.getElementById(s);
    if (el) el.style.display = 'none';
  });
  const pending = document.createElement('div');
  pending.id = 'step-pending';
  pending.innerHTML = `
    <div style="text-align:center; padding:1rem 0;">
      <div style="width:64px;height:64px;margin:0 auto 1rem;border-radius:50%;background:var(--orange-pale);display:flex;align-items:center;justify-content:center;font-size:1.8rem;">⏳</div>
      <h3 style="margin-bottom:.5rem;">Registration Submitted</h3>
      <p style="font-size:13.5px; color:var(--text-3); line-height:1.7; margin-bottom:1.25rem;">
        Thanks, ${name}! Your account has been created and is <strong>waiting for admin approval</strong>. You'll be able to log in once an administrator activates your account.
      </p>
      <a href="login.html" class="btn btn-ghost btn-full">← Back to login</a>
    </div>`;
  container.appendChild(pending);
}

function goBack() { showStep('step-email'); }

function showForgotPassword() {
  const email = document.getElementById('login-email').value.trim().toLowerCase();
  document.getElementById('forgot-email-display').textContent = email;
  showStep('step-forgot');
}

async function sendResetCode() {
  const email = document.getElementById('login-email').value.trim().toLowerCase();
  const btn = document.getElementById('send-reset-btn');
  if (btn) { btn.disabled = true; btn.textContent = 'Sending…'; }

  const fd = new FormData();
  fd.append('email', email);

  try {
    const res  = await fetch(window.location.origin + '/mindcare_final/backend/auth/forgot_password.php', { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
      document.getElementById('reset-email-display').textContent = email;
      document.getElementById('reset-otp-input').value = '';
      document.getElementById('reset-new-password').value = '';
      document.getElementById('reset-confirm-password').value = '';
      showStep('step-reset');
    } else {
      showError(data.error || 'Could not send reset code.');
    }
  } catch {
    showError('Network error — make sure XAMPP is running.');
  }
  if (btn) { btn.disabled = false; btn.textContent = 'Send Reset Code →'; }
}

async function confirmPasswordReset() {
  const email       = document.getElementById('login-email').value.trim().toLowerCase();
  const otp         = document.getElementById('reset-otp-input').value.trim();
  const newPassword = document.getElementById('reset-new-password').value.trim();
  const confirm     = document.getElementById('reset-confirm-password').value.trim();

  if (otp.length !== 6)          { showError('Enter the 6-digit code.'); return; }
  if (!newPassword)              { showError('Enter a new password.'); return; }
  if (newPassword !== confirm)   { showError('Passwords do not match.'); return; }
  if (newPassword.length < 6)    { showError('Password must be at least 6 characters.'); return; }

  const resetBtn = document.querySelector('button[onclick="confirmPasswordReset()"]');
  const originalText = resetBtn ? resetBtn.textContent : null;
  if (resetBtn) { resetBtn.disabled = true; resetBtn.textContent = 'Resetting…'; }

  const fd = new FormData();
  fd.append('email', email);
  fd.append('otp', otp);
  fd.append('new_password', newPassword);

  try {
    const res  = await fetch(window.location.origin + '/mindcare_final/backend/auth/reset_password_confirm.php', { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
      showStep('step-password');
      document.getElementById('login-password').value = '';
      document.getElementById('user-email-display').textContent = email;
    } else {
      showError(data.error || 'Could not reset password.');
    }
  } catch {
    showError('Network error — make sure XAMPP is running.');
  }
  if (resetBtn) { resetBtn.disabled = false; resetBtn.textContent = originalText; }
}

function togglePasswordVisibility(inputId, btn) {
  const input = document.getElementById(inputId);
  const icon  = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'bi bi-eye';
  }
}

function showError(msg) {
  const e = document.getElementById('error-msg');
  e.textContent = msg;
  e.style.display = 'block';
  e.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function setBtnLoading(on, text) {
  const b = document.getElementById('main-btn');
  if (b) { b.disabled = on; b.textContent = on ? text : 'Continue ->'; }
}

function redirectByRole(role) {
  const map = {
    student:          window.location.origin + '/mindcare_final/frontend/pages/student/dashboard.html',
    counselor:        window.location.origin + '/mindcare_final/frontend/pages/counselor/dashboard.html',
    learning_advisor: window.location.origin + '/mindcare_final/frontend/pages/advisor/dashboard.html',
    admin:            window.location.origin + '/mindcare_final/frontend/pages/admin/dashboard.html'
  };
  window.location.href = map[role] || window.location.origin + '/mindcare_final/frontend/pages/login.html';
}

async function doLogout() {
  await fetch(window.location.origin + '/mindcare_final/backend/auth/logout.php');
  window.location.href = window.location.origin + '/mindcare_final/frontend/pages/login.html';
}

// Banner shown when a counselor/admin is previewing a student relaxation page.
// Keeps their own session intact — no logout, no re-login needed.
function showStaffPreviewBanner(role) {
  if (document.getElementById('staff-preview-banner')) return;
  const bar = document.createElement('div');
  bar.id = 'staff-preview-banner';
  bar.style.cssText =
    'position:sticky;top:0;z-index:999;display:flex;align-items:center;justify-content:center;gap:14px;' +
    'flex-wrap:wrap;padding:10px 18px;font-size:13.5px;font-weight:600;color:#1C1410;' +
    'background:linear-gradient(135deg,#F5A846,#F5C846);box-shadow:0 2px 12px rgba(245,168,70,.4);';
  bar.innerHTML =
    '👁 <span>Preview mode — this is the student view. You are still signed in as ' + role + '.</span>' +
    '<a href="../../counselor/relaxation.html" ' +
    'style="background:rgba(255,255,255,.9);color:#1C1410;text-decoration:none;padding:5px 14px;' +
    'border-radius:10px;font-weight:700;font-size:12.5px;">← Back to Manager</a>';
  document.body.prepend(bar);
}

async function loadUserInfo() {
  try {
    const res  = await fetch(window.location.origin + '/mindcare_final/backend/auth/session.php');
    const data = await res.json();

    if (!data.logged_in) {
      window.location.href = window.location.origin + '/mindcare_final/frontend/pages/login.html';
      return;
    }

    // Role check — wrong page redirect
    const path = window.location.pathname;
    const role = data.role;

    // Exception: counselors/admins may VIEW the student relaxation pages read-only
    // (breathing / music / meditation) so they can preview exactly what students see.
    // These pages hold no private student data — just the relaxation content they manage.
    const isRelaxationView = path.includes('/student/relaxation/');
    const staffPreviewing  = isRelaxationView && (role === 'counselor' || role === 'admin');

    if (staffPreviewing) {
      showStaffPreviewBanner(role);
      // skip the student redirect below, let the page render
    } else {
      if (path.includes('/student/')  && role !== 'student')  { redirectByRole(role); return; }
      if (path.includes('/counselor/') && role !== 'counselor') { redirectByRole(role); return; }
      if (path.includes('/advisor/')  && role !== 'learning_advisor') { redirectByRole(role); return; }
      if (path.includes('/admin/')    && role !== 'admin')    { redirectByRole(role); return; }
    }

    const n = document.getElementById('user-name');
    if (n) n.textContent = data.full_name || 'User';
    const a = document.getElementById('user-avatar');
    if (a) {
      if (data.photo_url) {
        a.innerHTML = '';
        a.style.background = 'none';
        a.style.boxShadow = 'none';
        const img = document.createElement('img');
        img.src = window.location.origin + '/mindcare_final/frontend/assets/images/profiles/' + data.photo_url;
        img.style.cssText = 'width:100%; height:100%; border-radius:50%; object-fit:cover;';
        a.appendChild(img);
      } else {
        a.textContent = (data.full_name || 'U').charAt(0).toUpperCase();
      }
    }

    if (role === 'counselor' || role === 'admin') loadRiskAlertBadge();

  } catch(e) {
    console.error('Session error', e);
  }
}

async function loadRiskAlertBadge() {
  try {
    const res  = await fetch(window.location.origin + '/mindcare_final/backend/api/alerts/count.php');
    const data = await res.json();
    const count = data.count || 0;

    const topBadge = document.getElementById('alert-badge');
    if (topBadge) {
      topBadge.innerHTML = count > 0
        ? `🔴 Risk Alerts <span style="background:white;color:#c0392b;border-radius:20px;padding:1px 7px;font-size:11px;font-weight:800;margin-left:4px;">${count}</span>`
        : `🟢 Risk Alerts`;
      topBadge.style.cursor = 'pointer';
      topBadge.onclick = () => { window.location.href = 'alerts.html'; };
    }

    // Sidebar "Student Alerts" red dot — only show when there's something open
    document.querySelectorAll('a[href="alerts.html"] .notif-dot').forEach(dot => {
      dot.style.display = count > 0 ? 'inline-block' : 'none';
    });
  } catch (e) {
    console.error('Alert badge error', e);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('user-name')) loadUserInfo();
});