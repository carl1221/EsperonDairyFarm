<?php
// ============================================================
// api/customer_profile.php
// Customer profile endpoint
//
// GET  /api/customer_profile.php  → get current customer profile
// POST /api/customer_profile.php  → update name, phone, address
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';

if (!isset($_SESSION['customer'])) {
    sendError('Customer authentication required.', 401);
}

$customerId = (int) $_SESSION['customer']['id'];
$method     = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {

        case 'GET':
            $stmt = getConnection()->prepare(
                'SELECT c.CID, c.Customer_Name, c.Email, c.Phone,
                        a.Address, a.Contact_Num
                 FROM Customer c
                 JOIN Address a ON c.Address_ID = a.Address_ID
                 WHERE c.CID = ?'
            );
            $stmt->execute([$customerId]);
            $row = $stmt->fetch();
            $row ? sendSuccess('Profile retrieved.', $row) : sendError('Customer not found.', 404);
            break;

        case 'POST':
            $headers = getallheaders();
            $token   = $headers['X-CSRF-Token'] ?? '';
            if ($token === '' || !validateCsrfToken($token)) {
                sendError('Invalid or missing CSRF token.', 403);
            }

            $data = getRequestBody();
            $name    = isset($data['name'])    ? validateString($data['name'],    'name',    100) : null;
            $phone   = isset($data['phone'])   ? validateString($data['phone'],   'phone',   20)  : null;
            $address = isset($data['address']) ? validateString($data['address'], 'address', 100) : null;

            $db = getConnection();

            if ($name !== null) {
                $db->prepare('UPDATE Customer SET Customer_Name = ? WHERE CID = ?')
                   ->execute([$name, $customerId]);
                $_SESSION['customer']['name'] = $name;
            }
            if ($phone !== null) {
                $db->prepare('UPDATE Customer SET Phone = ? WHERE CID = ?')
                   ->execute([$phone, $customerId]);
                $_SESSION['customer']['phone'] = $phone;
            }
            if ($address !== null) {
                $db->prepare('UPDATE Address SET Address = ? WHERE Address_ID = (SELECT Address_ID FROM Customer WHERE CID = ?)')
                   ->execute([$address, $customerId]);
                $_SESSION['customer']['address'] = $address;
            }

            sendSuccess('Profile updated.', $_SESSION['customer']);
            break;

        default:
            sendError('Method not allowed.', 405);
    }

} catch (PDOException $e) {
    error_log('Customer Profile PDOException: ' . $e->getMessage());
    sendError('A database error occurred. Please try again later.', 500);
}
