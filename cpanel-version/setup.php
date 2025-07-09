<?php
/**
 * KiWiPazari Web Tabanlı Kurulum Scripti
 * Tarayıcı üzerinden kolay kurulum için
 */

header('Content-Type: text/html; charset=utf-8');

// Güvenlik kontrolü
$setup_completed = file_exists('.setup_completed');
if ($setup_completed && !isset($_GET['force'])) {
    die('<h1>Kurulum Tamamlanmış</h1><p>Kurulum zaten tamamlanmış. Tekrar kurmak için URL\'ye ?force=1 ekleyin.</p>');
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$action = isset($_POST['action']) ? $_POST['action'] : '';

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KiWiPazari Kurulum</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
        }
        .logo {
            text-align: center;
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        .step {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .step-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        input, textarea, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5a67d8;
        }
        .btn-success {
            background: #48bb78;
        }
        .btn-success:hover {
            background: #38a169;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .progress {
            background: #e9ecef;
            border-radius: 10px;
            height: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .progress-bar {
            background: #667eea;
            height: 100%;
            transition: width 0.3s;
        }
        .code {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            border-left: 4px solid #667eea;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">KIWIPAZARI</div>
        <div class="subtitle">Otomatik Kurulum Sihirbazı</div>
        
        <div class="progress">
            <div class="progress-bar" style="width: <?= $step * 25 ?>%"></div>
        </div>

        <?php if ($step == 1): ?>
            <!-- Adım 1: Hoş Geldiniz -->
            <div class="step">
                <div class="step-title">🎉 Hoş Geldiniz!</div>
                <p>KiWiPazari API key yönetim sistemi kurulumuna hoş geldiniz. Bu sihirbaz size adım adım kurulum sürecinde rehberlik edecek.</p>
                
                <h4>Sistem Gereksinimleri:</h4>
                <ul style="margin: 15px 0; padding-left: 20px;">
                    <li>Node.js v16+ (cPanel Node.js Selector'dan aktifleştirin)</li>
                    <li>MySQL veya PostgreSQL veritabanı</li>
                    <li>cPanel hosting hesabı</li>
                </ul>

                <?php
                // Sistem kontrolü
                $checks = [];
                
                // Node.js kontrolü
                exec('node --version 2>&1', $node_output, $node_return);
                $checks['node'] = $node_return === 0;
                
                // NPM kontrolü  
                exec('npm --version 2>&1', $npm_output, $npm_return);
                $checks['npm'] = $npm_return === 0;
                
                // package.json kontrolü
                $checks['package'] = file_exists('package.json');
                
                // Yazma izni kontrolü
                $checks['writable'] = is_writable('.');
                ?>

                <h4>Sistem Durumu:</h4>
                <ul style="margin: 15px 0; padding-left: 20px;">
                    <li><?= $checks['node'] ? '✅' : '❌' ?> Node.js <?= $checks['node'] ? '(' . trim($node_output[0]) . ')' : '(Bulunamadı)' ?></li>
                    <li><?= $checks['npm'] ? '✅' : '❌' ?> NPM <?= $checks['npm'] ? '(' . trim($npm_output[0]) . ')' : '(Bulunamadı)' ?></li>
                    <li><?= $checks['package'] ? '✅' : '❌' ?> package.json</li>
                    <li><?= $checks['writable'] ? '✅' : '❌' ?> Yazma İzni</li>
                </ul>

                <?php $all_checks_passed = !in_array(false, $checks); ?>
                
                <?php if ($all_checks_passed): ?>
                    <div class="alert alert-success">
                        <strong>Harika!</strong> Sistem gereksinimleri karşılanıyor. Kuruluma devam edebilirsiniz.
                    </div>
                    <form method="get">
                        <input type="hidden" name="step" value="2">
                        <button type="submit" class="btn">Sonraki Adım →</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-error">
                        <strong>Dikkat!</strong> Bazı gereksinimler karşılanmıyor. Lütfen eksiklikleri giderin ve sayfayı yenileyin.
                    </div>
                    <button onclick="location.reload()" class="btn">Tekrar Kontrol Et</button>
                <?php endif; ?>
            </div>

        <?php elseif ($step == 2): ?>
            <!-- Adım 2: Veritabanı Ayarları -->
            <?php if ($action == 'save_database'): ?>
                <?php
                // .env dosyasını oluştur
                $env_content = "# KiWiPazari Environment Configuration\n";
                $env_content .= "# Generated by setup wizard\n\n";
                $env_content .= "DATABASE_URL=" . $_POST['database_url'] . "\n";
                $env_content .= "PORT=" . $_POST['port'] . "\n";
                $env_content .= "NODE_ENV=" . $_POST['environment'] . "\n";
                $env_content .= "SESSION_SECRET=" . bin2hex(random_bytes(32)) . "\n";
                $env_content .= "SECURE_COOKIES=" . ($_POST['environment'] == 'production' ? 'true' : 'false') . "\n";
                
                if (file_put_contents('.env', $env_content)) {
                    echo '<div class="alert alert-success"><strong>Başarılı!</strong> Veritabanı ayarları kaydedildi.</div>';
                    echo '<form method="get"><input type="hidden" name="step" value="3"><button type="submit" class="btn">Sonraki Adım →</button></form>';
                } else {
                    echo '<div class="alert alert-error"><strong>Hata!</strong> .env dosyası oluşturulamadı.</div>';
                }
                ?>
            <?php else: ?>
                <div class="step">
                    <div class="step-title">🗄️ Veritabanı Ayarları</div>
                    <p>Veritabanı bağlantı bilgilerinizi girin:</p>
                    
                    <form method="post">
                        <input type="hidden" name="action" value="save_database">
                        
                        <div class="form-group">
                            <label>Veritabanı Türü:</label>
                            <select name="db_type" onchange="updateDatabaseUrl()">
                                <option value="postgresql">PostgreSQL</option>
                                <option value="mysql">MySQL</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Veritabanı URL:</label>
                            <input type="text" name="database_url" id="database_url" 
                                   placeholder="postgresql://kullanici:sifre@localhost:5432/veritabani_adi" required>
                            <small style="color: #666;">cPanel veritabanı bilgilerinizi buraya girin</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Port:</label>
                            <input type="number" name="port" value="3000" min="1000" max="65535" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Ortam:</label>
                            <select name="environment">
                                <option value="production">Production (Canlı)</option>
                                <option value="development">Development (Test)</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn">Ayarları Kaydet</button>
                    </form>
                </div>
                
                <script>
                function updateDatabaseUrl() {
                    const dbType = document.querySelector('select[name="db_type"]').value;
                    const urlField = document.getElementById('database_url');
                    
                    if (dbType === 'postgresql') {
                        urlField.placeholder = "postgresql://kullanici:sifre@localhost:5432/veritabani_adi";
                    } else {
                        urlField.placeholder = "mysql://kullanici:sifre@localhost:3306/veritabani_adi";
                    }
                }
                </script>
            <?php endif; ?>

        <?php elseif ($step == 3): ?>
            <!-- Adım 3: Bağımlılık Kurulumu -->
            <?php if ($action == 'install_dependencies'): ?>
                <div class="step">
                    <div class="step-title">📦 Bağımlılıklar Yükleniyor...</div>
                    <p>Bu işlem birkaç dakika sürebilir. Lütfen bekleyin...</p>
                    
                    <?php
                    echo '<div class="code">';
                    echo 'npm install --production<br><br>';
                    
                    // NPM install komutunu çalıştır
                    $install_command = 'npm install --production 2>&1';
                    exec($install_command, $install_output, $install_return);
                    
                    foreach ($install_output as $line) {
                        echo htmlspecialchars($line) . '<br>';
                    }
                    echo '</div>';
                    
                    if ($install_return === 0) {
                        echo '<div class="alert alert-success"><strong>Başarılı!</strong> Bağımlılıklar yüklendi.</div>';
                        echo '<form method="get"><input type="hidden" name="step" value="4"><button type="submit" class="btn">Sonraki Adım →</button></form>';
                    } else {
                        echo '<div class="alert alert-error"><strong>Hata!</strong> Bağımlılıklar yüklenemedi.</div>';
                        echo '<button onclick="history.back()" class="btn">Geri Dön</button>';
                    }
                    ?>
                </div>
            <?php else: ?>
                <div class="step">
                    <div class="step-title">📦 Bağımlılık Kurulumu</div>
                    <p>Node.js bağımlılıklarını yüklemek için hazırız.</p>
                    
                    <div class="alert alert-warning">
                        <strong>Dikkat:</strong> Bu işlem birkaç dakika sürebilir ve internet bağlantısı gerektirir.
                    </div>
                    
                    <form method="post">
                        <input type="hidden" name="action" value="install_dependencies">
                        <button type="submit" class="btn">Bağımlılıkları Yükle</button>
                    </form>
                </div>
            <?php endif; ?>

        <?php elseif ($step == 4): ?>
            <!-- Adım 4: Tamamlandı -->
            <div class="step">
                <div class="step-title">🎉 Kurulum Tamamlandı!</div>
                
                <div class="alert alert-success">
                    <strong>Tebrikler!</strong> KiWiPazari başarıyla kuruldu.
                </div>
                
                <h4>📋 Sonraki Adımlar:</h4>
                <ol style="margin: 15px 0; padding-left: 20px;">
                    <li><strong>Veritabanı Tablolarını Oluşturun:</strong>
                        <div class="code">phpMyAdmin'de install.sql dosyasını çalıştırın</div>
                    </li>
                    <li><strong>Uygulamayı Başlatın:</strong>
                        <div class="code">node index.js</div>
                    </li>
                </ol>
                
                <h4>🔗 Erişim Bilgileri:</h4>
                <ul style="margin: 15px 0; padding-left: 20px;">
                    <li><strong>Ana Site:</strong> <?= "http://{$_SERVER['HTTP_HOST']}" ?></li>
                    <li><strong>Admin Panel:</strong> <?= "http://{$_SERVER['HTTP_HOST']}/kiwi-management-portal" ?></li>
                    <li><strong>Admin Şifre:</strong> <code>ucFMkvJ5Tngq7QCN9Dl31edSWaPAmIRxfGwL62ih4U8jb0VosKHtO</code></li>
                </ul>
                
                <?php
                // Kurulum tamamlandı işareti
                file_put_contents('.setup_completed', date('Y-m-d H:i:s'));
                ?>
                
                <button onclick="window.location.href='/'" class="btn btn-success">Ana Sayfaya Git</button>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>