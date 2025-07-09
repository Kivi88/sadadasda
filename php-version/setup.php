<?php
/**
 * KiWiPazari Setup Script
 * Bu script MySQL veritabanını kurur ve gerekli tabloları oluşturur
 */

// Veritabanı bağlantı ayarları
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'kiwipazari';

// Veritabanı bağlantısı
$conn = new mysqli($host, $username, $password);

// Bağlantıyı kontrol et
if ($conn->connect_error) {
    die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}

// Veritabanını oluştur
$sql = "CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "Veritabanı başarıyla oluşturuldu veya zaten mevcut.<br>";
} else {
    echo "Veritabanı oluşturulurken hata: " . $conn->error . "<br>";
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
        echo "Tablo '$table' başarıyla oluşturuldu.<br>";
    } else {
        echo "Tablo '$table' oluşturulurken hata: " . $conn->error . "<br>";
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
    echo "Admin kullanıcı tablosu oluşturuldu.<br>";
    
    // Varsayılan admin kullanıcısı ekle
    $insert_admin = "INSERT IGNORE INTO admin_users (username, password) VALUES ('admin', '$admin_password')";
    if ($conn->query($insert_admin) === TRUE) {
        echo "Varsayılan admin kullanıcısı oluşturuldu.<br>";
    }
}

$conn->close();
echo "<br><strong>Kurulum tamamlandı!</strong><br>";
echo "Admin giriş: <a href='admin.php'>admin.php</a><br>";
echo "Ana sayfa: <a href='index.php'>index.php</a>";
?>