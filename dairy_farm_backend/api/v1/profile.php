<?php
// ============================================================
// api/v1/profile.php
//
// GET  → get current user profile (Worker or Customer)
// POST → update name and/or avatar photo
// ============================================================

require_once __DIR__ . '/../../config/bootstrap.php';

requireAuth();

$method     = $_SERVER['REQUEST_METHOD'];
$userId     = $_SESSION['user']['id'];
$userRole   = $_SESSION['user']['role'] ?? '';
$isCustomer = ($userRole === 'Customer');

try {
    switch ($method) {

        // ── Get current profile ───────────────────────────
        case 'GET':
            if ($isCustomer) {
                $stmt = getConnection()->prepare(
                    'SELECT c.CID AS id, c.Customer_Name AS name, \'Customer\' AS role,
                            \'\' AS email, \'\' AS avatar
                     FROM Customer c
                     WHERE c.CID = ?'
                );
                $stmt->execute([$userId]);
                $row = $stmt->fetch();
                if (!$row) sendError('Customer not found.', 404);

                sendSuccess('Profile retrieved.', [
                    'Worker_ID'   => $row['id'],
                    'Worker'      => $row['name'],
                    'Worker_Role' => 'Customer',
                    'Email'       => '',
                    'Avatar'      => '',
                ]);
            } else {
                $stmt = getConnection()->prepare(
                    'SELECT Worker_ID, Worker, Worker_Role, Email, Avatar
                     FROM Worker WHERE Worker_ID = ?'
                );
                $stmt->execute([$userId]);
                $row = $stmt->fetch();
                if (!$row) sendError('User not found.', 404);
                sendSuccess('Profile retrieved.', $row);
            }
            break;

        // ── Update name and/or avatar ─────────────────────
        case 'POST':
            requireCsrf();

            $newName   = trim($_POST['name'] ?? '');
            $avatarUrl = null;

            // ── Handle file upload (workers only) ─────────
            if (!$isCustomer && !empty($_FILES['avatar']['tmp_name'])) {
                $file    = $_FILES['avatar'];
                $maxSize = 2 * 1024 * 1024;
                $allowed = ['image/jpeg', 'image/png', 'image/webp'];

                if ($file['size'] > $maxSize) {
                    sendError('Image must be under 2 MB.', 400);
                }

                $finfo    = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($file['tmp_name']);
                if (!in_array($mimeType, $allowed, true)) {
                    sendError('Only JPEG, PNG, or WebP images are allowed.', 400);
                }

                $uploadDir = __DIR__ . '/../../../UI/uploads/avatars/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Delete old avatar
                $stmtOld = getConnection()->prepare('SELECT Avatar FROM Worker WHERE Worker_ID = ?');
                $stmtOld->execute([$userId]);
                $oldRow = $stmtOld->fetch();
                if (!empty($oldRow['Avatar'])) {
                    $oldPath = __DIR__ . '/../../../UI/' . ltrim($oldRow['Avatar'], '/');
                    if (file_exists($oldPath)) unlink($oldPath);
                }

                $ext = match($mimeType) {
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                    default      => 'jpg',
                };
                $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
                $destPath = $uploadDir . $filename;

                if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                    sendError('Failed to save image. Please try again.', 500);
                }

                $avatarUrl = 'uploads/avatars/' . $filename;
            }

            if ($isCustomer) {
                // Customers: only name update supported
                if ($newName === '') sendError('Nothing to update.', 400);

                getConnection()->prepare(
                    'UPDATE Customer SET Customer_Name = ? WHERE CID = ?'
                )->execute([$newName, $userId]);

                $_SESSION['user']['name'] = $newName;

                sendSuccess('Profile updated.', [
                    'Worker_ID'   => $userId,
                    'Worker'      => $newName,
                    'Worker_Role' => 'Customer',
                    'Email'       => '',
                    'Avatar'      => '',
                ]);
            } else {
                // Workers: name and/or avatar
                $fields = [];
                $params = [];

                if ($newName !== '') {
                    $fields[] = 'Worker = ?';
                    $params[] = $newName;
                }
                if ($avatarUrl !== null) {
                    $fields[] = 'Avatar = ?';
                    $params[] = $avatarUrl;
                }

                if (empty($fields)) sendError('Nothing to update.', 400);

                $params[] = $userId;
                getConnection()->prepare(
                    'UPDATE Worker SET ' . implode(', ', $fields) . ' WHERE Worker_ID = ?'
                )->execute($params);

                if ($newName !== '')   $_SESSION['user']['name']   = $newName;
                if ($avatarUrl !== null) $_SESSION['user']['avatar'] = $avatarUrl;

                $stmt = getConnection()->prepare(
                    'SELECT Worker_ID, Worker, Worker_Role, Email, Avatar
                     FROM Worker WHERE Worker_ID = ?'
                );
                $stmt->execute([$userId]);
                $updated = $stmt->fetch();

                sendSuccess('Profile updated.', $updated);
            }
            break;

        default:
            sendError('Method not allowed.', 405);
    }

} catch (PDOException $e) {
    error_log('Profile PDOException: ' . $e->getMessage());
    sendError('A database error occurred. Please try again later.', 500);
}
