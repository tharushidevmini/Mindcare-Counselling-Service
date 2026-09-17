const API = window.location.origin + '/mindcare_final/backend';

document.addEventListener('DOMContentLoaded', () => {
  loadCounselors();
  const dateInput = document.getElementById('booking-date');
  if (dateInput) {
    const today = new Date().toISOString().split('T')[0];
    dateInput.min   = today;
    dateInput.value = today;
    selectedDate = today;
  }
});

let selectedCounselor = null, selectedSlot = '', selectedMode = 'physical', selectedDate = '';

function changeDate() {
  const dateInput = document.getElementById('booking-date');
  selectedDate = dateInput.value || new Date().toISOString().split('T')[0];
  selectedSlot = '';                       // picking a new date clears the old time choice
  if (selectedCounselor) loadSlots(selectedCounselor.id);
  updateSummary();
}

async function loadCounselors() {
  const list = document.getElementById('counselor-list');
  try {
    const res  = await fetch(`${API}/api/appointments/counselors.php`);
    const data = await res.json();
    if (!list) return;
    if (!data.counselors || data.counselors.length === 0) {
      list.innerHTML = '<p>No counselors found.</p>'; return;
    }
    list.innerHTML = data.counselors.map(c => `
      <div class="counselor-item"
        onclick="pickCounselor(${c.id},'${c.full_name}',this)"
        style="cursor:pointer;padding:.75rem;border:2px solid #eee;border-radius:10px;margin-bottom:.5rem;display:flex;align-items:center;gap:.75rem;transition:.2s;">
        <div style="width:40px;height:40px;border-radius:50%;background:var(--color-mint);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:18px;color:#1A5C38;">${c.full_name.charAt(0)}</div>
        <div>
          <div style="font-weight:600;">${c.full_name}</div>
          <div style="font-size:12px;color:#888;">General Counseling</div>
        </div>
      </div>`).join('');
  } catch (e) {
    if (list) list.innerHTML = '<p style="color:red;">Error: ' + e.message + '</p>';
  }
}

function pickCounselor(id, name, el) {
  selectedCounselor = { id, name };
  document.querySelectorAll('.counselor-item').forEach(c => {
    c.style.borderColor = '#eee';
    c.style.background  = 'white';
  });
  el.style.borderColor = 'var(--color-orange)';
  el.style.background  = '#fffaf5';
  loadSlots(id);
  updateSummary();
}

async function loadSlots(cid) {
  const grid = document.getElementById('time-slots');
  if (!grid) return;
  grid.innerHTML = '<p style="color:#aaa;font-size:13px;">Loading slots...</p>';
  try {
    const dateParam = selectedDate || new Date().toISOString().split('T')[0];
    const res  = await fetch(`${API}/api/appointments/slots.php?counselor_id=${cid}&date=${dateParam}`);
    const data = await res.json();
    if (!data.slots || data.slots.length === 0) {
      grid.innerHTML = '<p>No slots available.</p>'; return;
    }
    const isToday = !data.date || data.date === new Date().toISOString().split('T')[0];

    grid.innerHTML = data.slots.map(s => {
      if (s.slot_type === 'recurring') {
        return `<button disabled title="Reserved every week for an ongoing student" style="
          position:relative;padding:.55rem .75rem;border-radius:8px;
          font-size:13px;font-weight:500;
          border:2px solid #a78bfa;background:#f5f2ff;
          color:#7c3aed;cursor:not-allowed;">
          🔁 ${s.time}
          <span style="position:absolute;top:-8px;right:-4px;font-size:8px;font-weight:700;background:#7c3aed;color:white;border-radius:20px;padding:2px 5px;">Weekly</span>
        </button>`;
      } else if (s.slot_type === 'guardian_emergency') {
        return `<button disabled title="Reserved every day for guardian emergency access" style="
          position:relative;padding:.55rem .75rem;border-radius:8px;
          font-size:13px;font-weight:500;
          border:2px solid #f5a846;background:#fef3df;
          color:#D4841A;cursor:not-allowed;">
          🛡️ ${s.time}
          <span style="position:absolute;top:-8px;right:-4px;font-size:8px;font-weight:700;background:#D4841A;color:white;border-radius:20px;padding:2px 5px;">Guardian</span>
        </button>`;
      } else if (s.slot_type === 'blocked') {
        return `<button disabled title="${s.reason || 'Reserved by counselor'}" style="
          position:relative;padding:.55rem .75rem;border-radius:8px;
          font-size:13px;font-weight:500;
          border:2px solid #fca5a5;background:#fff1f1;
          color:#ef4444;cursor:not-allowed;">
          🔒 ${s.time}
          <span style="position:absolute;top:-8px;right:-4px;font-size:9px;font-weight:700;background:#ef4444;color:white;border-radius:20px;padding:2px 6px;">Reserved</span>
        </button>`;
      } else if (s.slot_type === 'student') {
        return `<button disabled title="Already booked" style="
          position:relative;padding:.55rem .75rem;border-radius:8px;
          font-size:13px;font-weight:500;
          border:2px solid #e5e5e5;background:#fafafa;
          color:#bbb;cursor:not-allowed;text-decoration:line-through;">
          ${s.time}
          <span style="position:absolute;top:-8px;right:-4px;font-size:9px;font-weight:700;background:#6b7280;color:white;border-radius:20px;padding:2px 6px;">Booked</span>
        </button>`;
      } else {
        const urgency = isToday ? getSlotUrgency(s.time) : { state: 'open', border: 'var(--color-orange)', bg: 'white', text: 'var(--text)', label: '' };

        if (urgency.state === 'passed') {
          return `<button disabled title="This time has already passed today" style="
            position:relative;padding:.55rem .75rem;border-radius:8px;
            font-size:13px;font-weight:500;
            border:2px solid #e5e5e5;background:#fafafa;
            color:#ccc;cursor:not-allowed;">
            ${s.time}
          </button>`;
        }

        return `<button onclick="pickSlot('${s.time}',this)" title="${urgency.label}" style="
          position:relative;padding:.55rem .75rem;border-radius:8px;
          font-size:13px;font-weight:500;
          border:2px solid ${urgency.border};background:${urgency.bg};
          color:${urgency.text};cursor:pointer;transition:.15s;">
          ${s.time}
          ${urgency.label ? `<span style="position:absolute;top:-8px;right:-4px;font-size:8px;font-weight:700;background:${urgency.border};color:white;border-radius:20px;padding:2px 5px;">${urgency.label}</span>` : ''}
        </button>`;
      }
    }).join('');
  } catch (e) {
    grid.innerHTML = '<p style="color:red;">Error: ' + e.message + '</p>';
  }
}

function pickSlot(time, btn) {
  selectedSlot = time;
  document.querySelectorAll('#time-slots button:not([disabled])').forEach(s => {
    s.style.background  = 'white';
    s.style.borderColor = 'var(--color-orange)';
    s.style.color       = 'var(--text)';
  });
  btn.style.background  = 'var(--color-mint)';
  btn.style.borderColor = '#2D9B6A';
  btn.style.color       = '#1A5C38';
  updateSummary();
}

// ── Time proximity color coding (green -> orange -> red as slot approaches, grey once passed) ──
function timeStrToMinutes(str) {
  const m = str.match(/(\d+):(\d+)\s*(AM|PM)/i);
  if (!m) return null;
  let [, h, min, ap] = m;
  h = parseInt(h); min = parseInt(min);
  if (ap.toUpperCase() === 'PM' && h !== 12) h += 12;
  if (ap.toUpperCase() === 'AM' && h === 12) h = 0;
  return h * 60 + min;
}

function getSlotUrgency(timeStr) {
  const slotMin = timeStrToMinutes(timeStr);
  const now     = new Date();
  const nowMin  = now.getHours() * 60 + now.getMinutes();
  if (slotMin === null) return { state: 'open', border: 'var(--color-orange)', bg: 'white', text: 'var(--text)', label: '' };

  const diff = slotMin - nowMin;
  if (diff < 0)   return { state: 'passed' };
  if (diff <= 30) return { state: 'soon',  border: '#ef4444', bg: '#fff1f1', text: '#c0392b', label: 'Soon' };
  if (diff <= 60) return { state: 'near',  border: '#f59e0b', bg: '#fff8ec', text: '#b45309', label: '' };
  return { state: 'open', border: '#2D9B6A', bg: '#f0fbf5', text: '#1A5C38', label: '' };
}

function selectMode(mode, el) {
  selectedMode = mode;
  document.querySelectorAll('.mode-card').forEach(c => {
    c.style.border     = '2px solid #eee';
    c.style.background = 'white';
  });
  el.style.border     = '2px solid var(--color-orange)';
  el.style.background = 'var(--color-beige)';
  updateSummary();
}

function updateSummary() {
  const el = document.getElementById('summary-details');
  if (!el) return;
  if (!selectedCounselor || !selectedSlot) {
    el.innerHTML = '<span style="color:#aaa;">Select a counselor and time to see summary.</span>';
    return;
  }
  el.innerHTML = `
    <div style="display:flex;flex-direction:column;gap:.5rem;font-size:14px;">
      <div>👩‍⚕️ <strong>${selectedCounselor.name}</strong></div>
      <div>📅 ${selectedSlot}</div>
      <div>${selectedMode === 'physical' ? '🏫 Physical — Room 204, Block B' : '💻 Online — Secure video link'}</div>
    </div>`;
}

async function confirmBooking() {
  if (!selectedCounselor || !selectedSlot) {
    toast.warning('Please select a counselor and a time slot first.');
    return;
  }
  const note = document.getElementById('booking-note')?.value.trim() || '';
  const fd   = new FormData();
  fd.append('counselor_id',   selectedCounselor.id);
  fd.append('preferred_time', selectedSlot);
  fd.append('preferred_date', selectedDate || new Date().toISOString().split('T')[0]);
  fd.append('session_type',   selectedMode);
  fd.append('notes',          note);

  try {
    const res  = await fetch(`${API}/api/appointments/book.php`, { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
      toast.success('Booking confirmed! Your counselor will be in touch soon.');
      selectedCounselor = null;
      selectedSlot      = '';
      loadCounselors();
      loadMyAppointments();
    } else {
      toast.error(data.error || 'Could not complete booking. Please try again.');
    }
  } catch (e) {
    toast.error('Network error. Please check your connection.');
  }
}

// ── My Upcoming Appointments + Cancel ──────────────────────
async function loadMyAppointments() {
  const box = document.getElementById('upcoming-appointments');
  if (!box) return;
  box.innerHTML = '<p class="text-muted">Loading...</p>';
  try {
    const res  = await fetch(`${API}/api/appointments/my.php`);
    const data = await res.json();
    const list = data.appointments || [];
    if (list.length === 0) {
      box.innerHTML = '<p class="text-muted">No upcoming appointments yet.</p>';
      return;
    }
    const statusColor = { pending: '#f59e0b', accepted: '#2D9B6A', postponed: '#D4841A' };
    box.innerHTML = list.map(a => {
      const needsMyConfirmation = a.status === 'pending' && a.booking_source === 'counselor_assigned';
      return `
      <div style="display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.7rem; border:1.5px solid ${needsMyConfirmation ? 'var(--color-orange, #F5A846)' : 'var(--border)'}; background:${needsMyConfirmation ? '#FEF3DF' : 'transparent'}; border-radius:10px; margin-bottom:.5rem;">
        <div>
          <div style="font-weight:600; font-size:13.5px;">${a.counselor_name}</div>
          <div style="font-size:12px; color:var(--text-3);">${a.preferred_date} · ${a.preferred_time} · ${a.session_type}</div>
          <span style="font-size:11px; font-weight:700; color:${statusColor[a.status] || '#888'};">${a.status.toUpperCase()}</span>
          ${a.status === 'postponed' && a.new_date && a.new_time ? `
            <div style="font-size:11px; color:#D4841A; margin-top:2px;">New time proposed: ${a.new_date} · ${a.new_time}</div>
          ` : ''}
          ${needsMyConfirmation ? `
            <div style="font-size:11px; color:#D4841A; margin-top:2px;"><i class="bi bi-info-circle-fill"></i> Your counselor set this up for you — please confirm.</div>
          ` : ''}
        </div>
        <div style="display:flex; gap:.4rem;">
          ${needsMyConfirmation ? `<button class="btn btn-mint btn-sm" onclick="acceptAssignedAppointment(${a.id})">Accept</button>` : ''}
          <button class="btn btn-danger btn-sm" onclick="cancelAppointment(${a.id})">Cancel</button>
        </div>
      </div>`;
    }).join('');
  } catch (e) {
    box.innerHTML = '<p style="color:red;">Could not load appointments.</p>';
  }
}

// Confirms a session the counselor set up directly (e.g. an emergency
// walk-in slot) — the student's own acceptance, mirroring how a counselor
// accepts a normal student-initiated booking.
async function acceptAssignedAppointment(id) {
  const fd = new FormData();
  fd.append('appointment_id', id);
  fd.append('status', 'accepted');
  try {
    const res  = await fetch(`${API}/api/appointments/update.php`, { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
      toast.success('Session confirmed!');
      loadMyAppointments();
    } else {
      toast.error(data.error || 'Could not confirm this session.');
    }
  } catch (e) {
    toast.error('Network error. Please try again.');
  }
}

async function cancelAppointment(id) {
  const reason = prompt("Why can't you attend? (optional — this note is sent to your counselor)", '');
  if (reason === null) return;                 // user clicked Cancel on the box — leave the appointment alone
  const fd = new FormData();
  fd.append('appointment_id', id);
  fd.append('status', 'cancelled');
  fd.append('reason', (reason || '').trim());  // blank is fine — counselor just sees "none given"
  try {
    const res  = await fetch(`${API}/api/appointments/update.php`, { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
      toast.success('Appointment cancelled. Your counselor has been notified.');
      loadMyAppointments();
      if (selectedCounselor) loadSlots(selectedCounselor.id); // free the slot back up
    } else {
      toast.error(data.error || 'Could not cancel appointment.');
    }
  } catch (e) {
    toast.error('Network error. Make sure XAMPP is running.');
  }
}

document.addEventListener('DOMContentLoaded', loadMyAppointments);