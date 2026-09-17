<?php
// FILE: backend/api/counselor/diary_pin.php
// Toggles the pinned state of one of the counselor's OWN notes.
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['counselor','learning_advisor','admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }

$uid = $_SESSION['user_id'];
$id  = (int)($_POST['id'] ?? 0);
if (!$id) { echo json_encode(['error'=>'Missing note id']); exit; }

// Owner check + flip is_pinned in one guarded update
$stmt = $pdo->prepare("SELECT is_pinned FROM diary_entries WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $uid]);
$row = $stmt->fetch();
if (!$row) { echo json_encode(['error'=>'Not found or not yours']); exit; }

$new = $row['is_pinned'] ? 0 : 1;
$upd = $pdo->prepare("UPDATE diary_entries SET is_pinned = ? WHERE id = ? AND user_id = ?");
$upd->execute([$new, $id, $uid]);

echo json_encode(['success'=>true, 'is_pinned'=>$new]);
?>