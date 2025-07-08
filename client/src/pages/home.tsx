import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent } from "@/components/ui/card";
import { CheckCircle2, Search, Copy, ExternalLink } from "lucide-react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { apiRequest } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";
import type { Key, Service, Order } from "@shared/schema";

export default function Home() {
  const [keyValue, setKeyValue] = useState("");
  const [link, setLink] = useState("");
  const [quantity, setQuantity] = useState("");
  const [orderSearchId, setOrderSearchId] = useState("");
  const [validatedKey, setValidatedKey] = useState<Key | null>(null);
  const [service, setService] = useState<Service | null>(null);
  const [showOrderSearch, setShowOrderSearch] = useState(false);
  const [createdOrder, setCreatedOrder] = useState<Order | null>(null);
  const [showSuccessModal, setShowSuccessModal] = useState(false);
  const [countdown, setCountdown] = useState(5);
  
  const { toast } = useToast();
  const queryClient = useQueryClient();

  // Key validation
  const validateKeyMutation = useMutation({
    mutationFn: async (key: string) => {
      const response = await apiRequest("POST", `/api/keys/validate`, { keyValue: key });
      return response.json();
    },
    onSuccess: (data) => {
      setValidatedKey(data.key);
      setService(data.service);
      toast({
        title: "Key doğrulandı",
        description: `Kalan kullanım: ${data.key.maxAmount - data.key.usedAmount}`,
      });
    },
    onError: (error: any) => {
      toast({
        title: "Hata",
        description: error.message || "Key doğrulanamadı",
        variant: "destructive",
      });
    }
  });

  // Order creation
  const createOrderMutation = useMutation({
    mutationFn: async (orderData: any) => {
      const response = await apiRequest("POST", `/api/orders`, orderData);
      return response.json();
    },
    onSuccess: (data) => {
      setCreatedOrder(data);
      setShowSuccessModal(true);
      setValidatedKey(null);
      setService(null);
      setKeyValue("");
      setLink("");
      setQuantity("");
      
      // Start countdown
      const timer = setInterval(() => {
        setCountdown(prev => {
          if (prev <= 1) {
            clearInterval(timer);
            setShowSuccessModal(false);
            setShowOrderSearch(true);
            setOrderSearchId(data.orderId);
            return 5;
          }
          return prev - 1;
        });
      }, 1000);
    },
    onError: (error: any) => {
      toast({
        title: "Hata",
        description: error.message || "Sipariş oluşturulamadı",
        variant: "destructive",
      });
    }
  });

  // Order search
  const { data: searchedOrder, isLoading: isSearching } = useQuery({
    queryKey: ["/api/orders/search", orderSearchId],
    enabled: !!orderSearchId && orderSearchId.length > 0,
    queryFn: async () => {
      const response = await fetch(`/api/orders/search?orderId=${orderSearchId}`);
      if (!response.ok) {
        throw new Error('Order not found');
      }
      return response.json();
    }
  });

  const handleVerify = () => {
    if (!keyValue.trim()) return;
    validateKeyMutation.mutate(keyValue);
  };

  const handleCreateOrder = () => {
    if (!validatedKey || !service || !link || !quantity) return;
    
    const orderData = {
      keyValue: validatedKey.keyValue,
      serviceId: service.id,
      link,
      quantity: parseInt(quantity)
    };
    
    createOrderMutation.mutate(orderData);
  };

  const copyOrderId = (orderId: string) => {
    navigator.clipboard.writeText(orderId);
    toast({
      title: "Kopyalandı",
      description: "Sipariş ID panoya kopyalandı",
    });
  };

  const remainingAmount = validatedKey ? validatedKey.maxAmount - validatedKey.usedAmount : 0;

  return (
    <div className="min-h-screen bg-gray-800 relative">
      {/* Order Search Button */}
      <Button
        onClick={() => setShowOrderSearch(!showOrderSearch)}
        className="absolute top-4 right-4 bg-gray-600 hover:bg-gray-500 text-white"
      >
        <Search className="w-4 h-4 mr-2" />
        Sipariş Sorgula
      </Button>

      <div className="flex items-center justify-center min-h-screen p-4">
        {!showOrderSearch ? (
          <Card className="w-full max-w-md bg-gray-700 border-gray-600">
            <CardContent className="p-8 space-y-6">
              {/* Title */}
              <div className="text-center">
                <h1 className="text-2xl font-bold text-white mb-2">
                  KIWIPAZARI
                </h1>
                <p className="text-gray-400 text-sm">
                  Lütfen ürün anahtarınızı girin
                </p>
              </div>

              {/* Key Validation */}
              {!validatedKey && (
                <div className="space-y-4">
                  <Input
                    type="text"
                    placeholder="Ürün Anahtarı"
                    value={keyValue}
                    onChange={(e) => setKeyValue(e.target.value)}
                    className="bg-gray-800 border-gray-600 text-white placeholder-gray-400 focus:border-blue-500"
                  />
                  <Button
                    onClick={handleVerify}
                    disabled={!keyValue.trim() || validateKeyMutation.isPending}
                    className="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3"
                  >
                    {validateKeyMutation.isPending ? (
                      <div className="flex items-center gap-2">
                        <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        Doğrulanıyor...
                      </div>
                    ) : (
                      <div className="flex items-center gap-2">
                        <CheckCircle2 className="w-4 h-4" />
                        Doğrula
                      </div>
                    )}
                  </Button>
                </div>
              )}

              {/* Order Form */}
              {validatedKey && service && (
                <div className="space-y-4">
                  <div className="bg-gray-800 p-4 rounded-lg">
                    <h3 className="text-white font-medium mb-2">{service.name}</h3>
                    <p className="text-gray-400 text-sm mb-2">
                      Platform: {service.platform} | Kategori: {service.category}
                    </p>
                    <p className="text-green-400 text-sm">
                      Kalan kullanım: {remainingAmount}
                    </p>
                  </div>

                  <Input
                    type="text"
                    placeholder="Link/URL"
                    value={link}
                    onChange={(e) => setLink(e.target.value)}
                    className="bg-gray-800 border-gray-600 text-white placeholder-gray-400 focus:border-blue-500"
                  />

                  <Input
                    type="number"
                    placeholder={`Miktar (1-${remainingAmount})`}
                    value={quantity}
                    onChange={(e) => setQuantity(e.target.value)}
                    min="1"
                    max={remainingAmount}
                    className="bg-gray-800 border-gray-600 text-white placeholder-gray-400 focus:border-blue-500"
                  />

                  <div className="flex gap-2">
                    <Button
                      onClick={() => {
                        setValidatedKey(null);
                        setService(null);
                        setKeyValue("");
                        setLink("");
                        setQuantity("");
                      }}
                      variant="outline"
                      className="flex-1 border-gray-600 text-gray-400 hover:bg-gray-600"
                    >
                      İptal
                    </Button>
                    <Button
                      onClick={handleCreateOrder}
                      disabled={!link || !quantity || createOrderMutation.isPending}
                      className="flex-1 bg-green-600 hover:bg-green-700 text-white"
                    >
                      {createOrderMutation.isPending ? (
                        <div className="flex items-center gap-2">
                          <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                          Gönderiliyor...
                        </div>
                      ) : (
                        "Sipariş Ver"
                      )}
                    </Button>
                  </div>
                </div>
              )}
            </CardContent>
          </Card>
        ) : (
          <Card className="w-full max-w-md bg-gray-700 border-gray-600">
            <CardContent className="p-8 space-y-6">
              <div className="text-center">
                <h1 className="text-2xl font-bold text-white mb-2">
                  Sipariş Sorgula
                </h1>
                <p className="text-gray-400 text-sm">
                  Sipariş ID'nizi girin
                </p>
              </div>

              <div className="space-y-4">
                <Input
                  type="text"
                  placeholder="Sipariş ID (örn: #2384344)"
                  value={orderSearchId}
                  onChange={(e) => setOrderSearchId(e.target.value)}
                  className="bg-gray-800 border-gray-600 text-white placeholder-gray-400 focus:border-blue-500"
                />

                {isSearching && (
                  <div className="flex items-center justify-center py-4">
                    <div className="w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                  </div>
                )}

                {searchedOrder && (
                  <div className="bg-gray-800 p-4 rounded-lg space-y-2">
                    <div className="flex items-center justify-between">
                      <span className="text-white font-medium">
                        Sipariş ID: {searchedOrder.orderId}
                      </span>
                      <Button
                        onClick={() => copyOrderId(searchedOrder.orderId)}
                        size="sm"
                        variant="outline"
                        className="h-8 px-2 border-gray-600 hover:bg-gray-600"
                      >
                        <Copy className="w-3 h-3" />
                      </Button>
                    </div>
                    <p className="text-gray-400">
                      Durum: <span className="text-blue-400">{searchedOrder.status}</span>
                    </p>
                    <p className="text-gray-400">
                      Miktar: <span className="text-white">{searchedOrder.quantity}</span>
                    </p>
                    <p className="text-gray-400">
                      Link: <span className="text-blue-400 break-all">{searchedOrder.link}</span>
                    </p>
                    <p className="text-gray-400 text-sm">
                      Oluşturulma: {new Date(searchedOrder.createdAt).toLocaleDateString('tr-TR')}
                    </p>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>
        )}
      </div>

      {/* Success Modal */}
      {showSuccessModal && createdOrder && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <Card className="w-full max-w-md bg-gray-700 border-gray-600">
            <CardContent className="p-8 space-y-6 text-center">
              <div className="text-green-400">
                <CheckCircle2 className="w-16 h-16 mx-auto mb-4" />
                <h2 className="text-2xl font-bold text-white mb-2">
                  Sipariş Oluşturuldu!
                </h2>
              </div>
              
              <div className="bg-gray-800 p-4 rounded-lg">
                <div className="flex items-center justify-between mb-2">
                  <span className="text-white font-medium">
                    Sipariş ID: {createdOrder.orderId}
                  </span>
                  <Button
                    onClick={() => copyOrderId(createdOrder.orderId)}
                    size="sm"
                    variant="outline"
                    className="h-8 px-2 border-gray-600 hover:bg-gray-600"
                  >
                    <Copy className="w-3 h-3" />
                  </Button>
                </div>
                <p className="text-gray-400 text-sm">
                  Sipariş durumunu takip etmek için bu ID'yi saklayın
                </p>
              </div>

              <p className="text-gray-400">
                {countdown} saniye sonra sipariş sorgulama sayfasına yönlendirileceksiniz...
              </p>
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  );
}