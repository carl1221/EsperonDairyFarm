<?php
// ============================================================
// models/Worker.php
// ============================================================

require_once __DIR__ . '/../config/database.php';

class Worker {

    private PDO $db;

    public function __construct() {
        $this->db = getConnection();
    }

    /**
     * Return all workers.
     * Password hash is deliberately excluded — never send it to clients.
     */
    public function getAll(): array {
        $stmt = $this->db->query(
            'SELECT Worker_ID, Worker, Worker_Role FROM Worker ORDER BY Worker_ID'
        );
        return $stmt->fetchAll();
    }

    /**
     * Return a single worker by ID.
     * Password hash is excluded.
     */
    public function getById(int $id): array|false {
        $stmt = $this->db->prepare(
            'SELECT Worker_ID, Worker, Worker_Role FROM Worker WHERE Worker_ID = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Insert a new worker.
     * Expects $data to contain: Worker_ID, Worker, Worker_Role, Password (already hashed).
     */
    public function create(array $data): bool {
        // Worker_ID is AUTO_INCREMENT — only specify it if explicitly provided
        if (!empty($data['Worker_ID'])) {
            $stmt = $this->db->prepare(
                'INSERT INTO Worker (Worker_ID, Worker, Worker_Role, Password)
                 VALUES (:id, :name, :role, :password)'
            );
            return $stmt->execute([
                ':id'       => $data['Worker_ID'],
                ':name'     => $data['Worker'],
                ':role'     => $data['Worker_Role'],
                ':password' => $data['Password'] ?? '',
            ]);
        }
        $stmt = $this->db->prepare(
            'INSERT INTO Worker (Worker, Worker_Role, Password)
             VALUES (:name, :role, :password)'
        );
        return $stmt->execute([
            ':name'     => $data['Worker'],
            ':role'     => $data['Worker_Role'],
            ':password' => $data['Password'] ?? '',
        ]);
    }

    /**
     * Update a worker's name and role.
     * Password changes should go through a dedicated change-password endpoint.
     */
    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare(
            'UPDATE Worker SET Worker = :name, Worker_Role = :role WHERE Worker_ID = :id'
        );
        $stmt->execute([
            ':name' => $data['Worker'],
            ':role' => $data['Worker_Role'],
            ':id'   => $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Delete a worker by ID.
     */
    public function delete(int $id): bool {
        $stmt = $this->db->prepare('DELETE FROM Worker WHERE Worker_ID = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
