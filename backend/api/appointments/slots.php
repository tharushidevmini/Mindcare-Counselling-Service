<?php
// FILE: backend/api/appointments/slots.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');

$cid  = (int)($_GET['counselor_id'] ?? 0);
$date = trim($_GET['date'] ?? '') ?: date('Y-m-d');
$all  = ['9:00 AM','9:30 AM','10:00 AM','10:30 AM','11:00 AM','2:00 PM','2:30 PM','3:00 PM','3:30 PM'];

// day_of_week: PHP's 'w' gives 0 (Sun) - 6 (Sat), matching how we store recurring_slots
$dayOfWeek = (int)date('w', strtotime($date));

// Real student bookings (not cancelled)
$stmt = $pdo->prepare("SELECT preferred_time FROM appointments WHERE counselor_id = ? AND preferred_date = ? AND status != 'cancelled'");
$stmt->execute([$cid, $date]);
$booked = array_column($stmt->fetchAll(), 'preferred_time');

// Counselor's own persistent blocked slots (personal reasons, one-off emergency reserves)
$stmt2 = $pdo->prepare("SELECT block_time, reason FROM blocked_slots WHERE counselor_id = ? AND block_date = ?");
$stmt2->execute([$cid, $date]);
$blocked = [];
foreach ($stmt2->fetchAll() as $b) { $blocked[$b['block_time']] = $b['reason']; }

// Recurring weekly slots for this day of the week (e.g. every Monday 9:00 AM for a specific student)
$stmt3 = $pdo->prepare("SELECT slot_time FROM recurring_slots WHERE counselor_id = ? AND day_of_week = ? AND is_active = 1");
$stmt3->execute([$cid, $dayOfWeek]);
$recurring = array_column($stmt3->fetchAll(), 'slot_time');

// This counselor's permanent daily guardian-emergency reserved time (if
// they've set one) — blocks the same slot on EVERY date, not just
// pre-seeded ones, so students never see it as bookable on any future date.
$stmt4 = $pdo->prepare("SELECT slot_time FROM guardian_daily_slots WHERE counselor_id = ?");
$stmt4->execute([$cid]);
$guardianSlot = $stmt4->fetchColumn();

$slots = array_map(function($s) use ($booked, $blocked, $recurring, $guardianSlot) {
    if (in_array($s, $recurring)) {
        return ['time' => $s, 'taken' => true, 'slot_type' => 'recurring'];
    }
    if ($guardianSlot && $s === $guardianSlot) {
        return ['time' => $s, 'taken' => true, 'slot_type' => 'guardian_emergency', 'reason' => 'Reserved — Guardian Emergency Access'];
    }
    if (isset($blocked[$s])) {
        return ['time' => $s, 'taken' => true, 'slot_type' => 'blocked', 'reason' => $blocked[$s]];
    }
    if (in_array($s, $booked)) {
        return ['time' => $s, 'taken' => true, 'slot_type' => 'student'];
    }
    return ['time' => $s, 'taken' => false, 'slot_type' => 'available'];
}, $all);

echo json_encode(['slots' => $slots, 'date' => $date]);
?>