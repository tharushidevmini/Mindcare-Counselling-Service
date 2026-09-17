<?php
// FILE: backend/api/appointments/counselors.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireLogin();

$stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE role = 'counselor' AND is_active = 1");
$stmt->execute();
echo json_encode(['counselors' => $stmt->fetchAll()]);
?>
