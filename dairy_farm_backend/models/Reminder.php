<?php
// ============================================================
// models/Reminder.php
//
// reminders is a WEAK ENTITY — it depends on Worker.
// Composite PK: (reminder_id, created_by)  
//   • reminder_id  = partial key (unique only within one worker)
//   • created_by   = identifying FK → Worker (ON DELETE CASCADE)
//
// A reminder row cannot exist without its owner Worker row.
// Deleting a Worker cascades and removes all their reminders.
// ============================================================

require_once __DIR__ . '/../config/database.php';

class Reminder {
    private PDO $db;

    public function __construct() {
        $this->db = getConnection();
    }

    // ── READ ─────────────────────────────────────────────────

    private const REMINDER_SELECT = "
        SELECT r.reminder_id, r.title, r.description, r.due_date, r.status, r.created_at,
               r.created_by, creator.Worker AS created_by_name, creator.Worker_Role AS created_by_role,
               r.assigned_to, assignee.Worker AS assigned_to_name
        FROM reminders r
        JOIN Worker creator ON r.created_by = creator.Worker_ID
        LEFT JOIN Worker assignee ON r.assigned_to = assignee.Worker_ID";

    /** All reminders with creator and assignee names resolved. */
    public function getAll(): array {
        $stmt = $this->db->query(self::REMINDER_SELECT . " ORDER BY r.due_date ASC");
        return $stmt->fetchAll();
    }

    public function getByAssignee(int $workerId): array {
        $stmt = $this->db->prepare(self::REMINDER_SELECT . " WHERE r.assigned_to = ? ORDER BY r.due_date ASC");
        $stmt->execute([$workerId]);
        return $stmt->fetchAll();
    }

    public function getByCreator(int $workerId): array {
        $stmt = $this->db->prepare(self::REMINDER_SELECT . " WHERE r.created_by = ? ORDER BY r.due_date ASC");
        $stmt->execute([$workerId]);
        return $stmt->fetchAll();
    }

    public function getById(int $reminderId, int $createdBy): array|false {
        $stmt = $this->db->prepare(self::REMINDER_SELECT . " WHERE r.reminder_id = ? AND r.created_by = ?");
        $stmt->execute([$reminderId, $createdBy]);
        return $stmt->fetch();
    }

    public function getByReminderId(int $reminderId): array|false {
        $stmt = $this->db->prepare(self::REMINDER_SELECT . " WHERE r.reminder_id = ? LIMIT 1");
        $stmt->execute([$reminderId]);
        return $stmt->fetch();
    }

    // ── WRITE ────────────────────────────────────────────────

    /**
     * Create a reminder.
     * Required: title, due_date, created_by (identifying FK — weak entity rule)
     * Optional: assigned_to, description, status
     */
    public function create(array $data): bool {
        if (empty($data['created_by'])) {
            throw new \InvalidArgumentException(
                'created_by is required — reminders is a weak entity and must reference a Worker.'
            );
        }

        $stmt = $this->db->prepare(
            "INSERT INTO reminders
                (created_by, assigned_to, title, description, due_date, status)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            (int)   $data['created_by'],
            isset($data['assigned_to']) && $data['assigned_to'] !== ''
                    ? (int) $data['assigned_to']
                    : null,
                    $data['title'],
                    $data['description'] ?? null,
                    $data['due_date'],
                    $data['status']      ?? 'pending',
        ]);
    }

    /**
     * Update a reminder.
     * Updatable fields: title, description, due_date, status, assigned_to
     * created_by is immutable — it is part of the PK.
     */
    public function update(int $reminderId, array $data): bool {
        $allowed = ['title', 'description', 'due_date', 'status', 'assigned_to'];
        $fields  = [];
        $params  = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                // Treat empty string as NULL for nullable FK columns
                $params[] = ($field === 'assigned_to' && $data[$field] === '')
                    ? null
                    : $data[$field];
            }
        }

        if (empty($fields)) return false;

        $params[] = $reminderId;
        $stmt = $this->db->prepare(
            "UPDATE reminders SET " . implode(', ', $fields) . " WHERE reminder_id = ?"
        );
        return $stmt->execute($params);
    }

    /**
     * Delete a reminder by its partial key (reminder_id).
     * The cascade from Worker deletion is handled by the DB FK.
     */
    public function delete(int $reminderId): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM reminders WHERE reminder_id = ?"
        );
        $stmt->execute([$reminderId]);
        return $stmt->rowCount() > 0;
    }

    // ── FILTERED READS ───────────────────────────────────────

    public function getPending(): array {
        $stmt = $this->db->query(self::REMINDER_SELECT . " WHERE r.status = 'pending' ORDER BY r.due_date ASC");
        return $stmt->fetchAll();
    }

    public function getOverdue(): array {
        $stmt = $this->db->query(self::REMINDER_SELECT . " WHERE r.status = 'pending' AND r.due_date < NOW() ORDER BY r.due_date ASC");
        return $stmt->fetchAll();
    }

    public function getDueSoon(): array {
        $stmt = $this->db->query(self::REMINDER_SELECT . " WHERE r.status = 'pending' AND r.due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR) ORDER BY r.due_date ASC");
        return $stmt->fetchAll();
    }
}
