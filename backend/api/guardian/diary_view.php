<?php
// FILE: backend/api/guardian/diary_view.php
// Decrypts a single diary entry for a verified guardian — ONLY if the entry
// belongs to their linked student AND that student turned sharing ON for it.
// Double-checked here even though diary_shared.php already filters the list,
// so a guardian can never fetch an entry id directly and bypass the toggle.
require_once '../../config/db.php';
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['guardian_verified']) || !$_SESSION['guardian_verified']) {
    http_response_code(403);
    echo json_encode(['error' => 'Guardian session not verified']); exit;
}

$studentId = $_SESSION['guardian_student_id'] ?? 0;
$id        = (int)($_GET['id'] ?? 0);

if (!$id) { echo json_encode(['error' => 'Missing entry id']); exit; }

$stmt = $pdo->prepare("SELECT * FROM diary_entries WHERE id = ?");
$stmt->execute([$id]);
$entry = $stmt->fetch();

if (!$entry) { echo json_encode(['error' => 'Entry not found']); exit; }

$belongsToLinkedStudent = ((int)$entry['user_id'] === (int)$studentId);
$isShared               = ((int)$entry['share_with_guardian'] === 1);

if (!$belongsToLinkedStudent || !$isShared) {
    echo json_encode(['error' => 'This entry has not been shared with you']); exit;
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