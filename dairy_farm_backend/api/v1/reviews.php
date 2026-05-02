<?php
// ============================================================
// api/v1/reviews.php
//
// GET    ?product_id=X   → list reviews for a product
// GET    ?mine=1         → reviews written by the current customer
// POST                   → submit a review (Customer only)
// PUT    ?id=X           → edit own review (Customer only)
// DELETE ?id=X           → delete own review (Customer only)
//
// Note: is_verified_purchase was removed from the table (3NF fix).
// Verified status is computed at query time from CartItems + Cart.
// ============================================================

require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();

// CSRF only needed for state-changing requests
$method = $_SERVER['REQUEST_METHOD'];
if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    requireCsrf();
}

$db     = getConnection();
$userId = (int) $_SESSION['user']['id'];
$role   = $_SESSION['user']['role'] ?? '';

// Helper: check if a customer has purchased a product (verified purchase)
function isVerifiedPurchase(PDO $db, int $customerId, int $productId): bool {
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM CartItems ci
        JOIN Cart c ON ci.cart_id = c.cart_id
        WHERE c.CID = ? AND ci.product_id = ? AND c.status = 'checked_out'
    ");
    $stmt->execute([$customerId, $productId]);
    return (int)$stmt->fetchColumn() > 0;
}

try {
    switch ($method) {

        // ── GET ───────────────────────────────────────────
        case 'GET':
            // My reviews
            if (isset($_GET['mine']) && $role === 'Customer') {
                $stmt = $db->prepare("
                    SELECT r.review_id, r.product_id, r.rating, r.title,
                           r.comment, r.created_at, r.updated_at,
                           p.name AS product_name
                    FROM product_reviews r
                    JOIN Products p ON r.product_id = p.product_id
                    WHERE r.CID = ?
                    ORDER BY r.created_at DESC
                ");
                $stmt->execute([$userId]);
                sendSuccess('Reviews retrieved.', $stmt->fetchAll());
                break;
            }

            // Reviews for a product
            $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
            if (!$productId) sendError('product_id is required.', 400);

            // Fetch reviews (without verified purchase — computed separately below)
            $stmt = $db->prepare("
                SELECT r.review_id, r.rating, r.title, r.comment, r.created_at,
                       r.CID,
                       c.Customer_Name
                FROM product_reviews r
                JOIN Customer c ON r.CID = c.CID
                WHERE r.product_id = ?
                ORDER BY r.created_at DESC
            ");
            $stmt->execute([$productId]);
            $reviews = $stmt->fetchAll();

            // Compute verified purchase status per reviewer (requires Cart + CartItems tables)
            // Wrapped in try/catch so reviews still load even if those tables don't exist yet.
            $verifiedMap = [];
            try {
                $vStmt = $db->prepare("
                    SELECT DISTINCT ci.cart_id, ct.CID
                    FROM CartItems ci
                    JOIN Cart ct ON ci.cart_id = ct.cart_id
                    WHERE ci.product_id = ? AND ct.status = 'checked_out'
                ");
                $vStmt->execute([$productId]);
                foreach ($vStmt->fetchAll() as $row) {
                    $verifiedMap[(int)$row['CID']] = true;
                }
            } catch (PDOException $e) {
                // Cart tables may not exist yet — verified purchase defaults to false
                error_log('Reviews: Cart tables not available: ' . $e->getMessage());
            }

            foreach ($reviews as &$review) {
                $review['is_verified_purchase'] = isset($verifiedMap[(int)$review['CID']]) ? 1 : 0;
                unset($review['CID']); // don't expose customer ID to frontend
            }
            unset($review);

            // Summary stats
            $statStmt = $db->prepare("
                SELECT COUNT(*)          AS total,
                       ROUND(AVG(rating), 1) AS avg_rating,
                       SUM(rating = 5)   AS five,
                       SUM(rating = 4)   AS four,
                       SUM(rating = 3)   AS three,
                       SUM(rating = 2)   AS two,
                       SUM(rating = 1)   AS one
                FROM product_reviews WHERE product_id = ?
            ");
            $statStmt->execute([$productId]);
            $stats = $statStmt->fetch();

            // Current customer's own review (if any)
            $myReview = null;
            if ($role === 'Customer') {
                $myStmt = $db->prepare("
                    SELECT review_id, product_id, rating, title, comment, created_at
                    FROM product_reviews WHERE product_id = ? AND CID = ?
                ");
                $myStmt->execute([$productId, $userId]);
                $myReview = $myStmt->fetch() ?: null;
            }

            sendSuccess('Reviews retrieved.', [
                'stats'     => $stats,
                'reviews'   => $reviews,
                'my_review' => $myReview,
            ]);
            break;

        // ── POST — submit review ──────────────────────────
        case 'POST':
            if ($role !== 'Customer') sendError('Only customers can submit reviews.', 403);

            $data      = getRequestBody();
            $productId = isset($data['product_id']) ? (int)$data['product_id'] : 0;
            $rating    = isset($data['rating'])     ? (int)$data['rating']     : 0;
            $title     = trim($data['title']   ?? '');
            $comment   = trim($data['comment'] ?? '');

            if (!$productId) sendError('product_id is required.', 400);
            if ($rating < 1 || $rating > 5) sendError('Rating must be between 1 and 5.', 400);

            // Verify product exists
            $pStmt = $db->prepare("SELECT product_id FROM Products WHERE product_id = ? AND is_active = 1");
            $pStmt->execute([$productId]);
            if (!$pStmt->fetch()) sendError('Product not found.', 404);

            $stmt = $db->prepare("
                INSERT INTO product_reviews (product_id, CID, rating, title, comment)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $productId, $userId, $rating,
                $title   ?: null,
                $comment ?: null,
            ]);

            sendSuccess('Review submitted. Thank you!', ['review_id' => (int)$db->lastInsertId()], 201);
            break;

        // ── PUT — edit own review ─────────────────────────
        case 'PUT':
            if ($role !== 'Customer') sendError('Only customers can edit reviews.', 403);

            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if (!$id) sendError('Review ID is required.', 400);

            $own = $db->prepare("SELECT review_id FROM product_reviews WHERE review_id = ? AND CID = ?");
            $own->execute([$id, $userId]);
            if (!$own->fetch()) sendError('Review not found or not yours.', 404);

            $data    = getRequestBody();
            $rating  = isset($data['rating']) ? (int)$data['rating'] : 0;
            $title   = trim($data['title']   ?? '');
            $comment = trim($data['comment'] ?? '');

            if ($rating < 1 || $rating > 5) sendError('Rating must be between 1 and 5.', 400);

            $db->prepare("
                UPDATE product_reviews SET rating = ?, title = ?, comment = ? WHERE review_id = ?
            ")->execute([$rating, $title ?: null, $comment ?: null, $id]);

            sendSuccess('Review updated.');
            break;

        // ── DELETE — remove own review ────────────────────
        case 'DELETE':
            if ($role !== 'Customer') sendError('Only customers can delete reviews.', 403);

            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if (!$id) sendError('Review ID is required.', 400);

            $own = $db->prepare("SELECT review_id FROM product_reviews WHERE review_id = ? AND CID = ?");
            $own->execute([$id, $userId]);
            if (!$own->fetch()) sendError('Review not found or not yours.', 404);

            $db->prepare("DELETE FROM product_reviews WHERE review_id = ?")->execute([$id]);
            sendSuccess('Review deleted.');
            break;

        default:
            sendError('Method not allowed.', 405);
    }
} catch (PDOException $e) {
    error_log('Reviews PDOException: ' . $e->getMessage());
    sendError('A database error occurred.', 500);
}
