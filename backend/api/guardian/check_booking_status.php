<?php
// FILE: backend/api/guardian/check_booking_status.php
// Lets a guardian's page poll for their own booking's current status
// (pending → accepted), so approval shows up live in the same session —
// no email needed, just an in-system status check.
require_once '../../config/db.php';
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['guardian_verified']) || !$_SESSION['guardian_verified']) {
    http_response_code(403);
    echo json_encode(['error' => 'Guardian session not verified']); exit;
}

$appointmentId = (int)($_GET['appointment_id'] ?? 0);
if (!$appointmentId) {
    $appointmentId = (int)($_SESSION['guardian_last_appointment_id'] ?? 0);
}
$sessionId = $_SESSION['guardian_session_id'] ?? 0;

if (!$appointmentId) { echo json_encode(['success' => false]); exit; }

$stmt = $pdo->prepare("SELECT status, preferred_date, preferred_time, new_date, new_time FROM appointments WHERE id = ? AND guardian_id = ?");
$stmt->execute([$appointmentId, $sessionId]);
$apt = $stmt->fetch();

if (!$apt) { echo json_encode(['error' => 'Booking not found']); exit; }

echo json_encode([
    'success'        => true,
    'appointment_id' => $appointmentId,
    'status'         => $apt['status'],
    'date'           => $apt['preferred_date'],
    'time'           => $apt['preferred_time'],
    'new_date'       => $apt['new_date'],
    'new_time'       => $apt['new_time'],
]);
?>