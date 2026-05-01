<?php
// ============================================================
// models/Customer.php
// ============================================================

require_once __DIR__ . '/../config/database.php';

class Customer {
    private PDO $db;

    public function __construct() {
        $this->db = getConnection();
    }

    public function getAll(): array {
        $stmt = $this->db->query("
            SELECT c.CID, c.Customer_Name, c.Address_ID, a.Address, c.Contact_Num
            FROM Customer c
            JOIN Address a ON c.Address_ID = a.Address_ID
            ORDER BY c.CID
        ");
        return $stmt->fetchAll();
    }

    public function getById(int $id): array|false {
        $stmt = $this->db->prepare("
            SELECT c.CID, c.Customer_Name, c.Address_ID, a.Address, c.Contact_Num
            FROM Customer c
            JOIN Address a ON c.Address_ID = a.Address_ID
            WHERE c.CID = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(array $data): int {
        // Create or reuse Address (no Contact_Num on Address anymore)
        $addrStmt = $this->db->prepare("
            INSERT INTO Address (Address_ID, Address)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE Address = VALUES(Address)
        ");
        $addrStmt->execute([$data['Address_ID'], $data['Address']]);

        $stmt = $this->db->prepare("
            INSERT INTO Customer (CID, Customer_Name, Address_ID, Contact_Num)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$data['CID'], $data['Customer_Name'], $data['Address_ID'], $data['Contact_Num']]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        // Update Address text if provided
        if (!empty($data['Address'])) {
            $addrStmt = $this->db->prepare("
                UPDATE Address SET Address = ?
                WHERE Address_ID = (SELECT Address_ID FROM Customer WHERE CID = ?)
            ");
            $addrStmt->execute([$data['Address'], $id]);
        }

        $stmt = $this->db->prepare("
            UPDATE Customer SET Customer_Name = ?, Contact_Num = ? WHERE CID = ?
        ");
        $stmt->execute([$data['Customer_Name'], $data['Contact_Num'], $id]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM Customer WHERE CID = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
