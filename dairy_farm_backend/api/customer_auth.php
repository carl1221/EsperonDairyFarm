<?php
// ============================================================
// api/customer_auth.php
// Customer authentication endpoints
//
// POST /api/customer_auth.php?action=login   → customer login
// POST /api/customer_auth.php?action=logout  → customer logout
// GET  /api/customer_auth.php?action=status  → check customer session
// POST /api/customer_auth.php?action=signup  → register new customer
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {

        case 'POST':
            if ($action === 'login') {
                $data     = getRequestBody();
                $email    = trim($data['email']    ?? '');
                $password = $data['password'] ?? '';

                if ($email === '' || $password === '') {
                    sendError('Email and password are required.');
                }

                $stmt = getConnection()->prepare(
                    'SELECT c.CID, c.Customer_Name, c.Email, c.Phone, c.Password,
                            a.Address, a.Contact_Num
                     FROM Customer c
                     JOIN Address a ON c.Address_ID = a.Address_ID
                     WHERE c.Email = ?
                     LIMIT 1'
                );
                $stmt->execute([$email]);
                $customer = $stmt->fetch();

                if (!$customer || !$customer['Password'] || !password_verify($password, $customer['Password'])) {
                    sendError('Invalid email or password.', 401);
                }

                session_regenerate_id(true);

                $_SESSION['customer'] = [
                    'id'      => $customer['CID'],
                    'name'    => $customer['Customer_Name'],
                    'email'   => $customer['Email'],
                    'phone'   => $customer['Phone'] ?? '',
                    'address' => $customer['Address'] ?? '',
                ];

                $csrfToken = generateCsrfToken();

                sendSuccess('Login successful.', [
                    'customer'   => $_SESSION['customer'],
                    'csrf_token' => $csrfToken,
                ]);

            } elseif ($action === 'logout') {
                $_SESSION = [];
                if (ini_get('session.use_cookies')) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000,
                        $params['path'], $params['domain'],
                        $params['secure'], $params['httponly']);
                }
                session_destroy();
                sendSuccess('Logged out successfully.');

            } elseif ($action === 'signup') {
                $data    = getRequestBody();
                $name    = trim($data['name']    ?? '');
                $email   = trim($data['email']   ?? '');
                $phone   = trim($data['phone']   ?? '');
                $address = trim($data['address'] ?? '');
                $password = $data['password'] ?? '';

                if ($name === '' || $email === '' || $password === '') {
                    sendError('Name, email, and password are required.');
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    sendError('Please enter a valid email address.');
                }
                if (strlen($password) < 6) {
                    sendError('Password must be at least 6 characters.');
                }

                $db = getConnection();

                // Check duplicate email
                $check = $db->prepare('SELECT CID FROM Customer WHERE Email = ?');
                $check->execute([$email]);
                if ($check->fetch()) {
                    sendError('An account with this email already exists.', 409);
                }

                $db->beginTransaction();
                try {
                    // Create address record
                    $addrStmt = $db->prepare('INSERT INTO Address (Address, Contact_Num) VALUES (?, ?)');
                    $addrStmt->execute([$address ?: 'Not provided', $phone ?: 'Not provided']);
                    $addressId = (int) $db->lastInsertId();

                    // Create customer record
                    $custStmt = $db->prepare(
                        'INSERT INTO Customer (Customer_Name, Email, Password, Phone, Address_ID)
                         VALUES (?, ?, ?, ?, ?)'
                    );
                    $custStmt->execute([
                        $name,
                        $email,
                        password_hash($password, PASSWORD_DEFAULT),
                        $phone,
                        $addressId,
                    ]);
                    $newId = (int) $db->lastInsertId();
                    $db->commit();

                    sendSuccess('Account created successfully!', ['customer_id' => $newId], 201);
                } catch (PDOException $e) {
                    $db->rollBack();
                    throw $e;
                }

            } else {
                sendError('Invalid action.', 400);
            }
            break;

        case 'GET':
            if ($action === 'status') {
                if (isset($_SESSION['customer'])) {
                    sendSuccess('Authenticated.', [
                        'customer'   => $_SESSION['customer'],
                        'csrf_token' => generateCsrfToken(),
                    ]);
                } else {
                    sendError('Not authenticated.', 401);
                }
            } else {
                sendError('Invalid action.', 400);
            }
            break;

        default:
            sendError('Method not allowed.', 405);
    }

} catch (PDOException $e) {
    error_log('Customer Auth PDOException: ' . $e->getMessage());
    sendError('A database error occurred. Please try again later.', 500);
}
