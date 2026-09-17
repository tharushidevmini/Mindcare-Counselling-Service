<?php
// FILE: backend/api/public/settings.php
// Public read-only settings — no login required. Only exposes contact info,
// never anything sensitive.
require_once '../../config/db.php';
header('Content-Type: application/json');

$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings
                      WHERE setting_key IN ('campus_security_phone','health_center_phone','crisis_helpline_phone','admin_contact_email','admin_contact_phone')");
$rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
echo json_encode(['settings' => $rows]);
?>