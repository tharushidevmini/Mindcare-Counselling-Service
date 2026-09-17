<?php
// FILE: backend/api/guardian/cancel_booking.php
// Lets a guardian cancel the emergency appointment they just booked, in
// the same verified session. The counselor is notified via the normal
// appointment status change — same pattern as a student cancelling.
require_once '../../config/db.php';
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['guardian_verified']) || !$_SESSION['guardian_verified']) {
    http_response_code(403);
    echo json_encode(['error' => 'Guardian session not verified']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }

$appointmentId = (int)($_POST['appointment_id'] ?? 0);
$reason        = trim($_POST['reason'] ?? '');
$sessionId     = $_SESSION['guardian_session_id'] ?? 0;

if (!$appointmentId) { echo json_encode(['error' => 'Missing appointment ID']); exit; }

// Only allow cancelling a booking THIS guardian session actually created.
$stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ? AND guardian_id = ?");
$stmt->execute([$appointmentId, $sessionId]);
$apt = $stmt->fetch();

if (!$apt) {
    echo json_encode(['error' => 'Booking not found, or it was not made in this session.']); exit;
}
if ($apt['status'] === 'cancelled') {
    echo json_encode(['error' => 'This booking is already cancelled.']); exit;
}

$upd = $pdo->prepare("UPDATE appointments SET status = 'cancelled', reschedule_reason = ? WHERE id = ?");
$upd->execute([$reason ?: 'Cancelled by guardian', $appointmentId]);

// The reserved slot is no longer a consumable blocked_slots row — it's
// derived dynamically from this counselor's guardian_daily_slots time
// plus the appointments table, so cancelling here already frees it up
// automatically with nothing further to restore.

if (($_SESSION['guardian_last_appointment_id'] ?? 0) == $appointmentId) {
    unset($_SESSION['guardian_last_appointment_id']);
}

$log = $pdo->prepare("INSERT INTO system_logs (action, ip_address) VALUES (?,?)");
$log->execute(["guardian_cancelled_booking appointment:$appointmentId", $_SERVER['REMOTE_ADDR'] ?? '']);

echo json_encode(['success' => true, 'message' => 'Booking cancelled. The counselor has been notified.']);
?>