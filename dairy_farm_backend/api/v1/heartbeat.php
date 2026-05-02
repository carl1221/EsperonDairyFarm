<?php
// ============================================================
// api/heartbeat.php
// POST → update last_heartbeat timestamp for the current user
// ============================================================

require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError('Method not allowed.', 405);
requireCsrf();

// Customers don't have a Worker row — skip heartbeat silently
if (($_SESSION['user']['role'] ?? '') === 'Customer') {
    sendSuccess('Heartbeat recorded.');
}

$db     = getConnection();
$userId = (int) $_SESSION['user']['id'];

$db->prepare('UPDATE Worker SET last_heartbeat = NOW() WHERE Worker_ID = ?')
   ->execute([$userId]);

sendSuccess('Heartbeat recorded.');
