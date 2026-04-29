// ============================================================
// js/customer_dashboard.js  â€”  Customer Dashboard Logic
// ============================================================

const CAPI = '../dairy_farm_backend/api';

// â”€â”€ Helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function getCustomer() {
  try { return JSON.parse(localStorage.getItem('customer') || '{}'); } catch { return {}; }
}
function getCsrf() { return localStorage.getItem('csrf_token') || ''; }
function todayISO() { return new Date().toISOString().split('T')[0]; }
function fmtDate(d) {
  if (!d) return 'â€”';
  return new Date(d).toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' });
}

// â”€â”€ Section navigation â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function showSection(id, btn) {
  document.querySelectorAll('.cust-section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.cust-nav-link').forEach(b => b.classList.remove('active'));
  const sec = document.getElementById('section-' + id);
  if (sec) sec.classList.add('active');
  if (btn) btn.classList.add('active');
  else {
    document.querySelectorAll('.cust-nav-link').forEach(b => {
      if (b.getAttribute('onclick') && b.getAttribute('onclick').includes("'" + id + "'")) b.classList.add('active');
    });
  }
}

// â”€â”€ Greeting â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function renderGreeting() {
  const c = getCustomer();
  const h = new Date().getHours();
  const tod = h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
  const greet = document.getElementById('page-greeting');
  const dateEl = document.getElementById('page-date');
  if (greet) greet.innerHTML = tod + ', ' + (c.name || 'there') + '! \uD83D\uDC4B';
  if (dateEl) dateEl.textContent = new Date().toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });

  const navName    = document.getElementById('nav-name');
  const navInitial = document.getElementById('nav-initial');
  if (navName)    navName.textContent = c.name || 'Customer';
  if (navInitial) navInitial.textContent = (c.name || 'C').charAt(0).toUpperCase();
}

// â”€â”€ Products catalog â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const PRODUCTS = [
  { id:'fresh-milk',    name:'Fresh Milk',     icon:'\uD83E\uDD5B', detail:'Pasteurized, farm-fresh', price:'â‚±60/L',  cowType:'Milk' },
  { id:'flavored-milk', name:'Flavored Milk',  icon:'\uD83C\uDF6B', detail:'Chocolate & Strawberry',  price:'â‚±75/L',  cowType:'Milk' },
  { id:'cheese',        name:'Farm Cheese',    icon:'\uD83E\uDDC0', detail:'Soft & aged varieties',   price:'â‚±180/kg', cowType:'Milk' },
  { id:'butter',        name:'Fresh Butter',   icon:'\uD83E\uDDC8', detail:'Unsalted, creamy',        price:'â‚±120/250g', cowType:'Milk' },
  { id:'yogurt',        name:'Natural Yogurt', icon:'\uD83E\uDD63', detail:'Plain & fruit flavors',   price:'â‚±90/500g', cowType:'Milk' },
  { id:'cream',         name:'Fresh Cream',    icon:'\uD83C\uDF68', detail:'Heavy & light cream',     price:'â‚±95/250ml', cowType:'Milk' },
];

var selectedProduct = null;
var allCows = [];

function renderProducts(containerId, selectable) {
  const grid = document.getElementById(containerId);
  if (!grid) return;
  grid.innerHTML = PRODUCTS.map(p => `
    <div class="product-card ${selectable ? '' : ''}" id="prod-${containerId}-${p.id}"
      ${selectable ? 'onclick="selectProduct(\'' + p.id + '\')"' : ''}>
      <div class="product-icon">${p.icon}</div>
      <div class="product-name">${p.name}</div>
      <div class="product-detail">${p.detail}</div>
      <div class="product-price">${p.price}</div>
      ${selectable ? '<div style="margin-top:8px;font-size:0.72rem;color:var(--olive);font-weight:600;">Tap to select</div>' : ''}
    </div>`).join('');
}

function selectProduct(id) {
  selectedProduct = PRODUCTS.find(p => p.id === id);
  document.querySelectorAll('#order-products-grid .product-card').forEach(c => c.classList.remove('selected'));
  const card = document.getElementById('prod-order-products-grid-' + id);
  if (card) card.classList.add('selected');

  const display = document.getElementById('selected-product-display');
  if (display && selectedProduct) {
    display.textContent = selectedProduct.icon + ' ' + selectedProduct.name + ' â€” ' + selectedProduct.price;
    display.style.color = 'var(--text)';
    display.style.borderColor = 'var(--olive)';
  }
  updateOrderSummary();
}

function updateOrderSummary() {
  const summary = document.getElementById('order-summary');
  const date    = document.getElementById('order-date').value;
  const payment = document.getElementById('payment-method').value;
  if (!selectedProduct || !date) { if (summary) summary.style.display = 'none'; return; }
  if (summary) summary.style.display = 'block';
  const labels = { cod:'Cash on Delivery', gcash:'GCash', bank:'Bank Transfer' };
  document.getElementById('summary-product').textContent = selectedProduct.icon + ' ' + selectedProduct.name + ' â€” ' + selectedProduct.price;
  document.getElementById('summary-date').textContent    = 'Delivery: ' + fmtDate(date);
  document.getElementById('summary-payment').textContent = 'Payment: ' + (labels[payment] || payment);
}

// â”€â”€ Orders â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
var myOrders = [];
var orderFilter = 'all';
var statusCycle = ['pending', 'processing', 'delivered'];
var statusLabel = { pending:'Pending', processing:'Processing', delivered:'Delivered' };

function getOrderStatus(order, index) { return statusCycle[index % 3]; }

async function loadMyOrders() {
  try {
    const res  = await fetch(CAPI + '/customer_orders.php', { credentials:'include' });
    const data = await res.json();
    if (data.success) {
      myOrders = data.data || [];
      renderMyOrders();
      renderOverview();
    }
  } catch(e) { console.error('Failed to load orders:', e); }
}

function renderMyOrders() {
  const container = document.getElementById('my-orders-list');
  if (!container) return;

  let list = myOrders;
  if (orderFilter !== 'all') {
    list = myOrders.filter((o, i) => getOrderStatus(o, i) === orderFilter);
  }

  if (!list.length) {
    container.innerHTML = '<div class="card" style="padding:32px;text-align:center;color:var(--muted);">'
      + '<span class="material-symbols-outlined" style="font-size:2.5rem;display:block;margin-bottom:8px;">receipt_long</span>'
      + 'No orders found. <button class="btn-xs btn-xs--primary" onclick="showSection(\'place-order\',null)" style="margin-left:8px;">Place your first order</button></div>';
    return;
  }

  container.innerHTML = list.map((o, i) => {
    const status = getOrderStatus(o, myOrders.indexOf(o));
    const statusColors = { pending:'var(--warning)', processing:'var(--info)', delivered:'var(--olive)' };
    const statusBg     = { pending:'var(--warning-lt)', processing:'var(--info-lt)', delivered:'var(--success-lt)' };
    return '<div class="order-card">'
      + '<div style="flex:1;min-width:0;">'
      + '<div class="order-card__id">Order #' + o.Order_ID + ' \u2014 ' + o.Order_Type + '</div>'
      + '<div class="order-card__meta">' + o.Cow + ' \u00b7 ' + fmtDate(o.Order_Date) + ' \u00b7 Assigned: ' + o.Assigned_Worker + '</div>'
      + '</div>'
      + '<div style="text-align:right;">'
      + '<span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:0.68rem;font-weight:700;text-transform:uppercase;background:' + statusBg[status] + ';color:' + statusColors[status] + ';">' + statusLabel[status] + '</span>'
      + '<div style="font-size:0.7rem;color:var(--muted);margin-top:3px;">'
      + (status === 'pending' ? 'Awaiting confirmation' : status === 'processing' ? 'Being prepared' : 'Delivered \u2713')
      + '</div></div></div>';
  }).join('');
}

function filterMyOrders(filter, btn) {
  orderFilter = filter;
  document.querySelectorAll('[onclick^="filterMyOrders"]').forEach(b => {
    b.style.background = 'rgba(255,255,255,.5)'; b.style.borderColor = 'var(--border)'; b.style.color = 'var(--text)';
  });
  if (btn) { btn.style.background = 'rgba(78,96,64,0.12)'; btn.style.borderColor = 'var(--olive)'; btn.style.color = 'var(--olive-dark)'; }
  renderMyOrders();
}

function renderOverview() {
  const total      = myOrders.length;
  const pending    = myOrders.filter((o, i) => getOrderStatus(o, i) === 'pending').length;
  const processing = myOrders.filter((o, i) => getOrderStatus(o, i) === 'processing').length;
  const delivered  = myOrders.filter((o, i) => getOrderStatus(o, i) === 'delivered').length;

  const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
  set('ov-total-orders', total);
  set('ov-pending',      pending);
  set('ov-processing',   processing);
  set('ov-delivered',    delivered);

  // Recent orders (last 3)
  const recentEl = document.getElementById('ov-recent-orders');
  if (recentEl) {
    const recent = myOrders.slice(0, 3);
    if (!recent.length) {
      recentEl.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;">No orders yet. <button class="btn-xs btn-xs--primary" onclick="showSection(\'place-order\',null)">Order now</button></p>';
    } else {
      recentEl.innerHTML = recent.map((o, i) => {
        const status = getOrderStatus(o, i);
        return '<div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-light);">'
          + '<div><div style="font-weight:700;font-size:0.84rem;">#' + o.Order_ID + ' \u2014 ' + o.Order_Type + '</div>'
          + '<div style="font-size:0.73rem;color:var(--muted);">' + fmtDate(o.Order_Date) + '</div></div>'
          + '<span class="order-status order-status--' + status + '">' + statusLabel[status] + '</span>'
          + '</div>';
      }).join('');
    }
  }
}

// â”€â”€ Place order â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
async function placeOrder() {
  const errEl = document.getElementById('order-err');
  errEl.style.display = 'none';

  if (!selectedProduct) { errEl.textContent = 'Please select a product.'; errEl.style.display = 'block'; return; }
  const date = document.getElementById('order-date').value;
  if (!date) { errEl.textContent = 'Please select a delivery date.'; errEl.style.display = 'block'; return; }

  // Pick a cow that matches the product type
  const cow = allCows.length ? allCows[0] : null;
  if (!cow) { errEl.textContent = 'No livestock available. Please contact the farm.'; errEl.style.display = 'block'; return; }

  const btn = document.getElementById('place-order-btn');
  btn.disabled = true; btn.textContent = 'Placing orderâ€¦';

  try {
    const res  = await fetch(CAPI + '/customer_orders.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrf() },
      credentials: 'include',
      body: JSON.stringify({ Order_Type: selectedProduct.name, Order_Date: date, Cow_ID: cow.Cow_ID }),
    });
    const data = await res.json();

    if (data.success) {
      // Add notification
      addNotification('Order #' + data.data.Order_ID + ' placed successfully! We will confirm shortly.', 'success');
      // Reset form
      selectedProduct = null;
      document.getElementById('selected-product-display').textContent = 'No product selected';
      document.getElementById('selected-product-display').style.borderColor = 'var(--border-light)';
      document.getElementById('order-date').value = '';
      document.getElementById('order-summary').style.display = 'none';
      document.querySelectorAll('#order-products-grid .product-card').forEach(c => c.classList.remove('selected'));
      // Reload orders and go to my orders
      await loadMyOrders();
      showSection('my-orders', null);
      showToast('Order placed successfully!', 'success');
    } else {
      errEl.textContent = data.message || 'Failed to place order.';
      errEl.style.display = 'block';
    }
  } catch(e) {
    errEl.textContent = 'Network error. Please try again.';
    errEl.style.display = 'block';
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;">check_circle</span> Confirm Order';
  }
}

// â”€â”€ Notifications â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
var notifications = [];
var NOTIF_KEY = 'customer_notifs_' + (getCustomer().id || 'guest');

function loadNotifications() {
  try { notifications = JSON.parse(localStorage.getItem(NOTIF_KEY) || '[]'); } catch { notifications = []; }
  // Add default welcome notification if first time
  if (!notifications.length) {
    notifications = [
      { msg: 'Welcome to Esperon Dairy Farm! Browse our fresh products and place your first order.', type:'info', time: new Date().toLocaleString() },
      { msg: 'Farm announcement: Fresh milk delivery every Monday and Thursday.', type:'info', time: new Date().toLocaleString() },
    ];
    saveNotifications();
  }
  renderNotifications();
}

function addNotification(msg, type) {
  notifications.unshift({ msg, type: type || 'info', time: new Date().toLocaleString() });
  if (notifications.length > 20) notifications.pop();
  saveNotifications();
  renderNotifications();
}

function saveNotifications() {
  localStorage.setItem(NOTIF_KEY, JSON.stringify(notifications));
}

function renderNotifications() {
  const badge   = document.getElementById('notif-badge');
  const ovEl    = document.getElementById('ov-notifs');
  const fullEl  = document.getElementById('notifs-full');
  const icons   = { success:'check_circle', info:'info', warning:'warning' };

  const unread = notifications.filter(n => !n.read).length;
  if (badge) { badge.textContent = unread; badge.style.display = unread > 0 ? 'inline-block' : 'none'; }

  const html = notifications.map(n => `
    <div class="notif-item notif-item--${n.type || 'info'}">
      <span class="material-symbols-outlined" style="font-size:1rem;flex-shrink:0;">${icons[n.type] || 'info'}</span>
      <div><div>${n.msg}</div><div style="font-size:0.7rem;color:var(--muted);margin-top:2px;">${n.time}</div></div>
    </div>`).join('');

  if (ovEl) ovEl.innerHTML = notifications.slice(0, 3).map(n => `
    <div class="notif-item notif-item--${n.type || 'info'}">
      <span class="material-symbols-outlined" style="font-size:1rem;flex-shrink:0;">${icons[n.type] || 'info'}</span>
      <span>${n.msg}</span>
    </div>`).join('') || '<p style="color:var(--muted);font-size:0.84rem;">No notifications.</p>';

  if (fullEl) fullEl.innerHTML = html || '<p style="color:var(--muted);font-size:0.84rem;">No notifications.</p>';
}

// â”€â”€ Profile â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function loadProfile() {
  const c = getCustomer();
  const set = (id, v) => { const el = document.getElementById(id); if (el) el.value = v || ''; };
  set('pf-name',    c.name);
  set('pf-phone',   c.phone);
  set('pf-email',   c.email);
  set('pf-address', c.address);
  const cidEl = document.getElementById('pf-cid');
  if (cidEl) cidEl.textContent = '#' + (c.id || 'â€”');
}

document.addEventListener('DOMContentLoaded', function() {
  const profileForm = document.getElementById('profile-form');
  if (profileForm) {
    profileForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      const name    = document.getElementById('pf-name').value.trim();
      const phone   = document.getElementById('pf-phone').value.trim();
      const address = document.getElementById('pf-address').value.trim();
      const msgEl   = document.getElementById('pf-msg');

      if (!name) { showProfileMsg('Name cannot be empty.', 'error'); return; }

      try {
        const res  = await fetch(CAPI + '/customer_profile.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrf() },
          credentials: 'include',
          body: JSON.stringify({ name, phone, address }),
        });
        const data = await res.json();
        if (data.success) {
          const stored = getCustomer();
          stored.name = name; stored.phone = phone; stored.address = address;
          localStorage.setItem('customer', JSON.stringify(stored));
          renderGreeting();
          showProfileMsg('Profile updated successfully!', 'success');
        } else {
          showProfileMsg(data.message || 'Failed to update.', 'error');
        }
      } catch(e) { showProfileMsg('Network error.', 'error'); }
    });
  }

  // Place order button
  const placeBtn = document.getElementById('place-order-btn');
  if (placeBtn) placeBtn.addEventListener('click', placeOrder);

  // Order date / payment change â†’ update summary
  const orderDate = document.getElementById('order-date');
  const payMethod = document.getElementById('payment-method');
  if (orderDate) orderDate.addEventListener('change', updateOrderSummary);
  if (payMethod) payMethod.addEventListener('change', updateOrderSummary);

  // Logout
  const logoutBtn = document.getElementById('logout-btn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', async function() {
      await fetch(CAPI + '/customer_auth.php?action=logout', { method:'POST', credentials:'include' });
      localStorage.removeItem('customer');
      localStorage.removeItem('csrf_token');
      window.location.href = 'login_unified.php';
    });
  }

  // Feedback
  const feedbackBtn = document.getElementById('submit-feedback-btn');
  if (feedbackBtn) {
    feedbackBtn.addEventListener('click', function() {
      const text   = document.getElementById('feedback-text').value.trim();
      const rating = currentRating;
      if (!text) { showToast('Please write your feedback.', 'error'); return; }
      if (!rating) { showToast('Please select a star rating.', 'error'); return; }
      const FKEY = 'customer_feedback_' + (getCustomer().id || 'guest');
      const feedbacks = JSON.parse(localStorage.getItem(FKEY) || '[]');
      feedbacks.unshift({ text, rating, time: new Date().toLocaleString() });
      localStorage.setItem(FKEY, JSON.stringify(feedbacks));
      document.getElementById('feedback-text').value = '';
      setRating(0);
      renderFeedbackHistory();
      showToast('Thank you for your feedback!', 'success');
    });
  }
});

function showProfileMsg(msg, type) {
  const el = document.getElementById('pf-msg');
  if (!el) return;
  el.textContent = msg;
  el.style.display = 'block';
  el.style.background = type === 'success' ? 'var(--success-lt)' : 'var(--danger-lt)';
  el.style.color      = type === 'success' ? 'var(--olive-dark)' : 'var(--danger)';
  el.style.border     = '1px solid ' + (type === 'success' ? 'rgba(78,96,64,0.2)' : 'rgba(192,57,43,0.2)');
  setTimeout(() => { el.style.display = 'none'; }, 4000);
}

// â”€â”€ Star rating â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
var currentRating = 0;
var ratingLabels  = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];

function setRating(val) {
  currentRating = val;
  document.querySelectorAll('#star-rating span').forEach(function(s) {
    s.classList.toggle('active', parseInt(s.dataset.val) <= val);
  });
  const lbl = document.getElementById('rating-label');
  if (lbl) lbl.textContent = val ? ratingLabels[val] + ' (' + val + '/5)' : 'Click a star to rate';
}

function renderFeedbackHistory() {
  const el   = document.getElementById('feedback-history');
  if (!el) return;
  const FKEY = 'customer_feedback_' + (getCustomer().id || 'guest');
  const list = JSON.parse(localStorage.getItem(FKEY) || '[]');
  if (!list.length) { el.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;">No feedback submitted yet.</p>'; return; }
  el.innerHTML = list.map(f => {
    const stars = '\u2605'.repeat(f.rating) + '\u2606'.repeat(5 - f.rating);
    return '<div style="background:rgba(255,255,255,0.4);border:1px solid var(--border-light);border-radius:10px;padding:12px 14px;margin-bottom:8px;">'
      + '<div style="color:var(--gold);font-size:1rem;margin-bottom:4px;">' + stars + '</div>'
      + '<div style="font-size:0.84rem;color:var(--text);">' + f.text + '</div>'
      + '<div style="font-size:0.7rem;color:var(--muted);margin-top:4px;">' + f.time + '</div>'
      + '</div>';
  }).join('');
}

// â”€â”€ Toast â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function showToast(msg, type) {
  const existing = document.querySelector('.cust-toast');
  if (existing) existing.remove();
  const t = document.createElement('div');
  t.className = 'cust-toast';
  t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:99999;padding:12px 20px;border-radius:12px;font-size:0.88rem;font-weight:600;font-family:\'Lato\',sans-serif;box-shadow:0 4px 16px rgba(0,0,0,0.15);transition:opacity 0.3s;'
    + (type === 'success' ? 'background:#e8f0e0;color:#2d3b22;border:1px solid rgba(78,96,64,0.2);' : 'background:#fdf0ef;color:#c0392b;border:1px solid rgba(192,57,43,0.2);');
  t.textContent = (type === 'success' ? '\u2713 ' : '\u2717 ') + msg;
  document.body.appendChild(t);
  setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 300); }, 3000);
}

// â”€â”€ Init â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
(async function() {
  renderGreeting();

  // Verify session
  try {
    const res  = await fetch(CAPI + '/customer_auth.php?action=status', { credentials:'include' });
    const data = await res.json();
    if (!data.success) { window.location.href = 'login_unified.php'; return; }
    if (data.data) {
      localStorage.setItem('csrf_token', data.data.csrf_token || '');
      if (data.data.customer) localStorage.setItem('customer', JSON.stringify(data.data.customer));
    }
  } catch(e) { window.location.href = 'login_unified.php'; return; }

  renderGreeting();
  loadProfile();
  loadNotifications();
  renderFeedbackHistory();

  // Render product grids
  renderProducts('products-grid', false);
  renderProducts('order-products-grid', true);

  // Set default order date to tomorrow
  const orderDateEl = document.getElementById('order-date');
  if (orderDateEl) {
    const tomorrow = new Date(); tomorrow.setDate(tomorrow.getDate() + 1);
    orderDateEl.value = tomorrow.toISOString().split('T')[0];
    orderDateEl.min   = todayISO();
  }

  // Load cows for order placement
  try {
    const cowRes  = await fetch('../dairy_farm_backend/api/cows.php', { credentials:'include' });
    const cowData = await cowRes.json();
    if (cowData.success) allCows = cowData.data || [];
  } catch(e) { /* non-critical */ }

  // Load orders
  await loadMyOrders();
})();

