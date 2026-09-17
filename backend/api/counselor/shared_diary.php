<?php
// FILE: backend/api/counselor/shared_diary.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');

requireRole(['counselor', 'admin']);

// Optional filter: only entries from one specific student
$studentId = (int)($_GET['student_id'] ?? 0);

$sql = "SELECT d.id, d.title, d.mood_emoji, d.entry_date, d.created_at,
               u.id AS student_id, u.full_name AS student_name
        FROM diary_entries d
        JOIN users u ON u.id = d.user_id
        WHERE d.share_with_counselor = 1";
$params = [];
if ($studentId) { $sql .= " AND d.user_id = ?"; $params[] = $studentId; }
$sql .= " ORDER BY d.entry_date DESC, d.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$entries = $stmt->fetchAll();

// Distinct student list, for the filter dropdown
$stmt2 = $pdo->query("SELECT DISTINCT u.id, u.full_name
                       FROM diary_entries d JOIN users u ON u.id = d.user_id
                       WHERE d.share_with_counselor = 1
                       ORDER BY u.full_name");
$students = $stmt2->fetchAll();

echo json_encode(['entries' => $entries, 'students' => $students]);
?>