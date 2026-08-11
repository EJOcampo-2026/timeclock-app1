const AUTH_URL = 'auth.php';
const ADMIN_API_URL = 'admin_api.php';

const logsBody = document.getElementById('logsBody');
const emptyState = document.getElementById('emptyState');
const employeeFilter = document.getElementById('employeeFilter');
const dateFrom = document.getElementById('dateFrom');
const dateTo = document.getElementById('dateTo');

document.addEventListener('DOMContentLoaded', init);
document.getElementById('logoutBtn').addEventListener('click', handleLogout);
document.getElementById('applyFilters').addEventListener('click', loadLogs);
document.getElementById('refreshBtn').addEventListener('click', loadLogs);
document.getElementById('clearFilters').addEventListener('click', () => {
  employeeFilter.value = '';
  dateFrom.value = '';
  dateTo.value = '';
  loadLogs();
});
document.getElementById('exportBtn').addEventListener('click', handleExport);

// ---------- Init ----------
async function init() {
  try {
    const res = await fetch(`${AUTH_URL}?action=check`);
    const json = await res.json();

    if (!json.loggedIn) { window.location.href = 'login.html'; return; }
    if (json.role !== 'admin') { window.location.href = 'index.html'; return; }

    document.getElementById('userChipName').textContent = json.username;
    document.getElementById('userChip').hidden = false;

    await loadEmployees();
    await loadLogs();
  } catch (err) {
    window.location.href = 'login.html';
  }
}

async function handleLogout() {
  try {
    await fetch(`${AUTH_URL}?action=logout`, { method: 'POST' });
  } finally {
    window.location.href = 'login.html';
  }
}

// ---------- Data ----------
async function loadEmployees() {
  const res = await fetch(`${ADMIN_API_URL}?action=employees`);
  if (res.status === 403 || res.status === 401) { window.location.href = 'index.html'; return; }
  const json = await res.json();

  json.data.forEach(emp => {
    const opt = document.createElement('option');
    opt.value = emp.id;
    opt.textContent = emp.username;
    employeeFilter.appendChild(opt);
  });
}

async function loadLogs() {
  const params = new URLSearchParams({ action: 'logs' });
  if (employeeFilter.value) params.set('user_id', employeeFilter.value);
  if (dateFrom.value) params.set('date_from', dateFrom.value);
  if (dateTo.value) params.set('date_to', dateTo.value);

  const res = await fetch(`${ADMIN_API_URL}?${params.toString()}`);
  const json = await res.json();
  if (!json.success) return;

  const rows = json.data;
  emptyState.hidden = rows.length !== 0;
  logsBody.innerHTML = '';

  let activeCount = 0;
  let totalMinutes = 0;

  rows.forEach(r => {
    const active = !r.time_out;
    if (active) activeCount++;
    totalMinutes += Number(r.minutes_worked);

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${escapeHtml(r.username)}</td>
      <td>${formatDate(r.time_in)}</td>
      <td class="mono">${formatTime(r.time_in)}</td>
      <td class="mono">${r.time_out ? formatTime(r.time_out) : '—'}</td>
      <td class="mono">${formatDuration(r.minutes_worked)}</td>
      <td>
        <span class="status-pill ${active ? 'active' : ''}">
          <span class="dot"></span>${active ? 'Clocked in' : 'Done'}
        </span>
      </td>
    `;
    logsBody.appendChild(tr);
  });

  document.getElementById('statActive').textContent = activeCount;
  document.getElementById('statEntries').textContent = rows.length;
  document.getElementById('statHours').textContent = `${(totalMinutes / 60).toFixed(1)}h`;
}

// ---------- Export ----------
function handleExport() {
  const params = new URLSearchParams();
  if (employeeFilter.value) params.set('user_id', employeeFilter.value);
  if (dateFrom.value) params.set('date_from', dateFrom.value);
  if (dateTo.value) params.set('date_to', dateTo.value);

  // Navigating triggers the browser's normal file-download flow for the CSV.
  window.location.href = `export.php?${params.toString()}`;
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

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}
