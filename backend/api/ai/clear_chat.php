<?php
// FILE: backend/api/ai/clear_chat.php
// Wipes the student's AI chat so they can start a fresh conversation.
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['student']);

$uid = $_SESSION['user_id'];
$stmt = $pdo->prepare("DELETE FROM ai_chat_messages WHERE user_id = ?");
$stmt->execute([$uid]);

echo json_encode(['success' => true, 'cleared' => $stmt->rowCount()]);
?>