<?php
// ============================================================
// UI/index.php  —  Dashboard router
// Redirects to the correct dashboard based on the user's role.
// ============================================================
require_once __DIR__ . '/guard.php';
requireAuthPage();

$role = $_SESSION['user']['role'] ?? 'Staff';

if ($role === 'Admin') {
    header('Location: dashboard_admin.php');
} else {
    header('Location: dashboard_staff.php');
}
exit;
