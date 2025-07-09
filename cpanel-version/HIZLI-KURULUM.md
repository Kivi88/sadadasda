# ⚡ KiWiPazari Hızlı Kurulum

## 🎯 Tek Tıkla Kurulum

### Yöntem 1: Web Tabanlı Kurulum (Önerilen)
1. `kiwipazari-cpanel.tar.gz` dosyasını cPanel File Manager'a yükleyin
2. Dosyayı çıkarın (Extract)
3. Tarayıcıda `https://yourdomain.com/setup.php` adresine gidin
4. Kurulum sihirbazını takip edin

### Yöntem 2: Terminal Kurulum
```bash
# Dosyaları çıkarın
tar -xzf kiwipazari-cpanel.tar.gz

# Kurulum scriptini çalıştırın
chmod +x setup.sh
./setup.sh
```

## 🚀 Otomatik Kurulum Özellikleri

### Web Kurulum (setup.php)
- ✅ Görsel kurulum sihirbazı
- ✅ Sistem gereksinimlerini otomatik kontrol
- ✅ Veritabanı ayarlarını web üzerinden yapılandırma
- ✅ Bağımlılıkları otomatik yükleme
- ✅ .env dosyasını otomatik oluşturma
- ✅ Adım adım rehberlik

### Terminal Kurulum (setup.sh)
- ✅ Node.js ve NPM kontrolü
- ✅ Otomatik bağımlılık yükleme
- ✅ Dosya izinlerini ayarlama
- ✅ Port konfigürasyonu
- ✅ Hata kontrolü ve detaylı log

## 📋 Kurulum Sonrası

### Veritabanı Kurulumu
1. cPanel'de **phpMyAdmin**'e gidin
2. Veritabanınızı seçin
3. `install.sql` dosyasını içe aktarın (Import)

### Uygulama Başlatma
```bash
# Normal başlatma
node index.js

# PM2 ile başlatma (önerilen)
pm2 start index.js --name kiwipazari
```

## 🔧 Manuel Konfigürasyon

Otomatik kurulum çalışmazsa:

### 1. .env Dosyası
```bash
DATABASE_URL=postgresql://kullanici:sifre@localhost:5432/veritabani
PORT=3000
NODE_ENV=production
SESSION_SECRET=gizli-anahtar-buraya
```

### 2. Bağımlılıklar
```bash
npm install --production
```

### 3. Başlatma
```bash
node index.js
```

## 🎯 Erişim Bilgileri

- **Ana Site**: `https://yourdomain.com`
- **Admin Panel**: `https://yourdomain.com/kiwi-management-portal`
- **Admin Şifre**: `ucFMkvJ5Tngq7QCN9Dl31edSWaPAmIRxfGwL62ih4U8jb0VosKHtO`

## 🆘 Sorun Giderme

### Yaygın Hatalar
1. **Node.js bulunamadı**: cPanel Node.js Selector'dan aktifleştirin
2. **Veritabanı bağlantı hatası**: .env dosyasındaki DATABASE_URL'i kontrol edin
3. **Port zaten kullanımda**: .env dosyasında farklı port deneyin

### Destek
Kurulum sırasında sorun yaşarsanız README.md dosyasını inceleyin.

---
**🎉 5 dakikada kurulum tamamlanır!**