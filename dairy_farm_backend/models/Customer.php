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
        // ── Address handling ──────────────────────────────
        // If Address_ID is provided (Admin flow), upsert that specific row.
        // If not provided (Staff flow), insert a new Address row and get its ID.
        if (!empty($data['Address_ID'])) {
            $addrStmt = $this->db->prepare("
                INSERT INTO Address (Address_ID, Address)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE Address = VALUES(Address)
            ");
            $addrStmt->execute([$data['Address_ID'], $data['Address']]);
            $addressId = (int) $data['Address_ID'];
        } else {
            $addrStmt = $this->db->prepare("
                INSERT INTO Address (Address) VALUES (?)
            ");
            $addrStmt->execute([$data['Address']]);
            $addressId = (int) $this->db->lastInsertId();
        }

        // ── Customer insert — always let AUTO_INCREMENT assign CID ──
        $stmt = $this->db->prepare("
            INSERT INTO Customer (Customer_Name, Address_ID, Contact_Num)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$data['Customer_Name'], $addressId, $data['Contact_Num']]);

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
