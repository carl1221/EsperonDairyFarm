// ============================================================
// js/ui.js
// Shared UI utilities: toast notifications, modal, loader
// ============================================================

const UI = {

  // ---------- Toast ----------
  toast(message, type = 'success') {
    const existing = document.querySelector('.df-toast');
    if (existing) existing.remove();

    const t = document.createElement('div');
    t.className = `df-toast df-toast--${type}`;
    t.innerHTML = `
      <span class="df-toast__icon">${type === 'success' ? '✓' : '✗'}</span>
      <span>${message}</span>
    `;
    document.body.appendChild(t);
    requestAnimationFrame(() => t.classList.add('df-toast--show'));
    setTimeout(() => {
      t.classList.remove('df-toast--show');
      setTimeout(() => t.remove(), 400);
    }, 3000);
  },

  // ---------- Confirm modal ----------
  confirm(message) {
    return new Promise(resolve => {
      const overlay = document.createElement('div');
      overlay.className = 'df-confirm-overlay';
      overlay.innerHTML = `
        <div class="df-confirm">
          <p>${message}</p>
          <div class="df-confirm__btns">
            <button class="btn btn--ghost" id="df-confirm-cancel">Cancel</button>
            <button class="btn btn--danger" id="df-confirm-ok">Delete</button>
          </div>
        </div>
      `;
      document.body.appendChild(overlay);
      requestAnimationFrame(() => overlay.classList.add('df-confirm-overlay--show'));

      overlay.querySelector('#df-confirm-ok').onclick = () => {
        overlay.remove(); resolve(true);
      };
      overlay.querySelector('#df-confirm-cancel').onclick = () => {
        overlay.remove(); resolve(false);
      };
    });
  },

  // ---------- Page loader ----------
  setLoading(tableBody, cols) {
    tableBody.innerHTML = `
      <tr><td colspan="${cols}" class="tbl-empty">
        <span class="spinner"></span> Loading…
      </td></tr>`;
  },

  setEmpty(tableBody, cols, msg = 'No records found.') {
    tableBody.innerHTML = `<tr><td colspan="${cols}" class="tbl-empty">${msg}</td></tr>`;
  },

  // ---------- Active nav ----------
  setActiveNav() {
    const path = window.location.pathname.split('/').pop();
    document.querySelectorAll('.nav__link').forEach(a => {
      a.classList.toggle('nav__link--active', a.getAttribute('href') === path || a.getAttribute('href') === `./${path}`);
    });
  },

  // ---------- Session timeout warning ----------
  // Call UI.startSessionTimer(minutesUntilExpiry) once after login.
  // Shows a warning modal 2 minutes before the session expires.
  startSessionTimer(totalMinutes) {
    const warnAt = (totalMinutes - 2) * 60 * 1000; // warn 2 min before expiry
    if (warnAt <= 0) return;

    setTimeout(() => {
      // Don't show on login/signup pages
      if (window.location.pathname.includes('login') || window.location.pathname.includes('signup')) return;

      const overlay = document.createElement('div');
      overlay.id = 'session-timeout-overlay';
      overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;background:rgba(42,31,21,0.55);backdrop-filter:blur(5px);display:flex;align-items:center;justify-content:center;padding:16px;';
      overlay.innerHTML = `
        <div style="background:#faf6f0;border-radius:20px;box-shadow:0 16px 56px rgba(0,0,0,0.2);width:100%;max-width:380px;padding:28px 24px;text-align:center;font-family:'Lato',sans-serif;">
          <span class="material-symbols-outlined" style="font-size:2.5rem;color:var(--warning);display:block;margin-bottom:12px;">timer</span>
          <div style="font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:700;color:var(--text);margin-bottom:8px;">Session Expiring Soon</div>
          <p style="font-size:0.85rem;color:var(--muted);margin-bottom:20px;">Your session will expire in about 2 minutes. Click below to stay logged in.</p>
          <div style="display:flex;gap:10px;justify-content:center;">
            <button id="session-logout-btn" style="padding:10px 20px;border:1.5px solid var(--border);border-radius:10px;background:#fff;color:var(--text);font-family:'Lato',sans-serif;font-size:0.88rem;font-weight:600;cursor:pointer;">Log Out</button>
            <button id="session-stay-btn" style="padding:10px 22px;border:none;border-radius:10px;background:linear-gradient(135deg,var(--olive),#6b8a5c);color:#fff;font-family:'Lato',sans-serif;font-size:0.88rem;font-weight:700;cursor:pointer;box-shadow:0 2px 8px rgba(78,96,64,0.25);">Stay Logged In</button>
          </div>
        </div>`;
      document.body.appendChild(overlay);

      document.getElementById('session-stay-btn').onclick = async () => {
        try {
          // Ping the server to refresh the session
          await fetch('../dairy_farm_backend/api/auth.php?action=status', { credentials: 'include' });
          overlay.remove();
          // Restart the timer
          UI.startSessionTimer(totalMinutes);
        } catch(e) { overlay.remove(); }
      };

      document.getElementById('session-logout-btn').onclick = async () => {
        try {
          await fetch('../dairy_farm_backend/api/auth.php?action=logout', { method: 'POST', credentials: 'include' });
        } finally {
          localStorage.removeItem('csrf_token');
          localStorage.removeItem('user');
          window.location.href = 'login.php';
        }
      };
    }, warnAt);
  },
};
