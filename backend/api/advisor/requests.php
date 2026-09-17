<?php
// FILE: backend/api/advisor/requests.php
// CSP MODULE — Advisor sees ONLY token + accommodation type. No clinical data ever passes through here.
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['learning_advisor']);

$stmt = $pdo->prepare("
    SELECT ar.id, ar.token, ar.accommodation_type, u.full_name AS forwarded_by,
           ar.status, ar.reject_reason, DATE_FORMAT(ar.created_at, '%d %b %Y') AS created_at
    FROM academic_relief_requests ar
    JOIN users u ON u.id = ar.forwarded_by_id
    ORDER BY ar.created_at DESC
");
$stmt->execute();
echo json_encode(['requests' => $stmt->fetchAll()]);
?>