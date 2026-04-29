<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign In — Esperon Dairy Farm</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link rel="stylesheet" href="css/style.css" />
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <style>
    body {
      display: flex; align-items: center; justify-content: center;
      min-height: 100vh;
      background: url('assets/bg.png') no-repeat center center fixed;
      background-size: cover; padding: 2rem 0;
    }
    .auth-card {
      width: 100%; max-width: 440px;
      background: rgba(255,255,255,0.42); backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border: 1px solid rgba(255,255,255,0.6);
      border-radius: 24px; box-shadow: 0 12px 48px rgba(0,0,0,0.15);
      padding: 2.5rem 2rem;
    }
    .auth-logo { text-align: center; margin-bottom: 1.5rem; }
    .auth-logo img { max-width: 90px; border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
    .auth-logo-name { font-family: 'Playfair Display', serif; font-size: 1.3rem; color: #7a1f2e; font-weight: 700; margin-top: 0.5rem; }
    .auth-logo-sub  { font-size: 0.68rem; color: #5a4f45; text-transform: uppercase; letter-spacing: 0.12em; }
    h2 { font-family: 'Playfair Display', serif; font-size: 1.4rem; color: #2a1f15; text-align: center; margin-bottom: 1.5rem; }
    .form-group { display: flex; flex-direction: column; gap: 0.35rem; margin-bottom: 1rem; }
    .form-group label { font-size: 0.85rem; font-weight: 600; color: #2a1f15; }
    .form-group input {
      padding: 0.7rem 1rem; border: 1px solid #d4c9b8; border-radius: 12px;
      font-size: 0.92rem; font-family: 'Lato', sans-serif;
      background: rgba(255,255,255,0.7); color: #2a1f15;
      transition: all 0.15s; width: 100%; box-sizing: border-box;
    }
    .form-group input:focus { outline: none; border-color: #4e6040; box-shadow: 0 0 0 3px rgba(78,96,64,0.15); background: rgba(255,255,255,0.85); }
    /* Detected role pill */
    .role-pill {
      display: none; align-items: center; gap: 6px;
      padding: 6px 12px; border-radius: 20px;
      font-size: 0.78rem; font-weight: 700;
      margin-top: 6px; width: fit-content;
      transition: all 0.2s;
    }
    .role-pill--staff    { background: rgba(78,96,64,0.1);  color: #2d3b22; border: 1px solid rgba(78,96,64,0.25); }
    .role-pill--admin    { background: rgba(122,31,46,0.1); color: #7a1f2e; border: 1px solid rgba(122,31,46,0.25); }
    .role-pill--customer { background: rgba(200,150,62,0.1); color: #7a5a1e; border: 1px solid rgba(200,150,62,0.3); }
    /* reCAPTCHA */
    .recaptcha-wrap { display: none; justify-content: center; margin: 0.75rem 0; }
    /* Buttons */
    .submit-btn {
      width: 100%; padding: 0.8rem; margin-top: 0.5rem;
      background: linear-gradient(135deg, #4e6040, #6b8a5c); color: #fff;
      border: none; border-radius: 12px; font-size: 0.95rem; font-weight: 600;
      font-family: 'Lato', sans-serif; cursor: pointer; transition: all 0.15s;
    }
    .submit-btn:hover { background: linear-gradient(135deg, #2d3b22, #4e6040); transform: translateY(-1px); }
    .submit-btn:disabled { background: #d4c9b8; cursor: not-allowed; transform: none; }
    /* Alerts */
    .alert { padding: 0.7rem 0.9rem; border-radius: 10px; font-size: 0.85rem; margin-bottom: 1rem; display: none; }
    .alert--error   { background: #fdf0ef; border: 1px solid #f5c6cb; color: #c0392b; }
    .alert--success { background: #e8f0e0; border: 1px solid #c3e6cb; color: #2d3b22; }
    /* Footer */
    .auth-footer { text-align: center; margin-top: 1.25rem; font-size: 0.85rem; color: #5a4f45; }
    .auth-footer a { color: #4e6040; font-weight: 700; text-decoration: none; padding: 3px 10px; background: rgba(78,96,64,0.1); border-radius: 20px; }
    .auth-footer a:hover { background: rgba(78,96,64,0.2); }
    /* Domain hint */
    .domain-hint {
      font-size: 0.72rem; color: var(--muted);
      margin-top: 4px; line-height: 1.5;
    }
    .domain-hint span { font-weight: 700; color: var(--text-light); }
  </style>
</head>
<body>
<div class="auth-card">
  <div class="auth-logo">
    <img src="assets/logo.jpg" alt="Esperon Dairy Farm" onerror="this.style.display='none'" />
    <div class="auth-logo-name">Esperon Dairy Farm</div>
    <div class="auth-logo-sub">Management System</div>
  </div>

  <h2>Sign In</h2>

  <div id="alert-box" class="alert"></div>

  <form id="login-form" novalidate>

    <!-- Single email field — role detected from domain -->
    <div class="form-group">
      <label for="email">Email Address</label>
      <input type="email" id="email" placeholder="yourname@gmail.com"
             autocomplete="email" required />
      <!-- Auto-detected role badge -->
      <div class="role-pill" id="role-pill">
        <span class="material-symbols-outlined" style="font-size:1rem;" id="role-icon">badge</span>
        <span id="role-label">Staff</span>
      </div>
      <!-- Domain hint -->
      <div class="domain-hint">
        <span>@staffgmail.com</span> = Staff &nbsp;|&nbsp;
        <span>@admingmail.com</span> = Admin &nbsp;|&nbsp;
        <span>@gmail.com</span> = Customer
      </div>
    </div>

    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" placeholder="Your password"
             autocomplete="current-password" required />
    </div>

    <!-- reCAPTCHA shown only for Staff/Admin (detected from email) -->
    <div class="recaptcha-wrap" id="recaptcha-wrap">
      <div class="g-recaptcha" data-sitekey="6LdTbcssAAAAAJbLgdoZ98Iu7cZx7Lw7Nwik5C3n"></div>
    </div>

    <button type="submit" class="submit-btn" id="submit-btn">Sign In</button>
  </form>

  <div class="auth-footer">
    New here? <a href="signup.php">Create account</a>
  </div>
</div>

<script>
const API = '../dairy_farm_backend/api';

// ── Domain → role mapping ─────────────────────────────────
const DOMAIN_MAP = {
  'staffgmail.com':  'staff',
  'admingmail.com':  'admin',
  'gmail.com':       'customer',
};

const ROLE_CONFIG = {
  staff:    { label: 'Staff',    icon: 'badge',               pillClass: 'role-pill--staff' },
  admin:    { label: 'Admin',    icon: 'admin_panel_settings', pillClass: 'role-pill--admin' },
  customer: { label: 'Customer', icon: 'shopping_cart',        pillClass: 'role-pill--customer' },
};

function detectRoleFromEmail(email) {
  const parts = email.split('@');
  if (parts.length !== 2) return null;
  const domain = parts[1].toLowerCase().trim();
  return DOMAIN_MAP[domain] || null;
}

function showAlert(msg, type) {
  const box = document.getElementById('alert-box');
  box.textContent = msg;
  box.className = 'alert alert--' + type;
  box.style.display = 'block';
}

// ── Live email detection ──────────────────────────────────
document.getElementById('email').addEventListener('input', function() {
  const email   = this.value.trim();
  const role    = detectRoleFromEmail(email);
  const pill    = document.getElementById('role-pill');
  const iconEl  = document.getElementById('role-icon');
  const labelEl = document.getElementById('role-label');
  const recaptchaWrap = document.getElementById('recaptcha-wrap');

  // Remove all pill classes
  pill.classList.remove('role-pill--staff', 'role-pill--admin', 'role-pill--customer');

  if (role && ROLE_CONFIG[role]) {
    const cfg = ROLE_CONFIG[role];
    iconEl.textContent  = cfg.icon;
    labelEl.textContent = cfg.label + ' account detected';
    pill.classList.add(cfg.pillClass);
    pill.style.display = 'flex';
    // Show reCAPTCHA only for staff/admin
    recaptchaWrap.style.display = (role === 'staff' || role === 'admin') ? 'flex' : 'none';
  } else {
    pill.style.display = 'none';
    recaptchaWrap.style.display = 'none';
  }

  // Clear alert when user types
  document.getElementById('alert-box').style.display = 'none';
});

// ── Submit ────────────────────────────────────────────────
document.getElementById('login-form').addEventListener('submit', async function(e) {
  e.preventDefault();

  const email    = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  const btn      = document.getElementById('submit-btn');

  if (!email)    { showAlert('Please enter your email address.', 'error'); return; }
  if (!password) { showAlert('Please enter your password.', 'error'); return; }

  const role = detectRoleFromEmail(email);

  if (!role) {
    showAlert('Unrecognized email domain. Use @staffgmail.com, @admingmail.com, or @gmail.com.', 'error');
    return;
  }

  const isStaffOrAdmin = role === 'staff' || role === 'admin';

  // reCAPTCHA check for staff/admin
  let recaptchaToken = '';
  if (isStaffOrAdmin) {
    if (typeof grecaptcha === 'undefined') {
      showAlert('reCAPTCHA is still loading. Please wait a moment.', 'error'); return;
    }
    recaptchaToken = grecaptcha.getResponse();
    if (!recaptchaToken) {
      showAlert('Please verify the reCAPTCHA.', 'error'); return;
    }
  }

  btn.disabled = true; btn.textContent = 'Signing in...';

  try {
    let res, data;

    if (isStaffOrAdmin) {
      // Staff/Admin: look up by email in the Worker table
      res = await fetch(API + '/auth.php?action=login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ email, password, 'g-recaptcha-response': recaptchaToken }),
      });
      data = await res.json();
      if (data.success) {
        localStorage.setItem('csrf_token', data.data.csrf_token);
        localStorage.setItem('user', JSON.stringify(data.data.user));
        const userRole = data.data.user?.role || '';
        showAlert('Welcome ' + userRole + '! Redirecting...', 'success');
        setTimeout(() => { window.location.href = 'index.php'; }, 800);
      } else {
        showAlert(data.message || 'Login failed. Please check your credentials.', 'error');
        document.getElementById('password').value = '';
        if (typeof grecaptcha !== 'undefined') try { grecaptcha.reset(); } catch(ex) {}
      }

    } else {
      // Customer login
      res = await fetch(API + '/customer_auth.php?action=login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ email, password }),
      });
      data = await res.json();
      if (data.success) {
        localStorage.setItem('customer',   JSON.stringify(data.data.customer));
        localStorage.setItem('csrf_token', data.data.csrf_token);
        showAlert('Welcome back! Redirecting...', 'success');
        setTimeout(() => { window.location.href = 'customer_dashboard.php'; }, 800);
      } else {
        showAlert(data.message || 'Login failed. Please check your credentials.', 'error');
        document.getElementById('password').value = '';
      }
    }

  } catch(err) {
    showAlert('Network error. Please try again.', 'error');
    console.error('[Login Error]', err);
  } finally {
    btn.disabled = false; btn.textContent = 'Sign In';
  }
});
</script>
</body>
</html>
