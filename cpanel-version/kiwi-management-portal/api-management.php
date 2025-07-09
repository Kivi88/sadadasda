<?php
require_once '../config.php';
requireAdmin();

// Handle API actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(['error' => 'Invalid CSRF token'], 403);
    }
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_api':
            $name = sanitizeInput($_POST['name'] ?? '');
            $url = sanitizeInput($_POST['url'] ?? '');
            $api_key = sanitizeInput($_POST['api_key'] ?? '');
            
            if (!$name || !$url || !$api_key) {
                jsonResponse(['error' => 'Tüm alanlar zorunludur'], 400);
            }
            
            $stmt = $pdo->prepare("INSERT INTO apis (name, url, api_key) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $url, $api_key])) {
                jsonResponse(['success' => true, 'message' => 'API başarıyla eklendi']);
            } else {
                jsonResponse(['error' => 'API eklenirken hata oluştu'], 500);
            }
            break;
            
        case 'update_api':
            $id = intval($_POST['id'] ?? 0);
            $name = sanitizeInput($_POST['name'] ?? '');
            $url = sanitizeInput($_POST['url'] ?? '');
            $api_key = sanitizeInput($_POST['api_key'] ?? '');
            
            if (!$id || !$name || !$url || !$api_key) {
                jsonResponse(['error' => 'Tüm alanlar zorunludur'], 400);
            }
            
            $stmt = $pdo->prepare("UPDATE apis SET name = ?, url = ?, api_key = ? WHERE id = ?");
            if ($stmt->execute([$name, $url, $api_key, $id])) {
                jsonResponse(['success' => true, 'message' => 'API başarıyla güncellendi']);
            } else {
                jsonResponse(['error' => 'API güncellenirken hata oluştu'], 500);
            }
            break;
            
        case 'delete_api':
            $id = intval($_POST['id'] ?? 0);
            
            if (!$id) {
                jsonResponse(['error' => 'Geçersiz API ID'], 400);
            }
            
            $stmt = $pdo->prepare("DELETE FROM apis WHERE id = ?");
            if ($stmt->execute([$id])) {
                jsonResponse(['success' => true, 'message' => 'API başarıyla silindi']);
            } else {
                jsonResponse(['error' => 'API silinirken hata oluştu'], 500);
            }
            break;
            
        case 'test_api':
            $id = intval($_POST['id'] ?? 0);
            
            if (!$id) {
                jsonResponse(['error' => 'Geçersiz API ID'], 400);
            }
            
            $stmt = $pdo->prepare("SELECT * FROM apis WHERE id = ?");
            $stmt->execute([$id]);
            $api = $stmt->fetch();
            
            if (!$api) {
                jsonResponse(['error' => 'API bulunamadı'], 404);
            }
            
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api['url']);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                    'key' => $api['api_key'],
                    'action' => 'services'
                ]));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200) {
                    $responseData = json_decode($response, true);
                    if (is_array($responseData) && !empty($responseData)) {
                        jsonResponse(['success' => true, 'message' => 'API testi başarılı', 'services_count' => count($responseData)]);
                    } else {
                        jsonResponse(['error' => 'API geçersiz yanıt döndü'], 400);
                    }
                } else {
                    jsonResponse(['error' => "API testi başarısız (HTTP $httpCode)"], 400);
                }
            } catch (Exception $e) {
                jsonResponse(['error' => 'API testi başarısız: ' . $e->getMessage()], 500);
            }
            break;
            
        case 'fetch_services':
            $id = intval($_POST['id'] ?? 0);
            $limit = intval($_POST['limit'] ?? 0);
            
            if (!$id) {
                jsonResponse(['error' => 'Geçersiz API ID'], 400);
            }
            
            $stmt = $pdo->prepare("SELECT * FROM apis WHERE id = ?");
            $stmt->execute([$id]);
            $api = $stmt->fetch();
            
            if (!$api) {
                jsonResponse(['error' => 'API bulunamadı'], 404);
            }
            
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api['url']);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                    'key' => $api['api_key'],
                    'action' => 'services'
                ]));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200) {
                    $responseData = json_decode($response, true);
                    
                    // Handle different response formats
                    if (isset($responseData['services'])) {
                        $services = $responseData['services'];
                    } elseif (isset($responseData['data'])) {
                        $services = $responseData['data'];
                    } elseif (isset($responseData['result'])) {
                        $services = $responseData['result'];
                    } else {
                        $services = $responseData;
                    }
                    
                    if (!is_array($services) || empty($services)) {
                        jsonResponse(['error' => 'API\'dan servis verisi alınamadı'], 400);
                    }
                    
                    // Apply limit if specified
                    if ($limit > 0) {
                        $services = array_slice($services, 0, $limit);
                    }
                    
                    $processed = 0;
                    $skipped = 0;
                    
                    foreach ($services as $service) {
                        // Extract service ID from various possible field names
                        $serviceId = $service['service'] ?? $service['id'] ?? $service['serviceId'] ?? $service['service_id'] ?? null;
                        
                        if (!$serviceId) {
                            $skipped++;
                            continue;
                        }
                        
                        $name = $service['name'] ?? 'Unknown Service';
                        $category = $service['category'] ?? null;
                        $platform = $service['platform'] ?? null;
                        $minAmount = intval($service['min'] ?? $service['min_amount'] ?? 1);
                        $maxAmount = intval($service['max'] ?? $service['max_amount'] ?? 10000);
                        $price = floatval($service['rate'] ?? $service['price'] ?? 0);
                        
                        // Check if service already exists
                        $stmt = $pdo->prepare("SELECT id FROM services WHERE api_id = ? AND external_id = ?");
                        $stmt->execute([$api['id'], $serviceId]);
                        
                        if ($stmt->fetch()) {
                            // Update existing service
                            $stmt = $pdo->prepare("
                                UPDATE services 
                                SET name = ?, category = ?, platform = ?, min_amount = ?, max_amount = ?, price = ?
                                WHERE api_id = ? AND external_id = ?
                            ");
                            $stmt->execute([$name, $category, $platform, $minAmount, $maxAmount, $price, $api['id'], $serviceId]);
                        } else {
                            // Insert new service
                            $stmt = $pdo->prepare("
                                INSERT INTO services (api_id, external_id, name, category, platform, min_amount, max_amount, price)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([$api['id'], $serviceId, $name, $category, $platform, $minAmount, $maxAmount, $price]);
                        }
                        
                        $processed++;
                    }
                    
                    jsonResponse([
                        'success' => true,
                        'message' => "Servis çekme tamamlandı",
                        'processed' => $processed,
                        'skipped' => $skipped,
                        'total' => count($services)
                    ]);
                } else {
                    jsonResponse(['error' => "API isteği başarısız (HTTP $httpCode)"], 400);
                }
            } catch (Exception $e) {
                jsonResponse(['error' => 'Servis çekme başarısız: ' . $e->getMessage()], 500);
            }
            break;
    }
}

// Get all APIs
$stmt = $pdo->query("SELECT * FROM apis ORDER BY created_at DESC");
$apis = $stmt->fetchAll();

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Yönetimi - KiWiPazari</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #1a1a1a; color: #fff; }
        .header { background: #2a2a2a; padding: 1rem 2rem; border-bottom: 1px solid #444; }
        .header h1 { color: #4a9eff; display: inline-block; }
        .header .nav { float: right; }
        .header .nav a { color: #4a9eff; text-decoration: none; margin-left: 1rem; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .card { background: #2a2a2a; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 0.5rem; border: 1px solid #555; border-radius: 4px; background: #3a3a3a; color: #fff; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #4a9eff; }
        .btn { padding: 0.5rem 1rem; background: #4a9eff; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 0.5rem; }
        .btn:hover { background: #357abd; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .table th, .table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #444; }
        .table th { background: #333; }
        .table tr:hover { background: #333; }
        .success { color: #28a745; margin-top: 0.5rem; }
        .error { color: #dc3545; margin-top: 0.5rem; }
        .loader { border: 2px solid #333; border-top: 2px solid #4a9eff; border-radius: 50%; width: 16px; height: 16px; animation: spin 1s linear infinite; display: inline-block; margin-left: 5px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .hidden { display: none; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; }
        .modal-content { background: #2a2a2a; margin: 10% auto; padding: 2rem; border-radius: 8px; max-width: 500px; }
        .close { float: right; font-size: 1.5rem; cursor: pointer; color: #ccc; }
        .close:hover { color: #fff; }
    </style>
</head>
<body>
    <div class="header">
        <h1>API Yönetimi</h1>
        <div class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="index.php?logout=1">Çıkış</a>
        </div>
        <div style="clear: both;"></div>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>Yeni API Ekle</h2>
            <form id="apiForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="create_api">
                <div class="form-group">
                    <label for="name">API Adı</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="url">API URL</label>
                    <input type="url" id="url" name="url" required>
                </div>
                <div class="form-group">
                    <label for="api_key">API Key</label>
                    <input type="text" id="api_key" name="api_key" required>
                </div>
                <button type="submit" class="btn">API Ekle</button>
            </form>
            <div id="apiMessage"></div>
        </div>
        
        <div class="card">
            <h2>API Listesi</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ad</th>
                        <th>URL</th>
                        <th>Ekleme Tarihi</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apis as $api): ?>
                    <tr>
                        <td><?php echo $api['id']; ?></td>
                        <td><?php echo htmlspecialchars($api['name']); ?></td>
                        <td><?php echo htmlspecialchars($api['url']); ?></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($api['created_at'])); ?></td>
                        <td>
                            <button class="btn btn-success" onclick="testApi(<?php echo $api['id']; ?>)">Test</button>
                            <button class="btn" onclick="fetchServices(<?php echo $api['id']; ?>)">Servisleri Çek</button>
                            <button class="btn" onclick="editApi(<?php echo $api['id']; ?>, '<?php echo htmlspecialchars($api['name']); ?>', '<?php echo htmlspecialchars($api['url']); ?>', '<?php echo htmlspecialchars($api['api_key']); ?>')">Düzenle</button>
                            <button class="btn btn-danger" onclick="deleteApi(<?php echo $api['id']; ?>)">Sil</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>API Düzenle</h2>
            <form id="editForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="update_api">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_name">API Adı</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="edit_url">API URL</label>
                    <input type="url" id="edit_url" name="url" required>
                </div>
                <div class="form-group">
                    <label for="edit_api_key">API Key</label>
                    <input type="text" id="edit_api_key" name="api_key" required>
                </div>
                <button type="submit" class="btn">Güncelle</button>
            </form>
            <div id="editMessage"></div>
        </div>
    </div>
    
    <!-- Fetch Services Modal -->
    <div id="fetchModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeFetchModal()">&times;</span>
            <h2>Servisleri Çek</h2>
            <form id="fetchForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="fetch_services">
                <input type="hidden" id="fetch_api_id" name="id">
                <div class="form-group">
                    <label for="limit">Limit (0 = Sınırsız)</label>
                    <input type="number" id="limit" name="limit" value="0" min="0">
                </div>
                <button type="submit" class="btn">Servisleri Çek</button>
            </form>
            <div id="fetchMessage"></div>
        </div>
    </div>
    
    <script>
        // API Form Submit
        document.getElementById('apiForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const messageDiv = document.getElementById('apiMessage');
            
            try {
                const response = await fetch('api-management.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    messageDiv.innerHTML = `<div class="success">${result.message}</div>`;
                    this.reset();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    messageDiv.innerHTML = `<div class="error">${result.error}</div>`;
                }
            } catch (err) {
                messageDiv.innerHTML = '<div class="error">Bir hata oluştu</div>';
            }
        });
        
        // Edit Form Submit
        document.getElementById('editForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const messageDiv = document.getElementById('editMessage');
            
            try {
                const response = await fetch('api-management.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    messageDiv.innerHTML = `<div class="success">${result.message}</div>`;
                    setTimeout(() => location.reload(), 1000);
                } else {
                    messageDiv.innerHTML = `<div class="error">${result.error}</div>`;
                }
            } catch (err) {
                messageDiv.innerHTML = '<div class="error">Bir hata oluştu</div>';
            }
        });
        
        // Fetch Form Submit
        document.getElementById('fetchForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const messageDiv = document.getElementById('fetchMessage');
            const submitBtn = this.querySelector('button[type="submit"]');
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Çekiliyor... <span class="loader"></span>';
            
            try {
                const response = await fetch('api-management.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    messageDiv.innerHTML = `<div class="success">${result.message}<br>İşlenen: ${result.processed}, Atlanan: ${result.skipped}, Toplam: ${result.total}</div>`;
                } else {
                    messageDiv.innerHTML = `<div class="error">${result.error}</div>`;
                }
            } catch (err) {
                messageDiv.innerHTML = '<div class="error">Bir hata oluştu</div>';
            }
            
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Servisleri Çek';
        });
        
        function testApi(id) {
            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $csrf_token; ?>');
            formData.append('action', 'test_api');
            formData.append('id', id);
            
            fetch('api-management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert(`API testi başarılı! ${result.services_count} servis bulundu.`);
                } else {
                    alert(`API testi başarısız: ${result.error}`);
                }
            })
            .catch(err => {
                alert('Bir hata oluştu');
            });
        }
        
        function editApi(id, name, url, apiKey) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_url').value = url;
            document.getElementById('edit_api_key').value = apiKey;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function deleteApi(id) {
            if (confirm('Bu API\'yi silmek istediğinizden emin misiniz?')) {
                const formData = new FormData();
                formData.append('csrf_token', '<?php echo $csrf_token; ?>');
                formData.append('action', 'delete_api');
                formData.append('id', id);
                
                fetch('api-management.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert(result.message);
                        location.reload();
                    } else {
                        alert(result.error);
                    }
                })
                .catch(err => {
                    alert('Bir hata oluştu');
                });
            }
        }
        
        function fetchServices(id) {
            document.getElementById('fetch_api_id').value = id;
            document.getElementById('fetchModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        function closeFetchModal() {
            document.getElementById('fetchModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const fetchModal = document.getElementById('fetchModal');
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
            if (event.target === fetchModal) {
                fetchModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>