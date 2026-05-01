<?php
// ============================================================
// api/customer_portal.php
// Customer-facing read-only API.
//
// GET ?action=orders   → orders belonging to this customer
// GET ?action=profile  → customer's own profile (name, address, contact)
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
requireAuth();

// Only Customers can use this endpoint
if (($_SESSION['user']['role'] ?? '') !== 'Customer') {
    sendError('Access denied.', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed.', 405);
}

$action     = $_GET['action'] ?? 'orders';
$customerId = (int) $_SESSION['user']['id'];
$db         = getConnection();

try {
    switch ($action) {

        // ── Customer's own orders ─────────────────────────
        case 'orders':
            $stmt = $db->prepare("
                SELECT
                    o.Order_ID,
                    o.Order_Type,
                    o.Order_Date,
                    o.quantity_liters,
                    o.unit_price,
                    o.total_price,
                    o.status        AS Order_Status,
                    o.notes         AS Order_Notes,
                    cw.Cow,
                    cw.Breed,
                    w.Worker        AS Worker_Name
                FROM Orders o
                JOIN Cow    cw ON o.Cow_ID    = cw.Cow_ID
                JOIN Worker w  ON o.Worker_ID  = w.Worker_ID
                WHERE o.CID = ?
                ORDER BY o.Order_Date DESC, o.Order_ID DESC
            ");
            $stmt->execute([$customerId]);
            sendSuccess('Orders retrieved.', $stmt->fetchAll());
            break;

        // ── Customer's own profile ────────────────────────
        case 'profile':
            $stmt = $db->prepare("
                SELECT c.CID, c.Customer_Name, c.Contact_Num, a.Address
                FROM Customer c
                JOIN Address a ON c.Address_ID = a.Address_ID
                WHERE c.CID = ?
            ");
            $stmt->execute([$customerId]);
            $row = $stmt->fetch();
            $row
                ? sendSuccess('Profile retrieved.', $row)
                : sendError('Customer not found.', 404);
            break;

        default:
            sendError('Invalid action.', 400);
    }

} catch (PDOException $e) {
    error_log('Customer portal error: ' . $e->getMessage());
    sendError('A database error occurred.', 500);
}
