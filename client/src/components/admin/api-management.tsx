import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Plus, TestTube, Edit, Download, Trash2, Settings } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { apiRequest } from "@/lib/api";
import type { Api } from "@shared/schema";

export default function ApiManagement() {
  const [name, setName] = useState("");
  const [url, setUrl] = useState("");
  const [apiKey, setApiKey] = useState("");
  const [isActive, setIsActive] = useState(true);
  const [fetchLimit, setFetchLimit] = useState<number>(0); // 0 = tüm servisler
  
  const { toast } = useToast();
  const queryClient = useQueryClient();

  const { data: apis, isLoading: apisLoading } = useQuery({
    queryKey: ["/api/apis"],
  });

  const createApiMutation = useMutation({
    mutationFn: async (data: { name: string; url: string; apiKey: string; isActive: boolean }) => {
      const response = await apiRequest("POST", "/api/apis", data);
      return await response.json();
    },
    onSuccess: () => {
      toast({
        title: "Başarılı",
        description: "API başarıyla eklendi",
      });
      queryClient.invalidateQueries({ queryKey: ["/api/apis"] });
      queryClient.invalidateQueries({ queryKey: ["/api/services"] });
      setName("");
      setUrl("");
      setApiKey("");
      setIsActive(true);
    },
    onError: (error) => {
      toast({
        title: "Hata",
        description: error.message || "API eklenemedi",
        variant: "destructive",
      });
    },
  });

  const deleteApiMutation = useMutation({
    mutationFn: async (id: number) => {
      const response = await apiRequest("DELETE", `/api/apis/${id}`);
      return await response.json();
    },
    onSuccess: () => {
      toast({
        title: "Başarılı",
        description: "API başarıyla silindi",
      });
      queryClient.invalidateQueries({ queryKey: ["/api/apis"] });
      queryClient.invalidateQueries({ queryKey: ["/api/services"] });
    },
    onError: (error) => {
      toast({
        title: "Hata",
        description: error.message || "API silinemedi",
        variant: "destructive",
      });
    },
  });

  const fetchServicesMutation = useMutation({
    mutationFn: async ({ id, limit }: { id: number; limit?: number }) => {
      const response = await apiRequest("POST", `/api/apis/${id}/fetch-services`, { limit });
      return await response.json();
    },
    onSuccess: (data) => {
      const count = data?.addedCount ?? 0;
      toast({
        title: "Başarılı",
        description: count > 0 ? `${count} yeni servis eklendi` : "Tüm servisler zaten mevcut",
      });
      queryClient.invalidateQueries({ queryKey: ["/api/services"] });
    },
    onError: (error) => {
      toast({
        title: "Hata",
        description: error.message || "Servisler çekilemedi",
        variant: "destructive",
      });
    },
  });

  const handleCreateApi = async () => {
    if (!name || !url || !apiKey) {
      toast({
        title: "Hata",
        description: "Lütfen tüm alanları doldurun",
        variant: "destructive",
      });
      return;
    }

    createApiMutation.mutate({
      name,
      url,
      apiKey,
      isActive,
    });
  };

  const testApi = (api: Api) => {
    toast({
      title: "Test Başlatıldı",
      description: `${api.name} API'si test ediliyor...`,
    });
    
    // API URL'sini v1 olacak şekilde düzenle
    const baseUrl = api.url.replace('/v2', '/v1');
    const testUrl = `${baseUrl}/services`;
    const testData = {
      key: api.apiKey,
      action: "services"
    };

    fetch(testUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "User-Agent": "KiwiPazari/1.0"
      },
      body: JSON.stringify(testData)
    })
    .then(response => {
      if (!response.ok) {
        throw new Error(`${response.status} ${response.statusText}`);
      }
      return response.json();
    })
    .then(data => {
      toast({
        title: "Başarılı",
        description: `API bağlantısı başarılı. ${Array.isArray(data) ? data.length : 0} servis bulundu.`,
      });
    })
    .catch(error => {
      toast({
        title: "Hata",
        description: `API bağlantısı başarısız: ${error.message}`,
        variant: "destructive",
      });
    });
  };

  return (
    <div className="p-6 fade-in max-w-7xl mx-auto">
      <div className="mb-6">
        <div className="flex items-center justify-between mb-4">
          <div>
            <h3 className="text-2xl font-bold text-foreground">API Yönetimi</h3>
            <p className="text-muted-foreground mt-1">Harici API sağlayıcılarını yönetin ve servislerini senkronize edin</p>
          </div>
        </div>
        
        <Card className="mb-6 cpanel-card border-2 border-dashed border-slate-600 hover:border-blue-400 transition-colors">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-white">
              <Plus className="w-5 h-5 text-blue-400" />
              Yeni API Ekle
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label htmlFor="apiName">API Adı</Label>
                <Input
                  id="apiName"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  placeholder="MedyaBayim, Resellers API vb..."
                />
              </div>
              
              <div>
                <Label htmlFor="apiUrl">API URL</Label>
                <Input
                  id="apiUrl"
                  type="url"
                  value={url}
                  onChange={(e) => setUrl(e.target.value)}
                  placeholder="https://api.provider.com/v1 (önerilir)"
                />
              </div>
              
              <div>
                <Label htmlFor="apiKey">API Key</Label>
                <Input
                  id="apiKey"
                  type="password"
                  value={apiKey}
                  onChange={(e) => setApiKey(e.target.value)}
                  placeholder="API anahtarınız..."
                />
              </div>
              
              <div>
                <Label htmlFor="apiStatus">Durum</Label>
                <Select value={isActive ? "true" : "false"} onValueChange={(value) => setIsActive(value === "true")}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="true">Aktif</SelectItem>
                    <SelectItem value="false">Pasif</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
            
            <div className="mt-4">
              <Button 
                onClick={handleCreateApi}
                disabled={createApiMutation.isPending}
                className="cpanel-button bg-green-600 hover:bg-green-700 text-white px-6 py-2"
                size="lg"
              >
                <Plus className="w-4 h-4 mr-2" />
                {createApiMutation.isPending ? "Ekleniyor..." : "API Ekle"}
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
      
      <Card className="cpanel-card">
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-white">
            <div className="w-2 h-6 bg-blue-400 rounded-full"></div>
            Mevcut API'ler
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {apisLoading ? (
              <div className="col-span-full text-center py-8">
                <div className="pulse-loader">Yükleniyor...</div>
              </div>
            ) : apis?.length === 0 ? (
              <div className="col-span-full text-center py-8 text-muted-foreground">
                Henüz API eklenmemiş
              </div>
            ) : (
              apis?.map((api: Api) => (
                <Card key={api.id} className="cpanel-card border-slate-600 hover:border-slate-500 transition-all duration-200 hover:shadow-xl">
                  <CardContent className="p-6">
                    <div className="flex items-center justify-between mb-4">
                      <h5 className="font-semibold text-white text-lg">{api.name}</h5>
                      <Badge className={api.isActive ? "status-active" : "status-inactive"}>
                        {api.isActive ? "Aktif" : "Pasif"}
                      </Badge>
                    </div>
                    <div className="space-y-3 mb-4">
                      <div className="flex items-center justify-between text-sm bg-slate-800/50 p-3 rounded-lg border border-slate-700">
                        <span className="text-slate-300 font-medium">Servis Sayısı:</span>
                        <span className="text-white font-semibold">{api.serviceCount || 0}</span>
                      </div>
                      <div className="flex items-center justify-between text-sm bg-slate-800/50 p-3 rounded-lg border border-slate-700">
                        <span className="text-slate-300 font-medium">Son Güncelleme:</span>
                        <span className="text-white font-semibold">
                          {api.lastSync ? new Date(api.lastSync).toLocaleString("tr-TR") : "Hiç"}
                        </span>
                      </div>
                      <div className="flex items-center justify-between text-sm bg-slate-800/50 p-3 rounded-lg border border-slate-700">
                        <span className="text-slate-300 font-medium">Yanıt Süresi:</span>
                        <span className="text-white font-semibold">
                          {api.responseTime ? `${api.responseTime}ms` : "Bilinmiyor"}
                        </span>
                      </div>
                    </div>
                    <div className="flex flex-col gap-2">
                      <div className="flex gap-2">
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => testApi(api)}
                          className="cpanel-button flex-1 bg-blue-600 hover:bg-blue-700 text-white border-blue-600"
                        >
                          <TestTube className="w-4 h-4 mr-1" />
                          Test
                        </Button>
                        <Dialog>
                          <DialogTrigger asChild>
                            <Button
                              variant="outline"
                              size="sm"
                              className="cpanel-button flex-1 bg-green-600 hover:bg-green-700 text-white border-green-600"
                            >
                              <Download className="w-4 h-4 mr-1" />
                              Servis Çek
                            </Button>
                          </DialogTrigger>
                          <DialogContent className="bg-slate-900 border-slate-700">
                            <DialogHeader>
                              <DialogTitle className="text-white">
                                {api.name} - Servis Çekme Ayarları
                              </DialogTitle>
                            </DialogHeader>
                            <div className="space-y-4">
                              <div>
                                <Label htmlFor="fetchLimit" className="text-white">
                                  Çekilecek Servis Sayısı (0 = Tümü)
                                </Label>
                                <Input
                                  id="fetchLimit"
                                  type="number"
                                  min="0"
                                  max="10000"
                                  value={fetchLimit}
                                  onChange={(e) => setFetchLimit(parseInt(e.target.value) || 0)}
                                  placeholder="0 (tüm servisler için)"
                                  className="bg-slate-800 border-slate-600 text-white"
                                />
                                <p className="text-sm text-slate-400 mt-1">
                                  Test için küçük sayılar (10-100) önerilir
                                </p>
                              </div>
                              <div className="flex gap-2">
                                <Button
                                  onClick={() => {
                                    fetchServicesMutation.mutate({ 
                                      id: api.id, 
                                      limit: fetchLimit || undefined 
                                    });
                                  }}
                                  disabled={fetchServicesMutation.isPending}
                                  className="bg-green-600 hover:bg-green-700 text-white flex-1"
                                >
                                  <Download className="w-4 h-4 mr-1" />
                                  {fetchServicesMutation.isPending ? "Çekiliyor..." : "Başlat"}
                                </Button>
                                <Button
                                  variant="outline"
                                  onClick={() => setFetchLimit(100)}
                                  className="border-slate-600 text-slate-300 hover:bg-slate-800"
                                >
                                  Test (100)
                                </Button>
                              </div>
                            </div>
                          </DialogContent>
                        </Dialog>
                      </div>
                      <div className="flex gap-2">
                        <Button
                          variant="outline"
                          size="sm"
                          className="cpanel-button flex-1 bg-yellow-600 hover:bg-yellow-700 text-white border-yellow-600"
                        >
                          <Edit className="w-4 h-4 mr-1" />
                          Düzenle
                        </Button>
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => deleteApiMutation.mutate(api.id)}
                          disabled={deleteApiMutation.isPending}
                          className="cpanel-button flex-1 bg-red-600 hover:bg-red-700 text-white border-red-600"
                        >
                          <Trash2 className="w-4 h-4 mr-1" />
                          {deleteApiMutation.isPending ? "Siliniyor..." : "Sil"}
                        </Button>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              ))
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
