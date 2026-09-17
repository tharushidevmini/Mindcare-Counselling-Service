<?php
// FILE: backend/api/resources/public_list.php
// Public, read-only list of PUBLISHED resources for the Mental Health Library.
// No login required — the library page is public. Only published rows are ever
// returned here, so drafts stay private.
require_once '../../config/db.php';
header('Content-Type: application/json');

$stmt = $pdo->query(
    "SELECT id, title, description, resource_type, file_path, external_url, is_published, created_at
     FROM resources
     WHERE is_published = 1
     ORDER BY created_at DESC"
);
echo json_encode(['resources' => $stmt->fetchAll()]);
?>