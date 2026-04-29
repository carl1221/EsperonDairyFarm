<?php
// ============================================================
// api/customer_orders.php
// Customer-facing orders endpoint
//
// GET  /api/customer_orders.php        → get current customer's orders
// POST /api/customer_orders.php        → place a new order
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';

// Require customer session (not worker session)
if (!isset($_SESSION['customer'])) {
    sendError('Customer authentication required.', 401);
}

$customerId = (int) $_SESSION['customer']['id'];
$method     = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {

        case 'GET':
            $stmt = getConnection()->prepare(
                'SELECT o.Order_ID, o.Order_Type, o.Order_Date,
                        cw.Cow, cw.Production,
                        w.Worker AS Assigned_Worker
                 FROM Orders o
                 JOIN Cow    cw ON o.Cow_ID    = cw.Cow_ID
                 JOIN Worker w  ON o.Worker_ID = w.Worker_ID
                 WHERE o.CID = ?
                 ORDER BY o.Order_ID DESC'
            );
            $stmt->execute([$customerId]);
            sendSuccess('Orders retrieved.', $stmt->fetchAll());
            break;

        case 'POST':
            // Validate CSRF
            $headers = getallheaders();
            $token   = $headers['X-CSRF-Token'] ?? '';
            if ($token === '' || !validateCsrfToken($token)) {
                sendError('Invalid or missing CSRF token.', 403);
            }

            $data = getRequestBody();
            validateRequired($data, ['Order_Type', 'Order_Date', 'Cow_ID']);

            $orderType = validateString($data['Order_Type'], 'Order_Type', 100);
            $orderDate = validateDate($data['Order_Date'], 'Order_Date');
            $cowId     = validateInteger($data['Cow_ID'], 'Cow_ID');

            // Assign to first available worker (Worker_ID = 1 or lowest)
            $workerStmt = getConnection()->prepare('SELECT Worker_ID FROM Worker ORDER BY Worker_ID LIMIT 1');
            $workerStmt->execute();
            $worker = $workerStmt->fetch();
            if (!$worker) sendError('No workers available to process order.', 503);

            $stmt = getConnection()->prepare(
                'INSERT INTO Orders (CID, Cow_ID, Worker_ID, Order_Type, Order_Date)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$customerId, $cowId, $worker['Worker_ID'], $orderType, $orderDate]);
            $newId = (int) getConnection()->lastInsertId();

            sendSuccess('Order placed successfully!', ['Order_ID' => $newId], 201);
            break;

        default:
            sendError('Method not allowed.', 405);
    }

} catch (PDOException $e) {
    error_log('Customer Orders PDOException: ' . $e->getMessage());
    sendError('A database error occurred. Please try again later.', 500);
}
