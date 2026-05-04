<?php
// ============================================================
// api/cart.php  —  Customer shopping cart
//
// GET    /api/cart.php              → get current active cart + items
// POST   /api/cart.php?action=add   → add item (or increase qty)
// POST   /api/cart.php?action=remove → remove one item
// POST   /api/cart.php?action=update → set item quantity
// POST   /api/cart.php?action=clear  → empty the cart
// POST   /api/cart.php?action=checkout → place order, deduct stock
// ============================================================

require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();
requireCsrf();

// Cart is Customer-only
if (($_SESSION['user']['role'] ?? '') !== 'Customer') {
    sendError('Access denied. Cart is for customers only.', 403);
}

$method     = $_SERVER['REQUEST_METHOD'];
$action     = $_GET['action'] ?? '';
$customerId = (int) $_SESSION['user']['id'];
$db         = getConnection();

// ── Helper: get or create the active cart ─────────────────
function getOrCreateCart(PDO $db, int $cid): int {
    $stmt = $db->prepare(
        "SELECT cart_id FROM Cart WHERE CID = ? AND status = 'active' LIMIT 1"
    );
    $stmt->execute([$cid]);
    $row = $stmt->fetch();
    if ($row) return (int) $row['cart_id'];

    $ins = $db->prepare("INSERT INTO Cart (CID, status) VALUES (?, 'active')");
    $ins->execute([$cid]);
    return (int) $db->lastInsertId();
}

// ── Helper: fetch cart with items ─────────────────────────
function fetchCart(PDO $db, int $cartId): array {
    $items = $db->prepare("
        SELECT ci.item_id, ci.product_id, ci.quantity, ci.unit_price,
               p.name, p.stock_qty, p.unit, p.is_active,
               (ci.quantity * ci.unit_price) AS subtotal
        FROM CartItems ci
        JOIN Products p ON ci.product_id = p.product_id
        WHERE ci.cart_id = ?
        ORDER BY ci.item_id ASC
    ");
    $items->execute([$cartId]);
    $rows  = $items->fetchAll();
    $total = array_sum(array_column($rows, 'subtotal'));
    return ['cart_id' => $cartId, 'items' => $rows, 'total' => round($total, 2)];
}

try {
    if ($method === 'GET') {
        $cartId = getOrCreateCart($db, $customerId);
        sendSuccess('Cart retrieved.', fetchCart($db, $cartId));
    }

    elseif ($method === 'POST') {
        $data = getRequestBody();

        // ── Add item ──────────────────────────────────────
        if ($action === 'add') {
            $productId = (int) ($data['product_id'] ?? 0);
            $qty       = max(1, (int) ($data['quantity'] ?? 1));
            if (!$productId) sendError('product_id is required.', 400);

            // Check product exists and has stock
            $prod = $db->prepare("SELECT product_id, price, stock_qty, is_active FROM Products WHERE product_id = ?");
            $prod->execute([$productId]);
            $product = $prod->fetch();

            if (!$product)              sendError('Product not found.', 404);
            if (!$product['is_active']) sendError('This product is no longer available.', 400);
            if ($product['stock_qty'] < 1) sendError('This product is out of stock.', 400);
            if ($qty > $product['stock_qty']) {
                sendError("Only {$product['stock_qty']} unit(s) available.", 400);
            }

            $cartId = getOrCreateCart($db, $customerId);

            // Upsert: if already in cart, increase quantity
            $existing = $db->prepare(
                "SELECT item_id, quantity FROM CartItems WHERE cart_id = ? AND product_id = ?"
            );
            $existing->execute([$cartId, $productId]);
            $row = $existing->fetch();

            if ($row) {
                $newQty = $row['quantity'] + $qty;
                if ($newQty > $product['stock_qty']) {
                    sendError("Cannot add more — only {$product['stock_qty']} unit(s) in stock.", 400);
                }
                $db->prepare("UPDATE CartItems SET quantity = ? WHERE item_id = ?")
                   ->execute([$newQty, $row['item_id']]);
            } else {
                $db->prepare(
                    "INSERT INTO CartItems (cart_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)"
                )->execute([$cartId, $productId, $qty, $product['price']]);
            }

            sendSuccess('Item added to cart.', fetchCart($db, $cartId));
        }

        // ── Remove item ───────────────────────────────────
        elseif ($action === 'remove') {
            $productId = (int) ($data['product_id'] ?? 0);
            if (!$productId) sendError('product_id is required.', 400);

            $cartId = getOrCreateCart($db, $customerId);
            $db->prepare("DELETE FROM CartItems WHERE cart_id = ? AND product_id = ?")
               ->execute([$cartId, $productId]);

            sendSuccess('Item removed.', fetchCart($db, $cartId));
        }

        // ── Update quantity ───────────────────────────────
        elseif ($action === 'update') {
            $productId = (int) ($data['product_id'] ?? 0);
            $qty       = (int) ($data['quantity']   ?? 0);
            if (!$productId) sendError('product_id is required.', 400);

            $cartId = getOrCreateCart($db, $customerId);

            if ($qty <= 0) {
                // qty=0 means remove the item entirely
                $db->prepare("DELETE FROM CartItems WHERE cart_id = ? AND product_id = ?")
                   ->execute([$cartId, $productId]);
                sendSuccess('Item removed.', fetchCart($db, $cartId));
                // sendSuccess calls exit() — code below is unreachable but return is explicit
                return;
            }

            // Check stock before updating
            $prod = $db->prepare("SELECT stock_qty FROM Products WHERE product_id = ?");
            $prod->execute([$productId]);
            $product = $prod->fetch();
            if (!$product) sendError('Product not found.', 404);
            if ($qty > $product['stock_qty']) {
                sendError("Only {$product['stock_qty']} unit(s) available.", 400);
            }

            $db->prepare("UPDATE CartItems SET quantity = ? WHERE cart_id = ? AND product_id = ?")
               ->execute([$qty, $cartId, $productId]);

            sendSuccess('Quantity updated.', fetchCart($db, $cartId));
        }

        // ── Clear cart ────────────────────────────────────
        elseif ($action === 'clear') {
            $cartId = getOrCreateCart($db, $customerId);
            $db->prepare("DELETE FROM CartItems WHERE cart_id = ?")->execute([$cartId]);
            sendSuccess('Cart cleared.', fetchCart($db, $cartId));
        }

        // ── Checkout ──────────────────────────────────────
        elseif ($action === 'checkout') {
            $cartId = getOrCreateCart($db, $customerId);
            $cart   = fetchCart($db, $cartId);

            if (empty($cart['items'])) {
                sendError('Your cart is empty.', 400);
            }

            $db->beginTransaction();
            try {
                $purchased = [];

                // Resolve the "Shop Purchase" order type ID
                $typeRow = $db->prepare("SELECT type_id FROM OrderTypes WHERE type_name = 'Shop Purchase' LIMIT 1");
                $typeRow->execute();
                $shopTypeId = $typeRow->fetchColumn();
                if (!$shopTypeId) {
                    $db->prepare("INSERT INTO OrderTypes (type_name) VALUES ('Shop Purchase')")->execute();
                    $shopTypeId = (int) $db->lastInsertId();
                }

                // Find the first available Admin worker to assign shop orders to
                $workerRow = $db->prepare("SELECT Worker_ID FROM Worker WHERE Worker_Role = 'Admin' AND approval_status = 'approved' ORDER BY Worker_ID ASC LIMIT 1");
                $workerRow->execute();
                $assignedWorker = $workerRow->fetchColumn() ?: 1;

                foreach ($cart['items'] as $item) {
                    // Re-check stock inside transaction
                    $stockRow = $db->prepare(
                        "SELECT stock_qty FROM Products WHERE product_id = ? FOR UPDATE"
                    );
                    $stockRow->execute([$item['product_id']]);
                    $current = $stockRow->fetchColumn();

                    if ($current < $item['quantity']) {
                        $db->rollBack();
                        sendError(
                            "'{$item['name']}' only has {$current} unit(s) left. Please update your cart.",
                            400
                        );
                    }

                    // Deduct stock
                    $db->prepare(
                        "UPDATE Products SET stock_qty = stock_qty - ? WHERE product_id = ?"
                    )->execute([$item['quantity'], $item['product_id']]);

                    // Create an Orders row so admin/staff can see and manage this purchase
                    $db->prepare("
                        INSERT INTO Orders
                            (CID, Cow_ID, Worker_ID, type_id, Order_Date,
                             quantity_liters, unit_price, status, notes)
                        VALUES (?, NULL, ?, ?, CURDATE(), ?, ?, 'pending', ?)
                    ")->execute([
                        $customerId,
                        $assignedWorker,
                        $shopTypeId,
                        (float) $item['quantity'],
                        (float) $item['unit_price'],
                        'Shop purchase: ' . $item['name'],
                    ]);

                    $purchased[] = [
                        'product'  => $item['name'],
                        'qty'      => $item['quantity'],
                        'subtotal' => $item['subtotal'],
                    ];
                }

                // Mark cart as checked out
                $db->prepare("UPDATE Cart SET status = 'checked_out' WHERE cart_id = ?")
                   ->execute([$cartId]);

                $db->commit();

                sendSuccess('Order placed successfully! Thank you for your purchase.', [
                    'items'   => $purchased,
                    'total'   => $cart['total'],
                    'cart_id' => $cartId,
                ], 201);

            } catch (PDOException $e) {
                $db->rollBack();
                throw $e;
            }
        }

        else {
            sendError('Invalid action.', 400);
        }
    }

    else {
        sendError('Method not allowed.', 405);
    }

} catch (PDOException $e) {
    error_log('Cart error: ' . $e->getMessage());
    sendError('A database error occurred.', 500);
}
