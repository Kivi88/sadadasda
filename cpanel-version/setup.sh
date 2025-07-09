#!/bin/bash

# KiWiPazari Otomatik Kurulum Scripti
# Bu script cPanel ortamında otomatik kurulum yapar

echo "🚀 KiWiPazari Otomatik Kurulum Başlatılıyor..."
echo "=============================================="

# Renk kodları
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Hata kontrolü fonksiyonu
check_error() {
    if [ $? -ne 0 ]; then
        echo -e "${RED}❌ Hata: $1${NC}"
        exit 1
    fi
}

# Başlık yazdırma fonksiyonu
print_step() {
    echo -e "\n${BLUE}📋 $1${NC}"
    echo "----------------------------------------"
}

# Node.js kontrolü
print_step "Node.js Kontrol Ediliyor"
if command -v node &> /dev/null; then
    NODE_VERSION=$(node --version)
    echo -e "${GREEN}✅ Node.js bulundu: $NODE_VERSION${NC}"
else
    echo -e "${RED}❌ Node.js bulunamadı!${NC}"
    echo "cPanel Node.js Selector'dan Node.js'i aktifleştirin (v16+)"
    exit 1
fi

# NPM kontrolü
if command -v npm &> /dev/null; then
    NPM_VERSION=$(npm --version)
    echo -e "${GREEN}✅ NPM bulundu: $NPM_VERSION${NC}"
else
    echo -e "${RED}❌ NPM bulunamadı!${NC}"
    exit 1
fi

# Dizin kontrolü
print_step "Proje Dizini Kontrol Ediliyor"
if [ ! -f "package.json" ]; then
    echo -e "${RED}❌ package.json bulunamadı! Doğru dizinde olduğunuzdan emin olun.${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Proje dizini doğru${NC}"

# .env dosyası kontrolü ve oluşturma
print_step "Ortam Değişkenleri Ayarlanıyor"
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        cp .env.example .env
        echo -e "${YELLOW}⚠️  .env dosyası .env.example'dan oluşturuldu${NC}"
        echo -e "${YELLOW}💡 Lütfen .env dosyasını düzenleyerek veritabanı bilgilerinizi girin${NC}"
    else
        echo -e "${RED}❌ .env.example dosyası bulunamadı!${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}✅ .env dosyası mevcut${NC}"
fi

# .env dosyasında kritik değişkenleri kontrol et
print_step "Veritabanı Konfigürasyonu Kontrol Ediliyor"
if grep -q "postgresql://username:password" .env || grep -q "mysql://username:password" .env; then
    echo -e "${YELLOW}⚠️  .env dosyasında varsayılan veritabanı bilgileri bulundu${NC}"
    echo -e "${YELLOW}💡 Kuruluma devam etmeden önce gerçek veritabanı bilgilerinizi girin${NC}"
    
    # Kullanıcı onayı iste
    echo -n "Yine de devam etmek istiyor musunuz? (y/N): "
    read -r response
    case "$response" in
        [yY][eE][sS]|[yY])
            echo "Kuruluma devam ediliyor..."
            ;;
        *)
            echo -e "${YELLOW}Kurulum durduruldu. Lütfen .env dosyasını düzenleyin ve tekrar çalıştırın.${NC}"
            exit 0
            ;;
    esac
fi

# Bağımlılıkları yükle
print_step "Bağımlılıklar Yükleniyor"
echo "Bu işlem birkaç dakika sürebilir..."
npm install --production
check_error "NPM bağımlılıkları yüklenemedi"
echo -e "${GREEN}✅ Bağımlılıklar başarıyla yüklendi${NC}"

# Public dizini kontrolü
print_step "Frontend Dosyaları Kontrol Ediliyor"
if [ ! -d "public" ]; then
    mkdir -p public
    echo -e "${YELLOW}⚠️  public dizini oluşturuldu${NC}"
fi

if [ ! -f "public/index.html" ]; then
    echo -e "${YELLOW}⚠️  Frontend dosyaları bulunamadı, basit index.html oluşturuluyor${NC}"
else
    echo -e "${GREEN}✅ Frontend dosyaları mevcut${NC}"
fi

# Veritabanı tablo kontrolü (opsiyonel)
print_step "Veritabanı Kurulum Scripti Hazırlanıyor"
if [ -f "install.sql" ]; then
    echo -e "${GREEN}✅ install.sql dosyası bulundu${NC}"
    echo -e "${YELLOW}💡 Veritabanınızda install.sql dosyasını çalıştırmayı unutmayın${NC}"
else
    echo -e "${YELLOW}⚠️  install.sql dosyası bulunamadı${NC}"
fi

# Dosya izinlerini ayarla
print_step "Dosya İzinleri Ayarlanıyor"
chmod +x start.js 2>/dev/null || chmod +x index.js
chmod 644 *.json *.js *.md 2>/dev/null
echo -e "${GREEN}✅ Dosya izinleri ayarlandı${NC}"

# Port kontrolü
print_step "Port Konfigürasyonu"
PORT=$(grep -o 'PORT=[0-9]*' .env | cut -d'=' -f2)
if [ -z "$PORT" ]; then
    PORT=3000
fi
echo -e "${GREEN}✅ Uygulama portu: $PORT${NC}"

# PM2 kontrolü (opsiyonel)
print_step "Process Manager Kontrol Ediliyor"
if command -v pm2 &> /dev/null; then
    echo -e "${GREEN}✅ PM2 bulundu${NC}"
    echo -e "${BLUE}💡 PM2 ile başlatma: pm2 start index.js --name kiwipazari${NC}"
else
    echo -e "${YELLOW}⚠️  PM2 bulunamadı (opsiyonel)${NC}"
    echo -e "${BLUE}💡 Normal başlatma: node index.js${NC}"
fi

# Kurulum tamamlandı
print_step "Kurulum Tamamlandı!"
echo -e "${GREEN}🎉 KiWiPazari başarıyla kuruldu!${NC}"
echo ""
echo -e "${BLUE}📋 Sonraki Adımlar:${NC}"
echo "1. .env dosyasını düzenleyerek veritabanı bilgilerinizi girin"
echo "2. Veritabanınızda install.sql dosyasını çalıştırın"
echo "3. Uygulamayı başlatın:"
echo -e "${GREEN}   node index.js${NC}"
echo ""
echo -e "${BLUE}🔗 Erişim Bilgileri:${NC}"
echo "   Ana Site: http://yourdomain.com:$PORT"
echo "   Admin Panel: http://yourdomain.com:$PORT/kiwi-management-portal"
echo "   Admin Şifre: ucFMkvJ5Tngq7QCN9Dl31edSWaPAmIRxfGwL62ih4U8jb0VosKHtO"
echo ""
echo -e "${YELLOW}💡 Sorun yaşarsanız README.md dosyasını inceleyin${NC}"
echo "=============================================="