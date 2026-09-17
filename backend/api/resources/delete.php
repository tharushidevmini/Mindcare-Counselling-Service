<?php
// FILE: backend/api/resources/delete.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['counselor', 'admin']);

$id = (int)($_POST['id'] ?? 0);
if (!$id) { echo json_encode(['error' => 'Missing id']); exit; }

$stmt = $pdo->prepare("SELECT * FROM resources WHERE id = ?");
$stmt->execute([$id]);
$r = $stmt->fetch();
if (!$r) { echo json_encode(['error' => 'Not found']); exit; }

// Counselors may only delete their own uploads; admins may delete any
if ($_SESSION['role'] === 'counselor' && (int)$r['uploaded_by'] !== (int)$_SESSION['user_id']) {
    echo json_encode(['error' => 'You can only delete resources you uploaded']); exit;
}

$del = $pdo->prepare("DELETE FROM resources WHERE id = ?");
$del->execute([$id]);
echo json_encode(['success' => true]);
?>