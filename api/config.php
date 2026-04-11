<?php
header('Content-Type: application/json');

// CORS Configuration - Restrict to specific domains in production
$allowed_origins = [
    'https://teasandcs.com',
    'https://www.teasandcs.com',
    'http://localhost:3000', // Development only
    'http://localhost:8080'  // Development only
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} elseif (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false) {
    // Allow localhost for development
    header("Access-Control-Allow-Origin: $origin");
}

header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// Load environment variables from .env file (if available)
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Database credentials from environment variables with fallback
$DB_HOST = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
$DB_NAME = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: '';
$DB_USER = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: '';
$DB_PASS = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';

// Admin token from environment variables
$ADMIN_TOKEN = $_ENV['ADMIN_TOKEN'] ?? getenv('ADMIN_TOKEN') ?: '';

// Security: Validate that credentials are not empty in production
if (empty($DB_NAME) || empty($DB_USER) || empty($ADMIN_TOKEN)) {
    error_log('SECURITY WARNING: Database credentials or admin token not properly configured');
    // In production, don't reveal which credential is missing
    http_response_code(500);
    echo json_encode(['error' => 'Configuration error']);
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'DB connection failed',
        'details' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    exit;
}

function require_admin() {
    global $ADMIN_TOKEN;
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (!preg_match('/Bearer\s+(.*)/i', $auth, $m) || $m[1] !== $ADMIN_TOKEN) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}
?>


