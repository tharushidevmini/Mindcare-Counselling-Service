// ═══════════════════════════════════════════
// FILE: frontend/js/guardian.js
// Guardian OTP login flow
// ═══════════════════════════════════════════

let otpTimer = null;

function sendOTP() {
  const sid   = document.getElementById('student-id').value.trim();
  const email = document.getElementById('guardian-email').value.trim();
  if (!sid || !email) { showError('Please fill in both fields.'); return; }
  const fd = new FormData();
  fd.append('student_id', sid); fd.append('guardian_email', email);
  fetch(window.location.origin + '/mindcare_final/backend/api/guardian/send_otp.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        document.getElementById('step1-form').style.display = 'none';
        document.getElementById('step2-form').style.display = 'block';
        startCountdown(299); setupOTP();
      } else { showError(data.error || 'Could not send OTP. Check student ID.'); }
    }).catch(() => showError('Network error.'));
}

function showError(msg) {
  const e = document.getElementById('error-msg');
  if (e) { e.textContent = msg; e.style.display = 'block'; }
}

function startCountdown(secs) {
  const el = document.getElementById('otp-timer');
  otpTimer = setInterval(() => {
    if (secs <= 0) { clearInterval(otpTimer); if (el) el.textContent = 'Expired'; return; }
    secs--;
    if (el) el.textContent = `${Math.floor(secs/60).toString().padStart(2,'0')}:${(secs%60).toString().padStart(2,'0')}`;
  }, 1000);
}

function setupOTP() {
  const boxes = document.querySelectorAll('.otp-box');
  boxes.forEach((b, i) => {
    b.addEventListener('input', () => { if (b.value.length === 1 && i < boxes.length - 1) boxes[i+1].focus(); });
    b.addEventListener('keydown', e => { if (e.key === 'Backspace' && !b.value && i > 0) boxes[i-1].focus(); });
  });
}

function verifyOTP() {
  const boxes = document.querySelectorAll('.otp-box');
  const code  = Array.from(boxes).map(b => b.value).join('');
  if (code.length < 6) { alert('Enter the full 6-digit OTP.'); return; }
  const sid   = document.getElementById('student-id').value.trim();
  const email = document.getElementById('guardian-email').value.trim();
  const fd = new FormData();
  fd.append('student_id', sid); fd.append('guardian_email', email); fd.append('otp_code', code);
  fetch(window.location.origin + '/mindcare_final/backend/api/guardian/verify_otp.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) window.location.href = window.location.origin + '/mindcare_final/frontend/pages/guardian/booking.html';
      else alert('Invalid or expired OTP. Try again.');
    }).catch(() => alert('Network error.'));
}

document.addEventListener('DOMContentLoaded', setupOTP);