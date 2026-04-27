// ============================================================
// js/nav.js
// Injects sidebar navigation into every authenticated page.
// Reads the current user from localStorage to display their
// name, role, and email without an extra server round-trip.
// ============================================================

(function () {
  // Load Material Icons font if not already loaded
  if (!document.getElementById('material-icons-font')) {
    const link = document.createElement('link');
    link.id = 'material-icons-font';
    link.rel = 'stylesheet';
    link.href = 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0';
    document.head.appendChild(link);
  }

  const nav = document.getElementById('app-nav');
  if (!nav) return;

  // ── Get the stored user (set by login.php after auth) ──
  let currentUser = {};
  try {
    currentUser = JSON.parse(localStorage.getItem('user') || '{}');
  } catch {
    currentUser = {};
  }

  const displayName  = currentUser.name  || 'Unknown User';
  const displayRole  = currentUser.role  || '';
  const displayEmail = currentUser.email || '';

  // Role badge colour
  const roleBadgeClass = displayRole === 'Admin' ? 'badge--green' : 'badge--muted';

  // ── Render nav HTML ─────────────────────────────────────
  nav.innerHTML = `

    <!-- Brand -->
    <div class="nav__brand">
      <img src="assets/Esperon Logo.png" alt="Esperon Logo" class="nav__brand-logo-img" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;" />
      <div class="nav__brand-name">Esperon<br>Dairy Farm</div>
      <div class="nav__brand-sub">Management System</div>
    </div>

    <!-- Logged-in user card -->
    <div class="nav__user">
      <div class="nav__user-avatar">${displayName.charAt(0).toUpperCase()}</div>
      <div class="nav__user-info">
        <div class="nav__user-name">${displayName}</div>
        <div class="nav__user-meta">
          <span class="badge ${roleBadgeClass}" style="font-size:.68rem;">${displayRole}</span>
        </div>
        ${displayEmail
          ? `<div class="nav__user-email" title="${displayEmail}">${displayEmail}</div>`
          : ''}
      </div>
    </div>

    <!-- Navigation links -->
    <span class="nav__section">Overview</span>
    <a href="index.php" class="nav__link">
      <span class="nav__link-icon material-symbols-outlined">dashboard</span><span>Dashboard</span>
    </a>

    <span class="nav__section">Records</span>
    <a href="customers.php" class="nav__link">
      <span class="nav__link-icon material-symbols-outlined">people</span><span>Customers</span>
    </a>
    <a href="cows.php" class="nav__link">
      <span class="nav__link-icon material-symbols-outlined">pets</span><span>Cows</span>
    </a>
    <a href="workers.php" class="nav__link">
      <span class="nav__link-icon material-symbols-outlined">badge</span><span>Workers</span>
    </a>
    <a href="orders.php" class="nav__link">
      <span class="nav__link-icon material-symbols-outlined">shopping_cart</span><span>Orders</span>
    </a>

    <span class="nav__section">Account</span>
    <button id="logout-btn" class="nav__link nav__logout">
      <span class="nav__link-icon material-symbols-outlined">logout</span><span>Logout</span>
    </button>

    <div class="nav__footer">Esperon Farm © 2026</div>
  `;

  // ── Highlight the active link ───────────────────────────
  UI.setActiveNav();


  // ── Logout ─────────────────────────────────────────────
  document.getElementById('logout-btn').addEventListener('click', async () => {
    try {
      await API.auth.logout();
      // Clear all client-side session data before redirecting
      localStorage.removeItem('csrf_token');
      localStorage.removeItem('user');
      window.location.href = 'login.php';
    } catch (e) {
      UI.toast('Logout failed. Please try again.', 'error');
    }
  });

})();