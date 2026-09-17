<?php
// FILE: backend/api/diary/save.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['student']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }

$uid     = $_SESSION['user_id'];
$content = trim($_POST['content'] ?? '');
$title   = trim($_POST['title']   ?? '');
$share         = (int)($_POST['share_with_counselor'] ?? 0);
$shareGuardian = (int)($_POST['share_with_guardian'] ?? 0);
$moodEmoji     = trim($_POST['mood_emoji'] ?? '');

if (!$content) { echo json_encode(['error'=>'Content cannot be empty']); exit; }

// AES-256-CBC encryption
$iv        = openssl_random_pseudo_bytes(16);
$encrypted = openssl_encrypt($content, 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);
$stored    = base64_encode($iv) . '::' . $encrypted;

$stmt = $pdo->prepare("INSERT INTO diary_entries (user_id,title,content,mood_emoji,share_with_counselor,share_with_guardian,entry_date) VALUES (?,?,?,?,?,?,?)");
$stmt->execute([$uid, $title, $stored, $moodEmoji ?: null, $share, $shareGuardian, date('Y-m-d')]);
$entryId = $pdo->lastInsertId();

// Risk keyword scan — ALWAYS runs, even for private entries, because a
// suicide / self-harm risk overrides privacy. Only the alert (student +
// severity + matched words) reaches the counselor so they can reach out;
// the diary text itself stays encrypted and is not exposed unless shared.
$kw = $pdo->prepare("SELECT * FROM keyword_rules WHERE is_active = 1");
$kw->execute();
$keywords = $kw->fetchAll();
$found = []; $maxSev = null;
foreach ($keywords as $k) {
    if (stripos($content, $k['keyword']) !== false) {
        $found[] = $k['keyword'];
        if ($k['severity'] === 'critical') $maxSev = 'critical';
        elseif ($k['severity'] === 'high' && $maxSev !== 'critical') $maxSev = 'high';
        elseif ($k['severity'] === 'medium' && !$maxSev) $maxSev = 'medium';
    }
}
if ($maxSev) {
    $al = $pdo->prepare("INSERT INTO risk_alerts (student_id,diary_entry_id,keywords_found,severity) VALUES (?,?,?,?)");
    $al->execute([$uid, $entryId, implode(',', $found), $maxSev]);
}

echo json_encode(['success'=>true]);
?>