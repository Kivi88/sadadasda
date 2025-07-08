import { useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Settings, Search, Key, ShoppingCart, Star, Users, TrendingUp, Shield } from "lucide-react";
import { useLocation } from "wouter";
import KeyValidator from "@/components/client/key-validator";
import OrderSearch from "@/components/client/order-search";

export default function ClientPanel() {
  const [activeTab, setActiveTab] = useState("key-validator");
  const [, setLocation] = useLocation();

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50">
      <div className="container mx-auto px-4 py-8">
        {/* Header with Admin Button */}
        <div className="flex justify-between items-center mb-8">
          <div className="flex items-center space-x-3">
            <div className="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center">
              <Star className="w-5 h-5 text-white" />
            </div>
            <h1 className="text-2xl font-bold text-foreground">Kiwi Pazarı</h1>
          </div>
          <Button 
            variant="ghost" 
            onClick={() => setLocation("/admin")}
            className="text-muted-foreground hover:text-primary"
          >
            <Settings className="w-4 h-4 mr-2" />
            Admin
          </Button>
        </div>

        {/* Hero Section */}
        <div className="text-center mb-12">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 mb-4">
            <Star className="w-8 h-8 text-white" />
          </div>
          <h2 className="text-4xl font-bold text-foreground mb-4">
            Sosyal Medya Hizmetleri
          </h2>
          <p className="text-lg text-muted-foreground max-w-2xl mx-auto">
            Güvenilir platformunuz. Hızlı, güvenli ve profesyonel çözümler.
          </p>
        </div>

        {/* Feature Cards */}
        <div className="grid md:grid-cols-3 gap-6 mb-12">
          <Card className="border-0 shadow-lg bg-white/70 backdrop-blur-sm">
            <CardContent className="p-6 text-center">
              <div className="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-4">
                <Shield className="w-6 h-6 text-blue-600" />
              </div>
              <h3 className="font-semibold text-lg mb-2">Güvenli Sistem</h3>
              <p className="text-sm text-muted-foreground">
                SSL şifrelemesi ile korunan güvenli sipariş sistemi
              </p>
            </CardContent>
          </Card>

          <Card className="border-0 shadow-lg bg-white/70 backdrop-blur-sm">
            <CardContent className="p-6 text-center">
              <div className="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
                <TrendingUp className="w-6 h-6 text-green-600" />
              </div>
              <h3 className="font-semibold text-lg mb-2">Hızlı Teslimat</h3>
              <p className="text-sm text-muted-foreground">
                Siparişleriniz anında işleme alınır ve hızla teslim edilir
              </p>
            </CardContent>
          </Card>

          <Card className="border-0 shadow-lg bg-white/70 backdrop-blur-sm">
            <CardContent className="p-6 text-center">
              <div className="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center mx-auto mb-4">
                <Users className="w-6 h-6 text-purple-600" />
              </div>
              <h3 className="font-semibold text-lg mb-2">24/7 Destek</h3>
              <p className="text-sm text-muted-foreground">
                Profesyonel destek ekibimiz her zaman yanınızda
              </p>
            </CardContent>
          </Card>
        </div>

        {/* Navigation Tabs */}
        <div className="flex justify-center mb-8">
          <div className="flex space-x-1 bg-white/60 backdrop-blur-sm rounded-lg p-1 shadow-lg">
            <Button
              variant={activeTab === "key-validator" ? "default" : "ghost"}
              onClick={() => setActiveTab("key-validator")}
              className="flex items-center space-x-2"
            >
              <Key className="w-4 h-4" />
              <span>Key Doğrulama</span>
            </Button>
            <Button
              variant={activeTab === "order-search" ? "default" : "ghost"}
              onClick={() => setActiveTab("order-search")}
              className="flex items-center space-x-2"
            >
              <Search className="w-4 h-4" />
              <span>Sipariş Sorgula</span>
            </Button>
          </div>
        </div>

        {/* Tab Content */}
        <div className="max-w-md mx-auto">
          {activeTab === "key-validator" && <KeyValidator />}
          {activeTab === "order-search" && <OrderSearch />}
        </div>
      </div>
    </div>
  );
}
