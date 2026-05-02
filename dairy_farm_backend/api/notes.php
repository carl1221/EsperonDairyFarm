<?php
// ============================================================
// api/notes.php  —  Persistent notes/announcements
//
// GET    /api/notes.php          → list all notes (newest first)
// POST   /api/notes.php          → create a note
// DELETE /api/notes.php?id=1     → delete a note (own note or Admin)
// DELETE /api/notes.php?all=1    → clear all notes (Admin only)
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
requireAuth();
requireCsrf();

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id'])  ? (int) $_GET['id']  : null;
$all    = isset($_GET['all']) && $_GET['all'] === '1';
$db     = getConnection();
$user   = $_SESSION['user'];

// Ensure table exists
$db->exec("
    CREATE TABLE IF NOT EXISTS notes (
        note_id    INT          NOT NULL AUTO_INCREMENT,
        author_id  INT          NOT NULL,
        author     VARCHAR(100) NOT NULL,
        text       TEXT         NOT NULL,
        created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT pk_notes PRIMARY KEY (note_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

try {
    switch ($method) {

        case 'GET':
            $stmt = $db->query("SELECT * FROM notes ORDER BY created_at DESC LIMIT 50");
            sendSuccess('Notes retrieved.', $stmt->fetchAll());
            break;

        case 'POST':
            $data = getRequestBody();
            $text = trim($data['text'] ?? '');
            if ($text === '') sendError('Note text is required.', 400);
            if (strlen($text) > 500) sendError('Note must be 500 characters or less.', 400);

            $stmt = $db->prepare(
                "INSERT INTO notes (author_id, author, text) VALUES (?, ?, ?)"
            );
            $stmt->execute([(int)$user['id'], $user['name'], $text]);
            $noteId = $db->lastInsertId();

            // Return the new note so the UI can prepend it immediately
            $new = $db->prepare("SELECT * FROM notes WHERE note_id = ?");
            $new->execute([$noteId]);
            sendSuccess('Note posted.', $new->fetch(), 201);
            break;

        case 'DELETE':
            if ($all) {
                // Clear all — Admin only
                requireRole(['Admin']);
                $db->exec("DELETE FROM notes");
                sendSuccess('All notes cleared.');
            } elseif ($id) {
                // Delete one — own note or Admin
                $stmt = $db->prepare("SELECT author_id FROM notes WHERE note_id = ?");
                $stmt->execute([$id]);
                $note = $stmt->fetch();
                if (!$note) sendError('Note not found.', 404);

                $isAdmin = ($user['role'] ?? '') === 'Admin';
                if (!$isAdmin && (int)$note['author_id'] !== (int)$user['id']) {
                    sendError('You can only delete your own notes.', 403);
                }

                $db->prepare("DELETE FROM notes WHERE note_id = ?")->execute([$id]);
                sendSuccess('Note deleted.');
            } else {
                sendError('Note ID or ?all=1 required.', 400);
            }
            break;

        default:
            sendError('Method not allowed.', 405);
    }
} catch (PDOException $e) {
    error_log('Notes error: ' . $e->getMessage());
    sendError('A database error occurred.', 500);
}
