import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Settings } from "lucide-react";
import { useLocation } from "wouter";
import KeyValidator from "@/components/client/key-validator";
import OrderSearch from "@/components/client/order-search";

export default function ClientPanel() {
  const [, setLocation] = useLocation();

  return (
    <div className="min-h-screen bg-background">
      <div className="container mx-auto px-4 py-8">
        <div className="mb-6 text-center">
          <Button 
            variant="ghost" 
            onClick={() => setLocation("/admin")}
            className="absolute top-4 right-4 text-muted-foreground hover:text-primary"
          >
            <Settings className="w-4 h-4 mr-2" />
            Admin
          </Button>
        </div>

        <div className="max-w-md mx-auto space-y-6">
          <KeyValidator />
          <OrderSearch />
        </div>
      </div>
    </div>
  );
}
