// ============================================================
// js/modules/reminders.js  —  Shared reminder helpers
// Used by: dashboard_admin.js, dashboard_staff.js
//
// Globals expected from the host page:
//   reminders  — array (declared in host file)
//   addAlert() — function (declared in host file)
//   UI.toast() — from ui.js
// ============================================================

function getStatusInfo(dueDate, status) {
  if (status === 'completed') return { color:'var(--olive)', bg:'var(--olive-light)', label:'Completed', urgent:false };
  var now = new Date(), due = new Date(dueDate), h = (due - now) / (1000 * 60 * 60);
  if (h < 0)   return { color:'var(--danger)', bg:'var(--danger-lt)', label:'Overdue',  urgent:true  };
  if (h <= 24) return { color:'#f39c12',       bg:'#fef9e7',          label:'Due Soon', urgent:true  };
  return { color:'var(--olive)', bg:'var(--olive-light)', label:'Pending', urgent:false };
}

function formatDueDate(dateStr) {
  var date = new Date(dateStr), now = new Date(), tomorrow = new Date(now);
  tomorrow.setDate(tomorrow.getDate() + 1);
  var t = date.toLocaleTimeString([], { hour:'numeric', minute:'2-digit', hour12:true });
  if (date.toDateString() === now.toDateString())      return 'Today, ' + t;
  if (date.toDateString() === tomorrow.toDateString()) return 'Tomorrow, ' + t;
  var m = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  return m[date.getMonth()] + ' ' + date.getDate() + ', ' + date.getFullYear() + ' \u2014 ' + t;
}

async function loadReminders() {
  var list = document.getElementById('remindersList');
  if (!list) return;
  list.innerHTML = '<div class="skeleton-line"></div><div class="skeleton-line"></div><div class="skeleton-line"></div>';
  try {
    var res  = await fetch('../dairy_farm_backend/api/v1/reminders.php', { credentials:'include' });
    var data = await res.json();
    if (data.success) {
      reminders = data.data || [];
      renderReminders();
      updateReminderBadge();
      var overdue = reminders.filter(function(r) {
        return r.status === 'pending' && getStatusInfo(r.due_date, r.status).label === 'Overdue';
      });
      if (overdue.length) addAlert(overdue.length + ' overdue reminder(s) need attention.', 'danger');
    } else {
      list.innerHTML = '<p style="color:var(--danger);font-size:0.84rem;">Failed to load reminders.</p>';
    }
  } catch(e) {
    list.innerHTML = '<p style="color:var(--danger);font-size:0.84rem;">Error loading reminders.</p>';
  }
}

function updateReminderBadge() {
  var badge = document.getElementById('reminderBadge');
  if (!badge) return;
  var n = reminders.filter(function(r) {
    return r.status !== 'completed' && getStatusInfo(r.due_date, r.status).urgent;
  }).length;
  badge.textContent = n;
  badge.style.display = n > 0 ? 'inline-block' : 'none';
}
