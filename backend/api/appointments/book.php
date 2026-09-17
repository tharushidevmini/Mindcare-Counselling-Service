<?php
// FILE: backend/api/appointments/book.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['student']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }

$cid   = (int)($_POST['counselor_id']   ?? 0);
$time  = trim($_POST['preferred_time']  ?? '');
$type  = trim($_POST['session_type']    ?? 'physical');
$notes = trim($_POST['notes']           ?? '');
$date  = trim($_POST['preferred_date']  ?? '') ?: date('Y-m-d');

if (!$cid || !$time) { echo json_encode(['error'=>'Counselor and time required']); exit; }
if ($date < date('Y-m-d')) { echo json_encode(['error'=>'Cannot book a date in the past']); exit; }

// 1. Check if already booked
$chkApt = $pdo->prepare("SELECT id FROM appointments WHERE counselor_id = ? AND preferred_date = ? AND preferred_time = ? AND status != 'cancelled'");
$chkApt->execute([$cid, $date, $time]);
if ($chkApt->fetch()) {
    echo json_encode(['error'=>'That time slot has already been booked. Please choose another slot.']); exit;
}

// 2. Check if counselor has blocked this slot
$chkBlock = $pdo->prepare("SELECT id FROM blocked_slots WHERE counselor_id = ? AND block_date = ? AND block_time = ?");
$chkBlock->execute([$cid, $date, $time]);
if ($chkBlock->fetch()) {
    echo json_encode(['error'=>'That time slot is reserved or unavailable. Please choose another slot.']); exit;
}

// 3. Check recurring weekly slots
$dayOfWeek = (int)date('w', strtotime($date));
$chkRec = $pdo->prepare("SELECT id FROM recurring_slots WHERE counselor_id = ? AND day_of_week = ? AND slot_time = ? AND is_active = 1");
$chkRec->execute([$cid, $dayOfWeek, $time]);
if ($chkRec->fetch()) {
    echo json_encode(['error'=>'That time slot is reserved weekly. Please choose another slot.']); exit;
}

// 4. Check guardian daily emergency slot
$chkGrd = $pdo->prepare("SELECT id FROM guardian_daily_slots WHERE counselor_id = ? AND slot_time = ?");
$chkGrd->execute([$cid, $time]);
if ($chkGrd->fetch()) {
    echo json_encode(['error'=>'That time slot is reserved for guardian emergency access. Please choose another slot.']); exit;
}

$stmt = $pdo->prepare("INSERT INTO appointments (student_id,counselor_id,session_type,preferred_date,preferred_time,notes,status,booking_source) VALUES (?,?,?,?,?,?,'pending','student')");
$stmt->execute([$_SESSION['user_id'], $cid, $type, $date, $time, $notes]);
echo json_encode(['success'=>true]);
?>