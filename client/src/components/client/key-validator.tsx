import { useState } from "react";
import { useMutation } from "@tanstack/react-query";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Loader2 } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import OrderForm from "./order-form";
import type { Service } from "@shared/schema";

export default function KeyValidator() {
  const [keyValue, setKeyValue] = useState("");
  const [validatedKey, setValidatedKey] = useState<any>(null);
  const [validatedService, setValidatedService] = useState<Service | null>(null);
  const [quantity, setQuantity] = useState(1);
  const [link, setLink] = useState("");
  const { toast } = useToast();

  const validateKeyMutation = useMutation({
    mutationFn: async (key: string) => {
      const response = await fetch(`/api/keys/validate`, {
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
      setValidatedKey(data.key);
      setValidatedService(data.service);
      setQuantity(data.service.minQuantity || 1);
      toast({
        title: "✅ Başarılı",
        description: "Key doğrulandı! Sipariş bilgilerini girin.",
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
          
          {validatedKey && validatedService && (
            <div className="space-y-4 mt-4">
              <div className="p-4 bg-green-50 rounded-lg">
                <p className="text-sm text-green-800">
                  <strong>Servis:</strong> {validatedService.name}
                </p>
                <p className="text-sm text-green-600 mt-1">
                  Kalan miktar: {(validatedKey.maxAmount || 1000) - (validatedKey.usedAmount || 0)}
                </p>
              </div>
              
              <div>
                <Label htmlFor="orderQuantity">
                  Miktar (0 - {(validatedKey.maxAmount || 1000) - (validatedKey.usedAmount || 0)})
                </Label>
                <Input
                  id="orderQuantity"
                  type="number"
                  value={quantity}
                  onChange={(e) => setQuantity(parseInt(e.target.value) || 1)}
                  min={1}
                  max={(validatedKey.maxAmount || 1000) - (validatedKey.usedAmount || 0)}
                />
              </div>
              
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
            </div>
          )}
        </CardContent>
      </Card>

      {validatedKey && validatedService && (
        <OrderForm 
          keyValue={keyValue} 
          service={validatedService}
          quantity={quantity}
          link={link}
          onOrderCreated={() => {
            setKeyValue("");
            setValidatedKey(null);
            setValidatedService(null);
            setQuantity(1);
            setLink("");
          }}
        />
      )}
    </div>
  );
}