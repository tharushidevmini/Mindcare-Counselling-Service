<?php
// FILE: backend/api/guardian/session_info.php
require_once '../../config/db.php';
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['guardian_verified']) || !$_SESSION['guardian_verified']) {
    http_response_code(403);
    echo json_encode(['error' => 'Guardian session not verified']); exit;
}

$studentId = $_SESSION['guardian_student_id'] ?? 0;

$stmt = $pdo->prepare("SELECT full_name, student_id FROM users WHERE id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

$cs = $pdo->prepare("SELECT id, full_name FROM users WHERE role = 'counselor' AND is_active = 1");
$cs->execute();
$counselors = $cs->fetchAll();

echo json_encode([
    'student'    => $student,
    'counselors' => $counselors,
]);
?>