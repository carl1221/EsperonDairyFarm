<?php
// ============================================================
// api/customer_portal.php  —  Customer self-service API
//
// GET  ?action=orders         → farm orders for this customer
// GET  ?action=profile        → customer profile
// GET  ?action=cart_orders    → shop purchase history (checked-out carts)
// GET  ?action=featured       → top 4 active products for dashboard
// POST ?action=update_profile → update name, address, contact
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
requireAuth();

if (($_SESSION['user']['role'] ?? '') !== 'Customer') {
    sendError('Access denied.', 403);
}

$method     = $_SERVER['REQUEST_METHOD'];
$action     = $_GET['action'] ?? 'orders';
$customerId = (int) $_SESSION['user']['id'];
$db         = getConnection();

try {

    // ── GET actions ───────────────────────────────────────
    if ($method === 'GET') {

        switch ($action) {

            // Farm orders (milk, cheese, etc.)
            case 'orders':
                $stmt = $db->prepare("
                    SELECT o.Order_ID, o.Order_Type, o.Order_Date,
                           o.quantity_liters, o.unit_price, o.total_price,
                           o.status AS Order_Status, o.notes AS Order_Notes,
                           cw.Cow, cw.Breed, w.Worker AS Worker_Name
                    FROM Orders o
                    JOIN Cow    cw ON o.Cow_ID    = cw.Cow_ID
                    JOIN Worker w  ON o.Worker_ID  = w.Worker_ID
                    WHERE o.CID = ?
                    ORDER BY o.Order_Date DESC, o.Order_ID DESC
                ");
                $stmt->execute([$customerId]);
                sendSuccess('Orders retrieved.', $stmt->fetchAll());
                break;

            // Customer profile
            case 'profile':
                $stmt = $db->prepare("
                    SELECT c.CID, c.Customer_Name, c.Contact_Num, a.Address, a.Address_ID
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

            // Shop purchase history (checked-out carts)
            case 'cart_orders':
                $stmt = $db->prepare("
                    SELECT ca.cart_id, ca.updated_at AS purchased_at,
                           ci.item_id, ci.quantity, ci.unit_price,
                           (ci.quantity * ci.unit_price) AS subtotal,
                           p.name AS product_name, p.unit
                    FROM Cart ca
                    JOIN CartItems ci ON ca.cart_id   = ci.cart_id
                    JOIN Products  p  ON ci.product_id = p.product_id
                    WHERE ca.CID = ? AND ca.status = 'checked_out'
                    ORDER BY ca.updated_at DESC, ca.cart_id DESC
                ");
                $stmt->execute([$customerId]);
                $rows = $stmt->fetchAll();

                // Group by cart_id
                $grouped = [];
                foreach ($rows as $row) {
                    $cid = $row['cart_id'];
                    if (!isset($grouped[$cid])) {
                        $grouped[$cid] = [
                            'cart_id'      => $cid,
                            'purchased_at' => $row['purchased_at'],
                            'items'        => [],
                            'total'        => 0,
                        ];
                    }
                    $grouped[$cid]['items'][] = [
                        'product_name' => $row['product_name'],
                        'quantity'     => $row['quantity'],
                        'unit'         => $row['unit'],
                        'unit_price'   => $row['unit_price'],
                        'subtotal'     => $row['subtotal'],
                    ];
                    $grouped[$cid]['total'] += (float) $row['subtotal'];
                }
                sendSuccess('Cart orders retrieved.', array_values($grouped));
                break;

            // Featured products for dashboard quick-shop
            case 'featured':
                $stmt = $db->query("
                    SELECT product_id, name, description, price, stock_qty, unit
                    FROM Products
                    WHERE is_active = 1 AND stock_qty > 0
                    ORDER BY product_id ASC
                    LIMIT 4
                ");
                sendSuccess('Featured products retrieved.', $stmt->fetchAll());
                break;

            default:
                sendError('Invalid action.', 400);
        }
    }

    // ── POST actions ──────────────────────────────────────
    elseif ($method === 'POST') {
        requireCsrf();

        if ($action === 'update_profile') {
            $data = getRequestBody();

            $name    = trim($data['name']    ?? '');
            $address = trim($data['address'] ?? '');
            $contact = trim($data['contact'] ?? '');

            if ($name    === '') sendError('Name is required.', 400);
            if ($address === '') sendError('Address is required.', 400);
            if ($contact === '') sendError('Contact number is required.', 400);
            if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $contact)) {
                sendError('Please enter a valid contact number.', 400);
            }

            // Get the Address_ID for this customer first, then update it directly
            $addrRow = $db->prepare("SELECT Address_ID FROM Customer WHERE CID = ?");
            $addrRow->execute([$customerId]);
            $addrId  = $addrRow->fetchColumn();

            if (!$addrId) sendError('Customer not found.', 404);

            $db->prepare("UPDATE Address SET Address = ? WHERE Address_ID = ?")
               ->execute([$address, $addrId]);

            $db->prepare("UPDATE Customer SET Customer_Name = ?, Contact_Num = ? WHERE CID = ?")
               ->execute([$name, $contact, $customerId]);

            // Refresh session
            $_SESSION['user']['name']    = $name;
            $_SESSION['user']['address'] = $address;
            $_SESSION['user']['contact'] = $contact;

            sendSuccess('Profile updated.', [
                'name'    => $name,
                'address' => $address,
                'contact' => $contact,
            ]);
        } else {
            sendError('Invalid action.', 400);
        }
    }

    else {
        sendError('Method not allowed.', 405);
    }

} catch (PDOException $e) {
    error_log('Customer portal error: ' . $e->getMessage());
    sendError('A database error occurred.', 500);
}
