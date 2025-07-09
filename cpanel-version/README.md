# KiWiPazari - cPanel Installation Guide

Bu doküman KiWiPazari uygulamasının cPanel hosting ortamında nasıl kurulacağını açıklamaktadır.

## 📋 Gereksinimler

- cPanel hosting hesabı
- Node.js desteği (en az v16.0.0)
- PostgreSQL veya MySQL veritabanı
- SSH erişimi (opsiyonel)

## 🚀 Kurulum Adımları

### 1. Dosyaları Yükleme

1. Bu klasördeki tüm dosyaları cPanel File Manager ile hosting hesabınıza yükleyin
2. Dosyaları `public_html` veya alt domain klasörüne koyun

### 2. Veritabanı Kurulumu

1. cPanel'de **MySQL Databases** veya **PostgreSQL** bölümüne gidin
2. Yeni bir veritabanı oluşturun (örn: `kiwipazari_db`)
3. Veritabanı kullanıcısı oluşturun ve tüm yetkileri verin
4. Bağlantı bilgilerini kaydedin

### 3. Ortam Değişkenlerini Ayarlama

1. `.env.example` dosyasını `.env` olarak kopyalayın
2. Veritabanı bilgilerinizi girin:

```bash
DATABASE_URL=postgresql://kullanici:sifre@localhost:5432/veritabani_adi
PORT=3000
NODE_ENV=production
SESSION_SECRET=gizli-anahtar-buraya-yazin
```

### 4. Bağımlılıkları Yükleme

SSH ile bağlanın ve şu komutu çalıştırın:

```bash
npm install
```

Eğer SSH erişiminiz yoksa, cPanel'in **Node.js Selector** özelliğini kullanın.

### 5. Veritabanı Tablolarını Oluşturma

İlk çalıştırmada tablolar otomatik oluşturulacaktır. Manuel olarak oluşturmak için:

```sql
-- APIs tablosu
CREATE TABLE apis (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL,
    base_url TEXT NOT NULL,
    api_key TEXT NOT NULL,
    services_endpoint TEXT,
    order_endpoint TEXT,
    status_endpoint TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Services tablosu
CREATE TABLE services (
    id SERIAL PRIMARY KEY,
    api_id INTEGER REFERENCES apis(id),
    external_id TEXT,
    name TEXT NOT NULL,
    platform TEXT,
    category TEXT,
    description TEXT,
    min_quantity INTEGER DEFAULT 1,
    max_quantity INTEGER DEFAULT 10000,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Keys tablosu
CREATE TABLE keys (
    id SERIAL PRIMARY KEY,
    key_value TEXT NOT NULL UNIQUE,
    name TEXT,
    service_id INTEGER REFERENCES services(id),
    max_amount INTEGER DEFAULT 1000,
    used_amount INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Orders tablosu
CREATE TABLE orders (
    id SERIAL PRIMARY KEY,
    order_id TEXT NOT NULL UNIQUE,
    key_id INTEGER REFERENCES keys(id),
    service_id INTEGER REFERENCES services(id),
    link TEXT NOT NULL,
    quantity INTEGER NOT NULL,
    status TEXT DEFAULT 'pending',
    external_order_id TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);
```

### 6. Uygulamayı Başlatma

cPanel'de **Node.js Selector** ile:

1. **Node.js Selector**'a gidin
2. Doğru klasörü seçin
3. **Startup File**: `index.js`
4. **Application Mode**: Production
5. **Start** butonuna tıklayın

Veya SSH ile:

```bash
node index.js
```

## 🔧 Konfigürasyon

### SSL/HTTPS Kurulumu

Eğer SSL sertifikanız varsa:

1. `.env` dosyasında `SECURE_COOKIES=true` yapın
2. `NODE_ENV=production` olduğundan emin olun

### Port Ayarları

cPanel hosting genelde belirli portlar kullanır:

- Shared hosting: Genelde port 3000 veya hosting sağlayıcınızın belirttiği port
- VPS/Dedicated: İstediğiniz port (3000, 8080, vb.)

### Domain/Subdomain Kurulumu

1. cPanel'de **Subdomains** bölümünden yeni subdomain oluşturun
2. Document Root'u uygulamanızın bulunduğu klasör yapın
3. DNS ayarlarını kontrol edin

## 🔒 Güvenlik Ayarları

### Admin Şifresi

Varsayılan admin şifresi: `ucFMkvJ5Tngq7QCN9Dl31edSWaPAmIRxfGwL62ih4U8jb0VosKHtO`

Admin paneline erişim: `https://yourdomain.com/kiwi-management-portal`

### Güvenlik Önlemleri

- `.env` dosyasının public erişime kapalı olduğundan emin olun
- Admin şifresini mutlaka değiştirin
- SSL sertifikası kullanın
- Güvenlik duvarı ayarlarını kontrol edin

## 📁 Dosya Yapısı

```
cpanel-version/
├── index.js           # Ana sunucu dosyası
├── routes.js          # API route'ları
├── storage.js         # Veritabanı işlemleri
├── package.json       # Bağımlılıklar
├── .env.example       # Ortam değişkenleri örneği
├── README.md          # Bu dosya
└── public/            # Frontend dosyaları (build sonrası)
```

## 🚨 Sorun Giderme

### Yaygın Hatalar

1. **Port zaten kullanımda**
   - `.env` dosyasında farklı port deneyin
   - `netstat -tulpn` ile kullanılan portları kontrol edin

2. **Veritabanı bağlantı hatası**
   - DATABASE_URL'in doğru olduğundan emin olun
   - Veritabanı kullanıcısının yetkileri kontrolü

3. **Node.js versiyonu uyumsuz**
   - En az Node.js v16.0.0 gerekli
   - cPanel Node.js Selector'dan güncelleme yapın

### Log Kontrolü

```bash
# PM2 kullanıyorsanız
pm2 logs

# Normal çalıştırma
node index.js
```

## 📞 Destek

Kurulum sırasında sorun yaşarsanız:

1. Log dosyalarını kontrol edin
2. .env dosyasının doğru yapılandırıldığından emin olun
3. Veritabanı bağlantısını test edin

## 🎯 Özellikler

- ✅ API key yönetimi
- ✅ Servis yönetimi
- ✅ Sipariş takibi
- ✅ Admin paneli
- ✅ Güvenlik koruması
- ✅ Rate limiting
- ✅ CSRF koruması

Kurulum tamamlandığında uygulamanız tamamen çalışır durumda olacak!