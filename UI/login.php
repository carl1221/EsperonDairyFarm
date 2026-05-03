<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login — Esperon Dairy Farm</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <!-- reCAPTCHA: use grecaptcha.ready() so the widget renders only after
       the DOM is fully parsed and recaptcha-container exists.
       render=explicit prevents auto-render; onload is not used. -->
  <script>
    var recaptchaWidgetId = null;
  </script>
  <script src="https://www.google.com/recaptcha/api.js?render=explicit" async defer
          onload="grecaptcha.ready(function(){
            recaptchaWidgetId = grecaptcha.render('recaptcha-container',{
              sitekey:'6LdTbcssAAAAAJbLgdoZ98Iu7cZx7Lw7Nwik5C3n'
            });
          })"></script>
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
      padding: 2rem 1rem;
    }

    /* Override auth-card styles for glassmorphism */
    .auth-card {
      width: 100%;
      max-width: 520px;
      background: rgba(255, 255, 255, 0.4);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border: 1px solid rgba(255, 255, 255, 0.6);
      border-radius: 28px;
      box-shadow: 0 16px 56px rgba(0, 0, 0, 0.18);
      padding: 3rem 2.75rem;
    }

    .auth-card__logo {
      text-align: center;
      margin-bottom: 1.75rem;
    }

    .auth-card__logo-img {
      max-width: 120px;
      height: auto;
      border-radius: 18px;
      margin-bottom: 0.85rem;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .auth-card__logo-name {
      font-family: 'Playfair Display', serif;
      font-size: 1.6rem;
      color: #7a1f2e !important;
      font-weight: 700;
    }

    .auth-card__logo-sub {
      font-size: 0.75rem;
      color: #5a4f45 !important;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      margin-top: 0.3rem;
    }

    h2 {
      font-family: 'Playfair Display', serif;
      font-size: 1.75rem;
      margin-bottom: 1.5rem;
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
      font-size: 0.9rem;
      font-weight: 600;
      color: #2a1f15 !important;
    }

    .form-group .input-wrapper {
      position: relative;
    }

    .form-group input {
      padding: 0.85rem 1.1rem;
      border: 1px solid #d4c9b8;
      border-radius: 12px;
      font-size: 1rem;
      font-family: 'Lato', sans-serif;
      background: rgba(255, 255, 255, 0.7);
      color: #2a1f15 !important;
      transition: all 0.15s;
      width: 100%;
      padding-right: 2.8rem;
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
      padding: 0.9rem;
      background: linear-gradient(135deg, #4e6040, #6b8a5c);
      color: #fff !important;
      border: none;
      border-radius: 12px;
      font-size: 1rem;
      font-weight: 700;
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
      padding: 0.9rem;
      background: #fff;
      color: #3c4043;
      border: 1px solid #dadce0;
      border-radius: 12px;
      font-size: 1rem;
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

    /* ── Forgot Password Modal ── */
    .fp-overlay {
      display: none;
      position: fixed;
      inset: 0;
      z-index: 9999;
      background: rgba(42, 31, 21, 0.55);
      backdrop-filter: blur(6px);
      -webkit-backdrop-filter: blur(6px);
      align-items: center;
      justify-content: center;
      padding: 16px;
    }
    .fp-overlay.open { display: flex; }

    .fp-card {
      background: rgba(255,255,255,0.97);
      border-radius: 20px;
      box-shadow: 0 16px 56px rgba(0,0,0,0.22);
      width: 100%;
      max-width: 420px;
      overflow: hidden;
      animation: fpSlideIn 0.25s cubic-bezier(.34,1.56,.64,1);
      font-family: 'Lato', sans-serif;
    }
    @keyframes fpSlideIn {
      from { opacity:0; transform: translateY(-20px) scale(0.96); }
      to   { opacity:1; transform: translateY(0)     scale(1);    }
    }

    .fp-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 18px 22px 14px;
      background: linear-gradient(135deg, #4e6040, #6b8a5c);
    }
    .fp-header-left {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .fp-header-icon {
      font-size: 1.2rem;
      color: #fff;
    }
    .fp-header-title {
      font-family: 'Playfair Display', serif;
      font-size: 1.05rem;
      font-weight: 700;
      color: #fff;
    }
    .fp-close {
      background: rgba(255,255,255,0.18);
      border: none;
      cursor: pointer;
      width: 28px;
      height: 28px;
      border-radius: 50%;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.15s;
    }
    .fp-close:hover { background: rgba(255,255,255,0.32); }

    .fp-body { padding: 22px 22px 18px; }

    .fp-step { display: none; }
    .fp-step.active { display: block; }

    .fp-desc {
      font-size: 0.84rem;
      color: #5a4f45;
      margin-bottom: 16px;
      line-height: 1.5;
    }

    .fp-field {
      margin-bottom: 14px;
    }
    .fp-label {
      display: block;
      font-size: 0.72rem;
      font-weight: 700;
      color: #4a3f35;
      text-transform: uppercase;
      letter-spacing: 0.07em;
      margin-bottom: 5px;
    }
    .fp-input {
      width: 100%;
      padding: 10px 14px;
      border: 1.5px solid #e8dfd2;
      border-radius: 10px;
      font-size: 0.9rem;
      font-family: 'Lato', sans-serif;
      color: #2a1f15;
      background: #fff;
      outline: none;
      box-sizing: border-box;
      transition: border-color 0.15s, box-shadow 0.15s;
    }
    .fp-input:focus {
      border-color: #4e6040;
      box-shadow: 0 0 0 3px rgba(78,96,64,0.12);
    }
    .fp-input.fp-input--error { border-color: #c0392b; }

    .fp-field-err {
      display: none;
      font-size: 0.73rem;
      color: #c0392b;
      margin-top: 4px;
    }
    .fp-field-err.visible { display: block; }

    .fp-pw-wrap { position: relative; }
    .fp-pw-toggle {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      color: #8a7f72;
      padding: 4px;
      display: flex;
      align-items: center;
    }
    .fp-pw-toggle svg { width: 18px; height: 18px; }

    .fp-strength {
      display: flex;
      gap: 4px;
      margin-top: 6px;
    }
    .fp-strength-bar {
      flex: 1;
      height: 3px;
      border-radius: 2px;
      background: #e8dfd2;
      transition: background 0.2s;
    }

    .fp-alert {
      padding: 9px 12px;
      border-radius: 9px;
      font-size: 0.82rem;
      margin-bottom: 14px;
      display: none;
    }
    .fp-alert.visible { display: block; }
    .fp-alert--error   { background: #fdecea; border: 1px solid #f5c6cb; color: #c0392b; }
    .fp-alert--success { background: #eaf4ec; border: 1px solid #c3e6cb; color: #2d6a4f; }

    .fp-success-icon {
      text-align: center;
      padding: 8px 0 16px;
    }
    .fp-success-icon span {
      font-size: 3rem;
      color: #4e6040;
    }

    .fp-footer {
      display: flex;
      justify-content: flex-end;
      gap: 8px;
      padding: 0 22px 20px;
    }
    .fp-btn {
      padding: 10px 20px;
      border-radius: 10px;
      font-family: 'Lato', sans-serif;
      font-size: 0.88rem;
      font-weight: 700;
      cursor: pointer;
      transition: opacity 0.15s, transform 0.15s;
      display: flex;
      align-items: center;
      gap: 6px;
      border: none;
    }
    .fp-btn:hover { opacity: 0.88; transform: translateY(-1px); }
    .fp-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
    .fp-btn--ghost {
      background: #fff;
      color: #4a3f35;
      border: 1.5px solid #d4c9b8 !important;
    }
    .fp-btn--primary {
      background: linear-gradient(135deg, #4e6040, #6b8a5c);
      color: #fff;
      box-shadow: 0 2px 8px rgba(78,96,64,0.25);
    }
    .fp-btn--success {
      background: linear-gradient(135deg, #2d6a4f, #4e6040);
      color: #fff;
    }
    .fp-step-indicator {
      display: flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 16px;
    }
    .fp-dot {
      width: 8px; height: 8px;
      border-radius: 50%;
      background: #e8dfd2;
      transition: background 0.2s;
    }
    .fp-dot.active { background: #4e6040; }
    .fp-dot.done   { background: #6b8a5c; }

    /* ── Verification code input ── */
    .fp-code-wrap {
      display: flex;
      gap: 8px;
      justify-content: center;
      margin: 8px 0 4px;
    }
    .fp-code-digit {
      width: 44px;
      height: 52px;
      text-align: center;
      font-size: 1.4rem;
      font-weight: 700;
      font-family: 'Lato', sans-serif;
      border: 2px solid #e8dfd2;
      border-radius: 10px;
      color: #2a1f15;
      background: #fff;
      outline: none;
      transition: border-color 0.15s, box-shadow 0.15s;
      caret-color: transparent;
    }
    .fp-code-digit:focus {
      border-color: #4e6040;
      box-shadow: 0 0 0 3px rgba(78,96,64,0.12);
    }
    .fp-code-digit.fp-input--error {
      border-color: #c0392b;
    }
    .fp-code-hint {
      font-size: 0.75rem;
      color: #8a7f72;
      text-align: center;
      margin-top: 6px;
    }
    .fp-resend-wrap {
      text-align: center;
      margin-top: 10px;
      font-size: 0.8rem;
      color: #8a7f72;
    }
    .fp-resend-btn {
      background: none;
      border: none;
      color: #4e6040;
      font-weight: 700;
      cursor: pointer;
      font-size: 0.8rem;
      padding: 0;
      text-decoration: underline;
    }
    .fp-resend-btn:disabled {
      color: #b0a898;
      cursor: default;
      text-decoration: none;
    }
    /* dev-only code hint box */
    .fp-dev-code {
      background: #fffbe6;
      border: 1px dashed #e6c200;
      border-radius: 8px;
      padding: 8px 12px;
      font-size: 0.78rem;
      color: #7a6000;
      text-align: center;
      margin-bottom: 12px;
    }

    @media (max-width: 600px) {
      body { padding: 1rem 0.75rem; align-items: flex-start; padding-top: 1.5rem; }
      .auth-card {
        padding: 2rem 1.5rem;
        max-width: 100%;
        border-radius: 20px;
      }
      .auth-card__logo-img { max-width: 90px; }
      .auth-card__logo-name { font-size: 1.35rem; }
      h2 { font-size: 1.4rem; margin-bottom: 1.1rem; }
      .form-group input { font-size: 0.95rem; padding: 0.8rem 1rem; padding-right: 2.8rem; }
      .submit-btn, .google-btn { font-size: 0.95rem; padding: 0.8rem; }
      .form-options { flex-direction: column; gap: 0.5rem; align-items: flex-start; }
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

    <!-- reCAPTCHA rendered explicitly via grecaptcha.render() in onRecaptchaLoad
         to avoid the "Cannot read properties of null (reading 'style')" error
         that occurs with auto-render inside flex/transformed containers -->
    <div id="recaptcha-container" class="g-recaptcha"></div>

    <button type="submit" class="submit-btn" id="login-btn">
      <span class="spinner"></span>
      <span class="btn-text">Log In</span>
    </button>
  </form>

  <div class="auth-footer">
    Don't have an account? <a href="signup.php">Sign up</a>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     FORGOT PASSWORD MODAL
     Step 1 — Enter username + registered email (identity check)
     Step 2 — Enter + confirm new password
     Step 3 — Success confirmation
     No email server needed: email is used as a security question.
     ══════════════════════════════════════════════════════════ -->
<div class="fp-overlay" id="fpOverlay" role="dialog" aria-modal="true" aria-labelledby="fpTitle">
  <div class="fp-card">

    <!-- Header -->
    <div class="fp-header">
      <div class="fp-header-left">
        <span class="material-symbols-outlined fp-header-icon">lock_reset</span>
        <span class="fp-header-title" id="fpTitle">Reset Password</span>
      </div>
      <button class="fp-close" id="fpClose" aria-label="Close">
        <span class="material-symbols-outlined" style="font-size:1rem;">close</span>
      </button>
    </div>

    <div class="fp-body">

      <!-- Step indicator -->
      <div class="fp-step-indicator" id="fpDots">
        <div class="fp-dot active" id="fpDot1"></div>
        <div class="fp-dot"        id="fpDot2"></div>
        <div class="fp-dot"        id="fpDot3"></div>
        <div class="fp-dot"        id="fpDot4"></div>
      </div>

      <!-- Alert banner (shared across steps) -->
      <div class="fp-alert" id="fpAlert"></div>

      <!-- ── Step 1: Identity verification ── -->
      <div class="fp-step active" id="fpStep1">
        <p class="fp-desc">Enter your username and the email address registered to your account.</p>

        <div class="fp-field">
          <label class="fp-label" for="fpUsername">Username <span style="color:#c0392b;">*</span></label>
          <input class="fp-input" type="text" id="fpUsername" autocomplete="username"
                 placeholder="Your username" />
          <div class="fp-field-err" id="fpUsernameErr">Username is required.</div>
        </div>

        <div class="fp-field">
          <label class="fp-label" for="fpEmail">Registered Email <span style="color:#c0392b;">*</span></label>
          <input class="fp-input" type="email" id="fpEmail" autocomplete="email"
                 placeholder="email@example.com" />
          <div class="fp-field-err" id="fpEmailErr">A valid email is required.</div>
        </div>
      </div>

      <!-- ── Step 2: Verification code ── -->
      <div class="fp-step" id="fpStep2">
        <p class="fp-desc">
          A 6-digit verification code was sent to the email for
          <strong id="fpWorkerName"></strong>. Enter it below.
        </p>

        <!-- Dev-mode: shows the code returned by the API until email is wired up -->
        <div class="fp-dev-code" id="fpDevCode" style="display:none;">
          🔑 Dev mode — your code: <strong id="fpDevCodeValue"></strong>
        </div>

        <div class="fp-field">
          <label class="fp-label" style="text-align:center;display:block;">Verification Code</label>
          <div class="fp-code-wrap" id="fpCodeWrap">
            <input class="fp-code-digit" type="text" inputmode="numeric" maxlength="1" id="fpCode0" autocomplete="one-time-code" />
            <input class="fp-code-digit" type="text" inputmode="numeric" maxlength="1" id="fpCode1" />
            <input class="fp-code-digit" type="text" inputmode="numeric" maxlength="1" id="fpCode2" />
            <input class="fp-code-digit" type="text" inputmode="numeric" maxlength="1" id="fpCode3" />
            <input class="fp-code-digit" type="text" inputmode="numeric" maxlength="1" id="fpCode4" />
            <input class="fp-code-digit" type="text" inputmode="numeric" maxlength="1" id="fpCode5" />
          </div>
          <div class="fp-code-hint">Enter the 6-digit code</div>
          <div class="fp-field-err" id="fpCodeErr" style="text-align:center;"></div>
        </div>

        <div class="fp-resend-wrap">
          Didn't receive it?
          <button type="button" class="fp-resend-btn" id="fpResendBtn">Resend code</button>
          <span id="fpResendTimer" style="display:none;"></span>
        </div>
      </div>

      <!-- ── Step 3: New password ── -->
      <div class="fp-step" id="fpStep3">
        <p class="fp-desc">
          Identity verified for <strong id="fpWorkerName2"></strong>.
          Choose a strong new password.
        </p>

        <div class="fp-field">
          <label class="fp-label" for="fpNewPw">New Password <span style="color:#c0392b;">*</span></label>
          <div class="fp-pw-wrap">
            <input class="fp-input" type="password" id="fpNewPw"
                   autocomplete="new-password" placeholder="Min 8 chars, 1 uppercase, 1 number"
                   oninput="fpCheckStrength(this.value)" />
            <button type="button" class="fp-pw-toggle" onclick="fpTogglePw('fpNewPw',this)" aria-label="Toggle">
              <svg id="fpEye1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </button>
          </div>
          <div class="fp-strength" id="fpStrength">
            <div class="fp-strength-bar" id="fpBar1"></div>
            <div class="fp-strength-bar" id="fpBar2"></div>
            <div class="fp-strength-bar" id="fpBar3"></div>
            <div class="fp-strength-bar" id="fpBar4"></div>
          </div>
          <div class="fp-field-err" id="fpNewPwErr"></div>
        </div>

        <div class="fp-field">
          <label class="fp-label" for="fpConfirmPw">Confirm Password <span style="color:#c0392b;">*</span></label>
          <div class="fp-pw-wrap">
            <input class="fp-input" type="password" id="fpConfirmPw"
                   autocomplete="new-password" placeholder="Repeat new password" />
            <button type="button" class="fp-pw-toggle" onclick="fpTogglePw('fpConfirmPw',this)" aria-label="Toggle">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </button>
          </div>
          <div class="fp-field-err" id="fpConfirmPwErr"></div>
        </div>
      </div>

      <!-- ── Step 4: Success ── -->
      <div class="fp-step" id="fpStep4">
        <div class="fp-success-icon">
          <span class="material-symbols-outlined">check_circle</span>
        </div>
        <p class="fp-desc" style="text-align:center;font-size:0.9rem;">
          Your password has been reset successfully.<br>
          You can now log in with your new password.
        </p>
      </div>

    </div><!-- /fp-body -->

    <!-- Footer buttons -->
    <div class="fp-footer" id="fpFooter">
      <button class="fp-btn fp-btn--ghost" id="fpCancelBtn">Cancel</button>
      <button class="fp-btn fp-btn--primary" id="fpNextBtn">
        <span id="fpNextSpinner" style="display:none;width:14px;height:14px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;"></span>
        <span id="fpNextLabel">Verify Identity</span>
      </button>
    </div>

  </div><!-- /fp-card -->
</div><!-- /fpOverlay -->

<script>
const API_BASE       = '../dairy_farm_backend/api/v1'; // matches BASE_URL in api.js
const alertContainer = document.getElementById('alert-container');
const loginBtn       = document.getElementById('login-btn');
const form           = document.getElementById('login-form');

// recaptchaWidgetId and onRecaptchaLoad are defined in <head> before the
// reCAPTCHA script loads — do NOT redeclare them here.

// Helper: get reCAPTCHA response safely
function getRecaptchaToken() {
  if (typeof grecaptcha === 'undefined') return '';
  // Use widget ID if available, otherwise fall back to default widget (id 0)
  return recaptchaWidgetId !== null
    ? grecaptcha.getResponse(recaptchaWidgetId)
    : grecaptcha.getResponse();
}

// Helper: reset reCAPTCHA safely
function resetRecaptcha() {
  if (typeof grecaptcha === 'undefined') return;
  try {
    recaptchaWidgetId !== null
      ? grecaptcha.reset(recaptchaWidgetId)
      : grecaptcha.reset();
  } catch(e) {}
}

// ── Check for URL parameters (e.g., errors) ──────────────
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('error') === 'google_user_not_found') {
  showError('Your Google account is not associated with any user in the system. Please contact your administrator.');
} else if (urlParams.get('error') === 'pending_approval') {
  showError('Your account is awaiting admin approval. You will be able to log in once approved.');
} else if (urlParams.get('error') === 'account_rejected') {
  showError('Your account registration was rejected. Please contact your administrator.');
}

// ── Google Login Handler ──────────────────────────────────
document.getElementById('google-login-btn').addEventListener('click', () => {
  window.location.href = `${API_BASE}/auth.php?action=google_login`;
});

// ── Password visibility toggle ────────────────────────────
const pwToggle     = document.getElementById('pw-toggle');
const passwordInput = document.getElementById('password');
const eyeHidden    = document.getElementById('eye-hidden');
const eyeVisible   = document.getElementById('eye-visible');

pwToggle.addEventListener('click', () => {
  const isPassword = passwordInput.type === 'password';
  passwordInput.type = isPassword ? 'text' : 'password';
  eyeHidden.style.display  = isPassword ? 'none'  : 'block';
  eyeVisible.style.display = isPassword ? 'block' : 'none';
  pwToggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
});

// ── Alert helpers ─────────────────────────────────────────
function showAlert(msg, type = 'error', duration = 5000) {
  const el = document.createElement('div');
  el.className = `alert alert--${type}`;
  el.textContent = msg;
  el.style.display = 'block';
  alertContainer.appendChild(el);

  if (duration > 0) {
    setTimeout(() => {
      el.style.opacity = '0';
      el.style.transition = 'opacity .3s';
      setTimeout(() => el.remove(), 300);
    }, duration);
  }
  return el;
}

function showError(msg) {
  alertContainer.innerHTML = '';
  showAlert(msg, 'error', 6000);
}

// ── Field validation ──────────────────────────────────────
function validateField(input, errorId, validationFn) {
  const errorEl = document.getElementById(errorId);
  const value   = input.value.trim();
  const error   = validationFn(value);

  if (error) {
    input.classList.add('error');
    errorEl.textContent = error;
    errorEl.classList.add('visible');
    return false;
  }
  input.classList.remove('error');
  errorEl.textContent = '';
  errorEl.classList.remove('visible');
  return true;
}

document.getElementById('username').addEventListener('input', function () {
  validateField(this, 'username-error', v => v ? null : 'Username is required.');
});

document.getElementById('password').addEventListener('input', function () {
  validateField(this, 'password-error', v => v ? null : 'Password is required.');
});

// ── Forgot password handler ───────────────────────────────
document.getElementById('forgot-password-link').addEventListener('click', (e) => {
  e.preventDefault();
  fpOpen();
});

// ── Form submission ───────────────────────────────────────
form.addEventListener('submit', async (e) => {
  e.preventDefault();
  alertContainer.innerHTML = '';

  const usernameInput = document.getElementById('username');
  const rememberMe    = document.getElementById('remember').checked;
  const username      = usernameInput.value.trim();
  const password      = passwordInput.value;

  const usernameValid = validateField(usernameInput, 'username-error', v => {
    if (!v)          return 'Username is required.';
    if (v.length < 3) return 'Username must be at least 3 characters.';
    return null;
  });

  const passwordValid = validateField(passwordInput, 'password-error', v =>
    v ? null : 'Password is required.'
  );

  if (!usernameValid || !passwordValid) return;

  // reCAPTCHA check — widget renders via grecaptcha.ready() after DOM load
  if (typeof grecaptcha === 'undefined') {
    showError('reCAPTCHA is still loading. Please wait a moment and try again.');
    return;
  }
  const recaptchaToken = getRecaptchaToken();
  if (!recaptchaToken) {
    showError('Please complete the reCAPTCHA verification.');
    return;
  }

  // Loading state
  loginBtn.disabled = true;
  loginBtn.classList.add('loading');
  loginBtn.querySelector('.btn-text').textContent = 'Logging in…';

  try {
    // NOTE: No X-CSRF-Token header here — the login endpoint is unauthenticated.
    // The CSRF token is issued by the server AFTER a successful login and stored
    // in localStorage for use by subsequent authenticated API calls.
    const response = await fetch(`${API_BASE}/auth.php?action=login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({
        username,
        password,
        'g-recaptcha-response': recaptchaToken,
      }),
    });

    const data = await response.json();

    if (data.success) {
      if (rememberMe) {
        localStorage.setItem('remembered_username', username);
        localStorage.setItem('remember_me', 'true');
      } else {
        localStorage.removeItem('remembered_username');
        localStorage.removeItem('remember_me');
      }

      // Store CSRF token and user info for authenticated requests
      localStorage.setItem('csrf_token', data.data.csrf_token);
      localStorage.setItem('user', JSON.stringify(data.data.user));

      const role     = data.data.user?.role || '';
      const greeting = role === 'Admin' ? 'Welcome Admin!' : `Welcome ${role || 'back'}!`;
      showAlert(greeting + ' Redirecting…', 'success', 1000);
      setTimeout(() => { window.location.href = 'index.php'; }, 800);

    } else {
      showError(data.message || 'Login failed. Please check your credentials.');
      passwordInput.value = '';
      resetRecaptcha();
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

// ── Restore remembered username ───────────────────────────
(function restoreRememberedUser() {
  const remembered = localStorage.getItem('remembered_username');
  const rememberOn = localStorage.getItem('remember_me') === 'true';
  if (remembered && rememberOn) {
    document.getElementById('username').value = remembered;
    document.getElementById('remember').checked = true;
    document.getElementById('password').focus();
  } else {
    document.getElementById('username').focus();
  }
})();
</script>

</body>
</html>

<script>
// ══════════════════════════════════════════════════════════
// FORGOT PASSWORD MODAL  (4 steps)
// 1 — username + email
// 2 — 6-digit verification code
// 3 — new password
// 4 — success
// ══════════════════════════════════════════════════════════
const FP_API = '../dairy_farm_backend/api/v1/reset_password.php';

let fpCurrentStep  = 1;
let fpResetToken   = null;
let fpResendTimer  = null;

const fpOverlay    = document.getElementById('fpOverlay');
const fpAlert      = document.getElementById('fpAlert');
const fpNextBtn    = document.getElementById('fpNextBtn');
const fpNextLabel  = document.getElementById('fpNextLabel');
const fpNextSpinner= document.getElementById('fpNextSpinner');
const fpCancelBtn  = document.getElementById('fpCancelBtn');

// ── Open / Close ──────────────────────────────────────────
function fpOpen() {
  fpReset();
  fpOverlay.classList.add('open');
  setTimeout(() => document.getElementById('fpUsername').focus(), 80);
}

function fpClose() {
  fpOverlay.classList.remove('open');
  clearInterval(fpResendTimer);
}

function fpReset() {
  fpCurrentStep = 1;
  fpResetToken  = null;
  clearInterval(fpResendTimer);
  fpShowStep(1);
  fpHideAlert();
  ['fpUsername','fpEmail','fpNewPw','fpConfirmPw'].forEach(id => {
    const el = document.getElementById(id);
    if (el) { el.value = ''; el.classList.remove('fp-input--error'); }
  });
  // Clear code digits
  for (let i = 0; i < 6; i++) {
    const d = document.getElementById('fpCode' + i);
    if (d) { d.value = ''; d.classList.remove('fp-input--error'); }
  }
  ['fpUsernameErr','fpEmailErr','fpCodeErr','fpNewPwErr','fpConfirmPwErr'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.classList.remove('visible');
  });
  document.getElementById('fpDevCode').style.display = 'none';
  fpCheckStrength('');
  fpNextLabel.textContent = 'Verify Identity';
  fpNextBtn.style.display = '';
  fpCancelBtn.textContent = 'Cancel';
  fpCancelBtn.className   = 'fp-btn fp-btn--ghost';
}

document.getElementById('fpClose').addEventListener('click', fpClose);
fpCancelBtn.addEventListener('click', () => fpClose());
fpOverlay.addEventListener('click', e => { if (e.target === fpOverlay) fpClose(); });
document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && fpOverlay.classList.contains('open')) fpClose();
});

// ── Step navigation ───────────────────────────────────────
function fpShowStep(n) {
  [1,2,3,4].forEach(i => {
    const step = document.getElementById('fpStep' + i);
    const dot  = document.getElementById('fpDot'  + i);
    if (step) step.classList.toggle('active', i === n);
    if (dot)  {
      dot.classList.toggle('active', i === n);
      dot.classList.toggle('done',   i < n);
    }
  });
  fpCurrentStep = n;
}

// ── Alert helpers ─────────────────────────────────────────
function fpShowAlert(msg, type = 'error') {
  fpAlert.textContent = msg;
  fpAlert.className   = 'fp-alert fp-alert--' + type + ' visible';
}
function fpHideAlert() {
  fpAlert.className = 'fp-alert';
  fpAlert.textContent = '';
}

// ── Field error helpers ───────────────────────────────────
function fpFieldErr(inputId, errId, msg) {
  const inp = document.getElementById(inputId);
  const err = document.getElementById(errId);
  if (inp) inp.classList.add('fp-input--error');
  if (err) { err.textContent = msg; err.classList.add('visible'); }
}
function fpFieldOk(inputId, errId) {
  const inp = document.getElementById(inputId);
  const err = document.getElementById(errId);
  if (inp) inp.classList.remove('fp-input--error');
  if (err) err.classList.remove('visible');
}

// ── Password strength meter ───────────────────────────────
function fpCheckStrength(pw) {
  const bars   = [1,2,3,4].map(i => document.getElementById('fpBar' + i));
  const colors = ['#c0392b','#e67e22','#f1c40f','#27ae60'];
  let score = 0;
  if (pw.length >= 8)              score++;
  if (/[A-Z]/.test(pw))            score++;
  if (/[0-9]/.test(pw))            score++;
  if (/[^A-Za-z0-9]/.test(pw))     score++;
  bars.forEach((b, i) => {
    b.style.background = i < score ? colors[score - 1] : '#e8dfd2';
  });
}

// ── Password visibility toggle ────────────────────────────
function fpTogglePw(inputId, btn) {
  const inp = document.getElementById(inputId);
  const isHidden = inp.type === 'password';
  inp.type = isHidden ? 'text' : 'password';
  btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
}

// ── Loading state ─────────────────────────────────────────
function fpSetLoading(loading) {
  fpNextBtn.disabled          = loading;
  fpNextSpinner.style.display = loading ? 'block' : 'none';
}

// ── 6-digit code input behaviour ─────────────────────────
(function initCodeInputs() {
  const digits = Array.from({length: 6}, (_, i) => document.getElementById('fpCode' + i));

  digits.forEach((inp, idx) => {
    inp.addEventListener('input', e => {
      // Allow only digits
      inp.value = inp.value.replace(/\D/g, '').slice(-1);
      if (inp.value && idx < 5) digits[idx + 1].focus();
      // Auto-submit when all filled
      if (digits.every(d => d.value)) fpNextBtn.click();
    });

    inp.addEventListener('keydown', e => {
      if (e.key === 'Backspace' && !inp.value && idx > 0) {
        digits[idx - 1].value = '';
        digits[idx - 1].focus();
      }
      if (e.key === 'ArrowLeft'  && idx > 0) digits[idx - 1].focus();
      if (e.key === 'ArrowRight' && idx < 5) digits[idx + 1].focus();
    });

    // Handle paste of full code
    inp.addEventListener('paste', e => {
      e.preventDefault();
      const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
      pasted.split('').slice(0, 6).forEach((ch, i) => {
        if (digits[i]) digits[i].value = ch;
      });
      const next = Math.min(pasted.length, 5);
      digits[next].focus();
      if (pasted.length >= 6) fpNextBtn.click();
    });
  });
})();

function fpGetCode() {
  return Array.from({length: 6}, (_, i) => document.getElementById('fpCode' + i).value).join('');
}

// ── Resend countdown ──────────────────────────────────────
function fpStartResendCountdown(seconds = 60) {
  const btn   = document.getElementById('fpResendBtn');
  const timer = document.getElementById('fpResendTimer');
  btn.disabled = true;
  timer.style.display = 'inline';
  let remaining = seconds;
  timer.textContent = ` (${remaining}s)`;
  clearInterval(fpResendTimer);
  fpResendTimer = setInterval(() => {
    remaining--;
    if (remaining <= 0) {
      clearInterval(fpResendTimer);
      btn.disabled = false;
      timer.style.display = 'none';
    } else {
      timer.textContent = ` (${remaining}s)`;
    }
  }, 1000);
}

document.getElementById('fpResendBtn').addEventListener('click', async () => {
  fpHideAlert();
  const username = document.getElementById('fpUsername').value.trim();
  const email    = document.getElementById('fpEmail').value.trim();
  // Clear digits
  for (let i = 0; i < 6; i++) {
    const d = document.getElementById('fpCode' + i);
    if (d) { d.value = ''; d.classList.remove('fp-input--error'); }
  }
  fpFieldOk(null, 'fpCodeErr');
  document.getElementById('fpResendBtn').disabled = true;

  try {
    const res  = await fetch(FP_API + '?action=verify_identity', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ username, email }),
    });
    const data = await res.json();
    if (data.success) {
      fpShowAlert('A new code has been sent.', 'success');
      // Dev mode: show new code
      if (data.data && data.data.code) {
        document.getElementById('fpDevCodeValue').textContent = data.data.code;
        document.getElementById('fpDevCode').style.display = 'block';
      }
      fpStartResendCountdown(60);
      setTimeout(() => document.getElementById('fpCode0').focus(), 60);
    } else {
      fpShowAlert(data.message || 'Could not resend code. Please try again.');
      document.getElementById('fpResendBtn').disabled = false;
    }
  } catch (e) {
    fpShowAlert('Network error. Please try again.');
    document.getElementById('fpResendBtn').disabled = false;
  }
});

// ── Main action button ────────────────────────────────────
fpNextBtn.addEventListener('click', async () => {
  fpHideAlert();
  if      (fpCurrentStep === 1) await fpVerifyIdentity();
  else if (fpCurrentStep === 2) await fpVerifyCode();
  else if (fpCurrentStep === 3) await fpDoReset();
  else if (fpCurrentStep === 4) fpClose();
});

// ── Step 1: verify identity ───────────────────────────────
async function fpVerifyIdentity() {
  const username = document.getElementById('fpUsername').value.trim();
  const email    = document.getElementById('fpEmail').value.trim();
  let valid = true;

  if (!username) {
    fpFieldErr('fpUsername', 'fpUsernameErr', 'Username is required.'); valid = false;
  } else { fpFieldOk('fpUsername', 'fpUsernameErr'); }

  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    fpFieldErr('fpEmail', 'fpEmailErr', 'A valid email address is required.'); valid = false;
  } else { fpFieldOk('fpEmail', 'fpEmailErr'); }

  if (!valid) return;

  fpSetLoading(true);
  fpNextLabel.textContent = 'Sending…';

  try {
    const res  = await fetch(FP_API + '?action=verify_identity', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ username, email }),
    });
    const data = await res.json();

    if (data.success) {
      document.getElementById('fpWorkerName').textContent  = data.data.worker_name;
      document.getElementById('fpWorkerName2').textContent = data.data.worker_name;
      // Dev mode: display the code in the UI
      if (data.data && data.data.code) {
        document.getElementById('fpDevCodeValue').textContent = data.data.code;
        document.getElementById('fpDevCode').style.display = 'block';
      }
      fpShowStep(2);
      fpNextLabel.textContent = 'Verify Code';
      fpStartResendCountdown(60);
      setTimeout(() => document.getElementById('fpCode0').focus(), 60);
    } else {
      fpShowAlert(data.message || 'Verification failed. Please check your details.');
    }
  } catch (e) {
    fpShowAlert('Network error. Please try again.');
  } finally {
    fpSetLoading(false);
    if (fpCurrentStep === 1) fpNextLabel.textContent = 'Verify Identity';
  }
}

// ── Step 2: verify code ───────────────────────────────────
async function fpVerifyCode() {
  const code = fpGetCode();

  if (code.length < 6) {
    fpFieldErr(null, 'fpCodeErr', 'Please enter the full 6-digit code.');
    // Highlight empty boxes
    for (let i = 0; i < 6; i++) {
      const d = document.getElementById('fpCode' + i);
      if (!d.value) d.classList.add('fp-input--error');
    }
    return;
  }
  // Clear error state
  for (let i = 0; i < 6; i++) document.getElementById('fpCode' + i).classList.remove('fp-input--error');
  fpFieldOk(null, 'fpCodeErr');

  fpSetLoading(true);
  fpNextLabel.textContent = 'Verifying…';

  try {
    const res  = await fetch(FP_API + '?action=verify_code', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ code }),
    });
    const data = await res.json();

    if (data.success) {
      fpResetToken = data.data.token;
      clearInterval(fpResendTimer);
      fpShowStep(3);
      fpNextLabel.textContent = 'Reset Password';
      setTimeout(() => document.getElementById('fpNewPw').focus(), 60);
    } else {
      fpShowAlert(data.message || 'Incorrect code. Please try again.');
      // Shake the boxes and clear them
      for (let i = 0; i < 6; i++) {
        const d = document.getElementById('fpCode' + i);
        d.value = '';
        d.classList.add('fp-input--error');
      }
      setTimeout(() => document.getElementById('fpCode0').focus(), 60);
      // If too many attempts, go back to step 1
      if (res.status === 429) {
        setTimeout(() => { fpShowStep(1); fpNextLabel.textContent = 'Verify Identity'; }, 1800);
      }
    }
  } catch (e) {
    fpShowAlert('Network error. Please try again.');
  } finally {
    fpSetLoading(false);
    if (fpCurrentStep === 2) fpNextLabel.textContent = 'Verify Code';
  }
}

// ── Step 3: reset password ────────────────────────────────
async function fpDoReset() {
  const pw      = document.getElementById('fpNewPw').value;
  const confirm = document.getElementById('fpConfirmPw').value;
  let valid = true;

  if (pw.length < 8) {
    fpFieldErr('fpNewPw', 'fpNewPwErr', 'Password must be at least 8 characters.'); valid = false;
  } else if (!/[A-Z]/.test(pw)) {
    fpFieldErr('fpNewPw', 'fpNewPwErr', 'Must contain at least one uppercase letter.'); valid = false;
  } else if (!/[0-9]/.test(pw)) {
    fpFieldErr('fpNewPw', 'fpNewPwErr', 'Must contain at least one number.'); valid = false;
  } else {
    fpFieldOk('fpNewPw', 'fpNewPwErr');
  }

  if (pw !== confirm) {
    fpFieldErr('fpConfirmPw', 'fpConfirmPwErr', 'Passwords do not match.'); valid = false;
  } else if (confirm) {
    fpFieldOk('fpConfirmPw', 'fpConfirmPwErr');
  }

  if (!valid) return;

  fpSetLoading(true);
  fpNextLabel.textContent = 'Saving…';

  try {
    const res  = await fetch(FP_API + '?action=reset', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({
        token:            fpResetToken,
        password:         pw,
        password_confirm: confirm,
      }),
    });
    const data = await res.json();

    if (data.success) {
      fpShowStep(4);
      fpNextBtn.style.display = 'none';
      fpCancelBtn.textContent = 'Back to Login';
      fpCancelBtn.classList.remove('fp-btn--ghost');
      fpCancelBtn.classList.add('fp-btn--success');
    } else {
      fpShowAlert(data.message || 'Reset failed. Please try again.');
      if (data.message && data.message.includes('expired')) {
        setTimeout(() => { fpShowStep(1); fpNextLabel.textContent = 'Verify Identity'; }, 1500);
      }
    }
  } catch (e) {
    fpShowAlert('Network error. Please try again.');
  } finally {
    fpSetLoading(false);
    if (fpCurrentStep === 3) fpNextLabel.textContent = 'Reset Password';
  }
}
</script>

</body>
</html>
