<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$result = [
    'step' => 'start',
    'config_local_exists' => file_exists(__DIR__ . '/config.local.php'),
    'db_connection' => null,
    'error' => null,
    'tables' => []
];

// Load config.local.php - wrap in try/catch for runtime errors
$DB_HOST = 'localhost';
$DB_NAME = 'your_database_name';
$DB_USER = 'your_database_user';
$DB_PASS = 'your_database_password';

if ($result['config_local_exists']) {
    try {
        require __DIR__ . '/config.local.php';
        $result['step'] = 'config_loaded';
    } catch (Throwable $e) {
        $result['error'] = 'config.local.php error: ' . $e->getMessage();
        echo json_encode($result, JSON_PRETTY_PRINT);
        exit;
    }
}

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $result['db_connection'] = 'success';
    $result['tables'] = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $result['step'] = 'done';
} catch (Exception $e) {
    $result['db_connection'] = 'failed';
    $result['error'] = $e->getMessage();
    $result['step'] = 'done';
}

echo json_encode($result, JSON_PRETTY_PRINT);
