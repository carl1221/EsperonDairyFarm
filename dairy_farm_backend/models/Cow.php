<?php
// ============================================================
// models/Cow.php
// Extended with Breed, Date_Of_Birth, Health_Status,
// is_active, and notes.
// ============================================================

require_once __DIR__ . '/../config/database.php';

class Cow {
    private PDO $db;

    public function __construct() {
        $this->db = getConnection();
    }

    public function getAll(bool $activeOnly = false): array {
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        $stmt  = $this->db->query("
            SELECT Cow_ID, Cow, Breed, Date_Of_Birth,
                   Production_Liters,
                   CONCAT(Production_Liters, 'L') AS Production,
                   Health_Status, is_active, notes
            FROM Cow $where
            ORDER BY Cow_ID
        ");
        return $stmt->fetchAll();
    }

    public function getById(int $id): array|false {
        $stmt = $this->db->prepare("
            SELECT Cow_ID, Cow, Breed, Date_Of_Birth,
                   Production_Liters,
                   CONCAT(Production_Liters, 'L') AS Production,
                   Health_Status, is_active, notes
            FROM Cow WHERE Cow_ID = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Create a new cow.
     * Required: Cow
     * Optional: Breed, Date_Of_Birth, Production_Liters (or Production),
     *           Health_Status, is_active, notes
     */
    public function create(array $data): int {
        $liters = $this->parseProduction($data);
        $stmt   = $this->db->prepare("
            INSERT INTO Cow
                (Cow, Breed, Date_Of_Birth, Production_Liters,
                 Health_Status, is_active, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['Cow'],
            $data['Breed']         ?? null,
            $data['Date_Of_Birth'] ?? null,
            $liters,
            $data['Health_Status'] ?? 'Healthy',
            isset($data['is_active']) ? (int)$data['is_active'] : 1,
            $data['notes']         ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update a cow's details.
     */
    public function update(int $id, array $data): bool {
        $liters = $this->parseProduction($data);
        $stmt   = $this->db->prepare("
            UPDATE Cow
            SET Cow            = ?,
                Breed          = ?,
                Date_Of_Birth  = ?,
                Production_Liters = ?,
                Health_Status  = ?,
                is_active      = ?,
                notes          = ?
            WHERE Cow_ID = ?
        ");
        $stmt->execute([
            $data['Cow'],
            $data['Breed']         ?? null,
            $data['Date_Of_Birth'] ?? null,
            $liters,
            $data['Health_Status'] ?? 'Healthy',
            isset($data['is_active']) ? (int)$data['is_active'] : 1,
            $data['notes']         ?? null,
            $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    /** Soft-delete: mark cow as inactive instead of removing the row. */
    public function deactivate(int $id): bool {
        $stmt = $this->db->prepare("UPDATE Cow SET is_active = 0 WHERE Cow_ID = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /** Hard-delete — use only when no orders reference this cow. */
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM Cow WHERE Cow_ID = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /** Accept either Production_Liters (numeric) or Production (e.g. '10L'). */
    private function parseProduction(array $data): float {
        if (isset($data['Production_Liters'])) {
            return (float) $data['Production_Liters'];
        }
        return (float) preg_replace('/[^0-9.]/', '', $data['Production'] ?? '0');
    }
}
