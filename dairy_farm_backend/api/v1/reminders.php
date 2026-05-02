<?php
// ============================================================
// api/reminders.php
// Endpoint: /api/reminders.php
//
// GET    /api/reminders.php               → list all reminders
// GET    /api/reminders.php?id=1          → get one reminder
// GET    /api/reminders.php?assignee=201  → get reminders assigned to worker
// POST   /api/reminders.php               → create reminder (Admin only)
// PUT    /api/reminders.php?id=1          → full update (Admin only)
// PATCH  /api/reminders.php?id=1          → mark as completed (Staff or Admin)
// DELETE /api/reminders.php?id=1          → delete reminder (Admin only)
// ============================================================

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../models/Reminder.php';

requireAuth();
requireCsrf();

$method   = $_SERVER['REQUEST_METHOD'];
$userRole = $_SESSION['user']['role'] ?? '';

// Access control
if (in_array($method, ['POST', 'DELETE'], true)) {
    requireRole(['Admin']);
}
if ($method === 'PUT') {
    requireRole(['Admin']);
}
if ($method === 'PATCH') {
    // Staff may only mark their own assigned reminders as completed
    if ($userRole !== 'Admin') {
        $body = getRequestBody();
        $keys = array_keys($body);
        $onlyStatus = count($keys) === 1 && isset($body['status']);
        if (!$onlyStatus || $body['status'] !== 'completed') {
            sendError('Access denied. Staff may only mark reminders as completed.', 403);
        }
    }
}

$reminder  = new Reminder();
$id        = isset($_GET['id'])       ? (int) $_GET['id']       : null;
$assignee  = isset($_GET['assignee']) ? (int) $_GET['assignee'] : null;

try {
    switch ($method) {

        case 'GET':
            if ($id) {
                $row = $reminder->getById($id);
                $row
                    ? sendSuccess('Reminder found.', $row)
                    : sendError('Reminder not found.', 404);
            } elseif ($assignee) {
                sendSuccess('Reminders retrieved.', $reminder->getByAssignee($assignee));
            } else {
                sendSuccess('Reminders retrieved.', $reminder->getAll());
            }
            break;

        case 'POST':
            $data = getRequestBody();
            validateRequired($data, ['title', 'due_date']);

            $validatedData = [
                'created_by'  => $_SESSION['user']['id'] ?? null,
                'assigned_to' => isset($data['assigned_to']) ? (int)$data['assigned_to'] : null,
                'title'       => validateString($data['title'],       'title',       255),
                'description' => isset($data['description'])
                                    ? validateString($data['description'], 'description', 1000)
                                    : null,
                'due_date'    => $data['due_date'],
                'status'      => $data['status'] ?? 'pending',
            ];

            $reminder->create($validatedData)
                ? sendSuccess('Reminder created.', null, 201)
                : sendError('Failed to create reminder.', 500);
            break;

        case 'PUT':
            if (!$id) sendError('Reminder ID required.');
            $data = getRequestBody();
            validateRequired($data, ['title', 'due_date']);

            $validatedData = [
                'title'       => validateString($data['title'],       'title',       255),
                'description' => isset($data['description'])
                                    ? validateString($data['description'], 'description', 1000)
                                    : null,
                'due_date'    => $data['due_date'],
                'status'      => $data['status']      ?? 'pending',
                'assigned_to' => isset($data['assigned_to']) ? (int)$data['assigned_to'] : null,
            ];

            $reminder->update($id, $validatedData)
                ? sendSuccess('Reminder updated.')
                : sendError('Failed to update reminder.', 500);
            break;

        case 'PATCH':
            // Partial update — typically just flipping status to 'completed'
            if (!$id) sendError('Reminder ID required.');
            $data = getRequestBody();
            // Validate status value if provided
            if (isset($data['status'])) {
                $allowedStatuses = ['pending', 'completed'];
                if (!in_array($data['status'], $allowedStatuses, true)) {
                    sendError("Invalid status value. Must be one of: " . implode(', ', $allowedStatuses), 400);
                }
            }
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
