<?php
require_once 'config.php';

// Handle AJAX requests
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    switch ($action) {
        case 'validate_key':
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                jsonResponse(['error' => 'Invalid CSRF token'], 403);
            }
            
            $keyValue = sanitizeInput($_POST['key_value'] ?? '');
            if (!$keyValue) {
                jsonResponse(['error' => 'Key value is required'], 400);
            }
            
            $stmt = $pdo->prepare("
                SELECT k.*, s.name as service_name, s.platform, s.category 
                FROM keys k 
                JOIN services s ON k.service_id = s.id 
                WHERE k.key_value = ? AND k.is_active = 1
            ");
            $stmt->execute([$keyValue]);
            $key = $stmt->fetch();
            
            if (!$key) {
                jsonResponse(['error' => 'Geçersiz key'], 404);
            }
            
            if ($key['used_amount'] >= $key['max_amount']) {
                jsonResponse(['error' => 'Key kullanım limiti doldu'], 400);
            }
            
            $remainingAmount = $key['max_amount'] - $key['used_amount'];
            jsonResponse([
                'success' => true,
                'key' => $key,
                'service' => [
                    'id' => $key['service_id'],
                    'name' => $key['service_name'],
                    'platform' => $key['platform'],
                    'category' => $key['category']
                ],
                'remaining_amount' => $remainingAmount,
                'max_amount' => $key['max_amount']
            ]);
            break;
            
        case 'create_order':
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                jsonResponse(['error' => 'Invalid CSRF token'], 403);
            }
            
            $keyValue = sanitizeInput($_POST['key_value'] ?? '');
            $quantity = intval($_POST['quantity'] ?? 0);
            $link = sanitizeInput($_POST['link'] ?? '');
            
            if (!$keyValue || !$quantity || !$link) {
                jsonResponse(['error' => 'Tüm alanlar zorunludur'], 400);
            }
            
            // Get key info
            $stmt = $pdo->prepare("
                SELECT k.*, s.*, a.url as api_url, a.api_key 
                FROM keys k 
                JOIN services s ON k.service_id = s.id 
                JOIN apis a ON s.api_id = a.id 
                WHERE k.key_value = ? AND k.is_active = 1
            ");
            $stmt->execute([$keyValue]);
            $key = $stmt->fetch();
            
            if (!$key) {
                jsonResponse(['error' => 'Geçersiz key'], 404);
            }
            
            $remainingAmount = $key['max_amount'] - $key['used_amount'];
            if ($quantity > $remainingAmount) {
                jsonResponse(['error' => 'Yetersiz key limiti'], 400);
            }
            
            $orderId = generateOrderId();
            
            // Create order in external API
            $externalOrderId = null;
            try {
                $apiData = [
                    'key' => $key['api_key'],
                    'action' => 'add',
                    'service' => $key['external_id'],
                    'link' => $link,
                    'quantity' => $quantity
                ];
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $key['api_url']);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($apiData));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200) {
                    $responseData = json_decode($response, true);
                    if (isset($responseData['order'])) {
                        $externalOrderId = $responseData['order'];
                    }
                }
            } catch (Exception $e) {
                // Log error but continue with local order
            }
            
            // Create local order
            $stmt = $pdo->prepare("
                INSERT INTO orders (order_id, key_id, service_id, external_order_id, quantity, link, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'processing')
            ");
            $stmt->execute([$orderId, $key['id'], $key['service_id'], $externalOrderId, $quantity, $link]);
            
            // Update key usage
            $stmt = $pdo->prepare("UPDATE keys SET used_amount = used_amount + ? WHERE id = ?");
            $stmt->execute([$quantity, $key['id']]);
            
            // Deactivate key if fully used
            if ($key['used_amount'] + $quantity >= $key['max_amount']) {
                $stmt = $pdo->prepare("UPDATE keys SET is_active = 0 WHERE id = ?");
                $stmt->execute([$key['id']]);
            }
            
            jsonResponse([
                'success' => true,
                'order_id' => $orderId,
                'external_order_id' => $externalOrderId
            ]);
            break;
            
        case 'search_order':
            $orderId = sanitizeInput($_GET['order_id'] ?? '');
            $orderId = ltrim($orderId, '#'); // Remove # prefix if present
            
            if (!$orderId) {
                jsonResponse(['error' => 'Order ID is required'], 400);
            }
            
            $stmt = $pdo->prepare("
                SELECT o.*, s.name as service_name, s.platform, k.key_value
                FROM orders o
                JOIN services s ON o.service_id = s.id
                JOIN keys k ON o.key_id = k.id
                WHERE o.order_id = ?
            ");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            
            if (!$order) {
                jsonResponse(['error' => 'Sipariş bulunamadı'], 404);
            }
            
            // Try to update status from external API if external_order_id exists
            if ($order['external_order_id']) {
                try {
                    $stmt = $pdo->prepare("
                        SELECT a.url, a.api_key 
                        FROM apis a 
                        JOIN services s ON a.id = s.api_id 
                        WHERE s.id = ?
                    ");
                    $stmt->execute([$order['service_id']]);
                    $api = $stmt->fetch();
                    
                    if ($api) {
                        $apiData = [
                            'key' => $api['api_key'],
                            'action' => 'status',
                            'order' => $order['external_order_id']
                        ];
                        
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $api['url']);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($apiData));
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                        
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        if ($httpCode === 200) {
                            $responseData = json_decode($response, true);
                            if (isset($responseData['status'])) {
                                $newStatus = $responseData['status'];
                                if ($newStatus === 'Completed') {
                                    $newStatus = 'completed';
                                } elseif ($newStatus === 'In progress') {
                                    $newStatus = 'processing';
                                } elseif ($newStatus === 'Pending') {
                                    $newStatus = 'pending';
                                }
                                
                                // Update local status
                                $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                                $stmt->execute([$newStatus, $order['id']]);
                                $order['status'] = $newStatus;
                            }
                        }
                    }
                } catch (Exception $e) {
                    // Ignore API errors
                }
            }
            
            jsonResponse([
                'success' => true,
                'order' => $order
            ]);
            break;
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KiWiPazari - Ana Sayfa</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #1a1a1a; color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { max-width: 500px; width: 100%; padding: 2rem; }
        .header { text-align: center; margin-bottom: 2rem; }
        .header h1 { font-size: 2.5rem; color: #4a9eff; margin-bottom: 0.5rem; }
        .header p { color: #ccc; font-size: 1.1rem; }
        .card { background: #2a2a2a; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.3); margin-bottom: 1rem; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; color: #ddd; }
        input { width: 100%; padding: 0.75rem; border: 1px solid #555; border-radius: 6px; background: #3a3a3a; color: #fff; font-size: 1rem; }
        input:focus { outline: none; border-color: #4a9eff; }
        .btn { width: 100%; padding: 0.75rem; background: #4a9eff; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; font-weight: bold; transition: all 0.3s; }
        .btn:hover { background: #357abd; transform: translateY(-1px); }
        .btn:disabled { background: #666; cursor: not-allowed; transform: none; }
        .error { color: #ff4444; margin-top: 0.5rem; font-size: 0.9rem; }
        .success { color: #44ff44; margin-top: 0.5rem; font-size: 0.9rem; }
        .info { background: #333; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; border-left: 4px solid #4a9eff; }
        .hidden { display: none; }
        .order-form { margin-top: 2rem; }
        .order-search { margin-top: 2rem; }
        .order-result { margin-top: 1rem; padding: 1rem; background: #333; border-radius: 6px; }
        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.9rem; font-weight: bold; }
        .status-pending { background: #f59e0b; color: #000; }
        .status-processing { background: #3b82f6; color: #fff; }
        .status-completed { background: #10b981; color: #fff; }
        .status-error { background: #ef4444; color: #fff; }
        .nav-links { text-align: center; margin-top: 2rem; }
        .nav-links a { color: #4a9eff; text-decoration: none; margin: 0 1rem; }
        .nav-links a:hover { text-decoration: underline; }
        .loader { border: 2px solid #333; border-top: 2px solid #4a9eff; border-radius: 50%; width: 20px; height: 20px; animation: spin 1s linear infinite; display: inline-block; margin-left: 10px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>KIWIPAZARI</h1>
            <p>Lütfen ürün anahtarınızı girin</p>
        </div>
        
        <div class="card">
            <form id="keyValidationForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="form-group">
                    <label for="key_value">Ürün Anahtarı</label>
                    <input type="text" id="key_value" name="key_value" placeholder="Ürün anahtarınızı girin" required>
                    <div id="keyError" class="error hidden"></div>
                </div>
                <button type="submit" class="btn" id="validateBtn">
                    Doğrula
                    <span id="validateLoader" class="loader hidden"></span>
                </button>
            </form>
        </div>
        
        <div id="orderForm" class="card hidden">
            <h3>Sipariş Oluştur</h3>
            <div id="serviceInfo" class="info"></div>
            <form id="createOrderForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" id="validated_key" name="key_value">
                <div class="form-group">
                    <label for="quantity">Miktar</label>
                    <input type="number" id="quantity" name="quantity" min="1" required>
                </div>
                <div class="form-group">
                    <label for="link">Link</label>
                    <input type="url" id="link" name="link" placeholder="https://example.com" required>
                </div>
                <button type="submit" class="btn" id="orderBtn">
                    Sipariş Oluştur
                    <span id="orderLoader" class="loader hidden"></span>
                </button>
                <div id="orderError" class="error hidden"></div>
                <div id="orderSuccess" class="success hidden"></div>
            </form>
        </div>
        
        <div class="card">
            <h3>Sipariş Sorgula</h3>
            <form id="orderSearchForm">
                <div class="form-group">
                    <label for="search_order_id">Sipariş ID</label>
                    <input type="text" id="search_order_id" name="order_id" placeholder="#2384344 veya 2384344" required>
                </div>
                <button type="submit" class="btn" id="searchBtn">
                    Sorgula
                    <span id="searchLoader" class="loader hidden"></span>
                </button>
            </form>
            <div id="searchResult" class="order-result hidden"></div>
        </div>
        
        <div class="nav-links">
            <a href="kiwi-management-portal">Admin Panel</a>
        </div>
    </div>
    
    <script>
        // Key validation
        document.getElementById('keyValidationForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('validateBtn');
            const loader = document.getElementById('validateLoader');
            const error = document.getElementById('keyError');
            const orderForm = document.getElementById('orderForm');
            
            btn.disabled = true;
            loader.classList.remove('hidden');
            error.classList.add('hidden');
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('?action=validate_key', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('validated_key').value = formData.get('key_value');
                    document.getElementById('quantity').max = result.remaining_amount;
                    document.getElementById('serviceInfo').innerHTML = `
                        <strong>Platform:</strong> ${result.service.platform}<br>
                        <strong>Kategori:</strong> ${result.service.category}<br>
                        <strong>Kalan Miktar:</strong> ${result.remaining_amount}
                    `;
                    orderForm.classList.remove('hidden');
                    error.classList.add('hidden');
                } else {
                    error.textContent = result.error;
                    error.classList.remove('hidden');
                    orderForm.classList.add('hidden');
                }
            } catch (err) {
                error.textContent = 'Bir hata oluştu, lütfen tekrar deneyin';
                error.classList.remove('hidden');
                orderForm.classList.add('hidden');
            }
            
            btn.disabled = false;
            loader.classList.add('hidden');
        });
        
        // Order creation
        document.getElementById('createOrderForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('orderBtn');
            const loader = document.getElementById('orderLoader');
            const error = document.getElementById('orderError');
            const success = document.getElementById('orderSuccess');
            
            btn.disabled = true;
            loader.classList.remove('hidden');
            error.classList.add('hidden');
            success.classList.add('hidden');
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('?action=create_order', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    success.innerHTML = `Sipariş başarıyla oluşturuldu!<br>Sipariş ID: <strong>${result.order_id}</strong>`;
                    success.classList.remove('hidden');
                    this.reset();
                    document.getElementById('orderForm').classList.add('hidden');
                    document.getElementById('keyValidationForm').reset();
                    
                    // Auto-redirect to order search after 3 seconds
                    setTimeout(() => {
                        document.getElementById('search_order_id').value = result.order_id;
                    }, 3000);
                } else {
                    error.textContent = result.error;
                    error.classList.remove('hidden');
                }
            } catch (err) {
                error.textContent = 'Bir hata oluştu, lütfen tekrar deneyin';
                error.classList.remove('hidden');
            }
            
            btn.disabled = false;
            loader.classList.add('hidden');
        });
        
        // Order search
        document.getElementById('orderSearchForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('searchBtn');
            const loader = document.getElementById('searchLoader');
            const result = document.getElementById('searchResult');
            
            btn.disabled = true;
            loader.classList.remove('hidden');
            result.classList.add('hidden');
            
            const orderId = document.getElementById('search_order_id').value;
            
            try {
                const response = await fetch(`?action=search_order&order_id=${encodeURIComponent(orderId)}`);
                const data = await response.json();
                
                if (data.success) {
                    const order = data.order;
                    const statusClass = `status-${order.status}`;
                    const statusText = {
                        'pending': 'Beklemede',
                        'processing': 'İşleniyor',
                        'completed': 'Tamamlandı',
                        'cancelled': 'İptal Edildi',
                        'error': 'Hata'
                    }[order.status] || order.status;
                    
                    result.innerHTML = `
                        <h4>Sipariş Detayları</h4>
                        <p><strong>Sipariş ID:</strong> ${order.order_id}</p>
                        <p><strong>Platform:</strong> ${order.platform}</p>
                        <p><strong>Miktar:</strong> ${order.quantity}</p>
                        <p><strong>Link:</strong> ${order.link}</p>
                        <p><strong>Durum:</strong> <span class="status-badge ${statusClass}">${statusText}</span></p>
                        <p><strong>Oluşturulma:</strong> ${new Date(order.created_at).toLocaleString('tr-TR')}</p>
                    `;
                    result.classList.remove('hidden');
                } else {
                    result.innerHTML = `<p class="error">${data.error}</p>`;
                    result.classList.remove('hidden');
                }
            } catch (err) {
                result.innerHTML = '<p class="error">Bir hata oluştu, lütfen tekrar deneyin</p>';
                result.classList.remove('hidden');
            }
            
            btn.disabled = false;
            loader.classList.add('hidden');
        });
    </script>
</body>
</html>