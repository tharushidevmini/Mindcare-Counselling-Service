<?php
// FILE: backend/api/public/counselors.php
// Public counselor list for the home page — no login required.
// Only exposes name, specialty, and photo — nothing private.
require_once '../../config/db.php';
header('Content-Type: application/json');

$stmt = $pdo->prepare("SELECT full_name, specialty, photo_url, phone FROM users WHERE role = 'counselor' AND is_active = 1 ORDER BY id ASC");
$stmt->execute();
echo json_encode(['counselors' => $stmt->fetchAll()]);
?>