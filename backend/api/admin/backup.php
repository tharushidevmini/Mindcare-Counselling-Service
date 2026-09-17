<?php
// FILE: backend/api/admin/backup.php
// Pure-PHP database backup — no mysqldump / exec needed (works on any XAMPP).
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }

$backupDir = dirname(__DIR__, 3) . '/database/backups/';
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
$filename = 'mindcare_' . date('Y-m-d_H-i-s') . '.sql';
$filepath = $backupDir . $filename;

try {
    $out  = "-- MindCare database backup\n";
    $out .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $out .= "-- Database: " . DB_NAME . "\n\n";
    $out .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        // --- structure ---
        $create    = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $createSql = $create['Create Table'] ?? $create['Create View'] ?? '';
        $out .= "-- ----------------------------\n";
        $out .= "-- Table: $table\n";
        $out .= "-- ----------------------------\n";
        $out .= "DROP TABLE IF EXISTS `$table`;\n";
        $out .= $createSql . ";\n\n";

        // --- data ---
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            foreach ($rows as $row) {
                $cols = array_map(fn($c) => "`$c`", array_keys($row));
                $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote($v), array_values($row));
                $out .= "INSERT INTO `$table` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
            }
            $out .= "\n";
        }
    }

    $out .= "SET FOREIGN_KEY_CHECKS=1;\n";

    $bytes = file_put_contents($filepath, $out);
    if ($bytes === false) { echo json_encode(['error' => 'Could not write file. Check permissions on database/backups/']); exit; }

    $log = $pdo->prepare("INSERT INTO system_logs (user_id, action, ip_address) VALUES (?,?,?)");
    $log->execute([$_SESSION['user_id'], "database_backup: $filename", $_SERVER['REMOTE_ADDR'] ?? '']);

    echo json_encode(['success' => true, 'filename' => $filename, 'size' => $bytes]);
} catch (Exception $e) {
    if (is_file($filepath) && filesize($filepath) === 0) @unlink($filepath);
    echo json_encode(['error' => 'Backup failed: ' . $e->getMessage()]);
}
?>