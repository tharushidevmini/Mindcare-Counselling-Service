<?php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }

$postId = (int)($_POST['post_id'] ?? 0);
if (!$postId) { echo json_encode(['error'=>'Post ID required']); exit; }

$stmt = $pdo->prepare("UPDATE forum_posts SET is_reported = 1 WHERE id = ?");
$stmt->execute([$postId]);
echo json_encode(['success' => true]);
?>