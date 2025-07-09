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
            case 'update_status':
                $id = intval($_POST['id']);
                $status = sanitizeInput($_POST['status']);
                
                if ($id && in_array($status, ['pending', 'processing', 'completed', 'cancelled'])) {
                    try {
                        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
                        $stmt->execute([$status, $id]);
                        $message = 'Sipariş durumu güncellendi';
                        $messageType = 'success';
                    } catch (Exception $e) {
                        $message = 'Sipariş durumu güncellenirken hata: ' . $e->getMessage();
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                try {
                    $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = 'Sipariş silindi';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Sipariş silinirken hata: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Get orders with related data
try {
    $stmt = $db->query("
        SELECT o.*, 
               s.name as service_name, s.platform, s.category,
               k.name as key_name, k.key_value
        FROM orders o 
        LEFT JOIN services s ON o.service_id = s.id 
        LEFT JOIN `keys` k ON o.key_id = k.id 
        ORDER BY o.created_at DESC
    ");
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    $orders = [];
    $message = 'Siparişler yüklenirken hata: ' . $e->getMessage();
    $messageType = 'error';
}

// Get statistics
try {
    $stats = [
        'total' => $db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
        'pending' => $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
        'processing' => $db->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'")->fetchColumn(),
        'completed' => $db->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn(),
        'cancelled' => $db->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'")->fetchColumn(),
    ];
} catch (Exception $e) {
    $stats = ['total' => 0, 'pending' => 0, 'processing' => 0, 'completed' => 0, 'cancelled' => 0];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Yönetimi - KiWiPazari</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(45, 45, 45, 0.9);
            padding: 1.5rem;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #4A90E2;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
        
        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: #ffffff;
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
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
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
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.9rem;
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
            text-transform: uppercase;
        }
        
        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }
        
        .status-processing {
            background: rgba(0, 123, 255, 0.2);
            color: #007bff;
        }
        
        .status-completed {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }
        
        .status-cancelled {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }
        
        .order-id {
            font-family: monospace;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
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
            align-items: center;
        }
        
        .actions select {
            padding: 0.25rem 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            font-size: 0.8rem;
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
        
        .link-preview {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .table {
                font-size: 0.8rem;
            }
            
            .actions {
                flex-direction: column;
                gap: 0.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sipariş Yönetimi</h1>
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
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Toplam Sipariş</h3>
                <div class="number"><?php echo number_format($stats['total']); ?></div>
            </div>
            <div class="stat-card">
                <h3>Beklemede</h3>
                <div class="number"><?php echo number_format($stats['pending']); ?></div>
            </div>
            <div class="stat-card">
                <h3>İşleniyor</h3>
                <div class="number"><?php echo number_format($stats['processing']); ?></div>
            </div>
            <div class="stat-card">
                <h3>Tamamlandı</h3>
                <div class="number"><?php echo number_format($stats['completed']); ?></div>
            </div>
            <div class="stat-card">
                <h3>İptal Edildi</h3>
                <div class="number"><?php echo number_format($stats['cancelled']); ?></div>
            </div>
        </div>
        
        <div class="card">
            <h2>Sipariş Listesi</h2>
            <?php if (empty($orders)): ?>
                <p style="color: #999;">Henüz sipariş bulunmuyor.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Sipariş ID</th>
                            <th>Servis</th>
                            <th>Anahtar</th>
                            <th>Link</th>
                            <th>Miktar</th>
                            <th>Durum</th>
                            <th>Tarih</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <span class="order-id"><?php echo htmlspecialchars($order['order_id']); ?></span>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($order['platform']); ?></div>
                                    <div style="color: #999; font-size: 0.8rem;"><?php echo htmlspecialchars($order['category']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($order['key_name']); ?></td>
                                <td>
                                    <div class="link-preview" title="<?php echo htmlspecialchars($order['link']); ?>">
                                        <?php echo htmlspecialchars($order['link']); ?>
                                    </div>
                                </td>
                                <td><?php echo number_format($order['quantity']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php 
                                        $statusMap = [
                                            'pending' => 'Beklemede',
                                            'processing' => 'İşleniyor',
                                            'completed' => 'Tamamlandı',
                                            'cancelled' => 'İptal Edildi'
                                        ];
                                        echo $statusMap[$order['status']] ?? $order['status'];
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
                                            <select name="status" onchange="this.form.submit()">
                                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Beklemede</option>
                                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>İşleniyor</option>
                                                <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Tamamlandı</option>
                                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>İptal Edildi</option>
                                            </select>
                                        </form>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Bu siparişi silmek istediğinizden emin misiniz?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
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