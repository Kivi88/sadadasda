<?php
// Database setup script
$setup_complete = false;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_name = $_POST['db_name'] ?? 'kiwipazari_db';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    $admin_password = $_POST['admin_password'] ?? 'admin123';
    
    try {
        // Create database connection
        $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
        $pdo->exec("USE `$db_name`");
        
        // Create tables
        $tables = [
            "CREATE TABLE IF NOT EXISTS `apis` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `url` text NOT NULL,
                `api_key` text NOT NULL,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            "CREATE TABLE IF NOT EXISTS `services` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `api_id` int(11) NOT NULL,
                `external_id` varchar(255) NOT NULL,
                `name` text NOT NULL,
                `category` varchar(255) DEFAULT NULL,
                `platform` varchar(255) DEFAULT NULL,
                `min_amount` int(11) DEFAULT 1,
                `max_amount` int(11) DEFAULT 10000,
                `price` decimal(10,4) DEFAULT 0.0000,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `api_id` (`api_id`),
                KEY `external_id` (`external_id`),
                CONSTRAINT `services_ibfk_1` FOREIGN KEY (`api_id`) REFERENCES `apis` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            "CREATE TABLE IF NOT EXISTS `keys` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `key_value` varchar(255) NOT NULL UNIQUE,
                `key_name` varchar(255) NOT NULL,
                `service_id` int(11) NOT NULL,
                `max_amount` int(11) NOT NULL,
                `used_amount` int(11) DEFAULT 0,
                `is_active` tinyint(1) DEFAULT 1,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `service_id` (`service_id`),
                KEY `key_value` (`key_value`),
                CONSTRAINT `keys_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            "CREATE TABLE IF NOT EXISTS `orders` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `order_id` varchar(255) NOT NULL UNIQUE,
                `key_id` int(11) NOT NULL,
                `service_id` int(11) NOT NULL,
                `external_order_id` varchar(255) DEFAULT NULL,
                `quantity` int(11) NOT NULL,
                `link` text NOT NULL,
                `status` enum('pending','processing','completed','cancelled','error') DEFAULT 'pending',
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `key_id` (`key_id`),
                KEY `service_id` (`service_id`),
                KEY `order_id` (`order_id`),
                CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`key_id`) REFERENCES `keys` (`id`) ON DELETE CASCADE,
                CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            "CREATE TABLE IF NOT EXISTS `rate_limits` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `identifier` varchar(255) NOT NULL,
                `attempts` int(11) DEFAULT 1,
                `last_attempt` int(11) NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `identifier` (`identifier`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ];
        
        foreach ($tables as $table) {
            $pdo->exec($table);
        }
        
        // Update config file
        $config_content = file_get_contents('config.php');
        $config_content = str_replace("define('DB_HOST', 'localhost');", "define('DB_HOST', '$db_host');", $config_content);
        $config_content = str_replace("define('DB_NAME', 'kiwipazari_db');", "define('DB_NAME', '$db_name');", $config_content);
        $config_content = str_replace("define('DB_USER', 'root');", "define('DB_USER', '$db_user');", $config_content);
        $config_content = str_replace("define('DB_PASS', '');", "define('DB_PASS', '$db_pass');", $config_content);
        
        $admin_hash = password_hash($admin_password, PASSWORD_DEFAULT);
        $config_content = str_replace('$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', $admin_hash, $config_content);
        
        file_put_contents('config.php', $config_content);
        
        $setup_complete = true;
        
    } catch (Exception $e) {
        $error_message = "Setup failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KiWiPazari Setup</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #1a1a1a; color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { max-width: 500px; width: 100%; padding: 2rem; background: #2a2a2a; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        h1 { text-align: center; margin-bottom: 2rem; color: #4a9eff; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        input, select { width: 100%; padding: 0.75rem; border: 1px solid #555; border-radius: 4px; background: #3a3a3a; color: #fff; }
        input:focus, select:focus { outline: none; border-color: #4a9eff; }
        button { width: 100%; padding: 0.75rem; background: #4a9eff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; margin-top: 1rem; }
        button:hover { background: #357abd; }
        .error { color: #ff4444; margin-top: 1rem; text-align: center; }
        .success { color: #44ff44; margin-top: 1rem; text-align: center; }
        .info { background: #333; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; border-left: 4px solid #4a9eff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>KiWiPazari Kurulum</h1>
        
        <?php if ($setup_complete): ?>
            <div class="success">
                <h2>Kurulum Tamamlandı!</h2>
                <p>Veritabanı başarıyla oluşturuldu. Artık uygulamayı kullanabilirsiniz.</p>
                <button onclick="window.location.href='index.php'">Anasayfaya Git</button>
            </div>
        <?php else: ?>
            <div class="info">
                <strong>Kurulum Bilgileri:</strong><br>
                - Veritabanı tabloları otomatik olarak oluşturulacak<br>
                - Admin paneli: /kiwi-management-portal<br>
                - Varsayılan admin şifresi: admin123 (değiştirilebilir)
            </div>
            
            <form method="post">
                <div class="form-group">
                    <label for="db_host">Veritabanı Sunucusu:</label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required>
                </div>
                
                <div class="form-group">
                    <label for="db_name">Veritabanı Adı:</label>
                    <input type="text" id="db_name" name="db_name" value="kiwipazari_db" required>
                </div>
                
                <div class="form-group">
                    <label for="db_user">Veritabanı Kullanıcısı:</label>
                    <input type="text" id="db_user" name="db_user" value="root" required>
                </div>
                
                <div class="form-group">
                    <label for="db_pass">Veritabanı Şifresi:</label>
                    <input type="password" id="db_pass" name="db_pass" placeholder="Boş bırakılabilir">
                </div>
                
                <div class="form-group">
                    <label for="admin_password">Admin Şifresi:</label>
                    <input type="password" id="admin_password" name="admin_password" value="admin123" required>
                </div>
                
                <button type="submit">Kurulumu Başlat</button>
            </form>
            
            <?php if ($error_message): ?>
                <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>