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
  <title>Customers — Esperon Dairy Farm</title>
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
      <h1 class="page-title">Customers</h1>
      <p class="page-subtitle">
        <?= $_isAdmin ? 'Manage all farm customers and their addresses.' : 'View customers and add new ones.' ?>
      </p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;" id="customers-header-actions">
      <button class="btn btn--primary" onclick="openModal()">＋ Add Customer</button>
    </div>
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

<!-- Set Password Modal (Admin only) -->
<div class="modal-overlay" id="pw-modal">
  <div class="modal" style="max-width:400px;">
    <div class="modal__head">
      <span class="modal__title">Set Customer Password</span>
      <button class="modal__close" onclick="closeSetPassword()">✕</button>
    </div>
    <div class="modal__body">
      <p style="font-size:0.84rem;color:var(--muted);margin-bottom:14px;">
        Setting password for: <strong id="pw-customer-name"></strong>
      </p>
      <div id="pw-err" style="display:none;background:var(--danger-lt);border:1px solid #f5c6cb;color:var(--danger);padding:8px 12px;border-radius:8px;font-size:0.82rem;margin-bottom:12px;"></div>
      <form class="form-grid" onsubmit="return false">
        <div class="form-group form-group--full">
          <label>New Password</label>
          <input id="pw-new" type="password" placeholder="Min 8 chars, 1 uppercase, 1 number" />
        </div>
        <div class="form-group form-group--full">
          <label>Confirm Password</label>
          <input id="pw-confirm" type="password" placeholder="Repeat new password" />
        </div>
      </form>
    </div>
    <div class="modal__foot">
      <button class="btn btn--ghost" onclick="closeSetPassword()">Cancel</button>
      <button class="btn btn--primary" onclick="saveCustomerPassword()">Set Password</button>
    </div>
  </div>
</div>

<script src="js/api.js"></script>
<script src="js/ui.js"></script>
<script src="js/nav.js"></script>
<script src="js/import-export.js"></script>
<script>
let editingId = null;
let _customersData = [];
const IS_ADMIN = <?= $_isAdmin ? 'true' : 'false' ?>;

async function loadCustomers() {
  const tbody = document.getElementById('customers-body');
  UI.setLoading(tbody, 5);
  try {
    const rows = await API.customers.getAll();
    _customersData = rows;
    if (!rows.length) { UI.setEmpty(tbody, 5); return; }
    tbody.innerHTML = rows.map(c => `
      <tr>
        <td><strong>${c.CID}</strong></td>
        <td>${c.Customer_Name}</td>
        <td>${c.Address}</td>
        <td>${c.Contact_Num}</td>
        <td class="actions">
          ${IS_ADMIN ? `
          <button class="btn btn--icon btn--edit admin-only" onclick="openModal(${c.CID})">✏ Edit</button>
          <button class="btn btn--icon" onclick="openSetPassword(${c.CID}, '${c.Customer_Name.replace(/'/g,"\\'")}')">🔑 Password</button>
          <button class="btn btn--icon btn--del  admin-only" onclick="deleteCustomer(${c.CID})">🗑 Del</button>
          ` : '<span style="font-size:0.75rem;color:var(--muted);">View only</span>'}
        </td>
      </tr>
    `).join('');
  } catch (e) {
    UI.toast(e.message, 'error');
    UI.setEmpty(tbody, 5, 'Failed to load customers.');
  }
}

// ── Import/Export setup ───────────────────────────────────
const CUSTOMER_COLS = [
  { key: 'CID',           label: 'Customer ID' },
  { key: 'Customer_Name', label: 'Name'        },
  { key: 'Address',       label: 'Address'     },
  { key: 'Contact_Num',   label: 'Contact No.' },
];

document.addEventListener('DOMContentLoaded', function() {
  ImportExport.addButtons(
    document.getElementById('customers-header-actions'),
    {
      getData:   function() { return _customersData; },
      columns:   CUSTOMER_COLS,
      title:     'Customers — Esperon Dairy Farm',
      filename:  'customers',
      onImport:  async function(records) {
        if (!records.length) { UI.toast('No records found in file.', 'error'); return; }
        var ok = await UI.confirm('Import ' + records.length + ' customer(s)? Existing records will not be overwritten.');
        if (!ok) return;
        var success = 0, failed = 0;
        for (var r of records) {
          try {
            await API.customers.create({
              CID:           parseInt(r.CID) || 0,
              Customer_Name: r.Customer_Name || r.Name || '',
              Address_ID:    parseInt(r.Address_ID) || 1,
              Address:       r.Address || '',
              Contact_Num:   r.Contact_Num || r['Contact No.'] || '',
            });
            success++;
          } catch(e) { failed++; }
        }
        UI.toast('Imported ' + success + ' customer(s).' + (failed ? ' ' + failed + ' failed.' : ''), success ? 'success' : 'error');
        loadCustomers();
      }
    }
  );
});

function openModal(id = null) {
  // Staff can only add new customers, not edit existing ones
  if (!IS_ADMIN && id) { UI.toast('Only admins can edit customers.', 'error'); return; }

  editingId = id;
  document.getElementById('modal-title').textContent = id ? 'Edit Customer' : 'Add Customer';

  // Hide Customer ID and Address ID fields for Staff — auto-assigned by DB
  const cidRow    = document.getElementById('f-new-cid').closest('.form-group');
  const addrIdRow = document.getElementById('f-addr-id-new').closest('.form-group');
  if (cidRow)    cidRow.style.display    = IS_ADMIN ? '' : 'none';
  if (addrIdRow) addrIdRow.style.display = IS_ADMIN ? '' : 'none';

  document.getElementById('f-new-cid').disabled = !!id;

  if (!id) {
    ['f-cid','f-addr-id','f-new-cid','f-name','f-addr-id-new','f-addr','f-contact']
      .forEach(i => document.getElementById(i).value = '');
  } else {
    API.customers.getById(id).then(c => {
      document.getElementById('f-cid').value         = c.CID;
      document.getElementById('f-addr-id').value     = c.Address_ID;
      document.getElementById('f-new-cid').value     = c.CID;
      document.getElementById('f-name').value        = c.Customer_Name;
      document.getElementById('f-addr-id-new').value = c.Address_ID;
      document.getElementById('f-addr').value        = c.Address;
      document.getElementById('f-contact').value     = c.Contact_Num;
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
      // Admin only — already guarded in openModal
      await API.customers.update(editingId, { Customer_Name: name, Address: addr, Contact_Num: contact });
      UI.toast('Customer updated!');
    } else {
      // For Staff: CID and Address_ID are omitted — DB AUTO_INCREMENT handles CID,
      // and the backend will create a new Address row automatically.
      const payload = { Customer_Name: name, Address: addr, Contact_Num: contact };
      if (IS_ADMIN) {
        const cid    = parseInt(document.getElementById('f-new-cid').value);
        const addrId = parseInt(document.getElementById('f-addr-id-new').value);
        if (cid)    payload.CID        = cid;
        if (addrId) payload.Address_ID = addrId;
      }
      await API.customers.create(payload);
      UI.toast('Customer added!');
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

// ── Set Customer Password (Admin only) ────────────────────
let _pwCustomerId = null;

function openSetPassword(id, name) {
  _pwCustomerId = id;
  document.getElementById('pw-customer-name').textContent = name;
  document.getElementById('pw-new').value     = '';
  document.getElementById('pw-confirm').value = '';
  document.getElementById('pw-err').style.display = 'none';
  document.getElementById('pw-modal').classList.add('modal-overlay--open');
}

function closeSetPassword() {
  document.getElementById('pw-modal').classList.remove('modal-overlay--open');
  _pwCustomerId = null;
}

async function saveCustomerPassword() {
  const pw      = document.getElementById('pw-new').value;
  const confirm = document.getElementById('pw-confirm').value;
  const errEl   = document.getElementById('pw-err');

  if (!pw || pw.length < 8) {
    errEl.textContent = 'Password must be at least 8 characters.';
    errEl.style.display = 'block'; return;
  }
  if (!/[A-Z]/.test(pw)) {
    errEl.textContent = 'Must contain at least one uppercase letter.';
    errEl.style.display = 'block'; return;
  }
  if (!/[0-9]/.test(pw)) {
    errEl.textContent = 'Must contain at least one number.';
    errEl.style.display = 'block'; return;
  }
  if (pw !== confirm) {
    errEl.textContent = 'Passwords do not match.';
    errEl.style.display = 'block'; return;
  }
  errEl.style.display = 'none';

  try {
    await API.request('set_customer_password.php', 'POST', {
      customer_id: _pwCustomerId,
      password: pw,
      password_confirm: confirm,
    });
    UI.toast('Password set successfully!', 'success');
    closeSetPassword();
  } catch(e) {
    errEl.textContent = e.message;
    errEl.style.display = 'block';
  }
}

loadCustomers();
</script>
</body>
</html>