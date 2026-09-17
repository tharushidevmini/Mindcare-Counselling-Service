<?php
// FILE: backend/api/alerts/count.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['counselor', 'admin']);

$stmt = $pdo->query("SELECT COUNT(*) AS c FROM risk_alerts WHERE status = 'open'");
$row  = $stmt->fetch();
echo json_encode(['count' => (int)$row['c']]);
?>