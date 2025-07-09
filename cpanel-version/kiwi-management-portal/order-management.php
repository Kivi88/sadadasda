<?php
require_once '../config.php';
requireAdmin();

// Get all orders with related info
$stmt = $pdo->query("
    SELECT o.*, s.name as service_name, s.platform, k.key_value, k.key_name, a.name as api_name
    FROM orders o 
    JOIN services s ON o.service_id = s.id 
    JOIN keys k ON o.key_id = k.id 
    JOIN apis a ON s.api_id = a.id 
    ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll();

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Yönetimi - KiWiPazari</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #1a1a1a; color: #fff; }
        .header { background: #2a2a2a; padding: 1rem 2rem; border-bottom: 1px solid #444; }
        .header h1 { color: #4a9eff; display: inline-block; }
        .header .nav { float: right; }
        .header .nav a { color: #4a9eff; text-decoration: none; margin-left: 1rem; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .card { background: #2a2a2a; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-item { background: #333; padding: 1rem; border-radius: 4px; text-align: center; }
        .stat-item h3 { color: #4a9eff; margin-bottom: 0.5rem; }
        .stat-item .number { font-size: 1.5rem; font-weight: bold; }
        .filter-bar { display: flex; gap: 1rem; margin-bottom: 1rem; align-items: center; }
        .filter-bar input, .filter-bar select { padding: 0.5rem; border: 1px solid #555; border-radius: 4px; background: #3a3a3a; color: #fff; }
        .table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .table th, .table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #444; }
        .table th { background: #333; }
        .table tr:hover { background: #333; }
        .table-container { max-height: 600px; overflow-y: auto; }
        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.9rem; font-weight: bold; }
        .status-pending { background: #f59e0b; color: #000; }
        .status-processing { background: #3b82f6; color: #fff; }
        .status-completed { background: #10b981; color: #fff; }
        .status-cancelled { background: #ef4444; color: #fff; }
        .status-error { background: #ef4444; color: #fff; }
        .text-truncate { max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .order-id { font-family: monospace; background: #333; padding: 0.25rem 0.5rem; border-radius: 3px; cursor: pointer; }
        .order-id:hover { background: #4a9eff; }
        .pagination { display: flex; justify-content: center; gap: 0.5rem; margin-top: 1rem; }
        .pagination button { padding: 0.5rem 1rem; background: #3a3a3a; color: #fff; border: 1px solid #555; border-radius: 4px; cursor: pointer; }
        .pagination button:hover { background: #4a9eff; }
        .pagination button.active { background: #4a9eff; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sipariş Yönetimi</h1>
        <div class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="api-management.php">API Yönetimi</a>
            <a href="service-management.php">Servis Yönetimi</a>
            <a href="key-management.php">Key Yönetimi</a>
            <a href="index.php?logout=1">Çıkış</a>
        </div>
        <div style="clear: both;"></div>
    </div>
    
    <div class="container">
        <div class="stats">
            <div class="stat-item">
                <h3>Toplam Sipariş</h3>
                <div class="number"><?php echo count($orders); ?></div>
            </div>
            <div class="stat-item">
                <h3>Bekleyen</h3>
                <div class="number"><?php echo count(array_filter($orders, function($o) { return $o['status'] === 'pending'; })); ?></div>
            </div>
            <div class="stat-item">
                <h3>İşlenen</h3>
                <div class="number"><?php echo count(array_filter($orders, function($o) { return $o['status'] === 'processing'; })); ?></div>
            </div>
            <div class="stat-item">
                <h3>Tamamlanan</h3>
                <div class="number"><?php echo count(array_filter($orders, function($o) { return $o['status'] === 'completed'; })); ?></div>
            </div>
            <div class="stat-item">
                <h3>İptal Edilenler</h3>
                <div class="number"><?php echo count(array_filter($orders, function($o) { return $o['status'] === 'cancelled'; })); ?></div>
            </div>
            <div class="stat-item">
                <h3>Hatalı</h3>
                <div class="number"><?php echo count(array_filter($orders, function($o) { return $o['status'] === 'error'; })); ?></div>
            </div>
        </div>
        
        <div class="card">
            <h2>Sipariş Listesi</h2>
            <div class="filter-bar">
                <input type="text" id="searchInput" placeholder="Sipariş ara..." onkeyup="filterOrders()">
                <select id="statusFilter" onchange="filterOrders()">
                    <option value="">Tüm Durumlar</option>
                    <option value="pending">Bekleyen</option>
                    <option value="processing">İşlenen</option>
                    <option value="completed">Tamamlanan</option>
                    <option value="cancelled">İptal Edilen</option>
                    <option value="error">Hatalı</option>
                </select>
                <select id="apiFilter" onchange="filterOrders()">
                    <option value="">Tüm API'ler</option>
                    <?php 
                    $apis = array_unique(array_column($orders, 'api_name'));
                    foreach ($apis as $api): ?>
                        <option value="<?php echo htmlspecialchars($api); ?>"><?php echo htmlspecialchars($api); ?></option>
                    <?php endforeach; ?>
                </select>
                <span id="resultCount">Gösterilen: <?php echo count($orders); ?></span>
            </div>
            
            <div class="table-container">
                <table class="table" id="ordersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Sipariş ID</th>
                            <th>External ID</th>
                            <th>Key</th>
                            <th>Servis</th>
                            <th>Platform</th>
                            <th>Miktar</th>
                            <th>Link</th>
                            <th>Durum</th>
                            <th>Oluşturulma</th>
                            <th>Güncelleme</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <?php foreach ($orders as $order): ?>
                        <tr data-status="<?php echo $order['status']; ?>" data-api="<?php echo htmlspecialchars($order['api_name']); ?>">
                            <td><?php echo $order['id']; ?></td>
                            <td>
                                <span class="order-id" onclick="copyToClipboard('<?php echo htmlspecialchars($order['order_id']); ?>')" 
                                      title="Kopyalamak için tıklayın">
                                    <?php echo htmlspecialchars($order['order_id']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($order['external_order_id']): ?>
                                    <span class="order-id" onclick="copyToClipboard('<?php echo htmlspecialchars($order['external_order_id']); ?>')" 
                                          title="Kopyalamak için tıklayın">
                                        <?php echo htmlspecialchars($order['external_order_id']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #666;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span title="<?php echo htmlspecialchars($order['key_name']); ?>">
                                    <?php echo htmlspecialchars(substr($order['key_value'], 0, 15) . '...'); ?>
                                </span>
                            </td>
                            <td class="text-truncate" title="<?php echo htmlspecialchars($order['service_name']); ?>">
                                <?php echo htmlspecialchars($order['service_name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($order['platform']); ?></td>
                            <td><?php echo $order['quantity']; ?></td>
                            <td class="text-truncate" title="<?php echo htmlspecialchars($order['link']); ?>">
                                <a href="<?php echo htmlspecialchars($order['link']); ?>" target="_blank" style="color: #4a9eff;">
                                    <?php echo htmlspecialchars($order['link']); ?>
                                </a>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php 
                                    $statusText = [
                                        'pending' => 'Bekleyen',
                                        'processing' => 'İşlenen',
                                        'completed' => 'Tamamlanan',
                                        'cancelled' => 'İptal Edilen',
                                        'error' => 'Hatalı'
                                    ][$order['status']] ?? $order['status'];
                                    echo $statusText;
                                    ?>
                                </span>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($order['updated_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="pagination" id="pagination"></div>
        </div>
    </div>
    
    <script>
        let currentPage = 1;
        const itemsPerPage = 50;
        let filteredOrders = Array.from(document.querySelectorAll('#ordersTableBody tr'));
        
        function filterOrders() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const apiFilter = document.getElementById('apiFilter').value;
            
            const allRows = document.querySelectorAll('#ordersTableBody tr');
            filteredOrders = [];
            
            allRows.forEach(row => {
                const orderId = row.cells[1].textContent.toLowerCase();
                const keyValue = row.cells[3].textContent.toLowerCase();
                const serviceName = row.cells[4].textContent.toLowerCase();
                const status = row.getAttribute('data-status');
                const api = row.getAttribute('data-api');
                
                const matchesSearch = orderId.includes(searchTerm) || keyValue.includes(searchTerm) || serviceName.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;
                const matchesApi = !apiFilter || api === apiFilter;
                
                if (matchesSearch && matchesStatus && matchesApi) {
                    filteredOrders.push(row);
                }
            });
            
            document.getElementById('resultCount').textContent = `Gösterilen: ${filteredOrders.length}`;
            currentPage = 1;
            displayPage();
        }
        
        function displayPage() {
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            
            // Hide all rows
            document.querySelectorAll('#ordersTableBody tr').forEach(row => {
                row.style.display = 'none';
            });
            
            // Show current page rows
            filteredOrders.slice(startIndex, endIndex).forEach(row => {
                row.style.display = '';
            });
            
            // Update pagination
            updatePagination();
        }
        
        function updatePagination() {
            const totalPages = Math.ceil(filteredOrders.length / itemsPerPage);
            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';
            
            if (totalPages <= 1) return;
            
            // Previous button
            if (currentPage > 1) {
                const prevBtn = document.createElement('button');
                prevBtn.textContent = '← Önceki';
                prevBtn.onclick = () => {
                    currentPage--;
                    displayPage();
                };
                pagination.appendChild(prevBtn);
            }
            
            // Page numbers
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.textContent = i;
                pageBtn.className = i === currentPage ? 'active' : '';
                pageBtn.onclick = () => {
                    currentPage = i;
                    displayPage();
                };
                pagination.appendChild(pageBtn);
            }
            
            // Next button
            if (currentPage < totalPages) {
                const nextBtn = document.createElement('button');
                nextBtn.textContent = 'Sonraki →';
                nextBtn.onclick = () => {
                    currentPage++;
                    displayPage();
                };
                pagination.appendChild(nextBtn);
            }
        }
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Kopyalandı!');
            });
        }
        
        // Initialize page display
        displayPage();
        
        // Auto-refresh every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>