<?php
// ============================================================
// api/workers.php
// Endpoint: /api/workers.php
//
// GET    /api/workers.php          → list all workers
// GET    /api/workers.php?id=201   → get one worker
// POST   /api/workers.php          → create worker
// PUT    /api/workers.php?id=201   → update worker
// DELETE /api/workers.php?id=201   → delete worker
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/Worker.php';

requireAuth();
requireCsrf();
// Workers management is Admin-only
requireRole(['Admin']);

$worker = new Worker();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;

try {
    switch ($method) {

        case 'GET':
            if ($id) {
                $row = $worker->getById($id);
                $row
                    ? sendSuccess('Worker found.', $row)
                    : sendError('Worker not found.', 404);
            } else {
                sendSuccess('Workers retrieved.', $worker->getAll());
            }
            break;

        case 'POST':
            $data     = getRequestBody();
            $required = ['Worker_ID', 'Worker', 'Worker_Role'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    sendError("Missing required field: $field");
                }
            }
            $worker->create($data)
                ? sendSuccess('Worker created.', null, 201)
                : sendError('Failed to create worker.', 500);
            break;

        case 'PUT':
            if (!$id) sendError('Worker ID is required for update.');
            $data     = getRequestBody();
            $required = ['Worker', 'Worker_Role'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    sendError("Missing required field: $field");
                }
            }
            $worker->update($id, $data)
                ? sendSuccess('Worker updated.')
                : sendError('Worker not found or no changes made.', 404);
            break;

        case 'DELETE':
            if (!$id) sendError('Worker ID is required for delete.');
            $worker->delete($id)
                ? sendSuccess('Worker deleted.')
                : sendError('Worker not found.', 404);
            break;

        default:
            sendError('Method not allowed.', 405);
    }
} catch (PDOException $e) {
    sendError('A database error occurred. Please try again later.', 500);
}
