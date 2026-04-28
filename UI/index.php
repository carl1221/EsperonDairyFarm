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

// Format date nicely
function formatDueDate(dateStr) {
  const date = new Date(dateStr);
  const now = new Date();
  const tomorrow = new Date(now);
  tomorrow.setDate(tomorrow.getDate() + 1);
  
  const isToday = date.toDateString() === now.toDateString();
  const isTomorrow = date.toDateString() === tomorrow.toDateString();
  
  let timeStr = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  
  if (isToday) return `Today, ${timeStr}`;
  if (isTomorrow) return `Tomorrow, ${timeStr}`;
  return date.toLocaleDateString([], { month: 'short', day: 'numeric' }) + `, ${timeStr}`;
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

// Add new reminder
document.getElementById('addReminderBtn').onclick = async function() {
  const title = prompt('Enter task title:');
  if (!title || !title.trim()) return;
  
  const dueDate = prompt('Enter due date (YYYY-MM-DD HH:MM):', new Date().toISOString().slice(0, 16).replace('T', ' '));
  if (!dueDate) return;
  
  const description = prompt('Enter description (optional):', '');
  
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
        title: title.trim(),
        description: description?.trim() || null,
        due_date: dueDate,
        status: 'pending'
      })
    });
    const data = await res.json();
    
    if (data.success) {
      alert('Task created successfully!');
      loadReminders();
    } else {
      alert('Failed to create task: ' + data.message);
    }
  } catch (e) {
    console.error('Error creating task:', e);
    alert('Error creating task.');
  }
};

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