import { useState, useEffect } from "react";
import { Card, CardContent } from "@/components/ui/card";
import { CheckCircle, AlertCircle, Info, X } from "lucide-react";

export interface NotificationData {
  id: string;
  type: "success" | "error" | "info";
  message: string;
  duration?: number;
}

interface NotificationProps {
  notification: NotificationData;
  onClose: (id: string) => void;
}

export function Notification({ notification, onClose }: NotificationProps) {
  const { id, type, message, duration = 5000 } = notification;

  useEffect(() => {
    const timer = setTimeout(() => {
      onClose(id);
    }, duration);

    return () => clearTimeout(timer);
  }, [id, duration, onClose]);

  const iconMap = {
    success: <CheckCircle className="w-4 h-4" />,
    error: <AlertCircle className="w-4 h-4" />,
    info: <Info className="w-4 h-4" />,
  };

  const colorMap = {
    success: "bg-green-500/20 border-green-500 text-green-400",
    error: "bg-red-500/20 border-red-500 text-red-400",
    info: "bg-blue-500/20 border-blue-500 text-blue-400",
  };

  return (
    <Card className={`notification max-w-sm ${colorMap[type]}`}>
      <CardContent className="p-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-2">
            {iconMap[type]}
            <span className="text-sm font-medium">{message}</span>
          </div>
          <button
            onClick={() => onClose(id)}
            className="ml-2 hover:opacity-70 transition-opacity"
          >
            <X className="w-4 h-4" />
          </button>
        </div>
      </CardContent>
    </Card>
  );
}
