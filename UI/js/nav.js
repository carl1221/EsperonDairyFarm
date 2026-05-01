// ============================================================
// js/nav.js  —  Sidebar + Profile Edit Modal
// ============================================================
(function () {

  const BASE_API = '../dairy_farm_backend/api';

  // ── Material Icons ────────────────────────────────────────
  if (!document.getElementById('material-icons-font')) {
    const l = document.createElement('link');
    l.id = 'material-icons-font'; l.rel = 'stylesheet';
    l.href = 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0';
    document.head.appendChild(l);
  }

  const nav = document.getElementById('app-nav');
  if (!nav) return;

  // ── Stored user ───────────────────────────────────────────
  let currentUser = {};
  try { currentUser = JSON.parse(localStorage.getItem('user') || '{}'); } catch {}

  const uName   = currentUser.name   || 'Unknown';
  const uRole   = currentUser.role   || '';
  const uAvatar = currentUser.avatar || '';

  // ── Build avatar HTML ─────────────────────────────────────
  // Called by onerror on the avatar img — swaps it for the initial circle
  window._navAvatarFallback = function(img, initial, size) {
    var wrap = document.getElementById('nav-avatar-wrap');
    if (!wrap) return;
    var el = document.createElement('div');
    el.style.cssText = 'width:' + size + 'px;height:' + size + 'px;border-radius:50%;'
      + 'background:linear-gradient(135deg,#4e6040,#6b8a5c);'
      + 'display:flex;align-items:center;justify-content:center;'
      + 'font-size:' + Math.round(size * 0.38) + 'px;font-weight:700;color:#fff;'
      + 'border:2.5px solid rgba(255,255,255,0.6);box-shadow:0 2px 10px rgba(0,0,0,0.15);flex-shrink:0;';
    el.textContent = initial;
    wrap.innerHTML = '';
    wrap.appendChild(el);
  };

  function makeInitialCircle(initial, size) {
    var el = document.createElement('div');
    el.style.cssText = 'width:' + size + 'px;height:' + size + 'px;border-radius:50%;'
      + 'background:linear-gradient(135deg,#4e6040,#6b8a5c);'
      + 'display:flex;align-items:center;justify-content:center;'
      + 'font-size:' + Math.round(size * 0.38) + 'px;font-weight:700;color:#fff;'
      + 'border:2.5px solid rgba(255,255,255,0.6);box-shadow:0 2px 10px rgba(0,0,0,0.15);flex-shrink:0;';
    el.textContent = initial;
    return el;
  }

  function avatarHTML(src, name, size) {
    size = size || 46;
    var initial = (name || '?').charAt(0).toUpperCase();
    if (!src) {
      // Return outer HTML of the circle div
      return makeInitialCircle(initial, size).outerHTML;
    }
    // Only the <img> — no sibling fallback in the DOM
    // onerror calls the global helper function (no inline style strings)
    return '<img src="' + src + '?t=' + Date.now() + '" alt="' + name + '" '
      + 'style="width:' + size + 'px;height:' + size + 'px;border-radius:50%;object-fit:cover;'
      + 'border:2.5px solid rgba(255,255,255,0.6);box-shadow:0 2px 10px rgba(0,0,0,0.15);'
      + 'display:block;flex-shrink:0;" '
      + 'onerror="_navAvatarFallback(this,\'' + initial + '\',' + size + ');this.onerror=null;" />';
  }

  // ── Render sidebar ────────────────────────────────────────
  const isAdmin    = uRole === 'Admin';
  const isCustomer = uRole === 'Customer';
  const roleBadgeColor = isAdmin ? '#4e6040' : isCustomer ? '#2980b9' : '#8a7f72';
  const roleBadgeBg    = isAdmin ? 'rgba(78,96,64,0.12)' : isCustomer ? 'rgba(41,128,185,0.12)' : 'rgba(138,127,114,0.12)';

  nav.innerHTML =
    '<div class="nav__brand">'
    + '<img src="assets/Esperon Logo.png" alt="Esperon Logo" class="nav__brand-logo-img"'
    + ' style="width:40px;height:40px;border-radius:8px;object-fit:cover;" onerror="this.style.display=\'none\'" />'
    + '<div><div class="nav__brand-name">Esperon<br>Dairy Farm</div>'
    + '<div class="nav__brand-sub">Management System</div></div></div>'

    + '<div id="nav-profile-btn" title="Edit profile" style="'
    + 'display:flex;align-items:center;gap:11px;padding:10px 14px;margin:2px 10px 6px;'
    + 'border-radius:14px;cursor:pointer;transition:background 0.18s;user-select:none;"'
    + ' onmouseover="this.style.background=\'rgba(255,255,255,0.45)\'"'
    + ' onmouseout="this.style.background=\'transparent\'">'
    + '<div id="nav-avatar-wrap" style="flex-shrink:0;">' + avatarHTML(uAvatar, uName, 46) + '</div>'
    + '<div style="flex:1;min-width:0;">'
    + '<div id="nav-display-name" style="font-weight:700;font-size:0.92rem;color:var(--text);'
    + 'white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.3;">' + uName + '</div>'
    + '<div style="display:flex;align-items:center;gap:5px;margin-top:3px;">'
    + '<span style="display:inline-block;padding:2px 8px;border-radius:20px;font-size:0.65rem;font-weight:700;'
    + 'letter-spacing:0.06em;text-transform:uppercase;background:' + roleBadgeBg + ';color:' + roleBadgeColor + ';">'
    + uRole + '</span>'
    + '</div>'
    + '</div>'
    + '<span class="material-symbols-outlined" style="font-size:1.15rem;color:var(--muted);flex-shrink:0;opacity:0.65;">expand_more</span>'
    + '</div>'

    + '<div style="height:1px;background:var(--border-light);margin:0 14px 6px;"></div>'

    + '<span class="nav__section">Overview</span>'
    + '<a href="index.php" class="nav__link"><span class="nav__link-icon material-symbols-outlined">dashboard</span><span>Dashboard</span></a>'

    // Role-based nav sections
    + (isAdmin
      ? '<span class="nav__section">Records</span>'
        + '<a href="customers.php" class="nav__link"><span class="nav__link-icon material-symbols-outlined">people</span><span>Customers</span></a>'
        + '<a href="cows.php" class="nav__link"><span class="nav__link-icon material-symbols-outlined">pets</span><span>Cows</span></a>'
        + '<a href="workers.php" class="nav__link"><span class="nav__link-icon material-symbols-outlined">badge</span><span>Staff</span></a>'
        + '<a href="admins.php" class="nav__link"><span class="nav__link-icon material-symbols-outlined">admin_panel_settings</span><span>Admins</span></a>'
        + '<a href="orders.php" class="nav__link"><span class="nav__link-icon material-symbols-outlined">shopping_cart</span><span>Orders</span></a>'
        + '<a href="approvals.php" class="nav__link" id="nav-approvals-link"><span class="nav__link-icon material-symbols-outlined">how_to_reg</span><span>Approvals</span><span id="nav-approval-badge" style="display:none;margin-left:auto;background:var(--danger);color:#fff;border-radius:20px;font-size:0.6rem;font-weight:700;padding:1px 6px;min-width:16px;text-align:center;"></span></a>'
        + '<a href="online_staff.php" class="nav__link"><span class="nav__link-icon material-symbols-outlined">wifi</span><span>Online Staff</span></a>'
        + '<a href="inventory.php" class="nav__link"><span class="nav__link-icon material-symbols-outlined">inventory_2</span><span>Inventory</span></a>'
        + '<a href="reminders.php" class="nav__link"><span class="nav__link-icon material-symbols-outlined">alarm</span><span>Reminders</span></a>'
        + '<a href="notes.php" class="nav__link"><span class="nav__link-icon material-symbols-outlined">edit_note</span><span>Notes</span></a>'
        + '<a href="report.php" class="nav__link"><span class="nav__link-icon material-symbols-outlined">description</span><span>Staff Reports</span></a>'
      : isCustomer
      ? '<span class="nav__section">My Account</span>'
        + '<a href="dashboard_customer.php" class="nav__link"><span class="nav__link-icon material-symbols-outlined">home</span><span>My Dashboard</span></a>'
        + '<a href="dashboard_customer.php" class="nav__link"><span class="nav__link-icon material-symbols-outlined">receipt_long</span><span>My Orders</span></a>'
      : '<span class="nav__section">My Work</span>'
        + '<a href="orders.php" class="nav__link"><span class="nav__link-icon material-symbols-outlined">shopping_cart</span><span>Orders</span></a>'
        + '<a href="customers.php" class="nav__link"><span class="nav__link-icon material-symbols-outlined">people</span><span>Customers</span></a>'
        + '<a href="reminders.php" class="nav__link"><span class="nav__link-icon material-symbols-outlined">alarm</span><span>Reminders</span></a>'
        + '<a href="notes.php" class="nav__link"><span class="nav__link-icon material-symbols-outlined">edit_note</span><span>Notes</span></a>'
        + '<a href="report.php" class="nav__link"><span class="nav__link-icon material-symbols-outlined">description</span><span>My Reports</span></a>'
        + '<a href="inventory.php" class="nav__link"><span class="nav__link-icon material-symbols-outlined">inventory_2</span><span>Inventory</span></a>'
    )

    + '<span class="nav__section">Account</span>'
    + '<button id="logout-btn" class="nav__link nav__logout">'
    + '<span class="nav__link-icon material-symbols-outlined">logout</span><span>Logout</span></button>'

    + '<div class="nav__footer">Esperon Farm &copy; 2026</div>';

  UI.setActiveNav();

  // ── Logout ────────────────────────────────────────────────
  document.getElementById('logout-btn').addEventListener('click', async function () {
    try {
      await API.auth.logout();
      localStorage.removeItem('csrf_token');
      localStorage.removeItem('user');
      window.location.href = 'login.php';
    } catch (e) { UI.toast('Logout failed. Please try again.', 'error'); }
  });

  // ── Profile button ────────────────────────────────────────
  var profileBtn = document.getElementById('nav-profile-btn');

  // ══════════════════════════════════════════════════════════
  // PROFILE MODAL HTML
  // ══════════════════════════════════════════════════════════
  var modalEl = document.createElement('div');
  modalEl.id = 'profileModal';
  modalEl.style.cssText = 'display:none;position:fixed;inset:0;z-index:9999;'
    + 'background:rgba(42,31,21,0.5);backdrop-filter:blur(5px);-webkit-backdrop-filter:blur(5px);'
    + 'align-items:center;justify-content:center;padding:16px;';

  modalEl.innerHTML =
    '<div style="background:#faf6f0;border:1px solid rgba(255,255,255,0.8);border-radius:20px;'
    + 'box-shadow:0 16px 56px rgba(0,0,0,0.2);width:100%;max-width:400px;'
    + 'animation:pmSlideIn 0.25s cubic-bezier(.34,1.56,.64,1);font-family:\'Lato\',sans-serif;overflow:hidden;">'

    // Header
    + '<div style="display:flex;align-items:center;justify-content:space-between;'
    + 'padding:18px 22px 14px;background:linear-gradient(135deg,#4e6040,#6b8a5c);">'
    + '<div style="display:flex;align-items:center;gap:8px;">'
    + '<span class="material-symbols-outlined" style="color:rgba(255,255,255,0.9);font-size:1.2rem;">manage_accounts</span>'
    + '<span style="font-family:\'Playfair Display\',serif;font-size:1.05rem;font-weight:700;color:#fff;">Edit Profile</span>'
    + '</div>'
    + '<button id="pmClose" style="background:rgba(255,255,255,0.15);border:none;cursor:pointer;'
    + 'width:30px;height:30px;border-radius:50%;color:#fff;display:flex;align-items:center;'
    + 'justify-content:center;transition:background 0.15s;"'
    + ' onmouseover="this.style.background=\'rgba(255,255,255,0.3)\'"'
    + ' onmouseout="this.style.background=\'rgba(255,255,255,0.15)\'">'
    + '<span class="material-symbols-outlined" style="font-size:1.1rem;">close</span></button>'
    + '</div>'

    // Avatar section
    + '<div style="display:flex;flex-direction:column;align-items:center;padding:24px 22px 16px;'
    + 'background:linear-gradient(180deg,rgba(78,96,64,0.06) 0%,transparent 100%);border-bottom:1px solid #e8dfd2;">'
    + '<div id="pmAvatarRing" style="position:relative;width:96px;height:96px;cursor:pointer;margin-bottom:10px;" title="Click to change photo">'
    + '<div id="pmAvatarCircle" style="width:96px;height:96px;border-radius:50%;overflow:hidden;'
    + 'background:linear-gradient(135deg,#4e6040,#6b8a5c);display:flex;align-items:center;'
    + 'justify-content:center;border:3px solid #fff;box-shadow:0 4px 16px rgba(0,0,0,0.15);'
    + 'font-size:2.2rem;font-weight:700;color:#fff;"></div>'
    + '<div id="pmAvatarOverlay" style="position:absolute;inset:0;border-radius:50%;'
    + 'background:rgba(0,0,0,0);display:flex;align-items:center;justify-content:center;transition:background 0.2s;"'
    + ' onmouseover="this.style.background=\'rgba(0,0,0,0.35)\';document.getElementById(\'pmCameraIcon\').style.opacity=\'1\'"'
    + ' onmouseout="this.style.background=\'rgba(0,0,0,0)\';document.getElementById(\'pmCameraIcon\').style.opacity=\'0\'">'
    + '<span id="pmCameraIcon" class="material-symbols-outlined" style="color:#fff;font-size:1.6rem;opacity:0;transition:opacity 0.2s;">photo_camera</span>'
    + '</div>'
    + '<input type="file" id="pmFileInput" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;" />'
    + '</div>'
    + '<span style="font-size:0.75rem;color:#8a7f72;margin-bottom:4px;">Click avatar to change photo &nbsp;·&nbsp; JPG, PNG (max 2 MB)</span>'
    + '<div id="pmAvatarErr" style="display:none;color:#c0392b;font-size:0.75rem;margin-top:2px;"></div>'
    + '</div>'

    // Form
    + '<div style="padding:20px 22px;">'
    + '<div style="margin-bottom:14px;">'
    + '<label style="display:block;font-size:0.72rem;font-weight:700;color:#4a3f35;'
    + 'text-transform:uppercase;letter-spacing:0.07em;margin-bottom:6px;">'
    + 'Display Name <span style="color:#c0392b;">*</span></label>'
    + '<input id="pmName" type="text" placeholder="Your name" autocomplete="off" style="'
    + 'width:100%;padding:10px 14px;border:1.5px solid #e8dfd2;border-radius:10px;'
    + 'font-size:0.9rem;font-family:\'Lato\',sans-serif;color:#2a1f15;background:#fff;'
    + 'outline:none;box-sizing:border-box;transition:border-color 0.15s,box-shadow 0.15s;"'
    + ' onfocus="this.style.borderColor=\'#4e6040\';this.style.boxShadow=\'0 0 0 3px rgba(78,96,64,0.12)\'"'
    + ' onblur="this.style.borderColor=\'#e8dfd2\';this.style.boxShadow=\'none\'" />'
    + '<div id="pmNameErr" style="display:none;color:#c0392b;font-size:0.75rem;margin-top:5px;">'
    + '<span class="material-symbols-outlined" style="font-size:0.8rem;vertical-align:middle;">error</span>'
    + ' Name cannot be empty.</div>'
    + '</div>'
    + '<div>'
    + '<label style="display:block;font-size:0.72rem;font-weight:700;color:#4a3f35;'
    + 'text-transform:uppercase;letter-spacing:0.07em;margin-bottom:6px;">Role</label>'
    + '<div id="pmRole" style="padding:10px 14px;border:1.5px solid #e8dfd2;border-radius:10px;'
    + 'font-size:0.88rem;color:#8a7f72;background:#f5ede0;"></div>'
    + '</div></div>'

    // Footer
    + '<div style="display:flex;justify-content:flex-end;gap:10px;padding:0 22px 20px;">'
    + '<button id="pmCancel" style="padding:10px 20px;border:1.5px solid #d4c9b8;border-radius:10px;'
    + 'background:#fff;color:#4a3f35;font-family:\'Lato\',sans-serif;font-size:0.88rem;font-weight:600;'
    + 'cursor:pointer;transition:background 0.15s;"'
    + ' onmouseover="this.style.background=\'#f5ede0\'" onmouseout="this.style.background=\'#fff\'">Cancel</button>'
    + '<button id="pmSave" style="padding:10px 22px;border:none;border-radius:10px;'
    + 'background:linear-gradient(135deg,#4e6040,#6b8a5c);color:#fff;'
    + 'font-family:\'Lato\',sans-serif;font-size:0.88rem;font-weight:700;cursor:pointer;'
    + 'box-shadow:0 2px 8px rgba(78,96,64,0.3);display:flex;align-items:center;gap:6px;'
    + 'transition:opacity 0.15s,transform 0.15s;"'
    + ' onmouseover="this.style.opacity=\'0.88\';this.style.transform=\'translateY(-1px)\'"'
    + ' onmouseout="this.style.opacity=\'1\';this.style.transform=\'none\'">'
    + '<span class="material-symbols-outlined" style="font-size:1rem;">save</span> Save Changes</button>'
    + '</div>'
    + '</div>';

  document.body.appendChild(modalEl);

  // Keyframe styles
  var styleEl = document.createElement('style');
  styleEl.textContent = '@keyframes pmSlideIn{from{opacity:0;transform:translateY(-20px) scale(0.96)}to{opacity:1;transform:translateY(0) scale(1)}}'
    + '@keyframes pmSpin{to{transform:rotate(360deg)}}';
  document.head.appendChild(styleEl);

  // ── Element refs ──────────────────────────────────────────
  var modal          = document.getElementById('profileModal');
  var pmClose        = document.getElementById('pmClose');
  var pmCancel       = document.getElementById('pmCancel');
  var pmSave         = document.getElementById('pmSave');
  var pmName         = document.getElementById('pmName');
  var pmNameErr      = document.getElementById('pmNameErr');
  var pmRole         = document.getElementById('pmRole');
  var pmAvatarCircle = document.getElementById('pmAvatarCircle');
  var pmAvatarRing   = document.getElementById('pmAvatarRing');
  var pmFileInput    = document.getElementById('pmFileInput');
  var pmAvatarErr    = document.getElementById('pmAvatarErr');

  var pendingFile = null;

  // ── Set avatar preview in modal ───────────────────────────
  function setAvatarCircle(src, name) {
    if (src) {
      pmAvatarCircle.innerHTML = '<img src="' + src + '" style="width:100%;height:100%;object-fit:cover;display:block;" />';
    } else {
      pmAvatarCircle.innerHTML = '<span style="font-size:2.2rem;font-weight:700;color:#fff;">'
        + (name || '?').charAt(0).toUpperCase() + '</span>';
    }
  }

  // ── Open modal ────────────────────────────────────────────
  function openModal() {
    pendingFile = null;
    pmFileInput.value = '';
    pmNameErr.style.display   = 'none';
    pmAvatarErr.style.display = 'none';
    pmName.style.borderColor  = '#e8dfd2';

    // Prefill from localStorage immediately
    pmName.value           = currentUser.name  || '';
    pmRole.textContent     = currentUser.role  || '';
    setAvatarCircle(currentUser.avatar || '', currentUser.name || '');

    modal.style.display = 'flex';
    setTimeout(function () { pmName.focus(); }, 60);

    // Refresh from server in background
    fetch(BASE_API + '/profile.php', { credentials: 'include' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          var u = data.data;
          pmName.value       = u.Worker      || '';
          pmRole.textContent = u.Worker_Role || '';
          setAvatarCircle(u.Avatar || '', u.Worker || '');
        }
      })
      .catch(function () { /* keep localStorage values */ });
  }

  // ── Close modal ───────────────────────────────────────────
  function closeModal() {
    modal.style.display = 'none';
    pendingFile = null;
    pmFileInput.value = '';
  }

  // ── File picker ───────────────────────────────────────────
  pmFileInput.addEventListener('change', function () {
    var file = pmFileInput.files[0];
    if (!file) return;
    pmAvatarErr.style.display = 'none';

    var allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (allowed.indexOf(file.type) === -1) {
      pmAvatarErr.textContent = 'Only JPG, PNG, WebP or GIF images are allowed.';
      pmAvatarErr.style.display = 'block';
      pmFileInput.value = '';
      return;
    }
    if (file.size > 2 * 1024 * 1024) {
      pmAvatarErr.textContent = 'Image must be under 2 MB.';
      pmAvatarErr.style.display = 'block';
      pmFileInput.value = '';
      return;
    }
    pendingFile = file;
    var reader = new FileReader();
    reader.onload = function (e) {
      pmAvatarCircle.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;display:block;" />';
    };
    reader.readAsDataURL(file);
  });

  // ── Save ──────────────────────────────────────────────────
  pmSave.addEventListener('click', function () {
    pmNameErr.style.display   = 'none';
    pmAvatarErr.style.display = 'none';

    var newName = pmName.value.trim();
    if (!newName) {
      pmNameErr.style.display  = 'block';
      pmName.style.borderColor = '#c0392b';
      pmName.focus();
      return;
    }
    pmName.style.borderColor = '#e8dfd2';

    if (!pendingFile && newName === (currentUser.name || '')) {
      closeModal();
      return;
    }

    pmSave.disabled = true;
    pmSave.innerHTML = '<span class="material-symbols-outlined" style="font-size:1rem;animation:pmSpin 0.7s linear infinite;">progress_activity</span> Saving…';

    var fd = new FormData();
    fd.append('name', newName);
    if (pendingFile) fd.append('avatar', pendingFile);

    var csrf = localStorage.getItem('csrf_token') || '';
    fetch(BASE_API + '/profile.php', {
      method: 'POST',
      headers: { 'X-CSRF-Token': csrf },
      credentials: 'include',
      body: fd
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success) {
        var u = data.data;

        // Update localStorage
        var stored = {};
        try { stored = JSON.parse(localStorage.getItem('user') || '{}'); } catch {}
        stored.name = u.Worker;
        if (u.Avatar) stored.avatar = u.Avatar;
        localStorage.setItem('user', JSON.stringify(stored));
        currentUser = stored;

        // Update sidebar name
        var nameEl = document.getElementById('nav-display-name');
        if (nameEl) nameEl.textContent = u.Worker;

        // Update sidebar avatar
        var wrap = document.getElementById('nav-avatar-wrap');
        if (wrap) wrap.innerHTML = avatarHTML(u.Avatar || '', u.Worker, 46);

        // Update dashboard greeting
        var greet = document.getElementById('page-greeting');
        if (greet) {
          var h = new Date().getHours();
          var tod = h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
          greet.innerHTML = tod + ', ' + u.Worker + '! <span class="material-symbols-outlined" style="vertical-align:middle;font-size:1.5rem;">waving_hand</span>';
        }

        closeModal();
        UI.toast('Profile updated!', 'success');
      } else {
        UI.toast('Error: ' + (data.message || 'Could not save.'), 'error');
      }
    })
    .catch(function (err) {
      console.error('[Profile] save error:', err);
      UI.toast('Network error. Please try again.', 'error');
    })
    .finally(function () {
      pmSave.disabled = false;
      pmSave.innerHTML = '<span class="material-symbols-outlined" style="font-size:1rem;">save</span> Save Changes';
    });
  });

  // ── Wire events ───────────────────────────────────────────
  profileBtn.addEventListener('click', openModal);
  pmClose.addEventListener('click',    closeModal);
  pmCancel.addEventListener('click',   closeModal);
  pmAvatarRing.addEventListener('click', function () { pmFileInput.click(); });
  modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.style.display === 'flex') closeModal();
  });

})();
