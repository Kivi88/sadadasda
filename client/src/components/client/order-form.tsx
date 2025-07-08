import { useState } from "react";
import { useMutation } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { ShoppingCart, Copy, ExternalLink } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { apiRequest } from "@/lib/api";
import { useLocation } from "wouter";
import type { Service } from "@shared/schema";

interface OrderFormProps {
  keyValue: string;
  service: Service;
  onOrderCreated: () => void;
}

export default function OrderForm({ keyValue, service, onOrderCreated }: OrderFormProps) {
  const [link, setLink] = useState("");
  const [quantity, setQuantity] = useState(service.minQuantity || 1);
  const [showSuccessModal, setShowSuccessModal] = useState(false);
  const [createdOrder, setCreatedOrder] = useState<any>(null);
  
  const { toast } = useToast();
  const [, navigate] = useLocation();

  const createOrderMutation = useMutation({
    mutationFn: async (data: { keyValue: string; link: string; quantity: number }) => {
      const response = await apiRequest("POST", "/api/orders", data);
      return response.json();
    },
    onSuccess: (order) => {
      setCreatedOrder(order);
      setShowSuccessModal(true);
      onOrderCreated();
      setLink("");
      setQuantity(service.minQuantity || 1);
    },
    onError: (error) => {
      toast({
        title: "Sipariş Oluşturulamadı",
        description: error.message || "Sipariş oluştururken bir hata oluştu.",
        variant: "destructive",
      });
    },
  });

  const copyOrderId = async () => {
    if (!createdOrder?.orderId) return;
    
    try {
      await navigator.clipboard.writeText(createdOrder.orderId);
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

  const goToOrderSearch = () => {
    setShowSuccessModal(false);
    navigate("/order-search");
  };

  const handleCreateOrder = () => {
    if (!link.trim() || !quantity) {
      toast({
        title: "Hata",
        description: "Lütfen tüm alanları doldurun",
        variant: "destructive",
      });
      return;
    }

    createOrderMutation.mutate({
      keyValue,
      link: link.trim(),
      quantity,
    });
  };

  return (
    <Card className="fade-in">
      <CardHeader>
        <CardTitle className="text-center">Sipariş Detayları</CardTitle>
        <p className="text-sm text-muted-foreground text-center">
          Servis: {service.name}
        </p>
      </CardHeader>
      <CardContent className="space-y-4">
        <div>
          <Label htmlFor="orderLink">Link</Label>
          <Input
            id="orderLink"
            type="url"
            value={link}
            onChange={(e) => setLink(e.target.value)}
            placeholder="https://instagram.com/username"
          />
        </div>
        
        <div>
          <Label htmlFor="orderQuantity">
            Miktar ({service.minQuantity} - {service.maxQuantity?.toLocaleString()})
          </Label>
          <Input
            id="orderQuantity"
            type="number"
            value={quantity}
            onChange={(e) => setQuantity(parseInt(e.target.value) || service.minQuantity!)}
            min={service.minQuantity}
            max={service.maxQuantity}
          />
        </div>
        
        <Button
          onClick={handleCreateOrder}
          disabled={createOrderMutation.isPending}
          className="w-full btn-primary"
        >
          <ShoppingCart className="w-4 h-4 mr-2" />
          {createOrderMutation.isPending ? "Oluşturuluyor..." : "Sipariş Oluştur"}
        </Button>
      </CardContent>

      {/* Başarı Modal'ı */}
      <Dialog open={showSuccessModal} onOpenChange={setShowSuccessModal}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle className="text-center text-green-600">
              🎉 Sipariş Başarıyla Oluşturuldu!
            </DialogTitle>
          </DialogHeader>
          
          <div className="space-y-4">
            <div className="text-center">
              <p className="text-sm text-muted-foreground mb-2">Sipariş ID'niz:</p>
              <div className="flex items-center justify-center space-x-2 p-3 bg-muted rounded-lg">
                <code className="font-mono text-lg font-semibold">
                  {createdOrder?.orderId}
                </code>
                <Button size="sm" variant="outline" onClick={copyOrderId}>
                  <Copy className="h-4 w-4" />
                </Button>
              </div>
            </div>
            
            <div className="flex flex-col space-y-2">
              <Button onClick={goToOrderSearch} className="w-full">
                <ExternalLink className="h-4 w-4 mr-2" />
                Sipariş Sorgula Sayfasına Git
              </Button>
              <Button 
                variant="outline" 
                onClick={() => setShowSuccessModal(false)}
                className="w-full"
              >
                Kapat
              </Button>
            </div>
            
            <div className="text-xs text-muted-foreground text-center">
              Sipariş durumunuzu takip etmek için ID'nizi saklayın
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </Card>
  );
}
