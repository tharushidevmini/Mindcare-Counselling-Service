<?php
// FILE: backend/api/counselor/student_wellbeing.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['counselor', 'admin']);

$studentId = (int)($_GET['student_id'] ?? 0);
if (!$studentId) { echo json_encode(['error' => 'Missing student_id']); exit; }

$stmt = $pdo->prepare("SELECT full_name, student_id FROM users WHERE id = ? AND role = 'student'");
$stmt->execute([$studentId]);
$student = $stmt->fetch();
if (!$student) { echo json_encode(['error' => 'Student not found']); exit; }

// Full history — every check-in since the student joined (not just recent)
$stmt = $pdo->prepare("
    SELECT id, checkin_date, mood_emoji, total_score, severity
    FROM daily_checkins
    WHERE user_id = ?
    ORDER BY checkin_date ASC
");
$stmt->execute([$studentId]);
$records = $stmt->fetchAll();

$withScore = array_filter($records, fn($r) => $r['total_score'] !== null);
$avg = count($withScore) ? round(array_sum(array_column($withScore, 'total_score')) / count($withScore), 1) : null;

echo json_encode([
    'student'      => $student,
    'records'      => $records,
    'average'      => $avg,
    'total_checkins' => count($records),
]);
?>