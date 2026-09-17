<?php
// FILE: backend/api/counselor/student_report.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['counselor', 'admin']);

$counselorId = $_SESSION['user_id'];
$studentId   = (int)($_GET['student_id'] ?? 0);
if (!$studentId) { echo json_encode(['error' => 'Missing student_id']); exit; }

// Student basic info
$stmt = $pdo->prepare("SELECT id, full_name, email, student_id, created_at FROM users WHERE id = ? AND role = 'student'");
$stmt->execute([$studentId]);
$student = $stmt->fetch();
if (!$student) { echo json_encode(['error' => 'Student not found']); exit; }

// Appointment summary
$stmt = $pdo->prepare("SELECT status, COUNT(*) AS c FROM appointments WHERE student_id = ? GROUP BY status");
$stmt->execute([$studentId]);
$apptCounts = [];
foreach ($stmt->fetchAll() as $r) { $apptCounts[$r['status']] = (int)$r['c']; }

$stmt = $pdo->prepare("SELECT preferred_date, preferred_time, session_type, status FROM appointments WHERE student_id = ? ORDER BY preferred_date DESC LIMIT 10");
$stmt->execute([$studentId]);
$recentAppointments = $stmt->fetchAll();

// PHQ-9 wellbeing summary (last 30 days)
// daily_checkins now stores PHQ-9 screenings: total_score (0-27) + severity band.
$stmt = $pdo->prepare(
    "SELECT AVG(total_score) AS avg_score,
            MIN(total_score) AS min_score,
            MAX(total_score) AS max_score,
            COUNT(*)         AS total
     FROM daily_checkins
     WHERE user_id = ?
       AND total_score IS NOT NULL
       AND checkin_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
);
$stmt->execute([$studentId]);
$phq = $stmt->fetch();

// Latest PHQ-9 severity + score, and recent trend (last 10 check-ins)
$stmt = $pdo->prepare(
    "SELECT total_score, severity, checkin_date
     FROM daily_checkins
     WHERE user_id = ? AND total_score IS NOT NULL
     ORDER BY checkin_date DESC, id DESC
     LIMIT 10"
);
$stmt->execute([$studentId]);
$phqRecent = $stmt->fetchAll();
$phqLatest = $phqRecent[0] ?? null;

// Derive average severity band from the 30-day average score
function phqBand($score) {
    if ($score === null) return 'No data';
    $s = (float)$score;
    if ($s <= 4)  return 'Minimal';
    if ($s <= 9)  return 'Mild';
    if ($s <= 14) return 'Moderate';
    if ($s <= 19) return 'Moderately severe';
    return 'Severe';
}
$phq['avg_band']     = isset($phq['avg_score']) ? phqBand($phq['avg_score']) : 'No data';
$phq['latest_score'] = $phqLatest['total_score'] ?? null;
$phq['latest_band']  = $phqLatest['severity']    ?? 'No data';
$phq['recent']       = array_reverse($phqRecent); // oldest→newest for a trend line

// Diary entry counts (never expose content — privacy)
$stmt = $pdo->prepare("SELECT COUNT(*) AS total, SUM(share_with_counselor) AS shared FROM diary_entries WHERE user_id = ?");
$stmt->execute([$studentId]);
$diaryStats = $stmt->fetch();

// Counselor's own private notes for this student — full history
$stmt = $pdo->prepare("SELECT notes, created_at FROM counselor_notes WHERE counselor_id = ? AND student_id = ? ORDER BY created_at DESC");
$stmt->execute([$counselorId, $studentId]);
$notes = $stmt->fetchAll();

// Risk alert history
$stmt = $pdo->prepare("SELECT severity, status, created_at FROM risk_alerts WHERE student_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$studentId]);
$riskAlerts = $stmt->fetchAll();

echo json_encode([
    'student'      => $student,
    'appointments' => ['counts' => $apptCounts, 'recent' => $recentAppointments],
    'phq'          => $phq,
    'diary'        => $diaryStats,
    'notes'        => $notes,
    'risk_alerts'  => $riskAlerts,
    'generated_at' => date('Y-m-d H:i'),
]);
?>