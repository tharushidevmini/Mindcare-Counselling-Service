<?php
// FILE: backend/api/counselor/diary_view.php
// Decrypts a single entry. A counselor may ONLY view their own entries —
// strict owner check means one counselor can never read another user's journal.
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['counselor','learning_advisor','admin']);

$uid = $_SESSION['user_id'];
$id  = (int)($_GET['id'] ?? 0);

if (!$id) { echo json_encode(['error' => 'Missing entry id']); exit; }

$stmt = $pdo->prepare("SELECT * FROM diary_entries WHERE id = ?");
$stmt->execute([$id]);
$entry = $stmt->fetch();

if (!$entry) { echo json_encode(['error' => 'Entry not found']); exit; }

// Owner-only: the entry's user_id must match the logged-in counselor
if ((int)$entry['user_id'] !== (int)$uid) {
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