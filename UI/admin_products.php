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
  <title>Products — Esperon Dairy Farm</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link rel="stylesheet" href="css/style.css" />
  <style>
    .stock-badge { display:inline-block; padding:2px 10px; border-radius:20px; font-size:0.7rem; font-weight:700; }
    .stock-badge--ok  { background:var(--success-lt); color:var(--olive-dark); }
    .stock-badge--low { background:var(--warning-lt); color:#7a5a1e; }
    .stock-badge--out { background:var(--danger-lt);  color:var(--danger); }
    .inactive-row td { opacity: .55; }
  </style>
</head>
<body>
<nav class="nav" id="app-nav"></nav>

<main class="main">
  <div class="page-header">
    <div>
      <h1 class="page-title">Products</h1>
      <p class="page-subtitle">Manage the shop product catalogue — prices, stock, and availability.</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;" id="products-header-actions">
      <button class="btn btn--primary" onclick="openModal()">＋ Add Product</button>
    </div>
  </div>

  <div class="card">
    <div class="card__header">
      <span class="card__title">All Products</span>
      <label style="display:flex;align-items:center;gap:6px;font-size:0.82rem;color:var(--muted);cursor:pointer;">
        <input type="checkbox" id="show-inactive" onchange="loadProducts()" style="accent-color:var(--olive);" />
        Show inactive
      </label>
    </div>
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Unit</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="products-body">
          <tr><td colspan="7" class="tbl-empty"><span class="spinner"></span> Loading…</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</main>

<!-- Add / Edit Modal -->
<div class="modal-overlay" id="modal-overlay">
  <div class="modal" style="max-width:520px;">
    <div class="modal__head">
      <span class="modal__title" id="modal-title">Add Product</span>
      <button class="modal__close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal__body">
      <form class="form-grid" onsubmit="return false">
        <div class="form-group form-group--full">
          <label>Product Name <span style="color:var(--danger);">*</span></label>
          <input id="f-name" type="text" placeholder="e.g. Fresh Whole Milk" required />
        </div>
        <div class="form-group form-group--full">
          <label>Description</label>
          <input id="f-desc" type="text" placeholder="Short description (optional)" />
        </div>
        <div class="form-group">
          <label>Price (₱) <span style="color:var(--danger);">*</span></label>
          <input id="f-price" type="number" min="0" step="0.01" placeholder="e.g. 55.00" required />
        </div>
        <div class="form-group">
          <label>Stock Quantity <span style="color:var(--danger);">*</span></label>
          <input id="f-stock" type="number" min="0" placeholder="e.g. 100" required />
        </div>
        <div class="form-group">
          <label>Unit</label>
          <select id="f-unit">
            <option value="pcs">pcs</option>
            <option value="L">L (liters)</option>
            <option value="kg">kg</option>
            <option value="g">g</option>
            <option value="pack">pack</option>
          </select>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select id="f-active">
            <option value="1">Active (visible in shop)</option>
            <option value="0">Inactive (hidden)</option>
          </select>
        </div>
      </form>
    </div>
    <div class="modal__foot">
      <button class="btn btn--ghost" onclick="closeModal()">Cancel</button>
      <button class="btn btn--primary" onclick="saveProduct()">Save Product</button>
    </div>
  </div>
</div>

<script src="js/api.js"></script>
<script src="js/ui.js"></script>
<script src="js/nav.js"></script>
<script>
let editingId    = null;
let _productsData = [];

async function loadProducts() {
  const tbody    = document.getElementById('products-body');
  const showAll  = document.getElementById('show-inactive').checked;
  UI.setLoading(tbody, 7);
  try {
    _productsData = showAll ? await API.products.getAdmin() : await API.products.getAll();
    if (!_productsData.length) { UI.setEmpty(tbody, 7, 'No products found.'); return; }

    tbody.innerHTML = _productsData.map(p => {
      const outOfStock = p.stock_qty < 1;
      const lowStock   = p.stock_qty > 0 && p.stock_qty <= 5;
      const stockClass = outOfStock ? 'out' : lowStock ? 'low' : 'ok';
      const stockLabel = outOfStock ? 'Out of stock' : lowStock ? `Low (${p.stock_qty})` : p.stock_qty;

      return `<tr class="${!p.is_active ? 'inactive-row' : ''}">
        <td><strong>#${p.product_id}</strong></td>
        <td>${p.name}</td>
        <td>₱${parseFloat(p.price).toFixed(2)}</td>
        <td><span class="stock-badge stock-badge--${stockClass}">${stockLabel}</span></td>
        <td>${p.unit}</td>
        <td>
          <span class="badge ${p.is_active ? 'badge--green' : 'badge--muted'}">
            ${p.is_active ? 'Active' : 'Inactive'}
          </span>
        </td>
        <td class="actions">
          <button class="btn btn--icon btn--edit" onclick="openModal(${p.product_id})">✏ Edit</button>
          <button class="btn btn--icon" onclick="toggleActive(${p.product_id}, ${p.is_active})"
                  style="background:${p.is_active ? 'rgba(192,57,43,0.08)' : 'rgba(78,96,64,0.08)'};color:${p.is_active ? 'var(--danger)' : 'var(--olive)'};">
            ${p.is_active ? '🚫 Hide' : '✅ Show'}
          </button>
          <button class="btn btn--icon btn--del" onclick="deleteProduct(${p.product_id})">🗑 Del</button>
        </td>
      </tr>`;
    }).join('');
  } catch(e) {
    UI.toast(e.message, 'error');
    UI.setEmpty(tbody, 7, 'Failed to load products.');
  }
}

function openModal(id = null) {
  editingId = id;
  document.getElementById('modal-title').textContent = id ? 'Edit Product' : 'Add Product';

  if (!id) {
    document.getElementById('f-name').value   = '';
    document.getElementById('f-desc').value   = '';
    document.getElementById('f-price').value  = '';
    document.getElementById('f-stock').value  = '';
    document.getElementById('f-unit').value   = 'pcs';
    document.getElementById('f-active').value = '1';
  } else {
    const p = _productsData.find(x => x.product_id == id);
    if (p) {
      document.getElementById('f-name').value   = p.name;
      document.getElementById('f-desc').value   = p.description || '';
      document.getElementById('f-price').value  = p.price;
      document.getElementById('f-stock').value  = p.stock_qty;
      document.getElementById('f-unit').value   = p.unit;
      document.getElementById('f-active').value = p.is_active ? '1' : '0';
    }
  }
  document.getElementById('modal-overlay').classList.add('modal-overlay--open');
}

function closeModal() {
  document.getElementById('modal-overlay').classList.remove('modal-overlay--open');
  editingId = null;
}

async function saveProduct() {
  const name  = document.getElementById('f-name').value.trim();
  const price = parseFloat(document.getElementById('f-price').value);
  const stock = parseInt(document.getElementById('f-stock').value, 10);

  if (!name)       { UI.toast('Product name is required.', 'error'); return; }
  if (isNaN(price) || price < 0) { UI.toast('Enter a valid price.', 'error'); return; }
  if (isNaN(stock) || stock < 0) { UI.toast('Enter a valid stock quantity.', 'error'); return; }

  const data = {
    name,
    description: document.getElementById('f-desc').value.trim() || null,
    price,
    stock_qty:   stock,
    unit:        document.getElementById('f-unit').value,
    is_active:   parseInt(document.getElementById('f-active').value, 10),
  };

  try {
    if (editingId) {
      await API.products.update(editingId, data);
      UI.toast('Product updated!', 'success');
    } else {
      await API.products.create(data);
      UI.toast('Product created!', 'success');
    }
    closeModal();
    loadProducts();
  } catch(e) { UI.toast(e.message, 'error'); }
}

async function toggleActive(id, currentActive) {
  try {
    await API.products.patch(id, { is_active: currentActive ? 0 : 1 });
    UI.toast(currentActive ? 'Product hidden from shop.' : 'Product visible in shop.', 'success');
    loadProducts();
  } catch(e) { UI.toast(e.message, 'error'); }
}

async function deleteProduct(id) {
  const ok = await UI.confirm('Delete this product? This cannot be undone.');
  if (!ok) return;
  try {
    await API.products.delete(id);
    UI.toast('Product deleted.', 'success');
    loadProducts();
  } catch(e) { UI.toast(e.message, 'error'); }
}

loadProducts();
</script>
</body>
</html>
