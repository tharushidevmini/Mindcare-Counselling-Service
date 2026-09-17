<?php
// FILE: backend/api/advisor/update.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['learning_advisor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }

$id     = (int)($_POST['request_id'] ?? 0);
$status = trim($_POST['status']      ?? '');
$reason = trim($_POST['reason']      ?? '');
if (!$id || !in_array($status, ['approved','rejected'])) { echo json_encode(['error'=>'Invalid data']); exit; }
if ($status === 'rejected' && !$reason) { echo json_encode(['error'=>'Please give a reason for rejecting this request']); exit; }

$stmt = $pdo->prepare("UPDATE academic_relief_requests SET status = ?, reject_reason = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ?");
$stmt->execute([$status, $status === 'rejected' ? $reason : null, $_SESSION['user_id'], $id]);
echo json_encode(['success' => true]);
?>