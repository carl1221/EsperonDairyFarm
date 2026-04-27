<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login — Esperon Dairy Farm</title>
  <link rel="stylesheet" href="css/style.css" />
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <style>
    /* ── Login page specific styles ── */
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      background: url('assets/bg.png') no-repeat center center fixed;
      background-size: cover;
      overflow: auto !important;
      padding: 2rem 0;
    }

    /* Override auth-card styles for glassmorphism */
    .auth-card {
      width: 100%;
      max-width: 440px;
      background: rgba(255, 255, 255, 0.4);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border: 1px solid rgba(255, 255, 255, 0.6);
      border-radius: 24px;
      box-shadow: 0 12px 48px rgba(0, 0, 0, 0.15);
      padding: 2.5rem 2rem;
    }

    .auth-card__logo {
      text-align: center;
      margin-bottom: 1.75rem;
    }

    .auth-card__logo-img {
      max-width: 100px;
      height: auto;
      border-radius: 16px;
      margin-bottom: 0.75rem;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    }

    .auth-card__logo-name {
      font-family: 'Playfair Display', serif;
      font-size: 1.4rem;
      color: #7a1f2e !important;
      font-weight: 700;
    }

    .auth-card__logo-sub {
      font-size: 0.7rem;
      color: #5a4f45 !important;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      margin-top: 0.25rem;
    }

    h2 {
      font-family: 'Playfair Display', serif;
      font-size: 1.5rem;
      margin-bottom: 1.25rem;
      color: #2a1f15 !important;
      text-align: center;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 0.4rem;
      margin-bottom: 1rem;
    }

    .form-group label {
      font-size: 0.85rem;
      font-weight: 600;
      color: #2a1f15 !important;
    }

    .form-group .input-wrapper {
      position: relative;
    }

    .form-group input {
      padding: 0.7rem 1rem;
      border: 1px solid #d4c9b8;
      border-radius: 12px;
      font-size: 0.92rem;
      font-family: 'Lato', sans-serif;
      background: rgba(255, 255, 255, 0.7);
      color: #2a1f15 !important;
      transition: all 0.15s;
      width: 100%;
      padding-right: 2.5rem;
    }

    .form-group input:focus {
      outline: none;
      border-color: var(--olive);
      background: rgba(255, 255, 255, 0.8);
      box-shadow: 0 0 0 3px rgba(78, 96, 64, 0.15);
    }

    .form-group input.error {
      border-color: var(--danger);
    }

    .form-group input.error:focus {
      box-shadow: 0 0 0 3px rgba(184, 50, 50, 0.15);
    }

    .pw-toggle {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      color: var(--muted);
      padding: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.15s;
    }

    .pw-toggle:hover {
      color: var(--text);
    }

    .pw-toggle svg {
      width: 20px;
      height: 20px;
    }

    .field-error {
      font-size: 0.75rem;
      color: var(--danger);
      margin-top: 0.25rem;
      display: none;
    }

    .field-error.visible {
      display: block;
    }

    .form-options {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1rem;
      font-size: 0.85rem;
    }

    .remember-me {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      cursor: pointer;
      color: var(--text-light);
    }

    .remember-me input[type="checkbox"] {
      width: 16px;
      height: 16px;
      accent-color: var(--olive);
      cursor: pointer;
    }

    .forgot-password {
      color: var(--olive);
      text-decoration: none;
      font-weight: 600;
      transition: color 0.15s;
    }

    .forgot-password:hover {
      color: var(--olive-dark);
      text-decoration: underline;
    }

    .submit-btn {
      width: 100%;
      padding: 0.8rem;
      background: linear-gradient(135deg, #4e6040, #6b8a5c);
      color: #fff !important;
      border: none;
      border-radius: 12px;
      font-size: 0.95rem;
      font-weight: 600;
      font-family: 'Lato', sans-serif;
      cursor: pointer;
      transition: all 0.15s;
      margin-top: 0.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .submit-btn:hover {
      background: linear-gradient(135deg, #2d3b22, #4e6040);
      transform: translateY(-1px);
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    }

    .submit-btn:disabled {
      background: #d4c9b8;
      color: #8a7f72 !important;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }

    .spinner {
      display: none;
      width: 16px;
      height: 16px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin 0.7s linear infinite;
    }

    .submit-btn.loading .spinner {
      display: block;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    .google-btn {
      width: 100%;
      padding: 0.8rem;
      background: #fff;
      color: #3c4043;
      border: 1px solid #dadce0;
      border-radius: 12px;
      font-size: 0.95rem;
      font-weight: 500;
      font-family: 'Lato', sans-serif;
      cursor: pointer;
      transition: all 0.15s;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .google-btn:hover {
      background: #f8f9fa;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    }

    .google-btn:active {
      background: #f1f3f4;
    }

    .google-icon {
      width: 18px;
      height: 18px;
    }

    .divider {
      text-align: center;
      margin: 1rem 0;
      position: relative;
      color: #5f6368;
      font-size: 0.85rem;
    }

    .divider::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 0;
      right: 0;
      height: 1px;
      background: #dadce0;
    }

    .divider span {
      background: rgba(255, 255, 255, 0.4);
      padding: 0 1rem;
      position: relative;
      z-index: 1;
    }

    .alert {
      padding: 0.7rem 0.9rem;
      border-radius: var(--radius-md);
      font-size: 0.85rem;
      margin-bottom: 1rem;
      display: none;
      animation: slideIn 0.2s ease;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-8px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .alert--error {
      background: var(--danger-lt);
      border: 1px solid #f5c6cb;
      color: var(--danger);
    }

    .alert--success {
      background: var(--success-lt);
      border: 1px solid #c3e6cb;
      color: var(--olive-dark);
    }

    .auth-footer {
      text-align: center;
      margin-top: 1.25rem;
      font-size: 0.85rem;
      color: #5a4f45;
    }

    .auth-footer a {
      color: #4e6040 !important;
      font-weight: 700;
      text-decoration: none;
      padding: 4px 12px;
      background: rgba(78, 96, 64, 0.1);
      border-radius: 20px;
      transition: all 0.15s;
    }

    .auth-footer a:hover {
      background: rgba(78, 96, 64, 0.2);
      text-decoration: none;
      transform: scale(1.05);
    }

    .g-recaptcha {
      margin: 1rem 0;
      display: flex;
      justify-content: center;
    }

    @media (max-width: 480px) {
      .auth-card {
        padding: 1.5rem 1.25rem;
      }

      .form-options {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
      }
    }
  </style>
</head>
<body>

<div class="auth-card">
  <!-- Brand -->
  <div class="auth-card__logo">
    <img src="assets/logo.jpg" alt="Esperon Dairy Farm Logo" class="auth-card__logo-img">
    <div class="auth-card__logo-name">Esperon Dairy Farm</div>
    <div class="auth-card__logo-sub">Management System</div>
  </div>

  <h2>Welcome back</h2>

  <div id="alert-container"></div>

  <!-- Google Sign-In Button -->
  <button type="button" class="google-btn" id="google-login-btn">
    <svg class="google-icon" viewBox="0 0 24 24">
      <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
      <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
      <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
      <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
    </svg>
    Continue with Google
  </button>

  <div class="divider">
    <span>or</span>
  </div>

  <form id="login-form" novalidate>

    <div class="form-group">
      <label for="username">Username</label>
      <div class="input-wrapper">
        <input type="text" id="username" name="username"
               autocomplete="username"
               placeholder="Enter your username" required
               aria-describedby="username-error" />
      </div>
      <span id="username-error" class="field-error"></span>
    </div>

    <div class="form-group">
      <label for="password">Password</label>
      <div class="input-wrapper">
        <input type="password" id="password" name="password"
               autocomplete="current-password"
               placeholder="Enter your password" required
               aria-describedby="password-error" />
        <button type="button" class="pw-toggle" id="pw-toggle" aria-label="Toggle password visibility">
          <!-- Eye icon (hidden state) -->
          <svg id="eye-hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
            <line x1="1" y1="1" x2="23" y2="23"/>
          </svg>
          <!-- Eye icon (visible state) -->
          <svg id="eye-visible" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
        </button>
      </div>
      <span id="password-error" class="field-error"></span>
    </div>

    <div class="form-options">
      <label class="remember-me">
        <input type="checkbox" id="remember" name="remember" />
        <span>Remember me</span>
      </label>
      <a href="#" class="forgot-password" id="forgot-password-link">Forgot password?</a>
    </div>

    <div class="g-recaptcha" data-sitekey="6LdTbcssAAAAAJbLgdoZ98Iu7cZx7Lw7Nwik5C3n"></div>

    <button type="submit" class="submit-btn" id="login-btn">
      <span class="spinner"></span>
      <span class="btn-text">Log In</span>
    </button>
  </form>

  <div class="auth-footer">
    Don't have an account? <a href="signup.php">Sign up</a>
  </div>
</div>

<script>
const API_BASE  = '../dairy_farm_backend/api';
const alertContainer = document.getElementById('alert-container');
const loginBtn  = document.getElementById('login-btn');
const form = document.getElementById('login-form');

// ── Check for URL parameters (e.g., errors) ──
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('error') === 'google_user_not_found') {
  showError('Your Google account is not associated with any user in the system. Please contact your administrator.');
}

// ── Google Login Handler ──
document.getElementById('google-login-btn').addEventListener('click', () => {
  window.location.href = `${API_BASE}/auth.php?action=google_login`;
});

// ── Password visibility toggle ──
const pwToggle = document.getElementById('pw-toggle');
const passwordInput = document.getElementById('password');
const eyeHidden = document.getElementById('eye-hidden');
const eyeVisible = document.getElementById('eye-visible');

pwToggle.addEventListener('click', () => {
  const isPassword = passwordInput.type === 'password';
  passwordInput.type = isPassword ? 'text' : 'password';
  eyeHidden.style.display = isPassword ? 'none' : 'block';
  eyeVisible.style.display = isPassword ? 'block' : 'none';
  pwToggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
});

// ── Alert helpers ──
function showAlert(msg, type = 'error', duration = 5000) {
  const alert = document.createElement('div');
  alert.className = `alert alert--${type}`;
  alert.textContent = msg;
  alert.style.display = 'block';
  alertContainer.appendChild(alert);

  if (duration > 0) {
    setTimeout(() => {
      alert.style.opacity = '0';
      alert.style.transition = 'opacity .3s';
      setTimeout(() => alert.remove(), 300);
    }, duration);
  }

  return alert;
}

function showError(msg) {
  // Clear existing alerts
  alertContainer.innerHTML = '';
  showAlert(msg, 'error', 6000);
}

// ── Field validation ──
function validateField(input, errorId, validationFn) {
  const errorEl = document.getElementById(errorId);
  const value = input.value.trim();
  const error = validationFn(value);

  if (error) {
    input.classList.add('error');
    errorEl.textContent = error;
    errorEl.classList.add('visible');
    return false;
  } else {
    input.classList.remove('error');
    errorEl.textContent = '';
    errorEl.classList.remove('visible');
    return true;
  }
}

// Clear field error on input
document.getElementById('username').addEventListener('input', function() {
  validateField(this, 'username-error', (v) => {
    if (v && v.length > 0) return null;
    return 'Username is required.';
  });
});

document.getElementById('password').addEventListener('input', function() {
  validateField(this, 'password-error', (v) => {
    if (v && v.length > 0) return null;
    return 'Password is required.';
  });
});

// ── Forgot password handler ──
document.getElementById('forgot-password-link').addEventListener('click', (e) => {
  e.preventDefault();
  showAlert('Please contact your administrator to reset your password.', 'success', 5000);
});

// ── Form submission ──
form.addEventListener('submit', async (e) => {
  e.preventDefault();
  alertContainer.innerHTML = '';

  const usernameInput = document.getElementById('username');
  const passwordInput = document.getElementById('password');
  const username = usernameInput.value.trim();
  const password = passwordInput.value;

  // Validate all fields
  const usernameValid = validateField(usernameInput, 'username-error', (v) => {
    if (!v) return 'Username is required.';
    if (v.length < 3) return 'Username must be at least 3 characters.';
    return null;
  });

  const passwordValid = validateField(passwordInput, 'password-error', (v) => {
    if (!v) return 'Password is required.';
    if (v.length < 1) return 'Password is required.';
    return null;
  });

  if (!usernameValid || !passwordValid) {
    return;
  }

  // Check if reCAPTCHA is loaded
  if (typeof grecaptcha === 'undefined') {
    showError('reCAPTCHA is still loading. Please wait a moment and try again.');
    return;
  }

  // Get reCAPTCHA token
  const recaptchaToken = grecaptcha.getResponse();
  if (!recaptchaToken) {
    showError('Please verify the reCAPTCHA.');
    return;
  }

  // Start loading
  loginBtn.disabled = true;
  loginBtn.classList.add('loading');
  loginBtn.querySelector('.btn-text').textContent = 'Logging in…';

  try {
    const response = await fetch(`${API_BASE}/auth.php?action=login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ username, password, 'g-recaptcha-response': recaptchaToken }),
    });

    const data = await response.json();

    if (data.success) {
      // Persist session data in localStorage for UI display
      localStorage.setItem('csrf_token', data.data.csrf_token);
      localStorage.setItem('user', JSON.stringify(data.data.user));

      // Show success message briefly before redirect
      showAlert('Login successful! Redirecting…', 'success', 1000);
      setTimeout(() => {
        window.location.href = 'index.php';
      }, 800);
    } else {
      showError(data.message || 'Login failed. Please check your credentials.');
      passwordInput.value = '';
      if (typeof grecaptcha !== 'undefined') {
        grecaptcha.reset();
      }
      passwordInput.focus();
    }

  } catch (err) {
    showError('Network error. Please check your connection and try again.');
    console.error('[Login Error]', err);
  } finally {
    loginBtn.disabled = false;
    loginBtn.classList.remove('loading');
    loginBtn.querySelector('.btn-text').textContent = 'Log In';
  }
});

// ── Reset reCAPTCHA on focus ──
document.getElementById('username').addEventListener('focus', () => {
  if (typeof grecaptcha !== 'undefined' && grecaptcha.getResponse()) {
    grecaptcha.reset();
  }
});

// ── Auto-focus username field ──
document.getElementById('username').focus();
</script>

</body>
</html>