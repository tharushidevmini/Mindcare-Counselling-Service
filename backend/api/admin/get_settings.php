<?php
// FILE: backend/api/admin/get_settings.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['admin']);

$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings
                      WHERE setting_key IN ('campus_security_phone','health_center_phone','crisis_helpline_phone','admin_contact_email','admin_contact_phone')");
$rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
echo json_encode(['settings' => $rows]);
?>
