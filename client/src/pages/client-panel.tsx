import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { ArrowLeft } from "lucide-react";
import { Link } from "wouter";
import KeyValidator from "@/components/client/key-validator";
import OrderSearch from "@/components/client/order-search";

export default function ClientPanel() {
  return (
    <div className="min-h-screen bg-background">
      <div className="container mx-auto px-4 py-8">
        <div className="mb-6">
          <Link href="/admin">
            <Button variant="ghost" className="mb-4">
              <ArrowLeft className="w-4 h-4 mr-2" />
              Admin Paneline Dön
            </Button>
          </Link>
        </div>

        <div className="max-w-md mx-auto space-y-6">
          <KeyValidator />
          <OrderSearch />
        </div>
      </div>
    </div>
  );
}
