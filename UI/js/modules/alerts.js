// ============================================================
// js/modules/alerts.js  —  Alerts system
// ============================================================

// ── ALERTS ────────────────────────────────────────────────
var ALERTS_KEY        = 'admin_alerts_custom';
var DISMISSED_KEY     = 'admin_alerts_dismissed';
var alertItems        = [];   // system-generated (rebuilt each load)
var customAlerts      = [];   // user-created, persisted
var dismissedAlerts   = [];   // ids of dismissed system alerts

// Load persisted data
function loadAlertStorage() {
  try { customAlerts    = JSON.parse(localStorage.getItem(ALERTS_KEY)    || '[]'); } catch(e) { customAlerts    = []; }
  try { dismissedAlerts = JSON.parse(localStorage.getItem(DISMISSED_KEY) || '[]'); } catch(e) { dismissedAlerts = []; }
}

function saveAlertStorage() {
  localStorage.setItem(ALERTS_KEY,    JSON.stringify(customAlerts));
  localStorage.setItem(DISMISSED_KEY, JSON.stringify(dismissedAlerts));
}

// Called by data-loading functions to queue a system alert
function addAlert(msg, type) {
  // Generate a stable id from the message so dismissal persists across reloads
  var id = 'sys_' + msg.replace(/\W+/g, '_').toLowerCase().slice(0, 40);
  alertItems.push({ id: id, msg: msg, type: type || 'warning', system: true });
}

function dismissAlert(id, isCustom) {
  if (isCustom) {
    customAlerts = customAlerts.filter(function(a) { return a.id !== id; });
  } else {
    if (dismissedAlerts.indexOf(id) === -1) dismissedAlerts.push(id);
  }
  saveAlertStorage();
  renderAlerts();
}

function addCustomAlert(msg, type) {
  var id = 'custom_' + Date.now();
  customAlerts.unshift({ id: id, msg: msg, type: type || 'warning', custom: true });
  if (customAlerts.length > 20) customAlerts.pop();
  saveAlertStorage();
  renderAlerts();
}

function renderAlerts() {
  var container = document.getElementById('alerts-list');
  var badge     = document.getElementById('alerts-badge');
  var statEl    = document.getElementById('stat-alerts');
  if (!container) return;

  // Filter out dismissed system alerts
  var visible = alertItems.filter(function(a) {
    return dismissedAlerts.indexOf(a.id) === -1;
  }).concat(customAlerts);

  var count = visible.length;
  if (statEl) statEl.textContent = count;

  if (!count) {
    container.innerHTML = '<div class="alert-row alert-row--success"><span class="material-symbols-outlined" style="font-size:1rem;flex-shrink:0;">check_circle</span><span>No active alerts. All systems normal.</span></div>';
    if (badge) badge.style.display = 'none';
    var dot = document.getElementById('notif-dot');
    if (dot) dot.style.display = 'none';
    return;
  }

  if (badge) { badge.textContent = count; badge.style.display = 'inline-block'; }
  var dot = document.getElementById('notif-dot');
  if (dot) dot.style.display = 'block';

  var icons = { danger: 'warning', warning: 'info', info: 'info', success: 'check_circle' };
  container.innerHTML = visible.map(function(a) {
    var isCustom = !!a.custom;
    return '<div class="alert-row alert-row--' + a.type + '" style="justify-content:space-between;align-items:flex-start;">'
      + '<div style="display:flex;align-items:flex-start;gap:10px;flex:1;min-width:0;">'
      + '<span class="material-symbols-outlined" style="font-size:1rem;flex-shrink:0;">' + (icons[a.type] || 'info') + '</span>'
      + '<span style="flex:1;">' + a.msg + (isCustom ? ' <span style="font-size:0.65rem;opacity:0.6;font-weight:700;text-transform:uppercase;margin-left:4px;">custom</span>' : '') + '</span>'
      + '</div>'
      + '<button onclick="dismissAlert(\'' + a.id + '\',' + isCustom + ')" title="Dismiss" '
      + 'style="background:none;border:none;cursor:pointer;padding:0 0 0 8px;flex-shrink:0;opacity:0.5;line-height:1;" '
      + 'onmouseover="this.style.opacity=\'1\'" onmouseout="this.style.opacity=\'0.5\'">'
      + '<span class="material-symbols-outlined" style="font-size:0.95rem;">close</span>'
      + '</button>'
      + '</div>';
  }).join('');
}

// ── ADD ALERT MODAL ───────────────────────────────────────
function openAddAlertModal() {
  var existing = document.getElementById('addAlertModal');
  if (existing) { existing.remove(); return; }

  var el = document.createElement('div');
  el.id = 'addAlertModal';
  el.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(42,31,21,0.45);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:16px;';
  el.innerHTML = ''
    + '<div style="background:#faf6f0;border-radius:18px;box-shadow:0 12px 48px rgba(0,0,0,0.18);width:100%;max-width:420px;font-family:\'Lato\',sans-serif;overflow:hidden;">'
    + '<div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px 12px;background:linear-gradient(135deg,#c0392b,#e74c3c);">'
    + '<div style="display:flex;align-items:center;gap:8px;">'
    + '<span class="material-symbols-outlined" style="color:#fff;font-size:1.1rem;">add_alert</span>'
    + '<span style="font-family:\'Playfair Display\',serif;font-size:1rem;font-weight:700;color:#fff;">Add Custom Alert</span>'
    + '</div>'
    + '<button onclick="document.getElementById(\'addAlertModal\').remove()" style="background:rgba(255,255,255,0.2);border:none;cursor:pointer;width:26px;height:26px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;">'
    + '<span class="material-symbols-outlined" style="font-size:0.95rem;">close</span></button>'
    + '</div>'
    + '<div style="padding:18px 20px;">'
    + '<div style="margin-bottom:12px;">'
    + '<label style="display:block;font-size:0.75rem;font-weight:700;color:#4a3f35;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:5px;">Alert Message <span style="color:#c0392b;">*</span></label>'
    + '<textarea id="alert-msg-input" rows="3" placeholder="e.g. Vet arriving at 2 PM — prepare Cow #7" style="width:100%;padding:9px 13px;border:1.5px solid #e8dfd2;border-radius:9px;font-size:0.88rem;font-family:\'Lato\',sans-serif;color:#2a1f15;background:#fff;outline:none;resize:vertical;box-sizing:border-box;"></textarea>'
    + '<div id="alert-msg-err" style="display:none;color:#c0392b;font-size:0.73rem;margin-top:3px;">Message is required.</div>'
    + '</div>'
    + '<div style="margin-bottom:16px;">'
    + '<label style="display:block;font-size:0.75rem;font-weight:700;color:#4a3f35;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:5px;">Severity</label>'
    + '<div style="display:flex;gap:8px;">'
    + '<label style="display:flex;align-items:center;gap:5px;cursor:pointer;font-size:0.84rem;"><input type="radio" name="alert-type" value="danger"  style="accent-color:#c0392b;"> <span style="color:#c0392b;font-weight:600;">Danger</span></label>'
    + '<label style="display:flex;align-items:center;gap:5px;cursor:pointer;font-size:0.84rem;"><input type="radio" name="alert-type" value="warning" checked style="accent-color:#f39c12;"> <span style="color:#7a5a1e;font-weight:600;">Warning</span></label>'
    + '<label style="display:flex;align-items:center;gap:5px;cursor:pointer;font-size:0.84rem;"><input type="radio" name="alert-type" value="info"    style="accent-color:#2980b9;"> <span style="color:#2d4f5e;font-weight:600;">Info</span></label>'
    + '</div>'
    + '</div>'
    + '<div style="display:flex;justify-content:flex-end;gap:8px;">'
    + '<button onclick="document.getElementById(\'addAlertModal\').remove()" style="padding:8px 16px;border:1.5px solid #d4c9b8;border-radius:9px;background:#fff;color:#4a3f35;font-family:\'Lato\',sans-serif;font-size:0.83rem;font-weight:600;cursor:pointer;">Cancel</button>'
    + '<button onclick="submitCustomAlert()" style="padding:8px 18px;border:none;border-radius:9px;background:linear-gradient(135deg,#c0392b,#e74c3c);color:#fff;font-family:\'Lato\',sans-serif;font-size:0.83rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px;">'
    + '<span class="material-symbols-outlined" style="font-size:0.9rem;">add_alert</span> Post Alert</button>'
    + '</div>'
    + '</div></div>';

  document.body.appendChild(el);
  el.addEventListener('click', function(e) { if (e.target === el) el.remove(); });
  document.addEventListener('keydown', function onEsc(e) {
    if (e.key === 'Escape') { el.remove(); document.removeEventListener('keydown', onEsc); }
  });
  setTimeout(function() { var t = document.getElementById('alert-msg-input'); if (t) t.focus(); }, 50);
}

function submitCustomAlert() {
  var msgEl = document.getElementById('alert-msg-input');
  var errEl = document.getElementById('alert-msg-err');
  var msg   = msgEl ? msgEl.value.trim() : '';
  if (!msg) { if (errEl) errEl.style.display = 'block'; return; }
  if (errEl) errEl.style.display = 'none';

  var typeEl = document.querySelector('input[name="alert-type"]:checked');
  var type   = typeEl ? typeEl.value : 'warning';

  addCustomAlert(msg, type);
  document.getElementById('addAlertModal').remove();
  if (typeof UI !== 'undefined') UI.toast('Alert posted!', 'success');
}

// ── NOTIFICATION BELL PANEL ───────────────────────────────
(function() {
  var panelEl = null;
  var isOpen  = false;

  var icons = { danger: 'warning', warning: 'info', info: 'info', success: 'check_circle' };
  var typeColors = {
    danger:  { bg: 'var(--danger-lt)',  border: 'var(--danger)',  text: '#7a1f2e' },
    warning: { bg: 'var(--warning-lt)', border: 'var(--warning)', text: '#7a5a1e' },
    info:    { bg: 'var(--info-lt)',    border: 'var(--info)',    text: '#2d4f5e' },
    success: { bg: 'var(--success-lt)', border: 'var(--olive)',   text: 'var(--olive-dark)' },
  };

  function getVisible() {
    var sys = alertItems.filter(function(a) {
      return dismissedAlerts.indexOf(a.id) === -1;
    });
    return sys.concat(customAlerts);
  }

  function buildPanel() {
    var visible = getVisible();
    var el = document.createElement('div');
    el.id = 'notif-panel';
    el.style.cssText = 'position:absolute;top:calc(100% + 10px);right:0;width:340px;'
      + 'background:#faf6f0;border:1.5px solid var(--border-light);border-radius:16px;'
      + 'box-shadow:0 12px 40px rgba(0,0,0,0.16);z-index:99999;overflow:hidden;'
      + 'font-family:var(--font-sans);animation:notifSlideIn 0.2s ease;';

    // Header
    var header = '<div style="display:flex;align-items:center;justify-content:space-between;'
      + 'padding:14px 16px 10px;background:linear-gradient(135deg,#4e6040,#6b8a5c);">'
      + '<div style="display:flex;align-items:center;gap:7px;">'
      + '<span class="material-symbols-outlined" style="color:#fff;font-size:1.1rem;">notifications</span>'
      + '<span style="font-family:\'Playfair Display\',serif;font-size:0.95rem;font-weight:700;color:#fff;">Notifications</span>'
      + '</div>'
      + '<div style="display:flex;align-items:center;gap:6px;">'
      + (visible.length ? '<button onclick="clearAllAlerts()" style="background:rgba(255,255,255,0.2);border:none;cursor:pointer;'
        + 'padding:3px 9px;border-radius:6px;color:#fff;font-size:0.72rem;font-weight:600;font-family:\'Lato\',sans-serif;">'
        + 'Clear all</button>' : '')
      + '<button id="notif-panel-close" style="background:rgba(255,255,255,0.2);border:none;cursor:pointer;'
        + 'width:24px;height:24px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;">'
        + '<span class="material-symbols-outlined" style="font-size:0.9rem;">close</span></button>'
      + '</div></div>';

    // Body
    var body = '<div style="max-height:340px;overflow-y:auto;">';
    if (!visible.length) {
      body += '<div style="padding:28px 16px;text-align:center;">'
        + '<span class="material-symbols-outlined" style="font-size:2.5rem;color:var(--olive);display:block;margin-bottom:8px;">check_circle</span>'
        + '<div style="font-size:0.88rem;font-weight:600;color:var(--text);">All clear!</div>'
        + '<div style="font-size:0.78rem;color:var(--muted);margin-top:3px;">No active notifications.</div>'
        + '</div>';
    } else {
      body += visible.map(function(a) {
        var c = typeColors[a.type] || typeColors.warning;
        var isCustom = !!a.custom;
        return '<div style="display:flex;align-items:flex-start;gap:10px;padding:11px 16px;'
          + 'border-bottom:1px solid var(--border-light);background:' + c.bg + ';'
          + 'border-left:3px solid ' + c.border + ';">'
          + '<span class="material-symbols-outlined" style="font-size:1rem;color:' + c.border + ';flex-shrink:0;margin-top:1px;">'
          + (icons[a.type] || 'info') + '</span>'
          + '<div style="flex:1;min-width:0;">'
          + '<div style="font-size:0.83rem;color:' + c.text + ';line-height:1.4;">' + a.msg + '</div>'
          + (isCustom ? '<div style="font-size:0.68rem;color:var(--muted);margin-top:2px;font-weight:700;text-transform:uppercase;">Custom</div>' : '')
          + '</div>'
          + '<button onclick="dismissAlert(\'' + a.id + '\',' + isCustom + ');refreshNotifPanel();" title="Dismiss" '
          + 'style="background:none;border:none;cursor:pointer;padding:0;flex-shrink:0;opacity:0.45;line-height:1;" '
          + 'onmouseover="this.style.opacity=\'1\'" onmouseout="this.style.opacity=\'0.45\'">'
          + '<span class="material-symbols-outlined" style="font-size:0.9rem;color:' + c.text + ';">close</span>'
          + '</button>'
          + '</div>';
      }).join('');
    }
    body += '</div>';

    // Footer
    var footer = '<div style="padding:9px 16px;border-top:1px solid var(--border-light);'
      + 'background:rgba(255,255,255,0.5);display:flex;justify-content:space-between;align-items:center;">'
      + '<span style="font-size:0.72rem;color:var(--muted);">'
      + visible.length + ' active notification' + (visible.length !== 1 ? 's' : '') + '</span>'
      + '<button onclick="openAddAlertModal()" style="background:none;border:none;cursor:pointer;'
        + 'font-size:0.72rem;color:var(--olive);font-weight:700;font-family:\'Lato\',sans-serif;'
        + 'display:flex;align-items:center;gap:3px;">'
        + '<span class="material-symbols-outlined" style="font-size:0.85rem;">add_alert</span> Add alert</button>'
      + '</div>';

    el.innerHTML = header + body + footer;
    return el;
  }

  function openPanel() {
    closePanel();
    var btn = document.getElementById('notif-btn');
    if (!btn) return;
    var wrap = btn.parentElement;
    wrap.style.position = 'relative';
    panelEl = buildPanel();
    wrap.appendChild(panelEl);
    isOpen = true;

    // Close button
    var closeBtn = document.getElementById('notif-panel-close');
    if (closeBtn) closeBtn.onclick = closePanel;

    // Close on outside click
    setTimeout(function() {
      document.addEventListener('click', outsideClick);
    }, 10);
  }

  function closePanel() {
    if (panelEl) { panelEl.remove(); panelEl = null; }
    isOpen = false;
    document.removeEventListener('click', outsideClick);
  }

  function outsideClick(e) {
    if (panelEl && !panelEl.contains(e.target)) {
      var btn = document.getElementById('notif-btn');
      if (btn && btn.contains(e.target)) return;
      closePanel();
    }
  }

  // Expose so dismissAlert can refresh the panel
  window.refreshNotifPanel = function() {
    if (isOpen) { closePanel(); openPanel(); }
    renderAlerts(); // keep the alerts card in sync
  };

  window.clearAllAlerts = function() {
    // Dismiss all system alerts
    alertItems.forEach(function(a) {
      if (dismissedAlerts.indexOf(a.id) === -1) dismissedAlerts.push(a.id);
    });
    // Clear all custom alerts
    customAlerts = [];
    saveAlertStorage();
    renderAlerts();
    if (isOpen) { closePanel(); openPanel(); }
    if (typeof UI !== 'undefined') UI.toast('All notifications cleared.', 'success');
  };

  // Add keyframe animation
  var styleEl = document.createElement('style');
  styleEl.textContent = '@keyframes notifSlideIn{from{opacity:0;transform:translateY(-10px) scale(0.97)}to{opacity:1;transform:translateY(0) scale(1)}}';
  document.head.appendChild(styleEl);

  document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('notif-btn');
    if (!btn) return;
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      if (isOpen) closePanel(); else openPanel();
    });
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && isOpen) closePanel();
    });
  });
})();
