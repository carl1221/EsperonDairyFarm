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
};
