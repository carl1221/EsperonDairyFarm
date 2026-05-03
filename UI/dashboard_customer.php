<?php
require_once __DIR__ . '/guard.php';
requireAuthPage();
requireCustomerPage();
$customerName = $_SESSION['user']['name']    ?? 'Customer';
$address      = $_SESSION['user']['address'] ?? '';
$contact      = $_SESSION['user']['contact'] ?? '';
$initial      = strtoupper(substr($customerName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Dashboard � Esperon Dairy Farm</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link rel="stylesheet" href="css/style.css" />
  <style>
    /* -- Status pills -- */
    .order-pill { display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em; }
    .order-pill--pending   { background:var(--warning-lt); color:#7a5a1e; }
    .order-pill--confirmed { background:var(--info-lt);    color:#2d4f5e; }
    .order-pill--delivered { background:var(--success-lt); color:var(--olive-dark); }
    .order-pill--cancelled { background:var(--danger-lt);  color:var(--danger); }

    /* -- Profile card -- */
    .profile-card { background:rgba(255,255,255,0.35);border:1px solid rgba(255,255,255,0.5);border-radius:var(--radius-xl);padding:22px 24px;display:flex;align-items:center;gap:20px;flex-wrap:wrap; }
    .profile-avatar { width:64px;height:64px;border-radius:50%;flex-shrink:0;background:linear-gradient(135deg,#2980b9,#3498db);display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-weight:700;color:#fff;border:3px solid rgba(255,255,255,0.7);box-shadow:0 4px 16px rgba(0,0,0,0.12); }
    .profile-info { flex:1;min-width:0; }
    .profile-info__name  { font-family:var(--font-serif);font-size:1.15rem;font-weight:700;color:var(--text); }
    .profile-info__badge { display:inline-block;padding:2px 10px;border-radius:20px;font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;background:rgba(41,128,185,0.12);color:#2980b9;margin-top:4px; }
    .profile-info__meta  { font-size:0.8rem;color:var(--muted);margin-top:6px;display:flex;flex-direction:column;gap:3px; }
    .profile-info__meta span { display:flex;align-items:center;gap:5px; }

    /* -- Stat cards -- */
    .cust-stat { background:rgba(255,255,255,0.35);border:1px solid rgba(255,255,255,0.5);border-radius:var(--radius-xl);padding:18px 20px;text-align:center; }
    .cust-stat__val   { font-family:var(--font-serif);font-size:1.8rem;font-weight:700;color:var(--text);line-height:1; }
    .cust-stat__label { font-size:0.75rem;color:var(--muted);margin-top:5px;text-transform:uppercase;letter-spacing:.05em; }

    /* -- Order rows -- */
    .order-row { display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border-light);gap:12px;cursor:pointer;transition:background .12s;border-radius:6px;padding-left:6px;padding-right:6px; }
    .order-row:last-child { border-bottom:none; }
    .order-row:hover { background:rgba(255,255,255,0.4); }
    .order-row__id   { font-weight:700;font-size:0.88rem;color:var(--text); }
    .order-row__meta { font-size:0.75rem;color:var(--muted);margin-top:2px; }
    .order-row__price { text-align:right;flex-shrink:0; }
    .order-row__total { font-weight:700;font-size:0.9rem;color:var(--olive-dark); }
    .order-row__qty   { font-size:0.72rem;color:var(--muted);margin-top:2px; }

    /* -- Filter tabs -- */
    .filter-tab { background:none;border:none;border-bottom:2.5px solid transparent;padding:10px 14px;font-size:0.82rem;font-weight:600;color:var(--muted);cursor:pointer;font-family:var(--font-sans);transition:color .15s,border-color .15s;white-space:nowrap; }
    .filter-tab:hover { color:var(--text); }
    .filter-tab--active { color:#2980b9;border-bottom-color:#2980b9; }

    /* -- Empty state -- */
    .empty-state { text-align:center;padding:40px 20px;color:var(--muted); }
    .empty-state .material-symbols-outlined { font-size:3rem;display:block;margin-bottom:10px;color:var(--border); }
    .empty-state p { font-size:0.88rem; }

    /* -- Featured products -- */
    .feat-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:var(--spacing-md); }
    .feat-card { background:rgba(255,255,255,0.35);border:1px solid rgba(255,255,255,0.5);border-radius:var(--radius-xl);padding:16px;display:flex;flex-direction:column;gap:8px;transition:transform .2s,box-shadow .2s; }
    .feat-card:hover { transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,0.1); }
    .feat-card__emoji { font-size:2rem;text-align:center; }
    .feat-card__name  { font-weight:700;font-size:0.85rem;color:var(--text);text-align:center; }
    .feat-card__price { font-size:0.9rem;font-weight:700;color:var(--olive-dark);text-align:center; }
    .feat-card__stock { font-size:0.72rem;color:var(--muted);text-align:center; }
    .feat-card__btn   { width:100%;padding:7px;border:none;border-radius:8px;background:linear-gradient(135deg,var(--olive),#6b8a5c);color:#fff;font-size:0.8rem;font-weight:700;font-family:var(--font-sans);cursor:pointer;transition:opacity .15s; }
    .feat-card__btn:hover { opacity:.88; }

    /* -- Cart orders -- */
    .cart-order-card { background:rgba(255,255,255,0.3);border:1px solid rgba(255,255,255,0.5);border-radius:var(--radius-xl);padding:14px 18px;margin-bottom:10px; }
    .cart-order-card:last-child { margin-bottom:0; }
    .cart-order__header { display:flex;justify-content:space-between;align-items:center;margin-bottom:8px; }
    .cart-order__id    { font-weight:700;font-size:0.85rem;color:var(--text); }
    .cart-order__date  { font-size:0.75rem;color:var(--muted); }
    .cart-order__total { font-weight:700;color:var(--olive-dark); }
    .cart-order__item  { font-size:0.8rem;color:var(--muted);display:flex;justify-content:space-between;padding:3px 0;border-bottom:1px solid var(--border-light); }
    .cart-order__item:last-child { border-bottom:none; }

    /* -- Edit profile modal -- */
    @keyframes epSlide { from{opacity:0;transform:translateY(-16px) scale(0.97)} to{opacity:1;transform:none} }
    .ep-overlay { display:none;position:fixed;inset:0;z-index:9999;background:rgba(42,31,21,0.5);backdrop-filter:blur(5px);align-items:center;justify-content:center;padding:16px; }
    .ep-overlay.open { display:flex; }
    .ep-card { background:#faf6f0;border-radius:20px;box-shadow:0 16px 56px rgba(0,0,0,0.2);width:100%;max-width:420px;overflow:hidden;animation:epSlide .25s ease;font-family:'Lato',sans-serif; }
    .ep-header { display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;background:linear-gradient(135deg,#2980b9,#3498db); }
    .ep-header h3 { font-family:'Playfair Display',serif;font-size:1.05rem;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px; }
    .ep-close { background:rgba(255,255,255,0.18);border:none;cursor:pointer;width:28px;height:28px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center; }
    .ep-body { padding:20px 22px; }
    .ep-field { margin-bottom:14px; }
    .ep-label { display:block;font-size:0.72rem;font-weight:700;color:#4a3f35;text-transform:uppercase;letter-spacing:.07em;margin-bottom:5px; }
    .ep-input { width:100%;padding:10px 14px;border:1.5px solid #e8dfd2;border-radius:10px;font-size:0.9rem;font-family:'Lato',sans-serif;color:#2a1f15;background:#fff;outline:none;box-sizing:border-box;transition:border-color .15s,box-shadow .15s; }
    .ep-input:focus { border-color:#2980b9;box-shadow:0 0 0 3px rgba(41,128,185,0.12); }
    .ep-footer { display:flex;justify-content:flex-end;gap:8px;padding:0 22px 20px; }
    .ep-btn { padding:10px 20px;border-radius:10px;font-family:'Lato',sans-serif;font-size:0.88rem;font-weight:700;cursor:pointer;border:none;transition:opacity .15s; }
    .ep-btn:hover { opacity:.88; }
    .ep-btn--ghost   { background:#fff;color:#4a3f35;border:1.5px solid #d4c9b8 !important; }
    .ep-btn--primary { background:linear-gradient(135deg,#2980b9,#3498db);color:#fff;box-shadow:0 2px 8px rgba(41,128,185,0.25); }

    /* -- Order detail modal -- */
    @keyframes odSlide { from{opacity:0;transform:translateY(-16px) scale(0.97)} to{opacity:1;transform:none} }
    .od-overlay { display:none;position:fixed;inset:0;z-index:9999;background:rgba(42,31,21,0.5);backdrop-filter:blur(5px);align-items:center;justify-content:center;padding:16px; }
    .od-overlay.open { display:flex; }
    .od-card { background:#faf6f0;border-radius:20px;box-shadow:0 16px 56px rgba(0,0,0,0.2);width:100%;max-width:480px;overflow:hidden;animation:odSlide .25s ease;font-family:'Lato',sans-serif; }
    .od-header { display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;background:linear-gradient(135deg,var(--olive),#6b8a5c); }
    .od-header h3 { font-family:'Playfair Display',serif;font-size:1.05rem;font-weight:700;color:#fff; }
    .od-close { background:rgba(255,255,255,0.18);border:none;cursor:pointer;width:28px;height:28px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center; }
    .od-body { padding:20px 22px; }
    .od-row { display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e8dfd2;font-size:0.88rem; }
    .od-row:last-child { border-bottom:none; }
    .od-row__label { color:#8a7f72;font-weight:600; }
    .od-row__val   { color:#2a1f15;font-weight:700;text-align:right; }

    /* -- Two-column layout (Orders + Shop) -- */
    .cust-two-col { display:grid;grid-template-columns:1fr 1fr;gap:var(--spacing-lg);margin-bottom:var(--spacing-xl); }

    /* -- Mobile overrides -- */
    @media (max-width: 750px) {
      /* Stat cards: 2x2 grid */
      .cust-stat__val { font-size:1.4rem; }

      /* Profile card: stack on very small screens */
      .profile-card { padding:16px; gap:14px; }
      .profile-avatar { width:52px;height:52px;font-size:1.3rem; }

      /* Orders + Shop: single column */
      .cust-two-col { grid-template-columns:1fr; gap:var(--spacing-md); }

      /* Featured products: 2 columns on mobile */
      .feat-grid { grid-template-columns:repeat(2,1fr);gap:var(--spacing-sm); }
      .feat-card { padding:12px; }
      .feat-card__emoji { font-size:1.6rem; }

      /* Order rows: tighter */
      .order-row { padding:10px 4px; }
      .order-row__id { font-size:0.82rem; }
      .order-row__meta { font-size:0.7rem; }

      /* Filter tabs: scrollable */
      .filter-tabs-wrap { overflow-x:auto;-webkit-overflow-scrolling:touch; }
    }

    @media (max-width: 480px) {
      /* Stat cards: 2 per row, smaller */
      .cust-stat { padding:12px 10px; }
      .cust-stat__val { font-size:1.2rem; }
      .cust-stat__label { font-size:0.68rem; }

      /* Featured products: 2 columns */
      .feat-grid { grid-template-columns:repeat(2,1fr); }

      /* Profile edit button: full width */
      .profile-card > button { width:100%;justify-content:center; }

      /* Cart order cards */
      .cart-order-card { padding:12px 14px; }
    }
  </style>
</head>
<body>
<nav class="nav" id="app-nav"></nav>
<main class="main">

  <!-- -- Greeting -- -->
  <div class="page-header">
    <div>
      <h1 class="page-title" id="page-greeting">Welcome!</h1>
      <p class="page-subtitle">Here's your Esperon Dairy Farm account overview.</p>
    </div>
    <span id="current-date" style="font-size:0.82rem;color:var(--muted);align-self:center;"></span>
  </div>

  <!-- -- Profile card -- -->
  <div class="profile-card" style="margin-bottom:var(--spacing-xl);">
    <div class="profile-avatar"><?= htmlspecialchars($initial) ?></div>
    <div class="profile-info">
      <div class="profile-info__name" id="profile-name"><?= htmlspecialchars($customerName) ?></div>
      <div class="profile-info__badge">Customer</div>
      <div class="profile-info__meta">
        <span><span class="material-symbols-outlined" style="font-size:0.9rem;">location_on</span>
          <span id="profile-address"><?= htmlspecialchars($address ?: 'No address on file') ?></span></span>
        <span><span class="material-symbols-outlined" style="font-size:0.9rem;">phone</span>
          <span id="profile-contact"><?= htmlspecialchars($contact ?: 'No contact on file') ?></span></span>
      </div>
    </div>
    <button onclick="openEditProfile()"
            style="margin-left:auto;padding:8px 16px;border:1.5px solid rgba(41,128,185,0.3);border-radius:10px;background:rgba(41,128,185,0.08);color:#2980b9;font-family:'Lato',sans-serif;font-size:0.82rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px;transition:background .15s;"
            onmouseover="this.style.background='rgba(41,128,185,0.15)'" onmouseout="this.style.background='rgba(41,128,185,0.08)'">
      <span class="material-symbols-outlined" style="font-size:0.95rem;">edit</span> Edit Profile
    </button>
  </div>

  <!-- -- Stat cards -- -->
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:var(--spacing-md);margin-bottom:var(--spacing-xl);">
    <div class="cust-stat">
      <div class="cust-stat__val" id="stat-total">�</div>
      <div class="cust-stat__label">Total Orders</div>
    </div>
    <div class="cust-stat">
      <div class="cust-stat__val" id="stat-pending">�</div>
      <div class="cust-stat__label">Pending</div>
    </div>
    <div class="cust-stat">
      <div class="cust-stat__val" id="stat-delivered">�</div>
      <div class="cust-stat__label">Delivered</div>
    </div>
    <div class="cust-stat">
      <div class="cust-stat__val" id="stat-spent">�</div>
      <div class="cust-stat__label">Total Spent</div>
    </div>
  </div>

  <!-- -- Two-column layout: Orders + Shop -- -->
  <div class="cust-two-col">

    <!-- Farm Orders -->
    <div class="card">
      <div class="card__header">
        <span style="font-family:var(--font-serif);font-size:1rem;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px;">
          <span class="material-symbols-outlined" style="color:#2980b9;font-size:1.2rem;">receipt_long</span>
          Farm Orders
        </span>
        <button class="btn btn--ghost" onclick="loadOrders()" style="font-size:0.78rem;padding:5px 12px;">
          <span class="material-symbols-outlined" style="font-size:0.85rem;">refresh</span>
        </button>
      </div>
      <div class="filter-tabs-wrap" style="display:flex;gap:0;border-bottom:1px solid var(--border-light);padding:0 8px;overflow-x:auto;">
        <button class="filter-tab filter-tab--active" onclick="setFilter('all',this)">All</button>
        <button class="filter-tab" onclick="setFilter('pending',this)">Pending</button>
        <button class="filter-tab" onclick="setFilter('confirmed',this)">Confirmed</button>
        <button class="filter-tab" onclick="setFilter('delivered',this)">Delivered</button>
        <button class="filter-tab" onclick="setFilter('cancelled',this)">Cancelled</button>
      </div>
      <div id="orders-container" style="padding:12px 16px;min-height:120px;max-height:380px;overflow-y:auto;">
        <p style="color:var(--muted);font-size:0.84rem;">Loading�</p>
      </div>
    </div>

    <!-- Featured Products -->
    <div class="card">
      <div class="card__header">
        <span style="font-family:var(--font-serif);font-size:1rem;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px;">
          <span class="material-symbols-outlined" style="color:var(--olive);font-size:1.2rem;">storefront</span>
          Shop Products
        </span>
        <a href="shop.php" style="font-size:0.78rem;color:var(--olive);font-weight:700;text-decoration:none;padding:5px 12px;background:rgba(78,96,64,0.08);border-radius:8px;">
          View All ?
        </a>
      </div>
      <div id="featured-container" style="padding:14px 16px;">
        <p style="color:var(--muted);font-size:0.84rem;">Loading�</p>
      </div>
    </div>
  </div>

  <!-- -- Shop Purchase History -- -->
  <div class="card">
    <div class="card__header">
      <span style="font-family:var(--font-serif);font-size:1rem;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px;">
        <span class="material-symbols-outlined" style="color:var(--olive);font-size:1.2rem;">shopping_bag</span>
        Shop Purchase History
      </span>
    </div>
    <div id="cart-orders-container" style="padding:14px 20px;min-height:80px;max-height:320px;overflow-y:auto;">
      <p style="color:var(--muted);font-size:0.84rem;">Loading�</p>
    </div>
  </div>

</main>

<!-- -- Edit Profile Modal -- -->
<div class="ep-overlay" id="epOverlay">
  <div class="ep-card">
    <div class="ep-header">
      <h3><span class="material-symbols-outlined" style="font-size:1.1rem;">manage_accounts</span> Edit Profile</h3>
      <button class="ep-close" onclick="closeEditProfile()">
        <span class="material-symbols-outlined" style="font-size:1rem;">close</span>
      </button>
    </div>
    <div class="ep-body">
      <div id="ep-alert" style="display:none;padding:8px 12px;border-radius:8px;font-size:0.82rem;margin-bottom:12px;"></div>
      <div class="ep-field">
        <label class="ep-label">Full Name <span style="color:#c0392b;">*</span></label>
        <input class="ep-input" type="text" id="ep-name" placeholder="Your name" />
      </div>
      <div class="ep-field">
        <label class="ep-label">Address <span style="color:#c0392b;">*</span></label>
        <input class="ep-input" type="text" id="ep-address" placeholder="Your address" />
      </div>
      <div class="ep-field">
        <label class="ep-label">Contact Number <span style="color:#c0392b;">*</span></label>
        <input class="ep-input" type="text" id="ep-contact" placeholder="e.g. 09123456789" />
      </div>
    </div>
    <div class="ep-footer">
      <button class="ep-btn ep-btn--ghost" onclick="closeEditProfile()">Cancel</button>
      <button class="ep-btn ep-btn--primary" id="ep-save-btn" onclick="saveProfile()">
        Save Changes
      </button>
    </div>
  </div>
</div>

<!-- -- Order Detail Modal -- -->
<div class="od-overlay" id="odOverlay">
  <div class="od-card">
    <div class="od-header">
      <h3 id="od-title">Order Details</h3>
      <button class="od-close" onclick="closeOrderDetail()">
        <span class="material-symbols-outlined" style="font-size:1rem;">close</span>
      </button>
    </div>
    <div class="od-body" id="od-body"></div>
  </div>
</div>

<script src="js/api.js"></script>
<script src="js/ui.js"></script>
<script src="js/nav.js"></script>
<script>
const PORTAL = '../dairy_farm_backend/api/v1/customer_portal.php';

// -- Greeting ----------------------------------------------
(function() {
  const h   = new Date().getHours();
  const tod = h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
  const u   = (() => { try { return JSON.parse(localStorage.getItem('user')||'{}'); } catch { return {}; } })();
  const greet = document.getElementById('page-greeting');
  const date  = document.getElementById('current-date');
  if (greet) greet.innerHTML = `${tod}, ${u.name || '<?= htmlspecialchars($customerName) ?>'}! <span class="material-symbols-outlined" style="vertical-align:middle;font-size:1.5rem;">waving_hand</span>`;
  if (date)  date.textContent = new Date().toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
})();

// -- Status pills ------------------------------------------
const STATUS_PILL = {
  pending:   '<span class="order-pill order-pill--pending">Pending</span>',
  confirmed: '<span class="order-pill order-pill--confirmed">Confirmed</span>',
  delivered: '<span class="order-pill order-pill--delivered">Delivered</span>',
  cancelled: '<span class="order-pill order-pill--cancelled">Cancelled</span>',
};

// -- Product emoji -----------------------------------------
const EMOJI_MAP = { milk:'\uD83E\uDD5B', cheese:'\uD83E\uDDC0', butter:'\uD83E\uDDC8', yogurt:'\uD83C\uDF6C', cream:'\uD83C\uDF68', skim:'\uD83C\uDF76', mozzarella:'\uD83E\uDDC0' };
function getEmoji(name) {
  const l = name.toLowerCase();
  for (const [k,e] of Object.entries(EMOJI_MAP)) if (l.includes(k)) return e;
  return '\uD83D\uDED2'; // 🛒 shopping cart as fallback
}

// -- Farm orders -------------------------------------------
let _allOrders = [];
let _filter    = 'all';

async function loadOrders() {
  const c = document.getElementById('orders-container');
  c.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;">Loading�</p>';
  try {
    const res  = await fetch(PORTAL + '?action=orders', { credentials:'include' });
    const data = await res.json();
    if (!data.success) throw new Error(data.message);
    _allOrders = data.data || [];
    updateStats();
    renderOrders();
  } catch(e) {
    c.innerHTML = `<p style="color:var(--danger);font-size:0.84rem;">${e.message}</p>`;
  }
}

function updateStats() {
  const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
  const pending   = _allOrders.filter(o => o.Order_Status === 'pending').length;
  const delivered = _allOrders.filter(o => o.Order_Status === 'delivered').length;
  const spent     = _allOrders.filter(o => o.Order_Status !== 'cancelled')
                              .reduce((s, o) => s + parseFloat(o.total_price || 0), 0);
  set('stat-total',     _allOrders.length);
  set('stat-pending',   pending);
  set('stat-delivered', delivered);
  set('stat-spent',     '\u20B1' + spent.toFixed(2));
}

function setFilter(f, btn) {
  _filter = f;
  document.querySelectorAll('.filter-tab').forEach(b => b.classList.toggle('filter-tab--active', b === btn));
  renderOrders();
}

function renderOrders() {
  const c    = document.getElementById('orders-container');
  const list = _filter === 'all' ? _allOrders : _allOrders.filter(o => (o.Order_Status||'').toLowerCase() === _filter);
  if (!list.length) {
    c.innerHTML = `<div class="empty-state"><span class="material-symbols-outlined">receipt_long</span><p>No ${_filter === 'all' ? '' : _filter + ' '}orders found.</p></div>`;
    return;
  }
  c.innerHTML = list.map(o => {
    const status = (o.Order_Status || 'pending').toLowerCase();
    const pill   = STATUS_PILL[status] || `<span class="order-pill order-pill--pending">${status}</span>`;
    const date   = new Date(o.Order_Date).toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' });
    // Show green dot if order was updated in the last 24 hours
    const oneDayAgo  = Date.now() - 86400000;
    const updatedAt  = o.Order_Updated ? new Date(o.Order_Updated).getTime() : 0;
    const recentDot  = updatedAt > oneDayAgo
      ? `<span title="Status updated recently" style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#27ae60;margin-left:5px;vertical-align:middle;"></span>`
      : '';
    return `<div class="order-row" onclick="openOrderDetail(${JSON.stringify(o).replace(/"/g,'&quot;')})">
      <div style="flex:1;min-width:0;">
        <div class="order-row__id">Order #${o.Order_ID} &mdash; ${o.Order_Type}${recentDot}</div>
        <div class="order-row__meta">${date} &nbsp;&middot;&nbsp; \uD83D\uDC04 ${o.Cow}${o.Breed ? ' ('+o.Breed+')' : ''} &nbsp;&middot;&nbsp; ${o.Worker_Name}</div>
        <div style="margin-top:5px;">${pill}</div>
      </div>
      <div class="order-row__price">
        <div class="order-row__total">&#8369;${parseFloat(o.total_price||0).toFixed(2)}</div>
        <div class="order-row__qty">${parseFloat(o.quantity_liters||0).toFixed(2)}L &middot; &#8369;${parseFloat(o.unit_price||0).toFixed(2)}</div>
      </div>
    </div>`;
  }).join('');
}

// -- Order detail modal ------------------------------------
function openOrderDetail(o) {
  document.getElementById('od-title').textContent = `Order #${o.Order_ID}`;
  const status = (o.Order_Status || 'pending').toLowerCase();
  const pill   = STATUS_PILL[status] || status;
  document.getElementById('od-body').innerHTML = `
    <div class="od-row"><span class="od-row__label">Order Type</span><span class="od-row__val">${o.Order_Type}</span></div>
    <div class="od-row"><span class="od-row__label">Date</span><span class="od-row__val">${new Date(o.Order_Date).toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'})}</span></div>
    <div class="od-row"><span class="od-row__label">Cow</span><span class="od-row__val">\uD83D\uDC04 ${o.Cow}${o.Breed?' ('+o.Breed+')':''}</span></div>
    <div class="od-row"><span class="od-row__label">Handled by</span><span class="od-row__val">${o.Worker_Name}</span></div>
    <div class="od-row"><span class="od-row__label">Quantity</span><span class="od-row__val">${parseFloat(o.quantity_liters||0).toFixed(2)} L</span></div>
    <div class="od-row"><span class="od-row__label">Unit Price</span><span class="od-row__val">&#8369;${parseFloat(o.unit_price||0).toFixed(2)} / L</span></div>
    <div class="od-row"><span class="od-row__label">Total</span><span class="od-row__val" style="color:var(--olive-dark);font-size:1rem;">&#8369;${parseFloat(o.total_price||0).toFixed(2)}</span></div>
    <div class="od-row"><span class="od-row__label">Status</span><span class="od-row__val">${pill}</span></div>
    ${o.Order_Notes ? `<div class="od-row"><span class="od-row__label">Notes</span><span class="od-row__val">${o.Order_Notes}</span></div>` : ''}
  `;
  document.getElementById('odOverlay').classList.add('open');
}
function closeOrderDetail() { document.getElementById('odOverlay').classList.remove('open'); }
document.getElementById('odOverlay').addEventListener('click', e => { if (e.target.id === 'odOverlay') closeOrderDetail(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeOrderDetail(); closeEditProfile(); } });

// -- Featured products -------------------------------------
async function loadFeatured() {
  const c = document.getElementById('featured-container');
  try {
    const res  = await fetch(PORTAL + '?action=featured', { credentials:'include' });
    const data = await res.json();
    if (!data.success || !data.data.length) {
      c.innerHTML = `<div class="empty-state"><span class="material-symbols-outlined">storefront</span><p>No products available.</p></div>`;
      return;
    }
    c.innerHTML = `<div class="feat-grid">${data.data.map(p => `
      <div class="feat-card">
        <div class="feat-card__emoji">${getEmoji(p.name)}</div>
        <div class="feat-card__name">${p.name}</div>
        <div class="feat-card__price">&#8369;${parseFloat(p.price).toFixed(2)} / ${p.unit}</div>
        <div class="feat-card__stock">${p.stock_qty} in stock</div>
        <button class="feat-card__btn" onclick="quickAddToCart(${p.product_id}, '${p.name.replace(/'/g,"\\'")}')">
          Add to Cart
        </button>
      </div>`).join('')}</div>`;
  } catch(e) {
    c.innerHTML = `<p style="color:var(--danger);font-size:0.84rem;">${e.message}</p>`;
  }
}

async function quickAddToCart(productId, name) {
  try {
    await API.cart.add(productId, 1);
    UI.toast(`${name} added to cart!`, 'success');
  } catch(e) { UI.toast(e.message, 'error'); }
}

// -- Shop purchase history ---------------------------------
async function loadCartOrders() {
  const c = document.getElementById('cart-orders-container');
  try {
    const res  = await fetch(PORTAL + '?action=cart_orders', { credentials:'include' });
    const data = await res.json();
    if (!data.success || !data.data.length) {
      c.innerHTML = `<div class="empty-state"><span class="material-symbols-outlined">shopping_bag</span><p>No shop purchases yet. <a href="shop.php" style="color:var(--olive);font-weight:700;">Browse the shop ?</a></p></div>`;
      return;
    }
    c.innerHTML = data.data.map(cart => {
      const date  = new Date(cart.purchased_at).toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' });
      const items = cart.items.map(i =>
        `<div class="cart-order__item">
          <span>${getEmoji(i.product_name)} ${i.product_name} &times; ${i.quantity} ${i.unit}</span>
          <span>&#8369;${parseFloat(i.subtotal).toFixed(2)}</span>
        </div>`
      ).join('');
      return `<div class="cart-order-card">
        <div class="cart-order__header">
          <div>
            <div class="cart-order__id">Purchase #${cart.cart_id}</div>
            <div class="cart-order__date">${date}</div>
          </div>
          <div class="cart-order__total">&#8369;${parseFloat(cart.total).toFixed(2)}</div>
        </div>
        ${items}
      </div>`;
    }).join('');
  } catch(e) {
    c.innerHTML = `<p style="color:var(--danger);font-size:0.84rem;">${e.message}</p>`;
  }
}

// -- Edit profile ------------------------------------------
function openEditProfile() {
  document.getElementById('ep-name').value    = document.getElementById('profile-name').textContent.trim();
  document.getElementById('ep-address').value = document.getElementById('profile-address').textContent.trim();
  document.getElementById('ep-contact').value = document.getElementById('profile-contact').textContent.trim();
  document.getElementById('ep-alert').style.display = 'none';
  document.getElementById('epOverlay').classList.add('open');
  setTimeout(() => document.getElementById('ep-name').focus(), 60);
}
function closeEditProfile() { document.getElementById('epOverlay').classList.remove('open'); }
document.getElementById('epOverlay').addEventListener('click', e => { if (e.target.id === 'epOverlay') closeEditProfile(); });

async function saveProfile() {
  const name    = document.getElementById('ep-name').value.trim();
  const address = document.getElementById('ep-address').value.trim();
  const contact = document.getElementById('ep-contact').value.trim();
  const alertEl = document.getElementById('ep-alert');
  const saveBtn = document.getElementById('ep-save-btn');

  if (!name || !address || !contact) {
    alertEl.textContent = 'All fields are required.';
    alertEl.style.cssText = 'display:block;background:#fdecea;border:1px solid #f5c6cb;color:#c0392b;padding:8px 12px;border-radius:8px;font-size:0.82rem;margin-bottom:12px;';
    return;
  }

  saveBtn.disabled = true;
  saveBtn.textContent = 'Saving�';

  try {
    const csrf = localStorage.getItem('csrf_token') || '';
    const res  = await fetch(PORTAL + '?action=update_profile', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      credentials: 'include',
      body: JSON.stringify({ name, address, contact }),
    });
    const data = await res.json();

    if (data.success) {
      // Update displayed values
      document.getElementById('profile-name').textContent    = name;
      document.getElementById('profile-address').textContent = address;
      document.getElementById('profile-contact').textContent = contact;

      // Update localStorage
      const u = (() => { try { return JSON.parse(localStorage.getItem('user')||'{}'); } catch { return {}; } })();
      u.name = name; localStorage.setItem('user', JSON.stringify(u));

      // Update nav display name
      const navName = document.getElementById('nav-display-name');
      if (navName) navName.textContent = name;

      closeEditProfile();
      UI.toast('Profile updated!', 'success');
    } else {
      alertEl.textContent = data.message || 'Update failed.';
      alertEl.style.cssText = 'display:block;background:#fdecea;border:1px solid #f5c6cb;color:#c0392b;padding:8px 12px;border-radius:8px;font-size:0.82rem;margin-bottom:12px;';
    }
  } catch(e) {
    alertEl.textContent = 'Network error. Please try again.';
    alertEl.style.cssText = 'display:block;background:#fdecea;border:1px solid #f5c6cb;color:#c0392b;padding:8px 12px;border-radius:8px;font-size:0.82rem;margin-bottom:12px;';
  } finally {
    saveBtn.disabled = false;
    saveBtn.textContent = 'Save Changes';
  }
}

// -- Init --------------------------------------------------
loadOrders();
loadFeatured();
loadCartOrders();
</script>
</body>
</html>
