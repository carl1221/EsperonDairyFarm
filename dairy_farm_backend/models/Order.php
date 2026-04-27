<?php
// ============================================================
// models/Order.php
// ============================================================

require_once __DIR__ . '/../config/database.php';

class Order {
    private PDO $db;

    public function __construct() {
        $this->db = getConnection();
    }

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM vw_order_details ORDER BY Order_ID");
        return $stmt->fetchAll();
    }

    public function getById(int $id): array|false {
        $stmt = $this->db->prepare("SELECT * FROM vw_order_details WHERE Order_ID = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getByCustomer(int $cid): array {
        $stmt = $this->db->prepare("SELECT * FROM vw_order_details WHERE Customer_Name = (
            SELECT Customer_Name FROM Customer WHERE CID = ?
        )");
        $stmt->execute([$cid]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO Orders (CID, Cow_ID, Worker_ID, Order_Type, Order_Date)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['CID'],
            $data['Cow_ID'],
            $data['Worker_ID'],
            $data['Order_Type'],
            $data['Order_Date'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare("
            UPDATE Orders
            SET CID = ?, Cow_ID = ?, Worker_ID = ?, Order_Type = ?, Order_Date = ?
            WHERE Order_ID = ?
        ");
        $stmt->execute([
            $data['CID'],
            $data['Cow_ID'],
            $data['Worker_ID'],
            $data['Order_Type'],
            $data['Order_Date'],
            $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM Orders WHERE Order_ID = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
