import { useQuery } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import type { Order, Service, Key } from "@shared/schema";

export default function OrderManagement() {
  const { data: orders, isLoading: ordersLoading } = useQuery({
    queryKey: ["/api/orders"],
  });

  const { data: services } = useQuery({
    queryKey: ["/api/services"],
  });

  const { data: keys } = useQuery({
    queryKey: ["/api/keys"],
  });

  const getServiceName = (serviceId: number) => {
    const service = services?.find((s: Service) => s.id === serviceId);
    return service ? service.name : "Bilinmeyen Servis";
  };

  const getKeyDisplay = (keyId: number) => {
    const key = keys?.find((k: Key) => k.id === keyId);
    if (!key) return "Bilinmeyen Key";
    return `${key.prefix}...${key.keyValue.slice(-3)}`;
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case "completed":
        return <Badge className="status-completed">Tamamlandı</Badge>;
      case "processing":
        return <Badge className="status-processing">İşleniyor</Badge>;
      case "pending":
        return <Badge className="status-pending">Beklemede</Badge>;
      case "cancelled":
        return <Badge className="status-cancelled">İptal Edildi</Badge>;
      default:
        return <Badge className="status-pending">Bilinmeyen</Badge>;
    }
  };

  return (
    <div className="p-6 fade-in">
      <div className="mb-6">
        <h3 className="text-lg font-semibold text-foreground">Siparişler</h3>
      </div>
      
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle>Tüm Siparişler</CardTitle>
            <div className="flex items-center space-x-2">
              <Select>
                <SelectTrigger className="w-48">
                  <SelectValue placeholder="Tüm Durumlar" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Tüm Durumlar</SelectItem>
                  <SelectItem value="pending">Beklemede</SelectItem>
                  <SelectItem value="processing">İşleniyor</SelectItem>
                  <SelectItem value="completed">Tamamlandı</SelectItem>
                  <SelectItem value="cancelled">İptal Edildi</SelectItem>
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
                  <TableHead>Sipariş ID</TableHead>
                  <TableHead>Key</TableHead>
                  <TableHead>Servis</TableHead>
                  <TableHead>Link</TableHead>
                  <TableHead>Miktar</TableHead>
                  <TableHead>Durum</TableHead>
                  <TableHead>Tarih</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {ordersLoading ? (
                  <TableRow>
                    <TableCell colSpan={7} className="text-center py-8">
                      <div className="pulse-loader">Yükleniyor...</div>
                    </TableCell>
                  </TableRow>
                ) : orders?.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={7} className="text-center py-8 text-muted-foreground">
                      Henüz sipariş oluşturulmamış
                    </TableCell>
                  </TableRow>
                ) : (
                  orders?.map((order: Order) => (
                    <TableRow key={order.id} className="table-hover">
                      <TableCell>
                        <span className="font-mono text-sm">{order.orderId}</span>
                      </TableCell>
                      <TableCell>
                        <span className="font-mono text-sm text-muted-foreground">
                          {getKeyDisplay(order.keyId!)}
                        </span>
                      </TableCell>
                      <TableCell>
                        <span className="text-sm">{getServiceName(order.serviceId!)}</span>
                      </TableCell>
                      <TableCell>
                        <span className="text-sm text-muted-foreground">
                          {order.link.length > 30 ? `${order.link.substring(0, 30)}...` : order.link}
                        </span>
                      </TableCell>
                      <TableCell>
                        <span className="text-sm font-medium">{order.quantity?.toLocaleString()}</span>
                      </TableCell>
                      <TableCell>
                        {getStatusBadge(order.status!)}
                      </TableCell>
                      <TableCell>
                        <span className="text-sm text-muted-foreground">
                          {new Date(order.createdAt!).toLocaleDateString("tr-TR")}
                        </span>
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
