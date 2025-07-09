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
                $apiId = intval($_POST['api_id']);
                $externalId = sanitizeInput($_POST['external_id']);
                $name = sanitizeInput($_POST['name']);
                $platform = sanitizeInput($_POST['platform']);
                $category = sanitizeInput($_POST['category']);
                $minQuantity = intval($_POST['min_quantity']);
                $maxQuantity = intval($_POST['max_quantity']);
                
                if ($apiId && $externalId && $name && $platform && $category) {
                    try {
                        $stmt = $db->prepare("INSERT INTO services (api_id, external_id, name, platform, category, min_quantity, max_quantity) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$apiId, $externalId, $name, $platform, $category, $minQuantity, $maxQuantity]);
                        $message = 'Servis başarıyla eklendi';
                        $messageType = 'success';
                    } catch (Exception $e) {
                        $message = 'Servis eklenirken hata: ' . $e->getMessage();
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                try {
                    $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = 'Servis silindi';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Servis silinirken hata: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;
                
            case 'toggle':
                $id = intval($_POST['id']);
                try {
                    $stmt = $db->prepare("UPDATE services SET is_active = NOT is_active WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = 'Servis durumu güncellendi';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Servis durumu güncellenirken hata: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Get APIs for dropdown
try {
    $stmt = $db->query("SELECT id, name FROM apis WHERE is_active = 1 ORDER BY name");
    $apis = $stmt->fetchAll();
} catch (Exception $e) {
    $apis = [];
}

// Get services
try {
    $stmt = $db->query("SELECT s.*, a.name as api_name FROM services s LEFT JOIN apis a ON s.api_id = a.id ORDER BY s.created_at DESC");
    $services = $stmt->fetchAll();
} catch (Exception $e) {
    $services = [];
    $message = 'Servisler yüklenirken hata: ' . $e->getMessage();
    $messageType = 'error';
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servis Yönetimi - KiWiPazari</title>
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
        <h1>Servis Yönetimi</h1>
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
            <h2>Yeni Servis Ekle</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="api_id">API</label>
                        <select id="api_id" name="api_id" required>
                            <option value="">API Seçin</option>
                            <?php foreach ($apis as $api): ?>
                                <option value="<?php echo $api['id']; ?>"><?php echo htmlspecialchars($api['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="external_id">Harici ID</label>
                        <input type="text" id="external_id" name="external_id" required>
                    </div>
                    <div class="form-group">
                        <label for="name">Servis Adı</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="platform">Platform</label>
                        <input type="text" id="platform" name="platform" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Kategori</label>
                        <input type="text" id="category" name="category" required>
                    </div>
                    <div class="form-group">
                        <label for="min_quantity">Min Miktar</label>
                        <input type="number" id="min_quantity" name="min_quantity" value="1" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="max_quantity">Max Miktar</label>
                        <input type="number" id="max_quantity" name="max_quantity" value="10000" min="1" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Servis Ekle</button>
            </form>
        </div>
        
        <div class="card">
            <h2>Servis Listesi</h2>
            <?php if (empty($services)): ?>
                <p style="color: #999;">Henüz servis eklenmemiş.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>API</th>
                            <th>Servis Adı</th>
                            <th>Platform</th>
                            <th>Kategori</th>
                            <th>Min/Max</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td><?php echo $service['id']; ?></td>
                                <td><?php echo htmlspecialchars($service['api_name']); ?></td>
                                <td><?php echo htmlspecialchars($service['name']); ?></td>
                                <td><?php echo htmlspecialchars($service['platform']); ?></td>
                                <td><?php echo htmlspecialchars($service['category']); ?></td>
                                <td><?php echo number_format($service['min_quantity']) . ' - ' . number_format($service['max_quantity']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $service['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $service['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?php echo $service['id']; ?>">
                                            <button type="submit" class="btn <?php echo $service['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                                <?php echo $service['is_active'] ? 'Pasif Et' : 'Aktif Et'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Bu servisi silmek istediğinizden emin misiniz?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $service['id']; ?>">
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
</body>
</html>