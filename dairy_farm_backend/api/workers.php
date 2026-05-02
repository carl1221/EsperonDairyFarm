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

// Defined once — used in both POST and PUT
const ALLOWED_WORKER_ROLES = ['Admin', 'Staff'];

/**
 * Validate and return Worker_Role from request data.
 * Calls sendError() and exits on invalid value.
 */
function validateWorkerRole(array $data): string {
    $role = $data['Worker_Role'] ?? '';
    if (!in_array($role, ALLOWED_WORKER_ROLES, true)) {
        sendError("Worker_Role must be 'Admin' or 'Staff'.", 400);
    }
    return $role;
}

try {
    switch ($method) {

        case 'GET':
            if ($id) {
                $row = $worker->getById($id);
                $row
                    ? sendSuccess('Worker found.', $row)
                    : sendError('Worker not found.', 404);
            } else {
                // Optional ?role=Staff or ?role=Admin filter
                $roleFilter = isset($_GET['role']) ? trim($_GET['role']) : null;
                if ($roleFilter !== null && !in_array($roleFilter, ['Admin', 'Staff'], true)) {
                    sendError("role must be 'Admin' or 'Staff'.", 400);
                }
                sendSuccess('Workers retrieved.', $worker->getAll($roleFilter));
            }
            break;

        case 'POST':
            $data = getRequestBody();
            validateRequired($data, ['Worker', 'Worker_Role']);
            $validatedData = [
                'Worker_ID'   => isset($data['Worker_ID']) ? validateInteger($data['Worker_ID'], 'Worker_ID') : null,
                'Worker'      => validateString($data['Worker'], 'Worker', 100),
                'Worker_Role' => validateWorkerRole($data),
            ];
            $worker->create($validatedData)
                ? sendSuccess('Worker created.', null, 201)
                : sendError('Failed to create worker.', 500);
            break;

        case 'PUT':
            if (!$id) sendError('Worker ID is required for update.');
            $data = getRequestBody();
            validateRequired($data, ['Worker', 'Worker_Role']);
            $validatedData = [
                'Worker'      => validateString($data['Worker'], 'Worker', 100),
                'Worker_Role' => validateWorkerRole($data),
            ];
            $worker->update($id, $validatedData)
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
