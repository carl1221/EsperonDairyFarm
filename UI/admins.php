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
  <title>Admins — Esperon Dairy Farm</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>

<nav class="nav" id="app-nav"></nav>

<main class="main">
  <div class="page-header">
    <div>
      <h1 class="page-title">Admins</h1>
      <p class="page-subtitle">Manage administrator accounts.</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;" id="admins-header-actions">
      <button class="btn btn--primary" onclick="openModal()">＋ Add Admin</button>
    </div>
  </div>

  <div class="card">
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Role</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="admins-body">
          <tr><td colspan="4" class="tbl-empty"><span class="spinner"></span> Loading…</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</main>

<!-- Modal — role is always Admin on this page -->
<div class="modal-overlay" id="modal-overlay">
  <div class="modal">
    <div class="modal__head">
      <span class="modal__title" id="modal-title">Add Admin</span>
      <button class="modal__close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal__body">
      <form class="form-grid" onsubmit="return false">
        <div class="form-group">
          <label>Full Name</label>
          <input id="f-admin-name" type="text" placeholder="e.g. Maria" required />
        </div>
        <div class="form-group">
          <label>Role</label>
          <!-- Locked to Admin on this page -->
          <input type="text" value="Admin" disabled
                 style="background:rgba(255,255,255,0.5);color:var(--muted);cursor:not-allowed;" />
        </div>
      </form>
    </div>
    <div class="modal__foot">
      <button class="btn btn--ghost" onclick="closeModal()">Cancel</button>
      <button class="btn btn--primary" onclick="saveAdmin()">Save</button>
    </div>
  </div>
</div>

<script src="js/api.js"></script>
<script src="js/ui.js"></script>
<script src="js/nav.js"></script>
<script src="js/import-export.js"></script>
<script>
let editingId   = null;
let _adminsData = [];

async function loadAdmins() {
  const tbody = document.getElementById('admins-body');
  UI.setLoading(tbody, 4);
  try {
    // Only fetch Admin-role workers
    const rows = await API.request('workers.php?role=Admin');
    _adminsData = Array.isArray(rows) ? rows : [];
    if (!_adminsData.length) { UI.setEmpty(tbody, 4, 'No admins found.'); return; }
    tbody.innerHTML = _adminsData.map(w => `
      <tr>
        <td><strong>${w.Worker_ID}</strong></td>
        <td>🛡️ ${w.Worker}</td>
        <td><span class="badge badge--gold">${w.Worker_Role}</span></td>
        <td class="actions">
          <button class="btn btn--icon btn--edit" onclick="openModal(${w.Worker_ID})">✏ Edit</button>
          <button class="btn btn--icon btn--del"  onclick="deleteAdmin(${w.Worker_ID})">🗑 Del</button>
        </td>
      </tr>
    `).join('');
  } catch (e) {
    UI.toast(e.message, 'error');
    UI.setEmpty(tbody, 4, 'Failed to load admins.');
  }
}

const ADMIN_COLS = [
  { key: 'Worker_ID',   label: 'ID'   },
  { key: 'Worker',      label: 'Name' },
  { key: 'Worker_Role', label: 'Role' },
];

document.addEventListener('DOMContentLoaded', function() {
  ImportExport.addButtons(
    document.getElementById('admins-header-actions'),
    {
      getData:  function() { return _adminsData; },
      columns:  ADMIN_COLS,
      title:    'Admins — Esperon Dairy Farm',
      filename: 'admins',
      onImport: async function(records) {
        if (!records.length) { UI.toast('No records found in file.', 'error'); return; }
        var ok = await UI.confirm('Import ' + records.length + ' admin(s)?');
        if (!ok) return;
        var success = 0, failed = 0;
        for (var r of records) {
          try {
            await API.workers.create({
              Worker:      r.Worker || r.Name || '',
              Worker_Role: 'Admin',   // always Admin on this page
            });
            success++;
          } catch(e) { failed++; }
        }
        UI.toast('Imported ' + success + ' admin(s).' + (failed ? ' ' + failed + ' failed.' : ''), success ? 'success' : 'error');
        loadAdmins();
      }
    }
  );
});

function openModal(id = null) {
  editingId = id;
  document.getElementById('modal-title').textContent = id ? 'Edit Admin' : 'Add Admin';

  if (!id) {
    document.getElementById('f-admin-name').value = '';
  } else {
    API.workers.getById(id).then(w => {
      document.getElementById('f-admin-name').value = w.Worker;
    }).catch(e => UI.toast(e.message, 'error'));
  }
  document.getElementById('modal-overlay').classList.add('modal-overlay--open');
}

function closeModal() {
  document.getElementById('modal-overlay').classList.remove('modal-overlay--open');
  editingId = null;
}

async function saveAdmin() {
  const name = document.getElementById('f-admin-name').value.trim();
  if (!name) { UI.toast('Please enter a name.', 'error'); return; }

  try {
    if (editingId) {
      await API.workers.update(editingId, { Worker: name, Worker_Role: 'Admin' });
      UI.toast('Admin updated!');
    } else {
      await API.workers.create({ Worker: name, Worker_Role: 'Admin' });
      UI.toast('Admin added!');
    }
    closeModal(); loadAdmins();
  } catch (e) { UI.toast(e.message, 'error'); }
}

async function deleteAdmin(id) {
  const ok = await UI.confirm('Delete this admin account?');
  if (!ok) return;
  try {
    await API.workers.delete(id);
    UI.toast('Admin deleted.');
    loadAdmins();
  } catch (e) { UI.toast(e.message, 'error'); }
}

loadAdmins();
</script>
</body>
</html>
