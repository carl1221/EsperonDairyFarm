<?php
require_once __DIR__ . '/guard.php';
requireAuthPage();
// Admins are redirected to their own dashboard
if (($_SESSION['user']['role'] ?? '') === 'Admin') {
    header('Location: dashboard_admin.php');
    exit;
}
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
<script src="js/dashboard_staff.js"></script>
</body>
</html>
