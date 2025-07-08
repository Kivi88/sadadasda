import { useState } from "react";
import { useMutation } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { ShoppingCart } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { apiRequest } from "@/lib/api";
import type { Service } from "@shared/schema";

interface OrderFormProps {
  keyValue: string;
  service: Service;
  onOrderCreated: () => void;
}

export default function OrderForm({ keyValue, service, onOrderCreated }: OrderFormProps) {
  const [link, setLink] = useState("");
  const [quantity, setQuantity] = useState(service.minQuantity || 1);
  
  const { toast } = useToast();

  const createOrderMutation = useMutation({
    mutationFn: async (data: { keyValue: string; link: string; quantity: number }) => {
      const response = await apiRequest("POST", "/api/orders", data);
      return response.json();
    },
    onSuccess: (order) => {
      toast({
        title: "Sipariş Oluşturuldu",
        description: `Sipariş başarıyla oluşturuldu! ID: ${order.orderId}`,
      });
      
      // Auto-fill the search input with the new order ID
      const searchInput = document.getElementById("orderSearchId") as HTMLInputElement;
      if (searchInput) {
        searchInput.value = order.orderId;
      }
      
      onOrderCreated();
    },
    onError: (error) => {
      toast({
        title: "Sipariş Oluşturulamadı",
        description: error.message || "Sipariş oluştururken bir hata oluştu.",
        variant: "destructive",
      });
    },
  });

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
    </Card>
  );
}
