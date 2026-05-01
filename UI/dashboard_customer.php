<?php
require_once __DIR__ . '/guard.php';
requireAuthPage();
requireCustomerPage();

$customerName = $_SESSION['user']['name']    ?? 'Customer';
$address      = $_SESSION['user']['address'] ?? '';
$contact      = $_SESSION['user']['contact'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Dashboard — Esperon Dairy Farm</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link rel="stylesheet" href="css/style.css" />
  <style>
    /* ── Order status pills ── */
    .order-pill {
      display: inline-flex; align-items: center; gap: 4px;
      padding: 3px 10px; border-radius: 20px;
      font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
    }
    .order-pill--pending   { background: var(--warning-lt); color: #7a5a1e; }
    .order-pill--confirmed { background: var(--info-lt);    color: #2d4f5e; }
    .order-pill--delivered { background: var(--success-lt); color: var(--olive-dark); }
    .order-pill--cancelled { background: var(--danger-lt);  color: var(--danger); }

    /* ── Profile card ── */
    .profile-card {
      background: rgba(255,255,255,0.35);
      border: 1px solid rgba(255,255,255,0.5);
      border-radius: var(--radius-xl);
      padding: 22px 24px;
      display: flex; align-items: center; gap: 20px;
    }
    .profile-avatar {
      width: 64px; height: 64px; border-radius: 50%; flex-shrink: 0;
      background: linear-gradient(135deg, #2980b9, #3498db);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.6rem; font-weight: 700; color: #fff;
      border: 3px solid rgba(255,255,255,0.7);
      box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    }
    .profile-info__name  { font-family: var(--font-serif); font-size: 1.15rem; font-weight: 700; color: var(--text); }
    .profile-info__badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; background: rgba(41,128,185,0.12); color: #2980b9; margin-top: 4px; }
    .profile-info__meta  { font-size: 0.8rem; color: var(--muted); margin-top: 6px; display: flex; flex-direction: column; gap: 3px; }
    .profile-info__meta span { display: flex; align-items: center; gap: 5px; }

    /* ── Order table ── */
    .order-row { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-light); gap: 12px; }
    .order-row:last-child { border-bottom: none; }
    .order-row__id   { font-weight: 700; font-size: 0.88rem; color: var(--text); }
    .order-row__meta { font-size: 0.75rem; color: var(--muted); margin-top: 2px; }
    .order-row__price { text-align: right; flex-shrink: 0; }
    .order-row__total { font-weight: 700; font-size: 0.9rem; color: var(--olive-dark); }
    .order-row__qty   { font-size: 0.72rem; color: var(--muted); margin-top: 2px; }

    /* ── Summary stats ── */
    .cust-stat { background: rgba(255,255,255,0.35); border: 1px solid rgba(255,255,255,0.5); border-radius: var(--radius-xl); padding: 18px 20px; text-align: center; }
    .cust-stat__val   { font-family: var(--font-serif); font-size: 1.8rem; font-weight: 700; color: var(--text); line-height: 1; }
    .cust-stat__label { font-size: 0.75rem; color: var(--muted); margin-top: 5px; text-transform: uppercase; letter-spacing: .05em; }

    /* ── Empty state ── */
    .empty-state { text-align: center; padding: 40px 20px; color: var(--muted); }
    .empty-state .material-symbols-outlined { font-size: 3rem; display: block; margin-bottom: 10px; color: var(--border); }
    .empty-state p { font-size: 0.88rem; }

    /* ── Filter tabs ── */
    .filter-tab { background: none; border: none; border-bottom: 2.5px solid transparent; padding: 10px 14px; font-size: 0.82rem; font-weight: 600; color: var(--muted); cursor: pointer; font-family: var(--font-sans); transition: color .15s, border-color .15s; white-space: nowrap; }
    .filter-tab:hover { color: var(--text); }
    .filter-tab--active { color: #2980b9; border-bottom-color: #2980b9; }
  </style>
</head>
<body>
<nav class="nav" id="app-nav"></nav>
<main class="main">

  <!-- ── Greeting ─────────────────────────────────────────── -->
  <div class="page-header">
    <div>
      <h1 class="page-title" id="page-greeting">Welcome!</h1>
      <p class="page-subtitle" id="page-subtitle">Here's a summary of your orders with Esperon Dairy Farm.</p>
    </div>
    <span id="current-date" style="font-size:0.82rem;color:var(--muted);align-self:center;"></span>
  </div>

  <!-- ── Profile card ─────────────────────────────────────── -->
  <div class="profile-card" style="margin-bottom:var(--spacing-xl);">
    <div class="profile-avatar" id="cust-avatar"><?= strtoupper(substr(htmlspecialchars($customerName), 0, 1)) ?></div>
    <div class="profile-info">
      <div class="profile-info__name"><?= htmlspecialchars($customerName) ?></div>
      <div class="profile-info__badge">Customer</div>
      <div class="profile-info__meta">
        <?php if ($address): ?>
        <span>
          <span class="material-symbols-outlined" style="font-size:0.9rem;">location_on</span>
          <?= htmlspecialchars($address) ?>
        </span>
        <?php endif; ?>
        <?php if ($contact): ?>
        <span>
          <span class="material-symbols-outlined" style="font-size:0.9rem;">phone</span>
          <?= htmlspecialchars($contact) ?>
        </span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ── Summary stats ────────────────────────────────────── -->
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:var(--spacing-md);margin-bottom:var(--spacing-xl);">
    <div class="cust-stat">
      <div class="cust-stat__val" id="stat-total">—</div>
      <div class="cust-stat__label">Total Orders</div>
    </div>
    <div class="cust-stat">
      <div class="cust-stat__val" id="stat-pending">—</div>
      <div class="cust-stat__label">Pending</div>
    </div>
    <div class="cust-stat">
      <div class="cust-stat__val" id="stat-delivered">—</div>
      <div class="cust-stat__label">Delivered</div>
    </div>
    <div class="cust-stat">
      <div class="cust-stat__val" id="stat-spent">—</div>
      <div class="cust-stat__label">Total Spent</div>
    </div>
  </div>

  <!-- ── Orders card ──────────────────────────────────────── -->
  <div class="card">
    <div class="card__header">
      <span style="font-family:var(--font-serif);font-size:1rem;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px;">
        <span class="material-symbols-outlined" style="color:#2980b9;font-size:1.2rem;">receipt_long</span>
        My Orders
      </span>
      <button class="btn btn--ghost" onclick="loadOrders()" style="font-size:0.8rem;padding:6px 14px;">
        <span class="material-symbols-outlined" style="font-size:0.9rem;">refresh</span> Refresh
      </button>
    </div>

    <!-- Filter tabs -->
    <div style="display:flex;gap:0;border-bottom:1px solid var(--border-light);padding:0 8px;overflow-x:auto;">
      <button class="filter-tab filter-tab--active" onclick="setFilter('all',this)">All</button>
      <button class="filter-tab" onclick="setFilter('pending',this)">Pending</button>
      <button class="filter-tab" onclick="setFilter('confirmed',this)">Confirmed</button>
      <button class="filter-tab" onclick="setFilter('delivered',this)">Delivered</button>
      <button class="filter-tab" onclick="setFilter('cancelled',this)">Cancelled</button>
    </div>

    <div id="orders-container" style="padding:16px 20px;min-height:120px;">
      <p style="color:var(--muted);font-size:0.84rem;">Loading your orders…</p>
    </div>
  </div>

</main>

<script src="js/api.js"></script>
<script src="js/ui.js"></script>
<script src="js/nav.js"></script>
<script>
// ── Greeting ──────────────────────────────────────────────
(function() {
  const h   = new Date().getHours();
  const tod = h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
  const u   = (() => { try { return JSON.parse(localStorage.getItem('user') || '{}'); } catch { return {}; } })();
  const greet = document.getElementById('page-greeting');
  const date  = document.getElementById('current-date');
  if (greet) greet.innerHTML = `${tod}, ${u.name || '<?= htmlspecialchars($customerName) ?>'}! <span class="material-symbols-outlined" style="vertical-align:middle;font-size:1.5rem;">waving_hand</span>`;
  if (date)  date.textContent = new Date().toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
})();

// ── Data ──────────────────────────────────────────────────
let _allOrders = [];
let _filter    = 'all';

const STATUS_PILL = {
  pending:   '<span class="order-pill order-pill--pending">Pending</span>',
  confirmed: '<span class="order-pill order-pill--confirmed">Confirmed</span>',
  delivered: '<span class="order-pill order-pill--delivered">Delivered</span>',
  cancelled: '<span class="order-pill order-pill--cancelled">Cancelled</span>',
};

// ── Load orders ───────────────────────────────────────────
async function loadOrders() {
  const container = document.getElementById('orders-container');
  container.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;">Loading…</p>';

  try {
    const res  = await fetch('../dairy_farm_backend/api/customer_portal.php?action=orders', {
      credentials: 'include'
    });
    const data = await res.json();

    if (!data.success) throw new Error(data.message || 'Failed to load orders.');
    _allOrders = data.data || [];
    updateStats();
    renderOrders();
  } catch(e) {
    container.innerHTML = `<p style="color:var(--danger);font-size:0.84rem;">${e.message}</p>`;
  }
}

// ── Stats ─────────────────────────────────────────────────
function updateStats() {
  const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
  const pending   = _allOrders.filter(o => o.Order_Status === 'pending').length;
  const delivered = _allOrders.filter(o => o.Order_Status === 'delivered').length;
  const spent     = _allOrders
    .filter(o => o.Order_Status !== 'cancelled')
    .reduce((s, o) => s + parseFloat(o.total_price || 0), 0);

  set('stat-total',     _allOrders.length);
  set('stat-pending',   pending);
  set('stat-delivered', delivered);
  set('stat-spent',     '₱' + spent.toFixed(2));
}

// ── Filter ────────────────────────────────────────────────
function setFilter(f, btn) {
  _filter = f;
  document.querySelectorAll('.filter-tab').forEach(b => {
    b.classList.toggle('filter-tab--active', b === btn);
  });
  renderOrders();
}

// ── Render ────────────────────────────────────────────────
function renderOrders() {
  const container = document.getElementById('orders-container');
  const list = _filter === 'all'
    ? _allOrders
    : _allOrders.filter(o => (o.Order_Status || '').toLowerCase() === _filter);

  if (!list.length) {
    container.innerHTML = `
      <div class="empty-state">
        <span class="material-symbols-outlined">receipt_long</span>
        <p>No ${_filter === 'all' ? '' : _filter + ' '}orders found.</p>
      </div>`;
    return;
  }

  container.innerHTML = list.map(o => {
    const status  = (o.Order_Status || 'pending').toLowerCase();
    const pill    = STATUS_PILL[status] || `<span class="order-pill order-pill--pending">${status}</span>`;
    const date    = new Date(o.Order_Date).toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' });
    const cowInfo = o.Cow + (o.Breed ? ` (${o.Breed})` : '');

    return `<div class="order-row">
      <div style="flex:1;min-width:0;">
        <div class="order-row__id">Order #${o.Order_ID} &mdash; ${o.Order_Type}</div>
        <div class="order-row__meta">
          ${date} &nbsp;·&nbsp; 🐄 ${cowInfo} &nbsp;·&nbsp; Handled by ${o.Worker_Name}
        </div>
        <div style="margin-top:5px;">${pill}</div>
      </div>
      <div class="order-row__price">
        <div class="order-row__total">₱${parseFloat(o.total_price || 0).toFixed(2)}</div>
        <div class="order-row__qty">${parseFloat(o.quantity_liters || 0).toFixed(2)}L × ₱${parseFloat(o.unit_price || 0).toFixed(2)}</div>
      </div>
    </div>`;
  }).join('');
}

// ── Init ──────────────────────────────────────────────────
loadOrders();
</script>
</body>
</html>
