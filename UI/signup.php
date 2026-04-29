<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign Up â€” Esperon Dairy Farm</title>
  <link rel="stylesheet" href="css/style.css" />
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <style>
    /* â”€â”€ Signup page specific styles â”€â”€ */
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
      max-width: 460px;
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

    .form-group input,
    .form-group select {
      padding: 0.7rem 1rem;
      border: 1px solid #d4c9b8;
      border-radius: 12px;
      font-size: 0.92rem;
      font-family: 'Lato', sans-serif;
      background: rgba(255, 255, 255, 0.7);
      color: #2a1f15 !important;
      transition: all 0.15s;
      width: 100%;
    }

    .form-group input.has-toggle {
      padding-right: 2.5rem;
    }

    .form-group input:focus,
    .form-group select:focus {
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

    .form-group input.valid {
      border-color: var(--olive);
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

    .pw-strength {
      margin-top: 0.5rem;
      display: none;
    }

    .pw-strength.visible {
      display: block;
    }

    .pw-strength__bar {
      display: flex;
      gap: 4px;
      margin-bottom: 4px;
    }

    .pw-strength__segment {
      flex: 1;
      height: 4px;
      background: var(--border);
      border-radius: 2px;
      transition: background 0.2s;
    }

    .pw-strength__segment.active {
      background: var(--danger);
    }

    .pw-strength__segment.good {
      background: var(--gold);
    }

    .pw-strength__segment.strong {
      background: var(--olive);
    }

    .pw-strength__text {
      font-size: 0.72rem;
      color: var(--muted);
    }

    .terms-checkbox {
      display: flex;
      align-items: flex-start;
      gap: 0.6rem;
      margin-bottom: 1rem;
      font-size: 0.82rem;
      color: var(--text-light);
      line-height: 1.4;
    }

    .terms-checkbox input[type="checkbox"] {
      width: 16px;
      height: 16px;
      accent-color: var(--olive);
      cursor: pointer;
      flex-shrink: 0;
      margin-top: 2px;
    }

    .terms-checkbox a {
      color: var(--olive);
      text-decoration: none;
    }

    .terms-checkbox a:hover {
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

  <h2>Create an Account</h2>

  <!-- Alert banners -->
  <div id="alert-container"></div>

  <!-- Signup form -->
  <form id="signup-form" novalidate>

    <div class="form-group">
      <label for="username">Username</label>
      <div class="input-wrapper">
        <input type="text" id="username" name="username"
               placeholder="e.g. juan_dela_cruz"
               autocomplete="username" required
               aria-describedby="username-error" />
      </div>
      <span id="username-error" class="field-error"></span>
    </div>

    <div class="form-group">
      <label for="email">Email Address</label>
      <div class="input-wrapper">
        <input type="email" id="email" name="email"
               placeholder="e.g. juan@esperon.farm"
               autocomplete="email" required
               aria-describedby="email-error" />
      </div>
      <span id="email-error" class="field-error"></span>
    </div>

    <div class="form-group">
      <label for="password">Password</label>
      <div class="input-wrapper">
        <input type="password" id="password" name="password"
               class="has-toggle"
               placeholder="At least 8 characters"
               autocomplete="new-password" required
               aria-describedby="password-error pw-strength-text" />
        <button type="button" class="pw-toggle" data-target="password" aria-label="Toggle password visibility">
          <svg class="eye-hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
            <line x1="1" y1="1" x2="23" y2="23"/>
          </svg>
          <svg class="eye-visible" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
        </button>
      </div>
      <span id="password-error" class="field-error"></span>
      <div id="pw-strength" class="pw-strength">
        <div class="pw-strength__bar">
          <div class="pw-strength__segment" id="pw-seg-1"></div>
          <div class="pw-strength__segment" id="pw-seg-2"></div>
          <div class="pw-strength__segment" id="pw-seg-3"></div>
          <div class="pw-strength__segment" id="pw-seg-4"></div>
        </div>
        <span id="pw-strength-text" class="pw-strength__text"></span>
      </div>
    </div>

    <div class="form-group">
      <label for="confirm-password">Confirm Password</label>
      <div class="input-wrapper">
        <input type="password" id="confirm-password" name="confirm-password"
               class="has-toggle"
               placeholder="Re-enter your password"
               autocomplete="new-password" required
               aria-describedby="confirm-password-error" />
        <button type="button" class="pw-toggle" data-target="confirm-password" aria-label="Toggle password visibility">
          <svg class="eye-hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
            <line x1="1" y1="1" x2="23" y2="23"/>
          </svg>
          <svg class="eye-visible" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
        </button>
      </div>
      <span id="confirm-password-error" class="field-error"></span>
    </div>

    <div class="form-group">
      <label for="role">Account Type</label>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-top:4px;">
        <label id="type-staff-lbl" style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:12px 8px;border:2px solid var(--border);border-radius:12px;cursor:pointer;transition:all 0.15s;background:rgba(255,255,255,0.5);">
          <input type="radio" name="account_type" id="type-staff" value="Staff" checked style="display:none;" />
          <span class="material-symbols-outlined" style="font-size:1.8rem;color:#4e6040;">badge</span>
          <span style="font-size:0.8rem;font-weight:700;color:var(--text);">Staff</span>
          <span style="font-size:0.68rem;color:var(--muted);text-align:center;">Farm worker account</span>
        </label>
        <label id="type-admin-lbl" style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:12px 8px;border:2px solid var(--border);border-radius:12px;cursor:pointer;transition:all 0.15s;background:rgba(255,255,255,0.5);">
          <input type="radio" name="account_type" id="type-admin" value="Admin" style="display:none;" />
          <span class="material-symbols-outlined" style="font-size:1.8rem;color:#7a1f2e;">admin_panel_settings</span>
          <span style="font-size:0.8rem;font-weight:700;color:var(--text);">Admin</span>
          <span style="font-size:0.68rem;color:var(--muted);text-align:center;">Full system access</span>
        </label>
        <label id="type-customer-lbl" style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:12px 8px;border:2px solid var(--border);border-radius:12px;cursor:pointer;transition:all 0.15s;background:rgba(255,255,255,0.5);">
          <input type="radio" name="account_type" id="type-customer" value="Customer" style="display:none;" />
          <span class="material-symbols-outlined" style="font-size:1.8rem;color:#c8963e;">shopping_cart</span>
          <span style="font-size:0.8rem;font-weight:700;color:var(--text);">Customer</span>
          <span style="font-size:0.68rem;color:var(--muted);text-align:center;">Order dairy products</span>
        </label>
      </div>
      <span id="role-error" class="field-error"></span>
    </div>

    <!-- Customer-only fields (hidden for Staff/Admin) -->
    <div id="customer-fields" style="display:none;">
      <div class="form-group">
        <label for="phone">Phone Number</label>
        <div class="input-wrapper">
          <input type="tel" id="phone" name="phone" placeholder="e.g. 09012345678" autocomplete="tel" />
        </div>
      </div>
      <div class="form-group">
        <label for="delivery-address">Delivery Address</label>
        <div class="input-wrapper">
          <input type="text" id="delivery-address" name="delivery-address" placeholder="e.g. Casisang, Malaybalay City" autocomplete="street-address" />
        </div>
      </div>
    </div>

    <!-- Staff/Admin-only: username field label note -->
    <div id="staff-note" style="font-size:0.75rem;color:var(--muted);background:rgba(78,96,64,0.06);border-radius:8px;padding:8px 12px;margin-bottom:4px;">
      <span style="font-weight:700;color:var(--olive-dark);">Staff/Admin accounts</span> are for farm workers only. Contact your administrator if you need access.
    </div>

    <div class="terms-checkbox">
      <input type="checkbox" id="terms" name="terms" required aria-describedby="terms-error" />
      <span>I agree to the <a href="#" id="terms-link">Terms of Service</a> and <a href="#" id="privacy-link">Privacy Policy</a></span>
    </div>
    <span id="terms-error" class="field-error" style="margin-bottom: 1rem;"></span>

    <div class="g-recaptcha" data-sitekey="6LdTbcssAAAAAJbLgdoZ98Iu7cZx7Lw7Nwik5C3n"></div>

    <button type="submit" class="submit-btn" id="submit-btn">
      <span class="spinner"></span>
      <span class="btn-text">Create Account</span>
    </button>
  </form>

  <div class="auth-footer">
    Already have an account? <a href="login.php">Staff login</a> &nbsp;Â·&nbsp; <a href="customer_login.php">Customer login</a>
  </div>
</div>

<script>
// â”€â”€ Config â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const API_BASE = '../dairy_farm_backend/api';

// â”€â”€ DOM refs â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const form          = document.getElementById('signup-form');
const submitBtn     = document.getElementById('submit-btn');
const alertContainer = document.getElementById('alert-container');

// â”€â”€ Alert helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function showAlert(msg, type = 'error', duration = 5000) {
  // Clear existing alerts
  alertContainer.innerHTML = '';
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
  showAlert(msg, 'error', 6000);
}

function showSuccess(msg) {
  showAlert(msg, 'success', 4000);
}

// â”€â”€ Field validation â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function validateField(input, errorId, validationFn) {
  const errorEl = document.getElementById(errorId);
  const value = input.value;
  const error = validationFn(value);

  if (error) {
    input.classList.add('error');
    input.classList.remove('valid');
    if (errorEl) {
      errorEl.textContent = error;
      errorEl.classList.add('visible');
    }
    return false;
  } else {
    input.classList.remove('error');
    if (value.length > 0) {
      input.classList.add('valid');
    }
    if (errorEl) {
      errorEl.textContent = '';
      errorEl.classList.remove('visible');
    }
    return true;
  }
}

// â”€â”€ Password visibility toggles â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
document.querySelectorAll('.pw-toggle').forEach(button => {
  button.addEventListener('click', () => {
    const targetId = button.getAttribute('data-target');
    const input = document.getElementById(targetId);
    const eyeHidden = button.querySelector('.eye-hidden');
    const eyeVisible = button.querySelector('.eye-visible');

    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    eyeHidden.style.display = isPassword ? 'none' : 'block';
    eyeVisible.style.display = isPassword ? 'block' : 'none';
    button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
  });
});

// â”€â”€ Password strength indicator â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function getPasswordStrength(password) {
  let strength = 0;
  if (password.length >= 8) strength++;
  if (password.length >= 12) strength++;
  if (/[A-Z]/.test(password)) strength++;
  if (/[0-9]/.test(password)) strength++;
  if (/[^a-zA-Z0-9]/.test(password)) strength++;
  return Math.min(strength, 4);
}

function updatePasswordStrength(password) {
  const strengthContainer = document.getElementById('pw-strength');
  const strengthText = document.getElementById('pw-strength-text');

  if (!password) {
    strengthContainer.classList.remove('visible');
    return;
  }

  strengthContainer.classList.add('visible');
  const strength = getPasswordStrength(password);

  // Update segments
  for (let i = 1; i <= 4; i++) {
    const segment = document.getElementById(`pw-seg-${i}`);
    segment.className = 'pw-strength__segment';
    if (i <= strength) {
      if (strength <= 2) {
        segment.classList.add('active');
      } else if (strength === 3) {
        segment.classList.add('good');
      } else {
        segment.classList.add('strong');
      }
    }
  }

  // Update text
  const labels = ['Very Weak', 'Weak', 'Fair', 'Strong', 'Very Strong'];
  const colors = ['var(--danger)', 'var(--danger)', 'var(--gold)', 'var(--accent)', 'var(--accent)'];
  const label = labels[strength];
  const color = colors[strength];

  strengthText.textContent = `Password strength: ${label}`;
  strengthText.style.color = color;
}

document.getElementById('password').addEventListener('input', function() {
  updatePasswordStrength(this.value);

  // Real-time validation
  validateField(this, 'password-error', (v) => {
    if (!v) return null; // Don't show error if empty (required validation handles this)
    if (v.length < 8) return 'Password must be at least 8 characters.';
    if (!/[A-Z]/.test(v)) return 'Password must contain at least one uppercase letter.';
    if (!/[0-9]/.test(v)) return 'Password must contain at least one number.';
    return null;
  });

  // Re-validate confirm password if it has a value
  const confirmInput = document.getElementById('confirm-password');
  if (confirmInput.value) {
    validateField(confirmInput, 'confirm-password-error', (v) => {
      if (!v) return null;
      if (v !== document.getElementById('password').value) return 'Passwords do not match.';
      return null;
    });
  }
});

// â”€â”€ Real-time field validation â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
document.getElementById('username').addEventListener('input', function() {
  validateField(this, 'username-error', (v) => {
    if (!v) return null; // Don't show error if empty
    if (v.length < 3 || v.length > 50) return 'Username must be 3â€“50 characters.';
    if (!/^[a-zA-Z0-9_\-]+$/.test(v)) return 'Username may only contain letters, numbers, underscores, and hyphens.';
    return null;
  });
});

document.getElementById('email').addEventListener('input', function() {
  validateField(this, 'email-error', (v) => {
    if (!v) return null; // Don't show error if empty
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) return 'Please enter a valid email address.';
    return null;
  });
});

document.getElementById('confirm-password').addEventListener('input', function() {
  validateField(this, 'confirm-password-error', (v) => {
    if (!v) return null; // Don't show error if empty
    if (v !== document.getElementById('password').value) return 'Passwords do not match.';
    return null;
  });
});

// â”€â”€ Terms link handlers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
document.getElementById('terms-link').addEventListener('click', (e) => {
  e.preventDefault();
  showAlert('Terms of Service page coming soon.', 'success', 3000);
});

document.getElementById('privacy-link').addEventListener('click', (e) => {
  e.preventDefault();
  showAlert('Privacy Policy page coming soon.', 'success', 3000);
});

// â”€â”€ Account type toggle â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function getAccountType() {
  const checked = document.querySelector('input[name="account_type"]:checked');
  return checked ? checked.value : 'Staff';
}

function updateAccountTypeUI() {
  const type = getAccountType();
  const isCustomer = type === 'Customer';

  // Show/hide customer-only fields
  document.getElementById('customer-fields').style.display = isCustomer ? 'block' : 'none';
  document.getElementById('staff-note').style.display      = isCustomer ? 'none'  : 'block';

  // Show/hide username field (customers use email as identifier)
  const usernameGroup = document.getElementById('username').closest('.form-group');
  if (usernameGroup) usernameGroup.style.display = isCustomer ? 'none' : 'block';

  // Update card highlight styles
  ['staff', 'admin', 'customer'].forEach(t => {
    const lbl = document.getElementById('type-' + t + '-lbl');
    if (!lbl) return;
    const isSelected = type.toLowerCase() === t;
    lbl.style.borderColor  = isSelected ? 'var(--olive)' : 'var(--border)';
    lbl.style.background   = isSelected ? 'rgba(78,96,64,0.1)' : 'rgba(255,255,255,0.5)';
    lbl.style.boxShadow    = isSelected ? '0 0 0 2px rgba(78,96,64,0.2)' : 'none';
  });

  // Update page title
  const h2 = document.querySelector('h2');
  if (h2) {
    if (isCustomer) h2.textContent = 'Create Customer Account';
    else h2.textContent = 'Create an Account';
  }
}

document.querySelectorAll('input[name="account_type"]').forEach(radio => {
  radio.addEventListener('change', updateAccountTypeUI);
});

// Run once on load to set initial state
updateAccountTypeUI();

// ── Submit ────────────────────────────────────────────────────
form.addEventListener('submit', async (e) => {
  e.preventDefault();
  alertContainer.innerHTML = '';

  const accountType     = getAccountType();
  const isCustomer      = accountType === 'Customer';
  const username        = document.getElementById('username').value.trim();
  const email           = document.getElementById('email').value.trim();
  const password        = document.getElementById('password').value;
  const confirmPassword = document.getElementById('confirm-password').value;
  const termsAccepted   = document.getElementById('terms').checked;
  const phone           = isCustomer ? document.getElementById('phone').value.trim() : '';
  const address         = isCustomer ? document.getElementById('delivery-address').value.trim() : '';

  let isValid = true;

  if (!isCustomer) {
    isValid = validateField(document.getElementById('username'), 'username-error', (v) => {
      if (!v) return 'Username is required.';
      if (v.length < 3 || v.length > 50) return 'Username must be 3-50 characters.';
      if (!/^[a-zA-Z0-9_\-]+$/.test(v)) return 'Username may only contain letters, numbers, underscores, and hyphens.';
      return null;
    }) && isValid;
  }

  isValid = validateField(document.getElementById('email'), 'email-error', (v) => {
    if (!v) return 'Email address is required.';
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) return 'Please enter a valid email address.';
    return null;
  }) && isValid;

  isValid = validateField(document.getElementById('password'), 'password-error', (v) => {
    if (!v) return 'Password is required.';
    if (isCustomer) {
      if (v.length < 6) return 'Password must be at least 6 characters.';
    } else {
      if (v.length < 8) return 'Password must be at least 8 characters.';
      if (!/[A-Z]/.test(v)) return 'Password must contain at least one uppercase letter.';
      if (!/[0-9]/.test(v)) return 'Password must contain at least one number.';
    }
    return null;
  }) && isValid;

  isValid = validateField(document.getElementById('confirm-password'), 'confirm-password-error', (v) => {
    if (!v) return 'Please confirm your password.';
    if (v !== password) return 'Passwords do not match.';
    return null;
  }) && isValid;

  const termsErrorEl = document.getElementById('terms-error');
  if (!termsAccepted) {
    termsErrorEl.textContent = 'You must agree to the Terms of Service and Privacy Policy.';
    termsErrorEl.classList.add('visible');
    isValid = false;
  } else {
    termsErrorEl.textContent = '';
    termsErrorEl.classList.remove('visible');
  }

  if (!isValid) {
    const firstError = form.querySelector('.error, #terms-error.visible');
    if (firstError && firstError.id !== 'terms-error') firstError.focus();
    return;
  }

  let recaptchaToken = '';
  if (!isCustomer) {
    if (typeof grecaptcha === 'undefined') {
      showError('reCAPTCHA is still loading. Please wait a moment and try again.');
      return;
    }
    recaptchaToken = grecaptcha.getResponse();
    if (!recaptchaToken) { showError('Please verify the reCAPTCHA.'); return; }
  }

  submitBtn.disabled = true;
  submitBtn.classList.add('loading');
  submitBtn.querySelector('.btn-text').textContent = 'Creating account...';

  try {
    let response, data;

    if (isCustomer) {
      // Customer signup
      const custName = email.split('@')[0];
      response = await fetch(`${API_BASE}/customer_auth.php?action=signup`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ name: custName, email, phone, address, password }),
      });
      data = await response.json();
      if (data.success) {
        showSuccess('Customer account created! Redirecting to customer login...');
        form.reset();
        document.getElementById('pw-strength').classList.remove('visible');
        updateAccountTypeUI();
        setTimeout(() => { window.location.href = 'customer_login.php'; }, 2000);
      } else {
        showError(data.message || 'Signup failed. Please try again.');
        if (response.status === 409) {
          document.getElementById('email').classList.add('error');
          document.getElementById('email-error').textContent = 'An account with this email already exists.';
          document.getElementById('email-error').classList.add('visible');
        }
      }
    } else {
      // Staff/Admin signup
      response = await fetch(`${API_BASE}/signup.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ username, email, password, role: accountType, 'g-recaptcha-response': recaptchaToken }),
      });
      data = await response.json();
      if (data.success) {
        showSuccess(data.message + ' Redirecting to login...');
        form.reset();
        document.getElementById('pw-strength').classList.remove('visible');
        updateAccountTypeUI();
        setTimeout(() => { window.location.href = 'login.php'; }, 2500);
      } else {
        showError(data.message || 'Signup failed. Please try again.');
        if (response.status === 409) {
          document.getElementById('username').classList.add('error');
          document.getElementById('email').classList.add('error');
          document.getElementById('username-error').textContent = 'Username or email already in use.';
          document.getElementById('username-error').classList.add('visible');
        }
        if (typeof grecaptcha !== 'undefined' && document.querySelector('.g-recaptcha iframe')) {
          try { grecaptcha.reset(); } catch(ex) {}
        }
      }
    }
  } catch (err) {
    showError('Network error. Please check your connection and try again.');
    console.error('[Signup Error]', err);
  } finally {
    submitBtn.disabled = false;
    submitBtn.classList.remove('loading');
    submitBtn.querySelector('.btn-text').textContent = 'Create Account';
  }
});

// â”€â”€ Reset reCAPTCHA on focus â”€â”€
document.getElementById('username').addEventListener('focus', () => {
  if (typeof grecaptcha !== 'undefined' && grecaptcha.getResponse && grecaptcha.getResponse()) {
    try { grecaptcha.reset(); } catch(e) {}
  }
});

// â”€â”€ Auto-focus username field â”€â”€
document.getElementById('username').focus();
</script>

</body>
</html>
