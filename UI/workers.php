<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Workers — Esperon Dairy Farm</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>

<nav class="nav" id="app-nav"></nav>

<main class="main">
  <div class="page-header">
    <div>
      <h1 class="page-title">Workers</h1>
      <p class="page-subtitle">Manage farm staff and their roles.</p>
    </div>
    <button class="btn btn--primary" onclick="openModal()">＋ Add Worker</button>
  </div>

  <div class="card">
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>Worker ID</th>
            <th>Name</th>
            <th>Role</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="workers-body">
          <tr><td colspan="4" class="tbl-empty"><span class="spinner"></span> Loading…</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</main>

<!-- Modal -->
<div class="modal-overlay" id="modal-overlay">
  <div class="modal">
    <div class="modal__head">
      <span class="modal__title" id="modal-title">Add Worker</span>
      <button class="modal__close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal__body">
      <form class="form-grid" onsubmit="return false">
        <div class="form-group">
          <label>Worker ID</label>
          <input id="f-worker-id" type="number" placeholder="e.g. 203" />
        </div>
        <div class="form-group">
          <label>Full Name</label>
          <input id="f-worker-name" type="text" placeholder="e.g. Jose" required />
        </div>
        <div class="form-group form-group--full">
          <label>Role</label>
          <select id="f-role">
            <option value="Staff">Staff</option>
            <option value="Admin">Admin</option>
            <option value="Manager">Manager</option>
            <option value="Veterinarian">Veterinarian</option>
          </select>
        </div>
      </form>
    </div>
    <div class="modal__foot">
      <button class="btn btn--ghost" onclick="closeModal()">Cancel</button>
      <button class="btn btn--primary" onclick="saveWorker()">Save</button>
    </div>
  </div>
</div>

<script src="js/api.js"></script>
<script src="js/ui.js"></script>
<script src="js/nav.js"></script>
<script>
let editingId = null;

const roleBadge = r => r === 'Admin' ? 'badge--gold' : r === 'Manager' ? 'badge--muted' : 'badge--green';

async function loadWorkers() {
  const tbody = document.getElementById('workers-body');
  UI.setLoading(tbody, 4);
  try {
    const rows = await API.workers.getAll();
    if (!rows.length) { UI.setEmpty(tbody, 4); return; }
    tbody.innerHTML = rows.map(w => `
      <tr>
        <td><strong>${w.Worker_ID}</strong></td>
        <td>👷 ${w.Worker}</td>
        <td><span class="badge ${roleBadge(w.Worker_Role)}">${w.Worker_Role}</span></td>
        <td class="actions">
          <button class="btn btn--icon btn--edit" onclick="openModal(${w.Worker_ID})">✏ Edit</button>
          <button class="btn btn--icon btn--del"  onclick="deleteWorker(${w.Worker_ID})">🗑 Del</button>
        </td>
      </tr>
    `).join('');
  } catch (e) {
    UI.toast(e.message, 'error');
    UI.setEmpty(tbody, 4, 'Failed to load workers.');
  }
}

function openModal(id = null) {
  editingId = id;
  document.getElementById('modal-title').textContent = id ? 'Edit Worker' : 'Add Worker';
  document.getElementById('f-worker-id').disabled = !!id;

  if (!id) {
    document.getElementById('f-worker-id').value   = '';
    document.getElementById('f-worker-name').value = '';
    document.getElementById('f-role').value        = 'Staff';
  } else {
    API.workers.getById(id).then(w => {
      document.getElementById('f-worker-id').value   = w.Worker_ID;
      document.getElementById('f-worker-name').value = w.Worker;
      document.getElementById('f-role').value        = w.Worker_Role;
    }).catch(e => UI.toast(e.message, 'error'));
  }
  document.getElementById('modal-overlay').classList.add('modal-overlay--open');
}

function closeModal() {
  document.getElementById('modal-overlay').classList.remove('modal-overlay--open');
  editingId = null;
}

async function saveWorker() {
  const name = document.getElementById('f-worker-name').value.trim();
  const role = document.getElementById('f-role').value;
  if (!name) { UI.toast('Please enter a worker name.', 'error'); return; }

  try {
    if (editingId) {
      await API.workers.update(editingId, { Worker: name, Worker_Role: role });
      UI.toast('Worker updated!');
    } else {
      const wid = parseInt(document.getElementById('f-worker-id').value);
      await API.workers.create({ Worker_ID: wid, Worker: name, Worker_Role: role });
      UI.toast('Worker added!');
    }
    closeModal(); loadWorkers();
  } catch (e) { UI.toast(e.message, 'error'); }
}

async function deleteWorker(id) {
  const ok = await UI.confirm('Delete this worker?');
  if (!ok) return;
  try {
    await API.workers.delete(id);
    UI.toast('Worker deleted.');
    loadWorkers();
  } catch (e) { UI.toast(e.message, 'error'); }
}

loadWorkers();
</script>
</body>
</html>