<?php
/**
 * KiWiPazari Configuration File
 * Veritabanı ve sistem ayarları
 */

// Veritabanı ayarları
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kiwipazari');

// Sistem ayarları
define('SITE_NAME', 'KiWiPazari');
define('ADMIN_PATH', '/kiwi-management-portal');
define('DEFAULT_KEY_PREFIX', 'KIWIPAZARI');

// Güvenlik ayarları
define('SESSION_TIMEOUT', 3600); // 1 saat
define('RATE_LIMIT_ATTEMPTS', 15);
define('RATE_LIMIT_WINDOW', 900); // 15 dakika

// Hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Oturum başlat
session_start();

// Veritabanı bağlantısı
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->connection->connect_error) {
            die("Veritabanı bağlantısı başarısız: " . $this->connection->connect_error);
        }
        
        $this->connection->set_charset("utf8mb4");
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

// Güvenlik fonksiyonları
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function validateInput($data, $type = 'string') {
    switch ($type) {
        case 'int':
            return filter_var($data, FILTER_VALIDATE_INT);
        case 'email':
            return filter_var($data, FILTER_VALIDATE_EMAIL);
        case 'url':
            return filter_var($data, FILTER_VALIDATE_URL);
        default:
            return sanitize($data);
    }
}

function checkRateLimit($ip, $action = 'general') {
    $db = Database::getInstance()->getConnection();
    $window = RATE_LIMIT_WINDOW; // Sabit değeri değişkene atayalım
    $stmt = $db->prepare("SELECT COUNT(*) as attempts FROM rate_limits WHERE ip = ? AND action = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $stmt->bind_param("ssi", $ip, $action, $window);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['attempts'] >= RATE_LIMIT_ATTEMPTS) {
        return false;
    }
    
    // Deneme kaydını ekle
    $stmt = $db->prepare("INSERT INTO rate_limits (ip, action) VALUES (?, ?)");
    $stmt->bind_param("ss", $ip, $action);
    $stmt->execute();
    
    return true;
}

function isAdmin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: admin-login.php');
        exit;
    }
}

// JSON response helper
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Rate limiting tablosunu oluştur
$db = Database::getInstance()->getConnection();
$rate_limit_table = "CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    action VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_action_time (ip, action, created_at)
)";
$db->query($rate_limit_table);
?>