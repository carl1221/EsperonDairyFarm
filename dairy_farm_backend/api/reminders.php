<?php
// ============================================================
// api/reminders.php
// Endpoint: /api/reminders.php
//
// GET    /api/reminders.php          → list all reminders
// GET    /api/reminders.php?id=1      → get one reminder
// POST   /api/reminders.php          → create reminder
// PUT    /api/reminders.php?id=1      → update reminder
// DELETE /api/reminders.php?id=1      → delete reminder
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/Reminder.php';

requireAuth();
requireCsrf();
// Staff can view reminders (GET) and mark them complete (PUT with status only).
// Only Admin can create, delete, or do full updates.
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' || $method === 'DELETE') {
    requireRole(['Admin']);
}
if ($method === 'PUT') {
    $userRole = $_SESSION['user']['role'] ?? '';
    if ($userRole !== 'Admin') {
        // Staff may only update the status field to 'completed'
        $body = getRequestBody();
        $allowedKeys = array_keys($body);
        $onlyStatus  = $allowedKeys === ['status'] || (count($allowedKeys) === 1 && isset($body['status']));
        if (!$onlyStatus || $body['status'] !== 'completed') {
            sendError('Access denied. Staff may only mark reminders as completed.', 403);
        }
    }
}

$reminder = new Reminder();
$id       = isset($_GET['id']) ? (int) $_GET['id'] : null;

try {
    switch ($method) {

        case 'GET':
            if ($id) {
                $row = $reminder->getById($id);
                $row
                    ? sendSuccess('Reminder found.', $row)
                    : sendError('Reminder not found.', 404);
            } else {
                sendSuccess('Reminders retrieved.', $reminder->getAll());
            }
            break;

        case 'POST':
            $data = getRequestBody();
            validateRequired($data, ['title', 'due_date']);
            $validatedData = [
                'title'       => validateString($data['title'], 'title', 255),
                'description' => isset($data['description']) ? validateString($data['description'], 'description', 1000) : null,
                'due_date'    => $data['due_date'],
                'status'      => isset($data['status']) ? validateString($data['status'], 'status', 20) : 'pending',
            ];
            $reminder->create($validatedData)
                ? sendSuccess('Reminder created.', null, 201)
                : sendError('Failed to create reminder.', 500);
            break;

        case 'PUT':
            if (!$id) sendError('Reminder ID required.');
            $data = getRequestBody();
            $reminder->update($id, $data)
                ? sendSuccess('Reminder updated.')
                : sendError('Failed to update reminder.', 500);
            break;

        case 'DELETE':
            if (!$id) sendError('Reminder ID required.');
            $reminder->delete($id)
                ? sendSuccess('Reminder deleted.')
                : sendError('Failed to delete reminder.', 500);
            break;

        default:
            sendError('Method not allowed.', 405);
    }
} catch (Exception $e) {
    sendError($e->getMessage(), 500);
}