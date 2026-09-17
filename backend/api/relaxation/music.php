<?php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');

$category = $_GET['category'] ?? '';

if ($category) {
    $stmt = $pdo->prepare("SELECT * FROM music_tracks WHERE category=? AND is_active=1 ORDER BY id");
    $stmt->execute([$category]);
} else {
    $stmt = $pdo->query("SELECT * FROM music_tracks WHERE is_active=1 ORDER BY category, id");
}

echo json_encode(['tracks' => $stmt->fetchAll()]);
?>