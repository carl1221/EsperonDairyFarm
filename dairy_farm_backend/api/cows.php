<?php
// ============================================================
// api/cows.php
// Endpoint: /api/cows.php
//
// GET    /api/cows.php              → list all cows (active + inactive)
// GET    /api/cows.php?active=1     → list active cows only
// GET    /api/cows.php?id=101       → get one cow
// POST   /api/cows.php              → create cow (Admin only)
// PUT    /api/cows.php?id=101       → update cow (Admin only)
// PATCH  /api/cows.php?id=101       → deactivate cow / update health (Admin only)
// DELETE /api/cows.php?id=101       → hard-delete cow (Admin only)
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/Cow.php';

requireAuth();
requireCsrf();
// Staff can view cows (GET), only Admin can create/update/delete
if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    requireRole(['Admin']);
}

$cow    = new Cow();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id'])     ? (int) $_GET['id']     : null;
$active = isset($_GET['active']) ? (bool)$_GET['active'] : false;

try {
    switch ($method) {

        case 'GET':
            if ($id) {
                $row = $cow->getById($id);
                $row
                    ? sendSuccess('Cow found.', $row)
                    : sendError('Cow not found.', 404);
            } else {
                sendSuccess('Cows retrieved.', $cow->getAll($active));
            }
            break;

        case 'POST':
            $data = getRequestBody();
            validateRequired($data, ['Cow']);

            $allowedHealth = ['Healthy', 'Sick', 'Under Treatment', 'Retired'];
            $healthStatus  = $data['Health_Status'] ?? 'Healthy';
            if (!in_array($healthStatus, $allowedHealth, true)) {
                sendError("Health_Status must be one of: " . implode(', ', $allowedHealth), 400);
            }

            $validatedData = [
                'Cow'              => validateString($data['Cow'],  'Cow',   100),
                'Breed'            => isset($data['Breed'])         ? validateString($data['Breed'],         'Breed',         100) : null,
                'Date_Of_Birth'    => $data['Date_Of_Birth']        ?? null,
                'Production_Liters'=> isset($data['Production_Liters']) ? (float)$data['Production_Liters'] : 0.0,
                'Health_Status'    => $healthStatus,
                'is_active'        => isset($data['is_active'])     ? (int)$data['is_active'] : 1,
                'notes'            => $data['notes']                ?? null,
            ];

            $newId = $cow->create($validatedData);
            sendSuccess('Cow created.', ['Cow_ID' => $newId], 201);
            break;

        case 'PUT':
            if (!$id) sendError('Cow ID is required for update.');
            $data = getRequestBody();
            validateRequired($data, ['Cow']);

            $allowedHealth = ['Healthy', 'Sick', 'Under Treatment', 'Retired'];
            $healthStatus  = $data['Health_Status'] ?? 'Healthy';
            if (!in_array($healthStatus, $allowedHealth, true)) {
                sendError("Health_Status must be one of: " . implode(', ', $allowedHealth), 400);
            }

            $validatedData = [
                'Cow'              => validateString($data['Cow'],  'Cow',   100),
                'Breed'            => isset($data['Breed'])         ? validateString($data['Breed'],         'Breed',         100) : null,
                'Date_Of_Birth'    => $data['Date_Of_Birth']        ?? null,
                'Production_Liters'=> isset($data['Production_Liters']) ? (float)$data['Production_Liters'] : 0.0,
                'Health_Status'    => $healthStatus,
                'is_active'        => isset($data['is_active'])     ? (int)$data['is_active'] : 1,
                'notes'            => $data['notes']                ?? null,
            ];

            $cow->update($id, $validatedData)
                ? sendSuccess('Cow updated.')
                : sendError('Cow not found or no changes made.', 404);
            break;

        case 'PATCH':
            // Soft-delete (deactivate) or quick health status update
            if (!$id) sendError('Cow ID is required.');
            $data = getRequestBody();

            if (isset($data['is_active']) && (int)$data['is_active'] === 0) {
                $cow->deactivate($id)
                    ? sendSuccess('Cow deactivated.')
                    : sendError('Cow not found.', 404);
            } else {
                $cow->update($id, $data)
                    ? sendSuccess('Cow updated.')
                    : sendError('Cow not found or no changes made.', 404);
            }
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
