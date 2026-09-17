<?php
// FILE: backend/api/admin/logs.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['admin']);

$stmt = $pdo->query("
    SELECT l.id, l.action, l.ip_address, l.is_pinned,
           DATE_FORMAT(l.created_at, '%Y-%m-%d %H:%i') AS created_at,
           u.full_name, u.role
    FROM system_logs l
    LEFT JOIN users u ON u.id = l.user_id
    ORDER BY l.is_pinned DESC, l.created_at DESC
    LIMIT 100
");
echo json_encode(['logs' => $stmt->fetchAll()]);
?>