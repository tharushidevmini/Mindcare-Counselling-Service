<?php
// FILE: backend/api/guardian/book.php
require_once '../../config/db.php';
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['guardian_verified']) || !$_SESSION['guardian_verified']) {
    http_response_code(403);
    echo json_encode(['error' => 'Guardian session not verified']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }

$sessionId   = $_SESSION['guardian_session_id'] ?? 0;
$reason      = trim($_POST['reason'] ?? '');
$blockId     = (int)($_POST['block_id'] ?? 0);
$counselorId = (int)($_POST['counselor_id'] ?? 0);
$date        = trim($_POST['date'] ?? '');
if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}
$mode = trim($_POST['mode'] ?? 'physical');
if (!in_array($mode, ['physical', 'online'])) { $mode = 'physical'; }

$gs = $pdo->prepare("SELECT * FROM guardian_sessions WHERE id = ? AND is_verified = 1");
$gs->execute([$sessionId]);
$session = $gs->fetch();
if (!$session) { echo json_encode(['error'=>'Invalid guardian session']); exit; }

// The frontend still sends "block_id" as a UI selection flag, but it's no
// longer a real database row — the actual reserved time now comes from
// this counselor's permanent guardian_daily_slots entry, re-derived and
// re-validated here server-side (never trust a time from the client).
if (!$counselorId || !$blockId) { echo json_encode(['error'=>'Please pick a counselor and a slot']); exit; }

$counsel = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role='counselor' AND is_active=1");
$counsel->execute([$counselorId]);
$counselor = $counsel->fetch();
if (!$counselor) { echo json_encode(['error'=>'Counselor not found']); exit; }

$cfg = $pdo->prepare("SELECT slot_time FROM guardian_daily_slots WHERE counselor_id = ?");
$cfg->execute([$counselorId]);
$config = $cfg->fetch();
if (!$config) { echo json_encode(['error'=>'That slot is no longer available. Please pick another.']); exit; }
$time = $config['slot_time'];

// Make sure nobody booked this exact date+time in the moment between the
// guardian loading the page and confirming.
$already = $pdo->prepare("SELECT id FROM appointments WHERE counselor_id = ? AND preferred_date = ? AND preferred_time = ? AND status != 'cancelled'");
$already->execute([$counselorId, $date, $time]);
if ($already->fetch()) { echo json_encode(['error'=>'That slot is no longer available. Please pick another.']); exit; }

$stmt = $pdo->prepare("
    INSERT INTO appointments (student_id, counselor_id, guardian_id, session_type, preferred_date, preferred_time, notes, status, booking_source)
    VALUES (?,?,?,?,?,?,?,'pending','guardian')
");
$stmt->execute([$session['student_id'], $counselor['id'], $sessionId, $mode, $date, $time, 'GUARDIAN EMERGENCY BOOKING: ' . $reason]);
$newAppointmentId = $pdo->lastInsertId();

$log = $pdo->prepare("INSERT INTO system_logs (action, ip_address) VALUES (?,?)");
$log->execute(['guardian_emergency_booking counselor:' . $counselorId . ' time:' . $time, $_SERVER['REMOTE_ADDR'] ?? '']);

// Remember this booking in the session itself, so if the guardian refreshes
// or navigates away and back, the page can restore "your booking" without
// needing anything stored client-side.
$_SESSION['guardian_last_appointment_id'] = $newAppointmentId;

echo json_encode(['success' => true, 'appointment_id' => $newAppointmentId, 'date' => $date, 'time' => $time]);
?>