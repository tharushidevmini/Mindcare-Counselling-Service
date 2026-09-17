<?php
// FILE: backend/api/guardian/diary_shared.php
// Lists ONLY the diary entries this student has explicitly shared with their
// guardian (share_with_guardian = 1). Never returns entries the student
// hasn't opted to share, and never returns decrypted content here — just
// the summary list, same as the student's own list view.
require_once '../../config/db.php';
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['guardian_verified']) || !$_SESSION['guardian_verified']) {
    http_response_code(403);
    echo json_encode(['error' => 'Guardian session not verified']); exit;
}

$studentId = $_SESSION['guardian_student_id'] ?? 0;

$stmt = $pdo->prepare("SELECT id, title, mood_emoji, entry_date
                        FROM diary_entries
                        WHERE user_id = ? AND share_with_guardian = 1
                        ORDER BY entry_date DESC");
$stmt->execute([$studentId]);
echo json_encode(['entries' => $stmt->fetchAll()]);
?>