<?php
// ============================================================
// api/online_status.php
// GET → list all approved workers with online/offline status
//       (online = last_heartbeat within the last 60 seconds)
// Admin only.
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
requireAuth();
requireRole(['Admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Method not allowed.', 405);

$db = getConnection();

// All migration columns are guaranteed present — no SHOW COLUMNS needed
$stmt = $db->query("
    SELECT Worker_ID, Worker, Worker_Role, Avatar,
           last_heartbeat,
           CASE
               WHEN last_heartbeat >= DATE_SUB(NOW(), INTERVAL 60 SECOND) THEN 1
               ELSE 0
           END AS is_online
    FROM Worker
    WHERE approval_status = 'approved'
    ORDER BY is_online DESC, Worker ASC
");

sendSuccess('Online status retrieved.', $stmt->fetchAll());
