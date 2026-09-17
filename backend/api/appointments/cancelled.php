<?php
// FILE: backend/api/appointments/cancelled.php
// Cancellation notices for the counselor: sessions a student cancelled
// (e.g. an emergency came up). Shows the student + the reason they gave.
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');

requireRole(['counselor', 'admin']);

$cid = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT a.id, a.preferred_date, a.preferred_time, a.session_type,
           a.reschedule_reason, a.created_at, a.guardian_id,
           u.full_name AS student_name, u.student_id
    FROM appointments a
    JOIN users u ON a.student_id = u.id
    WHERE a.counselor_id = ? AND a.status = 'cancelled'
    ORDER BY a.preferred_date DESC, a.preferred_time DESC
    LIMIT 20
");
$stmt->execute([$cid]);

echo json_encode(['cancellations' => $stmt->fetchAll()]);
?>