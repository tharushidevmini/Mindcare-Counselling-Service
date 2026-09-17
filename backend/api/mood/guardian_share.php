<?php
// FILE: backend/api/mood/guardian_share.php
// GET  -> returns the student's current preference
// POST -> student toggles whether their guardian can see the general status
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['student']);

$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enabled = ($_POST['enabled'] ?? '1') === '1' ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE users SET share_status_with_guardian = ? WHERE id = ?");
    $stmt->execute([$enabled, $uid]);
    echo json_encode(['success' => true, 'enabled' => $enabled]);
    exit;
}

$stmt = $pdo->prepare("SELECT share_status_with_guardian FROM users WHERE id = ?");
$stmt->execute([$uid]);
$row = $stmt->fetch();
echo json_encode(['enabled' => $row ? (int)$row['share_status_with_guardian'] : 1]);
?>