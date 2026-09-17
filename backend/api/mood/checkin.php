<?php
// FILE: backend/api/mood/checkin.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['student']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }

$uid   = $_SESSION['user_id'];
$today = date('Y-m-d');

$chk = $pdo->prepare("SELECT id FROM daily_checkins WHERE user_id = ? AND checkin_date = ?");
$chk->execute([$uid, $today]);
if ($chk->fetch()) { echo json_encode(['error'=>'Already checked in today']); exit; }

// PHQ-9 answers, each 0-3
$q = [];
for ($i = 1; $i <= 9; $i++) {
    $q[$i] = max(0, min(3, (int)($_POST["q$i"] ?? 0)));
}
$total = array_sum($q);

$severity = 'Minimal';
if     ($total >= 20) $severity = 'Severe';
elseif ($total >= 15) $severity = 'Moderately severe';
elseif ($total >= 10) $severity = 'Moderate';
elseif ($total >= 5)  $severity = 'Mild';

$emoji = $_POST['mood_emoji'] ?? '';

$stmt = $pdo->prepare("
    INSERT INTO daily_checkins
    (user_id, mood_emoji, checkin_date, q1_interest, q2_mood, q3_sleep, q4_energy, q5_appetite,
     q6_selfworth, q7_concentration, q8_restlessness, q9_selfharm, total_score, severity)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
");
$stmt->execute([
    $uid, $emoji, $today,
    $q[1], $q[2], $q[3], $q[4], $q[5], $q[6], $q[7], $q[8], $q[9],
    $total, $severity
]);

// SAFETY: Question 9 asks about thoughts of self-harm / being better off dead.
// Any non-zero answer creates an immediate alert for the counselor to review —
// this mirrors the existing diary keyword-alert system.
if ($q[9] > 0) {
    $sev = $q[9] >= 2 ? 'critical' : 'high';
    $labels = ['', 'Several days', 'More than half the days', 'Nearly every day'];
    $alert = $pdo->prepare("INSERT INTO risk_alerts (student_id, diary_entry_id, keywords_found, severity, status) VALUES (?, NULL, ?, ?, 'open')");
    $alert->execute([$uid, 'PHQ-9 check-in: thoughts of self-harm — "' . $labels[$q[9]] . '"', $sev]);
}

echo json_encode(['success' => true, 'total_score' => $total, 'severity' => $severity]);
?>