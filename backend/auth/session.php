<?php
// FILE: backend/auth/session.php
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['logged_in' => false]);
    exit;
}

// Re-check the database rather than trusting the cached session values —
// if the account was deleted or disabled since login, reflect that now.
$stmt = $pdo->prepare("SELECT role, is_active, full_name, photo_url FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || !$user['is_active']) {
    session_unset();
    session_destroy();
    echo json_encode(['logged_in' => false]);
    exit;
}

$_SESSION['role']      = $user['role'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['photo_url'] = $user['photo_url'];

echo json_encode([
    'logged_in' => true,
    'user_id'   => $_SESSION['user_id'],
    'role'      => $user['role'],
    'full_name' => $user['full_name'],
    'photo_url' => $user['photo_url'],
]);
?>