<?php
/**
 * Database Configuration
 * Fill in your MySQL database details below
 */

// Load environment variables from .env file (if available)
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Database credentials from environment variables with secure fallback
// SECURITY: Never hardcode credentials - use environment variables
define('DB_HOST', $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: '');
define('DB_USER', $_ENV['DB_USER'] ?? getenv('DB_USER') ?: '');
define('DB_PASS', $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Error reporting - SECURITY: Disable error display in production
// Only show errors in development (when APP_ENV is 'development')
$app_env = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production';

if ($app_env === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0); // SECURITY: Hide errors in production
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/php-errors.log');
}

/**
 * Get database connection
 * @return PDO|null
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        return null;
    }
}
?>

