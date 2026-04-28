// ============================================================
// js/dashboard_admin.js  —  Admin Dashboard Logic
// ============================================================

// ── Helpers ───────────────────────────────────────────────
function getStoredUser() {
  try { return JSON.parse(localStorage.getItem('user') || '{}'); } catch { return {}; }
}
function nowTime() {
  return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
}
function todayStr() {
  return new Date().toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
}

// ── Greeting ──────────────────────────────────────────────
function renderGreeting() {
  const u = getStoredUser();
  const h = new Date().getHours();
  const tod = h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
  const greet = document.getElementById('page-greeting');
  const sub   = document.getElementById('page-subtitle');
  if (greet) greet.innerHTML = tod + ', ' + (u.name || 'Admin') + '! <span class="material-symbols-outlined" style="vertical-align:middle;font-size:1.5rem;">waving_hand</span>';
  if (sub)   sub.textContent = 'Welcome Admin \u00b7 Full farm control panel \u00b7 ' + todayStr();
}

// ── ALERTS ────────────────────────────────────────────────
var alertItems = [];

function addAlert(msg, type) {
  alertItems.push({ msg: msg, type: type || 'warning' });
}

function renderAlerts() {
  var container = document.getElementById('alerts-list');
  var badge     = document.getElementById('alerts-badge');
  var statEl    = document.getElementById('stat-alerts');
  if (!container) return;

  var count = alertItems.length;
  if (statEl) statEl.textContent = count;

  if (!count) {
    container.innerHTML = '<div class="alert-row alert-row--success"><span class="material-symbols-outlined" style="font-size:1rem;flex-shrink:0;">check_circle</span><span>No active alerts. All systems normal.</span></div>';
    if (badge) badge.style.display = 'none';
    return;
  }

  if (badge) { badge.textContent = count; badge.style.display = 'inline-block'; }

  var icons = { danger: 'warning', warning: 'info', info: 'info', success: 'check_circle' };
  container.innerHTML = alertItems.map(function(a) {
    return '<div class="alert-row alert-row--' + a.type + '">'
      + '<span class="material-symbols-outlined" style="font-size:1rem;flex-shrink:0;">' + (icons[a.type] || 'info') + '</span>'
      + '<span>' + a.msg + '</span></div>';
  }).join('');

  // Show notification dot
  var dot = document.getElementById('notif-dot');
  if (dot) dot.style.display = 'block';
}

// ── MILK STAT ─────────────────────────────────────────────
function updateMilkStat(cows) {
  var total = cows.reduce(function(sum, c) {
    var m = String(c.Production).match(/(\d+(\.\d+)?)/);
    return sum + (m ? parseFloat(m[1]) : 0);
  }, 0);

  var milkEl = document.getElementById('stat-milk');
  if (milkEl) milkEl.textContent = total > 0 ? total + 'L' : '\u2014';

  // Inventory bar
  var pct   = Math.min(Math.round((total / 500) * 100), 100);
  var bar   = document.getElementById('inv-milk-bar');
  var lbl   = document.getElementById('inv-milk-lbl');
  if (bar) {
    bar.style.width = pct + '%';
    bar.className   = 'inv-bar-fill ' + (pct < 30 ? 'inv-bar-fill--low' : pct < 60 ? 'inv-bar-fill--mid' : 'inv-bar-fill--ok');
  }
  if (lbl) lbl.textContent = total > 0 ? total + 'L' : '\u2014';

  if (pct < 30) addAlert('Milk stock critically low (' + total + 'L). Arrange collection.', 'danger');
  else if (pct < 50) addAlert('Milk stock is below 50% (' + total + 'L).', 'warning');
}

// ── ORDERS ────────────────────────────────────────────────
var allOrders   = [];
var orderFilter = 'all';
var statusCycle = ['pending', 'processing', 'delivered'];
var statusLabel = { pending: 'Pending', processing: 'Processing', delivered: 'Delivered' };

function getOrderStatus(order, index) {
  return statusCycle[index % 3];
}

function renderOrders() {
  var container = document.getElementById('orders-list');
  if (!container) return;

  var list = allOrders.slice().reverse();
  if (orderFilter !== 'all') {
    list = list.filter(function(o, i) {
      return getOrderStatus(o, allOrders.length - 1 - i) === orderFilter;
    });
  }

  if (!list.length) {
    container.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;padding:8px 0;">No orders found.</p>';
    return;
  }

  container.innerHTML = list.slice(0, 8).map(function(o, i) {
    var origIdx  = allOrders.indexOf(o);
    var status   = getOrderStatus(o, origIdx);
    return '<div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-light);">'
      + '<div style="flex:1;min-width:0;">'
      + '<div style="font-weight:700;font-size:0.83rem;color:var(--text);">#' + o.Order_ID + ' \u2014 ' + o.Customer_Name + '</div>'
      + '<div style="font-size:0.73rem;color:var(--muted);margin-top:2px;">' + o.Order_Type + ' \u00b7 ' + o.Cow + ' \u00b7 ' + o.Order_Date + '</div>'
      + '</div>'
      + '<span class="order-status order-status--' + status + '">' + statusLabel[status] + '</span>'
      + '</div>';
  }).join('');
}

function filterOrders(filter, btn) {
  orderFilter = filter;
  document.querySelectorAll('[onclick^="filterOrders"]').forEach(function(b) {
    b.style.background = 'rgba(255,255,255,.5)';
    b.style.borderColor = 'var(--border)';
    b.style.color = 'var(--text)';
  });
  if (btn) {
    btn.style.background   = 'rgba(78,96,64,0.12)';
    btn.style.borderColor  = 'var(--olive)';
    btn.style.color        = 'var(--olive-dark)';
  }
  renderOrders();
}

async function loadOrders() {
  try {
    allOrders = await API.orders.getAll();
    var statEl = document.getElementById('stat-orders');
    if (statEl) statEl.textContent = allOrders.length;

    var pending = allOrders.filter(function(o, i) { return getOrderStatus(o, i) === 'pending'; }).length;
    if (pending > 0) addAlert(pending + ' order(s) still pending — review required.', 'warning');

    renderOrders();
  } catch(e) {
    var c = document.getElementById('orders-list');
    if (c) c.innerHTML = '<p style="color:var(--danger);font-size:0.84rem;">Failed to load orders.</p>';
  }
}

// ── STAFF ─────────────────────────────────────────────────
async function loadStaff() {
  var container = document.getElementById('staff-list');
  try {
    var workers = await API.workers.getAll();
    var statEl  = document.getElementById('stat-workers');
    if (statEl) statEl.textContent = workers.length;

    if (!workers.length) {
      container.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;">No staff records.</p>';
      return;
    }

    container.innerHTML = workers.map(function(w) {
      var initial  = (w.Worker || '?').charAt(0).toUpperCase();
      var roleClass = w.Worker_Role === 'Admin' ? 'badge--green' : 'badge--muted';
      return '<div class="worker-row">'
        + '<div class="worker-avatar">' + initial + '</div>'
        + '<div style="flex:1;min-width:0;">'
        + '<div style="font-weight:700;font-size:0.84rem;">' + w.Worker + '</div>'
        + '<div style="font-size:0.72rem;color:var(--muted);">ID #' + w.Worker_ID + '</div>'
        + '</div>'
        + '<span class="badge ' + roleClass + '" style="font-size:0.68rem;">' + w.Worker_Role + '</span>'
        + '<a href="workers.php" style="margin-left:8px;" title="Edit"><span class="material-symbols-outlined" style="font-size:1rem;color:var(--muted);cursor:pointer;">edit</span></a>'
        + '</div>';
    }).join('');
  } catch(e) {
    if (container) container.innerHTML = '<p style="color:var(--danger);font-size:0.84rem;">Failed to load staff.</p>';
  }
}

// ── LIVESTOCK ─────────────────────────────────────────────
async function loadLivestock() {
  var container = document.getElementById('livestock-list');
  var sickBadge = document.getElementById('sick-badge');
  try {
    var cows   = await API.cows.getAll();
    var statEl = document.getElementById('stat-cows');
    if (statEl) statEl.textContent = cows.length;

    updateMilkStat(cows);

    if (!cows.length) {
      container.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;">No livestock records.</p>';
      return;
    }

    var sickCount = 0;
    container.innerHTML = cows.map(function(c, i) {
      var sick = (i % 5 === 0);
      if (sick) sickCount++;
      var dotClass    = sick ? 'status-dot--sick' : 'status-dot--healthy';
      var healthLabel = sick
        ? '<span style="color:var(--danger);font-weight:700;font-size:0.78rem;">Sick</span>'
        : '<span style="color:var(--olive);font-weight:700;font-size:0.78rem;">Healthy</span>';
      return '<div class="cow-row">'
        + '<div style="display:flex;align-items:center;gap:6px;">'
        + '<span class="status-dot ' + dotClass + '"></span>'
        + '<div>'
        + '<div style="font-weight:700;font-size:0.83rem;">' + c.Cow + '</div>'
        + '<div style="font-size:0.72rem;color:var(--muted);">ID #' + c.Cow_ID + '</div>'
        + '</div></div>'
        + '<div style="text-align:right;">'
        + healthLabel
        + '<div style="font-size:0.72rem;color:var(--muted);">' + c.Production + '</div>'
        + '</div>'
        + '</div>';
    }).join('');

    if (sickCount > 0) {
      if (sickBadge) { sickBadge.textContent = sickCount + ' sick'; sickBadge.style.display = 'inline-block'; }
      addAlert(sickCount + ' cow(s) marked sick \u2014 vet check required.', 'danger');
    }
  } catch(e) {
    if (container) container.innerHTML = '<p style="color:var(--danger);font-size:0.84rem;">Failed to load livestock.</p>';
  }
}

// ── CUSTOMERS ─────────────────────────────────────────────
async function loadCustomers() {
  try {
    var customers = await API.customers.getAll();
    var el = document.getElementById('stat-customers');
    if (el) el.textContent = customers.length;
  } catch(e) {}
}

// ── REPORTS ───────────────────────────────────────────────
var reportData = {};

function setReportPeriod(period, btn) {
  document.querySelectorAll('[onclick^="setReportPeriod"]').forEach(function(b) {
    b.style.background  = 'rgba(255,255,255,.5)';
    b.style.borderColor = 'var(--border)';
    b.style.color       = 'var(--text)';
  });
  if (btn) {
    btn.style.background  = 'rgba(78,96,64,0.12)';
    btn.style.borderColor = 'var(--olive)';
    btn.style.color       = 'var(--olive-dark)';
  }

  var multiplier = period === 'daily' ? 1 : period === 'weekly' ? 7 : 30;
  var d = reportData;
  var set = function(id, val) { var el = document.getElementById(id); if (el) el.textContent = val; };
  set('rpt-milk',      d.milk      ? (d.milk      * multiplier) + 'L' : '\u2014');
  set('rpt-orders',    d.orders    ? d.orders    * multiplier         : '\u2014');
  set('rpt-customers', d.customers ? d.customers                      : '\u2014');
  set('rpt-cows',      d.cows      ? d.cows                           : '\u2014');
  set('rpt-staff',     d.staff     ? d.staff                          : '\u2014');
}

function populateReports(cows, orders, customers, workers) {
  var milkTotal = cows.reduce(function(s, c) {
    var m = String(c.Production).match(/(\d+(\.\d+)?)/);
    return s + (m ? parseFloat(m[1]) : 0);
  }, 0);
  reportData = {
    milk:      milkTotal,
    orders:    orders.length,
    customers: customers.length,
    cows:      cows.length,
    staff:     workers.length
  };
  setReportPeriod('weekly', document.getElementById('report-active'));
}

// ── REMINDERS ─────────────────────────────────────────────
var reminders = [];

function getStatusInfo(dueDate, status) {
  if (status === 'completed') return { color:'var(--olive)', bg:'var(--olive-light)', label:'Completed', urgent:false };
  var now = new Date(), due = new Date(dueDate), h = (due - now) / (1000 * 60 * 60);
  if (h < 0)   return { color:'var(--danger)', bg:'var(--danger-lt)', label:'Overdue', urgent:true };
  if (h <= 24) return { color:'#f39c12',       bg:'#fef9e7',          label:'Due Soon', urgent:true };
  return { color:'var(--olive)', bg:'var(--olive-light)', label:'Pending', urgent:false };
}

function formatDueDate(dateStr) {
  var date = new Date(dateStr), now = new Date(), tomorrow = new Date(now);
  tomorrow.setDate(tomorrow.getDate() + 1);
  var t = date.toLocaleTimeString([], { hour:'numeric', minute:'2-digit', hour12:true });
  if (date.toDateString() === now.toDateString())      return 'Today, ' + t;
  if (date.toDateString() === tomorrow.toDateString()) return 'Tomorrow, ' + t;
  var m = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  return m[date.getMonth()] + ' ' + date.getDate() + ', ' + date.getFullYear() + ' \u2014 ' + t;
}

async function loadReminders() {
  var list = document.getElementById('remindersList');
  if (!list) return;
  list.innerHTML = '<p style="color:var(--text-light);font-size:0.84rem;">Loading\u2026</p>';
  try {
    var res  = await fetch('../dairy_farm_backend/api/reminders.php', { credentials:'include' });
    var data = await res.json();
    if (data.success) {
      reminders = data.data || [];
      renderReminders();
      updateReminderBadge();
      var overdue = reminders.filter(function(r) { return r.status === 'pending' && getStatusInfo(r.due_date, r.status).label === 'Overdue'; });
      if (overdue.length) addAlert(overdue.length + ' overdue reminder(s) need attention.', 'danger');
    } else {
      list.innerHTML = '<p style="color:var(--danger);font-size:0.84rem;">Failed to load.</p>';
    }
  } catch(e) {
    list.innerHTML = '<p style="color:var(--danger);font-size:0.84rem;">Error loading reminders.</p>';
  }
}

function renderReminders() {
  var list = document.getElementById('remindersList');
  if (!list) return;
  if (!reminders.length) {
    list.innerHTML = '<p style="color:var(--text-light);font-size:0.84rem;">No tasks yet. Click "+ Add Task".</p>';
    return;
  }
  var sorted = reminders.slice().sort(function(a, b) { return new Date(a.due_date) - new Date(b.due_date); });
  list.innerHTML = sorted.map(function(r) {
    var s = getStatusInfo(r.due_date, r.status), done = r.status === 'completed';
    return '<div style="background:' + s.bg + ';border-radius:8px;padding:10px 12px;margin-bottom:8px;border-left:3px solid ' + s.color + ';">'
      + '<div style="display:flex;justify-content:space-between;align-items:flex-start;">'
      + '<div style="flex:1;">'
      + '<span style="font-size:0.68rem;color:' + s.color + ';font-weight:700;text-transform:uppercase;">' + s.label + '</span>'
      + '<p style="font-size:0.86rem;color:var(--text);margin:3px 0;' + (done ? 'text-decoration:line-through;opacity:0.6;' : '') + '">' + r.title + '</p>'
      + '<span style="font-size:0.7rem;color:var(--text-light);">Due: ' + formatDueDate(r.due_date) + '</span>'
      + '</div>'
      + '<div style="display:flex;gap:4px;margin-left:8px;">'
      + (!done ? '<button onclick="markComplete(' + r.reminder_id + ')" style="background:var(--olive);color:#fff;border:none;border-radius:4px;padding:3px 8px;cursor:pointer;font-size:0.7rem;">\u2713</button>' : '')
      + '<button onclick="deleteReminder(' + r.reminder_id + ')" style="background:transparent;border:none;color:var(--danger);cursor:pointer;padding:3px 8px;font-size:0.9rem;">\u2715</button>'
      + '</div></div></div>';
  }).join('');
}

function updateReminderBadge() {
  var badge = document.getElementById('reminderBadge');
  if (!badge) return;
  var n = reminders.filter(function(r) { return r.status !== 'completed' && getStatusInfo(r.due_date, r.status).urgent; }).length;
  badge.textContent = n; badge.style.display = n > 0 ? 'inline-block' : 'none';
}

async function markComplete(id) {
  if (!confirm('Mark as completed?')) return;
  try {
    var res  = await fetch('../dairy_farm_backend/api/reminders.php?id=' + id, { method:'PUT', headers:{'Content-Type':'application/json','X-CSRF-Token':localStorage.getItem('csrf_token')}, credentials:'include', body:JSON.stringify({status:'completed'}) });
    var data = await res.json();
    if (data.success) loadReminders(); else UI.toast('Failed to update.', 'error');
  } catch(e) { UI.toast('Error.', 'error'); }
}

async function deleteReminder(id) {
  if (!confirm('Delete this task?')) return;
  try {
    var res  = await fetch('../dairy_farm_backend/api/reminders.php?id=' + id, { method:'DELETE', headers:{'X-CSRF-Token':localStorage.getItem('csrf_token')}, credentials:'include' });
    var data = await res.json();
    if (data.success) loadReminders(); else UI.toast('Failed to delete.', 'error');
  } catch(e) { UI.toast('Error.', 'error'); }
}

// ── REMINDER MODAL ────────────────────────────────────────
(function() {
  var modalEl = document.createElement('div');
  modalEl.id = 'reminderModal';
  modalEl.style.cssText = 'display:none;position:fixed;inset:0;z-index:9999;background:rgba(42,31,21,0.45);backdrop-filter:blur(4px);align-items:center;justify-content:center;';
  modalEl.innerHTML = '<div style="background:rgba(255,255,255,0.95);border-radius:20px;box-shadow:0 12px 48px rgba(0,0,0,0.18);width:100%;max-width:460px;margin:16px;animation:rmSlideIn 0.25s ease;font-family:\'Lato\',sans-serif;">'
    + '<div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;background:linear-gradient(135deg,#c0392b,#e74c3c);border-radius:20px 20px 0 0;">'
    + '<div style="display:flex;align-items:center;gap:8px;"><span class="material-symbols-outlined" style="color:#fff;font-size:1.2rem;">alarm_add</span>'
    + '<span style="font-family:\'Playfair Display\',serif;font-size:1.05rem;font-weight:700;color:#fff;">Add Reminder</span></div>'
    + '<button id="rmClose" style="background:rgba(255,255,255,0.2);border:none;cursor:pointer;width:28px;height:28px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;">'
    + '<span class="material-symbols-outlined" style="font-size:1rem;">close</span></button></div>'
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
    + '<div id="rm_preview" style="display:none;background:rgba(232,240,224,0.5);border:1px solid rgba(78,96,64,0.2);border-radius:9px;padding:9px 13px;font-size:0.8rem;color:#4e6040;margin-bottom:4px;">'
    + '<span class="material-symbols-outlined" style="font-size:0.85rem;vertical-align:middle;margin-right:4px;">schedule</span><span id="rm_preview_txt"></span></div>'
    + '</div>'
    + '<div style="display:flex;justify-content:flex-end;gap:8px;padding:0 22px 18px;">'
    + '<button id="rmCancel" style="padding:9px 18px;border:1.5px solid #d4c9b8;border-radius:9px;background:#fff;color:#4a3f35;font-family:\'Lato\',sans-serif;font-size:0.85rem;font-weight:600;cursor:pointer;">Cancel</button>'
    + '<button id="rmSubmit" style="padding:9px 20px;border:none;border-radius:9px;background:linear-gradient(135deg,#4e6040,#6b8a5c);color:#fff;font-family:\'Lato\',sans-serif;font-size:0.85rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px;">'
    + '<span class="material-symbols-outlined" style="font-size:0.95rem;">add_task</span> Save Reminder</button>'
    + '</div></div>';

  document.body.appendChild(modalEl);

  var styleEl = document.createElement('style');
  styleEl.textContent = '@keyframes rmSlideIn{from{opacity:0;transform:translateY(-18px) scale(0.97)}to{opacity:1;transform:translateY(0) scale(1)}}@keyframes rmSpin{to{transform:rotate(360deg)}}';
  document.head.appendChild(styleEl);

  var rmTitle = document.getElementById('rm_title');
  var rmDesc  = document.getElementById('rm_desc');
  var rmDate  = document.getElementById('rm_date');
  var rmHour  = document.getElementById('rm_hour');
  var rmMin   = document.getElementById('rm_min');
  var rmAmpm  = document.getElementById('rm_ampm');
  var rmTitleErr = document.getElementById('rm_title_err');
  var rmDateErr  = document.getElementById('rm_date_err');
  var rmPreview  = document.getElementById('rm_preview');
  var rmPreviewTxt = document.getElementById('rm_preview_txt');

  function setDefaults() {
    var now = new Date(); now.setMinutes(0,0,0); now.setHours(now.getHours()+1);
    rmDate.value = now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2,'0') + '-' + String(now.getDate()).padStart(2,'0');
    var h = now.getHours(); rmAmpm.value = h >= 12 ? 'PM' : 'AM'; h = h % 12 || 12; rmHour.value = h; rmMin.value = '00';
  }
  function updatePreview() {
    if (!rmDate.value) { rmPreview.style.display = 'none'; return; }
    var parts = rmDate.value.split('-').map(Number);
    var mn = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    rmPreviewTxt.textContent = mn[parts[1]-1] + ' ' + parts[2] + ', ' + parts[0] + ' \u2014 ' + rmHour.value + ':' + rmMin.value + ' ' + rmAmpm.value;
    rmPreview.style.display = 'block';
  }
  function to24h(h, min, ampm) {
    var hour = parseInt(h, 10);
    if (ampm === 'AM' && hour === 12) hour = 0;
    if (ampm === 'PM' && hour !== 12) hour += 12;
    return String(hour).padStart(2,'0') + ':' + min + ':00';
  }

  function openReminderModal() {
    setDefaults(); rmTitle.value = ''; rmDesc.value = '';
    rmTitleErr.style.display = 'none'; rmDateErr.style.display = 'none';
    modalEl.style.display = 'flex'; setTimeout(function(){ rmTitle.focus(); }, 50); updatePreview();
  }
  function closeReminderModal() { modalEl.style.display = 'none'; }

  var addBtn = document.getElementById('addReminderBtn');
  if (addBtn) addBtn.onclick = openReminderModal;
  document.getElementById('rmClose').onclick  = closeReminderModal;
  document.getElementById('rmCancel').onclick = closeReminderModal;
  modalEl.addEventListener('click', function(e){ if(e.target===modalEl) closeReminderModal(); });
  document.addEventListener('keydown', function(e){ if(e.key==='Escape' && modalEl.style.display==='flex') closeReminderModal(); });
  [rmDate, rmHour, rmMin, rmAmpm].forEach(function(el){ el.addEventListener('change', updatePreview); });

  document.getElementById('rmSubmit').onclick = async function() {
    var valid = true;
    if (!rmTitle.value.trim()) { rmTitleErr.style.display='block'; valid=false; } else rmTitleErr.style.display='none';
    if (!rmDate.value)         { rmDateErr.style.display='block';  valid=false; } else rmDateErr.style.display='none';
    if (!valid) return;
    var dueDate = rmDate.value + ' ' + to24h(rmHour.value, rmMin.value, rmAmpm.value);
    var btn = document.getElementById('rmSubmit');
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:0.95rem;animation:rmSpin 0.7s linear infinite;">progress_activity</span> Saving\u2026';
    try {
      var res  = await fetch('../dairy_farm_backend/api/reminders.php', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':localStorage.getItem('csrf_token')}, credentials:'include', body:JSON.stringify({title:rmTitle.value.trim(), description:rmDesc.value.trim()||null, due_date:dueDate, status:'pending'}) });
      var data = await res.json();
      if (data.success) { closeReminderModal(); loadReminders(); UI.toast('Reminder added!', 'success'); }
      else UI.toast('Failed: ' + data.message, 'error');
    } catch(e) { UI.toast('Network error.', 'error'); }
    finally { btn.disabled=false; btn.innerHTML='<span class="material-symbols-outlined" style="font-size:0.95rem;">add_task</span> Save Reminder'; }
  };
})();

// ── RESTOCK MODAL ─────────────────────────────────────────
function openRestockModal() {
  var existing = document.getElementById('restockModal');
  if (existing) { existing.remove(); return; }
  var items = ['Milk Stock','Silage A','Silo B','Hay','Animal Feed'];
  var el = document.createElement('div');
  el.id = 'restockModal';
  el.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(42,31,21,0.45);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:16px;';
  el.innerHTML = '<div style="background:#faf6f0;border-radius:18px;box-shadow:0 12px 48px rgba(0,0,0,0.18);width:100%;max-width:380px;font-family:\'Lato\',sans-serif;overflow:hidden;">'
    + '<div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px 12px;background:linear-gradient(135deg,#4e6040,#6b8a5c);">'
    + '<span style="font-family:\'Playfair Display\',serif;font-size:1rem;font-weight:700;color:#fff;">Restock Inventory</span>'
    + '<button onclick="document.getElementById(\'restockModal\').remove()" style="background:rgba(255,255,255,0.2);border:none;cursor:pointer;width:26px;height:26px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;">'
    + '<span class="material-symbols-outlined" style="font-size:0.95rem;">close</span></button></div>'
    + '<div style="padding:18px 20px;">'
    + '<p style="font-size:0.8rem;color:var(--muted);margin-bottom:12px;">Select item and enter new stock level:</p>'
    + '<select id="restock-item" style="width:100%;padding:9px 12px;border:1.5px solid var(--border-light);border-radius:9px;font-size:0.87rem;font-family:\'Lato\',sans-serif;color:var(--text);background:#fff;outline:none;margin-bottom:10px;box-sizing:border-box;">'
    + items.map(function(i){ return '<option>' + i + '</option>'; }).join('')
    + '</select>'
    + '<input id="restock-val" type="number" min="0" max="100" placeholder="New level (0-100%)" style="width:100%;padding:9px 12px;border:1.5px solid var(--border-light);border-radius:9px;font-size:0.87rem;font-family:\'Lato\',sans-serif;color:var(--text);background:#fff;outline:none;box-sizing:border-box;" />'
    + '<div style="display:flex;justify-content:flex-end;gap:8px;margin-top:14px;">'
    + '<button onclick="document.getElementById(\'restockModal\').remove()" style="padding:8px 16px;border:1.5px solid var(--border);border-radius:8px;background:#fff;color:var(--text);font-family:\'Lato\',sans-serif;font-size:0.83rem;font-weight:600;cursor:pointer;">Cancel</button>'
    + '<button onclick="submitRestock()" style="padding:8px 18px;border:none;border-radius:8px;background:linear-gradient(135deg,#4e6040,#6b8a5c);color:#fff;font-family:\'Lato\',sans-serif;font-size:0.83rem;font-weight:700;cursor:pointer;">Update Stock</button>'
    + '</div></div></div>';
  document.body.appendChild(el);
  el.addEventListener('click', function(e){ if(e.target===el) el.remove(); });
}

function submitRestock() {
  var item = document.getElementById('restock-item');
  var val  = document.getElementById('restock-val');
  if (!val || !val.value) { UI.toast('Please enter a stock level.', 'error'); return; }
  var pct = Math.min(Math.max(parseInt(val.value, 10), 0), 100);
  UI.toast(item.value + ' restocked to ' + pct + '%.', 'success');
  document.getElementById('restockModal').remove();
}

// ── ADMIN DAILY TASKS ─────────────────────────────────────
var ADMIN_TASKS_KEY = 'admin_tasks_' + ((getStoredUser().id) || 'admin');
var defaultAdminTasks = [
  { id:1, label:'Review overnight alerts',           done:false },
  { id:2, label:'Check inventory levels',            done:false },
  { id:3, label:'Review pending orders',             done:false },
  { id:4, label:'Check staff attendance',            done:false },
  { id:5, label:'Review livestock health reports',   done:false },
  { id:6, label:'Approve restock requests',          done:false },
  { id:7, label:'Post daily announcements to staff', done:false },
];

function loadAdminTasks() {
  var tasks;
  try {
    var stored    = localStorage.getItem(ADMIN_TASKS_KEY);
    var storedDay = localStorage.getItem(ADMIN_TASKS_KEY + '_date');
    var todayD    = new Date().toDateString();
    if (!stored || storedDay !== todayD) {
      tasks = defaultAdminTasks.map(function(t){ return Object.assign({}, t); });
      localStorage.setItem(ADMIN_TASKS_KEY, JSON.stringify(tasks));
      localStorage.setItem(ADMIN_TASKS_KEY + '_date', todayD);
    } else {
      tasks = JSON.parse(stored);
    }
  } catch(e) { tasks = defaultAdminTasks.map(function(t){ return Object.assign({}, t); }); }
  renderAdminTasks(tasks);
}

function renderAdminTasks(tasks) {
  var container = document.getElementById('admin-tasks-list');
  var progress  = document.getElementById('admin-tasks-progress');
  if (!container) return;
  var done = tasks.filter(function(t){ return t.done; }).length;
  if (progress) progress.textContent = done + '/' + tasks.length + ' Done';
  container.innerHTML = tasks.map(function(t) {
    return '<div class="task-row' + (t.done ? ' done' : '') + '" id="atask-' + t.id + '">'
      + '<input type="checkbox"' + (t.done ? ' checked' : '') + ' onchange="toggleAdminTask(' + t.id + ',this.checked)" />'
      + '<span>' + t.label + '</span>'
      + (t.done ? '<span style="margin-left:auto;font-size:0.7rem;color:var(--olive);">\u2713 Done</span>' : '')
      + '</div>';
  }).join('');
}

function toggleAdminTask(id, checked) {
  try {
    var tasks = JSON.parse(localStorage.getItem(ADMIN_TASKS_KEY) || '[]');
    var task  = tasks.find(function(t){ return t.id === id; });
    if (task) {
      task.done = checked;
      localStorage.setItem(ADMIN_TASKS_KEY, JSON.stringify(tasks));
      renderAdminTasks(tasks);
      if (checked) UI.toast('Task marked as done!', 'success');
    }
  } catch(e) {}
}

// ── NOTES ─────────────────────────────────────────────────
var NOTES_KEY = 'admin_notes';

function loadNotes() { renderNotes(); }

function renderNotes() {
  var feed = document.getElementById('notes-feed');
  if (!feed) return;
  try {
    var notes = JSON.parse(localStorage.getItem(NOTES_KEY) || '[]');
    if (!notes.length) { feed.innerHTML = '<p style="color:var(--muted);font-size:0.82rem;">No announcements yet.</p>'; return; }
    feed.innerHTML = notes.map(function(n) {
      return '<div class="note-bubble"><div>' + n.text + '</div><div class="note-bubble__meta">' + n.author + ' \u00b7 ' + n.time + '</div></div>';
    }).join('');
  } catch(e) { feed.innerHTML = ''; }
}

document.addEventListener('DOMContentLoaded', function() {
  var saveBtn = document.getElementById('save-note-btn');
  if (saveBtn) {
    saveBtn.addEventListener('click', function() {
      var input = document.getElementById('note-input');
      var text  = input ? input.value.trim() : '';
      if (!text) { UI.toast('Please write a note first.', 'error'); return; }
      try {
        var notes = JSON.parse(localStorage.getItem(NOTES_KEY) || '[]');
        var u     = getStoredUser();
        notes.unshift({ text:text, author:u.name||'Admin', time: new Date().toLocaleString() });
        if (notes.length > 15) notes.pop();
        localStorage.setItem(NOTES_KEY, JSON.stringify(notes));
        input.value = '';
        renderNotes();
        UI.toast('Announcement posted!', 'success');
      } catch(e) { UI.toast('Failed to save.', 'error'); }
    });
  }
});

// ── INIT ──────────────────────────────────────────────────
(async function() {
  renderGreeting();

  var params = new URLSearchParams(window.location.search);
  if (params.get('access_denied') === '1') {
    UI.toast('Access denied.', 'error');
    history.replaceState({}, '', 'dashboard_admin.php');
  }

  try {
    var res  = await fetch('../dairy_farm_backend/api/auth.php?action=status', { credentials:'include' });
    var data = await res.json();
    if (!data.success) { window.location.href = 'login.php'; return; }
    if (data.data) {
      localStorage.setItem('csrf_token', data.data.csrf_token || '');
      if (data.data.user) localStorage.setItem('user', JSON.stringify(data.data.user));
    }
  } catch(e) { window.location.href = 'login.php'; return; }

  renderGreeting();
  loadAdminTasks();
  loadNotes();

  // Load all data in parallel, then build alerts and reports
  var results = await Promise.allSettled([
    API.customers.getAll(),
    API.cows.getAll(),
    API.workers.getAll(),
    API.orders.getAll(),
    loadReminders(),
  ]);

  var customers = results[0].status === 'fulfilled' && Array.isArray(results[0].value) ? results[0].value : [];
  var cows      = results[1].status === 'fulfilled' && Array.isArray(results[1].value) ? results[1].value : [];
  var workers   = results[2].status === 'fulfilled' && Array.isArray(results[2].value) ? results[2].value : [];
  var orders    = results[3].status === 'fulfilled' && Array.isArray(results[3].value) ? results[3].value : [];

  // Stat cards
  var set = function(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; };
  set('stat-customers', customers.length);
  set('stat-workers',   workers.length);
  set('stat-orders',    orders.length);

  // Livestock + milk
  if (cows.length) {
    set('stat-cows', cows.length);
    updateMilkStat(cows);
  }

  // Orders
  allOrders = orders;
  renderOrders();

  // Staff
  var staffContainer = document.getElementById('staff-list');
  if (staffContainer && workers.length) {
    staffContainer.innerHTML = workers.map(function(w) {
      var initial   = (w.Worker || '?').charAt(0).toUpperCase();
      var roleClass = w.Worker_Role === 'Admin' ? 'badge--green' : 'badge--muted';
      return '<div class="worker-row">'
        + '<div class="worker-avatar">' + initial + '</div>'
        + '<div style="flex:1;min-width:0;">'
        + '<div style="font-weight:700;font-size:0.84rem;">' + w.Worker + '</div>'
        + '<div style="font-size:0.72rem;color:var(--muted);">ID #' + w.Worker_ID + '</div>'
        + '</div>'
        + '<span class="badge ' + roleClass + '" style="font-size:0.68rem;">' + w.Worker_Role + '</span>'
        + '<a href="workers.php" style="margin-left:8px;" title="Edit"><span class="material-symbols-outlined" style="font-size:1rem;color:var(--muted);cursor:pointer;">edit</span></a>'
        + '</div>';
    }).join('');
  }

  // Livestock
  var liveContainer = document.getElementById('livestock-list');
  var sickBadge     = document.getElementById('sick-badge');
  if (liveContainer && cows.length) {
    var sickCount = 0;
    liveContainer.innerHTML = cows.map(function(c, i) {
      var sick = (i % 5 === 0);
      if (sick) sickCount++;
      var dotClass    = sick ? 'status-dot--sick' : 'status-dot--healthy';
      var healthLabel = sick
        ? '<span style="color:var(--danger);font-weight:700;font-size:0.78rem;">Sick</span>'
        : '<span style="color:var(--olive);font-weight:700;font-size:0.78rem;">Healthy</span>';
      return '<div class="cow-row">'
        + '<div style="display:flex;align-items:center;gap:6px;">'
        + '<span class="status-dot ' + dotClass + '"></span>'
        + '<div><div style="font-weight:700;font-size:0.83rem;">' + c.Cow + '</div>'
        + '<div style="font-size:0.72rem;color:var(--muted);">ID #' + c.Cow_ID + '</div></div></div>'
        + '<div style="text-align:right;">' + healthLabel
        + '<div style="font-size:0.72rem;color:var(--muted);">' + c.Production + '</div></div>'
        + '</div>';
    }).join('');
    if (sickCount > 0) {
      if (sickBadge) { sickBadge.textContent = sickCount + ' sick'; sickBadge.style.display = 'inline-block'; }
      addAlert(sickCount + ' cow(s) marked sick \u2014 vet check required.', 'danger');
    }
  }

  // Static inventory alerts
  addAlert('Silo B feed level is low (38%) \u2014 restock soon.', 'danger');
  addAlert('Milk collection truck scheduled for 4:00 PM today.', 'info');
  addAlert('Animal Feed at 52% \u2014 monitor closely.', 'warning');

  // Reports
  populateReports(cows, orders, customers, workers);

  // Render alerts last (after all data is collected)
  renderAlerts();
})();
