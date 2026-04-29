<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Customer Login — Esperon Dairy Farm</title>
  <link rel="stylesheet" href="css/style.css" />
  <style>
    body {
      display: flex; align-items: center; justify-content: center;
      min-height: 100vh;
      background: url('assets/bg.png') no-repeat center center fixed;
      background-size: cover;
      padding: 2rem 0;
    }
    .auth-card {
      width: 100%; max-width: 420px;
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
    h2 { font-family: 'Playfair Display', serif; font-size: 1.4rem; color: #2a1f15; text-align: center; margin-bottom: 1.25rem; }
    .form-group { display: flex; flex-direction: column; gap: 0.35rem; margin-bottom: 1rem; }
    .form-group label { font-size: 0.85rem; font-weight: 600; color: #2a1f15; }
    .form-group input {
      padding: 0.7rem 1rem; border: 1px solid #d4c9b8; border-radius: 12px;
      font-size: 0.92rem; font-family: 'Lato', sans-serif;
      background: rgba(255,255,255,0.7); color: #2a1f15;
      transition: all 0.15s; width: 100%; box-sizing: border-box;
    }
    .form-group input:focus { outline: none; border-color: #4e6040; box-shadow: 0 0 0 3px rgba(78,96,64,0.15); background: rgba(255,255,255,0.85); }
    .submit-btn {
      width: 100%; padding: 0.8rem; margin-top: 0.5rem;
      background: linear-gradient(135deg, #4e6040, #6b8a5c); color: #fff;
      border: none; border-radius: 12px; font-size: 0.95rem; font-weight: 600;
      font-family: 'Lato', sans-serif; cursor: pointer; transition: all 0.15s;
    }
    .submit-btn:hover { background: linear-gradient(135deg, #2d3b22, #4e6040); transform: translateY(-1px); }
    .submit-btn:disabled { background: #d4c9b8; cursor: not-allowed; transform: none; }
    .alert { padding: 0.7rem 0.9rem; border-radius: 10px; font-size: 0.85rem; margin-bottom: 1rem; display: none; }
    .alert--error   { background: #fdf0ef; border: 1px solid #f5c6cb; color: #c0392b; }
    .alert--success { background: #e8f0e0; border: 1px solid #c3e6cb; color: #2d3b22; }
    .auth-footer { text-align: center; margin-top: 1.25rem; font-size: 0.85rem; color: #5a4f45; }
    .auth-footer a { color: #4e6040; font-weight: 700; text-decoration: none; padding: 3px 10px; background: rgba(78,96,64,0.1); border-radius: 20px; }
    .auth-footer a:hover { background: rgba(78,96,64,0.2); }
    .divider { text-align: center; margin: 1rem 0; color: #8a7f72; font-size: 0.82rem; position: relative; }
    .divider::before, .divider::after { content: ''; position: absolute; top: 50%; width: 40%; height: 1px; background: #d4c9b8; }
    .divider::before { left: 0; } .divider::after { right: 0; }
  </style>
</head>
<body>
<div class="auth-card">
  <div class="auth-logo">
    <img src="assets/logo.jpg" alt="Esperon Dairy Farm" />
    <div class="auth-logo-name">Esperon Dairy Farm</div>
    <div class="auth-logo-sub">Customer Portal</div>
  </div>

  <h2>Welcome Back</h2>

  <div id="alert-box" class="alert"></div>

  <form id="login-form" novalidate>
    <div class="form-group">
      <label for="email">Email Address</label>
      <input type="email" id="email" placeholder="your@email.com" autocomplete="email" required />
    </div>
    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" placeholder="Your password" autocomplete="current-password" required />
    </div>
    <button type="submit" class="submit-btn" id="submit-btn">Sign In</button>
  </form>

  <div class="divider">or</div>

  <div class="auth-footer">
    New customer? <a href="customer_signup.php">Create account</a>
  </div>
  <div class="auth-footer" style="margin-top:0.5rem;">
    Are you staff? <a href="login.php">Staff login</a>
  </div>
</div>

<script>
const API = '../dairy_farm_backend/api';

function showAlert(msg, type) {
  const box = document.getElementById('alert-box');
  box.textContent = msg;
  box.className = 'alert alert--' + type;
  box.style.display = 'block';
}

document.getElementById('login-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const email    = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  const btn      = document.getElementById('submit-btn');

  if (!email || !password) { showAlert('Please enter your email and password.', 'error'); return; }

  btn.disabled = true; btn.textContent = 'Signing in…';

  try {
    const res  = await fetch(API + '/customer_auth.php?action=login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ email, password }),
    });
    const data = await res.json();

    if (data.success) {
      localStorage.setItem('customer',    JSON.stringify(data.data.customer));
      localStorage.setItem('csrf_token',  data.data.csrf_token);
      showAlert('Login successful! Redirecting…', 'success');
      setTimeout(() => { window.location.href = 'customer_dashboard.php'; }, 800);
    } else {
      showAlert(data.message || 'Login failed. Please check your credentials.', 'error');
      document.getElementById('password').value = '';
    }
  } catch(e) {
    showAlert('Network error. Please try again.', 'error');
  } finally {
    btn.disabled = false; btn.textContent = 'Sign In';
  }
});
</script>
</body>
</html>
