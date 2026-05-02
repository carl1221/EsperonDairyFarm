<?php
require_once __DIR__ . '/guard.php';
requireAuthPage();
$isAdmin  = ($_SESSION['user']['role'] ?? '') === 'Admin';
$userName = $_SESSION['user']['name'] ?? 'Staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Notes &amp; Announcements — Esperon Dairy Farm</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link rel="stylesheet" href="css/style.css" />
  <style>
    .note-bubble {
      background: rgba(255,255,255,0.55);
      border: 1px solid var(--border-light);
      border-radius: 12px;
      padding: 14px 18px;
      margin-bottom: 10px;
      transition: box-shadow .15s;
    }
    .note-bubble:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.07); }
    .note-bubble__text { font-size: 0.9rem; color: var(--text); line-height: 1.5; white-space: pre-wrap; }
    .note-bubble__meta { font-size: 0.72rem; color: var(--muted); margin-top: 6px; display:flex; align-items:center; justify-content:space-between; flex-wrap: wrap; gap: 4px; }
    .note-bubble__actions { display: flex; gap: 8px; align-items: center; }
    .note-bubble__tag {
      display: inline-flex; align-items: center; gap: 3px;
      font-size: 0.68rem; font-weight: 600; padding: 2px 8px;
      border-radius: 20px; background: rgba(78,96,64,0.1); color: var(--olive);
    }
    .note-bubble__tag--entity {
      background: rgba(59,130,246,0.1); color: #3b82f6;
    }
    .note-bubble__tag--edited {
      background: rgba(245,158,11,0.1); color: #d97706; font-weight: 400; font-style: italic;
    }
    textarea.note-input {
      width: 100%; padding: 12px 16px;
      border: 1.5px solid var(--border-light); border-radius: 12px;
      font-size: 0.9rem; font-family: var(--font-sans); color: var(--text);
      background: rgba(255,255,255,.7); resize: vertical; outline: none;
      transition: border-color .15s, box-shadow .15s; box-sizing: border-box;
    }
    textarea.note-input:focus { border-color: var(--olive); box-shadow: 0 0 0 3px rgba(78,96,64,.12); }
    select.note-select {
      width: 100%; padding: 9px 14px;
      border: 1.5px solid var(--border-light); border-radius: 10px;
      font-size: 0.88rem; font-family: var(--font-sans); color: var(--text);
      background: rgba(255,255,255,.7); outline: none; cursor: pointer;
      transition: border-color .15s;
    }
    select.note-select:focus { border-color: var(--olive); }
    .filter-bar { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
    .filter-chip {
      padding: 5px 14px; border-radius: 20px; font-size: 0.78rem; font-weight: 600;
      border: 1.5px solid var(--border-light); background: rgba(255,255,255,.6);
      cursor: pointer; transition: all .15s; color: var(--text-light);
    }
    .filter-chip.active, .filter-chip:hover {
      background: var(--olive); color: #fff; border-color: var(--olive);
    }
    .entity-row { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px; }
  </style>
</head>
<body>
<nav class="nav" id="app-nav"></nav>
<main class="main">

  <div class="page-header">
    <div>
      <h1 class="page-title">Notes &amp; Announcements</h1>
      <p class="page-subtitle">
        <?= $isAdmin ? 'Post announcements and notes for staff.' : 'View announcements and post notes to admin.' ?>
      </p>
    </div>
    <button class="btn btn--ghost" onclick="loadNotes()">
      <span class="material-symbols-outlined" style="font-size:1rem;">refresh</span> Refresh
    </button>
  </div>

  <!-- Stat cards -->
  <div class="stats-grid" style="margin-bottom:var(--spacing-xl);grid-template-columns:repeat(4,1fr);">
    <div class="stat-card">
      <div class="stat-card__icon"><span class="material-symbols-outlined">edit_note</span></div>
      <div class="stat-card__content"><div class="stat-card__val" id="stat-total">0</div><div class="stat-card__label">Total Notes</div></div>
    </div>
    <div class="stat-card stat-card--gold">
      <div class="stat-card__icon"><span class="material-symbols-outlined">today</span></div>
      <div class="stat-card__content"><div class="stat-card__val" id="stat-today">0</div><div class="stat-card__label">Posted Today</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-card__icon"><span class="material-symbols-outlined">person</span></div>
      <div class="stat-card__content"><div class="stat-card__val" id="stat-author">—</div><div class="stat-card__label">Last Author</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-card__icon"><span class="material-symbols-outlined">link</span></div>
      <div class="stat-card__content"><div class="stat-card__val" id="stat-linked">0</div><div class="stat-card__label">Linked Notes</div></div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--spacing-lg);align-items:start;">

    <!-- Compose -->
    <div class="card">
      <div class="card__header">
        <span style="font-family:var(--font-serif);font-size:1rem;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px;">
          <span class="material-symbols-outlined" style="color:var(--info);font-size:1.2rem;" id="compose-icon">edit_note</span>
          <span id="compose-title">Post New Note</span>
        </span>
        <button id="cancel-edit-btn" class="btn-xs" onclick="cancelEdit()" style="display:none;font-size:0.75rem;">
          <span class="material-symbols-outlined" style="font-size:0.85rem;">close</span> Cancel
        </button>
      </div>
      <div style="padding:18px 20px;">
        <input type="hidden" id="edit-note-id" value="" />

        <!-- Category -->
        <div style="margin-bottom:10px;">
          <label style="font-size:0.78rem;font-weight:600;color:var(--text-light);display:block;margin-bottom:4px;">Category</label>
          <select class="note-select" id="note-category">
            <option value="General">📋 General</option>
            <option value="Health">🏥 Health</option>
            <option value="Feeding">🌾 Feeding</option>
            <option value="Maintenance">🔧 Maintenance</option>
            <option value="Finance">💰 Finance</option>
            <option value="Other">📌 Other</option>
          </select>
        </div>

        <!-- Entity link (optional) -->
        <div style="margin-bottom:10px;">
          <label style="font-size:0.78rem;font-weight:600;color:var(--text-light);display:block;margin-bottom:4px;">
            Link to (optional)
          </label>
          <div class="entity-row">
            <select class="note-select" id="note-entity-type" onchange="onEntityTypeChange()">
              <option value="">— None —</option>
              <option value="Cow">🐄 Cow</option>
              <option value="Order">📦 Order</option>
              <option value="Customer">👤 Customer</option>
              <option value="Worker">👷 Worker</option>
            </select>
            <input type="number" class="note-select" id="note-entity-id" placeholder="ID #"
              style="display:none;" min="1" />
          </div>
        </div>

        <!-- Text -->
        <textarea class="note-input" id="note-input" rows="5"
          placeholder="Post an announcement or note for staff…"></textarea>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;">
          <span style="font-size:0.75rem;color:var(--muted);" id="char-count">0 / 1000 characters</span>
          <button class="btn btn--primary" id="save-note-btn">
            <span class="material-symbols-outlined" style="font-size:1rem;">send</span>
            <span id="save-btn-label">Post Note</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Tips -->
    <div class="card" style="background:rgba(78,96,64,0.06);">
      <div class="card__header">
        <span style="font-family:var(--font-serif);font-size:1rem;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px;">
          <span class="material-symbols-outlined" style="color:var(--olive);font-size:1.2rem;">lightbulb</span>
          Tips
        </span>
      </div>
      <div style="padding:16px 20px;font-size:0.84rem;color:var(--text-light);line-height:1.7;">
        <p>• Notes are visible to all staff on the dashboard.</p>
        <p style="margin-top:6px;">• Use <strong>Category</strong> to classify notes (Health, Feeding, etc.).</p>
        <p style="margin-top:6px;">• Use <strong>Link to</strong> to attach a note to a specific Cow, Order, Customer, or Worker by their ID.</p>
        <p style="margin-top:6px;">• You can edit your own notes after posting.</p>
        <p style="margin-top:6px;">• To assign tasks with due dates, use <a href="reminders.php" style="color:var(--olive);font-weight:600;">Reminders</a> instead.</p>
      </div>
    </div>

  </div>

  <!-- Notes feed -->
  <div class="card" style="margin-top:var(--spacing-lg);">
    <div class="card__header">
      <span style="font-family:var(--font-serif);font-size:1rem;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px;">
        <span class="material-symbols-outlined" style="color:var(--olive);font-size:1.2rem;">forum</span>
        <?= $isAdmin ? 'All Notes' : 'Announcements' ?>
        <span id="notes-count-badge" class="badge badge--green" style="font-size:0.65rem;">0</span>
      </span>
      <div style="display:flex;gap:8px;align-items:center;">
        <?php if ($isAdmin): ?>
        <button class="btn-xs btn-xs--danger" onclick="clearAllNotes()" style="font-size:0.75rem;">
          <span class="material-symbols-outlined" style="font-size:0.85rem;">delete_sweep</span> Clear All
        </button>
        <?php endif; ?>
      </div>
    </div>

    <!-- Filter chips -->
    <div style="padding:12px 20px 0;">
      <div class="filter-bar" id="filter-bar">
        <button class="filter-chip active" data-cat="" onclick="setFilter(this, '')">All</button>
        <button class="filter-chip" data-cat="General"     onclick="setFilter(this, 'General')">📋 General</button>
        <button class="filter-chip" data-cat="Health"      onclick="setFilter(this, 'Health')">🏥 Health</button>
        <button class="filter-chip" data-cat="Feeding"     onclick="setFilter(this, 'Feeding')">🌾 Feeding</button>
        <button class="filter-chip" data-cat="Maintenance" onclick="setFilter(this, 'Maintenance')">🔧 Maintenance</button>
        <button class="filter-chip" data-cat="Finance"     onclick="setFilter(this, 'Finance')">💰 Finance</button>
        <button class="filter-chip" data-cat="Other"       onclick="setFilter(this, 'Other')">📌 Other</button>
      </div>
    </div>

    <div id="notes-feed" style="padding:16px 20px;"></div>
  </div>

</main>

<script src="js/api.js"></script>
<script src="js/ui.js"></script>
<script src="js/nav.js"></script>
<script>
var IS_ADMIN     = <?= $isAdmin ? 'true' : 'false' ?>;
var CURRENT_USER = '<?= htmlspecialchars($userName) ?>';
var CURRENT_ID   = <?= (int)($_SESSION['user']['id'] ?? 0) ?>;
var _notes       = [];
var _activeFilter = '';

// ── Category emoji map ────────────────────────────────────
var CAT_EMOJI = {
  General: '📋', Health: '🏥', Feeding: '🌾',
  Maintenance: '🔧', Finance: '💰', Other: '📌'
};
var ENTITY_EMOJI = { Cow: '🐄', Order: '📦', Customer: '👤', Worker: '👷' };

// ── Load notes from DB ────────────────────────────────────
async function loadNotes() {
  try {
    _notes = await API.notes.getAll();
    renderNotes();
  } catch(e) {
    document.getElementById('notes-feed').innerHTML =
      '<p style="color:var(--danger);font-size:0.84rem;">' + escapeHtml(e.message) + '</p>';
  }
}

function setFilter(btn, cat) {
  _activeFilter = cat;
  document.querySelectorAll('.filter-chip').forEach(function(c){ c.classList.remove('active'); });
  btn.classList.add('active');
  renderNotes();
}

function renderNotes() {
  var feed  = document.getElementById('notes-feed');
  var badge = document.getElementById('notes-count-badge');
  if (!feed) return;

  var filtered = _activeFilter
    ? _notes.filter(function(n){ return n.category === _activeFilter; })
    : _notes;

  var today      = new Date().toDateString();
  var todayCount = _notes.filter(function(n){ return new Date(n.created_at).toDateString() === today; }).length;
  var lastAuthor = _notes.length ? _notes[0].author : '—';
  var linkedCount = _notes.filter(function(n){ return n.entity_type; }).length;

  var set = function(id,v){ var el=document.getElementById(id); if(el) el.textContent=v; };
  set('stat-total',  _notes.length);
  set('stat-today',  todayCount);
  set('stat-author', lastAuthor);
  set('stat-linked', linkedCount);
  if (badge) badge.textContent = filtered.length;

  if (!filtered.length) {
    feed.innerHTML = '<div style="text-align:center;padding:28px 0;">'
      + '<span class="material-symbols-outlined" style="font-size:2.2rem;color:var(--muted);display:block;margin-bottom:8px;">edit_note</span>'
      + '<p style="color:var(--muted);font-size:0.84rem;">No notes found.</p></div>';
    return;
  }

  feed.innerHTML = filtered.map(function(n) {
    var canEdit   = IS_ADMIN || (toInt(n.author_id) === CURRENT_ID);
    var canDelete = canEdit;

    var wasEdited = n.updated_at && n.updated_at !== n.created_at;

    var catTag = '<span class="note-bubble__tag">'
      + (CAT_EMOJI[n.category] || '📋') + ' ' + escapeHtml(n.category)
      + '</span>';

    var entityTag = n.entity_type
      ? '<span class="note-bubble__tag note-bubble__tag--entity">'
        + (ENTITY_EMOJI[n.entity_type] || '') + ' ' + escapeHtml(n.entity_type) + ' #' + n.entity_id
        + '</span>'
      : '';

    var editedTag = wasEdited
      ? '<span class="note-bubble__tag note-bubble__tag--edited">edited</span>'
      : '';

    var actions = '';
    if (canEdit) {
      actions += '<button onclick="startEdit(' + JSON.stringify(n).replace(/"/g,'&quot;') + ')" title="Edit note" '
        + 'style="background:none;border:none;cursor:pointer;color:var(--muted);padding:0;display:flex;align-items:center;gap:3px;font-size:0.72rem;" '
        + 'onmouseover="this.style.color=\'var(--info)\'" onmouseout="this.style.color=\'var(--muted)\'">'
        + '<span class="material-symbols-outlined" style="font-size:0.9rem;">edit</span> Edit</button>';
    }
    if (canDelete) {
      actions += '<button onclick="deleteNote(' + n.note_id + ')" title="Delete note" '
        + 'style="background:none;border:none;cursor:pointer;color:var(--muted);padding:0;display:flex;align-items:center;gap:3px;font-size:0.72rem;" '
        + 'onmouseover="this.style.color=\'var(--danger)\'" onmouseout="this.style.color=\'var(--muted)\'">'
        + '<span class="material-symbols-outlined" style="font-size:0.9rem;">delete</span> Delete</button>';
    }

    var timeStr = new Date(n.created_at).toLocaleString();
    return '<div class="note-bubble">'
      + '<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px;">' + catTag + entityTag + editedTag + '</div>'
      + '<div class="note-bubble__text">' + escapeHtml(n.text) + '</div>'
      + '<div class="note-bubble__meta">'
      + '<span><strong>' + escapeHtml(n.author) + '</strong>'
      + ' <span style="opacity:.6;">(' + escapeHtml(n.author_role) + ')</span>'
      + ' · ' + timeStr + '</span>'
      + '<div class="note-bubble__actions">' + actions + '</div>'
      + '</div>'
      + '</div>';
  }).join('');
}

// ── Edit helpers ──────────────────────────────────────────
function startEdit(n) {
  document.getElementById('edit-note-id').value    = n.note_id;
  document.getElementById('note-input').value      = n.text;
  document.getElementById('note-category').value   = n.category || 'General';
  document.getElementById('note-entity-type').value = n.entity_type || '';
  onEntityTypeChange();
  if (n.entity_id) document.getElementById('note-entity-id').value = n.entity_id;

  document.getElementById('compose-title').textContent = 'Edit Note';
  document.getElementById('compose-icon').textContent  = 'edit';
  document.getElementById('save-btn-label').textContent = 'Save Changes';
  document.getElementById('cancel-edit-btn').style.display = '';

  document.getElementById('note-input').focus();
  document.getElementById('note-input').scrollIntoView({ behavior: 'smooth', block: 'center' });
  updateCharCount();
}

function cancelEdit() {
  document.getElementById('edit-note-id').value     = '';
  document.getElementById('note-input').value       = '';
  document.getElementById('note-category').value    = 'General';
  document.getElementById('note-entity-type').value = '';
  document.getElementById('note-entity-id').value   = '';
  document.getElementById('note-entity-id').style.display = 'none';
  document.getElementById('compose-title').textContent    = 'Post New Note';
  document.getElementById('compose-icon').textContent     = 'edit_note';
  document.getElementById('save-btn-label').textContent   = 'Post Note';
  document.getElementById('cancel-edit-btn').style.display = 'none';
  updateCharCount();
}

function onEntityTypeChange() {
  var type = document.getElementById('note-entity-type').value;
  var idField = document.getElementById('note-entity-id');
  if (type) {
    idField.style.display = '';
    idField.placeholder = ENTITY_EMOJI[type] + ' ' + type + ' ID #';
  } else {
    idField.style.display = 'none';
    idField.value = '';
  }
}

// ── Utilities ─────────────────────────────────────────────
function toInt(v) { return parseInt(v, 10) || 0; }

function escapeHtml(str) {
  return String(str || '')
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function updateCharCount() {
  var len = (document.getElementById('note-input').value || '').length;
  var el  = document.getElementById('char-count');
  if (el) {
    el.textContent = len + ' / 1000 characters';
    el.style.color = len > 900 ? 'var(--danger)' : 'var(--muted)';
  }
}

// ── Character counter ─────────────────────────────────────
var noteInput = document.getElementById('note-input');
if (noteInput) {
  noteInput.addEventListener('input', function() {
    if (this.value.length > 1000) this.value = this.value.slice(0, 1000);
    updateCharCount();
  });
}

// ── Save (post or edit) ───────────────────────────────────
var saveBtn = document.getElementById('save-note-btn');
if (saveBtn) {
  saveBtn.addEventListener('click', async function() {
    var text       = (document.getElementById('note-input').value || '').trim();
    var category   = document.getElementById('note-category').value || 'General';
    var entityType = document.getElementById('note-entity-type').value || null;
    var entityId   = entityType ? toInt(document.getElementById('note-entity-id').value) : null;
    var editId     = toInt(document.getElementById('edit-note-id').value);

    if (!text) { UI.toast('Please write a note first.', 'error'); return; }
    if (entityType && !entityId) { UI.toast('Please enter a valid ' + entityType + ' ID.', 'error'); return; }

    var payload = { text, category };
    if (entityType) { payload.entity_type = entityType; payload.entity_id = entityId; }

    saveBtn.disabled = true;
    document.getElementById('save-btn-label').textContent = editId ? 'Saving…' : 'Posting…';

    try {
      if (editId) {
        await API.notes.update(editId, payload);
        UI.toast('Note updated.', 'success');
        cancelEdit();
      } else {
        await API.notes.post(payload);
        document.getElementById('note-input').value = '';
        updateCharCount();
        UI.toast('Note posted!', 'success');
      }
      loadNotes();
    } catch(e) {
      UI.toast(e.message, 'error');
    } finally {
      saveBtn.disabled = false;
      document.getElementById('save-btn-label').textContent = editId ? 'Save Changes' : 'Post Note';
    }
  });
}

// ── Delete ────────────────────────────────────────────────
async function deleteNote(id) {
  var ok = await UI.confirm('Delete this note?');
  if (!ok) return;
  try {
    await API.notes.delete(id);
    UI.toast('Note deleted.', 'success');
    loadNotes();
  } catch(e) { UI.toast(e.message, 'error'); }
}

<?php if ($isAdmin): ?>
async function clearAllNotes() {
  var ok = await UI.confirm('Clear all notes and announcements? This cannot be undone.');
  if (!ok) return;
  try {
    await API.notes.clearAll();
    UI.toast('All notes cleared.', 'success');
    loadNotes();
  } catch(e) { UI.toast(e.message, 'error'); }
}
<?php endif; ?>

loadNotes();
</script>
</body>
</html>
