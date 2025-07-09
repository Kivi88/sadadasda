<?php
// Setup script for KiWiPazari
$error = '';
$success = '';
$step = 1;

// Database connection test
function testDatabaseConnection($host, $name, $user, $pass) {
    try {
        $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Create database tables
function createTables($pdo) {
    $tables = [
        'apis' => "CREATE TABLE IF NOT EXISTS apis (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            url VARCHAR(500) NOT NULL,
            api_key VARCHAR(255) NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        'services' => "CREATE TABLE IF NOT EXISTS services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            api_id INT NOT NULL,
            external_id VARCHAR(255) NOT NULL,
            name VARCHAR(255) NOT NULL,
            platform VARCHAR(100) NOT NULL,
            category VARCHAR(100) NOT NULL,
            min_quantity INT DEFAULT 1,
            max_quantity INT DEFAULT 10000,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (api_id) REFERENCES apis(id) ON DELETE CASCADE
        )",
        'keys' => "CREATE TABLE IF NOT EXISTS `keys` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            key_value VARCHAR(255) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            service_id INT,
            max_amount INT DEFAULT 1000,
            used_amount INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            is_hidden BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
        )",
        'orders' => "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id VARCHAR(255) NOT NULL UNIQUE,
            key_id INT,
            service_id INT,
            link VARCHAR(500) NOT NULL,
            quantity INT NOT NULL,
            status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
            external_order_id VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (key_id) REFERENCES `keys`(id) ON DELETE SET NULL,
            FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
        )"
    ];
    
    foreach ($tables as $name => $sql) {
        $pdo->exec($sql);
    }
    
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step']) && $_POST['step'] == '1') {
        $dbHost = trim($_POST['db_host'] ?? '');
        $dbName = trim($_POST['db_name'] ?? '');
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass = trim($_POST['db_pass'] ?? '');
        
        if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
            $error = 'Veritabanı bilgileri eksik';
        } else {
            if (testDatabaseConnection($dbHost, $dbName, $dbUser, $dbPass)) {
                // Create config file
                $configContent = "<?php
// Database configuration
define('DB_HOST', '$dbHost');
define('DB_NAME', '$dbName');
define('DB_USER', '$dbUser');
define('DB_PASS', '$dbPass');

// Admin credentials
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123');

// Security settings
define('SITE_URL', 'https://yoursite.com');
define('SESSION_TIMEOUT', 3600); // 1 hour

// Error reporting (set to 0 in production)
error_reporting(0);
ini_set('display_errors', 0);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection function
function getDatabase() {
    static \$pdo = null;
    
    if (\$pdo === null) {
        try {
            \$dsn = \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\";
            \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException \$e) {
            die(\"Database connection failed: \" . \$e->getMessage());
        }
    }
    
    return \$pdo;
}

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset(\$_SESSION['admin_logged_in']) && \$_SESSION['admin_logged_in'] === true;
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
    if (!isset(\$_SESSION['csrf_token'])) {
        \$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return \$_SESSION['csrf_token'];
}

function validateCSRFToken(\$token) {
    return isset(\$_SESSION['csrf_token']) && hash_equals(\$_SESSION['csrf_token'], \$token);
}

// Sanitize input
function sanitizeInput(\$input) {
    return htmlspecialchars(trim(\$input), ENT_QUOTES, 'UTF-8');
}

// Rate limiting (simple implementation)
function checkRateLimit(\$action, \$limit = 5, \$window = 900) {
    \$ip = \$_SERVER['REMOTE_ADDR'];
    \$key = \$action . '_' . \$ip;
    
    if (!isset(\$_SESSION['rate_limit'])) {
        \$_SESSION['rate_limit'] = [];
    }
    
    \$now = time();
    
    if (!isset(\$_SESSION['rate_limit'][\$key])) {
        \$_SESSION['rate_limit'][\$key] = ['count' => 0, 'reset' => \$now + \$window];
    }
    
    \$data = \$_SESSION['rate_limit'][\$key];
    
    if (\$now > \$data['reset']) {
        \$_SESSION['rate_limit'][\$key] = ['count' => 0, 'reset' => \$now + \$window];
        \$data = \$_SESSION['rate_limit'][\$key];
    }
    
    if (\$data['count'] >= \$limit) {
        return false;
    }
    
    \$_SESSION['rate_limit'][\$key]['count']++;
    return true;
}
?>";
                
                if (file_put_contents('config.php', $configContent)) {
                    try {
                        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        ]);
                        
                        createTables($pdo);
                        $step = 2;
                        $success = 'Veritabanı bağlantısı başarılı! Tablolar oluşturuldu.';
                    } catch (Exception $e) {
                        $error = 'Tablo oluşturma hatası: ' . $e->getMessage();
                    }
                } else {
                    $error = 'Config dosyası oluşturulamadı. Dosya izinlerini kontrol edin.';
                }
            } else {
                $error = 'Veritabanı bağlantısı başarısız. Bilgileri kontrol edin.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KiWiPazari Kurulum</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1e1e1e 0%, #2d2d2d 100%);
            min-height: 100vh;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .container {
            max-width: 500px;
            width: 100%;
        }
        
        .setup-card {
            background: rgba(45, 45, 45, 0.9);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        
        .logo {
            font-size: 2rem;
            font-weight: bold;
            color: #4A90E2;
            margin-bottom: 1rem;
        }
        
        .step-indicator {
            margin-bottom: 2rem;
            color: #bbb;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #ddd;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4A90E2;
            background: rgba(255, 255, 255, 0.15);
        }
        
        .form-group input::placeholder {
            color: #888;
        }
        
        .btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #4A90E2 0%, #357abd 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(74, 144, 226, 0.3);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .alert-error {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid rgba(231, 76, 60, 0.3);
            color: #ff6b6b;
        }
        
        .alert-success {
            background: rgba(46, 204, 113, 0.2);
            border: 1px solid rgba(46, 204, 113, 0.3);
            color: #51cf66;
        }
        
        .success-message {
            text-align: center;
            padding: 2rem;
        }
        
        .success-message h2 {
            color: #51cf66;
            margin-bottom: 1rem;
        }
        
        .success-message p {
            color: #bbb;
            margin-bottom: 1rem;
        }
        
        .credentials {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            font-family: monospace;
        }
        
        .actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #ddd;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        @media (max-width: 768px) {
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="setup-card">
            <div class="logo">KiWiPazari</div>
            
            <?php if ($step == 1): ?>
                <div class="step-indicator">Adım 1/2: Veritabanı Ayarları</div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="step" value="1">
                    
                    <div class="form-group">
                        <label for="db_host">Veritabanı Sunucusu</label>
                        <input type="text" id="db_host" name="db_host" value="localhost" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_name">Veritabanı Adı</label>
                        <input type="text" id="db_name" name="db_name" placeholder="kiwipazari" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_user">Veritabanı Kullanıcısı</label>
                        <input type="text" id="db_user" name="db_user" placeholder="root" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_pass">Veritabanı Şifresi</label>
                        <input type="password" id="db_pass" name="db_pass" placeholder="Şifre (boş bırakabilirsiniz)">
                    </div>
                    
                    <button type="submit" class="btn">Devam Et</button>
                </form>
                
            <?php elseif ($step == 2): ?>
                <div class="success-message">
                    <h2>✅ Kurulum Tamamlandı!</h2>
                    <p>KiWiPazari başarıyla kuruldu. Sistemi kullanmaya başlayabilirsiniz.</p>
                    
                    <div class="credentials">
                        <strong>Admin Giriş Bilgileri:</strong><br>
                        Kullanıcı: admin<br>
                        Şifre: admin123
                    </div>
                    
                    <div class="actions">
                        <a href="index.php" class="btn btn-secondary">Ana Sayfa</a>
                        <a href="admin-login.php" class="btn">Admin Paneli</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>