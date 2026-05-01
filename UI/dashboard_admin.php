<?php
require_once __DIR__ . '/guard.php';
requireAuthPage();
requireAdminPage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard — Esperon Dairy Farm</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link rel="stylesheet" href="css/style.css" />
  <style>
    /* ── Admin Dashboard extras ── */
    .dash-section-title {
      font-family: var(--font-serif);
      font-size: 1rem; font-weight: 700; color: var(--text);
      display: flex; align-items: center; gap: 8px;
    }
    .two-col  { display: grid; grid-template-columns: 1fr 1fr;     gap: var(--spacing-lg); margin-bottom: var(--spacing-xl); }
    .three-col{ display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--spacing-lg); margin-bottom: var(--spacing-xl); }
    @media(max-width:960px){ .two-col,.three-col{ grid-template-columns:1fr; } }
    .inv-bar-wrap { margin-bottom: 13px; }
    .inv-bar-wrap:last-child { margin-bottom: 0; }
    .inv-bar-label { display:flex; justify-content:space-between; font-size:0.82rem; margin-bottom:4px; }
    .inv-bar-label span:last-child { font-weight:700; }
    .inv-bar { height:8px; background:var(--beige); border-radius:4px; overflow:hidden; }
    .inv-bar-fill { height:100%; border-radius:4px; transition:width .6s ease; }
    .inv-bar-fill--ok  { background:linear-gradient(90deg,var(--olive),var(--olive-light)); }
    .inv-bar-fill--mid { background:linear-gradient(90deg,var(--gold),var(--gold-light)); }
    .inv-bar-fill--low { background:linear-gradient(90deg,var(--danger),#e74c3c); }
    .alert-row { display:flex; align-items:flex-start; gap:10px; padding:9px 14px; border-radius:10px; margin-bottom:7px; font-size:0.83rem; }
    .alert-row--danger  { background:var(--danger-lt);  border-left:3px solid var(--danger);  color:#7a1f2e; }
    .alert-row--warning { background:var(--warning-lt); border-left:3px solid var(--warning); color:#7a5a1e; }
    .alert-row--info    { background:var(--info-lt);    border-left:3px solid var(--info);    color:#2d4f5e; }
    .alert-row--success { background:var(--success-lt); border-left:3px solid var(--olive);   color:var(--olive-dark); }
    .cow-row { display:flex; align-items:center; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--border-light); font-size:0.83rem; }
    .cow-row:last-child { border-bottom:none; }
    .status-dot { width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:5px; flex-shrink:0; }
    .status-dot--healthy { background:var(--olive); }
    .status-dot--sick    { background:var(--danger); }
    .worker-row { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid var(--border-light); font-size:0.83rem; }
    .worker-row:last-child { border-bottom:none; }
    .worker-avatar { width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg,var(--olive),var(--olive-light)); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem; color:#fff; flex-shrink:0; }
    .order-status { display:inline-block; padding:2px 9px; border-radius:20px; font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
    .order-status--pending    { background:var(--warning-lt); color:#7a5a1e; }
    .order-status--processing { background:var(--info-lt);    color:#2d4f5e; }
    .order-status--delivered  { background:var(--success-lt); color:var(--olive-dark); }
    .note-bubble { background:rgba(255,255,255,0.5); border:1px solid var(--border-light); border-radius:10px; padding:10px 14px; margin-bottom:8px; font-size:0.83rem; }
    .note-bubble__meta { font-size:0.7rem; color:var(--muted); margin-top:4px; }
    .report-stat { text-align:center; padding:16px 10px; }
    .report-stat__val { font-family:var(--font-serif); font-size:1.8rem; font-weight:700; color:var(--text); line-height:1; }
    .report-stat__label { font-size:0.72rem; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; margin-top:4px; }
    .report-divider { width:1px; background:var(--border-light); align-self:stretch; }
    .btn-xs { padding:5px 12px; border:none; border-radius:7px; font-size:0.78rem; font-weight:600; cursor:pointer; font-family:var(--font-sans); display:inline-flex; align-items:center; gap:4px; transition:opacity .15s; }
    .btn-xs--primary { background:linear-gradient(135deg,var(--olive),var(--olive-light)); color:#fff; }
    .btn-xs--danger  { background:var(--danger-lt); color:var(--danger); border:1px solid rgba(192,57,43,.2); }
    .btn-xs--ghost   { background:rgba(255,255,255,.5); border:1.5px solid var(--border); color:var(--text); }
    .btn-xs:hover { opacity:.85; }
    textarea.note-input { width:100%; padding:10px 14px; border:1.5px solid var(--border-light); border-radius:10px; font-size:0.87rem; font-family:var(--font-sans); color:var(--text); background:rgba(255,255,255,.7); resize:vertical; outline:none; transition:border-color .15s,box-shadow .15s; box-sizing:border-box; }
    textarea.note-input:focus { border-color:var(--olive); box-shadow:0 0 0 3px rgba(78,96,64,.12); }
    .task-row { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid var(--border-light); font-size:0.84rem; }
    .task-row:last-child { border-bottom:none; }
    .task-row.done span { color:var(--muted); text-decoration:line-through; }
    .task-row input[type=checkbox] { width:16px; height:16px; accent-color:var(--olive); cursor:pointer; flex-shrink:0; }

    /* ── Dashboard Tabs ── */
    .dash-tab {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 11px 16px; background: none; border: none;
      border-bottom: 2.5px solid transparent; cursor: pointer;
      font-size: 0.82rem; font-weight: 600; color: var(--muted);
      font-family: var(--font-sans); white-space: nowrap;
      transition: color .15s, border-color .15s;
    }
    .dash-tab:hover { color: var(--text); }
    .dash-tab--active { color: var(--olive-dark); border-bottom-color: var(--olive); }
    .dash-tab-panel { animation: tabFadeIn .18s ease; }
    @keyframes tabFadeIn { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:none; } }
  </style>
</head>
<body>
<nav class="nav" id="app-nav"></nav>
<main class="main">

  <!-- ══ PAGE HEADER ══════════════════════════════════════ -->
  <div class="page-header">
    <div>
      <h1 class="page-title" id="page-greeting">Welcome back!</h1>
      <p class="page-subtitle" id="page-subtitle">Full farm control panel.</p>
    </div>
    <div class="dashboard-header__actions">
      <div class="header__search">
        <span class="material-symbols-outlined" style="font-size:1.1rem;color:var(--muted);">search</span>
        <input type="text" id="global-search" placeholder="Search orders, cows, staff…" />
      </div>
      <button class="header__icon-btn" title="Notifications" id="notif-btn" style="position:relative;">
        <span class="material-symbols-outlined">notifications</span>
        <span id="notif-dot" style="display:none;position:absolute;top:6px;right:6px;width:8px;height:8px;border-radius:50%;background:var(--danger);"></span>
      </button>
    </div>
  </div>

  <!-- ══ SECTION 1: OVERVIEW STATS ════════════════════════ -->
  <div class="stats-grid" style="margin-bottom:var(--spacing-xl);">
    <div class="stat-card">
      <div class="stat-card__icon"><span class="material-symbols-outlined">water_drop</span></div>
      <div class="stat-card__content">
        <div class="stat-card__val" id="stat-milk">—</div>
        <div class="stat-card__label">Total Milk (L)</div>
      </div>
    </div>
    <div class="stat-card stat-card--danger">
      <div class="stat-card__icon"><span class="material-symbols-outlined">shopping_cart</span></div>
      <div class="stat-card__content">
        <div class="stat-card__val" id="stat-orders">0</div>
        <div class="stat-card__label">Total Orders</div>
      </div>
    </div>
    <div class="stat-card stat-card--gold">
      <div class="stat-card__icon"><span class="material-symbols-outlined">pets</span></div>
      <div class="stat-card__content">
        <div class="stat-card__val" id="stat-cows">0</div>
        <div class="stat-card__label">Total Cows</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-card__icon"><span class="material-symbols-outlined">badge</span></div>
      <div class="stat-card__content">
        <div class="stat-card__val" id="stat-workers">0</div>
        <div class="stat-card__label">Active Staff</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-card__icon"><span class="material-symbols-outlined">people</span></div>
      <div class="stat-card__content">
        <div class="stat-card__val" id="stat-customers">0</div>
        <div class="stat-card__label">Customers</div>
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

  <!-- ══ SECTION 2: ALERTS + INVENTORY ════════════════════ -->
  <div class="two-col">

    <!-- Alerts & Notifications -->
    <div class="card">
      <div class="card__header">
        <span class="dash-section-title">
          <span class="material-symbols-outlined" style="color:var(--danger);font-size:1.2rem;">campaign</span>
          Alerts &amp; Notifications
        </span>
        <div style="display:flex;align-items:center;gap:8px;">
          <span id="alerts-badge" class="badge badge--red" style="display:none;font-size:0.68rem;">0</span>
          <button class="btn-xs btn-xs--danger" onclick="openAddAlertModal()">
            <span class="material-symbols-outlined" style="font-size:0.9rem;">add_alert</span> Add Alert
          </button>
        </div>
      </div>
      <div id="alerts-list" style="padding:14px 20px;">
        <p style="color:var(--muted);font-size:0.84rem;">Checking alerts…</p>
      </div>
    </div>

    <!-- Inventory Management -->
    <div class="card">
      <div class="card__header">
        <span class="dash-section-title">
          <span class="material-symbols-outlined" style="color:var(--olive);font-size:1.2rem;">inventory_2</span>
          Inventory Levels
        </span>
        <div style="display:flex;gap:6px;align-items:center;">
          <button class="btn-xs btn-xs--ghost" onclick="openEditInventoryModal()">
            <span class="material-symbols-outlined" style="font-size:0.9rem;">edit</span> Edit
          </button>
          <button class="btn-xs btn-xs--primary" onclick="openRestockModal()">
            <span class="material-symbols-outlined" style="font-size:0.9rem;">add</span> Restock
          </button>
        </div>
      </div>
      <div style="padding:16px 20px;" id="inventory-bars">
        <!-- Rendered by JS -->
      </div>
      <div style="padding:0 20px 14px;display:flex;justify-content:space-between;align-items:center;">
        <span style="font-size:0.72rem;color:var(--muted);" id="inv-last-updated"></span>
        <button class="btn-xs btn-xs--ghost" onclick="resetInventory()" style="font-size:0.72rem;padding:3px 10px;">
          <span class="material-symbols-outlined" style="font-size:0.8rem;">restart_alt</span> Reset
        </button>
      </div>
    </div>
  </div>

  <!-- ══ SECTION 3: REPORTS / ANALYTICS ══════════════════ -->
  <div class="card" style="margin-bottom:var(--spacing-xl);">
    <div class="card__header">
      <span class="dash-section-title">
        <span class="material-symbols-outlined" style="color:var(--olive);font-size:1.2rem;">bar_chart</span>
        Reports &amp; Analytics
      </span>
      <div style="display:flex;gap:6px;">
        <button class="btn-xs btn-xs--ghost" onclick="setReportPeriod('daily',this)">Daily</button>
        <button class="btn-xs btn-xs--ghost" onclick="setReportPeriod('weekly',this)" id="report-active" style="background:rgba(78,96,64,0.12);border-color:var(--olive);color:var(--olive-dark);">Weekly</button>
        <button class="btn-xs btn-xs--ghost" onclick="setReportPeriod('monthly',this)">Monthly</button>
      </div>
    </div>
    <div style="display:flex;align-items:stretch;flex-wrap:wrap;">
      <div class="report-stat" style="flex:1;min-width:120px;">
        <div class="report-stat__val" id="rpt-milk">—</div>
        <div class="report-stat__label">Milk Produced (L)</div>
      </div>
      <div class="report-divider"></div>
      <div class="report-stat" style="flex:1;min-width:120px;">
        <div class="report-stat__val" id="rpt-orders">—</div>
        <div class="report-stat__label">Orders</div>
      </div>
      <div class="report-divider"></div>
      <div class="report-stat" style="flex:1;min-width:120px;">
        <div class="report-stat__val" id="rpt-customers">—</div>
        <div class="report-stat__label">Customers Served</div>
      </div>
      <div class="report-divider"></div>
      <div class="report-stat" style="flex:1;min-width:120px;">
        <div class="report-stat__val" id="rpt-cows">—</div>
        <div class="report-stat__label">Active Cows</div>
      </div>
      <div class="report-divider"></div>
      <div class="report-stat" style="flex:1;min-width:120px;">
        <div class="report-stat__val" id="rpt-staff">—</div>
        <div class="report-stat__label">Staff Active</div>
      </div>
    </div>
  </div>

  <!-- ══ SECTIONS 4–7: TABBED PANEL ══════════════════════ -->
  <div class="card" style="margin-bottom:var(--spacing-xl);">

    <!-- Tab bar -->
    <div style="display:flex;gap:0;border-bottom:1px solid var(--border-light);padding:0 8px;overflow-x:auto;flex-wrap:nowrap;">
      <button class="dash-tab dash-tab--active" onclick="switchTab('tab-orders',this)" id="btn-tab-orders">
        <span class="material-symbols-outlined" style="font-size:1rem;">receipt_long</span> Orders
      </button>
      <button class="dash-tab" onclick="switchTab('tab-staff',this)" id="btn-tab-staff">
        <span class="material-symbols-outlined" style="font-size:1rem;">manage_accounts</span> Staff
      </button>
      <button class="dash-tab" onclick="switchTab('tab-livestock',this)" id="btn-tab-livestock">
        <span class="material-symbols-outlined" style="font-size:1rem;">pets</span> Livestock
      </button>
      <button class="dash-tab" onclick="switchTab('tab-reminders',this)" id="btn-tab-reminders">
        <span class="material-symbols-outlined" style="font-size:1rem;">alarm</span> Reminders
        <span id="reminderBadge" class="badge badge--red" style="display:none;font-size:0.6rem;margin-left:3px;">0</span>
      </button>
      <button class="dash-tab" onclick="switchTab('tab-tasks',this)" id="btn-tab-tasks">
        <span class="material-symbols-outlined" style="font-size:1rem;">checklist</span> Tasks
        <span id="admin-tasks-progress" class="badge badge--green" style="font-size:0.6rem;margin-left:3px;">—</span>
      </button>
      <button class="dash-tab" onclick="switchTab('tab-notes',this)" id="btn-tab-notes">
        <span class="material-symbols-outlined" style="font-size:1rem;">edit_note</span> Notes
      </button>
      <button class="dash-tab" onclick="switchTab('tab-approvals',this)" id="btn-tab-approvals">
        <span class="material-symbols-outlined" style="font-size:1rem;">how_to_reg</span> Approvals
        <span id="approval-badge" class="badge badge--red" style="display:none;font-size:0.6rem;margin-left:3px;">0</span>
      </button>
      <button class="dash-tab" onclick="switchTab('tab-online',this)" id="btn-tab-online">
        <span class="material-symbols-outlined" style="font-size:1rem;">wifi</span> Online
        <span id="online-count-badge" class="badge badge--green" style="font-size:0.6rem;margin-left:3px;">0</span>
      </button>
    </div>

    <!-- ── Tab: Orders ── -->
    <div id="tab-orders" class="dash-tab-panel">
      <div style="padding:8px 16px 4px;display:flex;gap:6px;flex-wrap:wrap;align-items:center;justify-content:space-between;">
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
          <button class="btn-xs btn-xs--ghost" onclick="filterOrders('all',this)" style="background:rgba(78,96,64,0.12);border-color:var(--olive);color:var(--olive-dark);">All</button>
          <button class="btn-xs btn-xs--ghost" onclick="filterOrders('pending',this)">Pending</button>
          <button class="btn-xs btn-xs--ghost" onclick="filterOrders('processing',this)">Processing</button>
          <button class="btn-xs btn-xs--ghost" onclick="filterOrders('delivered',this)">Delivered</button>
        </div>
        <a href="orders.php" class="btn-xs btn-xs--ghost">Manage All →</a>
      </div>
      <div id="orders-list" style="padding:4px 16px 16px;max-height:340px;overflow-y:auto;">
        <p style="color:var(--muted);font-size:0.84rem;padding:8px 0;">Loading orders…</p>
      </div>
    </div>

    <!-- ── Tab: Staff ── -->
    <div id="tab-staff" class="dash-tab-panel" style="display:none;">
      <div style="padding:12px 16px 4px;display:flex;justify-content:flex-end;">
        <a href="workers.php" class="btn-xs btn-xs--primary">
          <span class="material-symbols-outlined" style="font-size:0.9rem;">add</span> Add Staff
        </a>
      </div>
      <div id="staff-list" style="padding:4px 20px 16px;max-height:340px;overflow-y:auto;">
        <p style="color:var(--muted);font-size:0.84rem;">Loading staff…</p>
      </div>
    </div>

    <!-- ── Tab: Livestock ── -->
    <div id="tab-livestock" class="dash-tab-panel" style="display:none;">
      <div style="padding:12px 16px 4px;display:flex;justify-content:space-between;align-items:center;">
        <span id="sick-badge" class="badge badge--red" style="display:none;font-size:0.68rem;">0 sick</span>
        <a href="cows.php" class="btn-xs btn-xs--ghost" style="margin-left:auto;">Manage All →</a>
      </div>
      <div id="livestock-list" style="padding:4px 20px 16px;max-height:340px;overflow-y:auto;">
        <p style="color:var(--muted);font-size:0.84rem;">Loading livestock…</p>
      </div>
    </div>

    <!-- ── Tab: Reminders ── -->
    <div id="tab-reminders" class="dash-tab-panel" style="display:none;">
      <div style="padding:12px 16px 4px;display:flex;justify-content:space-between;align-items:center;">
        <a href="reminders.php" class="btn-xs btn-xs--ghost">View All →</a>
        <button id="addReminderBtn" style="background:var(--danger);color:#fff;border:none;border-radius:6px;padding:5px 12px;cursor:pointer;font-size:0.75rem;font-weight:600;">+ Add Task</button>
      </div>
      <div id="remindersList" style="padding:4px 20px 16px;max-height:340px;overflow-y:auto;"></div>
    </div>

    <!-- ── Tab: Tasks ── -->
    <div id="tab-tasks" class="dash-tab-panel" style="display:none;">
      <div id="admin-tasks-list" style="padding:16px 20px;"></div>
    </div>

    <!-- ── Tab: Notes ── -->
    <div id="tab-notes" class="dash-tab-panel" style="display:none;">
      <div style="padding:16px 20px;">
        <textarea class="note-input" id="note-input" rows="3" placeholder="Post an announcement or note for staff…"></textarea>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">
          <a href="notes.php" class="btn-xs btn-xs--ghost">View All →</a>
          <button class="btn-xs btn-xs--primary" id="save-note-btn">
            <span class="material-symbols-outlined" style="font-size:0.9rem;">send</span> Post Note
          </button>
        </div>
        <div id="notes-feed" style="margin-top:14px;max-height:220px;overflow-y:auto;"></div>
      </div>
    </div>

    <!-- ── Tab: Approvals ── -->
    <div id="tab-approvals" class="dash-tab-panel" style="display:none;">
      <div style="padding:12px 16px 4px;display:flex;justify-content:space-between;align-items:center;">
        <a href="approvals.php" class="btn-xs btn-xs--ghost">View All →</a>
        <button class="btn-xs btn-xs--ghost" onclick="loadPendingApprovals()">
          <span class="material-symbols-outlined" style="font-size:0.9rem;">refresh</span> Refresh
        </button>
      </div>
      <div id="approvals-list" style="padding:4px 20px 16px;max-height:340px;overflow-y:auto;">
        <p style="color:var(--muted);font-size:0.84rem;">Loading…</p>
      </div>
    </div>

    <!-- ── Tab: Online Staff ── -->
    <div id="tab-online" class="dash-tab-panel" style="display:none;">
      <div style="padding:12px 16px 4px;display:flex;justify-content:space-between;align-items:center;">
        <a href="online_staff.php" class="btn-xs btn-xs--ghost">View All →</a>
        <span id="online-last-refresh" style="font-size:0.72rem;color:var(--muted);"></span>
      </div>
      <div id="online-staff-list" style="padding:4px 20px 16px;max-height:340px;overflow-y:auto;">
        <p style="color:var(--muted);font-size:0.84rem;">Loading…</p>
      </div>
    </div>

  </div>

  <!-- Reject Confirmation Modal -->
  <div id="rejectModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(42,31,21,0.45);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
    <div style="background:#faf6f0;border-radius:18px;box-shadow:0 12px 48px rgba(0,0,0,0.18);width:100%;max-width:400px;margin:16px;font-family:'Lato',sans-serif;overflow:hidden;">
      <div style="padding:18px 22px 14px;background:linear-gradient(135deg,#c0392b,#e74c3c);display:flex;align-items:center;gap:8px;">
        <span class="material-symbols-outlined" style="color:#fff;font-size:1.2rem;">person_remove</span>
        <span style="font-family:'Playfair Display',serif;font-size:1rem;font-weight:700;color:#fff;">Reject Registration</span>
      </div>
      <div style="padding:20px 22px;">
        <p style="font-size:0.9rem;color:var(--text);margin-bottom:6px;">Are you sure you want to <strong>reject</strong> this registration?</p>
        <p id="reject-worker-name" style="font-size:0.84rem;color:var(--muted);margin-bottom:18px;"></p>
        <div style="display:flex;justify-content:flex-end;gap:8px;">
          <button onclick="closeRejectModal()" style="padding:9px 18px;border:1.5px solid #d4c9b8;border-radius:9px;background:#fff;color:#4a3f35;font-family:'Lato',sans-serif;font-size:0.85rem;font-weight:600;cursor:pointer;">Cancel</button>
          <button id="confirm-reject-btn" style="padding:9px 20px;border:none;border-radius:9px;background:linear-gradient(135deg,#c0392b,#e74c3c);color:#fff;font-family:'Lato',sans-serif;font-size:0.85rem;font-weight:700;cursor:pointer;">Reject</button>
        </div>
      </div>
    </div>
  </div>

</main>

<script src="js/api.js"></script>
<script src="js/ui.js"></script>
<script src="js/nav.js"></script>
<script src="js/dashboard_admin.js"></script>
</body>
</html>
