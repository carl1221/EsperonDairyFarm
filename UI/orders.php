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
      <button class="btn btn--primary admin-only" onclick="openModal()">＋ New Order</button>
    </div>
  </div>

  <div class="card">
    <div class="card__header">
      <span class="card__title">All Orders</span>
      <input type="text" id="search" placeholder="🔍 Search orders…"
        style="width:220px;padding:7px 12px;font-size:.83rem"
        oninput="filterOrders()" />
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
            <th>Cow</th>
            <th>Production</th>
            <th>Worker</th>
            <th>Role</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="orders-body">
          <tr><td colspan="11" class="tbl-empty"><span class="spinner"></span> Loading…</td></tr>
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
        <div class="form-group">
          <label>Worker</label>
          <select id="f-worker"></select>
        </div>
        <div class="form-group form-group--full">
          <label>Order Date</label>
          <input id="f-date" type="date" required />
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

const ORDER_COLS = [
  { key: 'Order_ID',      label: 'Order ID'    },
  { key: 'Customer_Name', label: 'Customer'    },
  { key: 'Address',       label: 'Address'     },
  { key: 'Contact_Num',   label: 'Contact'     },
  { key: 'Order_Type',    label: 'Order Type'  },
  { key: 'Order_Date',    label: 'Date'        },
  { key: 'Cow',           label: 'Cow'         },
  { key: 'Production',    label: 'Production'  },
  { key: 'Worker',        label: 'Worker'      },
  { key: 'Worker_Role',   label: 'Role'        },
];

async function init() {
  // Only Admin needs customers/cows/workers for the "New Order" modal
  if (<?= $_isAdmin ? 'true' : 'false' ?>) {
    try {
      [customers, cows, workers] = await Promise.all([
        API.customers.getAll(),
        API.cows.getAll(),
        API.workers.getAll(),
      ]);
      populateSelects();
    } catch(e) {
      console.warn('Could not load modal data:', e.message);
    }
  }
  loadOrders();

  // Add export buttons (orders are export-only — no import due to FK complexity)
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
    cows.map(c => `<option value="${c.Cow_ID}">${c.Cow} (${c.Production})</option>`).join('');
  document.getElementById('f-worker').innerHTML =
    workers.map(w => `<option value="${w.Worker_ID}">${w.Worker} — ${w.Worker_Role}</option>`).join('');
}

async function loadOrders() {
  const tbody = document.getElementById('orders-body');
  UI.setLoading(tbody, 11);
  try {
    allOrders = await API.orders.getAll();
    renderOrders(allOrders);
  } catch (e) {
    UI.toast(e.message, 'error');
    UI.setEmpty(tbody, 11, 'Failed to load orders.');
  }
}

function renderOrders(rows) {
  const tbody = document.getElementById('orders-body');
  if (!rows.length) { UI.setEmpty(tbody, 11); return; }
  tbody.innerHTML = rows.map(o => `
    <tr>
      <td><strong>#${o.Order_ID}</strong></td>
      <td>${o.Customer_Name}</td>
      <td>${o.Address}</td>
      <td>${o.Contact_Num}</td>
      <td><span class="badge badge--green">${o.Order_Type}</span></td>
      <td>${o.Order_Date}</td>
      <td>🐄 ${o.Cow}</td>
      <td>${o.Production}</td>
      <td>${o.Worker}</td>
      <td><span class="badge ${o.Worker_Role === 'Admin' ? 'badge--gold' : 'badge--muted'}">${o.Worker_Role}</span></td>
      <td class="actions">
        <button class="btn btn--icon btn--edit admin-only" onclick="openModal(${o.Order_ID})">✏</button>
        <button class="btn btn--icon btn--del  admin-only" onclick="deleteOrder(${o.Order_ID})">🗑</button>
      </td>
    </tr>
  `).join('');
}

function filterOrders() {
  const q = document.getElementById('search').value.toLowerCase();
  renderOrders(allOrders.filter(o =>
    Object.values(o).some(v => String(v).toLowerCase().includes(q))
  ));
}

function openModal(id = null) {
  editingId = id;
  document.getElementById('modal-title').textContent = id ? 'Edit Order' : 'New Order';

  if (!id) {
    document.getElementById('f-date').value = new Date().toISOString().split('T')[0];
    document.getElementById('f-type').value = 'Milk';
  } else {
    API.orders.getById(id).then(o => {
      const custMatch = customers.find(c => c.Customer_Name === o.Customer_Name);
      const cowMatch  = cows.find(c => c.Cow === o.Cow);
      const wrkMatch  = workers.find(w => w.Worker === o.Worker);
      if (custMatch) document.getElementById('f-cid').value    = custMatch.CID;
      if (cowMatch)  document.getElementById('f-cow').value    = cowMatch.Cow_ID;
      if (wrkMatch)  document.getElementById('f-worker').value = wrkMatch.Worker_ID;
      document.getElementById('f-type').value  = o.Order_Type;
      document.getElementById('f-date').value  = o.Order_Date;
    }).catch(e => UI.toast(e.message, 'error'));
  }
  document.getElementById('modal-overlay').classList.add('modal-overlay--open');
}

function closeModal() {
  document.getElementById('modal-overlay').classList.remove('modal-overlay--open');
  editingId = null;
}

async function saveOrder() {
  const data = {
    CID:        parseInt(document.getElementById('f-cid').value),
    Cow_ID:     parseInt(document.getElementById('f-cow').value),
    Worker_ID:  parseInt(document.getElementById('f-worker').value),
    Order_Type: document.getElementById('f-type').value,
    Order_Date: document.getElementById('f-date').value,
  };
  if (!data.Order_Date) { UI.toast('Please select a date.', 'error'); return; }

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