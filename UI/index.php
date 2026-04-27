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

    <!-- Health Alerts -->
    <div class="card">
      <div class="card__header">
        <span class="card__title" style="display: flex; align-items: center; gap: 8px;">
          <span class="material-symbols-outlined" style="font-size: 1.2rem; color: var(--danger);">warning</span>
          Health Alerts
        </span>
        <span class="badge badge--red" style="font-size: 0.7rem;">3 Urgent</span>
      </div>
      <div style="padding: 16px 24px;">
        <div style="background: var(--danger-lt); border-radius: 8px; padding: 12px; margin-bottom: 10px; border-left: 3px solid var(--danger);">
          <span style="font-size: 0.75rem; color: var(--danger); font-weight: 700; text-transform: uppercase;">URGENT</span>
          <p style="font-size: 0.85rem; color: var(--text); margin-top: 4px; margin-bottom: 0;">Cow #102: Mastitis Check</p>
        </div>
        <div style="background: var(--danger-lt); border-radius: 8px; padding: 12px; margin-bottom: 10px; border-left: 3px solid var(--danger);">
          <span style="font-size: 0.75rem; color: var(--danger); font-weight: 700; text-transform: uppercase;">URGENT</span>
          <p style="font-size: 0.85rem; color: var(--text); margin-top: 4px; margin-bottom: 0;">Cow #144: Lameness Check</p>
        </div>
        <div style="background: var(--danger-lt); border-radius: 8px; padding: 12px; border-left: 3px solid var(--danger);">
          <span style="font-size: 0.75rem; color: var(--danger); font-weight: 700; text-transform: uppercase;">URGENT</span>
          <p style="font-size: 0.85rem; color: var(--text); margin-top: 4px; margin-bottom: 0;">Cow #210: Urgent Check</p>
        </div>
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
  try {
    const [customers, cows, workers, orders] = await Promise.all([
      API.customers.getAll(),
      API.cows.getAll(),
      API.workers.getAll(),
      API.orders.getAll(),
    ]);

    document.getElementById('stat-customers').textContent = customers.length;
    document.getElementById('stat-cows').textContent      = cows.length;
    document.getElementById('stat-workers').textContent   = workers.length;
    document.getElementById('stat-orders').textContent    = orders.length;

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

  } catch (err) {
    UI.toast('Failed to load dashboard data. Please check your connection.', 'error');
  }
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