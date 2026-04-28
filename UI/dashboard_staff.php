<?php
require_once __DIR__ . '/guard.php';
requireAuthPage();
if (($_SESSION['user']['role'] ?? '') === 'Admin') { header('Location: dashboard_admin.php'); exit; }
$staffName = $_SESSION['user']['name'] ?? 'Staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Staff Dashboard — Esperon Dairy Farm</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link rel="stylesheet" href="css/style.css" />
  <style>
    /* ── Staff Dashboard extras ── */
    .section-title {
      font-family: var(--font-serif);
      font-size: 1rem;
      font-weight: 700;
      color: var(--text);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .alert-item {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      padding: 10px 14px;
      border-radius: 10px;
      margin-bottom: 8px;
      font-size: 0.84rem;
    }
    .alert-item--danger  { background: var(--danger-lt);  border-left: 3px solid var(--danger);  color: var(--danger); }
    .alert-item--warning { background: var(--warning-lt); border-left: 3px solid var(--warning); color: #7a5a1e; }
    .alert-item--info    { background: var(--info-lt);    border-left: 3px solid var(--info);    color: #2d4f5e; }
    .task-row {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px 0;
      border-bottom: 1px solid var(--border-light);
      font-size: 0.85rem;
    }
    .task-row:last-child { border-bottom: none; }
    .task-row input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--olive); cursor: pointer; flex-shrink: 0; }
    .task-row.done span  { color: var(--muted); text-decoration: line-through; }
    .cow-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 9px 0;
      border-bottom: 1px solid var(--border-light);
      font-size: 0.84rem;
    }
    .cow-row:last-child { border-bottom: none; }
    .status-dot {
      width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 5px;
    }
    .status-dot--healthy { background: var(--olive); }
    .status-dot--sick    { background: var(--danger); }
    .log-entry {
      display: flex;
      gap: 10px;
      padding: 8px 0;
      border-bottom: 1px solid var(--border-light);
      font-size: 0.83rem;
    }
    .log-entry:last-child { border-bottom: none; }
    .log-time {
      font-size: 0.72rem;
      color: var(--muted);
      white-space: nowrap;
      min-width: 52px;
      padding-top: 2px;
    }
    .note-bubble {
      background: rgba(255,255,255,0.5);
      border: 1px solid var(--border-light);
      border-radius: 10px;
      padding: 10px 14px;
      margin-bottom: 8px;
      font-size: 0.84rem;
    }
    .note-bubble__meta {
      font-size: 0.7rem;
      color: var(--muted);
      margin-top: 4px;
    }
    .order-status {
      display: inline-block;
      padding: 2px 10px;
      border-radius: 20px;
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .order-status--pending    { background: var(--warning-lt); color: #7a5a1e; }
    .order-status--processing { background: var(--info-lt);    color: #2d4f5e; }
    .order-status--delivered  { background: var(--success-lt); color: var(--olive-dark); }
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg); margin-bottom: var(--spacing-xl); }
    .three-col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--spacing-lg); margin-bottom: var(--spacing-xl); }
    @media (max-width: 900px) { .two-col, .three-col { grid-template-columns: 1fr; } }
    .inv-bar-wrap { margin-bottom: 14px; }
    .inv-bar-wrap:last-child { margin-bottom: 0; }
    .inv-bar-label { display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 5px; }
    .inv-bar-label span:last-child { font-weight: 700; }
    .inv-bar { height: 8px; background: var(--beige); border-radius: 4px; overflow: hidden; }
    .inv-bar-fill { height: 100%; border-radius: 4px; transition: width 0.6s ease; }
    .inv-bar-fill--ok  { background: linear-gradient(90deg, var(--olive), var(--olive-light)); }
    .inv-bar-fill--low { background: linear-gradient(90deg, var(--danger), #e74c3c); }
    .inv-bar-fill--mid { background: linear-gradient(90deg, var(--gold), var(--gold-light)); }
    textarea.note-input {
      width: 100%; padding: 10px 14px; border: 1.5px solid var(--border-light);
      border-radius: 10px; font-size: 0.88rem; font-family: var(--font-sans);
      color: var(--text); background: rgba(255,255,255,0.7); resize: vertical;
      outline: none; transition: border-color 0.15s, box-shadow 0.15s;
    }
    textarea.note-input:focus { border-color: var(--olive); box-shadow: 0 0 0 3px rgba(78,96,64,0.12); }
    .btn-sm {
      padding: 7px 16px; border: none; border-radius: 8px; font-size: 0.82rem;
      font-weight: 600; cursor: pointer; font-family: var(--font-sans);
      display: inline-flex; align-items: center; gap: 5px; transition: opacity 0.15s;
    }
    .btn-sm--primary { background: linear-gradient(135deg, var(--olive), var(--olive-light)); color: #fff; }
    .btn-sm--primary:hover { opacity: 0.88; }
    .btn-sm--ghost { background: rgba(255,255,255,0.5); border: 1.5px solid var(--border); color: var(--text); }
    .btn-sm--ghost:hover { background: rgba(255,255,255,0.8); }
  </style>
</head>
<body>
<nav class="nav" id="app-nav"></nav>
<main class="main">

  <!-- ── Page Header ─────────────────────────────────────── -->
  <div class="page-header">
    <div>
      <h1 class="page-title" id="page-greeting">Good morning!</h1>
      <p class="page-subtitle" id="page-subtitle">Here's your farm work summary for today.</p>
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
      <span id="current-date" style="font-size:0.82rem;color:var(--muted);"></span>
    </div>
  </div>

  <!-- ── SECTION 1: Overview Stat Cards ─────────────────── -->
  <div class="stats-grid" style="margin-bottom:var(--spacing-xl);">
    <div class="stat-card">
      <div class="stat-card__icon"><span class="material-symbols-outlined">water_drop</span></div>
      <div class="stat-card__content">
        <div class="stat-card__val" id="stat-milk">—</div>
        <div class="stat-card__label">Milk Production (L)</div>
      </div>
    </div>
    <div class="stat-card stat-card--danger">
      <div class="stat-card__icon"><span class="material-symbols-outlined">shopping_cart</span></div>
      <div class="stat-card__content">
        <div class="stat-card__val" id="stat-orders">0</div>
        <div class="stat-card__label">Today's Orders</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-card__icon"><span class="material-symbols-outlined">task_alt</span></div>
      <div class="stat-card__content">
        <div class="stat-card__val" id="stat-tasks">0</div>
        <div class="stat-card__label">Pending Tasks</div>
      </div>
    </div>
    <div class="stat-card stat-card--gold">
      <div class="stat-card__icon"><span class="material-symbols-outlined">notification_important</span></div>
      <div class="stat-card__content">
        <div class="stat-card__val" id="stat-alerts">0</div>
        <div class="stat-card__label">Active Alerts</div>
      </div>
    </div>
  </div>

  <!-- ── SECTION 2: Alerts + Inventory ──────────────────── -->
  <div class="two-col">

    <!-- Alerts & Notifications -->
    <div class="card">
      <div class="card__header">
        <span class="section-title">
          <span class="material-symbols-outlined" style="color:var(--danger);font-size:1.2rem;">campaign</span>
          Alerts &amp; Notifications
        </span>
        <span id="alerts-badge" class="badge badge--red" style="display:none;font-size:0.68rem;">0</span>
      </div>
      <div id="alerts-list" style="padding:14px 20px;">
        <p style="color:var(--muted);font-size:0.84rem;">Checking for alerts…</p>
      </div>
    </div>

    <!-- Inventory Monitoring -->
    <div class="card">
      <div class="card__header">
        <span class="section-title">
          <span class="material-symbols-outlined" style="color:var(--olive);font-size:1.2rem;">inventory_2</span>
          Inventory Levels
        </span>
      </div>
      <div style="padding:16px 20px;">
        <div class="inv-bar-wrap">
          <div class="inv-bar-label">
            <span>Milk Stock</span>
            <span id="inv-milk-pct" style="color:var(--olive-dark);">—</span>
          </div>
          <div class="inv-bar"><div class="inv-bar-fill inv-bar-fill--ok" id="inv-milk-bar" style="width:0%"></div></div>
        </div>
        <div class="inv-bar-wrap">
          <div class="inv-bar-label">
            <span>Silage A</span>
            <span style="color:var(--olive-dark);">78%</span>
          </div>
          <div class="inv-bar"><div class="inv-bar-fill inv-bar-fill--ok" style="width:78%"></div></div>
        </div>
        <div class="inv-bar-wrap">
          <div class="inv-bar-label">
            <span>Silo B</span>
            <span style="color:#7a5a1e;">38%</span>
          </div>
          <div class="inv-bar"><div class="inv-bar-fill inv-bar-fill--low" style="width:38%"></div></div>
          <div style="font-size:0.7rem;color:var(--danger);margin-top:3px;">⚠ Low — notify admin</div>
        </div>
        <div class="inv-bar-wrap">
          <div class="inv-bar-label">
            <span>Hay</span>
            <span style="color:var(--olive-dark);">88%</span>
          </div>
          <div class="inv-bar"><div class="inv-bar-fill inv-bar-fill--ok" style="width:88%"></div></div>
        </div>
        <div class="inv-bar-wrap">
          <div class="inv-bar-label">
            <span>Animal Feed</span>
            <span style="color:#7a5a1e;">52%</span>
          </div>
          <div class="inv-bar"><div class="inv-bar-fill inv-bar-fill--mid" style="width:52%"></div></div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── SECTION 3: Tasks + Activity Log ────────────────── -->
  <div class="two-col">

    <!-- Task & Schedule Management -->
    <div class="card">
      <div class="card__header">
        <span class="section-title">
          <span class="material-symbols-outlined" style="color:var(--olive);font-size:1.2rem;">checklist</span>
          Today's Tasks
        </span>
        <span id="tasks-progress" class="badge badge--green" style="font-size:0.68rem;">Loading…</span>
      </div>
      <div id="tasks-list" style="padding:12px 20px;">
        <p style="color:var(--muted);font-size:0.84rem;">Loading tasks…</p>
      </div>
    </div>

    <!-- Daily Activity Log -->
    <div class="card">
      <div class="card__header">
        <span class="section-title">
          <span class="material-symbols-outlined" style="color:var(--info);font-size:1.2rem;">history</span>
          Activity Log
        </span>
        <button class="btn-sm btn-sm--primary" id="log-activity-btn">
          <span class="material-symbols-outlined" style="font-size:0.9rem;">add</span> Log Activity
        </button>
      </div>
      <div id="activity-log" style="padding:12px 20px;max-height:280px;overflow-y:auto;">
        <p style="color:var(--muted);font-size:0.84rem;">No activities logged today.</p>
      </div>
    </div>
  </div>

  <!-- ── SECTION 4: Orders + Livestock ─────────────────── -->
  <div class="two-col">

    <!-- Order Management -->
    <div class="card">
      <div class="card__header">
        <span class="section-title">
          <span class="material-symbols-outlined" style="color:var(--danger);font-size:1.2rem;">receipt_long</span>
          Today's Orders
        </span>
        <a href="orders.php" class="btn-sm btn-sm--ghost">View All</a>
      </div>
      <div style="padding:0 4px;">
        <div id="orders-list" style="padding:8px 16px;">
          <p style="color:var(--muted);font-size:0.84rem;padding:8px 0;">Loading orders…</p>
        </div>
      </div>
    </div>

    <!-- Livestock Monitoring -->
    <div class="card">
      <div class="card__header">
        <span class="section-title">
          <span class="material-symbols-outlined" style="color:var(--olive);font-size:1.2rem;">pets</span>
          Livestock Status
        </span>
        <span id="cow-count" style="font-size:0.78rem;color:var(--muted);"></span>
      </div>
      <div id="livestock-list" style="padding:12px 20px;max-height:280px;overflow-y:auto;">
        <p style="color:var(--muted);font-size:0.84rem;">Loading livestock…</p>
      </div>
    </div>
  </div>

  <!-- ── SECTION 5: Reminders + Notes ──────────────────── -->
  <div class="two-col">

    <!-- Reminders (view-only) -->
    <div class="card">
      <div class="card__header">
        <span class="section-title">
          <span class="material-symbols-outlined" style="color:var(--danger);font-size:1.2rem;">alarm</span>
          My Reminders
          <span id="reminderBadge" class="badge badge--red" style="display:none;font-size:0.65rem;margin-left:4px;">0</span>
        </span>
        <span style="font-size:0.72rem;color:var(--muted);font-style:italic;">View only</span>
      </div>
      <div id="remindersList" style="padding:12px 20px;max-height:280px;overflow-y:auto;"></div>
    </div>

    <!-- Notes & Communication -->
    <div class="card">
      <div class="card__header">
        <span class="section-title">
          <span class="material-symbols-outlined" style="color:var(--info);font-size:1.2rem;">edit_note</span>
          Notes &amp; Communication
        </span>
      </div>
      <div style="padding:14px 20px;">
        <textarea class="note-input" id="note-input" rows="3" placeholder="Write a note for admin or other staff (e.g. Cow #3 seems unwell, needs vet check)…"></textarea>
        <div style="display:flex;justify-content:flex-end;margin-top:8px;">
          <button class="btn-sm btn-sm--primary" id="save-note-btn">
            <span class="material-symbols-outlined" style="font-size:0.9rem;">send</span> Submit Note
          </button>
        </div>
        <div id="notes-feed" style="margin-top:16px;max-height:200px;overflow-y:auto;"></div>
      </div>
    </div>
  </div>

</main>

<script src="js/api.js"></script>
<script src="js/ui.js"></script>
<script src="js/nav.js"></script>
<script>
// ════════════════════════════════════════════════════════════
// Staff Dashboard — JavaScript
// ════════════════════════════════════════════════════════════

// ── Helpers ───────────────────────────────────────────────
function getStoredUser() { try { return JSON.parse(localStorage.getItem('user')||'{}'); } catch { return {}; } }

function today() {
  return new Date().toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
}

function nowTime() {
  return new Date().toLocaleTimeString([], { hour:'2-digit', minute:'2-digit', hour12:true });
}

// ── Greeting ──────────────────────────────────────────────
function renderGreeting() {
  const u = getStoredUser();
  const h = new Date().getHours();
  const tod = h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
  document.getElementById('page-greeting').innerHTML =
    `${tod}, ${u.name || 'there'}! <span class="material-symbols-outlined" style="vertical-align:middle;font-size:1.5rem;">waving_hand</span>`;
  document.getElementById('page-subtitle').textContent = 'Welcome Staff · Here\'s your farm work summary for today.';
  const dateEl = document.getElementById('current-date');
  if (dateEl) dateEl.textContent = today();
}

// ── SECTION 1: Load Orders ────────────────────────────────
async function loadOrders() {
  const container = document.getElementById('orders-list');
  try {
    const orders = await API.orders.getAll();
    document.getElementById('stat-orders').textContent = orders.length;

    if (!orders.length) {
      container.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;padding:8px 0;">No orders found.</p>';
      return;
    }

    // Show last 6 orders, newest first
    const recent = [...orders].reverse().slice(0, 6);

    // Simulate status based on order position (in real system this would be a DB field)
    const statuses = ['pending', 'processing', 'delivered'];
    const statusLabels = { pending:'Pending', processing:'Processing', delivered:'Delivered' };

    container.innerHTML = recent.map((o, i) => {
      const statusKey = statuses[i % 3];
      return `
        <div style="display:flex;align-items:center;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--border-light);">
          <div style="flex:1;min-width:0;">
            <div style="font-weight:700;font-size:0.84rem;color:var(--text);">#${o.Order_ID} — ${o.Customer_Name}</div>
            <div style="font-size:0.75rem;color:var(--muted);margin-top:2px;">${o.Order_Type} · ${o.Cow} · ${o.Order_Date}</div>
          </div>
          <span class="order-status order-status--${statusKey}">${statusLabels[statusKey]}</span>
        </div>`;
    }).join('');
  } catch(e) {
    container.innerHTML = '<p style="color:var(--danger);font-size:0.84rem;">Failed to load orders.</p>';
  }
}

// ── SECTION 2: Load Livestock ─────────────────────────────
async function loadLivestock() {
  const container = document.getElementById('livestock-list');
  const countEl   = document.getElementById('cow-count');
  try {
    const cows = await API.cows.getAll();
    if (countEl) countEl.textContent = `${cows.length} cows`;

    if (!cows.length) {
      container.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;">No livestock records.</p>';
      return;
    }

    // Simulate health status — in a real system this would come from DB
    const healthMap = {};
    cows.forEach((c, i) => { healthMap[c.Cow_ID] = i % 5 === 0 ? 'sick' : 'healthy'; });

    let sickCount = 0;
    container.innerHTML = cows.map(c => {
      const health = healthMap[c.Cow_ID];
      if (health === 'sick') sickCount++;
      const dotClass = health === 'sick' ? 'status-dot--sick' : 'status-dot--healthy';
      const healthLabel = health === 'sick'
        ? `<span style="color:var(--danger);font-weight:700;">Sick</span>`
        : `<span style="color:var(--olive);font-weight:700;">Healthy</span>`;
      return `
        <div class="cow-row">
          <div>
            <span class="status-dot ${dotClass}"></span>
            <strong style="font-size:0.84rem;">${c.Cow}</strong>
            <span style="font-size:0.75rem;color:var(--muted);margin-left:6px;">ID #${c.Cow_ID}</span>
          </div>
          <div style="text-align:right;">
            ${healthLabel}
            <div style="font-size:0.72rem;color:var(--muted);">${c.Production} production</div>
          </div>
        </div>`;
    }).join('');

    // Add sick cow alerts
    if (sickCount > 0) addAlert(`${sickCount} cow(s) marked as sick — vet check required.`, 'danger');
  } catch(e) {
    container.innerHTML = '<p style="color:var(--danger);font-size:0.84rem;">Failed to load livestock.</p>';
  }
}

// ── SECTION 3: Alerts ─────────────────────────────────────
const alertItems = [];

function addAlert(msg, type = 'warning') {
  alertItems.push({ msg, type });
  renderAlerts();
}

function renderAlerts() {
  const container = document.getElementById('alerts-list');
  const badge     = document.getElementById('alerts-badge');
  document.getElementById('stat-alerts').textContent = alertItems.length;

  if (!alertItems.length) {
    container.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;">No active alerts. All good! ✓</p>';
    if (badge) badge.style.display = 'none';
    return;
  }

  if (badge) { badge.textContent = alertItems.length; badge.style.display = 'inline-block'; }

  const icons = { danger:'warning', warning:'info', info:'info' };
  container.innerHTML = alertItems.map(a => `
    <div class="alert-item alert-item--${a.type}">
      <span class="material-symbols-outlined" style="font-size:1rem;flex-shrink:0;">${icons[a.type]||'info'}</span>
      <span>${a.msg}</span>
    </div>`).join('');
}

function loadAlerts() {
  // Static farm alerts — in a real system these would come from DB
  addAlert('Silo B feed level is low (38%) — report to admin.', 'danger');
  addAlert('Scheduled vet visit tomorrow at 9:00 AM.', 'info');
  addAlert('Milk collection truck arrives at 4:00 PM today.', 'info');
}

// ── SECTION 4: Tasks ──────────────────────────────────────
const TASKS_KEY = 'staff_tasks_' + (getStoredUser().id || 'default');

const defaultTasks = [
  { id:1, label:'Morning milking (6:00 AM)',       done:false },
  { id:2, label:'Feed cattle — Silage A',           done:false },
  { id:3, label:'Clean milking equipment',          done:false },
  { id:4, label:'Check water troughs',              done:false },
  { id:5, label:'Evening milking (4:00 PM)',        done:false },
  { id:6, label:'Record daily milk production',     done:false },
  { id:7, label:'Pasture rotation check',           done:false },
];

function loadTasks() {
  let tasks;
  try {
    const stored = localStorage.getItem(TASKS_KEY);
    // Reset tasks daily
    const storedDate = localStorage.getItem(TASKS_KEY + '_date');
    const todayStr   = new Date().toDateString();
    if (!stored || storedDate !== todayStr) {
      tasks = defaultTasks.map(t => ({ ...t }));
      localStorage.setItem(TASKS_KEY, JSON.stringify(tasks));
      localStorage.setItem(TASKS_KEY + '_date', todayStr);
    } else {
      tasks = JSON.parse(stored);
    }
  } catch { tasks = defaultTasks.map(t => ({ ...t })); }
  renderTasks(tasks);
}

function renderTasks(tasks) {
  const container = document.getElementById('tasks-list');
  const progress  = document.getElementById('tasks-progress');
  const done      = tasks.filter(t => t.done).length;
  if (progress) progress.textContent = `${done}/${tasks.length} Done`;

  document.getElementById('stat-tasks').textContent = tasks.filter(t => !t.done).length;

  container.innerHTML = tasks.map(t => `
    <div class="task-row ${t.done ? 'done' : ''}" id="task-row-${t.id}">
      <input type="checkbox" ${t.done ? 'checked' : ''} onchange="toggleTask(${t.id}, this.checked)" />
      <span>${t.label}</span>
      ${t.done ? `<span style="margin-left:auto;font-size:0.7rem;color:var(--olive);">✓ Done</span>` : ''}
    </div>`).join('');
}

function toggleTask(id, checked) {
  try {
    const tasks = JSON.parse(localStorage.getItem(TASKS_KEY) || '[]');
    const task  = tasks.find(t => t.id === id);
    if (task) {
      task.done = checked;
      localStorage.setItem(TASKS_KEY, JSON.stringify(tasks));
      renderTasks(tasks);
      if (checked) {
        logActivity(`Completed task: "${task.label}"`);
        UI.toast('Task marked as done!', 'success');
      }
    }
  } catch(e) { console.error(e); }
}

// ── SECTION 5: Activity Log ───────────────────────────────
const LOG_KEY = 'staff_activity_log_' + (getStoredUser().id || 'default');

function logActivity(text) {
  try {
    const logs = JSON.parse(localStorage.getItem(LOG_KEY) || '[]');
    logs.unshift({ text, time: nowTime(), date: new Date().toDateString() });
    // Keep last 20 entries
    if (logs.length > 20) logs.pop();
    localStorage.setItem(LOG_KEY, JSON.stringify(logs));
    renderActivityLog();
  } catch(e) {}
}

function renderActivityLog() {
  const container = document.getElementById('activity-log');
  try {
    const allLogs = JSON.parse(localStorage.getItem(LOG_KEY) || '[]');
    const todayStr = new Date().toDateString();
    const logs = allLogs.filter(l => l.date === todayStr);

    if (!logs.length) {
      container.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;">No activities logged today.</p>';
      return;
    }
    container.innerHTML = logs.map(l => `
      <div class="log-entry">
        <span class="log-time">${l.time}</span>
        <span style="color:var(--text);">${l.text}</span>
      </div>`).join('');
  } catch(e) {
    container.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;">No activities logged today.</p>';
  }
}

// Log Activity button — quick log modal
document.getElementById('log-activity-btn').addEventListener('click', () => {
  const activities = [
    'Morning milking completed',
    'Cattle fed — Silage A',
    'Water troughs checked and refilled',
    'Milking equipment cleaned and sanitized',
    'Evening milking completed',
    'Pasture rotation completed',
    'Sick cow reported to admin',
    'Milk production recorded',
  ];

  // Simple inline quick-log
  const existing = document.getElementById('quick-log-panel');
  if (existing) { existing.remove(); return; }

  const panel = document.createElement('div');
  panel.id = 'quick-log-panel';
  panel.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(42,31,21,0.45);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:16px;';
  panel.innerHTML = `
    <div style="background:#faf6f0;border-radius:20px;box-shadow:0 16px 56px rgba(0,0,0,0.2);width:100%;max-width:420px;font-family:'Lato',sans-serif;overflow:hidden;">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;background:linear-gradient(135deg,#4e6040,#6b8a5c);">
        <span style="font-family:'Playfair Display',serif;font-size:1.05rem;font-weight:700;color:#fff;">Log Activity</span>
        <button onclick="document.getElementById('quick-log-panel').remove()" style="background:rgba(255,255,255,0.15);border:none;cursor:pointer;width:30px;height:30px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;">
          <span class="material-symbols-outlined" style="font-size:1.1rem;">close</span>
        </button>
      </div>
      <div style="padding:20px 22px;">
        <p style="font-size:0.8rem;color:var(--muted);margin-bottom:10px;">Quick select or type a custom activity:</p>
        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px;">
          ${activities.map(a => `<button onclick="quickLog('${a}')" style="padding:5px 12px;border:1.5px solid var(--border);border-radius:20px;background:rgba(255,255,255,0.6);font-size:0.78rem;cursor:pointer;font-family:'Lato',sans-serif;color:var(--text);transition:background 0.15s;" onmouseover="this.style.background='rgba(78,96,64,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.6)'">${a}</button>`).join('')}
        </div>
        <textarea id="custom-log-input" placeholder="Or type a custom activity…" rows="2" style="width:100%;padding:10px 14px;border:1.5px solid var(--border-light);border-radius:10px;font-size:0.88rem;font-family:'Lato',sans-serif;color:var(--text);background:#fff;outline:none;resize:none;box-sizing:border-box;"></textarea>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:10px;">
          <button onclick="document.getElementById('quick-log-panel').remove()" style="padding:8px 16px;border:1.5px solid var(--border);border-radius:8px;background:#fff;color:var(--text);font-family:'Lato',sans-serif;font-size:0.84rem;font-weight:600;cursor:pointer;">Cancel</button>
          <button onclick="submitCustomLog()" style="padding:8px 18px;border:none;border-radius:8px;background:linear-gradient(135deg,#4e6040,#6b8a5c);color:#fff;font-family:'Lato',sans-serif;font-size:0.84rem;font-weight:700;cursor:pointer;">Log It</button>
        </div>
      </div>
    </div>`;
  document.body.appendChild(panel);
  panel.addEventListener('click', e => { if (e.target === panel) panel.remove(); });
});

function quickLog(text) {
  logActivity(text);
  const panel = document.getElementById('quick-log-panel');
  if (panel) panel.remove();
  UI.toast('Activity logged!', 'success');
}

function submitCustomLog() {
  const input = document.getElementById('custom-log-input');
  const text  = input ? input.value.trim() : '';
  if (!text) { UI.toast('Please enter an activity.', 'error'); return; }
  quickLog(text);
}

// ── SECTION 6: Reminders (view-only) ─────────────────────
let reminders = [];

function getStatusInfo(dueDate, status) {
  if (status === 'completed') return { color:'var(--olive)', bg:'var(--olive-light)', label:'Completed', urgent:false };
  const now = new Date(), due = new Date(dueDate), h = (due - now) / (1000 * 60 * 60);
  if (h < 0)   return { color:'var(--danger)', bg:'var(--danger-lt)', label:'Overdue', urgent:true };
  if (h <= 24) return { color:'#f39c12', bg:'#fef9e7', label:'Due Soon', urgent:true };
  return { color:'var(--olive)', bg:'var(--olive-light)', label:'Pending', urgent:false };
}

function formatDueDate(dateStr) {
  const date = new Date(dateStr), now = new Date(), tomorrow = new Date(now);
  tomorrow.setDate(tomorrow.getDate() + 1);
  const timeStr = date.toLocaleTimeString([], { hour:'numeric', minute:'2-digit', hour12:true });
  if (date.toDateString() === now.toDateString())      return `Today, ${timeStr}`;
  if (date.toDateString() === tomorrow.toDateString()) return `Tomorrow, ${timeStr}`;
  const m = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  return `${m[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()} — ${timeStr}`;
}

async function loadReminders() {
  const list = document.getElementById('remindersList');
  if (!list) return;
  list.innerHTML = '<p style="color:var(--text-light);font-size:0.84rem;"><span class="spinner"></span> Loading…</p>';
  try {
    const res  = await fetch('../dairy_farm_backend/api/reminders.php', { credentials:'include' });
    const data = await res.json();
    if (data.success) {
      reminders = data.data || [];
      renderReminders();
      updateReminderBadge();
      // Add overdue reminders to alerts
      const overdue = reminders.filter(r => r.status === 'pending' && getStatusInfo(r.due_date, r.status).label === 'Overdue');
      if (overdue.length) addAlert(`${overdue.length} overdue reminder(s) need attention.`, 'danger');
    } else {
      list.innerHTML = '<p style="color:var(--danger);font-size:0.84rem;">Failed to load reminders.</p>';
    }
  } catch(e) {
    list.innerHTML = '<p style="color:var(--danger);font-size:0.84rem;">Error loading reminders.</p>';
  }
}

function renderReminders() {
  const list = document.getElementById('remindersList');
  if (!list) return;
  if (!reminders.length) {
    list.innerHTML = '<p style="color:var(--text-light);font-size:0.84rem;">No reminders assigned.</p>';
    return;
  }
  const sorted = [...reminders].sort((a, b) => new Date(a.due_date) - new Date(b.due_date));
  list.innerHTML = sorted.map(r => {
    const s = getStatusInfo(r.due_date, r.status), done = r.status === 'completed';
    return `
      <div style="background:${s.bg};border-radius:8px;padding:10px 12px;margin-bottom:8px;border-left:3px solid ${s.color};">
        <span style="font-size:0.68rem;color:${s.color};font-weight:700;text-transform:uppercase;">${s.label}</span>
        <p style="font-size:0.86rem;color:var(--text);margin:3px 0;${done?'text-decoration:line-through;opacity:0.6;':''}">${r.title}</p>
        <span style="font-size:0.7rem;color:var(--text-light);">Due: ${formatDueDate(r.due_date)}</span>
      </div>`;
  }).join('');
}

function updateReminderBadge() {
  const badge = document.getElementById('reminderBadge');
  if (!badge) return;
  const n = reminders.filter(r => r.status !== 'completed' && getStatusInfo(r.due_date, r.status).urgent).length;
  badge.textContent = n; badge.style.display = n > 0 ? 'inline-block' : 'none';
}

// ── SECTION 7: Notes ──────────────────────────────────────
const NOTES_KEY = 'staff_notes_' + (getStoredUser().id || 'default');

function loadNotes() {
  renderNotes();
}

function renderNotes() {
  const feed = document.getElementById('notes-feed');
  try {
    const notes = JSON.parse(localStorage.getItem(NOTES_KEY) || '[]');
    if (!notes.length) {
      feed.innerHTML = '<p style="color:var(--muted);font-size:0.82rem;">No notes yet.</p>';
      return;
    }
    feed.innerHTML = notes.map(n => `
      <div class="note-bubble">
        <div>${n.text}</div>
        <div class="note-bubble__meta">${n.author} · ${n.time}</div>
      </div>`).join('');
  } catch(e) {
    feed.innerHTML = '';
  }
}

document.getElementById('save-note-btn').addEventListener('click', () => {
  const input = document.getElementById('note-input');
  const text  = input.value.trim();
  if (!text) { UI.toast('Please write a note first.', 'error'); return; }

  try {
    const notes  = JSON.parse(localStorage.getItem(NOTES_KEY) || '[]');
    const u      = getStoredUser();
    notes.unshift({ text, author: u.name || 'Staff', time: `${today()} ${nowTime()}` });
    if (notes.length > 10) notes.pop();
    localStorage.setItem(NOTES_KEY, JSON.stringify(notes));
    input.value = '';
    renderNotes();
    logActivity('Submitted a note to admin.');
    UI.toast('Note submitted!', 'success');
  } catch(e) { UI.toast('Failed to save note.', 'error'); }
});

// ── Milk production stat ──────────────────────────────────
async function loadMilkStat() {
  try {
    const cows = await API.cows.getAll();
    // Sum up production values (e.g. "15L" → 15)
    const total = cows.reduce((sum, c) => {
      const match = String(c.Production).match(/(\d+(\.\d+)?)/);
      return sum + (match ? parseFloat(match[1]) : 0);
    }, 0);
    document.getElementById('stat-milk').textContent = total > 0 ? total + 'L' : '—';

    // Update milk inventory bar
    const maxMilk = 500; // assume 500L capacity
    const pct = Math.min(Math.round((total / maxMilk) * 100), 100);
    const bar = document.getElementById('inv-milk-bar');
    const pctEl = document.getElementById('inv-milk-pct');
    if (bar) { bar.style.width = pct + '%'; bar.className = 'inv-bar-fill ' + (pct < 30 ? 'inv-bar-fill--low' : pct < 60 ? 'inv-bar-fill--mid' : 'inv-bar-fill--ok'); }
    if (pctEl) pctEl.textContent = total + 'L';
    if (pct < 30) addAlert('Milk stock is low — check production records.', 'warning');
  } catch(e) { /* non-critical */ }
}

// ── Init ──────────────────────────────────────────────────
(async () => {
  renderGreeting();

  const params = new URLSearchParams(window.location.search);
  if (params.get('access_denied') === '1') {
    UI.toast('Access denied. Admins only.', 'error');
    history.replaceState({}, '', 'dashboard_staff.php');
  }

  try {
    const res  = await fetch('../dairy_farm_backend/api/auth.php?action=status', { credentials:'include' });
    const data = await res.json();
    if (!data.success) { window.location.href = 'login.php'; return; }
    if (data.data) {
      localStorage.setItem('csrf_token', data.data.csrf_token || '');
      if (data.data.user) localStorage.setItem('user', JSON.stringify(data.data.user));
    }
  } catch { window.location.href = 'login.php'; return; }

  renderGreeting();

  // Load all sections in parallel
  loadAlerts();
  loadTasks();
  renderActivityLog();
  loadNotes();

  await Promise.allSettled([
    loadOrders(),
    loadLivestock(),
    loadReminders(),
    loadMilkStat(),
  ]);
})();
</script>
</body>
</html>
