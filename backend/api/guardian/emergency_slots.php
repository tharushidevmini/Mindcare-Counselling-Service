<?php
// FILE: backend/api/guardian/emergency_slots.php
// Returns this counselor's ONE permanent daily emergency-reserved slot
// (set by the counselor themselves — see backend/api/counselor/guardian_daily_slot.php),
// for the given date, but only if that specific date's instance hasn't
// already been booked. This is now a fixed daily rule per counselor, not
// finite pre-seeded blocked_slots rows, so it works for any future date
// automatically and indefinitely — no re-seeding ever needed.
//
// Deliberately does NOT read from blocked_slots — that table is only for
// the counselor's separate, ad-hoc "Emergency Reserved Slots" (one-off,
// alert-triggered walk-in assignments), which guardians should never see.
require_once '../../config/db.php';
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['guardian_verified']) || !$_SESSION['guardian_verified']) {
    http_response_code(403);
    echo json_encode(['error' => 'Guardian session not verified']); exit;
}

$counselorId = (int)($_GET['counselor_id'] ?? 0);
if (!$counselorId) { echo json_encode(['error' => 'Missing counselor_id']); exit; }

$date = trim($_GET['date'] ?? '');
if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d'); // fall back to today if missing/malformed
}

// This counselor's own fixed daily reserved time (configured from their dashboard)
$cfg = $pdo->prepare("SELECT slot_time FROM guardian_daily_slots WHERE counselor_id = ?");
$cfg->execute([$counselorId]);
$config = $cfg->fetch();

if (!$config) {
    // This counselor hasn't set up a guardian emergency slot yet
    echo json_encode(['slots' => [], 'date' => $date]);
    exit;
}

$slotTime = $config['slot_time'];

// Already booked by someone for this specific date?
$taken = $pdo->prepare("SELECT id FROM appointments WHERE counselor_id = ? AND preferred_date = ? AND preferred_time = ? AND status != 'cancelled'");
$taken->execute([$counselorId, $date, $slotTime]);

if ($taken->fetch()) {
    echo json_encode(['slots' => [], 'date' => $date]);
    exit;
}

// 'id' is a fixed placeholder (not a real row id) — there's only ever
// at most one slot per counselor now, so nothing else needs it to be unique.
echo json_encode([
    'slots' => [['id' => 1, 'block_time' => $slotTime, 'reason' => 'Reserved — Guardian Emergency Access']],
    'date'  => $date,
]);
?>