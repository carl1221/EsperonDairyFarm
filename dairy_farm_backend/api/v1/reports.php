<?php
// ============================================================
// api/reports.php
// Staff can submit daily reports.
// Admin can view all reports.
//
// GET    /api/reports.php          → list reports (Admin: all, Staff: own)
// GET    /api/reports.php?id=1     → get one report
// POST   /api/reports.php          → submit a report (Staff or Admin)
// PUT    /api/reports.php?id=1     → update status (Admin only)
// DELETE /api/reports.php?id=1     → delete (Admin only)
// ============================================================

require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();
requireCsrf();

$db      = getConnection();
$method  = $_SERVER['REQUEST_METHOD'];
$id      = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action  = $_GET['action'] ?? '';
$user    = $_SESSION['user'];
$isAdmin = ($user['role'] ?? '') === 'Admin';

// staff_reports table and vw_staff_reports view are created by db.sql.
// No need to CREATE TABLE/VIEW on every request — removed for performance.

// ── CSV Export ────────────────────────────────────────────
if ($method === 'GET' && $action === 'export_csv') {
    if (!$isAdmin) {
        sendError('Only admins can export reports.', 403);
    }

    // Optional status filter (mirrors the client-side filter)
    $statusFilter = $_GET['status'] ?? '';
    $allowedStatuses = ['pending', 'reviewed', 'acknowledged'];

    if ($statusFilter !== '' && !in_array($statusFilter, $allowedStatuses, true)) {
        sendError('Invalid status filter.', 400);
    }

    // Build query — join staff_reports with Worker directly
    if ($statusFilter !== '') {
        $stmt = $db->prepare("
            SELECT r.*, w.Worker AS worker_name, w.Worker_Role AS worker_role
            FROM staff_reports r JOIN Worker w ON r.worker_id = w.Worker_ID
            WHERE r.status = ? ORDER BY r.created_at DESC
        ");
        $stmt->execute([$statusFilter]);
    } else {
        $stmt = $db->query("
            SELECT r.*, w.Worker AS worker_name, w.Worker_Role AS worker_role
            FROM staff_reports r JOIN Worker w ON r.worker_id = w.Worker_ID
            ORDER BY r.created_at DESC
        ");
    }
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Override the application/json header set by bootstrap.php
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="esperon_report_' . date('Y-m-d') . '.csv"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');

    // UTF-8 BOM so Excel opens the file correctly
    fwrite($out, "\xEF\xBB\xBF");

    if (empty($rows)) {
        fputcsv($out, ['No reports found.']);
        fclose($out);
        exit;
    }

    // Column headers — human-readable labels matching the UI
    fputcsv($out, [
        'Report ID',
        'Title',
        'Report Type',
        'Staff Name',
        'Status',
        'Content',
        'Admin Note',
        'Submitted At',
    ]);

    foreach ($rows as $row) {
        fputcsv($out, [
            $row['report_id']   ?? '',
            $row['title']       ?? '',
            $row['report_type'] ?? '',
            $row['worker_name'] ?? '',
            $row['status']      ?? '',
            $row['content']     ?? '',
            $row['admin_note']  ?? '',
            $row['created_at']  ?? '',
        ]);
    }

    fclose($out);
    exit;
}

try {
    switch ($method) {

        case 'GET':
            if ($id) {
                $stmt = $db->prepare("
                    SELECT r.*, w.Worker AS worker_name, w.Worker_Role AS worker_role
                    FROM staff_reports r JOIN Worker w ON r.worker_id = w.Worker_ID
                    WHERE r.report_id = ?
                ");
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                if (!$row) sendError('Report not found.', 404);
                if (!$isAdmin && (int)$row['worker_id'] !== (int)$user['id']) {
                    sendError('Access denied.', 403);
                }
                sendSuccess('Report found.', $row);
            } else {
                if ($isAdmin) {
                    $stmt = $db->query("
                        SELECT r.*, w.Worker AS worker_name, w.Worker_Role AS worker_role
                        FROM staff_reports r JOIN Worker w ON r.worker_id = w.Worker_ID
                        ORDER BY r.created_at DESC
                    ");
                } else {
                    $stmt = $db->prepare("
                        SELECT r.*, w.Worker AS worker_name, w.Worker_Role AS worker_role
                        FROM staff_reports r JOIN Worker w ON r.worker_id = w.Worker_ID
                        WHERE r.worker_id = ? ORDER BY r.created_at DESC
                    ");
                    $stmt->execute([$user['id']]);
                }
                sendSuccess('Reports retrieved.', $stmt->fetchAll());
            }
            break;

        case 'POST':
            $data = getRequestBody();
            validateRequired($data, ['title', 'content', 'report_type']);
            $stmt = $db->prepare("
                INSERT INTO staff_reports (worker_id, report_type, title, content, status)
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $user['id'],
                validateString($data['report_type'], 'report_type', 50),
                validateString($data['title'],       'title',       255),
                validateString($data['content'],     'content',     5000),
            ]);
            sendSuccess('Report submitted successfully.', ['report_id' => $db->lastInsertId()], 201);
            break;

        case 'PUT':
            if (!$id) sendError('Report ID required.');
            if (!$isAdmin) sendError('Only admins can update report status.', 403);
            $data = getRequestBody();
            $allowed = ['pending', 'reviewed', 'acknowledged'];
            $status  = $data['status'] ?? '';
            if (!in_array($status, $allowed, true)) sendError('Invalid status value.', 400);
            $note = isset($data['admin_note']) ? validateString($data['admin_note'], 'admin_note', 1000) : null;
            $stmt = $db->prepare("UPDATE staff_reports SET status = ?, admin_note = ? WHERE report_id = ?");
            $stmt->execute([$status, $note, $id]);
            if ($stmt->rowCount() === 0) sendError('Report not found.', 404);
            sendSuccess('Report updated.');
            break;

        case 'DELETE':
            if (!$id) sendError('Report ID required.');
            if (!$isAdmin) sendError('Only admins can delete reports.', 403);
            $stmt = $db->prepare("DELETE FROM staff_reports WHERE report_id = ?");
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) sendError('Report not found.', 404);
            sendSuccess('Report deleted.');
            break;

        default:
            sendError('Method not allowed.', 405);
    }
} catch (PDOException $e) {
    error_log('Reports error: ' . $e->getMessage());
    sendError('A database error occurred.', 500);
}
