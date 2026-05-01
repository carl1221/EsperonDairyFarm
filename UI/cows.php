<?php
require_once __DIR__ . '/guard.php';
requireAuthPage();
requireAdminPage();  // Cows page is Admin-only
?>
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
    <div style="display:flex;gap:8px;align-items:center;" id="cows-header-actions">
      <button class="btn btn--primary" onclick="openModal()">＋ Add Cow</button>
    </div>
  </div>

  <div class="card">
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>Cow ID</th>
            <th>Name</th>
            <th>Breed</th>
            <th>Daily Production</th>
            <th>Health</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="cows-body">
          <tr><td colspan="7" class="tbl-empty"><span class="spinner"></span> Loading…</td></tr>
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
          <label>Cow Name</label>
          <input id="f-cow-name" type="text" placeholder="e.g. Cow3" required />
        </div>
        <div class="form-group">
          <label>Breed <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
          <input id="f-breed" type="text" placeholder="e.g. Holstein" />
        </div>
        <div class="form-group">
          <label>Date of Birth <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
          <input id="f-dob" type="date" />
        </div>
        <div class="form-group">
          <label>Daily Production (liters)</label>
          <input id="f-production" type="number" min="0" step="0.01" placeholder="e.g. 12.00" required />
        </div>
        <div class="form-group">
          <label>Health Status</label>
          <select id="f-health">
            <option value="Healthy">Healthy</option>
            <option value="Sick">Sick</option>
            <option value="Under Treatment">Under Treatment</option>
            <option value="Retired">Retired</option>
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
      <button class="btn btn--primary" onclick="saveCow()">Save</button>
    </div>
  </div>
</div>

<script src="js/api.js"></script>
<script src="js/ui.js"></script>
<script src="js/nav.js"></script>
<script src="js/import-export.js"></script>
<script>
let editingId = null;
let _cowsData = [];

async function loadCows() {
  const tbody = document.getElementById('cows-body');
  UI.setLoading(tbody, 7);
  try {
    const rows = await API.cows.getAll();
    _cowsData = rows;
    if (!rows.length) { UI.setEmpty(tbody, 7); return; }

    const healthBadge = {
      'Healthy':         'badge--green',
      'Sick':            'badge--danger',
      'Under Treatment': 'badge--gold',
      'Retired':         'badge--muted',
    };

    tbody.innerHTML = rows.map(c => `
      <tr>
        <td><strong>${c.Cow_ID}</strong></td>
        <td>
          <span class="material-symbols-outlined" style="font-size:1.3rem;vertical-align:middle;margin-right:8px;color:var(--olive);">pets</span>
          <span style="vertical-align:middle;">${c.Cow}</span>
        </td>
        <td>${c.Breed || '—'}</td>
        <td><span class="badge badge--green">${parseFloat(c.Production_Liters || 0).toFixed(2)}L</span></td>
        <td><span class="badge ${healthBadge[c.Health_Status] || 'badge--muted'}">${c.Health_Status || '—'}</span></td>
        <td>${c.is_active ? '<span class="badge badge--green">Active</span>' : '<span class="badge badge--muted">Inactive</span>'}</td>
        <td class="actions">
          <button class="btn btn--icon btn--edit" onclick="openModal(${c.Cow_ID})">
            <span class="material-symbols-outlined" style="font-size:1rem;">edit</span> Edit
          </button>
          <button class="btn btn--icon btn--del" onclick="deleteCow(${c.Cow_ID})">
            <span class="material-symbols-outlined" style="font-size:1rem;">delete</span> Del
          </button>
        </td>
      </tr>
    `).join('');
  } catch (e) {
    UI.toast(e.message, 'error');
    UI.setEmpty(tbody, 7, 'Failed to load cows.');
  }
}

const COW_COLS = [
  { key: 'Cow_ID',            label: 'Cow ID'        },
  { key: 'Cow',               label: 'Name'          },
  { key: 'Breed',             label: 'Breed'         },
  { key: 'Date_Of_Birth',     label: 'Date of Birth' },
  { key: 'Production_Liters', label: 'Production (L)'},
  { key: 'Health_Status',     label: 'Health'        },
  { key: 'is_active',         label: 'Active'        },
];

document.addEventListener('DOMContentLoaded', function() {
  ImportExport.addButtons(
    document.getElementById('cows-header-actions'),
    {
      getData:  function() { return _cowsData; },
      columns:  COW_COLS,
      title:    'Cows — Esperon Dairy Farm',
      filename: 'cows',
      onImport: async function(records) {
        if (!records.length) { UI.toast('No records found in file.', 'error'); return; }
        var ok = await UI.confirm('Import ' + records.length + ' cow(s)?');
        if (!ok) return;
        var success = 0, failed = 0;
        for (var r of records) {
          try {
            await API.cows.create({
              Cow:               r.Cow || r.Name || '',
              Breed:             r.Breed || null,
              Date_Of_Birth:     r.Date_Of_Birth || null,
              Production_Liters: parseFloat(r['Production (L)'] || r.Production_Liters || 0),
              Health_Status:     r.Health || r.Health_Status || 'Healthy',
            });
            success++;
          } catch(e) { failed++; }
        }
        UI.toast('Imported ' + success + ' cow(s).' + (failed ? ' ' + failed + ' failed.' : ''), success ? 'success' : 'error');
        loadCows();
      }
    }
  );
});

function openModal(id = null) {
  editingId = id;
  document.getElementById('modal-title').textContent = id ? 'Edit Cow' : 'Add Cow';

  if (!id) {
    ['f-cow-name','f-breed','f-dob','f-production','f-notes'].forEach(i => {
      const el = document.getElementById(i); if (el) el.value = '';
    });
    document.getElementById('f-health').value = 'Healthy';
  } else {
    API.cows.getById(id).then(c => {
      document.getElementById('f-cow-name').value  = c.Cow;
      document.getElementById('f-breed').value     = c.Breed || '';
      document.getElementById('f-dob').value       = c.Date_Of_Birth || '';
      document.getElementById('f-production').value = c.Production_Liters || 0;
      document.getElementById('f-health').value    = c.Health_Status || 'Healthy';
      document.getElementById('f-notes').value     = c.notes || '';
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
  const prod = parseFloat(document.getElementById('f-production').value);
  if (!name)       { UI.toast('Please enter a cow name.', 'error'); return; }
  if (isNaN(prod)) { UI.toast('Please enter a valid production value.', 'error'); return; }

  const data = {
    Cow:               name,
    Breed:             document.getElementById('f-breed').value.trim() || null,
    Date_Of_Birth:     document.getElementById('f-dob').value || null,
    Production_Liters: prod,
    Health_Status:     document.getElementById('f-health').value,
    notes:             document.getElementById('f-notes').value.trim() || null,
  };

  try {
    if (editingId) {
      await API.cows.update(editingId, data);
      UI.toast('Cow updated!');
    } else {
      await API.cows.create(data);
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