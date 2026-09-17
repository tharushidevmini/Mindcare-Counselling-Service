<?php
$secretsFile = __DIR__ . '/secrets.php';
if (!file_exists($secretsFile)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Missing database configuration', 'error' => 'Missing backend/config/secrets.php']);
    exit;
}
require_once $secretsFile;

$host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
$user = defined('DB_USER') ? DB_USER : 'root';
$pass = defined('DB_PASS') ? DB_PASS : '';
$name = defined('DB_NAME') ? DB_NAME : 'mindcare_db';
$primaryPort = defined('DB_PORT') ? (string)DB_PORT : '3306';

// Auto-detect active MariaDB/MySQL port: primary port first (3306 on Linux VM), with fallback to 3307
$ports = array_values(array_unique([$primaryPort, '3306', '3307']));

$pdo = null;
$lastException = null;

foreach ($ports as $port) {
    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        break; // Successfully connected
    } catch (PDOException $e) {
        $lastException = $e;
    }
}

if (!$pdo) {
    error_log("DB Error: " . ($lastException ? $lastException->getMessage() : "Could not connect to database"));
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed', 'error' => 'Database connection failed']);
    exit;
}
?>