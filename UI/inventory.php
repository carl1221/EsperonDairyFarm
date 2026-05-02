<?php
require_once __DIR__ . '/guard.php';
requireAuthPage();
$isAdmin = ($_SESSION['user']['role'] ?? '') === 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Inventory — Esperon Dairy Farm</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link rel="stylesheet" href="css/style.css" />
  <style>
    .inv-bar-wrap { margin-bottom: 0; }
    .inv-bar-label { display:flex; justify-content:space-between; font-size:0.84rem; margin-bottom:6px; }
    .inv-bar { height:10px; background:var(--beige); border-radius:5px; overflow:hidden; }
    .inv-bar-fill { height:100%; border-radius:5px; transition:width .6s ease; }
    .inv-bar-fill--ok  { background:linear-gradient(90deg,var(--olive),var(--olive-light)); }
    .inv-bar-fill--mid { background:linear-gradient(90deg,var(--gold),var(--gold-light)); }
    .inv-bar-fill--low { background:linear-gradient(90deg,var(--danger),#e74c3c); }
    .inv-card {
      background:rgba(255,255,255,0.35);
      backdrop-filter:blur(16px);
      border:1px solid rgba(255,255,255,0.5);
      border-radius:var(--radius-xl);
      box-shadow:var(--shadow-glass);
      padding:20px 24px;
      transition:all .2s;
    }
    .inv-card:hover { box-shadow:var(--shadow-lg); transform:translateY(-2px); }
    .inv-grid {
      display:grid;
      grid-template-columns:repeat(auto-fill,minmax(260px,1fr));
      gap:var(--spacing-lg);
      margin-bottom:var(--spacing-xl);
    }
  </style>
</head>
<body>
<nav class="nav" id="app-nav"></nav>
<main class="main">

  <!-- Page header -->
  <div class="page-header">
    <div>
      <h1 class="page-title">Inventory Levels</h1>
      <p class="page-subtitle" id="inv-last-updated-page">
        <?= $isAdmin ? 'Track and manage farm stock levels.' : 'View and update farm stock levels.' ?>
      </p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
      <?php if ($isAdmin): ?>
      <button class="btn btn--ghost" onclick="openEditInventoryModal()">
        <span class="material-symbols-outlined" style="font-size:1rem;">edit</span> Edit Items
      </button>
      <?php endif; ?>
      <button class="btn btn--primary" onclick="openRestockModal()">
        <span class="material-symbols-outlined" style="font-size:1rem;">add</span> Restock
      </button>
    </div>
  </div>

  <!-- Summary stat cards -->
  <div class="stats-grid" style="margin-bottom:var(--spacing-xl);" id="inv-stat-cards">
    <!-- Rendered by JS -->
  </div>

  <!-- Inventory cards grid -->
  <div class="inv-grid" id="inv-cards-grid">
    <!-- Rendered by JS -->
  </div>

  <!-- Reset + last updated footer -->
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--spacing-xl);">
    <span style="font-size:0.78rem;color:var(--muted);" id="inv-footer-updated"></span>
    <?php if ($isAdmin): ?>
    <button class="btn btn--ghost" onclick="doResetInventory()" style="font-size:0.82rem;">
      <span class="material-symbols-outlined" style="font-size:1rem;">restart_alt</span> Reset to Defaults
    </button>
    <?php endif; ?>
  </div>

</main>

<script src="js/api.js"></script>
<script src="js/ui.js"></script>
<script src="js/nav.js"></script>
<script>
var IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;
// ── Shared inventory constants (same key as dashboard) ────
var INV_KEY = 'admin_inventory';

var defaultInventory = [
  { id: 'milk',    name: 'Milk Stock',  pct: 65, unit: 'L',  capacity: 500,  icon: 'water_drop'   },
  { id: 'silageA', name: 'Silage A',    pct: 78, unit: 'kg', capacity: 1000, icon: 'grass'         },
  { id: 'siloB',   name: 'Silo B',      pct: 38, unit: 'kg', capacity: 800,  icon: 'warehouse'     },
  { id: 'hay',     name: 'Hay',         pct: 88, unit: 'kg', capacity: 600,  icon: 'agriculture'   },
  { id: 'feed',    name: 'Animal Feed', pct: 52, unit: 'kg', capacity: 400,  icon: 'lunch_dining'  },
];

function loadInventory() {
  try {
    var stored = localStorage.getItem(INV_KEY);
    if (!stored) return defaultInventory.map(function(i){ return Object.assign({}, i); });
    var parsed = JSON.parse(stored);
    if (!Array.isArray(parsed) || parsed.length === 0 || typeof parsed[0].pct === 'undefined') {
      return defaultInventory.map(function(i){ return Object.assign({}, i); });
    }
    return parsed;
  } catch(e) {
    return defaultInventory.map(function(i){ return Object.assign({}, i); });
  }
}

function saveInventory(items) {
  try {
    localStorage.setItem(INV_KEY, JSON.stringify(items));
    localStorage.setItem(INV_KEY + '_updated', new Date().toLocaleString());
  } catch(e) { console.error('Failed to save inventory:', e); }
}

function getBarClass(pct) {
  return pct < 30 ? 'inv-bar-fill--low' : pct < 60 ? 'inv-bar-fill--mid' : 'inv-bar-fill--ok';
}

function getLabelColor(pct) {
  return pct < 30 ? 'var(--danger)' : pct < 60 ? '#7a5a1e' : 'var(--olive-dark)';
}

function getStatusLabel(pct) {
  if (pct < 30) return '<span style="color:var(--danger);font-weight:700;font-size:0.75rem;">&#9888; Critical</span>';
  if (pct < 60) return '<span style="color:#7a5a1e;font-weight:700;font-size:0.75rem;">&#9888; Low</span>';
  return '<span style="color:var(--olive-dark);font-weight:700;font-size:0.75rem;">&#10003; OK</span>';
}

// ── Render stat cards at top ──────────────────────────────
function renderStatCards(items) {
  var container = document.getElementById('inv-stat-cards');
  if (!container) return;
  var critical = items.filter(function(i){ return i.pct < 30; }).length;
  var low      = items.filter(function(i){ return i.pct >= 30 && i.pct < 60; }).length;
  var ok       = items.filter(function(i){ return i.pct >= 60; }).length;

  container.innerHTML =
    '<div class="stat-card stat-card--danger">'
    + '<div class="stat-card__icon"><span class="material-symbols-outlined">warning</span></div>'
    + '<div class="stat-card__content"><div class="stat-card__val">' + critical + '</div><div class="stat-card__label">Critical (< 30%)</div></div>'
    + '</div>'
    + '<div class="stat-card stat-card--gold">'
    + '<div class="stat-card__icon"><span class="material-symbols-outlined">info</span></div>'
    + '<div class="stat-card__content"><div class="stat-card__val">' + low + '</div><div class="stat-card__label">Low (30–60%)</div></div>'
    + '</div>'
    + '<div class="stat-card">'
    + '<div class="stat-card__icon"><span class="material-symbols-outlined">check_circle</span></div>'
    + '<div class="stat-card__content"><div class="stat-card__val">' + ok + '</div><div class="stat-card__label">Sufficient (≥ 60%)</div></div>'
    + '</div>'
    + '<div class="stat-card">'
    + '<div class="stat-card__icon"><span class="material-symbols-outlined">inventory_2</span></div>'
    + '<div class="stat-card__content"><div class="stat-card__val">' + items.length + '</div><div class="stat-card__label">Total Items</div></div>'
    + '</div>';
}

// ── Render inventory cards ────────────────────────────────
function renderInventoryCards() {
  var grid      = document.getElementById('inv-cards-grid');
  var footerEl  = document.getElementById('inv-footer-updated');
  if (!grid) return;

  var items   = loadInventory();
  var updated = localStorage.getItem(INV_KEY + '_updated');
  if (footerEl) footerEl.textContent = updated ? 'Last updated: ' + updated : '';

  renderStatCards(items);

  // Show role-appropriate subtitle
  var subEl = document.getElementById('inv-last-updated-page');
  if (subEl && !IS_ADMIN) {
    subEl.textContent = 'You can update stock levels. Contact admin to add or rename items.';
  }

  grid.innerHTML = items.map(function(item) {
    var pct  = Math.min(100, Math.max(0, item.pct || 0));
    var amt  = Math.round(pct / 100 * (item.capacity || 100));
    var barW = pct + '%';
    var color = getLabelColor(pct);

    return '<div class="inv-card">'
      + '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">'
      + '<div style="display:flex;align-items:center;gap:10px;min-width:0;flex:1;">'
      + '<div style="width:42px;height:42px;min-width:42px;border-radius:12px;background:rgba(78,96,64,0.1);display:flex;align-items:center;justify-content:center;overflow:hidden;">'
      + '<span class="material-symbols-outlined" style="font-size:1.4rem;color:var(--olive);">' + (item.icon || 'inventory_2') + '</span>'
      + '</div>'
      + '<div style="min-width:0;">'
      + '<div style="font-family:var(--font-serif);font-size:1rem;font-weight:700;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + item.name + '</div>'
      + '<div style="font-size:0.72rem;color:var(--muted);">Capacity: ' + item.capacity + ' ' + item.unit + '</div>'
      + '</div>'
      + '</div>'
      + '<div style="flex-shrink:0;margin-left:8px;">' + getStatusLabel(pct) + '</div>'
      + '</div>'
      // Big percentage display
      + '<div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:10px;">'
      + '<div style="font-family:var(--font-serif);font-size:2.4rem;font-weight:700;color:' + color + ';line-height:1;">' + pct + '<span style="font-size:1.2rem;">%</span></div>'
      + '<div style="text-align:right;">'
      + '<div style="font-size:0.88rem;font-weight:700;color:var(--text);">' + amt + ' ' + item.unit + '</div>'
      + '<div style="font-size:0.72rem;color:var(--muted);">of ' + item.capacity + ' ' + item.unit + '</div>'
      + '</div>'
      + '</div>'
      // Progress bar
      + '<div class="inv-bar"><div class="inv-bar-fill ' + getBarClass(pct) + '" style="width:' + barW + '"></div></div>'
      // Quick restock button
      + '<div style="margin-top:14px;display:flex;justify-content:flex-end;">'
      + '<button onclick="quickRestock(\'' + item.id + '\')" '
      + 'style="background:rgba(78,96,64,0.1);border:1.5px solid rgba(78,96,64,0.2);border-radius:8px;padding:5px 12px;cursor:pointer;font-size:0.78rem;font-weight:600;color:var(--olive-dark);display:inline-flex;align-items:center;gap:4px;font-family:var(--font-sans);">'
      + '<span class="material-symbols-outlined" style="font-size:0.9rem;">add</span> Restock</button>'
      + '</div>'
      + '</div>';
  }).join('');
}

// ── Quick restock (opens modal pre-selected) ──────────────
function quickRestock(itemId) {
  openRestockModal(itemId);
}

// ── Restock Modal ─────────────────────────────────────────
function openRestockModal(preselect) {
  var existing = document.getElementById('restockModal');
  if (existing) { existing.remove(); return; }

  var items = loadInventory();
  var el = document.createElement('div');
  el.id = 'restockModal';
  el.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(42,31,21,0.45);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:16px;';

  var options = items.map(function(i) {
    return '<option value="' + i.id + '"' + (i.id === preselect ? ' selected' : '') + '>' + i.name + ' (currently ' + i.pct + '%)</option>';
  }).join('');

  var initItem = preselect ? items.find(function(i){ return i.id === preselect; }) : items[0];
  var initPct  = initItem ? initItem.pct : items[0].pct;

  el.innerHTML = '<div style="background:#faf6f0;border-radius:18px;box-shadow:0 12px 48px rgba(0,0,0,0.18);width:100%;max-width:400px;font-family:\'Lato\',sans-serif;overflow:hidden;">'
    + '<div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px 12px;background:linear-gradient(135deg,#4e6040,#6b8a5c);">'
    + '<div style="display:flex;align-items:center;gap:8px;"><span class="material-symbols-outlined" style="color:#fff;font-size:1.1rem;">add_circle</span>'
    + '<span style="font-family:\'Playfair Display\',serif;font-size:1rem;font-weight:700;color:#fff;">Restock Inventory</span></div>'
    + '<button onclick="document.getElementById(\'restockModal\').remove()" style="background:rgba(255,255,255,0.2);border:none;cursor:pointer;width:26px;height:26px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;">'
    + '<span class="material-symbols-outlined" style="font-size:0.95rem;">close</span></button></div>'
    + '<div style="padding:18px 20px;">'
    + '<div style="margin-bottom:12px;">'
    + '<label style="display:block;font-size:0.75rem;font-weight:700;color:#4a3f35;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;">Select Item</label>'
    + '<select id="restock-item" onchange="onRestockItemChange()" style="width:100%;padding:9px 12px;border:1.5px solid var(--border-light);border-radius:9px;font-size:0.87rem;font-family:\'Lato\',sans-serif;color:var(--text);background:#fff;outline:none;box-sizing:border-box;">'
    + options + '</select></div>'
    + '<div style="margin-bottom:12px;">'
    + '<label style="display:block;font-size:0.75rem;font-weight:700;color:#4a3f35;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;">New Stock Level (%)</label>'
    + '<div style="display:flex;align-items:center;gap:10px;">'
    + '<input id="restock-val" type="range" min="0" max="100" value="' + initPct + '" oninput="syncRestockNumber();updateRestockPreview();" style="flex:1;accent-color:var(--olive);cursor:pointer;" />'
    + '<input id="restock-num" type="number" min="0" max="100" value="' + initPct + '" oninput="syncRestockSlider();updateRestockPreview();" style="width:64px;padding:7px 8px;border:1.5px solid var(--border-light);border-radius:8px;font-size:0.88rem;font-family:\'Lato\',sans-serif;color:var(--text);background:#fff;outline:none;text-align:center;" />'
    + '<span style="font-size:0.84rem;color:var(--muted);">%</span>'
    + '</div>'
    + '<div style="display:flex;justify-content:space-between;font-size:0.72rem;color:var(--muted);margin-top:2px;"><span>0%</span><span>50%</span><span>100%</span></div>'
    + '</div>'
    + '<div id="restock-preview" style="background:rgba(232,240,224,0.5);border:1px solid rgba(78,96,64,0.2);border-radius:9px;padding:10px 14px;margin-bottom:14px;font-size:0.84rem;color:var(--olive-dark);">'
    + '<span class="material-symbols-outlined" style="font-size:0.9rem;vertical-align:middle;margin-right:4px;">info</span>'
    + '<span id="restock-preview-text">Select an item to preview</span></div>'
    + '<div style="display:flex;justify-content:flex-end;gap:8px;">'
    + '<button onclick="document.getElementById(\'restockModal\').remove()" style="padding:8px 16px;border:1.5px solid var(--border);border-radius:8px;background:#fff;color:var(--text);font-family:\'Lato\',sans-serif;font-size:0.83rem;font-weight:600;cursor:pointer;">Cancel</button>'
    + '<button onclick="submitRestock()" style="padding:8px 18px;border:none;border-radius:8px;background:linear-gradient(135deg,#4e6040,#6b8a5c);color:#fff;font-family:\'Lato\',sans-serif;font-size:0.83rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px;">'
    + '<span class="material-symbols-outlined" style="font-size:0.9rem;">save</span> Save</button>'
    + '</div></div></div>';

  document.body.appendChild(el);
  el.addEventListener('click', function(e){ if(e.target===el) el.remove(); });
  updateRestockPreview();
}

function syncRestockNumber() {
  var s = document.getElementById('restock-val'), n = document.getElementById('restock-num');
  if (s && n) n.value = s.value;
}
function syncRestockSlider() {
  var s = document.getElementById('restock-val'), n = document.getElementById('restock-num');
  if (!s || !n) return;
  var v = Math.min(100, Math.max(0, parseInt(n.value,10)||0));
  n.value = v; s.value = v;
}
function updateRestockPreview() {
  var itemSel = document.getElementById('restock-item');
  var valInput = document.getElementById('restock-val');
  var previewEl = document.getElementById('restock-preview-text');
  if (!itemSel || !valInput || !previewEl) return;
  var items = loadInventory();
  var item  = items.find(function(i){ return i.id === itemSel.value; });
  if (!item) return;
  var newPct = parseInt(valInput.value, 10);
  var newAmt = Math.round(newPct / 100 * item.capacity);
  var oldAmt = Math.round(item.pct / 100 * item.capacity);
  var diff   = newAmt - oldAmt;
  previewEl.textContent = item.name + ': ' + item.pct + '% \u2192 ' + newPct + '% ('
    + oldAmt + ' \u2192 ' + newAmt + ' ' + item.unit + ', ' + (diff >= 0 ? '+' : '') + diff + ' ' + item.unit + ')';
}
function onRestockItemChange() {
  var itemSel = document.getElementById('restock-item');
  var slider  = document.getElementById('restock-val');
  var num     = document.getElementById('restock-num');
  if (!itemSel || !slider || !num) return;
  var items = loadInventory();
  var item  = items.find(function(i){ return i.id === itemSel.value; });
  if (!item) return;
  slider.value = item.pct; num.value = item.pct;
  updateRestockPreview();
}
function submitRestock() {
  var itemSel = document.getElementById('restock-item');
  var numInput = document.getElementById('restock-num');
  if (!itemSel || !numInput) return;
  var newPct = Math.min(100, Math.max(0, parseInt(numInput.value, 10)));
  if (isNaN(newPct)) { UI.toast('Please enter a valid percentage (0-100).', 'error'); return; }
  var items = loadInventory();
  var item  = items.find(function(i){ return i.id === itemSel.value; });
  if (!item) return;
  item.pct = newPct;
  saveInventory(items);
  document.getElementById('restockModal').remove();
  renderInventoryCards();
  UI.toast(item.name + ' updated to ' + newPct + '%.', 'success');
}

// ── Edit Items Modal ──────────────────────────────────────
function openEditInventoryModal() {
  if (!IS_ADMIN) { UI.toast('Only admins can edit inventory items.', 'error'); return; }
  var existing = document.getElementById('editInvModal');
  if (existing) { existing.remove(); return; }
  var items = loadInventory();
  var el = document.createElement('div');
  el.id = 'editInvModal';
  el.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(42,31,21,0.45);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:16px;';

  var rows = items.map(function(item) {
    return '<div style="display:grid;grid-template-columns:1fr 80px 70px;gap:8px;margin-bottom:10px;align-items:center;">'
      + '<input type="text" value="' + item.name + '" data-id="' + item.id + '" data-field="name" style="padding:7px 10px;border:1.5px solid var(--border-light);border-radius:8px;font-size:0.84rem;font-family:\'Lato\',sans-serif;color:var(--text);background:#fff;outline:none;box-sizing:border-box;" />'
      + '<input type="number" value="' + item.capacity + '" data-id="' + item.id + '" data-field="capacity" min="1" style="padding:7px 8px;border:1.5px solid var(--border-light);border-radius:8px;font-size:0.84rem;font-family:\'Lato\',sans-serif;color:var(--text);background:#fff;outline:none;box-sizing:border-box;" />'
      + '<input type="text" value="' + item.unit + '" data-id="' + item.id + '" data-field="unit" style="padding:7px 8px;border:1.5px solid var(--border-light);border-radius:8px;font-size:0.84rem;font-family:\'Lato\',sans-serif;color:var(--text);background:#fff;outline:none;box-sizing:border-box;" />'
      + '</div>';
  }).join('');

  el.innerHTML = '<div style="background:#faf6f0;border-radius:18px;box-shadow:0 12px 48px rgba(0,0,0,0.18);width:100%;max-width:440px;font-family:\'Lato\',sans-serif;overflow:hidden;">'
    + '<div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px 12px;background:linear-gradient(135deg,#4e6040,#6b8a5c);">'
    + '<div style="display:flex;align-items:center;gap:8px;"><span class="material-symbols-outlined" style="color:#fff;font-size:1.1rem;">edit</span>'
    + '<span style="font-family:\'Playfair Display\',serif;font-size:1rem;font-weight:700;color:#fff;">Edit Inventory Items</span></div>'
    + '<button onclick="document.getElementById(\'editInvModal\').remove()" style="background:rgba(255,255,255,0.2);border:none;cursor:pointer;width:26px;height:26px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;">'
    + '<span class="material-symbols-outlined" style="font-size:0.95rem;">close</span></button></div>'
    + '<div style="padding:18px 20px;">'
    + '<div style="display:grid;grid-template-columns:1fr 80px 70px;gap:8px;margin-bottom:6px;">'
    + '<span style="font-size:0.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;">Item Name</span>'
    + '<span style="font-size:0.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;">Capacity</span>'
    + '<span style="font-size:0.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;">Unit</span>'
    + '</div>' + rows
    + '<div style="display:flex;justify-content:flex-end;gap:8px;margin-top:6px;">'
    + '<button onclick="document.getElementById(\'editInvModal\').remove()" style="padding:8px 16px;border:1.5px solid var(--border);border-radius:8px;background:#fff;color:var(--text);font-family:\'Lato\',sans-serif;font-size:0.83rem;font-weight:600;cursor:pointer;">Cancel</button>'
    + '<button onclick="saveEditInventory()" style="padding:8px 18px;border:none;border-radius:8px;background:linear-gradient(135deg,#4e6040,#6b8a5c);color:#fff;font-family:\'Lato\',sans-serif;font-size:0.83rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px;">'
    + '<span class="material-symbols-outlined" style="font-size:0.9rem;">save</span> Save</button>'
    + '</div></div></div>';

  document.body.appendChild(el);
  el.addEventListener('click', function(e){ if(e.target===el) el.remove(); });
}

function saveEditInventory() {
  var items = loadInventory();
  document.querySelectorAll('#editInvModal input').forEach(function(input) {
    var id    = input.dataset.id;
    var field = input.dataset.field;
    var item  = items.find(function(i){ return i.id === id; });
    if (!item) return;
    if (field === 'name')     item.name     = input.value.trim() || item.name;
    if (field === 'capacity') item.capacity = Math.max(1, parseInt(input.value, 10) || item.capacity);
    if (field === 'unit')     item.unit     = input.value.trim() || item.unit;
  });
  saveInventory(items);
  document.getElementById('editInvModal').remove();
  renderInventoryCards();
  UI.toast('Inventory items updated.', 'success');
}

// ── Reset ─────────────────────────────────────────────────
function doResetInventory() {
  if (!IS_ADMIN) { UI.toast('Only admins can reset inventory.', 'error'); return; }
  // Use UI.confirm if available, otherwise a simple check
  if (typeof UI.confirm === 'function') {
    UI.confirm('Reset all inventory levels to defaults?').then(function(ok) {
      if (!ok) return;
      localStorage.removeItem(INV_KEY);
      localStorage.removeItem(INV_KEY + '_updated');
      renderInventoryCards();
      UI.toast('Inventory reset to defaults.', 'success');
    });
  } else {
    localStorage.removeItem(INV_KEY);
    localStorage.removeItem(INV_KEY + '_updated');
    renderInventoryCards();
    UI.toast('Inventory reset to defaults.', 'success');
  }
}

// ── Init ──────────────────────────────────────────────────
(async function() {
  var res  = await fetch('../dairy_farm_backend/api/v1/auth.php?action=status', { credentials:'include' });
  var data = await res.json();
  if (data.success && data.data) {
    localStorage.setItem('csrf_token', data.data.csrf_token || '');
    if (data.data.user) localStorage.setItem('user', JSON.stringify(data.data.user));
  }
  renderInventoryCards();
})();
</script>
</body>
</html>
