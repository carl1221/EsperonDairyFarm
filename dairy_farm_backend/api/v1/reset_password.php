<?php
// ============================================================
// api/reset_password.php
// Password reset with 6-digit verification code.
// Supports both Worker and Customer accounts.
//
// POST ?action=verify_identity
//   body: { username, email }
//   → generates a 6-digit code stored in session (15 min TTL)
//   → returns account_name + code (display in UI; wire up email later)
//
// POST ?action=verify_code
//   body: { code }
//   → validates the code, issues a short-lived reset token
//
// POST ?action=reset
//   body: { token, password, password_confirm }
//   → verifies token, updates password in correct table, clears session
// ============================================================

require_once __DIR__ . '/../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed.', 405);
}

$action = $_GET['action'] ?? '';
$data   = getRequestBody();

try {
    $db = getConnection();

    // ── Step 1: verify identity → send code ──────────────
    if ($action === 'verify_identity') {
        $username = trim($data['username'] ?? '');
        $email    = trim($data['email']    ?? '');

        if ($username === '' || $email === '') {
            sendError('Username and email are required.', 400);
        }

        // Try Worker table first
        $stmt = $db->prepare(
            "SELECT Worker_ID AS id, Worker AS name, 'worker' AS account_type, Email
             FROM Worker
             WHERE LOWER(Worker) = LOWER(?)
               AND LOWER(Email)  = LOWER(?)
             LIMIT 1"
        );
        $stmt->execute([$username, $email]);
        $account = $stmt->fetch();

        // Fall back to Customer table
        if (!$account) {
            $stmt = $db->prepare(
                "SELECT CID AS id, Customer_Name AS name, 'customer' AS account_type, Email
                 FROM Customer
                 WHERE LOWER(Customer_Name) = LOWER(?)
                   AND LOWER(Email)         = LOWER(?)
                 LIMIT 1"
            );
            $stmt->execute([$username, $email]);
            $account = $stmt->fetch();
        }

        if (!$account) {
            // Deliberately vague — don't reveal whether username or email was wrong
            sendError('No account found with that username and email combination.', 404);
        }

        // Generate a 6-digit verification code (valid for 15 minutes)
        $code      = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = time() + 900; // 15 minutes

        $_SESSION['pw_reset'] = [
            'stage'        => 'code',   // waiting for code verification
            'code'         => $code,
            'attempts'     => 0,
            'account_id'   => $account['id'],
            'account_name' => $account['name'],
            'account_type' => $account['account_type'],
            'expires_at'   => $expiresAt,
        ];

        // TODO: send $code via email to $account['Email']
        // For now the code is returned in the response so the UI can display it.
        sendSuccess('Verification code sent.', [
            'worker_name' => $account['name'],
            'code'        => $code,   // remove this line once real email is wired up
        ]);
    }

    // ── Step 2: verify the 6-digit code ──────────────────
    elseif ($action === 'verify_code') {
        $code = trim($data['code'] ?? '');

        if ($code === '') {
            sendError('Verification code is required.', 400);
        }

        $reset = $_SESSION['pw_reset'] ?? null;

        if (!$reset || ($reset['stage'] ?? '') !== 'code') {
            sendError('No pending verification. Please start over.', 400);
        }

        if (time() > $reset['expires_at']) {
            unset($_SESSION['pw_reset']);
            sendError('Verification code has expired (15 min limit). Please start over.', 400);
        }

        // Throttle: max 5 attempts
        $_SESSION['pw_reset']['attempts'] = ($reset['attempts'] ?? 0) + 1;
        if ($_SESSION['pw_reset']['attempts'] > 5) {
            unset($_SESSION['pw_reset']);
            sendError('Too many incorrect attempts. Please start over.', 429);
        }

        if ($code !== $reset['code']) {
            $remaining = 5 - $_SESSION['pw_reset']['attempts'];
            sendError('Incorrect code. ' . ($remaining > 0 ? "$remaining attempt(s) remaining." : 'No attempts remaining.'), 400);
        }

        // Code is correct — upgrade session to password-reset stage
        $token = bin2hex(random_bytes(24));
        $_SESSION['pw_reset'] = [
            'stage'        => 'reset',
            'token'        => $token,
            'account_id'   => $reset['account_id'],
            'account_name' => $reset['account_name'],
            'account_type' => $reset['account_type'],
            'expires_at'   => $reset['expires_at'],
        ];

        sendSuccess('Code verified.', [
            'token'      => $token,
            'worker_name'=> $reset['account_name'],
        ]);
    }

    // ── Step 3: reset password ────────────────────────────
    elseif ($action === 'reset') {
        $token    = trim($data['token']       ?? '');
        $password = $data['password']         ?? '';
        $confirm  = $data['password_confirm'] ?? '';

        if ($token === '' || $password === '' || $confirm === '') {
            sendError('All fields are required.', 400);
        }

        $reset = $_SESSION['pw_reset'] ?? null;

        if (!$reset || ($reset['stage'] ?? '') !== 'reset' || $reset['token'] !== $token) {
            sendError('Invalid or expired reset token. Please start over.', 400);
        }

        if (time() > $reset['expires_at']) {
            unset($_SESSION['pw_reset']);
            sendError('Reset token has expired (15 min limit). Please start over.', 400);
        }

        $pwError = validatePasswordStrength($password);
        if ($pwError) sendError($pwError, 400);

        if ($password !== $confirm) {
            sendError('Passwords do not match.', 400);
        }

        // Update password in the correct table
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($reset['account_type'] === 'customer') {
            $stmt = $db->prepare('UPDATE Customer SET Password = ? WHERE CID = ?');
        } else {
            $stmt = $db->prepare('UPDATE Worker SET Password = ? WHERE Worker_ID = ?');
        }
        $stmt->execute([$hash, $reset['account_id']]);

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
