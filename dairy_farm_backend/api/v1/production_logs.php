<?php
// ============================================================
// api/production_logs.php  —  Daily milk production tracking
//
// GET  /api/production_logs.php?days=30  → last N days totals (chart data)
// GET  /api/production_logs.php?cow=101  → logs for one cow
// POST /api/production_logs.php          → record today's production
// ============================================================

require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();
requireCsrf();

$method = $_SERVER['REQUEST_METHOD'];
$db     = getConnection();

try {
    if ($method === 'GET') {
        $cowId = isset($_GET['cow']) ? (int)$_GET['cow'] : null;
        $days  = isset($_GET['days']) ? max(1, min(365, (int)$_GET['days'])) : 30;

        if ($cowId) {
            // Single cow history
            $stmt = $db->prepare("
                SELECT pl.log_date, pl.liters, pl.notes, w.Worker AS recorded_by
                FROM production_logs pl
                JOIN Worker w ON pl.recorded_by = w.Worker_ID
                WHERE pl.cow_id = ?
                ORDER BY pl.log_date DESC
                LIMIT 90
            ");
            $stmt->execute([$cowId]);
            sendSuccess('Production logs retrieved.', $stmt->fetchAll());
        } else {
            // Daily totals for chart — sum all cows per day
            $stmt = $db->prepare("
                SELECT log_date, SUM(liters) AS total_liters, COUNT(DISTINCT cow_id) AS cow_count
                FROM production_logs
                WHERE log_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY log_date
                ORDER BY log_date ASC
            ");
            $stmt->execute([$days]);
            sendSuccess('Production totals retrieved.', $stmt->fetchAll());
        }
    }

    elseif ($method === 'POST') {
        requireRole(['Admin', 'Staff']);
        $data   = getRequestBody();
        $cowId  = isset($data['cow_id'])  ? (int)$data['cow_id']  : 0;
        $liters = isset($data['liters'])  ? (float)$data['liters'] : 0;
        $date   = $data['log_date'] ?? date('Y-m-d');
        $notes  = $data['notes']    ?? null;

        if (!$cowId)    sendError('cow_id is required.', 400);
        if ($liters < 0) sendError('liters must be a positive number.', 400);

        $date = validateDate($date, 'log_date');

        // Upsert — if a log already exists for this cow+date, update it
        $stmt = $db->prepare("
            INSERT INTO production_logs (cow_id, log_date, liters, notes, recorded_by)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE liters = VALUES(liters), notes = VALUES(notes),
                                    recorded_by = VALUES(recorded_by)
        ");
        $stmt->execute([$cowId, $date, $liters, $notes, (int)$_SESSION['user']['id']]);

        // Also update the cow's average Production_Liters
        $db->prepare("UPDATE Cow SET Production_Liters = ? WHERE Cow_ID = ?")
           ->execute([$liters, $cowId]);

        sendSuccess('Production logged.', ['log_date' => $date, 'liters' => $liters]);
    }

    else {
        sendError('Method not allowed.', 405);
    }

} catch (PDOException $e) {
    error_log('Production logs error: ' . $e->getMessage());
    sendError('A database error occurred.', 500);
}
