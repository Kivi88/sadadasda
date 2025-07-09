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
                $url = sanitizeInput($_POST['url']);
                $apiKey = sanitizeInput($_POST['api_key']);
                
                if ($name && $url && $apiKey) {
                    try {
                        $stmt = $db->prepare("INSERT INTO apis (name, url, api_key) VALUES (?, ?, ?)");
                        $stmt->execute([$name, $url, $apiKey]);
                        $message = 'API başarıyla eklendi';
                        $messageType = 'success';
                    } catch (Exception $e) {
                        $message = 'API eklenirken hata: ' . $e->getMessage();
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                try {
                    $stmt = $db->prepare("DELETE FROM apis WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = 'API silindi';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'API silinirken hata: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;
                
            case 'toggle':
                $id = intval($_POST['id']);
                try {
                    $stmt = $db->prepare("UPDATE apis SET is_active = NOT is_active WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = 'API durumu güncellendi';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'API durumu güncellenirken hata: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Get APIs
try {
    $stmt = $db->query("SELECT * FROM apis ORDER BY created_at DESC");
    $apis = $stmt->fetchAll();
} catch (Exception $e) {
    $apis = [];
    $message = 'API\'ler yüklenirken hata: ' . $e->getMessage();
    $messageType = 'error';
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Yönetimi - KiWiPazari</title>
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
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            font-size: 1rem;
        }
        
        .form-group input:focus {
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
        <h1>API Yönetimi</h1>
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
            <h2>Yeni API Ekle</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">API Adı</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="url">API URL</label>
                        <input type="url" id="url" name="url" required>
                    </div>
                    <div class="form-group">
                        <label for="api_key">API Anahtarı</label>
                        <input type="text" id="api_key" name="api_key" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">API Ekle</button>
            </form>
        </div>
        
        <div class="card">
            <h2>API Listesi</h2>
            <?php if (empty($apis)): ?>
                <p style="color: #999;">Henüz API eklenmemiş.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Adı</th>
                            <th>URL</th>
                            <th>Durum</th>
                            <th>Oluşturma Tarihi</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($apis as $api): ?>
                            <tr>
                                <td><?php echo $api['id']; ?></td>
                                <td><?php echo htmlspecialchars($api['name']); ?></td>
                                <td><?php echo htmlspecialchars($api['url']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $api['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $api['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($api['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?php echo $api['id']; ?>">
                                            <button type="submit" class="btn <?php echo $api['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                                <?php echo $api['is_active'] ? 'Pasif Et' : 'Aktif Et'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Bu API\'yi silmek istediğinizden emin misiniz?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $api['id']; ?>">
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