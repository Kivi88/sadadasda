# KiWiPazari - PHP/MySQL Sürümü

Bu proje, sosyal medya hizmetleri için anahtar yönetimi ve sipariş sistemi sağlayan bir web uygulamasıdır. cPanel hosting ortamları için optimize edilmiştir.

## Özellikler

- **Anahtar Doğrulama**: Müşteriler anahtarlarını doğrulayabilir
- **Sipariş Yönetimi**: Servis siparişleri oluşturma ve takip
- **Admin Paneli**: API'ler, servisler, anahtarlar ve siparişler için yönetim
- **Güvenlik**: Rate limiting, CSRF koruması, input validation
- **Responsive Tasarım**: Mobil ve masaüstü uyumlu

## Kurulum

### 1. Dosyaları Yükleme
Tüm dosyaları hosting hesabınızın public_html klasörüne yükleyin.

### 2. Veritabanı Kurulumu
1. `setup.php` dosyasını çalıştırın: `https://yourdomain.com/setup.php`
2. Veritabanı ayarlarını `config.php` dosyasında düzenleyin:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   define('DB_NAME', 'your_db_name');
   ```

### 3. Dosya İzinleri
Aşağıdaki dosyalara yazma izni verin:
- `config.php` (644)
- `api/` klasörü (755)

### 4. Güvenlik Ayarları
- `config.php` dosyasındaki güvenlik ayarlarını kontrol edin
- Admin şifresini değiştirin (varsayılan: ucFMkvJ5Tngq7QCN9Dl31edSWaPAmIRxfGwL62ih4U8jb0VosKHtO)

## Admin Paneli

Admin paneline erişim için:
- URL: `https://yourdomain.com/kiwi-management-portal`
- Kullanıcı Adı: `admin`
- Şifre: `ucFMkvJ5Tngq7QCN9Dl31edSWaPAmIRxfGwL62ih4U8jb0VosKHtO`

## API Endpoints

### Genel API'ler
- `POST /api/validate-key.php` - Anahtar doğrulama
- `POST /api/create-order.php` - Sipariş oluşturma
- `POST /api/search-order.php` - Sipariş arama

### Admin API'leri
- `GET /api/admin/apis.php` - API'leri listele
- `POST /api/admin/apis.php` - API ekle
- `DELETE /api/admin/apis.php` - API sil

- `GET /api/admin/services.php` - Servisleri listele
- `POST /api/admin/services.php` - Servis ekle
- `DELETE /api/admin/services.php` - Servis sil

- `GET /api/admin/keys.php` - Anahtarları listele
- `POST /api/admin/keys.php` - Anahtar ekle
- `DELETE /api/admin/keys.php` - Anahtar sil

- `GET /api/admin/orders.php` - Siparişleri listele
- `PUT /api/admin/orders.php` - Sipariş durumu güncelle
- `DELETE /api/admin/orders.php` - Sipariş sil

## Veritabanı Yapısı

### `apis` Tablosu
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `name` (VARCHAR(255), NOT NULL)
- `url` (VARCHAR(500), NOT NULL)
- `api_key` (VARCHAR(255), NOT NULL)
- `is_active` (BOOLEAN, DEFAULT TRUE)
- `last_sync` (TIMESTAMP, NULL)
- `response_time` (INT, NULL)
- `service_count` (INT, DEFAULT 0)
- `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- `updated_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

### `services` Tablosu
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `api_id` (INT, FOREIGN KEY -> apis.id)
- `external_id` (VARCHAR(255), NOT NULL)
- `name` (VARCHAR(500), NOT NULL)
- `platform` (VARCHAR(100), NOT NULL)
- `category` (VARCHAR(100), NOT NULL)
- `min_quantity` (INT, DEFAULT 1)
- `max_quantity` (INT, DEFAULT 10000)
- `is_active` (BOOLEAN, DEFAULT TRUE)
- `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- `updated_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

### `keys` Tablosu
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `key_value` (VARCHAR(255), NOT NULL, UNIQUE)
- `service_id` (INT, FOREIGN KEY -> services.id)
- `name` (VARCHAR(255), NOT NULL)
- `prefix` (VARCHAR(50), DEFAULT 'KIWIPAZARI')
- `max_amount` (INT, DEFAULT 1000)
- `used_amount` (INT, DEFAULT 0)
- `is_active` (BOOLEAN, DEFAULT TRUE)
- `is_hidden` (BOOLEAN, DEFAULT FALSE)
- `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- `updated_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

### `orders` Tablosu
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `order_id` (VARCHAR(255), NOT NULL, UNIQUE)
- `key_id` (INT, FOREIGN KEY -> keys.id)
- `service_id` (INT, FOREIGN KEY -> services.id)
- `link` (VARCHAR(500), NOT NULL)
- `quantity` (INT, NOT NULL)
- `status` (VARCHAR(50), DEFAULT 'pending')
- `external_order_id` (VARCHAR(255), NULL)
- `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- `updated_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

## Güvenlik Özellikleri

- **Rate Limiting**: 15 deneme/15 dakika admin girişi için
- **CSRF Koruması**: Tüm formlar için
- **Input Validation**: Tüm kullanıcı girişleri doğrulanır
- **SQL Injection Koruması**: Prepared statements kullanılır
- **XSS Koruması**: Tüm çıktılar sanitize edilir
- **Secure Headers**: HTTP güvenlik başlıkları
- **Session Management**: Güvenli oturum yönetimi

## Konfigürasyon

### config.php Ayarları
```php
// Veritabanı ayarları
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'your_database');

// Sistem ayarları
define('SITE_NAME', 'KiWiPazari');
define('ADMIN_PATH', '/kiwi-management-portal');
define('DEFAULT_KEY_PREFIX', 'KIWIPAZARI');

// Güvenlik ayarları
define('SESSION_TIMEOUT', 3600);
define('RATE_LIMIT_ATTEMPTS', 15);
define('RATE_LIMIT_WINDOW', 900);
```

## Sorun Giderme

### Yaygın Sorunlar

1. **Veritabanı Bağlantı Hatası**
   - `config.php` dosyasındaki veritabanı bilgilerini kontrol edin
   - Hosting sağlayıcınızdan doğru bilgileri alın

2. **500 Internal Server Error**
   - Dosya izinlerini kontrol edin
   - Error log'larını inceleyin

3. **API Çalışmıyor**
   - `.htaccess` dosyasının doğru yüklendiğini kontrol edin
   - mod_rewrite modülünün aktif olduğundan emin olun

4. **Admin Paneline Erişim Sorunu**
   - `/kiwi-management-portal` URL'sini kullanın
   - Varsayılan kullanıcı adı: `admin`

## Destek

Bu sistem tamamen özelleştirilebilir ve genişletilebilir. Herhangi bir sorun durumunda:

1. Error log'larını kontrol edin
2. Veritabanı bağlantısını test edin
3. Dosya izinlerini kontrol edin
4. Hosting sağlayıcınızla iletişime geçin

## Lisans

Bu proje MIT lisansı altında lisanslanmıştır.