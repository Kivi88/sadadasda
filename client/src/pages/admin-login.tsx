import { useState } from "react";
import { useLocation } from "wouter";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Lock, Shield } from "lucide-react";
import { useToast } from "@/hooks/use-toast";

export default function AdminLogin() {
  const [password, setPassword] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [, setLocation] = useLocation();
  const { toast } = useToast();

  const handleLogin = async () => {
    if (!password.trim()) {
      toast({
        title: "Hata",
        description: "Lütfen şifrenizi girin",
        variant: "destructive",
      });
      return;
    }

    setIsLoading(true);

    try {
      const response = await fetch("/api/admin/login", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          username: "admin",
          password: password,
        }),
      });

      const data = await response.json();

      if (response.ok && data.success) {
        localStorage.setItem("admin_authenticated", "true");
        toast({
          title: "Başarılı",
          description: "Admin paneline hoş geldiniz",
        });
        setLocation("/admin/dashboard");
      } else {
        toast({
          title: "Hata",
          description: data.message || "Yanlış şifre",
          variant: "destructive",
        });
      }
    } catch (error) {
      toast({
        title: "Hata",
        description: "Bağlantı hatası",
        variant: "destructive",
      });
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-background flex items-center justify-center p-4">
      <div className="w-full max-w-md">
        <Card className="shadow-2xl border-border">
          <CardHeader className="text-center space-y-4">
            <div className="w-16 h-16 bg-primary/20 rounded-full flex items-center justify-center mx-auto">
              <Shield className="w-8 h-8 text-primary" />
            </div>
            <CardTitle className="text-2xl font-bold">Admin Paneli</CardTitle>
            <p className="text-muted-foreground">Yönetici girişi için şifrenizi girin</p>
          </CardHeader>
          <CardContent className="space-y-6">
            <div className="space-y-2">
              <Label htmlFor="password">Şifre</Label>
              <Input
                id="password"
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="Admin şifrenizi girin..."
                onKeyPress={(e) => {
                  if (e.key === "Enter") {
                    handleLogin();
                  }
                }}
              />
            </div>
            
            <Button
              onClick={handleLogin}
              disabled={isLoading}
              className="w-full btn-primary"
            >
              <Lock className="w-4 h-4 mr-2" />
              {isLoading ? "Giriş yapılıyor..." : "Giriş Yap"}
            </Button>

            <div className="text-center">
              <button
                onClick={() => setLocation("/")}
                className="text-sm text-muted-foreground hover:text-primary transition-colors"
              >
                ← Ana sayfaya dön
              </button>
            </div>

            <div className="bg-muted/50 rounded-lg p-3 text-center">
              <p className="text-xs text-muted-foreground">
                Şifre: <span className="font-mono">ucFMkvJ5Tngq7QCN9Dl31edSWaPAmIRxfGwL62ih4U8jb0VosKHtO</span>
              </p>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}