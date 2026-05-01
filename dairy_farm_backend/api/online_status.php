<?php
// ============================================================
// api/online_status.php
// GET → list all approved workers with online/offline status
//       (online = last_heartbeat within the last 60 seconds)
// Admin only.
// Falls back gracefully if migration columns don't exist yet.
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
requireAuth();
requireRole(['Admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Method not allowed.', 405);

$db = getConnection();

// Check which columns exist so we work before and after migration
$colCheck = $db->query("SHOW COLUMNS FROM Worker")->fetchAll(PDO::FETCH_COLUMN);
$hasApproval   = in_array('approval_status',  $colCheck);
$hasHeartbeat  = in_array('last_heartbeat',   $colCheck);

// Build query dynamically based on available columns
$heartbeatSel = $hasHeartbeat
    ? ", last_heartbeat, CASE WHEN last_heartbeat >= DATE_SUB(NOW(), INTERVAL 60 SECOND) THEN 1 ELSE 0 END AS is_online"
    : ", NULL AS last_heartbeat, 0 AS is_online";

$whereClause = $hasApproval
    ? "WHERE approval_status = 'approved'"
    : "";   // no filter — show all workers if column missing

$sql = "SELECT Worker_ID, Worker, Worker_Role, Avatar {$heartbeatSel}
        FROM Worker {$whereClause}
        ORDER BY " . ($hasHeartbeat ? "is_online DESC, " : "") . "Worker ASC";

$stmt = $db->query($sql);
sendSuccess('Online status retrieved.', $stmt->fetchAll());
