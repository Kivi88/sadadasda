<?php
require_once 'config.php';

// Handle both POST and GET requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = trim($_POST['orderId'] ?? '');
    $contentType = 'application/json';
} else {
    $orderId = trim($_GET['orderId'] ?? '');
    $contentType = 'text/html';
}

$response = ['success' => false, 'message' => 'Sipariş bulunamadı'];

if (!empty($orderId)) {
    // Remove # prefix if present
    $cleanOrderId = $orderId[0] === '#' ? substr($orderId, 1) : $orderId;
    
    try {
        $db = getDatabase();
        
        // Search for order
        $stmt = $db->prepare("SELECT o.*, s.name as service_name, s.platform, s.category, k.name as key_name 
                             FROM orders o 
                             LEFT JOIN services s ON o.service_id = s.id 
                             LEFT JOIN keys k ON o.key_id = k.id 
                             WHERE o.order_id = ? OR o.order_id = ?");
        $stmt->execute([$orderId, '#' . $cleanOrderId]);
        $order = $stmt->fetch();
        
        if ($order) {
            // Map status to Turkish
            $statusMap = [
                'pending' => 'Beklemede',
                'processing' => 'İşleniyor',
                'completed' => 'Tamamlandı',
                'cancelled' => 'İptal Edildi'
            ];
            
            $response = [
                'success' => true,
                'order' => [
                    'orderId' => $order['order_id'],
                    'status' => $statusMap[$order['status']] ?? $order['status'],
                    'quantity' => $order['quantity'],
                    'service' => $order['service_name'],
                    'platform' => $order['platform'],
                    'category' => $order['category'],
                    'link' => $order['link'],
                    'created_at' => $order['created_at']
                ]
            ];
        } else {
            $response['message'] = 'Sipariş bulunamadı';
        }
    } catch (Exception $e) {
        $response['message'] = 'Veritabanı hatası';
    }
}

// Return JSON for AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// HTML page for direct access
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Sorgula - KiWiPazari</title>
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
        
        .search-card {
            background: rgba(45, 45, 45, 0.9);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
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
            padding: 0.75rem;
            background: linear-gradient(135deg, #4A90E2 0%, #357abd 100%);
            color: white;
            border: none;
            border-radius: 6px;
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
        
        .order-info {
            background: rgba(74, 144, 226, 0.1);
            border: 1px solid rgba(74, 144, 226, 0.3);
            border-radius: 10px;
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .order-info h3 {
            color: #4A90E2;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }
        
        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .order-detail {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .order-detail .label {
            color: #bbb;
            font-size: 0.8rem;
            margin-bottom: 0.3rem;
        }
        
        .order-detail .value {
            color: #fff;
            font-size: 1rem;
            font-weight: 500;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        
        .status-processing {
            background: rgba(0, 123, 255, 0.2);
            color: #007bff;
            border: 1px solid rgba(0, 123, 255, 0.3);
        }
        
        .status-completed {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }
        
        .status-cancelled {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
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
            .order-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Sipariş Sorgula</h1>
            <p>Sipariş durumunu kontrol edin</p>
        </div>
        
        <div class="search-card">
            <form method="GET">
                <div class="form-group">
                    <label for="orderId">Sipariş ID</label>
                    <input type="text" id="orderId" name="orderId" placeholder="#2384344 veya 2384344" 
                           value="<?php echo htmlspecialchars($orderId); ?>" required>
                </div>
                
                <button type="submit" class="btn">Sorgula</button>
            </form>
            
            <?php if (!empty($orderId)): ?>
                <?php if ($response['success']): ?>
                    <div class="order-info">
                        <h3>Sipariş Detayları</h3>
                        <div class="order-details">
                            <div class="order-detail">
                                <div class="label">Sipariş ID</div>
                                <div class="value"><?php echo htmlspecialchars($response['order']['orderId']); ?></div>
                            </div>
                            <div class="order-detail">
                                <div class="label">Durum</div>
                                <div class="value">
                                    <?php 
                                    $statusClass = 'status-' . strtolower($response['order']['status']);
                                    echo '<span class="status-badge ' . $statusClass . '">' . htmlspecialchars($response['order']['status']) . '</span>';
                                    ?>
                                </div>
                            </div>
                            <div class="order-detail">
                                <div class="label">Miktar</div>
                                <div class="value"><?php echo number_format($response['order']['quantity']); ?></div>
                            </div>
                            <div class="order-detail">
                                <div class="label">Platform</div>
                                <div class="value"><?php echo htmlspecialchars($response['order']['platform']); ?></div>
                            </div>
                            <div class="order-detail">
                                <div class="label">Kategori</div>
                                <div class="value"><?php echo htmlspecialchars($response['order']['category']); ?></div>
                            </div>
                            <div class="order-detail">
                                <div class="label">Sipariş Tarihi</div>
                                <div class="value"><?php echo date('d.m.Y H:i', strtotime($response['order']['created_at'])); ?></div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($response['message']); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="back-link">
            <a href="index.php">← Ana Sayfaya Dön</a>
        </div>
    </div>
</body>
</html>