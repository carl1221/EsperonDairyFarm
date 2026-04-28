// ============================================================
// js/nav.js
// Injects sidebar navigation + profile edit modal into every page.
// ============================================================

(function () {

  // ── Detect base path (works from any UI/*.php page) ──────
  // All pages live in UI/, so the API is always one level up.
  const BASE_API = '../dairy_farm_backend/api';
  const BASE_UI  = '';   // relative to current page (same folder)

  // ── Load Material Icons font ──────────────────────────────
  if (!document.getElementById('material-icons-font')) {
    const link = document.createElement('link');
    link.id   = 'material-icons-font';
    link.rel  = 'stylesheet';
    link.href = 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0';
    document.head.appendChild(link);
  }

  const nav = document.getElementById('app-nav');
  if (!nav) return;

  // ── Read stored user ──────────────────────────────────────
  let currentUser = {};
  try { currentUser = JSON.parse(localStorage.getItem('user') || '{}'); } catch {}

  const displayName   = currentUser.name   || 'Unknown User';
  const displayRole   = currentUser.role   || '';
  const displayEmail  = currentUser.email  || '';
  const displayAvatar = currentUser.avatar || '';

  const roleBadgeClass = displayRole === 'Admin' ? 'badge--green' : 'badge--muted';

  function buildAvatarHTML(src, name, size = 44) {
    if (src) {
      return `<img src="${src}?t=${Date.now()}" alt="${name}" style="
        width:${size}px; height:${size}px; border-radius:50%; object-fit:cover;
        border:2px solid rgba(255,255,255,0.5); box-shadow:0 2px 8px rgba(0,0,0,0.12);
        display:block;
      " onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />
      <div style="display:none; width:${size}px; height:${size}px; border-radius:50%;
        background:linear-gradient(135deg,#4e6040,#6b8a5c);
        align-items:center; justify-content:center;
        font-size:${Math.round(size*0.4)}px; font-weight:700; color:#fff;">
        ${name.charAt(0).toUpperCase()}
      </div>`;
    }
    return `<div class="nav__user-avatar">${name.charAt(0).toUpperCase()}</div>`;
  }

  // ── Render sidebar ────────────────────────────────────────
  nav.innerHTML = `
    <!-- Brand -->
    <div class="nav__brand">
      <img src="${BASE_UI}assets/Esperon Logo.png" alt="Esperon Logo"
        class="nav__brand-logo-img"
        style="width:40px;height:40px;border-radius:8px;object-fit:cover;"
        onerror="this.style.display='none'" />
      <div>
        <div class="nav__brand-name">Esperon<br>Dairy Farm</div>
        <div class="nav__brand-sub">Management System</div>
      </div>
    </div>

    <!-- User card — click to open profile modal -->
    <div class="nav__user" id="nav-profile-btn" title="Edit profile"
      style="cursor:pointer; transition:background 0.2s; user-select:none;">
      <div id="nav-avatar-wrap" style="flex-shrink:0; position:relative;">
        ${buildAvatarHTML(displayAvatar, displayName)}
      </div>
      <div class="nav__user-info" style="flex:1; min-width:0;">
        <div class="nav__user-name" id="nav-display-name">${displayName}</div>
        <div class="nav__user-meta">
          <span class="badge ${roleBadgeClass}" style="font-size:.68rem;">${displayRole}</span>
        </div>
        ${displayEmail ? `<div class="nav__user-email" title="${displayEmail}">${displayEmail}</div>` : ''}
      </div>
      <span class="material-symbols-outlined"
        style="font-size:1rem;color:var(--muted);margin-left:auto;flex-shrink:0;opacity:0.7;">
        edit
      </span>
    </div>

    <!-- Nav links -->
    <span class="nav__section">Overview</span>
    <a href="${BASE_UI}index.php" class="nav__link">
      <span class="nav__link-icon material-symbols-outlined">dashboard</span><span>Dashboard</span>
    </a>

    <span class="nav__section">Records</span>
    <a href="${BASE_UI}customers.php" class="nav__link">
      <span class="nav__link-icon material-symbols-outlined">people</span><span>Customers</span>
    </a>
    <a href="${BASE_UI}cows.php" class="nav__link">
      <span class="nav__link-icon material-symbols-outlined">pets</span><span>Cows</span>
    </a>
    <a href="${BASE_UI}workers.php" class="nav__link">
      <span class="nav__link-icon material-symbols-outlined">badge</span><span>Workers</span>
    </a>
    <a href="${BASE_UI}orders.php" class="nav__link">
      <span class="nav__link-icon material-symbols-outlined">shopping_cart</span><span>Orders</span>
    </a>

    <span class="nav__section">Account</span>
    <button id="logout-btn" class="nav__link nav__logout">
      <span class="nav__link-icon material-symbols-outlined">logout</span><span>Logout</span>
    </button>

    <div class="nav__footer">Esperon Farm © 2026</div>
  `;

  UI.setActiveNav();

  // ── Logout ────────────────────────────────────────────────
  document.getElementById('logout-btn').addEventListener('click', async () => {
    try {
      await API.auth.logout();
      localStorage.removeItem('csrf_token');
      localStorage.removeItem('user');
      window.location.href = 'login.php';
    } catch { UI.toast('Logout failed. Please try again.', 'error'); }
  });

  // ── Hover effect on user card ─────────────────────────────
  const profileBtn = document.getElementById('nav-profile-btn');
  profileBtn.addEventListener('mouseenter', () => profileBtn.style.background = 'rgba(255,255,255,0.45)');
  profileBtn.addEventListener('mouseleave', () => profileBtn.style.background = '');

  // ══════════════════════════════════════════════════════════
  // PROFILE MODAL
  // ══════════════════════════════════════════════════════════
  document.body.insertAdjacentHTML('beforeend', `
    <div id="profileModal" style="
      display:none; position:fixed; inset:0; z-index:9999;
      background:rgba(42,31,21,0.5); backdrop-filter:blur(5px);
      -webkit-backdrop-filter:blur(5px);
      align-items:center; justify-content:center; padding:16px;
    ">
      <div id="profileModalBox" style="
        background:#faf6f0; border:1px solid rgba(255,255,255,0.8);
        border-radius:20px; box-shadow:0 16px 56px rgba(0,0,0,0.2);
        width:100%; max-width:400px;
        animation:pmSlideIn 0.25s cubic-bezier(.34,1.56,.64,1);
        font-family:'Lato',sans-serif; overflow:hidden;
      ">

        <!-- Modal header -->
        <div style="
          display:flex; align-items:center; justify-content:space-between;
          padding:18px 22px 14px;
          background:linear-gradient(135deg,#4e6040,#6b8a5c);
        ">
          <div style="display:flex; align-items:center; gap:8px;">
            <span class="material-symbols-outlined" style="color:rgba(255,255,255,0.9);font-size:1.2rem;">manage_accounts</span>
            <span style="font-family:'Playfair Display',serif; font-size:1.05rem; font-weight:700; color:#fff;">Edit Profile</span>
          </div>
          <button id="pmClose" style="
            background:rgba(255,255,255,0.15); border:none; cursor:pointer;
            width:30px; height:30px; border-radius:50%; color:#fff;
            display:flex; align-items:center; justify-content:center;
            transition:background 0.15s;
          "
          onmouseover="this.style.background='rgba(255,255,255,0.3)'"
          onmouseout="this.style.background='rgba(255,255,255,0.15)'">
            <span class="material-symbols-outlined" style="font-size:1.1rem;">close</span>
          </button>
        </div>

        <!-- Avatar section -->
        <div style="
          display:flex; flex-direction:column; align-items:center;
          padding:24px 22px 16px;
          background:linear-gradient(180deg, rgba(78,96,64,0.06) 0%, transparent 100%);
          border-bottom:1px solid #e8dfd2;
        ">
          <!-- Clickable avatar circle -->
          <div id="pmAvatarRing" style="
            position:relative; width:96px; height:96px;
            cursor:pointer; margin-bottom:10px;
          " title="Click to change photo">
            <!-- Avatar display -->
            <div id="pmAvatarCircle" style="
              width:96px; height:96px; border-radius:50%; overflow:hidden;
              background:linear-gradient(135deg,#4e6040,#6b8a5c);
              display:flex; align-items:center; justify-content:center;
              border:3px solid #fff; box-shadow:0 4px 16px rgba(0,0,0,0.15);
              font-size:2.2rem; font-weight:700; color:#fff;
            "></div>
            <!-- Camera overlay -->
            <div style="
              position:absolute; inset:0; border-radius:50%;
              background:rgba(0,0,0,0); display:flex; align-items:center;
              justify-content:center; transition:background 0.2s;
            " id="pmAvatarOverlay"
            onmouseover="this.style.background='rgba(0,0,0,0.35)';document.getElementById('pmCameraIcon').style.opacity='1'"
            onmouseout="this.style.background='rgba(0,0,0,0)';document.getElementById('pmCameraIcon').style.opacity='0'">
              <span id="pmCameraIcon" class="material-symbols-outlined" style="
                color:#fff; font-size:1.6rem; opacity:0; transition:opacity 0.2s;
              ">photo_camera</span>
            </div>
            <!-- Hidden file input -->
            <input type="file" id="pmFileInput" accept="image/jpeg,image/png,image/webp,image/gif"
              style="display:none;" />
          </div>

          <span style="font-size:0.75rem; color:#8a7f72; margin-bottom:4px;">
            Click avatar to change photo &nbsp;·&nbsp; JPG, PNG, WebP (max 2 MB)
          </span>
          <div id="pmAvatarErr" style="display:none; color:#c0392b; font-size:0.75rem; margin-top:2px;"></div>
        </div>

        <!-- Form fields -->
        <div style="padding:20px 22px;">

          <!-- Name -->
          <div style="margin-bottom:14px;">
            <label style="
              display:block; font-size:0.72rem; font-weight:700; color:#4a3f35;
              text-transform:uppercase; letter-spacing:0.07em; margin-bottom:6px;
            ">Display Name <span style="color:#c0392b;">*</span></label>
            <input id="pmName" type="text" placeholder="Your name" autocomplete="off" style="
              width:100%; padding:10px 14px; border:1.5px solid #e8dfd2;
              border-radius:10px; font-size:0.9rem; font-family:'Lato',sans-serif;
              color:#2a1f15; background:#fff; outline:none; box-sizing:border-box;
              transition:border-color 0.15s, box-shadow 0.15s;
            "
            onfocus="this.style.borderColor='#4e6040';this.style.boxShadow='0 0 0 3px rgba(78,96,64,0.12)'"
            onblur="this.style.borderColor='#e8dfd2';this.style.boxShadow='none'" />
            <div id="pmNameErr" style="display:none; color:#c0392b; font-size:0.75rem; margin-top:5px;">
              <span class="material-symbols-outlined" style="font-size:0.8rem;vertical-align:middle;">error</span>
              Name cannot be empty.
            </div>
          </div>

          <!-- Role (read-only) -->
          <div>
            <label style="
              display:block; font-size:0.72rem; font-weight:700; color:#4a3f35;
              text-transform:uppercase; letter-spacing:0.07em; margin-bottom:6px;
            ">Role</label>
            <div id="pmRole" style="
              padding:10px 14px; border:1.5px solid #e8dfd2; border-radius:10px;
              font-size:0.88rem; color:#8a7f72; background:#f5ede0;
            "></div>
          </div>

        </div>

        <!-- Footer buttons -->
        <div style="
          display:flex; justify-content:flex-end; gap:10px;
          padding:0 22px 20px;
        ">
          <button id="pmCancel" style="
            padding:10px 20px; border:1.5px solid #d4c9b8; border-radius:10px;
            background:#fff; color:#4a3f35; font-family:'Lato',sans-serif;
            font-size:0.88rem; font-weight:600; cursor:pointer;
            transition:background 0.15s, border-color 0.15s;
          "
          onmouseover="this.style.background='#f5ede0'"
          onmouseout="this.style.background='#fff'">Cancel</button>

          <button id="pmSave" style="
            padding:10px 22px; border:none; border-radius:10px;
            background:linear-gradient(135deg,#4e6040,#6b8a5c); color:#fff;
            font-family:'Lato',sans-serif; font-size:0.88rem; font-weight:700;
            cursor:pointer; box-shadow:0 2px 8px rgba(78,96,64,0.3);
            display:flex; align-items:center; gap:6px;
            transition:opacity 0.15s, transform 0.15s;
          "
          onmouseover="this.style.opacity='0.88';this.style.transform='translateY(-1px)'"
          onmouseout="this.style.opacity='1';this.style.transform='none'">
            <span class="material-symbols-outlined" style="font-size:1rem;">save</span>
            Save Changes
          </button>
        </div>

      </div>
    </div>

    <style>
      @keyframes pmSlideIn {
        from { opacity:0; transform:translateY(-20px) scale(0.96); }
        to   { opacity:1; transform:translateY(0)     scale(1);    }
      }
      @keyframes pmSpin {
        to { transform:rotate(360deg); }
      }
    </style>
  `);

  // ── Element refs ──────────────────────────────────────────
  const modal       = document.getElementById('profileModal');
  const pmClose     = document.getElementById('pmClose');
  const pmCancel    = document.getElementById('pmCancel');
  const pmSave      = document.getElementById('pmSave');
  const pmName      = document.getElementById('pmName');
  const pmNameErr   = document.getElementById('pmNameErr');
  const pmRole      = document.getElementById('pmRole');
  const pmAvatarCircle = document.getElementById('pmAvatarCircle');
  const pmAvatarRing   = document.getElementById('pmAvatarRing');
  const pmFileInput    = document.getElementById('pmFileInput');
  const pmAvatarErr    = document.getElementById('pmAvatarErr');

  let pendingFile = null;

  // ── Avatar circle renderer ────────────────────────────────
  function setAvatarCircle(src, name) {
    if (src) {
      pmAvatarCircle.innerHTML = `<img src="${src}" style="width:100%;height:100%;object-fit:cover;display:block;" />`;
    } else {
      pmAvatarCircle.innerHTML = `<span style="font-size:2.2rem;font-weight:700;color:#fff;">${(name||'?').charAt(0).toUpperCase()}</span>`;
    }
  }

  // ── Open modal ────────────────────────────────────────────
  async function openModal() {
    pendingFile = null;
    pmFileInput.value = '';
    pmNameErr.style.display   = 'none';
    pmAvatarErr.style.display = 'none';
    pmName.style.borderColor  = '#e8dfd2';

    // Prefill from localStorage immediately (fast)
    pmName.value = currentUser.name || '';
    pmRole.textContent = currentUser.role || '';
    setAvatarCircle(currentUser.avatar || '', currentUser.name || '');

    modal.style.display = 'flex';
    setTimeout(() => pmName.focus(), 60);

    // Then refresh from server in background
    try {
      const res  = await fetch(`${BASE_API}/profile.php`, { credentials: 'include' });
      const data = await res.json();
      if (data.success) {
        const u = data.data;
        pmName.value = u.Worker || '';
        pmRole.textContent = u.Worker_Role || '';
        setAvatarCircle(u.Avatar || '', u.Worker || '');
      }
    } catch { /* keep localStorage values */ }
  }

  function closeModal() {
    modal.style.display = 'none';
    pendingFile = null;
    pmFileInput.value = '';
  }

  // ── Click avatar circle → trigger file input ──────────────
  pmAvatarRing.addEventListener('click', () => pmFileInput.click());

  // ── File selected ─────────────────────────────────────────
  pmFileInput.addEventListener('change', () => {
    const file = pmFileInput.files[0];
    if (!file) return;

    pmAvatarErr.style.display = 'none';

    const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!allowed.includes(file.type)) {
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
    const reader = new FileReader();
    reader.onload = e => {
      pmAvatarCircle.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;display:block;" />`;
    };
    reader.readAsDataURL(file);
  });

  // ── Save ──────────────────────────────────────────────────
  pmSave.addEventListener('click', async () => {
    pmNameErr.style.display   = 'none';
    pmAvatarErr.style.display = 'none';

    const newName = pmName.value.trim();
    if (!newName) {
      pmNameErr.style.display = 'block';
      pmName.style.borderColor = '#c0392b';
      pmName.focus();
      return;
    }
    pmName.style.borderColor = '#e8dfd2';

    // Nothing changed
    if (!pendingFile && newName === (currentUser.name || '')) {
      closeModal();
      return;
    }

    // Loading state
    pmSave.disabled = true;
    pmSave.innerHTML = `
      <span class="material-symbols-outlined" style="font-size:1rem;animation:pmSpin 0.7s linear infinite;">progress_activity</span>
      Saving…`;

    try {
      const fd = new FormData();
      fd.append('name', newName);
      if (pendingFile) fd.append('avatar', pendingFile);

      const csrf = localStorage.getItem('csrf_token') || '';
      const res  = await fetch(`${BASE_API}/profile.php`, {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrf },
        credentials: 'include',
        body: fd,
      });
      const data = await res.json();

      if (data.success) {
        const u = data.data;

        // ── Update localStorage ───────────────────────────
        const stored = JSON.parse(localStorage.getItem('user') || '{}');
        stored.name = u.Worker;
        if (u.Avatar) stored.avatar = u.Avatar;
        localStorage.setItem('user', JSON.stringify(stored));
        currentUser = stored;

        // ── Update sidebar name ───────────────────────────
        const nameEl = document.getElementById('nav-display-name');
        if (nameEl) nameEl.textContent = u.Worker;

        // ── Update sidebar avatar ─────────────────────────
        const wrap = document.getElementById('nav-avatar-wrap');
        if (wrap) wrap.innerHTML = buildAvatarHTML(u.Avatar || '', u.Worker);

        // ── Update dashboard greeting if on index ─────────
        const greet = document.getElementById('page-greeting');
        if (greet) {
          const h = new Date().getHours();
          const tod = h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
          greet.innerHTML = `${tod}, ${u.Worker}! <span class="material-symbols-outlined" style="vertical-align:middle;font-size:1.5rem;">waving_hand</span>`;
        }

        closeModal();
        UI.toast('Profile updated!', 'success');
      } else {
        UI.toast('Error: ' + (data.message || 'Could not save.'), 'error');
      }
    } catch (err) {
      console.error('[Profile] save error:', err);
      UI.toast('Network error. Please try again.', 'error');
    } finally {
      pmSave.disabled = false;
      pmSave.innerHTML = `<span class="material-symbols-outlined" style="font-size:1rem;">save</span> Save Changes`;
    }
  });

  // ── Wire close actions ────────────────────────────────────
  profileBtn.addEventListener('click', openModal);
  pmClose.addEventListener('click',  closeModal);
  pmCancel.addEventListener('click', closeModal);
  modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && modal.style.display === 'flex') closeModal();
  });

})();
