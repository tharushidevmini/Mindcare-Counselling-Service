<?php
// FILE: backend/api/diary/toggle_share.php
// Lets a student change the "share with counselor" status of their OWN diary
// entries AFTER saving them. Works for one entry, several selected entries,
// or all of them. When an entry becomes shared, it is risk-scanned (same as
// on save) so alerts still work.
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
    $upd = $pdo->prepare("UPDATE diary_entries SET share_with_counselor = ? WHERE user_id = ?");
    $upd->execute([$share, $uid]);
    $targetIds = null; // means "all" — fetch below for scanning
} else {
    // Only the ids given, and only if they belong to this student
    $ids = array_values(array_filter(array_map('intval', explode(',', $idsRaw))));
    if (empty($ids)) { echo json_encode(['error'=>'No valid entries']); exit; }
    $place = implode(',', array_fill(0, count($ids), '?'));
    $upd = $pdo->prepare("UPDATE diary_entries SET share_with_counselor = ? WHERE user_id = ? AND id IN ($place)");
    $upd->execute(array_merge([$share, $uid], $ids));
    $targetIds = $ids;
}

// If we just SHARED entries, run the risk keyword scan on them (like save.php)
if ($share === 1) {
    // Which entries to scan?
    if ($targetIds === null) {
        $sel = $pdo->prepare("SELECT id, content FROM diary_entries WHERE user_id = ?");
        $sel->execute([$uid]);
    } else {
        $place = implode(',', array_fill(0, count($targetIds), '?'));
        $sel = $pdo->prepare("SELECT id, content FROM diary_entries WHERE user_id = ? AND id IN ($place)");
        $sel->execute(array_merge([$uid], $targetIds));
    }
    $rows = $sel->fetchAll();

    $kw = $pdo->query("SELECT * FROM keyword_rules WHERE is_active = 1");
    $keywords = $kw->fetchAll();

    foreach ($rows as $r) {
        // decrypt the entry to scan its text
        $parts = explode('::', $r['content'], 2);
        if (count($parts) !== 2) continue;
        $iv  = base64_decode($parts[0]);
        $txt = openssl_decrypt($parts[1], 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);
        if ($txt === false) continue;

        $found = []; $maxSev = null;
        foreach ($keywords as $k) {
            if (stripos($txt, $k['keyword']) !== false) {
                $found[] = $k['keyword'];
                if ($k['severity'] === 'critical') $maxSev = 'critical';
                elseif ($k['severity'] === 'high' && $maxSev !== 'critical') $maxSev = 'high';
                elseif ($k['severity'] === 'medium' && !$maxSev) $maxSev = 'medium';
            }
        }
        if ($maxSev) {
            // avoid duplicate alerts for the same entry
            $chk = $pdo->prepare("SELECT id FROM risk_alerts WHERE diary_entry_id = ?");
            $chk->execute([$r['id']]);
            if (!$chk->fetch()) {
                $al = $pdo->prepare("INSERT INTO risk_alerts (student_id,diary_entry_id,keywords_found,severity) VALUES (?,?,?,?)");
                $al->execute([$uid, $r['id'], implode(',', $found), $maxSev]);
            }
        }
    }
}

echo json_encode(['success'=>true, 'share'=>$share]);
?>