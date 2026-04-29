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
    /* Google button */
    .google-btn {
      width: 100%; padding: 0.8rem; background: #fff; color: #3c4043;
      border: 1px solid #dadce0; border-radius: 12px; font-size: 0.95rem;
      font-weight: 500; font-family: 'Lato', sans-serif; cursor: pointer;
      transition: all 0.15s; margin-bottom: 1rem;
      display: flex; align-items: center; justify-content: center; gap: 8px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .google-btn:hover { background: #f8f9fa; box-shadow: 0 2px 6px rgba(0,0,0,0.15); }
    .google-icon { width: 18px; height: 18px; }
    .divider {
      text-align: center; margin: 0.75rem 0; position: relative;
      color: #5f6368; font-size: 0.85rem;
    }
    .divider::before {
      content: ''; position: absolute; top: 50%; left: 0; right: 0;
      height: 1px; background: #dadce0;
    }
    .divider span { background: rgba(255,255,255,0.55); padding: 0 1rem; position: relative; z-index: 1; }
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

  <!-- Google Sign-In -->
  <button type="button" class="google-btn" id="google-login-btn">
    <svg class="google-icon" viewBox="0 0 24 24">
      <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
      <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
      <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
      <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
    </svg>
    Continue with Google
  </button>

  <div class="divider"><span>or sign in with email</span></div>

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

// ── Google Sign-In ────────────────────────────────────────
document.getElementById('google-login-btn').addEventListener('click', function() {
  window.location.href = API + '/auth.php?action=google_login';
});

// Handle Google OAuth error redirect
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('error') === 'google_user_not_found') {
  const box = document.getElementById('alert-box');
  box.textContent = 'Your Google account is not linked to any account. Please sign in with email or contact your administrator.';
  box.className = 'alert alert--error';
  box.style.display = 'block';
}

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
