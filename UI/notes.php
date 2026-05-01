<?php
require_once __DIR__ . '/guard.php';
requireAuthPage();
$isAdmin = ($_SESSION['user']['role'] ?? '') === 'Admin';
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
    .note-bubble__text { font-size: 0.9rem; color: var(--text); line-height: 1.5; }
    .note-bubble__meta { font-size: 0.72rem; color: var(--muted); margin-top: 6px; display:flex; align-items:center; justify-content:space-between; }
    textarea.note-input {
      width: 100%; padding: 12px 16px;
      border: 1.5px solid var(--border-light); border-radius: 12px;
      font-size: 0.9rem; font-family: var(--font-sans); color: var(--text);
      background: rgba(255,255,255,.7); resize: vertical; outline: none;
      transition: border-color .15s, box-shadow .15s; box-sizing: border-box;
    }
    textarea.note-input:focus { border-color: var(--olive); box-shadow: 0 0 0 3px rgba(78,96,64,.12); }
  </style>
</head>
<body>
<nav class="nav" id="app-nav"></nav>
<main class="main">

  <div class="page-header">
    <div>
      <h1 class="page-title">Notes &amp; Announcements</h1>
      <p class="page-subtitle" id="notes-subtitle">
        <?= $isAdmin ? 'Post announcements and notes for staff.' : 'View announcements and post notes to admin.' ?>
      </p>
    </div>
    <button class="btn btn--ghost" onclick="renderNotes()">
      <span class="material-symbols-outlined" style="font-size:1rem;">refresh</span> Refresh
    </button>
  </div>

  <!-- Stat cards -->
  <div class="stats-grid" style="margin-bottom:var(--spacing-xl);grid-template-columns:repeat(3,1fr);">
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
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--spacing-lg);align-items:start;">

    <!-- Compose -->
    <div class="card">
      <div class="card__header">
        <span style="font-family:var(--font-serif);font-size:1rem;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px;">
          <span class="material-symbols-outlined" style="color:var(--info);font-size:1.2rem;">edit_note</span>
          Post New Note
        </span>
      </div>
      <div style="padding:18px 20px;">
        <textarea class="note-input" id="note-input" rows="5"
          placeholder="Post an announcement or note for staff (e.g. Vet visit tomorrow at 9 AM — all staff must be present)…"></textarea>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;">
          <span style="font-size:0.75rem;color:var(--muted);" id="char-count">0 / 500 characters</span>
          <button class="btn btn--primary" id="save-note-btn">
            <span class="material-symbols-outlined" style="font-size:1rem;">send</span> Post Note
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
        <p>• Notes are visible to all Admin users on the dashboard.</p>
        <p style="margin-top:6px;">• Up to <strong>15 notes</strong> are stored at a time. Older notes are removed automatically.</p>
        <p style="margin-top:6px;">• Use notes for shift announcements, vet visits, or important reminders.</p>
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
      <?php if ($isAdmin): ?>
      <button class="btn-xs btn-xs--danger" onclick="clearAllNotes()" style="font-size:0.75rem;">
        <span class="material-symbols-outlined" style="font-size:0.85rem;">delete_sweep</span> Clear All
      </button>
      <?php endif; ?>
    </div>
    <div id="notes-feed" style="padding:16px 20px;"></div>
  </div>

</main>

<script src="js/api.js"></script>
<script src="js/ui.js"></script>
<script src="js/nav.js"></script>
<script>
// Staff use a shared key so they see admin notes; admin uses admin_notes
var NOTES_KEY = 'admin_notes';
var IS_ADMIN  = <?= $isAdmin ? 'true' : 'false' ?>;
var CURRENT_USER = '<?= htmlspecialchars($userName) ?>';

function getStoredUser() {
  try { return JSON.parse(localStorage.getItem('user') || '{}'); } catch { return {}; }
}
function loadNotes() {
  try { return JSON.parse(localStorage.getItem(NOTES_KEY) || '[]'); } catch { return []; }
}

function renderNotes() {
  var feed  = document.getElementById('notes-feed');
  var badge = document.getElementById('notes-count-badge');
  var sub   = document.getElementById('notes-subtitle');
  if (!feed) return;
  var notes = loadNotes();
  var today = new Date().toDateString();
  var todayCount = notes.filter(function(n){ return new Date(n.time).toDateString() === today; }).length;
  var lastAuthor = notes.length ? notes[0].author : '—';
  var set = function(id,v){ var el=document.getElementById(id); if(el) el.textContent=v; };
  set('stat-total',  notes.length);
  set('stat-today',  todayCount);
  set('stat-author', lastAuthor);
  if (badge) badge.textContent = notes.length;

  if (!notes.length) {
    feed.innerHTML = '<div style="text-align:center;padding:28px 0;">'
      + '<span class="material-symbols-outlined" style="font-size:2.2rem;color:var(--muted);display:block;margin-bottom:8px;">edit_note</span>'
      + '<p style="color:var(--muted);font-size:0.84rem;">No announcements yet.</p></div>';
    return;
  }

  feed.innerHTML = notes.map(function(n, idx) {
    // Staff can only delete their own notes; Admin can delete any
    var canDelete = IS_ADMIN || n.author === CURRENT_USER;
    var deleteBtn = canDelete
      ? '<button onclick="deleteNote('+idx+')" title="Delete note" '
        + 'style="background:none;border:none;cursor:pointer;color:var(--muted);padding:0;display:flex;align-items:center;gap:3px;font-size:0.72rem;" '
        + 'onmouseover="this.style.color=\'var(--danger)\'" onmouseout="this.style.color=\'var(--muted)\'">'
        + '<span class="material-symbols-outlined" style="font-size:0.9rem;">delete</span> Delete</button>'
      : '';
    return '<div class="note-bubble">'
      + '<div class="note-bubble__text">' + n.text + '</div>'
      + '<div class="note-bubble__meta">'
      + '<span><strong>' + n.author + '</strong> · ' + n.time + '</span>'
      + deleteBtn
      + '</div>'
      + '</div>';
  }).join('');
}

function deleteNote(idx) {
  UI.confirm('Delete this note?').then(function(ok) {
    if (!ok) return;
    var notes = loadNotes();
    // Double-check permission
    if (!IS_ADMIN && notes[idx] && notes[idx].author !== CURRENT_USER) {
      UI.toast('You can only delete your own notes.', 'error'); return;
    }
    notes.splice(idx, 1);
    localStorage.setItem(NOTES_KEY, JSON.stringify(notes));
    renderNotes();
    UI.toast('Note deleted.', 'success');
  });
}

<?php if ($isAdmin): ?>
function clearAllNotes() {
  UI.confirm('Clear all notes and announcements? This cannot be undone.').then(function(ok) {
    if (!ok) return;
    localStorage.removeItem(NOTES_KEY);
    renderNotes();
    UI.toast('All notes cleared.', 'success');
  });
}
<?php endif; ?>

// Character counter
var noteInput = document.getElementById('note-input');
var charCount = document.getElementById('char-count');
if (noteInput) {
  noteInput.addEventListener('input', function() {
    var len = this.value.length;
    charCount.textContent = len + ' / 500 characters';
    charCount.style.color = len > 450 ? 'var(--danger)' : 'var(--muted)';
    if (len > 500) this.value = this.value.slice(0, 500);
  });
}

// Post note
var saveBtn = document.getElementById('save-note-btn');
if (saveBtn) {
  saveBtn.addEventListener('click', function() {
    var text = noteInput.value.trim();
    if (!text) { UI.toast('Please write a note first.', 'error'); return; }
    var notes = loadNotes();
    var u     = getStoredUser();
    notes.unshift({ text: text, author: u.name || CURRENT_USER, time: new Date().toLocaleString() });
    if (notes.length > 15) notes.pop();
    localStorage.setItem(NOTES_KEY, JSON.stringify(notes));
    noteInput.value = '';
    if (charCount) charCount.textContent = '0 / 500 characters';
    renderNotes();
    UI.toast('Note posted!', 'success');
  });
}

(async function() {
  var res  = await fetch('../dairy_farm_backend/api/auth.php?action=status', { credentials:'include' });
  var data = await res.json();
  if (data.success && data.data) {
    localStorage.setItem('csrf_token', data.data.csrf_token || '');
    if (data.data.user) localStorage.setItem('user', JSON.stringify(data.data.user));
  }
  renderNotes();
})();
</script>
</body>
</html>
