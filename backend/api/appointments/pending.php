<?php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');

requireRole(['counselor', 'admin']);

$cid = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT a.*, u.full_name AS student_name, u.student_id, u.email AS student_email,
           gs.guardian_phone AS guardian_email
    FROM appointments a
    JOIN users u ON a.student_id = u.id
    LEFT JOIN guardian_sessions gs ON a.guardian_id = gs.id
    WHERE a.counselor_id = ? AND a.status IN ('pending','accepted','postponed')
    ORDER BY (a.status = 'pending') DESC, a.preferred_date, a.preferred_time
");
$stmt->execute([$cid]);
$apts = $stmt->fetchAll();

$pendingOnly = array_filter($apts, fn($a) => $a['status'] === 'pending');

$t = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE counselor_id=? AND preferred_date=? AND status='accepted'");
$t->execute([$cid, date('Y-m-d')]);

$m = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE counselor_id=? AND MONTH(preferred_date)=? AND status='completed'");
$m->execute([$cid, date('n')]);

echo json_encode([
    'appointments' => $apts,
    'pending'      => count($pendingOnly),
    'today'        => $t->fetchColumn(),
    'month'        => $m->fetchColumn()
]);
?>