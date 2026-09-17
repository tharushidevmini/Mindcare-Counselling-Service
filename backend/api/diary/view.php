<?php
// FILE: backend/api/diary/view.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireLogin();

$uid = $_SESSION['user_id'] ?? 0;
$id  = (int)($_GET['id'] ?? 0);

if (!$id) { echo json_encode(['error' => 'Missing entry id']); exit; }

// A student may only view their OWN entries.
// A counselor/admin may view an entry only if the student shared it with the counselor.
$stmt = $pdo->prepare("SELECT * FROM diary_entries WHERE id = ?");
$stmt->execute([$id]);
$entry = $stmt->fetch();

if (!$entry) { echo json_encode(['error' => 'Entry not found']); exit; }

$role = $_SESSION['role'] ?? '';
$isOwner    = ((int)$entry['user_id'] === (int)$uid);
$isReviewer = in_array($role, ['counselor', 'admin']) && (int)$entry['share_with_counselor'] === 1;

if (!$isOwner && !$isReviewer) {
    echo json_encode(['error' => 'Not authorized to view this entry']); exit;
}

// Decrypt: stored as base64(iv)::ciphertext
$parts = explode('::', $entry['content'], 2);
if (count($parts) !== 2) { echo json_encode(['error' => 'Could not read entry']); exit; }

$iv        = base64_decode($parts[0]);
$decrypted = openssl_decrypt($parts[1], 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);

echo json_encode([
    'content'    => $decrypted !== false ? $decrypted : '(Could not decrypt this entry)',
    'title'      => $entry['title'],
    'mood_emoji' => $entry['mood_emoji'],
    'entry_date' => $entry['entry_date'],
]);
?>