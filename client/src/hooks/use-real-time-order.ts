import { useEffect, useRef } from "react";
import { useQueryClient } from "@tanstack/react-query";
import type { Order } from "@shared/schema";

export function useRealTimeOrder(orderId: string | null, intervalMs: number = 5000) {
  const queryClient = useQueryClient();
  const intervalRef = useRef<NodeJS.Timeout | null>(null);

  useEffect(() => {
    if (!orderId) return;

    // Function to refresh order data
    const refreshOrder = async () => {
      try {
        const response = await fetch(`/api/orders/search?orderId=${encodeURIComponent(orderId)}`);
        if (response.ok) {
          const order: Order = await response.json();
          // Update the query cache
          queryClient.setQueryData(['order', orderId], order);
        }
      } catch (error) {
        console.error('Failed to refresh order:', error);
      }
    };

    // Start polling
    intervalRef.current = setInterval(refreshOrder, intervalMs);

    // Cleanup function
    return () => {
      if (intervalRef.current) {
        clearInterval(intervalRef.current);
        intervalRef.current = null;
      }
    };
  }, [orderId, intervalMs, queryClient]);

  // Stop polling function
  const stopPolling = () => {
    if (intervalRef.current) {
      clearInterval(intervalRef.current);
      intervalRef.current = null;
    }
  };

  return { stopPolling };
}