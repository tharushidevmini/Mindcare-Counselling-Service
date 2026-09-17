<?php
// FILE: backend/api/guardian/upcoming_slots.php
// Returns a counselor's next few reserved emergency slots (any date from
// today onward) — used to help the guardian see when slots actually exist,
// instead of guessing dates one at a time.
require_once '../../config/db.php';
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['guardian_verified']) || !$_SESSION['guardian_verified']) {
    http_response_code(403);
    echo json_encode(['error' => 'Guardian session not verified']); exit;
}

$counselorId = (int)($_GET['counselor_id'] ?? 0);
if (!$counselorId) { echo json_encode(['error' => 'Missing counselor_id']); exit; }

$stmt = $pdo->prepare(
    "SELECT id, block_date, block_time, reason
     FROM blocked_slots
     WHERE counselor_id = ? AND block_date >= CURDATE()
     ORDER BY block_date ASC, STR_TO_DATE(block_time, '%h:%i %p') ASC
     LIMIT 5"
);
$stmt->execute([$counselorId]);
$slots = $stmt->fetchAll();

echo json_encode(['slots' => $slots]);
?>