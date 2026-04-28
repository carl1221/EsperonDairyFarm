<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard — Esperon Dairy Farm</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>

<nav class="nav" id="app-nav"></nav>

<main class="main">
  <!-- Page Header -->
  <div class="page-header">
    <div>
      <h1 class="page-title" id="page-greeting">Welcome back!</h1>
      <p class="page-subtitle" id="page-subtitle">Here's a snapshot of your farm.</p>
    </div>
    <div class="dashboard-header__actions">
      <div class="header__search">
        <span class="material-symbols-outlined" style="font-size: 1.1rem; color: var(--muted);">search</span>
        <input type="text" placeholder="Search..." />
      </div>
      <button class="header__icon-btn" title="Inbox" aria-label="Inbox">
        <span class="material-symbols-outlined">mail</span>
      </button>
      <button class="header__icon-btn" title="Notifications" aria-label="Notifications">
        <span class="material-symbols-outlined">notifications</span>
      </button>
    </div>
  </div>

  <!-- Stat Cards -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-card__icon">
        <span class="material-symbols-outlined">people</span>
      </div>
      <div class="stat-card__content">
        <div class="stat-card__val" id="stat-customers">0</div>
        <div class="stat-card__label">Total Customers</div>
      </div>
    </div>
    <div class="stat-card stat-card--gold">
      <div class="stat-card__icon">
        <span class="material-symbols-outlined">pets</span>
      </div>
      <div class="stat-card__content">
        <div class="stat-card__val" id="stat-cows">0</div>
        <div class="stat-card__label">Total Cows</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-card__icon">
        <span class="material-symbols-outlined">badge</span>
      </div>
      <div class="stat-card__content">
        <div class="stat-card__val" id="stat-workers">0</div>
        <div class="stat-card__label">Active Workers</div>
      </div>
    </div>
    <div class="stat-card stat-card--danger">
      <div class="stat-card__icon">
        <span class="material-symbols-outlined">shopping_cart</span>
      </div>
      <div class="stat-card__content">
        <div class="stat-card__val" id="stat-orders">0</div>
        <div class="stat-card__label">Total Orders</div>
      </div>
    </div>
  </div>

  <!-- Info Cards Row -->
  <div class="info-cards-row">
    <!-- Feed Inventory -->
    <div class="card">
      <div class="card__header">
        <span class="card__title" style="display: flex; align-items: center; gap: 8px;">
          <span class="material-symbols-outlined" style="font-size: 1.2rem; color: var(--olive);">lunch_dining</span>
          Feed Inventory
        </span>
      </div>
      <div style="padding: 20px 24px;">
        <div style="margin-bottom: 16px;">
          <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
            <span style="font-size: 0.85rem; color: var(--text);">Silage A</span>
            <span style="font-size: 0.85rem; font-weight: 700; color: var(--olive-dark);">78%</span>
          </div>
          <div style="height: 8px; background: var(--beige); border-radius: 4px; overflow: hidden;">
            <div style="height: 100%; width: 78%; background: linear-gradient(90deg, var(--olive), var(--olive-light)); border-radius: 4px;"></div>
          </div>
        </div>
        <div style="margin-bottom: 16px;">
          <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
            <span style="font-size: 0.85rem; color: var(--text);">Silo B</span>
            <span style="font-size: 0.85rem; font-weight: 700; color: var(--olive-dark);">62%</span>
          </div>
          <div style="height: 8px; background: var(--beige); border-radius: 4px; overflow: hidden;">
            <div style="height: 100%; width: 62%; background: linear-gradient(90deg, var(--olive), var(--olive-light)); border-radius: 4px;"></div>
          </div>
        </div>
        <div>
          <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
            <span style="font-size: 0.85rem; color: var(--text);">Hay</span>
            <span style="font-size: 0.85rem; font-weight: 700; color: var(--olive-dark);">88%</span>
          </div>
          <div style="height: 8px; background: var(--beige); border-radius: 4px; overflow: hidden;">
            <div style="height: 100%; width: 88%; background: linear-gradient(90deg, var(--olive), var(--olive-light)); border-radius: 4px;"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reminders / Task System -->
    <div class="card">
      <div class="card__header">
        <span class="card__title" style="display: flex; align-items: center; gap: 8px;">
          <span class="material-symbols-outlined" style="font-size: 1.2rem; color: var(--danger);">warning</span>
          Reminders
          <span id="reminderBadge" class="badge badge--red" style="display: none; font-size: 0.65rem; margin-left: 8px;">0</span>
        </span>
        <button id="addReminderBtn" style="background: var(--danger); color: #fff; border: none; border-radius: 4px; padding: 4px 12px; cursor: pointer; font-size: 0.75rem;">+ Add Task</button>
      </div>
      <div id="remindersList" style="padding: 16px 24px;">
        <!-- Reminders loaded from database -->
      </div>
    </div>

    <!-- Daily Tasks -->
    <div class="card">
      <div class="card__header">
        <span class="card__title" style="display: flex; align-items: center; gap: 8px;">
          <span class="material-symbols-outlined" style="font-size: 1.2rem; color: var(--olive);">checklist</span>
          Daily Tasks
        </span>
        <span class="badge badge--green" style="font-size: 0.7rem;">4/5 Done</span>
      </div>
      <div style="padding: 16px 24px;">
        <ul style="list-style: none; padding: 0; margin: 0;">
          <li style="display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--border-light);">
            <input type="checkbox" checked disabled style="accent-color: var(--olive); width: 16px; height: 16px;" />
            <span style="font-size: 0.85rem; color: var(--muted); text-decoration: line-through;">Milk Quality Check</span>
          </li>
          <li style="display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--border-light);">
            <input type="checkbox" checked disabled style="accent-color: var(--olive); width: 16px; height: 16px;" />
            <span style="font-size: 0.85rem; color: var(--muted); text-decoration: line-through;">Calving Prep</span>
          </li>
          <li style="display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--border-light);">
            <input type="checkbox" checked disabled style="accent-color: var(--olive); width: 16px; height: 16px;" />
            <span style="font-size: 0.85rem; color: var(--muted); text-decoration: line-through;">Equipment Check</span>
          </li>
          <li style="display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--border-light);">
            <input type="checkbox" checked disabled style="accent-color: var(--olive); width: 16px; height: 16px;" />
            <span style="font-size: 0.85rem; color: var(--muted); text-decoration: line-through;">Feed Mix</span>
          </li>
          <li style="display: flex; align-items: center; gap: 10px; padding: 8px 0;">
            <input type="checkbox" disabled style="accent-color: var(--olive); width: 16px; height: 16px;" />
            <span style="font-size: 0.85rem; color: var(--text);">Pasture Rotation Planning</span>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <!-- Recent Orders -->
  <div class="card">
    <div class="card__header">
      <span class="card__title" style="display: flex; align-items: center; gap: 8px;">
        <span class="material-symbols-outlined" style="font-size: 1.2rem; color: var(--olive);">receipt_long</span>
        Recent Orders
      </span>
      <button class="btn btn--ghost" style="font-size: 0.8rem; padding: 4px 12px;">View All</button>
    </div>
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Type</th>
            <th>Cow</th>
            <th>Production</th>
            <th>Worker</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody id="recent-orders-body">
          <tr><td colspan="7" class="tbl-empty"><span class="spinner"></span> Loading…</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</main>

<script src="js/api.js"></script>
<script src="js/ui.js"></script>
<script src="js/nav.js"></script>
<script>
// ── Reminders / Task System (Database-powered) ───────────────
let reminders = [];

// Get status color based on due date
function getStatusInfo(dueDate, status) {
  if (status === 'completed') {
    return { color: 'var(--olive)', bg: 'var(--olive-light)', label: 'Completed', urgent: false };
  }
  const now = new Date();
  const due = new Date(dueDate);
  const hoursUntilDue = (due - now) / (1000 * 60 * 60);
  
  if (hoursUntilDue < 0) {
    return { color: 'var(--danger)', bg: 'var(--danger-lt)', label: 'Overdue', urgent: true };
  } else if (hoursUntilDue <= 24) {
    return { color: '#f39c12', bg: '#fef9e7', label: 'Due Soon', urgent: true };
  } else {
    return { color: 'var(--olive)', bg: 'var(--olive-light)', label: 'Pending', urgent: false };
  }
}

// Format date nicely — always AM/PM
function formatDueDate(dateStr) {
  const date = new Date(dateStr);
  const now = new Date();
  const tomorrow = new Date(now);
  tomorrow.setDate(tomorrow.getDate() + 1);

  const isToday    = date.toDateString() === now.toDateString();
  const isTomorrow = date.toDateString() === tomorrow.toDateString();

  const timeStr = date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true });

  if (isToday)    return `Today, ${timeStr}`;
  if (isTomorrow) return `Tomorrow, ${timeStr}`;

  const monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  return `${monthNames[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()} — ${timeStr}`;
}

// Load reminders from API
async function loadReminders() {
  const list = document.getElementById('remindersList');
  if (!list) return;
  
  list.innerHTML = '<p style="color: var(--text-light); font-size: 0.85rem;"><span class="spinner"></span> Loading...</p>';
  
  try {
    const res = await fetch('../dairy_farm_backend/api/reminders.php', {
      credentials: 'include'
    });
    const data = await res.json();
    
    if (data.success) {
      reminders = data.data || [];
      renderReminders();
      updateBadge();
      checkNotifications();
    } else {
      list.innerHTML = '<p style="color: var(--danger); font-size: 0.85rem;">Failed to load reminders.</p>';
    }
  } catch (e) {
    console.error('Error loading reminders:', e);
    list.innerHTML = '<p style="color: var(--danger); font-size: 0.85rem;">Error loading reminders.</p>';
  }
}

// Render reminders in the UI
function renderReminders() {
  const list = document.getElementById('remindersList');
  if (!list) return;
  
  if (reminders.length === 0) {
    list.innerHTML = '<p style="color: var(--text-light); font-size: 0.85rem;">No tasks yet. Click "+ Add Task" to create one.</p>';
    return;
  }
  
  // Sort by due date (nearest first)
  const sorted = [...reminders].sort((a, b) => new Date(a.due_date) - new Date(b.due_date));
  
  list.innerHTML = sorted.map(r => {
    const status = getStatusInfo(r.due_date, r.status);
    const isCompleted = r.status === 'completed';
    return `
      <div style="background: ${status.bg}; border-radius: 8px; padding: 12px; margin-bottom: 10px; border-left: 3px solid ${status.color};">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
          <div style="flex: 1;">
            <span style="font-size: 0.7rem; color: ${status.color}; font-weight: 700; text-transform: uppercase;">${status.label}</span>
            <p style="font-size: 0.9rem; color: var(--text); margin: 4px 0; ${isCompleted ? 'text-decoration: line-through; opacity: 0.6;' : ''}">${r.title}</p>
            <span style="font-size: 0.7rem; color: var(--text-light);">Due: ${formatDueDate(r.due_date)}</span>
          </div>
          <div style="display: flex; gap: 4px;">
            ${!isCompleted ? `<button onclick="markComplete(${r.reminder_id})" style="background: var(--olive); color: #fff; border: none; border-radius: 4px; padding: 4px 8px; cursor: pointer; font-size: 0.7rem;">✓</button>` : ''}
            <button onclick="deleteReminder(${r.reminder_id})" style="background: transparent; border: none; color: var(--danger); cursor: pointer; padding: 4px 8px; font-size: 0.9rem;">✕</button>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

// Update badge counter
function updateBadge() {
  const badge = document.getElementById('reminderBadge');
  if (!badge) return;
  
  const urgentCount = reminders.filter(r => {
    if (r.status === 'completed') return false;
    const status = getStatusInfo(r.due_date, r.status);
    return status.urgent;
  }).length;
  
  if (urgentCount > 0) {
    badge.textContent = urgentCount;
    badge.style.display = 'inline-block';
  } else {
    badge.style.display = 'none';
  }
}

// Check for notifications
function checkNotifications() {
  const overdue = reminders.filter(r => r.status === 'pending' && getStatusInfo(r.due_date, r.status).label === 'Overdue');
  const dueSoon = reminders.filter(r => r.status === 'pending' && getStatusInfo(r.due_date, r.status).label === 'Due Soon');
  
  if (overdue.length > 0) {
    setTimeout(() => alert(`⚠️ You have ${overdue.length} overdue task(s)!`), 500);
  } else if (dueSoon.length > 0) {
    setTimeout(() => alert(`⏰ You have ${dueSoon.length} task(s) due within 24 hours!`), 500);
  }
}

// ── Reminder Modal ────────────────────────────────────────
(function () {
  // Inject modal HTML once
  const modalHTML = `
  <div id="reminderModal" style="
    display: none; position: fixed; inset: 0; z-index: 9999;
    background: rgba(42,31,21,0.45); backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    align-items: center; justify-content: center;
  ">
    <div id="reminderModalBox" style="
      background: rgba(255,255,255,0.92); backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,0.7);
      border-radius: 20px; box-shadow: 0 12px 48px rgba(0,0,0,0.18);
      width: 100%; max-width: 460px; margin: 16px;
      animation: modalSlideIn 0.25s ease;
      font-family: 'Lato', sans-serif;
    ">
      <!-- Header -->
      <div style="
        display: flex; align-items: center; justify-content: space-between;
        padding: 20px 24px 16px; border-bottom: 1px solid rgba(212,201,184,0.4);
      ">
        <div style="display:flex; align-items:center; gap:10px;">
          <span class="material-symbols-outlined" style="color:#c0392b; font-size:1.3rem;">alarm_add</span>
          <span style="font-family:'Playfair Display',serif; font-size:1.1rem; font-weight:700; color:#2a1f15;">Add Reminder</span>
        </div>
        <button id="reminderModalClose" style="
          background:none; border:none; cursor:pointer; color:#8a7f72;
          width:32px; height:32px; border-radius:50%; display:flex;
          align-items:center; justify-content:center; font-size:1.2rem;
          transition: background 0.15s, color 0.15s;
        " onmouseover="this.style.background='#fdf0ef';this.style.color='#c0392b'"
           onmouseout="this.style.background='none';this.style.color='#8a7f72'">
          <span class="material-symbols-outlined" style="font-size:1.2rem;">close</span>
        </button>
      </div>

      <!-- Body -->
      <div style="padding: 20px 24px;">

        <!-- Title -->
        <div style="margin-bottom:16px;">
          <label style="display:block; font-size:0.78rem; font-weight:700; color:#4a3f35; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:6px;">
            Task Title <span style="color:#c0392b;">*</span>
          </label>
          <input id="rm_title" type="text" placeholder="e.g. Vet check for Cow #3"
            style="
              width:100%; padding:10px 14px; border:1.5px solid #e8dfd2;
              border-radius:10px; font-size:0.9rem; font-family:'Lato',sans-serif;
              color:#2a1f15; background:rgba(255,255,255,0.7); outline:none;
              transition: border-color 0.15s, box-shadow 0.15s;
            "
            onfocus="this.style.borderColor='#4e6040';this.style.boxShadow='0 0 0 3px rgba(78,96,64,0.12)'"
            onblur="this.style.borderColor='#e8dfd2';this.style.boxShadow='none'"
          />
          <div id="rm_title_err" style="display:none; color:#c0392b; font-size:0.75rem; margin-top:4px;">
            <span class="material-symbols-outlined" style="font-size:0.85rem; vertical-align:middle;">error</span>
            Title is required.
          </div>
        </div>

        <!-- Description -->
        <div style="margin-bottom:16px;">
          <label style="display:block; font-size:0.78rem; font-weight:700; color:#4a3f35; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:6px;">
            Description <span style="color:#8a7f72; font-weight:400; text-transform:none; letter-spacing:0;">(optional)</span>
          </label>
          <textarea id="rm_desc" rows="2" placeholder="Add any extra details..."
            style="
              width:100%; padding:10px 14px; border:1.5px solid #e8dfd2;
              border-radius:10px; font-size:0.9rem; font-family:'Lato',sans-serif;
              color:#2a1f15; background:rgba(255,255,255,0.7); outline:none; resize:vertical;
              transition: border-color 0.15s, box-shadow 0.15s;
            "
            onfocus="this.style.borderColor='#4e6040';this.style.boxShadow='0 0 0 3px rgba(78,96,64,0.12)'"
            onblur="this.style.borderColor='#e8dfd2';this.style.boxShadow='none'"
          ></textarea>
        </div>

        <!-- Date + Time row -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:8px;">
          <div>
            <label style="display:block; font-size:0.78rem; font-weight:700; color:#4a3f35; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:6px;">
              Due Date <span style="color:#c0392b;">*</span>
            </label>
            <input id="rm_date" type="date"
              style="
                width:100%; padding:10px 14px; border:1.5px solid #e8dfd2;
                border-radius:10px; font-size:0.9rem; font-family:'Lato',sans-serif;
                color:#2a1f15; background:rgba(255,255,255,0.7); outline:none;
                transition: border-color 0.15s, box-shadow 0.15s;
              "
              onfocus="this.style.borderColor='#4e6040';this.style.boxShadow='0 0 0 3px rgba(78,96,64,0.12)'"
              onblur="this.style.borderColor='#e8dfd2';this.style.boxShadow='none'"
            />
          </div>
          <div>
            <label style="display:block; font-size:0.78rem; font-weight:700; color:#4a3f35; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:6px;">
              Time <span style="color:#c0392b;">*</span>
            </label>
            <div style="display:flex; gap:6px; align-items:center;">
              <select id="rm_time_hour" style="
                flex:2; min-width:0; padding:10px 6px; border:1.5px solid #e8dfd2;
                border-radius:10px; font-size:0.88rem; font-family:'Lato',sans-serif;
                color:#2a1f15; background:rgba(255,255,255,0.7); outline:none; cursor:pointer;
                transition: border-color 0.15s;
              "
              onfocus="this.style.borderColor='#4e6040'" onblur="this.style.borderColor='#e8dfd2'">
                ${Array.from({length:12},(_,i)=>{const h=i+1;return `<option value="${h}">${h}</option>`;}).join('')}
              </select>
              <select id="rm_time_min" style="
                flex:2; min-width:0; padding:10px 6px; border:1.5px solid #e8dfd2;
                border-radius:10px; font-size:0.88rem; font-family:'Lato',sans-serif;
                color:#2a1f15; background:rgba(255,255,255,0.7); outline:none; cursor:pointer;
                transition: border-color 0.15s;
              "
              onfocus="this.style.borderColor='#4e6040'" onblur="this.style.borderColor='#e8dfd2'">
                <option value="00">00</option>
                <option value="15">15</option>
                <option value="30">30</option>
                <option value="45">45</option>
              </select>
              <select id="rm_time_ampm" style="
                flex:2; min-width:0; padding:10px 6px; border:1.5px solid #e8dfd2;
                border-radius:10px; font-size:0.88rem; font-family:'Lato',sans-serif;
                color:#2a1f15; background:rgba(255,255,255,0.7); outline:none; cursor:pointer;
                transition: border-color 0.15s;
              "
              onfocus="this.style.borderColor='#4e6040'" onblur="this.style.borderColor='#e8dfd2'">
                <option value="AM">AM</option>
                <option value="PM">PM</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Date/time error -->
        <div id="rm_date_err" style="display:none; color:#c0392b; font-size:0.75rem; margin-bottom:12px;">
          <span class="material-symbols-outlined" style="font-size:0.85rem; vertical-align:middle;">error</span>
          Due date and time are required.
        </div>

        <!-- Preview -->
        <div id="rm_preview" style="
          display:none; background:rgba(232,240,224,0.5); border:1px solid rgba(78,96,64,0.2);
          border-radius:10px; padding:10px 14px; font-size:0.82rem; color:#4e6040;
          margin-bottom:16px;
        ">
          <span class="material-symbols-outlined" style="font-size:0.9rem; vertical-align:middle; margin-right:4px;">schedule</span>
          <span id="rm_preview_text"></span>
        </div>

      </div>

      <!-- Footer -->
      <div style="
        display:flex; justify-content:flex-end; gap:10px;
        padding: 0 24px 20px;
      ">
        <button id="reminderModalCancel" style="
          padding:10px 20px; border:1.5px solid #d4c9b8; border-radius:10px;
          background:rgba(255,255,255,0.5); color:#4a3f35; font-family:'Lato',sans-serif;
          font-size:0.88rem; font-weight:600; cursor:pointer;
          transition: background 0.15s, border-color 0.15s;
        "
        onmouseover="this.style.background='rgba(255,255,255,0.8)'"
        onmouseout="this.style.background='rgba(255,255,255,0.5)'">
          Cancel
        </button>
        <button id="reminderModalSubmit" style="
          padding:10px 24px; border:none; border-radius:10px;
          background:linear-gradient(135deg,#4e6040,#6b8a5c); color:#fff;
          font-family:'Lato',sans-serif; font-size:0.88rem; font-weight:700;
          cursor:pointer; box-shadow:0 2px 8px rgba(78,96,64,0.25);
          transition: opacity 0.15s, transform 0.15s;
          display:flex; align-items:center; gap:6px;
        "
        onmouseover="this.style.opacity='0.9';this.style.transform='translateY(-1px)'"
        onmouseout="this.style.opacity='1';this.style.transform='none'">
          <span class="material-symbols-outlined" style="font-size:1rem;">add_task</span>
          Save Reminder
        </button>
      </div>
    </div>
  </div>

  <style>
    @keyframes modalSlideIn {
      from { opacity:0; transform: translateY(-16px) scale(0.97); }
      to   { opacity:1; transform: translateY(0)     scale(1);    }
    }
  </style>
  `;

  document.body.insertAdjacentHTML('beforeend', modalHTML);

  const modal       = document.getElementById('reminderModal');
  const box         = document.getElementById('reminderModalBox');
  const closeBtn    = document.getElementById('reminderModalClose');
  const cancelBtn   = document.getElementById('reminderModalCancel');
  const submitBtn   = document.getElementById('reminderModalSubmit');
  const titleInput  = document.getElementById('rm_title');
  const descInput   = document.getElementById('rm_desc');
  const dateInput   = document.getElementById('rm_date');
  const hourSel     = document.getElementById('rm_time_hour');
  const minSel      = document.getElementById('rm_time_min');
  const ampmSel     = document.getElementById('rm_time_ampm');
  const titleErr    = document.getElementById('rm_title_err');
  const dateErr     = document.getElementById('rm_date_err');
  const preview     = document.getElementById('rm_preview');
  const previewText = document.getElementById('rm_preview_text');

  // Default time to next hour, rounded
  function setDefaultDateTime() {
    const now = new Date();
    now.setMinutes(0, 0, 0);
    now.setHours(now.getHours() + 1);

    // Date
    const yyyy = now.getFullYear();
    const mm   = String(now.getMonth() + 1).padStart(2, '0');
    const dd   = String(now.getDate()).padStart(2, '0');
    dateInput.value = `${yyyy}-${mm}-${dd}`;

    // Hour (12h)
    let h = now.getHours();
    ampmSel.value = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    hourSel.value = h;
    minSel.value  = '00';
  }

  // Build preview string
  function updatePreview() {
    if (!dateInput.value) { preview.style.display = 'none'; return; }
    const [y, m, d] = dateInput.value.split('-').map(Number);
    const monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const h   = hourSel.value;
    const min = minSel.value;
    const ap  = ampmSel.value;
    previewText.textContent = `${monthNames[m-1]} ${d}, ${y} — ${h}:${min} ${ap}`;
    preview.style.display = 'block';
  }

  // Convert 12h → 24h for DB storage
  function to24h(h, min, ampm) {
    let hour = parseInt(h, 10);
    if (ampm === 'AM' && hour === 12) hour = 0;
    if (ampm === 'PM' && hour !== 12) hour += 12;
    return `${String(hour).padStart(2,'0')}:${min}:00`;
  }

  // Open
  function openModal() {
    setDefaultDateTime();
    titleInput.value = '';
    descInput.value  = '';
    titleErr.style.display = 'none';
    dateErr.style.display  = 'none';
    preview.style.display  = 'none';
    modal.style.display    = 'flex';
    setTimeout(() => titleInput.focus(), 50);
    updatePreview();
  }

  // Close
  function closeModal() {
    modal.style.display = 'none';
  }

  // Wire up the existing "+ Add Task" button
  const addBtn = document.getElementById('addReminderBtn');
  if (addBtn) {
    addBtn.onclick = openModal;
  }

  closeBtn.onclick  = closeModal;
  cancelBtn.onclick = closeModal;

  // Close on backdrop click
  modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

  // Close on Escape
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

  // Live preview on change
  [dateInput, hourSel, minSel, ampmSel].forEach(el => el.addEventListener('change', updatePreview));

  // Submit
  submitBtn.onclick = async function () {
    // Validate
    let valid = true;
    if (!titleInput.value.trim()) {
      titleErr.style.display = 'block';
      titleInput.style.borderColor = '#c0392b';
      valid = false;
    } else {
      titleErr.style.display = 'none';
      titleInput.style.borderColor = '#e8dfd2';
    }
    if (!dateInput.value) {
      dateErr.style.display = 'block';
      dateInput.style.borderColor = '#c0392b';
      valid = false;
    } else {
      dateErr.style.display = 'none';
      dateInput.style.borderColor = '#e8dfd2';
    }
    if (!valid) return;

    // Build datetime string for DB (YYYY-MM-DD HH:MM:SS)
    const time24   = to24h(hourSel.value, minSel.value, ampmSel.value);
    const dueDate  = `${dateInput.value} ${time24}`;

    // Disable button while saving
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size:1rem;animation:spin 0.8s linear infinite">progress_activity</span> Saving…';

    try {
      const csrfToken = localStorage.getItem('csrf_token');
      const res = await fetch('../dairy_farm_backend/api/reminders.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken
        },
        credentials: 'include',
        body: JSON.stringify({
          title:       titleInput.value.trim(),
          description: descInput.value.trim() || null,
          due_date:    dueDate,
          status:      'pending'
        })
      });
      const data = await res.json();

      if (data.success) {
        closeModal();
        loadReminders();
        UI.toast('Reminder added successfully!', 'success');
      } else {
        UI.toast('Failed to save: ' + data.message, 'error');
      }
    } catch (e) {
      console.error('Error saving reminder:', e);
      UI.toast('Network error. Please try again.', 'error');
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size:1rem;">add_task</span> Save Reminder';
    }
  };

  // Add spin keyframe for loading state
  const spinStyle = document.createElement('style');
  spinStyle.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
  document.head.appendChild(spinStyle);
})();
// ── End Reminder Modal ────────────────────────────────────

// Mark as complete
async function markComplete(id) {
  if (!confirm('Mark this task as completed?')) return;
  
  try {
    const csrfToken = localStorage.getItem('csrf_token');
    const res = await fetch('../dairy_farm_backend/api/reminders.php?id=' + id, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
      },
      credentials: 'include',
      body: JSON.stringify({ status: 'completed' })
    });
    const data = await res.json();
    
    if (data.success) {
      loadReminders();
    } else {
      alert('Failed to update task: ' + data.message);
    }
  } catch (e) {
    console.error('Error updating task:', e);
    alert('Error updating task.');
  }
};

// Delete reminder
async function deleteReminder(id) {
  if (!confirm('Delete this task?')) return;
  
  try {
    const csrfToken = localStorage.getItem('csrf_token');
    const res = await fetch('../dairy_farm_backend/api/reminders.php?id=' + id, {
      method: 'DELETE',
      headers: { 'X-CSRF-Token': csrfToken },
      credentials: 'include'
    });
    const data = await res.json();
    
    if (data.success) {
      loadReminders();
    } else {
      alert('Failed to delete task: ' + data.message);
    }
  } catch (e) {
    console.error('Error deleting task:', e);
    alert('Error deleting task.');
  }
};

// Load on page load
loadReminders();
// ── End Reminders ─────────────────────────────────────────

// ── Helpers ───────────────────────────────────────────────
function getStoredUser() {
  try {
    return JSON.parse(localStorage.getItem('user') || '{}');
  } catch {
    return {};
  }
}

// ── Personalised greeting ─────────────────────────────────
function renderGreeting() {
  const user     = getStoredUser();
  const name     = user.name  || 'there';
  const role     = user.role  || '';
  const hour     = new Date().getHours();

  const timeOfDay =
    hour < 12 ? 'Good morning' :
    hour < 18 ? 'Good afternoon' :
                'Good evening';

  document.getElementById('page-greeting').innerHTML =
    `${timeOfDay}, ${name}! <span class="material-symbols-outlined" style="vertical-align: middle; font-size: 1.5rem;">waving_hand</span>`;

  document.getElementById('page-subtitle').textContent =
    role
      ? `Logged in as ${role} · Here's a snapshot of the farm.`
      : "Here's a snapshot of the farm.";
}

// ── Dashboard data ────────────────────────────────────────
async function loadDashboard() {
  // Fetch all four in parallel; use allSettled so one failure doesn't block the rest
  const [customersResult, cowsResult, workersResult, ordersResult] = await Promise.allSettled([
    API.customers.getAll(),
    API.cows.getAll(),
    API.workers.getAll(),
    API.orders.getAll(),
  ]);

  const customers = customersResult.status === 'fulfilled' && Array.isArray(customersResult.value) ? customersResult.value : [];
  const cows      = cowsResult.status      === 'fulfilled' && Array.isArray(cowsResult.value)      ? cowsResult.value      : [];
  const workers   = workersResult.status   === 'fulfilled' && Array.isArray(workersResult.value)   ? workersResult.value   : [];
  const orders    = ordersResult.status    === 'fulfilled' && Array.isArray(ordersResult.value)    ? ordersResult.value    : [];

  document.getElementById('stat-customers').textContent = customers.length;
  document.getElementById('stat-cows').textContent      = cows.length;
  document.getElementById('stat-workers').textContent   = workers.length;
  document.getElementById('stat-orders').textContent    = orders.length;

  // Log any individual failures for debugging
  [customersResult, cowsResult, workersResult, ordersResult].forEach((r, i) => {
    if (r.status === 'rejected') {
      const names = ['customers', 'cows', 'workers', 'orders'];
      console.error(`[Dashboard] Failed to load ${names[i]}:`, r.reason?.message || r.reason);
    }
  });

  const tbody  = document.getElementById('recent-orders-body');
  const recent = orders.slice(-5).reverse();

  if (!recent.length) {
    UI.setEmpty(tbody, 7);
    return;
  }

  tbody.innerHTML = recent.map(o => `
    <tr>
      <td><strong>#${o.Order_ID}</strong></td>
      <td>${o.Customer_Name}</td>
      <td><span class="badge badge--green">${o.Order_Type}</span></td>
      <td>${o.Cow}</td>
      <td>${o.Production}</td>
      <td>${o.Worker} <span class="badge badge--muted">${o.Worker_Role}</span></td>
      <td>${o.Order_Date}</td>
    </tr>
  `).join('');
}

// ── Init ──────────────────────────────────────────────────
(async () => {
  // Show a personalised greeting right away using localStorage
  renderGreeting();

  // Then confirm the session is still valid server-side
  try {
    const res = await fetch('../dairy_farm_backend/api/auth.php?action=status', {
      credentials: 'include',
    });
    const data = await res.json();

    if (!data.success) {
      window.location.href = 'login.php';
      return;
    }

    // Sync localStorage with server session
    if (data.data) {
      localStorage.setItem('csrf_token', data.data.csrf_token || '');
      if (data.data.user) {
        localStorage.setItem('user', JSON.stringify(data.data.user));
      }
    }
  } catch {
    window.location.href = 'login.php';
    return;
  }

  // Re-render greeting with freshest data
  renderGreeting();

  // Load dashboard data
  await loadDashboard();
})();

</script>
</body>
</html>