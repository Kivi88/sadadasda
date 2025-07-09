<?php
// Şifrenizi buraya yazın
$new_password = "YeniSifreniz123";

// Hash oluştur
$hash = password_hash($new_password, PASSWORD_DEFAULT);

echo "Yeni şifreniz: " . $new_password . "<br>";
echo "Hash: " . $hash . "<br><br>";
echo "Bu hash'i config.php dosyasındaki ADMIN_PASSWORD_HASH satırına yapıştırın.";

// Bu dosyayı kullandıktan sonra silin!
?>