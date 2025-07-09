<?php
require_once '../config.php';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!checkRateLimit('admin_login_' . $_SERVER['REMOTE_ADDR'], 15, 900)) {
        $error = 'Çok fazla başarısız deneme. 15 dakika sonra tekrar deneyin.';
    } else {
        $password = $_POST['password'] ?? '';
        
        if (password_verify($password, ADMIN_PASSWORD_HASH)) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_login_time'] = time();
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Geçersiz şifre';
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// If already logged in, redirect to dashboard
if (isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Giriş - KiWiPazari</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #1a1a1a; color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { max-width: 400px; width: 100%; padding: 2rem; }
        .header { text-align: center; margin-bottom: 2rem; }
        .header h1 { color: #4a9eff; margin-bottom: 0.5rem; }
        .card { background: #2a2a2a; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; color: #ddd; }
        input { width: 100%; padding: 0.75rem; border: 1px solid #555; border-radius: 6px; background: #3a3a3a; color: #fff; font-size: 1rem; }
        input:focus { outline: none; border-color: #4a9eff; }
        .btn { width: 100%; padding: 0.75rem; background: #4a9eff; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; font-weight: bold; transition: all 0.3s; }
        .btn:hover { background: #357abd; transform: translateY(-1px); }
        .error { color: #ff4444; margin-top: 1rem; text-align: center; }
        .back-link { text-align: center; margin-top: 1rem; }
        .back-link a { color: #4a9eff; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Admin Panel</h1>
            <p>Yönetici girişi yapın</p>
        </div>
        
        <div class="card">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="form-group">
                    <label for="password">Şifre</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" name="login" class="btn">Giriş Yap</button>
            </form>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="back-link">
            <a href="../index.php">← Ana Sayfaya Dön</a>
        </div>
    </div>
</body>
</html>