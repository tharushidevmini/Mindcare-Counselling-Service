<?php
// FILE: backend/api/counselor/delete_checkin.php
// Deletes one PHQ-9 check-in record. Nothing auto-deletes on its own —
// this is only for a counselor/admin who explicitly wants to remove one entry.
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['counselor', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); exit; }

$checkin_id = (int)($_POST['checkin_id'] ?? 0);
if (!$checkin_id) { echo json_encode(['error' => 'Missing checkin_id']); exit; }

$stmt = $pdo->prepare("DELETE FROM daily_checkins WHERE id = ?");
$stmt->execute([$checkin_id]);
echo json_encode(['success' => true]);
?>