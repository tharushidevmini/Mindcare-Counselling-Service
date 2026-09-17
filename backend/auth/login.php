<?php
require_once '../config/db.php';
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST required']); exit;
}

$email    = strtolower(trim($_POST['email']    ?? ''));
$password = trim($_POST['password']            ?? '');
$role     = trim($_POST['role']                ?? '');

if (!$email || !$password) {
    echo json_encode(['error' => 'Email and password are required.']); exit;
}

$domain = substr(strrchr($email, '@'), 1);
if ($domain !== 'edu.lnbti.lk') {
    echo json_encode(['error' => 'Only @edu.lnbti.lk campus emails are allowed.']); exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['error' => 'No account found. Please register first.']); exit;
}
if (!$user['is_active']) {
    echo json_encode(['error' => 'This account is disabled. Contact admin.']); exit;
}
if (!password_verify($password, $user['password'])) {
    echo json_encode(['error' => 'Incorrect password.']); exit;
}
if ($role && $role !== $user['role']) {
    $roleLabels = ['student' => 'Student', 'counselor' => 'Counselor', 'learning_advisor' => 'Advisor', 'admin' => 'Admin'];
    $actualLabel = $roleLabels[$user['role']] ?? $user['role'];
    echo json_encode(['error' => "This account is registered as $actualLabel, not the selected role. Please choose the correct tab."]); exit;
}

$_SESSION['user_id']   = $user['id'];
$_SESSION['role']      = $user['role'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['photo_url'] = $user['photo_url'];

$log = $pdo->prepare("INSERT INTO system_logs (user_id, action, ip_address) VALUES (?,?,?)");
$log->execute([$user['id'], 'login', $_SERVER['REMOTE_ADDR'] ?? '']);

echo json_encode(['success' => true, 'role' => $user['role'], 'full_name' => $user['full_name']]);
?>