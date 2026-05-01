<?php
// ============================================================
// api/approval.php
// GET  → list workers by approval_status filter
// POST → approve or reject a worker (Admin only)
// Falls back gracefully if migration columns don't exist yet.
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
requireAuth();
requireRole(['Admin']);

$method = $_SERVER['REQUEST_METHOD'];
$db = getConnection();

try {
    // Check if migration columns exist
    $colCheck    = $db->query("SHOW COLUMNS FROM Worker")->fetchAll(PDO::FETCH_COLUMN);
    $hasApproval = in_array('approval_status', $colCheck);
    $hasCreated  = in_array('created_at',      $colCheck);

    if ($method === 'GET') {
        $filter  = $_GET['filter'] ?? 'pending';
        $allowed = ['pending', 'approved', 'rejected', 'all'];
        if (!in_array($filter, $allowed, true)) $filter = 'pending';

        $approvalSel = $hasApproval ? ', approval_status' : ", 'approved' AS approval_status";
        $createdSel  = $hasCreated  ? ', created_at'      : ', NULL AS created_at';
        $select      = "SELECT Worker_ID, Worker, Worker_Role, Email{$approvalSel}{$createdSel} FROM Worker";

        if (!$hasApproval || $filter === 'all') {
            $stmt = $db->query($select . " ORDER BY Worker_ID DESC");
        } else {
            $stmt = $db->prepare($select . " WHERE approval_status = ? ORDER BY created_at DESC");
            $stmt->execute([$filter]);
        }
        sendSuccess('Approvals retrieved.', $stmt->fetchAll());

    } elseif ($method === 'POST') {
        requireCsrf();
        $data     = getRequestBody();
        $workerId = isset($data['worker_id']) ? (int)$data['worker_id'] : 0;
        $action   = $data['action'] ?? '';
        if (!$workerId) sendError('worker_id is required.', 400);
        if (!in_array($action, ['approve','reject'], true)) sendError('Invalid action.', 400);

        if (!$hasApproval) {
            sendError('Approval system not yet set up. Please run the database migration.', 503);
        }

        $status = $action === 'approve' ? 'approved' : 'rejected';
        $stmt   = $db->prepare('UPDATE Worker SET approval_status = ? WHERE Worker_ID = ?');
        $stmt->execute([$status, $workerId]);
        if ($stmt->rowCount() === 0) sendError('Worker not found.', 404);
        sendSuccess('Worker ' . $status . '.');

    } else {
        sendError('Method not allowed.', 405);
    }
} catch (PDOException $e) {
    error_log('Approval error: ' . $e->getMessage());
    sendError('A database error occurred.', 500);
}
