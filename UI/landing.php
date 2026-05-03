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
      --olive:      #3d5230;
      --olive-lt:   #5a7a45;
      --cream:      #ede3d0;
      --cream-lt:   #f5f0e8;
      --beige:      #c8b898;
      --text:       #2a1f15;
      --muted:      #7a6f62;
      --font-serif: 'Playfair Display', serif;
      --font-sans:  'Lato', sans-serif;
    }
    html { scroll-behavior: smooth; }
    body { font-family: var(--font-sans); background: var(--cream-lt); color: var(--text); overflow-x: hidden; }

    /* ══════════════════════════════════════
       HERO — full viewport height
    ══════════════════════════════════════ */
    .hero {
      position: relative;
      width: 100%;
      height: 100vh;
      min-height: 600px;
      overflow: hidden;
      background: var(--cream);
    }

    /* Full-bleed faded background */
    .hero__bg {
      position: absolute;
      inset: 0;
      background: url('assets/bg.png') no-repeat center center / cover;
      opacity: 0.12;
      filter: saturate(0.4);
      z-index: 0;
    }

    /* ── NAV ── */
    .hero__nav {
      position: absolute;
      top: 0; left: 0; right: 0;
      z-index: 20;
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      padding: 32px 48px 0;
    }

    /* Brand block — top left */
    .brand {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      text-decoration: none;
      gap: 0;
    }
    .brand__top {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .brand__logo {
      width: 54px;
      height: 54px;
      border-radius: 10px;
      object-fit: cover;
      border: 2px solid rgba(122,31,46,0.2);
      box-shadow: 0 2px 12px rgba(0,0,0,0.12);
    }
    .brand__name {
      font-family: var(--font-serif);
      font-size: 3.2rem;
      font-weight: 900;
      color: var(--maroon);
      line-height: 1;
      letter-spacing: -0.01em;
    }
    .brand__sub {
      font-family: var(--font-serif);
      font-size: 0.9rem;
      font-weight: 400;
      color: var(--maroon);
      letter-spacing: 0.4em;
      text-transform: uppercase;
      margin-top: 4px;
      padding-left: 2px;
    }

    /* Nav links — top right pill */
    .nav__pill {
      display: flex;
      align-items: center;
      gap: 2px;
      background: rgba(255,255,255,0.55);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255,255,255,0.7);
      border-radius: 50px;
      padding: 5px 6px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    .nav__link {
      text-decoration: none;
      font-size: 0.78rem;
      font-weight: 700;
      color: var(--text);
      padding: 9px 20px;
      border-radius: 50px;
      transition: all .15s;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      white-space: nowrap;
    }
    .nav__link:hover { color: var(--maroon); background: rgba(122,31,46,0.06); }
    .nav__link--cta {
      background: var(--text);
      color: #fff !important;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    .nav__link--cta:hover { background: var(--maroon) !important; }

    /* ── HERO CONTENT ── */
    .hero__content {
      position: absolute;
      inset: 0;
      z-index: 10;
      display: grid;
      grid-template-columns: 1fr 1.05fr;
      align-items: center;
      padding: 0 48px;
      gap: 40px;
    }

    /* Left: tagline + CTA */
    .hero__left {
      display: flex;
      flex-direction: column;
      gap: 20px;
      padding-top: 60px; /* push below nav */
    }
    .hero__tagline {
      font-size: 0.92rem;
      color: var(--muted);
      line-height: 1.75;
      max-width: 300px;
    }
    .hero__cta {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--olive);
      color: #fff;
      border: none;
      border-radius: 50px;
      padding: 13px 28px;
      font-family: var(--font-sans);
      font-size: 0.9rem;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      transition: all .18s;
      width: fit-content;
      box-shadow: 0 4px 16px rgba(61,82,48,0.3);
    }
    .hero__cta:hover {
      background: var(--olive-lt);
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(61,82,48,0.35);
    }

    /* Right: rounded image card */
    .hero__right {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      padding-top: 60px;
    }
    .hero__img-card {
      position: relative;
      width: 100%;
      max-width: 520px;
    }
    .hero__img {
      width: 100%;
      height: 400px;
      object-fit: cover;
      /* Asymmetric border-radius: big on top-left and bottom-left, smaller on right */
      border-radius: 32px 32px 32px 110px;
      box-shadow:
        0 24px 60px rgba(0,0,0,0.22),
        0 4px 16px rgba(0,0,0,0.1);
      display: block;
    }
    /* Decorative diagonal lines */
    .hero__deco {
      position: absolute;
      bottom: -28px;
      right: -28px;
      display: flex;
      flex-direction: column;
      gap: 10px;
      transform: rotate(0deg);
    }
    .hero__deco span {
      display: block;
      height: 3.5px;
      background: var(--beige);
      border-radius: 3px;
      transform: rotate(-32deg);
      opacity: 0.6;
    }
    .hero__deco span:nth-child(1) { width: 50px; }
    .hero__deco span:nth-child(2) { width: 70px; margin-left: 14px; }
    .hero__deco span:nth-child(3) { width: 42px; margin-left: 26px; }

    /* ══════════════════════════════════════
       SECTIONS BELOW HERO
    ══════════════════════════════════════ */
    .section { padding: 80px 48px; }
    .section--alt { background: rgba(232,220,200,0.25); }
    .section__head { text-align: center; margin-bottom: 52px; }
    .section__tag  { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .16em; color: var(--olive); margin-bottom: 10px; }
    .section__h2   { font-family: var(--font-serif); font-size: clamp(1.6rem,3vw,2.3rem); font-weight: 700; color: var(--text); margin-bottom: 12px; }
    .section__desc { font-size: 0.92rem; color: var(--muted); max-width: 540px; margin: 0 auto; line-height: 1.7; }

    /* Features grid */
    .features { display: grid; grid-template-columns: repeat(auto-fit,minmax(240px,1fr)); gap: 20px; max-width: 1100px; margin: 0 auto; }
    .feat {
      background: rgba(255,255,255,0.75);
      border: 1px solid rgba(200,184,152,0.5);
      border-radius: 20px;
      padding: 26px 22px;
      transition: all .2s;
    }
    .feat:hover { transform: translateY(-4px); box-shadow: 0 14px 40px rgba(0,0,0,0.09); border-color: rgba(122,31,46,0.2); }
    .feat__icon { width: 46px; height: 46px; border-radius: 12px; background: rgba(122,31,46,0.08); display: flex; align-items: center; justify-content: center; margin-bottom: 14px; }
    .feat__icon .material-symbols-outlined { font-size: 1.4rem; color: var(--maroon); }
    .feat h3 { font-family: var(--font-serif); font-size: 1rem; font-weight: 700; color: var(--text); margin-bottom: 8px; }
    .feat p  { font-size: 0.84rem; color: var(--muted); line-height: 1.65; }

    /* Stats bar */
    .stats-bar {
      background: linear-gradient(135deg, var(--maroon-dk) 0%, var(--maroon) 100%);
      padding: 56px 48px;
    }
    .stats-bar__inner { display: grid; grid-template-columns: repeat(auto-fit,minmax(160px,1fr)); gap: 32px; max-width: 1000px; margin: 0 auto; text-align: center; }
    .stat__val { font-family: var(--font-serif); font-size: 3rem; font-weight: 700; color: #fff; line-height: 1; }
    .stat__lbl { font-size: 0.72rem; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: .1em; margin-top: 8px; }

    /* Products */
    .products { display: grid; grid-template-columns: repeat(auto-fill,minmax(260px,1fr)); gap: 18px; max-width: 1100px; margin: 0 auto; }
    .prod {
      background: rgba(255,255,255,0.85);
      border: 1px solid rgba(200,184,152,0.5);
      border-radius: 18px;
      padding: 22px 20px;
      display: flex; flex-direction: column; gap: 10px;
      transition: all .2s;
    }
    .prod:hover { transform: translateY(-3px); box-shadow: 0 10px 32px rgba(0,0,0,0.08); }
    .prod__emoji { font-size: 2rem; text-align: center; }
    .prod__name  { font-weight: 700; font-size: 0.95rem; color: var(--text); }
    .prod__desc  { font-size: 0.82rem; color: var(--muted); line-height: 1.55; flex: 1; }
    .prod__foot  { display: flex; align-items: center; justify-content: space-between; margin-top: 4px; }
    .prod__price { font-family: var(--font-serif); font-size: 1.1rem; font-weight: 700; color: var(--maroon); }
    .prod__unit  { font-size: 0.7rem; color: var(--muted); }
    .prod__stock { font-size: 0.7rem; color: var(--olive); font-weight: 700; background: rgba(61,82,48,0.1); padding: 3px 9px; border-radius: 20px; }

    /* CTA */
    .cta-section { background: var(--cream); padding: 80px 48px; text-align: center; }
    .cta-section__inner { max-width: 560px; margin: 0 auto; }
    .cta-section h2 { font-family: var(--font-serif); font-size: clamp(1.6rem,3vw,2.2rem); font-weight: 700; color: var(--text); margin-bottom: 14px; }
    .cta-section p  { font-size: 0.92rem; color: var(--muted); line-height: 1.7; margin-bottom: 32px; }
    .cta-btns { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
    .btn { padding: 12px 28px; border-radius: 50px; font-family: var(--font-sans); font-size: 0.9rem; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .15s; display: inline-flex; align-items: center; gap: 7px; }
    .btn--solid { background: var(--maroon); border: none; color: #fff; box-shadow: 0 3px 12px rgba(122,31,46,0.3); }
    .btn--solid:hover { background: var(--maroon-dk); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(122,31,46,0.35); }
    .btn--ghost { background: transparent; border: 2px solid var(--maroon); color: var(--maroon); }
    .btn--ghost:hover { background: rgba(122,31,46,0.06); }

    /* Footer */
    .footer { background: var(--maroon-dk); color: rgba(255,255,255,0.5); padding: 28px 48px; text-align: center; font-size: 0.82rem; }
    .footer a { color: rgba(255,255,255,0.75); text-decoration: none; }
    .footer a:hover { color: #fff; }

    /* Mobile */
    @media (max-width: 800px) {
      .hero__nav { padding: 24px 24px 0; }
      .hero__content { grid-template-columns: 1fr; padding: 0 24px; gap: 24px; }
      .hero__left { padding-top: 140px; }
      .hero__right { padding-top: 0; justify-content: center; }
      .hero__img { height: 280px; }
      .brand__name { font-size: 2.2rem; }
      .nav__pill { display: none; }
      .section, .stats-bar, .cta-section, .footer { padding-left: 24px; padding-right: 24px; }
    }
  </style>
</head>
<body>

<!-- ══════════════════════════════════════
     HERO
══════════════════════════════════════ -->
<section class="hero">
  <div class="hero__bg"></div>

  <!-- NAV -->
  <nav class="hero__nav">
    <a href="landing.php" class="brand">
      <div class="brand__top">
        <img src="assets/logo.jpg" alt="Esperon logo" class="brand__logo" onerror="this.style.display='none'" />
        <span class="brand__name">ESPERON</span>
      </div>
      <div class="brand__sub">Dairy</div>
    </a>

    <div class="nav__pill">
      <a href="login.php"  class="nav__link">Sign In</a>
      <a href="#features"  class="nav__link">Services</a>
      <a href="#products"  class="nav__link">Products</a>
      <a href="#contact"   class="nav__link nav__link--cta">Contact</a>
    </div>
  </nav>

  <!-- CONTENT -->
  <div class="hero__content">
    <div class="hero__left">
      <p class="hero__tagline">
        The bridge that leads you to the gateway of the
        Dairy Industry abroad, so sign up now and start
        your journey today!
      </p>
      <a href="#features" class="hero__cta">Explore more</a>
    </div>

    <div class="hero__right">
      <div class="hero__img-card">
        <img src="assets/bg.png" alt="Esperon Dairy Farm cows" class="hero__img" />
        <div class="hero__deco">
          <span></span><span></span><span></span>
        </div>
      </div>
    </div>
  </div>
</section>


<!-- ══════════════════════════════════════
     FEATURES
══════════════════════════════════════ -->
<section class="section" id="features">
  <div class="section__head">
    <div class="section__tag">Our Services</div>
    <h2 class="section__h2">Everything You Need to Run the Farm</h2>
    <p class="section__desc">From livestock tracking to customer orders, our platform covers every aspect of dairy farm management.</p>
  </div>
  <div class="features">
    <div class="feat">
      <div class="feat__icon"><span class="material-symbols-outlined">pets</span></div>
      <h3>Livestock Management</h3>
      <p>Track cow health, breed records, daily milk production, and get alerts for sick animals.</p>
    </div>
    <div class="feat">
      <div class="feat__icon"><span class="material-symbols-outlined">receipt_long</span></div>
      <h3>Order Tracking</h3>
      <p>Manage milk delivery orders from creation to delivery with real-time status updates.</p>
    </div>
    <div class="feat">
      <div class="feat__icon"><span class="material-symbols-outlined">storefront</span></div>
      <h3>Online Shop</h3>
      <p>Customers can browse and purchase dairy products directly through the customer portal.</p>
    </div>
    <div class="feat">
      <div class="feat__icon"><span class="material-symbols-outlined">badge</span></div>
      <h3>Staff Management</h3>
      <p>Manage worker accounts, track online status, assign reminders, and review daily reports.</p>
    </div>
    <div class="feat">
      <div class="feat__icon"><span class="material-symbols-outlined">inventory_2</span></div>
      <h3>Inventory Control</h3>
      <p>Monitor feed, milk stock, and supply levels with low-stock alerts and restock tracking.</p>
    </div>
    <div class="feat">
      <div class="feat__icon"><span class="material-symbols-outlined">bar_chart</span></div>
      <h3>Reports &amp; Analytics</h3>
      <p>View production trends, order summaries, and staff activity reports in one place.</p>
    </div>
  </div>
</section>


<!-- ══════════════════════════════════════
     STATS
══════════════════════════════════════ -->
<div class="stats-bar">
  <div class="stats-bar__inner">
    <div><div class="stat__val"><?= $stats['cows'] ?></div><div class="stat__lbl">Active Cows</div></div>
    <div><div class="stat__val"><?= $stats['products'] ?></div><div class="stat__lbl">Products</div></div>
    <div><div class="stat__val"><?= $stats['orders'] ?></div><div class="stat__lbl">Total Orders</div></div>
    <div><div class="stat__val"><?= $stats['customers'] ?></div><div class="stat__lbl">Customers</div></div>
  </div>
</div>


<!-- ══════════════════════════════════════
     PRODUCTS
══════════════════════════════════════ -->
<section class="section section--alt" id="products">
  <div class="section__head">
    <div class="section__tag">Fresh Products</div>
    <h2 class="section__h2">From Our Farm to Your Table</h2>
    <p class="section__desc">
      Browse our available dairy products.
      <a href="login.php"  style="color:var(--maroon);font-weight:700;">Log in</a> or
      <a href="signup.php" style="color:var(--maroon);font-weight:700;">create an account</a>
      to place an order.
    </p>
  </div>
  <?php if (empty($products)): ?>
    <p style="text-align:center;color:var(--muted);font-size:0.9rem;">No products available at the moment.</p>
  <?php else: ?>
  <div class="products">
    <?php
    $emojiMap = ['milk'=>'🥛','cheese'=>'🧀','butter'=>'🧈','yogurt'=>'🍦','cream'=>'🍨','skim'=>'🥛','mozzarella'=>'🧀'];
    foreach ($products as $p):
      $emoji = '🛒';
      foreach ($emojiMap as $k => $e) { if (stripos($p['name'], $k) !== false) { $emoji = $e; break; } }
    ?>
    <div class="prod">
      <div class="prod__emoji"><?= $emoji ?></div>
      <div class="prod__name"><?= htmlspecialchars($p['name']) ?></div>
      <?php if ($p['description']): ?>
      <div class="prod__desc"><?= htmlspecialchars($p['description']) ?></div>
      <?php endif; ?>
      <div class="prod__foot">
        <div>
          <div class="prod__price">&#8369;<?= number_format($p['price'], 2) ?></div>
          <div class="prod__unit">per <?= htmlspecialchars($p['unit']) ?></div>
        </div>
        <div class="prod__stock"><?= $p['stock_qty'] ?> in stock</div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>


<!-- ══════════════════════════════════════
     CTA
══════════════════════════════════════ -->
<section class="cta-section" id="contact">
  <div class="cta-section__inner">
    <h2>Ready to Get Started?</h2>
    <p>Join Esperon Dairy Farm's management system. Staff and admins can log in directly. Customers can create an account to start ordering fresh dairy products.</p>
    <div class="cta-btns">
      <a href="login.php"  class="btn btn--solid">
        <span class="material-symbols-outlined" style="font-size:1rem;">login</span> Sign In
      </a>
      <a href="signup.php" class="btn btn--ghost">
        <span class="material-symbols-outlined" style="font-size:1rem;">person_add</span> Create Account
      </a>
    </div>
  </div>
</section>


<!-- ══════════════════════════════════════
     FOOTER
══════════════════════════════════════ -->
<footer class="footer">
  <p>
    &copy; 2026 Esperon Dairy Farm &mdash; Malaybalay City &nbsp;&middot;&nbsp;
    <a href="login.php">Sign In</a> &nbsp;&middot;&nbsp;
    <a href="signup.php">Sign Up</a>
  </p>
</footer>

</body>
</html>
