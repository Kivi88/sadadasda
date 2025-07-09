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
    <title>KIWIPAZARI</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%); 
            color: #fff; 
            min-height: 100vh; 
            position: relative;
        }
        .top-nav {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 10;
        }
        .search-btn {
            background: rgba(74, 85, 104, 0.9);
            border: 1px solid rgba(160, 174, 192, 0.3);
            color: #CBD5E0;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .search-btn:hover {
            background: rgba(74, 85, 104, 1);
            border-color: rgba(160, 174, 192, 0.5);
        }
        .main-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
        }
        .card { 
            background: rgba(74, 85, 104, 0.7); 
            border-radius: 12px; 
            padding: 2.5rem; 
            width: 100%;
            max-width: 420px;
            border: 1px solid rgba(160, 174, 192, 0.2);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
        }
        .card-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .card-title { 
            font-size: 2rem; 
            color: #fff; 
            font-weight: 700; 
            letter-spacing: 1px; 
            margin-bottom: 0.5rem; 
        }
        .card-subtitle { 
            color: #A0AEC0; 
            font-size: 1rem; 
            font-weight: 400; 
        }
        .form-group { 
            margin-bottom: 1.5rem; 
        }
        .form-group input { 
            width: 100%; 
            padding: 1rem; 
            border: 1px solid rgba(160, 174, 192, 0.3); 
            border-radius: 8px; 
            background: rgba(45, 55, 72, 0.8); 
            color: #fff; 
            font-size: 1rem; 
            transition: all 0.3s ease;
        }
        .form-group input:focus { 
            outline: none; 
            border-color: #4A90E2; 
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }
        .form-group input::placeholder { 
            color: #718096; 
        }
        .btn { 
            width: 100%; 
            padding: 1rem; 
            background: linear-gradient(135deg, #4A90E2 0%, #357ABD 100%); 
            color: white; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 1rem; 
            font-weight: 600; 
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn:hover { 
            transform: translateY(-1px); 
            box-shadow: 0 10px 20px rgba(74, 144, 226, 0.3);
        }
        .btn:disabled { 
            background: #4A5568; 
            cursor: not-allowed; 
            transform: none; 
            box-shadow: none;
        }
        .error { 
            color: #F56565; 
            margin-top: 0.75rem; 
            font-size: 0.875rem; 
            font-weight: 500;
        }
        .success { 
            color: #48BB78; 
            margin-top: 0.75rem; 
            font-size: 0.875rem; 
            font-weight: 500;
        }
        .info { 
            background: rgba(74, 144, 226, 0.15); 
            padding: 1rem; 
            border-radius: 8px; 
            margin-bottom: 1.5rem; 
            border-left: 4px solid #4A90E2; 
            color: #E2E8F0;
            font-size: 0.9rem;
        }
        .hidden { 
            display: none; 
        }
        .loader { 
            border: 2px solid rgba(255, 255, 255, 0.3); 
            border-top: 2px solid #fff; 
            border-radius: 50%; 
            width: 16px; 
            height: 16px; 
            animation: spin 1s linear infinite; 
        }
        @keyframes spin { 
            0% { transform: rotate(0deg); } 
            100% { transform: rotate(360deg); } 
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .modal.show {
            display: flex;
        }
        .modal-content {
            background: rgba(74, 85, 104, 0.95);
            border-radius: 12px;
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            border: 1px solid rgba(160, 174, 192, 0.2);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #fff;
        }
        .close-btn {
            background: none;
            border: none;
            color: #A0AEC0;
            font-size: 1.5rem;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        .close-btn:hover {
            color: #fff;
        }
        .order-result { 
            margin-top: 1rem; 
            padding: 1rem; 
            background: rgba(45, 55, 72, 0.8); 
            border-radius: 8px; 
            border: 1px solid rgba(160, 174, 192, 0.2);
        }
        .status-badge { 
            display: inline-block; 
            padding: 0.25rem 0.75rem; 
            border-radius: 6px; 
            font-size: 0.8rem; 
            font-weight: 600; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-pending { background: rgba(237, 137, 54, 0.2); color: #ED8936; border: 1px solid rgba(237, 137, 54, 0.3); }
        .status-processing { background: rgba(66, 153, 225, 0.2); color: #4299E1; border: 1px solid rgba(66, 153, 225, 0.3); }
        .status-completed { background: rgba(72, 187, 120, 0.2); color: #48BB78; border: 1px solid rgba(72, 187, 120, 0.3); }
        .status-error { background: rgba(245, 101, 101, 0.2); color: #F56565; border: 1px solid rgba(245, 101, 101, 0.3); }
        
        /* Admin link at bottom */
        .admin-link {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
        }
        .admin-link a {
            color: #4A90E2;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        .admin-link a:hover {
            opacity: 1;
            text-decoration: underline;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .main-container { padding: 1rem; }
            .card { padding: 2rem 1.5rem; max-width: 350px; }
            .card-title { font-size: 1.75rem; }
            .top-nav { top: 15px; right: 15px; }
            .search-btn { padding: 0.625rem 1rem; font-size: 0.8rem; }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <div class="top-nav">
        <button class="search-btn" onclick="openSearchModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
            </svg>
            Sipariş Sorgula
        </button>
    </div>

    <!-- Main Content -->
    <div class="main-container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">KIWIPAZARI</h1>
                <p class="card-subtitle">Lütfen ürün anahtarınızı girin</p>
            </div>
            
            <form id="keyValidationForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="form-group">
                    <input type="text" id="key_value" name="key_value" placeholder="Ürün Anahtarı" required>
                    <div id="keyError" class="error hidden"></div>
                </div>
                <button type="submit" class="btn" id="validateBtn">
                    <span id="validateLoader" class="loader hidden"></span>
                    Doğrula
                </button>
            </form>
            
            <div id="orderForm" class="hidden">
                <div id="serviceInfo" class="info"></div>
                <form id="createOrderForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" id="validated_key" name="key_value">
                    <div class="form-group">
                        <input type="number" id="quantity" name="quantity" min="1" placeholder="Miktar" required>
                    </div>
                    <div class="form-group">
                        <input type="url" id="link" name="link" placeholder="Link" required>
                    </div>
                    <button type="submit" class="btn" id="orderBtn">
                        <span id="orderLoader" class="loader hidden"></span>
                        Sipariş Oluştur
                    </button>
                    <div id="orderError" class="error hidden"></div>
                    <div id="orderSuccess" class="success hidden"></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Search Modal -->
    <div id="searchModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Sipariş Sorgula</h2>
                <button class="close-btn" onclick="closeSearchModal()">&times;</button>
            </div>
            <form id="orderSearchForm">
                <div class="form-group">
                    <input type="text" id="search_order_id" name="order_id" placeholder="Sipariş ID (#2384344 veya 2384344)" required>
                </div>
                <button type="submit" class="btn" id="searchBtn">
                    <span id="searchLoader" class="loader hidden"></span>
                    Sorgula
                </button>
            </form>
            <div id="searchResult" class="order-result hidden"></div>
        </div>
    </div>

    <!-- Admin Link -->
    <div class="admin-link">
        <a href="kiwi-management-portal">Admin Panel</a>
    </div>
    
    <script>
        // Modal functions
        function openSearchModal() {
            document.getElementById('searchModal').classList.add('show');
        }
        
        function closeSearchModal() {
            document.getElementById('searchModal').classList.remove('show');
            document.getElementById('searchResult').classList.add('hidden');
            document.getElementById('orderSearchForm').reset();
        }
        
        // Close modal when clicking outside
        document.getElementById('searchModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSearchModal();
            }
        });
        
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