<?php
require_once 'config.php';
requireAdmin();

// Logout işlemi
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin-login.php');
    exit;
}

// İstatistikler
$db = Database::getInstance()->getConnection();
$stats = [];

// API sayısı
$result = $db->query("SELECT COUNT(*) as count FROM apis");
$stats['apis'] = $result->fetch_assoc()['count'];

// Servis sayısı
$result = $db->query("SELECT COUNT(*) as count FROM services");
$stats['services'] = $result->fetch_assoc()['count'];

// Anahtar sayısı
$result = $db->query("SELECT COUNT(*) as count FROM `keys`");
$stats['keys'] = $result->fetch_assoc()['count'];

// Sipariş sayısı
$result = $db->query("SELECT COUNT(*) as count FROM orders");
$stats['orders'] = $result->fetch_assoc()['count'];

// Son siparişler
$recent_orders = $db->query("
    SELECT o.*, s.name as service_name, s.platform, s.category 
    FROM orders o 
    LEFT JOIN services s ON o.service_id = s.id 
    ORDER BY o.created_at DESC 
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body { background: #0f172a; color: #e2e8f0; }
        .gradient-bg { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); }
        .border-slate { border-color: #475569; }
        .text-slate-400 { color: #94a3b8; }
    </style>
</head>
<body class="min-h-screen gradient-bg">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-white"><?php echo SITE_NAME; ?> Admin Panel</h1>
            <div class="flex items-center space-x-4">
                <span class="text-slate-400">Hoş geldiniz, <?php echo $_SESSION['admin_username']; ?></span>
                <a href="?logout=1" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md transition-colors">
                    Çıkış
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gray-800 rounded-lg p-6 border border-slate">
                <h3 class="text-lg font-semibold text-white mb-2">API'ler</h3>
                <p class="text-3xl font-bold text-blue-400"><?php echo $stats['apis']; ?></p>
            </div>
            <div class="bg-gray-800 rounded-lg p-6 border border-slate">
                <h3 class="text-lg font-semibold text-white mb-2">Servisler</h3>
                <p class="text-3xl font-bold text-green-400"><?php echo $stats['services']; ?></p>
            </div>
            <div class="bg-gray-800 rounded-lg p-6 border border-slate">
                <h3 class="text-lg font-semibold text-white mb-2">Anahtarlar</h3>
                <p class="text-3xl font-bold text-purple-400"><?php echo $stats['keys']; ?></p>
            </div>
            <div class="bg-gray-800 rounded-lg p-6 border border-slate">
                <h3 class="text-lg font-semibold text-white mb-2">Siparişler</h3>
                <p class="text-3xl font-bold text-orange-400"><?php echo $stats['orders']; ?></p>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="mb-8">
            <nav class="flex space-x-1 bg-gray-800 p-1 rounded-lg">
                <button onclick="showTab('apis')" class="tab-button px-4 py-2 text-sm font-medium rounded-md transition-colors" data-tab="apis">API Yönetimi</button>
                <button onclick="showTab('services')" class="tab-button px-4 py-2 text-sm font-medium rounded-md transition-colors" data-tab="services">Servis Yönetimi</button>
                <button onclick="showTab('keys')" class="tab-button px-4 py-2 text-sm font-medium rounded-md transition-colors" data-tab="keys">Anahtar Yönetimi</button>
                <button onclick="showTab('orders')" class="tab-button px-4 py-2 text-sm font-medium rounded-md transition-colors" data-tab="orders">Sipariş Yönetimi</button>
            </nav>
        </div>

        <!-- API Management Tab -->
        <div id="apis-tab" class="tab-content hidden">
            <div class="bg-gray-800 rounded-lg p-6 border border-slate">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-white">API Yönetimi</h2>
                    <button onclick="showAddApiForm()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                        API Ekle
                    </button>
                </div>
                
                <div id="apis-list" class="space-y-4">
                    <!-- API listesi buraya yüklenecek -->
                </div>
            </div>
        </div>

        <!-- Services Management Tab -->
        <div id="services-tab" class="tab-content hidden">
            <div class="bg-gray-800 rounded-lg p-6 border border-slate">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-white">Servis Yönetimi</h2>
                    <button onclick="syncServices()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition-colors">
                        Servisleri Senkronize Et
                    </button>
                </div>
                
                <div id="services-list" class="space-y-4">
                    <!-- Servis listesi buraya yüklenecek -->
                </div>
            </div>
        </div>

        <!-- Keys Management Tab -->
        <div id="keys-tab" class="tab-content hidden">
            <div class="bg-gray-800 rounded-lg p-6 border border-slate">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-white">Anahtar Yönetimi</h2>
                    <button onclick="showAddKeyForm()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md transition-colors">
                        Anahtar Ekle
                    </button>
                </div>
                
                <div id="keys-list" class="space-y-4">
                    <!-- Anahtar listesi buraya yüklenecek -->
                </div>
            </div>
        </div>

        <!-- Orders Management Tab -->
        <div id="orders-tab" class="tab-content hidden">
            <div class="bg-gray-800 rounded-lg p-6 border border-slate">
                <h2 class="text-xl font-semibold text-white mb-4">Sipariş Yönetimi</h2>
                
                <div id="orders-list" class="space-y-4">
                    <!-- Sipariş listesi buraya yüklenecek -->
                </div>
            </div>
        </div>

        <!-- Recent Orders (Always visible) -->
        <div class="bg-gray-800 rounded-lg p-6 border border-slate">
            <h2 class="text-xl font-semibold text-white mb-4">Son Siparişler</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-slate-400">
                    <thead class="text-xs text-slate-400 uppercase bg-gray-700">
                        <tr>
                            <th class="px-4 py-3">Sipariş ID</th>
                            <th class="px-4 py-3">Servis</th>
                            <th class="px-4 py-3">Platform</th>
                            <th class="px-4 py-3">Miktar</th>
                            <th class="px-4 py-3">Durum</th>
                            <th class="px-4 py-3">Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                        <tr class="border-b border-gray-700">
                            <td class="px-4 py-3 font-medium text-white"><?php echo $order['order_id']; ?></td>
                            <td class="px-4 py-3"><?php echo $order['service_name'] ?? 'Bilinmiyor'; ?></td>
                            <td class="px-4 py-3"><?php echo $order['platform'] ?? 'Bilinmiyor'; ?></td>
                            <td class="px-4 py-3"><?php echo $order['quantity']; ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                    <?php echo $order['status'] === 'completed' ? 'bg-green-900 text-green-300' : 
                                        ($order['status'] === 'processing' ? 'bg-yellow-900 text-yellow-300' : 'bg-gray-900 text-gray-300'); ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.add('hidden'));
            
            // Remove active class from all buttons
            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => {
                button.classList.remove('bg-blue-600', 'text-white');
                button.classList.add('text-slate-400', 'hover:text-white');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.remove('hidden');
            
            // Add active class to clicked button
            const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
            activeButton.classList.add('bg-blue-600', 'text-white');
            activeButton.classList.remove('text-slate-400', 'hover:text-white');
            
            // Load content for the selected tab
            switch(tabName) {
                case 'apis':
                    loadApis();
                    break;
                case 'services':
                    loadServices();
                    break;
                case 'keys':
                    loadKeys();
                    break;
                case 'orders':
                    loadOrders();
                    break;
            }
        }

        // Load functions
        async function loadApis() {
            try {
                const response = await fetch('api/admin/apis.php');
                const data = await response.json();
                
                const container = document.getElementById('apis-list');
                container.innerHTML = data.apis.map(api => `
                    <div class="bg-gray-700 p-4 rounded-lg border border-slate">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-semibold text-white">${api.name}</h3>
                                <p class="text-sm text-slate-400">${api.url}</p>
                                <p class="text-xs text-slate-500">Servis Sayısı: ${api.service_count}</p>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="testApi(${api.id})" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">Test</button>
                                <button onclick="deleteApi(${api.id})" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">Sil</button>
                            </div>
                        </div>
                    </div>
                `).join('');
            } catch (error) {
                console.error('API listesi yüklenemedi:', error);
            }
        }

        async function loadServices() {
            try {
                const response = await fetch('api/admin/services.php');
                const data = await response.json();
                
                const container = document.getElementById('services-list');
                container.innerHTML = data.services.map(service => `
                    <div class="bg-gray-700 p-4 rounded-lg border border-slate">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-semibold text-white">${service.name}</h3>
                                <p class="text-sm text-slate-400">${service.platform} - ${service.category}</p>
                                <p class="text-xs text-slate-500">Min: ${service.min_quantity} - Max: ${service.max_quantity}</p>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="deleteService(${service.id})" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">Sil</button>
                            </div>
                        </div>
                    </div>
                `).join('');
            } catch (error) {
                console.error('Servis listesi yüklenemedi:', error);
            }
        }

        async function loadKeys() {
            try {
                const response = await fetch('api/admin/keys.php');
                const data = await response.json();
                
                const container = document.getElementById('keys-list');
                container.innerHTML = data.keys.map(key => `
                    <div class="bg-gray-700 p-4 rounded-lg border border-slate">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-semibold text-white">${key.name}</h3>
                                <p class="text-sm text-slate-400">${key.key_value}</p>
                                <p class="text-xs text-slate-500">Kullanılan: ${key.used_amount}/${key.max_amount}</p>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="deleteKey(${key.id})" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">Sil</button>
                            </div>
                        </div>
                    </div>
                `).join('');
            } catch (error) {
                console.error('Anahtar listesi yüklenemedi:', error);
            }
        }

        async function loadOrders() {
            try {
                const response = await fetch('api/admin/orders.php');
                const data = await response.json();
                
                const container = document.getElementById('orders-list');
                container.innerHTML = data.orders.map(order => `
                    <div class="bg-gray-700 p-4 rounded-lg border border-slate">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-semibold text-white">${order.order_id}</h3>
                                <p class="text-sm text-slate-400">Miktar: ${order.quantity}</p>
                                <p class="text-xs text-slate-500">${order.created_at}</p>
                            </div>
                            <div class="flex space-x-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                    ${order.status === 'completed' ? 'bg-green-900 text-green-300' : 
                                      order.status === 'processing' ? 'bg-yellow-900 text-yellow-300' : 'bg-gray-900 text-gray-300'}">
                                    ${order.status}
                                </span>
                            </div>
                        </div>
                    </div>
                `).join('');
            } catch (error) {
                console.error('Sipariş listesi yüklenemedi:', error);
            }
        }

        // Initialize first tab
        document.addEventListener('DOMContentLoaded', function() {
            showTab('apis');
        });

        // Placeholder functions for actions
        function showAddApiForm() {
            alert('API ekleme formu yakında eklenecek');
        }

        function showAddKeyForm() {
            alert('Anahtar ekleme formu yakında eklenecek');
        }

        function testApi(id) {
            alert('API test özelliği yakında eklenecek');
        }

        function syncServices() {
            alert('Servis senkronizasyonu yakında eklenecek');
        }

        function deleteApi(id) {
            if (confirm('Bu API\'yi silmek istediğinizden emin misiniz?')) {
                // Delete API logic
                alert('API silme özelliği yakında eklenecek');
            }
        }

        function deleteService(id) {
            if (confirm('Bu servisi silmek istediğinizden emin misiniz?')) {
                // Delete service logic
                alert('Servis silme özelliği yakında eklenecek');
            }
        }

        function deleteKey(id) {
            if (confirm('Bu anahtarı silmek istediğinizden emin misiniz?')) {
                // Delete key logic
                alert('Anahtar silme özelliği yakında eklenecek');
            }
        }
    </script>
</body>
</html>