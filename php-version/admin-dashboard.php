<?php
require_once 'config.php';
requireAdminLogin();

// Handle logout
if (isset($_GET['logout'])) {
    adminLogout();
}

$db = getDatabase();

// Get statistics
try {
    $stats = [
        'total_apis' => $db->query("SELECT COUNT(*) FROM apis")->fetchColumn(),
        'total_services' => $db->query("SELECT COUNT(*) FROM services")->fetchColumn(),
        'total_keys' => $db->query("SELECT COUNT(*) FROM keys")->fetchColumn(),
        'total_orders' => $db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    ];
} catch (Exception $e) {
    $stats = [
        'total_apis' => 0,
        'total_services' => 0,
        'total_keys' => 0,
        'total_orders' => 0,
    ];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - KiWiPazari</title>
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
        }
        
        .header {
            background: rgba(45, 45, 45, 0.9);
            padding: 1rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #4A90E2;
            font-size: 1.5rem;
        }
        
        .header .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: rgba(45, 45, 45, 0.9);
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card h3 {
            color: #4A90E2;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .card .number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }
        
        .card .label {
            color: #bbb;
            font-size: 0.9rem;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .menu-item {
            background: rgba(45, 45, 45, 0.9);
            padding: 1.5rem;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-decoration: none;
            color: #ffffff;
            transition: all 0.3s ease;
            display: block;
        }
        
        .menu-item:hover {
            background: rgba(74, 144, 226, 0.1);
            border-color: #4A90E2;
            transform: translateY(-2px);
        }
        
        .menu-item h4 {
            color: #4A90E2;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .menu-item p {
            color: #bbb;
            font-size: 0.9rem;
        }
        
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #4A90E2;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #357abd;
            transform: translateY(-1px);
        }
        
        .btn-danger {
            background: #e74c3c;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            background: rgba(46, 204, 113, 0.2);
            border: 1px solid rgba(46, 204, 113, 0.3);
            color: #51cf66;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>KiWiPazari Admin Panel</h1>
        <div class="user-info">
            <span>Hoş geldiniz, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</span>
            <a href="?logout=1" class="btn btn-danger">Çıkış Yap</a>
        </div>
    </div>
    
    <div class="container">
        <div class="alert">
            Admin paneline başarıyla giriş yaptınız. Tüm yönetim işlemlerini buradan gerçekleştirebilirsiniz.
        </div>
        
        <div class="dashboard-grid">
            <div class="card">
                <h3>API'ler</h3>
                <div class="number"><?php echo $stats['total_apis']; ?></div>
                <div class="label">Toplam API Sayısı</div>
            </div>
            
            <div class="card">
                <h3>Servisler</h3>
                <div class="number"><?php echo $stats['total_services']; ?></div>
                <div class="label">Toplam Servis Sayısı</div>
            </div>
            
            <div class="card">
                <h3>Anahtarlar</h3>
                <div class="number"><?php echo $stats['total_keys']; ?></div>
                <div class="label">Toplam Anahtar Sayısı</div>
            </div>
            
            <div class="card">
                <h3>Siparişler</h3>
                <div class="number"><?php echo $stats['total_orders']; ?></div>
                <div class="label">Toplam Sipariş Sayısı</div>
            </div>
        </div>
        
        <div class="menu-grid">
            <a href="api-management.php" class="menu-item">
                <h4>API Yönetimi</h4>
                <p>Dış API'leri ekleyin, düzenleyin ve yönetin</p>
            </a>
            
            <a href="service-management.php" class="menu-item">
                <h4>Servis Yönetimi</h4>
                <p>Servisleri görüntüleyin, ekleyin ve düzenleyin</p>
            </a>
            
            <a href="key-management.php" class="menu-item">
                <h4>Anahtar Yönetimi</h4>
                <p>Müşteri anahtarlarını oluşturun ve yönetin</p>
            </a>
            
            <a href="order-management.php" class="menu-item">
                <h4>Sipariş Yönetimi</h4>
                <p>Siparişleri görüntüleyin ve takip edin</p>
            </a>
        </div>
    </div>
</body>
</html>