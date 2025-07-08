import { Card, CardContent } from "@/components/ui/card";

interface StatsCardProps {
  title: string;
  value: string;
  icon: React.ReactNode;
  bgColor?: string;
}

export default function StatsCard({ title, value, icon, bgColor = "bg-primary/20" }: StatsCardProps) {
  return (
    <Card className="stats-card">
      <CardContent className="p-6">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm font-medium text-muted-foreground">{title}</p>
            <p className="text-2xl font-bold text-foreground">{value}</p>
          </div>
          <div className={`stats-icon ${bgColor}`}>
            {icon}
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
