<?php
// ============================================================
// UI/landing.php — Public landing page (redesigned)
// ============================================================

// Start session only if not already started
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
  <title>Esperon Dairy Farm — Malaybalay City</title>
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

    /* ── FADE-IN ANIMATION ── */
    .fade-in {
      opacity: 0;
      transform: translateY(28px);
      transition: opacity 0.65s ease, transform 0.65s ease;
    }
    .fade-in.visible {
      opacity: 1;
      transform: translateY(0);
    }
    .fade-in-left {
      opacity: 0;
      transform: translateX(-32px);
      transition: opacity 0.7s ease, transform 0.7s ease;
    }
    .fade-in-left.visible { opacity: 1; transform: translateX(0); }
    .fade-in-right {
      opacity: 0;
      transform: translateX(32px);
      transition: opacity 0.7s ease, transform 0.7s ease;
    }
    .fade-in-right.visible { opacity: 1; transform: translateX(0); }
    .delay-1 { transition-delay: 0.1s; }
    .delay-2 { transition-delay: 0.2s; }
    .delay-3 { transition-delay: 0.3s; }
    .delay-4 { transition-delay: 0.4s; }
    .delay-5 { transition-delay: 0.5s; }
    .delay-6 { transition-delay: 0.6s; }

    /* ══════════════════════════════════════
       HERO
    ══════════════════════════════════════ */
    .hero {
      position: relative;
      width: 100%;
      height: 100vh;
      min-height: 640px;
      overflow: hidden;
      background: var(--cream);
    }
    .hero__bg {
      position: absolute;
      inset: 0;
      background: url('assets/bg.png') no-repeat center center / cover;
      opacity: 0.12;
      filter: saturate(0.4);
      z-index: 0;
    }

    /* ── STICKY NAV PILL ── */
    .hero__nav {
      position: fixed;
      top: 22px;
      right: 40px;
      z-index: 100;
    }
    .nav__pill {
      display: flex;
      align-items: center;
      gap: 2px;
      background: rgba(255,255,255,0.62);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,0.75);
      border-radius: 50px;
      padding: 5px 6px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.10), 0 1px 4px rgba(0,0,0,0.06);
      transition: background 0.3s;
    }
    .nav__pill:hover { background: rgba(255,255,255,0.82); }
    .nav__link {
      text-decoration: none;
      font-size: 0.76rem;
      font-weight: 700;
      color: var(--text);
      padding: 9px 18px;
      border-radius: 50px;
      transition: all .15s;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      white-space: nowrap;
    }
    .nav__link:hover { color: var(--maroon); background: rgba(122,31,46,0.07); }
    .nav__link--cta {
      background: var(--maroon);
      color: #fff !important;
      box-shadow: 0 2px 10px rgba(122,31,46,0.35);
    }
    .nav__link--cta:hover { background: var(--maroon-dk) !important; transform: translateY(-1px); }

    /* ── HERO CONTENT ── */
    .hero__content {
      position: absolute;
      inset: 0;
      z-index: 10;
      display: grid;
      grid-template-columns: 1fr 1.05fr;
      align-items: center;
      padding: 0 56px;
      gap: 48px;
    }
    .hero__left {
      display: flex;
      flex-direction: column;
      gap: 0;
      padding-top: 40px;
    }
    .hero__location {
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: var(--olive);
      margin-bottom: 14px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .hero__location::before {
      content: '';
      display: inline-block;
      width: 28px;
      height: 2px;
      background: var(--olive);
      border-radius: 2px;
    }
    .hero__brand-name {
      font-family: var(--font-serif);
      font-size: clamp(4rem, 7vw, 7rem);
      font-weight: 900;
      color: var(--maroon);
      line-height: 0.92;
      letter-spacing: -0.02em;
      margin-bottom: 6px;
    }
    .hero__brand-sub {
      font-family: var(--font-serif);
      font-size: clamp(1.1rem, 2vw, 1.6rem);
      font-weight: 400;
      font-style: italic;
      color: var(--maroon);
      letter-spacing: 0.22em;
      text-transform: uppercase;
      margin-bottom: 28px;
      opacity: 0.75;
    }
    .hero__tagline {
      font-size: 0.95rem;
      color: var(--muted);
      line-height: 1.8;
      max-width: 340px;
      margin-bottom: 36px;
    }
    .hero__btns {
      display: flex;
      gap: 14px;
      flex-wrap: wrap;
    }
    .hero__cta {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--olive);
      color: #fff;
      border: none;
      border-radius: 50px;
      padding: 14px 30px;
      font-family: var(--font-sans);
      font-size: 0.9rem;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      transition: all .2s;
      box-shadow: 0 4px 18px rgba(61,82,48,0.32);
    }
    .hero__cta:hover {
      background: var(--olive-lt);
      transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(61,82,48,0.38);
    }
    .hero__cta--ghost {
      background: transparent;
      border: 2px solid var(--maroon);
      color: var(--maroon);
      box-shadow: none;
    }
    .hero__cta--ghost:hover {
      background: rgba(122,31,46,0.07);
      transform: translateY(-2px);
      box-shadow: 0 4px 16px rgba(122,31,46,0.15);
    }

    /* ── HERO RIGHT ── */
    .hero__right {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      padding-top: 40px;
    }
    .hero__img-card {
      position: relative;
      width: 100%;
      max-width: 520px;
    }
    .hero__img {
      width: 100%;
      height: 440px;
      object-fit: cover;
      border-radius: 32px 32px 32px 110px;
      box-shadow: 0 28px 70px rgba(0,0,0,0.24), 0 4px 18px rgba(0,0,0,0.1);
      display: block;
    }
    .hero__deco {
      position: absolute;
      bottom: -32px;
      right: -32px;
      display: grid;
      grid-template-columns: repeat(5, 10px);
      grid-template-rows: repeat(5, 10px);
      gap: 8px;
    }
    .hero__deco span {
      display: block;
      width: 5px;
      height: 5px;
      border-radius: 50%;
      background: var(--beige);
      opacity: 0.55;
    }

    /* ══════════════════════════════════════
       SECTIONS
    ══════════════════════════════════════ */
    .section { padding: 88px 56px; }
    .section--alt {
      background: linear-gradient(160deg, rgba(237,227,208,0.55) 0%, rgba(245,240,232,0.9) 100%);
    }
    .section--features {
      background: linear-gradient(160deg, #f8f4ee 0%, #ede3d0 100%);
    }
    .section__head { text-align: center; margin-bottom: 56px; }
    .section__tag  {
      font-size: 0.68rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .2em;
      color: var(--olive);
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }
    .section__tag::before,
    .section__tag::after {
      content: '';
      display: block;
      width: 32px;
      height: 1.5px;
      background: var(--olive);
      opacity: 0.5;
      border-radius: 2px;
    }
    .section__h2   { font-family: var(--font-serif); font-size: clamp(1.7rem,3vw,2.5rem); font-weight: 700; color: var(--text); margin-bottom: 14px; }
    .section__desc { font-size: 0.93rem; color: var(--muted); max-width: 540px; margin: 0 auto; line-height: 1.75; }

    /* ── FEATURES 3-COL GRID ── */
    .features {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 22px;
      max-width: 1100px;
      margin: 0 auto;
    }
    .feat {
      background: rgba(255,255,255,0.82);
      border: 1px solid rgba(200,184,152,0.45);
      border-radius: 22px;
      padding: 32px 26px;
      transition: all .22s;
      position: relative;
      overflow: hidden;
    }
    .feat::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--maroon), var(--olive));
      opacity: 0;
      transition: opacity .22s;
    }
    .feat:hover { transform: translateY(-5px); box-shadow: 0 18px 48px rgba(0,0,0,0.10); border-color: rgba(122,31,46,0.18); }
    .feat:hover::before { opacity: 1; }
    .feat__icon {
      width: 58px;
      height: 58px;
      border-radius: 16px;
      background: linear-gradient(135deg, rgba(122,31,46,0.10), rgba(122,31,46,0.05));
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 18px;
      transition: transform .22s;
    }
    .feat:hover .feat__icon { transform: scale(1.08); }
    .feat__icon .material-symbols-outlined { font-size: 1.8rem; color: var(--maroon); }
    .feat h3 { font-family: var(--font-serif); font-size: 1.05rem; font-weight: 700; color: var(--text); margin-bottom: 10px; }
    .feat p  { font-size: 0.85rem; color: var(--muted); line-height: 1.7; }

    /* ── STATS BAR ── */
    .stats-bar {
      background: linear-gradient(135deg, var(--maroon-dk) 0%, var(--maroon) 60%, #9a2f42 100%);
      padding: 64px 56px;
      position: relative;
      overflow: hidden;
    }
    .stats-bar::before {
      content: '';
      position: absolute;
      inset: 0;
      background: url('assets/bg.png') no-repeat center center / cover;
      opacity: 0.05;
      filter: saturate(0);
    }
    .stats-bar__inner {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 32px;
      max-width: 1000px;
      margin: 0 auto;
      text-align: center;
      position: relative;
      z-index: 1;
    }
    .stat__val {
      font-family: var(--font-serif);
      font-size: 3.4rem;
      font-weight: 700;
      color: #fff;
      line-height: 1;
      display: block;
    }
    .stat__lbl {
      font-size: 0.72rem;
      color: rgba(255,255,255,0.6);
      text-transform: uppercase;
      letter-spacing: .12em;
      margin-top: 10px;
      display: block;
    }
    .stat-item {
      padding: 20px 10px;
      border-right: 1px solid rgba(255,255,255,0.12);
    }
    .stat-item:last-child { border-right: none; }

    /* ── PRODUCTS ── */
    .products {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
      gap: 20px;
      max-width: 1100px;
      margin: 0 auto;
    }
    .prod {
      background: rgba(255,255,255,0.9);
      border: 1px solid rgba(200,184,152,0.45);
      border-radius: 20px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: all .22s;
    }
    .prod:hover { transform: translateY(-4px); box-shadow: 0 14px 40px rgba(0,0,0,0.09); }
    .prod__accent {
      height: 5px;
      width: 100%;
    }
    .prod__accent--0 { background: linear-gradient(90deg, #7a1f2e, #b04060); }
    .prod__accent--1 { background: linear-gradient(90deg, #3d5230, #6a9a50); }
    .prod__accent--2 { background: linear-gradient(90deg, #7a5a1f, #c09040); }
    .prod__accent--3 { background: linear-gradient(90deg, #1f4a7a, #4080c0); }
    .prod__accent--4 { background: linear-gradient(90deg, #4a1f7a, #8040c0); }
    .prod__accent--5 { background: linear-gradient(90deg, #1f7a5a, #40c090); }
    .prod__body { padding: 22px 20px 20px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
    .prod__emoji { font-size: 2.2rem; }
    .prod__name  { font-weight: 700; font-size: 1rem; color: var(--text); }
    .prod__desc  { font-size: 0.83rem; color: var(--muted); line-height: 1.6; flex: 1; }
    .prod__foot  { display: flex; align-items: center; justify-content: space-between; margin-top: 8px; padding-top: 12px; border-top: 1px solid rgba(200,184,152,0.3); }
    .prod__price { font-family: var(--font-serif); font-size: 1.15rem; font-weight: 700; color: var(--maroon); }
    .prod__unit  { font-size: 0.7rem; color: var(--muted); }
    .prod__stock { font-size: 0.7rem; color: var(--olive); font-weight: 700; background: rgba(61,82,48,0.1); padding: 4px 10px; border-radius: 20px; }

    /* ── TEAM ── */
    .team-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 26px;
      max-width: 1100px;
      margin: 0 auto;
    }
    .team-card {
      background: rgba(255,255,255,0.85);
      border: 1px solid rgba(200,184,152,0.45);
      border-radius: 22px;
      overflow: hidden;
      transition: all .22s;
      text-align: center;
    }
    .team-card:hover { transform: translateY(-6px); box-shadow: 0 20px 56px rgba(0,0,0,0.12); }
    .team-card__img-wrap {
      width: 100%;
      aspect-ratio: 3 / 4;
      overflow: hidden;
      background: var(--cream);
      position: relative;
    }
    .team-card__img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: top;
      display: block;
      transition: transform .35s;
    }
    .team-card:hover .team-card__img { transform: scale(1.05); }
    .team-card__overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(to top, rgba(122,31,46,0.82) 0%, transparent 55%);
      opacity: 0;
      transition: opacity .3s;
      display: flex;
      align-items: flex-end;
      justify-content: center;
      padding-bottom: 18px;
    }
    .team-card:hover .team-card__overlay { opacity: 1; }
    .team-card__overlay-role {
      font-size: 0.8rem;
      font-weight: 700;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 0.1em;
    }
    .team-card__placeholder {
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, var(--cream), var(--beige));
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .team-card__placeholder .material-symbols-outlined { font-size: 4rem; color: var(--beige); }
    .team-card__info { padding: 18px 16px 22px; }
    .team-card__name {
      font-family: var(--font-serif);
      font-size: 1.05rem;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 8px;
      padding-bottom: 8px;
      position: relative;
    }
    .team-card__name::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 32px;
      height: 2.5px;
      background: var(--maroon);
      border-radius: 2px;
    }
    .team-card__role {
      font-size: 0.76rem;
      color: var(--maroon);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .08em;
      margin-top: 8px;
    }

    /* ── CTA SECTION ── */
    .cta-section {
      position: relative;
      background: var(--cream);
      padding: 96px 56px;
      text-align: center;
      overflow: hidden;
    }
    .cta-section__bg {
      position: absolute;
      inset: 0;
      background: url('assets/bg.png') no-repeat center center / cover;
      opacity: 0.08;
      filter: saturate(0.3);
    }
    .cta-section__inner {
      position: relative;
      z-index: 1;
      max-width: 580px;
      margin: 0 auto;
    }
    .cta-section h2 {
      font-family: var(--font-serif);
      font-size: clamp(1.7rem, 3vw, 2.4rem);
      font-weight: 700;
      color: var(--text);
      margin-bottom: 16px;
    }
    .cta-section p {
      font-size: 0.93rem;
      color: var(--muted);
      line-height: 1.75;
      margin-bottom: 36px;
    }
    .cta-btns { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
    .btn {
      padding: 13px 30px;
      border-radius: 50px;
      font-family: var(--font-sans);
      font-size: 0.9rem;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      transition: all .18s;
      display: inline-flex;
      align-items: center;
      gap: 7px;
    }
    .btn--solid {
      background: var(--maroon);
      border: none;
      color: #fff;
      box-shadow: 0 4px 16px rgba(122,31,46,0.32);
    }
    .btn--solid:hover { background: var(--maroon-dk); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(122,31,46,0.38); }
    .btn--ghost {
      background: transparent;
      border: 2px solid var(--maroon);
      color: var(--maroon);
    }
    .btn--ghost:hover { background: rgba(122,31,46,0.07); transform: translateY(-2px); }

    /* ── FOOTER ── */
    .footer {
      background: var(--maroon-dk);
      padding: 52px 56px 32px;
    }
    .footer__inner {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 40px;
      max-width: 1100px;
      margin: 0 auto 36px;
      align-items: start;
    }
    .footer__brand-name {
      font-family: var(--font-serif);
      font-size: 2rem;
      font-weight: 900;
      color: #fff;
      line-height: 1;
      margin-bottom: 6px;
    }
    .footer__brand-sub {
      font-family: var(--font-serif);
      font-size: 0.75rem;
      color: rgba(255,255,255,0.5);
      letter-spacing: 0.3em;
      text-transform: uppercase;
      margin-bottom: 14px;
    }
    .footer__tagline {
      font-size: 0.84rem;
      color: rgba(255,255,255,0.45);
      line-height: 1.7;
      max-width: 280px;
    }
    .footer__links-title {
      font-size: 0.68rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.18em;
      color: rgba(255,255,255,0.4);
      margin-bottom: 16px;
    }
    .footer__links {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .footer__links a {
      text-decoration: none;
      font-size: 0.88rem;
      color: rgba(255,255,255,0.65);
      transition: color .15s;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .footer__links a:hover { color: #fff; }
    .footer__links a::before {
      content: '';
      display: inline-block;
      width: 16px;
      height: 1.5px;
      background: var(--beige);
      opacity: 0.4;
      border-radius: 2px;
      transition: opacity .15s, width .15s;
    }
    .footer__links a:hover::before { opacity: 0.8; width: 22px; }
    .footer__bottom {
      border-top: 1px solid rgba(255,255,255,0.08);
      padding-top: 24px;
      text-align: center;
      font-size: 0.78rem;
      color: rgba(255,255,255,0.3);
    }

    /* ── MOBILE ── */
    @media (max-width: 900px) {
      .hero__nav { right: 20px; top: 16px; }
      .hero__content { grid-template-columns: 1fr; padding: 0 28px; gap: 28px; }
      .hero__left { padding-top: 120px; }
      .hero__right { padding-top: 0; justify-content: center; }
      .hero__img { height: 300px; }
      .hero__brand-name { font-size: 3.8rem; }
      .features { grid-template-columns: repeat(2, 1fr); }
      .stats-bar__inner { grid-template-columns: repeat(2, 1fr); }
      .stat-item { border-right: none; border-bottom: 1px solid rgba(255,255,255,0.12); }
      .stat-item:nth-child(2n) { border-bottom: none; }
      .team-grid { grid-template-columns: repeat(2, 1fr); }
      .footer__inner { grid-template-columns: 1fr; gap: 28px; }
      .section, .stats-bar, .cta-section { padding-left: 28px; padding-right: 28px; }
      .footer { padding-left: 28px; padding-right: 28px; }
    }
    @media (max-width: 560px) {
      .nav__pill { display: none; }
      .features { grid-template-columns: 1fr; }
      .team-grid { grid-template-columns: 1fr 1fr; gap: 14px; }
      .hero__brand-name { font-size: 3rem; }
    }
  </style>
</head>
<body>
