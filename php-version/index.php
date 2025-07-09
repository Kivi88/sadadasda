<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body { background: #0f172a; color: #e2e8f0; }
        .gradient-bg { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); }
        .border-slate { border-color: #475569; }
        .text-slate-400 { color: #94a3b8; }
        .hover-glow:hover { box-shadow: 0 0 20px rgba(59, 130, 246, 0.5); }
    </style>
</head>
<body class="min-h-screen gradient-bg">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto">
            <!-- Logo/Title -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-white mb-2"><?php echo SITE_NAME; ?></h1>
                <p class="text-slate-400">Sosyal Medya Hizmetleri</p>
            </div>

            <!-- Key Validation Form -->
            <div class="bg-gray-800 rounded-lg p-6 shadow-lg border border-slate">
                <h2 class="text-xl font-semibold mb-4 text-white">Anahtar Doğrulama</h2>
                
                <form id="keyValidationForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">
                            Anahtar Değeri
                        </label>
                        <input 
                            type="text" 
                            id="keyValue" 
                            name="keyValue"
                            placeholder="Anahtar değerinizi girin..."
                            class="w-full px-3 py-2 bg-gray-700 border border-slate rounded-md text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                    </div>
                    
                    <button 
                        type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors hover-glow"
                    >
                        Anahtarı Doğrula
                    </button>
                </form>

                <!-- Loading State -->
                <div id="loadingState" class="hidden text-center py-4">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"></div>
                    <p class="mt-2 text-slate-400">Doğrulanıyor...</p>
                </div>

                <!-- Key Info -->
                <div id="keyInfo" class="hidden mt-6 p-4 bg-gray-700 rounded-lg border border-slate">
                    <h3 class="font-semibold text-white mb-2">Anahtar Bilgileri</h3>
                    <div id="keyDetails" class="space-y-2 text-sm text-slate-400"></div>
                </div>

                <!-- Order Form -->
                <div id="orderForm" class="hidden mt-6 p-4 bg-gray-700 rounded-lg border border-slate">
                    <h3 class="font-semibold text-white mb-4">Sipariş Ver</h3>
                    <form id="createOrderForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-400 mb-2">
                                Link
                            </label>
                            <input 
                                type="url" 
                                id="orderLink" 
                                name="link"
                                placeholder="Hedef linki girin..."
                                class="w-full px-3 py-2 bg-gray-600 border border-slate rounded-md text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required
                            >
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-400 mb-2">
                                Miktar
                            </label>
                            <input 
                                type="number" 
                                id="orderQuantity" 
                                name="quantity"
                                min="1"
                                max="1000"
                                placeholder="1"
                                class="w-full px-3 py-2 bg-gray-600 border border-slate rounded-md text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required
                            >
                        </div>
                        
                        <button 
                            type="submit"
                            class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition-colors hover-glow"
                        >
                            Sipariş Oluştur
                        </button>
                    </form>
                </div>
            </div>

            <!-- Order Search -->
            <div class="mt-8 bg-gray-800 rounded-lg p-6 shadow-lg border border-slate">
                <h2 class="text-xl font-semibold mb-4 text-white">Sipariş Sorgula</h2>
                
                <form id="orderSearchForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">
                            Sipariş ID
                        </label>
                        <input 
                            type="text" 
                            id="searchOrderId" 
                            name="orderId"
                            placeholder="#2384344 veya 2384344"
                            class="w-full px-3 py-2 bg-gray-700 border border-slate rounded-md text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                    </div>
                    
                    <button 
                        type="submit"
                        class="w-full bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-md transition-colors hover-glow"
                    >
                        Sipariş Ara
                    </button>
                </form>

                <!-- Order Result -->
                <div id="orderResult" class="hidden mt-6 p-4 bg-gray-700 rounded-lg border border-slate">
                    <div id="orderDetails" class="space-y-2 text-sm text-slate-400"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentKey = null;
        let currentService = null;

        // Key validation
        document.getElementById('keyValidationForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const keyValue = document.getElementById('keyValue').value;
            const loadingState = document.getElementById('loadingState');
            const keyInfo = document.getElementById('keyInfo');
            const orderForm = document.getElementById('orderForm');
            
            loadingState.classList.remove('hidden');
            keyInfo.classList.add('hidden');
            orderForm.classList.add('hidden');
            
            try {
                const response = await fetch('api/validate-key.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ keyValue })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    currentKey = data.key;
                    currentService = data.service;
                    
                    document.getElementById('keyDetails').innerHTML = `
                        <p><strong>Anahtar Adı:</strong> ${data.key.name}</p>
                        <p><strong>Platform:</strong> ${data.service.platform}</p>
                        <p><strong>Kategori:</strong> ${data.service.category}</p>
                        <p><strong>Kalan Miktar:</strong> ${data.key.max_amount - data.key.used_amount}</p>
                    `;
                    
                    // Set quantity limits
                    const quantityInput = document.getElementById('orderQuantity');
                    quantityInput.max = data.key.max_amount - data.key.used_amount;
                    quantityInput.min = data.service.min_quantity;
                    
                    keyInfo.classList.remove('hidden');
                    orderForm.classList.remove('hidden');
                } else {
                    alert('Geçersiz anahtar: ' + data.message);
                }
            } catch (error) {
                alert('Bir hata oluştu: ' + error.message);
            } finally {
                loadingState.classList.add('hidden');
            }
        });

        // Order creation
        document.getElementById('createOrderForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!currentKey || !currentService) {
                alert('Lütfen önce anahtarı doğrulayın');
                return;
            }
            
            const link = document.getElementById('orderLink').value;
            const quantity = parseInt(document.getElementById('orderQuantity').value);
            
            try {
                const response = await fetch('api/create-order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        keyValue: currentKey.key_value,
                        serviceId: currentService.id,
                        link,
                        quantity
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Sipariş başarıyla oluşturuldu!\nSipariş ID: ' + data.order.order_id);
                    
                    // Reset form
                    document.getElementById('createOrderForm').reset();
                    document.getElementById('keyValidationForm').reset();
                    document.getElementById('keyInfo').classList.add('hidden');
                    document.getElementById('orderForm').classList.add('hidden');
                    
                    // Auto-search the created order
                    document.getElementById('searchOrderId').value = data.order.order_id;
                    document.getElementById('orderSearchForm').dispatchEvent(new Event('submit'));
                } else {
                    alert('Sipariş oluşturulamadı: ' + data.message);
                }
            } catch (error) {
                alert('Bir hata oluştu: ' + error.message);
            }
        });

        // Order search
        document.getElementById('orderSearchForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const orderId = document.getElementById('searchOrderId').value.replace('#', '');
            const orderResult = document.getElementById('orderResult');
            
            try {
                const response = await fetch('api/search-order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ orderId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const order = data.order;
                    const service = data.service;
                    
                    document.getElementById('orderDetails').innerHTML = `
                        <p><strong>Sipariş ID:</strong> ${order.order_id}</p>
                        <p><strong>Platform:</strong> ${service.platform}</p>
                        <p><strong>Kategori:</strong> ${service.category}</p>
                        <p><strong>Link:</strong> ${order.link}</p>
                        <p><strong>Miktar:</strong> ${order.quantity}</p>
                        <p><strong>Durum:</strong> ${order.status}</p>
                        <p><strong>Oluşturulma:</strong> ${new Date(order.created_at).toLocaleString('tr-TR')}</p>
                    `;
                    
                    orderResult.classList.remove('hidden');
                } else {
                    alert('Sipariş bulunamadı: ' + data.message);
                    orderResult.classList.add('hidden');
                }
            } catch (error) {
                alert('Bir hata oluştu: ' + error.message);
            }
        });
    </script>
</body>
</html>