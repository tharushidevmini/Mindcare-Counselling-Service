<?php
// FILE: backend/api/mood/history.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['student']);

$stmt = $pdo->prepare("SELECT * FROM daily_checkins WHERE user_id = ? ORDER BY checkin_date DESC LIMIT 7");
$stmt->execute([$_SESSION['user_id']]);
echo json_encode(['records' => array_reverse($stmt->fetchAll())]);
?>
