// ═══════════════════════════════════════════
// FILE: frontend/js/advisor.js
// Learning Advisor: academic accommodation requests (CSP module)
// ═══════════════════════════════════════════

const ADVISOR_API = window.location.origin + '/mindcare_final/backend/api/advisor';

document.addEventListener('DOMContentLoaded', loadRelief);

let _advisorRequests = [];

async function loadRelief() {
  try {
    const res  = await fetch(`${ADVISOR_API}/requests.php`);
    const data = await res.json();
    _advisorRequests = data.requests || [];
    const tbody = document.getElementById('relief-table');
    if (!tbody || !data.requests) return;
    if (data.requests.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--text-3);padding:2rem;">No pending requests.</td></tr>'; return;
    }
    tbody.innerHTML = data.requests.map(r => `
      <tr>
        <td><span class="badge badge-beige">STU-#${r.token}</span></td>
        <td>${r.accommodation_type}</td>
        <td>${r.forwarded_by}</td>
        <td style="font-size:12.5px;">${r.created_at}</td>
        <td>
          <span class="badge ${r.status==='approved'?'badge-mint':r.status==='rejected'?'badge-red':'badge-orange'}">${r.status}</span>
          ${r.status === 'rejected' && r.reject_reason ? `<div style="font-size:11px; color:var(--text-3); margin-top:3px; max-width:160px;">${r.reject_reason}</div>` : ''}
        </td>
        <td>${r.status === 'pending' ? `<div style="display:flex;gap:.4rem;">
          <button class="btn btn-primary btn-sm" onclick="updateRelief(${r.id},'approved')">Approve</button>
          <button class="btn btn-danger btn-sm" onclick="openRejectModal(${r.id})">Reject</button>
        </div>` : `<button class="btn btn-ghost btn-sm">View</button>`}</td>
      </tr>`).join('');
  } catch (e) { console.error(e); }
}

async function updateRelief(id, status, reason) {
  const fd = new FormData();
  fd.append('request_id', id); fd.append('status', status);
  if (reason) fd.append('reason', reason);
  const res = await fetch(`${ADVISOR_API}/update.php`, { method:'POST', body:fd });
  const d   = await res.json();
  if (d.success) loadRelief(); else toast.error('Error: ' + d.error);
  return d;
}

// ── Add Accommodation Request modal ─────────────────────────
let _advisorStudents = [];

function openAddRequestModal() {
  document.getElementById('add-request-modal').style.display = 'flex';
  loadAdvisorStudents();
}
function closeAddRequestModal() {
  document.getElementById('add-request-modal').style.display = 'none';
  document.getElementById('req-accommodation').value = '';
  document.getElementById('req-student').value = '';
}

async function loadAdvisorStudents() {
  const select = document.getElementById('req-student');
  if (_advisorStudents.length) return; // already loaded once
  try {
    const res  = await fetch(`${ADVISOR_API}/students.php`);
    const data = await res.json();
    _advisorStudents = data.students || [];
    select.innerHTML = '<option value="">— Select a student —</option>' +
      _advisorStudents.map(s => `<option value="${s.id}">${s.full_name}${s.student_id ? ' (' + s.student_id + ')' : ''}</option>`).join('');
    if (window.enhanceSearchable) window.enhanceSearchable();
  } catch (e) {
    select.innerHTML = '<option value="">Could not load students</option>';
  }
}

async function submitAddRequest() {
  const studentId = document.getElementById('req-student').value;
  const accommodation = document.getElementById('req-accommodation').value.trim();
  if (!studentId)     { toast.warning('Please select a student.'); return; }
  if (!accommodation) { toast.warning('Please describe the accommodation needed.'); return; }

  const fd = new FormData();
  fd.append('student_id', studentId);
  fd.append('accommodation_type', accommodation);
  try {
    const res  = await fetch(`${ADVISOR_API}/add_request.php`, { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
      toast.success('Accommodation request added.');
      closeAddRequestModal();
      loadRelief();
    } else {
      toast.error(data.error || 'Could not add request.');
    }
  } catch (e) {
    toast.error('Network error.');
  }
}

// ── Email Counselor modal ───────────────────────────────────
let _advisorCounselors = [];

function openEmailCounselorModal() {
  document.getElementById('email-counselor-modal').style.display = 'flex';
  loadAdvisorCounselors();
}
function closeEmailCounselorModal() {
  document.getElementById('email-counselor-modal').style.display = 'none';
  document.getElementById('ec-counselor').value = '';
  document.getElementById('ec-subject').value = '';
  document.getElementById('ec-message').value = '';
}

async function loadAdvisorCounselors() {
  const select = document.getElementById('ec-counselor');
  if (_advisorCounselors.length) return; // already loaded once
  try {
    const res  = await fetch(`${ADVISOR_API}/counselors.php`);
    const data = await res.json();
    _advisorCounselors = data.counselors || [];
    select.innerHTML = '<option value="">— Select a counselor —</option>' +
      _advisorCounselors.map(c => `<option value="${c.id}">${c.full_name}${c.specialty ? ' — ' + c.specialty : ''}</option>`).join('');
  } catch (e) {
    select.innerHTML = '<option value="">Could not load counselors</option>';
  }
}

async function submitEmailCounselor() {
  const counselorId = document.getElementById('ec-counselor').value;
  const subject      = document.getElementById('ec-subject').value.trim();
  const message       = document.getElementById('ec-message').value.trim();
  if (!counselorId) { toast.warning('Please select a counselor.'); return; }
  if (!subject)     { toast.warning('Please add a subject.'); return; }
  if (!message)     { toast.warning('Please write a message.'); return; }

  const btn = document.getElementById('ec-send-btn');
  const originalHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = 'Sending…';

  const fd = new FormData();
  fd.append('counselor_id', counselorId);
  fd.append('subject', subject);
  fd.append('message', message);
  try {
    const res  = await fetch(`${ADVISOR_API}/email_counselor.php`, { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
      toast.success(data.message || 'Email sent.');
      closeEmailCounselorModal();
    } else {
      toast.error(data.error || 'Could not send email.');
    }
  } catch (e) {
    toast.error('Network error.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalHtml;
  }
}

// ── Export Approved Requests ────────────────────────────────
function _approvedRequests() {
  return _advisorRequests.filter(r => r.status === 'approved');
}

function _csvEscape(val) {
  const s = String(val ?? '');
  return /[",\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
}

function exportApprovedCSV() {
  const rows = _approvedRequests();
  if (rows.length === 0) { toast.warning('No approved requests to export yet.'); return; }

  const header = ['Student Token', 'Accommodation Needed', 'Forwarded By', 'Date', 'Status'];
  const lines = [header.join(',')];
  rows.forEach(r => {
    lines.push([
      _csvEscape('STU-#' + r.token),
      _csvEscape(r.accommodation_type),
      _csvEscape(r.forwarded_by),
      _csvEscape(r.created_at),
      _csvEscape(r.status),
    ].join(','));
  });

  const csvContent = lines.join('\r\n');
  const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  const dateStr = new Date().toISOString().slice(0, 10);
  a.href = url;
  a.download = `mindcare_approved_accommodations_${dateStr}.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
  toast.success(`Exported ${rows.length} approved request${rows.length === 1 ? '' : 's'}.`);
}

function printApprovedPDF() {
  const rows = _approvedRequests();
  if (rows.length === 0) { toast.warning('No approved requests to print yet.'); return; }

  const dateStr = new Date().toLocaleDateString('en-GB', { day:'numeric', month:'long', year:'numeric' });
  const rowsHtml = rows.map(r => `
    <tr>
      <td>STU-#${r.token}</td>
      <td>${r.accommodation_type}</td>
      <td>${r.forwarded_by}</td>
      <td>${r.created_at}</td>
    </tr>`).join('');

  const win = window.open('', '_blank');
  win.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>Approved Academic Accommodations — MindCare</title>
      <style>
        body { font-family: Arial, sans-serif; color:#1C1410; padding: 2rem; }
        h1 { font-size: 1.4rem; margin-bottom: .25rem; }
        p.sub { color:#8C7060; font-size: 13px; margin-top:0; margin-bottom: 1.5rem; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #eee; }
        th { background: #FEF3DF; color: #8C7060; text-transform: uppercase; font-size: 11px; }
        @media print { body { padding: 0.5rem; } }
      </style>
    </head>
    <body>
      <h1>Approved Academic Accommodations</h1>
      <p class="sub">MindCare — Learning Advisor Dashboard &nbsp;·&nbsp; Generated ${dateStr} &nbsp;·&nbsp; ${rows.length} record${rows.length === 1 ? '' : 's'}</p>
      <table>
        <thead><tr><th>Student Token</th><th>Accommodation Needed</th><th>Forwarded By</th><th>Date</th></tr></thead>
        <tbody>${rowsHtml}</tbody>
      </table>
    </body>
    </html>`);
  win.document.close();
  win.onload = () => win.print();
}

// ── Reject Reason modal ─────────────────────────────────────
let _rejectingId = null;

function openRejectModal(id) {
  _rejectingId = id;
  document.getElementById('reject-reason-text').value = '';
  document.getElementById('reject-reason-modal').style.display = 'flex';
}
function closeRejectModal() {
  document.getElementById('reject-reason-modal').style.display = 'none';
  _rejectingId = null;
}

async function submitReject() {
  const reason = document.getElementById('reject-reason-text').value.trim();
  if (!reason) { toast.warning('Please give a reason for rejecting this request.'); return; }
  if (!_rejectingId) { closeRejectModal(); return; }

  const btn = document.getElementById('reject-confirm-btn');
  const originalText = btn.textContent;
  btn.disabled = true;
  btn.textContent = 'Rejecting…';

  const d = await updateRelief(_rejectingId, 'rejected', reason);
  btn.disabled = false;
  btn.textContent = originalText;

  if (d.success) {
    toast.success('Request rejected.');
    closeRejectModal();
  }
}

// ── Email History modal ─────────────────────────────────────
function openEmailHistoryModal() {
  document.getElementById('email-history-modal').style.display = 'flex';
  loadEmailHistory();
}
function closeEmailHistoryModal() {
  document.getElementById('email-history-modal').style.display = 'none';
}

async function loadEmailHistory() {
  const list = document.getElementById('email-history-list');
  list.innerHTML = '<p style="color:var(--text-3); font-size:13.5px;">Loading…</p>';
  try {
    const res  = await fetch(`${ADVISOR_API}/email_history.php`);
    const data = await res.json();
    const emails = data.emails || [];
    if (emails.length === 0) {
      list.innerHTML = '<p style="color:var(--text-3); font-size:13.5px;">No emails sent yet.</p>';
      return;
    }
    list.innerHTML = emails.map(e => `
      <div style="border:1px solid var(--border, #eee); border-radius:10px; padding:12px 14px; margin-bottom:10px;">
        <div style="display:flex; justify-content:space-between; gap:.5rem; align-items:flex-start;">
          <strong style="font-size:13.5px;">${e.subject}</strong>
          <span style="font-size:11px; color:var(--text-3); white-space:nowrap;">${e.sent_at}</span>
        </div>
        <div style="font-size:12px; color:var(--text-3); margin:2px 0 6px;">To: ${e.counselor_name}</div>
        <div style="font-size:12.5px; color:var(--text-2); line-height:1.6; white-space:pre-wrap;">${e.message}</div>
      </div>`).join('');
  } catch (e) {
    list.innerHTML = '<p style="color:var(--red); font-size:13.5px;">Could not load email history.</p>';
  }
}