<?php
// FILE: backend/api/admin/download_backup.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
requireRole(['admin']);

$backupDir = dirname(__DIR__, 3) . '/database/backups/';
$file = basename($_GET['file'] ?? '');   // basename() strips any path traversal

// Only allow simple .sql filenames that really exist in the backups folder
if ($file === '' || !preg_match('/^[\w\-.]+\.sql$/', $file)) {
    http_response_code(400); echo 'Invalid file'; exit;
}
$path = $backupDir . $file;
if (!is_file($path)) { http_response_code(404); echo 'File not found'; exit; }

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
?>