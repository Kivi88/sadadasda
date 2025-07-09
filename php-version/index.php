<?php
session_start();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KiWiPazari - Sosyal Medya Servisleri</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .container {
            max-width: 500px;
            width: 100%;
        }
        
        .main-card {
            background: rgba(45, 45, 45, 0.9);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            position: relative;
        }
        
        .logo {
            font-size: 2.5rem;
            font-weight: bold;
            color: #4A90E2;
            margin-bottom: 1rem;
            text-shadow: 0 0 20px rgba(74, 144, 226, 0.3);
        }
        
        .subtitle {
            color: #bbb;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .order-search-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(74, 144, 226, 0.2);
            color: #4A90E2;
            border: 1px solid rgba(74, 144, 226, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .order-search-btn:hover {
            background: rgba(74, 144, 226, 0.3);
            transform: translateY(-2px);
        }
        
        .key-form {
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #ddd;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4A90E2;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }
        
        .form-group input::placeholder {
            color: #888;
        }
        
        .btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #4A90E2 0%, #357abd 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(74, 144, 226, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .admin-link {
            margin-top: 2rem;
            text-align: center;
        }
        
        .admin-link a {
            color: #666;
            text-decoration: none;
            font-size: 0.8rem;
            transition: color 0.3s ease;
        }
        
        .admin-link a:hover {
            color: #4A90E2;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: rgba(45, 45, 45, 0.95);
            border-radius: 15px;
            padding: 2rem;
            width: 90%;
            max-width: 400px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-title {
            color: #4A90E2;
            font-size: 1.3rem;
            font-weight: 600;
        }
        
        .close-btn {
            background: none;
            border: none;
            color: #999;
            font-size: 1.5rem;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .close-btn:hover {
            color: #fff;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .alert-error {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid rgba(231, 76, 60, 0.3);
            color: #ff6b6b;
        }
        
        .alert-success {
            background: rgba(46, 204, 113, 0.2);
            border: 1px solid rgba(46, 204, 113, 0.3);
            color: #51cf66;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-card">
            <button class="order-search-btn" onclick="openOrderModal()">Sipariş Sorgula</button>
            
            <h1 class="logo">KiWiPazari</h1>
            <p class="subtitle">Sosyal medya hesaplarınızı büyütmek için güvenilir servisler</p>
            
            <form class="key-form" action="key-validator.php" method="POST">
                <div class="form-group">
                    <label for="key">Anahtar Kodunuz</label>
                    <input type="text" id="key" name="key" placeholder="Anahtar kodunuzu girin..." required>
                </div>
                
                <button type="submit" class="btn">Doğrula</button>
            </form>
            
            <div class="admin-link">
                <a href="admin-login.php">Yönetici Girişi</a>
            </div>
        </div>
    </div>
    
    <!-- Order Search Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Sipariş Sorgula</h3>
                <button class="close-btn" onclick="closeOrderModal()">&times;</button>
            </div>
            
            <div id="orderResult"></div>
            
            <form id="orderSearchForm">
                <div class="form-group">
                    <label for="orderId">Sipariş ID</label>
                    <input type="text" id="orderId" name="orderId" placeholder="#2384344 veya 2384344" required>
                </div>
                
                <button type="submit" class="btn">Sorgula</button>
            </form>
        </div>
    </div>
    
    <script>
        function openOrderModal() {
            document.getElementById('orderModal').classList.add('active');
        }
        
        function closeOrderModal() {
            document.getElementById('orderModal').classList.remove('active');
            document.getElementById('orderResult').innerHTML = '';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('orderModal');
            if (event.target === modal) {
                closeOrderModal();
            }
        }
        
        // Order search form handler
        document.getElementById('orderSearchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const orderId = document.getElementById('orderId').value.trim();
            const resultDiv = document.getElementById('orderResult');
            
            if (!orderId) {
                resultDiv.innerHTML = '<div class="alert alert-error">Lütfen sipariş ID\'sini girin</div>';
                return;
            }
            
            // Remove # prefix if present
            const cleanOrderId = orderId.startsWith('#') ? orderId.substring(1) : orderId;
            
            resultDiv.innerHTML = '<div class="alert alert-success">Sipariş aranıyor...</div>';
            
            // Simulate API call (replace with actual API call)
            fetch('order-search.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'orderId=' + encodeURIComponent(cleanOrderId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const order = data.order;
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <strong>Sipariş Bulundu!</strong><br>
                            Sipariş ID: #${order.orderId}<br>
                            Durum: ${order.status}<br>
                            Miktar: ${order.quantity}<br>
                            Servis: ${order.service}
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="alert alert-error">Bir hata oluştu. Lütfen tekrar deneyin.</div>';
            });
        });
    </script>
</body>
</html>