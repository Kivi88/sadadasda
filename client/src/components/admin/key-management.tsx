import { useState, useMemo, useEffect, useRef } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Plus, Copy, Eye, EyeOff, Trash2, Download } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { apiRequest } from "@/lib/api";
import type { Key, Service } from "@shared/schema";

export default function KeyManagement() {
  const [selectedService, setSelectedService] = useState("");
  const [serviceSearch, setServiceSearch] = useState("");
  const [keyName, setKeyName] = useState("");
  const [keyCount, setKeyCount] = useState(1);
  const [maxAmount, setMaxAmount] = useState(1000);
  const [hiddenKeys, setHiddenKeys] = useState<Set<number>>(new Set());
  const [showServiceDropdown, setShowServiceDropdown] = useState(false);
  const [downloadServiceName, setDownloadServiceName] = useState("");
  const dropdownRef = useRef<HTMLDivElement>(null);
  
  const { toast } = useToast();
  const queryClient = useQueryClient();

  const { data: keys, isLoading: keysLoading } = useQuery({
    queryKey: ["/api/keys"],
  });

  const { data: services, isLoading: servicesLoading } = useQuery({
    queryKey: ["/api/services"],
  });

  // Servis arama ve filtreleme
  const filteredServices = useMemo(() => {
    if (!services || !serviceSearch.trim()) return services?.slice(0, 50) || []; // İlk 50 servisi göster
    
    const searchTerm = serviceSearch.toLowerCase().trim();
    return services.filter((service: Service) => 
      service.name.toLowerCase().includes(searchTerm) || 
      service.externalId.toLowerCase().includes(searchTerm) ||
      service.id.toString() === searchTerm
    ).slice(0, 20); // Arama sonuçlarından ilk 20'sini göster
  }, [services, serviceSearch]);

  const handleServiceSelect = (service: Service) => {
    setSelectedService(service.id.toString());
    setServiceSearch(`${service.name} (ID: ${service.externalId})`);
    setShowServiceDropdown(false);
  };

  // Dropdown'un dışına tıklandığında kapat
  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setShowServiceDropdown(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const createKeysMutation = useMutation({
    mutationFn: async (data: { serviceId: number; name: string; count: number; maxAmount: number }) => {
      return await apiRequest("POST", "/api/keys", data);
    },
    onSuccess: () => {
      toast({
        title: "Başarılı",
        description: "Keyler başarıyla oluşturuldu",
      });
      queryClient.invalidateQueries({ queryKey: ["/api/keys"] });
      setSelectedService("");
      setKeyName("");
      setKeyCount(1);
      setMaxAmount(1000);
    },
    onError: (error) => {
      toast({
        title: "Hata",
        description: error.message || "Keyler oluşturulamadı",
        variant: "destructive",
      });
    },
  });

  const deleteKeyMutation = useMutation({
    mutationFn: async (id: number) => {
      return await apiRequest("DELETE", `/api/keys/${id}`);
    },
    onSuccess: () => {
      toast({
        title: "Başarılı",
        description: "Key başarıyla silindi",
      });
      queryClient.invalidateQueries({ queryKey: ["/api/keys"] });
    },
    onError: (error) => {
      toast({
        title: "Hata",
        description: error.message || "Key silinemedi",
        variant: "destructive",
      });
    },
  });

  const updateKeyMutation = useMutation({
    mutationFn: async ({ id, data }: { id: number; data: Partial<Key> }) => {
      return await apiRequest("PUT", `/api/keys/${id}`, data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/api/keys"] });
    },
    onError: (error) => {
      toast({
        title: "Hata",
        description: error.message || "Key güncellenemedi",
        variant: "destructive",
      });
    },
  });

  const downloadKeysMutation = useMutation({
    mutationFn: async (serviceName: string) => {
      const response = await fetch("/api/keys/download", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ serviceName }),
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || "Keyler indirilemedi");
      }

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      // Dosya adını backend'den gelen content-disposition header'ından al
      const contentDisposition = response.headers.get('content-disposition');
      let fileName = 'service_keys.txt';
      if (contentDisposition) {
        const fileNameMatch = contentDisposition.match(/filename="([^"]+)"/);
        if (fileNameMatch) {
          fileName = fileNameMatch[1];
        }
      }
      a.download = fileName;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    },
    onSuccess: () => {
      toast({
        title: "Başarılı",
        description: "Keyler başarıyla indirildi",
      });
      setDownloadServiceName("");
    },
    onError: (error) => {
      toast({
        title: "Hata",
        description: error.message || "Keyler indirilemedi",
        variant: "destructive",
      });
    },
  });

  const handleCreateKeys = async () => {
    if (!selectedService || !keyName) {
      toast({
        title: "Hata",
        description: "Lütfen tüm alanları doldurun",
        variant: "destructive",
      });
      return;
    }

    createKeysMutation.mutate({
      serviceId: parseInt(selectedService),
      name: keyName,
      count: keyCount,
      maxAmount: maxAmount,
    });
  };

  const copyToClipboard = async (text: string) => {
    try {
      await navigator.clipboard.writeText(text);
      toast({
        title: "Kopyalandı",
        description: "Key panoya kopyalandı",
      });
    } catch (error) {
      toast({
        title: "Hata",
        description: "Kopyalama başarısız",
        variant: "destructive",
      });
    }
  };

  const toggleKeyVisibility = (keyId: number) => {
    const newHiddenKeys = new Set(hiddenKeys);
    if (newHiddenKeys.has(keyId)) {
      newHiddenKeys.delete(keyId);
    } else {
      newHiddenKeys.add(keyId);
    }
    setHiddenKeys(newHiddenKeys);
  };

  const getServiceName = (serviceId: number) => {
    const service = services?.find((s: Service) => s.id === serviceId);
    return service ? service.name : "Bilinmeyen Servis";
  };

  const formatKey = (key: string, isHidden: boolean) => {
    if (!isHidden) return key;
    return `${key.substring(0, 8)}***${key.substring(key.length - 3)}`;
  };

  return (
    <div className="p-8 fade-in">
      <div className="mb-8">
        <div className="flex items-center justify-between mb-6">
          <h3 className="text-2xl font-semibold text-foreground">Key Yönetimi</h3>
        </div>
        
        <div className="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-8">
          <Card className="shadow-lg">
            <CardHeader className="pb-6">
              <CardTitle className="text-xl">Key Oluştur</CardTitle>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid grid-cols-1 gap-6">
                <div className="relative" ref={dropdownRef}>
                  <Label htmlFor="service" className="text-sm font-medium mb-2 block">Servis Adı veya Servis ID</Label>
                  <Input
                    id="service"
                    value={serviceSearch}
                    onChange={(e) => {
                      setServiceSearch(e.target.value);
                      setShowServiceDropdown(true);
                    }}
                    onFocus={() => setShowServiceDropdown(true)}
                    placeholder="Servis adı veya ID girin..."
                    className="w-full h-11"
                  />
                  {showServiceDropdown && serviceSearch && filteredServices.length > 0 && (
                    <div className="absolute z-50 w-full mt-1 bg-background border border-border rounded-md shadow-lg max-h-60 overflow-y-auto">
                      {filteredServices.map((service: Service) => (
                        <div
                          key={service.id}
                          onClick={() => handleServiceSelect(service)}
                          className="px-3 py-2 cursor-pointer hover:bg-muted border-b border-border last:border-b-0"
                        >
                          <div className="font-medium text-sm">{service.name}</div>
                          <div className="text-xs text-muted-foreground">
                            ID: {service.externalId} | Platform: {service.platform}
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                  {showServiceDropdown && serviceSearch && filteredServices.length === 0 && (
                    <div className="absolute z-50 w-full mt-1 bg-background border border-border rounded-md shadow-lg p-3">
                      <div className="text-sm text-muted-foreground">Servis bulunamadı</div>
                    </div>
                  )}
                </div>
              
                <div>
                  <Label htmlFor="keyName" className="text-sm font-medium mb-2 block">Key Adı</Label>
                  <Input
                    id="keyName"
                    value={keyName}
                    onChange={(e) => setKeyName(e.target.value)}
                    placeholder="Key adı girin..."
                    className="h-11"
                  />
                </div>
                
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <Label htmlFor="keyCount" className="text-sm font-medium mb-2 block">Key Sayısı</Label>
                    <Input
                      id="keyCount"
                      type="number"
                      value={keyCount}
                      onChange={(e) => setKeyCount(parseInt(e.target.value) || 1)}
                      min="1"
                      max="100"
                      className="h-11"
                    />
                  </div>
                  
                  <div>
                    <Label htmlFor="maxAmount" className="text-sm font-medium mb-2 block">Maksimum Miktar</Label>
                    <Input
                      id="maxAmount"
                      type="number"
                      value={maxAmount}
                      onChange={(e) => setMaxAmount(Number(e.target.value))}
                      placeholder="Maksimum miktar..."
                      min="1"
                      className="h-11"
                    />
                  </div>
                </div>
                
                <div className="pt-2">
                  <Button 
                    onClick={handleCreateKeys}
                    disabled={createKeysMutation.isPending}
                    className="btn-success h-11 px-6"
                    size="lg"
                  >
                    <Plus className="w-4 h-4 mr-2" />
                    {createKeysMutation.isPending ? "Oluşturuluyor..." : "Key Oluştur"}
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="shadow-lg">
            <CardHeader className="pb-6">
              <CardTitle className="text-xl">Key İndir</CardTitle>
            </CardHeader>
            <CardContent className="space-y-6">
              <div>
                <Label htmlFor="downloadServiceName" className="text-sm font-medium mb-2 block">Servis Adı</Label>
                <Input
                  id="downloadServiceName"
                  value={downloadServiceName}
                  onChange={(e) => setDownloadServiceName(e.target.value)}
                  placeholder="İndirmek istediğiniz servis adını girin..."
                  className="w-full h-11"
                />
                <p className="text-sm text-muted-foreground mt-3">
                  Bu servis için oluşturulmuş tüm keyler TXT dosyası olarak indirilecek
                </p>
                <p className="text-xs text-muted-foreground mt-1">
                  Örnek: "Youtube Video Likes | Faster | Max 1M | Low Drop | 30 Days Refill ♻️"
                </p>
              </div>
              
              <div className="pt-2">
                <Button 
                  onClick={() => {
                    if (!downloadServiceName.trim()) {
                      toast({
                        title: "Hata",
                        description: "Lütfen servis adını girin",
                        variant: "destructive",
                      });
                      return;
                    }
                    downloadKeysMutation.mutate(downloadServiceName.trim());
                  }}
                  disabled={downloadKeysMutation.isPending || !downloadServiceName.trim()}
                  className="btn-primary h-11 px-6"
                  size="lg"
                >
                  <Download className="w-4 h-4 mr-2" />
                  {downloadKeysMutation.isPending ? "İndiriliyor..." : "Keyleri İndir"}
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
      
      <Card className="shadow-lg">
        <CardHeader className="pb-6">
          <CardTitle className="text-xl">Mevcut Keyler</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Key</TableHead>
                  <TableHead>Servis</TableHead>
                  <TableHead>Durum</TableHead>
                  <TableHead>Oluşturulma</TableHead>
                  <TableHead>İşlemler</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {keysLoading ? (
                  <TableRow>
                    <TableCell colSpan={5} className="text-center py-8">
                      <div className="pulse-loader">Yükleniyor...</div>
                    </TableCell>
                  </TableRow>
                ) : keys?.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={5} className="text-center py-8 text-muted-foreground">
                      Henüz key oluşturulmamış
                    </TableCell>
                  </TableRow>
                ) : (
                  keys?.map((key: Key) => (
                    <TableRow key={key.id} className="table-hover">
                      <TableCell>
                        <div className="flex items-center space-x-2">
                          <span className="font-mono text-sm">
                            {formatKey(key.keyValue, hiddenKeys.has(key.id))}
                          </span>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => copyToClipboard(key.keyValue)}
                            className="copy-button"
                          >
                            <Copy className="w-4 h-4 text-cyan-500" />
                          </Button>
                        </div>
                      </TableCell>
                      <TableCell>
                        <span className="text-sm">{getServiceName(key.serviceId!)}</span>
                      </TableCell>
                      <TableCell>
                        <Badge className={key.isActive ? "status-active" : "status-inactive"}>
                          {key.isActive ? "Aktif" : "Pasif"}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        <span className="text-sm text-muted-foreground">
                          {new Date(key.createdAt!).toLocaleDateString("tr-TR")}
                        </span>
                      </TableCell>
                      <TableCell>
                        <div className="flex items-center space-x-2">
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => toggleKeyVisibility(key.id)}
                          >
                            {hiddenKeys.has(key.id) ? (
                              <EyeOff className="w-4 h-4" />
                            ) : (
                              <Eye className="w-4 h-4" />
                            )}
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => deleteKeyMutation.mutate(key.id)}
                            disabled={deleteKeyMutation.isPending}
                          >
                            <Trash2 className="w-4 h-4 text-destructive" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
