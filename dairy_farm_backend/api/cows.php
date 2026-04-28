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
            $data = getRequestBody();
            validateRequired($data, ['Cow_ID', 'Cow', 'Production']);
            $validatedData = [
                'Cow_ID'     => validateInteger($data['Cow_ID'], 'Cow_ID'),
                'Cow'        => validateString($data['Cow'], 'Cow', 50),
                'Production' => validateString($data['Production'], 'Production', 20),
            ];
            $cow->create($validatedData)
                ? sendSuccess('Cow created.', null, 201)
                : sendError('Failed to create cow.', 500);
            break;

        case 'PUT':
            if (!$id) sendError('Cow ID is required for update.');
            $data = getRequestBody();
            validateRequired($data, ['Cow', 'Production']);
            $validatedData = [
                'Cow'        => validateString($data['Cow'], 'Cow', 50),
                'Production' => validateString($data['Production'], 'Production', 20),
            ];
            $cow->update($id, $validatedData)
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
