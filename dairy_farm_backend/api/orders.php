<?php
// ============================================================
// api/orders.php
// Endpoint: /api/orders.php
//
// GET    /api/orders.php              → list all orders
// GET    /api/orders.php?id=1         → get one order
// GET    /api/orders.php?customer=1   → get orders by customer ID
// POST   /api/orders.php              → create order
// PUT    /api/orders.php?id=1         → update order
// PATCH  /api/orders.php?id=1         → update order status only
// DELETE /api/orders.php?id=1         → delete order
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/Order.php';

requireAuth();
requireCsrf();

// Role-based access:
// - GET:    both Admin and Staff
// - POST:   both Admin and Staff (Staff auto-assigned as the worker)
// - PUT:    Admin only
// - PATCH:  both Admin and Staff (status updates)
// - DELETE: Admin only
$method = $_SERVER['REQUEST_METHOD'];
if (in_array($method, ['PUT', 'DELETE'], true)) {
    requireRole(['Admin']);
}

$order = new Order();
$id    = isset($_GET['id'])       ? (int) $_GET['id']       : null;
$cid   = isset($_GET['customer']) ? (int) $_GET['customer'] : null;

try {
    switch ($method) {

        case 'GET':
            if ($id) {
                $row = $order->getById($id);
                $row
                    ? sendSuccess('Order found.', $row)
                    : sendError('Order not found.', 404);
            } elseif ($cid) {
                sendSuccess('Orders retrieved.', $order->getByCustomer($cid));
            } else {
                // Optional server-side search and worker filter
                $search   = isset($_GET['search']) ? trim($_GET['search']) : null;
                $myOrders = isset($_GET['mine']) && $_GET['mine'] === '1';
                $workerFilter = $myOrders ? (int) $_SESSION['user']['id'] : null;
                sendSuccess('Orders retrieved.', $order->getAll($search, $workerFilter));
            }
            break;

        case 'POST':
            $data = getRequestBody();
            validateRequired($data, [
                'CID', 'Cow_ID',
                'Order_Type', 'Order_Date',
                'quantity_liters', 'unit_price',
            ]);

            // Staff are always assigned as the worker on their own orders.
            // Admin can specify any Worker_ID.
            $isAdmin  = ($_SESSION['user']['role'] ?? '') === 'Admin';
            $workerId = $isAdmin
                ? validateInteger($data['Worker_ID'] ?? 0, 'Worker_ID')
                : (int) $_SESSION['user']['id'];

            $validatedData = [
                'CID'             => validateInteger($data['CID'],             'CID'),
                'Cow_ID'          => validateInteger($data['Cow_ID'],          'Cow_ID'),
                'Worker_ID'       => $workerId,
                'Order_Type'      => validateString($data['Order_Type'],       'Order_Type', 100),
                'Order_Date'      => validateDate($data['Order_Date'],         'Order_Date'),
                'quantity_liters' => (float) $data['quantity_liters'],
                'unit_price'      => (float) $data['unit_price'],
                'status'          => $data['status'] ?? 'pending',
                'notes'           => $data['notes']  ?? null,
            ];

            $newId = $order->create($validatedData);
            sendSuccess('Order created.', ['Order_ID' => $newId], 201);
            break;

        case 'PUT':
            if (!$id) sendError('Order ID is required for update.');
            $data = getRequestBody();
            validateRequired($data, [
                'CID', 'Cow_ID', 'Worker_ID',
                'Order_Type', 'Order_Date',
                'quantity_liters', 'unit_price',
            ]);

            $validatedData = [
                'CID'             => validateInteger($data['CID'],             'CID'),
                'Cow_ID'          => validateInteger($data['Cow_ID'],          'Cow_ID'),
                'Worker_ID'       => validateInteger($data['Worker_ID'],       'Worker_ID'),
                'Order_Type'      => validateString($data['Order_Type'],       'Order_Type', 100),
                'Order_Date'      => validateDate($data['Order_Date'],         'Order_Date'),
                'quantity_liters' => (float) $data['quantity_liters'],
                'unit_price'      => (float) $data['unit_price'],
                'status'          => $data['status'] ?? 'pending',
                'notes'           => $data['notes']  ?? null,
            ];

            $order->update($id, $validatedData)
                ? sendSuccess('Order updated.')
                : sendError('Order not found or no changes made.', 404);
            break;

        case 'PATCH':
            // Staff and Admin can update status (e.g. mark as delivered)
            if (!$id) sendError('Order ID is required.');
            $data   = getRequestBody();
            $status = $data['status'] ?? '';
            if (empty($status)) sendError('status field is required for PATCH.');

            $order->updateStatus($id, $status)
                ? sendSuccess('Order status updated.')
                : sendError('Invalid status value or order not found.', 400);
            break;

        case 'DELETE':
            requireRole(['Admin']);
            if (!$id) sendError('Order ID is required for delete.');
            $order->delete($id)
                ? sendSuccess('Order deleted.')
                : sendError('Order not found.', 404);
            break;

        default:
            sendError('Method not allowed.', 405);
    }
} catch (PDOException $e) {
    sendError('A database error occurred. Please try again later.', 500);
}
