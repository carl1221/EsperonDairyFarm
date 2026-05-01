<?php
// ============================================================
// api/heartbeat.php
// POST → update last_heartbeat timestamp for the current user
// Falls back gracefully if migration column doesn't exist yet.
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError('Method not allowed.', 405);
requireCsrf();

$db = getConnection();

// Only update if the column exists
$colCheck     = $db->query("SHOW COLUMNS FROM Worker")->fetchAll(PDO::FETCH_COLUMN);
$hasHeartbeat = in_array('last_heartbeat', $colCheck);

if ($hasHeartbeat) {
    $userId = $_SESSION['user']['id'];
    $stmt   = $db->prepare('UPDATE Worker SET last_heartbeat = NOW() WHERE Worker_ID = ?');
    $stmt->execute([$userId]);
}

sendSuccess('Heartbeat recorded.');
