<?php
// FILE: backend/api/mood/today.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['student']);

$stmt = $pdo->prepare("SELECT * FROM daily_checkins WHERE user_id = ? AND checkin_date = ?");
$stmt->execute([$_SESSION['user_id'], date('Y-m-d')]);
$row = $stmt->fetch();
echo json_encode($row ? ['checked_in'=>true] + $row : ['checked_in'=>false]);
?>
