import { useState } from "react";
import { useMutation } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Search, X, Copy, Clock, CheckCircle, XCircle, Loader2 } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import type { Order } from "@shared/schema";

export default function OrderSearch() {
  const [orderId, setOrderId] = useState("");
  const [searchedOrder, setSearchedOrder] = useState<Order | null>(null);
  const [showModal, setShowModal] = useState(false);
  
  const { toast } = useToast();

  const searchOrderMutation = useMutation({
    mutationFn: async (orderId: string) => {
      const response = await fetch(`/api/orders/search?orderId=${encodeURIComponent(orderId)}`);
      if (!response.ok) {
        throw new Error("Sipariş bulunamadı");
      }
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

  const copyOrderId = async (orderIdToCopy: string) => {
    try {
      await navigator.clipboard.writeText(orderIdToCopy);
      toast({
        title: "Kopyalandı",
        description: "Sipariş ID panoya kopyalandı",
      });
    } catch (error) {
      toast({
        title: "Hata",
        description: "Kopyalama başarısız",
        variant: "destructive",
      });
    }
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
            {searchOrderMutation.isPending ? (
              <Loader2 className="w-4 h-4 mr-2 animate-spin" />
            ) : (
              <Search className="w-4 h-4 mr-2" />
            )}
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
              {/* Progress Tracker */}
              <div className="bg-gradient-to-r from-blue-900/20 to-green-900/20 rounded-lg p-4">
                <div className="flex items-center justify-between mb-4">
                  <div className={`flex items-center space-x-2 ${searchedOrder.status === 'pending' ? 'text-blue-400' : 'text-green-400'}`}>
                    <div className={`w-8 h-8 rounded-full flex items-center justify-center ${searchedOrder.status === 'pending' ? 'bg-blue-500' : 'bg-green-500'}`}>
                      <CheckCircle className="w-4 h-4 text-white" />
                    </div>
                    <span className="text-sm font-medium">Sipariş Alındı</span>
                  </div>
                  <div className="h-0.5 flex-1 mx-4 bg-gray-600 relative overflow-hidden">
                    {(searchedOrder.status === 'processing' || searchedOrder.status === 'completed') && (
                      <div className="absolute inset-0 bg-blue-500"></div>
                    )}
                    {searchedOrder.status === 'processing' && (
                      <div className="absolute inset-0 bg-gradient-to-r from-blue-500 via-blue-300 to-blue-500 animate-pulse"></div>
                    )}
                  </div>
                  <div className={`flex items-center space-x-2 ${searchedOrder.status === 'processing' ? 'text-blue-400' : searchedOrder.status === 'completed' ? 'text-green-400' : 'text-gray-400'}`}>
                    <div className={`w-8 h-8 rounded-full flex items-center justify-center ${searchedOrder.status === 'processing' ? 'bg-blue-500' : searchedOrder.status === 'completed' ? 'bg-green-500' : 'bg-gray-600'}`}>
                      {searchedOrder.status === 'processing' ? (
                        <Loader2 className="w-4 h-4 text-white animate-spin" />
                      ) : (
                        <Clock className="w-4 h-4 text-white" />
                      )}
                    </div>
                    <span className="text-sm font-medium">İşleniyor</span>
                  </div>
                  <div className="h-0.5 flex-1 mx-4 bg-gray-600 relative overflow-hidden">
                    {searchedOrder.status === 'completed' && (
                      <div className="absolute inset-0 bg-green-500"></div>
                    )}
                    {searchedOrder.status === 'processing' && (
                      <div className="absolute inset-0 bg-gradient-to-r from-transparent via-blue-400/50 to-transparent animate-pulse"></div>
                    )}
                  </div>
                  <div className={`flex items-center space-x-2 ${searchedOrder.status === 'completed' ? 'text-green-400' : 'text-gray-400'}`}>
                    <div className={`w-8 h-8 rounded-full flex items-center justify-center ${searchedOrder.status === 'completed' ? 'bg-green-500' : 'bg-gray-600'}`}>
                      <CheckCircle className="w-4 h-4 text-white" />
                    </div>
                    <span className="text-sm font-medium">Tamamlandı</span>
                  </div>
                </div>
              </div>

              {/* Order Details */}
              <div className="bg-muted/50 rounded-lg p-4">
                <div className="flex items-center justify-between mb-2">
                  <span className="text-sm text-muted-foreground">Sipariş ID:</span>
                  <div className="flex items-center space-x-2">
                    <span className="font-mono text-sm">{searchedOrder.orderId}</span>
                    <Button 
                      size="sm" 
                      variant="outline" 
                      onClick={() => copyOrderId(searchedOrder.orderId)}
                      className="h-6 w-6 p-0"
                    >
                      <Copy className="h-3 w-3" />
                    </Button>
                  </div>
                </div>
                <div className="flex items-center justify-between mb-2">
                  <span className="text-sm text-muted-foreground">Link:</span>
                  <span className="text-sm truncate max-w-[200px]">{searchedOrder.link}</span>
                </div>
                <div className="flex items-center justify-between mb-2">
                  <span className="text-sm text-muted-foreground">Miktar:</span>
                  <span className="text-sm font-semibold">{searchedOrder.quantity.toLocaleString()}</span>
                </div>
                <div className="flex items-center justify-between mb-2">
                  <span className="text-sm text-muted-foreground">Durum:</span>
                  {getStatusBadge(searchedOrder.status)}
                </div>
                {searchedOrder.service && (
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-sm text-muted-foreground">Servis:</span>
                    <span className="text-sm text-right max-w-[200px] truncate">{searchedOrder.service.name}</span>
                  </div>
                )}
                {searchedOrder.externalOrderId && (
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-sm text-muted-foreground">API Sipariş ID:</span>
                    <div className="flex items-center space-x-2">
                      <span className="font-mono text-sm">{searchedOrder.externalOrderId}</span>
                      <Button 
                        size="sm" 
                        variant="outline" 
                        onClick={() => copyOrderId(searchedOrder.externalOrderId!)}
                        className="h-6 w-6 p-0"
                      >
                        <Copy className="h-3 w-3" />
                      </Button>
                    </div>
                  </div>
                )}
                <div className="flex items-center justify-between">
                  <span className="text-sm text-muted-foreground">Oluşturulma:</span>
                  <span className="text-sm">{new Date(searchedOrder.createdAt).toLocaleString("tr-TR")}</span>
                </div>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </>
  );
}
