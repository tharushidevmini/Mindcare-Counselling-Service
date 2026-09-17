<?php
// FILE: backend/api/alerts/review.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['counselor', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }

$alertId = (int)($_POST['alert_id'] ?? 0);
if (!$alertId) { echo json_encode(['error'=>'Alert ID required']); exit; }

$stmt = $pdo->prepare("UPDATE risk_alerts SET status='reviewed', reviewed_by=?, reviewed_at=NOW() WHERE id=?");
$stmt->execute([$_SESSION['user_id'], $alertId]);
echo json_encode(['success' => true]);
?>
