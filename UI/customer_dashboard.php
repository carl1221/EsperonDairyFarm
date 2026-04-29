<?php
session_set_cookie_params(["lifetime"=>0,"path"=>"/","domain"=>false,"secure"=>false,"httponly"=>true,"samesite"=>"Lax"]);
session_start();
if (!isset($_SESSION["customer"])) { header("Location: login_unified.php"); exit; }
$c = $_SESSION["customer"];
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
    /* ── Customer Dashboard ── */
    .cust-nav {
      width: 240px; min-width: 240px; background: var(--sidebar-bg);
      border-right: 1px solid var(--border-light);
      display: flex; flex-direction: column; min-height: 100vh;
      position: fixed; top: 0; left: 0; bottom: 0; z-index: 100;
      overflow-y: auto;
    }
    .cust-main { margin-left: 240px; flex: 1; padding: var(--spacing-lg) var(--spacing-xl); min-height: 100vh; }
    .cust-brand { padding: 20px 20px 12px; display: flex; align-items: center; gap: 10px; }
    .cust-brand img { width: 38px; height: 38px; border-radius: 8px; object-fit: cover; }
    .cust-brand-name { font-family: var(--font-serif); font-size: 1rem; font-weight: 700; color: var(--maroon); line-height: 1.2; }
    .cust-brand-sub  { font-size: 0.6rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.12em; }
    .cust-user { display: flex; align-items: center; gap: 10px; padding: 10px 14px; margin: 4px 10px 8px; background: rgba(255,255,255,0.35); border-radius: 12px; border: 1px solid rgba(255,255,255,0.4); }
    .cust-avatar { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg,#7a1f2e,#9b3040); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.95rem; color: #fff; flex-shrink: 0; }
    .cust-nav-section { padding: 10px 18px 4px; font-size: 0.62rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: var(--olive-dark); opacity: 0.7; }
    .cust-nav-link { display: flex; align-items: center; gap: 8px; padding: 9px 18px; margin: 1px 10px; text-decoration: none; color: var(--text); font-size: 0.84rem; font-weight: 600; border-radius: 10px; border-left: 3px solid transparent; transition: all 0.15s; cursor: pointer; background: none; border-top: none; border-right: none; border-bottom: none; font-family: var(--font-sans); width: calc(100% - 20px); text-align: left; }
    .cust-nav-link:hover { background: rgba(255,255,255,0.4); color: var(--olive-dark); border-left-color: var(--olive); }
    .cust-nav-link.active { background: rgba(78,96,64,0.1); color: var(--olive-dark); border-left-color: var(--olive); font-weight: 700; }
    .cust-nav-link .material-symbols-outlined { font-size: 1.15rem; width: 20px; text-align: center; }
    .cust-nav-footer { margin-top: auto; padding: 14px 18px; font-size: 0.7rem; color: var(--muted-light); text-align: center; border-top: 1px solid var(--border-light); }
    /* Sections */
    .cust-section { display: none; }
    .cust-section.active { display: block; }
    /* Product cards */
    .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: var(--spacing-lg); margin-top: var(--spacing-lg); }
    .product-card { background: rgba(255,255,255,0.35); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.5); border-radius: 16px; padding: 20px; text-align: center; transition: all 0.2s; cursor: pointer; }
    .product-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); background: rgba(255,255,255,0.5); }
    .product-card.selected { border-color: var(--olive); background: rgba(78,96,64,0.08); box-shadow: 0 0 0 2px var(--olive); }
    .product-icon { font-size: 2.5rem; margin-bottom: 10px; }
    .product-name { font-weight: 700; font-size: 0.95rem; color: var(--text); margin-bottom: 4px; }
    .product-detail { font-size: 0.78rem; color: var(--muted); }
    .product-price { font-family: var(--font-serif); font-size: 1.2rem; font-weight: 700; color: var(--olive-dark); margin-top: 8px; }
    /* Order history */
    .order-card { background: rgba(255,255,255,0.35); border: 1px solid rgba(255,255,255,0.5); border-radius: 14px; padding: 16px 20px; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .order-card__id { font-weight: 700; font-size: 0.9rem; color: var(--text); }
    .order-card__meta { font-size: 0.75rem; color: var(--muted); margin-top: 2px; }
    /* Notification items */
    .notif-item { display: flex; align-items: flex-start; gap: 10px; padding: 10px 14px; border-radius: 10px; margin-bottom: 8px; font-size: 0.84rem; }
    .notif-item--success { background: var(--success-lt); border-left: 3px solid var(--olive); color: var(--olive-dark); }
    .notif-item--info    { background: var(--info-lt);    border-left: 3px solid var(--info);  color: #2d4f5e; }
    .notif-item--warning { background: var(--warning-lt); border-left: 3px solid var(--warning); color: #7a5a1e; }
    /* Profile form */
    .profile-form .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
    .profile-form .form-group { display: flex; flex-direction: column; gap: 5px; }
    .profile-form label { font-size: 0.78rem; font-weight: 700; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.06em; }
    .profile-form input, .profile-form textarea {
      padding: 10px 14px; border: 1.5px solid var(--border-light); border-radius: 10px;
      font-size: 0.9rem; font-family: var(--font-sans); color: var(--text);
      background: rgba(255,255,255,0.7); outline: none; transition: border-color 0.15s, box-shadow 0.15s;
      box-sizing: border-box; width: 100%;
    }
    .profile-form input:focus, .profile-form textarea:focus { border-color: var(--olive); box-shadow: 0 0 0 3px rgba(78,96,64,0.12); }
    /* Feedback stars */
    .star-rating { display: flex; gap: 4px; margin: 8px 0; }
    .star-rating span { font-size: 1.6rem; cursor: pointer; color: #d4c9b8; transition: color 0.15s; }
    .star-rating span.active { color: var(--gold); }
    @media (max-width: 768px) {
      .cust-nav { width: 100%; min-width: unset; height: auto; position: relative; flex-direction: row; flex-wrap: wrap; }
      .cust-main { margin-left: 0; }
      .profile-form .form-row { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<!-- ── Sidebar ── -->
<nav class="cust-nav" id="cust-nav">
  <div class="cust-brand">
    <img src="assets/logo.jpg" alt="Esperon" onerror="this.style.display='none'" />
    <div><div class="cust-brand-name">Esperon<br>Dairy Farm</div><div class="cust-brand-sub">Customer Portal</div></div>
  </div>

  <div class="cust-user">
    <div class="cust-avatar" id="nav-initial">?</div>
    <div style="flex:1;min-width:0;">
      <div style="font-weight:700;font-size:0.88rem;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" id="nav-name">Loading…</div>
      <div style="font-size:0.72rem;color:var(--muted);">Customer</div>
    </div>
  </div>

  <div style="height:1px;background:var(--border-light);margin:0 14px 6px;"></div>

  <span class="cust-nav-section">Menu</span>
  <button class="cust-nav-link active" onclick="showSection('overview',this)">
    <span class="material-symbols-outlined">dashboard</span> Overview
  </button>
  <button class="cust-nav-link" onclick="showSection('products',this)">
    <span class="material-symbols-outlined">storefront</span> Products
  </button>
  <button class="cust-nav-link" onclick="showSection('place-order',this)">
    <span class="material-symbols-outlined">add_shopping_cart</span> Place Order
  </button>
  <button class="cust-nav-link" onclick="showSection('my-orders',this)">
    <span class="material-symbols-outlined">receipt_long</span> My Orders
  </button>
  <button class="cust-nav-link" onclick="showSection('notifications',this)">
    <span class="material-symbols-outlined">notifications</span> Notifications
    <span id="notif-badge" class="badge badge--red" style="display:none;font-size:0.62rem;margin-left:auto;">0</span>
  </button>

  <span class="cust-nav-section">Account</span>
  <button class="cust-nav-link" onclick="showSection('profile',this)">
    <span class="material-symbols-outlined">manage_accounts</span> My Profile
  </button>
  <button class="cust-nav-link" onclick="showSection('feedback',this)">
    <span class="material-symbols-outlined">star_rate</span> Feedback
  </button>
  <button class="cust-nav-link" id="logout-btn" style="color:var(--danger);">
    <span class="material-symbols-outlined">logout</span> Logout
  </button>

  <div class="cust-nav-footer">Esperon Farm &copy; 2026</div>
</nav>

<!-- ── Main Content ── -->
<main class="cust-main">

  <!-- ══ SECTION 1: OVERVIEW ══════════════════════════════ -->
  <div class="cust-section active" id="section-overview">
    <div class="page-header">
      <div>
        <h1 class="page-title" id="page-greeting">Welcome!</h1>
        <p class="page-subtitle" id="page-date"></p>
      </div>
    </div>

    <!-- Quick stats -->
    <div class="stats-grid" style="margin-bottom:var(--spacing-xl);">
      <div class="stat-card">
        <div class="stat-card__icon"><span class="material-symbols-outlined">receipt_long</span></div>
        <div class="stat-card__content">
          <div class="stat-card__val" id="ov-total-orders">0</div>
          <div class="stat-card__label">Total Orders</div>
        </div>
      </div>
      <div class="stat-card stat-card--gold">
        <div class="stat-card__icon"><span class="material-symbols-outlined">pending_actions</span></div>
        <div class="stat-card__content">
          <div class="stat-card__val" id="ov-pending">0</div>
          <div class="stat-card__label">Pending</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-card__icon"><span class="material-symbols-outlined">local_shipping</span></div>
        <div class="stat-card__content">
          <div class="stat-card__val" id="ov-processing">0</div>
          <div class="stat-card__label">Processing</div>
        </div>
      </div>
      <div class="stat-card stat-card--danger">
        <div class="stat-card__icon"><span class="material-symbols-outlined">check_circle</span></div>
        <div class="stat-card__content">
          <div class="stat-card__val" id="ov-delivered">0</div>
          <div class="stat-card__label">Delivered</div>
        </div>
      </div>
    </div>

    <div class="two-col">
      <!-- Recent Orders -->
      <div class="card">
        <div class="card__header">
          <span class="dash-section-title"><span class="material-symbols-outlined" style="color:var(--olive);font-size:1.2rem;">receipt_long</span> Recent Orders</span>
          <button class="btn-xs btn-xs--ghost" onclick="showSection('my-orders',null)">View All</button>
        </div>
        <div id="ov-recent-orders" style="padding:12px 20px;"></div>
      </div>

      <!-- Notifications preview -->
      <div class="card">
        <div class="card__header">
          <span class="dash-section-title"><span class="material-symbols-outlined" style="color:var(--danger);font-size:1.2rem;">notifications</span> Notifications</span>
        </div>
        <div id="ov-notifs" style="padding:12px 20px;"></div>
      </div>
    </div>

    <!-- Quick order CTA -->
    <div class="card" style="background:linear-gradient(135deg,rgba(78,96,64,0.12),rgba(107,138,92,0.08));border:1px solid rgba(78,96,64,0.2);">
      <div style="padding:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div>
          <div style="font-family:var(--font-serif);font-size:1.1rem;font-weight:700;color:var(--text);margin-bottom:4px;">Ready to order fresh dairy?</div>
          <div style="font-size:0.84rem;color:var(--muted);">Browse our products and place your order in seconds.</div>
        </div>
        <button class="btn btn--primary" onclick="showSection('place-order',null)" style="white-space:nowrap;">
          <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;">add_shopping_cart</span>
          Order Now
        </button>
      </div>
    </div>
  </div>

  <!-- ══ SECTION 2: PRODUCTS ═══════════════════════════════ -->
  <div class="cust-section" id="section-products">
    <div class="page-header">
      <div><h1 class="page-title">Our Products</h1><p class="page-subtitle">Fresh dairy products from Esperon Farm.</p></div>
    </div>
    <div class="products-grid" id="products-grid"></div>
  </div>

  <!-- ══ SECTION 3: PLACE ORDER ════════════════════════════ -->
  <div class="cust-section" id="section-place-order">
    <div class="page-header">
      <div><h1 class="page-title">Place an Order</h1><p class="page-subtitle">Select a product and confirm your order.</p></div>
    </div>

    <div class="two-col">
      <!-- Step 1: Select product -->
      <div class="card">
        <div class="card__header">
          <span class="dash-section-title"><span class="material-symbols-outlined" style="color:var(--olive);font-size:1.2rem;">storefront</span> Step 1 — Select Product</span>
        </div>
        <div id="order-products-grid" style="padding:16px 20px;display:grid;grid-template-columns:1fr 1fr;gap:10px;"></div>
      </div>

      <!-- Step 2: Order details -->
      <div class="card">
        <div class="card__header">
          <span class="dash-section-title"><span class="material-symbols-outlined" style="color:var(--olive);font-size:1.2rem;">edit_note</span> Step 2 — Order Details</span>
        </div>
        <div style="padding:16px 20px;">
          <div style="margin-bottom:14px;">
            <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;">Selected Product</label>
            <div id="selected-product-display" style="padding:10px 14px;background:rgba(232,217,197,0.3);border:1.5px solid var(--border-light);border-radius:10px;font-size:0.88rem;color:var(--muted);">No product selected</div>
          </div>
          <div style="margin-bottom:14px;">
            <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;">Delivery Date <span style="color:#c0392b;">*</span></label>
            <input type="date" id="order-date" style="width:100%;padding:10px 14px;border:1.5px solid var(--border-light);border-radius:10px;font-size:0.9rem;font-family:var(--font-sans);color:var(--text);background:rgba(255,255,255,0.7);outline:none;box-sizing:border-box;" />
          </div>
          <div style="margin-bottom:14px;">
            <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;">Payment Method</label>
            <select id="payment-method" style="width:100%;padding:10px 14px;border:1.5px solid var(--border-light);border-radius:10px;font-size:0.9rem;font-family:var(--font-sans);color:var(--text);background:rgba(255,255,255,0.7);outline:none;box-sizing:border-box;">
              <option value="cod">Cash on Delivery</option>
              <option value="gcash">GCash</option>
              <option value="bank">Bank Transfer</option>
            </select>
          </div>
          <!-- Order summary -->
          <div id="order-summary" style="display:none;background:rgba(232,240,224,0.5);border:1px solid rgba(78,96,64,0.2);border-radius:10px;padding:12px 14px;margin-bottom:14px;font-size:0.84rem;">
            <div style="font-weight:700;color:var(--olive-dark);margin-bottom:6px;">Order Summary</div>
            <div id="summary-product" style="color:var(--text);margin-bottom:3px;"></div>
            <div id="summary-date"    style="color:var(--muted);font-size:0.78rem;"></div>
            <div id="summary-payment" style="color:var(--muted);font-size:0.78rem;"></div>
          </div>
          <div id="order-err" style="display:none;color:#c0392b;font-size:0.78rem;margin-bottom:10px;"></div>
          <button class="btn btn--primary" id="place-order-btn" style="width:100%;">
            <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;">check_circle</span>
            Confirm Order
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ══ SECTION 4: MY ORDERS ══════════════════════════════ -->
  <div class="cust-section" id="section-my-orders">
    <div class="page-header">
      <div><h1 class="page-title">My Orders</h1><p class="page-subtitle">Track and view your order history.</p></div>
    </div>
    <!-- Filter tabs -->
    <div style="display:flex;gap:8px;margin-bottom:var(--spacing-lg);flex-wrap:wrap;">
      <button class="btn-xs btn-xs--ghost" onclick="filterMyOrders('all',this)" style="background:rgba(78,96,64,0.12);border-color:var(--olive);color:var(--olive-dark);">All</button>
      <button class="btn-xs btn-xs--ghost" onclick="filterMyOrders('pending',this)">Pending</button>
      <button class="btn-xs btn-xs--ghost" onclick="filterMyOrders('processing',this)">Processing</button>
      <button class="btn-xs btn-xs--ghost" onclick="filterMyOrders('delivered',this)">Delivered</button>
    </div>
    <div id="my-orders-list"></div>
  </div>

  <!-- ══ SECTION 5: NOTIFICATIONS ══════════════════════════ -->
  <div class="cust-section" id="section-notifications">
    <div class="page-header">
      <div><h1 class="page-title">Notifications</h1><p class="page-subtitle">Updates and announcements from Esperon Farm.</p></div>
    </div>
    <div class="card">
      <div style="padding:16px 20px;" id="notifs-full"></div>
    </div>
  </div>

  <!-- ══ SECTION 6: PROFILE ════════════════════════════════ -->
  <div class="cust-section" id="section-profile">
    <div class="page-header">
      <div><h1 class="page-title">My Profile</h1><p class="page-subtitle">View and update your personal information.</p></div>
    </div>
    <div class="two-col">
      <div class="card">
        <div class="card__header">
          <span class="dash-section-title"><span class="material-symbols-outlined" style="color:var(--olive);font-size:1.2rem;">person</span> Personal Information</span>
        </div>
        <div style="padding:20px;">
          <form class="profile-form" id="profile-form">
            <div class="form-row">
              <div class="form-group">
                <label>Full Name</label>
                <input type="text" id="pf-name" placeholder="Your name" />
              </div>
              <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" id="pf-phone" placeholder="09012345678" />
              </div>
            </div>
            <div class="form-group" style="margin-bottom:14px;">
              <label>Email Address</label>
              <input type="email" id="pf-email" disabled style="background:rgba(232,217,197,0.3);color:var(--muted);" />
            </div>
            <div class="form-group" style="margin-bottom:16px;">
              <label>Delivery Address</label>
              <input type="text" id="pf-address" placeholder="Your delivery address" />
            </div>
            <div id="pf-msg" style="display:none;padding:8px 12px;border-radius:8px;font-size:0.82rem;margin-bottom:12px;"></div>
            <button type="submit" class="btn btn--primary">
              <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;">save</span>
              Save Changes
            </button>
          </form>
        </div>
      </div>

      <!-- Account info -->
      <div class="card">
        <div class="card__header">
          <span class="dash-section-title"><span class="material-symbols-outlined" style="color:var(--olive);font-size:1.2rem;">info</span> Account Info</span>
        </div>
        <div style="padding:20px;">
          <div style="margin-bottom:14px;">
            <div style="font-size:0.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;">Customer ID</div>
            <div id="pf-cid" style="font-family:var(--font-serif);font-size:1.4rem;font-weight:700;color:var(--text);">#—</div>
          </div>
          <div style="margin-bottom:14px;">
            <div style="font-size:0.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;">Member Since</div>
            <div style="font-size:0.9rem;color:var(--text);">Esperon Dairy Farm</div>
          </div>
          <div style="background:var(--success-lt);border:1px solid rgba(78,96,64,0.2);border-radius:10px;padding:12px 14px;font-size:0.82rem;color:var(--olive-dark);">
            <span class="material-symbols-outlined" style="font-size:0.9rem;vertical-align:middle;margin-right:4px;">verified</span>
            Verified Customer Account
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ══ SECTION 7: FEEDBACK ═══════════════════════════════ -->
  <div class="cust-section" id="section-feedback">
    <div class="page-header">
      <div><h1 class="page-title">Feedback &amp; Reviews</h1><p class="page-subtitle">Help us improve by sharing your experience.</p></div>
    </div>
    <div class="two-col">
      <div class="card">
        <div class="card__header">
          <span class="dash-section-title"><span class="material-symbols-outlined" style="color:var(--gold);font-size:1.2rem;">star_rate</span> Rate Our Service</span>
        </div>
        <div style="padding:20px;">
          <div style="margin-bottom:16px;">
            <div style="font-size:0.84rem;color:var(--text-light);margin-bottom:8px;">How would you rate your overall experience?</div>
            <div class="star-rating" id="star-rating">
              <span onclick="setRating(1)" data-val="1">&#9733;</span>
              <span onclick="setRating(2)" data-val="2">&#9733;</span>
              <span onclick="setRating(3)" data-val="3">&#9733;</span>
              <span onclick="setRating(4)" data-val="4">&#9733;</span>
              <span onclick="setRating(5)" data-val="5">&#9733;</span>
            </div>
            <div id="rating-label" style="font-size:0.78rem;color:var(--muted);">Click a star to rate</div>
          </div>
          <div style="margin-bottom:14px;">
            <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;">Your Comments</label>
            <textarea id="feedback-text" rows="4" placeholder="Tell us about your experience, product quality, delivery, or any suggestions…" style="width:100%;padding:10px 14px;border:1.5px solid var(--border-light);border-radius:10px;font-size:0.88rem;font-family:var(--font-sans);color:var(--text);background:rgba(255,255,255,0.7);outline:none;resize:vertical;box-sizing:border-box;"></textarea>
          </div>
          <button class="btn btn--primary" id="submit-feedback-btn">
            <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;">send</span>
            Submit Feedback
          </button>
        </div>
      </div>

      <!-- Previous feedback -->
      <div class="card">
        <div class="card__header">
          <span class="dash-section-title"><span class="material-symbols-outlined" style="color:var(--olive);font-size:1.2rem;">history</span> Your Previous Feedback</span>
        </div>
        <div id="feedback-history" style="padding:16px 20px;"></div>
      </div>
    </div>
  </div>

</main>

<script src="js/customer_dashboard.js"></script>
</body>
</html>

