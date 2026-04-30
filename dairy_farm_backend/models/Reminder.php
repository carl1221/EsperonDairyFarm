<?php
// ============================================================
// models/Reminder.php
// ============================================================

require_once __DIR__ . '/../config/database.php';

class Reminder {
    private PDO $db;

    public function __construct() {
        $this->db = getConnection();
    }

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM reminders ORDER BY due_date ASC");
        return $stmt->fetchAll();
    }

    public function getById(int $id): array|false {
        $stmt = $this->db->prepare("SELECT * FROM reminders WHERE reminder_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(array $data): bool {
        $stmt = $this->db->prepare("
            INSERT INTO reminders (title, description, due_date, status) VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['title'],
            $data['description'] ?? null,
            $data['due_date'],
            $data['status'] ?? 'pending'
        ]);
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $params = [];
        
        if (isset($data['title'])) {
            $fields[] = "title = ?";
            $params[] = $data['title'];
        }
        if (isset($data['description'])) {
            $fields[] = "description = ?";
            $params[] = $data['description'];
        }
        if (isset($data['due_date'])) {
            $fields[] = "due_date = ?";
            $params[] = $data['due_date'];
        }
        if (isset($data['status'])) {
            $fields[] = "status = ?";
            $params[] = $data['status'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        $sql = "UPDATE reminders SET " . implode(", ", $fields) . " WHERE reminder_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM reminders WHERE reminder_id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function getPending(): array {
        $stmt = $this->db->query("SELECT * FROM reminders WHERE status = 'pending' ORDER BY due_date ASC");
        return $stmt->fetchAll();
    }

    public function getOverdue(): array {
        $stmt = $this->db->query("SELECT * FROM reminders WHERE status = 'pending' AND due_date < NOW() ORDER BY due_date ASC");
        return $stmt->fetchAll();
    }

    public function getDueSoon(): array {
        $stmt = $this->db->query("SELECT * FROM reminders WHERE status = 'pending' AND due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR) ORDER BY due_date ASC");
        return $stmt->fetchAll();
    }
}