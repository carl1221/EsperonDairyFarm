// ============================================================
// js/modules/production.js  —  Production stats and reports
// ============================================================

// ── MILK STAT ─────────────────────────────────────────────
function updateMilkStat(cows) {
  var total = cows.reduce(function(sum, c) {
    return sum + parseFloat(c.Production_Liters || 0);
  }, 0);

  var milkEl = document.getElementById('stat-milk');
  if (milkEl) milkEl.textContent = total > 0 ? total + 'L' : '\u2014';

  // Inventory bar
  var pct   = Math.min(Math.round((total / 500) * 100), 100);
  var bar   = document.getElementById('inv-milk-bar');
  var lbl   = document.getElementById('inv-milk-lbl');
  if (bar) {
    bar.style.width = pct + '%';
    bar.className   = 'inv-bar-fill ' + (pct < 30 ? 'inv-bar-fill--low' : pct < 60 ? 'inv-bar-fill--mid' : 'inv-bar-fill--ok');
  }
  if (lbl) lbl.textContent = total > 0 ? total + 'L' : '\u2014';

  if (pct < 30) addAlert('Milk stock critically low (' + total + 'L). Arrange collection.', 'danger');
  else if (pct < 50) addAlert('Milk stock is below 50% (' + total + 'L).', 'warning');
}

// ── REPORTS ───────────────────────────────────────────────
var reportData = {};

function setReportPeriod(period, btn) {
  document.querySelectorAll('[onclick^="setReportPeriod"]').forEach(function(b) {
    b.style.background  = 'rgba(255,255,255,.5)';
    b.style.borderColor = 'var(--border)';
    b.style.color       = 'var(--text)';
  });
  if (btn) {
    btn.style.background  = 'rgba(78,96,64,0.12)';
    btn.style.borderColor = 'var(--olive)';
    btn.style.color       = 'var(--olive-dark)';
  }

  var multiplier = period === 'daily' ? 1 : period === 'weekly' ? 7 : 30;
  var d = reportData;
  var set = function(id, val) { var el = document.getElementById(id); if (el) el.textContent = val; };
  set('rpt-milk',      d.milk      ? (d.milk      * multiplier) + 'L' : '\u2014');
  set('rpt-orders',    d.orders    ? d.orders    * multiplier         : '\u2014');
  set('rpt-customers', d.customers ? d.customers                      : '\u2014');
  set('rpt-cows',      d.cows      ? d.cows                           : '\u2014');
  set('rpt-staff',     d.staff     ? d.staff                          : '\u2014');
}

function populateReports(cows, orders, customers, workers) {
  var milkTotal = cows.reduce(function(s, c) {
    return s + parseFloat(c.Production_Liters || 0);
  }, 0);
  reportData = {
    milk:      milkTotal,
    orders:    orders.length,
    customers: customers.length,
    cows:      cows.length,
    staff:     workers.length
  };
  setReportPeriod('weekly', document.getElementById('report-active'));
}
