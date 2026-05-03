<?php
// ============================================================
// UI/landing.php — Public landing page
// Visible to everyone before logging in.
// ============================================================

// If already logged in, redirect to dashboard
session_start();
if (isset($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? 'Staff';
    if ($role === 'Admin')    { header('Location: dashboard_admin.php');    exit; }
    if ($role === 'Customer') { header('Location: dashboard_customer.php'); exit; }
    header('Location: dashboard_staff.php'); exit;
}

// Fetch public stats from DB (no auth required — read-only public data)
$stats = ['products' => 0, 'cows' => 0, 'orders' => 0, 'customers' => 0];
$products = [];
try {
    require_once __DIR__ . '/../dairy_farm_backend/config/database.php';
    $db = getConnection();
    $stats['products']  = $db->query("SELECT COUNT(*) FROM Products  WHERE is_active = 1")->fetchColumn();
    $stats['cows']      = $db->query("SELECT COUNT(*) FROM Cow       WHERE is_active = 1")->fetchColumn();
    $stats['orders']    = $db->query("SELECT COUNT(*) FROM Orders")->fetchColumn();
    $stats['customers'] = $db->query("SELECT COUNT(*) FROM Customer")->fetchColumn();
    $stmt = $db->query("SELECT name, description, price, unit, stock_qty FROM Products WHERE is_active = 1 AND stock_qty > 0 ORDER BY product_id ASC LIMIT 6");
    $products = $stmt->fetchAll();
} catch (Exception $e) { /* DB not ready yet — show zeros */ }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Esperon Dairy Farm — Management System</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;600;700&display=swap" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --olive:      #4e6040;
      --olive-dark: #2d3b22;
      --olive-lt:   #6b8a5c;
      --cream:      #f5ede0;
      --cream-lt:   #faf6f0;
      --maroon:     #7a1f2e;
      --text:       #2a1f15;
      --muted:      #8a7f72;
      --border:     #e8dfd2;
      --gold:       #c8963e;
      --font-serif: 'Playfair Display', serif;
      --font-sans:  'Lato', sans-serif;
    }
    html { scroll-behavior: smooth; }
    body { font-family: var(--font-sans); background: #fff; color: var(--text); overflow-x: hidden; }

    /* ── NAV ── */
    .lp-nav {
      position: fixed; top: 0; left: 0; right: 0; z-index: 100;
      background: rgba(255,255,255,0.92);
      backdrop-filter: blur(16px);
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 5vw; height: 64px;
    }
    .lp-nav__brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }
    .lp-nav__logo  { width: 38px; height: 38px; border-radius: 8px; object-fit: cover; }
    .lp-nav__name  { font-family: var(--font-serif); font-size: 1rem; font-weight: 700; color: var(--maroon); line-height: 1.2; }
    .lp-nav__sub   { font-size: 0.6rem; color: var(--muted); text-transform: uppercase; letter-spacing: .12em; }
    .lp-nav__links { display: flex; align-items: center; gap: 28px; }
    .lp-nav__link  { text-decoration: none; font-size: 0.88rem; font-weight: 600; color: var(--text); transition: color .15s; }
    .lp-nav__link:hover { color: var(--olive); }
    .lp-nav__btns  { display: flex; gap: 10px; }
    .lp-btn        { padding: 8px 20px; border-radius: 10px; font-family: var(--font-sans); font-size: 0.88rem; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .15s; display: inline-flex; align-items: center; gap: 6px; }
    .lp-btn--ghost { background: transparent; border: 1.5px solid var(--olive); color: var(--olive); }
    .lp-btn--ghost:hover { background: rgba(78,96,64,0.08); }
    .lp-btn--solid { background: linear-gradient(135deg,var(--olive),var(--olive-lt)); border: none; color: #fff; box-shadow: 0 2px 8px rgba(78,96,64,0.25); }
    .lp-btn--solid:hover { background: linear-gradient(135deg,var(--olive-dark),var(--olive)); transform: translateY(-1px); }

    /* ── HERO ── */
    .lp-hero {
      min-height: 100vh;
      background: url('assets/bg.png') no-repeat center center / cover;
      display: flex; align-items: center;
      padding: 80px 5vw 60px;
      position: relative;
    }
    .lp-hero::before {
      content: ''; position: absolute; inset: 0;
      background: linear-gradient(135deg, rgba(42,31,21,0.55) 0%, rgba(78,96,64,0.35) 100%);
    }
    .lp-hero__inner {
      position: relative; z-index: 1;
      display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center;
      max-width: 1100px; margin: 0 auto; width: 100%;
    }
    .lp-hero__tag  { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .12em; color: rgba(255,255,255,0.7); margin-bottom: 14px; }
    .lp-hero__h1   { font-family: var(--font-serif); font-size: clamp(2rem,4vw,3.2rem); font-weight: 700; color: #fff; line-height: 1.15; margin-bottom: 18px; }
    .lp-hero__h1 span { color: #f0d080; }
    .lp-hero__desc { font-size: 1rem; color: rgba(255,255,255,0.82); line-height: 1.65; margin-bottom: 32px; max-width: 480px; }
    .lp-hero__btns { display: flex; gap: 12px; flex-wrap: wrap; }
    .lp-hero__card {
      background: rgba(255,255,255,0.15);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,0.3);
      border-radius: 24px;
      padding: 36px 32px;
      text-align: center;
    }
    .lp-hero__card-logo { width: 90px; height: 90px; border-radius: 18px; object-fit: cover; margin: 0 auto 16px; display: block; box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
    .lp-hero__card-name { font-family: var(--font-serif); font-size: 1.5rem; font-weight: 700; color: #fff; margin-bottom: 6px; }
    .lp-hero__card-sub  { font-size: 0.78rem; color: rgba(255,255,255,0.7); text-transform: uppercase; letter-spacing: .1em; margin-bottom: 24px; }
    .lp-hero__stat-row  { display: grid; grid-template-columns: repeat(2,1fr); gap: 12px; }
    .lp-hero__stat      { background: rgba(255,255,255,0.12); border-radius: 12px; padding: 14px 10px; }
    .lp-hero__stat-val  { font-family: var(--font-serif); font-size: 1.8rem; font-weight: 700; color: #fff; line-height: 1; }
    .lp-hero__stat-lbl  { font-size: 0.68rem; color: rgba(255,255,255,0.65); text-transform: uppercase; letter-spacing: .06em; margin-top: 4px; }

    /* ── FEATURES ── */
    .lp-section { padding: 80px 5vw; }
    .lp-section--alt { background: var(--cream-lt); }
    .lp-section__head { text-align: center; margin-bottom: 52px; }
    .lp-section__tag  { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .12em; color: var(--olive); margin-bottom: 10px; }
    .lp-section__h2   { font-family: var(--font-serif); font-size: clamp(1.6rem,3vw,2.4rem); font-weight: 700; color: var(--text); margin-bottom: 12px; }
    .lp-section__desc { font-size: 0.95rem; color: var(--muted); max-width: 560px; margin: 0 auto; line-height: 1.65; }

    .lp-features { display: grid; grid-template-columns: repeat(auto-fit,minmax(240px,1fr)); gap: 24px; max-width: 1100px; margin: 0 auto; }
    .lp-feat-card {
      background: #fff; border: 1px solid var(--border); border-radius: 20px;
      padding: 28px 24px; transition: all .2s;
    }
    .lp-feat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,0.08); border-color: rgba(78,96,64,0.3); }
    .lp-feat-icon {
      width: 48px; height: 48px; border-radius: 12px;
      background: rgba(78,96,64,0.1); display: flex; align-items: center; justify-content: center;
      margin-bottom: 16px;
    }
    .lp-feat-icon .material-symbols-outlined { font-size: 1.5rem; color: var(--olive); }
    .lp-feat-card h3 { font-family: var(--font-serif); font-size: 1.05rem; font-weight: 700; color: var(--text); margin-bottom: 8px; }
    .lp-feat-card p  { font-size: 0.85rem; color: var(--muted); line-height: 1.6; }

    /* ── STATS BAR ── */
    .lp-stats {
      background: linear-gradient(135deg,var(--olive-dark),var(--olive));
      padding: 56px 5vw;
    }
    .lp-stats__inner { display: grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap: 32px; max-width: 1100px; margin: 0 auto; text-align: center; }
    .lp-stats__val   { font-family: var(--font-serif); font-size: 2.8rem; font-weight: 700; color: #fff; line-height: 1; }
    .lp-stats__lbl   { font-size: 0.78rem; color: rgba(255,255,255,0.7); text-transform: uppercase; letter-spacing: .08em; margin-top: 6px; }
    .lp-stats__divider { width: 1px; background: rgba(255,255,255,0.2); align-self: stretch; }

    /* ── PRODUCTS ── */
    .lp-products { display: grid; grid-template-columns: repeat(auto-fill,minmax(260px,1fr)); gap: 20px; max-width: 1100px; margin: 0 auto; }
    .lp-prod-card {
      background: #fff; border: 1px solid var(--border); border-radius: 18px;
      padding: 22px 20px; display: flex; flex-direction: column; gap: 10px;
      transition: all .2s;
    }
    .lp-prod-card:hover { transform: translateY(-3px); box-shadow: 0 10px 32px rgba(0,0,0,0.07); }
    .lp-prod-emoji { font-size: 2.2rem; text-align: center; }
    .lp-prod-name  { font-weight: 700; font-size: 0.95rem; color: var(--text); }
    .lp-prod-desc  { font-size: 0.82rem; color: var(--muted); line-height: 1.5; flex: 1; }
    .lp-prod-foot  { display: flex; align-items: center; justify-content: space-between; margin-top: 4px; }
    .lp-prod-price { font-family: var(--font-serif); font-size: 1.1rem; font-weight: 700; color: var(--olive-dark); }
    .lp-prod-unit  { font-size: 0.72rem; color: var(--muted); }
    .lp-prod-stock { font-size: 0.72rem; color: var(--olive); font-weight: 600; background: rgba(78,96,64,0.08); padding: 3px 8px; border-radius: 20px; }

    /* ── CTA ── */
    .lp-cta {
      background: var(--cream);
      padding: 80px 5vw;
      text-align: center;
    }
    .lp-cta__inner { max-width: 600px; margin: 0 auto; }
    .lp-cta h2    { font-family: var(--font-serif); font-size: clamp(1.6rem,3vw,2.2rem); font-weight: 700; color: var(--text); margin-bottom: 14px; }
    .lp-cta p     { font-size: 0.95rem; color: var(--muted); line-height: 1.65; margin-bottom: 32px; }
    .lp-cta__btns { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }

    /* ── FOOTER ── */
    .lp-footer {
      background: var(--olive-dark); color: rgba(255,255,255,0.6);
      padding: 32px 5vw; text-align: center; font-size: 0.82rem;
    }
    .lp-footer a { color: rgba(255,255,255,0.8); text-decoration: none; }
    .lp-footer a:hover { color: #fff; }

    /* ── MOBILE ── */
    @media (max-width: 750px) {
      .lp-nav__links { display: none; }
      .lp-hero__inner { grid-template-columns: 1fr; gap: 36px; }
      .lp-hero__card  { display: none; }
      .lp-hero { min-height: auto; padding: 100px 5vw 60px; }
      .lp-stats__divider { display: none; }
    }
  </style>
</head>
<body>

<!-- ── NAV ── -->
<nav class="lp-nav">
  <a href="landing.php" class="lp-nav__brand">
    <img src="assets/logo.jpg" alt="Logo" class="lp-nav__logo" onerror="this.style.display='none'">
    <div>
      <div class="lp-nav__name">Esperon Dairy Farm</div>
      <div class="lp-nav__sub">Management System</div>
    </div>
  </a>
  <div class="lp-nav__links">
    <a href="#features" class="lp-nav__link">Features</a>
    <a href="#overview" class="lp-nav__link">Overview</a>
    <a href="#products" class="lp-nav__link">Products</a>
    <a href="#contact"  class="lp-nav__link">Contact</a>
  </div>
  <div class="lp-nav__btns">
    <a href="login.php"  class="lp-btn lp-btn--ghost">Log In</a>
    <a href="signup.php" class="lp-btn lp-btn--solid">
      <span class="material-symbols-outlined" style="font-size:1rem;">person_add</span>
      Sign Up
    </a>
  </div>
</nav>

<!-- ── HERO ── -->
<section class="lp-hero">
  <div class="lp-hero__inner">
    <div>
      <div class="lp-hero__tag">Esperon Dairy Farm &mdash; Malaybalay City</div>
      <h1 class="lp-hero__h1">
        Farm Management<br>
        <span>Made Simple</span>
      </h1>
      <p class="lp-hero__desc">
        Track orders, monitor livestock health, manage staff, and serve customers —
        all from one integrated platform built for modern dairy farming.
      </p>
      <div class="lp-hero__btns">
        <a href="login.php"  class="lp-btn lp-btn--solid" style="font-size:0.95rem;padding:12px 28px;">
          <span class="material-symbols-outlined" style="font-size:1.1rem;">login</span> Log In
        </a>
        <a href="signup.php" class="lp-btn lp-btn--ghost" style="font-size:0.95rem;padding:12px 28px;border-color:rgba(255,255,255,0.6);color:#fff;">
          Create Account
        </a>
      </div>
    </div>
    <div class="lp-hero__card">
      <img src="assets/logo.jpg" alt="Esperon Logo" class="lp-hero__card-logo" onerror="this.style.display='none'">
      <div class="lp-hero__card-name">Esperon Dairy Farm</div>
      <div class="lp-hero__card-sub">Integrated Management System</div>
      <div class="lp-hero__stat-row">
        <div class="lp-hero__stat">
          <div class="lp-hero__stat-val"><?= $stats['cows'] ?></div>
          <div class="lp-hero__stat-lbl">Active Cows</div>
        </div>
        <div class="lp-hero__stat">
          <div class="lp-hero__stat-val"><?= $stats['products'] ?></div>
          <div class="lp-hero__stat-lbl">Products</div>
        </div>
        <div class="lp-hero__stat">
          <div class="lp-hero__stat-val"><?= $stats['orders'] ?></div>
          <div class="lp-hero__stat-lbl">Total Orders</div>
        </div>
        <div class="lp-hero__stat">
          <div class="lp-hero__stat-val"><?= $stats['customers'] ?></div>
          <div class="lp-hero__stat-lbl">Customers</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── FEATURES ── -->
<section class="lp-section" id="features">
  <div class="lp-section__head">
    <div class="lp-section__tag">What We Offer</div>
    <h2 class="lp-section__h2">Everything You Need to Run the Farm</h2>
    <p class="lp-section__desc">From livestock tracking to customer orders, our platform covers every aspect of dairy farm management.</p>
  </div>
  <div class="lp-features">
    <div class="lp-feat-card">
      <div class="lp-feat-icon"><span class="material-symbols-outlined">pets</span></div>
      <h3>Livestock Management</h3>
      <p>Track cow health, breed records, daily milk production, and get alerts for sick animals.</p>
    </div>
    <div class="lp-feat-card">
      <div class="lp-feat-icon"><span class="material-symbols-outlined">receipt_long</span></div>
      <h3>Order Tracking</h3>
      <p>Manage milk delivery orders from creation to delivery with real-time status updates.</p>
    </div>
    <div class="lp-feat-card">
      <div class="lp-feat-icon"><span class="material-symbols-outlined">storefront</span></div>
      <h3>Online Shop</h3>
      <p>Customers can browse and purchase dairy products directly through the customer portal.</p>
    </div>
    <div class="lp-feat-card">
      <div class="lp-feat-icon"><span class="material-symbols-outlined">badge</span></div>
      <h3>Staff Management</h3>
      <p>Manage worker accounts, track online status, assign reminders, and review daily reports.</p>
    </div>
    <div class="lp-feat-card">
      <div class="lp-feat-icon"><span class="material-symbols-outlined">inventory_2</span></div>
      <h3>Inventory Control</h3>
      <p>Monitor feed, milk stock, and supply levels with low-stock alerts and restock tracking.</p>
    </div>
    <div class="lp-feat-card">
      <div class="lp-feat-icon"><span class="material-symbols-outlined">bar_chart</span></div>
      <h3>Reports &amp; Analytics</h3>
      <p>View production trends, order summaries, and staff activity reports in one place.</p>
    </div>
  </div>
</section>

<!-- ── STATS BAR ── -->
<section class="lp-stats" id="overview">
  <div class="lp-stats__inner">
    <div>
      <div class="lp-stats__val"><?= $stats['cows'] ?></div>
      <div class="lp-stats__lbl">Active Cows</div>
    </div>
    <div class="lp-stats__divider"></div>
    <div>
      <div class="lp-stats__val"><?= $stats['products'] ?></div>
      <div class="lp-stats__lbl">Products Available</div>
    </div>
    <div class="lp-stats__divider"></div>
    <div>
      <div class="lp-stats__val"><?= $stats['orders'] ?></div>
      <div class="lp-stats__lbl">Total Orders</div>
    </div>
    <div class="lp-stats__divider"></div>
    <div>
      <div class="lp-stats__val"><?= $stats['customers'] ?></div>
      <div class="lp-stats__lbl">Registered Customers</div>
    </div>
  </div>
</section>

<!-- ── PRODUCTS ── -->
<section class="lp-section lp-section--alt" id="products">
  <div class="lp-section__head">
    <div class="lp-section__tag">Our Products</div>
    <h2 class="lp-section__h2">Fresh from the Farm</h2>
    <p class="lp-section__desc">Browse our available dairy products. <a href="login.php" style="color:var(--olive);font-weight:700;">Log in</a> or <a href="signup.php" style="color:var(--olive);font-weight:700;">create an account</a> to place an order.</p>
  </div>
  <?php if (empty($products)): ?>
    <p style="text-align:center;color:var(--muted);font-size:0.9rem;">No products available at the moment.</p>
  <?php else: ?>
  <div class="lp-products">
    <?php
    $emojiMap = ['milk'=>'🥛','cheese'=>'🧀','butter'=>'🧈','yogurt'=>'🍦','cream'=>'🍨','skim'=>'🥛','mozzarella'=>'🧀'];
    foreach ($products as $p):
      $emoji = '🛒';
      foreach ($emojiMap as $k => $e) { if (stripos($p['name'], $k) !== false) { $emoji = $e; break; } }
    ?>
    <div class="lp-prod-card">
      <div class="lp-prod-emoji"><?= $emoji ?></div>
      <div class="lp-prod-name"><?= htmlspecialchars($p['name']) ?></div>
      <?php if ($p['description']): ?>
      <div class="lp-prod-desc"><?= htmlspecialchars($p['description']) ?></div>
      <?php endif; ?>
      <div class="lp-prod-foot">
        <div>
          <div class="lp-prod-price">&#8369;<?= number_format($p['price'], 2) ?></div>
          <div class="lp-prod-unit">per <?= htmlspecialchars($p['unit']) ?></div>
        </div>
        <div class="lp-prod-stock"><?= $p['stock_qty'] ?> in stock</div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<!-- ── CTA ── -->
<section class="lp-cta" id="contact">
  <div class="lp-cta__inner">
    <h2>Ready to Get Started?</h2>
    <p>Join Esperon Dairy Farm's management system. Staff and admins can log in directly. Customers can create an account to start ordering fresh dairy products.</p>
    <div class="lp-cta__btns">
      <a href="login.php"  class="lp-btn lp-btn--solid" style="font-size:0.95rem;padding:12px 28px;">
        <span class="material-symbols-outlined" style="font-size:1.1rem;">login</span> Log In
      </a>
      <a href="signup.php" class="lp-btn lp-btn--ghost" style="font-size:0.95rem;padding:12px 28px;">
        <span class="material-symbols-outlined" style="font-size:1.1rem;">person_add</span> Create Account
      </a>
    </div>
  </div>
</section>

<!-- ── FOOTER ── */
<footer class="lp-footer">
  <p>
    &copy; 2026 Esperon Dairy Farm &mdash; Malaybalay City &nbsp;&middot;&nbsp;
    <a href="login.php">Staff Login</a> &nbsp;&middot;&nbsp;
    <a href="signup.php">Sign Up</a>
  </p>
</footer>

</body>
</html>
