<?php
// FILE: backend/api/diary/toggle_share_guardian.php
// Lets a student change the "share with guardian" status of their OWN diary
// entries AFTER saving them. Works for one entry, several selected entries,
// or all of them. Mirrors toggle_share.php (counselor version) but writes
// to share_with_guardian instead, and never touches the counselor flag.
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['student']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }

$uid   = $_SESSION['user_id'];
$share = (int)($_POST['share'] ?? 0) === 1 ? 1 : 0;

// ids: comma-separated list of entry ids, OR the word "all"
$idsRaw = trim($_POST['ids'] ?? '');
if ($idsRaw === '') { echo json_encode(['error'=>'No entries selected']); exit; }

if (strtolower($idsRaw) === 'all') {
    // Toggle every entry this student owns
    $upd = $pdo->prepare("UPDATE diary_entries SET share_with_guardian = ? WHERE user_id = ?");
    $upd->execute([$share, $uid]);
} else {
    // Only the ids given, and only if they belong to this student
    $ids = array_values(array_filter(array_map('intval', explode(',', $idsRaw))));
    if (empty($ids)) { echo json_encode(['error'=>'No valid entries']); exit; }
    $place = implode(',', array_fill(0, count($ids), '?'));
    $upd = $pdo->prepare("UPDATE diary_entries SET share_with_guardian = ? WHERE user_id = ? AND id IN ($place)");
    $upd->execute(array_merge([$share, $uid], $ids));
}

echo json_encode(['success'=>true, 'share'=>$share]);
?>