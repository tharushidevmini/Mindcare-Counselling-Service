const API = window.location.origin + '/mindcare_final/backend';

document.addEventListener('DOMContentLoaded', () => {
  loadAppointments();
  loadAllStudentsForNotes();
  loadCancellations();
  loadGuardianDailySlot();
});

// ── Guardian's permanent daily emergency slot (separate from the ad-hoc
// "Emergency Reserved Slots" below) ──────────────────────────────────────
async function loadGuardianDailySlot() {
  const select = document.getElementById('guardian-daily-time');
  if (!select) return;
  try {
    const res  = await fetch(`${API}/api/counselor/guardian_daily_slot.php?action=get`);
    const data = await res.json();
    if (data.slot_time) select.value = data.slot_time;
  } catch (e) {
    console.error('Could not load guardian daily slot', e);
  }
}

async function saveGuardianDailySlot() {
  const time = document.getElementById('guardian-daily-time').value;
  const status = document.getElementById('guardian-daily-time-status');
  const fd = new FormData();
  fd.append('action', 'save');
  fd.append('slot_time', time);
  try {
    const res  = await fetch(`${API}/api/counselor/guardian_daily_slot.php`, { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
      toast.success(`Saved! ${time} is now your daily guardian emergency slot, every day going forward.`);
      if (status) { status.textContent = 'Saved ✓'; setTimeout(() => status.textContent = '', 2500); }
    } else {
      toast.error(data.error || 'Could not save.');
    }
  } catch (e) {
    toast.error('Network error: ' + e.message);
  }
}

// ── Cancellation notices: sessions a student cancelled (with their reason) ──
async function loadCancellations() {
  const box = document.getElementById('cancellation-list');
  if (!box) return;
  try {
    const res  = await fetch(`${API}/api/appointments/cancelled.php`);
    const data = await res.json();
    const list = data.cancellations || [];
    if (list.length === 0) {
      box.innerHTML = '<p style="color:var(--text-3); font-size:13px; margin:0;">No cancellations. 🎉</p>';
      return;
    }
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    box.innerHTML = `
      <div style="display:flex; justify-content:flex-end; margin-bottom:.6rem;">
        <button class="btn btn-ghost btn-sm" onclick="dismissAllCancellations()">Clear all</button>
      </div>` +
      list.map(c => `
      <div style="display:flex; gap:.75rem; padding:.75rem .9rem; border:1.5px solid ${c.guardian_id ? '#f5a846' : '#fca5a5'}; background:${c.guardian_id ? '#fef3df' : '#fff5f5'}; border-radius:10px; margin-bottom:.6rem;">
        <span style="font-size:1.2rem;">${c.guardian_id ? '🛡️' : '🔔'}</span>
        <div style="flex:1;">
          <div style="font-weight:600; font-size:13.5px; color:${c.guardian_id ? '#D4841A' : '#c0392b'};">
            ${c.guardian_id
              ? `Cancelled by Guardian — ${esc(c.student_name)}${c.student_id ? ' (' + esc(c.student_id) + ')' : ''}`
              : `${esc(c.student_name)}${c.student_id ? ' (' + esc(c.student_id) + ')' : ''} cancelled a session`}
          </div>
          <div style="font-size:12px; color:var(--text-3); margin:2px 0 4px;">
            ${esc(c.preferred_date)} · ${esc(c.preferred_time)} · ${esc(c.session_type)}
          </div>
          <div style="font-size:13px; color:var(--text-2);">
            <strong>Reason:</strong> ${c.reschedule_reason ? esc(c.reschedule_reason) : '— none given —'}
          </div>
        </div>
        <button title="Dismiss" onclick="dismissCancellation(${c.id})" style="align-self:flex-start; background:none; border:none; color:#c0392b; font-size:18px; cursor:pointer; line-height:1;">✕</button>
      </div>`).join('');
  } catch (e) {
    box.innerHTML = '<p style="color:red; font-size:13px; margin:0;">Could not load cancellations.</p>';
  }
}

// Remove a single cancellation notice
async function dismissCancellation(id) {
  const fd = new FormData();
  fd.append('appointment_id', id);
  try {
    const res  = await fetch(`${API}/api/appointments/dismiss_cancellation.php`, { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) loadCancellations();
    else if (typeof toast !== 'undefined') toast.error(data.error || 'Could not dismiss notice.');
  } catch (e) {
    if (typeof toast !== 'undefined') toast.error('Network error. Please try again.');
  }
}

// Clear every cancellation notice at once
async function dismissAllCancellations() {
  if (!confirm('Clear all cancellation notices?')) return;
  const fd = new FormData();
  fd.append('action', 'all');
  try {
    const res  = await fetch(`${API}/api/appointments/dismiss_cancellation.php`, { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) loadCancellations();
    else if (typeof toast !== 'undefined') toast.error(data.error || 'Could not clear notices.');
  } catch (e) {
    if (typeof toast !== 'undefined') toast.error('Network error. Please try again.');
  }
}

// Populate the "Private Session Notes" student dropdown with EVERY active student —
// not just ones who happen to have an appointment right now.
async function loadAllStudentsForNotes() {
  const select = document.getElementById('session-student');
  if (!select) return;
  try {
    const res  = await fetch(`${API}/api/appointments/assign_emergency.php?action=students`);
    const data = await res.json();
    const students = data.students || [];
    select.innerHTML = '<option value="">— Select a student —</option>' +
      students.map(s => `<option value="${s.id}">${s.full_name}${s.student_id ? ' (' + s.student_id + ')' : ''}</option>`).join('');
  } catch (e) {
    console.error('Could not load student list', e);
  }
}

const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

async function loadAppointments() {
  try {
    const res  = await fetch(`${API}/api/appointments/pending.php`);
    const data = await res.json();
    const tbody = document.getElementById('appointments-table');

    // Stat cards now use the counts the backend already computes correctly
    // (pending / accepted-today / completed-this-month) instead of
    // recounting the currently-loaded list client-side, which used to give
    // misleading numbers (e.g. "Completed This Month" was actually
    // counting any accepted appointment still in the list, not completed
    // ones, and not scoped to the month at all).
    document.getElementById('count-pending').textContent = data.pending ?? 0;
    document.getElementById('count-today').textContent   = data.today   ?? 0;
    document.getElementById('count-month').textContent   = data.month   ?? 0;

    renderTodaySessions(data.appointments || []);

    if (!data.appointments || data.appointments.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:#aaa; padding:2rem;">No pending appointments</td></tr>';
      return;
    }

    tbody.innerHTML = data.appointments.map(a => {
      const postponedInfo = (a.status === 'postponed' && a.new_date && a.new_time)
        ? `<div style="font-size:11px; color:#D4841A; margin-top:2px;"><i class="bi bi-arrow-right-short"></i> New: ${esc(a.new_date)} · ${esc(a.new_time)}</div>`
        : '';
      const isCounselorAssigned = a.booking_source === 'counselor_assigned';
      const sourceInfo = isCounselorAssigned
        ? `<div style="font-size:10.5px; color:#8C7060; margin-top:2px;"><i class="bi bi-person-check-fill"></i> Assigned by you</div>`
        : (a.guardian_id ? `<div style="font-size:10.5px; color:#8C7060; margin-top:2px;"><i class="bi bi-shield-fill"></i> Guardian booking</div>` : '');

      return `<tr style="${a.status === 'accepted' ? 'background:#F0FBF4;' : a.status === 'postponed' ? 'background:#FFF9EE;' : ''}">
        <td><strong>${esc(a.student_name) || 'Student'}</strong>${sourceInfo}</td>
        <td>${esc(a.preferred_date)}${postponedInfo}</td>
        <td>${esc(a.preferred_time)}</td>
        <td>
          <span class="badge ${a.session_type === 'online' ? 'badge-mint' : 'badge-beige'}">${esc(a.session_type)}</span>
          ${a.session_type === 'online' ? `
            <a href="mailto:${a.guardian_id ? a.guardian_email : a.student_email}?subject=MindCare%20Session%20-%20Zoom%20Link" title="Email ${a.guardian_id ? 'the guardian' : 'the student'} the Zoom link" style="display:block; font-size:11px; color:var(--text-3); margin-top:3px; text-decoration:none;">
              <i class="bi bi-envelope-fill"></i> ${a.guardian_id ? '(Guardian) ' : ''}${esc(a.guardian_id ? a.guardian_email : a.student_email)}
            </a>
          ` : ''}
        </td>
        <td style="font-size:12px; color:#888;">${esc(a.notes) || '—'}</td>
        <td style="display:flex; gap:.5rem; flex-wrap:wrap; align-items:center;">
          ${a.status === 'pending' && isCounselorAssigned ? `
            <span class="badge badge-beige"><i class="bi bi-hourglass-split"></i> Waiting for student to confirm</span>
            <button class="btn btn-danger btn-sm" onclick="cancelAppointmentAsCounselor(${a.id})"><i class="bi bi-x-circle"></i> Cancel</button>
          ` : ''}
          ${a.status === 'pending' && !isCounselorAssigned ? `
            <button class="btn btn-mint btn-sm" onclick="updateAppointment(${a.id},'accepted')"><i class="bi bi-check-lg"></i> Accept</button>
            <button class="btn btn-ghost btn-sm" onclick="openPostponeModal(${a.id})"><i class="bi bi-clock-history"></i> Postpone</button>
          ` : ''}
          ${a.status === 'postponed' ? `
            <span class="badge badge-orange"><i class="bi bi-clock-history"></i> Postponed</span>
            <button class="btn btn-mint btn-sm" onclick="updateAppointment(${a.id},'accepted')"><i class="bi bi-check-lg"></i> Accept</button>
            <button class="btn btn-ghost btn-sm" onclick="openPostponeModal(${a.id})"><i class="bi bi-clock-history"></i> Reschedule again</button>
          ` : ''}
          ${a.status === 'accepted' ? `
            <span class="badge badge-mint"><i class="bi bi-check-circle-fill"></i> Accepted</span>
            <button class="btn btn-ghost btn-sm" onclick="markAppointmentComplete(${a.id})" title="Remove once the session has happened"><i class="bi bi-check2-all"></i> Mark Complete</button>
            ${(a.guardian_id || isCounselorAssigned) ? `<button class="btn btn-danger btn-sm" onclick="cancelAppointmentAsCounselor(${a.id})"><i class="bi bi-x-circle"></i> Cancel</button>` : ''}
          ` : ''}
        </td>
      </tr>`;
    }).join('');

  } catch (e) {
    console.error(e);
    document.getElementById('appointments-table').innerHTML =
      '<tr><td colspan="6" style="color:red; text-align:center;">Error: ' + e.message + '</td></tr>';
  }
}

// A dedicated, clearly-separated list of just today's accepted sessions —
// separate from the full Pending/Accepted/Postponed table below, so the
// counselor can see at a glance what's actually happening today without
// scanning the whole list.
function renderTodaySessions(appointments) {
  const card = document.getElementById('today-sessions-card');
  const box  = document.getElementById('today-sessions-list');
  if (!card || !box) return;

  const today = new Date().toISOString().split('T')[0];
  const todays = appointments
    .filter(a => a.status === 'accepted' && a.preferred_date === today)
    .sort((a, b) => a.preferred_time.localeCompare(b.preferred_time));

  if (todays.length === 0) {
    card.style.display = 'none';
    return;
  }

  card.style.display = 'block';
  box.innerHTML = todays.map(a => `
    <div style="display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.75rem .9rem; background:var(--mint-pale, #EAF9F0); border-radius:10px; margin-bottom:.5rem;">
      <div>
        <div style="font-weight:600; font-size:13.5px;">${esc(a.student_name) || 'Student'}</div>
        <div style="font-size:12px; color:var(--text-3);">
          <i class="bi bi-clock"></i> ${esc(a.preferred_time)} ·
          <span class="badge ${a.session_type === 'online' ? 'badge-mint' : 'badge-beige'}" style="margin-left:2px;">${esc(a.session_type)}</span>
        </div>
      </div>
      <button class="btn btn-ghost btn-sm" onclick="markAppointmentComplete(${a.id})" title="Remove once the session has happened"><i class="bi bi-check2-all"></i> Mark Complete</button>
    </div>`).join('');
}

// Removes an accepted appointment from the active list once the session
// has actually happened — sets status to 'completed', matching the same
// pattern already used by the backend (update.php already allows this).
let _completingAppointmentId = null;

function markAppointmentComplete(id) {
  _completingAppointmentId = id;
  document.getElementById('complete-modal').style.display = 'flex';
}

function closeCompleteModal() {
  document.getElementById('complete-modal').style.display = 'none';
  _completingAppointmentId = null;
}

async function confirmMarkComplete() {
  if (!_completingAppointmentId) return;
  const id = _completingAppointmentId;
  closeCompleteModal();

  const fd = new FormData();
  fd.append('appointment_id', id);
  fd.append('status', 'completed');
  try {
    const res  = await fetch(`${API}/api/appointments/update.php`, { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
      toast.success('Session marked complete.');
      loadAppointments();
    } else {
      toast.error('Could not update: ' + (data.error || 'Please try again.'));
    }
  } catch (e) {
    toast.error('Network error: ' + e.message);
  }
}

// Postpone flow — collects a new date/time (and optional reason) via the
// postpone-modal, then sends them to update.php, which emails the
// student/guardian the new proposed time automatically.
let _postponingAppointmentId = null;

function openPostponeModal(id) {
  _postponingAppointmentId = id;
  document.getElementById('postpone-new-date').value = '';
  document.getElementById('postpone-reason').value = '';
  document.getElementById('postpone-modal').style.display = 'flex';
}

function closePostponeModal() {
  document.getElementById('postpone-modal').style.display = 'none';
  _postponingAppointmentId = null;
}

async function confirmPostpone() {
  if (!_postponingAppointmentId) return;
  const id      = _postponingAppointmentId;
  const newDate = document.getElementById('postpone-new-date').value;
  const newTime = document.getElementById('postpone-new-time').value;
  const reason  = document.getElementById('postpone-reason').value.trim();

  if (!newDate) { toast.warning('Please pick a new date.'); return; }

  const fd = new FormData();
  fd.append('appointment_id', id);
  fd.append('status', 'postponed');
  fd.append('new_date', newDate);
  fd.append('new_time', newTime);
  fd.append('reason', reason);

  try {
    const res  = await fetch(`${API}/api/appointments/update.php`, { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
      closePostponeModal();
      toast.info('Session postponed — the student/guardian has been emailed the new time.');
      loadAppointments();
    } else {
      toast.error('Could not postpone: ' + (data.error || 'Please try again.'));
    }
  } catch (e) {
    toast.error('Network error: ' + e.message);
  }
}

async function updateAppointment(id, status) {
  const fd = new FormData();
  fd.append('appointment_id', id);
  fd.append('status', status);

  try {
    const res  = await fetch(`${API}/api/appointments/update.php`, { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
      toast.success('Appointment accepted! The student/guardian has been emailed.');
      loadAppointments();
    } else {
      toast.error('Could not update: ' + (data.error || 'Please try again.'));
    }
  } catch (e) {
    toast.error('Network error: ' + e.message);
  }
}

// Cancel an already-accepted/postponed appointment (student-booked or
// guardian-emergency-booked) — asks for a short reason, which then shows
// up in this same counselor's "Cancellation Notices" list above.
async function cancelAppointmentAsCounselor(id) {
  const reason = prompt('Why are you cancelling this session? (shown in Cancellation Notices)');
  if (reason === null) return; // user pressed Cancel on the prompt itself
  if (!reason.trim()) { toast.warning('Please give a short reason.'); return; }

  const fd = new FormData();
  fd.append('appointment_id', id);
  fd.append('status', 'cancelled');
  fd.append('reason', reason.trim());

  try {
    const res  = await fetch(`${API}/api/appointments/update.php`, { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
      toast.success('Session cancelled.');
      loadAppointments();
      loadCancellations();
    } else {
      toast.error('Could not cancel: ' + (data.error || 'Please try again.'));
    }
  } catch (e) {
    toast.error('Network error: ' + e.message);
  }
}

// ── Clinical Notes (full history per student) ──────
document.getElementById('session-student')?.addEventListener('change', function() {
  const studentId = this.value;
  document.getElementById('clinical-notes').value = '';  // fresh box for a NEW note
  if (!studentId) {
    document.getElementById('notes-history-wrap').style.display = 'none';
    return;
  }
  loadNotesHistory(studentId);
});

let _notesCache = [];  // holds the loaded history for print/download

async function loadNotesHistory(studentId) {
  const wrap = document.getElementById('notes-history-wrap');
  const box  = document.getElementById('notes-history');
  try {
    const res  = await fetch(`${API}/api/counselor/get_notes.php?student_id=${studentId}`);
    const data = await res.json();
    _notesCache = data.notes || [];
    if (_notesCache.length === 0) {
      wrap.style.display = 'none';
      return;
    }
    document.getElementById('notes-count').textContent = _notesCache.length + (_notesCache.length === 1 ? ' note' : ' notes');
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    box.innerHTML = _notesCache.map(n => `
      <div style="border-left:3px solid var(--color-orange); background:var(--beige-light); border-radius:8px; padding:.85rem 1rem; margin-bottom:.7rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:.4rem;">
          <span style="font-size:11.5px; color:var(--text-3);">🗓 ${esc(n.created_at)}</span>
          <button class="btn btn-ghost btn-sm" onclick="deleteNote(${n.id}, '${studentId}')" style="padding:2px 8px;">🗑</button>
        </div>
        <div style="font-size:13.5px; color:var(--text-2); line-height:1.7; white-space:pre-wrap;">${esc(n.notes)}</div>
      </div>`).join('');
    wrap.style.display = 'block';
  } catch (e) { console.error(e); }
}

async function saveClinicalNotes() {
  const studentId = document.getElementById('session-student').value;
  const notes     = document.getElementById('clinical-notes').value.trim();
  if (!studentId) { toast.warning('Please select a student first.'); return; }
  if (!notes)     { toast.warning('Please enter notes before saving.'); return; }

  const fd = new FormData();
  fd.append('student_id', studentId);
  fd.append('notes',      notes);
  try {
    const res  = await fetch(`${API}/api/counselor/save_notes.php`, { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
      toast.success('Session note saved securely 🔒');
      document.getElementById('clinical-notes').value = '';   // clear for the next session
      loadNotesHistory(studentId);                            // refresh the timeline
    } else {
      toast.error('Could not save: ' + (data.error || 'Please try again.'));
    }
  } catch (e) {
    toast.error('Network error: ' + e.message);
  }
}

let _pendingDelete = null;  // {noteId, studentId}

function deleteNote(noteId, studentId) {
  _pendingDelete = { noteId, studentId };
  document.getElementById('note-del-modal').style.display = 'flex';
}
function closeNoteDelModal() {
  document.getElementById('note-del-modal').style.display = 'none';
  _pendingDelete = null;
}
async function confirmDeleteNote() {
  if (!_pendingDelete) return;
  const { noteId, studentId } = _pendingDelete;
  closeNoteDelModal();
  const fd = new FormData();
  fd.append('note_id', noteId);
  try {
    const res  = await fetch(`${API}/api/counselor/delete_note.php`, { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) { toast.success('Session note deleted.'); loadNotesHistory(studentId); }
  } catch (e) { toast.error('Could not delete.'); }
}

function printNotes() {
  const studentName = document.getElementById('session-student').selectedOptions[0]?.textContent || 'Student';
  const rows = _notesCache.map(n => `<div style="border-left:3px solid #F5A846; background:#FBF6EF; padding:12px 14px; border-radius:8px; margin-bottom:10px;"><div style="font-size:11px; color:#8C7060; margin-bottom:6px;">${n.created_at}</div><div style="white-space:pre-wrap; line-height:1.6;">${n.notes.replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]))}</div></div>`).join('');
  const w = window.open('', '_blank');
  w.document.write(`<html><head><title>Session Notes — ${studentName}</title></head><body style="font-family:Arial,sans-serif; max-width:700px; margin:24px auto; color:#1C1410;"><h2>Private Session Notes</h2><p style="color:#8C7060;">${studentName}</p><hr>${rows}</body></html>`);
  w.document.close();
  w.print();
}

function downloadNotes() {
  const studentName = document.getElementById('session-student').selectedOptions[0]?.textContent || 'Student';
  let txt = `MindCare — Private Session Notes\nStudent: ${studentName}\nGenerated: ${new Date().toLocaleString()}\n\n`;
  _notesCache.slice().reverse().forEach(n => { txt += `[${n.created_at}]\n${n.notes}\n\n----------------------------\n\n`; });
  const blob = new Blob([txt], { type: 'text/plain' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = `session-notes-${studentName.replace(/\s+/g,'-')}.txt`;
  a.click();
  URL.revokeObjectURL(a.href);
}