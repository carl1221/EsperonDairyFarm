<?php
// ============================================================
// api/signup.php
// Validates and processes new user registration.
//
// Role = Staff | Admin  → inserts into Worker table (pending approval)
// Role = Customer       → inserts into Customer table (no approval)
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';

const ALLOWED_ROLES = ['Staff', 'Admin', 'Customer'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }

    $data     = getRequestBody();
    $username = trim($data['username'] ?? '');
    $email    = trim($data['email']    ?? '');
    $password = $data['password']      ?? '';
    $role     = trim($data['role']     ?? 'Staff');
    $recaptchaToken = $data['g-recaptcha-response'] ?? '';

    // ── reCAPTCHA ───────────────────────────────────────────
    if (!$recaptchaToken || !verifyRecaptcha($recaptchaToken)) {
        sendError('reCAPTCHA verification failed. Please try again.', 400);
    }

    // ── Common validation ───────────────────────────────────
    if ($username === '') sendError('Username is required.');
    if ($email    === '') sendError('Email address is required.');
    if ($password === '') sendError('Password is required.');

    if (strlen($username) < 3 || strlen($username) > 50) {
        sendError('Username must be between 3 and 50 characters.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('Please enter a valid email address.');
    }
    // Use shared password validator from bootstrap.php
    $pwError = validatePasswordStrength($password);
    if ($pwError) sendError($pwError);
    if (!in_array($role, ALLOWED_ROLES, true)) {
        sendError('Invalid role selected.');
    }

    $db             = getConnection();
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // ── Customer signup ─────────────────────────────────────
    if ($role === 'Customer') {
        $address    = trim($data['address']     ?? '');
        $contactNum = trim($data['contact_num'] ?? '');

        if ($address === '')    sendError('Address is required for customer accounts.');
        // Use shared contact validator from bootstrap.php
        $contactError = validateContactNumber($contactNum);
        if ($contactError) sendError($contactError);

        // Check for duplicate username in Customer table (Customer_Name)
        $stmt = $db->prepare('SELECT CID FROM Customer WHERE Customer_Name = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            sendError('A customer with that name already exists.', 409);
        }

        $db->beginTransaction();
        try {
            // Insert address
            $addrStmt = $db->prepare('INSERT INTO Address (Address) VALUES (?)');
            $addrStmt->execute([$address]);
            $addressId = (int) $db->lastInsertId();

            // Insert customer — store hashed password so they can log in
            $custStmt = $db->prepare(
                'INSERT INTO Customer (Customer_Name, Address_ID, Contact_Num, Password) VALUES (?, ?, ?, ?)'
            );
            $custStmt->execute([$username, $addressId, $contactNum, $hashedPassword]);
            $newId = $db->lastInsertId();
            $db->commit();

            sendSuccess(
                'Customer account created! You can now log in.',
                ['customer_id' => $newId],
                201
            );
        } catch (PDOException $e) {
            $db->rollBack();
            throw $e;
        }
    }

    // ── Staff / Admin signup ────────────────────────────────
    else {
        // Check for duplicate username or email in Worker table
        $stmt = $db->prepare('SELECT Worker_ID FROM Worker WHERE Worker = ? OR Email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            sendError('Username or Email is already taken.', 409);
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                'INSERT INTO Worker (Worker, Worker_Role, Email, Password, approval_status)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$username, $role, $email, $hashedPassword, 'pending']);
            $newId = $db->lastInsertId();
            $db->commit();

            sendSuccess(
                'Account created! Your registration is pending admin approval.',
                ['user_id' => $newId],
                201
            );
        } catch (PDOException $e) {
            $db->rollBack();
            throw $e;
        }
    }

} catch (PDOException $e) {
    error_log('Signup Error: ' . $e->getMessage());
    sendError('A database error occurred.', 500);
}
