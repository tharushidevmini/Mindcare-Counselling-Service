<?php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['counselor', 'admin']);

$id     = (int)($_POST['id']     ?? 0);
$action = (int)($_POST['action'] ?? 0);

if (!$id) { echo json_encode(['error' => 'Invalid post']); exit; }

if ($action === -1) {
    // Delete
    $stmt = $pdo->prepare("UPDATE forum_posts SET is_removed = 1 WHERE id = ?");
    $stmt->execute([$id]);
} elseif ($action === 1) {
    // Approve
    $stmt = $pdo->prepare("UPDATE forum_posts SET is_approved = 1 WHERE id = ?");
    $stmt->execute([$id]);
} else {
    // Remove from public
    $stmt = $pdo->prepare("UPDATE forum_posts SET is_approved = 0 WHERE id = ?");
    $stmt->execute([$id]);
}

echo json_encode(['success' => true]);
?>