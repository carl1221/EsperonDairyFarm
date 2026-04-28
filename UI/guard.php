<?php
// ============================================================
// UI/guard.php
// Include at the top of every protected UI page.
// Starts the session and redirects unauthenticated users to login.
// For Admin-only pages, call requireAdminPage() after including this file.
// ============================================================

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => false,
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

/**
 * Redirect to login if no session exists.
 */
function requireAuthPage(): void {
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Redirect to staff dashboard with an access-denied flag if role is not Admin.
 * Call after requireAuthPage().
 */
function requireAdminPage(): void {
    $role = $_SESSION['user']['role'] ?? '';
    if ($role !== 'Admin') {
        header('Location: dashboard_staff.php?access_denied=1');
        exit;
    }
}
