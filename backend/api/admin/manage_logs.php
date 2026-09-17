<?php
// FILE: backend/api/admin/manage_logs.php
// action=pin        -> toggle pin on one log (id)
// action=delete     -> delete one log (id)
// action=delete_all -> delete every log EXCEPT pinned ones
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }

$action = $_POST['action'] ?? '';

if ($action === 'delete_all') {
    $n = $pdo->exec("DELETE FROM system_logs WHERE is_pinned = 0");   // pinned ones stay
    echo json_encode(['success' => true, 'deleted' => $n]);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) { echo json_encode(['error' => 'Missing id']); exit; }

if ($action === 'delete') {
    $st = $pdo->prepare("DELETE FROM system_logs WHERE id = ?");
    $st->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'pin') {
    $st = $pdo->prepare("UPDATE system_logs SET is_pinned = 1 - is_pinned WHERE id = ?");
    $st->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Unknown action']);
?>