<?php
// FILE: backend/api/advisor/email_history.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['learning_advisor']);

$stmt = $pdo->prepare("
    SELECT ael.id, ael.subject, ael.message, u.full_name AS counselor_name,
           DATE_FORMAT(ael.sent_at, '%d %b %Y, %h:%i %p') AS sent_at
    FROM advisor_email_log ael
    JOIN users u ON u.id = ael.counselor_id
    ORDER BY ael.sent_at DESC
    LIMIT 100
");
$stmt->execute();
echo json_encode(['emails' => $stmt->fetchAll()]);
?>