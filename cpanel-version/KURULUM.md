# 🚀 KIWIPAZARI - cPanel Kurulum Kılavuzu

## 📦 İndirme

`kiwipazari-cpanel.tar.gz` dosyasını indirin ve hosting hesabınıza yükleyin.

## ⚡ Hızlı Kurulum

### 1. Dosyaları Yükleme
1. cPanel File Manager'ı açın
2. `public_html` klasörüne gidin
3. `kiwipazari-cpanel.tar.gz` dosyasını yükleyin
4. Dosyaya sağ tıklayın → "Extract" seçin

### 2. Veritabanı Kurulumu
1. cPanel'de **MySQL Databases** bölümüne gidin
2. Yeni veritabanı oluşturun: `kiwipazari_db`
3. Yeni kullanıcı oluşturun ve tüm yetkileri verin
4. `install.sql` dosyasını **phpMyAdmin**'de çalıştırın

### 3. Ortam Ayarları
1. `.env.example` dosyasını `.env` olarak kopyalayın
2. Veritabanı bilgilerini düzenleyin:

```bash
DATABASE_URL=mysql://kullanici:sifre@localhost/kiwipazari_db
PORT=3000
NODE_ENV=production
SESSION_SECRET=gizli-anahtar-buraya-yazin
```

### 4. Node.js Aktifleştirme
1. cPanel'de **Node.js Selector**'a gidin
2. Node.js'i aktifleştirin (v16+ gerekli)
3. **Startup File**: `index.js`
4. **Application Mode**: Production

### 5. Başlatma
Dosya yöneticisinde terminal açın:
```bash
npm install
node start.js
```

## 🔗 Erişim

- **Ana Site**: `https://yourdomain.com`
- **Admin Panel**: `https://yourdomain.com/kiwi-management-portal`
- **Admin Şifre**: `ucFMkvJ5Tngq7QCN9Dl31edSWaPAmIRxfGwL62ih4U8jb0VosKHtO`

## ✅ Özellikler

- ✅ API key yönetimi
- ✅ Servis yönetimi  
- ✅ Sipariş takibi
- ✅ Güvenlik koruması
- ✅ Rate limiting
- ✅ Admin paneli

## 🚨 Sorun Giderme

**Node.js bulunamadı**: cPanel Node.js Selector'dan aktifleştirin
**Veritabanı hatası**: DATABASE_URL'i kontrol edin
**Port hatası**: .env dosyasında farklı port deneyin

Kurulum tamamlandığında siteniz tamamen çalışır durumda olacak! 🎉