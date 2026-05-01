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
  <title>Online Staff — Esperon Dairy Farm</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link rel="stylesheet" href="css/style.css" />
  <style>
    .pulse { animation: pulse 2s infinite; }
    @keyframes pulse {
      0%, 100% { box-shadow: 0 0 0 0 rgba(39,174,96,0.4); }
      50%       { box-shadow: 0 0 0 5px rgba(39,174,96,0); }
    }
    @keyframes spin { to { transform: rotate(360deg); } }
  </style>
</head>
<body>
<nav class="nav" id="app-nav"></nav>
<main class="main">

  <div class="page-header">
    <div>
      <h1 class="page-title">Online Staff</h1>
      <p class="page-subtitle" id="page-sub">Real-time staff activity monitor.</p>
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
      <span id="last-refresh" style="font-size:0.78rem;color:var(--muted);"></span>
      <button class="btn btn--ghost" onclick="loadOnlineStaff()">
        <span class="material-symbols-outlined" style="font-size:1rem;">refresh</span> Refresh
      </button>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats-grid" style="margin-bottom:var(--spacing-xl);grid-template-columns:repeat(3,1fr);">
    <div class="stat-card">
      <div class="stat-card__icon"><span class="material-symbols-outlined" style="color:#27ae60;">wifi</span></div>
      <div class="stat-card__content">
        <div class="stat-card__val" id="stat-online" style="color:#27ae60;">—</div>
        <div class="stat-card__label">Online Now</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-card__icon"><span class="material-symbols-outlined">wifi_off</span></div>
      <div class="stat-card__content">
        <div class="stat-card__val" id="stat-offline">—</div>
        <div class="stat-card__label">Offline</div>
      </div>
    </div>
    <div class="stat-card stat-card--gold">
      <div class="stat-card__icon"><span class="material-symbols-outlined">groups</span></div>
      <div class="stat-card__content">
        <div class="stat-card__val" id="stat-total">—</div>
        <div class="stat-card__label">Total Staff</div>
      </div>
    </div>
  </div>

  <!-- Staff table -->
  <div class="card">
    <div class="card__header">
      <span style="font-family:var(--font-serif);font-size:1rem;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px;">
        <span class="material-symbols-outlined" style="color:var(--olive);font-size:1.2rem;">manage_accounts</span>
        Staff Activity
      </span>
      <div style="display:flex;gap:6px;">
        <button class="btn-xs btn-xs--ghost" onclick="setOnlineFilter('all',this)"
          style="background:rgba(78,96,64,0.12);border-color:var(--olive);color:var(--olive-dark);">All</button>
        <button class="btn-xs btn-xs--ghost" onclick="setOnlineFilter('online',this)">Online</button>
        <button class="btn-xs btn-xs--ghost" onclick="setOnlineFilter('offline',this)">Offline</button>
      </div>
    </div>

    <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="background:rgba(255,255,255,0.2);">
            <th style="padding:12px 20px;text-align:left;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted);border-bottom:1px solid var(--border-light);">Staff Member</th>
            <th style="padding:12px 20px;text-align:left;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted);border-bottom:1px solid var(--border-light);">Role</th>
            <th style="padding:12px 20px;text-align:left;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted);border-bottom:1px solid var(--border-light);">Status</th>
            <th style="padding:12px 20px;text-align:left;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted);border-bottom:1px solid var(--border-light);">Last Active</th>
            <th style="padding:12px 20px;text-align:left;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted);border-bottom:1px solid var(--border-light);">Last Heartbeat</th>
          </tr>
        </thead>
        <tbody id="online-tbody">
          <tr><td colspan="5" style="padding:32px;text-align:center;color:var(--muted);font-size:0.84rem;">Loading…</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</main>

<script src="js/api.js"></script>
<script src="js/ui.js"></script>
<script src="js/nav.js"></script>
<script>
var _allStaff      = [];
var _onlineFilter  = 'all';
var _refreshTimer  = null;

function timeAgo(dateStr) {
  if (!dateStr) return 'Never';
  var diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
  if (diff < 5)     return 'Just now';
  if (diff < 60)    return diff + 's ago';
  if (diff < 3600)  return Math.floor(diff / 60) + 'm ago';
  if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
  return Math.floor(diff / 86400) + 'd ago';
}

function formatDateTime(dateStr) {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleString('en-US', {
    month:'short', day:'numeric', hour:'numeric', minute:'2-digit', hour12:true
  });
}

async function loadOnlineStaff() {
  var tbody = document.getElementById('online-tbody');
  try {
    var staff = await API.onlineStatus.getAll();
    _allStaff = Array.isArray(staff) ? staff : [];
  } catch(e) {
    tbody.innerHTML = '<tr><td colspan="5" style="padding:32px;text-align:center;color:var(--danger);font-size:0.84rem;">Failed to load staff status.</td></tr>';
    return;
  }

  var onlineCount  = _allStaff.filter(function(w){ return w.is_online == 1; }).length;
  var offlineCount = _allStaff.length - onlineCount;

  var so = document.getElementById('stat-online');
  var sf = document.getElementById('stat-offline');
  var st = document.getElementById('stat-total');
  var lr = document.getElementById('last-refresh');
  var ps = document.getElementById('page-sub');

  if (so) so.textContent = onlineCount;
  if (sf) sf.textContent = offlineCount;
  if (st) st.textContent = _allStaff.length;
  if (lr) lr.textContent = 'Updated ' + new Date().toLocaleTimeString([], { hour:'2-digit', minute:'2-digit', second:'2-digit' });
  if (ps) ps.textContent = onlineCount + ' of ' + _allStaff.length + ' staff currently online.';

  renderTable();
}

function setOnlineFilter(filter, btn) {
  _onlineFilter = filter;
  document.querySelectorAll('[onclick^="setOnlineFilter"]').forEach(function(b) {
    b.style.background  = 'rgba(255,255,255,.5)';
    b.style.borderColor = 'var(--border)';
    b.style.color       = 'var(--text)';
  });
  if (btn) {
    btn.style.background  = 'rgba(78,96,64,0.12)';
    btn.style.borderColor = 'var(--olive)';
    btn.style.color       = 'var(--olive-dark)';
  }
  renderTable();
}

function renderTable() {
  var tbody = document.getElementById('online-tbody');
  var list  = _onlineFilter === 'all'
    ? _allStaff
    : _allStaff.filter(function(w){
        return _onlineFilter === 'online' ? w.is_online == 1 : w.is_online != 1;
      });

  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="5" style="padding:32px;text-align:center;">'
      + '<span class="material-symbols-outlined" style="font-size:2rem;color:var(--muted);display:block;margin-bottom:8px;">wifi_off</span>'
      + '<span style="color:var(--muted);font-size:0.84rem;">No ' + _onlineFilter + ' staff found.</span>'
      + '</td></tr>';
    return;
  }

  tbody.innerHTML = list.map(function(w) {
    var online  = w.is_online == 1;
    var initial = (w.Worker || '?').charAt(0).toUpperCase();
    var avatarHtml = w.Avatar
      ? '<img src="' + w.Avatar + '" style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;" onerror="this.style.display=\'none\'" />'
      : '<div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--olive),var(--olive-light));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.9rem;color:#fff;flex-shrink:0;">' + initial + '</div>';

    var statusDot = online
      ? '<span class="pulse" style="width:10px;height:10px;border-radius:50%;background:#27ae60;display:inline-block;flex-shrink:0;"></span>'
      : '<span style="width:10px;height:10px;border-radius:50%;background:#bdc3c7;display:inline-block;flex-shrink:0;"></span>';

    var statusLabel = online
      ? '<span style="color:#27ae60;font-weight:700;font-size:0.82rem;">● Online</span>'
      : '<span style="color:var(--muted);font-size:0.82rem;">○ Offline</span>';

    var roleClass = w.Worker_Role === 'Admin' ? 'badge--green' : 'badge--muted';

    return '<tr style="border-bottom:1px solid var(--border-light);transition:background 0.12s;" '
      + 'onmouseover="this.style.background=\'rgba(255,255,255,0.3)\'" onmouseout="this.style.background=\'\'">'
      + '<td style="padding:12px 20px;">'
      + '<div style="display:flex;align-items:center;gap:10px;">'
      + avatarHtml
      + '<div>'
      + '<div style="font-weight:700;font-size:0.88rem;display:flex;align-items:center;gap:7px;">' + (w.Worker || '') + ' ' + statusDot + '</div>'
      + '<div style="font-size:0.72rem;color:var(--muted);">ID #' + w.Worker_ID + '</div>'
      + '</div>'
      + '</div></td>'
      + '<td style="padding:12px 20px;"><span class="badge ' + roleClass + '" style="font-size:0.7rem;">' + (w.Worker_Role || '') + '</span></td>'
      + '<td style="padding:12px 20px;">' + statusLabel + '</td>'
      + '<td style="padding:12px 20px;font-size:0.84rem;color:var(--muted);">' + timeAgo(w.last_heartbeat) + '</td>'
      + '<td style="padding:12px 20px;font-size:0.82rem;color:var(--muted);">' + formatDateTime(w.last_heartbeat) + '</td>'
      + '</tr>';
  }).join('');
}

// Init
(async function() {
  var res  = await fetch('../dairy_farm_backend/api/auth.php?action=status', { credentials:'include' });
  var data = await res.json();
  if (data.success && data.data) {
    localStorage.setItem('csrf_token', data.data.csrf_token || '');
    if (data.data.user) localStorage.setItem('user', JSON.stringify(data.data.user));
  }
  loadOnlineStaff();
  // Auto-refresh every 30 seconds
  _refreshTimer = setInterval(loadOnlineStaff, 30000);
})();
</script>
</body>
</html>
