<?php
// FILE: backend/api/admin/save_settings.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }

$allowedKeys = ['campus_security_phone', 'health_center_phone', 'crisis_helpline_phone', 'admin_contact_email', 'admin_contact_phone'];
$stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

foreach ($allowedKeys as $key) {
    if (isset($_POST[$key])) {
        $value = trim($_POST[$key]);
        if ($value !== '') $stmt->execute([$key, $value]);
    }
}

$log = $pdo->prepare("INSERT INTO system_logs (user_id, action, ip_address) VALUES (?,?,?)");
$log->execute([$_SESSION['user_id'], 'updated_contact_settings', $_SERVER['REMOTE_ADDR'] ?? '']);

echo json_encode(['success' => true]);
?>