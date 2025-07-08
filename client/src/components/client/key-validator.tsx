import { useState } from "react";
import { useMutation } from "@tanstack/react-query";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Loader2 } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { apiRequest } from "@/lib/api";
import OrderForm from "./order-form";
import type { Service } from "@shared/schema";

export default function KeyValidator() {
  const [keyValue, setKeyValue] = useState("");
  const [validatedKey, setValidatedKey] = useState<any>(null);
  const [validatedService, setValidatedService] = useState<Service | null>(null);
  const { toast } = useToast();

  const validateKeyMutation = useMutation({
    mutationFn: async (key: string) => {
      const response = await apiRequest(`/api/keys/validate`, {
        method: "POST",
        body: JSON.stringify({ keyValue: key }),
        headers: { "Content-Type": "application/json" },
      });
      
      if (!response.ok) {
        const error = await response.text();
        throw new Error(error || "Key doğrulama başarısız");
      }
      
      return response.json();
    },
    onSuccess: (data) => {
      setValidatedKey(data);
      toast({
        title: "✅ Başarılı",
        description: "Key doğrulandı! Servisler yükleniyor...",
      });
    },
    onError: (error: any) => {
      toast({
        title: "❌ Hata",
        description: error.message || "Key doğrulama başarısız",
        variant: "destructive",
      });
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
          <CardTitle className="text-2xl font-bold">KiwiPazarı</CardTitle>
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
            disabled={validateKeyMutation.isPending || !keyValue}
            className="w-full"
          >
            {validateKeyMutation.isPending ? (
              <>
                <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                Doğrulanıyor...
              </>
            ) : (
              "✅ Doğrula"
            )}
          </Button>
          
          {validatedKey && (
            <p className="text-sm text-green-600 mt-2">
              Sipariş oluşturduysanız ürün anahtarınızı girerek durumunu kontrol edebilirsiniz
            </p>
          )}
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