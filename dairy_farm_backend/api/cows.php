<?php
// ============================================================
// api/cows.php
// Endpoint: /api/cows.php
//
// GET    /api/cows.php          → list all cows
// GET    /api/cows.php?id=101   → get one cow
// POST   /api/cows.php          → create cow
// PUT    /api/cows.php?id=101   → update cow
// DELETE /api/cows.php?id=101   → delete cow
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/Cow.php';

requireAuth();
requireCsrf();
// Cow management is Admin-only
requireRole(['Admin']);

$cow    = new Cow();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;

try {
    switch ($method) {

        case 'GET':
            if ($id) {
                $row = $cow->getById($id);
                $row
                    ? sendSuccess('Cow found.', $row)
                    : sendError('Cow not found.', 404);
            } else {
                sendSuccess('Cows retrieved.', $cow->getAll());
            }
            break;

        case 'POST':
            $data     = getRequestBody();
            $required = ['Cow_ID', 'Cow', 'Production'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    sendError("Missing required field: $field");
                }
            }
            $cow->create($data)
                ? sendSuccess('Cow created.', null, 201)
                : sendError('Failed to create cow.', 500);
            break;

        case 'PUT':
            if (!$id) sendError('Cow ID is required for update.');
            $data     = getRequestBody();
            $required = ['Cow', 'Production'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    sendError("Missing required field: $field");
                }
            }
            $cow->update($id, $data)
                ? sendSuccess('Cow updated.')
                : sendError('Cow not found or no changes made.', 404);
            break;

        case 'DELETE':
            if (!$id) sendError('Cow ID is required for delete.');
            $cow->delete($id)
                ? sendSuccess('Cow deleted.')
                : sendError('Cow not found.', 404);
            break;

        default:
            sendError('Method not allowed.', 405);
    }
} catch (PDOException $e) {
    sendError('A database error occurred. Please try again later.', 500);
}
