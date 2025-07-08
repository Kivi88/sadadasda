import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";
import { Plus, TestTube, Edit, Download, Trash2 } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { apiRequest } from "@/lib/api";
import type { Api } from "@shared/schema";

export default function ApiManagement() {
  const [name, setName] = useState("");
  const [url, setUrl] = useState("");
  const [apiKey, setApiKey] = useState("");
  const [isActive, setIsActive] = useState(true);
  
  const { toast } = useToast();
  const queryClient = useQueryClient();

  const { data: apis, isLoading: apisLoading } = useQuery({
    queryKey: ["/api/apis"],
  });

  const createApiMutation = useMutation({
    mutationFn: async (data: { name: string; url: string; apiKey: string; isActive: boolean }) => {
      return await apiRequest("POST", "/api/apis", data);
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
      return await apiRequest("DELETE", `/api/apis/${id}`);
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
    mutationFn: async (id: number) => {
      return await apiRequest("POST", `/api/apis/${id}/fetch-services`);
    },
    onSuccess: (data) => {
      toast({
        title: "Başarılı",
        description: `${data.addedCount} servis çekildi`,
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
    // TODO: Implement actual API testing
  };

  return (
    <div className="p-6 fade-in">
      <div className="mb-6">
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-lg font-semibold text-foreground">API Yönetimi</h3>
        </div>
        
        <Card className="mb-6">
          <CardHeader>
            <CardTitle>API Ekle</CardTitle>
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
                  placeholder="https://api.provider.com/v1"
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
                className="btn-success"
              >
                <Plus className="w-4 h-4 mr-2" />
                {createApiMutation.isPending ? "Ekleniyor..." : "API Ekle"}
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
      
      <Card>
        <CardHeader>
          <CardTitle>Mevcut API'ler</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
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
                <Card key={api.id} className="bg-muted/50 border-border">
                  <CardContent className="p-4">
                    <div className="flex items-center justify-between mb-3">
                      <h5 className="font-medium text-foreground">{api.name}</h5>
                      <Badge className={api.isActive ? "status-active" : "status-inactive"}>
                        {api.isActive ? "Aktif" : "Pasif"}
                      </Badge>
                    </div>
                    <div className="space-y-2">
                      <div className="flex items-center justify-between text-sm">
                        <span className="text-muted-foreground">Servis Sayısı:</span>
                        <span className="text-foreground">{api.serviceCount || 0}</span>
                      </div>
                      <div className="flex items-center justify-between text-sm">
                        <span className="text-muted-foreground">Son Güncelleme:</span>
                        <span className="text-foreground">
                          {api.lastSync ? new Date(api.lastSync).toLocaleString("tr-TR") : "Hiç"}
                        </span>
                      </div>
                      <div className="flex items-center justify-between text-sm">
                        <span className="text-muted-foreground">Yanıt Süresi:</span>
                        <span className="text-foreground">
                          {api.responseTime ? `${api.responseTime}ms` : "Bilinmiyor"}
                        </span>
                      </div>
                    </div>
                    <div className="mt-3 flex items-center space-x-2">
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => testApi(api)}
                        className="btn-primary"
                      >
                        <TestTube className="w-4 h-4 mr-1" />
                        Test Et
                      </Button>
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => fetchServicesMutation.mutate(api.id)}
                        disabled={fetchServicesMutation.isPending}
                        className="bg-blue-500 hover:bg-blue-600 text-white"
                      >
                        <Download className="w-4 h-4 mr-1" />
                        Servis Çek
                      </Button>
                      <Button
                        variant="outline"
                        size="sm"
                        className="bg-muted hover:bg-muted/80"
                      >
                        <Edit className="w-4 h-4 mr-1" />
                        Düzenle
                      </Button>
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => deleteApiMutation.mutate(api.id)}
                        disabled={deleteApiMutation.isPending}
                        className="bg-red-500 hover:bg-red-600 text-white"
                      >
                        <Trash2 className="w-4 h-4 mr-1" />
                        Sil
                      </Button>
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
