<?php
// ============================================================
// UI/dashboard_staff.php  —  Staff Dashboard
// Limited access: reminders (view only), orders count, daily tasks
// ============================================================
require_once __DIR__ . '/guard.php';
requireAuthPage();
// Staff only — if somehow an Admin lands here, redirect them
if (($_SESSION['user']['role'] ?? '') === 'Admin') {
    header('Location: dashboard_admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Staff Dashboard — Esperon Dairy Farm</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>

<nav class="nav" id="app-nav"></nav>

<main class="main">
  <!-- Page Header -->
  <div class="page-header">
    <div>
      <h1 class="page-title" id="page-greeting">Welcome back!</h1>
      <p class="page-subtitle" id="page-subtitle">Here's your work summary for today.</p>
    </div>
  </div>

  <!-- Stat Cards (Staff sees Orders only) -->
  <div class="stats-grid">
    <div class="stat-card stat-card--danger">
      <div class="stat-card__icon"><span class="material-symbols-outlined">shopping_cart</span></div>
      <div class="stat-card__content">
        <div class="stat-card__val" id="stat-orders">0</div>
        <div class="stat-card__label">Total Orders</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-card__icon"><span class="material-symbols-outlined">task_alt</span></div>
      <div class="stat-card__content">
        <div class="stat-card__val" id="stat-tasks">0</div>
        <div class="stat-card__label">Pending Tasks</div>
      </div>
    </div>
    <div class="stat-card stat-card--gold">
      <div class="stat-card__icon"><span class="material-symbols-outlined">warning</span></div>
      <div class="stat-card__content">
        <div class="stat-card__val" id="stat-overdue">0</div>
        <div class="stat-card__label">Overdue Tasks</div>
      </div>
    </div>
  </div>

  <!-- Info Cards Row -->
  <div class="info-cards-row">
    <!-- Reminders (Staff: view only) -->
    <div class="card" style="flex:2;">
      <div class="card__header">
        <span class="card__title" style="display:flex;align-items:center;gap:8px;">
          <span class="material-symbols-outlined" style="font-size:1.2rem;color:var(--danger);">warning</span>
          My Reminders
          <span id="reminderBadge" class="badge badge--red" style="display:none;font-size:0.65rem;margin-left:8px;">0</span>
        </span>
        <span style="font-size:0.75rem;color:var(--muted);font-style:italic;">View only</span>
      </div>
      <div id="remindersList" style="padding:16px 24px;"></div>
    </div>

    <!-- Daily Tasks -->
    <div class="card">
      <div class="card__header">
        <span class="card__title" style="display:flex;align-items:center;gap:8px;">
          <span class="material-symbols-outlined" style="font-size:1.2rem;color:var(--olive);">checklist</span>
          Daily Tasks
        </span>
        <span class="badge badge--green" style="font-size:0.7rem;">4/5 Done</span>
      </div>
      <div style="padding:16px 24px;">
        <ul style="list-style:none;padding:0;margin:0;">
          <li style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border-light);">
            <input type="checkbox" checked disabled style="accent-color:var(--olive);width:16px;height:16px;" />
            <span style="font-size:0.85rem;color:var(--muted);text-decoration:line-through;">Milk Quality Check</span>
          </li>
          <li style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border-light);">
            <input type="checkbox" checked disabled style="accent-color:var(--olive);width:16px;height:16px;" />
            <span style="font-size:0.85rem;color:var(--muted);text-decoration:line-through;">Calving Prep</span>
          </li>
          <li style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border-light);">
            <input type="checkbox" checked disabled style="accent-color:var(--olive);width:16px;height:16px;" />
            <span style="font-size:0.85rem;color:var(--muted);text-decoration:line-through;">Equipment Check</span>
          </li>
          <li style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border-light);">
            <input type="checkbox" checked disabled style="accent-color:var(--olive);width:16px;height:16px;" />
            <span style="font-size:0.85rem;color:var(--muted);text-decoration:line-through;">Feed Mix</span>
          </li>
          <li style="display:flex;align-items:center;gap:10px;padding:8px 0;">
            <input type="checkbox" disabled style="accent-color:var(--olive);width:16px;height:16px;" />
            <span style="font-size:0.85rem;color:var(--text);">Pasture Rotation Planning</span>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <!-- Quick Links for Staff -->
  <div class="card" style="margin-top:0;">
    <div class="card__header">
      <span class="card__title" style="display:flex;align-items:center;gap:8px;">
        <span class="material-symbols-outlined" style="font-size:1.2rem;color:var(--olive);">apps</span>
        Quick Access
      </span>
    </div>
    <div style="padding:20px 24px;display:flex;gap:12px;flex-wrap:wrap;">
      <a href="orders.php" style="display:flex;align-items:center;gap:8px;padding:12px 20px;background:rgba(78,96,64,0.08);border:1px solid rgba(78,96,64,0.2);border-radius:12px;text-decoration:none;color:var(--olive-dark);font-weight:600;font-size:0.88rem;transition:background 0.15s;"
        onmouseover="this.style.background='rgba(78,96,64,0.15)'" onmouseout="this.style.background='rgba(78,96,64,0.08)'">
        <span class="material-symbols-outlined" style="font-size:1.2rem;">shopping_cart</span> View Orders
      </a>
    </div>
  </div>
</main>

<script src="js/api.js"></script>
<script src="js/ui.js"></script>
<script src="js/nav.js"></script>
<script>
// ── Reminders (read-only for Staff) ───────────────────────
let reminders = [];

function getStatusInfo(dueDate, status) {
  if (status === 'completed') return { color:'var(--olive)', bg:'var(--olive-light)', label:'Completed', urgent:false };
  const now=new Date(), due=new Date(dueDate), h=(due-now)/(1000*60*60);
  if (h<0)   return { color:'var(--danger)', bg:'var(--danger-lt)', label:'Overdue', urgent:true };
  if (h<=24) return { color:'#f39c12', bg:'#fef9e7', label:'Due Soon', urgent:true };
  return { color:'var(--olive)', bg:'var(--olive-light)', label:'Pending', urgent:false };
}

function formatDueDate(dateStr) {
  const date=new Date(dateStr), now=new Date(), tomorrow=new Date(now);
  tomorrow.setDate(tomorrow.getDate()+1);
  const timeStr=date.toLocaleTimeString([],{hour:'numeric',minute:'2-digit',hour12:true});
  if(date.toDateString()===now.toDateString()) return `Today, ${timeStr}`;
  if(date.toDateString()===tomorrow.toDateString()) return `Tomorrow, ${timeStr}`;
  const m=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  return `${m[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()} — ${timeStr}`;
}

async function loadReminders() {
  const list=document.getElementById('remindersList');
  if(!list) return;
  list.innerHTML='<p style="color:var(--text-light);font-size:0.85rem;"><span class="spinner"></span> Loading...</p>';
  try {
    const res=await fetch('../dairy_farm_backend/api/reminders.php',{credentials:'include'});
    const data=await res.json();
    if(data.success){reminders=data.data||[];renderReminders();updateBadge();}
    else list.innerHTML='<p style="color:var(--danger);font-size:0.85rem;">Failed to load.</p>';
  } catch(e){list.innerHTML='<p style="color:var(--danger);font-size:0.85rem;">Error loading.</p>';}
}

function renderReminders() {
  const list=document.getElementById('remindersList');
  if(!list) return;
  if(!reminders.length){list.innerHTML='<p style="color:var(--text-light);font-size:0.85rem;">No tasks assigned yet.</p>';return;}
  const sorted=[...reminders].sort((a,b)=>new Date(a.due_date)-new Date(b.due_date));
  list.innerHTML=sorted.map(r=>{
    const s=getStatusInfo(r.due_date,r.status), done=r.status==='completed';
    return `<div style="background:${s.bg};border-radius:8px;padding:12px;margin-bottom:10px;border-left:3px solid ${s.color};">
      <span style="font-size:0.7rem;color:${s.color};font-weight:700;text-transform:uppercase;">${s.label}</span>
      <p style="font-size:0.9rem;color:var(--text);margin:4px 0;${done?'text-decoration:line-through;opacity:0.6;':''}">${r.title}</p>
      <span style="font-size:0.7rem;color:var(--text-light);">Due: ${formatDueDate(r.due_date)}</span>
    </div>`;
  }).join('');

  // Update stat cards
  const pending=reminders.filter(r=>r.status==='pending').length;
  const overdue=reminders.filter(r=>r.status==='pending'&&getStatusInfo(r.due_date,r.status).label==='Overdue').length;
  document.getElementById('stat-tasks').textContent=pending;
  document.getElementById('stat-overdue').textContent=overdue;
}

function updateBadge() {
  const badge=document.getElementById('reminderBadge');
  if(!badge) return;
  const n=reminders.filter(r=>r.status!=='completed'&&getStatusInfo(r.due_date,r.status).urgent).length;
  badge.textContent=n; badge.style.display=n>0?'inline-block':'none';
}

loadReminders();

// ── Greeting ──────────────────────────────────────────────
function getStoredUser(){try{return JSON.parse(localStorage.getItem('user')||'{}');}catch{return {};}}
function renderGreeting(){
  const u=getStoredUser(), h=new Date().getHours();
  const tod=h<12?'Good morning':h<18?'Good afternoon':'Good evening';
  document.getElementById('page-greeting').innerHTML=`${tod}, ${u.name||'there'}! <span class="material-symbols-outlined" style="vertical-align:middle;font-size:1.5rem;">waving_hand</span>`;
  document.getElementById('page-subtitle').textContent=`Welcome Staff · Here's your work summary for today.`;
}

// ── Init ──────────────────────────────────────────────────
(async () => {
  renderGreeting();
  const params=new URLSearchParams(window.location.search);
  if(params.get('access_denied')==='1'){UI.toast('Access denied. Admins only.','error');history.replaceState({},'','dashboard_staff.php');}
  try {
    const res=await fetch('../dairy_farm_backend/api/auth.php?action=status',{credentials:'include'});
    const data=await res.json();
    if(!data.success){window.location.href='login.php';return;}
    if(data.data){
      localStorage.setItem('csrf_token',data.data.csrf_token||'');
      if(data.data.user)localStorage.setItem('user',JSON.stringify(data.data.user));
    }
    // Load orders count
    const ordersRes=await Promise.allSettled([API.orders.getAll()]);
    const orders=ordersRes[0].status==='fulfilled'&&Array.isArray(ordersRes[0].value)?ordersRes[0].value:[];
    document.getElementById('stat-orders').textContent=orders.length;
  } catch { window.location.href='login.php'; return; }
  renderGreeting();
})();
</script>
</body>
</html>
