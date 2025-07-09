<?php
require_once 'config.php';

$error = '';
$keyData = null;
$serviceData = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = trim($_POST['key'] ?? '');
    
    if (empty($key)) {
        $error = 'Anahtar kodu gerekli';
    } else {
        try {
            $db = getDatabase();
            
            // Key'i bul
            $stmt = $db->prepare("SELECT * FROM keys WHERE key_value = ? AND is_active = 1");
            $stmt->execute([$key]);
            $keyData = $stmt->fetch();
            
            if (!$keyData) {
                $error = 'Geçersiz veya aktif olmayan anahtar';
            } else {
                // Servis bilgisini al
                $stmt = $db->prepare("SELECT * FROM services WHERE id = ? AND is_active = 1");
                $stmt->execute([$keyData['service_id']]);
                $serviceData = $stmt->fetch();
                
                if (!$serviceData) {
                    $error = 'Servis bulunamadı veya aktif değil';
                }
            }
        } catch (Exception $e) {
            $error = 'Veritabanı hatası';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anahtar Doğrulama - KiWiPazari</title>
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
            padding: 2rem;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .header h1 {
            color: #4A90E2;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            color: #bbb;
            font-size: 1rem;
        }
        
        .card {
            background: rgba(45, 45, 45, 0.9);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
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
        
        .key-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .info-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .info-item h4 {
            color: #4A90E2;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .info-item p {
            color: #ddd;
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .service-info {
            background: rgba(74, 144, 226, 0.1);
            padding: 1.5rem;
            border-radius: 10px;
            border: 1px solid rgba(74, 144, 226, 0.3);
            margin-bottom: 2rem;
        }
        
        .service-info h3 {
            color: #4A90E2;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .service-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .service-detail {
            text-align: center;
        }
        
        .service-detail .label {
            color: #bbb;
            font-size: 0.8rem;
            margin-bottom: 0.3rem;
        }
        
        .service-detail .value {
            color: #fff;
            font-size: 1rem;
            font-weight: 500;
        }
        
        .order-form {
            background: rgba(255, 255, 255, 0.05);
            padding: 2rem;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .order-form h3 {
            color: #4A90E2;
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
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
        
        .btn:active {
            transform: translateY(0);
        }
        
        .back-link {
            text-align: center;
            margin-top: 2rem;
        }
        
        .back-link a {
            color: #4A90E2;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .back-link a:hover {
            color: #357abd;
        }
        
        @media (max-width: 768px) {
            .key-info {
                grid-template-columns: 1fr;
            }
            
            .service-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Anahtar Doğrulama</h1>
            <p>Servis sipariş etmek için anahtar kodunuzu doğrulayın</p>
        </div>
        
        <div class="card">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <div class="back-link">
                    <a href="index.php">← Ana Sayfaya Dön</a>
                </div>
            <?php elseif ($keyData && $serviceData): ?>
                <div class="alert alert-success">
                    Anahtar başarıyla doğrulandı! Sipariş verebilirsiniz.
                </div>
                
                <div class="key-info">
                    <div class="info-item">
                        <h4>Anahtar Adı</h4>
                        <p><?php echo htmlspecialchars($keyData['name']); ?></p>
                    </div>
                    <div class="info-item">
                        <h4>Kalan Miktar</h4>
                        <p><?php echo number_format(($keyData['max_amount'] ?? 1000) - ($keyData['used_amount'] ?? 0)); ?></p>
                    </div>
                </div>
                
                <div class="service-info">
                    <h3>Servis Bilgileri</h3>
                    <div class="service-details">
                        <div class="service-detail">
                            <div class="label">Platform</div>
                            <div class="value"><?php echo htmlspecialchars($serviceData['platform']); ?></div>
                        </div>
                        <div class="service-detail">
                            <div class="label">Kategori</div>
                            <div class="value"><?php echo htmlspecialchars($serviceData['category']); ?></div>
                        </div>
                        <div class="service-detail">
                            <div class="label">Min Miktar</div>
                            <div class="value"><?php echo number_format($serviceData['min_quantity']); ?></div>
                        </div>
                        <div class="service-detail">
                            <div class="label">Max Miktar</div>
                            <div class="value"><?php echo number_format($serviceData['max_quantity']); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="order-form">
                    <h3>Sipariş Ver</h3>
                    <form action="create-order.php" method="POST">
                        <input type="hidden" name="key" value="<?php echo htmlspecialchars($keyData['key_value']); ?>">
                        <input type="hidden" name="service_id" value="<?php echo $serviceData['id']; ?>">
                        
                        <div class="form-group">
                            <label for="link">Hedef Link</label>
                            <input type="url" id="link" name="link" placeholder="https://instagram.com/profile veya post linki" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="quantity">Miktar (<?php echo number_format($serviceData['min_quantity']); ?> - <?php echo number_format(min($serviceData['max_quantity'], ($keyData['max_amount'] ?? 1000) - ($keyData['used_amount'] ?? 0))); ?>)</label>
                            <input type="number" 
                                   id="quantity" 
                                   name="quantity" 
                                   min="<?php echo $serviceData['min_quantity']; ?>" 
                                   max="<?php echo min($serviceData['max_quantity'], ($keyData['max_amount'] ?? 1000) - ($keyData['used_amount'] ?? 0)); ?>" 
                                   placeholder="Sipariş miktarı" 
                                   required>
                        </div>
                        
                        <button type="submit" class="btn">Sipariş Ver</button>
                    </form>
                </div>
                
                <div class="back-link">
                    <a href="index.php">← Ana Sayfaya Dön</a>
                </div>
            <?php else: ?>
                <div class="alert alert-error">
                    Anahtar doğrulanmadı. Lütfen geçerli bir anahtar girin.
                </div>
                <div class="back-link">
                    <a href="index.php">← Ana Sayfaya Dön</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>