<?php
// FILE: backend/api/appointments/block_slot.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['counselor', 'admin']);

$cid    = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'list') {
    $date = trim($_GET['date'] ?? '') ?: date('Y-m-d');
    $stmt = $pdo->prepare("SELECT * FROM blocked_slots WHERE counselor_id = ? AND block_date = ? ORDER BY block_time");
    $stmt->execute([$cid, $date]);
    echo json_encode(['blocks' => $stmt->fetchAll()]);
    exit;
}

if ($action === 'add') {
    $date   = trim($_POST['block_date'] ?? '');
    $time   = trim($_POST['block_time'] ?? '');
    $reason = trim($_POST['reason'] ?? 'Reserved');
    if (!$date || !$time) { echo json_encode(['error' => 'Date and time required']); exit; }

    $stmt = $pdo->prepare("INSERT INTO blocked_slots (counselor_id, block_date, block_time, reason) VALUES (?,?,?,?)
                            ON DUPLICATE KEY UPDATE reason = VALUES(reason)");
    $stmt->execute([$cid, $date, $time, $reason]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'remove') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM blocked_slots WHERE id = ? AND counselor_id = ?");
    $stmt->execute([$id, $cid]);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Unknown action']);
?>