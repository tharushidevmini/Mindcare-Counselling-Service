<?php
// FILE: backend/api/counselor/diary_save.php
// Counselor's OWN private reflection journal. Entries are encrypted and
// visible only to the counselor who wrote them (scoped by user_id).
// No sharing, no risk-scan — this is a private space, just like a student's diary is theirs.
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['counselor','learning_advisor','admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }

$uid     = $_SESSION['user_id'];
$content = trim($_POST['content'] ?? '');
$title   = trim($_POST['title']   ?? '');
$mood    = trim($_POST['mood_emoji'] ?? '');

if (!$content) { echo json_encode(['error'=>'Content cannot be empty']); exit; }

// AES-256-CBC encryption — same scheme as the student diary
$iv        = openssl_random_pseudo_bytes(16);
$encrypted = openssl_encrypt($content, 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);
$stored    = base64_encode($iv) . '::' . $encrypted;

// share_with_counselor forced to 0 — a counselor's private journal is never shared/scanned
$stmt = $pdo->prepare("INSERT INTO diary_entries (user_id,title,content,mood_emoji,share_with_counselor,entry_date) VALUES (?,?,?,?,0,?)");
$stmt->execute([$uid, $title, $stored, $mood, date('Y-m-d')]);

echo json_encode(['success'=>true, 'id'=>$pdo->lastInsertId()]);
?>