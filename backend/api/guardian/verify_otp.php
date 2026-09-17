<?php
// FILE: backend/api/guardian/verify_otp.php
require_once '../../config/db.php';
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }

$code      = trim($_POST['otp_code']       ?? '');
$email     = strtolower(trim($_POST['guardian_email'] ?? ''));
$studentId = trim($_POST['student_id']     ?? '');
if (!$code || !$email || !$studentId) { echo json_encode(['error'=>'Missing fields']); exit; }

$stmt = $pdo->prepare("
    SELECT gs.id, u.id AS user_student_id FROM guardian_sessions gs
    JOIN users u ON u.student_id = ?
    WHERE gs.guardian_phone = ? AND gs.student_id = u.id AND gs.otp_code = ?
      AND gs.otp_expires > NOW() AND gs.is_verified = 0
    ORDER BY gs.created_at DESC LIMIT 1
");
$stmt->execute([$studentId, $email, $code]);
$row = $stmt->fetch();
if (!$row) { echo json_encode(['error'=>'Invalid or expired OTP']); exit; }

$upd = $pdo->prepare("UPDATE guardian_sessions SET is_verified = 1 WHERE id = ?");
$upd->execute([$row['id']]);

$_SESSION['guardian_verified']   = true;
$_SESSION['guardian_session_id'] = $row['id'];
$_SESSION['guardian_student_id'] = (int)$row['user_student_id'];

$logStmt = $pdo->prepare("INSERT INTO system_logs (action, ip_address) VALUES (?,?)");
$logStmt->execute(['guardian_otp_verified for student_id:' . $studentId, $_SERVER['REMOTE_ADDR'] ?? '']);

echo json_encode(['success' => true]);
?>