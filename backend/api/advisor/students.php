<?php
// FILE: backend/api/advisor/students.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['learning_advisor']);

$stmt = $pdo->query("SELECT id, full_name, student_id FROM users WHERE role = 'student' AND is_active = 1 ORDER BY full_name");
echo json_encode(['students' => $stmt->fetchAll()]);
?>