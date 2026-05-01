// ============================================================
// js/inventory.js  —  Shared Inventory Utilities
// Used by: dashboard_admin.js, inventory.php, dashboard_staff.js
// ============================================================

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
