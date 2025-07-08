import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { ScrollArea } from "@/components/ui/scroll-area";
import { 
  TrendingUp,
  Key, 
  Plug, 
  Settings, 
  ShoppingCart, 
  Users,
  Menu,
  X
} from "lucide-react";

interface SidebarProps {
  activeTab: string;
  onTabChange: (tab: string) => void;
  className?: string;
  collapsed?: boolean;
  onToggle?: () => void;
}

interface NavItem {
  id: string;
  label: string;
  icon: React.ComponentType<{ className?: string }>;
  href?: string;
}

const navItems: NavItem[] = [
  {
    id: "dashboard",
    label: "Dashboard",
    icon: TrendingUp,
  },
  {
    id: "keys",
    label: "Key Yönetimi",
    icon: Key,
  },
  {
    id: "apis",
    label: "API Yönetimi",
    icon: Plug,
  },
  {
    id: "services",
    label: "Servis Yönetimi",
    icon: Settings,
  },
  {
    id: "orders",
    label: "Siparişler",
    icon: ShoppingCart,
  },
];

export function Sidebar({ 
  activeTab, 
  onTabChange, 
  className,
  collapsed = false,
  onToggle 
}: SidebarProps) {
  return (
    <div className={cn(
      "flex flex-col h-full bg-card border-r border-border transition-all duration-300",
      collapsed ? "w-16" : "w-64",
      className
    )}>
      {/* Header */}
      <div className="flex items-center justify-between h-16 px-4 border-b border-border">
        {!collapsed && (
          <h1 className="text-xl font-bold text-foreground">SMM Panel</h1>
        )}
        {onToggle && (
          <Button
            variant="ghost"
            size="sm"
            onClick={onToggle}
            className="h-8 w-8 p-0"
          >
            {collapsed ? (
              <Menu className="h-4 w-4" />
            ) : (
              <X className="h-4 w-4" />
            )}
          </Button>
        )}
      </div>

      {/* Navigation */}
      <ScrollArea className="flex-1 py-6">
        <nav className="px-4 space-y-2">
          {navItems.map((item) => {
            const Icon = item.icon;
            const isActive = activeTab === item.id;
            
            return (
              <Button
                key={item.id}
                variant="ghost"
                onClick={() => onTabChange(item.id)}
                className={cn(
                  "w-full justify-start gap-3 h-10 px-3",
                  "text-muted-foreground hover:text-foreground hover:bg-accent/50",
                  "transition-colors duration-200",
                  isActive && "bg-primary/10 text-primary hover:bg-primary/15 hover:text-primary",
                  collapsed && "justify-center px-0"
                )}
              >
                <Icon className="h-4 w-4 flex-shrink-0" />
                {!collapsed && (
                  <span className="text-sm font-medium">{item.label}</span>
                )}
              </Button>
            );
          })}
          
          {/* Client Panel Link */}
          <Button
            variant="ghost"
            onClick={() => window.open("/client", "_blank")}
            className={cn(
              "w-full justify-start gap-3 h-10 px-3 mt-4",
              "text-muted-foreground hover:text-foreground hover:bg-accent/50",
              "transition-colors duration-200 border-t border-border pt-4",
              collapsed && "justify-center px-0"
            )}
          >
            <Users className="h-4 w-4 flex-shrink-0" />
            {!collapsed && (
              <span className="text-sm font-medium">Müşteri Paneli</span>
            )}
          </Button>
        </nav>
      </ScrollArea>

      {/* Footer */}
      {!collapsed && (
        <div className="p-4 border-t border-border">
          <div className="flex items-center gap-3">
            <div className="w-8 h-8 bg-muted rounded-full flex items-center justify-center">
              <Users className="w-4 h-4" />
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-foreground truncate">
                Admin
              </p>
              <p className="text-xs text-muted-foreground truncate">
                Yönetici Panel
              </p>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default Sidebar;
