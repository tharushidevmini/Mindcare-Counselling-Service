const API = window.location.origin + '/mindcare_final/backend';

function escapeHtml(str) {
  const d = document.createElement('div');
  d.textContent = str ?? '';
  return d.innerHTML;
}

let allEntries = [];
let diaryEmoji = '';
let calYear  = new Date().getFullYear();
let calMonth = new Date().getMonth();

document.addEventListener('DOMContentLoaded', async () => {
  await loadDiaryEntries();
  buildCalendar();
});

function selectDiaryEmoji(e, btn) {
  diaryEmoji = e;
  document.querySelectorAll('.emoji-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
}

async function saveDiaryEntry() {
  const title   = document.getElementById('diary-title').value.trim();
  const content = document.getElementById('diary-content').value.trim();
  const share        = document.getElementById('share-toggle').checked ? 1 : 0;
  const shareGuardian = document.getElementById('share-guardian-toggle').checked ? 1 : 0;
  if (!content) { toast.warning('Please write something before saving.'); return; }
  const fd = new FormData();
  fd.append('title', title);
  fd.append('content', content);
  fd.append('mood_emoji', diaryEmoji);
  fd.append('share_with_counselor', share);
  fd.append('share_with_guardian', shareGuardian);
  try {
    const res  = await fetch(`${API}/api/diary/save.php`, { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
      document.getElementById('diary-title').value    = '';
      document.getElementById('diary-content').value  = '';
      document.getElementById('share-toggle').checked = false;
      document.getElementById('share-guardian-toggle').checked = false;
      diaryEmoji = '';
      document.querySelectorAll('.emoji-btn').forEach(b => b.classList.remove('selected'));
      await loadDiaryEntries();
      buildCalendar();
      toast.success('Entry saved!');
    } else {
      toast.error('Could not save: ' + (data.error || 'Try again.'));
    }
  } catch (e) { toast.error('Error: ' + e.message); }
}

async function loadDiaryEntries() {
  try {
    const res  = await fetch(`${API}/api/diary/list.php`);
    const data = await res.json();
    allEntries = data.entries || [];
    renderEntryList();
  } catch (e) { console.error(e); }
}

function renderEntryList() {
  const list = document.getElementById('diary-entries-list');
  if (!list) return;
  if (allEntries.length === 0) {
    list.innerHTML = '<p style="color:#aaa;font-size:13px;">No entries yet.</p>';
    return;
  }

  const sharedCount   = allEntries.filter(e => e.share_with_counselor == 1).length;
  const allShared     = sharedCount === allEntries.length;
  const guardShared   = allEntries.filter(e => e.share_with_guardian == 1).length;
  const allGuardShared = guardShared === allEntries.length;

  // Bulk controls bar
  const controls = `
    <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;flex-wrap:wrap;
         margin-bottom:.85rem;padding:.6rem .85rem;background:var(--white);border:1px solid var(--border);border-radius:12px;">
      <label style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--text-2);cursor:pointer;">
        <input type="checkbox" id="select-all-entries" onchange="toggleSelectAll(this)">
        Select all
      </label>
      <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
        <button class="btn btn-mint btn-sm" onclick="shareSelected(1)">Share w/ counselor</button>
        <button class="btn btn-ghost btn-sm" onclick="shareSelected(0)">Unshare counselor</button>
        <button class="btn btn-mint btn-sm" onclick="shareSelectedGuardian(1)">Share w/ guardian</button>
        <button class="btn btn-ghost btn-sm" onclick="shareSelectedGuardian(0)">Unshare guardian</button>
      </div>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.35rem;font-size:12px;color:var(--text-3);">
      <span>${sharedCount} of ${allEntries.length} shared with counselor</span>
      <button class="btn btn-ghost btn-sm" style="font-size:11px;padding:3px 10px;"
        onclick="shareAll(${allShared ? 0 : 1})">
        ${allShared ? 'Make whole diary private' : 'Share whole diary'}
      </button>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;font-size:12px;color:var(--text-3);">
      <span>${guardShared} of ${allEntries.length} shared with guardian</span>
      <button class="btn btn-ghost btn-sm" style="font-size:11px;padding:3px 10px;"
        onclick="shareAllGuardian(${allGuardShared ? 0 : 1})">
        ${allGuardShared ? 'Unshare whole diary from guardian' : 'Share whole diary with guardian'}
      </button>
    </div>`;

  const items = allEntries.map(e => `
    <div style="margin-bottom:.7rem;padding:.85rem 1rem;background:var(--beige-light);border-radius:12px;border:1.5px solid transparent;transition:.2s;display:flex;gap:.6rem;align-items:flex-start;"
      onmouseover="this.style.borderColor='var(--color-orange)'"
      onmouseout="this.style.borderColor='transparent'">
      <input type="checkbox" class="entry-check" value="${e.id}" style="margin-top:3px;flex-shrink:0;cursor:pointer;">
      <div style="flex:1;cursor:pointer;" onclick="showEntryModal('${e.entry_date}')">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
          <span style="font-weight:600;font-size:14px;">${escapeHtml(e.mood_emoji || '')} ${escapeHtml(e.title || 'Untitled')}</span>
          <span style="font-size:11px;color:#999;">${e.entry_date}</span>
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
          <span style="font-size:11px;padding:2px 8px;border-radius:20px;
            background:${e.share_with_counselor == 1 ? 'var(--mint-pale)' : '#f0f0f0'};
            color:${e.share_with_counselor == 1 ? '#2d7a4f' : '#888'};">
            ${e.share_with_counselor == 1 ? '✓ Shared with counselor' : '🔒 Private from counselor'}
          </span>
          <span style="font-size:11px;padding:2px 8px;border-radius:20px;
            background:${e.share_with_guardian == 1 ? '#FEF3DF' : '#f0f0f0'};
            color:${e.share_with_guardian == 1 ? '#B4790E' : '#888'};">
            ${e.share_with_guardian == 1 ? '✓ Shared with guardian' : '🔒 Private from guardian'}
          </span>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0;">
        <button class="btn btn-ghost btn-sm" style="font-size:11px;padding:3px 10px;"
          onclick="shareOne(${e.id}, ${e.share_with_counselor == 1 ? 0 : 1})">
          ${e.share_with_counselor == 1 ? 'Unshare counselor' : 'Share counselor'}
        </button>
        <button class="btn btn-ghost btn-sm" style="font-size:11px;padding:3px 10px;"
          onclick="shareOneGuardian(${e.id}, ${e.share_with_guardian == 1 ? 0 : 1})">
          ${e.share_with_guardian == 1 ? 'Unshare guardian' : 'Share guardian'}
        </button>
      </div>
    </div>`).join('');

  list.innerHTML = controls + items;
}

// Tick / untick every entry checkbox
function toggleSelectAll(box) {
  document.querySelectorAll('.entry-check').forEach(c => c.checked = box.checked);
}

// Collect the ids of ticked entries
function selectedEntryIds() {
  return Array.from(document.querySelectorAll('.entry-check:checked')).map(c => c.value);
}

async function postToggleShare(ids, share) {
  const fd = new FormData();
  fd.append('ids', ids);
  fd.append('share', share);
  const res  = await fetch(`${API}/api/diary/toggle_share.php`, { method:'POST', body:fd });
  return res.json();
}

// Share/unshare the ticked entries
async function shareSelected(share) {
  const ids = selectedEntryIds();
  if (!ids.length) { toast.warning('Tick the entries you want first.'); return; }
  try {
    const data = await postToggleShare(ids.join(','), share);
    if (data.success) {
      toast.success(share ? 'Selected entries shared with counselor.' : 'Selected entries are now private.');
      loadDiaryEntries();
    } else { toast.error(data.error || 'Could not update.'); }
  } catch (e) { toast.error('Network error.'); }
}

// One entry's Share / Make private button
async function shareOne(id, share) {
  try {
    const data = await postToggleShare(String(id), share);
    if (data.success) {
      toast.success(share ? 'Shared with counselor.' : 'Now private.');
      loadDiaryEntries();
    } else { toast.error(data.error || 'Could not update.'); }
  } catch (e) { toast.error('Network error.'); }
}

// Whole diary at once
async function shareAll(share) {
  if (!confirm(share ? 'Share your WHOLE diary with your counselor?' : 'Make your WHOLE diary private?')) return;
  try {
    const data = await postToggleShare('all', share);
    if (data.success) {
      toast.success(share ? 'Whole diary shared with counselor.' : 'Whole diary is now private.');
      loadDiaryEntries();
    } else { toast.error(data.error || 'Could not update.'); }
  } catch (e) { toast.error('Network error.'); }
}

// ── Guardian sharing — same pattern as counselor sharing above, separate
// column (share_with_guardian) and separate endpoint, so the two are
// controlled fully independently. ──────────────────────────────────────
async function postToggleShareGuardian(ids, share) {
  const fd = new FormData();
  fd.append('ids', ids);
  fd.append('share', share);
  const res = await fetch(`${API}/api/diary/toggle_share_guardian.php`, { method:'POST', body:fd });
  return res.json();
}

async function shareSelectedGuardian(share) {
  const ids = selectedEntryIds();
  if (!ids.length) { toast.warning('Tick the entries you want first.'); return; }
  try {
    const data = await postToggleShareGuardian(ids.join(','), share);
    if (data.success) {
      toast.success(share ? 'Selected entries shared with guardian.' : 'Selected entries no longer shared with guardian.');
      loadDiaryEntries();
    } else { toast.error(data.error || 'Could not update.'); }
  } catch (e) { toast.error('Network error.'); }
}

async function shareOneGuardian(id, share) {
  try {
    const data = await postToggleShareGuardian(String(id), share);
    if (data.success) {
      toast.success(share ? 'Shared with guardian.' : 'No longer shared with guardian.');
      loadDiaryEntries();
    } else { toast.error(data.error || 'Could not update.'); }
  } catch (e) { toast.error('Network error.'); }
}

async function shareAllGuardian(share) {
  if (!confirm(share ? 'Share your WHOLE diary with your guardian?' : 'Stop sharing your WHOLE diary with your guardian?')) return;
  try {
    const data = await postToggleShareGuardian('all', share);
    if (data.success) {
      toast.success(share ? 'Whole diary shared with guardian.' : 'Whole diary no longer shared with guardian.');
      loadDiaryEntries();
    } else { toast.error(data.error || 'Could not update.'); }
  } catch (e) { toast.error('Network error.'); }
}

function buildCalendar() {
  const calEl = document.getElementById('diary-calendar');
  if (!calEl) return;

  const entryDates  = allEntries.map(e => e.entry_date);
  const today       = new Date();
  const isThisMonth = calYear === today.getFullYear() && calMonth === today.getMonth();
  const todayDate   = today.getDate();
  const firstDay    = new Date(calYear, calMonth, 1).getDay();
  const daysInMonth = new Date(calYear, calMonth + 1, 0).getDate();
  const monthNames  = ['January','February','March','April','May','June',
                       'July','August','September','October','November','December'];

  // Header
  const header = document.getElementById('calendar-month');
  if (header) {
    header.innerHTML = `
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;padding-bottom:.75rem;border-bottom:1px solid var(--beige-light);">
        <button onclick="changeMonth(-1)"
          style="width:34px;height:34px;border-radius:50%;border:1.5px solid var(--color-beige);background:white;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;transition:.2s;"
          onmouseover="this.style.background='var(--beige-light)'"
          onmouseout="this.style.background='white'">&lsaquo;</button>
        <div style="text-align:center;">
          <div style="font-weight:700;font-size:15px;color:var(--text);">${monthNames[calMonth]}</div>
          <div style="font-size:11px;color:var(--text-3);margin-top:1px;">${calYear}</div>
        </div>
        <button onclick="changeMonth(1)"
          style="width:34px;height:34px;border-radius:50%;border:1.5px solid var(--color-beige);background:white;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;transition:.2s;"
          onmouseover="this.style.background='var(--beige-light)'"
          onmouseout="this.style.background='white'">&rsaquo;</button>
      </div>`;
  }

  // Day labels
  const days = ['Su','Mo','Tu','We','Th','Fr','Sa'];
  let html = '<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:6px;">';

  html += days.map(d =>
    `<div style="font-size:10px;color:var(--text-3);text-align:center;font-weight:700;padding:4px 0;">${d}</div>`
  ).join('');

  // Empty cells
  for (let i = 0; i < firstDay; i++) html += '<div></div>';

  // Day cells
  for (let d = 1; d <= daysInMonth; d++) {
    const dateStr  = `${calYear}-${String(calMonth+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
    const isToday  = isThisMonth && d === todayDate;
    const hasEntry = entryDates.includes(dateStr);

    let bg     = 'transparent';
    let color  = 'var(--text-2)';
    let fw     = '400';
    let border = '1.5px solid transparent';
    let hover  = 'var(--beige-light)';

    if (isToday) {
      bg = 'var(--color-orange)'; color = 'white'; fw = '700'; border = 'none'; hover = 'var(--color-orange)';
    } else if (hasEntry) {
      bg = 'var(--orange-pale)'; color = 'var(--orange-dark)'; fw = '600'; border = '1.5px solid var(--color-orange)'; hover = '#f5d9a0';
    }

    html += `
      <div onclick="handleDateClick('${dateStr}')"
        style="
          aspect-ratio:1;
          display:flex;align-items:center;justify-content:center;
          border-radius:10px;font-size:13px;cursor:pointer;
          transition:all .15s;
          font-weight:${fw};
          background:${bg};
          color:${color};
          border:${border};
          position:relative;
        "
        onmouseover="this.style.transform='scale(1.1)';this.style.background='${hover}'"
        onmouseout="this.style.transform='scale(1)';this.style.background='${bg}'">
        ${d}
        ${hasEntry && !isToday ? `<span style="position:absolute;bottom:3px;width:4px;height:4px;border-radius:50%;background:var(--color-orange);"></span>` : ''}
      </div>`;
  }

  html += '</div>';

  // Legend
  html += `
    <div style="display:flex;align-items:center;gap:1rem;margin-top:.85rem;padding-top:.75rem;border-top:1px solid var(--beige-light);">
      <div style="display:flex;align-items:center;gap:5px;font-size:11px;color:var(--text-3);">
        <div style="width:10px;height:10px;border-radius:3px;background:var(--orange-pale);border:1px solid var(--color-orange);"></div>
        Has entry
      </div>
      <div style="display:flex;align-items:center;gap:5px;font-size:11px;color:var(--text-3);">
        <div style="width:10px;height:10px;border-radius:3px;background:var(--color-orange);"></div>
        Today
      </div>
      <div style="margin-left:auto;font-size:11px;color:var(--text-3);">${allEntries.length} entries total</div>
    </div>`;

  calEl.innerHTML = html;
}

function changeMonth(dir) {
  calMonth += dir;
  if (calMonth < 0)  { calMonth = 11; calYear--; }
  if (calMonth > 11) { calMonth = 0;  calYear++; }
  buildCalendar();
}

function handleDateClick(dateStr) {
  const entry = allEntries.find(e => e.entry_date === dateStr);
  if (entry) {
    showEntryModal(dateStr);
  } else {
    showNoEntryModal(dateStr);
  }
}

async function showEntryModal(dateStr) {
  const entry = allEntries.find(e => e.entry_date === dateStr);
  if (!entry) return;

  // Show the modal immediately with a loading state, then fill in the real content.
  openModal(`
    <div style="font-size:11px;color:var(--text-3);margin-bottom:.5rem;">${escapeHtml(entry.entry_date)}</div>
    <h3 style="margin-bottom:.75rem;font-size:1.2rem;">${escapeHtml(entry.mood_emoji || '')} ${escapeHtml(entry.title || 'Untitled')}</h3>
    <div style="display:flex;gap:6px;flex-wrap:wrap;">
      <span style="font-size:11px;padding:3px 10px;border-radius:20px;
        background:${entry.share_with_counselor == 1 ? 'var(--mint-pale)' : '#f0f0f0'};
        color:${entry.share_with_counselor == 1 ? '#2d7a4f' : '#888'};">
        ${entry.share_with_counselor == 1 ? 'Shared with counselor' : 'Private from counselor'}
      </span>
      <span style="font-size:11px;padding:3px 10px;border-radius:20px;
        background:${entry.share_with_guardian == 1 ? '#FEF3DF' : '#f0f0f0'};
        color:${entry.share_with_guardian == 1 ? '#B4790E' : '#888'};">
        ${entry.share_with_guardian == 1 ? 'Shared with guardian' : 'Private from guardian'}
      </span>
    </div>
    <div id="entry-content-box" style="margin-top:1.25rem;padding:1rem;background:var(--beige-light);border-radius:12px;font-size:14px;color:#555;line-height:1.8;white-space:pre-wrap;">
      Loading your entry…
    </div>
    <div style="margin-top:.75rem;font-size:11px;color:#bbb;text-align:center;">Saved on ${entry.entry_date}</div>
  `);

  try {
    const res  = await fetch(`${API}/api/diary/view.php?id=${entry.id}`);
    const data = await res.json();
    const box  = document.getElementById('entry-content-box');
    if (!box) return; // modal was closed before the fetch finished
    if (data.error) {
      box.textContent = 'Could not load this entry: ' + data.error;
    } else {
      box.textContent = data.content;
    }
  } catch (e) {
    const box = document.getElementById('entry-content-box');
    if (box) box.textContent = 'Network error — could not load entry.';
  }
}

function showNoEntryModal(dateStr) {
  openModal(`
    <div style="text-align:center;padding:1rem 0;">
      <div style="font-size:11px;color:var(--text-3);margin-bottom:.75rem;">${dateStr}</div>
      <h3 style="margin-bottom:.5rem;font-size:1.1rem;color:var(--text-2);">No entry for this day</h3>
      <p style="font-size:13px;color:var(--text-3);">Nothing was written on this date.</p>
    </div>
  `);
}

function openModal(content) {
  let modal = document.getElementById('entry-view-modal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'entry-view-modal';
    modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.45);z-index:1000;display:flex;align-items:center;justify-content:center;';
    modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
    document.body.appendChild(modal);
  }
  modal.innerHTML = `
    <div style="background:white;border-radius:18px;padding:2rem;max-width:460px;width:90%;max-height:80vh;overflow-y:auto;position:relative;box-shadow:0 12px 48px rgba(0,0,0,0.18);">
      <button onclick="document.getElementById('entry-view-modal').remove()"
        style="position:absolute;top:1rem;right:1rem;width:28px;height:28px;border-radius:50%;background:#f0f0f0;border:none;cursor:pointer;font-size:14px;">X</button>
      ${content}
      <button onclick="document.getElementById('entry-view-modal').remove()"
        style="width:100%;margin-top:1.25rem;background:var(--color-orange);border:none;border-radius:10px;padding:10px;font-size:14px;font-weight:600;cursor:pointer;color:white;">
        Close
      </button>
    </div>`;
  modal.style.display = 'flex';
}