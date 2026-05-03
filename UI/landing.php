<?php
// ============================================================
// UI/landing.php — Public landing page
// ============================================================
session_start();
if (isset($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? 'Staff';
    if ($role === 'Admin')    { header('Location: dashboard_admin.php');    exit; }
    if ($role === 'Customer') { header('Location: dashboard_customer.php'); exit; }
    header('Location: dashboard_staff.php'); exit;
}

$stats = ['products' => 0, 'cows' => 0, 'orders' => 0, 'customers' => 0];
$products = [];
try {
    require_once __DIR__ . '/../dairy_farm_backend/config/database.php';
    $db = getConnection();
    $stats['products']  = $db->query("SELECT COUNT(*) FROM Products WHERE is_active = 1")->fetchColumn();
    $stats['cows']      = $db->query("SELECT COUNT(*) FROM Cow WHERE is_active = 1")->fetchColumn();
    $stats['orders']    = $db->query("SELECT COUNT(*) FROM Orders")->fetchColumn();
    $stats['customers'] = $db->query("SELECT COUNT(*) FROM Customer")->fetchColumn();
    $stmt = $db->query("SELECT name, description, price, unit, stock_qty FROM Products WHERE is_active = 1 AND stock_qty > 0 ORDER BY product_id ASC LIMIT 6");
    $products = $stmt->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Esperon Dairy Farm</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400&family=Lato:wght@300;400;600;700&display=swap" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --maroon:     #7a1f2e;
      --maroon-dk:  #5a1520;
      --olive:      #4e6040;
      --olive-lt:   #6b8a5c;
      --cream:      #e8dcc8;
      --cream-lt:   #f2ebe0;
      --beige:      #d4c9b0;
      --text:       #2a1f15;
      --muted:      #8a7f72;
      --font-serif: 'Playfair Display', serif;
      --font-sans:  'Lato', sans-serif;
    }
    html { scroll-behavior: smooth; }
    body {
      font-family: var(--font-sans);
      background: var(--cream-lt);
      color: var(--text);
      overflow-x: hidden;
      min-height: 100vh;
    }

    /* ── HERO PAGE ── */
    .hero-page {
      min-height: 100vh;
      background: var(--cream-lt);
      position: relative;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    /* Faded background image — very low opacity */
    .hero-page::before {
      content: '';
      position: absolute;
      inset: 0;
      background: url('assets/bg.png') no-repeat center center / cover;
      opacity: 0.18;
      filter: grayscale(30%);
      z-index: 0;
    }

    /* ── NAV ── */
    .lp-nav {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 28px 5vw 0;
      position: relative;
      z-index: 10;
    }
    .lp-nav__brand {
      display: flex; flex-direction: column; align-items: flex-start;
      text-decoration: none;
    }
    .lp-nav__logo-row {
      display: flex; align-items: center; gap: 10px;
    }
    .lp-nav__cow-img {
      width: 52px; height: 40px; object-fit: contain;
    }
    .lp-nav__esperon {
      font-family: var(--font-serif);
      font-size: 2.8rem;
      font-weight: 900;
      color: var(--maroon);
      line-height: 1;
      letter-spacing: -0.02em;
    }
    .lp-nav__dairy {
      font-family: var(--font-serif);
      font-size: 0.95rem;
      font-weight: 400;
      color: var(--maroon);
      letter-spacing: 0.35em;
      text-transform: uppercase;
      margin-top: 2px;
      padding-left: 4px;
    }

    .lp-nav__links {
      display: flex; align-items: center; gap: 4px;
      background: rgba(255,255,255,0.25);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(255,255,255,0.4);
      border-radius: 50px;
      padding: 6px 8px;
    }
    .lp-nav__link {
      text-decoration: none;
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--text);
      padding: 8px 18px;
      border-radius: 50px;
      transition: all .15s;
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }
    .lp-nav__link:hover { color: var(--maroon); }
    .lp-nav__link--active {
      background: var(--text);
      color: #fff !important;
    }
    .lp-nav__link--active:hover { background: var(--maroon); }

    /* ── HERO BODY ── */
    .hero-body {
      flex: 1;
      display: grid;
      grid-template-columns: 1fr 1.15fr;
      gap: 0;
      align-items: center;
      padding: 50px 5vw 70px;
      position: relative;
      z-index: 1;
      min-height: calc(100vh - 90px);
    }

    /* Left side */
    .hero-left {
      padding-right: 40px;
      display: flex;
      flex-direction: column;
      gap: 22px;
    }
    .hero-tagline {
      font-size: 0.88rem;
      color: #5a4f45;
      line-height: 1.7;
      max-width: 300px;
    }
    .hero-explore {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--olive);
      color: #fff;
      border: none;
      border-radius: 50px;
      padding: 12px 26px;
      font-family: var(--font-sans);
      font-size: 0.88rem;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: all .15s;
      width: fit-content;
    }
    .hero-explore:hover {
      background: var(--olive-lt);
      transform: translateY(-1px);
      box-shadow: 0 4px 16px rgba(78,96,64,0.3);
    }

    /* Right side — big rounded image card */
    .hero-right {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: flex-end;
    }
    .hero-img-wrap {
      position: relative;
      width: 100%;
      max-width: 560px;
    }
    .hero-img-main {
      width: 100%;
      height: 420px;
      object-fit: cover;
      /* Top-left corner very rounded, bottom-left very rounded, right side less rounded */
      border-radius: 36px 36px 36px 100px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.22);
      display: block;
    }
    /* Decorative slash lines bottom-right */
    .hero-slash {
      position: absolute;
      bottom: -24px;
      right: -24px;
      display: flex;
      flex-direction: column;
      gap: 9px;
      opacity: 0.4;
    }
    .hero-slash span {
      display: block;
      height: 3px;
      background: var(--beige);
      border-radius: 2px;
      transform: rotate(-35deg);
    }
    .hero-slash span:nth-child(1) { width: 55px; }
    .hero-slash span:nth-child(2) { width: 75px; margin-left: 12px; }
    .hero-slash span:nth-child(3) { width: 45px; margin-left: 22px; }

    /* ── SECTIONS ── */
    .lp-section { padding: 80px 5vw; }
    .lp-section--alt { background: rgba(232,220,200,0.3); }
    .lp-section__head { text-align: center; margin-bottom: 52px; }
    .lp-section__tag  { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .14em; color: var(--olive); margin-bottom: 10px; }
    .lp-section__h2   { font-family: var(--font-serif); font-size: clamp(1.6rem,3vw,2.4rem); font-weight: 700; color: var(--text); margin-bottom: 12px; }
    .lp-section__desc { font-size: 0.95rem; color: var(--muted); max-width: 560px; margin: 0 auto; line-height: 1.65; }

    /* ── FEATURES ── */
    .lp-features { display: grid; grid-template-columns: repeat(auto-fit,minmax(240px,1fr)); gap: 20px; max-width: 1100px; margin: 0 auto; }
    .lp-feat-card {
      background: rgba(255,255,255,0.7);
      border: 1px solid rgba(212,201,176,0.6);
      border-radius: 20px;
      padding: 26px 22px;
      transition: all .2s;
      backdrop-filter: blur(8px);
    }
    .lp-feat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,0.08); }
    .lp-feat-icon { width: 46px; height: 46px; border-radius: 12px; background: rgba(122,31,46,0.08); display: flex; align-items: center; justify-content: center; margin-bottom: 14px; }
    .lp-feat-icon .material-symbols-outlined { font-size: 1.4rem; color: var(--maroon); }
    .lp-feat-card h3 { font-family: var(--font-serif); font-size: 1rem; font-weight: 700; color: var(--text); margin-bottom: 8px; }
    .lp-feat-card p  { font-size: 0.84rem; color: var(--muted); line-height: 1.6; }

    /* ── STATS ── */
    .lp-stats {
      background: linear-gradient(135deg, var(--maroon-dk), var(--maroon));
      padding: 56px 5vw;
    }
    .lp-stats__inner { display: grid; grid-template-columns: repeat(auto-fit,minmax(160px,1fr)); gap: 32px; max-width: 1000px; margin: 0 auto; text-align: center; }
    .lp-stats__val   { font-family: var(--font-serif); font-size: 2.8rem; font-weight: 700; color: #fff; line-height: 1; }
    .lp-stats__lbl   { font-size: 0.75rem; color: rgba(255,255,255,0.65); text-transform: uppercase; letter-spacing: .08em; margin-top: 6px; }

    /* ── PRODUCTS ── */
    .lp-products { display: grid; grid-template-columns: repeat(auto-fill,minmax(260px,1fr)); gap: 18px; max-width: 1100px; margin: 0 auto; }
    .lp-prod-card {
      background: rgba(255,255,255,0.8);
      border: 1px solid rgba(212,201,176,0.6);
      border-radius: 18px;
      padding: 22px 20px;
      display: flex; flex-direction: column; gap: 10px;
      transition: all .2s;
    }
    .lp-prod-card:hover { transform: translateY(-3px); box-shadow: 0 10px 32px rgba(0,0,0,0.07); }
    .lp-prod-emoji { font-size: 2rem; text-align: center; }
    .lp-prod-name  { font-weight: 700; font-size: 0.95rem; color: var(--text); }
    .lp-prod-desc  { font-size: 0.82rem; color: var(--muted); line-height: 1.5; flex: 1; }
    .lp-prod-foot  { display: flex; align-items: center; justify-content: space-between; margin-top: 4px; }
    .lp-prod-price { font-family: var(--font-serif); font-size: 1.1rem; font-weight: 700; color: var(--maroon); }
    .lp-prod-unit  { font-size: 0.72rem; color: var(--muted); }
    .lp-prod-stock { font-size: 0.72rem; color: var(--olive); font-weight: 600; background: rgba(78,96,64,0.1); padding: 3px 8px; border-radius: 20px; }

    /* ── CTA ── */
    .lp-cta { background: var(--cream); padding: 80px 5vw; text-align: center; }
    .lp-cta__inner { max-width: 580px; margin: 0 auto; }
    .lp-cta h2 { font-family: var(--font-serif); font-size: clamp(1.6rem,3vw,2.2rem); font-weight: 700; color: var(--text); margin-bottom: 14px; }
    .lp-cta p  { font-size: 0.95rem; color: var(--muted); line-height: 1.65; margin-bottom: 32px; }
    .lp-cta__btns { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
    .lp-btn { padding: 11px 26px; border-radius: 50px; font-family: var(--font-sans); font-size: 0.88rem; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .15s; display: inline-flex; align-items: center; gap: 6px; }
    .lp-btn--solid { background: var(--maroon); border: none; color: #fff; box-shadow: 0 2px 8px rgba(122,31,46,0.25); }
    .lp-btn--solid:hover { background: var(--maroon-dk); transform: translateY(-1px); }
    .lp-btn--ghost { background: transparent; border: 1.5px solid var(--maroon); color: var(--maroon); }
    .lp-btn--ghost:hover { background: rgba(122,31,46,0.06); }

    /* ── FOOTER ── */
    .lp-footer { background: var(--maroon-dk); color: rgba(255,255,255,0.55); padding: 28px 5vw; text-align: center; font-size: 0.82rem; }
    .lp-footer a { color: rgba(255,255,255,0.75); text-decoration: none; }
    .lp-footer a:hover { color: #fff; }

    /* ── MOBILE ── */
    @media (max-width: 750px) {
      .hero-body { grid-template-columns: 1fr; padding: 30px 5vw 50px; }
      .hero-left { padding-right: 0; }
      .hero-right { margin-top: 30px; }
      .lp-nav__links { display: none; }
      .lp-nav__esperon { font-size: 2rem; }
    }
  </style>
</head>
<body>

<!-- ══════════════════════════════════════════════
     HERO PAGE
══════════════════════════════════════════════ -->
<div class="hero-page">

  <!-- NAV -->
  <nav class="lp-nav">
    <a href="landing.php" class="lp-nav__brand">
      <div class="lp-nav__logo-row">
        <img src="assets/logo.jpg" alt="Esperon cow logo" class="lp-nav__cow-img"
             onerror="this.style.display='none'" />
        <span class="lp-nav__esperon">ESPERON</span>
      </div>
      <div class="lp-nav__dairy">Dairy</div>
    </a>

    <div class="lp-nav__links">
      <a href="login.php"   class="lp-nav__link">Sign In</a>
      <a href="#features"   class="lp-nav__link">Services</a>
      <a href="#products"   class="lp-nav__link">Products</a>
      <a href="#contact"    class="lp-nav__link lp-nav__link--active">Contact</a>
    </div>
  </nav>

  <!-- HERO BODY -->
  <div class="hero-body">

    <!-- Left: brand + tagline + CTA -->
    <div class="hero-left">
      <p class="hero-tagline">
        The bridge that leads you to the gateway of the
        Dairy Industry abroad, so sign up now and start
        your journey today!
      </p>
      <a href="#features" class="hero-explore">Explore more</a>
    </div>

    <!-- Right: big cow image -->
    <div class="hero-right">
      <div class="hero-img-wrap">
        <img src="assets/bg.png" alt="Esperon Dairy Farm cows grazing" class="hero-img-main"
             onerror="this.style.background:'#c8b89a';this.alt=''" />
        <div class="hero-slash">
          <span></span><span></span><span></span>
        </div>
      </div>
    </div>

  </div>
</div><!-- /hero-page -->


<!-- ══════════════════════════════════════════════
     FEATURES / SERVICES
══════════════════════════════════════════════ -->
<section class="lp-section" id="features">
  <div class="lp-section__head">
    <div class="lp-section__tag">Our Services</div>
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


<!-- ══════════════════════════════════════════════
     STATS BAR
══════════════════════════════════════════════ -->
<div class="lp-stats">
  <div class="lp-stats__inner">
    <div>
      <div class="lp-stats__val"><?= $stats['cows'] ?></div>
      <div class="lp-stats__lbl">Active Cows</div>
    </div>
    <div>
      <div class="lp-stats__val"><?= $stats['products'] ?></div>
      <div class="lp-stats__lbl">Products</div>
    </div>
    <div>
      <div class="lp-stats__val"><?= $stats['orders'] ?></div>
      <div class="lp-stats__lbl">Total Orders</div>
    </div>
    <div>
      <div class="lp-stats__val"><?= $stats['customers'] ?></div>
      <div class="lp-stats__lbl">Customers</div>
    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════════
     PRODUCTS
══════════════════════════════════════════════ -->
<section class="lp-section lp-section--alt" id="products">
  <div class="lp-section__head">
    <div class="lp-section__tag">Fresh Products</div>
    <h2 class="lp-section__h2">From Our Farm to Your Table</h2>
    <p class="lp-section__desc">
      Browse our available dairy products.
      <a href="login.php"  style="color:var(--maroon);font-weight:700;">Log in</a> or
      <a href="signup.php" style="color:var(--maroon);font-weight:700;">create an account</a>
      to place an order.
    </p>
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


<!-- ══════════════════════════════════════════════
     CTA
══════════════════════════════════════════════ -->
<section class="lp-cta" id="contact">
  <div class="lp-cta__inner">
    <h2>Ready to Get Started?</h2>
    <p>Join Esperon Dairy Farm's management system. Staff and admins can log in directly. Customers can create an account to start ordering fresh dairy products.</p>
    <div class="lp-cta__btns">
      <a href="login.php"  class="lp-btn lp-btn--solid">
        <span class="material-symbols-outlined" style="font-size:1rem;">login</span> Sign In
      </a>
      <a href="signup.php" class="lp-btn lp-btn--ghost">
        <span class="material-symbols-outlined" style="font-size:1rem;">person_add</span> Create Account
      </a>
    </div>
  </div>
</section>


<!-- ══════════════════════════════════════════════
     FOOTER
══════════════════════════════════════════════ -->
<footer class="lp-footer">
  <p>
    &copy; 2026 Esperon Dairy Farm &mdash; Malaybalay City &nbsp;&middot;&nbsp;
    <a href="login.php">Sign In</a> &nbsp;&middot;&nbsp;
    <a href="signup.php">Sign Up</a>
  </p>
</footer>

</body>
</html>
