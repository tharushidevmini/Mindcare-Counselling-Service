<?php
// FILE: backend/api/guardian/student_status.php
// A privacy-safe "is my student okay?" view for guardians. Never exposes the
// PHQ-9 score, diary content, or counselor notes — only a friendly status
// badge and general engagement info (last check-in date, last session date).
require_once '../../config/db.php';
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['guardian_verified']) || !$_SESSION['guardian_verified']) {
    http_response_code(403);
    echo json_encode(['error' => 'Guardian session not verified']); exit;
}

$studentId = $_SESSION['guardian_student_id'] ?? 0;

$s = $pdo->prepare("SELECT full_name, student_id, share_status_with_guardian FROM users WHERE id = ?");
$s->execute([$studentId]);
$student = $s->fetch();
if (!$student) { echo json_encode(['error' => 'Student not found']); exit; }

// The student can turn this off — respect it before sharing anything.
if ((int)$student['share_status_with_guardian'] === 0) {
    echo json_encode([
        'shared_off' => true,
        'student'    => ['full_name' => $student['full_name'], 'student_id' => $student['student_id']],
    ]);
    exit;
}

// Most recent check-in — only the DATE and a simplified friendly status,
// never the raw PHQ-9 total_score.
$c = $pdo->prepare("SELECT checkin_date, severity, mood_emoji FROM daily_checkins WHERE user_id = ? ORDER BY checkin_date DESC LIMIT 1");
$c->execute([$studentId]);
$lastCheckin = $c->fetch();

$statusMap = [
    'minimal'           => ['label' => "Doing well 🟢",        'note' => 'Recent check-ins look positive.'],
    'mild'              => ['label' => "Doing okay 🟢",        'note' => 'Managing well day-to-day.'],
    'moderate'          => ['label' => "Managing 🟡",          'note' => 'Going through some stress — support helps.'],
    'moderately severe' => ['label' => "Needs support 🟠",     'note' => 'Encourage them to reach out to their counselor.'],
    'severe'            => ['label' => "Needs support 🔴",     'note' => 'Please encourage them to talk to a counselor soon.'],
];
$status = $lastCheckin && isset($statusMap[$lastCheckin['severity']])
    ? $statusMap[$lastCheckin['severity']]
    : ['label' => 'No recent check-in', 'note' => 'This student hasn\'t logged a daily check-in recently.'];

// Most recent confirmed counseling session — shows engagement, not content.
$a = $pdo->prepare("SELECT preferred_date FROM appointments WHERE student_id = ? AND status = 'accepted' ORDER BY preferred_date DESC LIMIT 1");
$a->execute([$studentId]);
$lastSession = $a->fetch();

echo json_encode([
    'student'          => $student,
    'status_label'      => $status['label'],
    'status_note'       => $status['note'],
    'last_checkin_date' => $lastCheckin['checkin_date'] ?? null,
    'mood_emoji'        => $lastCheckin['mood_emoji'] ?? null,
    'last_session_date' => $lastSession['preferred_date'] ?? null,
]);
?>