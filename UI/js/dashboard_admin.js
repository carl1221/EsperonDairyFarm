// ============================================================
// js/dashboard_admin.js  �  Entry point
// Modules loaded via <script defer> in dashboard_admin.php:
//   modules/greeting.js   � renderGreeting, switchTab, helpers
//   modules/alerts.js     � alert system + notification bell
//   modules/orders.js     � orders + global search
//   modules/cows.js       � livestock + staff + customers
//   modules/production.js � milk stat + reports
// ============================================================

// -- REMINDERS ---------------------------------------------
// getStatusInfo, formatDueDate, loadReminders, updateReminderBadge
// are loaded from modules/reminders.js
var reminders = [];

function renderReminders() {
  var list = document.getElementById('remindersList');
  if (!list) return;
  if (!reminders.length) {
    list.innerHTML = '<p style="color:var(--text-light);font-size:0.84rem;">No tasks yet. Click "+ Add Task".</p>';
    return;
  }
  var sorted = reminders.slice().sort(function(a, b) { return new Date(a.due_date) - new Date(b.due_date); });
  list.innerHTML = sorted.map(function(r) {
    var s = getStatusInfo(r.due_date, r.status), done = r.status === 'completed';
    return '<div style="background:' + s.bg + ';border-radius:8px;padding:10px 12px;margin-bottom:8px;border-left:3px solid ' + s.color + ';">'
      + '<div style="display:flex;justify-content:space-between;align-items:flex-start;">'
      + '<div style="flex:1;">'
      + '<span style="font-size:0.68rem;color:' + s.color + ';font-weight:700;text-transform:uppercase;">' + s.label + '</span>'
      + '<p style="font-size:0.86rem;color:var(--text);margin:3px 0;' + (done ? 'text-decoration:line-through;opacity:0.6;' : '') + '">' + r.title + '</p>'
      + '<span style="font-size:0.7rem;color:var(--text-light);">Due: ' + formatDueDate(r.due_date) + '</span>'
      + '</div>'
      + '<div style="display:flex;gap:4px;margin-left:8px;">'
      + (!done ? '<button onclick="markComplete(' + r.reminder_id + ')" style="background:var(--olive);color:#fff;border:none;border-radius:4px;padding:3px 8px;cursor:pointer;font-size:0.7rem;">\u2713</button>' : '')
      + '<button onclick="deleteReminder(' + r.reminder_id + ')" style="background:transparent;border:none;color:var(--danger);cursor:pointer;padding:3px 8px;font-size:0.9rem;">\u2715</button>'
      + '</div></div></div>';
  }).join('');
}

// updateReminderBadge is in modules/reminders.js

async function markComplete(id) {
  if (!confirm('Mark as completed?')) return;
  try {
    var res  = await fetch('../dairy_farm_backend/api/v1/reminders.php?id=' + id, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': localStorage.getItem('csrf_token') },
      credentials: 'include',
      body: JSON.stringify({ status: 'completed' })
    });
    var data = await res.json();
    if (data.success) loadReminders(); else UI.toast('Failed to update.', 'error');
  } catch(e) { UI.toast('Error.', 'error'); }
}

async function deleteReminder(id) {
  if (!confirm('Delete this task?')) return;
  try {
    var res  = await fetch('../dairy_farm_backend/api/v1/reminders.php?id=' + id, { method:'DELETE', headers:{'X-CSRF-Token':localStorage.getItem('csrf_token')}, credentials:'include' });
    var data = await res.json();
    if (data.success) loadReminders(); else UI.toast('Failed to delete.', 'error');
  } catch(e) { UI.toast('Error.', 'error'); }
}

// -- REMINDER MODAL ----------------------------------------
(function() {
  var modalEl = document.createElement('div');
  modalEl.id = 'reminderModal';
  modalEl.style.cssText = 'display:none;position:fixed;inset:0;z-index:9999;background:rgba(42,31,21,0.45);backdrop-filter:blur(4px);align-items:center;justify-content:center;';
  modalEl.innerHTML = '<div style="background:rgba(255,255,255,0.95);border-radius:20px;box-shadow:0 12px 48px rgba(0,0,0,0.18);width:100%;max-width:460px;margin:16px;animation:rmSlideIn 0.25s ease;font-family:\'Lato\',sans-serif;">'
    + '<div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;background:linear-gradient(135deg,#c0392b,#e74c3c);border-radius:20px 20px 0 0;">'
    + '<div style="display:flex;align-items:center;gap:8px;"><span class="material-symbols-outlined" style="color:#fff;font-size:1.2rem;">alarm_add</span>'
    + '<span style="font-family:\'Playfair Display\',serif;font-size:1.05rem;font-weight:700;color:#fff;">Add Reminder</span></div>'
    + '<button id="rmClose" style="background:rgba(255,255,255,0.2);border:none;cursor:pointer;width:28px;height:28px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;">'
    + '<span class="material-symbols-outlined" style="font-size:1rem;">close</span></button></div>'
    + '<div style="padding:20px 22px;">'
    + '<div style="margin-bottom:14px;"><label style="display:block;font-size:0.75rem;font-weight:700;color:#4a3f35;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:5px;">Task Title <span style="color:#c0392b;">*</span></label>'
    + '<input id="rm_title" type="text" placeholder="e.g. Vet check for Cow #3" style="width:100%;padding:9px 13px;border:1.5px solid #e8dfd2;border-radius:9px;font-size:0.88rem;font-family:\'Lato\',sans-serif;color:#2a1f15;background:#fff;outline:none;box-sizing:border-box;" />'
    + '<div id="rm_title_err" style="display:none;color:#c0392b;font-size:0.73rem;margin-top:3px;">Title is required.</div></div>'
    + '<div style="margin-bottom:14px;"><label style="display:block;font-size:0.75rem;font-weight:700;color:#4a3f35;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:5px;">Description <span style="color:#8a7f72;font-weight:400;">(optional)</span></label>'
    + '<textarea id="rm_desc" rows="2" placeholder="Add details..." style="width:100%;padding:9px 13px;border:1.5px solid #e8dfd2;border-radius:9px;font-size:0.88rem;font-family:\'Lato\',sans-serif;color:#2a1f15;background:#fff;outline:none;resize:vertical;box-sizing:border-box;"></textarea></div>'
    + '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:8px;">'
    + '<div><label style="display:block;font-size:0.75rem;font-weight:700;color:#4a3f35;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:5px;">Due Date <span style="color:#c0392b;">*</span></label>'
    + '<input id="rm_date" type="date" style="width:100%;padding:9px 13px;border:1.5px solid #e8dfd2;border-radius:9px;font-size:0.88rem;font-family:\'Lato\',sans-serif;color:#2a1f15;background:#fff;outline:none;box-sizing:border-box;" /></div>'
    + '<div><label style="display:block;font-size:0.75rem;font-weight:700;color:#4a3f35;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:5px;">Time <span style="color:#c0392b;">*</span></label>'
    + '<div style="display:flex;gap:5px;">'
    + '<select id="rm_hour" style="flex:2;min-width:0;padding:9px 4px;border:1.5px solid #e8dfd2;border-radius:9px;font-size:0.85rem;font-family:\'Lato\',sans-serif;color:#2a1f15;background:#fff;outline:none;cursor:pointer;">'
    + [1,2,3,4,5,6,7,8,9,10,11,12].map(function(h){return '<option value="'+h+'">'+h+'</option>';}).join('')
    + '</select>'
    + '<select id="rm_min" style="flex:2;min-width:0;padding:9px 4px;border:1.5px solid #e8dfd2;border-radius:9px;font-size:0.85rem;font-family:\'Lato\',sans-serif;color:#2a1f15;background:#fff;outline:none;cursor:pointer;">'
    + '<option value="00">00</option><option value="15">15</option><option value="30">30</option><option value="45">45</option>'
    + '</select>'
    + '<select id="rm_ampm" style="flex:2;min-width:0;padding:9px 4px;border:1.5px solid #e8dfd2;border-radius:9px;font-size:0.85rem;font-family:\'Lato\',sans-serif;color:#2a1f15;background:#fff;outline:none;cursor:pointer;">'
    + '<option value="AM">AM</option><option value="PM">PM</option>'
    + '</select></div></div></div>'
    + '<div id="rm_date_err" style="display:none;color:#c0392b;font-size:0.73rem;margin-bottom:10px;">Date and time are required.</div>'
    + '<div id="rm_preview" style="display:none;background:rgba(232,240,224,0.5);border:1px solid rgba(78,96,64,0.2);border-radius:9px;padding:9px 13px;font-size:0.8rem;color:#4e6040;margin-bottom:4px;">'
    + '<span class="material-symbols-outlined" style="font-size:0.85rem;vertical-align:middle;margin-right:4px;">schedule</span><span id="rm_preview_txt"></span></div>'
    + '</div>'
    + '<div style="display:flex;justify-content:flex-end;gap:8px;padding:0 22px 18px;">'
    + '<button id="rmCancel" style="padding:9px 18px;border:1.5px solid #d4c9b8;border-radius:9px;background:#fff;color:#4a3f35;font-family:\'Lato\',sans-serif;font-size:0.85rem;font-weight:600;cursor:pointer;">Cancel</button>'
    + '<button id="rmSubmit" style="padding:9px 20px;border:none;border-radius:9px;background:linear-gradient(135deg,#4e6040,#6b8a5c);color:#fff;font-family:\'Lato\',sans-serif;font-size:0.85rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px;">'
    + '<span class="material-symbols-outlined" style="font-size:0.95rem;">add_task</span> Save Reminder</button>'
    + '</div></div>';

  document.body.appendChild(modalEl);

  var styleEl = document.createElement('style');
  styleEl.textContent = '@keyframes rmSlideIn{from{opacity:0;transform:translateY(-18px) scale(0.97)}to{opacity:1;transform:translateY(0) scale(1)}}@keyframes rmSpin{to{transform:rotate(360deg)}}';
  document.head.appendChild(styleEl);

  var rmTitle = document.getElementById('rm_title');
  var rmDesc  = document.getElementById('rm_desc');
  var rmDate  = document.getElementById('rm_date');
  var rmHour  = document.getElementById('rm_hour');
  var rmMin   = document.getElementById('rm_min');
  var rmAmpm  = document.getElementById('rm_ampm');
  var rmTitleErr = document.getElementById('rm_title_err');
  var rmDateErr  = document.getElementById('rm_date_err');
  var rmPreview  = document.getElementById('rm_preview');
  var rmPreviewTxt = document.getElementById('rm_preview_txt');

  function setDefaults() {
    var now = new Date(); now.setMinutes(0,0,0); now.setHours(now.getHours()+1);
    rmDate.value = now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2,'0') + '-' + String(now.getDate()).padStart(2,'0');
    var h = now.getHours(); rmAmpm.value = h >= 12 ? 'PM' : 'AM'; h = h % 12 || 12; rmHour.value = h; rmMin.value = '00';
  }
  function updatePreview() {
    if (!rmDate.value) { rmPreview.style.display = 'none'; return; }
    var parts = rmDate.value.split('-').map(Number);
    var mn = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    rmPreviewTxt.textContent = mn[parts[1]-1] + ' ' + parts[2] + ', ' + parts[0] + ' \u2014 ' + rmHour.value + ':' + rmMin.value + ' ' + rmAmpm.value;
    rmPreview.style.display = 'block';
  }
  function to24h(h, min, ampm) {
    var hour = parseInt(h, 10);
    if (ampm === 'AM' && hour === 12) hour = 0;
    if (ampm === 'PM' && hour !== 12) hour += 12;
    return String(hour).padStart(2,'0') + ':' + min + ':00';
  }

  function openReminderModal() {
    setDefaults(); rmTitle.value = ''; rmDesc.value = '';
    rmTitleErr.style.display = 'none'; rmDateErr.style.display = 'none';
    modalEl.style.display = 'flex'; setTimeout(function(){ rmTitle.focus(); }, 50); updatePreview();
  }
  function closeReminderModal() { modalEl.style.display = 'none'; }

  var addBtn = document.getElementById('addReminderBtn');
  if (addBtn) addBtn.onclick = openReminderModal;
  document.getElementById('rmClose').onclick  = closeReminderModal;
  document.getElementById('rmCancel').onclick = closeReminderModal;
  modalEl.addEventListener('click', function(e){ if(e.target===modalEl) closeReminderModal(); });
  document.addEventListener('keydown', function(e){ if(e.key==='Escape' && modalEl.style.display==='flex') closeReminderModal(); });
  [rmDate, rmHour, rmMin, rmAmpm].forEach(function(el){ el.addEventListener('change', updatePreview); });

  document.getElementById('rmSubmit').onclick = async function() {
    var valid = true;
    if (!rmTitle.value.trim()) { rmTitleErr.style.display='block'; valid=false; } else rmTitleErr.style.display='none';
    if (!rmDate.value)         { rmDateErr.style.display='block';  valid=false; } else rmDateErr.style.display='none';
    if (!valid) return;
    var dueDate = rmDate.value + ' ' + to24h(rmHour.value, rmMin.value, rmAmpm.value);
    var btn = document.getElementById('rmSubmit');
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:0.95rem;animation:rmSpin 0.7s linear infinite;">progress_activity</span> Saving\u2026';
    try {
      var res  = await fetch('../dairy_farm_backend/api/v1/reminders.php', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':localStorage.getItem('csrf_token')}, credentials:'include', body:JSON.stringify({title:rmTitle.value.trim(), description:rmDesc.value.trim()||null, due_date:dueDate, status:'pending'}) });
      var data = await res.json();
      if (data.success) { closeReminderModal(); loadReminders(); UI.toast('Reminder added!', 'success'); }
      else UI.toast('Failed: ' + data.message, 'error');
    } catch(e) { UI.toast('Network error.', 'error'); }
    finally { btn.disabled=false; btn.innerHTML='<span class="material-symbols-outlined" style="font-size:0.95rem;">add_task</span> Save Reminder'; }
  };
})();

// -- INVENTORY MANAGEMENT ----------------------------------
// Core helpers (INV_KEY, defaultInventory, loadInventory, saveInventory,
// getBarClass, getLabelColor) are loaded from js/inventory.js

function resetInventory() {
  if (!confirm('Reset all inventory levels to defaults?')) return;
  localStorage.removeItem(INV_KEY);
  localStorage.removeItem(INV_KEY + '_updated');
  renderInventoryBars();
  UI.toast('Inventory reset to defaults.', 'success');
}

function renderInventoryBars() {
  var container = document.getElementById('inventory-bars');
  var lastUpdEl = document.getElementById('inv-last-updated');
  if (!container) { console.warn('inventory-bars element not found'); return; }

  var items   = loadInventory();
  var updated = localStorage.getItem(INV_KEY + '_updated');
  if (lastUpdEl) lastUpdEl.textContent = updated ? 'Updated: ' + updated : '';

  if (!items || items.length === 0) {
    container.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;">No inventory data.</p>';
    return;
  }

  container.innerHTML = items.map(function(item) {
    var pct  = Math.min(100, Math.max(0, item.pct || 0));
    var warn = pct < 30 ? ' <span style="color:var(--danger);font-size:0.75rem;">&#9888; Low</span>' : '';
    var amt  = Math.round(pct / 100 * (item.capacity || 100));
    return '<div class="inv-bar-wrap">'
      + '<div class="inv-bar-label">'
      + '<span style="display:flex;align-items:center;gap:5px;">'
      + '<span class="material-symbols-outlined" style="font-size:0.9rem;color:var(--muted);">' + (item.icon || 'inventory_2') + '</span>'
      + item.name + warn
      + '</span>'
      + '<span style="color:' + getLabelColor(pct) + ';font-weight:700;">'
      + pct + '%'
      + ' <span style="font-weight:400;font-size:0.72rem;color:var(--muted);">(' + amt + '/' + (item.capacity || 100) + ' ' + (item.unit || '') + ')</span>'
      + '</span>'
      + '</div>'
      + '<div class="inv-bar"><div class="inv-bar-fill ' + getBarClass(pct) + '" style="width:' + pct + '%"></div></div>'
      + '</div>';
  }).join('');

  // Sync milk stat card
  var milkItem = items.find(function(i){ return i.id === 'milk'; });
  if (milkItem) {
    var milkLbl = document.getElementById('inv-milk-lbl');
    var milkBar = document.getElementById('inv-milk-bar');
    if (milkLbl) milkLbl.textContent = Math.round(milkItem.pct / 100 * milkItem.capacity) + 'L';
    if (milkBar) { milkBar.style.width = milkItem.pct + '%'; milkBar.className = 'inv-bar-fill ' + getBarClass(milkItem.pct); }
  }
}

// -- RESTOCK MODAL -----------------------------------------
function openRestockModal() {
  var existing = document.getElementById('restockModal');
  if (existing) { existing.remove(); return; }

  var items = loadInventory();
  var el = document.createElement('div');
  el.id = 'restockModal';
  el.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(42,31,21,0.45);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:16px;';

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
    + items.map(function(i){ return '<option value="' + i.id + '">' + i.name + ' (currently ' + i.pct + '%)</option>'; }).join('')
    + '</select></div>'
    + '<div style="margin-bottom:12px;">'
    + '<label style="display:block;font-size:0.75rem;font-weight:700;color:#4a3f35;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;">New Stock Level (%)</label>'
    + '<div style="display:flex;align-items:center;gap:10px;">'
    + '<input id="restock-val" type="range" min="0" max="100" value="' + items[0].pct + '" oninput="syncRestockNumber();updateRestockPreview();" style="flex:1;accent-color:var(--olive);cursor:pointer;" />'
    + '<input id="restock-num" type="number" min="0" max="100" value="' + items[0].pct + '" oninput="syncRestockSlider();updateRestockPreview();" style="width:64px;padding:7px 8px;border:1.5px solid var(--border-light);border-radius:8px;font-size:0.88rem;font-family:\'Lato\',sans-serif;color:var(--text);background:#fff;outline:none;text-align:center;" />'
    + '<span style="font-size:0.84rem;color:var(--muted);">%</span>'
    + '</div>'
    + '<div style="display:flex;justify-content:space-between;font-size:0.72rem;color:var(--muted);margin-top:2px;"><span>0%</span><span>50%</span><span>100%</span></div>'
    + '</div>'
    + '<div id="restock-preview" style="background:rgba(232,240,224,0.5);border:1px solid rgba(78,96,64,0.2);border-radius:9px;padding:10px 14px;margin-bottom:14px;font-size:0.84rem;color:var(--olive-dark);">'
    + '<span class="material-symbols-outlined" style="font-size:0.9rem;vertical-align:middle;margin-right:4px;">info</span>'
    + '<span id="restock-preview-text">Select an item to preview</span>'
    + '</div>'
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
  var slider = document.getElementById('restock-val');
  var num    = document.getElementById('restock-num');
  if (slider && num) num.value = slider.value;
}

function syncRestockSlider() {
  var slider = document.getElementById('restock-val');
  var num    = document.getElementById('restock-num');
  if (!slider || !num) return;
  var v = Math.min(100, Math.max(0, parseInt(num.value, 10) || 0));
  num.value    = v;
  slider.value = v;
}

function updateRestockPreview() {
  var itemSel   = document.getElementById('restock-item');
  var valInput  = document.getElementById('restock-val');
  var numInput  = document.getElementById('restock-num');
  var previewEl = document.getElementById('restock-preview-text');
  if (!itemSel || !valInput || !previewEl) return;

  var items  = loadInventory();
  var item   = items.find(function(i){ return i.id === itemSel.value; });
  if (!item) return;

  // When dropdown changes, sync slider + number to the item's current value
  var newPct = parseInt(valInput.value, 10);
  var newAmt = Math.round(newPct / 100 * item.capacity);
  var oldAmt = Math.round(item.pct / 100 * item.capacity);
  var diff   = newAmt - oldAmt;
  var diffStr = diff >= 0 ? '+' + diff : '' + diff;

  previewEl.textContent = item.name + ': ' + item.pct + '% \u2192 ' + newPct + '% ('
    + oldAmt + ' \u2192 ' + newAmt + ' ' + item.unit + ', ' + diffStr + ' ' + item.unit + ')';
}

function onRestockItemChange() {
  // Sync slider and number to the selected item's current value
  var itemSel  = document.getElementById('restock-item');
  var slider   = document.getElementById('restock-val');
  var numInput = document.getElementById('restock-num');
  if (!itemSel || !slider || !numInput) return;
  var items = loadInventory();
  var item  = items.find(function(i){ return i.id === itemSel.value; });
  if (!item) return;
  slider.value   = item.pct;
  numInput.value = item.pct;
  updateRestockPreview();
}

function submitRestock() {
  var itemSel  = document.getElementById('restock-item');
  var numInput = document.getElementById('restock-num');
  if (!itemSel || !numInput) return;

  var newPct = Math.min(100, Math.max(0, parseInt(numInput.value, 10)));
  if (isNaN(newPct)) { UI.toast('Please enter a valid percentage (0-100).', 'error'); return; }

  var items = loadInventory();
  var item  = items.find(function(i){ return i.id === itemSel.value; });
  if (!item) return;

  item.pct = newPct;
  saveInventory(items);
  renderInventoryBars();

  // Re-check alerts -- remove old alerts for this item then re-evaluate
  alertItems = alertItems.filter(function(a){ return !a.msg.includes(item.name); });
  if (newPct < 30) addAlert(item.name + ' is critically low (' + newPct + '%) \u2014 restock urgently.', 'danger');
  else if (newPct < 50) addAlert(item.name + ' is below 50% (' + newPct + '%).', 'warning');
  renderAlerts();

  document.getElementById('restockModal').remove();
  UI.toast(item.name + ' updated to ' + newPct + '%.', 'success');
}

// -- EDIT INVENTORY MODAL (rename / change capacity) -------
function openEditInventoryModal() {
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
    + '</div>'
    + rows
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
  renderInventoryBars();
  document.getElementById('editInvModal').remove();
  UI.toast('Inventory items updated.', 'success');
}

// -- ADMIN DAILY TASKS -------------------------------------
var ADMIN_TASKS_KEY = 'admin_tasks_' + ((getStoredUser().id) || 'admin');
var defaultAdminTasks = [
  { id:1, label:'Review overnight alerts',           done:false },
  { id:2, label:'Check inventory levels',            done:false },
  { id:3, label:'Review pending orders',             done:false },
  { id:4, label:'Check staff attendance',            done:false },
  { id:5, label:'Review livestock health reports',   done:false },
  { id:6, label:'Approve restock requests',          done:false },
  { id:7, label:'Post daily announcements to staff', done:false },
];

function loadAdminTasks() {
  var tasks;
  try {
    var stored    = localStorage.getItem(ADMIN_TASKS_KEY);
    var storedDay = localStorage.getItem(ADMIN_TASKS_KEY + '_date');
    var todayD    = new Date().toDateString();
    if (!stored || storedDay !== todayD) {
      tasks = defaultAdminTasks.map(function(t){ return Object.assign({}, t); });
      localStorage.setItem(ADMIN_TASKS_KEY, JSON.stringify(tasks));
      localStorage.setItem(ADMIN_TASKS_KEY + '_date', todayD);
    } else {
      tasks = JSON.parse(stored);
    }
  } catch(e) { tasks = defaultAdminTasks.map(function(t){ return Object.assign({}, t); }); }
  renderAdminTasks(tasks);
}

function renderAdminTasks(tasks) {
  var container = document.getElementById('admin-tasks-list');
  var progress  = document.getElementById('admin-tasks-progress');
  if (!container) return;
  var done = tasks.filter(function(t){ return t.done; }).length;
  if (progress) progress.textContent = done + '/' + tasks.length + ' Done';
  if (!tasks.length) {
    container.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;padding:8px 0;">No tasks yet. Click "Add Task" to create one.</p>';
    return;
  }
  container.innerHTML = tasks.map(function(t) {
    return '<div class="task-row' + (t.done ? ' done' : '') + '" id="atask-' + t.id + '">'
      + '<input type="checkbox"' + (t.done ? ' checked' : '') + ' onchange="toggleAdminTask(' + t.id + ',this.checked)" />'
      + '<span style="flex:1;">' + t.label.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</span>'
      + (t.done ? '<span style="font-size:0.7rem;color:var(--olive);">\u2713 Done</span>' : '')
      + '<button onclick="deleteAdminTask(' + t.id + ')" title="Delete task" style="background:none;border:none;cursor:pointer;color:var(--muted);padding:0 2px;display:flex;align-items:center;margin-left:6px;" onmouseover="this.style.color=\'var(--danger)\'" onmouseout="this.style.color=\'var(--muted)\'">'
      + '<span class="material-symbols-outlined" style="font-size:1rem;">close</span></button>'
      + '</div>';
  }).join('');
}

function toggleAdminTask(id, checked) {
  try {
    var tasks = JSON.parse(localStorage.getItem(ADMIN_TASKS_KEY) || '[]');
    var task  = tasks.find(function(t){ return t.id === id; });
    if (task) {
      task.done = checked;
      localStorage.setItem(ADMIN_TASKS_KEY, JSON.stringify(tasks));
      renderAdminTasks(tasks);
      if (checked) UI.toast('Task marked as done!', 'success');
    }
  } catch(e) {}
}

function deleteAdminTask(id) {
  try {
    var tasks = JSON.parse(localStorage.getItem(ADMIN_TASKS_KEY) || '[]');
    tasks = tasks.filter(function(t){ return t.id !== id; });
    localStorage.setItem(ADMIN_TASKS_KEY, JSON.stringify(tasks));
    renderAdminTasks(tasks);
    UI.toast('Task removed.', 'success');
  } catch(e) {}
}

function openAddTaskModal() {
  var existing = document.getElementById('addTaskModal');
  if (existing) { existing.remove(); return; }

  var el = document.createElement('div');
  el.id = 'addTaskModal';
  el.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(42,31,21,0.45);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:16px;';
  el.innerHTML = '<div style="background:#faf6f0;border-radius:18px;box-shadow:0 12px 48px rgba(0,0,0,0.18);width:100%;max-width:420px;font-family:\'Lato\',sans-serif;overflow:hidden;">'
    + '<div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px 12px;background:linear-gradient(135deg,#4e6040,#6b8a5c);">'
    + '<div style="display:flex;align-items:center;gap:8px;"><span class="material-symbols-outlined" style="color:#fff;font-size:1.1rem;">add_task</span>'
    + '<span style="font-family:\'Playfair Display\',serif;font-size:1rem;font-weight:700;color:#fff;">Add Task</span></div>'
    + '<button onclick="document.getElementById(\'addTaskModal\').remove()" style="background:rgba(255,255,255,0.2);border:none;cursor:pointer;width:26px;height:26px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;">'
    + '<span class="material-symbols-outlined" style="font-size:0.95rem;">close</span></button></div>'
    + '<div style="padding:20px;">'
    + '<label style="display:block;font-size:0.75rem;font-weight:700;color:#4a3f35;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;">Task Label <span style="color:#c0392b;">*</span></label>'
    + '<input id="new-task-input" type="text" placeholder="e.g. Review milk production logs" autofocus style="width:100%;padding:10px 13px;border:1.5px solid #e8dfd2;border-radius:9px;font-size:0.88rem;font-family:\'Lato\',sans-serif;color:#2a1f15;background:#fff;outline:none;box-sizing:border-box;" />'
    + '<div id="new-task-err" style="display:none;color:#c0392b;font-size:0.75rem;margin-top:5px;">Task label is required.</div>'
    + '<div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px;">'
    + '<button onclick="document.getElementById(\'addTaskModal\').remove()" style="padding:9px 18px;border:1.5px solid #d4c9b8;border-radius:9px;background:#fff;color:#4a3f35;font-family:\'Lato\',sans-serif;font-size:0.85rem;font-weight:600;cursor:pointer;">Cancel</button>'
    + '<button onclick="addAdminTask()" style="padding:9px 20px;border:none;border-radius:9px;background:linear-gradient(135deg,#4e6040,#6b8a5c);color:#fff;font-family:\'Lato\',sans-serif;font-size:0.85rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px;">'
    + '<span class="material-symbols-outlined" style="font-size:0.95rem;">add</span> Add Task</button>'
    + '</div></div></div>';

  document.body.appendChild(el);
  el.addEventListener('click', function(e){ if(e.target===el) el.remove(); });
  document.addEventListener('keydown', function onKey(e){
    if (e.key === 'Escape') { el.remove(); document.removeEventListener('keydown', onKey); }
    if (e.key === 'Enter' && document.getElementById('addTaskModal')) addAdminTask();
  });
  setTimeout(function(){ var inp = document.getElementById('new-task-input'); if(inp) inp.focus(); }, 50);
}

function addAdminTask() {
  var input  = document.getElementById('new-task-input');
  var errEl  = document.getElementById('new-task-err');
  var label  = input ? input.value.trim() : '';
  if (!label) { if(errEl) errEl.style.display = 'block'; return; }
  if (errEl) errEl.style.display = 'none';

  try {
    var tasks = JSON.parse(localStorage.getItem(ADMIN_TASKS_KEY) || '[]');
    var maxId = tasks.reduce(function(m, t){ return Math.max(m, t.id); }, 0);
    tasks.push({ id: maxId + 1, label: label, done: false });
    localStorage.setItem(ADMIN_TASKS_KEY, JSON.stringify(tasks));
    renderAdminTasks(tasks);
    document.getElementById('addTaskModal').remove();
    UI.toast('Task added!', 'success');
  } catch(e) { UI.toast('Failed to add task.', 'error'); }
}

// -- NOTES (DB-backed) -------------------------------------
async function loadNotes() {
  var feed = document.getElementById('notes-feed');
  if (!feed) return;
  feed.innerHTML = '<div class="skeleton-line"></div><div class="skeleton-line"></div>';
  try {
    var notes = await API.notes.getAll();
    if (!notes.length) {
      feed.innerHTML = '<p style="color:var(--muted);font-size:0.82rem;">No announcements yet.</p>';
      return;
    }
    feed.innerHTML = notes.map(function(n) {
      var timeStr = new Date(n.created_at).toLocaleString();
      return '<div class="note-bubble">'
        + '<div>' + n.text.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</div>'
        + '<div class="note-bubble__meta">' + n.author + ' \u00b7 ' + timeStr + '</div>'
        + '</div>';
    }).join('');
  } catch(e) {
    if (feed) feed.innerHTML = '<p style="color:var(--muted);font-size:0.82rem;">Could not load notes.</p>';
  }
}

document.addEventListener('DOMContentLoaded', function() {
  var saveBtn = document.getElementById('save-note-btn');
  if (saveBtn) {
    saveBtn.addEventListener('click', async function() {
      var input = document.getElementById('note-input');
      var text  = input ? input.value.trim() : '';
      if (!text) { UI.toast('Please write a note first.', 'error'); return; }
      try {
        await API.notes.post(text);
        input.value = '';
        loadNotes();
        UI.toast('Announcement posted!', 'success');
      } catch(e) { UI.toast(e.message || 'Failed to save.', 'error'); }
    });
  }
});

// -- PENDING APPROVALS -------------------------------------
async function loadPendingApprovals() {
  var container = document.getElementById('approvals-list');
  var badge     = document.getElementById('approval-badge');
  if (!container) return;
  container.innerHTML = '<div class="skeleton-card"></div><div class="skeleton-card"></div>';
  try {
    var pending = await API.approval.getPending();
    if (!Array.isArray(pending)) pending = [];

    if (badge) {
      badge.textContent = pending.length;
      badge.style.display = pending.length > 0 ? 'inline-block' : 'none';
    }

    if (!pending.length) {
      container.innerHTML = '<div style="text-align:center;padding:20px 0;">'
        + '<span class="material-symbols-outlined" style="font-size:2rem;color:var(--olive);display:block;margin-bottom:6px;">check_circle</span>'
        + '<p style="color:var(--muted);font-size:0.84rem;">No pending registrations.</p></div>';
      return;
    }

    container.innerHTML = pending.map(function(w) {
      var initials = (w.Worker || '?').charAt(0).toUpperCase();
      var date = w.created_at
        ? new Date(w.created_at).toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' })
        : '\u2014';
      var safeName = (w.Worker || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
      return '<div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--border-light);">'
        + '<div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--olive),var(--olive-light));'
        + 'display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.9rem;color:#fff;flex-shrink:0;">' + initials + '</div>'
        + '<div style="flex:1;min-width:0;">'
        + '<div style="font-weight:700;font-size:0.84rem;">' + (w.Worker || '') + '</div>'
        + '<div style="font-size:0.72rem;color:var(--muted);">' + (w.Email || '') + ' \u00b7 ' + (w.Worker_Role || '') + ' \u00b7 ' + date + '</div>'
        + '</div>'
        + '<div style="display:flex;gap:5px;flex-shrink:0;">'
        + '<button onclick="approveWorker(' + w.Worker_ID + ',this)" '
        + 'style="background:var(--olive);color:#fff;border:none;border-radius:6px;padding:5px 10px;cursor:pointer;font-size:0.75rem;font-weight:600;display:flex;align-items:center;gap:3px;">'
        + '<span class="material-symbols-outlined" style="font-size:0.85rem;">check</span> Approve</button>'
        + '<button onclick="openRejectModal(' + w.Worker_ID + ',\'' + safeName + '\')" '
        + 'style="background:var(--danger-lt);color:var(--danger);border:1px solid rgba(192,57,43,.2);border-radius:6px;padding:5px 10px;cursor:pointer;font-size:0.75rem;font-weight:600;display:flex;align-items:center;gap:3px;">'
        + '<span class="material-symbols-outlined" style="font-size:0.85rem;">close</span> Reject</button>'
        + '</div>'
        + '</div>';
    }).join('');
  } catch(e) {
    if (container) container.innerHTML = '<p style="color:var(--danger);font-size:0.84rem;">Failed to load approvals.</p>';
  }
}

async function approveWorker(id, btn) {
  if (btn) { btn.disabled = true; btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:0.85rem;animation:rmSpin 0.7s linear infinite;">progress_activity</span>'; }
  try {
    await API.approval.approve(id);
    UI.toast('User approved successfully!', 'success');
    loadPendingApprovals();
  } catch(e) {
    UI.toast('Failed to approve: ' + (e.message || 'Unknown error'), 'error');
    if (btn) { btn.disabled = false; btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:0.85rem;">check</span> Approve'; }
  }
}

var _rejectWorkerId = null;

function openRejectModal(id, name) {
  _rejectWorkerId = id;
  var modal  = document.getElementById('rejectModal');
  var nameEl = document.getElementById('reject-worker-name');
  if (nameEl) nameEl.textContent = 'Worker: ' + name;
  if (modal)  modal.style.display = 'flex';
  var confirmBtn = document.getElementById('confirm-reject-btn');
  if (confirmBtn) confirmBtn.onclick = confirmReject;
}

function closeRejectModal() {
  var modal = document.getElementById('rejectModal');
  if (modal) modal.style.display = 'none';
  _rejectWorkerId = null;
}

async function confirmReject() {
  if (!_rejectWorkerId) return;
  var id = _rejectWorkerId;
  closeRejectModal();
  try {
    await API.approval.reject(id);
    UI.toast('Registration rejected.', 'success');
    loadPendingApprovals();
  } catch(e) {
    UI.toast('Failed to reject: ' + (e.message || 'Unknown error'), 'error');
  }
}

// Close reject modal on backdrop click / Escape
document.addEventListener('DOMContentLoaded', function() {
  var modal = document.getElementById('rejectModal');
  if (modal) {
    modal.addEventListener('click', function(e) { if (e.target === modal) closeRejectModal(); });
  }
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && _rejectWorkerId) closeRejectModal();
  });
});

// -- ONLINE STAFF ------------------------------------------
function timeAgo(dateStr) {
  if (!dateStr) return 'Never';
  var diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
  if (diff < 5)    return 'Just now';
  if (diff < 60)   return diff + 's ago';
  if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
  if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
  return Math.floor(diff / 86400) + 'd ago';
}

async function loadOnlineStaff() {
  var container = document.getElementById('online-staff-list');
  var badge     = document.getElementById('online-count-badge');
  var refreshEl = document.getElementById('online-last-refresh');
  if (!container) return;
  container.innerHTML = '<div class="skeleton-card"></div><div class="skeleton-card"></div>';
  try {
    var staff = await API.onlineStatus.getAll();
    if (!Array.isArray(staff)) staff = [];

    var onlineCount = staff.filter(function(w) { return w.is_online == 1; }).length;
    if (badge) badge.textContent = onlineCount + ' online';
    if (refreshEl) refreshEl.textContent = 'Updated ' + new Date().toLocaleTimeString([], { hour:'2-digit', minute:'2-digit' });

    if (!staff.length) {
      container.innerHTML = '<p style="color:var(--muted);font-size:0.84rem;">No staff records found.</p>';
      return;
    }

    container.innerHTML = staff.map(function(w) {
      var online  = w.is_online == 1;
      var dot     = online
        ? '<span style="width:9px;height:9px;border-radius:50%;background:#27ae60;display:inline-block;flex-shrink:0;box-shadow:0 0 0 2px rgba(39,174,96,0.25);"></span>'
        : '<span style="width:9px;height:9px;border-radius:50%;background:#bdc3c7;display:inline-block;flex-shrink:0;"></span>';
      var initial = (w.Worker || '?').charAt(0).toUpperCase();
      var avatarHtml = w.Avatar
        ? '<img src="' + w.Avatar + '" style="width:34px;height:34px;border-radius:50%;object-fit:cover;flex-shrink:0;" onerror="this.style.display=\'none\'" />'
        : '<div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--olive),var(--olive-light));'
          + 'display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem;color:#fff;flex-shrink:0;">' + initial + '</div>';
      var roleClass = w.Worker_Role === 'Admin' ? 'badge--green' : 'badge--muted';
      return '<div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border-light);">'
        + avatarHtml
        + '<div style="flex:1;min-width:0;">'
        + '<div style="font-weight:700;font-size:0.84rem;display:flex;align-items:center;gap:6px;">' + (w.Worker || '') + ' ' + dot + '</div>'
        + '<div style="font-size:0.72rem;color:var(--muted);margin-top:1px;">'
        + '<span class="badge ' + roleClass + '" style="font-size:0.62rem;">' + (w.Worker_Role || '') + '</span>'
        + ' \u00b7 ' + (online
          ? '<span style="color:#27ae60;font-weight:600;">Online</span>'
          : '<span style="color:var(--muted);">Offline</span>')
        + '</div>'
        + '</div>'
        + '<div style="font-size:0.72rem;color:var(--muted);text-align:right;flex-shrink:0;">' + timeAgo(w.last_heartbeat) + '</div>'
        + '</div>';
    }).join('');
  } catch(e) {
    if (container) container.innerHTML = '<p style="color:var(--danger);font-size:0.84rem;padding:8px 0;">Failed to load online status.</p>';
  }
}

// -- HEARTBEAT ---------------------------------------------
function startHeartbeat() {
  function ping() {
    var csrf = localStorage.getItem('csrf_token');
    if (!csrf) return;
    fetch('../dairy_farm_backend/api/v1/heartbeat.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      credentials: 'include'
    }).catch(function() {}); // silent fail -- don't disrupt the UI
  }
  ping(); // immediate first ping
  return setInterval(ping, 30000);
}

// -- INIT --------------------------------------------------
(async function() {
  renderGreeting();
  loadAlertStorage();   // load persisted custom alerts + dismissed ids

  var params = new URLSearchParams(window.location.search);
  if (params.get('access_denied') === '1') {
    UI.toast('Access denied.', 'error');
    history.replaceState({}, '', 'dashboard_admin.php');
  }

  try {
    var res  = await fetch('../dairy_farm_backend/api/v1/auth.php?action=status', { credentials:'include' });
    var data = await res.json();
    if (!data.success) { window.location.href = 'login.php'; return; }
    if (data.data) {
      localStorage.setItem('csrf_token', data.data.csrf_token || '');
      if (data.data.user) localStorage.setItem('user', JSON.stringify(data.data.user));
    }
  } catch(e) { window.location.href = 'login.php'; return; }

  renderGreeting();
  loadAdminTasks();
  loadNotes();
  renderInventoryBars();

  // Load all data in parallel, then build alerts and reports
  var results = await Promise.allSettled([
    API.customers.getAll(),
    API.cows.getAll(),
    API.workers.getAll(),
    API.orders.getAll(),
    loadReminders(),
  ]);

  var customers = results[0].status === 'fulfilled' && Array.isArray(results[0].value) ? results[0].value : [];
  var cows      = results[1].status === 'fulfilled' && Array.isArray(results[1].value) ? results[1].value : [];
  var workers   = results[2].status === 'fulfilled' && Array.isArray(results[2].value) ? results[2].value : [];
  var orders    = results[3].status === 'fulfilled' && Array.isArray(results[3].value) ? results[3].value : [];

  // Store in module-level vars so global search can access them
  allCustomers = customers;
  allCows      = cows;
  allWorkers   = workers;
  allOrders    = orders;

  // Stat cards
  var set = function(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; };
  set('stat-customers', customers.length);
  set('stat-workers',   workers.length);
  set('stat-orders',    orders.length);

  // Livestock + milk
  if (cows.length) {
    set('stat-cows', cows.length);
    updateMilkStat(cows);
  }

  // Orders
  allOrders = orders;
  renderOrders();

  // Staff
  var staffContainer = document.getElementById('staff-list');
  if (staffContainer && workers.length) {
    staffContainer.innerHTML = workers.map(function(w) {
      var initial   = (w.Worker || '?').charAt(0).toUpperCase();
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
  }

  // Livestock
  var liveContainer = document.getElementById('livestock-list');
  var sickBadge     = document.getElementById('sick-badge');
  if (liveContainer && cows.length) {
    var sickCount = 0;
    liveContainer.innerHTML = cows.map(function(c) {
      var health    = c.Health_Status || 'Healthy';
      var isSick    = health === 'Sick' || health === 'Under Treatment';
      if (isSick) sickCount++;
      var dotClass    = isSick ? 'status-dot--sick' : 'status-dot--healthy';
      var healthColor = isSick ? 'var(--danger)' : 'var(--olive)';
      return '<div class="cow-row">'
        + '<div style="display:flex;align-items:center;gap:6px;">'
        + '<span class="status-dot ' + dotClass + '"></span>'
        + '<div><div style="font-weight:700;font-size:0.83rem;">' + c.Cow + '</div>'
        + '<div style="font-size:0.72rem;color:var(--muted);">ID #' + c.Cow_ID + (c.Breed ? ' \u00b7 ' + c.Breed : '') + '</div></div></div>'
        + '<div style="text-align:right;">'
        + '<span style="color:' + healthColor + ';font-weight:700;font-size:0.78rem;">' + health + '</span>'
        + '<div style="font-size:0.72rem;color:var(--muted);">' + parseFloat(c.Production_Liters || 0).toFixed(2) + 'L/day</div></div>'
        + '</div>';
    }).join('');
    if (sickCount > 0) {
      if (sickBadge) { sickBadge.textContent = sickCount + ' sick'; sickBadge.style.display = 'inline-block'; }
      addAlert(sickCount + ' cow(s) marked sick \u2014 vet check required.', 'danger');
    }
  }

  // Dynamic inventory alerts from stored data
  var invItems = loadInventory();
  invItems.forEach(function(item) {
    if (item.pct < 30) addAlert(item.name + ' is critically low (' + item.pct + '%) \u2014 restock urgently.', 'danger');
    else if (item.pct < 50) addAlert(item.name + ' is below 50% (' + item.pct + '%).', 'warning');
  });

  // Low stock alerts from shop Products table
  try {
    var lowStockItems = await API.products.getLowStock();
    lowStockItems.forEach(function(p) {
      if (p.stock_qty === 0) {
        addAlert('\u2018' + p.name + '\u2019 is out of stock in the shop.', 'danger');
      } else {
        addAlert('\u2018' + p.name + '\u2019 is low in stock (' + p.stock_qty + ' ' + p.unit + ' left).', 'warning');
      }
    });
  } catch(e) { /* non-critical */ }

  addAlert('Milk collection truck scheduled for 4:00 PM today.', 'info');

  // Reports
  populateReports(cows, orders, customers, workers);

  // Load new sections: approvals + online staff
  loadPendingApprovals();
  loadOnlineStaff();
  startHeartbeat();
  setInterval(loadOnlineStaff, 30000); // auto-refresh online status every 30s

  // Render alerts last (after all data is collected)
  renderAlerts();
})();


