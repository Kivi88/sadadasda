import { useState } from "react";
import { useMutation } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { CheckCircle } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { apiRequest } from "@/lib/api";
import OrderForm from "./order-form";
import type { Key, Service } from "@shared/schema";

export default function KeyValidator() {
  const [keyValue, setKeyValue] = useState("");
  const [validatedKey, setValidatedKey] = useState<Key | null>(null);
  const [validatedService, setValidatedService] = useState<Service | null>(null);
  
  const { toast } = useToast();

  const validateKeyMutation = useMutation({
    mutationFn: async (keyValue: string) => {
      const response = await apiRequest("POST", "/api/keys/validate", { keyValue });
      return response.json();
    },
    onSuccess: (data) => {
      setValidatedKey(data.key);
      setValidatedService(data.service);
      toast({
        title: "Key Doğrulandı",
        description: "Key başarıyla doğrulandı! Sipariş formunu doldurabilirsiniz.",
      });
    },
    onError: (error) => {
      toast({
        title: "Geçersiz Key",
        description: error.message || "Lütfen doğru key'i girin.",
        variant: "destructive",
      });
      setValidatedKey(null);
      setValidatedService(null);
    },
  });

  const handleValidateKey = () => {
    if (!keyValue.trim()) {
      toast({
        title: "Hata",
        description: "Lütfen bir key girin",
        variant: "destructive",
      });
      return;
    }

    validateKeyMutation.mutate(keyValue.trim());
  };

  return (
    <div className="space-y-6">
      <Card className="text-center">
        <CardHeader>
          <CardTitle className="text-2xl font-bold">Ürünü Teslim AI</CardTitle>
          <p className="text-muted-foreground">Lütfen ürün anahtarınızı girin</p>
        </CardHeader>
        <CardContent className="space-y-4">
          <Input
            value={keyValue}
            onChange={(e) => setKeyValue(e.target.value)}
            placeholder="Ürün Anahtarı"
            className="text-center"
            onKeyPress={(e) => {
              if (e.key === "Enter") {
                handleValidateKey();
              }
            }}
          />
          
          <Button
            onClick={handleValidateKey}
            disabled={validateKeyMutation.isPending}
            className="w-full btn-success"
          >
            <CheckCircle className="w-4 h-4 mr-2" />
            {validateKeyMutation.isPending ? "Doğrulanıyor..." : "Doğrula"}
          </Button>
          
          <p className="text-sm text-muted-foreground">
            Siparişi oluşturduktan sonra ürün anahtarınızı girerek durumunu kontrol edebilirsiniz.
          </p>
        </CardContent>
      </Card>

      {validatedKey && validatedService && (
        <OrderForm 
          keyValue={keyValue} 
          service={validatedService}
          onOrderCreated={() => {
            setKeyValue("");
            setValidatedKey(null);
            setValidatedService(null);
          }}
        />
      )}
    </div>
  );
}
