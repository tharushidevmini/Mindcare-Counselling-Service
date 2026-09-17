<?php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');

$stmt = $pdo->query("SELECT * FROM meditation_types WHERE is_active=1 ORDER BY id");
$types = $stmt->fetchAll();

foreach ($types as &$t) {
    $t['steps']    = json_decode($t['steps'],    true);
    $t['guidance'] = json_decode($t['guidance'], true);
}

echo json_encode(['types' => $types]);
?>