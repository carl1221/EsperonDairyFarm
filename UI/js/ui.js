// ============================================================
// js/ui.js
// Shared UI utilities: toast notifications, modal, loader
// ============================================================

const UI = {

  // ---------- Toast ----------
  toast(message, type = 'success') {
    // Stack toasts instead of replacing
    const existing = document.querySelectorAll('.df-toast');
    existing.forEach(t => {
      t.classList.remove('df-toast--show');
      setTimeout(() => t.remove(), 300);
    });

    const icons = { success: '✓', error: '✗', warning: '⚠', info: 'ℹ' };
    const t = document.createElement('div');
    t.className = `df-toast df-toast--${type}`;
    t.innerHTML = `
      <span class="df-toast__icon">${icons[type] || icons.success}</span>
      <span>${message}</span>
    `;
    document.body.appendChild(t);

    // Double rAF ensures transition fires after paint
    requestAnimationFrame(() => requestAnimationFrame(() => t.classList.add('df-toast--show')));

    setTimeout(() => {
      t.classList.remove('df-toast--show');
      setTimeout(() => t.remove(), 350);
    }, 3200);
  },

  // ---------- Confirm modal ----------
  confirm(message, okLabel = 'Delete') {
    return new Promise(resolve => {
      const overlay = document.createElement('div');
      overlay.className = 'df-confirm-overlay';
      overlay.innerHTML = `
        <div class="df-confirm">
          <p>${message}</p>
          <div class="df-confirm__btns">
            <button class="btn btn--ghost" id="df-confirm-cancel">Cancel</button>
            <button class="btn btn--danger" id="df-confirm-ok">${okLabel}</button>
          </div>
        </div>
      `;
      document.body.appendChild(overlay);
      requestAnimationFrame(() => requestAnimationFrame(() =>
        overlay.classList.add('df-confirm-overlay--show')
      ));

      const close = (val) => {
        overlay.classList.remove('df-confirm-overlay--show');
        setTimeout(() => { overlay.remove(); resolve(val); }, 220);
      };

      overlay.querySelector('#df-confirm-ok').onclick     = () => close(true);
      overlay.querySelector('#df-confirm-cancel').onclick = () => close(false);
      overlay.addEventListener('click', e => { if (e.target === overlay) close(false); });
      document.addEventListener('keydown', function onKey(e) {
        if (e.key === 'Escape') { close(false); document.removeEventListener('keydown', onKey); }
        if (e.key === 'Enter')  { close(true);  document.removeEventListener('keydown', onKey); }
      });
    });
  },

  // ---------- Page loader ----------
  setLoading(tableBody, cols) {
    const rows = Array.from({ length: 3 }, () =>
      `<tr>${Array.from({ length: cols }, () =>
        `<td><div class="skeleton-line skeleton" style="height:14px;border-radius:6px;"></div></td>`
      ).join('')}</tr>`
    ).join('');
    tableBody.innerHTML = rows;
  },

  setEmpty(tableBody, cols, msg = 'No records found.') {
    tableBody.innerHTML = `<tr><td colspan="${cols}" class="tbl-empty">${msg}</td></tr>`;
  },

  // ---------- Active nav ----------
  setActiveNav() {
    const path = window.location.pathname.split('/').pop();
    document.querySelectorAll('.nav__link').forEach(a => {
      a.classList.toggle('nav__link--active',
        a.getAttribute('href') === path || a.getAttribute('href') === `./${path}`
      );
    });
  },

  // ---------- Page navigation with fade ----------
  navigate(url) {
    document.body.style.transition = 'opacity 0.18s ease';
    document.body.style.opacity = '0';
    setTimeout(() => { window.location.href = url; }, 180);
  },

  // ---------- Session timeout warning ----------
  startSessionTimer(totalMinutes) {
    const warnAt = (totalMinutes - 2) * 60 * 1000;
    if (warnAt <= 0) return;

    setTimeout(() => {
      if (window.location.pathname.includes('login') || window.location.pathname.includes('signup')) return;

      const overlay = document.createElement('div');
      overlay.id = 'session-timeout-overlay';
      overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;background:rgba(42,31,21,0.55);backdrop-filter:blur(5px);display:flex;align-items:center;justify-content:center;padding:16px;opacity:0;transition:opacity 0.22s ease;';
      overlay.innerHTML = `
        <div style="background:#faf6f0;border-radius:20px;box-shadow:0 16px 56px rgba(0,0,0,0.2);width:100%;max-width:380px;padding:28px 24px;text-align:center;font-family:'Lato',sans-serif;transform:scale(0.94) translateY(12px);transition:transform 0.28s cubic-bezier(0.34,1.56,0.64,1);">
          <span class="material-symbols-outlined" style="font-size:2.5rem;color:var(--warning);display:block;margin-bottom:12px;">timer</span>
          <div style="font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:700;color:var(--text);margin-bottom:8px;">Session Expiring Soon</div>
          <p style="font-size:0.85rem;color:var(--muted);margin-bottom:20px;">Your session will expire in about 2 minutes. Click below to stay logged in.</p>
          <div style="display:flex;gap:10px;justify-content:center;">
            <button id="session-logout-btn" style="padding:10px 20px;border:1.5px solid var(--border);border-radius:10px;background:#fff;color:var(--text);font-family:'Lato',sans-serif;font-size:0.88rem;font-weight:600;cursor:pointer;">Log Out</button>
            <button id="session-stay-btn" style="padding:10px 22px;border:none;border-radius:10px;background:linear-gradient(135deg,var(--olive),#6b8a5c);color:#fff;font-family:'Lato',sans-serif;font-size:0.88rem;font-weight:700;cursor:pointer;box-shadow:0 2px 8px rgba(78,96,64,0.25);">Stay Logged In</button>
          </div>
        </div>`;
      document.body.appendChild(overlay);
      requestAnimationFrame(() => requestAnimationFrame(() => {
        overlay.style.opacity = '1';
        overlay.querySelector('div').style.transform = 'scale(1) translateY(0)';
      }));

      document.getElementById('session-stay-btn').onclick = async () => {
        try {
          await fetch('../dairy_farm_backend/api/v1/auth.php?action=status', { credentials: 'include' });
          overlay.style.opacity = '0';
          setTimeout(() => overlay.remove(), 220);
          UI.startSessionTimer(totalMinutes);
        } catch(e) { overlay.remove(); }
      };

      document.getElementById('session-logout-btn').onclick = async () => {
        try {
          await fetch('../dairy_farm_backend/api/v1/auth.php?action=logout', { method: 'POST', credentials: 'include' });
        } finally {
          localStorage.removeItem('csrf_token');
          localStorage.removeItem('user');
          window.location.href = 'login.php';
        }
      };
    }, warnAt);
  },
};

