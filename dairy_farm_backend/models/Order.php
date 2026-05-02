<?php
// ============================================================
// models/Order.php
// 3NF: Order_Type (free-text) replaced by type_id FK → OrderTypes.
// Order_Type name is derived at query time via vw_order_details.
// ============================================================

require_once __DIR__ . '/../config/database.php';

class Order {
    private PDO $db;

    public function __construct() {
        $this->db = getConnection();
    }

    /**
     * Return all orders, optionally filtered by search term and/or worker.
     * @param string|null $search    Searches Customer_Name, Order_Type, Cow, Worker_Name
     * @param int|null    $workerId  Filter to a specific worker (for Staff "my orders")
     */
    public function getAll(?string $search = null, ?int $workerId = null): array {
        $where  = [];
        $params = [];

        if ($search !== null && $search !== '') {
            $like    = '%' . $search . '%';
            $where[] = "(Customer_Name LIKE ? OR Order_Type LIKE ? OR Cow LIKE ? OR Worker_Name LIKE ?)";
            $params  = array_merge($params, [$like, $like, $like, $like]);
        }
        if ($workerId !== null) {
            $where[]  = "Worker_ID = ?";
            $params[] = $workerId;
        }

        $sql = "SELECT * FROM vw_order_details";
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
     * Resolve an order type name to its type_id.
     * If the name doesn't exist yet, insert it (auto-extend the lookup table).
     */
    private function resolveTypeId(string $typeName): int {
        $stmt = $this->db->prepare("SELECT type_id FROM OrderTypes WHERE type_name = ?");
        $stmt->execute([$typeName]);
        $row = $stmt->fetch();
        if ($row) return (int) $row['type_id'];

        // Auto-insert unknown types so existing data isn't rejected
        $ins = $this->db->prepare("INSERT INTO OrderTypes (type_name) VALUES (?)");
        $ins->execute([$typeName]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Create a new order.
     * Accepts either type_id (int) or Order_Type (string — resolved to type_id).
     * Required: CID, Cow_ID, Worker_ID, Order_Date, quantity_liters, unit_price
     * Optional: status, notes
     */
    public function create(array $data): int {
        $typeId = isset($data['type_id'])
            ? (int) $data['type_id']
            : $this->resolveTypeId($data['Order_Type'] ?? 'Custom Order');

        $stmt = $this->db->prepare("
            INSERT INTO Orders
                (CID, Cow_ID, Worker_ID, type_id, Order_Date,
                 quantity_liters, unit_price, status, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            (int)   $data['CID'],
            (int)   $data['Cow_ID'],
            (int)   $data['Worker_ID'],
                    $typeId,
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
        $typeId = isset($data['type_id'])
            ? (int) $data['type_id']
            : $this->resolveTypeId($data['Order_Type'] ?? 'Custom Order');

        $stmt = $this->db->prepare("
            UPDATE Orders
            SET CID             = ?,
                Cow_ID          = ?,
                Worker_ID       = ?,
                type_id         = ?,
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
                    $typeId,
                    $data['Order_Date'],
            (float) ($data['quantity_liters'] ?? 0),
            (float) ($data['unit_price']      ?? 0),
                    $data['status']            ?? 'pending',
                    $data['notes']             ?? null,
            $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    /** Update only the status of an order. */
    public function updateStatus(int $id, string $status): bool {
        $allowed = ['pending', 'confirmed', 'delivered', 'cancelled'];
        if (!in_array($status, $allowed, true)) return false;
        $stmt = $this->db->prepare("UPDATE Orders SET status = ? WHERE Order_ID = ?");
        $stmt->execute([$status, $id]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM Orders WHERE Order_ID = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /** Return all available order types for dropdowns. */
    public function getTypes(): array {
        return $this->db->query("SELECT type_id, type_name FROM OrderTypes ORDER BY type_name")->fetchAll();
    }
}
