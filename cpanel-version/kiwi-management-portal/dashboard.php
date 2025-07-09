<?php
require_once '../config.php';
requireAdmin();

// Get statistics with error handling
try {
    $stats = [
        'apis' => $pdo->query("SELECT COUNT(*) FROM apis")->fetchColumn() ?: 0,
        'services' => $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn() ?: 0,
        'keys' => $pdo->query("SELECT COUNT(*) FROM keys")->fetchColumn() ?: 0,
        'orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: 0,
        'active_keys' => $pdo->query("SELECT COUNT(*) FROM keys WHERE is_active = 1")->fetchColumn() ?: 0,
        'pending_orders' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn() ?: 0,
        'processing_orders' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'")->fetchColumn() ?: 0,
        'completed_orders' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn() ?: 0
    ];
} catch (Exception $e) {
    // If tables don't exist, set default values
    $stats = [
        'apis' => 0,
        'services' => 0,
        'keys' => 0,
        'orders' => 0,
        'active_keys' => 0,
        'pending_orders' => 0,
        'processing_orders' => 0,
        'completed_orders' => 0
    ];
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - KiWiPazari</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #1a1a1a; color: #fff; min-height: 100vh; }
        .header { background: #2a2a2a; padding: 1rem 2rem; border-bottom: 1px solid #444; }
        .header h1 { color: #4a9eff; display: inline-block; }
        .header .logout { float: right; }
        .header .logout a { color: #ff4444; text-decoration: none; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: #2a2a2a; padding: 1.5rem; border-radius: 8px; text-align: center; }
        .stat-card h3 { color: #4a9eff; margin-bottom: 0.5rem; }
        .stat-card .number { font-size: 2rem; font-weight: bold; color: #fff; }
        .nav-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .nav-card { background: #2a2a2a; padding: 1.5rem; border-radius: 8px; text-align: center; transition: all 0.3s; }
        .nav-card:hover { background: #333; transform: translateY(-2px); }
        .nav-card a { color: #4a9eff; text-decoration: none; font-weight: bold; }
        .nav-card p { color: #ccc; margin-top: 0.5rem; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="header">
        <h1>KiWiPazari Admin</h1>
        <div class="logout">
            <a href="index.php?logout=1">Çıkış Yap</a>
        </div>
        <div style="clear: both;"></div>
    </div>
    
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <h3>API Sayısı</h3>
                <div class="number"><?php echo $stats['apis']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Servis Sayısı</h3>
                <div class="number"><?php echo $stats['services']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Toplam Key</h3>
                <div class="number"><?php echo $stats['keys']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Aktif Key</h3>
                <div class="number"><?php echo $stats['active_keys']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Toplam Sipariş</h3>
                <div class="number"><?php echo $stats['orders']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Bekleyen Siparişler</h3>
                <div class="number"><?php echo $stats['pending_orders']; ?></div>
            </div>
            <div class="stat-card">
                <h3>İşlenen Siparişler</h3>
                <div class="number"><?php echo $stats['processing_orders']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Tamamlanan Siparişler</h3>
                <div class="number"><?php echo $stats['completed_orders']; ?></div>
            </div>
        </div>
        
        <div class="nav-grid">
            <div class="nav-card">
                <a href="api-management.php">API Yönetimi</a>
                <p>Harici API'leri ekle ve yönet</p>
            </div>
            <div class="nav-card">
                <a href="service-management.php">Servis Yönetimi</a>
                <p>Servisleri görüntüle ve yönet</p>
            </div>
            <div class="nav-card">
                <a href="key-management.php">Key Yönetimi</a>
                <p>Müşteri key'lerini oluştur ve yönet</p>
            </div>
            <div class="nav-card">
                <a href="order-management.php">Sipariş Yönetimi</a>
                <p>Siparişleri görüntüle ve takip et</p>
            </div>
        </div>
    </div>
</body>
</html>