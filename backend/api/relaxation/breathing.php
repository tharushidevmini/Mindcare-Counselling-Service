<?php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');

$stmt = $pdo->query("SELECT * FROM relaxation_exercises WHERE type='breathing' AND is_active=1 ORDER BY id");
$exercises = $stmt->fetchAll();

foreach ($exercises as &$e) {
    $e['phases'] = json_decode($e['phases'], true);
}

echo json_encode(['exercises' => $exercises]);
?>