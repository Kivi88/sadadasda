import { useState } from "react";
import * as React from "react";
import { useMutation } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { ShoppingCart, Copy, ExternalLink, Loader2 } from "lucide-react";
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
    mutationFn: async (data: { keyValue: string; link: string; quantity: number; serviceId: number }) => {
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

  // Auto redirect after 5 seconds
  React.useEffect(() => {
    if (showSuccessModal) {
      const timer = setTimeout(() => {
        goToOrderSearch();
      }, 5000);
      return () => clearTimeout(timer);
    }
  }, [showSuccessModal]);

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
      serviceId: service.id,
    });
  };

  return (
    <div className="modern-card slide-up p-8 max-w-2xl mx-auto">
      <div className="text-center mb-8">
        <div className="w-20 h-20 bg-gradient-to-br from-primary to-accent rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-xl">
          <ShoppingCart className="h-10 w-10 text-white" />
        </div>
        <h2 className="text-3xl font-bold bg-gradient-to-r from-primary to-accent bg-clip-text text-transparent mb-3">
          Sipariş Detayları
        </h2>
        <div className="inline-flex items-center px-4 py-2 bg-gradient-to-r from-primary/10 to-accent/10 rounded-full border border-primary/20">
          <span className="text-muted-foreground mr-2">Servis:</span>
          <span className="text-foreground font-semibold">{service.name}</span>
        </div>
      </div>
      
      <div className="space-y-6">
        <div className="space-y-2">
          <Label htmlFor="orderLink" className="text-sm font-semibold text-foreground flex items-center gap-2">
            <ExternalLink className="h-4 w-4 text-primary" />
            Hedef Link
          </Label>
          <Input
            id="orderLink"
            type="url"
            value={link}
            onChange={(e) => setLink(e.target.value)}
            placeholder="https://instagram.com/username"
            className="modern-input h-12 text-base"
          />
        </div>
        
        <div className="space-y-2">
          <Label htmlFor="orderQuantity" className="text-sm font-semibold text-foreground">
            Miktar ({service.minQuantity?.toLocaleString()} - {service.maxQuantity?.toLocaleString()})
          </Label>
          <Input
            id="orderQuantity"
            type="number"
            value={quantity}
            onChange={(e) => setQuantity(parseInt(e.target.value) || service.minQuantity!)}
            min={service.minQuantity}
            max={service.maxQuantity}
            className="modern-input h-12 text-base"
          />
          <p className="text-xs text-muted-foreground">
            Min: {service.minQuantity?.toLocaleString()} • Max: {service.maxQuantity?.toLocaleString()}
          </p>
        </div>
        
        <Button
          onClick={handleCreateOrder}
          disabled={createOrderMutation.isPending}
          className="w-full btn-gradient-primary h-14 text-lg font-semibold mt-8 transition-all duration-300"
        >
          {createOrderMutation.isPending ? (
            <div className="flex items-center gap-3">
              <Loader2 className="w-5 h-5 loading-spinner" />
              <span>Sipariş Oluşturuluyor...</span>
            </div>
          ) : (
            <div className="flex items-center gap-3">
              <ShoppingCart className="w-5 h-5" />
              <span>Sipariş Oluştur</span>
            </div>
          )}
        </Button>
      </div>

      {/* Başarı Modal'ı */}
      <Dialog open={showSuccessModal} onOpenChange={setShowSuccessModal}>
        <DialogContent className="sm:max-w-lg modern-modal bounce-in border-0">
          <DialogHeader className="text-center pb-6">
            <div className="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 pulse-success">
              <ShoppingCart className="h-8 w-8 text-white" />
            </div>
            <DialogTitle className="text-2xl font-bold bg-gradient-to-r from-green-500 to-emerald-600 bg-clip-text text-transparent">
              Sipariş Başarıyla Oluşturuldu!
            </DialogTitle>
            <p className="text-muted-foreground mt-2">
              Siparişiniz sistemimize kaydedildi ve işleme alındı
            </p>
          </DialogHeader>
          
          <div className="space-y-6">
            <div className="p-6 bg-gradient-to-r from-green-500/10 to-emerald-600/10 rounded-2xl border border-green-500/20">
              <p className="text-sm font-medium text-foreground mb-3 text-center">Sipariş ID'niz:</p>
              <div className="flex items-center justify-center space-x-3 p-4 bg-background/50 rounded-xl backdrop-blur-sm">
                <code className="font-mono text-xl font-bold text-primary tracking-wider">
                  {createdOrder?.orderId}
                </code>
                <Button 
                  size="sm" 
                  variant="outline" 
                  onClick={copyOrderId}
                  className="h-10 w-10 p-0 border-primary/30 hover:bg-primary/10"
                >
                  <Copy className="h-4 w-4" />
                </Button>
              </div>
            </div>
            
            <div className="flex flex-col space-y-3">
              <Button 
                onClick={goToOrderSearch} 
                className="w-full btn-gradient-primary h-12 text-base font-semibold"
              >
                <ExternalLink className="h-5 w-5 mr-3" />
                Sipariş Durumunu Kontrol Et
              </Button>
              <Button 
                variant="outline" 
                onClick={() => setShowSuccessModal(false)}
                className="w-full h-12 border-border/50 hover:bg-muted/50"
              >
                Kapat
              </Button>
            </div>
            
            <div className="text-center space-y-2">
              <p className="text-sm text-muted-foreground">
                Sipariş durumunuzu takip etmek için ID'nizi kaydedin
              </p>
              <div className="inline-flex items-center px-3 py-1 bg-primary/10 rounded-full">
                <div className="w-2 h-2 bg-primary rounded-full animate-pulse mr-2"></div>
                <span className="text-xs text-primary font-medium">
                  5 saniye sonra otomatik yönlendirileceksiniz
                </span>
              </div>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
}
