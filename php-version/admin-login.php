<?php
require_once 'config.php';

// Eğer zaten giriş yapılmışsa admin paneline yönlendir
if (isAdmin()) {
    header('Location: admin.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = validateInput($_POST['username']);
    $password = $_POST['password'];
    
    if (!checkRateLimit($_SERVER['REMOTE_ADDR'], 'admin_login')) {
        $error = 'Çok fazla deneme yaptınız. Lütfen daha sonra tekrar deneyin.';
    } else {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT password FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                header('Location: admin.php');
                exit;
            }
        }
        $error = 'Geçersiz kullanıcı adı veya şifre.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body { background: #0f172a; color: #e2e8f0; }
        .gradient-bg { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); }
        .border-slate { border-color: #475569; }
        .text-slate-400 { color: #94a3b8; }
    </style>
</head>
<body class="min-h-screen gradient-bg flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">Admin Girişi</h1>
            <p class="text-slate-400"><?php echo SITE_NAME; ?> Yönetim Paneli</p>
        </div>

        <div class="bg-gray-800 rounded-lg p-6 shadow-lg border border-slate">
            <?php if ($error): ?>
                <div class="bg-red-900 border border-red-700 text-red-100 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">
                        Kullanıcı Adı
                    </label>
                    <input 
                        type="text" 
                        name="username" 
                        required
                        class="w-full px-3 py-2 bg-gray-700 border border-slate rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">
                        Şifre
                    </label>
                    <input 
                        type="password" 
                        name="password" 
                        required
                        class="w-full px-3 py-2 bg-gray-700 border border-slate rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>

                <button 
                    type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors"
                >
                    Giriş Yap
                </button>
            </form>

            <div class="mt-4 text-center">
                <a href="index.php" class="text-slate-400 hover:text-white text-sm">
                    ← Ana Sayfaya Dön
                </a>
            </div>
        </div>
    </div>
</body>
</html>