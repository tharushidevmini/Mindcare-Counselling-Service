<?php
// FILE: backend/api/resources/list.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireLogin();

// Public/student view only sees published resources; counselor/admin sees everything (to manage drafts too)
$role = $_SESSION['role'] ?? '';
if (in_array($role, ['counselor', 'admin'])) {
    $stmt = $pdo->query("SELECT r.*, u.full_name AS uploaded_by_name FROM resources r
                          JOIN users u ON u.id = r.uploaded_by ORDER BY r.created_at DESC");
} else {
    $stmt = $pdo->query("SELECT * FROM resources WHERE is_published = 1 ORDER BY created_at DESC");
}
echo json_encode(['resources' => $stmt->fetchAll()]);
?>