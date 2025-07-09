<?php
require_once '../config.php';
requireAdmin();

// Handle key actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(['error' => 'Invalid CSRF token'], 403);
    }
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_key':
            $keyName = sanitizeInput($_POST['key_name'] ?? '');
            $serviceId = intval($_POST['service_id'] ?? 0);
            $maxAmount = intval($_POST['max_amount'] ?? 0);
            
            if (!$keyName || !$serviceId || !$maxAmount) {
                jsonResponse(['error' => 'Tüm alanlar zorunludur'], 400);
            }
            
            // Check if service exists
            $stmt = $pdo->prepare("SELECT id FROM services WHERE id = ?");
            $stmt->execute([$serviceId]);
            if (!$stmt->fetch()) {
                jsonResponse(['error' => 'Geçersiz servis'], 400);
            }
            
            $keyValue = generateRandomKey();
            
            $stmt = $pdo->prepare("INSERT INTO keys (key_value, key_name, service_id, max_amount) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$keyValue, $keyName, $serviceId, $maxAmount])) {
                jsonResponse(['success' => true, 'message' => 'Key başarıyla oluşturuldu', 'key_value' => $keyValue]);
            } else {
                jsonResponse(['error' => 'Key oluşturulurken hata oluştu'], 500);
            }
            break;
            
        case 'update_key':
            $id = intval($_POST['id'] ?? 0);
            $keyName = sanitizeInput($_POST['key_name'] ?? '');
            $maxAmount = intval($_POST['max_amount'] ?? 0);
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            if (!$id || !$keyName || !$maxAmount) {
                jsonResponse(['error' => 'Tüm alanlar zorunludur'], 400);
            }
            
            $stmt = $pdo->prepare("UPDATE keys SET key_name = ?, max_amount = ?, is_active = ? WHERE id = ?");
            if ($stmt->execute([$keyName, $maxAmount, $isActive, $id])) {
                jsonResponse(['success' => true, 'message' => 'Key başarıyla güncellendi']);
            } else {
                jsonResponse(['error' => 'Key güncellenirken hata oluştu'], 500);
            }
            break;
            
        case 'delete_key':
            $id = intval($_POST['id'] ?? 0);
            
            if (!$id) {
                jsonResponse(['error' => 'Geçersiz key ID'], 400);
            }
            
            $stmt = $pdo->prepare("DELETE FROM keys WHERE id = ?");
            if ($stmt->execute([$id])) {
                jsonResponse(['success' => true, 'message' => 'Key başarıyla silindi']);
            } else {
                jsonResponse(['error' => 'Key silinirken hata oluştu'], 500);
            }
            break;
            
        case 'download_keys':
            $serviceName = sanitizeInput($_POST['service_name'] ?? '');
            
            if (!$serviceName) {
                jsonResponse(['error' => 'Servis adı gereklidir'], 400);
            }
            
            $stmt = $pdo->prepare("
                SELECT k.key_value 
                FROM keys k 
                JOIN services s ON k.service_id = s.id 
                WHERE s.name LIKE ?
            ");
            $stmt->execute(["%$serviceName%"]);
            $keys = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($keys)) {
                jsonResponse(['error' => 'Bu servis adıyla key bulunamadı'], 404);
            }
            
            $content = implode("\n", $keys);
            $filename = "keys_" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $serviceName) . "_" . date('Y-m-d_H-i-s') . ".txt";
            
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($content));
            echo $content;
            exit;
            break;
    }
}

// Get all keys with service info
$stmt = $pdo->query("
    SELECT k.*, s.name as service_name, s.platform, s.category, a.name as api_name
    FROM keys k 
    JOIN services s ON k.service_id = s.id 
    JOIN apis a ON s.api_id = a.id 
    ORDER BY k.created_at DESC
");
$keys = $stmt->fetchAll();

// Get all services for dropdown
$stmt = $pdo->query("
    SELECT s.id, s.name, s.platform, s.category, a.name as api_name
    FROM services s 
    JOIN apis a ON s.api_id = a.id 
    ORDER BY a.name, s.name
");
$services = $stmt->fetchAll();

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Key Yönetimi - KiWiPazari</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #1a1a1a; color: #fff; }
        .header { background: #2a2a2a; padding: 1rem 2rem; border-bottom: 1px solid #444; }
        .header h1 { color: #4a9eff; display: inline-block; }
        .header .nav { float: right; }
        .header .nav a { color: #4a9eff; text-decoration: none; margin-left: 1rem; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .card { background: #2a2a2a; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .form-group { margin-bottom: 1rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 0.75rem; border: 1px solid #555; border-radius: 4px; background: #3a3a3a; color: #fff; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #4a9eff; }
        .btn { padding: 0.75rem 1.5rem; background: #4a9eff; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 0.5rem; font-weight: bold; }
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
        .modal-content { background: #2a2a2a; margin: 5% auto; padding: 2rem; border-radius: 8px; max-width: 600px; }
        .close { float: right; font-size: 1.5rem; cursor: pointer; color: #ccc; }
        .close:hover { color: #fff; }
        .status-active { color: #28a745; font-weight: bold; }
        .status-inactive { color: #dc3545; font-weight: bold; }
        .key-value { font-family: monospace; background: #333; padding: 0.25rem 0.5rem; border-radius: 3px; cursor: pointer; }
        .key-value:hover { background: #4a9eff; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-item { background: #333; padding: 1rem; border-radius: 4px; text-align: center; }
        .stat-item h3 { color: #4a9eff; margin-bottom: 0.5rem; }
        .stat-item .number { font-size: 1.5rem; font-weight: bold; }
        .filter-bar { display: flex; gap: 1rem; margin-bottom: 1rem; align-items: center; }
        .filter-bar input, .filter-bar select { padding: 0.5rem; border: 1px solid #555; border-radius: 4px; background: #3a3a3a; color: #fff; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Key Yönetimi</h1>
        <div class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="api-management.php">API Yönetimi</a>
            <a href="service-management.php">Servis Yönetimi</a>
            <a href="index.php?logout=1">Çıkış</a>
        </div>
        <div style="clear: both;"></div>
    </div>
    
    <div class="container">
        <div class="stats">
            <div class="stat-item">
                <h3>Toplam Key</h3>
                <div class="number"><?php echo count($keys); ?></div>
            </div>
            <div class="stat-item">
                <h3>Aktif Key</h3>
                <div class="number"><?php echo count(array_filter($keys, function($k) { return $k['is_active']; })); ?></div>
            </div>
            <div class="stat-item">
                <h3>Toplam Kullanım</h3>
                <div class="number"><?php echo array_sum(array_column($keys, 'used_amount')); ?></div>
            </div>
            <div class="stat-item">
                <h3>Toplam Kapasite</h3>
                <div class="number"><?php echo array_sum(array_column($keys, 'max_amount')); ?></div>
            </div>
        </div>
        
        <div class="card">
            <h2>Yeni Key Oluştur</h2>
            <form id="keyForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="create_key">
                <div class="form-row">
                    <div class="form-group">
                        <label for="key_name">Key Adı</label>
                        <input type="text" id="key_name" name="key_name" required>
                    </div>
                    <div class="form-group">
                        <label for="max_amount">Maksimum Miktar</label>
                        <input type="number" id="max_amount" name="max_amount" min="1" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="service_id">Servis Seçin</label>
                    <select id="service_id" name="service_id" required>
                        <option value="">Servis seçin...</option>
                        <?php foreach ($services as $service): ?>
                            <option value="<?php echo $service['id']; ?>">
                                <?php echo htmlspecialchars($service['api_name'] . ' - ' . $service['platform'] . ' - ' . $service['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn">Key Oluştur</button>
            </form>
            <div id="keyMessage"></div>
        </div>
        
        <div class="card">
            <h2>Key İndir</h2>
            <form id="downloadForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="download_keys">
                <div class="form-group">
                    <label for="service_name">Servis Adı</label>
                    <input type="text" id="service_name" name="service_name" placeholder="Örn: YouTube Video Likes" required>
                </div>
                <button type="submit" class="btn btn-success">Key'leri İndir</button>
            </form>
            <div id="downloadMessage"></div>
        </div>
        
        <div class="card">
            <h2>Key Listesi</h2>
            <div class="filter-bar">
                <input type="text" id="searchInput" placeholder="Key ara..." onkeyup="filterKeys()">
                <select id="statusFilter" onchange="filterKeys()">
                    <option value="">Tüm Durumlar</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Pasif</option>
                </select>
                <span id="resultCount">Gösterilen: <?php echo count($keys); ?></span>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Key Value</th>
                        <th>Key Adı</th>
                        <th>Servis</th>
                        <th>Platform</th>
                        <th>Kullanım</th>
                        <th>Durum</th>
                        <th>Oluşturulma</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody id="keysTableBody">
                    <?php foreach ($keys as $key): ?>
                    <tr data-status="<?php echo $key['is_active'] ? 'active' : 'inactive'; ?>">
                        <td><?php echo $key['id']; ?></td>
                        <td>
                            <span class="key-value" onclick="copyToClipboard('<?php echo htmlspecialchars($key['key_value']); ?>')" 
                                  title="Kopyalamak için tıklayın">
                                <?php echo htmlspecialchars($key['key_value']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($key['key_name']); ?></td>
                        <td title="<?php echo htmlspecialchars($key['service_name']); ?>">
                            <?php echo htmlspecialchars(substr($key['service_name'], 0, 50) . (strlen($key['service_name']) > 50 ? '...' : '')); ?>
                        </td>
                        <td><?php echo htmlspecialchars($key['platform']); ?></td>
                        <td><?php echo $key['used_amount']; ?>/<?php echo $key['max_amount']; ?></td>
                        <td>
                            <span class="status-<?php echo $key['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $key['is_active'] ? 'Aktif' : 'Pasif'; ?>
                            </span>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($key['created_at'])); ?></td>
                        <td>
                            <button class="btn" onclick="editKey(<?php echo $key['id']; ?>, '<?php echo htmlspecialchars($key['key_name']); ?>', <?php echo $key['max_amount']; ?>, <?php echo $key['is_active']; ?>)">
                                Düzenle
                            </button>
                            <button class="btn btn-danger" onclick="deleteKey(<?php echo $key['id']; ?>)">
                                Sil
                            </button>
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
            <h2>Key Düzenle</h2>
            <form id="editForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="update_key">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_key_name">Key Adı</label>
                    <input type="text" id="edit_key_name" name="key_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_max_amount">Maksimum Miktar</label>
                    <input type="number" id="edit_max_amount" name="max_amount" min="1" required>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="edit_is_active" name="is_active"> Aktif
                    </label>
                </div>
                <button type="submit" class="btn">Güncelle</button>
            </form>
            <div id="editMessage"></div>
        </div>
    </div>
    
    <script>
        // Key Form Submit
        document.getElementById('keyForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const messageDiv = document.getElementById('keyMessage');
            
            try {
                const response = await fetch('key-management.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    messageDiv.innerHTML = `<div class="success">${result.message}<br>Key: <strong>${result.key_value}</strong></div>`;
                    this.reset();
                    setTimeout(() => location.reload(), 2000);
                } else {
                    messageDiv.innerHTML = `<div class="error">${result.error}</div>`;
                }
            } catch (err) {
                messageDiv.innerHTML = '<div class="error">Bir hata oluştu</div>';
            }
        });
        
        // Download Form Submit
        document.getElementById('downloadForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const messageDiv = document.getElementById('downloadMessage');
            
            try {
                const response = await fetch('key-management.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.headers.get('content-type') === 'text/plain') {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = response.headers.get('content-disposition').split('filename=')[1].replace(/"/g, '');
                    a.click();
                    window.URL.revokeObjectURL(url);
                    messageDiv.innerHTML = '<div class="success">Key\'ler başarıyla indirildi</div>';
                } else {
                    const result = await response.json();
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
                const response = await fetch('key-management.php', {
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
        
        function editKey(id, keyName, maxAmount, isActive) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_key_name').value = keyName;
            document.getElementById('edit_max_amount').value = maxAmount;
            document.getElementById('edit_is_active').checked = isActive;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function deleteKey(id) {
            if (confirm('Bu key\'i silmek istediğinizden emin misiniz?')) {
                const formData = new FormData();
                formData.append('csrf_token', '<?php echo $csrf_token; ?>');
                formData.append('action', 'delete_key');
                formData.append('id', id);
                
                fetch('key-management.php', {
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
        
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Key kopyalandı!');
            });
        }
        
        function filterKeys() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            
            const rows = document.querySelectorAll('#keysTableBody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const keyValue = row.cells[1].textContent.toLowerCase();
                const keyName = row.cells[2].textContent.toLowerCase();
                const serviceName = row.cells[3].textContent.toLowerCase();
                const status = row.getAttribute('data-status');
                
                const matchesSearch = keyValue.includes(searchTerm) || keyName.includes(searchTerm) || serviceName.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;
                
                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            document.getElementById('resultCount').textContent = `Gösterilen: ${visibleCount}`;
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>