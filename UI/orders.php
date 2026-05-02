<?php
require_once __DIR__ . '/guard.php';
requireAuthPage();
$_isAdmin = ($_SESSION['user']['role'] ?? '') === 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Orders — Esperon Dairy Farm</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link rel="stylesheet" href="css/style.css" />
  <?php if (!$_isAdmin): ?>
  <style>.admin-only { display: none !important; }</style>
  <?php endif; ?>
</head>
<body>

<nav class="nav" id="app-nav"></nav>

<main class="main">
  <div class="page-header">
    <div>
      <h1 class="page-title">Orders</h1>
      <p class="page-subtitle">Full order history with customer, cow, and worker details.</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;" id="orders-header-actions">
      <button class="btn btn--primary" onclick="openModal()">＋ New Order</button>
    </div>
  </div>

  <div class="card">
    <div class="card__header">
      <span class="card__title">All Orders</span>
      <div style="display:flex;gap:8px;align-items:center;">
        <?php if (!$_isAdmin): ?>
        <label style="display:flex;align-items:center;gap:6px;font-size:0.82rem;color:var(--muted);cursor:pointer;">
          <input type="checkbox" id="my-orders-toggle" onchange="toggleMyOrders()" style="accent-color:var(--olive);" />
          My orders only
        </label>
        <?php endif; ?>
        <input type="text" id="search" placeholder="🔍 Search orders…"
          style="width:200px;padding:7px 12px;font-size:.83rem"
          oninput="debounceSearch()" />
      </div>
    </div>
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Customer</th>
            <th>Address</th>
            <th>Contact</th>
            <th>Order</th>
            <th>Date</th>
            <th>Qty (L)</th>
            <th>Unit Price</th>
            <th>Total</th>
            <th>Status</th>
            <th>Cow</th>
            <th>Worker</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="orders-body">
          <tr><td colspan="13" class="tbl-empty"><span class="spinner"></span> Loading…</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</main>

<!-- Modal -->
<div class="modal-overlay" id="modal-overlay">
  <div class="modal" style="max-width:580px">
    <div class="modal__head">
      <span class="modal__title" id="modal-title">New Order</span>
      <button class="modal__close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal__body">
      <form class="form-grid" onsubmit="return false">
        <div class="form-group">
          <label>Customer</label>
          <select id="f-cid"></select>
        </div>
        <div class="form-group">
          <label>Order Type</label>
          <select id="f-type">
            <option>Milk</option>
            <option>Cheese</option>
            <option>Butter</option>
            <option>Yogurt</option>
          </select>
        </div>
        <div class="form-group">
          <label>Cow</label>
          <select id="f-cow"></select>
        </div>
        <div class="form-group" id="worker-field">
          <label>Worker</label>
          <select id="f-worker"></select>
        </div>
        <div class="form-group" id="worker-readonly" style="display:none;">
          <label>Worker</label>
          <input type="text" id="f-worker-name" disabled
                 style="background:rgba(255,255,255,0.5);color:var(--muted);cursor:not-allowed;" />
          <small style="color:var(--muted);font-size:0.72rem;">Assigned to you automatically</small>
        </div>
        <div class="form-group">
          <label>Order Date</label>
          <input id="f-date" type="date" required />
        </div>
        <div class="form-group">
          <label>Quantity (liters)</label>
          <input id="f-qty" type="number" min="0" step="0.01" placeholder="e.g. 5.00" required />
        </div>
        <div class="form-group">
          <label>Unit Price (₱/liter)</label>
          <input id="f-price" type="number" min="0" step="0.01" placeholder="e.g. 50.00" required />
        </div>
        <div class="form-group">
          <label>Status</label>
          <select id="f-status">
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div class="form-group form-group--full">
          <label>Notes <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
          <input id="f-notes" type="text" placeholder="Any additional notes…" />
        </div>
      </form>
    </div>
    <div class="modal__foot">
      <button class="btn btn--ghost" onclick="closeModal()">Cancel</button>
      <button class="btn btn--primary" onclick="saveOrder()">Save Order</button>
    </div>
  </div>
</div>

<script src="js/api.js"></script>
<script src="js/ui.js"></script>
<script src="js/nav.js"></script>
<script src="js/import-export.js"></script>
<script>
let editingId  = null;
let allOrders  = [];
let customers  = [];
let cows       = [];
let workers    = [];
const IS_ADMIN = <?= $_isAdmin ? 'true' : 'false' ?>;
const CURRENT_USER = <?= json_encode(['id' => $_SESSION['user']['id'], 'name' => $_SESSION['user']['name']]) ?>;

const ORDER_COLS = [
  { key: 'Order_ID',        label: 'Order ID'    },
  { key: 'Customer_Name',   label: 'Customer'    },
  { key: 'Address',         label: 'Address'     },
  { key: 'Contact_Num',     label: 'Contact'     },
  { key: 'Order_Type',      label: 'Order Type'  },
  { key: 'Order_Date',      label: 'Date'        },
  { key: 'quantity_liters', label: 'Qty (L)'     },
  { key: 'unit_price',      label: 'Unit Price'  },
  { key: 'total_price',     label: 'Total'       },
  { key: 'Order_Status',    label: 'Status'      },
  { key: 'Cow',             label: 'Cow'         },
  { key: 'Worker_Name',     label: 'Worker'      },
  { key: 'Worker_Role',     label: 'Role'        },
];

async function init() {
  // Both Admin and Staff need customers and cows to fill the modal.
  // Admin also needs the workers list to assign any worker.
  try {
    const fetches = [API.customers.getAll(), API.cows.getAll()];
    if (IS_ADMIN) fetches.push(API.workers.getAll());

    const results = await Promise.all(fetches);
    customers = results[0];
    cows      = results[1];
    workers   = IS_ADMIN ? results[2] : [];
    populateSelects();
  } catch(e) {
    console.warn('Could not load modal data:', e.message);
  }

  runSearch();

  ImportExport.addButtons(
    document.getElementById('orders-header-actions'),
    {
      getData:  function() { return allOrders; },
      columns:  ORDER_COLS,
      title:    'Orders — Esperon Dairy Farm',
      filename: 'orders',
    }
  );
}

function populateSelects() {
  document.getElementById('f-cid').innerHTML =
    customers.map(c => `<option value="${c.CID}">${c.Customer_Name}</option>`).join('');
  document.getElementById('f-cow').innerHTML =
    cows.map(c => `<option value="${c.Cow_ID}">${c.Cow} (${parseFloat(c.Production_Liters||0).toFixed(2)}L)</option>`).join('');

  if (IS_ADMIN) {
    // Admin: show worker dropdown
    document.getElementById('f-worker').innerHTML =
      workers.map(w => `<option value="${w.Worker_ID}">${w.Worker} — ${w.Worker_Role}</option>`).join('');
    document.getElementById('worker-field').style.display    = '';
    document.getElementById('worker-readonly').style.display = 'none';
  } else {
    // Staff: show their own name as read-only, hide the dropdown
    document.getElementById('f-worker-name').value           = CURRENT_USER.name;
    document.getElementById('worker-field').style.display    = 'none';
    document.getElementById('worker-readonly').style.display = '';
  }
}

async function loadOrders() {
  const tbody = document.getElementById('orders-body');
  UI.setLoading(tbody, 13);
  try {
    allOrders = await API.orders.getAll();
    renderOrders(allOrders);
  } catch (e) {
    UI.toast(e.message, 'error');
    UI.setEmpty(tbody, 13, 'Failed to load orders.');
  }
}

function renderOrders(rows) {
  const tbody = document.getElementById('orders-body');
  if (!rows.length) { UI.setEmpty(tbody, 13); return; }

  const statusBadge = {
    pending:   'badge--gold',
    confirmed: 'badge--green',
    delivered: 'badge--muted',
    cancelled: 'badge--danger',
  };

  // "Recently updated" = updated within the last 24 hours
  const oneDayAgo = Date.now() - 86400000;

  tbody.innerHTML = rows.map(o => {
    const updatedAt  = o.Order_Updated ? new Date(o.Order_Updated).getTime() : 0;
    const isRecent   = updatedAt > oneDayAgo;
    const recentBadge = isRecent
      ? `<span title="Updated recently" style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#27ae60;margin-left:6px;vertical-align:middle;"></span>`
      : '';

    return `
    <tr>
      <td><strong>#${o.Order_ID}</strong>${recentBadge}</td>
      <td>${o.Customer_Name}</td>
      <td>${o.Address}</td>
      <td>${o.Contact_Num}</td>
      <td><span class="badge badge--green">${o.Order_Type}</span></td>
      <td>${o.Order_Date}</td>
      <td>${parseFloat(o.quantity_liters || 0).toFixed(2)}L</td>
      <td>₱${parseFloat(o.unit_price || 0).toFixed(2)}</td>
      <td>₱${parseFloat(o.total_price || 0).toFixed(2)}</td>
      <td><span class="badge ${statusBadge[o.Order_Status] || 'badge--muted'}">${o.Order_Status || '—'}</span></td>
      <td>🐄 ${o.Cow}</td>
      <td>${o.Worker_Name || o.Worker || '—'}</td>
      <td class="actions">
        <button class="btn btn--icon btn--edit admin-only" onclick="openModal(${o.Order_ID})">✏</button>
        <button class="btn btn--icon btn--del  admin-only" onclick="deleteOrder(${o.Order_ID})">🗑</button>
      </td>
    </tr>`;
  }).join('');
}

// ── Server-side search with debounce ──────────────────────
let _searchTimer = null;
let _myOrdersOnly = false;

function debounceSearch() {
  clearTimeout(_searchTimer);
  _searchTimer = setTimeout(runSearch, 300);
}

function toggleMyOrders() {
  _myOrdersOnly = document.getElementById('my-orders-toggle')?.checked || false;
  runSearch();
}

async function runSearch() {
  const q     = document.getElementById('search').value.trim();
  const tbody = document.getElementById('orders-body');
  UI.setLoading(tbody, 13);
  try {
    let endpoint = 'orders.php';
    const params = [];
    if (q)             params.push(`search=${encodeURIComponent(q)}`);
    if (_myOrdersOnly) params.push('mine=1');
    if (params.length) endpoint += '?' + params.join('&');

    allOrders = await API.request(endpoint);
    renderOrders(allOrders);
  } catch(e) {
    UI.toast(e.message, 'error');
    UI.setEmpty(tbody, 13, 'Failed to load orders.');
  }
}

function filterOrders() { debounceSearch(); } // keep backward compat

function openModal(id = null) {
  // Staff can only create new orders, not edit existing ones
  if (!IS_ADMIN && id) { UI.toast('Only admins can edit orders.', 'error'); return; }

  editingId = id;
  document.getElementById('modal-title').textContent = id ? 'Edit Order' : 'New Order';

  if (!id) {
    document.getElementById('f-date').value   = new Date().toISOString().split('T')[0];
    document.getElementById('f-type').value   = 'Milk';
    document.getElementById('f-qty').value    = '';
    document.getElementById('f-price').value  = '';
    document.getElementById('f-status').value = 'pending';
    document.getElementById('f-notes').value  = '';
  } else {
    API.orders.getById(id).then(o => {
      const custMatch = customers.find(c => c.CID === o.CID);
      const cowMatch  = cows.find(c => c.Cow_ID === o.Cow_ID);
      const wrkMatch  = workers.find(w => w.Worker_ID === o.Worker_ID);
      if (custMatch) document.getElementById('f-cid').value    = custMatch.CID;
      if (cowMatch)  document.getElementById('f-cow').value    = cowMatch.Cow_ID;
      if (wrkMatch)  document.getElementById('f-worker').value = wrkMatch.Worker_ID;
      document.getElementById('f-type').value   = o.Order_Type;
      document.getElementById('f-date').value   = o.Order_Date;
      document.getElementById('f-qty').value    = o.quantity_liters || 0;
      document.getElementById('f-price').value  = o.unit_price || 0;
      document.getElementById('f-status').value = o.Order_Status || 'pending';
      document.getElementById('f-notes').value  = o.Order_Notes || '';
    }).catch(e => UI.toast(e.message, 'error'));
  }
  document.getElementById('modal-overlay').classList.add('modal-overlay--open');
}

function closeModal() {
  document.getElementById('modal-overlay').classList.remove('modal-overlay--open');
  editingId = null;
}

async function saveOrder() {
  const qty   = parseFloat(document.getElementById('f-qty').value);
  const price = parseFloat(document.getElementById('f-price').value);
  const date  = document.getElementById('f-date').value;

  if (!date)                     { UI.toast('Please select a date.', 'error'); return; }
  if (isNaN(qty)   || qty   < 0) { UI.toast('Please enter a valid quantity.', 'error'); return; }
  if (isNaN(price) || price < 0) { UI.toast('Please enter a valid unit price.', 'error'); return; }

  const data = {
    CID:             parseInt(document.getElementById('f-cid').value),
    Cow_ID:          parseInt(document.getElementById('f-cow').value),
    Order_Type:      document.getElementById('f-type').value,
    Order_Date:      date,
    quantity_liters: qty,
    unit_price:      price,
    status:          document.getElementById('f-status').value,
    notes:           document.getElementById('f-notes').value.trim() || null,
  };

  // Admin picks the worker; Staff is auto-assigned server-side from session
  if (IS_ADMIN) {
    data.Worker_ID = parseInt(document.getElementById('f-worker').value);
  }

  try {
    if (editingId) {
      await API.orders.update(editingId, data);
      UI.toast('Order updated!');
    } else {
      await API.orders.create(data);
      UI.toast('Order created!');
    }
    closeModal(); loadOrders();
  } catch (e) { UI.toast(e.message, 'error'); }
}

async function deleteOrder(id) {
  const ok = await UI.confirm('Delete this order?');
  if (!ok) return;
  try {
    await API.orders.delete(id);
    UI.toast('Order deleted.');
    loadOrders();
  } catch (e) { UI.toast(e.message, 'error'); }
}

init();
</script>
</body>
</html>