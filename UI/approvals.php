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
  <title>Pending Approvals — Esperon Dairy Farm</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>
<nav class="nav" id="app-nav"></nav>
<main class="main">

  <div class="page-header">
    <div>
      <h1 class="page-title">Pending Approvals</h1>
      <p class="page-subtitle">Review and manage new user registration requests.</p>
    </div>
    <button class="btn btn--ghost" onclick="loadApprovals()" id="refresh-btn">
      <span class="material-symbols-outlined" style="font-size:1rem;">refresh</span> Refresh
    </button>
  </div>

  <!-- Stats row -->
  <div class="stats-grid" style="margin-bottom:var(--spacing-xl);grid-template-columns:repeat(3,1fr);">
    <div class="stat-card">
      <div class="stat-card__icon"><span class="material-symbols-outlined">pending_actions</span></div>
      <div class="stat-card__content">
        <div class="stat-card__val" id="stat-pending">—</div>
        <div class="stat-card__label">Pending</div>
      </div>
    </div>
    <div class="stat-card stat-card--gold">
      <div class="stat-card__icon"><span class="material-symbols-outlined">check_circle</span></div>
      <div class="stat-card__content">
        <div class="stat-card__val" id="stat-approved">—</div>
        <div class="stat-card__label">Approved</div>
      </div>
    </div>
    <div class="stat-card stat-card--danger">
      <div class="stat-card__icon"><span class="material-symbols-outlined">cancel</span></div>
      <div class="stat-card__content">
        <div class="stat-card__val" id="stat-rejected">—</div>
        <div class="stat-card__label">Rejected</div>
      </div>
    </div>
  </div>

  <!-- Filter tabs -->
  <div class="card" style="margin-bottom:var(--spacing-xl);">
    <div class="card__header">
      <span style="font-family:var(--font-serif);font-size:1rem;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px;">
        <span class="material-symbols-outlined" style="color:var(--olive);font-size:1.2rem;">how_to_reg</span>
        User Registrations
      </span>
      <div style="display:flex;gap:6px;">
        <button class="btn-xs btn-xs--ghost" onclick="setFilter('pending',this)" id="filter-pending"
          style="background:rgba(78,96,64,0.12);border-color:var(--olive);color:var(--olive-dark);">Pending</button>
        <button class="btn-xs btn-xs--ghost" onclick="setFilter('approved',this)">Approved</button>
        <button class="btn-xs btn-xs--ghost" onclick="setFilter('rejected',this)">Rejected</button>
        <button class="btn-xs btn-xs--ghost" onclick="setFilter('all',this)">All</button>
      </div>
    </div>

    <!-- Table -->
    <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="background:rgba(255,255,255,0.2);">
            <th style="padding:12px 20px;text-align:left;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted);border-bottom:1px solid var(--border-light);">User</th>
            <th style="padding:12px 20px;text-align:left;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted);border-bottom:1px solid var(--border-light);">Email</th>
            <th style="padding:12px 20px;text-align:left;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted);border-bottom:1px solid var(--border-light);">Role</th>
            <th style="padding:12px 20px;text-align:left;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted);border-bottom:1px solid var(--border-light);">Registered</th>
            <th style="padding:12px 20px;text-align:left;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted);border-bottom:1px solid var(--border-light);">Status</th>
            <th style="padding:12px 20px;text-align:right;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted);border-bottom:1px solid var(--border-light);">Actions</th>
          </tr>
        </thead>
        <tbody id="approvals-tbody">
          <tr><td colspan="6" style="padding:32px;text-align:center;color:var(--muted);font-size:0.84rem;">Loading…</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</main>

<!-- Reject Confirmation Modal -->
<div id="rejectModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(42,31,21,0.45);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
  <div style="background:#faf6f0;border-radius:18px;box-shadow:0 12px 48px rgba(0,0,0,0.18);width:100%;max-width:400px;margin:16px;font-family:'Lato',sans-serif;overflow:hidden;">
    <div style="padding:18px 22px 14px;background:linear-gradient(135deg,#c0392b,#e74c3c);display:flex;align-items:center;gap:8px;">
      <span class="material-symbols-outlined" style="color:#fff;font-size:1.2rem;">person_remove</span>
      <span style="font-family:'Playfair Display',serif;font-size:1rem;font-weight:700;color:#fff;">Reject Registration</span>
    </div>
    <div style="padding:20px 22px;">
      <p style="font-size:0.9rem;color:var(--text);margin-bottom:6px;">Are you sure you want to <strong>reject</strong> this registration?</p>
      <p id="reject-worker-name" style="font-size:0.84rem;color:var(--muted);margin-bottom:18px;"></p>
      <div style="display:flex;justify-content:flex-end;gap:8px;">
        <button onclick="closeRejectModal()" style="padding:9px 18px;border:1.5px solid #d4c9b8;border-radius:9px;background:#fff;color:#4a3f35;font-family:'Lato',sans-serif;font-size:0.85rem;font-weight:600;cursor:pointer;">Cancel</button>
        <button id="confirm-reject-btn" style="padding:9px 20px;border:none;border-radius:9px;background:linear-gradient(135deg,#c0392b,#e74c3c);color:#fff;font-family:'Lato',sans-serif;font-size:0.85rem;font-weight:700;cursor:pointer;">Reject</button>
      </div>
    </div>
  </div>
</div>

<script src="js/api.js"></script>
<script src="js/ui.js"></script>
<script src="js/nav.js"></script>
<script>
var _allUsers     = [];
var _currentFilter = 'pending';
var _rejectId      = null;

var STATUS_BADGE = {
  pending:  '<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:0.7rem;font-weight:700;background:var(--warning-lt);color:#7a5a1e;"><span style="width:6px;height:6px;border-radius:50%;background:#c8963e;display:inline-block;"></span>Pending</span>',
  approved: '<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:0.7rem;font-weight:700;background:var(--success-lt);color:var(--olive-dark);"><span style="width:6px;height:6px;border-radius:50%;background:var(--olive);display:inline-block;"></span>Approved</span>',
  rejected: '<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:0.7rem;font-weight:700;background:var(--danger-lt);color:var(--danger);"><span style="width:6px;height:6px;border-radius:50%;background:var(--danger);display:inline-block;"></span>Rejected</span>',
};

async function loadApprovals() {
  var tbody = document.getElementById('approvals-tbody');
  tbody.innerHTML = '<tr><td colspan="6" style="padding:32px;text-align:center;color:var(--muted);font-size:0.84rem;"><span class="material-symbols-outlined" style="font-size:1.5rem;display:block;margin-bottom:6px;animation:spin 1s linear infinite;">progress_activity</span>Loading…</td></tr>';

  try {
    // Fetch all users (pending + all statuses via a broader endpoint)
    var res = await fetch('../dairy_farm_backend/api/v1/approval.php?filter=all', { credentials: 'include' });
    var data = await res.json();
    _allUsers = Array.isArray(data.data) ? data.data : [];
  } catch(e) {
    tbody.innerHTML = '<tr><td colspan="6" style="padding:32px;text-align:center;color:var(--danger);font-size:0.84rem;">Failed to load users.</td></tr>';
    return;
  }

  updateStats();
  renderTable();
}

function updateStats() {
  var pending  = _allUsers.filter(function(u){ return u.approval_status === 'pending';  }).length;
  var approved = _allUsers.filter(function(u){ return u.approval_status === 'approved'; }).length;
  var rejected = _allUsers.filter(function(u){ return u.approval_status === 'rejected'; }).length;
  var sp = document.getElementById('stat-pending');
  var sa = document.getElementById('stat-approved');
  var sr = document.getElementById('stat-rejected');
  if (sp) sp.textContent = pending;
  if (sa) sa.textContent = approved;
  if (sr) sr.textContent = rejected;

  // Update nav badge
  var navBadge = document.getElementById('nav-approval-badge');
  if (navBadge) {
    navBadge.textContent = pending;
    navBadge.style.display = pending > 0 ? 'inline-block' : 'none';
  }
}

function setFilter(filter, btn) {
  _currentFilter = filter;
  document.querySelectorAll('[onclick^="setFilter"]').forEach(function(b) {
    b.style.background   = 'rgba(255,255,255,.5)';
    b.style.borderColor  = 'var(--border)';
    b.style.color        = 'var(--text)';
  });
  if (btn) {
    btn.style.background  = 'rgba(78,96,64,0.12)';
    btn.style.borderColor = 'var(--olive)';
    btn.style.color       = 'var(--olive-dark)';
  }
  renderTable();
}

function renderTable() {
  var tbody = document.getElementById('approvals-tbody');
  var list  = _currentFilter === 'all'
    ? _allUsers
    : _allUsers.filter(function(u){ return u.approval_status === _currentFilter; });

  if (!list.length) {
    var labels = { pending:'pending registrations', approved:'approved users', rejected:'rejected users', all:'users' };
    tbody.innerHTML = '<tr><td colspan="6" style="padding:32px;text-align:center;">'
      + '<span class="material-symbols-outlined" style="font-size:2rem;color:var(--olive);display:block;margin-bottom:8px;">check_circle</span>'
      + '<span style="color:var(--muted);font-size:0.84rem;">No ' + (labels[_currentFilter]||'records') + ' found.</span>'
      + '</td></tr>';
    return;
  }

  tbody.innerHTML = list.map(function(u) {
    var initial = (u.Worker || '?').charAt(0).toUpperCase();
    var date    = u.created_at
      ? new Date(u.created_at).toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' })
      : '—';
    var safeName = (u.Worker || '').replace(/\\/g,'\\\\').replace(/'/g,"\\'");
    var isPending = u.approval_status === 'pending';

    var actions = isPending
      ? '<div style="display:flex;gap:6px;justify-content:flex-end;">'
        + '<button onclick="approveUser(' + u.Worker_ID + ',this)" '
        + 'style="background:var(--olive);color:#fff;border:none;border-radius:6px;padding:6px 12px;cursor:pointer;font-size:0.78rem;font-weight:600;display:inline-flex;align-items:center;gap:4px;">'
        + '<span class="material-symbols-outlined" style="font-size:0.9rem;">check</span> Approve</button>'
        + '<button onclick="openRejectModal(' + u.Worker_ID + ',\'' + safeName + '\')" '
        + 'style="background:var(--danger-lt);color:var(--danger);border:1px solid rgba(192,57,43,.2);border-radius:6px;padding:6px 12px;cursor:pointer;font-size:0.78rem;font-weight:600;display:inline-flex;align-items:center;gap:4px;">'
        + '<span class="material-symbols-outlined" style="font-size:0.9rem;">close</span> Reject</button>'
        + '</div>'
      : '<span style="font-size:0.78rem;color:var(--muted);">—</span>';

    return '<tr style="border-bottom:1px solid var(--border-light);transition:background 0.12s;" '
      + 'onmouseover="this.style.background=\'rgba(255,255,255,0.3)\'" onmouseout="this.style.background=\'\'">'
      + '<td style="padding:12px 20px;">'
      + '<div style="display:flex;align-items:center;gap:10px;">'
      + '<div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--olive),var(--olive-light));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.88rem;color:#fff;flex-shrink:0;">' + initial + '</div>'
      + '<span style="font-weight:700;font-size:0.88rem;">' + (u.Worker || '') + '</span>'
      + '</div></td>'
      + '<td style="padding:12px 20px;font-size:0.84rem;color:var(--muted);">' + (u.Email || '—') + '</td>'
      + '<td style="padding:12px 20px;"><span class="badge ' + (u.Worker_Role === 'Admin' ? 'badge--green' : 'badge--muted') + '" style="font-size:0.7rem;">' + (u.Worker_Role || '') + '</span></td>'
      + '<td style="padding:12px 20px;font-size:0.82rem;color:var(--muted);">' + date + '</td>'
      + '<td style="padding:12px 20px;">' + (STATUS_BADGE[u.approval_status] || u.approval_status) + '</td>'
      + '<td style="padding:12px 20px;">' + actions + '</td>'
      + '</tr>';
  }).join('');
}

async function approveUser(id, btn) {
  if (btn) { btn.disabled = true; btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:0.9rem;">progress_activity</span>'; }
  try {
    await API.approval.approve(id);
    UI.toast('User approved!', 'success');
    loadApprovals();
  } catch(e) {
    UI.toast('Failed: ' + (e.message || 'Unknown error'), 'error');
    if (btn) { btn.disabled = false; btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:0.9rem;">check</span> Approve'; }
  }
}

function openRejectModal(id, name) {
  _rejectId = id;
  var modal  = document.getElementById('rejectModal');
  var nameEl = document.getElementById('reject-worker-name');
  if (nameEl) nameEl.textContent = 'Worker: ' + name;
  if (modal)  modal.style.display = 'flex';
  document.getElementById('confirm-reject-btn').onclick = confirmReject;
}

function closeRejectModal() {
  document.getElementById('rejectModal').style.display = 'none';
  _rejectId = null;
}

async function confirmReject() {
  if (!_rejectId) return;
  var id = _rejectId;
  closeRejectModal();
  try {
    await API.approval.reject(id);
    UI.toast('Registration rejected.', 'success');
    loadApprovals();
  } catch(e) {
    UI.toast('Failed: ' + (e.message || 'Unknown error'), 'error');
  }
}

// Modal close on backdrop / Escape
document.getElementById('rejectModal').addEventListener('click', function(e) {
  if (e.target === this) closeRejectModal();
});
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape' && _rejectId) closeRejectModal();
});

// Spin animation
var style = document.createElement('style');
style.textContent = '@keyframes spin{to{transform:rotate(360deg)}}';
document.head.appendChild(style);

// Init
(async function() {
  var res  = await fetch('../dairy_farm_backend/api/v1/auth.php?action=status', { credentials:'include' });
  var data = await res.json();
  if (data.success && data.data) {
    localStorage.setItem('csrf_token', data.data.csrf_token || '');
    if (data.data.user) localStorage.setItem('user', JSON.stringify(data.data.user));
  }
  loadApprovals();
})();
</script>
</body>
</html>
