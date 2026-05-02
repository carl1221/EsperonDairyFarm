<?php
// ============================================================
// api/set_customer_password.php  —  Admin sets a customer password
//
// POST body: { customer_id, password, password_confirm }
// Admin only.
// ============================================================

require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();
requireCsrf();
requireRole(['Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed.', 405);
}

$data       = getRequestBody();
$customerId = isset($data['customer_id']) ? (int) $data['customer_id'] : 0;
$password   = $data['password']         ?? '';
$confirm    = $data['password_confirm'] ?? '';

if (!$customerId) sendError('customer_id is required.', 400);
if ($password === '') sendError('Password is required.', 400);

// Use shared validator from bootstrap.php
$pwError = validatePasswordStrength($password);
if ($pwError) sendError($pwError, 400);

if ($password !== $confirm) sendError('Passwords do not match.', 400);

try {
    $db   = getConnection();
    $stmt = $db->prepare('SELECT CID, Customer_Name FROM Customer WHERE CID = ?');
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();

    if (!$customer) sendError('Customer not found.', 404);

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $db->prepare('UPDATE Customer SET Password = ? WHERE CID = ?')
       ->execute([$hash, $customerId]);

    sendSuccess("Password set for {$customer['Customer_Name']}.");

} catch (PDOException $e) {
    error_log('Set customer password error: ' . $e->getMessage());
    sendError('A database error occurred.', 500);
}
