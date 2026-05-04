<?php
// ============================================================
// api/notes.php  —  Persistent notes/announcements
//
// GET    /api/notes.php                        → list all notes (newest first)
// GET    /api/notes.php?entity_type=Cow&entity_id=5 → notes for a specific entity
// GET    /api/notes.php?category=Health        → filter by category
// POST   /api/notes.php                        → create a note
// PUT    /api/notes.php?id=1                   → edit a note (own note or Admin)
// DELETE /api/notes.php?id=1                   → delete a note (own note or Admin)
// DELETE /api/notes.php?all=1                  → clear all notes (Admin only)
//
// 3NF: author name is NOT stored in notes — derived via vw_notes
//      JOIN Worker at query time.
//
// Relationships supported:
//   entity_type = 'Cow'      → entity_id = Cow_ID
//   entity_type = 'Order'    → entity_id = Order_ID
//   entity_type = 'Customer' → entity_id = CID
//   entity_type = 'Worker'   → entity_id = Worker_ID
// ============================================================

require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();
requireCsrf();

$method      = $_SERVER['REQUEST_METHOD'];
$id          = isset($_GET['id'])          ? (int)    $_GET['id']          : null;
$all         = isset($_GET['all'])         && $_GET['all'] === '1';
$entityType  = isset($_GET['entity_type']) ? trim($_GET['entity_type'])    : null;
$entityId    = isset($_GET['entity_id'])   ? (int)    $_GET['entity_id']   : null;
$filterCat   = isset($_GET['category'])    ? trim($_GET['category'])       : null;

$db   = getConnection();
$user = $_SESSION['user'];

$validEntityTypes = ['Cow', 'Order', 'Customer', 'Worker'];
$validCategories  = ['General', 'Health', 'Feeding', 'Maintenance', 'Finance', 'Other'];

try {
    switch ($method) {

        // ── GET ──────────────────────────────────────────────
        case 'GET':
            $where  = [];
            $params = [];

            if ($entityType !== null) {
                if (!in_array($entityType, $validEntityTypes, true)) {
                    sendError('Invalid entity_type.', 400);
                }
                $where[]  = 'entity_type = ?';
                $params[] = $entityType;

                if ($entityId !== null) {
                    $where[]  = 'entity_id = ?';
                    $params[] = $entityId;
                }
            }

            if ($filterCat !== null) {
                if (!in_array($filterCat, $validCategories, true)) {
                    sendError('Invalid category.', 400);
                }
                $where[]  = 'category = ?';
                $params[] = $filterCat;
            }

            $sql = "SELECT n.note_id, n.text, n.category, n.entity_type, n.entity_id,
                           n.created_at, n.updated_at, n.author_id,
                           w.Worker AS author, w.Worker_Role AS author_role
                    FROM notes n JOIN Worker w ON n.author_id = w.Worker_ID"
                 . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
                 . " ORDER BY n.created_at DESC LIMIT 100";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            sendSuccess('Notes retrieved.', $stmt->fetchAll());
            break;

        // ── POST ─────────────────────────────────────────────
        case 'POST':
            $data       = getRequestBody();
            $text       = trim($data['text']        ?? '');
            $category   = trim($data['category']    ?? 'General');
            $entType    = isset($data['entity_type']) ? trim($data['entity_type']) : null;
            $entId      = isset($data['entity_id'])   ? (int) $data['entity_id']  : null;

            if ($text === '')                                    sendError('Note text is required.', 400);
            if (strlen($text) > 1000)                           sendError('Note must be 1000 characters or less.', 400);
            if (!in_array($category, $validCategories, true))   sendError('Invalid category.', 400);

            if ($entType !== null && !in_array($entType, $validEntityTypes, true)) {
                sendError('Invalid entity_type.', 400);
            }
            if ($entType !== null && $entId === null) {
                sendError('entity_id is required when entity_type is set.', 400);
            }
            if ($entType === null) $entId = null; // keep consistent

            $stmt = $db->prepare("
                INSERT INTO notes (author_id, text, category, entity_type, entity_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([(int)$user['id'], $text, $category, $entType, $entId]);
            $noteId = $db->lastInsertId();

            $new = $db->prepare("
                SELECT n.note_id, n.text, n.category, n.entity_type, n.entity_id,
                       n.created_at, n.updated_at, n.author_id,
                       w.Worker AS author, w.Worker_Role AS author_role
                FROM notes n JOIN Worker w ON n.author_id = w.Worker_ID
                WHERE n.note_id = ?
            ");
            $new->execute([$noteId]);
            sendSuccess('Note posted.', $new->fetch(), 201);
            break;

        // ── PUT (edit) ────────────────────────────────────────
        case 'PUT':
            if (!$id) sendError('Note ID is required.', 400);

            $stmt = $db->prepare("SELECT author_id FROM notes WHERE note_id = ?");
            $stmt->execute([$id]);
            $note = $stmt->fetch();
            if (!$note) sendError('Note not found.', 404);

            $isAdmin = ($user['role'] ?? '') === 'Admin';
            if (!$isAdmin && (int)$note['author_id'] !== (int)$user['id']) {
                sendError('You can only edit your own notes.', 403);
            }

            $data     = getRequestBody();
            $text     = trim($data['text']     ?? '');
            $category = trim($data['category'] ?? 'General');
            $entType  = isset($data['entity_type']) ? trim($data['entity_type']) : null;
            $entId    = isset($data['entity_id'])   ? (int) $data['entity_id']  : null;

            if ($text === '')                                    sendError('Note text is required.', 400);
            if (strlen($text) > 1000)                           sendError('Note must be 1000 characters or less.', 400);
            if (!in_array($category, $validCategories, true))   sendError('Invalid category.', 400);
            if ($entType !== null && !in_array($entType, $validEntityTypes, true)) {
                sendError('Invalid entity_type.', 400);
            }
            if ($entType !== null && $entId === null) {
                sendError('entity_id is required when entity_type is set.', 400);
            }
            if ($entType === null) $entId = null;

            $db->prepare("
                UPDATE notes SET text = ?, category = ?, entity_type = ?, entity_id = ?
                WHERE note_id = ?
            ")->execute([$text, $category, $entType, $entId, $id]);

            $updated = $db->prepare("
                SELECT n.note_id, n.text, n.category, n.entity_type, n.entity_id,
                       n.created_at, n.updated_at, n.author_id,
                       w.Worker AS author, w.Worker_Role AS author_role
                FROM notes n JOIN Worker w ON n.author_id = w.Worker_ID
                WHERE n.note_id = ?
            ");
            $updated->execute([$id]);
            sendSuccess('Note updated.', $updated->fetch());
            break;

        // ── DELETE ────────────────────────────────────────────
        case 'DELETE':
            if ($all) {
                requireRole(['Admin']);
                $db->exec("DELETE FROM notes");
                sendSuccess('All notes cleared.');
            } elseif ($id) {
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
