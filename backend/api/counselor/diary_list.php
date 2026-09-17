<?php
// FILE: backend/api/counselor/diary_list.php
// Lists the logged-in counselor's OWN notes (scoped by user_id).
// Pinned notes first, then newest first. Includes a short decrypted
// preview so the card can show the first line even when there's no title.
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['counselor','learning_advisor','admin']);

$uid = $_SESSION['user_id'];

$stmt = $pdo->prepare(
    "SELECT id, title, content, mood_emoji, entry_date, is_pinned
     FROM diary_entries
     WHERE user_id = ?
     ORDER BY is_pinned DESC, entry_date DESC, id DESC"
);
$stmt->execute([$uid]);
$rows = $stmt->fetchAll();

$out = [];
foreach ($rows as $r) {
    // Decrypt just enough for a short preview
    $preview = '';
    $parts = explode('::', $r['content'], 2);
    if (count($parts) === 2) {
        $iv  = base64_decode($parts[0]);
        $dec = openssl_decrypt($parts[1], 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);
        if ($dec !== false) {
            $dec = trim(preg_replace('/\s+/', ' ', $dec));
            $preview = mb_substr($dec, 0, 100);
            if (mb_strlen($dec) > 100) $preview .= '…';
        }
    }
    $out[] = [
        'id'         => $r['id'],
        'title'      => $r['title'],
        'preview'    => $preview,
        'mood_emoji' => $r['mood_emoji'],
        'entry_date' => $r['entry_date'],
        'is_pinned'  => (int)$r['is_pinned'],
    ];
}

echo json_encode(['entries' => $out]);
?>