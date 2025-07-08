import { Card, CardContent } from "@/components/ui/card";

interface StatsCardProps {
  title: string;
  value: string;
  icon: React.ReactNode;
  bgColor?: string;
}

export default function StatsCard({ title, value, icon, bgColor = "bg-primary/20" }: StatsCardProps) {
  return (
    <div className="modern-card p-6 group cursor-pointer transition-all duration-300">
      <div className="flex items-center justify-between">
        <div className="flex-1">
          <p className="text-sm text-muted-foreground font-semibold mb-2 tracking-wide uppercase">
            {title}
          </p>
          <p className="text-4xl font-bold bg-gradient-to-r from-primary to-accent bg-clip-text text-transparent group-hover:from-accent group-hover:to-primary transition-all duration-300">
            {value}
          </p>
        </div>
        <div className="w-16 h-16 bg-gradient-to-br from-primary/20 to-accent/20 rounded-2xl flex items-center justify-center group-hover:from-primary/30 group-hover:to-accent/30 transition-all duration-300 border border-primary/20">
          <div className="text-primary group-hover:scale-110 transition-transform duration-300">
            {icon}
          </div>
        </div>
      </div>
      <div className="mt-4 pt-4 border-t border-border/50">
        <div className="flex items-center text-xs text-muted-foreground">
          <div className="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></div>
          <span>Anlık veri</span>
        </div>
      </div>
    </div>
  );
}
