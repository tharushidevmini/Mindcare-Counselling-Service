<?php
// FILE: backend/api/counselor/save_notes.php
// Saves a session note. ALWAYS inserts a new row, so every note the counselor
// takes — from the student's very first session onward — is kept as history.
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['counselor', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); exit; }

$counselor_id = $_SESSION['user_id'];
$student_id   = (int)($_POST['student_id'] ?? 0);
$notes        = trim($_POST['notes'] ?? '');

if (!$student_id || !$notes) { echo json_encode(['error' => 'Student and notes required']); exit; }

$stmt = $pdo->prepare("INSERT INTO counselor_notes (counselor_id, student_id, notes) VALUES (?, ?, ?)");
$stmt->execute([$counselor_id, $student_id, $notes]);

echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
?>