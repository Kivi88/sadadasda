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
      setLocation("/kiwi-management-portal");
    }
  }, [setLocation]);

  const handleLogout = () => {
    localStorage.removeItem("admin_authenticated");
    toast({
      title: "Çıkış yapıldı",
      description: "Admin panelinden çıkış yaptınız",
    });
    setLocation("/kiwi-management-portal");
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
      <div className="w-64 sidebar-dark flex-shrink-0">
        <div className="flex items-center justify-center h-16 border-b border-slate-700">
          <div className="flex items-center space-x-2">
            <div className="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
              <Settings className="w-4 h-4 text-white" />
            </div>
            <h1 className="text-xl font-bold text-white">KiwiPazari</h1>
          </div>
        </div>
        
        <nav className="mt-6">
          <div className="px-4 space-y-1">
            <button
              onClick={() => setActiveTab("dashboard")}
              className={`flex items-center w-full px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 ${
                activeTab === "dashboard" 
                  ? "bg-blue-600 text-white shadow-lg" 
                  : "text-slate-300 hover:text-white hover:bg-slate-700"
              }`}
            >
              <TrendingUp className="w-4 h-4 mr-3" />
              Dashboard
            </button>
            
            <button
              onClick={() => setActiveTab("keys")}
              className={`flex items-center w-full px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 ${
                activeTab === "keys" 
                  ? "bg-blue-600 text-white shadow-lg" 
                  : "text-slate-300 hover:text-white hover:bg-slate-700"
              }`}
            >
              <Key className="w-4 h-4 mr-3" />
              Key Yönetimi
            </button>
            
            <button
              onClick={() => setActiveTab("apis")}
              className={`flex items-center w-full px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 ${
                activeTab === "apis" 
                  ? "bg-blue-600 text-white shadow-lg" 
                  : "text-slate-300 hover:text-white hover:bg-slate-700"
              }`}
            >
              <Plug className="w-4 h-4 mr-3" />
              API Yönetimi
            </button>
            
            <button
              onClick={() => setActiveTab("services")}
              className={`flex items-center w-full px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 ${
                activeTab === "services" 
                  ? "bg-blue-600 text-white shadow-lg" 
                  : "text-slate-300 hover:text-white hover:bg-slate-700"
              }`}
            >
              <Settings className="w-4 h-4 mr-3" />
              Servis Yönetimi
            </button>
            
            <button
              onClick={() => setActiveTab("orders")}
              className={`flex items-center w-full px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 ${
                activeTab === "orders" 
                  ? "bg-blue-600 text-white shadow-lg" 
                  : "text-slate-300 hover:text-white hover:bg-slate-700"
              }`}
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
        <header className="admin-header">
          <div className="flex items-center justify-between px-6 py-4">
            <h2 className="text-xl font-semibold text-white">
              {activeTab === "dashboard" && "Dashboard"}
              {activeTab === "keys" && "Key Yönetimi"}
              {activeTab === "apis" && "API Yönetimi"}
              {activeTab === "services" && "Servis Yönetimi"}
              {activeTab === "orders" && "Siparişler"}
            </h2>
            <div className="flex items-center space-x-4">
              <Badge className="status-active">
                Sistem Aktif
              </Badge>
              <div className="text-sm text-slate-300">
                {new Date().toLocaleString('tr-TR')}
              </div>
              <div className="flex items-center space-x-2">
                <div className="flex items-center space-x-2">
                  <div className="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                    <Users className="w-4 h-4 text-white" />
                  </div>
                  <span className="text-sm font-medium text-white">Admin</span>
                </div>
                <Button
                  onClick={handleLogout}
                  variant="ghost"
                  size="sm"
                  className="text-white hover:bg-slate-700"
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
                <Card className="metric-card">
                  <CardContent className="p-6">
                    <div className="flex items-center justify-between">
                      <div>
                        <p className="text-sm font-medium text-slate-400">Toplam Key</p>
                        <p className="text-2xl font-bold text-white">{statsLoading ? "..." : stats?.totalKeys || "0"}</p>
                      </div>
                      <div className="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center">
                        <Key className="w-6 h-6 text-white" />
                      </div>
                    </div>
                  </CardContent>
                </Card>
                
                <Card className="metric-card">
                  <CardContent className="p-6">
                    <div className="flex items-center justify-between">
                      <div>
                        <p className="text-sm font-medium text-slate-400">Aktif API</p>
                        <p className="text-2xl font-bold text-white">{statsLoading ? "..." : stats?.activeApis || "0"}</p>
                      </div>
                      <div className="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center">
                        <Plug className="w-6 h-6 text-white" />
                      </div>
                    </div>
                  </CardContent>
                </Card>
                
                <Card className="metric-card">
                  <CardContent className="p-6">
                    <div className="flex items-center justify-between">
                      <div>
                        <p className="text-sm font-medium text-slate-400">Toplam Sipariş</p>
                        <p className="text-2xl font-bold text-white">{statsLoading ? "..." : stats?.totalOrders || "0"}</p>
                      </div>
                      <div className="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center">
                        <ShoppingCart className="w-6 h-6 text-white" />
                      </div>
                    </div>
                  </CardContent>
                </Card>
                
                <Card className="metric-card">
                  <CardContent className="p-6">
                    <div className="flex items-center justify-between">
                      <div>
                        <p className="text-sm font-medium text-slate-400">Başarı Oranı</p>
                        <p className="text-2xl font-bold text-white">{statsLoading ? "..." : stats?.successRate || "0%"}</p>
                      </div>
                      <div className="w-12 h-12 bg-cyan-600 rounded-lg flex items-center justify-center">
                        <TrendingUp className="w-6 h-6 text-white" />
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </div>

              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Recent Activities */}
                <Card className="cpanel-card">
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-white">
                      <Activity className="w-5 h-5 text-blue-400" />
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
                <Card className="cpanel-card">
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-white">
                      <Activity className="w-5 h-5 text-green-400" />
                      Sistem Durumu
                    </CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-3">
                      {systemStatus.map((item) => (
                        <div key={item.name} className="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg border border-slate-700">
                          <span className="text-sm font-medium text-white">
                            {item.name}
                          </span>
                          <Badge className={
                            item.status === "active" ? "status-active" : 
                            item.status === "warning" ? "bg-orange-500/20 text-orange-400 border-orange-500/30" : 
                            "status-inactive"
                          }>
                            {item.status === "active" ? "Aktif" : 
                             item.status === "warning" ? "Uyarı" : "Pasif"}
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
