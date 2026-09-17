<?php
// FILE: backend/api/appointments/my.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['student']);

$uid = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT a.*, u.full_name AS counselor_name
    FROM appointments a
    JOIN users u ON u.id = a.counselor_id
    WHERE a.student_id = ? AND a.status != 'cancelled' AND a.status != 'completed'
    ORDER BY a.preferred_date, a.preferred_time
");
$stmt->execute([$uid]);
echo json_encode(['appointments' => $stmt->fetchAll()]);
?>