<?php
require_once __DIR__ . '/guard.php';
requireAuthPage();
requireCustomerPage();
$customerName = $_SESSION['user']['name'] ?? 'Customer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Shop — Esperon Dairy Farm</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link rel="stylesheet" href="css/style.css" />
  <style>
    /* ── Product grid ── */
    .product-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      gap: var(--spacing-lg);
      margin-bottom: var(--spacing-xl);
    }
    .product-card {
      background: rgba(255,255,255,0.38);
      border: 1px solid rgba(255,255,255,0.55);
      border-radius: var(--radius-xl);
      overflow: hidden;
      transition: transform .2s, box-shadow .2s;
      display: flex; flex-direction: column;
    }
    .product-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,0.12); }
    .product-card__img {
      width: 100%; height: 160px; object-fit: cover;
      background: linear-gradient(135deg, rgba(78,96,64,0.12), rgba(107,138,92,0.18));
      display: flex; align-items: center; justify-content: center;
      font-size: 3.5rem;
    }
    .product-card__body { padding: 16px; flex: 1; display: flex; flex-direction: column; gap: 6px; }
    .product-card__name  { font-family: var(--font-serif); font-size: 1rem; font-weight: 700; color: var(--text); }
    .product-card__desc  { font-size: 0.78rem; color: var(--muted); line-height: 1.5; flex: 1; }
    .product-card__price { font-size: 1.1rem; font-weight: 700; color: var(--olive-dark); }
    .product-card__unit  { font-size: 0.72rem; color: var(--muted); }
    .product-card__stock { font-size: 0.75rem; font-weight: 600; }
    .product-card__stock--ok  { color: var(--olive); }
    .product-card__stock--low { color: #f39c12; }
    .product-card__stock--out { color: var(--danger); }
    .product-card__footer { padding: 0 16px 16px; display: flex; align-items: center; gap: 8px; }
    .qty-input {
      width: 56px; padding: 6px 8px; border: 1.5px solid var(--border-light);
      border-radius: 8px; font-size: 0.88rem; text-align: center;
      font-family: var(--font-sans); color: var(--text); background: rgba(255,255,255,0.7);
      outline: none;
    }
    .qty-input:focus { border-color: var(--olive); box-shadow: 0 0 0 3px rgba(78,96,64,0.12); }
    .btn-add {
      flex: 1; padding: 8px 12px; border: none; border-radius: 8px;
      background: linear-gradient(135deg, var(--olive), #6b8a5c);
      color: #fff; font-size: 0.82rem; font-weight: 700;
      font-family: var(--font-sans); cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 5px;
      transition: opacity .15s, transform .15s;
    }
    .btn-add:hover { opacity: .88; transform: translateY(-1px); }
    .btn-add:disabled { background: var(--border); color: var(--muted); cursor: not-allowed; transform: none; }

    /* ── Cart sidebar ── */
    .cart-panel {
      position: fixed; top: 0; right: -420px; width: 400px; height: 100vh;
      background: rgba(250,246,240,0.97);
      backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
      border-left: 1px solid var(--border-light);
      box-shadow: -8px 0 32px rgba(0,0,0,0.12);
      z-index: 9998; display: flex; flex-direction: column;
      transition: right .3s cubic-bezier(.4,0,.2,1);
    }
    .cart-panel.open { right: 0; }
    .cart-overlay {
      display: none; position: fixed; inset: 0; z-index: 9997;
      background: rgba(42,31,21,0.35); backdrop-filter: blur(2px);
    }
    .cart-overlay.open { display: block; }
    .cart-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 18px 20px; border-bottom: 1px solid var(--border-light);
      background: linear-gradient(135deg, var(--olive), #6b8a5c);
    }
    .cart-header h3 { font-family: var(--font-serif); font-size: 1.05rem; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 8px; }
    .cart-close { background: rgba(255,255,255,0.18); border: none; cursor: pointer; width: 28px; height: 28px; border-radius: 50%; color: #fff; display: flex; align-items: center; justify-content: center; }
    .cart-close:hover { background: rgba(255,255,255,0.32); }
    .cart-items { flex: 1; overflow-y: auto; padding: 16px 20px; }
    .cart-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--border-light); }
    .cart-item:last-child { border-bottom: none; }
    .cart-item__icon { width: 40px; height: 40px; border-radius: 10px; background: rgba(78,96,64,0.1); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
    .cart-item__info { flex: 1; min-width: 0; }
    .cart-item__name { font-size: 0.85rem; font-weight: 700; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cart-item__price { font-size: 0.75rem; color: var(--muted); }
    .cart-item__controls { display: flex; align-items: center; gap: 5px; flex-shrink: 0; }
    .qty-btn { width: 24px; height: 24px; border: 1.5px solid var(--border); border-radius: 6px; background: #fff; cursor: pointer; font-size: 0.9rem; font-weight: 700; display: flex; align-items: center; justify-content: center; color: var(--text); transition: background .12s; }
    .qty-btn:hover { background: var(--beige); }
    .cart-item__qty { font-size: 0.85rem; font-weight: 700; min-width: 20px; text-align: center; }
    .cart-item__subtotal { font-size: 0.82rem; font-weight: 700; color: var(--olive-dark); min-width: 60px; text-align: right; }
    .cart-item__remove { background: none; border: none; cursor: pointer; color: var(--muted); padding: 2px; display: flex; align-items: center; }
    .cart-item__remove:hover { color: var(--danger); }
    .cart-footer { padding: 16px 20px; border-top: 1px solid var(--border-light); background: rgba(255,255,255,0.5); }
    .cart-total { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
    .cart-total__label { font-size: 0.88rem; font-weight: 600; color: var(--text); }
    .cart-total__val   { font-family: var(--font-serif); font-size: 1.2rem; font-weight: 700; color: var(--olive-dark); }
    .btn-checkout {
      width: 100%; padding: 12px; border: none; border-radius: 10px;
      background: linear-gradient(135deg, var(--olive), #6b8a5c);
      color: #fff; font-size: 0.95rem; font-weight: 700;
      font-family: var(--font-sans); cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 8px;
      transition: opacity .15s, transform .15s;
      box-shadow: 0 2px 8px rgba(78,96,64,0.25);
    }
    .btn-checkout:hover { opacity: .88; transform: translateY(-1px); }
    .btn-checkout:disabled { background: var(--border); color: var(--muted); cursor: not-allowed; transform: none; box-shadow: none; }
    .cart-empty { text-align: center; padding: 40px 20px; color: var(--muted); }
    .cart-empty .material-symbols-outlined { font-size: 3rem; display: block; margin-bottom: 10px; color: var(--border); }

    /* ── Cart badge ── */
    .cart-fab {
      position: fixed; bottom: 28px; right: 28px; z-index: 9996;
      width: 56px; height: 56px; border-radius: 50%;
      background: linear-gradient(135deg, var(--olive), #6b8a5c);
      color: #fff; border: none; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 4px 20px rgba(78,96,64,0.4);
      transition: transform .2s, box-shadow .2s;
    }
    .cart-fab:hover { transform: scale(1.08); box-shadow: 0 6px 28px rgba(78,96,64,0.5); }
    .cart-fab .material-symbols-outlined { font-size: 1.6rem; }
    .cart-badge {
      position: absolute; top: -4px; right: -4px;
      background: var(--danger); color: #fff;
      border-radius: 50%; width: 20px; height: 20px;
      font-size: 0.65rem; font-weight: 700;
      display: none; align-items: center; justify-content: center;
      border: 2px solid #fff;
    }
    .cart-badge.visible { display: flex; }

    /* ── Out of stock overlay ── */
    .product-card.out-of-stock { opacity: .7; }
    .out-of-stock-banner {
      background: var(--danger-lt); color: var(--danger);
      font-size: 0.72rem; font-weight: 700; text-align: center;
      padding: 4px; text-transform: uppercase; letter-spacing: .05em;
    }

    /* ── Star rating ── */
    .stars { display:inline-flex; gap:1px; }
    .star  { font-size:0.9rem; color:#d4c9b8; }
    .star.filled { color:#f39c12; }
    .star-btn { font-size:1.6rem; color:#d4c9b8; background:none; border:none; cursor:pointer; padding:0 2px; transition:color .12s, transform .12s; line-height:1; }
    .star-btn:hover, .star-btn.active { color:#f39c12; transform:scale(1.15); }
    .review-count { font-size:0.72rem; color:var(--muted); margin-left:4px; }
    .product-card__reviews { display:flex; align-items:center; gap:4px; margin-top:2px; }
    .btn-review {
      width:100%; padding:7px 10px; border:1.5px solid var(--olive); border-radius:8px;
      background:transparent; color:var(--olive-dark); font-size:0.78rem; font-weight:700;
      font-family:var(--font-sans); cursor:pointer; display:flex; align-items:center;
      justify-content:center; gap:4px; transition:background .15s, color .15s;
      margin-top:6px;
    }
    .btn-review:hover { background:var(--olive); color:#fff; }

    /* ── Review modal ── */
    @keyframes revSlide { from{opacity:0;transform:translateY(-14px) scale(0.97)} to{opacity:1;transform:none} }
    .rev-overlay { display:none;position:fixed;inset:0;z-index:9999;background:rgba(42,31,21,0.5);backdrop-filter:blur(5px);align-items:center;justify-content:center;padding:16px; }
    .rev-overlay.open { display:flex; }
    .rev-modal { background:#faf6f0;border-radius:20px;box-shadow:0 16px 56px rgba(0,0,0,0.2);width:100%;max-width:520px;max-height:90vh;overflow-y:auto;animation:revSlide .25s ease;font-family:'Lato',sans-serif; }
    .rev-modal__head { display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;background:linear-gradient(135deg,var(--olive),#6b8a5c);position:sticky;top:0;z-index:1; }
    .rev-modal__head h3 { font-family:'Playfair Display',serif;font-size:1.05rem;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px; }
    .rev-modal__close { background:rgba(255,255,255,0.18);border:none;cursor:pointer;width:28px;height:28px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center; }
    .rev-modal__body { padding:20px 22px; }
    .rev-summary { display:flex;align-items:center;gap:20px;padding:14px 18px;background:rgba(255,255,255,0.5);border-radius:12px;margin-bottom:18px; }
    .rev-avg { font-family:'Playfair Display',serif;font-size:2.8rem;font-weight:700;color:var(--text);line-height:1; }
    .rev-item { padding:12px 0;border-bottom:1px solid var(--border-light); }
    .rev-item:last-child { border-bottom:none; }
    .rev-item__header { display:flex;align-items:center;justify-content:space-between;margin-bottom:4px; }
    .rev-item__name { font-weight:700;font-size:0.85rem;color:var(--text); }
    .rev-item__date { font-size:0.72rem;color:var(--muted); }
    .rev-item__title { font-weight:700;font-size:0.88rem;color:var(--text);margin-bottom:3px; }
    .rev-item__comment { font-size:0.82rem;color:var(--muted);line-height:1.5; }
    .verified-badge { display:inline-flex;align-items:center;gap:3px;font-size:0.68rem;font-weight:700;color:var(--olive-dark);background:var(--success-lt);padding:2px 7px;border-radius:20px;margin-left:6px; }

    /* ── Success modal ── */
    @keyframes orderSuccess { from{opacity:0;transform:scale(0.9)} to{opacity:1;transform:scale(1)} }
  </style>
</head>
<body>
<nav class="nav" id="app-nav"></nav>

<main class="main">
  <div class="page-header">
    <div>
      <h1 class="page-title">Shop</h1>
      <p class="page-subtitle">Fresh dairy products from Esperon Farm — delivered to your door.</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
      <input type="text" id="search-input" placeholder="🔍 Search products…"
             style="padding:8px 14px;border:1.5px solid var(--border-light);border-radius:10px;font-size:0.85rem;font-family:var(--font-sans);background:rgba(255,255,255,0.6);outline:none;width:200px;"
             oninput="filterProducts()" />
    </div>
  </div>

  <div id="product-grid" class="product-grid">
    <div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--muted);">
      <span class="spinner"></span> Loading products…
    </div>
  </div>
</main>

<!-- ── Cart FAB ── -->
<button class="cart-fab" id="cart-fab" onclick="openCart()" title="View Cart">
  <span class="material-symbols-outlined">shopping_cart</span>
  <span class="cart-badge" id="cart-badge">0</span>
</button>

<!-- ── Cart overlay ── -->
<div class="cart-overlay" id="cart-overlay" onclick="closeCart()"></div>

<!-- ── Cart panel ── -->
<div class="cart-panel" id="cart-panel">
  <div class="cart-header">
    <h3><span class="material-symbols-outlined" style="font-size:1.1rem;">shopping_cart</span> My Cart</h3>
    <button class="cart-close" onclick="closeCart()">
      <span class="material-symbols-outlined" style="font-size:1rem;">close</span>
    </button>
  </div>
  <div class="cart-items" id="cart-items">
    <div class="cart-empty">
      <span class="material-symbols-outlined">shopping_cart</span>
      <p>Your cart is empty.</p>
    </div>
  </div>
  <div class="cart-footer">
    <div class="cart-total">
      <span class="cart-total__label">Total</span>
      <span class="cart-total__val" id="cart-total">₱0.00</span>
    </div>
    <button class="btn-checkout" id="checkout-btn" onclick="checkout()" disabled>
      <span class="material-symbols-outlined" style="font-size:1rem;">payments</span>
      Place Order
    </button>
  </div>
</div>

<div class="rev-overlay" id="rev-overlay">
  <div class="rev-modal">
    <div class="rev-modal__head">
      <h3><span class="material-symbols-outlined" style="font-size:1.1rem;">rate_review</span>
        <span id="rev-product-name"></span>
      </h3>
      <button class="rev-modal__close" onclick="closeReviewModal()">
        <span class="material-symbols-outlined" style="font-size:1rem;">close</span>
      </button>
    </div>
    <div class="rev-modal__body">
      <!-- Summary -->
      <div class="rev-summary" id="rev-summary"></div>

      <!-- Write / Edit form -->
      <div id="rev-form-section" style="display:none;margin-bottom:20px;">
        <div style="font-size:0.85rem;font-weight:700;color:var(--text);margin-bottom:10px;" id="rev-form-title">Write a Review</div>
        <div style="margin-bottom:10px;">
          <div style="font-size:0.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;">Your Rating <span style="color:var(--danger);">*</span></div>
          <div id="rev-star-picker" style="display:flex;gap:2px;"></div>
        </div>
        <div style="margin-bottom:10px;">
          <div style="font-size:0.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px;">Title <span style="font-weight:400;">(optional)</span></div>
          <input id="rev-title-input" type="text" placeholder="Summarize your experience…"
                 style="width:100%;padding:9px 13px;border:1.5px solid var(--border-light);border-radius:9px;font-size:0.88rem;font-family:'Lato',sans-serif;color:var(--text);background:#fff;outline:none;box-sizing:border-box;" />
        </div>
        <div style="margin-bottom:14px;">
          <div style="font-size:0.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px;">Comment <span style="font-weight:400;">(optional)</span></div>
          <textarea id="rev-comment-input" rows="3" placeholder="Tell others what you think…"
                    style="width:100%;padding:9px 13px;border:1.5px solid var(--border-light);border-radius:9px;font-size:0.88rem;font-family:'Lato',sans-serif;color:var(--text);background:#fff;outline:none;resize:vertical;box-sizing:border-box;"></textarea>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
          <button id="rev-delete-btn" onclick="deleteReview()" style="display:none;padding:9px 16px;border:1.5px solid rgba(192,57,43,0.3);border-radius:9px;background:var(--danger-lt);color:var(--danger);font-family:'Lato',sans-serif;font-size:0.83rem;font-weight:700;cursor:pointer;display:none;align-items:center;gap:5px;">
            <span class="material-symbols-outlined" style="font-size:0.9rem;">delete</span> Delete
          </button>
          <button id="rev-submit-btn" onclick="submitReview()"
                  style="margin-left:auto;padding:9px 22px;border:none;border-radius:9px;background:linear-gradient(135deg,var(--olive),#6b8a5c);color:#fff;font-family:'Lato',sans-serif;font-size:0.85rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px;">
            <span class="material-symbols-outlined" style="font-size:0.9rem;">send</span> Submit Review
          </button>
        </div>
      </div>

      <!-- Reviews list -->
      <div style="font-size:0.85rem;font-weight:700;color:var(--text);margin-bottom:10px;">Customer Reviews</div>
      <div id="rev-list"></div>
    </div>
  </div>
</div>

<script src="js/api.js"></script>
<script src="js/ui.js"></script>
<script src="js/nav.js"></script>
<script>
let _products = [];
let _cart     = { items: [], total: 0 };
let _reviewCache = {};

// ── Product emoji map ─────────────────────────────────────
const PRODUCT_EMOJI = {
  'milk':    '🥛', 'cheese': '🧀', 'butter': '🧈',
  'yogurt':  '🫙', 'cream':  '🍶', 'skim':   '🥛',
  'mozzarella': '🧀',
};
function getEmoji(name) {
  const lower = name.toLowerCase();
  for (const [key, emoji] of Object.entries(PRODUCT_EMOJI)) {
    if (lower.includes(key)) return emoji;
  }
  return '🛒';
}

// ── Stars helper ──────────────────────────────────────────
function starsHTML(rating, size = '0.9rem') {
  return Array.from({length:5}, (_,i) =>
    `<span class="star${i < Math.round(rating) ? ' filled' : ''}" style="font-size:${size};">★</span>`
  ).join('');
}

// ── Load products ─────────────────────────────────────────
async function loadProducts() {
  try {
    _products = await API.products.getAll();
    await loadAllReviewStats();
    renderProducts(_products);
  } catch(e) {
    document.getElementById('product-grid').innerHTML =
      `<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--danger);">${e.message}</div>`;
  }
}

async function loadAllReviewStats() {
  await Promise.all(_products.map(async p => {
    try {
      _reviewCache[p.product_id] = await API.reviews.getByProduct(p.product_id);
    } catch(e) {
      _reviewCache[p.product_id] = { stats: { total: 0, avg_rating: null }, reviews: [], my_review: null };
    }
  }));
}

// ── Filter ────────────────────────────────────────────────
function filterProducts() {
  const q = document.getElementById('search-input').value.toLowerCase();
  renderProducts(q ? _products.filter(p => p.name.toLowerCase().includes(q) || (p.description||'').toLowerCase().includes(q)) : _products);
}

// ── Render products ───────────────────────────────────────
function renderProducts(list) {
  const grid = document.getElementById('product-grid');
  if (!list.length) {
    grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--muted);">
      <span class="material-symbols-outlined" style="font-size:3rem;display:block;margin-bottom:10px;color:var(--border);">search_off</span>
      <p>No products found.</p></div>`;
    return;
  }

  grid.innerHTML = list.map(p => {
    const outOfStock = p.stock_qty < 1;
    const lowStock   = p.stock_qty > 0 && p.stock_qty <= 5;
    const stockClass = outOfStock ? 'out' : lowStock ? 'low' : 'ok';
    const stockLabel = outOfStock ? 'Out of stock' : lowStock ? `Only ${p.stock_qty} left!` : `${p.stock_qty} in stock`;
    const emoji      = getEmoji(p.name);

    const cache  = _reviewCache[p.product_id] || {};
    const stats  = cache.stats || {};
    const total  = parseInt(stats.total || 0);
    const avg    = parseFloat(stats.avg_rating || 0);
    const myRev  = cache.my_review;

    return `<div class="product-card${outOfStock ? ' out-of-stock' : ''}">
      ${outOfStock ? '<div class="out-of-stock-banner">Out of Stock</div>' : ''}
      <div class="product-card__img">${emoji}</div>
      <div class="product-card__body">
        <div class="product-card__name">${p.name}</div>
        <div class="product-card__desc">${p.description || ''}</div>
        <div class="product-card__reviews">
          <div class="stars">${starsHTML(avg)}</div>
          <span class="review-count">${total > 0 ? avg.toFixed(1) + ' (' + total + ')' : 'No reviews yet'}</span>
        </div>
        <div style="display:flex;align-items:baseline;gap:6px;margin-top:4px;">
          <div class="product-card__price">₱${parseFloat(p.price).toFixed(2)}</div>
          <div class="product-card__unit">/ ${p.unit}</div>
        </div>
        <div class="product-card__stock product-card__stock--${stockClass}">${stockLabel}</div>
      </div>
      <div class="product-card__footer" style="flex-direction:column;gap:6px;">
        <div style="display:flex;gap:8px;width:100%;">
          <input type="number" class="qty-input" id="qty-${p.product_id}"
                 value="1" min="1" max="${p.stock_qty}" ${outOfStock ? 'disabled' : ''} />
          <button class="btn-add" style="flex:1;" onclick="addToCart(${p.product_id})" ${outOfStock ? 'disabled' : ''}>
            <span class="material-symbols-outlined" style="font-size:0.9rem;">add_shopping_cart</span> Add
          </button>
        </div>
        <button class="btn-review" onclick="openReviewModal(${p.product_id}, '${p.name.replace(/'/g,"\\'")}')">
          <span class="material-symbols-outlined" style="font-size:0.9rem;">${myRev ? 'edit' : 'rate_review'}</span>
          ${myRev ? 'Edit My Review' : (total > 0 ? 'See Reviews' : 'Write a Review')}
        </button>
      </div>
    </div>`;
  }).join('');
}

// ── Review modal ──────────────────────────────────────────
let _revProductId = null, _revProductName = '', _revEditId = null, _revSelectedRating = 0;

function openReviewModal(productId, productName) {
  _revProductId = productId; _revProductName = productName;
  _revEditId = null; _revSelectedRating = 0;
  document.getElementById('rev-product-name').textContent = productName;
  document.getElementById('rev-list').innerHTML = '<p style="color:var(--muted);font-size:0.84rem;text-align:center;padding:16px 0;">Loading…</p>';
  document.getElementById('rev-form-section').style.display = 'none';
  document.getElementById('rev-overlay').classList.add('open');
  loadReviewModal(productId);
}

async function loadReviewModal(productId) {
  try {
    const data = await API.reviews.getByProduct(productId);
    _reviewCache[productId] = data;
    renderReviewModal(data);
  } catch(e) {
    document.getElementById('rev-list').innerHTML = `<p style="color:var(--danger);font-size:0.84rem;">${e.message}</p>`;
  }
}

function renderReviewModal(data) {
  const stats = data.stats || {}, reviews = data.reviews || [], myRev = data.my_review;
  const total = parseInt(stats.total || 0), avg = parseFloat(stats.avg_rating || 0);

  document.getElementById('rev-summary').innerHTML = total > 0
    ? `<div class="rev-avg">${avg.toFixed(1)}</div>
       <div>
         <div class="stars">${starsHTML(avg, '1.1rem')}</div>
         <div style="font-size:0.78rem;color:var(--muted);margin-top:4px;">${total} review${total!==1?'s':''}</div>
         <div style="font-size:0.72rem;color:var(--muted);margin-top:6px;">
           ${[5,4,3,2,1].map(n => {
             const cnt = parseInt(stats[['five','four','three','two','one'][5-n]]||0);
             const pct = total > 0 ? Math.round(cnt/total*100) : 0;
             return `<div style="display:flex;align-items:center;gap:6px;margin-bottom:2px;">
               <span style="font-size:0.7rem;width:8px;">${n}</span>
               <span style="font-size:0.75rem;color:#f39c12;">★</span>
               <div style="flex:1;height:6px;background:var(--beige);border-radius:3px;overflow:hidden;">
                 <div style="width:${pct}%;height:100%;background:#f39c12;border-radius:3px;"></div>
               </div>
               <span style="font-size:0.7rem;width:24px;text-align:right;">${cnt}</span>
             </div>`;
           }).join('')}
         </div>
       </div>`
    : `<div style="color:var(--muted);font-size:0.84rem;">No reviews yet. Be the first!</div>`;

  const formSection = document.getElementById('rev-form-section');
  formSection.style.display = 'block';
  document.getElementById('rev-form-title').textContent = myRev ? 'Edit Your Review' : 'Write a Review';
  document.getElementById('rev-title-input').value   = myRev ? (myRev.title   || '') : '';
  document.getElementById('rev-comment-input').value = myRev ? (myRev.comment || '') : '';
  _revEditId = myRev ? myRev.review_id : null;
  _revSelectedRating = myRev ? myRev.rating : 0;
  renderStarPicker(_revSelectedRating);
  const delBtn = document.getElementById('rev-delete-btn');
  if (delBtn) delBtn.style.display = myRev ? 'inline-flex' : 'none';

  const listEl = document.getElementById('rev-list');
  if (!reviews.length) {
    listEl.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;text-align:center;padding:16px 0;">No reviews yet.</p>';
    return;
  }
  listEl.innerHTML = reviews.map(r => {
    const date = new Date(r.created_at).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'});
    return `<div class="rev-item">
      <div class="rev-item__header">
        <div style="display:flex;align-items:center;gap:6px;">
          <span class="rev-item__name">${r.Customer_Name}</span>
          ${r.is_verified_purchase ? '<span class="verified-badge"><span class="material-symbols-outlined" style="font-size:0.7rem;">verified</span> Verified</span>' : ''}
        </div>
        <span class="rev-item__date">${date}</span>
      </div>
      <div class="stars" style="margin-bottom:4px;">${starsHTML(r.rating,'0.85rem')}</div>
      ${r.title   ? `<div class="rev-item__title">${r.title}</div>` : ''}
      ${r.comment ? `<div class="rev-item__comment">${r.comment}</div>` : ''}
    </div>`;
  }).join('');
}

function renderStarPicker(selected) {
  document.getElementById('rev-star-picker').innerHTML = [1,2,3,4,5].map(n =>
    `<button type="button" class="star-btn${n<=selected?' active':''}" onclick="selectStar(${n})" title="${n} star${n>1?'s':''}">★</button>`
  ).join('');
}
function selectStar(n) { _revSelectedRating = n; renderStarPicker(n); }

async function submitReview() {
  if (!_revSelectedRating) { UI.toast('Please select a star rating.', 'error'); return; }
  const title = document.getElementById('rev-title-input').value.trim();
  const comment = document.getElementById('rev-comment-input').value.trim();
  const btn = document.getElementById('rev-submit-btn');
  btn.disabled = true;
  try {
    if (_revEditId) {
      await API.reviews.update(_revEditId, { rating: _revSelectedRating, title, comment });
      UI.toast('Review updated!', 'success');
    } else {
      await API.reviews.submit({ product_id: _revProductId, rating: _revSelectedRating, title, comment });
      UI.toast('Review submitted! Thank you.', 'success');
    }
    await loadReviewModal(_revProductId);
    renderProducts(_products);
  } catch(e) { UI.toast(e.message, 'error'); }
  finally { btn.disabled = false; }
}

async function deleteReview() {
  if (!_revEditId) return;
  const ok = await UI.confirm('Delete your review?', 'Delete');
  if (!ok) return;
  try {
    await API.reviews.delete(_revEditId);
    UI.toast('Review deleted.', 'success');
    _revEditId = null;
    await loadReviewModal(_revProductId);
    renderProducts(_products);
  } catch(e) { UI.toast(e.message, 'error'); }
}

function closeReviewModal() {
  document.getElementById('rev-overlay').classList.remove('open');
}

// ── Add to cart ───────────────────────────────────────────
async function addToCart(productId) {
  const qtyEl = document.getElementById('qty-' + productId);
  const qty   = parseInt(qtyEl?.value || '1', 10);
  try {
    _cart = await API.cart.add(productId, qty);
    renderCart();
    UI.toast('Added to cart!', 'success');
    openCart();
  } catch(e) {
    UI.toast(e.message, 'error');
  }
}

// ── Load cart ─────────────────────────────────────────────
async function loadCart() {
  try {
    _cart = await API.cart.get();
    renderCart();
  } catch(e) { /* non-critical */ }
}

// ── Render cart ───────────────────────────────────────────
function renderCart() {
  const container = document.getElementById('cart-items');
  const totalEl   = document.getElementById('cart-total');
  const badge     = document.getElementById('cart-badge');
  const checkBtn  = document.getElementById('checkout-btn');

  const items = _cart.items || [];
  const total = _cart.total || 0;
  const count = items.reduce((s, i) => s + i.quantity, 0);

  // Badge
  badge.textContent = count;
  badge.classList.toggle('visible', count > 0);

  // Total
  totalEl.textContent = '₱' + parseFloat(total).toFixed(2);

  // Checkout button
  checkBtn.disabled = items.length === 0;

  if (!items.length) {
    container.innerHTML = `<div class="cart-empty">
      <span class="material-symbols-outlined">shopping_cart</span>
      <p>Your cart is empty.<br>Browse products and add items!</p>
    </div>`;
    return;
  }

  container.innerHTML = items.map(item => `
    <div class="cart-item">
      <div class="cart-item__icon">${getEmoji(item.name)}</div>
      <div class="cart-item__info">
        <div class="cart-item__name">${item.name}</div>
        <div class="cart-item__price">₱${parseFloat(item.unit_price).toFixed(2)} / ${item.unit}</div>
      </div>
      <div class="cart-item__controls">
        <button class="qty-btn" onclick="changeQty(${item.product_id}, ${item.quantity - 1})">−</button>
        <span class="cart-item__qty">${item.quantity}</span>
        <button class="qty-btn" onclick="changeQty(${item.product_id}, ${item.quantity + 1})">+</button>
      </div>
      <div class="cart-item__subtotal">₱${parseFloat(item.subtotal).toFixed(2)}</div>
      <button class="cart-item__remove" onclick="removeFromCart(${item.product_id})" title="Remove">
        <span class="material-symbols-outlined" style="font-size:1rem;">close</span>
      </button>
    </div>
  `).join('');
}

async function changeQty(productId, newQty) {
  try {
    _cart = await API.cart.update(productId, newQty);
    renderCart();
  } catch(e) { UI.toast(e.message, 'error'); }
}

async function removeFromCart(productId) {
  try {
    _cart = await API.cart.remove(productId);
    renderCart();
    UI.toast('Item removed.', 'success');
  } catch(e) { UI.toast(e.message, 'error'); }
}

// ── Checkout ──────────────────────────────────────────────
async function checkout() {
  const btn = document.getElementById('checkout-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="display:block;border-top-color:#fff;"></span> Processing…';

  try {
    const result = await API.cart.checkout();
    closeCart();

    // Success modal
    const modal = document.createElement('div');
    modal.style.cssText = 'position:fixed;inset:0;z-index:99999;background:rgba(42,31,21,0.5);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;padding:16px;';
    modal.innerHTML = `
      <div style="background:#faf6f0;border-radius:20px;box-shadow:0 16px 56px rgba(0,0,0,0.2);width:100%;max-width:420px;padding:32px 28px;text-align:center;animation:orderSuccess .3s ease;font-family:'Lato',sans-serif;">
        <div style="font-size:3.5rem;margin-bottom:12px;">🎉</div>
        <div style="font-family:'Playfair Display',serif;font-size:1.3rem;font-weight:700;color:var(--text);margin-bottom:8px;">Order Placed!</div>
        <p style="font-size:0.88rem;color:var(--muted);margin-bottom:20px;">Thank you for your purchase. Your order total was <strong style="color:var(--olive-dark);">₱${parseFloat(result.total).toFixed(2)}</strong>.</p>
        <button onclick="this.closest('div[style]').remove();loadProducts();"
                style="padding:10px 28px;border:none;border-radius:10px;background:linear-gradient(135deg,var(--olive),#6b8a5c);color:#fff;font-family:'Lato',sans-serif;font-size:0.9rem;font-weight:700;cursor:pointer;">
          Continue Shopping
        </button>
      </div>`;
    document.body.appendChild(modal);
    modal.addEventListener('click', e => { if (e.target === modal) { modal.remove(); loadProducts(); } });

    // Refresh cart and products
    _cart = { items: [], total: 0 };
    renderCart();
    loadProducts();

  } catch(e) {
    UI.toast(e.message, 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:1rem;">payments</span> Place Order';
  }
}

// ── Cart panel open/close ─────────────────────────────────
function openCart()  {
  document.getElementById('cart-panel').classList.add('open');
  document.getElementById('cart-overlay').classList.add('open');
}
function closeCart() {
  document.getElementById('cart-panel').classList.remove('open');
  document.getElementById('cart-overlay').classList.remove('open');
}

// ── Keyframe for success modal ────────────────────────────
const style = document.createElement('style');
style.textContent = '@keyframes orderSuccess{from{opacity:0;transform:scale(0.9)}to{opacity:1;transform:scale(1)}}';
document.head.appendChild(style);

// ── Init ──────────────────────────────────────────────────
loadProducts();
loadCart();
</script>
</body>
</html>
