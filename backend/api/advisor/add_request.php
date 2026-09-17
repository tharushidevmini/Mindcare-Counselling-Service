<?php
// FILE: backend/api/advisor/add_request.php
// Lets the learning advisor log a new accommodation request themselves
// (e.g. after meeting a student directly) instead of waiting on a forward.
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['learning_advisor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); exit; }

$studentId     = (int)($_POST['student_id'] ?? 0);
$accommodation = trim($_POST['accommodation_type'] ?? '');
$advisorId     = $_SESSION['user_id'];

if (!$studentId)     { echo json_encode(['error' => 'Please select a student']); exit; }
if (!$accommodation) { echo json_encode(['error' => 'Please describe the accommodation needed']); exit; }

// Confirm the student exists and is active
$chk = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'student' AND is_active = 1");
$chk->execute([$studentId]);
if (!$chk->fetch()) { echo json_encode(['error' => 'Student not found']); exit; }

// Short random token so the request is still trackable without exposing the
// student's name anywhere else in the CSP flow (kept consistent with counselor-forwarded requests)
$token = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

$stmt = $pdo->prepare("INSERT INTO academic_relief_requests (student_id, token, accommodation_type, forwarded_by_id, status) VALUES (?,?,?,?, 'pending')");
$stmt->execute([$studentId, $token, $accommodation, $advisorId]);

echo json_encode(['success' => true, 'token' => $token]);
?>