<?php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['admin']);

$stmt = $pdo->query("SELECT id,full_name,email,role,is_active,created_at FROM users ORDER BY role,full_name");
$users = $stmt->fetchAll();

$counts = ['student'=>0,'counselor'=>0,'learning_advisor'=>0,'admin'=>0,'disabled'=>0];
foreach ($users as $u) {
    if (isset($counts[$u['role']])) $counts[$u['role']]++;
    if (!$u['is_active']) $counts['disabled']++;
}
echo json_encode(['users'=>$users, 'counts'=>$counts]);
?>