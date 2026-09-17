<?php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireLogin();

$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? 'like'; // like or unlike

if (!$id) { echo json_encode(['error' => 'Invalid post']); exit; }

if ($action === 'like') {
    $stmt = $pdo->prepare("UPDATE forum_posts SET likes = likes + 1 WHERE id = ?");
} else {
    $stmt = $pdo->prepare("UPDATE forum_posts SET likes = GREATEST(likes - 1, 0) WHERE id = ?");
}

$stmt->execute([$id]);
$count = $pdo->prepare("SELECT likes FROM forum_posts WHERE id = ?");
$count->execute([$id]);
$row = $count->fetch();

echo json_encode(['success' => true, 'likes' => $row['likes']]);
?>