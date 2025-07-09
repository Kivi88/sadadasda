<?php
require_once 'config.php';
requireAdminLogin();

$db = getDatabase();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'add':
                $name = sanitizeInput($_POST['name']);
                $serviceId = intval($_POST['service_id']);
                $maxAmount = intval($_POST['max_amount']);
                
                if ($name && $serviceId && $maxAmount > 0) {
                    // Generate unique key
                    $keyValue = 'KIWIPAZARI' . strtoupper(substr(md5(time() . rand()), 0, 8));
                    
                    try {
                        $stmt = $db->prepare("INSERT INTO `keys` (key_value, name, service_id, max_amount) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$keyValue, $name, $serviceId, $maxAmount]);
                        $message = 'Anahtar başarıyla oluşturuldu: ' . $keyValue;
                        $messageType = 'success';
                    } catch (Exception $e) {
                        $message = 'Anahtar oluşturulurken hata: ' . $e->getMessage();
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                try {
                    $stmt = $db->prepare("DELETE FROM `keys` WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = 'Anahtar silindi';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Anahtar silinirken hata: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;
                
            case 'toggle':
                $id = intval($_POST['id']);
                try {
                    $stmt = $db->prepare("UPDATE `keys` SET is_active = NOT is_active WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = 'Anahtar durumu güncellendi';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Anahtar durumu güncellenirken hata: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Get services for dropdown
try {
    $stmt = $db->query("SELECT id, name, platform, category FROM services WHERE is_active = 1 ORDER BY name");
    $services = $stmt->fetchAll();
} catch (Exception $e) {
    $services = [];
}

// Get keys
try {
    $stmt = $db->query("SELECT k.*, s.name as service_name, s.platform, s.category FROM `keys` k LEFT JOIN services s ON k.service_id = s.id ORDER BY k.created_at DESC");
    $keys = $stmt->fetchAll();
} catch (Exception $e) {
    $keys = [];
    $message = 'Anahtarlar yüklenirken hata: ' . $e->getMessage();
    $messageType = 'error';
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anahtar Yönetimi - KiWiPazari</title>
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
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .card {
            background: rgba(45, 45, 45, 0.9);
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
        }
        
        .card h2 {
            color: #4A90E2;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #ddd;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            font-size: 1rem;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4A90E2;
            background: rgba(255, 255, 255, 0.15);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #4A90E2;
            color: white;
        }
        
        .btn-primary:hover {
            background: #357abd;
            transform: translateY(-1px);
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-warning:hover {
            background: #e67e22;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .table th {
            background: rgba(255, 255, 255, 0.05);
            color: #4A90E2;
            font-weight: 600;
        }
        
        .table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .key-value {
            font-family: monospace;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
            cursor: pointer;
        }
        
        .key-value:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active {
            background: rgba(39, 174, 96, 0.2);
            color: #27ae60;
        }
        
        .status-inactive {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .alert-success {
            background: rgba(46, 204, 113, 0.2);
            border: 1px solid rgba(46, 204, 113, 0.3);
            color: #51cf66;
        }
        
        .alert-error {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid rgba(231, 76, 60, 0.3);
            color: #ff6b6b;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .back-link {
            margin-bottom: 2rem;
        }
        
        .back-link a {
            color: #4A90E2;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .back-link a:hover {
            color: #357abd;
        }
        
        .usage-progress {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            height: 8px;
            overflow: hidden;
            margin-top: 0.25rem;
        }
        
        .usage-progress-bar {
            height: 100%;
            background: #4A90E2;
            transition: width 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .table {
                font-size: 0.9rem;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Anahtar Yönetimi</h1>
        <a href="admin-dashboard.php" class="btn btn-primary">← Admin Panel</a>
    </div>
    
    <div class="container">
        <div class="back-link">
            <a href="admin-dashboard.php">← Admin Dashboard'a Dön</a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Yeni Anahtar Oluştur</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Anahtar Adı</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="service_id">Servis</label>
                        <select id="service_id" name="service_id" required>
                            <option value="">Servis Seçin</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>">
                                    <?php echo htmlspecialchars($service['platform'] . ' - ' . $service['category']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="max_amount">Maksimum Miktar</label>
                        <input type="number" id="max_amount" name="max_amount" value="1000" min="1" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Anahtar Oluştur</button>
            </form>
        </div>
        
        <div class="card">
            <h2>Anahtar Listesi</h2>
            <?php if (empty($keys)): ?>
                <p style="color: #999;">Henüz anahtar oluşturulmamış.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Anahtar Değeri</th>
                            <th>Adı</th>
                            <th>Servis</th>
                            <th>Kullanım</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($keys as $key): ?>
                            <?php
                            $usagePercent = $key['max_amount'] > 0 ? (($key['used_amount'] ?? 0) / $key['max_amount']) * 100 : 0;
                            ?>
                            <tr>
                                <td><?php echo $key['id']; ?></td>
                                <td>
                                    <span class="key-value" onclick="copyToClipboard('<?php echo $key['key_value']; ?>')" title="Kopyalamak için tıklayın">
                                        <?php echo htmlspecialchars($key['key_value']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($key['name']); ?></td>
                                <td><?php echo htmlspecialchars($key['platform'] . ' - ' . $key['category']); ?></td>
                                <td>
                                    <?php echo number_format($key['used_amount'] ?? 0) . ' / ' . number_format($key['max_amount']); ?>
                                    <div class="usage-progress">
                                        <div class="usage-progress-bar" style="width: <?php echo $usagePercent; ?>%"></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $key['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $key['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?php echo $key['id']; ?>">
                                            <button type="submit" class="btn <?php echo $key['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                                <?php echo $key['is_active'] ? 'Pasif Et' : 'Aktif Et'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Bu anahtarı silmek istediğinizden emin misiniz?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $key['id']; ?>">
                                            <button type="submit" class="btn btn-danger">Sil</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Visual feedback
                const element = event.target;
                const originalBg = element.style.backgroundColor;
                element.style.backgroundColor = 'rgba(74, 144, 226, 0.3)';
                setTimeout(() => {
                    element.style.backgroundColor = originalBg;
                }, 300);
            });
        }
    </script>
</body>
</html>