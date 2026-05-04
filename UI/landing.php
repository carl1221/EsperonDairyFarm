<?php
// ============================================================
// UI/landing.php — Public landing page
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','httponly'=>true,'samesite'=>'Lax']);
    session_start();
}
if (isset($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? 'Staff';
    if ($role === 'Admin')    { header('Location: dashboard_admin.php');    exit; }
    if ($role === 'Customer') { header('Location: dashboard_customer.php'); exit; }
    header('Location: dashboard_staff.php'); exit;
}

$stats = ['products' => 0, 'cows' => 0, 'orders' => 0, 'customers' => 0];
$products = [];
try {
    // Load .env manually so database.php can read credentials
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k); $v = trim($v);
            if (!array_key_exists($k, $_ENV)) { putenv("$k=$v"); $_ENV[$k] = $v; }
        }
    }
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

    /* ── STICKY NAVBAR ── */
    .navbar {
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 100;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 20px 48px;
      transition: background .3s, box-shadow .3s, padding .3s;
    }
    .navbar.scrolled {
      background: #fff;
      box-shadow: 0 2px 20px rgba(0,0,0,0.10);
      padding: 12px 48px;
    }
    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
    }
    .brand__logo {
      width: 42px; height: 42px;
      border-radius: 8px;
      object-fit: cover;
      border: 2px solid rgba(122,31,46,0.25);
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    .brand__text { display: flex; flex-direction: column; line-height: 1; }
    .brand__name {
      font-family: var(--font-serif);
      font-size: 1.5rem;
      font-weight: 900;
      color: #fff;
      letter-spacing: -0.01em;
      transition: color .3s;
    }
    .navbar.scrolled .brand__name { color: var(--maroon); }
    .brand__sub {
      font-size: 0.62rem;
      font-weight: 700;
      letter-spacing: .25em;
      text-transform: uppercase;
      color: rgba(255,255,255,0.7);
      transition: color .3s;
    }
    .navbar.scrolled .brand__sub { color: var(--muted); }

    /* Desktop nav links */
    .nav__links {
      display: flex;
      align-items: center;
      gap: 4px;
    }
    .nav__link {
      text-decoration: none;
      font-size: 0.78rem;
      font-weight: 700;
      color: rgba(255,255,255,0.88);
      padding: 8px 16px;
      border-radius: 50px;
      transition: all .15s;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      white-space: nowrap;
    }
    .navbar.scrolled .nav__link { color: var(--text); }
    .nav__link:hover { background: rgba(255,255,255,0.15); color: #fff; }
    .navbar.scrolled .nav__link:hover { background: rgba(122,31,46,0.07); color: var(--maroon); }
    .nav__link--signin {
      background: rgba(255,255,255,0.18);
      border: 1px solid rgba(255,255,255,0.4);
      color: #fff !important;
      margin-left: 8px;
    }
    .navbar.scrolled .nav__link--signin {
      background: var(--maroon);
      border-color: var(--maroon);
      color: #fff !important;
    }
    .nav__link--signin:hover { background: rgba(255,255,255,0.3) !important; }
    .navbar.scrolled .nav__link--signin:hover { background: var(--maroon-dk) !important; }

    /* Hamburger */
    .nav__hamburger {
      display: none;
      flex-direction: column;
      gap: 5px;
      cursor: pointer;
      padding: 6px;
      background: none;
      border: none;
    }
    .nav__hamburger span {
      display: block;
      width: 24px; height: 2px;
      background: #fff;
      border-radius: 2px;
      transition: all .25s;
    }
    .navbar.scrolled .nav__hamburger span { background: var(--text); }
    .nav__hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
    .nav__hamburger.open span:nth-child(2) { opacity: 0; }
    .nav__hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

    /* Mobile drawer */
    .nav__drawer {
      display: none;
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(42,31,21,0.97);
      z-index: 99;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 8px;
      opacity: 0;
      pointer-events: none;
      transition: opacity .25s;
    }
    .nav__drawer.open { opacity: 1; pointer-events: all; }
    .nav__drawer .nav__link {
      font-size: 1.2rem;
      color: rgba(255,255,255,0.85) !important;
      padding: 14px 40px;
      letter-spacing: .1em;
    }
    .nav__drawer .nav__link:hover { background: rgba(255,255,255,0.08); color: #fff !important; }
    .nav__drawer .nav__link--signin {
      margin-top: 16px;
      background: var(--maroon) !important;
      border-color: var(--maroon) !important;
    }

    /* ── HERO ── */
    .hero {
      position: relative;
      width: 100%;
      height: 100vh;
      min-height: 640px;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }
    .hero__bg {
      position: absolute;
      inset: 0;
      background: url('assets/bg.png') no-repeat center center / cover;
      opacity: 1;
      z-index: 0;
    }
    .hero__overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(
        160deg,
        rgba(42,20,12,0.72) 0%,
        rgba(90,21,32,0.60) 50%,
        rgba(30,40,20,0.65) 100%
      );
      z-index: 1;
    }
    .hero__content {
      position: relative;
      z-index: 2;
      text-align: center;
      padding: 0 24px;
      max-width: 780px;
    }
    .hero__eyebrow {
      display: inline-block;
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: .22em;
      text-transform: uppercase;
      color: var(--beige);
      margin-bottom: 20px;
      opacity: 0.9;
    }
    .hero__headline {
      font-family: var(--font-serif);
      font-size: clamp(3rem, 7vw, 5.5rem);
      font-weight: 900;
      color: #fff;
      line-height: 1.08;
      margin-bottom: 22px;
      text-shadow: 0 4px 32px rgba(0,0,0,0.35);
    }
    .hero__headline em {
      font-style: italic;
      color: var(--beige);
    }
    .hero__subtitle {
      font-size: clamp(0.95rem, 2vw, 1.1rem);
      color: rgba(255,255,255,0.78);
      line-height: 1.7;
      max-width: 520px;
      margin: 0 auto 36px;
    }
    .hero__ctas {
      display: flex;
      gap: 14px;
      justify-content: center;
      flex-wrap: wrap;
    }
    .btn {
      padding: 13px 30px;
      border-radius: 50px;
      font-family: var(--font-sans);
      font-size: 0.88rem;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      transition: all .18s;
      display: inline-flex;
      align-items: center;
      gap: 7px;
      letter-spacing: .04em;
    }
    .btn--solid {
      background: var(--maroon);
      border: 2px solid var(--maroon);
      color: #fff;
      box-shadow: 0 4px 20px rgba(122,31,46,0.45);
    }
    .btn--solid:hover { background: var(--maroon-dk); border-color: var(--maroon-dk); transform: translateY(-2px); box-shadow: 0 8px 28px rgba(122,31,46,0.5); }
    .btn--outline {
      background: transparent;
      border: 2px solid rgba(255,255,255,0.6);
      color: #fff;
    }
    .btn--outline:hover { background: rgba(255,255,255,0.12); border-color: #fff; transform: translateY(-2px); }
    .btn--maroon-ghost {
      background: transparent;
      border: 2px solid var(--maroon);
      color: var(--maroon);
    }
    .btn--maroon-ghost:hover { background: rgba(122,31,46,0.07); transform: translateY(-1px); }

    /* Scroll arrow */
    .hero__scroll {
      position: absolute;
      bottom: 32px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 2;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      text-decoration: none;
      color: rgba(255,255,255,0.55);
      font-size: 0.65rem;
      letter-spacing: .15em;
      text-transform: uppercase;
      animation: scrollBounce 2s ease-in-out infinite;
    }
    .hero__scroll .material-symbols-outlined { font-size: 1.6rem; }
    @keyframes scrollBounce {
      0%, 100% { transform: translateX(-50%) translateY(0); }
      50%       { transform: translateX(-50%) translateY(6px); }
    }

    /* ── SECTION SHARED ── */
    .section { padding: 88px 48px; }
    .section--alt { background: rgba(232,220,200,0.28); }
    .section__head { text-align: center; margin-bottom: 56px; }
    .section__tag  { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .18em; color: var(--olive); margin-bottom: 10px; }
    .section__h2   { font-family: var(--font-serif); font-size: clamp(1.7rem, 3vw, 2.4rem); font-weight: 700; color: var(--text); margin-bottom: 14px; }
    .section__desc { font-size: 0.92rem; color: var(--muted); max-width: 540px; margin: 0 auto; line-height: 1.75; }

    /* ── FEATURES — horizontal icon cards with left accent ── */
    .features {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 18px;
      max-width: 1100px;
      margin: 0 auto;
    }
    .feat {
      background: #fff;
      border: 1px solid rgba(200,184,152,0.4);
      border-left: 4px solid var(--maroon);
      border-radius: 0 16px 16px 0;
      padding: 22px 22px 22px 20px;
      display: flex;
      align-items: flex-start;
      gap: 18px;
      transition: all .2s;
    }
    .feat:hover { transform: translateX(4px); box-shadow: 0 8px 32px rgba(0,0,0,0.08); border-left-color: var(--olive); }
    .feat__icon {
      flex-shrink: 0;
      width: 48px; height: 48px;
      border-radius: 12px;
      background: rgba(122,31,46,0.08);
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .feat__icon .material-symbols-outlined { font-size: 1.5rem; color: var(--maroon); }
    .feat__body h3 { font-family: var(--font-serif); font-size: 1rem; font-weight: 700; color: var(--text); margin-bottom: 6px; }
    .feat__body p  { font-size: 0.83rem; color: var(--muted); line-height: 1.65; }

    /* ── STATS BAR ── */
    .stats-bar {
      background: linear-gradient(135deg, var(--maroon-dk) 0%, var(--maroon) 60%, #9a2f42 100%);
      padding: 64px 48px;
      position: relative;
      overflow: hidden;
    }
    .stats-bar::before {
      content: '';
      position: absolute;
      inset: 0;
      background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .stats-bar__inner {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 32px;
      max-width: 1000px;
      margin: 0 auto;
      text-align: center;
      position: relative;
    }
    .stat__val {
      font-family: var(--font-serif);
      font-size: 3.2rem;
      font-weight: 700;
      color: #fff;
      line-height: 1;
    }
    .stat__lbl {
      font-size: 0.7rem;
      color: rgba(255,255,255,0.55);
      text-transform: uppercase;
      letter-spacing: .12em;
      margin-top: 10px;
    }
    .stat__divider {
      width: 32px; height: 2px;
      background: rgba(255,255,255,0.2);
      margin: 10px auto 0;
      border-radius: 2px;
    }

    /* ── PRODUCTS — colored top band cards ── */
    .products {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
      gap: 20px;
      max-width: 1100px;
      margin: 0 auto;
    }
    .prod {
      background: #fff;
      border: 1px solid rgba(200,184,152,0.4);
      border-radius: 18px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: all .22s;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .prod:hover { transform: translateY(-5px); box-shadow: 0 16px 40px rgba(0,0,0,0.10); }
    .prod__band {
      height: 6px;
      background: var(--band-color, var(--maroon));
    }
    .prod__body { padding: 20px 20px 18px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
    .prod__emoji { font-size: 2.2rem; }
    .prod__name  { font-weight: 700; font-size: 0.97rem; color: var(--text); }
    .prod__desc  { font-size: 0.82rem; color: var(--muted); line-height: 1.6; flex: 1; }
    .prod__foot  { display: flex; align-items: flex-end; justify-content: space-between; margin-top: 6px; }
    .prod__price { font-family: var(--font-serif); font-size: 1.2rem; font-weight: 700; color: var(--maroon); }
    .prod__unit  { font-size: 0.68rem; color: var(--muted); margin-top: 1px; }
    .prod__stock {
      font-size: 0.7rem;
      color: var(--olive);
      font-weight: 700;
      background: rgba(61,82,48,0.1);
      padding: 4px 10px;
      border-radius: 20px;
    }

    /* ── TEAM — circular photos with maroon ring on hover ── */
    .team-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 28px;
      max-width: 1000px;
      margin: 0 auto;
    }
    .team-card {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 16px;
      text-align: center;
    }
    .team-card__avatar {
      position: relative;
      width: 150px; height: 150px;
      border-radius: 50%;
      overflow: hidden;
      border: 3px solid var(--beige);
      transition: border-color .25s, box-shadow .25s;
      box-shadow: 0 4px 20px rgba(0,0,0,0.10);
    }
    .team-card:hover .team-card__avatar {
      border-color: var(--maroon);
      box-shadow: 0 0 0 5px rgba(122,31,46,0.18), 0 8px 28px rgba(0,0,0,0.14);
    }
    .team-card__img {
      width: 100%; height: 100%;
      object-fit: cover;
      object-position: top;
      display: block;
      transition: transform .3s;
    }
    .team-card:hover .team-card__img { transform: scale(1.06); }
    .team-card__placeholder {
      width: 100%; height: 100%;
      background: linear-gradient(135deg, var(--cream), var(--beige));
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .team-card__placeholder .material-symbols-outlined { font-size: 3.5rem; color: var(--muted); }
    .team-card__name { font-family: var(--font-serif); font-size: 1.05rem; font-weight: 700; color: var(--text); }
    .team-card__role {
      font-size: 0.75rem;
      color: var(--maroon);
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .08em;
      background: rgba(122,31,46,0.08);
      padding: 4px 12px;
      border-radius: 20px;
    }

    /* ── CTA SECTION — full-width maroon gradient ── */
    .cta-section {
      background: linear-gradient(135deg, var(--maroon-dk) 0%, var(--maroon) 60%, #9a2f42 100%);
      padding: 96px 48px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    .cta-section::before {
      content: '';
      position: absolute;
      top: -80px; right: -80px;
      width: 320px; height: 320px;
      border-radius: 50%;
      background: rgba(255,255,255,0.04);
    }
    .cta-section::after {
      content: '';
      position: absolute;
      bottom: -60px; left: -60px;
      width: 240px; height: 240px;
      border-radius: 50%;
      background: rgba(255,255,255,0.04);
    }
    .cta-section__inner { max-width: 600px; margin: 0 auto; position: relative; z-index: 1; }
    .cta-section h2 {
      font-family: var(--font-serif);
      font-size: clamp(1.8rem, 3.5vw, 2.6rem);
      font-weight: 700;
      color: #fff;
      margin-bottom: 16px;
    }
    .cta-section p { font-size: 0.95rem; color: rgba(255,255,255,0.75); line-height: 1.75; margin-bottom: 36px; }
    .cta-btns { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
    .btn--white {
      background: #fff;
      border: 2px solid #fff;
      color: var(--maroon);
    }
    .btn--white:hover { background: var(--cream-lt); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.2); }
    .btn--white-ghost {
      background: transparent;
      border: 2px solid rgba(255,255,255,0.55);
      color: #fff;
    }
    .btn--white-ghost:hover { background: rgba(255,255,255,0.12); border-color: #fff; transform: translateY(-2px); }

    /* ── FOOTER — two-column ── */
    .footer {
      background: var(--maroon-dk);
      padding: 40px 48px;
    }
    .footer__inner {
      max-width: 1100px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 32px;
      flex-wrap: wrap;
    }
    .footer__brand { display: flex; flex-direction: column; gap: 6px; }
    .footer__brand-name {
      font-family: var(--font-serif);
      font-size: 1.3rem;
      font-weight: 700;
      color: #fff;
    }
    .footer__brand-tagline { font-size: 0.78rem; color: rgba(255,255,255,0.45); }
    .footer__links { display: flex; gap: 24px; flex-wrap: wrap; align-items: center; }
    .footer__link {
      font-size: 0.82rem;
      color: rgba(255,255,255,0.55);
      text-decoration: none;
      transition: color .15s;
    }
    .footer__link:hover { color: #fff; }
    .footer__copy { font-size: 0.75rem; color: rgba(255,255,255,0.3); margin-top: 4px; }

    /* ── FADE-IN ANIMATIONS ── */
    .fade-in {
      opacity: 0;
      transform: translateY(28px);
      transition: opacity .6s ease, transform .6s ease;
    }
    .fade-in.visible {
      opacity: 1;
      transform: translateY(0);
    }
    .fade-in-delay-1 { transition-delay: .1s; }
    .fade-in-delay-2 { transition-delay: .2s; }
    .fade-in-delay-3 { transition-delay: .3s; }
    .fade-in-delay-4 { transition-delay: .4s; }
    .fade-in-delay-5 { transition-delay: .5s; }

    /* ── RESPONSIVE ── */
    @media (max-width: 900px) {
      .team-grid { grid-template-columns: repeat(2, 1fr); gap: 32px; }
    }
    @media (max-width: 768px) {
      .navbar { padding: 16px 24px; }
      .navbar.scrolled { padding: 12px 24px; }
      .nav__links { display: none; }
      .nav__hamburger { display: flex; }
      .nav__drawer { display: flex; }
      .section, .stats-bar, .cta-section { padding-left: 24px; padding-right: 24px; }
      .footer { padding: 32px 24px; }
      .footer__inner { flex-direction: column; align-items: flex-start; gap: 20px; }
      .features { grid-template-columns: 1fr; }
    }
    @media (max-width: 480px) {
      .team-grid { grid-template-columns: repeat(2, 1fr); gap: 20px; }
      .team-card__avatar { width: 110px; height: 110px; }
      .hero__headline { font-size: 2.6rem; }
    }
  </style>
</head>
<body>

<!-- ══════════════════════════════════════════════════════
     STICKY NAVBAR
══════════════════════════════════════════════════════ -->
<nav class="navbar" id="navbar">
  <a href="landing.php" class="brand">
    <img src="assets/logo.jpg" alt="Esperon logo" class="brand__logo"
         onerror="this.style.display='none'" />
    <div class="brand__text">
      <span class="brand__name">ESPERON</span>
      <span class="brand__sub">Dairy Farm</span>
    </div>
  </a>

  <!-- Desktop links -->
  <div class="nav__links">
    <a href="#features" class="nav__link">Services</a>
    <a href="#products" class="nav__link">Products</a>
    <a href="#team"     class="nav__link">Team</a>
    <a href="#contact"  class="nav__link">Contact</a>
    <a href="login.php" class="nav__link nav__link--signin">
      <span class="material-symbols-outlined" style="font-size:.9rem;vertical-align:middle;">login</span>
      Sign In
    </a>
  </div>

  <!-- Hamburger -->
  <button class="nav__hamburger" id="hamburger" aria-label="Toggle menu">
    <span></span><span></span><span></span>
  </button>
</nav>

<!-- Mobile drawer -->
<div class="nav__drawer" id="navDrawer">
  <a href="#features" class="nav__link" onclick="closeDrawer()">Services</a>
  <a href="#products" class="nav__link" onclick="closeDrawer()">Products</a>
  <a href="#team"     class="nav__link" onclick="closeDrawer()">Team</a>
  <a href="#contact"  class="nav__link" onclick="closeDrawer()">Contact</a>
  <a href="login.php" class="nav__link nav__link--signin">Sign In</a>
  <a href="signup.php" class="nav__link" style="margin-top:4px;color:rgba(255,255,255,0.6)!important;">Create Account</a>
</div>


<!-- ══════════════════════════════════════════════════════
     HERO
══════════════════════════════════════════════════════ -->
<section class="hero">
  <div class="hero__bg"></div>
  <div class="hero__overlay"></div>

  <div class="hero__content">
    <span class="hero__eyebrow">Esperon Dairy Farm &mdash; Malaybalay City</span>
    <h1 class="hero__headline">Pure. <em>Fresh.</em> Local.</h1>
    <p class="hero__subtitle">
      From our pastures to your table &mdash; traceable, farm-fresh dairy products
      managed through a modern platform built for farmers and customers alike.
    </p>
    <div class="hero__ctas">
      <a href="login.php"  class="btn btn--solid">
        <span class="material-symbols-outlined" style="font-size:1rem;">login</span>
        Sign In
      </a>
      <a href="#features" class="btn btn--outline">
        <span class="material-symbols-outlined" style="font-size:1rem;">explore</span>
        Explore
      </a>
    </div>
  </div>

  <a href="#features" class="hero__scroll" aria-label="Scroll down">
    <span class="material-symbols-outlined">keyboard_arrow_down</span>
  </a>
</section>


<!-- ══════════════════════════════════════════════════════
     FEATURES
══════════════════════════════════════════════════════ -->
<section class="section" id="features">
  <div class="section__head fade-in">
    <div class="section__tag">Our Services</div>
    <h2 class="section__h2">Everything You Need to Run the Farm</h2>
    <p class="section__desc">From livestock tracking to customer orders, our platform covers every aspect of dairy farm management.</p>
  </div>
  <div class="features">
    <div class="feat fade-in fade-in-delay-1">
      <div class="feat__icon"><span class="material-symbols-outlined">pets</span></div>
      <div class="feat__body">
        <h3>Livestock Management</h3>
        <p>Track cow health, breed records, daily milk production, and get alerts for sick animals.</p>
      </div>
    </div>
    <div class="feat fade-in fade-in-delay-2">
      <div class="feat__icon"><span class="material-symbols-outlined">receipt_long</span></div>
      <div class="feat__body">
        <h3>Order Tracking</h3>
        <p>Manage milk delivery orders from creation to delivery with real-time status updates.</p>
      </div>
    </div>
    <div class="feat fade-in fade-in-delay-3">
      <div class="feat__icon"><span class="material-symbols-outlined">storefront</span></div>
      <div class="feat__body">
        <h3>Online Shop</h3>
        <p>Customers can browse and purchase dairy products directly through the customer portal.</p>
      </div>
    </div>
    <div class="feat fade-in fade-in-delay-1">
      <div class="feat__icon"><span class="material-symbols-outlined">badge</span></div>
      <div class="feat__body">
        <h3>Staff Management</h3>
        <p>Manage worker accounts, track online status, assign reminders, and review daily reports.</p>
      </div>
    </div>
    <div class="feat fade-in fade-in-delay-2">
      <div class="feat__icon"><span class="material-symbols-outlined">inventory_2</span></div>
      <div class="feat__body">
        <h3>Inventory Control</h3>
        <p>Monitor feed, milk stock, and supply levels with low-stock alerts and restock tracking.</p>
      </div>
    </div>
    <div class="feat fade-in fade-in-delay-3">
      <div class="feat__icon"><span class="material-symbols-outlined">bar_chart</span></div>
      <div class="feat__body">
        <h3>Reports &amp; Analytics</h3>
        <p>View production trends, order summaries, and staff activity reports in one place.</p>
      </div>
    </div>
  </div>
</section>


<!-- ══════════════════════════════════════════════════════
     STATS BAR
══════════════════════════════════════════════════════ -->
<div class="stats-bar" id="statsBar">
  <div class="stats-bar__inner">
    <div class="fade-in">
      <div class="stat__val" data-target="<?= (int)$stats['cows'] ?>">0</div>
      <div class="stat__divider"></div>
      <div class="stat__lbl">Active Cows</div>
    </div>
    <div class="fade-in fade-in-delay-1">
      <div class="stat__val" data-target="<?= (int)$stats['products'] ?>">0</div>
      <div class="stat__divider"></div>
      <div class="stat__lbl">Products</div>
    </div>
    <div class="fade-in fade-in-delay-2">
      <div class="stat__val" data-target="<?= (int)$stats['orders'] ?>">0</div>
      <div class="stat__divider"></div>
      <div class="stat__lbl">Total Orders</div>
    </div>
    <div class="fade-in fade-in-delay-3">
      <div class="stat__val" data-target="<?= (int)$stats['customers'] ?>">0</div>
      <div class="stat__divider"></div>
      <div class="stat__lbl">Customers</div>
    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════════════════
     PRODUCTS
══════════════════════════════════════════════════════ -->
<section class="section section--alt" id="products">
  <div class="section__head fade-in">
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
    $emojiMap = ['milk'=>'🥛','cheese'=>'🧀','butter'=>'🧈','yogurt'=>'🍦','cream'=>'🍶','skim'=>'🥛','mozzarella'=>'🧀'];
    $bandColors = [
      'milk'       => '#4a90d9',
      'cheese'     => '#e8a020',
      'butter'     => '#f0c040',
      'yogurt'     => '#7a9e5a',
      'cream'      => '#c8a0d0',
      'skim'       => '#5ab0d0',
      'mozzarella' => '#d4a060',
    ];
    $defaultBands = ['#7a1f2e','#3d5230','#5a7a45','#9a4a20','#4a6080','#7a5a30'];
    $bi = 0;
    foreach ($products as $p):
      $emoji = '🐄';
      $band  = $defaultBands[$bi % count($defaultBands)];
      foreach ($emojiMap as $k => $e) {
        if (stripos($p['name'], $k) !== false) {
          $emoji = $e;
          $band  = $bandColors[$k] ?? $band;
          break;
        }
      }
      $bi++;
    ?>
    <div class="prod fade-in">
      <div class="prod__band" style="--band-color:<?= $band ?>"></div>
      <div class="prod__body">
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
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>


<!-- ══════════════════════════════════════════════════════
     TEAM
══════════════════════════════════════════════════════ -->
<section class="section" id="team">
  <div class="section__head fade-in">
    <div class="section__tag">The People Behind the Farm</div>
    <h2 class="section__h2">Meet Our Team</h2>
    <p class="section__desc">Dedicated individuals working together to bring you the finest dairy products from Esperon Farm.</p>
  </div>
  <div class="team-grid">

    <div class="team-card fade-in fade-in-delay-1">
      <div class="team-card__avatar">
        <img src="assets/team/PJ.jpg" alt="PJ Asombrado" class="team-card__img"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
        <div class="team-card__placeholder" style="display:none;">
          <span class="material-symbols-outlined">person</span>
        </div>
      </div>
      <div class="team-card__name">PJ Asombrado</div>
      <div class="team-card__role">Team Leader &amp; Web Developer</div>
    </div>

    <div class="team-card fade-in fade-in-delay-2">
      <div class="team-card__avatar">
        <img src="assets/team/Carl.jpg" alt="Carl Ian Pepito" class="team-card__img"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
        <div class="team-card__placeholder" style="display:none;">
          <span class="material-symbols-outlined">person</span>
        </div>
      </div>
      <div class="team-card__name">Carl Ian Pepito</div>
      <div class="team-card__role">Web Developer &amp; Documenter</div>
    </div>

    <div class="team-card fade-in fade-in-delay-3">
      <div class="team-card__avatar">
        <img src="assets/team/Kurt.jpg" alt="Kurt Yandrie Arangcon" class="team-card__img"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
        <div class="team-card__placeholder" style="display:none;">
          <span class="material-symbols-outlined">person</span>
        </div>
      </div>
      <div class="team-card__name">Kurt Yandrie Arangcon</div>
      <div class="team-card__role">UI/UX Designer</div>
    </div>

    <div class="team-card fade-in fade-in-delay-4">
      <div class="team-card__avatar">
        <img src="assets/team/Justine.jpg" alt="Justine Barsana" class="team-card__img"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
        <div class="team-card__placeholder" style="display:none;">
          <span class="material-symbols-outlined">person</span>
        </div>
      </div>
      <div class="team-card__name">Justine Barsana</div>
      <div class="team-card__role">System Analyst</div>
    </div>

  </div>
</section>


<!-- ══════════════════════════════════════════════════════
     CTA
══════════════════════════════════════════════════════ -->
<section class="cta-section" id="contact">
  <div class="cta-section__inner fade-in">
    <h2>Ready to Get Started?</h2>
    <p>Join Esperon Dairy Farm's management system. Staff and admins can log in directly. Customers can create an account to start ordering fresh dairy products.</p>
    <div class="cta-btns">
      <a href="login.php"  class="btn btn--white">
        <span class="material-symbols-outlined" style="font-size:1rem;">login</span>
        Sign In
      </a>
      <a href="signup.php" class="btn btn--white-ghost">
        <span class="material-symbols-outlined" style="font-size:1rem;">person_add</span>
        Create Account
      </a>
    </div>
  </div>
</section>


<!-- ══════════════════════════════════════════════════════
     FOOTER
══════════════════════════════════════════════════════ -->
<footer class="footer">
  <div class="footer__inner">
    <div class="footer__brand">
      <div class="footer__brand-name">Esperon Dairy Farm</div>
      <div class="footer__brand-tagline">Pure. Fresh. Local. &mdash; Malaybalay City</div>
      <div class="footer__copy">&copy; 2026 Esperon Dairy Farm. All rights reserved.</div>
    </div>
    <div class="footer__links">
      <a href="#features" class="footer__link">Services</a>
      <a href="#products" class="footer__link">Products</a>
      <a href="#team"     class="footer__link">Team</a>
      <a href="#contact"  class="footer__link">Contact</a>
      <a href="login.php"  class="footer__link">Sign In</a>
      <a href="signup.php" class="footer__link">Sign Up</a>
    </div>
  </div>
</footer>


<!-- ══════════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════════ -->
<script>
(function () {
  'use strict';

  /* ── Sticky navbar ── */
  const navbar = document.getElementById('navbar');
  function onScroll() {
    if (window.scrollY > 60) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ── Hamburger / mobile drawer ── */
  const hamburger  = document.getElementById('hamburger');
  const navDrawer  = document.getElementById('navDrawer');

  hamburger.addEventListener('click', function () {
    const isOpen = navDrawer.classList.toggle('open');
    hamburger.classList.toggle('open', isOpen);
    document.body.style.overflow = isOpen ? 'hidden' : '';
  });

  window.closeDrawer = function () {
    navDrawer.classList.remove('open');
    hamburger.classList.remove('open');
    document.body.style.overflow = '';
  };

  /* ── IntersectionObserver: fade-in on scroll ── */
  const fadeEls = document.querySelectorAll('.fade-in');
  const fadeObserver = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        fadeObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });

  fadeEls.forEach(function (el) { fadeObserver.observe(el); });

  /* ── Animated count-up for stats ── */
  const statVals = document.querySelectorAll('.stat__val[data-target]');
  let statsAnimated = false;

  function animateCount(el) {
    const target = parseInt(el.dataset.target, 10) || 0;
    if (target === 0) { el.textContent = '0'; return; }
    const duration = 1400;
    const start    = performance.now();
    function step(now) {
      const elapsed  = now - start;
      const progress = Math.min(elapsed / duration, 1);
      // ease-out cubic
      const eased    = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.round(eased * target).toLocaleString();
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  const statsObserver = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting && !statsAnimated) {
        statsAnimated = true;
        statVals.forEach(animateCount);
        statsObserver.disconnect();
      }
    });
  }, { threshold: 0.3 });

  const statsBar = document.getElementById('statsBar');
  if (statsBar) statsObserver.observe(statsBar);

})();
</script>
</body>
</html>
