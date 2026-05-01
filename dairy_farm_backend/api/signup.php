<?php
// ============================================================
// api/signup.php
// Validates and processes new user registration.
// ============================================================

// CORS headers are handled by bootstrap.php

require_once __DIR__ . '/../config/bootstrap.php';

const ALLOWED_ROLES = ['Staff', 'Admin'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }

    $data     = getRequestBody();
    $username = trim($data['username'] ?? '');
    $email    = trim($data['email']    ?? '');
    $password = $data['password'] ?? ''; 
    $role     = trim($data['role']     ?? 'Staff');
    $recaptchaToken = $data['g-recaptcha-response'] ?? '';

    // Verify reCAPTCHA token
    if (!$recaptchaToken || !verifyRecaptcha($recaptchaToken)) {
        sendError('reCAPTCHA verification failed. Please try again.', 400);
    }

    // ── Validation ──────────────────────────────────────────
    if ($username === '') sendError('Username is required');
    if ($email    === '') sendError('Email address is required');
    if ($password === '') sendError('Password is required');

    if (strlen($username) < 3 || strlen($username) > 50) {
        sendError('Username must be between 3 and 50 characters');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('Please enter a valid email address');
    }

    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        sendError('Password must be 8+ chars, with one uppercase and one number');
    }

    $db = getConnection();

    // ── Check Duplicates ────────────────────────────────────
    $stmt = $db->prepare('SELECT Worker_ID FROM Worker WHERE Worker = ? OR Email = ?');
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        sendError('Username or Email is already taken.', 409);
    }

    // ── Hash & Insert ───────────────────────────────────────
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $db->beginTransaction();
    try {
        $stmt = $db->prepare(
            'INSERT INTO Worker (Worker, Worker_Role, Email, Password, approval_status)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$username, $role, $email, $hashedPassword, 'pending']);
        $newId = $db->lastInsertId();
        $db->commit();

        sendSuccess('Account created! Your registration is pending admin approval.', ['user_id' => $newId], 201);

    } catch (PDOException $insertEx) {
        $db->rollBack();
        throw $insertEx;
    }

} catch (PDOException $e) {
    error_log('Signup Error: ' . $e->getMessage());
    sendError('A database error occurred.', 500);
}