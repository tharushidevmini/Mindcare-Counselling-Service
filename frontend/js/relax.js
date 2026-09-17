const API = window.location.origin + '/mindcare_final/backend';

// ── Breathing Exercise ────────────────────────────────
let breatheInterval = null;

function startBreathing() {
  document.getElementById('breathe-card').style.display = 'block';
  document.getElementById('breathe-card').scrollIntoView({ behavior: 'smooth' });
  runBreathCycle();
}

function runBreathCycle() {
  const el = document.getElementById('breathe-text');
  const circle = el;

  const phases = [
    { text: 'Breathe In',  duration: 4000, scale: 1.3 },
    { text: 'Hold',        duration: 7000, scale: 1.3 },
    { text: 'Breathe Out', duration: 8000, scale: 1.0 },
  ];

  let i = 0;
  function next() {
    const phase = phases[i % phases.length];
    circle.textContent = phase.text;
    circle.style.transform = `scale(${phase.scale})`;
    circle.style.background = phase.text === 'Breathe In'
      ? 'var(--color-mint)'
      : phase.text === 'Hold'
      ? 'var(--color-beige)'
      : '#d0e8f5';
    i++;
    breatheInterval = setTimeout(next, phase.duration);
  }
  next();
}

function stopBreathing() {
  clearTimeout(breatheInterval);
  document.getElementById('breathe-card').style.display = 'none';
  document.getElementById('breathe-text').textContent = 'Breathe In';
}

// ── Calm Music ────────────────────────────────────────
let audio = null;
let playing = false;

function playAudio() {
  const btn = event.target;
  if (!audio) {
    // Free ambient music from Pixabay
    audio = new Audio('https://cdn.pixabay.com/download/audio/2022/03/10/audio_270f41e9bd.mp3?filename=soft-rain-ambient-111154.mp3');
    audio.loop = true;
    audio.volume = 0.5;
  }
  if (playing) {
    audio.pause();
    playing = false;
    btn.textContent = '▶ Play Music';
  } else {
    audio.play().catch(() => {
      alert('Could not play audio. Check your internet connection.');
    });
    playing = true;
    btn.textContent = '⏸ Pause Music';
  }
}

// ── Meditation Timer ──────────────────────────────────
let timerSeconds = 300;
let timerRunning = false;
let timerInterval = null;

function startTimer() {
  document.getElementById('timer-card').style.display = 'block';
  document.getElementById('timer-card').scrollIntoView({ behavior: 'smooth' });
}

function setTimer(minutes) {
  clearInterval(timerInterval);
  timerRunning = false;
  timerSeconds = minutes * 60;
  document.getElementById('timer-display').textContent = formatTime(timerSeconds);
  document.getElementById('timer-toggle-btn').textContent = '▶ Start';
}

function toggleTimer() {
  const btn = document.getElementById('timer-toggle-btn');
  if (timerRunning) {
    clearInterval(timerInterval);
    timerRunning = false;
    btn.textContent = '▶ Resume';
  } else {
    timerRunning = true;
    btn.textContent = '⏸ Pause';
    timerInterval = setInterval(() => {
      timerSeconds--;
      document.getElementById('timer-display').textContent = formatTime(timerSeconds);
      if (timerSeconds <= 0) {
        clearInterval(timerInterval);
        timerRunning = false;
        btn.textContent = '▶ Start';
        document.getElementById('timer-display').textContent = '00:00';
        alert('Session complete. Well done 🌿');
      }
    }, 1000);
  }
}

function stopTimer() {
  clearInterval(timerInterval);
  timerRunning = false;
  timerSeconds = 300;
  document.getElementById('timer-display').textContent = '05:00';
  document.getElementById('timer-toggle-btn').textContent = '▶ Start';
  document.getElementById('timer-card').style.display = 'none';
}

function formatTime(s) {
  const m = Math.floor(s / 60);
  const sec = s % 60;
  return String(m).padStart(2,'0') + ':' + String(sec).padStart(2,'0');
}