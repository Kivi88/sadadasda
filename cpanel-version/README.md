# KiWiPazari - PHP/MySQL Version

Bu, KiWiPazari uygulamasının PHP/MySQL ile yazılmış tam işlevsel versiyonudur. cPanel shared hosting ortamında çalışacak şekilde optimize edilmiştir.

## Özellikler

- ✅ **Ana Sayfa**: Key doğrulama ve sipariş oluşturma
- ✅ **Sipariş Sorgulama**: Sipariş durumu takibi
- ✅ **Admin Paneli**: Tam yönetim sistemi
- ✅ **API Yönetimi**: Harici API'leri ekle, test et, servisleri çek
- ✅ **Servis Yönetimi**: Servisleri görüntüle ve filtrele
- ✅ **Key Yönetimi**: Key oluştur, düzenle, indir
- ✅ **Sipariş Yönetimi**: Siparişleri takip et
- ✅ **Güvenlik**: CSRF koruması, rate limiting, input validation
- ✅ **Harici API Entegrasyonu**: Gerçek API'lerle sipariş oluşturma

## Kurulum

### 1. Dosyaları Yükleyin
- Tüm dosyaları cPanel File Manager ile public_html klasörüne yükleyin
- Alternatif olarak FTP ile yükleyebilirsiniz

### 2. Veritabanı Kurulumu
- `yoursite.com/setup.php` adresine gidin
- Veritabanı bilgilerini girin:
  - **Host**: localhost (genellikle)
  - **Database**: MySQL veritabanı adı
  - **Username**: MySQL kullanıcı adı
  - **Password**: MySQL şifresi
  - **Admin Password**: Admin panel şifresi (varsayılan: admin123)

### 3. Dosya İzinleri
```bash
chmod 755 /public_html/
chmod 644 /public_html/*.php
chmod 644 /public_html/.htaccess
chmod 755 /public_html/kiwi-management-portal/
chmod 644 /public_html/kiwi-management-portal/*.php
```

## Kullanım

### Ana Sayfa
- `yoursite.com` - Key doğrulama ve sipariş oluşturma
- Sipariş sorgulama sistemi

### Admin Paneli
- `yoursite.com/kiwi-management-portal` - Admin girişi
- Varsayılan şifre: admin123 (setup sırasında değiştirilebilir)

### Admin Paneli Özellikleri:

#### 1. API Yönetimi
- Harici API'leri ekle, düzenle, sil
- API testleri yapma
- Servisleri otomatik çekme (sınırsız veya limitli)

#### 2. Servis Yönetimi
- Tüm servisleri görüntüleme
- Platform, kategori ve API'ye göre filtreleme
- Sayfalama ve arama

#### 3. Key Yönetimi
- Key oluşturma (otomatik benzersiz key'ler)
- Key düzenleme ve silme
- Servis adına göre key'leri toplu indirme
- Kullanım durumu takibi

#### 4. Sipariş Yönetimi
- Tüm siparişleri görüntüleme
- Durum takibi (bekleyen, işlenen, tamamlanan, vb.)
- Harici API'lerle otomatik senkronizasyon

## Güvenlik Özellikleri

- **CSRF Koruması**: Tüm formlarda token koruması
- **Rate Limiting**: Brute force saldırılarına karşı koruma
- **Input Validation**: Tüm girişlerde doğrulama
- **SQL Injection Koruması**: Prepared statements kullanımı
- **XSS Koruması**: HTML entity encoding
- **Session Security**: Güvenli session ayarları
- **File Protection**: .htaccess ile dosya koruması

## Veritabanı Yapısı

### Tablolar:
- `apis` - Harici API bilgileri
- `services` - API servis bilgileri
- `keys` - Müşteri key'leri
- `orders` - Sipariş bilgileri
- `rate_limits` - Rate limiting verileri

## Desteklenen API Formatları

Sistem çeşitli API formatlarını destekler:
- MedyaBayim API
- Standart SMM panel API'leri
- Özel API formatları

## Troubleshooting

### Yaygın Sorunlar:

1. **Veritabanı Bağlantı Hatası**
   - config.php dosyasında veritabanı bilgilerini kontrol edin
   - MySQL servisinin çalıştığından emin olun

2. **Admin Panel Erişim Sorunu**
   - URL: yoursite.com/kiwi-management-portal
   - Şifre: setup sırasında belirlediğiniz şifre

3. **API Servisleri Çekilmiyor**
   - API URL ve key'i doğru olduğundan emin olun
   - API test özelliğini kullanın

4. **Sipariş Oluşturma Hatası**
   - Key'in aktif olduğundan emin olun
   - Yeterli key limitinin olduğunu kontrol edin

## Teknik Detaylar

- **PHP Version**: 7.4+
- **MySQL Version**: 5.7+
- **Required Extensions**: PDO, MySQLi, cURL, JSON
- **Memory Limit**: 128MB önerilir
- **Execution Time**: 30 saniye

## Lisans

Bu proje MIT lisansı altında lisanslanmıştır.

## Destek

Herhangi bir sorun yaşarsanız:
1. README dosyasını kontrol edin
2. Hata loglarını inceleyin
3. Veritabanı bağlantısını test edin
4. API bağlantılarını kontrol edin

---

**Not**: Bu sistem production-ready olarak tasarlanmıştır ve tüm güvenlik önlemleri alınmıştır. Düzenli backup almayı unutmayın.