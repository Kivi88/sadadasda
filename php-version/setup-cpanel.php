<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KiWiPazari - cPanel Kurulum</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .success { background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px; color: #155724; }
        .error { background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24; }
        .info { background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb; border-radius: 4px; color: #0c5460; }
    </style>
</head>
<body>
    <h1>KiWiPazari - cPanel Kurulum</h1>
    
    <div class="info">
        <strong>Adım 1:</strong> cPanel'de MySQL Databases bölümünden veritabanı ve kullanıcı oluşturun.<br>
        <strong>Adım 2:</strong> Aşağıdaki forma bilgilerinizi girin.
    </div>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $host = trim($_POST['host']);
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $database = trim($_POST['database']);
        
        try {
            // Veritabanı bağlantısı test et
            $conn = new mysqli($host, $username, $password);
            
            if ($conn->connect_error) {
                throw new Exception("Bağlantı hatası: " . $conn->connect_error);
            }
            
            echo "<div class='success'>✅ Veritabanı bağlantısı başarılı!</div>";
            
            // Veritabanını oluştur
            $sql = "CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            if ($conn->query($sql) === TRUE) {
                echo "<div class='success'>✅ Veritabanı oluşturuldu</div>";
            } else {
                echo "<div class='error'>❌ Veritabanı oluşturma hatası: " . $conn->error . "</div>";
            }
            
            // Veritabanını seç
            $conn->select_db($database);
            
            // Tabloları oluştur
            $tables = [
                'apis' => "CREATE TABLE IF NOT EXISTS `apis` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(255) NOT NULL,
                    `url` VARCHAR(500) NOT NULL,
                    `api_key` VARCHAR(255) NOT NULL,
                    `is_active` BOOLEAN DEFAULT TRUE,
                    `last_sync` TIMESTAMP NULL,
                    `response_time` INT NULL,
                    `service_count` INT DEFAULT 0,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )",
                
                'services' => "CREATE TABLE IF NOT EXISTS `services` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `api_id` INT,
                    `external_id` VARCHAR(255) NOT NULL,
                    `name` VARCHAR(500) NOT NULL,
                    `platform` VARCHAR(100) NOT NULL,
                    `category` VARCHAR(100) NOT NULL,
                    `min_quantity` INT DEFAULT 1,
                    `max_quantity` INT DEFAULT 10000,
                    `is_active` BOOLEAN DEFAULT TRUE,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (`api_id`) REFERENCES `apis`(`id`) ON DELETE CASCADE
                )",
                
                'keys' => "CREATE TABLE IF NOT EXISTS `keys` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `key_value` VARCHAR(255) NOT NULL UNIQUE,
                    `service_id` INT,
                    `name` VARCHAR(255) NOT NULL,
                    `prefix` VARCHAR(50) DEFAULT 'KIWIPAZARI',
                    `max_amount` INT DEFAULT 1000,
                    `used_amount` INT DEFAULT 0,
                    `is_active` BOOLEAN DEFAULT TRUE,
                    `is_hidden` BOOLEAN DEFAULT FALSE,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE
                )",
                
                'orders' => "CREATE TABLE IF NOT EXISTS `orders` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `order_id` VARCHAR(255) NOT NULL UNIQUE,
                    `key_id` INT,
                    `service_id` INT,
                    `link` VARCHAR(500) NOT NULL,
                    `quantity` INT NOT NULL,
                    `status` VARCHAR(50) DEFAULT 'pending',
                    `external_order_id` VARCHAR(255) NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (`key_id`) REFERENCES `keys`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE
                )",
                
                'admin_users' => "CREATE TABLE IF NOT EXISTS `admin_users` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `username` VARCHAR(50) NOT NULL UNIQUE,
                    `password` VARCHAR(255) NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                
                'rate_limits' => "CREATE TABLE IF NOT EXISTS `rate_limits` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `ip` VARCHAR(45) NOT NULL,
                    `action` VARCHAR(50) NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_ip_action_time` (`ip`, `action`, `created_at`)
                )"
            ];
            
            foreach ($tables as $table => $sql) {
                if ($conn->query($sql) === TRUE) {
                    echo "<div class='success'>✅ Tablo '$table' oluşturuldu</div>";
                } else {
                    echo "<div class='error'>❌ Tablo '$table' hatası: " . $conn->error . "</div>";
                }
            }
            
            // Admin kullanıcısı ekle
            $admin_password = password_hash('ucFMkvJ5Tngq7QCN9Dl31edSWaPAmIRxfGwL62ih4U8jb0VosKHtO', PASSWORD_DEFAULT);
            $insert_admin = "INSERT IGNORE INTO `admin_users` (`username`, `password`) VALUES ('admin', '$admin_password')";
            if ($conn->query($insert_admin) === TRUE) {
                echo "<div class='success'>✅ Admin kullanıcısı oluşturuldu</div>";
            }
            
            // config.php dosyasını güncelle
            $config_content = "<?php
/**
 * KiWiPazari Configuration File
 * Veritabanı ve sistem ayarları
 */

// Veritabanı ayarları
define('DB_HOST', '$host');
define('DB_USER', '$username');
define('DB_PASS', '$password');
define('DB_NAME', '$database');

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
    private static \$instance = null;
    private \$connection;
    
    private function __construct() {
        \$this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if (\$this->connection->connect_error) {
            die(\"Veritabanı bağlantısı başarısız: \" . \$this->connection->connect_error);
        }
        
        \$this->connection->set_charset(\"utf8mb4\");
    }
    
    public static function getInstance() {
        if (self::\$instance === null) {
            self::\$instance = new Database();
        }
        return self::\$instance;
    }
    
    public function getConnection() {
        return \$this->connection;
    }
}

// Güvenlik fonksiyonları
function sanitize(\$data) {
    return htmlspecialchars(strip_tags(trim(\$data)));
}

function validateInput(\$data, \$type = 'string') {
    switch (\$type) {
        case 'int':
            return filter_var(\$data, FILTER_VALIDATE_INT);
        case 'email':
            return filter_var(\$data, FILTER_VALIDATE_EMAIL);
        case 'url':
            return filter_var(\$data, FILTER_VALIDATE_URL);
        default:
            return sanitize(\$data);
    }
}

function checkRateLimit(\$ip, \$action = 'general') {
    \$db = Database::getInstance()->getConnection();
    \$window = RATE_LIMIT_WINDOW;
    \$stmt = \$db->prepare(\"SELECT COUNT(*) as attempts FROM rate_limits WHERE ip = ? AND action = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)\");
    \$stmt->bind_param(\"ssi\", \$ip, \$action, \$window);
    \$stmt->execute();
    \$result = \$stmt->get_result();
    \$row = \$result->fetch_assoc();
    
    if (\$row['attempts'] >= RATE_LIMIT_ATTEMPTS) {
        return false;
    }
    
    // Deneme kaydını ekle
    \$stmt = \$db->prepare(\"INSERT INTO rate_limits (ip, action) VALUES (?, ?)\");
    \$stmt->bind_param(\"ss\", \$ip, \$action);
    \$stmt->execute();
    
    return true;
}

function isAdmin() {
    return isset(\$_SESSION['admin_logged_in']) && \$_SESSION['admin_logged_in'] === true;
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: admin-login.php');
        exit;
    }
}

// JSON response helper
function jsonResponse(\$data, \$status = 200) {
    http_response_code(\$status);
    header('Content-Type: application/json');
    echo json_encode(\$data);
    exit;
}
?>";
            
            if (file_put_contents('config.php', $config_content)) {
                echo "<div class='success'>✅ config.php dosyası güncellendi</div>";
            } else {
                echo "<div class='error'>❌ config.php dosyası güncellenemedi</div>";
            }
            
            $conn->close();
            
            echo "<div class='success'>
                <h3>🎉 Kurulum Tamamlandı!</h3>
                <p><strong>Admin Paneli:</strong> <a href='/kiwi-management-portal'>Yönetim Paneli</a></p>
                <p><strong>Ana Sayfa:</strong> <a href='index.php'>Ana Sayfa</a></p>
                <p><strong>Kullanıcı Adı:</strong> admin</p>
                <p><strong>Şifre:</strong> ucFMkvJ5Tngq7QCN9Dl31edSWaPAmIRxfGwL62ih4U8jb0VosKHtO</p>
            </div>";
            
            echo "<div class='error'>
                <h4>⚠️ Güvenlik Uyarısı</h4>
                <p>Kurulum tamamlandıktan sonra bu dosyayı (<strong>setup-cpanel.php</strong>) silin!</p>
            </div>";
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
        }
    } else {
    ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="host">Host:</label>
            <input type="text" id="host" name="host" value="localhost" required>
        </div>
        
        <div class="form-group">
            <label for="username">Kullanıcı Adı:</label>
            <input type="text" id="username" name="username" placeholder="smmkiwic_user" required>
        </div>
        
        <div class="form-group">
            <label for="password">Şifre:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label for="database">Veritabanı Adı:</label>
            <input type="text" id="database" name="database" placeholder="smmkiwic_kiwipazari" required>
        </div>
        
        <button type="submit">Kurulumu Başlat</button>
    </form>
    
    <?php } ?>
</body>
</html>