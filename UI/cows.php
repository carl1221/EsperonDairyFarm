<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cows — Esperon Dairy Farm</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>

<nav class="nav" id="app-nav"></nav>

<!-- Reminders Section (replaces Health Alerts) -->
<div class="card" style="max-width: 500px; margin: 24px auto 0 auto;">
  <div style="display: flex; justify-content: space-between; align-items: center;">
    <div style="display: flex; align-items: center;">
      <span style="color: #e74c3c; margin-right: 8px;">
        <span class="material-symbols-outlined" style="vertical-align: middle;">warning</span>
      </span>
      <span style="font-weight: bold;">Reminders</span>
    </div>
    <button id="addReminderBtn" style="background: #e74c3c; color: #fff; border: none; border-radius: 4px; padding: 4px 12px; cursor: pointer; font-size: 0.95rem;">Add Reminder</button>
  </div>
  <div id="remindersList" style="margin-top: 12px;"></div>
</div>

<main class="main">
  <div class="page-header">
    <div>
      <h1 class="page-title">Cows</h1>
      <p class="page-subtitle">Track your herd and individual milk production.</p>
    </div>
    <button class="btn btn--primary" onclick="openModal()">＋ Add Cow</button>
  </div>

  <div class="card">
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>Cow ID</th>
            <th>Name</th>
            <th>Daily Production</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="cows-body">
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
      <span class="modal__title" id="modal-title">Add Cow</span>
      <button class="modal__close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal__body">
      <form class="form-grid" onsubmit="return false">
        <div class="form-group">
          <label>Cow ID</label>
          <input id="f-cow-id" type="number" placeholder="e.g. 103" />
        </div>
        <div class="form-group">
          <label>Cow Name</label>
          <input id="f-cow-name" type="text" placeholder="e.g. Cow3" required />
        </div>
        <div class="form-group form-group--full">
          <label>Daily Production</label>
          <input id="f-production" type="text" placeholder="e.g. 12L" required />
        </div>
      </form>
    </div>
    <div class="modal__foot">
      <button class="btn btn--ghost" onclick="closeModal()">Cancel</button>
      <button class="btn btn--primary" onclick="saveCow()">Save</button>
    </div>
  </div>
</div>

<script src="js/api.js"></script>
<script src="js/ui.js"></script>
<script src="js/nav.js"></script>
<script>
// --- Reminders Functionality ---
let reminders = [];
function renderReminders() {
  const list = document.getElementById('remindersList');
  list.innerHTML = '';
  reminders.forEach((reminder, idx) => {
    const div = document.createElement('div');
    div.style.background = '#fdeaea';
    div.style.border = '1px solid #e74c3c';
    div.style.borderRadius = '8px';
    div.style.padding = '12px';
    div.style.marginBottom = '10px';
    div.style.color = '#e74c3c';
    div.style.cursor = 'pointer';
    div.innerHTML = `<strong>REMINDER</strong><br>${reminder}`;
    div.onclick = () => { alert(reminder); };
    list.appendChild(div);
  });
}
document.getElementById('addReminderBtn').onclick = function() {
  const text = prompt('Enter reminder text:');
  if (text && text.trim() !== '') {
    reminders.push(text.trim());
    renderReminders();
    alert(text.trim());
  }
};
renderReminders();
// --- End Reminders ---

let editingId = null;

async function loadCows() {
  const tbody = document.getElementById('cows-body');
  UI.setLoading(tbody, 4);
  try {
    const rows = await API.cows.getAll();
    if (!rows.length) { UI.setEmpty(tbody, 4); return; }
    tbody.innerHTML = rows.map(c => `
      <tr>
        <td><strong>${c.Cow_ID}</strong></td>
        <td>
          <span class="material-symbols-outlined" style="font-size: 1.3rem; vertical-align: middle; margin-right: 8px; color: var(--olive);">pets</span>
          <span style="vertical-align: middle;">${c.Cow}</span>
        </td>
        <td><span class="badge badge--green">${c.Production}</span></td>
        <td class="actions">
          <button class="btn btn--icon btn--edit" onclick="openModal(${c.Cow_ID})">
            <span class="material-symbols-outlined" style="font-size: 1rem;">edit</span> Edit
          </button>
          <button class="btn btn--icon btn--del" onclick="deleteCow(${c.Cow_ID})">
            <span class="material-symbols-outlined" style="font-size: 1rem;">delete</span> Del
          </button>
        </td>
      </tr>
    `).join('');
  } catch (e) {
    UI.toast(e.message, 'error');
    UI.setEmpty(tbody, 4, 'Failed to load cows.');
  }
}

function openModal(id = null) {
  editingId = id;
  document.getElementById('modal-title').textContent = id ? 'Edit Cow' : 'Add Cow';
  document.getElementById('f-cow-id').disabled = !!id;

  if (!id) {
    ['f-cow-id','f-cow-name','f-production'].forEach(i => document.getElementById(i).value = '');
  } else {
    API.cows.getById(id).then(c => {
      document.getElementById('f-cow-id').value    = c.Cow_ID;
      document.getElementById('f-cow-name').value  = c.Cow;
      document.getElementById('f-production').value = c.Production;
    }).catch(e => UI.toast(e.message, 'error'));
  }
  document.getElementById('modal-overlay').classList.add('modal-overlay--open');
}

function closeModal() {
  document.getElementById('modal-overlay').classList.remove('modal-overlay--open');
  editingId = null;
}

async function saveCow() {
  const name = document.getElementById('f-cow-name').value.trim();
  const prod = document.getElementById('f-production').value.trim();
  if (!name || !prod) { UI.toast('Please fill in all fields.', 'error'); return; }

  try {
    if (editingId) {
      await API.cows.update(editingId, { Cow: name, Production: prod });
      UI.toast('Cow updated!');
    } else {
      const cowId = parseInt(document.getElementById('f-cow-id').value);
      await API.cows.create({ Cow_ID: cowId, Cow: name, Production: prod });
      UI.toast('Cow added!');
    }
    closeModal(); loadCows();
  } catch (e) { UI.toast(e.message, 'error'); }
}

async function deleteCow(id) {
  const ok = await UI.confirm('Delete this cow record?');
  if (!ok) return;
  try {
    await API.cows.delete(id);
    UI.toast('Cow deleted.');
    loadCows();
  } catch (e) { UI.toast(e.message, 'error'); }
}

loadCows();
</script>
</body>
</html>