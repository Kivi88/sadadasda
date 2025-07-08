import { useQuery } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { RefreshCw } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { queryClient } from "@/lib/queryClient";
import type { Service, Api } from "@shared/schema";

export default function ServiceManagement() {
  const { toast } = useToast();

  const { data: services, isLoading: servicesLoading } = useQuery({
    queryKey: ["/api/services"],
  });

  const { data: apis } = useQuery({
    queryKey: ["/api/apis"],
  });

  const refreshServices = () => {
    toast({
      title: "Yenileniyor",
      description: "Servisler güncelleniyor...",
    });
    // Invalidate and refetch services
    queryClient.invalidateQueries({ queryKey: ["/api/services"] });
    queryClient.invalidateQueries({ queryKey: ["/api/apis"] });
  };

  const getApiName = (apiId: number) => {
    const api = apis?.find((a: Api) => a.id === apiId);
    return api ? api.name : "Bilinmeyen API";
  };

  const getStatusBadge = (isActive: boolean) => {
    if (isActive) return <Badge className="status-active">Aktif</Badge>;
    return <Badge className="status-inactive">Pasif</Badge>;
  };

  return (
    <div className="p-6 fade-in">
      <div className="mb-6">
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-lg font-semibold text-foreground">Servis Yönetimi</h3>
          <Button onClick={refreshServices} className="btn-primary">
            <RefreshCw className="w-4 h-4 mr-2" />
            Servisleri Yenile
          </Button>
        </div>
      </div>
      
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle>Mevcut Servisler</CardTitle>
            <div className="flex items-center space-x-2">
              <Select>
                <SelectTrigger className="w-48">
                  <SelectValue placeholder="Tüm Platformlar" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Tüm Platformlar</SelectItem>
                  <SelectItem value="instagram">Instagram</SelectItem>
                  <SelectItem value="tiktok">TikTok</SelectItem>
                  <SelectItem value="youtube">YouTube</SelectItem>
                  <SelectItem value="twitter">Twitter</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Servis ID</TableHead>
                  <TableHead>Servis Adı</TableHead>
                  <TableHead>Platform</TableHead>
                  <TableHead>API Kaynağı</TableHead>
                  <TableHead>Minimum</TableHead>
                  <TableHead>Maksimum</TableHead>
                  <TableHead>Durum</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {servicesLoading ? (
                  <TableRow>
                    <TableCell colSpan={7} className="text-center py-8">
                      <div className="pulse-loader">Yükleniyor...</div>
                    </TableCell>
                  </TableRow>
                ) : services?.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={7} className="text-center py-8 text-muted-foreground">
                      Henüz servis eklenmemiş
                    </TableCell>
                  </TableRow>
                ) : (
                  services?.map((service: Service) => (
                    <TableRow key={service.id} className="table-hover">
                      <TableCell>
                        <span className="font-mono text-sm">#{service.id}</span>
                      </TableCell>
                      <TableCell>
                        <span className="text-sm font-medium">{service.name}</span>
                      </TableCell>
                      <TableCell>
                        <span className="text-sm capitalize">{service.platform}</span>
                      </TableCell>
                      <TableCell>
                        <span className="text-sm">{getApiName(service.apiId!)}</span>
                      </TableCell>
                      <TableCell>
                        <span className="text-sm">{service.minQuantity?.toLocaleString()}</span>
                      </TableCell>
                      <TableCell>
                        <span className="text-sm">{service.maxQuantity?.toLocaleString()}</span>
                      </TableCell>
                      <TableCell>
                        {getStatusBadge(service.isActive!)}
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
