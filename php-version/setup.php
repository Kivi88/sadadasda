<?php
/**
 * KiWiPazari Setup Script
 * Bu script MySQL veritabanını kurur ve gerekli tabloları oluşturur
 */

// cPanel veritabanı bilgilerini buraya girin
$host = 'localhost';
$username = 'smmkiwic_user';  // cPanel kullanıcı adınızı girin
$password = 'YOUR_DB_PASSWORD';  // Veritabanı şifrenizi girin
$database = 'smmkiwic_kiwipazari';  // Veritabanı adınızı girin

echo "<h2>KiWiPazari Kurulum</h2>";
echo "<p>Veritabanı bağlantısı test ediliyor...</p>";

// Veritabanı bağlantısı
$conn = new mysqli($host, $username, $password);

// Bağlantıyı kontrol et
if ($conn->connect_error) {
    die("<div style='color: red;'>❌ Veritabanı bağlantısı başarısız: " . $conn->connect_error . "</div>");
}

echo "<p style='color: green;'>✅ Veritabanı bağlantısı başarılı!</p>";

// Veritabanını oluştur
$sql = "CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>✅ Veritabanı başarıyla oluşturuldu veya zaten mevcut.</p>";
} else {
    echo "<p style='color: red;'>❌ Veritabanı oluşturulurken hata: " . $conn->error . "</p>";
}

// Veritabanını seç
$conn->select_db($database);

// Tabloları oluştur
$tables = [
    'apis' => "CREATE TABLE IF NOT EXISTS apis (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        url VARCHAR(500) NOT NULL,
        api_key VARCHAR(255) NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        last_sync TIMESTAMP NULL,
        response_time INT NULL,
        service_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    'services' => "CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        api_id INT,
        external_id VARCHAR(255) NOT NULL,
        name VARCHAR(500) NOT NULL,
        platform VARCHAR(100) NOT NULL,
        category VARCHAR(100) NOT NULL,
        min_quantity INT DEFAULT 1,
        max_quantity INT DEFAULT 10000,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (api_id) REFERENCES apis(id)
    )",
    
    'keys' => "CREATE TABLE IF NOT EXISTS `keys` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        key_value VARCHAR(255) NOT NULL UNIQUE,
        service_id INT,
        name VARCHAR(255) NOT NULL,
        prefix VARCHAR(50) DEFAULT 'KIWIPAZARI',
        max_amount INT DEFAULT 1000,
        used_amount INT DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        is_hidden BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (service_id) REFERENCES services(id)
    )",
    
    'orders' => "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id VARCHAR(255) NOT NULL UNIQUE,
        key_id INT,
        service_id INT,
        link VARCHAR(500) NOT NULL,
        quantity INT NOT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        external_order_id VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (key_id) REFERENCES `keys`(id),
        FOREIGN KEY (service_id) REFERENCES services(id)
    )"
];

foreach ($tables as $table => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: green;'>✅ Tablo '$table' başarıyla oluşturuldu.</p>";
    } else {
        echo "<p style='color: red;'>❌ Tablo '$table' oluşturulurken hata: " . $conn->error . "</p>";
    }
}

// Admin kullanıcısı oluştur (isteğe bağlı)
$admin_password = password_hash('ucFMkvJ5Tngq7QCN9Dl31edSWaPAmIRxfGwL62ih4U8jb0VosKHtO', PASSWORD_DEFAULT);
$admin_sql = "CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($admin_sql) === TRUE) {
    echo "<p style='color: green;'>✅ Admin kullanıcı tablosu oluşturuldu.</p>";
    
    // Varsayılan admin kullanıcısı ekle
    $insert_admin = "INSERT IGNORE INTO admin_users (username, password) VALUES ('admin', '$admin_password')";
    if ($conn->query($insert_admin) === TRUE) {
        echo "<p style='color: green;'>✅ Varsayılan admin kullanıcısı oluşturuldu.</p>";
    }
}

$conn->close();
echo "<div style='background: #d4edda; padding: 20px; border: 1px solid #c3e6cb; border-radius: 5px; margin-top: 20px;'>";
echo "<h3 style='color: #155724; margin-top: 0;'>🎉 Kurulum Tamamlandı!</h3>";
echo "<p><strong>Admin Paneli:</strong> <a href='/kiwi-management-portal' style='color: #007bff;'>Yönetim Paneli</a></p>";
echo "<p><strong>Ana Sayfa:</strong> <a href='index.php' style='color: #007bff;'>Ana Sayfa</a></p>";
echo "<p><strong>Admin Kullanıcı Adı:</strong> admin</p>";
echo "<p><strong>Admin Şifre:</strong> ucFMkvJ5Tngq7QCN9Dl31edSWaPAmIRxfGwL62ih4U8jb0VosKHtO</p>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 20px; border: 1px solid #ffeaa7; border-radius: 5px; margin-top: 20px;'>";
echo "<h4 style='color: #856404; margin-top: 0;'>⚠️ Önemli Güvenlik Uyarısı:</h4>";
echo "<p>Kurulum tamamlandıktan sonra <strong>setup.php</strong> dosyasını silin veya yeniden adlandırın!</p>";
echo "</div>";
?>