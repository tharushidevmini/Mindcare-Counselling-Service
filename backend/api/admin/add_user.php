<?php
// FILE: backend/api/admin/add_user.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['admin', 'learning_advisor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }

$name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role  = trim($_POST['role'] ?? '');

// Learning advisors may only create student accounts
if ($_SESSION['role'] === 'learning_advisor') { $role = 'student'; }

if (!$name || !$email || !$role) { echo json_encode(['error'=>'All fields required']); exit; }

$chk = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$chk->execute([$email]);
if ($chk->fetch()) { echo json_encode(['error'=>'Email already exists']); exit; }

$studentId = trim($_POST['student_id'] ?? '');
if (!$studentId && $role === 'student') {
    $localPart = strstr($email, '@', true);
    if ($localPart && preg_match('/[a-zA-Z]{2,5}\d{2,6}/', $localPart, $m)) {
        $studentId = strtoupper($m[0]);
    }
}

$tempPassword = 'Mind' . rand(1000,9999) . '!';
$hashed       = password_hash($tempPassword, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("INSERT INTO users (full_name,email,password,role,student_id,is_active) VALUES (?,?,?,?,?,1)");
$stmt->execute([$name, $email, $hashed, $role, $studentId ?: null]);

echo json_encode(['success'=>true, 'temp_password'=>$tempPassword]);
?>
