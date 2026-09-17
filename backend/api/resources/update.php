<?php
// FILE: backend/api/resources/update.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['counselor', 'admin']);

$id       = (int)($_POST['id'] ?? 0);
$title    = trim($_POST['title'] ?? '');
$desc     = trim($_POST['description'] ?? '');
$filePath = trim($_POST['file_path'] ?? '');
$externalUrl = trim($_POST['external_url'] ?? '');

if (!$id || !$title) { echo json_encode(['error' => 'Title is required']); exit; }
if ($externalUrl && !preg_match('#^https?://#i', $externalUrl)) { echo json_encode(['error' => 'Article link must start with http:// or https://']); exit; }

$stmt = $pdo->prepare("SELECT * FROM resources WHERE id = ?");
$stmt->execute([$id]);
$r = $stmt->fetch();
if (!$r) { echo json_encode(['error' => 'Not found']); exit; }

if ($_SESSION['role'] === 'counselor' && (int)$r['uploaded_by'] !== (int)$_SESSION['user_id']) {
    echo json_encode(['error' => 'You can only edit resources you uploaded']); exit;
}

// External links only make sense for articles, not videos.
if ($r['resource_type'] === 'video') { $externalUrl = ''; }

$stmt = $pdo->prepare("UPDATE resources SET title=?, description=?, file_path=?, external_url=? WHERE id=?");
$stmt->execute([$title, $desc, $filePath, $externalUrl ?: null, $id]);
echo json_encode(['success' => true]);
?>