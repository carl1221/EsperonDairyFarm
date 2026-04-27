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
            SELECT c.CID, c.Customer_Name, a.Address_ID, a.Address, a.Contact_Num
            FROM Customer c
            JOIN Address a ON c.Address_ID = a.Address_ID
            ORDER BY c.CID
        ");
        return $stmt->fetchAll();
    }

    public function getById(int $id): array|false {
        $stmt = $this->db->prepare("
            SELECT c.CID, c.Customer_Name, a.Address_ID, a.Address, a.Contact_Num
            FROM Customer c
            JOIN Address a ON c.Address_ID = a.Address_ID
            WHERE c.CID = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(array $data): int {
        // First create or reuse Address
        $addrStmt = $this->db->prepare("
            INSERT INTO Address (Address_ID, Address, Contact_Num)
            VALUES (?, ?, ?)
        ");
        $addrStmt->execute([$data['Address_ID'], $data['Address'], $data['Contact_Num']]);

        $stmt = $this->db->prepare("
            INSERT INTO Customer (CID, Customer_Name, Address_ID)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$data['CID'], $data['Customer_Name'], $data['Address_ID']]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        // Update Address first
        $addrStmt = $this->db->prepare("
            UPDATE Address SET Address = ?, Contact_Num = ?
            WHERE Address_ID = (SELECT Address_ID FROM Customer WHERE CID = ?)
        ");
        $addrStmt->execute([$data['Address'], $data['Contact_Num'], $id]);

        $stmt = $this->db->prepare("
            UPDATE Customer SET Customer_Name = ? WHERE CID = ?
        ");
        $stmt->execute([$data['Customer_Name'], $id]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM Customer WHERE CID = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
