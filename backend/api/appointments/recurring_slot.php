<?php
// FILE: backend/api/appointments/recurring_slot.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['counselor', 'admin']);

$cid    = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'list') {
    $stmt = $pdo->prepare("SELECT r.*, u.full_name AS student_name
                            FROM recurring_slots r
                            JOIN users u ON u.id = r.student_id
                            WHERE r.counselor_id = ? AND r.is_active = 1
                            ORDER BY r.day_of_week, r.slot_time");
    $stmt->execute([$cid]);
    echo json_encode(['recurring' => $stmt->fetchAll()]);
    exit;
}

if ($action === 'add') {
    $studentId = (int)($_POST['student_id']  ?? 0);
    $day       = (int)($_POST['day_of_week'] ?? -1);
    $time      = trim($_POST['slot_time']    ?? '');
    $reason    = trim($_POST['reason'] ?? 'Ongoing weekly support');

    if (!$studentId || $day < 0 || $day > 6 || !$time) {
        echo json_encode(['error' => 'Student, day and time are required']); exit;
    }

    // Prevent duplicate recurring bookings on the same counselor/day/time
    $check = $pdo->prepare("SELECT id FROM recurring_slots WHERE counselor_id = ? AND day_of_week = ? AND slot_time = ? AND is_active = 1");
    $check->execute([$cid, $day, $time]);
    if ($check->fetch()) { echo json_encode(['error' => 'This weekly slot is already reserved']); exit; }

    $stmt = $pdo->prepare("INSERT INTO recurring_slots (counselor_id, student_id, day_of_week, slot_time, reason) VALUES (?,?,?,?,?)");
    $stmt->execute([$cid, $studentId, $day, $time, $reason]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'remove') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE recurring_slots SET is_active = 0 WHERE id = ? AND counselor_id = ?");
    $stmt->execute([$id, $cid]);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Unknown action']);
?>