import { Button } from "@/components/ui/button";
import { Settings } from "lucide-react";
import { useLocation } from "wouter";
import KeyValidator from "@/components/client/key-validator";

export default function ClientPanel() {
  const [, setLocation] = useLocation();

  return (
    <div className="min-h-screen bg-background flex items-center justify-center p-4">
      <div className="w-full max-w-md">
        <Button 
          variant="ghost" 
          onClick={() => setLocation("/admin")}
          className="absolute top-4 right-4 text-muted-foreground hover:text-primary"
        >
          <Settings className="w-4 h-4 mr-2" />
          Admin
        </Button>
        
        <KeyValidator />
      </div>
    </div>
  );
}
