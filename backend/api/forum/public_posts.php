<?php
require_once '../../config/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$stmt = $pdo->query("
    SELECT id, content, category, display_name, likes, created_at 
    FROM forum_posts 
    WHERE is_approved = 1 
    AND is_removed = 0 
    ORDER BY created_at DESC 
    LIMIT 6
");

echo json_encode(['posts' => $stmt->fetchAll()]);
?>