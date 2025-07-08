import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent } from "@/components/ui/card";
import { CheckCircle2 } from "lucide-react";

export default function Home() {
  const [keyValue, setKeyValue] = useState("");
  const [isVerifying, setIsVerifying] = useState(false);

  const handleVerify = async () => {
    if (!keyValue.trim()) return;
    
    setIsVerifying(true);
    
    // Simulate verification process
    setTimeout(() => {
      setIsVerifying(false);
      // You can add verification logic here
    }, 1000);
  };

  return (
    <div className="min-h-screen bg-gray-800 flex items-center justify-center p-4">
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

          {/* Key Input */}
          <div className="space-y-4">
            <div className="space-y-2">
              <Input
                type="text"
                placeholder="Ürün Anahtarı"
                value={keyValue}
                onChange={(e) => setKeyValue(e.target.value)}
                className="bg-gray-800 border-gray-600 text-white placeholder-gray-400 focus:border-blue-500"
              />
            </div>

            {/* Verify Button */}
            <Button
              onClick={handleVerify}
              disabled={!keyValue.trim() || isVerifying}
              className="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3"
            >
              {isVerifying ? (
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
        </CardContent>
      </Card>
    </div>
  );
}