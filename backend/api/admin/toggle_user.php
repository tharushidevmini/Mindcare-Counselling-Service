<?php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }

$userId   = (int)($_POST['user_id']   ?? 0);
$isActive = (int)($_POST['is_active'] ?? 0);
if (!$userId) { echo json_encode(['error'=>'User ID required']); exit; }
if ($userId === (int)$_SESSION['user_id'] && !$isActive) {
    echo json_encode(['error'=>'You cannot disable your own admin account']); exit;
}

$stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
$stmt->execute([$isActive, $userId]);

echo json_encode(['success' => true]);
?>