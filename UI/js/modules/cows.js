// ============================================================
// js/modules/cows.js  —  Livestock loading, rendering, management
// ============================================================

// ── STAFF ─────────────────────────────────────────────────
async function loadStaff() {
  var container = document.getElementById('staff-list');
  if (container) {
    container.innerHTML = '<div class="skeleton-card"></div><div class="skeleton-card"></div><div class="skeleton-card"></div>';
  }
  try {
    var workers = await API.workers.getAll();
    var statEl  = document.getElementById('stat-workers');
    if (statEl) statEl.textContent = workers.length;

    if (!workers.length) {
      container.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;">No staff records.</p>';
      return;
    }

    container.innerHTML = workers.map(function(w) {
      var initial  = (w.Worker || '?').charAt(0).toUpperCase();
      var roleClass = w.Worker_Role === 'Admin' ? 'badge--green' : 'badge--muted';
      return '<div class="worker-row">'
        + '<div class="worker-avatar">' + initial + '</div>'
        + '<div style="flex:1;min-width:0;">'
        + '<div style="font-weight:700;font-size:0.84rem;">' + w.Worker + '</div>'
        + '<div style="font-size:0.72rem;color:var(--muted);">ID #' + w.Worker_ID + '</div>'
        + '</div>'
        + '<span class="badge ' + roleClass + '" style="font-size:0.68rem;">' + w.Worker_Role + '</span>'
        + '<a href="workers.php" style="margin-left:8px;" title="Edit"><span class="material-symbols-outlined" style="font-size:1rem;color:var(--muted);cursor:pointer;">edit</span></a>'
        + '</div>';
    }).join('');
  } catch(e) {
    if (container) container.innerHTML = '<p style="color:var(--danger);font-size:0.84rem;">Failed to load staff.</p>';
  }
}

// ── LIVESTOCK ─────────────────────────────────────────────
async function loadLivestock() {
  var container = document.getElementById('livestock-list');
  var sickBadge = document.getElementById('sick-badge');
  if (container) {
    container.innerHTML = '<div class="skeleton-card"></div><div class="skeleton-card"></div><div class="skeleton-card"></div>';
  }
  try {
    var cows   = await API.cows.getAll();
    var statEl = document.getElementById('stat-cows');
    if (statEl) statEl.textContent = cows.length;

    updateMilkStat(cows);

    if (!cows.length) {
      container.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;">No livestock records.</p>';
      return;
    }

    var sickCount = 0;
    container.innerHTML = cows.map(function(c) {
      // Use real Health_Status from the database
      var health    = c.Health_Status || 'Healthy';
      var isSick    = health === 'Sick' || health === 'Under Treatment';
      if (isSick) sickCount++;
      var dotClass    = isSick ? 'status-dot--sick' : 'status-dot--healthy';
      var healthColor = isSick ? 'var(--danger)' : 'var(--olive)';
      return '<div class="cow-row">'
        + '<div style="display:flex;align-items:center;gap:6px;">'
        + '<span class="status-dot ' + dotClass + '"></span>'
        + '<div>'
        + '<div style="font-weight:700;font-size:0.83rem;">' + c.Cow + '</div>'
        + '<div style="font-size:0.72rem;color:var(--muted);">ID #' + c.Cow_ID + (c.Breed ? ' · ' + c.Breed : '') + '</div>'
        + '</div></div>'
        + '<div style="text-align:right;">'
        + '<span style="color:' + healthColor + ';font-weight:700;font-size:0.78rem;">' + health + '</span>'
        + '<div style="font-size:0.72rem;color:var(--muted);">' + parseFloat(c.Production_Liters || 0).toFixed(2) + 'L/day</div>'
        + '</div>'
        + '</div>';
    }).join('');

    if (sickCount > 0) {
      if (sickBadge) { sickBadge.textContent = sickCount + ' sick'; sickBadge.style.display = 'inline-block'; }
      addAlert(sickCount + ' cow(s) marked sick \u2014 vet check required.', 'danger');
    }
  } catch(e) {
    if (container) container.innerHTML = '<p style="color:var(--danger);font-size:0.84rem;">Failed to load livestock.</p>';
  }
}

// ── CUSTOMERS ─────────────────────────────────────────────
async function loadCustomers() {
  try {
    var customers = await API.customers.getAll();
    var el = document.getElementById('stat-customers');
    if (el) el.textContent = customers.length;
  } catch(e) {}
}
