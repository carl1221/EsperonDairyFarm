<?php
// ============================================================
// models/Cow.php
// ============================================================

require_once __DIR__ . '/../config/database.php';

class Cow {
    private PDO $db;

    public function __construct() {
        $this->db = getConnection();
    }

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM Cow ORDER BY Cow_ID");
        return $stmt->fetchAll();
    }

    public function getById(int $id): array|false {
        $stmt = $this->db->prepare("SELECT * FROM Cow WHERE Cow_ID = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(array $data): bool {
        $stmt = $this->db->prepare("
            INSERT INTO Cow (Cow_ID, Cow, Production) VALUES (?, ?, ?)
        ");
        return $stmt->execute([$data['Cow_ID'], $data['Cow'], $data['Production']]);
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare("
            UPDATE Cow SET Cow = ?, Production = ? WHERE Cow_ID = ?
        ");
        $stmt->execute([$data['Cow'], $data['Production'], $id]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM Cow WHERE Cow_ID = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
