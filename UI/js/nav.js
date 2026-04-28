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

  const displayName  = currentUser.name   || 'Unknown User';
  const displayRole  = currentUser.role   || '';
  const displayEmail = currentUser.email  || '';
  const displayAvatar = currentUser.avatar || '';

  // Role badge colour
  const roleBadgeClass = displayRole === 'Admin' ? 'badge--green' : 'badge--muted';

  // Avatar: photo or initial
  const avatarHTML = displayAvatar
    ? `<img src="${displayAvatar}" alt="${displayName}" style="
        width:44px; height:44px; border-radius:50%; object-fit:cover;
        border:2px solid rgba(255,255,255,0.5); box-shadow:0 2px 8px rgba(0,0,0,0.12);
      " />`
    : `<div class="nav__user-avatar" id="nav-avatar-initial">${displayName.charAt(0).toUpperCase()}</div>`;

  // ── Render nav HTML ─────────────────────────────────────
  nav.innerHTML = `

    <!-- Brand -->
    <div class="nav__brand">
      <img src="assets/Esperon Logo.png" alt="Esperon Logo" class="nav__brand-logo-img" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;" />
      <div class="nav__brand-name">Esperon<br>Dairy Farm</div>
      <div class="nav__brand-sub">Management System</div>
    </div>

    <!-- Logged-in user card — click to edit profile -->
    <div class="nav__user" id="nav-profile-btn" title="Edit profile" style="cursor:pointer; transition: background 0.2s;">
      <div id="nav-avatar-wrap" style="flex-shrink:0;">
        ${avatarHTML}
      </div>
      <div class="nav__user-info">
        <div class="nav__user-name" id="nav-display-name">${displayName}</div>
        <div class="nav__user-meta">
          <span class="badge ${roleBadgeClass}" style="font-size:.68rem;">${displayRole}</span>
        </div>
        ${displayEmail
          ? `<div class="nav__user-email" title="${displayEmail}">${displayEmail}</div>`
          : ''}
      </div>
      <span class="material-symbols-outlined" style="font-size:1rem; color:var(--muted); margin-left:auto; flex-shrink:0;">edit</span>
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
      localStorage.removeItem('csrf_token');
      localStorage.removeItem('user');
      window.location.href = 'login.php';
    } catch (e) {
      UI.toast('Logout failed. Please try again.', 'error');
    }
  });

  // ── Profile hover effect ────────────────────────────────
  const profileBtn = document.getElementById('nav-profile-btn');
  profileBtn.addEventListener('mouseenter', () => {
    profileBtn.style.background = 'rgba(255,255,255,0.45)';
  });
  profileBtn.addEventListener('mouseleave', () => {
    profileBtn.style.background = '';
  });

  // ── Profile Modal ───────────────────────────────────────
  const modalHTML = `
  <div id="profileModal" style="
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(42,31,21,0.45); backdrop-filter:blur(4px);
    -webkit-backdrop-filter:blur(4px);
    align-items:center; justify-content:center;
  ">
    <div style="
      background:rgba(255,255,255,0.95); backdrop-filter:blur(20px);
      -webkit-backdrop-filter:blur(20px);
      border:1px solid rgba(255,255,255,0.7);
      border-radius:20px; box-shadow:0 12px 48px rgba(0,0,0,0.18);
      width:100%; max-width:420px; margin:16px;
      animation:profileSlideIn 0.25s ease;
      font-family:'Lato',sans-serif;
    ">
      <!-- Header -->
      <div style="
        display:flex; align-items:center; justify-content:space-between;
        padding:20px 24px 16px; border-bottom:1px solid rgba(212,201,184,0.4);
      ">
        <div style="display:flex; align-items:center; gap:10px;">
          <span class="material-symbols-outlined" style="color:#4e6040; font-size:1.3rem;">manage_accounts</span>
          <span style="font-family:'Playfair Display',serif; font-size:1.1rem; font-weight:700; color:#2a1f15;">Edit Profile</span>
        </div>
        <button id="profileModalClose" style="
          background:none; border:none; cursor:pointer; color:#8a7f72;
          width:32px; height:32px; border-radius:50%; display:flex;
          align-items:center; justify-content:center;
          transition:background 0.15s, color 0.15s;
        "
        onmouseover="this.style.background='#fdf0ef';this.style.color='#c0392b'"
        onmouseout="this.style.background='none';this.style.color='#8a7f72'">
          <span class="material-symbols-outlined" style="font-size:1.2rem;">close</span>
        </button>
      </div>

      <!-- Body -->
      <div style="padding:24px;">

        <!-- Avatar upload -->
        <div style="display:flex; flex-direction:column; align-items:center; margin-bottom:24px;">
          <div id="pm_avatar_wrap" style="position:relative; width:88px; height:88px; margin-bottom:12px;">
            <div id="pm_avatar_preview" style="
              width:88px; height:88px; border-radius:50%;
              background:linear-gradient(135deg,#4e6040,#6b8a5c);
              display:flex; align-items:center; justify-content:center;
              font-size:2rem; font-weight:700; color:#fff;
              border:3px solid rgba(255,255,255,0.7);
              box-shadow:0 4px 16px rgba(0,0,0,0.12);
              overflow:hidden;
            "></div>
            <label for="pm_avatar_input" style="
              position:absolute; bottom:0; right:0;
              width:28px; height:28px; border-radius:50%;
              background:#4e6040; color:#fff; cursor:pointer;
              display:flex; align-items:center; justify-content:center;
              box-shadow:0 2px 8px rgba(0,0,0,0.2);
              border:2px solid #fff;
              transition:background 0.15s;
            "
            onmouseover="this.style.background='#2d3b22'"
            onmouseout="this.style.background='#4e6040'">
              <span class="material-symbols-outlined" style="font-size:0.85rem;">photo_camera</span>
            </label>
            <input type="file" id="pm_avatar_input" accept="image/*" style="display:none;" />
          </div>
          <span style="font-size:0.75rem; color:#8a7f72;">Click the camera icon to change photo</span>
          <div id="pm_avatar_err" style="display:none; color:#c0392b; font-size:0.75rem; margin-top:4px;"></div>
        </div>

        <!-- Name -->
        <div style="margin-bottom:16px;">
          <label style="display:block; font-size:0.78rem; font-weight:700; color:#4a3f35; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:6px;">
            Display Name <span style="color:#c0392b;">*</span>
          </label>
          <input id="pm_name" type="text" placeholder="Your name"
            style="
              width:100%; padding:10px 14px; border:1.5px solid #e8dfd2;
              border-radius:10px; font-size:0.9rem; font-family:'Lato',sans-serif;
              color:#2a1f15; background:rgba(255,255,255,0.7); outline:none;
              transition:border-color 0.15s, box-shadow 0.15s;
            "
            onfocus="this.style.borderColor='#4e6040';this.style.boxShadow='0 0 0 3px rgba(78,96,64,0.12)'"
            onblur="this.style.borderColor='#e8dfd2';this.style.boxShadow='none'"
          />
          <div id="pm_name_err" style="display:none; color:#c0392b; font-size:0.75rem; margin-top:4px;">
            <span class="material-symbols-outlined" style="font-size:0.85rem; vertical-align:middle;">error</span>
            Name is required.
          </div>
        </div>

        <!-- Role (read-only) -->
        <div style="margin-bottom:8px;">
          <label style="display:block; font-size:0.78rem; font-weight:700; color:#4a3f35; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:6px;">
            Role
          </label>
          <div id="pm_role_display" style="
            padding:10px 14px; border:1.5px solid #e8dfd2; border-radius:10px;
            font-size:0.9rem; color:#8a7f72; background:rgba(232,217,197,0.3);
          "></div>
        </div>

      </div>

      <!-- Footer -->
      <div style="display:flex; justify-content:flex-end; gap:10px; padding:0 24px 20px;">
        <button id="profileModalCancel" style="
          padding:10px 20px; border:1.5px solid #d4c9b8; border-radius:10px;
          background:rgba(255,255,255,0.5); color:#4a3f35; font-family:'Lato',sans-serif;
          font-size:0.88rem; font-weight:600; cursor:pointer;
          transition:background 0.15s;
        "
        onmouseover="this.style.background='rgba(255,255,255,0.8)'"
        onmouseout="this.style.background='rgba(255,255,255,0.5)'">
          Cancel
        </button>
        <button id="profileModalSubmit" style="
          padding:10px 24px; border:none; border-radius:10px;
          background:linear-gradient(135deg,#4e6040,#6b8a5c); color:#fff;
          font-family:'Lato',sans-serif; font-size:0.88rem; font-weight:700;
          cursor:pointer; box-shadow:0 2px 8px rgba(78,96,64,0.25);
          transition:opacity 0.15s, transform 0.15s;
          display:flex; align-items:center; gap:6px;
        "
        onmouseover="this.style.opacity='0.9';this.style.transform='translateY(-1px)'"
        onmouseout="this.style.opacity='1';this.style.transform='none'">
          <span class="material-symbols-outlined" style="font-size:1rem;">save</span>
          Save Changes
        </button>
      </div>
    </div>
  </div>

  <style>
    @keyframes profileSlideIn {
      from { opacity:0; transform:translateY(-16px) scale(0.97); }
      to   { opacity:1; transform:translateY(0) scale(1); }
    }
  </style>
  `;

  document.body.insertAdjacentHTML('beforeend', modalHTML);

  const modal        = document.getElementById('profileModal');
  const closeBtn     = document.getElementById('profileModalClose');
  const cancelBtn    = document.getElementById('profileModalCancel');
  const submitBtn    = document.getElementById('profileModalSubmit');
  const nameInput    = document.getElementById('pm_name');
  const nameErr      = document.getElementById('pm_name_err');
  const avatarErr    = document.getElementById('pm_avatar_err');
  const avatarInput  = document.getElementById('pm_avatar_input');
  const avatarPreview = document.getElementById('pm_avatar_preview');
  const roleDisplay  = document.getElementById('pm_role_display');

  let pendingAvatarFile = null;

  // ── Render avatar preview ─────────────────────────────
  function renderAvatarPreview(src, name) {
    if (src) {
      avatarPreview.innerHTML = `<img src="${src}" style="width:100%;height:100%;object-fit:cover;" />`;
    } else {
      avatarPreview.innerHTML = `<span style="font-size:2rem;font-weight:700;color:#fff;">${(name||'?').charAt(0).toUpperCase()}</span>`;
    }
  }

  // ── Open modal ────────────────────────────────────────
  async function openModal() {
    pendingAvatarFile = null;
    nameErr.style.display   = 'none';
    avatarErr.style.display = 'none';

    // Load fresh data from server
    try {
      const res  = await fetch('../dairy_farm_backend/api/profile.php', { credentials: 'include' });
      const data = await res.json();
      if (data.success) {
        const u = data.data;
        nameInput.value = u.Worker || '';
        roleDisplay.textContent = u.Worker_Role || '';
        renderAvatarPreview(u.Avatar || '', u.Worker);
      }
    } catch (e) {
      // Fall back to localStorage
      const u = currentUser;
      nameInput.value = u.name || '';
      roleDisplay.textContent = u.role || '';
      renderAvatarPreview(u.avatar || '', u.name);
    }

    modal.style.display = 'flex';
    setTimeout(() => nameInput.focus(), 50);
  }

  function closeModal() {
    modal.style.display = 'none';
    pendingAvatarFile = null;
  }

  // ── Avatar file picker ────────────────────────────────
  avatarInput.addEventListener('change', () => {
    const file = avatarInput.files[0];
    if (!file) return;

    avatarErr.style.display = 'none';

    if (file.size > 2 * 1024 * 1024) {
      avatarErr.textContent = 'Image must be under 2 MB.';
      avatarErr.style.display = 'block';
      avatarInput.value = '';
      return;
    }
    if (!file.type.startsWith('image/')) {
      avatarErr.textContent = 'Please select a valid image file.';
      avatarErr.style.display = 'block';
      avatarInput.value = '';
      return;
    }

    pendingAvatarFile = file;
    const reader = new FileReader();
    reader.onload = e => {
      avatarPreview.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;" />`;
    };
    reader.readAsDataURL(file);
  });

  // ── Submit ────────────────────────────────────────────
  submitBtn.addEventListener('click', async () => {
    nameErr.style.display   = 'none';
    avatarErr.style.display = 'none';

    const newName = nameInput.value.trim();
    if (!newName) {
      nameErr.style.display = 'block';
      nameInput.style.borderColor = '#c0392b';
      return;
    }
    nameInput.style.borderColor = '#e8dfd2';

    if (!pendingAvatarFile && newName === currentUser.name) {
      closeModal();
      return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size:1rem;animation:spin 0.8s linear infinite">progress_activity</span> Saving…';

    try {
      const formData = new FormData();
      formData.append('name', newName);
      if (pendingAvatarFile) {
        formData.append('avatar', pendingAvatarFile);
      }

      const csrfToken = localStorage.getItem('csrf_token');
      const res  = await fetch('../dairy_farm_backend/api/profile.php', {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrfToken },
        credentials: 'include',
        body: formData,
      });
      const data = await res.json();

      if (data.success) {
        const updated = data.data;

        // Update localStorage
        const stored = JSON.parse(localStorage.getItem('user') || '{}');
        stored.name   = updated.Worker;
        if (updated.Avatar) stored.avatar = updated.Avatar;
        localStorage.setItem('user', JSON.stringify(stored));

        // Update nav in-place without full reload
        const nameEl = document.getElementById('nav-display-name');
        if (nameEl) nameEl.textContent = updated.Worker;

        const avatarWrap = document.getElementById('nav-avatar-wrap');
        if (avatarWrap) {
          if (updated.Avatar) {
            avatarWrap.innerHTML = `<img src="${updated.Avatar}?t=${Date.now()}" alt="${updated.Worker}" style="
              width:44px; height:44px; border-radius:50%; object-fit:cover;
              border:2px solid rgba(255,255,255,0.5); box-shadow:0 2px 8px rgba(0,0,0,0.12);
            " />`;
          } else {
            avatarWrap.innerHTML = `<div class="nav__user-avatar">${updated.Worker.charAt(0).toUpperCase()}</div>`;
          }
        }

        // Update greeting on dashboard if present
        const greetingEl = document.getElementById('page-greeting');
        if (greetingEl) {
          const hour = new Date().getHours();
          const timeOfDay = hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
          greetingEl.innerHTML = `${timeOfDay}, ${updated.Worker}! <span class="material-symbols-outlined" style="vertical-align:middle;font-size:1.5rem;">waving_hand</span>`;
        }

        closeModal();
        UI.toast('Profile updated successfully!', 'success');
      } else {
        UI.toast('Failed to update: ' + data.message, 'error');
      }
    } catch (e) {
      console.error('Profile update error:', e);
      UI.toast('Network error. Please try again.', 'error');
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size:1rem;">save</span> Save Changes';
    }
  });

  // ── Wire up open/close ────────────────────────────────
  profileBtn.addEventListener('click', openModal);
  closeBtn.addEventListener('click', closeModal);
  cancelBtn.addEventListener('click', closeModal);
  modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && modal.style.display === 'flex') closeModal(); });

})();
