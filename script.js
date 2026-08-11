const AUTH_URL = 'auth.php';
const API_URL = 'api.php';

let isClockedIn = false;

const clockStatus = document.getElementById('clockStatus');
const clockSince = document.getElementById('clockSince');
const clockBtn = document.getElementById('clockBtn');
const liveClock = document.getElementById('liveClock');
const historyBody = document.getElementById('historyBody');
const emptyState = document.getElementById('emptyState');
const toast = document.getElementById('toast');

document.addEventListener('DOMContentLoaded', init);
document.getElementById('logoutBtn').addEventListener('click', handleLogout);
clockBtn.addEventListener('click', handleClockToggle);

// ---------- Init ----------
async function init() {
  try {
    const res = await fetch(`${AUTH_URL}?action=check`);
    const json = await res.json();

    if (!json.loggedIn) {
      window.location.href = 'login.html';
      return;
    }

    document.getElementById('userChipName').textContent = json.username;
    document.getElementById('userChip').hidden = false;

    if (json.role === 'admin') {
      document.getElementById('adminLink').hidden = false;
    }

    startLiveClock();
    await refreshStatus();
    await loadHistory();
  } catch (err) {
    window.location.href = 'login.html';
  }
}

function startLiveClock() {
  const tick = () => {
    liveClock.textContent = new Date().toLocaleTimeString();
  };
  tick();
  setInterval(tick, 1000);
}

async function handleLogout() {
  try {
    await fetch(`${AUTH_URL}?action=logout`, { method: 'POST' });
  } finally {
    window.location.href = 'login.html';
  }
}

// ---------- Status ----------
async function refreshStatus() {
  const res = await fetch(`${API_URL}?action=status`);
  if (res.status === 401) { window.location.href = 'login.html'; return; }
  const json = await res.json();

  isClockedIn = json.clockedIn;
  const dot = document.getElementById('userDot');

  if (isClockedIn) {
    clockStatus.textContent = "You're clocked in";
    clockSince.textContent = `Since ${formatTime(json.sinceTime)}`;
    clockBtn.textContent = 'Clock out';
    clockBtn.className = 'btn btn-clock state-out';
    dot.classList.remove('off');
  } else {
    clockStatus.textContent = "You're clocked out";
    clockSince.textContent = '';
    clockBtn.textContent = 'Clock in';
    clockBtn.className = 'btn btn-clock state-in';
    dot.classList.add('off');
  }
  clockBtn.disabled = false;
}

async function handleClockToggle() {
  clockBtn.disabled = true;
  const action = isClockedIn ? 'clock_out' : 'clock_in';

  try {
    const res = await fetch(`${API_URL}?action=${action}`, { method: 'POST' });
    const json = await res.json();
    if (!json.success) throw new Error(json.message || 'Action failed.');

    showToast(isClockedIn ? 'Clocked out.' : 'Clocked in.');
    await refreshStatus();
    await loadHistory();
  } catch (err) {
    showToast(err.message, true);
    clockBtn.disabled = false;
  }
}

// ---------- History ----------
async function loadHistory() {
  try {
    const res = await fetch(`${API_URL}?action=history`);
    const json = await res.json();
    if (!json.success) throw new Error(json.message);

    const rows = json.data;
    emptyState.hidden = rows.length !== 0;
    historyBody.innerHTML = '';

    rows.forEach(r => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${formatDate(r.time_in)}</td>
        <td class="mono">${formatTime(r.time_in)}</td>
        <td class="mono">${r.time_out ? formatTime(r.time_out) : '—'}</td>
        <td class="mono">${formatDuration(r.minutes_worked)}</td>
      `;
      historyBody.appendChild(tr);
    });
  } catch (err) {
    showToast(err.message, true);
  }
}

// ---------- Helpers ----------
function formatTime(dtString) {
  if (!dtString) return '—';
  const d = new Date(dtString.replace(' ', 'T'));
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function formatDate(dtString) {
  if (!dtString) return '—';
  const d = new Date(dtString.replace(' ', 'T'));
  return d.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatDuration(minutes) {
  const h = Math.floor(minutes / 60);
  const m = Math.round(minutes % 60);
  return `${h}h ${m}m`;
}

function showToast(message, isError = false) {
  toast.textContent = message;
  toast.className = 'toast' + (isError ? ' error' : '');
  toast.hidden = false;
  clearTimeout(showToast._t);
  showToast._t = setTimeout(() => { toast.hidden = true; }, 2800);
}
