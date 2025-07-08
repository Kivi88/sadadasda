import { Button } from "@/components/ui/button";
import { Settings, Globe, Search, Star, Shield, Clock } from "lucide-react";
import { useLocation } from "wouter";
import KeyValidator from "@/components/client/key-validator";

export default function ClientPanel() {
  const [, setLocation] = useLocation();

  return (
    <div className="min-h-screen bg-background">
      {/* Modern Header */}
      <header className="modern-header border-b border-border/50 backdrop-blur-xl sticky top-0 z-50">
        <div className="container mx-auto px-6 py-6">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-4">
              <div className="w-12 h-12 bg-gradient-to-br from-primary to-accent rounded-2xl flex items-center justify-center shadow-lg">
                <Globe className="h-7 w-7 text-white" />
              </div>
              <div>
                <h1 className="text-3xl font-bold bg-gradient-to-r from-primary to-accent bg-clip-text text-transparent">
                  KiwiPazarı
                </h1>
                <p className="text-muted-foreground text-sm">
                  Sosyal Medya Hizmetleri
                </p>
              </div>
            </div>
            
            <div className="flex items-center space-x-4">
              <Button
                variant="outline"
                onClick={() => setLocation("/order-search")}
                className="flex items-center space-x-2 h-11 px-6 border-primary/30 hover:bg-primary/10"
              >
                <Search className="h-5 w-5" />
                <span>Sipariş Sorgula</span>
              </Button>
              
              <Button 
                variant="ghost" 
                onClick={() => setLocation("/admin")}
                className="h-11 px-4 text-muted-foreground hover:text-primary hover:bg-muted/50"
              >
                <Settings className="w-5 h-5 mr-2" />
                Admin
              </Button>
            </div>
          </div>
        </div>
      </header>

      {/* Hero Section */}
      <section className="py-20 px-6">
        <div className="container mx-auto text-center">
          <div className="max-w-4xl mx-auto">
            <h2 className="text-6xl font-bold bg-gradient-to-r from-primary via-accent to-primary bg-clip-text text-transparent mb-6 leading-tight">
              Sosyal Medya<br />Büyütme Platformu
            </h2>
            <p className="text-xl text-muted-foreground mb-16 leading-relaxed max-w-2xl mx-auto">
              Instagram, TikTok, YouTube ve diğer sosyal medya platformlarında 
              <span className="text-primary font-semibold"> organik büyüme </span>
              için güvenilir ve hızlı hizmetler
            </p>
            
            {/* Features */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
              <div className="modern-card p-8 text-center group">
                <div className="w-16 h-16 bg-gradient-to-br from-primary/20 to-accent/20 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:from-primary/30 group-hover:to-accent/30 transition-all duration-300">
                  <Star className="h-8 w-8 text-primary" />
                </div>
                <h3 className="text-xl font-semibold mb-3">Kaliteli Hizmet</h3>
                <p className="text-muted-foreground">
                  Premium kalitede, gerçek ve aktif hesaplardan etkileşim
                </p>
              </div>
              
              <div className="modern-card p-8 text-center group">
                <div className="w-16 h-16 bg-gradient-to-br from-primary/20 to-accent/20 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:from-primary/30 group-hover:to-accent/30 transition-all duration-300">
                  <Clock className="h-8 w-8 text-primary" />
                </div>
                <h3 className="text-xl font-semibold mb-3">Hızlı Teslimat</h3>
                <p className="text-muted-foreground">
                  Siparişleriniz dakikalar içinde başlar, hızla tamamlanır
                </p>
              </div>
              
              <div className="modern-card p-8 text-center group">
                <div className="w-16 h-16 bg-gradient-to-br from-primary/20 to-accent/20 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:from-primary/30 group-hover:to-accent/30 transition-all duration-300">
                  <Shield className="h-8 w-8 text-primary" />
                </div>
                <h3 className="text-xl font-semibold mb-3">Güvenli Platform</h3>
                <p className="text-muted-foreground">
                  7/24 destek ve %100 güvenli ödeme sistemi
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Key Validator Section */}
      <section className="py-16 px-6">
        <div className="container mx-auto">
          <div className="max-w-2xl mx-auto">
            <div className="text-center mb-12">
              <h3 className="text-3xl font-bold bg-gradient-to-r from-primary to-accent bg-clip-text text-transparent mb-4">
                Hizmetlere Başlayın
              </h3>
              <p className="text-muted-foreground text-lg">
                API anahtarınızı girerek mevcut servisleri görüntüleyin ve sipariş verin
              </p>
            </div>
            
            <KeyValidator />
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="border-t border-border/50 py-16 px-6 mt-20">
        <div className="container mx-auto">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div className="md:col-span-2">
              <div className="flex items-center space-x-3 mb-6">
                <div className="w-10 h-10 bg-gradient-to-br from-primary to-accent rounded-xl flex items-center justify-center">
                  <Globe className="h-6 w-6 text-white" />
                </div>
                <span className="font-bold text-2xl">KiwiPazarı</span>
              </div>
              <p className="text-muted-foreground text-lg leading-relaxed max-w-md">
                Sosyal medya büyütme hizmetleri sunan güvenilir ve modern platform. 
                Hesaplarınızı organik yöntemlerle büyütün.
              </p>
            </div>
            
            <div>
              <h3 className="font-semibold text-lg mb-6">Hızlı Bağlantılar</h3>
              <div className="space-y-3">
                <Button
                  variant="ghost"
                  onClick={() => setLocation("/order-search")}
                  className="block text-left justify-start p-0 h-auto text-muted-foreground hover:text-primary transition-colors"
                >
                  Sipariş Sorgula
                </Button>
                <Button
                  variant="ghost"
                  onClick={() => setLocation("/")}
                  className="block text-left justify-start p-0 h-auto text-muted-foreground hover:text-primary transition-colors"
                >
                  Ana Sayfa
                </Button>
              </div>
            </div>
            
            <div>
              <h3 className="font-semibold text-lg mb-6">Destek</h3>
              <p className="text-muted-foreground leading-relaxed">
                7/24 müşteri desteği için bizimle iletişime geçin. 
                Sorularınızı yanıtlamaktan memnuniyet duyarız.
              </p>
            </div>
          </div>
          
          <div className="border-t border-border/50 mt-12 pt-8">
            <div className="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
              <p className="text-muted-foreground">
                © 2025 KiwiPazarı. Tüm hakları saklıdır.
              </p>
              <div className="flex items-center space-x-6">
                <div className="px-4 py-2 bg-gradient-to-r from-green-500/20 to-emerald-600/20 rounded-full border border-green-500/30">
                  <div className="flex items-center space-x-2">
                    <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                    <span className="text-sm font-medium text-green-400">Sistem Online</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </footer>
    </div>
  );
}
