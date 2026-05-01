<?php
// ============================================================
// api/reset_password.php
// Password reset without email — uses registered email as
// the identity verification step (suitable for local/intranet).
//
// POST ?action=verify_identity
//   body: { username, email }
//   → returns a short-lived reset token stored in the session
//
// POST ?action=reset
//   body: { token, password, password_confirm }
//   → verifies token, updates password, clears token
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed.', 405);
}

$action = $_GET['action'] ?? '';
$data   = getRequestBody();

try {
    $db = getConnection();

    // ── Step 1: verify identity ───────────────────────────
    if ($action === 'verify_identity') {
        $username = trim($data['username'] ?? '');
        $email    = trim($data['email']    ?? '');

        if ($username === '' || $email === '') {
            sendError('Username and email are required.', 400);
        }

        // Look up the worker — case-insensitive on both fields
        $stmt = $db->prepare(
            "SELECT Worker_ID, Worker, Email
             FROM Worker
             WHERE LOWER(Worker) = LOWER(?)
               AND LOWER(Email)  = LOWER(?)
             LIMIT 1"
        );
        $stmt->execute([$username, $email]);
        $worker = $stmt->fetch();

        if (!$worker) {
            // Deliberately vague — don't reveal whether username or email was wrong
            sendError('No account found with that username and email combination.', 404);
        }

        // Issue a short-lived reset token (valid for 15 minutes)
        $token     = bin2hex(random_bytes(24));
        $expiresAt = time() + 900; // 15 minutes

        $_SESSION['pw_reset'] = [
            'token'      => $token,
            'worker_id'  => $worker['Worker_ID'],
            'worker_name'=> $worker['Worker'],
            'expires_at' => $expiresAt,
        ];

        sendSuccess('Identity verified.', [
            'token'      => $token,
            'worker_name'=> $worker['Worker'],
        ]);
    }

    // ── Step 2: reset password ────────────────────────────
    elseif ($action === 'reset') {
        $token    = trim($data['token']            ?? '');
        $password = $data['password']              ?? '';
        $confirm  = $data['password_confirm']      ?? '';

        if ($token === '' || $password === '' || $confirm === '') {
            sendError('All fields are required.', 400);
        }

        // Validate token from session
        $reset = $_SESSION['pw_reset'] ?? null;

        if (!$reset || $reset['token'] !== $token) {
            sendError('Invalid or expired reset token. Please start over.', 400);
        }

        if (time() > $reset['expires_at']) {
            unset($_SESSION['pw_reset']);
            sendError('Reset token has expired (15 min limit). Please start over.', 400);
        }

        // Password strength
        if (strlen($password) < 8) {
            sendError('Password must be at least 8 characters.', 400);
        }
        if (!preg_match('/[A-Z]/', $password)) {
            sendError('Password must contain at least one uppercase letter.', 400);
        }
        if (!preg_match('/[0-9]/', $password)) {
            sendError('Password must contain at least one number.', 400);
        }
        if ($password !== $confirm) {
            sendError('Passwords do not match.', 400);
        }

        // Update password
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE Worker SET Password = ? WHERE Worker_ID = ?');
        $stmt->execute([$hash, $reset['worker_id']]);

        // Clear the reset token so it can't be reused
        unset($_SESSION['pw_reset']);

        sendSuccess('Password reset successfully. You can now log in.');
    }

    else {
        sendError('Invalid action.', 400);
    }

} catch (PDOException $e) {
    error_log('Reset password error: ' . $e->getMessage());
    sendError('A database error occurred. Please try again later.', 500);
}
