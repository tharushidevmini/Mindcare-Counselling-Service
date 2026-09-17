const API = window.location.origin + '/mindcare_final/backend';

document.addEventListener('DOMContentLoaded', () => {
  setGreeting();
  loadCheckinStatus();
  loadMoodChart();
  renderPhqQuestions();
});

function setGreeting() {
  const h = new Date().getHours();
  const g = h < 12 ? 'Good morning' : h < 17 ? 'Good afternoon' : 'Good evening';
  const el = document.getElementById('greeting');
  if (el) el.textContent = g + ' 👋';
  const d = document.getElementById('today-date');
  if (d) d.textContent = new Date().toLocaleDateString('en-US', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
  });
}

// ── PHQ-9 Questionnaire ──────────────────────────────
const PHQ_QUESTIONS = [
  'Little interest or pleasure in doing things',
  'Feeling down, depressed, or hopeless',
  'Trouble falling or staying asleep, or sleeping too much',
  'Feeling tired or having little energy',
  'Poor appetite or overeating',
  'Feeling bad about yourself — or that you are a failure, or have let yourself or your family down',
  'Trouble concentrating on things, such as studying or reading',
  'Moving or speaking so slowly that others noticed — or being so restless that you moved around a lot more than usual',
  'Thoughts that you would be better off dead, or of hurting yourself in some way'
];
const PHQ_OPTIONS = ['Not at all', 'Several days', 'More than half the days', 'Nearly every day'];

function renderPhqQuestions() {
  const box = document.getElementById('phq-questions');
  if (!box) return;
  box.innerHTML = PHQ_QUESTIONS.map((q, i) => {
    const n = i + 1;
    return `
      <div style="margin-bottom:1.1rem; padding-bottom:1rem; border-bottom:1px solid var(--beige-light);">
        <div style="font-size:13.5px; font-weight:600; color:var(--text); margin-bottom:.6rem;">${n}. ${q}</div>
        <div style="display:flex; gap:.4rem; flex-wrap:wrap;">
          ${PHQ_OPTIONS.map((opt, val) => `
            <label style="flex:1; min-width:110px; cursor:pointer;">
              <input type="radio" name="q${n}" value="${val}" ${val === 0 ? 'checked' : ''} style="display:none;" onchange="highlightPhq(${n})">
              <div class="phq-opt" id="phq-${n}-${val}" style="text-align:center; padding:.5rem .3rem; border-radius:8px; border:1.5px solid ${val===0 ? 'var(--color-orange)' : 'var(--border)'}; background:${val===0 ? 'var(--orange-pale)' : 'white'}; font-size:11px; color:${val===0 ? 'var(--orange-dark)' : 'var(--text-3)'}; font-weight:${val===0 ? '700':'500'}; transition:.15s;">${opt}</div>
            </label>`).join('')}
        </div>
      </div>`;
  }).join('');
}

function highlightPhq(n) {
  const selected = document.querySelector(`input[name="q${n}"]:checked`).value;
  for (let v = 0; v <= 3; v++) {
    const el = document.getElementById(`phq-${n}-${v}`);
    if (!el) continue;
    const isSel = v == selected;
    el.style.borderColor = isSel ? 'var(--color-orange)' : 'var(--border)';
    el.style.background  = isSel ? 'var(--orange-pale)' : 'white';
    el.style.color       = isSel ? 'var(--orange-dark)' : 'var(--text-3)';
    el.style.fontWeight  = isSel ? '700' : '500';
  }
}

async function loadCheckinStatus() {
  try {
    const res  = await fetch(`${API}/api/mood/today.php`);
    const data = await res.json();
    if (data.checked_in) {
      const al = document.getElementById('checkin-alert');
      if (al) al.style.display = 'none';
      setLabel('stat-sleep',  severityShortLabel(data.severity));
      setLabel('stat-energy', (data.total_score ?? '—'));
      setLabel('stat-stress', data.mood_emoji || '—');
      setLabel('stat-social', data.checkin_date || '—');
    }
  } catch (e) { console.error(e); }
}

function severityShortLabel(sev) {
  return sev || '—';
}

function setLabel(id, val) {
  const el = document.getElementById(id);
  if (el) el.textContent = val;
}

async function loadMoodChart() {
  try {
    const res   = await fetch(`${API}/api/mood/history.php`);
    const data  = await res.json();
    const chart = document.getElementById('mood-chart');
    if (!chart || !data.records) return;

    const W = 640, H = 260, PAD_L = 32, PAD_R = 14, PAD_T = 16, PAD_B = 30;
    const plotW = W - PAD_L - PAD_R;
    const plotH = H - PAD_T - PAD_B;
    const maxScore = 27;

    // Only real PHQ-9 check-ins count as data points — old pre-PHQ9 rows (total_score is NULL)
    // are treated as "no data" instead of being falsely shown as a perfect score of 0.
    const dayAbbrev = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const points = data.records.map(r => (r.total_score === null || r.total_score === undefined) ? null : +r.total_score);
    const dates  = data.records.map(r => r.checkin_date);
    // Pad to 7 slots aligned to the right (most recent = last)
    while (points.length < 7) points.unshift(null);
    while (dates.length < 7) dates.unshift(null);
    // Real records get their actual weekday; empty padded slots get the correct
    // calendar weekday counting back from today, so labels always match real dates.
    const today = new Date();
    const labelSet = dates.map((d, i) => {
      if (d) return dayAbbrev[new Date(d + 'T00:00:00').getDay()];
      const offset = (dates.length - 1) - i;
      const dt = new Date(today);
      dt.setDate(dt.getDate() - offset);
      return dayAbbrev[dt.getDay()];
    });

    const x = i => PAD_L + (plotW / 6) * i;
    const y = score => PAD_T + plotH - (score / maxScore) * plotH;

    const zoneColor = s => s === null ? '#ccc' : s <= 4 ? '#2D9B6A' : s <= 9 ? '#3B82C4' : s <= 14 ? '#F5A846' : '#DC4C4C';

    // Severity background bands (Minimal/Mild/Moderate/Severe+)
    const bands = [
      { from: 0,  to: 4,  color: 'rgba(45,155,106,0.09)' },
      { from: 4,  to: 9,  color: 'rgba(59,130,196,0.10)' },
      { from: 9,  to: 14, color: 'rgba(245,168,70,0.12)' },
      { from: 14, to: 27, color: 'rgba(220,76,76,0.10)' },
    ];
    const bandRects = bands.map(b => `<rect x="${PAD_L}" y="${y(b.to)}" width="${plotW}" height="${y(b.from)-y(b.to)}" fill="${b.color}"/>`).join('');
    const bandNames = [
      { label: 'Minimal',  mid: 2,   color: '#2D9B6A' },
      { label: 'Mild',     mid: 6.5, color: '#3B82C4' },
      { label: 'Moderate', mid: 11.5,color: '#D9860F' },
      { label: 'Severe',   mid: 20.5,color: '#DC4C4C' },
    ];
    const bandLabels = bandNames.map(b => `<text x="${PAD_L + plotW - 6}" y="${y(b.mid)+4}" font-size="11" font-weight="700" fill="${b.color}" text-anchor="end" opacity="0.85">${b.label}</text>`).join('');

    // Line path — skip gaps where there's no data
    let pathD = '';
    let drawing = false;
    points.forEach((s, i) => {
      if (s === null) { drawing = false; return; }
      const cmd = drawing ? 'L' : 'M';
      pathD += `${cmd}${x(i)},${y(s)} `;
      drawing = true;
    });

    const dots = points.map((s, i) => s === null ? '' : `
      <circle cx="${x(i)}" cy="${y(s)}" r="6.5" fill="${zoneColor(s)}" stroke="white" stroke-width="2.5"/>
      <title>${labelSet[i]}: score ${s}</title>`).join('');

    const xLabels = labelSet.map((d, i) => `<text x="${x(i)}" y="${H-8}" font-size="12" font-weight="600" fill="var(--text-2)" text-anchor="middle">${d}</text>`).join('');
    const yLabels = [0, 9, 18, 27].map(v => `<text x="${PAD_L-10}" y="${y(v)+4}" font-size="11" font-weight="700" fill="var(--text-2)" text-anchor="end">${v}</text>`).join('');

    chart.innerHTML = `
      <svg viewBox="0 0 ${W} ${H}" style="width:100%; height:${H}px;">
        ${bandRects}
        ${bandLabels}
        ${yLabels}
        <path d="${pathD.trim()}" fill="none" stroke="var(--color-orange)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        ${dots}
        ${xLabels}
      </svg>
      <div style="display:flex; gap:1.5rem; margin-top:1rem; flex-wrap:wrap; align-items:center;">
        <span style="display:flex; align-items:center; font-size:13px; font-weight:600; color:var(--text-2);"><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#2D9B6A;margin-right:6px;"></span>Minimal (0-4)</span>
        <span style="display:flex; align-items:center; font-size:13px; font-weight:600; color:var(--text-2);"><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#3B82C4;margin-right:6px;"></span>Mild (5-9)</span>
        <span style="display:flex; align-items:center; font-size:13px; font-weight:600; color:var(--text-2);"><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#F5A846;margin-right:6px;"></span>Moderate (10-14)</span>
        <span style="display:flex; align-items:center; font-size:13px; font-weight:600; color:var(--text-2);"><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#DC4C4C;margin-right:6px;"></span>Severe (15+)</span>
      </div>`;

    setProgress('streak-bar', 'streak-val', data.records.length, 7);
    setProgress('relax-bar',  'relax-val',  3, 5);
    setProgress('diary-bar',  'diary-val',  4, 5);
  } catch (e) { console.error(e); }
}

function setProgress(barId, valId, cur, max) {
  const b = document.getElementById(barId);
  if (b) b.style.width = Math.round((cur / max) * 100) + '%';
  const v = document.getElementById(valId);
  if (v) v.textContent = `${cur} / ${max}`;
}

function openCheckinModal()  { document.getElementById('checkin-modal').style.display = 'flex'; }
function closeCheckinModal() { document.getElementById('checkin-modal').style.display = 'none'; }
document.addEventListener('keydown', (e) => {
  const m = document.getElementById('checkin-modal');
  if (e.key === 'Escape' && m && m.style.display === 'flex') closeCheckinModal();
});

let checkinEmoji = '';
function selectEmoji(e, btn) {
  checkinEmoji = e;
  document.querySelectorAll('#checkin-modal .emoji-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
}

async function submitCheckin() {
  const fd = new FormData();
  for (let n = 1; n <= 9; n++) {
    const checked = document.querySelector(`input[name="q${n}"]:checked`);
    fd.append(`q${n}`, checked ? checked.value : 0);
  }
  fd.append('mood_emoji', checkinEmoji);

  try {
    const res  = await fetch(`${API}/api/mood/checkin.php`, { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
      closeCheckinModal();
      loadCheckinStatus();
      loadMoodChart();
      toast.success(`Check-in saved — ${data.severity} level today. Take care of yourself 🌿`);
    } else {
      toast.error('Could not save: ' + (data.error || 'Please try again.'));
    }
  } catch (e) {
    toast.error('Network error: ' + e.message);
  }
}

function doLogout() {
  window.location.href = window.location.origin + '/mindcare_final/frontend/pages/login.html';
}