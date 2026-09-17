<?php
// FILE: backend/auth/reset_password_confirm.php
// Verifies the OTP from forgot_password.php and lets the user set a
// brand new password of their own choosing.
require_once '../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST required']); exit;
}

$email       = strtolower(trim($_POST['email']        ?? ''));
$otp         = trim($_POST['otp']                      ?? '');
$newPassword = trim($_POST['new_password']              ?? '');

if (!$email || !$otp || !$newPassword) {
    echo json_encode(['error' => 'All fields are required.']); exit;
}
if (strlen($newPassword) < 6) {
    echo json_encode(['error' => 'Password must be at least 6 characters.']); exit;
}

$stmt = $pdo->prepare("
    SELECT * FROM password_resets
    WHERE email = ? AND otp_code = ? AND otp_expires > NOW() AND is_used = 0
    ORDER BY created_at DESC LIMIT 1
");
$stmt->execute([$email, $otp]);
$reset = $stmt->fetch();

if (!$reset) {
    echo json_encode(['error' => 'Invalid or expired code. Please request a new one.']); exit;
}

$u = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$u->execute([$email]);
$user = $u->fetch();
if (!$user) {
    echo json_encode(['error' => 'Account not found.']); exit;
}

$hashed = password_hash($newPassword, PASSWORD_BCRYPT);
$pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $user['id']]);
$pdo->prepare("UPDATE password_resets SET is_used = 1 WHERE id = ?")->execute([$reset['id']]);

$log = $pdo->prepare("INSERT INTO system_logs (user_id, action, ip_address) VALUES (?,?,?)");
$log->execute([$user['id'], 'self_service_password_reset', $_SERVER['REMOTE_ADDR'] ?? '']);

echo json_encode(['success' => true, 'message' => 'Your password has been reset. You can now sign in.']);
?>