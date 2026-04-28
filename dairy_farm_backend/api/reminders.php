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

$reminder = new Reminder();
$method   = $_SERVER['REQUEST_METHOD'];
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
            $data     = getRequestBody();
            $required = ['title', 'due_date'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    sendError("Missing required field: $field");
                }
            }
            $reminder->create($data)
                ? sendSuccess('Reminder created.', null, 201)
                : sendError('Failed to create reminder.', 500);
            break;

        case 'PUT':
            if (!$id) {
                sendError('Reminder ID required.');
            }
            $data = getRequestBody();
            $reminder->update($id, $data)
                ? sendSuccess('Reminder updated.')
                : sendError('Failed to update reminder.', 500);
            break;

        case 'DELETE':
            if (!$id) {
                sendError('Reminder ID required.');
            }
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