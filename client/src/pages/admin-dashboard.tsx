import { useState, useEffect } from "react";
import { useQuery } from "@tanstack/react-query";
import { useLocation } from "wouter";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Separator } from "@/components/ui/separator";
import { 
  Key, 
  Plug, 
  Settings, 
  ShoppingCart, 
  Users, 
  TrendingUp,
  Activity,
  CheckCircle,
  Clock,
  AlertCircle,
  LogOut
} from "lucide-react";
import StatsCard from "@/components/ui/stats-card";
import KeyManagement from "@/components/admin/key-management";
import ApiManagement from "@/components/admin/api-management";
import ServiceManagement from "@/components/admin/service-management";
import OrderManagement from "@/components/admin/order-management";
import { useToast } from "@/hooks/use-toast";

export default function AdminDashboard() {
  const [activeTab, setActiveTab] = useState("dashboard");
  const [, setLocation] = useLocation();
  const { toast } = useToast();

  // Admin authentication check
  useEffect(() => {
    const isAuthenticated = localStorage.getItem("admin_authenticated");
    if (!isAuthenticated) {
      setLocation("/admin");
    }
  }, [setLocation]);

  const handleLogout = () => {
    localStorage.removeItem("admin_authenticated");
    toast({
      title: "Çıkış yapıldı",
      description: "Admin panelinden çıkış yaptınız",
    });
    setLocation("/admin");
  };

  const { data: stats, isLoading: statsLoading } = useQuery({
    queryKey: ["/api/stats"],
  });

  const { data: orders, isLoading: ordersLoading } = useQuery({
    queryKey: ["/api/orders"],
  });

  const recentOrders = orders?.slice(0, 5) || [];

  const systemStatus = [
    { name: "API Bağlantısı", status: "active" },
    { name: "Veritabanı", status: "active" },
    { name: "Sipariş İşleme", status: "active" },
    { name: "Sunucu Yükü", status: "warning" },
  ];

  return (
    <div className="flex h-screen bg-background">
      {/* Sidebar */}
      <div className="w-64 bg-card border-r border-border flex-shrink-0">
        <div className="flex items-center justify-center h-16 border-b border-border">
          <h1 className="text-xl font-bold text-foreground">SMM Panel</h1>
        </div>
        
        <nav className="mt-6">
          <div className="px-4 space-y-2">
            <button
              onClick={() => setActiveTab("dashboard")}
              className={`sidebar-item w-full ${activeTab === "dashboard" ? "active" : ""}`}
            >
              <TrendingUp className="w-4 h-4 mr-3" />
              Dashboard
            </button>
            
            <button
              onClick={() => setActiveTab("keys")}
              className={`sidebar-item w-full ${activeTab === "keys" ? "active" : ""}`}
            >
              <Key className="w-4 h-4 mr-3" />
              Key Yönetimi
            </button>
            
            <button
              onClick={() => setActiveTab("apis")}
              className={`sidebar-item w-full ${activeTab === "apis" ? "active" : ""}`}
            >
              <Plug className="w-4 h-4 mr-3" />
              API Yönetimi
            </button>
            
            <button
              onClick={() => setActiveTab("services")}
              className={`sidebar-item w-full ${activeTab === "services" ? "active" : ""}`}
            >
              <Settings className="w-4 h-4 mr-3" />
              Servis Yönetimi
            </button>
            
            <button
              onClick={() => setActiveTab("orders")}
              className={`sidebar-item w-full ${activeTab === "orders" ? "active" : ""}`}
            >
              <ShoppingCart className="w-4 h-4 mr-3" />
              Siparişler
            </button>
            
            <button
              onClick={() => window.open("/", "_blank")}
              className="sidebar-item w-full"
            >
              <Users className="w-4 h-4 mr-3" />
              Müşteri Paneli
            </button>
          </div>
        </nav>
      </div>

      {/* Main Content */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Header */}
        <header className="bg-card border-b border-border px-6 py-4">
          <div className="flex items-center justify-between">
            <h2 className="text-xl font-semibold text-foreground">
              {activeTab === "dashboard" && "Dashboard"}
              {activeTab === "keys" && "Key Yönetimi"}
              {activeTab === "apis" && "API Yönetimi"}
              {activeTab === "services" && "Servis Yönetimi"}
              {activeTab === "orders" && "Siparişler"}
            </h2>
            <div className="flex items-center space-x-4">
              <Button className="btn-primary">
                <span className="text-sm">Yeni Ekle</span>
              </Button>
              <div className="flex items-center space-x-2">
                <div className="flex items-center space-x-2">
                  <div className="w-8 h-8 bg-muted rounded-full flex items-center justify-center">
                    <Users className="w-4 h-4" />
                  </div>
                  <span className="text-sm font-medium">Admin</span>
                </div>
                <Button
                  onClick={handleLogout}
                  variant="ghost"
                  size="sm"
                  className="text-muted-foreground hover:text-destructive"
                >
                  <LogOut className="w-4 h-4" />
                </Button>
              </div>
            </div>
          </div>
        </header>

        {/* Content Area */}
        <main className="flex-1 overflow-y-auto">
          {activeTab === "dashboard" && (
            <div className="p-6 fade-in">
              {/* Stats Cards */}
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <StatsCard
                  title="Toplam Key"
                  value={statsLoading ? "..." : stats?.totalKeys || "0"}
                  icon={<Key className="w-6 h-6 text-primary" />}
                  bgColor="bg-primary/20"
                />
                <StatsCard
                  title="Aktif API"
                  value={statsLoading ? "..." : stats?.activeApis || "0"}
                  icon={<Plug className="w-6 h-6 text-green-500" />}
                  bgColor="bg-green-500/20"
                />
                <StatsCard
                  title="Toplam Sipariş"
                  value={statsLoading ? "..." : stats?.totalOrders || "0"}
                  icon={<ShoppingCart className="w-6 h-6 text-orange-500" />}
                  bgColor="bg-orange-500/20"
                />
                <StatsCard
                  title="Başarı Oranı"
                  value={statsLoading ? "..." : stats?.successRate || "0%"}
                  icon={<TrendingUp className="w-6 h-6 text-cyan-500" />}
                  bgColor="bg-cyan-500/20"
                />
              </div>

              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Recent Activities */}
                <Card>
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                      <Activity className="w-5 h-5" />
                      Son Aktiviteler
                    </CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-4">
                      {recentOrders.map((order) => (
                        <div key={order.id} className="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
                          <div className="flex items-center space-x-3">
                            <div className="w-8 h-8 bg-primary/20 rounded-full flex items-center justify-center">
                              <ShoppingCart className="w-4 h-4 text-primary" />
                            </div>
                            <div>
                              <p className="text-sm font-medium text-foreground">
                                Sipariş {order.orderId} oluşturuldu
                              </p>
                              <p className="text-xs text-muted-foreground">
                                {new Date(order.createdAt).toLocaleString("tr-TR")}
                              </p>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  </CardContent>
                </Card>

                {/* System Status */}
                <Card>
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                      <Activity className="w-5 h-5" />
                      Sistem Durumu
                    </CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-4">
                      {systemStatus.map((item) => (
                        <div key={item.name} className="flex items-center justify-between">
                          <span className="text-sm font-medium text-muted-foreground">
                            {item.name}
                          </span>
                          <Badge className={
                            item.status === "active" ? "status-active" : 
                            item.status === "warning" ? "status-inactive" : 
                            "status-pending"
                          }>
                            {item.status === "active" ? "Aktif" : 
                             item.status === "warning" ? "Orta" : "Beklemede"}
                          </Badge>
                        </div>
                      ))}
                    </div>
                  </CardContent>
                </Card>
              </div>
            </div>
          )}

          {activeTab === "keys" && <KeyManagement />}
          {activeTab === "apis" && <ApiManagement />}
          {activeTab === "services" && <ServiceManagement />}
          {activeTab === "orders" && <OrderManagement />}
        </main>
      </div>
    </div>
  );
}
