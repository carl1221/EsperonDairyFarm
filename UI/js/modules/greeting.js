// ============================================================
// js/modules/greeting.js  —  Greeting, tab switching, helpers
// ============================================================

// ── Helpers ───────────────────────────────────────────────
function getStoredUser() {
  try { return JSON.parse(localStorage.getItem('user') || '{}'); } catch { return {}; }
}
function nowTime() {
  return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
}
function todayStr() {
  return new Date().toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
}

// ── Greeting ──────────────────────────────────────────────
function renderGreeting() {
  const u = getStoredUser();
  const h = new Date().getHours();
  const tod = h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
  const greet = document.getElementById('page-greeting');
  const sub   = document.getElementById('page-subtitle');
  if (greet) greet.innerHTML = tod + ', ' + (u.name || 'Admin') + '! <span class="material-symbols-outlined" style="vertical-align:middle;font-size:1.5rem;">waving_hand</span>';
  if (sub)   sub.textContent = 'Welcome Admin \u00b7 Full farm control panel \u00b7 ' + todayStr();
}

// ── DASHBOARD TABS ────────────────────────────────────────
function switchTab(tabId, btn) {
  // Hide all panels
  document.querySelectorAll('.dash-tab-panel').forEach(function(p) {
    p.style.display = 'none';
  });
  // Deactivate all tab buttons
  document.querySelectorAll('.dash-tab').forEach(function(b) {
    b.classList.remove('dash-tab--active');
  });
  // Show selected panel
  var panel = document.getElementById(tabId);
  if (panel) panel.style.display = 'block';
  if (btn)   btn.classList.add('dash-tab--active');
  // Persist active tab
  try { localStorage.setItem('dash_active_tab', tabId); } catch(e) {}
}

// Restore last active tab on load
document.addEventListener('DOMContentLoaded', function() {
  var saved = localStorage.getItem('dash_active_tab') || 'tab-orders';
  var btn   = document.getElementById('btn-' + saved);
  if (btn) switchTab(saved, btn);
});
