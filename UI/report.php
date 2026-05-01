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
  <title>Reports — Esperon Dairy Farm</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link rel="stylesheet" href="css/style.css" />
  <style>
    .report-card {
      background: rgba(255,255,255,0.35);
      border: 1px solid rgba(255,255,255,0.5);
      border-radius: var(--radius-xl);
      padding: 18px 22px;
      margin-bottom: 12px;
      transition: box-shadow .15s;
      border-left: 4px solid var(--border);
    }
    .report-card:hover { box-shadow: var(--shadow-md); }
    .report-card--pending      { border-left-color: var(--warning); }
    .report-card--reviewed     { border-left-color: var(--info); }
    .report-card--acknowledged { border-left-color: var(--olive); }
    .status-pill {
      display: inline-flex; align-items: center; gap: 4px;
      padding: 3px 10px; border-radius: 20px;
      font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
    }
    .status-pill--pending      { background: var(--warning-lt); color: #7a5a1e; }
    .status-pill--reviewed     { background: var(--info-lt);    color: #2d4f5e; }
    .status-pill--acknowledged { background: var(--success-lt); color: var(--olive-dark); }
    textarea.report-textarea {
      width: 100%; padding: 12px 16px; border: 1.5px solid var(--border-light);
      border-radius: 12px; font-size: 0.9rem; font-family: var(--font-sans);
      color: var(--text); background: rgba(255,255,255,.7); resize: vertical;
      outline: none; transition: border-color .15s, box-shadow .15s; box-sizing: border-box;
      min-height: 120px;
    }
    textarea.report-textarea:focus { border-color: var(--olive); box-shadow: 0 0 0 3px rgba(78,96,64,.12); }
    @keyframes slideIn { from{opacity:0;transform:translateY(-10px)} to{opacity:1;transform:none} }
  </style>
</head>
<body>
<nav class="nav" id="app-nav"></nav>
<main class="main">

  <div class="page-header">
    <div>
      <h1 class="page-title"><?= $isAdmin ? 'Staff Reports' : 'My Reports' ?></h1>
      <p class="page-subtitle" id="report-subtitle">
        <?= $isAdmin ? 'Review and acknowledge reports submitted by staff.' : 'Submit daily work reports to admin.' ?>
      </p>
    </div>
    <?php if (!$isAdmin): ?>
    <button class="btn btn--primary" onclick="openSubmitModal()">
      <span class="material-symbols-outlined" style="font-size:1rem;">add</span> Submit Report
    </button>
    <?php endif; ?>
  </div>

  <!-- Stats -->
  <div class="stats-grid" style="margin-bottom:var(--spacing-xl);grid-template-columns:repeat(4,1fr);">
    <div class="stat-card">
      <div class="stat-card__icon"><span class="material-symbols-outlined">description</span></div>
      <div class="stat-card__content"><div class="stat-card__val" id="stat-total">—</div><div class="stat-card__label">Total Reports</div></div>
    </div>
    <div class="stat-card stat-card--gold">
      <div class="stat-card__icon"><span class="material-symbols-outlined">pending_actions</span></div>
      <div class="stat-card__content"><div class="stat-card__val" id="stat-pending">—</div><div class="stat-card__label">Pending</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-card__icon"><span class="material-symbols-outlined">rate_review</span></div>
      <div class="stat-card__content"><div class="stat-card__val" id="stat-reviewed">—</div><div class="stat-card__label">Reviewed</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-card__icon"><span class="material-symbols-outlined">check_circle</span></div>
      <div class="stat-card__content"><div class="stat-card__val" id="stat-acknowledged">—</div><div class="stat-card__label">Acknowledged</div></div>
    </div>
  </div>

  <!-- Filter tabs -->
  <div class="card">
    <div class="card__header">
      <span style="font-family:var(--font-serif);font-size:1rem;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px;">
        <span class="material-symbols-outlined" style="color:var(--olive);font-size:1.2rem;">description</span>
        <?= $isAdmin ? 'All Staff Reports' : 'My Submitted Reports' ?>
      </span>
      <div style="display:flex;gap:6px;flex-wrap:wrap;">
        <button class="btn-xs btn-xs--ghost" onclick="setFilter('all',this)" style="background:rgba(78,96,64,0.12);border-color:var(--olive);color:var(--olive-dark);">All</button>
        <button class="btn-xs btn-xs--ghost" onclick="setFilter('pending',this)">Pending</button>
        <button class="btn-xs btn-xs--ghost" onclick="setFilter('reviewed',this)">Reviewed</button>
        <button class="btn-xs btn-xs--ghost" onclick="setFilter('acknowledged',this)">Acknowledged</button>
      </div>
    </div>
    <div id="reports-list" style="padding:16px 20px;min-height:100px;">
      <p style="color:var(--muted);font-size:0.84rem;">Loading reports…</p>
    </div>
  </div>

</main>

<!-- Submit Report Modal -->
<div id="submitModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(42,31,21,0.45);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
  <div style="background:#faf6f0;border-radius:20px;box-shadow:0 12px 48px rgba(0,0,0,0.18);width:100%;max-width:520px;margin:16px;font-family:'Lato',sans-serif;overflow:hidden;animation:slideIn .2s ease;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;background:linear-gradient(135deg,#4e6040,#6b8a5c);">
      <div style="display:flex;align-items:center;gap:8px;">
        <span class="material-symbols-outlined" style="color:#fff;font-size:1.2rem;">description</span>
        <span style="font-family:'Playfair Display',serif;font-size:1.05rem;font-weight:700;color:#fff;">Submit Report</span>
      </div>
      <button onclick="closeSubmitModal()" style="background:rgba(255,255,255,0.2);border:none;cursor:pointer;width:28px;height:28px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;">
        <span class="material-symbols-outlined" style="font-size:1rem;">close</span>
      </button>
    </div>
    <div style="padding:20px 22px;">
      <div style="margin-bottom:14px;">
        <label style="display:block;font-size:0.75rem;font-weight:700;color:#4a3f35;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;">Report Type <span style="color:#c0392b;">*</span></label>
        <select id="r-type" style="width:100%;padding:10px 14px;border:1.5px solid #e8dfd2;border-radius:10px;font-size:0.88rem;font-family:'Lato',sans-serif;color:#2a1f15;background:#fff;outline:none;box-sizing:border-box;">
          <option value="Daily Report">Daily Report</option>
          <option value="Incident Report">Incident Report</option>
          <option value="Livestock Report">Livestock Report</option>
          <option value="Maintenance Report">Maintenance Report</option>
          <option value="Health & Safety Report">Health &amp; Safety Report</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div style="margin-bottom:14px;">
        <label style="display:block;font-size:0.75rem;font-weight:700;color:#4a3f35;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;">Title <span style="color:#c0392b;">*</span></label>
        <input id="r-title" type="text" placeholder="e.g. Morning shift — April 30, 2026"
          style="width:100%;padding:10px 14px;border:1.5px solid #e8dfd2;border-radius:10px;font-size:0.88rem;font-family:'Lato',sans-serif;color:#2a1f15;background:#fff;outline:none;box-sizing:border-box;" />
        <div id="r-title-err" style="display:none;color:#c0392b;font-size:0.73rem;margin-top:3px;">Title is required.</div>
      </div>
      <div style="margin-bottom:16px;">
        <label style="display:block;font-size:0.75rem;font-weight:700;color:#4a3f35;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;">Report Content <span style="color:#c0392b;">*</span></label>
        <textarea id="r-content" class="report-textarea" placeholder="Describe what you did today, any issues encountered, livestock observations, etc."></textarea>
        <div id="r-content-err" style="display:none;color:#c0392b;font-size:0.73rem;margin-top:3px;">Content is required.</div>
        <div style="text-align:right;font-size:0.72rem;color:var(--muted);margin-top:3px;" id="r-char-count">0 / 5000</div>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:8px;">
        <button onclick="closeSubmitModal()" style="padding:9px 18px;border:1.5px solid #d4c9b8;border-radius:9px;background:#fff;color:#4a3f35;font-family:'Lato',sans-serif;font-size:0.85rem;font-weight:600;cursor:pointer;">Cancel</button>
        <button id="r-submit-btn" onclick="submitReport()" style="padding:9px 20px;border:none;border-radius:9px;background:linear-gradient(135deg,#4e6040,#6b8a5c);color:#fff;font-family:'Lato',sans-serif;font-size:0.85rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px;">
          <span class="material-symbols-outlined" style="font-size:0.95rem;">send</span> Submit
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Admin Review Modal -->
<div id="reviewModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(42,31,21,0.45);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
  <div style="background:#faf6f0;border-radius:20px;box-shadow:0 12px 48px rgba(0,0,0,0.18);width:100%;max-width:520px;margin:16px;font-family:'Lato',sans-serif;overflow:hidden;animation:slideIn .2s ease;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;background:linear-gradient(135deg,#4e6040,#6b8a5c);">
      <div style="display:flex;align-items:center;gap:8px;">
        <span class="material-symbols-outlined" style="color:#fff;font-size:1.2rem;">rate_review</span>
        <span style="font-family:'Playfair Display',serif;font-size:1.05rem;font-weight:700;color:#fff;">Review Report</span>
      </div>
      <button onclick="closeReviewModal()" style="background:rgba(255,255,255,0.2);border:none;cursor:pointer;width:28px;height:28px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;">
        <span class="material-symbols-outlined" style="font-size:1rem;">close</span>
      </button>
    </div>
    <div style="padding:20px 22px;">
      <div id="review-report-content" style="background:rgba(255,255,255,0.5);border:1px solid var(--border-light);border-radius:12px;padding:14px 16px;margin-bottom:16px;font-size:0.88rem;color:var(--text);line-height:1.6;max-height:200px;overflow-y:auto;"></div>
      <div style="margin-bottom:14px;">
        <label style="display:block;font-size:0.75rem;font-weight:700;color:#4a3f35;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;">Admin Note <span style="color:#8a7f72;font-weight:400;">(optional)</span></label>
        <textarea id="review-note" rows="3" placeholder="Add a note or feedback for the staff member…"
          style="width:100%;padding:10px 14px;border:1.5px solid #e8dfd2;border-radius:10px;font-size:0.88rem;font-family:'Lato',sans-serif;color:#2a1f15;background:#fff;outline:none;resize:vertical;box-sizing:border-box;"></textarea>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:8px;">
        <button onclick="closeReviewModal()" style="padding:9px 18px;border:1.5px solid #d4c9b8;border-radius:9px;background:#fff;color:#4a3f35;font-family:'Lato',sans-serif;font-size:0.85rem;font-weight:600;cursor:pointer;">Cancel</button>
        <button onclick="updateReportStatus('reviewed')" style="padding:9px 16px;border:none;border-radius:9px;background:var(--info-lt);color:#2d4f5e;border:1px solid rgba(91,122,138,.3);font-family:'Lato',sans-serif;font-size:0.85rem;font-weight:700;cursor:pointer;">Mark Reviewed</button>
        <button onclick="updateReportStatus('acknowledged')" style="padding:9px 16px;border:none;border-radius:9px;background:linear-gradient(135deg,#4e6040,#6b8a5c);color:#fff;font-family:'Lato',sans-serif;font-size:0.85rem;font-weight:700;cursor:pointer;">Acknowledge</button>
      </div>
    </div>
  </div>
</div>

<script src="js/api.js"></script>
<script src="js/ui.js"></script>
<script src="js/nav.js"></script>
<script>
var _reports    = [];
var _filter     = 'all';
var _reviewId   = null;
var IS_ADMIN    = <?= $isAdmin ? 'true' : 'false' ?>;

var STATUS_PILL = {
  pending:      '<span class="status-pill status-pill--pending"><span class="material-symbols-outlined" style="font-size:0.75rem;">schedule</span>Pending</span>',
  reviewed:     '<span class="status-pill status-pill--reviewed"><span class="material-symbols-outlined" style="font-size:0.75rem;">rate_review</span>Reviewed</span>',
  acknowledged: '<span class="status-pill status-pill--acknowledged"><span class="material-symbols-outlined" style="font-size:0.75rem;">check_circle</span>Acknowledged</span>',
};

var TYPE_ICON = {
  'Daily Report':           'today',
  'Activity Log':           'history',
  'Incident Report':        'warning',
  'Livestock Report':       'pets',
  'Maintenance Report':     'build',
  'Health & Safety Report': 'health_and_safety',
  'Other':                  'description',
};

async function loadReports() {
  var container = document.getElementById('reports-list');
  container.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;">Loading…</p>';
  try {
    _reports = await API.reports.getAll();
    if (!Array.isArray(_reports)) _reports = [];
  } catch(e) {
    container.innerHTML = '<p style="color:var(--danger);font-size:0.84rem;">Failed to load reports.</p>';
    return;
  }
  updateStats();
  renderReports();
}

function updateStats() {
  var pending      = _reports.filter(function(r){ return r.status==='pending'; }).length;
  var reviewed     = _reports.filter(function(r){ return r.status==='reviewed'; }).length;
  var acknowledged = _reports.filter(function(r){ return r.status==='acknowledged'; }).length;
  var set = function(id,v){ var el=document.getElementById(id); if(el) el.textContent=v; };
  set('stat-total',        _reports.length);
  set('stat-pending',      pending);
  set('stat-reviewed',     reviewed);
  set('stat-acknowledged', acknowledged);
  var sub = document.getElementById('report-subtitle');
  if (sub && IS_ADMIN) sub.textContent = _reports.length + ' total reports · ' + pending + ' awaiting review.';
}

function setFilter(f, btn) {
  _filter = f;
  document.querySelectorAll('[onclick^="setFilter"]').forEach(function(b){
    b.style.background='rgba(255,255,255,.5)'; b.style.borderColor='var(--border)'; b.style.color='var(--text)';
  });
  if (btn) { btn.style.background='rgba(78,96,64,0.12)'; btn.style.borderColor='var(--olive)'; btn.style.color='var(--olive-dark)'; }
  renderReports();
}

function renderReports() {
  var container = document.getElementById('reports-list');
  var list = _filter === 'all' ? _reports : _reports.filter(function(r){ return r.status === _filter; });

  if (!list.length) {
    container.innerHTML = '<div style="text-align:center;padding:28px 0;">'
      + '<span class="material-symbols-outlined" style="font-size:2.2rem;color:var(--muted);display:block;margin-bottom:8px;">description</span>'
      + '<p style="color:var(--muted);font-size:0.84rem;">No ' + (_filter==='all'?'':_filter+' ') + 'reports found.</p></div>';
    return;
  }

  container.innerHTML = list.map(function(r) {
    var icon    = TYPE_ICON[r.report_type] || 'description';
    var date    = new Date(r.created_at).toLocaleString('en-US', {month:'short',day:'numeric',year:'numeric',hour:'numeric',minute:'2-digit',hour12:true});
    var preview = r.content.length > 120 ? r.content.slice(0,120) + '…' : r.content;

    var adminActions = IS_ADMIN
      ? '<div style="display:flex;gap:6px;margin-top:10px;">'
        + '<button onclick="openReviewModal(' + r.report_id + ')" style="background:rgba(78,96,64,0.1);border:1.5px solid rgba(78,96,64,0.2);border-radius:7px;padding:5px 12px;cursor:pointer;font-size:0.78rem;font-weight:600;color:var(--olive-dark);font-family:var(--font-sans);display:inline-flex;align-items:center;gap:4px;">'
        + '<span class="material-symbols-outlined" style="font-size:0.9rem;">rate_review</span> Review</button>'
        + '<button onclick="deleteReport(' + r.report_id + ')" style="background:var(--danger-lt);border:1px solid rgba(192,57,43,.2);border-radius:7px;padding:5px 12px;cursor:pointer;font-size:0.78rem;font-weight:600;color:var(--danger);font-family:var(--font-sans);display:inline-flex;align-items:center;gap:4px;">'
        + '<span class="material-symbols-outlined" style="font-size:0.9rem;">delete</span> Delete</button>'
        + '</div>'
      : '';

    var adminNote = r.admin_note
      ? '<div style="margin-top:8px;padding:8px 12px;background:rgba(78,96,64,0.07);border-radius:8px;border-left:3px solid var(--olive);">'
        + '<div style="font-size:0.68rem;font-weight:700;color:var(--olive-dark);text-transform:uppercase;margin-bottom:2px;">Admin Note</div>'
        + '<div style="font-size:0.82rem;color:var(--text);">' + r.admin_note + '</div>'
        + '</div>'
      : '';

    return '<div class="report-card report-card--' + r.status + '">'
      + '<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">'
      + '<div style="display:flex;align-items:flex-start;gap:12px;flex:1;min-width:0;">'
      + '<div style="width:40px;height:40px;min-width:40px;border-radius:10px;background:rgba(78,96,64,0.1);display:flex;align-items:center;justify-content:center;">'
      + '<span class="material-symbols-outlined" style="font-size:1.3rem;color:var(--olive);">' + icon + '</span>'
      + '</div>'
      + '<div style="flex:1;min-width:0;">'
      + '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:3px;">'
      + '<span style="font-family:var(--font-serif);font-size:0.95rem;font-weight:700;color:var(--text);">' + r.title + '</span>'
      + (STATUS_PILL[r.status] || '')
      + '</div>'
      + '<div style="font-size:0.72rem;color:var(--muted);margin-bottom:6px;">'
      + (IS_ADMIN ? '<strong>' + r.worker_name + '</strong> · ' : '')
      + r.report_type + ' · ' + date
      + '</div>'
      + '<div style="font-size:0.84rem;color:var(--text-light);line-height:1.5;">' + preview + '</div>'
      + adminNote
      + adminActions
      + '</div>'
      + '</div>'
      + '</div>'
      + '</div>';
  }).join('');
}

// ── Submit Modal ──────────────────────────────────────────
function openSubmitModal() {
  document.getElementById('r-title').value   = '';
  document.getElementById('r-content').value = '';
  document.getElementById('r-type').value    = 'Daily Report';
  document.getElementById('r-title-err').style.display   = 'none';
  document.getElementById('r-content-err').style.display = 'none';
  document.getElementById('r-char-count').textContent    = '0 / 5000';
  document.getElementById('submitModal').style.display   = 'flex';
  setTimeout(function(){ document.getElementById('r-title').focus(); }, 50);
}
function closeSubmitModal() { document.getElementById('submitModal').style.display = 'none'; }

document.addEventListener('DOMContentLoaded', function() {
  var content = document.getElementById('r-content');
  if (content) {
    content.addEventListener('input', function() {
      document.getElementById('r-char-count').textContent = this.value.length + ' / 5000';
    });
  }
  document.getElementById('submitModal').addEventListener('click', function(e){ if(e.target===this) closeSubmitModal(); });
  document.getElementById('reviewModal').addEventListener('click', function(e){ if(e.target===this) closeReviewModal(); });
  document.addEventListener('keydown', function(e){
    if (e.key==='Escape') { closeSubmitModal(); closeReviewModal(); }
  });
});

async function submitReport() {
  var title   = document.getElementById('r-title').value.trim();
  var content = document.getElementById('r-content').value.trim();
  var type    = document.getElementById('r-type').value;
  var valid   = true;
  if (!title)   { document.getElementById('r-title-err').style.display='block';   valid=false; } else document.getElementById('r-title-err').style.display='none';
  if (!content) { document.getElementById('r-content-err').style.display='block'; valid=false; } else document.getElementById('r-content-err').style.display='none';
  if (!valid) return;

  var btn = document.getElementById('r-submit-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:0.95rem;animation:spin .7s linear infinite;">progress_activity</span> Submitting…';

  try {
    await API.reports.submit({ title: title, content: content, report_type: type });
    closeSubmitModal();
    UI.toast('Report submitted successfully!', 'success');
    loadReports();
  } catch(e) {
    UI.toast('Failed to submit: ' + (e.message || 'Unknown error'), 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:0.95rem;">send</span> Submit';
  }
}

// ── Review Modal (Admin) ──────────────────────────────────
function openReviewModal(id) {
  _reviewId = id;
  var report = _reports.find(function(r){ return r.report_id == id; });
  if (!report) return;
  var content = document.getElementById('review-report-content');
  content.innerHTML = '<div style="font-size:0.68rem;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:6px;">' + report.report_type + ' · ' + report.worker_name + '</div>'
    + '<div style="font-family:var(--font-serif);font-size:1rem;font-weight:700;margin-bottom:8px;">' + report.title + '</div>'
    + '<div style="font-size:0.88rem;line-height:1.6;white-space:pre-wrap;">' + report.content + '</div>';
  document.getElementById('review-note').value = report.admin_note || '';
  document.getElementById('reviewModal').style.display = 'flex';
}
function closeReviewModal() { document.getElementById('reviewModal').style.display = 'none'; _reviewId = null; }

async function updateReportStatus(status) {
  if (!_reviewId) return;
  var note = document.getElementById('review-note').value.trim();
  try {
    await API.reports.update(_reviewId, { status: status, admin_note: note || null });
    closeReviewModal();
    UI.toast('Report ' + status + '!', 'success');
    loadReports();
  } catch(e) {
    UI.toast('Failed: ' + (e.message || 'error'), 'error');
  }
}

async function deleteReport(id) {
  UI.confirm('Delete this report? This cannot be undone.').then(async function(ok) {
    if (!ok) return;
    try {
      await API.reports.delete(id);
      UI.toast('Report deleted.', 'success');
      loadReports();
    } catch(e) { UI.toast('Failed: ' + (e.message||'error'), 'error'); }
  });
}

// ── Init ──────────────────────────────────────────────────
var style = document.createElement('style');
style.textContent = '@keyframes spin{to{transform:rotate(360deg)}}';
document.head.appendChild(style);

(async function() {
  var res  = await fetch('../dairy_farm_backend/api/auth.php?action=status', { credentials:'include' });
  var data = await res.json();
  if (data.success && data.data) {
    localStorage.setItem('csrf_token', data.data.csrf_token || '');
    if (data.data.user) localStorage.setItem('user', JSON.stringify(data.data.user));
  }
  loadReports();
})();
</script>
</body>
</html>