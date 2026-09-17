<?php
// FILE: backend/api/advisor/counselors.php
// Lets the learning advisor list active counselors, so they can pick who
// to email directly from the Academic Accommodations dashboard.
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['learning_advisor']);

$stmt = $pdo->prepare("SELECT id, full_name, specialty FROM users WHERE role = 'counselor' AND is_active = 1 ORDER BY full_name ASC");
$stmt->execute();
echo json_encode(['counselors' => $stmt->fetchAll()]);
?>