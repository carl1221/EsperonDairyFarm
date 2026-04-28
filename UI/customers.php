<?php
require_once __DIR__ . '/guard.php';
requireAuthPage();
requireAdminPage();  // Customers page is Admin-only
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Customers — Esperon Dairy Farm</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>

<nav class="nav" id="app-nav"></nav>

<main class="main">
  <div class="page-header">
    <div>
      <h1 class="page-title">Customers</h1>
      <p class="page-subtitle">Manage all farm customers and their addresses.</p>
    </div>
    <button class="btn btn--primary" onclick="openModal()">＋ Add Customer</button>
  </div>

  <div class="card">
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Address</th>
            <th>Contact No.</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="customers-body">
          <tr><td colspan="5" class="tbl-empty"><span class="spinner"></span> Loading…</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</main>

<!-- Modal -->
<div class="modal-overlay" id="modal-overlay">
  <div class="modal">
    <div class="modal__head">
      <span class="modal__title" id="modal-title">Add Customer</span>
      <button class="modal__close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal__body">
      <form class="form-grid" id="customer-form" onsubmit="return false">
        <input type="hidden" id="f-cid" />
        <input type="hidden" id="f-addr-id" />
        <div class="form-group">
          <label for="f-name">Customer ID</label>
          <input id="f-new-cid" type="number" placeholder="e.g. 3" />
        </div>
        <div class="form-group">
          <label for="f-name">Full Name</label>
          <input id="f-name" type="text" placeholder="e.g. Maria" required />
        </div>
        <div class="form-group">
          <label for="f-addr-id-new">Address ID</label>
          <input id="f-addr-id-new" type="number" placeholder="e.g. 303" />
        </div>
        <div class="form-group">
          <label for="f-addr">Address</label>
          <input id="f-addr" type="text" placeholder="e.g. Barangay 5" required />
        </div>
        <div class="form-group">
          <label for="f-contact">Contact Number</label>
          <input id="f-contact" type="text" placeholder="e.g. 09123456789" required />
        </div>
      </form>
    </div>
    <div class="modal__foot">
      <button class="btn btn--ghost" onclick="closeModal()">Cancel</button>
      <button class="btn btn--primary" onclick="saveCustomer()">Save</button>
    </div>
  </div>
</div>

<script src="js/api.js"></script>
<script src="js/ui.js"></script>
<script src="js/nav.js"></script>
<script>
let editingId = null;

async function loadCustomers() {
  const tbody = document.getElementById('customers-body');
  UI.setLoading(tbody, 5);
  try {
    const rows = await API.customers.getAll();
    if (!rows.length) { UI.setEmpty(tbody, 5); return; }
    tbody.innerHTML = rows.map(c => `
      <tr>
        <td><strong>${c.CID}</strong></td>
        <td>${c.Customer_Name}</td>
        <td>${c.Address}</td>
        <td>${c.Contact_Num}</td>
        <td class="actions">
          <button class="btn btn--icon btn--edit" onclick="openModal(${c.CID})">✏ Edit</button>
          <button class="btn btn--icon btn--del"  onclick="deleteCustomer(${c.CID})">🗑 Del</button>
        </td>
      </tr>
    `).join('');
  } catch (e) {
    UI.toast(e.message, 'error');
    UI.setEmpty(tbody, 5, 'Failed to load customers.');
  }
}

function openModal(id = null) {
  editingId = id;
  document.getElementById('modal-title').textContent = id ? 'Edit Customer' : 'Add Customer';
  document.getElementById('f-new-cid').disabled = !!id;

  if (!id) {
    ['f-cid','f-addr-id','f-new-cid','f-name','f-addr-id-new','f-addr','f-contact']
      .forEach(i => document.getElementById(i).value = '');
  } else {
    API.customers.getById(id).then(c => {
      document.getElementById('f-cid').value       = c.CID;
      document.getElementById('f-addr-id').value   = c.Address_ID;
      document.getElementById('f-new-cid').value   = c.CID;
      document.getElementById('f-name').value       = c.Customer_Name;
      document.getElementById('f-addr-id-new').value = c.Address_ID;
      document.getElementById('f-addr').value       = c.Address;
      document.getElementById('f-contact').value    = c.Contact_Num;
    }).catch(e => UI.toast(e.message, 'error'));
  }
  document.getElementById('modal-overlay').classList.add('modal-overlay--open');
}

function closeModal() {
  document.getElementById('modal-overlay').classList.remove('modal-overlay--open');
  editingId = null;
}

async function saveCustomer() {
  const name    = document.getElementById('f-name').value.trim();
  const addr    = document.getElementById('f-addr').value.trim();
  const contact = document.getElementById('f-contact').value.trim();
  if (!name || !addr || !contact) { UI.toast('Please fill in all fields.', 'error'); return; }

  try {
    if (editingId) {
      await API.customers.update(editingId, { Customer_Name: name, Address: addr, Contact_Num: contact });
      UI.toast('Customer updated!');
    } else {
      const cid    = parseInt(document.getElementById('f-new-cid').value);
      const addrId = parseInt(document.getElementById('f-addr-id-new').value);
      await API.customers.create({ CID: cid, Customer_Name: name, Address_ID: addrId, Address: addr, Contact_Num: contact });
      UI.toast('Customer created!');
    }
    closeModal();
    loadCustomers();
  } catch (e) { UI.toast(e.message, 'error'); }
}

async function deleteCustomer(id) {
  const ok = await UI.confirm('Delete this customer? This cannot be undone.');
  if (!ok) return;
  try {
    await API.customers.delete(id);
    UI.toast('Customer deleted.');
    loadCustomers();
  } catch (e) { UI.toast(e.message, 'error'); }
}

loadCustomers();
</script>
</body>
</html>