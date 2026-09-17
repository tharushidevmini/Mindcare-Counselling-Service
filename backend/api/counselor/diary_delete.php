<?php
// FILE: backend/api/counselor/diary_delete.php
// Deletes one of the counselor's OWN notes. Owner check prevents
// deleting anyone else's entry.
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['counselor','learning_advisor','admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }

$uid = $_SESSION['user_id'];
$id  = (int)($_POST['id'] ?? 0);
if (!$id) { echo json_encode(['error'=>'Missing note id']); exit; }

// Scoped delete: id AND user_id must both match, so a counselor can only
// ever delete their own note.
$stmt = $pdo->prepare("DELETE FROM diary_entries WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $uid]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['error'=>'Note not found or not yours']);
}
?>