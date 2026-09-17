<?php
// FILE: backend/api/resources/upload.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['counselor', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }

$title       = trim($_POST['title']         ?? '');
$type        = trim($_POST['resource_type'] ?? 'article');
$desc        = trim($_POST['description']   ?? '');
$filePath    = trim($_POST['file_path']     ?? '');   // cover image URL for articles, YouTube link for videos
$externalUrl = trim($_POST['external_url']  ?? '');   // optional — article's real source page, if any
$isPublished = (int)($_POST['is_published'] ?? 1);

if (!$title) { echo json_encode(['error'=>'Title is required']); exit; }
if (!in_array($type, ['article','audio','video'])) { echo json_encode(['error'=>'Invalid type']); exit; }
if ($type === 'video' && !$filePath) { echo json_encode(['error'=>'Please paste a YouTube link']); exit; }
if ($externalUrl && !preg_match('#^https?://#i', $externalUrl)) { echo json_encode(['error'=>'Article link must start with http:// or https://']); exit; }
// External links only make sense for articles, not for videos (which already link out to YouTube).
if ($type === 'video') { $externalUrl = ''; }

$stmt = $pdo->prepare("INSERT INTO resources (uploaded_by, title, description, resource_type, file_path, external_url, is_published) VALUES (?,?,?,?,?,?,?)");
$stmt->execute([$_SESSION['user_id'], $title, $desc, $type, $filePath, $externalUrl ?: null, $isPublished]);

echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
?>