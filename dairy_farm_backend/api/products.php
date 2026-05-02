<?php
// ============================================================
// api/products.php
//
// GET    /api/products.php          → list active products (public to all logged-in)
// GET    /api/products.php?id=1     → get one product
// GET    /api/products.php?all=1    → list ALL products incl. inactive (Admin only)
// POST   /api/products.php          → create product (Admin only)
// PUT    /api/products.php?id=1     → update product (Admin only)
// PATCH  /api/products.php?id=1     → toggle active / adjust stock (Admin only)
// DELETE /api/products.php?id=1     → delete product (Admin only)
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
requireAuth();
requireCsrf();

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id'])  ? (int) $_GET['id']  : null;
$all    = isset($_GET['all']) && $_GET['all'] === '1';
$db     = getConnection();

// Write operations are Admin-only
if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    requireRole(['Admin']);
}
// Listing all (including inactive) is Admin-only
if ($method === 'GET' && $all) {
    requireRole(['Admin']);
}

try {
    switch ($method) {

        // ── List / Get ────────────────────────────────────
        case 'GET':
            if ($id) {
                $stmt = $db->prepare("SELECT * FROM Products WHERE product_id = ?");
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                $row
                    ? sendSuccess('Product found.', $row)
                    : sendError('Product not found.', 404);
            } else {
                $where = $all ? '' : 'WHERE is_active = 1';
                // Optional low-stock filter: ?low_stock=1 returns items with stock_qty <= 5
                $lowStock = isset($_GET['low_stock']) && $_GET['low_stock'] === '1';
                if ($lowStock) {
                    requireRole(['Admin']);
                    $where = 'WHERE is_active = 1 AND stock_qty <= 5';
                }
                $stmt = $db->query("SELECT * FROM Products $where ORDER BY stock_qty ASC, product_id ASC");
                sendSuccess('Products retrieved.', $stmt->fetchAll());
            }
            break;

        // ── Create ────────────────────────────────────────
        case 'POST':
            $data = getRequestBody();
            validateRequired($data, ['name', 'price', 'stock_qty']);

            $stmt = $db->prepare("
                INSERT INTO Products (name, description, price, stock_qty, unit, image_url, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                validateString($data['name'],        'name',        150),
                $data['description'] ?? null,
                (float)  $data['price'],
                (int)    $data['stock_qty'],
                validateString($data['unit'] ?? 'pcs', 'unit', 30),
                $data['image_url'] ?? null,
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
            ]);
            sendSuccess('Product created.', ['product_id' => $db->lastInsertId()], 201);
            break;

        // ── Full update ───────────────────────────────────
        case 'PUT':
            if (!$id) sendError('Product ID required.');
            $data = getRequestBody();
            validateRequired($data, ['name', 'price', 'stock_qty']);

            $stmt = $db->prepare("
                UPDATE Products
                SET name = ?, description = ?, price = ?, stock_qty = ?,
                    unit = ?, image_url = ?, is_active = ?
                WHERE product_id = ?
            ");
            $stmt->execute([
                validateString($data['name'],        'name',        150),
                $data['description'] ?? null,
                (float)  $data['price'],
                (int)    $data['stock_qty'],
                validateString($data['unit'] ?? 'pcs', 'unit', 30),
                $data['image_url'] ?? null,
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                $id,
            ]);
            $stmt->rowCount()
                ? sendSuccess('Product updated.')
                : sendError('Product not found.', 404);
            break;

        // ── Partial update (toggle active, adjust stock) ──
        case 'PATCH':
            if (!$id) sendError('Product ID required.');
            $data   = getRequestBody();
            $fields = [];
            $params = [];

            foreach (['is_active', 'stock_qty', 'price', 'name', 'description'] as $f) {
                if (array_key_exists($f, $data)) {
                    $fields[] = "$f = ?";
                    $params[] = $data[$f];
                }
            }
            if (empty($fields)) sendError('No fields to update.', 400);

            $params[] = $id;
            $stmt = $db->prepare("UPDATE Products SET " . implode(', ', $fields) . " WHERE product_id = ?");
            $stmt->execute($params);
            $stmt->rowCount()
                ? sendSuccess('Product updated.')
                : sendError('Product not found.', 404);
            break;

        // ── Delete ────────────────────────────────────────
        case 'DELETE':
            if (!$id) sendError('Product ID required.');
            $stmt = $db->prepare("DELETE FROM Products WHERE product_id = ?");
            $stmt->execute([$id]);
            $stmt->rowCount()
                ? sendSuccess('Product deleted.')
                : sendError('Product not found.', 404);
            break;

        default:
            sendError('Method not allowed.', 405);
    }
} catch (PDOException $e) {
    error_log('Products error: ' . $e->getMessage());
    sendError('A database error occurred.', 500);
}
