<?php
require_once 'config.php';

$error = '';
$success = '';
$orderId = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = trim($_POST['key'] ?? '');
    $serviceId = intval($_POST['service_id'] ?? 0);
    $link = trim($_POST['link'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);
    
    if (empty($key) || empty($link) || $quantity <= 0 || $serviceId <= 0) {
        $error = 'Tüm alanlar gerekli';
    } else {
        try {
            $db = getDatabase();
            
            // Key'i doğrula
            $stmt = $db->prepare("SELECT * FROM keys WHERE key_value = ? AND is_active = 1");
            $stmt->execute([$key]);
            $keyData = $stmt->fetch();
            
            if (!$keyData) {
                $error = 'Geçersiz anahtar';
            } else {
                // Servis bilgisini al
                $stmt = $db->prepare("SELECT * FROM services WHERE id = ? AND is_active = 1");
                $stmt->execute([$serviceId]);
                $serviceData = $stmt->fetch();
                
                if (!$serviceData) {
                    $error = 'Servis bulunamadı';
                } else {
                    // Miktar kontrolü
                    $remainingAmount = ($keyData['max_amount'] ?? 1000) - ($keyData['used_amount'] ?? 0);
                    
                    if ($quantity > $remainingAmount) {
                        $error = "Bu anahtar için kalan miktar: {$remainingAmount}";
                    } elseif ($quantity < $serviceData['min_quantity'] || $quantity > $serviceData['max_quantity']) {
                        $error = "Miktar {$serviceData['min_quantity']} ile {$serviceData['max_quantity']} arasında olmalı";
                    } else {
                        // Sipariş ID oluştur
                        $orderId = '#' . (1000000 + rand(0, 8999999));
                        
                        // Sipariş oluştur
                        $stmt = $db->prepare("INSERT INTO orders (order_id, key_id, service_id, link, quantity, status, created_at) VALUES (?, ?, ?, ?, ?, 'processing', NOW())");
                        $stmt->execute([$orderId, $keyData['id'], $serviceId, $link, $quantity]);
                        
                        // Key kullanım miktarını güncelle
                        $newUsedAmount = ($keyData['used_amount'] ?? 0) + $quantity;
                        $stmt = $db->prepare("UPDATE keys SET used_amount = ? WHERE id = ?");
                        $stmt->execute([$newUsedAmount, $keyData['id']]);
                        
                        // Eğer key'in max_amount'u dolmuşsa deaktif et
                        if ($newUsedAmount >= ($keyData['max_amount'] ?? 1000)) {
                            $stmt = $db->prepare("UPDATE keys SET is_active = 0 WHERE id = ?");
                            $stmt->execute([$keyData['id']]);
                        }
                        
                        $success = "Sipariş başarıyla oluşturuldu! Sipariş ID: {$orderId}";
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'Sipariş oluşturulamadı: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Sonucu - KiWiPazari</title>
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
            text-align: center;
        }
        
        .result-card {
            background: rgba(45, 45, 45, 0.9);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .logo {
            font-size: 2rem;
            font-weight: bold;
            color: #4A90E2;
            margin-bottom: 2rem;
        }
        
        .alert {
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            font-size: 1rem;
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
        
        .order-id {
            background: rgba(74, 144, 226, 0.1);
            border: 1px solid rgba(74, 144, 226, 0.3);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            font-family: monospace;
            font-size: 1.2rem;
            color: #4A90E2;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .order-id:hover {
            background: rgba(74, 144, 226, 0.2);
        }
        
        .copy-hint {
            font-size: 0.8rem;
            color: #999;
            margin-top: 0.5rem;
        }
        
        .actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4A90E2 0%, #357abd 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(74, 144, 226, 0.3);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #ddd;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }
        
        .countdown {
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #999;
        }
        
        .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .success-icon {
            color: #51cf66;
        }
        
        .error-icon {
            color: #ff6b6b;
        }
        
        @media (max-width: 768px) {
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="result-card">
            <div class="logo">KiWiPazari</div>
            
            <?php if ($error): ?>
                <div class="icon error-icon">❌</div>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <div class="actions">
                    <a href="javascript:history.back()" class="btn btn-secondary">← Geri Dön</a>
                    <a href="index.php" class="btn btn-primary">Ana Sayfa</a>
                </div>
            <?php elseif ($success): ?>
                <div class="icon success-icon">✅</div>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
                
                <?php if ($orderId): ?>
                    <div class="order-id" onclick="copyOrderId()" id="orderIdDiv">
                        <?php echo htmlspecialchars($orderId); ?>
                    </div>
                    <div class="copy-hint">Sipariş ID'sini kopyalamak için tıklayın</div>
                <?php endif; ?>
                
                <div class="countdown" id="countdown">
                    5 saniye sonra sipariş sorgulama sayfasına yönlendirileceksiniz...
                </div>
                
                <div class="actions">
                    <a href="index.php" class="btn btn-secondary">Ana Sayfa</a>
                    <a href="order-search.php" class="btn btn-primary">Sipariş Sorgula</a>
                </div>
            <?php else: ?>
                <div class="icon error-icon">❌</div>
                <div class="alert alert-error">
                    Beklenmeyen bir hata oluştu.
                </div>
                <div class="actions">
                    <a href="index.php" class="btn btn-primary">Ana Sayfa</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function copyOrderId() {
            const orderIdText = document.getElementById('orderIdDiv').textContent;
            navigator.clipboard.writeText(orderIdText).then(function() {
                const hint = document.querySelector('.copy-hint');
                hint.textContent = 'Sipariş ID kopyalandı!';
                hint.style.color = '#51cf66';
                setTimeout(() => {
                    hint.textContent = 'Sipariş ID\'sini kopyalamak için tıklayın';
                    hint.style.color = '#999';
                }, 2000);
            });
        }
        
        <?php if ($success && $orderId): ?>
        // 5 saniye sonra sipariş sorgulama sayfasına yönlendir
        let countdown = 5;
        const countdownElement = document.getElementById('countdown');
        
        const timer = setInterval(function() {
            countdown--;
            countdownElement.textContent = countdown + ' saniye sonra sipariş sorgulama sayfasına yönlendirileceksiniz...';
            
            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = 'order-search.php?orderId=' + encodeURIComponent('<?php echo $orderId; ?>');
            }
        }, 1000);
        <?php endif; ?>
    </script>
</body>
</html>