// ============================================================
// js/modules/orders.js  —  Orders loading, rendering, filtering
// ============================================================

// ── Shared data stores (also used by global search) ───────
var allOrders    = [];
var allCows      = [];
var allWorkers   = [];
var allCustomers = [];

// ── GLOBAL SEARCH ─────────────────────────────────────────
(function() {
  var searchTimeout = null;
  var dropEl = null;

  function getOrCreateDrop() {
    if (dropEl) return dropEl;
    dropEl = document.createElement('div');
    dropEl.id = 'global-search-drop';
    dropEl.style.cssText = 'position:absolute;top:calc(100% + 6px);left:0;right:0;'
      + 'background:#faf6f0;border:1.5px solid var(--border-light);border-radius:14px;'
      + 'box-shadow:0 8px 32px rgba(0,0,0,0.18);z-index:99999;max-height:380px;overflow-y:auto;'
      + 'font-family:var(--font-sans);min-width:320px;';
    var wrap = document.querySelector('.header__search');
    if (wrap) {
      wrap.style.position = 'relative';
      wrap.appendChild(dropEl);
    }
    return dropEl;
  }

  function closeDrop() {
    if (dropEl) { dropEl.remove(); dropEl = null; }
  }

  function highlight(text, q) {
    if (!q) return text;
    var re = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
    return String(text).replace(re, '<mark style="background:rgba(78,96,64,0.18);border-radius:3px;padding:0 2px;">$1</mark>');
  }

  function makeRow(icon, title, sub, href) {
    var el = document.createElement('a');
    el.href = href || '#';
    el.style.cssText = 'display:flex;align-items:center;gap:10px;padding:9px 14px;'
      + 'text-decoration:none;color:var(--text);border-bottom:1px solid var(--border-light);'
      + 'transition:background 0.12s;';
    el.onmouseover = function() { el.style.background = 'rgba(78,96,64,0.07)'; };
    el.onmouseout  = function() { el.style.background = ''; };
    el.innerHTML = '<span class="material-symbols-outlined" style="font-size:1rem;color:var(--muted);flex-shrink:0;">' + icon + '</span>'
      + '<div style="flex:1;min-width:0;">'
      + '<div style="font-size:0.84rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + title + '</div>'
      + '<div style="font-size:0.72rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + sub + '</div>'
      + '</div>';
    return el;
  }

  function makeSectionHeader(label) {
    var el = document.createElement('div');
    el.style.cssText = 'padding:6px 14px 3px;font-size:0.65rem;font-weight:700;'
      + 'text-transform:uppercase;letter-spacing:0.07em;color:var(--muted);'
      + 'background:rgba(255,255,255,0.6);';
    el.textContent = label;
    return el;
  }

  function runSearch(q) {
    var drop = getOrCreateDrop();
    drop.innerHTML = '';

    if (!q) { closeDrop(); return; }

    var ql = q.toLowerCase();

    // Orders
    var orderMatches = allOrders.filter(function(o) {
      return (String(o.Order_ID).includes(ql))
        || (o.Customer_Name || '').toLowerCase().includes(ql)
        || (o.Order_Type    || '').toLowerCase().includes(ql)
        || (o.Cow           || '').toLowerCase().includes(ql);
    }).slice(0, 5);

    // Cows
    var cowMatches = allCows.filter(function(c) {
      return (c.Cow || '').toLowerCase().includes(ql)
        || String(c.Cow_ID).includes(ql)
        || (c.Production || '').toLowerCase().includes(ql);
    }).slice(0, 5);

    // Workers / Staff
    var workerMatches = allWorkers.filter(function(w) {
      return (w.Worker      || '').toLowerCase().includes(ql)
        || (w.Worker_Role   || '').toLowerCase().includes(ql)
        || String(w.Worker_ID).includes(ql);
    }).slice(0, 5);

    // Customers
    var customerMatches = allCustomers.filter(function(c) {
      return (c.Customer_Name || '').toLowerCase().includes(ql)
        || (c.Address         || '').toLowerCase().includes(ql)
        || (c.Contact_Num     || '').toLowerCase().includes(ql)
        || String(c.CID).includes(ql);
    }).slice(0, 5);

    var total = orderMatches.length + cowMatches.length + workerMatches.length + customerMatches.length;

    if (!total) {
      var empty = document.createElement('div');
      empty.style.cssText = 'padding:16px 14px;text-align:center;color:var(--muted);font-size:0.84rem;';
      empty.textContent = 'No results for "' + q + '"';
      drop.appendChild(empty);
      return;
    }

    if (orderMatches.length) {
      drop.appendChild(makeSectionHeader('Orders'));
      orderMatches.forEach(function(o) {
        drop.appendChild(makeRow('receipt_long',
          highlight('#' + o.Order_ID + ' — ' + (o.Customer_Name || ''), q),
          highlight((o.Order_Type || '') + ' · ' + (o.Cow || '') + ' · ' + (o.Order_Date || ''), q),
          'orders.php'
        ));
      });
    }

    if (cowMatches.length) {
      drop.appendChild(makeSectionHeader('Cows'));
      cowMatches.forEach(function(c) {
        drop.appendChild(makeRow('pets',
          highlight(c.Cow || '', q),
          highlight('ID #' + c.Cow_ID + ' · ' + (c.Production || ''), q),
          'cows.php'
        ));
      });
    }

    if (workerMatches.length) {
      drop.appendChild(makeSectionHeader('Staff'));
      workerMatches.forEach(function(w) {
        drop.appendChild(makeRow('badge',
          highlight(w.Worker || '', q),
          highlight((w.Worker_Role || '') + ' · ID #' + w.Worker_ID, q),
          'workers.php'
        ));
      });
    }

    if (customerMatches.length) {
      drop.appendChild(makeSectionHeader('Customers'));
      customerMatches.forEach(function(c) {
        drop.appendChild(makeRow('people',
          highlight(c.Customer_Name || '', q),
          highlight((c.Address || '') + ' · ' + (c.Contact_Num || ''), q),
          'customers.php'
        ));
      });
    }

    // Footer hint
    var footer = document.createElement('div');
    footer.style.cssText = 'padding:7px 14px;font-size:0.7rem;color:var(--muted);text-align:center;'
      + 'border-top:1px solid var(--border-light);background:rgba(255,255,255,0.5);';
    footer.textContent = total + ' result' + (total !== 1 ? 's' : '') + ' found';
    drop.appendChild(footer);
  }

  document.addEventListener('DOMContentLoaded', function() {
    var input = document.getElementById('global-search');
    if (!input) return;

    input.addEventListener('input', function() {
      clearTimeout(searchTimeout);
      var q = input.value.trim();
      searchTimeout = setTimeout(function() { runSearch(q); }, 180);
    });

    input.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') { input.value = ''; closeDrop(); }
    });

    // Close when clicking outside
    document.addEventListener('click', function(e) {
      var wrap = document.querySelector('.header__search');
      if (wrap && !wrap.contains(e.target)) closeDrop();
    });
  });
})();

// ── ORDERS ────────────────────────────────────────────────
var orderFilter = 'all';

// Status badge classes matching the DB ENUM values
var statusBadgeClass = {
  pending:   'order-status--pending',
  confirmed: 'order-status--processing',
  delivered: 'order-status--delivered',
  cancelled: 'order-status--cancelled',
};
var statusLabel = { pending: 'Pending', confirmed: 'Confirmed', delivered: 'Delivered', cancelled: 'Cancelled' };

function renderOrders() {
  var container = document.getElementById('orders-list');
  if (!container) return;

  var list = allOrders.slice().reverse();
  if (orderFilter !== 'all') {
    list = list.filter(function(o) { return (o.Order_Status || o.status || '').toLowerCase() === orderFilter; });
  }

  if (!list.length) {
    container.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;padding:8px 0;">No orders found.</p>';
    return;
  }

  container.innerHTML = list.slice(0, 8).map(function(o) {
    var statusKey = (o.Order_Status || o.status || 'pending').toLowerCase();
    var cls       = statusBadgeClass[statusKey] || 'order-status--pending';
    var lbl       = statusLabel[statusKey]      || statusKey;
    return '<div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-light);">'
      + '<div style="flex:1;min-width:0;">'
      + '<div style="font-weight:700;font-size:0.83rem;color:var(--text);">#' + o.Order_ID + ' \u2014 ' + (o.Customer_Name || '') + '</div>'
      + '<div style="font-size:0.73rem;color:var(--muted);margin-top:2px;">' + (o.Order_Type || '') + ' \u00b7 ' + (o.Cow || '') + ' \u00b7 ' + (o.Order_Date || '') + '</div>'
      + '</div>'
      + '<span class="order-status ' + cls + '">' + lbl + '</span>'
      + '</div>';
  }).join('');
}

function filterOrders(filter, btn) {
  orderFilter = filter;
  document.querySelectorAll('[onclick^="filterOrders"]').forEach(function(b) {
    b.style.background = 'rgba(255,255,255,.5)';
    b.style.borderColor = 'var(--border)';
    b.style.color = 'var(--text)';
  });
  if (btn) {
    btn.style.background   = 'rgba(78,96,64,0.12)';
    btn.style.borderColor  = 'var(--olive)';
    btn.style.color        = 'var(--olive-dark)';
  }
  renderOrders();
}

async function loadOrders() {
  var container = document.getElementById('orders-list');
  if (container) {
    container.innerHTML = '<div class="skeleton-card"></div><div class="skeleton-card"></div><div class="skeleton-card"></div>';
  }
  try {
    allOrders = await API.orders.getAll();
    var statEl = document.getElementById('stat-orders');
    if (statEl) statEl.textContent = allOrders.length;

    var pending = allOrders.filter(function(o) {
      return (o.Order_Status || o.status || '').toLowerCase() === 'pending';
    }).length;
    if (pending > 0) addAlert(pending + ' order(s) still pending — review required.', 'warning');

    renderOrders();
  } catch(e) {
    var c = document.getElementById('orders-list');
    if (c) c.innerHTML = '<p style="color:var(--danger);font-size:0.84rem;">Failed to load orders.</p>';
  }
}
