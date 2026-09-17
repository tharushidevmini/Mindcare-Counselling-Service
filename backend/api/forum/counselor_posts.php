<?php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['counselor', 'admin']);

$stmt = $pdo->query("
    SELECT id, content, category, is_approved, is_removed, created_at
    FROM forum_posts
    WHERE is_removed = 0
    ORDER BY is_approved ASC, created_at DESC
");

echo json_encode(['posts' => $stmt->fetchAll()]);
?>