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
        $stmt = $this->db->query("
            SELECT Cow_ID, Cow,
                   CONCAT(Production_Liters, 'L') AS Production,
                   Production_Liters
            FROM Cow ORDER BY Cow_ID
        ");
        return $stmt->fetchAll();
    }

    public function getById(int $id): array|false {
        $stmt = $this->db->prepare("
            SELECT Cow_ID, Cow,
                   CONCAT(Production_Liters, 'L') AS Production,
                   Production_Liters
            FROM Cow WHERE Cow_ID = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(array $data): bool {
        // Accept either Production (e.g. '10L') or Production_Liters (numeric)
        $liters = isset($data['Production_Liters'])
            ? (float)$data['Production_Liters']
            : (float)preg_replace('/[^0-9.]/', '', $data['Production'] ?? '0');
        $stmt = $this->db->prepare("
            INSERT INTO Cow (Cow_ID, Cow, Production_Liters) VALUES (?, ?, ?)
        ");
        return $stmt->execute([$data['Cow_ID'], $data['Cow'], $liters]);
    }

    public function update(int $id, array $data): bool {
        $liters = isset($data['Production_Liters'])
            ? (float)$data['Production_Liters']
            : (float)preg_replace('/[^0-9.]/', '', $data['Production'] ?? '0');
        $stmt = $this->db->prepare("
            UPDATE Cow SET Cow = ?, Production_Liters = ? WHERE Cow_ID = ?
        ");
        $stmt->execute([$data['Cow'], $liters, $id]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM Cow WHERE Cow_ID = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
