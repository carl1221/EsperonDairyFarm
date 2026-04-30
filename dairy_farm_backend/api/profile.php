<?php
// ============================================================
// api/profile.php
// Endpoint: /api/profile.php
//
// GET  /api/profile.php          → get current user profile
// POST /api/profile.php          → update name and/or avatar photo
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';

requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['user']['id'];

try {
    switch ($method) {

        // ── Get current profile ───────────────────────────
        case 'GET':
            $stmt = getConnection()->prepare(
                'SELECT Worker_ID, Worker, Worker_Role, Email, Avatar FROM Worker WHERE Worker_ID = ?'
            );
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
            if (!$row) sendError('User not found.', 404);
            sendSuccess('Profile retrieved.', $row);
            break;

        // ── Update name and/or avatar ─────────────────────
        case 'POST':
            // Validate CSRF for state-changing request
            $headers = getallheaders();
            $token   = $headers['X-CSRF-Token'] ?? '';
            if ($token === '' || !validateCsrfToken($token)) {
                sendError('Invalid or missing CSRF token', 403);
            }

            $newName   = trim($_POST['name'] ?? '');
            $avatarUrl = null;

            // ── Handle file upload ────────────────────────
            if (!empty($_FILES['avatar']['tmp_name'])) {
                $file     = $_FILES['avatar'];
                $maxSize  = 2 * 1024 * 1024; // 2 MB
                $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                if ($file['size'] > $maxSize) {
                    sendError('Image must be under 2 MB.', 400);
                }

                // Validate MIME type from actual file content (not just extension)
                $finfo    = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($file['tmp_name']);
                if (!in_array($mimeType, $allowed, true)) {
                    sendError('Only JPEG, PNG, GIF, or WebP images are allowed.', 400);
                }

                // Save to uploads/avatars/
                $uploadDir = __DIR__ . '/../../UI/uploads/avatars/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Delete old avatar if it exists
                $stmtOld = getConnection()->prepare('SELECT Avatar FROM Worker WHERE Worker_ID = ?');
                $stmtOld->execute([$userId]);
                $oldRow = $stmtOld->fetch();
                if (!empty($oldRow['Avatar'])) {
                    $oldPath = __DIR__ . '/../../UI/' . ltrim($oldRow['Avatar'], '/');
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $ext      = match($mimeType) {
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/gif'  => 'gif',
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

            // Build update query dynamically
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

            if (empty($fields)) {
                sendError('Nothing to update.', 400);
            }

            $params[] = $userId;
            $sql = 'UPDATE Worker SET ' . implode(', ', $fields) . ' WHERE Worker_ID = ?';
            getConnection()->prepare($sql)->execute($params);

            // Refresh session with updated values
            if ($newName !== '') {
                $_SESSION['user']['name'] = $newName;
            }
            if ($avatarUrl !== null) {
                $_SESSION['user']['avatar'] = $avatarUrl;
            }

            // Return updated user data
            $stmt = getConnection()->prepare(
                'SELECT Worker_ID, Worker, Worker_Role, Email, Avatar FROM Worker WHERE Worker_ID = ?'
            );
            $stmt->execute([$userId]);
            $updated = $stmt->fetch();

            sendSuccess('Profile updated.', $updated);
            break;

        default:
            sendError('Method not allowed.', 405);
    }

} catch (PDOException $e) {
    error_log('Profile PDOException: ' . $e->getMessage());
    sendError('A database error occurred. Please try again later.', 500);
}
