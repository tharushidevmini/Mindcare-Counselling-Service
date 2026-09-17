<?php
// FILE: backend/api/ai/chat_history.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['student']);

$uid = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT role, content, created_at FROM ai_chat_messages WHERE user_id = ? ORDER BY created_at ASC LIMIT 100");
$stmt->execute([$uid]);
echo json_encode(['messages' => $stmt->fetchAll()]);
?>