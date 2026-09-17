<?php
// FILE: backend/api/alerts/list.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['counselor', 'admin']);

$stmt = $pdo->prepare("
    SELECT ra.id, ra.keywords_found, ra.severity, ra.status, ra.diary_entry_id,
           u.full_name AS student_name, u.email AS student_email, u.student_id,
           d.share_with_counselor AS shared,
           DATE_FORMAT(ra.created_at, '%d %b %Y %H:%i') AS created_at
    FROM risk_alerts ra
    JOIN users u ON u.id = ra.student_id
    LEFT JOIN diary_entries d ON d.id = ra.diary_entry_id
    WHERE ra.status = 'open'
    ORDER BY FIELD(ra.severity, 'critical','high','medium'), ra.created_at DESC
");
$stmt->execute();
echo json_encode(['alerts' => $stmt->fetchAll()]);
?>