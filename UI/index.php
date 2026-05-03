<?php
// ============================================================
// UI/index.php  —  Entry point
// Logged-in users → their dashboard
// Guests → public landing page
// ============================================================
require_once __DIR__ . '/guard.php';

if (!isset($_SESSION['user'])) {
    header('Location: landing.php');
    exit;
}

$role = $_SESSION['user']['role'] ?? 'Staff';

if ($role === 'Admin') {
    header('Location: dashboard_admin.php');
} elseif ($role === 'Customer') {
    header('Location: dashboard_customer.php');
} else {
    header('Location: dashboard_staff.php');
}
exit;
