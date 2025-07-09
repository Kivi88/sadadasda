<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'kiwipazari_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Security settings
define('ADMIN_PASSWORD_HASH', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); // "password"
define('SESSION_TIMEOUT', 3600); // 1 hour
define('CSRF_TOKEN_EXPIRE', 1800); // 30 minutes
define('RATE_LIMIT_ATTEMPTS', 15);
define('RATE_LIMIT_WINDOW', 900); // 15 minutes

// Application settings
define('SITE_NAME', 'KIWIPAZARI');
define('ADMIN_URL', '/kiwi-management-portal');
define('DEFAULT_KEY_PREFIX', 'KIWIPAZARI');

// Initialize database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Start session with security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
ini_set('session.use_strict_mode', 1);
session_start();

// Security functions
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 
        time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_EXPIRE) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && 
           isset($_SESSION['csrf_token_time']) && 
           hash_equals($_SESSION['csrf_token'], $token) && 
           time() - $_SESSION['csrf_token_time'] <= CSRF_TOKEN_EXPIRE;
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function checkRateLimit($identifier, $attempts = RATE_LIMIT_ATTEMPTS, $window = RATE_LIMIT_WINDOW) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT attempts, last_attempt FROM rate_limits WHERE identifier = ? AND last_attempt > ?");
    $stmt->execute([$identifier, time() - $window]);
    $result = $stmt->fetch();
    
    if ($result && $result['attempts'] >= $attempts) {
        return false;
    }
    
    $stmt = $pdo->prepare("INSERT INTO rate_limits (identifier, attempts, last_attempt) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = ?");
    $stmt->execute([$identifier, time(), time()]);
    
    return true;
}

function isAdmin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && 
           isset($_SESSION['admin_login_time']) && time() - $_SESSION['admin_login_time'] < SESSION_TIMEOUT;
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . ADMIN_URL);
        exit;
    }
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function generateRandomKey($prefix = DEFAULT_KEY_PREFIX) {
    return $prefix . '-' . strtoupper(bin2hex(random_bytes(16)));
}

function generateOrderId() {
    return 'ORD' . date('Ymd') . strtoupper(bin2hex(random_bytes(4)));
}
?>