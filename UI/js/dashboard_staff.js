// ============================================================
// js/dashboard_staff.js  —  Staff Dashboard Logic
// ============================================================

// ── Helpers ───────────────────────────────────────────────
function getStoredUser() { try { return JSON.parse(localStorage.getItem('user')||'{}'); } catch { return {}; } }
function today() { return new Date().toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' }); }
function nowTime() { return new Date().toLocaleTimeString([], { hour:'2-digit', minute:'2-digit', hour12:true }); }

// ── Tab switcher ──────────────────────────────────────────
function switchStaffTab(tabId, btn) {
  document.querySelectorAll('.dash-tab-panel').forEach(function(p){ p.style.display='none'; });
  document.querySelectorAll('.dash-tab').forEach(function(b){ b.classList.remove('dash-tab--active'); });
  var panel = document.getElementById(tabId);
  if (panel) panel.style.display = 'block';
  if (btn)   btn.classList.add('dash-tab--active');
  try { localStorage.setItem('staff_active_tab', tabId); } catch(e) {}
}

document.addEventListener('DOMContentLoaded', function() {
  var saved = localStorage.getItem('staff_active_tab') || 'tab-orders';
  var btn   = document.getElementById('sbtn-' + saved.replace('tab-',''));
  if (btn) switchStaffTab(saved, btn);
});

// ── Greeting ──────────────────────────────────────────────
function renderGreeting() {
  const u = getStoredUser();
  const h = new Date().getHours();
  const tod = h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
  const greet = document.getElementById('page-greeting');
  const sub   = document.getElementById('page-subtitle');
  const dateEl = document.getElementById('current-date');
  if (greet) greet.innerHTML = `${tod}, ${u.name || 'there'}! <span class="material-symbols-outlined" style="vertical-align:middle;font-size:1.5rem;">waving_hand</span>`;
  if (sub)   sub.textContent = "Welcome Staff \u00b7 Here's your farm work summary for today.";
  if (dateEl) dateEl.textContent = today();
}

// ── ALERTS ────────────────────────────────────────────────
const alertItems = [];

function addAlert(msg, type = 'warning') {
  alertItems.push({ msg, type });
  renderAlerts();
}

function renderAlerts() {
  const container = document.getElementById('alerts-list');
  const badge     = document.getElementById('alerts-badge');
  const statEl    = document.getElementById('stat-alerts');
  if (!container) return;
  if (statEl) statEl.textContent = alertItems.length;
  if (!alertItems.length) {
    container.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;">No active alerts. All good! \u2713</p>';
    if (badge) badge.style.display = 'none';
    return;
  }
  if (badge) { badge.textContent = alertItems.length; badge.style.display = 'inline-block'; }
  const icons = { danger:'warning', warning:'info', info:'info' };
  container.innerHTML = alertItems.map(a => `
    <div class="alert-item alert-item--${a.type}">
      <span class="material-symbols-outlined" style="font-size:1rem;flex-shrink:0;">${icons[a.type]||'info'}</span>
      <span>${a.msg}</span>
    </div>`).join('');
}

function loadAlerts() {
  addAlert('Silo B feed level is low (38%) \u2014 report to admin.', 'danger');
  addAlert('Scheduled vet visit tomorrow at 9:00 AM.', 'info');
  addAlert('Milk collection truck arrives at 4:00 PM today.', 'info');
}

// ── ORDERS ────────────────────────────────────────────────
async function loadOrders() {
  const container = document.getElementById('orders-list');
  try {
    const orders = await API.orders.getAll();
    const statEl = document.getElementById('stat-orders');
    if (statEl) statEl.textContent = orders.length;

    // Update quick-action sub-label
    const sub = document.getElementById('qa-orders-sub');
    if (sub) sub.textContent = orders.length + ' total order' + (orders.length !== 1 ? 's' : '');

    if (!orders.length) { container.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;padding:8px 0;">No orders found.</p>'; return; }

    const recent = [...orders].reverse().slice(0, 6);
    const statusBadge = {
      pending:   'order-status--pending',
      confirmed: 'order-status--processing',
      delivered: 'order-status--delivered',
      cancelled: 'order-status--cancelled',
    };
    const statusLabel = { pending:'Pending', confirmed:'Confirmed', delivered:'Delivered', cancelled:'Cancelled' };

    container.innerHTML = recent.map(o => {
      const statusKey = (o.Order_Status || o.status || 'pending').toLowerCase();
      return `<div style="display:flex;align-items:center;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--border-light);">
        <div style="flex:1;min-width:0;">
          <div style="font-weight:700;font-size:0.84rem;color:var(--text);">#${o.Order_ID} \u2014 ${o.Customer_Name}</div>
          <div style="font-size:0.75rem;color:var(--muted);margin-top:2px;">${o.Order_Type} \u00b7 ${o.Cow} \u00b7 ${o.Order_Date}</div>
        </div>
        <span class="order-status ${statusBadge[statusKey] || 'order-status--pending'}">${statusLabel[statusKey] || statusKey}</span>
      </div>`;
    }).join('');
  } catch(e) {
    if (container) container.innerHTML = '<p style="color:var(--danger);font-size:0.84rem;">Failed to load orders.</p>';
  }
}

// ── LIVESTOCK ─────────────────────────────────────────────
async function loadLivestock() {
  const container = document.getElementById('livestock-list');
  const countEl   = document.getElementById('cow-count');
  try {
    const cows = await API.cows.getAll();
    if (countEl) countEl.textContent = `${cows.length} cows`;
    if (!cows.length) { container.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;">No livestock records.</p>'; return; }
    let sickCount = 0;
    container.innerHTML = cows.map((c) => {
      // Use real Health_Status from the database
      const health  = c.Health_Status || 'Healthy';
      const isSick  = health === 'Sick' || health === 'Under Treatment';
      if (isSick) sickCount++;
      const dotClass    = isSick ? 'status-dot--sick' : 'status-dot--healthy';
      const healthLabel = isSick
        ? `<span style="color:var(--danger);font-weight:700;">${health}</span>`
        : `<span style="color:var(--olive);font-weight:700;">${health}</span>`;
      return `<div class="cow-row">
        <div><span class="status-dot ${dotClass}"></span>
          <strong style="font-size:0.84rem;">${c.Cow}</strong>
          <span style="font-size:0.75rem;color:var(--muted);margin-left:6px;">ID #${c.Cow_ID}</span>
        </div>
        <div style="text-align:right;">${healthLabel}
          <div style="font-size:0.72rem;color:var(--muted);">${parseFloat(c.Production_Liters || 0).toFixed(2)}L/day</div>
        </div>
      </div>`;
    }).join('');
    if (sickCount > 0) addAlert(`${sickCount} cow(s) marked as sick \u2014 vet check required.`, 'danger');
  } catch(e) {
    if (container) container.innerHTML = '<p style="color:var(--danger);font-size:0.84rem;">Failed to load livestock.</p>';
  }
}

// ── TASKS ─────────────────────────────────────────────────
const TASKS_KEY = 'staff_tasks_' + (getStoredUser().id || 'default');
const defaultTasks = [
  { id:1, label:'Morning milking (6:00 AM)',     done:false },
  { id:2, label:'Feed cattle \u2014 Silage A',   done:false },
  { id:3, label:'Clean milking equipment',        done:false },
  { id:4, label:'Check water troughs',            done:false },
  { id:5, label:'Evening milking (4:00 PM)',      done:false },
  { id:6, label:'Record daily milk production',   done:false },
  { id:7, label:'Pasture rotation check',         done:false },
];

function loadTasks() {
  let tasks;
  try {
    const stored     = localStorage.getItem(TASKS_KEY);
    const storedDate = localStorage.getItem(TASKS_KEY + '_date');
    const todayStr   = new Date().toDateString();
    if (!stored || storedDate !== todayStr) {
      tasks = defaultTasks.map(t => ({ ...t }));
      localStorage.setItem(TASKS_KEY, JSON.stringify(tasks));
      localStorage.setItem(TASKS_KEY + '_date', todayStr);
    } else {
      tasks = JSON.parse(stored);
    }
  } catch { tasks = defaultTasks.map(t => ({ ...t })); }
  renderTasks(tasks);
}

function renderTasks(tasks) {
  const container = document.getElementById('tasks-list');
  const progress  = document.getElementById('tasks-progress');
  const done      = tasks.filter(t => t.done).length;
  if (progress) progress.textContent = `${done}/${tasks.length} Done`;
  const statEl = document.getElementById('stat-tasks');
  if (statEl) statEl.textContent = tasks.filter(t => !t.done).length;
  if (!container) return;
  container.innerHTML = tasks.map(t => `
    <div class="task-row ${t.done ? 'done' : ''}" id="task-row-${t.id}">
      <input type="checkbox" ${t.done ? 'checked' : ''} onchange="toggleTask(${t.id}, this.checked)" />
      <span>${t.label}</span>
      ${t.done ? `<span style="margin-left:auto;font-size:0.7rem;color:var(--olive);">\u2713 Done</span>` : ''}
    </div>`).join('');
}

function toggleTask(id, checked) {
  try {
    const tasks = JSON.parse(localStorage.getItem(TASKS_KEY) || '[]');
    const task  = tasks.find(t => t.id === id);
    if (task) {
      task.done = checked;
      localStorage.setItem(TASKS_KEY, JSON.stringify(tasks));
      renderTasks(tasks);
      if (checked) { logActivity(`Completed task: "${task.label}"`); UI.toast('Task marked as done!', 'success'); }
    }
  } catch(e) { console.error(e); }
}

// ── ACTIVITY LOG ──────────────────────────────────────────
const LOG_KEY = 'staff_activity_log_' + (getStoredUser().id || 'default');

async function logActivity(text) {
  try {
    // Save locally for instant display
    const logs = JSON.parse(localStorage.getItem(LOG_KEY) || '[]');
    logs.unshift({ text, time: nowTime(), date: new Date().toDateString() });
    if (logs.length > 20) logs.pop();
    localStorage.setItem(LOG_KEY, JSON.stringify(logs));
    renderActivityLog();

    // Also save to database so admin can see it in Staff Reports
    const csrf = localStorage.getItem('csrf_token') || '';
    await fetch('../dairy_farm_backend/api/reports.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify({
        report_type: 'Activity Log',
        title: new Date().toLocaleDateString('en-US', { weekday:'short', month:'short', day:'numeric' }) + ' — Activity',
        content: text
      })
    });
  } catch(e) { /* non-critical — local log still saved */ }
}

function renderActivityLog() {
  const container = document.getElementById('activity-log');
  if (!container) return;
  try {
    const allLogs  = JSON.parse(localStorage.getItem(LOG_KEY) || '[]');
    const todayStr = new Date().toDateString();
    const logs     = allLogs.filter(l => l.date === todayStr);
    if (!logs.length) { container.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;">No activities logged today.</p>'; return; }
    container.innerHTML = logs.map(l => `
      <div class="log-entry">
        <span class="log-time">${l.time}</span>
        <span style="color:var(--text);">${l.text}</span>
      </div>`).join('');
  } catch(e) { container.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;">No activities logged today.</p>'; }
}

document.addEventListener('DOMContentLoaded', () => {
  const logBtn = document.getElementById('log-activity-btn');
  if (!logBtn) return;
  logBtn.addEventListener('click', () => {
    const existing = document.getElementById('quick-log-panel');
    if (existing) { existing.remove(); return; }
    const activities = [
      'Morning milking completed','Cattle fed \u2014 Silage A',
      'Water troughs checked and refilled','Milking equipment cleaned and sanitized',
      'Evening milking completed','Pasture rotation completed',
      'Sick cow reported to admin','Milk production recorded',
    ];
    const panel = document.createElement('div');
    panel.id = 'quick-log-panel';
    panel.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(42,31,21,0.45);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:16px;';
    panel.innerHTML = `
      <div style="background:#faf6f0;border-radius:20px;box-shadow:0 16px 56px rgba(0,0,0,0.2);width:100%;max-width:420px;font-family:'Lato',sans-serif;overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;background:linear-gradient(135deg,#4e6040,#6b8a5c);">
          <span style="font-family:'Playfair Display',serif;font-size:1.05rem;font-weight:700;color:#fff;">Log Activity</span>
          <button onclick="document.getElementById('quick-log-panel').remove()" style="background:rgba(255,255,255,0.15);border:none;cursor:pointer;width:30px;height:30px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;">
            <span class="material-symbols-outlined" style="font-size:1.1rem;">close</span>
          </button>
        </div>
        <div style="padding:20px 22px;">
          <p style="font-size:0.8rem;color:var(--muted);margin-bottom:10px;">Quick select or type a custom activity:</p>
          <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px;">
            ${activities.map(a => `<button onclick="quickLog('${a.replace(/'/g,"\\'")}',event)" style="padding:5px 12px;border:1.5px solid var(--border);border-radius:20px;background:rgba(255,255,255,0.6);font-size:0.78rem;cursor:pointer;font-family:'Lato',sans-serif;color:var(--text);">${a}</button>`).join('')}
          </div>
          <textarea id="custom-log-input" placeholder="Or type a custom activity\u2026" rows="2" style="width:100%;padding:10px 14px;border:1.5px solid var(--border-light);border-radius:10px;font-size:0.88rem;font-family:'Lato',sans-serif;color:var(--text);background:#fff;outline:none;resize:none;box-sizing:border-box;"></textarea>
          <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:10px;">
            <button onclick="document.getElementById('quick-log-panel').remove()" style="padding:8px 16px;border:1.5px solid var(--border);border-radius:8px;background:#fff;color:var(--text);font-family:'Lato',sans-serif;font-size:0.84rem;font-weight:600;cursor:pointer;">Cancel</button>
            <button onclick="submitCustomLog()" style="padding:8px 18px;border:none;border-radius:8px;background:linear-gradient(135deg,#4e6040,#6b8a5c);color:#fff;font-family:'Lato',sans-serif;font-size:0.84rem;font-weight:700;cursor:pointer;">Log It</button>
          </div>
        </div>
      </div>`;
    document.body.appendChild(panel);
    panel.addEventListener('click', e => { if (e.target === panel) panel.remove(); });
  });

  const saveNoteBtn = document.getElementById('save-note-btn');
  if (saveNoteBtn) {
    saveNoteBtn.addEventListener('click', () => {
      const input = document.getElementById('note-input');
      const text  = input ? input.value.trim() : '';
      if (!text) { UI.toast('Please write a note first.', 'error'); return; }
      try {
        const notes = JSON.parse(localStorage.getItem(NOTES_KEY) || '[]');
        const u     = getStoredUser();
        notes.unshift({ text, author: u.name || 'Staff', time: `${today()} ${nowTime()}` });
        if (notes.length > 10) notes.pop();
        localStorage.setItem(NOTES_KEY, JSON.stringify(notes));
        input.value = '';
        renderNotes();
        logActivity('Submitted a note to admin.');
        UI.toast('Note submitted!', 'success');
      } catch(e) { UI.toast('Failed to save note.', 'error'); }
    });
  }
});

function quickLog(text) {
  logActivity(text);
  const panel = document.getElementById('quick-log-panel');
  if (panel) panel.remove();
  UI.toast('Activity logged!', 'success');
}

function submitCustomLog() {
  const input = document.getElementById('custom-log-input');
  const text  = input ? input.value.trim() : '';
  if (!text) { UI.toast('Please enter an activity.', 'error'); return; }
  quickLog(text);
}

// ── REMINDERS (view-only) ─────────────────────────────────
let reminders = [];

function getStatusInfo(dueDate, status) {
  if (status === 'completed') return { color:'var(--olive)', bg:'var(--olive-light)', label:'Completed', urgent:false };
  const now = new Date(), due = new Date(dueDate), h = (due - now) / (1000 * 60 * 60);
  if (h < 0)   return { color:'var(--danger)', bg:'var(--danger-lt)', label:'Overdue', urgent:true };
  if (h <= 24) return { color:'#f39c12', bg:'#fef9e7', label:'Due Soon', urgent:true };
  return { color:'var(--olive)', bg:'var(--olive-light)', label:'Pending', urgent:false };
}

function formatDueDate(dateStr) {
  const date = new Date(dateStr), now = new Date(), tomorrow = new Date(now);
  tomorrow.setDate(tomorrow.getDate() + 1);
  const timeStr = date.toLocaleTimeString([], { hour:'numeric', minute:'2-digit', hour12:true });
  if (date.toDateString() === now.toDateString())      return `Today, ${timeStr}`;
  if (date.toDateString() === tomorrow.toDateString()) return `Tomorrow, ${timeStr}`;
  const m = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  return `${m[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()} \u2014 ${timeStr}`;
}

async function loadReminders() {
  const list = document.getElementById('remindersList');
  if (!list) return;
  list.innerHTML = '<p style="color:var(--text-light);font-size:0.84rem;"><span class="spinner"></span> Loading\u2026</p>';
  try {
    const res  = await fetch('../dairy_farm_backend/api/reminders.php', { credentials:'include' });
    const data = await res.json();
    if (data.success) {
      reminders = data.data || [];
      renderReminders();
      updateReminderBadge();
      const overdue = reminders.filter(r => r.status === 'pending' && getStatusInfo(r.due_date, r.status).label === 'Overdue');
      if (overdue.length) addAlert(`${overdue.length} overdue reminder(s) need attention.`, 'danger');
    } else {
      list.innerHTML = '<p style="color:var(--danger);font-size:0.84rem;">Failed to load reminders.</p>';
    }
  } catch(e) {
    list.innerHTML = '<p style="color:var(--danger);font-size:0.84rem;">Error loading reminders.</p>';
  }
}

function renderReminders() {
  const list = document.getElementById('remindersList');
  if (!list) return;
  if (!reminders.length) { list.innerHTML = '<p style="color:var(--text-light);font-size:0.84rem;">No reminders assigned.</p>'; return; }
  const sorted = [...reminders].sort((a, b) => new Date(a.due_date) - new Date(b.due_date));
  list.innerHTML = sorted.map(r => {
    const s = getStatusInfo(r.due_date, r.status), done = r.status === 'completed';
    const doneBtn = !done
      ? `<button onclick="markReminderComplete(${r.reminder_id})"
           title="Mark as done"
           style="background:var(--olive);color:#fff;border:none;border-radius:6px;padding:4px 10px;cursor:pointer;font-size:0.72rem;font-weight:600;display:inline-flex;align-items:center;gap:3px;margin-top:6px;">
           <span class="material-symbols-outlined" style="font-size:0.85rem;">check</span> Mark Done
         </button>`
      : `<span style="font-size:0.72rem;color:var(--olive);font-weight:600;display:inline-flex;align-items:center;gap:3px;margin-top:6px;">
           <span class="material-symbols-outlined" style="font-size:0.85rem;">check_circle</span> Completed
         </span>`;
    return `<div style="background:${s.bg};border-radius:8px;padding:10px 12px;margin-bottom:8px;border-left:3px solid ${s.color};">
      <span style="font-size:0.68rem;color:${s.color};font-weight:700;text-transform:uppercase;">${s.label}</span>
      <p style="font-size:0.86rem;color:var(--text);margin:3px 0;${done?'text-decoration:line-through;opacity:0.6;':''}">${r.title}</p>
      <span style="font-size:0.7rem;color:var(--text-light);">Due: ${formatDueDate(r.due_date)}</span>
      <div>${doneBtn}</div>
    </div>`;
  }).join('');
}

async function markReminderComplete(id) {
  try {
    const csrf = localStorage.getItem('csrf_token') || '';
    const res  = await fetch('../dairy_farm_backend/api/reminders.php?id=' + id, {
      method: 'PATCH',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify({ status: 'completed' })
    });
    const data = await res.json();
    if (data.success) {
      UI.toast('Task marked as done!', 'success');
      loadReminders();
    } else {
      UI.toast('Failed: ' + (data.message || 'error'), 'error');
    }
  } catch(e) {
    UI.toast('Network error.', 'error');
  }
}

function updateReminderBadge() {
  const badge = document.getElementById('reminderBadge');
  if (!badge) return;
  const n = reminders.filter(r => r.status !== 'completed' && getStatusInfo(r.due_date, r.status).urgent).length;
  badge.textContent = n; badge.style.display = n > 0 ? 'inline-block' : 'none';
}

// ── NOTES ─────────────────────────────────────────────────
const NOTES_KEY = 'staff_notes_' + (getStoredUser().id || 'default');

function loadNotes() { renderNotes(); }

function renderNotes() {
  const feed = document.getElementById('notes-feed');
  if (!feed) return;
  try {
    const notes = JSON.parse(localStorage.getItem(NOTES_KEY) || '[]');
    if (!notes.length) { feed.innerHTML = '<p style="color:var(--muted);font-size:0.82rem;">No notes yet.</p>'; return; }
    feed.innerHTML = notes.map(n => `
      <div class="note-bubble">
        <div>${n.text}</div>
        <div class="note-bubble__meta">${n.author} \u00b7 ${n.time}</div>
      </div>`).join('');
  } catch(e) { feed.innerHTML = ''; }
}

// ── INVENTORY (read from localStorage — same key as admin) ─
function loadStaffInventory() {
  var INV_KEY = 'admin_inventory';
  var defaultInv = [
    { name:'Milk Stock',  pct:65, unit:'L',  icon:'water_drop'  },
    { name:'Silage A',    pct:78, unit:'kg', icon:'grass'        },
    { name:'Silo B',      pct:38, unit:'kg', icon:'warehouse'    },
    { name:'Hay',         pct:88, unit:'kg', icon:'agriculture'  },
    { name:'Animal Feed', pct:52, unit:'kg', icon:'lunch_dining' },
  ];
  var items;
  try {
    var stored = localStorage.getItem(INV_KEY);
    items = stored ? JSON.parse(stored) : defaultInv;
    if (!Array.isArray(items) || !items.length) items = defaultInv;
  } catch(e) { items = defaultInv; }

  var container = document.getElementById('staff-inventory-bars');
  if (!container) return;

  container.innerHTML = items.map(function(item) {
    var pct  = Math.min(100, Math.max(0, item.pct || 0));
    var cls  = pct < 30 ? 'inv-bar-fill--low' : pct < 60 ? 'inv-bar-fill--mid' : 'inv-bar-fill--ok';
    var col  = pct < 30 ? 'var(--danger)' : pct < 60 ? '#7a5a1e' : 'var(--olive-dark)';
    var warn = pct < 30 ? ' <span style="color:var(--danger);font-size:0.72rem;">⚠ Low</span>' : '';
    return '<div class="inv-bar-wrap">'
      + '<div class="inv-bar-label">'
      + '<span style="display:flex;align-items:center;gap:5px;">'
      + '<span class="material-symbols-outlined" style="font-size:0.9rem;color:var(--muted);">' + (item.icon||'inventory_2') + '</span>'
      + item.name + warn + '</span>'
      + '<span style="color:' + col + ';font-weight:700;">' + pct + '%</span>'
      + '</div>'
      + '<div class="inv-bar"><div class="inv-bar-fill ' + cls + '" style="width:' + pct + '%"></div></div>'
      + '</div>';
  }).join('');

  // Alert for low items
  items.forEach(function(item) {
    if ((item.pct||0) < 30) addAlert(item.name + ' is critically low (' + item.pct + '%) — notify admin.', 'danger');
  });
}

// ── STAFF RESTOCK MODAL ───────────────────────────────────
function openStaffRestockModal() {
  var existing = document.getElementById('staffRestockModal');
  if (existing) { existing.remove(); return; }

  var INV_KEY = 'admin_inventory';
  var items;
  try {
    var stored = localStorage.getItem(INV_KEY);
    items = stored ? JSON.parse(stored) : null;
    if (!Array.isArray(items) || !items.length) items = null;
  } catch(e) { items = null; }

  if (!items) {
    UI.toast('No inventory data found.', 'error');
    return;
  }

  var el = document.createElement('div');
  el.id = 'staffRestockModal';
  el.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(42,31,21,0.45);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:16px;';

  var options = items.map(function(i) {
    return '<option value="' + i.id + '">' + i.name + ' (currently ' + i.pct + '%)</option>';
  }).join('');
  var initPct = items[0].pct;

  el.innerHTML = '<div style="background:#faf6f0;border-radius:18px;box-shadow:0 12px 48px rgba(0,0,0,0.18);width:100%;max-width:400px;font-family:\'Lato\',sans-serif;overflow:hidden;">'
    + '<div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px 12px;background:linear-gradient(135deg,#4e6040,#6b8a5c);">'
    + '<div style="display:flex;align-items:center;gap:8px;"><span class="material-symbols-outlined" style="color:#fff;font-size:1.1rem;">add_circle</span>'
    + '<span style="font-family:\'Playfair Display\',serif;font-size:1rem;font-weight:700;color:#fff;">Restock Inventory</span></div>'
    + '<button onclick="document.getElementById(\'staffRestockModal\').remove()" style="background:rgba(255,255,255,0.2);border:none;cursor:pointer;width:26px;height:26px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;">'
    + '<span class="material-symbols-outlined" style="font-size:0.95rem;">close</span></button></div>'
    + '<div style="padding:18px 20px;">'
    + '<div style="margin-bottom:12px;">'
    + '<label style="display:block;font-size:0.75rem;font-weight:700;color:#4a3f35;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;">Select Item</label>'
    + '<select id="sr-item" onchange="onSrItemChange()" style="width:100%;padding:9px 12px;border:1.5px solid var(--border-light);border-radius:9px;font-size:0.87rem;font-family:\'Lato\',sans-serif;color:var(--text);background:#fff;outline:none;box-sizing:border-box;">'
    + options + '</select></div>'
    + '<div style="margin-bottom:12px;">'
    + '<label style="display:block;font-size:0.75rem;font-weight:700;color:#4a3f35;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;">New Stock Level (%)</label>'
    + '<div style="display:flex;align-items:center;gap:10px;">'
    + '<input id="sr-val" type="range" min="0" max="100" value="' + initPct + '" oninput="syncSrNumber();updateSrPreview();" style="flex:1;accent-color:var(--olive);cursor:pointer;" />'
    + '<input id="sr-num" type="number" min="0" max="100" value="' + initPct + '" oninput="syncSrSlider();updateSrPreview();" style="width:64px;padding:7px 8px;border:1.5px solid var(--border-light);border-radius:8px;font-size:0.88rem;font-family:\'Lato\',sans-serif;color:var(--text);background:#fff;outline:none;text-align:center;" />'
    + '<span style="font-size:0.84rem;color:var(--muted);">%</span>'
    + '</div>'
    + '<div style="display:flex;justify-content:space-between;font-size:0.72rem;color:var(--muted);margin-top:2px;"><span>0%</span><span>50%</span><span>100%</span></div>'
    + '</div>'
    + '<div id="sr-preview" style="background:rgba(232,240,224,0.5);border:1px solid rgba(78,96,64,0.2);border-radius:9px;padding:10px 14px;margin-bottom:14px;font-size:0.84rem;color:var(--olive-dark);">'
    + '<span class="material-symbols-outlined" style="font-size:0.9rem;vertical-align:middle;margin-right:4px;">info</span>'
    + '<span id="sr-preview-text">Select an item to preview</span></div>'
    + '<div style="display:flex;justify-content:flex-end;gap:8px;">'
    + '<button onclick="document.getElementById(\'staffRestockModal\').remove()" style="padding:8px 16px;border:1.5px solid var(--border);border-radius:8px;background:#fff;color:var(--text);font-family:\'Lato\',sans-serif;font-size:0.83rem;font-weight:600;cursor:pointer;">Cancel</button>'
    + '<button onclick="submitStaffRestock()" style="padding:8px 18px;border:none;border-radius:8px;background:linear-gradient(135deg,#4e6040,#6b8a5c);color:#fff;font-family:\'Lato\',sans-serif;font-size:0.83rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px;">'
    + '<span class="material-symbols-outlined" style="font-size:0.9rem;">save</span> Save</button>'
    + '</div></div></div>';

  document.body.appendChild(el);
  el.addEventListener('click', function(e){ if(e.target===el) el.remove(); });
  updateSrPreview();
}

function syncSrNumber() {
  var s = document.getElementById('sr-val'), n = document.getElementById('sr-num');
  if (s && n) n.value = s.value;
}
function syncSrSlider() {
  var s = document.getElementById('sr-val'), n = document.getElementById('sr-num');
  if (!s || !n) return;
  var v = Math.min(100, Math.max(0, parseInt(n.value,10)||0));
  n.value = v; s.value = v;
}
function updateSrPreview() {
  var itemSel = document.getElementById('sr-item');
  var valInput = document.getElementById('sr-val');
  var previewEl = document.getElementById('sr-preview-text');
  if (!itemSel || !valInput || !previewEl) return;
  var INV_KEY = 'admin_inventory';
  var items; try { items = JSON.parse(localStorage.getItem(INV_KEY)||'[]'); } catch(e){ items=[]; }
  var item = items.find(function(i){ return i.id === itemSel.value; });
  if (!item) return;
  var newPct = parseInt(valInput.value, 10);
  var newAmt = Math.round(newPct / 100 * item.capacity);
  var oldAmt = Math.round(item.pct / 100 * item.capacity);
  var diff = newAmt - oldAmt;
  previewEl.textContent = item.name + ': ' + item.pct + '% → ' + newPct + '% ('
    + oldAmt + ' → ' + newAmt + ' ' + item.unit + ', ' + (diff >= 0 ? '+' : '') + diff + ' ' + item.unit + ')';
}
function onSrItemChange() {
  var itemSel = document.getElementById('sr-item');
  var slider  = document.getElementById('sr-val');
  var num     = document.getElementById('sr-num');
  if (!itemSel || !slider || !num) return;
  var INV_KEY = 'admin_inventory';
  var items; try { items = JSON.parse(localStorage.getItem(INV_KEY)||'[]'); } catch(e){ items=[]; }
  var item = items.find(function(i){ return i.id === itemSel.value; });
  if (!item) return;
  slider.value = item.pct; num.value = item.pct;
  updateSrPreview();
}
function submitStaffRestock() {
  var itemSel = document.getElementById('sr-item');
  var numInput = document.getElementById('sr-num');
  if (!itemSel || !numInput) return;
  var newPct = Math.min(100, Math.max(0, parseInt(numInput.value, 10)));
  if (isNaN(newPct)) { UI.toast('Please enter a valid percentage.', 'error'); return; }
  var INV_KEY = 'admin_inventory';
  var items; try { items = JSON.parse(localStorage.getItem(INV_KEY)||'[]'); } catch(e){ items=[]; }
  var item = items.find(function(i){ return i.id === itemSel.value; });
  if (!item) return;
  item.pct = newPct;
  localStorage.setItem(INV_KEY, JSON.stringify(items));
  localStorage.setItem(INV_KEY + '_updated', new Date().toLocaleString());
  document.getElementById('staffRestockModal').remove();
  loadStaffInventory(); // refresh the bars
  UI.toast(item.name + ' updated to ' + newPct + '%.', 'success');
}

// ── HEARTBEAT ─────────────────────────────────────────────
function startStaffHeartbeat() {
  function ping() {
    var csrf = localStorage.getItem('csrf_token');
    if (!csrf) return;
    fetch('../dairy_farm_backend/api/heartbeat.php', {
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json','X-CSRF-Token':csrf}
    }).catch(function(){});
  }
  ping();
  return setInterval(ping, 30000);
}
async function loadMilkStat() {
  try {
    const cows  = await API.cows.getAll();
    const total = cows.reduce((sum, c) => {
      const match = String(c.Production).match(/(\d+(\.\d+)?)/);
      return sum + (match ? parseFloat(match[1]) : 0);
    }, 0);
    const statEl = document.getElementById('stat-milk');
    if (statEl) statEl.textContent = total > 0 ? total + 'L' : '\u2014';
    const pct   = Math.min(Math.round((total / 500) * 100), 100);
    const bar   = document.getElementById('inv-milk-bar');
    const pctEl = document.getElementById('inv-milk-pct');
    if (bar)   { bar.style.width = pct + '%'; bar.className = 'inv-bar-fill ' + (pct < 30 ? 'inv-bar-fill--low' : pct < 60 ? 'inv-bar-fill--mid' : 'inv-bar-fill--ok'); }
    if (pctEl) pctEl.textContent = total > 0 ? total + 'L' : '\u2014';
    if (pct < 30) addAlert('Milk stock is low \u2014 check production records.', 'warning');
  } catch(e) { /* non-critical */ }
}

// ── INIT ──────────────────────────────────────────────────
(async () => {
  renderGreeting();

  const params = new URLSearchParams(window.location.search);
  if (params.get('access_denied') === '1') {
    UI.toast('Access denied. Admins only.', 'error');
    history.replaceState({}, '', 'dashboard_staff.php');
  }

  try {
    const res  = await fetch('../dairy_farm_backend/api/auth.php?action=status', { credentials:'include' });
    const data = await res.json();
    if (!data.success) { window.location.href = 'login.php'; return; }
    if (data.data) {
      localStorage.setItem('csrf_token', data.data.csrf_token || '');
      if (data.data.user) localStorage.setItem('user', JSON.stringify(data.data.user));
    }
  } catch { window.location.href = 'login.php'; return; }

  renderGreeting();

  // Load static sections immediately
  loadAlerts();
  loadTasks();
  renderActivityLog();
  loadNotes();
  loadStaffInventory();
  startStaffHeartbeat();

  // Load all API-dependent sections in parallel
  await Promise.allSettled([
    loadOrders(),
    loadLivestock(),
    loadReminders(),
    loadMilkStat(),
  ]);

  // Update quick action subtitles after data loads
  var tasksDone = document.getElementById('tasks-progress');
  var qaSub     = document.getElementById('qa-tasks-sub');
  if (tasksDone && qaSub) qaSub.textContent = tasksDone.textContent;
  var ordersEl = document.getElementById('stat-orders');
  var qaOrders = document.getElementById('qa-orders-sub');
  if (ordersEl && qaOrders) qaOrders.textContent = ordersEl.textContent + ' total orders';
  var cowEl    = document.getElementById('cow-count');
  var qaLive   = document.getElementById('qa-livestock-sub');
  if (cowEl && qaLive) qaLive.textContent = cowEl.textContent || 'Check herd status';
  var remBadge = document.getElementById('reminderBadge');
  var qaRem    = document.getElementById('qa-reminders-sub');
  if (remBadge && qaRem && remBadge.style.display !== 'none') qaRem.textContent = remBadge.textContent + ' urgent reminder(s)';

  // Inventory quick card subtitle
  var invItems = loadInventory();
  var lowCount = invItems.filter(function(i){ return (i.pct||0) < 30; }).length;
  var qaInv    = document.getElementById('qa-inventory-sub');
  if (qaInv) {
    qaInv.textContent = lowCount > 0
      ? lowCount + ' item(s) critically low!'
      : 'Update stock levels';
    if (lowCount > 0) qaInv.style.color = 'var(--danger)';
  }
})();
