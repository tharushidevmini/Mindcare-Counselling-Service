<?php
// FILE: backend/api/admin/list_backups.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['admin']);

$backupDir = dirname(__DIR__, 3) . '/database/backups/';
$files = [];
if (is_dir($backupDir)) {
    foreach (glob($backupDir . '*.sql') as $f) {
        $files[] = [
            'name'     => basename($f),
            'size'     => filesize($f),
            'modified' => date('Y-m-d H:i:s', filemtime($f)),
            'mtime'    => filemtime($f),
        ];
    }
    usort($files, fn($a, $b) => $b['mtime'] <=> $a['mtime']);  // newest first
}
echo json_encode(['backups' => $files]);
?>