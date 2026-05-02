<?php
require_once __DIR__ . '/guard.php';
requireAuthPage();
$isAdmin = ($_SESSION['user']['role'] ?? '') === 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reminders &amp; Tasks � Esperon Dairy Farm</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link rel="stylesheet" href="css/style.css" />
  <style>
    .reminder-card { border-radius:12px; padding:14px 16px; margin-bottom:10px; border-left:4px solid; display:flex; align-items:flex-start; justify-content:space-between; gap:12px; transition:box-shadow .15s; }
    .reminder-card:hover { box-shadow:0 4px 16px rgba(0,0,0,0.08); }
    .reminder-card.overdue   { background:var(--danger-lt);  border-color:var(--danger); }
    .reminder-card.due-soon  { background:#fef9e7;           border-color:#f39c12; }
    .reminder-card.pending   { background:var(--success-lt); border-color:var(--olive); }
    .reminder-card.completed { background:rgba(255,255,255,0.4); border-color:#bdc3c7; opacity:.75; }
    @keyframes rmSlideIn { from{opacity:0;transform:translateY(-14px) scale(0.97)} to{opacity:1;transform:none} }
    @keyframes rmSpin    { to{transform:rotate(360deg)} }
  </style>
</head>
<body>
<nav class="nav" id="app-nav"></nav>
<main class="main">

  <div class="page-header">
    <div>
      <h1 class="page-title">Reminders &amp; Tasks</h1>
      <p class="page-subtitle" id="rem-subtitle">
        <?= $isAdmin ? 'Manage farm tasks and upcoming reminders.' : 'View your assigned tasks and reminders.' ?>
      </p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
      <button class="btn btn--ghost" onclick="loadReminders()">
        <span class="material-symbols-outlined" style="font-size:1rem;">refresh</span> Refresh
      </button>
      <?php if ($isAdmin): ?>
      <button class="btn btn--primary" id="addReminderBtn" style="background:var(--danger);border:none;">
        <span class="material-symbols-outlined" style="font-size:1rem;">add_task</span> Add Task
      </button>
      <?php else: ?>
      <span style="font-size:0.78rem;color:var(--muted);font-style:italic;padding:6px 12px;background:rgba(255,255,255,0.4);border-radius:8px;">
        <span class="material-symbols-outlined" style="font-size:0.9rem;vertical-align:middle;">visibility</span> View only � Admin manages tasks
      </span>
      <?php endif; ?>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats-grid" style="margin-bottom:var(--spacing-xl);grid-template-columns:repeat(4,1fr);">
    <div class="stat-card stat-card--danger">
      <div class="stat-card__icon"><span class="material-symbols-outlined">warning</span></div>
      <div class="stat-card__content"><div class="stat-card__val" id="stat-overdue">�</div><div class="stat-card__label">Overdue</div></div>
    </div>
    <div class="stat-card stat-card--gold">
      <div class="stat-card__icon"><span class="material-symbols-outlined">schedule</span></div>
      <div class="stat-card__content"><div class="stat-card__val" id="stat-due-soon">�</div><div class="stat-card__label">Due Soon</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-card__icon"><span class="material-symbols-outlined">pending_actions</span></div>
      <div class="stat-card__content"><div class="stat-card__val" id="stat-pending">�</div><div class="stat-card__label">Pending</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-card__icon"><span class="material-symbols-outlined">check_circle</span></div>
      <div class="stat-card__content"><div class="stat-card__val" id="stat-completed">�</div><div class="stat-card__label">Completed</div></div>
    </div>
  </div>

  <div class="card">
    <div class="card__header">
      <span style="font-family:var(--font-serif);font-size:1rem;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px;">
        <span class="material-symbols-outlined" style="color:var(--danger);font-size:1.2rem;">alarm</span>
        <?= $isAdmin ? 'All Tasks' : 'My Assigned Tasks' ?>
      </span>
      <div style="display:flex;gap:6px;flex-wrap:wrap;">
        <button class="btn-xs btn-xs--ghost" onclick="setFilter('all',this)" style="background:rgba(78,96,64,0.12);border-color:var(--olive);color:var(--olive-dark);">All</button>
        <button class="btn-xs btn-xs--ghost" onclick="setFilter('overdue',this)">Overdue</button>
        <button class="btn-xs btn-xs--ghost" onclick="setFilter('due-soon',this)">Due Soon</button>
        <button class="btn-xs btn-xs--ghost" onclick="setFilter('pending',this)">Pending</button>
        <button class="btn-xs btn-xs--ghost" onclick="setFilter('completed',this)">Completed</button>
      </div>
    </div>
    <div id="reminders-list" style="padding:16px 20px;min-height:120px;">
      <p style="color:var(--muted);font-size:0.84rem;">Loading�</p>
    </div>
  </div>

</main>

<script src="js/api.js"></script>
<script src="js/ui.js"></script>
<script src="js/nav.js"></script>
<script>
var _reminders = [];
var _filter    = 'all';
var IS_ADMIN   = <?= $isAdmin ? 'true' : 'false' ?>;

function getStatusInfo(dueDate, status) {
  if (status === 'completed') return { cls:'completed', color:'#8a7f72', label:'Completed', urgent:false };
  var now = new Date(), due = new Date(dueDate), h = (due - now) / (1000*60*60);
  if (h < 0)   return { cls:'overdue',  color:'var(--danger)', label:'Overdue',  urgent:true  };
  if (h <= 24) return { cls:'due-soon', color:'#f39c12',       label:'Due Soon', urgent:true  };
  return           { cls:'pending',  color:'var(--olive)',  label:'Pending',  urgent:false };
}

function formatDue(dateStr) {
  var d = new Date(dateStr), now = new Date(), tom = new Date(now);
  tom.setDate(tom.getDate()+1);
  var t = d.toLocaleTimeString([],{hour:'numeric',minute:'2-digit',hour12:true});
  if (d.toDateString()===now.toDateString()) return 'Today, '+t;
  if (d.toDateString()===tom.toDateString()) return 'Tomorrow, '+t;
  var m=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  return m[d.getMonth()]+' '+d.getDate()+', '+d.getFullYear()+' � '+t;
}

async function loadReminders() {
  var container = document.getElementById('reminders-list');
  container.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;">Loading�</p>';
  try {
    var res  = await fetch('../dairy_farm_backend/api/v1/reminders.php', { credentials:'include' });
    var data = await res.json();
    _reminders = data.success ? (data.data || []) : [];
  } catch(e) {
    container.innerHTML = '<p style="color:var(--danger);font-size:0.84rem;">Failed to load reminders.</p>';
    return;
  }
  updateStats();
  renderList();
}

function updateStats() {
  var overdue=0, dueSoon=0, pending=0, completed=0;
  _reminders.forEach(function(r) {
    var s = getStatusInfo(r.due_date, r.status);
    if (r.status==='completed') completed++;
    else if (s.cls==='overdue')  overdue++;
    else if (s.cls==='due-soon') dueSoon++;
    else pending++;
  });
  var set = function(id,v){ var el=document.getElementById(id); if(el) el.textContent=v; };
  set('stat-overdue', overdue); set('stat-due-soon', dueSoon);
  set('stat-pending', pending); set('stat-completed', completed);
  var sub = document.getElementById('rem-subtitle');
  if (sub && IS_ADMIN) sub.textContent = _reminders.length + ' total tasks � ' + (overdue+dueSoon) + ' need attention.';
  if (sub && !IS_ADMIN) sub.textContent = _reminders.length + ' task(s) assigned � ' + (overdue+dueSoon) + ' need attention.';
}

function setFilter(f, btn) {
  _filter = f;
  document.querySelectorAll('[onclick^="setFilter"]').forEach(function(b){
    b.style.background='rgba(255,255,255,.5)'; b.style.borderColor='var(--border)'; b.style.color='var(--text)';
  });
  if (btn) { btn.style.background='rgba(78,96,64,0.12)'; btn.style.borderColor='var(--olive)'; btn.style.color='var(--olive-dark)'; }
  renderList();
}

function renderList() {
  var container = document.getElementById('reminders-list');
  var list = _reminders.filter(function(r) {
    if (_filter === 'all') return true;
    var s = getStatusInfo(r.due_date, r.status);
    if (_filter === 'completed') return r.status === 'completed';
    return s.cls === _filter;
  });
  list.sort(function(a,b){ return new Date(a.due_date)-new Date(b.due_date); });

  if (!list.length) {
    container.innerHTML = '<div style="text-align:center;padding:28px 0;">'
      + '<span class="material-symbols-outlined" style="font-size:2.2rem;color:var(--olive);display:block;margin-bottom:8px;">check_circle</span>'
      + '<p style="color:var(--muted);font-size:0.84rem;">No ' + (_filter==='all'?'tasks':_filter+' tasks') + ' found.</p></div>';
    return;
  }

  container.innerHTML = list.map(function(r) {
    var s    = getStatusInfo(r.due_date, r.status);
    var done = r.status === 'completed';

    // Staff: can only mark complete. Admin: can mark complete + delete.
    var actions = '<div style="display:flex;gap:5px;flex-shrink:0;">'
      + (!done ? '<button onclick="markComplete('+r.reminder_id+')" title="Mark complete" '
        + 'style="background:var(--olive);color:#fff;border:none;border-radius:6px;width:30px;height:30px;cursor:pointer;display:flex;align-items:center;justify-content:center;">'
        + '<span class="material-symbols-outlined" style="font-size:1rem;">check</span></button>' : '')
      + (IS_ADMIN ? '<button onclick="deleteReminder('+r.reminder_id+')" title="Delete" '
        + 'style="background:rgba(192,57,43,0.1);color:var(--danger);border:none;border-radius:6px;width:30px;height:30px;cursor:pointer;display:flex;align-items:center;justify-content:center;">'
        + '<span class="material-symbols-outlined" style="font-size:1rem;">close</span></button>' : '')
      + '</div>';

    return '<div class="reminder-card ' + s.cls + '">'
      + '<div style="flex:1;min-width:0;">'
      + '<div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;color:' + s.color + ';margin-bottom:3px;">' + s.label + '</div>'
      + '<div style="font-size:0.92rem;font-weight:700;color:var(--text);' + (done?'text-decoration:line-through;opacity:.6;':'') + '">' + r.title + '</div>'
      + (r.description ? '<div style="font-size:0.78rem;color:var(--muted);margin-top:3px;">' + r.description + '</div>' : '')
      + '<div style="font-size:0.72rem;color:var(--muted);margin-top:5px;display:flex;align-items:center;gap:4px;">'
      + '<span class="material-symbols-outlined" style="font-size:0.85rem;">schedule</span>Due: ' + formatDue(r.due_date)
      + '</div>'
      + '</div>'
      + actions
      + '</div>';
  }).join('');
}

async function markComplete(id) {
  try {
    var csrf = localStorage.getItem('csrf_token') || '';
    var res  = await fetch('../dairy_farm_backend/api/v1/reminders.php?id='+id, {
      method:'PATCH', credentials:'include',
      headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},
      body: JSON.stringify({status:'completed'})
    });
    var data = await res.json();
    if (data.success) { UI.toast('Marked as completed!','success'); loadReminders(); }
    else UI.toast('Failed: '+(data.message||'error'),'error');
  } catch(e) { UI.toast('Network error.','error'); }
}

async function deleteReminder(id) {
  if (!IS_ADMIN) { UI.toast('Only admins can delete reminders.', 'error'); return; }
  UI.confirm('Delete this reminder?').then(async function(ok) {
    if (!ok) return;
    try {
      var csrf = localStorage.getItem('csrf_token') || '';
      var res  = await fetch('../dairy_farm_backend/api/v1/reminders.php?id='+id, {
        method:'DELETE', credentials:'include', headers:{'X-CSRF-Token':csrf}
      });
      var data = await res.json();
      if (data.success) { UI.toast('Reminder deleted.','success'); loadReminders(); }
      else UI.toast('Failed: '+(data.message||'error'),'error');
    } catch(e) { UI.toast('Network error.','error'); }
  });
}

<?php if ($isAdmin): ?>
// -- Add Reminder Modal (Admin only) ----------------------
(function() {
  var modalEl = document.createElement('div');
  modalEl.id = 'reminderModal';
  modalEl.style.cssText = 'display:none;position:fixed;inset:0;z-index:9999;background:rgba(42,31,21,0.45);backdrop-filter:blur(4px);align-items:center;justify-content:center;';
  modalEl.innerHTML = '<div style="background:rgba(255,255,255,0.97);border-radius:20px;box-shadow:0 12px 48px rgba(0,0,0,0.18);width:100%;max-width:460px;margin:16px;animation:rmSlideIn 0.25s ease;font-family:\'Lato\',sans-serif;">'
    + '<div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;background:linear-gradient(135deg,#c0392b,#e74c3c);border-radius:20px 20px 0 0;">'
    + '<div style="display:flex;align-items:center;gap:8px;"><span class="material-symbols-outlined" style="color:#fff;font-size:1.2rem;">alarm_add</span>'
    + '<span style="font-family:\'Playfair Display\',serif;font-size:1.05rem;font-weight:700;color:#fff;">Add Reminder</span></div>'
    + '<button id="rmClose" style="background:rgba(255,255,255,0.2);border:none;cursor:pointer;width:28px;height:28px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;"><span class="material-symbols-outlined" style="font-size:1rem;">close</span></button></div>'
    + '<div style="padding:20px 22px;">'
    + '<div style="margin-bottom:14px;"><label style="display:block;font-size:0.75rem;font-weight:700;color:#4a3f35;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:5px;">Task Title <span style="color:#c0392b;">*</span></label>'
    + '<input id="rm_title" type="text" placeholder="e.g. Vet check for Cow #3" style="width:100%;padding:9px 13px;border:1.5px solid #e8dfd2;border-radius:9px;font-size:0.88rem;font-family:\'Lato\',sans-serif;color:#2a1f15;background:#fff;outline:none;box-sizing:border-box;" />'
    + '<div id="rm_title_err" style="display:none;color:#c0392b;font-size:0.73rem;margin-top:3px;">Title is required.</div></div>'
    + '<div style="margin-bottom:14px;"><label style="display:block;font-size:0.75rem;font-weight:700;color:#4a3f35;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:5px;">Description <span style="color:#8a7f72;font-weight:400;">(optional)</span></label>'
    + '<textarea id="rm_desc" rows="2" placeholder="Add details..." style="width:100%;padding:9px 13px;border:1.5px solid #e8dfd2;border-radius:9px;font-size:0.88rem;font-family:\'Lato\',sans-serif;color:#2a1f15;background:#fff;outline:none;resize:vertical;box-sizing:border-box;"></textarea></div>'
    + '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:8px;">'
    + '<div><label style="display:block;font-size:0.75rem;font-weight:700;color:#4a3f35;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:5px;">Due Date <span style="color:#c0392b;">*</span></label>'
    + '<input id="rm_date" type="date" style="width:100%;padding:9px 13px;border:1.5px solid #e8dfd2;border-radius:9px;font-size:0.88rem;font-family:\'Lato\',sans-serif;color:#2a1f15;background:#fff;outline:none;box-sizing:border-box;" /></div>'
    + '<div><label style="display:block;font-size:0.75rem;font-weight:700;color:#4a3f35;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:5px;">Time <span style="color:#c0392b;">*</span></label>'
    + '<div style="display:flex;gap:5px;">'
    + '<select id="rm_hour" style="flex:2;min-width:0;padding:9px 4px;border:1.5px solid #e8dfd2;border-radius:9px;font-size:0.85rem;font-family:\'Lato\',sans-serif;color:#2a1f15;background:#fff;outline:none;cursor:pointer;">'
    + [1,2,3,4,5,6,7,8,9,10,11,12].map(function(h){return '<option value="'+h+'">'+h+'</option>';}).join('')
    + '</select>'
    + '<select id="rm_min" style="flex:2;min-width:0;padding:9px 4px;border:1.5px solid #e8dfd2;border-radius:9px;font-size:0.85rem;font-family:\'Lato\',sans-serif;color:#2a1f15;background:#fff;outline:none;cursor:pointer;">'
    + '<option value="00">00</option><option value="15">15</option><option value="30">30</option><option value="45">45</option>'
    + '</select>'
    + '<select id="rm_ampm" style="flex:2;min-width:0;padding:9px 4px;border:1.5px solid #e8dfd2;border-radius:9px;font-size:0.85rem;font-family:\'Lato\',sans-serif;color:#2a1f15;background:#fff;outline:none;cursor:pointer;">'
    + '<option value="AM">AM</option><option value="PM">PM</option>'
    + '</select></div></div></div>'
    + '<div id="rm_date_err" style="display:none;color:#c0392b;font-size:0.73rem;margin-bottom:10px;">Date and time are required.</div>'
    + '</div>'
    + '<div style="display:flex;justify-content:flex-end;gap:8px;padding:0 22px 18px;">'
    + '<button id="rmCancel" style="padding:9px 18px;border:1.5px solid #d4c9b8;border-radius:9px;background:#fff;color:#4a3f35;font-family:\'Lato\',sans-serif;font-size:0.85rem;font-weight:600;cursor:pointer;">Cancel</button>'
    + '<button id="rmSubmit" style="padding:9px 20px;border:none;border-radius:9px;background:linear-gradient(135deg,#4e6040,#6b8a5c);color:#fff;font-family:\'Lato\',sans-serif;font-size:0.85rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px;">'
    + '<span class="material-symbols-outlined" style="font-size:0.95rem;">add_task</span> Save Reminder</button>'
    + '</div></div>';
  document.body.appendChild(modalEl);
  var styleEl = document.createElement('style');
  styleEl.textContent = '@keyframes rmSlideIn{from{opacity:0;transform:translateY(-18px) scale(0.97)}to{opacity:1;transform:none}}@keyframes rmSpin{to{transform:rotate(360deg)}}';
  document.head.appendChild(styleEl);
  function setDefaults() {
    var now = new Date(); now.setMinutes(0,0,0); now.setHours(now.getHours()+1);
    document.getElementById('rm_date').value = now.getFullYear()+'-'+String(now.getMonth()+1).padStart(2,'0')+'-'+String(now.getDate()).padStart(2,'0');
    var h = now.getHours(); document.getElementById('rm_ampm').value = h>=12?'PM':'AM'; h=h%12||12; document.getElementById('rm_hour').value=h; document.getElementById('rm_min').value='00';
  }
  function to24h(h,min,ampm) { var hour=parseInt(h,10); if(ampm==='AM'&&hour===12) hour=0; if(ampm==='PM'&&hour!==12) hour+=12; return String(hour).padStart(2,'0')+':'+min+':00'; }
  function open() { setDefaults(); document.getElementById('rm_title').value=''; document.getElementById('rm_desc').value=''; document.getElementById('rm_title_err').style.display='none'; document.getElementById('rm_date_err').style.display='none'; modalEl.style.display='flex'; setTimeout(function(){ document.getElementById('rm_title').focus(); },50); }
  function close() { modalEl.style.display='none'; }
  document.getElementById('addReminderBtn').onclick = open;
  document.getElementById('rmClose').onclick  = close;
  document.getElementById('rmCancel').onclick = close;
  modalEl.addEventListener('click', function(e){ if(e.target===modalEl) close(); });
  document.addEventListener('keydown', function(e){ if(e.key==='Escape'&&modalEl.style.display==='flex') close(); });
  document.getElementById('rmSubmit').onclick = async function() {
    var title = document.getElementById('rm_title').value.trim();
    var date  = document.getElementById('rm_date').value;
    var valid = true;
    if (!title) { document.getElementById('rm_title_err').style.display='block'; valid=false; } else document.getElementById('rm_title_err').style.display='none';
    if (!date)  { document.getElementById('rm_date_err').style.display='block';  valid=false; } else document.getElementById('rm_date_err').style.display='none';
    if (!valid) return;
    var dueDate = date+' '+to24h(document.getElementById('rm_hour').value, document.getElementById('rm_min').value, document.getElementById('rm_ampm').value);
    var btn = document.getElementById('rmSubmit');
    btn.disabled=true; btn.innerHTML='<span class="material-symbols-outlined" style="font-size:0.95rem;animation:rmSpin 0.7s linear infinite;">progress_activity</span> Saving�';
    try {
      var csrf = localStorage.getItem('csrf_token')||'';
      var res  = await fetch('../dairy_farm_backend/api/v1/reminders.php', { method:'POST', credentials:'include', headers:{'Content-Type':'application/json','X-CSRF-Token':csrf}, body: JSON.stringify({title:title, description:document.getElementById('rm_desc').value.trim()||null, due_date:dueDate, status:'pending'}) });
      var data = await res.json();
      if (data.success) { close(); loadReminders(); UI.toast('Reminder added!','success'); }
      else UI.toast('Failed: '+(data.message||'error'),'error');
    } catch(e) { UI.toast('Network error.','error'); }
    finally { btn.disabled=false; btn.innerHTML='<span class="material-symbols-outlined" style="font-size:0.95rem;">add_task</span> Save Reminder'; }
  };
})();
<?php endif; ?>

(async function() {
  var res  = await fetch('../dairy_farm_backend/api/v1/auth.php?action=status', { credentials:'include' });
  var data = await res.json();
  if (data.success && data.data) {
    localStorage.setItem('csrf_token', data.data.csrf_token||'');
    if (data.data.user) localStorage.setItem('user', JSON.stringify(data.data.user));
  }
  loadReminders();
})();
</script>
</body>
</html>