<?php
require_once '../config.php';
requireAdmin();

// Get all services with API info
$stmt = $pdo->query("
    SELECT s.*, a.name as api_name 
    FROM services s 
    JOIN apis a ON s.api_id = a.id 
    ORDER BY s.created_at DESC
");
$services = $stmt->fetchAll();

// Get all APIs for filter
$stmt = $pdo->query("SELECT id, name FROM apis ORDER BY name");
$apis = $stmt->fetchAll();

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servis Yönetimi - KiWiPazari</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #1a1a1a; color: #fff; }
        .header { background: #2a2a2a; padding: 1rem 2rem; border-bottom: 1px solid #444; }
        .header h1 { color: #4a9eff; display: inline-block; }
        .header .nav { float: right; }
        .header .nav a { color: #4a9eff; text-decoration: none; margin-left: 1rem; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .card { background: #2a2a2a; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem; }
        .filter-bar { display: flex; gap: 1rem; margin-bottom: 1rem; align-items: center; }
        .filter-bar input, .filter-bar select { padding: 0.5rem; border: 1px solid #555; border-radius: 4px; background: #3a3a3a; color: #fff; }
        .table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .table th, .table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #444; }
        .table th { background: #333; position: sticky; top: 0; }
        .table tr:hover { background: #333; }
        .table-container { max-height: 600px; overflow-y: auto; }
        .btn { padding: 0.5rem 1rem; background: #4a9eff; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 0.5rem; }
        .btn:hover { background: #357abd; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-item { background: #333; padding: 1rem; border-radius: 4px; text-align: center; }
        .stat-item h3 { color: #4a9eff; margin-bottom: 0.5rem; }
        .stat-item .number { font-size: 1.5rem; font-weight: bold; }
        .pagination { display: flex; justify-content: center; gap: 0.5rem; margin-top: 1rem; }
        .pagination button { padding: 0.5rem 1rem; background: #3a3a3a; color: #fff; border: 1px solid #555; border-radius: 4px; cursor: pointer; }
        .pagination button:hover { background: #4a9eff; }
        .pagination button.active { background: #4a9eff; }
        .text-truncate { max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Servis Yönetimi</h1>
        <div class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="api-management.php">API Yönetimi</a>
            <a href="index.php?logout=1">Çıkış</a>
        </div>
        <div style="clear: both;"></div>
    </div>
    
    <div class="container">
        <div class="stats">
            <div class="stat-item">
                <h3>Toplam Servis</h3>
                <div class="number"><?php echo count($services); ?></div>
            </div>
            <div class="stat-item">
                <h3>Farklı Platform</h3>
                <div class="number"><?php echo count(array_unique(array_column($services, 'platform'))); ?></div>
            </div>
            <div class="stat-item">
                <h3>Farklı Kategori</h3>
                <div class="number"><?php echo count(array_unique(array_column($services, 'category'))); ?></div>
            </div>
            <div class="stat-item">
                <h3>API Sayısı</h3>
                <div class="number"><?php echo count($apis); ?></div>
            </div>
        </div>
        
        <div class="card">
            <h2>Servis Listesi</h2>
            <div class="filter-bar">
                <input type="text" id="searchInput" placeholder="Servis ara..." onkeyup="filterServices()">
                <select id="apiFilter" onchange="filterServices()">
                    <option value="">Tüm API'ler</option>
                    <?php foreach ($apis as $api): ?>
                        <option value="<?php echo $api['id']; ?>"><?php echo htmlspecialchars($api['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="platformFilter" onchange="filterServices()">
                    <option value="">Tüm Platformlar</option>
                    <?php 
                    $platforms = array_unique(array_filter(array_column($services, 'platform')));
                    foreach ($platforms as $platform): ?>
                        <option value="<?php echo htmlspecialchars($platform); ?>"><?php echo htmlspecialchars($platform); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="categoryFilter" onchange="filterServices()">
                    <option value="">Tüm Kategoriler</option>
                    <?php 
                    $categories = array_unique(array_filter(array_column($services, 'category')));
                    foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                    <?php endforeach; ?>
                </select>
                <span id="resultCount">Gösterilen: <?php echo count($services); ?></span>
            </div>
            
            <div class="table-container">
                <table class="table" id="servicesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>External ID</th>
                            <th>Servis Adı</th>
                            <th>API</th>
                            <th>Platform</th>
                            <th>Kategori</th>
                            <th>Min</th>
                            <th>Max</th>
                            <th>Fiyat</th>
                            <th>Eklenme</th>
                        </tr>
                    </thead>
                    <tbody id="servicesTableBody">
                        <?php foreach ($services as $service): ?>
                        <tr data-api-id="<?php echo $service['api_id']; ?>" data-platform="<?php echo htmlspecialchars($service['platform']); ?>" data-category="<?php echo htmlspecialchars($service['category']); ?>">
                            <td><?php echo $service['id']; ?></td>
                            <td><?php echo htmlspecialchars($service['external_id']); ?></td>
                            <td class="text-truncate" title="<?php echo htmlspecialchars($service['name']); ?>">
                                <?php echo htmlspecialchars($service['name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($service['api_name']); ?></td>
                            <td><?php echo htmlspecialchars($service['platform']); ?></td>
                            <td><?php echo htmlspecialchars($service['category']); ?></td>
                            <td><?php echo $service['min_amount']; ?></td>
                            <td><?php echo $service['max_amount']; ?></td>
                            <td><?php echo number_format($service['price'], 4); ?></td>
                            <td><?php echo date('d.m.Y', strtotime($service['created_at'])); ?></td>
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
        let filteredServices = Array.from(document.querySelectorAll('#servicesTableBody tr'));
        
        function filterServices() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const apiFilter = document.getElementById('apiFilter').value;
            const platformFilter = document.getElementById('platformFilter').value;
            const categoryFilter = document.getElementById('categoryFilter').value;
            
            const allRows = document.querySelectorAll('#servicesTableBody tr');
            filteredServices = [];
            
            allRows.forEach(row => {
                const serviceName = row.cells[2].textContent.toLowerCase();
                const externalId = row.cells[1].textContent.toLowerCase();
                const apiId = row.getAttribute('data-api-id');
                const platform = row.getAttribute('data-platform');
                const category = row.getAttribute('data-category');
                
                const matchesSearch = serviceName.includes(searchTerm) || externalId.includes(searchTerm);
                const matchesApi = !apiFilter || apiId === apiFilter;
                const matchesPlatform = !platformFilter || platform === platformFilter;
                const matchesCategory = !categoryFilter || category === categoryFilter;
                
                if (matchesSearch && matchesApi && matchesPlatform && matchesCategory) {
                    filteredServices.push(row);
                }
            });
            
            document.getElementById('resultCount').textContent = `Gösterilen: ${filteredServices.length}`;
            currentPage = 1;
            displayPage();
        }
        
        function displayPage() {
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            
            // Hide all rows
            document.querySelectorAll('#servicesTableBody tr').forEach(row => {
                row.style.display = 'none';
            });
            
            // Show current page rows
            filteredServices.slice(startIndex, endIndex).forEach(row => {
                row.style.display = '';
            });
            
            // Update pagination
            updatePagination();
        }
        
        function updatePagination() {
            const totalPages = Math.ceil(filteredServices.length / itemsPerPage);
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
        
        // Initialize page display
        displayPage();
        
        // Auto-refresh every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>