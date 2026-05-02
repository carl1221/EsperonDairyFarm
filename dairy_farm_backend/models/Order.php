<?php
// ============================================================
// models/Order.php
// Orders now include quantity_liters, unit_price, total_price
// (generated column), status, and notes.
// ============================================================

require_once __DIR__ . '/../config/database.php';

class Order {
    private PDO $db;

    public function __construct() {
        $this->db = getConnection();
    }

    /**
     * Return all orders, optionally filtered by search term and/or worker.
     * @param string|null $search  Searches Customer_Name, Order_Type, Cow, Worker_Name
     * @param int|null    $workerId  Filter to a specific worker (for Staff "my orders")
     */
    public function getAll(?string $search = null, ?int $workerId = null): array {
        $where  = [];
        $params = [];

        if ($search !== null && $search !== '') {
            $like     = '%' . $search . '%';
            $where[]  = "(Customer_Name LIKE ? OR Order_Type LIKE ? OR Cow LIKE ? OR Worker_Name LIKE ?)";
            $params   = array_merge($params, [$like, $like, $like, $like]);
        }
        if ($workerId !== null) {
            $where[]  = "Worker_ID = ?";
            $params[] = $workerId;
        }

        $sql  = "SELECT * FROM vw_order_details";
        if ($where) $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY Order_ID DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById(int $id): array|false {
        $stmt = $this->db->prepare("SELECT * FROM vw_order_details WHERE Order_ID = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getByCustomer(int $cid): array {
        $stmt = $this->db->prepare("SELECT * FROM vw_order_details WHERE CID = ?");
        $stmt->execute([$cid]);
        return $stmt->fetchAll();
    }

    /**
     * Create a new order.
     * Required keys: CID, Cow_ID, Worker_ID, Order_Type, Order_Date,
     *                quantity_liters, unit_price
     * Optional keys: status, notes
     *
     * total_price is a GENERATED column — do NOT insert it.
     */
    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO Orders
                (CID, Cow_ID, Worker_ID, Order_Type, Order_Date,
                 quantity_liters, unit_price, status, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            (int)   $data['CID'],
            (int)   $data['Cow_ID'],
            (int)   $data['Worker_ID'],
                    $data['Order_Type'],
                    $data['Order_Date'],
            (float) ($data['quantity_liters'] ?? 0),
            (float) ($data['unit_price']      ?? 0),
                    $data['status']            ?? 'pending',
                    $data['notes']             ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update an existing order.
     * Accepts the same keys as create().
     */
    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare("
            UPDATE Orders
            SET CID             = ?,
                Cow_ID          = ?,
                Worker_ID       = ?,
                Order_Type      = ?,
                Order_Date      = ?,
                quantity_liters = ?,
                unit_price      = ?,
                status          = ?,
                notes           = ?
            WHERE Order_ID = ?
        ");
        $stmt->execute([
            (int)   $data['CID'],
            (int)   $data['Cow_ID'],
            (int)   $data['Worker_ID'],
                    $data['Order_Type'],
                    $data['Order_Date'],
            (float) ($data['quantity_liters'] ?? 0),
            (float) ($data['unit_price']      ?? 0),
                    $data['status']            ?? 'pending',
                    $data['notes']             ?? null,
            $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    /** Update only the status of an order (e.g. confirm, deliver, cancel). */
    public function updateStatus(int $id, string $status): bool {
        $allowed = ['pending', 'confirmed', 'delivered', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE Orders SET status = ? WHERE Order_ID = ?");
        $stmt->execute([$status, $id]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM Orders WHERE Order_ID = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
