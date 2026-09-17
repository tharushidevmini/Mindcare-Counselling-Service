<?php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireLogin();

$stmt = $pdo->prepare("SELECT id, anon_key AS anonymous_id, content, category, likes, created_at FROM forum_posts WHERE is_removed=0 ORDER BY created_at DESC LIMIT 20");
$stmt->execute();
echo json_encode(['posts' => $stmt->fetchAll()]);
?>