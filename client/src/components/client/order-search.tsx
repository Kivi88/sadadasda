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
      <div className="max-w-md mx-auto">
        <Card className="bg-gradient-to-br from-slate-800 to-slate-900 border-slate-700">
          <CardHeader className="text-center pb-3">
            <CardTitle className="text-xl font-bold text-white">Sipariş Sorgula</CardTitle>
            <p className="text-sm text-slate-400 mt-1">Sipariş ID'nizi girin</p>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="relative">
              <Input
                id="orderSearchId"
                value={orderId}
                onChange={(e) => setOrderId(e.target.value)}
                placeholder="Sipariş ID"
                className="bg-slate-700 border-slate-600 text-white placeholder-slate-400 focus:border-blue-500 focus:ring-blue-500/20 h-12 text-lg"
                onKeyPress={(e) => {
                  if (e.key === "Enter") {
                    handleSearchOrder();
                  }
                }}
              />
              <Search className="absolute right-3 top-3 h-6 w-6 text-slate-400" />
            </div>
            
            <Button
              onClick={handleSearchOrder}
              disabled={searchOrderMutation.isPending}
              className="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-medium py-3 rounded-lg transition-all duration-200 transform hover:scale-105"
            >
              {searchOrderMutation.isPending ? (
                <Loader2 className="w-5 h-5 mr-2 animate-spin" />
              ) : (
                <Search className="w-5 h-5 mr-2" />
              )}
              {searchOrderMutation.isPending ? "Aranıyor..." : "Sipariş Sorgula"}
            </Button>
          </CardContent>
        </Card>
      </div>

      <Dialog open={showModal} onOpenChange={setShowModal}>
        <DialogContent className="max-w-md bg-gradient-to-br from-slate-800 to-slate-900 border-slate-700">
          <DialogHeader className="border-b border-slate-700 pb-3">
            <div className="flex items-center justify-between">
              <DialogTitle className="text-xl font-bold text-white">Sipariş Detayları</DialogTitle>
              <Button
                variant="ghost"
                size="sm"
                onClick={() => setShowModal(false)}
                className="text-slate-400 hover:text-white hover:bg-slate-700"
              >
                <X className="w-4 h-4" />
              </Button>
            </div>
          </DialogHeader>
          
          {searchedOrder && (
            <div className="space-y-4 pt-4">
              {/* Order Status Header */}
              <div className="text-center p-4 bg-slate-700/50 rounded-lg">
                <div className="text-2xl font-bold text-white mb-1">{searchedOrder.orderId}</div>
                <div className={`text-sm px-3 py-1 rounded-full inline-block font-medium ${
                  searchedOrder.status === 'completed' 
                    ? 'bg-green-600 text-white' 
                    : searchedOrder.status === 'processing' 
                    ? 'bg-blue-600 text-white' 
                    : 'bg-orange-600 text-white'
                }`}>
                  {searchedOrder.status === 'completed' ? 'Tamamlandı' : 
                   searchedOrder.status === 'processing' ? 'İşleniyor' : 
                   searchedOrder.status === 'pending' ? 'Beklemede' : searchedOrder.status}
                </div>
              </div>

              {/* Order Details */}
              <div className="bg-slate-700/30 rounded-lg p-4 space-y-3">
                <div className="flex items-center justify-between">
                  <span className="text-sm text-slate-400">Miktar:</span>
                  <span className="text-sm font-semibold text-white">{searchedOrder.quantity.toLocaleString()}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-sm text-slate-400">Link:</span>
                  <span className="text-sm truncate max-w-[200px] text-blue-400">{searchedOrder.link}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-sm text-slate-400">Oluşturulma:</span>
                  <span className="text-sm text-white">{new Date(searchedOrder.createdAt).toLocaleString("tr-TR")}</span>
                </div>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </>
  );
}
