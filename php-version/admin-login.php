<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = 'Kullanıcı adı ve şifre gerekli';
    } else {
        // Admin credentials
        $admin_username = 'admin';
        $admin_password = 'admin123';
        
        if ($username === $admin_username && $password === $admin_password) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            $success = 'Giriş başarılı! Yönlendiriliyorsunuz...';
            header('refresh:2;url=admin-dashboard.php');
        } else {
            $error = 'Geçersiz kullanıcı adı veya şifre';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli - KiWiPazari</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
        }
        
        .login-container {
            background: rgba(45, 45, 45, 0.9);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: #4A90E2;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }
        
        .login-header p {
            color: #bbb;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
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
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4A90E2;
            background: rgba(255, 255, 255, 0.15);
        }
        
        .form-group input::placeholder {
            color: #888;
        }
        
        .btn {
            width: 100%;
            padding: 0.75rem;
            background: #4A90E2;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #357abd;
            transform: translateY(-1px);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 0.75rem;
            border-radius: 6px;
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
        
        .back-link {
            text-align: center;
            margin-top: 1rem;
        }
        
        .back-link a {
            color: #4A90E2;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .back-link a:hover {
            color: #357abd;
        }
        
        .credentials-info {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 6px;
            margin-top: 1rem;
            font-size: 0.85rem;
            color: #aaa;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Admin Paneli</h1>
            <p>Yönetici girişi yapın</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Kullanıcı Adı</label>
                <input type="text" id="username" name="username" value="admin" required>
            </div>
            
            <div class="form-group">
                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" placeholder="Şifrenizi girin" required>
            </div>
            
            <button type="submit" class="btn">Giriş Yap</button>
        </form>
        
        <div class="credentials-info">
            <strong>Test Bilgileri:</strong><br>
            Kullanıcı: admin<br>
            Şifre: admin123
        </div>
        
        <div class="back-link">
            <a href="index.php">← Ana Sayfaya Dön</a>
        </div>
    </div>
</body>
</html>