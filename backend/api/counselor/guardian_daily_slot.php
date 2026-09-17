<?php
// FILE: backend/api/counselor/guardian_daily_slot.php
// Lets a counselor view/set THEIR OWN fixed daily time reserved for
// guardian emergency access. This is deliberately separate from
// "Emergency Reserved Slots" (block_slot.php + assign_emergency.php),
// which stays exactly as it was — for one-off, alert-triggered walk-in
// sessions the counselor assigns to a specific at-risk student.
//
// This file is instead for the ONE permanent daily rule: whatever time is
// set here blocks that same slot every single day, forever, and is the
// ONLY slot guardians are ever shown when booking an emergency session.
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['counselor', 'admin']);

$cid    = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

if ($action === 'get') {
    $stmt = $pdo->prepare("SELECT slot_time FROM guardian_daily_slots WHERE counselor_id = ?");
    $stmt->execute([$cid]);
    $row = $stmt->fetch();
    echo json_encode(['slot_time' => $row ? $row['slot_time'] : null]);
    exit;
}

if ($action === 'save') {
    $time = trim($_POST['slot_time'] ?? '');
    $allowed = ['9:00 AM','9:30 AM','10:00 AM','10:30 AM','11:00 AM','2:00 PM','2:30 PM','3:00 PM','3:30 PM'];
    if (!in_array($time, $allowed, true)) { echo json_encode(['error' => 'Please pick a valid time.']); exit; }

    // counselor_id has a UNIQUE key, so this always updates the counselor's
    // single existing row rather than creating duplicates.
    $stmt = $pdo->prepare("INSERT INTO guardian_daily_slots (counselor_id, slot_time) VALUES (?, ?)
                            ON DUPLICATE KEY UPDATE slot_time = VALUES(slot_time)");
    $stmt->execute([$cid, $time]);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Unknown action']);
?>