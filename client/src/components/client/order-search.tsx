import { useState } from "react";
import { useMutation } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Search, X } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { apiRequest } from "@/lib/api";
import type { Order } from "@shared/schema";

export default function OrderSearch() {
  const [orderId, setOrderId] = useState("");
  const [searchedOrder, setSearchedOrder] = useState<Order | null>(null);
  const [showModal, setShowModal] = useState(false);
  
  const { toast } = useToast();

  const searchOrderMutation = useMutation({
    mutationFn: async (orderId: string) => {
      const response = await apiRequest("GET", `/api/orders/${orderId}`);
      return response.json();
    },
    onSuccess: (order) => {
      setSearchedOrder(order);
      setShowModal(true);
    },
    onError: (error) => {
      toast({
        title: "Sipariş Bulunamadı",
        description: error.message || "Belirtilen sipariş bulunamadı.",
        variant: "destructive",
      });
    },
  });

  const handleSearchOrder = () => {
    if (!orderId.trim()) {
      toast({
        title: "Hata",
        description: "Lütfen sipariş ID girin",
        variant: "destructive",
      });
      return;
    }

    searchOrderMutation.mutate(orderId.trim());
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
    <>
      <Card>
        <CardHeader>
          <CardTitle className="text-center">Sipariş Sorgula</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <Input
            id="orderSearchId"
            value={orderId}
            onChange={(e) => setOrderId(e.target.value)}
            placeholder="Sipariş ID"
            onKeyPress={(e) => {
              if (e.key === "Enter") {
                handleSearchOrder();
              }
            }}
          />
          
          <Button
            onClick={handleSearchOrder}
            disabled={searchOrderMutation.isPending}
            className="w-full btn-warning"
          >
            <Search className="w-4 h-4 mr-2" />
            {searchOrderMutation.isPending ? "Aranıyor..." : "Sipariş Sorgula"}
          </Button>
        </CardContent>
      </Card>

      <Dialog open={showModal} onOpenChange={setShowModal}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <div className="flex items-center justify-between">
              <DialogTitle>Sipariş Detayları</DialogTitle>
              <Button
                variant="ghost"
                size="sm"
                onClick={() => setShowModal(false)}
              >
                <X className="w-4 h-4" />
              </Button>
            </div>
          </DialogHeader>
          
          {searchedOrder && (
            <div className="space-y-4">
              <div className="bg-muted/50 rounded-lg p-4">
                <div className="flex items-center justify-between mb-2">
                  <span className="text-sm text-muted-foreground">Sipariş ID:</span>
                  <span className="font-mono text-sm">{searchedOrder.orderId}</span>
                </div>
                <div className="flex items-center justify-between mb-2">
                  <span className="text-sm text-muted-foreground">Link:</span>
                  <span className="text-sm truncate max-w-[200px]">{searchedOrder.link}</span>
                </div>
                <div className="flex items-center justify-between mb-2">
                  <span className="text-sm text-muted-foreground">Miktar:</span>
                  <span className="text-sm">{searchedOrder.quantity?.toLocaleString()}</span>
                </div>
                <div className="flex items-center justify-between mb-2">
                  <span className="text-sm text-muted-foreground">Durum:</span>
                  {getStatusBadge(searchedOrder.status!)}
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-sm text-muted-foreground">Tarih:</span>
                  <span className="text-sm">
                    {new Date(searchedOrder.createdAt!).toLocaleString("tr-TR")}
                  </span>
                </div>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </>
  );
}
