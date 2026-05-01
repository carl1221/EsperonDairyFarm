<?php
require_once __DIR__ . '/guard.php';
requireAuthPage();
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
    .section-title { font-family:var(--font-serif); font-size:1rem; font-weight:700; color:var(--text); display:flex; align-items:center; gap:8px; }
    .alert-item { display:flex; align-items:flex-start; gap:10px; padding:9px 14px; border-radius:10px; margin-bottom:7px; font-size:0.83rem; }
    .alert-item--danger  { background:var(--danger-lt);  border-left:3px solid var(--danger);  color:#7a1f2e; }
    .alert-item--warning { background:var(--warning-lt); border-left:3px solid var(--warning); color:#7a5a1e; }
    .alert-item--info    { background:var(--info-lt);    border-left:3px solid var(--info);    color:#2d4f5e; }
    .task-row { display:flex; align-items:center; gap:10px; padding:9px 0; border-bottom:1px solid var(--border-light); font-size:0.85rem; }
    .task-row:last-child { border-bottom:none; }
    .task-row input[type="checkbox"] { width:16px; height:16px; accent-color:var(--olive); cursor:pointer; flex-shrink:0; }
    .task-row.done span { color:var(--muted); text-decoration:line-through; }
    .cow-row { display:flex; align-items:center; justify-content:space-between; padding:9px 0; border-bottom:1px solid var(--border-light); font-size:0.84rem; }
    .cow-row:last-child { border-bottom:none; }
    .status-dot { width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:5px; }
    .status-dot--healthy { background:var(--olive); }
    .status-dot--sick    { background:var(--danger); }
    .log-entry { display:flex; gap:10px; padding:8px 0; border-bottom:1px solid var(--border-light); font-size:0.83rem; }
    .log-entry:last-child { border-bottom:none; }
    .log-time { font-size:0.72rem; color:var(--muted); white-space:nowrap; min-width:52px; padding-top:2px; }
    .note-bubble { background:rgba(255,255,255,0.5); border:1px solid var(--border-light); border-radius:10px; padding:10px 14px; margin-bottom:8px; font-size:0.84rem; }
    .note-bubble__meta { font-size:0.7rem; color:var(--muted); margin-top:4px; }
    .order-status { display:inline-block; padding:2px 10px; border-radius:20px; font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
    .order-status--pending    { background:var(--warning-lt); color:#7a5a1e; }
    .order-status--processing { background:var(--info-lt);    color:#2d4f5e; }
    .order-status--delivered  { background:var(--success-lt); color:var(--olive-dark); }
    .inv-bar-wrap { margin-bottom:13px; }
    .inv-bar-wrap:last-child { margin-bottom:0; }
    .inv-bar-label { display:flex; justify-content:space-between; font-size:0.82rem; margin-bottom:4px; }
    .inv-bar { height:8px; background:var(--beige); border-radius:4px; overflow:hidden; }
    .inv-bar-fill { height:100%; border-radius:4px; transition:width .6s ease; }
    .inv-bar-fill--ok  { background:linear-gradient(90deg,var(--olive),var(--olive-light)); }
    .inv-bar-fill--mid { background:linear-gradient(90deg,var(--gold),var(--gold-light)); }
    .inv-bar-fill--low { background:linear-gradient(90deg,var(--danger),#e74c3c); }
    textarea.note-input { width:100%; padding:10px 14px; border:1.5px solid var(--border-light); border-radius:10px; font-size:0.87rem; font-family:var(--font-sans); color:var(--text); background:rgba(255,255,255,.7); resize:vertical; outline:none; transition:border-color .15s,box-shadow .15s; box-sizing:border-box; }
    textarea.note-input:focus { border-color:var(--olive); box-shadow:0 0 0 3px rgba(78,96,64,.12); }
    .btn-sm { padding:7px 16px; border:none; border-radius:8px; font-size:0.82rem; font-weight:600; cursor:pointer; font-family:var(--font-sans); display:inline-flex; align-items:center; gap:5px; transition:opacity .15s; }
    .btn-sm--primary { background:linear-gradient(135deg,var(--olive),var(--olive-light)); color:#fff; }
    .btn-sm--primary:hover { opacity:.88; }
    .btn-sm--ghost { background:rgba(255,255,255,.5); border:1.5px solid var(--border); color:var(--text); }
    .btn-sm--ghost:hover { background:rgba(255,255,255,.8); }
    /* ── Tabs ── */
    .dash-tab { display:inline-flex; align-items:center; gap:5px; padding:11px 16px; background:none; border:none; border-bottom:2.5px solid transparent; cursor:pointer; font-size:0.82rem; font-weight:600; color:var(--muted); font-family:var(--font-sans); white-space:nowrap; transition:color .15s,border-color .15s; }
    .dash-tab:hover { color:var(--text); }
    .dash-tab--active { color:var(--olive-dark); border-bottom-color:var(--olive); }
    .dash-tab-panel { animation:tabFadeIn .18s ease; }
    @keyframes tabFadeIn { from{opacity:0;transform:translateY(4px)} to{opacity:1;transform:none} }
    /* ── Quick action cards ── */
    .quick-card { background:rgba(255,255,255,0.35); border:1px solid rgba(255,255,255,0.5); border-radius:var(--radius-xl); padding:18px 20px; display:flex; align-items:center; gap:14px; cursor:pointer; transition:all .2s; }
    .quick-card:hover { background:rgba(255,255,255,0.55); transform:translateY(-2px); box-shadow:var(--shadow-md); }
    .quick-card__icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .quick-card__title { font-weight:700; font-size:0.88rem; color:var(--text); }
    .quick-card__sub   { font-size:0.74rem; color:var(--muted); margin-top:2px; }
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
      <span id="online-indicator" style="display:inline-flex;align-items:center;gap:5px;font-size:0.78rem;color:#27ae60;font-weight:600;">
        <span style="width:8px;height:8px;border-radius:50%;background:#27ae60;display:inline-block;animation:pulse 2s infinite;"></span> Online
      </span>
    </div>
  </div>

  <!-- ── SECTION 1: Stat Cards ──────────────────────────── -->
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
        <div class="stat-card__label">Total Orders</div>
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

  <!-- ── SECTION 2: Alerts + Inventory ─────────────────── -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--spacing-lg);margin-bottom:var(--spacing-xl);">

    <!-- Alerts -->
    <div class="card">
      <div class="card__header">
        <span class="section-title">
          <span class="material-symbols-outlined" style="color:var(--danger);font-size:1.2rem;">campaign</span>
          Alerts &amp; Notifications
        </span>
        <span id="alerts-badge" class="badge badge--red" style="display:none;font-size:0.68rem;">0</span>
      </div>
      <div id="alerts-list" style="padding:14px 20px;">
        <p style="color:var(--muted);font-size:0.84rem;">Checking alerts…</p>
      </div>
    </div>

    <!-- Inventory (read-only from localStorage) -->
    <div class="card">
      <div class="card__header">
        <span class="section-title">
          <span class="material-symbols-outlined" style="color:var(--olive);font-size:1.2rem;">inventory_2</span>
          Inventory Levels
        </span>
        <button class="btn-sm btn-sm--primary" onclick="openStaffRestockModal()" style="font-size:0.78rem;padding:5px 12px;">
          <span class="material-symbols-outlined" style="font-size:0.9rem;">add</span> Restock
        </button>
      </div>
      <div style="padding:16px 20px;" id="staff-inventory-bars">
        <p style="color:var(--muted);font-size:0.84rem;">Loading…</p>
      </div>
    </div>
  </div>

  <!-- ── SECTION 3: Quick Actions ───────────────────────── -->
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:var(--spacing-md);margin-bottom:var(--spacing-xl);">
    <div class="quick-card" onclick="switchStaffTab('tab-tasks',document.getElementById('sbtn-tasks'))">
      <div class="quick-card__icon" style="background:rgba(78,96,64,0.1);">
        <span class="material-symbols-outlined" style="color:var(--olive);font-size:1.4rem;">checklist</span>
      </div>
      <div>
        <div class="quick-card__title">My Tasks</div>
        <div class="quick-card__sub" id="qa-tasks-sub">Loading…</div>
      </div>
    </div>
    <div class="quick-card" onclick="switchStaffTab('tab-orders',document.getElementById('sbtn-orders'))">
      <div class="quick-card__icon" style="background:rgba(192,57,43,0.08);">
        <span class="material-symbols-outlined" style="color:var(--danger);font-size:1.4rem;">receipt_long</span>
      </div>
      <div>
        <div class="quick-card__title">Orders</div>
        <div class="quick-card__sub" id="qa-orders-sub">View today's orders</div>
      </div>
    </div>
    <div class="quick-card" onclick="switchStaffTab('tab-livestock',document.getElementById('sbtn-livestock'))">
      <div class="quick-card__icon" style="background:rgba(78,96,64,0.1);">
        <span class="material-symbols-outlined" style="color:var(--olive);font-size:1.4rem;">pets</span>
      </div>
      <div>
        <div class="quick-card__title">Livestock</div>
        <div class="quick-card__sub" id="qa-livestock-sub">Check herd status</div>
      </div>
    </div>
    <div class="quick-card" onclick="switchStaffTab('tab-reminders',document.getElementById('sbtn-reminders'))">
      <div class="quick-card__icon" style="background:rgba(192,57,43,0.08);">
        <span class="material-symbols-outlined" style="color:var(--danger);font-size:1.4rem;">alarm</span>
      </div>
      <div>
        <div class="quick-card__title">Reminders</div>
        <div class="quick-card__sub" id="qa-reminders-sub">View assigned tasks</div>
      </div>
    </div>
    <div class="quick-card" onclick="document.getElementById('log-activity-btn').click()">
      <div class="quick-card__icon" style="background:rgba(91,122,138,0.1);">
        <span class="material-symbols-outlined" style="color:var(--info);font-size:1.4rem;">add_circle</span>
      </div>
      <div>
        <div class="quick-card__title">Log Activity</div>
        <div class="quick-card__sub">Record what you did</div>
      </div>
    </div>
    <div class="quick-card" onclick="switchStaffTab('tab-notes',document.getElementById('sbtn-notes'))">
      <div class="quick-card__icon" style="background:rgba(91,122,138,0.1);">
        <span class="material-symbols-outlined" style="color:var(--info);font-size:1.4rem;">edit_note</span>
      </div>
      <div>
        <div class="quick-card__title">Notes</div>
        <div class="quick-card__sub">Message admin or staff</div>
      </div>
    </div>
    <div class="quick-card" onclick="window.location.href='inventory.php'">
      <div class="quick-card__icon" style="background:rgba(78,96,64,0.1);">
        <span class="material-symbols-outlined" style="color:var(--olive);font-size:1.4rem;">inventory_2</span>
      </div>
      <div>
        <div class="quick-card__title">Inventory</div>
        <div class="quick-card__sub" id="qa-inventory-sub">Update stock levels</div>
      </div>
    </div>
  </div>

  <!-- ── SECTION 4: Tabbed Panel ────────────────────────── -->
  <div class="card" style="margin-bottom:var(--spacing-xl);">

    <!-- Tab bar -->
    <div style="display:flex;gap:0;border-bottom:1px solid var(--border-light);padding:0 8px;overflow-x:auto;flex-wrap:nowrap;">
      <button class="dash-tab dash-tab--active" onclick="switchStaffTab('tab-orders',this)" id="sbtn-orders">
        <span class="material-symbols-outlined" style="font-size:1rem;">receipt_long</span> Orders
      </button>
      <button class="dash-tab" onclick="switchStaffTab('tab-tasks',this)" id="sbtn-tasks">
        <span class="material-symbols-outlined" style="font-size:1rem;">checklist</span> Tasks
        <span id="tasks-progress" class="badge badge--green" style="font-size:0.6rem;margin-left:3px;">—</span>
      </button>
      <button class="dash-tab" onclick="switchStaffTab('tab-livestock',this)" id="sbtn-livestock">
        <span class="material-symbols-outlined" style="font-size:1rem;">pets</span> Livestock
      </button>
      <button class="dash-tab" onclick="switchStaffTab('tab-reminders',this)" id="sbtn-reminders">
        <span class="material-symbols-outlined" style="font-size:1rem;">alarm</span> Reminders
        <span id="reminderBadge" class="badge badge--red" style="display:none;font-size:0.6rem;margin-left:3px;">0</span>
      </button>
      <button class="dash-tab" onclick="switchStaffTab('tab-activity',this)" id="sbtn-activity">
        <span class="material-symbols-outlined" style="font-size:1rem;">history</span> Activity
      </button>
      <button class="dash-tab" onclick="switchStaffTab('tab-notes',this)" id="sbtn-notes">
        <span class="material-symbols-outlined" style="font-size:1rem;">edit_note</span> Notes
      </button>
    </div>

    <!-- Tab: Orders -->
    <div id="tab-orders" class="dash-tab-panel">
      <div style="padding:12px 16px 4px;display:flex;justify-content:flex-end;">
        <a href="orders.php" class="btn-sm btn-sm--ghost">View All →</a>
      </div>
      <div id="orders-list" style="padding:4px 16px 16px;max-height:340px;overflow-y:auto;">
        <p style="color:var(--muted);font-size:0.84rem;padding:8px 0;">Loading orders…</p>
      </div>
    </div>

    <!-- Tab: Tasks -->
    <div id="tab-tasks" class="dash-tab-panel" style="display:none;">
      <div id="tasks-list" style="padding:16px 20px;">
        <p style="color:var(--muted);font-size:0.84rem;">Loading tasks…</p>
      </div>
    </div>

    <!-- Tab: Livestock -->
    <div id="tab-livestock" class="dash-tab-panel" style="display:none;">
      <div style="padding:12px 16px 4px;display:flex;justify-content:space-between;align-items:center;">
        <span id="cow-count" style="font-size:0.78rem;color:var(--muted);"></span>
      </div>
      <div id="livestock-list" style="padding:4px 20px 16px;max-height:340px;overflow-y:auto;">
        <p style="color:var(--muted);font-size:0.84rem;">Loading livestock…</p>
      </div>
    </div>

    <!-- Tab: Reminders -->
    <div id="tab-reminders" class="dash-tab-panel" style="display:none;">
      <div style="padding:12px 16px 4px;display:flex;justify-content:space-between;align-items:center;">
        <span style="font-size:0.75rem;color:var(--muted);font-style:italic;">Assigned by admin · Mark tasks done when completed</span>
      </div>
      <div id="remindersList" style="padding:4px 20px 16px;max-height:340px;overflow-y:auto;"></div>
    </div>

    <!-- Tab: Activity Log -->
    <div id="tab-activity" class="dash-tab-panel" style="display:none;">
      <div style="padding:12px 16px 4px;display:flex;justify-content:flex-end;">
        <button class="btn-sm btn-sm--primary" id="log-activity-btn">
          <span class="material-symbols-outlined" style="font-size:0.9rem;">add</span> Log Activity
        </button>
      </div>
      <div id="activity-log" style="padding:4px 20px 16px;max-height:340px;overflow-y:auto;">
        <p style="color:var(--muted);font-size:0.84rem;">No activities logged today.</p>
      </div>
    </div>

    <!-- Tab: Notes -->
    <div id="tab-notes" class="dash-tab-panel" style="display:none;">
      <div style="padding:16px 20px;">
        <textarea class="note-input" id="note-input" rows="3" placeholder="Write a note for admin or other staff (e.g. Cow #3 seems unwell, needs vet check)…"></textarea>
        <div style="display:flex;justify-content:flex-end;margin-top:8px;">
          <button class="btn-sm btn-sm--primary" id="save-note-btn">
            <span class="material-symbols-outlined" style="font-size:0.9rem;">send</span> Submit Note
          </button>
        </div>
        <div id="notes-feed" style="margin-top:14px;max-height:220px;overflow-y:auto;"></div>
      </div>
    </div>

  </div>

</main>

<style>
@keyframes pulse { 0%,100%{box-shadow:0 0 0 0 rgba(39,174,96,.4)} 50%{box-shadow:0 0 0 5px rgba(39,174,96,0)} }
</style>

<script src="js/api.js"></script>
<script src="js/ui.js"></script>
<script src="js/nav.js"></script>
<script src="js/dashboard_staff.js"></script>
</body>
</html>
