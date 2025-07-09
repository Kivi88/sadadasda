<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'kiwipazari');
define('DB_USER', 'root');
define('DB_PASS', '');

// Admin credentials
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123');

// Security settings
define('SITE_URL', 'https://yoursite.com');
define('SESSION_TIMEOUT', 3600); // 1 hour

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection function
function getDatabase() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Redirect to login if not logged in
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: admin-login.php');
        exit;
    }
}

// Logout function
function adminLogout() {
    session_destroy();
    header('Location: admin-login.php');
    exit;
}

// CSRF protection
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Rate limiting (simple implementation)
function checkRateLimit($action, $limit = 5, $window = 900) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = $action . '_' . $ip;
    
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    
    $now = time();
    
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = ['count' => 0, 'reset' => $now + $window];
    }
    
    $data = $_SESSION['rate_limit'][$key];
    
    if ($now > $data['reset']) {
        $_SESSION['rate_limit'][$key] = ['count' => 0, 'reset' => $now + $window];
        $data = $_SESSION['rate_limit'][$key];
    }
    
    if ($data['count'] >= $limit) {
        return false;
    }
    
    $_SESSION['rate_limit'][$key]['count']++;
    return true;
}
?>