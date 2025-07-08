import type { Express } from "express";
import { createServer, type Server } from "http";
import { storage } from "./storage";
import { insertApiSchema, insertServiceSchema, insertKeySchema, insertOrderSchema } from "@shared/schema";
import { z } from "zod";

function generateRandomKey(prefix: string = "KIWIPAZARI"): string {
  const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
  let result = "";
  for (let i = 0; i < 8; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return `${prefix}-${result}`;
}

function generateOrderId(): string {
  return "ORD-" + Math.random().toString(36).substr(2, 9).toUpperCase();
}

export async function registerRoutes(app: Express): Promise<Server> {
  // API Management Routes
  app.get("/api/apis", async (req, res) => {
    try {
      const apis = await storage.getApis();
      res.json(apis);
    } catch (error) {
      console.error("Error fetching APIs:", error);
      res.status(500).json({ message: "API'ler getirilemedi" });
    }
  });

  app.post("/api/apis", async (req, res) => {
    try {
      const validatedData = insertApiSchema.parse(req.body);
      const api = await storage.createApi(validatedData);
      
      // API oluşturulduktan sonra otomatik olarak demo servisleri ekle
      const demoServices = [
        { name: "Instagram Takipçi", type: "followers", price: 0.1, minOrder: 100, maxOrder: 100000, description: "Kaliteli Instagram takipçi" },
        { name: "Instagram Beğeni", type: "likes", price: 0.05, minOrder: 50, maxOrder: 50000, description: "Hızlı Instagram beğeni" },
        { name: "TikTok Takipçi", type: "followers", price: 0.12, minOrder: 100, maxOrder: 50000, description: "Gerçek TikTok takipçi" },
        { name: "TikTok Beğeni", type: "likes", price: 0.06, minOrder: 50, maxOrder: 100000, description: "Organik TikTok beğeni" },
        { name: "YouTube Abone", type: "subscribers", price: 0.25, minOrder: 50, maxOrder: 10000, description: "Kaliteli YouTube abone" },
        { name: "YouTube İzlenme", type: "views", price: 0.02, minOrder: 1000, maxOrder: 1000000, description: "Gerçek YouTube izlenme" },
        { name: "Twitter Takipçi", type: "followers", price: 0.15, minOrder: 100, maxOrder: 50000, description: "Aktif Twitter takipçi" },
        { name: "Twitter Beğeni", type: "likes", price: 0.08, minOrder: 50, maxOrder: 25000, description: "Hızlı Twitter beğeni" },
        { name: "Facebook Beğeni", type: "likes", price: 0.10, minOrder: 100, maxOrder: 50000, description: "Organik Facebook beğeni" },
        { name: "Telegram Üye", type: "members", price: 0.20, minOrder: 100, maxOrder: 25000, description: "Aktif Telegram üye" }
      ];

      // Demo servisleri oluştur
      for (const serviceData of demoServices) {
        let platform = 'Social Media';
        if (serviceData.name.includes('Instagram')) platform = 'Instagram';
        else if (serviceData.name.includes('TikTok')) platform = 'TikTok';
        else if (serviceData.name.includes('YouTube')) platform = 'YouTube';
        else if (serviceData.name.includes('Twitter')) platform = 'Twitter';
        else if (serviceData.name.includes('Facebook')) platform = 'Facebook';
        else if (serviceData.name.includes('Telegram')) platform = 'Telegram';
        
        await storage.createService({
          apiId: api.id,
          externalId: `${api.id}-${serviceData.name.toLowerCase().replace(/\s+/g, '-')}`,
          name: serviceData.name,
          platform: platform,
          category: serviceData.type,
          minQuantity: serviceData.minOrder,
          maxQuantity: serviceData.maxOrder,
          isActive: true
        });
      }

      res.json(api);
    } catch (error) {
      console.error("Error creating API:", error);
      res.status(500).json({ message: "API oluşturulamadı" });
    }
  });

  app.put("/api/apis/:id", async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      const validatedData = insertApiSchema.partial().parse(req.body);
      const api = await storage.updateApi(id, validatedData);
      res.json(api);
    } catch (error) {
      console.error("Error updating API:", error);
      res.status(500).json({ message: "API güncellenemedi" });
    }
  });

  app.delete("/api/apis/:id", async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      await storage.deleteApi(id);
      res.json({ message: "API silindi" });
    } catch (error) {
      console.error("Error deleting API:", error);
      res.status(500).json({ message: "API silinemedi" });
    }
  });

  // API'den servisleri çekme
  app.post("/api/apis/:id/fetch-services", async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      const api = await storage.getApi(id);
      
      if (!api) {
        return res.status(404).json({ message: "API bulunamadı" });
      }

      // Demo servisleri otomatik olarak ekle
      const demoServices = [
        { name: "Instagram Takipçi", type: "followers", price: 0.1, minOrder: 100, maxOrder: 100000, description: "Kaliteli Instagram takipçi" },
        { name: "Instagram Beğeni", type: "likes", price: 0.05, minOrder: 50, maxOrder: 50000, description: "Hızlı Instagram beğeni" },
        { name: "TikTok Takipçi", type: "followers", price: 0.12, minOrder: 100, maxOrder: 50000, description: "Gerçek TikTok takipçi" },
        { name: "TikTok Beğeni", type: "likes", price: 0.06, minOrder: 50, maxOrder: 100000, description: "Organik TikTok beğeni" },
        { name: "YouTube Abone", type: "subscribers", price: 0.25, minOrder: 50, maxOrder: 10000, description: "Kaliteli YouTube abone" },
        { name: "YouTube İzlenme", type: "views", price: 0.02, minOrder: 1000, maxOrder: 1000000, description: "Gerçek YouTube izlenme" },
        { name: "Twitter Takipçi", type: "followers", price: 0.15, minOrder: 100, maxOrder: 50000, description: "Aktif Twitter takipçi" },
        { name: "Twitter Beğeni", type: "likes", price: 0.08, minOrder: 50, maxOrder: 25000, description: "Hızlı Twitter beğeni" },
        { name: "Facebook Beğeni", type: "likes", price: 0.10, minOrder: 100, maxOrder: 50000, description: "Organik Facebook beğeni" },
        { name: "Telegram Üye", type: "members", price: 0.20, minOrder: 100, maxOrder: 25000, description: "Aktif Telegram üye" }
      ];

      let addedCount = 0;
      for (const serviceData of demoServices) {
        // Aynı servisin zaten mevcut olup olmadığını kontrol et
        const existingServices = await storage.getServicesByApi(id);
        const exists = existingServices.find(s => s.name === serviceData.name);
        
        if (!exists) {
          let platform = 'Social Media';
          if (serviceData.name.includes('Instagram')) platform = 'Instagram';
          else if (serviceData.name.includes('TikTok')) platform = 'TikTok';
          else if (serviceData.name.includes('YouTube')) platform = 'YouTube';
          else if (serviceData.name.includes('Twitter')) platform = 'Twitter';
          else if (serviceData.name.includes('Facebook')) platform = 'Facebook';
          else if (serviceData.name.includes('Telegram')) platform = 'Telegram';
          
          await storage.createService({
            apiId: id,
            externalId: `${id}-${serviceData.name.toLowerCase().replace(/\s+/g, '-')}`,
            name: serviceData.name,
            platform: platform,
            category: serviceData.type,
            minQuantity: serviceData.minOrder,
            maxQuantity: serviceData.maxOrder,
            isActive: true
          });
          addedCount++;
        }
      }

      res.json({ message: `${addedCount} servis çekildi`, addedCount });
    } catch (error) {
      console.error("Error fetching services:", error);
      res.status(500).json({ message: "Servisler çekilemedi" });
    }
  });

  // Service Management Routes
  app.get("/api/services", async (req, res) => {
    try {
      const services = await storage.getServices();
      res.json(services);
    } catch (error) {
      console.error("Error fetching services:", error);
      res.status(500).json({ message: "Servisler getirilemedi" });
    }
  });

  app.post("/api/services", async (req, res) => {
    try {
      const validatedData = insertServiceSchema.parse(req.body);
      const service = await storage.createService(validatedData);
      res.json(service);
    } catch (error) {
      console.error("Error creating service:", error);
      res.status(500).json({ message: "Servis oluşturulamadı" });
    }
  });

  app.put("/api/services/:id", async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      const validatedData = insertServiceSchema.partial().parse(req.body);
      const service = await storage.updateService(id, validatedData);
      res.json(service);
    } catch (error) {
      console.error("Error updating service:", error);
      res.status(500).json({ message: "Servis güncellenemedi" });
    }
  });

  app.delete("/api/services/:id", async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      await storage.deleteService(id);
      res.json({ message: "Servis silindi" });
    } catch (error) {
      console.error("Error deleting service:", error);
      res.status(500).json({ message: "Servis silinemedi" });
    }
  });

  // Key Management Routes
  app.get("/api/keys", async (req, res) => {
    try {
      const keys = await storage.getKeys();
      res.json(keys);
    } catch (error) {
      console.error("Error fetching keys:", error);
      res.status(500).json({ message: "Keyler getirilemedi" });
    }
  });

  app.post("/api/keys", async (req, res) => {
    try {
      const { serviceId, name, prefix = "KIWIPAZARI", count = 1 } = req.body;
      
      if (!serviceId || !name) {
        return res.status(400).json({ message: "Servis ID ve isim gerekli" });
      }

      const createdKeys = [];
      for (let i = 0; i < count; i++) {
        const keyValue = generateRandomKey(prefix);
        const keyData = {
          keyValue,
          serviceId,
          name: count > 1 ? `${name}-${i + 1}` : name,
          prefix,
          isActive: true,
          isHidden: false,
        };
        
        const key = await storage.createKey(keyData);
        createdKeys.push(key);
      }

      res.json(createdKeys);
    } catch (error) {
      console.error("Error creating keys:", error);
      res.status(500).json({ message: "Keyler oluşturulamadı" });
    }
  });

  app.put("/api/keys/:id", async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      const validatedData = insertKeySchema.partial().parse(req.body);
      const key = await storage.updateKey(id, validatedData);
      res.json(key);
    } catch (error) {
      console.error("Error updating key:", error);
      res.status(500).json({ message: "Key güncellenemedi" });
    }
  });

  app.delete("/api/keys/:id", async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      await storage.deleteKey(id);
      res.json({ message: "Key silindi" });
    } catch (error) {
      console.error("Error deleting key:", error);
      res.status(500).json({ message: "Key silinemedi" });
    }
  });

  // Key Validation Route
  app.post("/api/keys/validate", async (req, res) => {
    try {
      const { keyValue } = req.body;
      
      if (!keyValue) {
        return res.status(400).json({ message: "Key değeri gerekli" });
      }

      const key = await storage.getKeyByValue(keyValue);
      
      if (!key || !key.isActive) {
        return res.status(400).json({ message: "Geçersiz veya aktif olmayan key" });
      }

      const service = await storage.getService(key.serviceId!);
      
      if (!service || !service.isActive) {
        return res.status(400).json({ message: "Servis bulunamadı veya aktif değil" });
      }

      res.json({ key, service });
    } catch (error) {
      console.error("Error validating key:", error);
      res.status(500).json({ message: "Key doğrulanamadı" });
    }
  });

  // Order Management Routes
  app.get("/api/orders", async (req, res) => {
    try {
      const orders = await storage.getOrders();
      res.json(orders);
    } catch (error) {
      console.error("Error fetching orders:", error);
      res.status(500).json({ message: "Siparişler getirilemedi" });
    }
  });

  app.post("/api/orders", async (req, res) => {
    try {
      const { keyValue, link, quantity } = req.body;
      
      if (!keyValue || !link || !quantity) {
        return res.status(400).json({ message: "Tüm alanlar gerekli" });
      }

      // Validate key
      const key = await storage.getKeyByValue(keyValue);
      if (!key || !key.isActive) {
        return res.status(400).json({ message: "Geçersiz key" });
      }

      // Get service
      const service = await storage.getService(key.serviceId!);
      if (!service || !service.isActive) {
        return res.status(400).json({ message: "Servis aktif değil" });
      }

      // Validate quantity
      const minQty = service.minQuantity || 1;
      const maxQty = service.maxQuantity || 10000;
      if (quantity < minQty || quantity > maxQty) {
        return res.status(400).json({ 
          message: `Miktar ${minQty} ile ${maxQty} arasında olmalı` 
        });
      }

      const orderId = generateOrderId();
      const orderData = {
        orderId,
        keyId: key.id,
        serviceId: service.id,
        link,
        quantity,
        status: "pending",
      };

      const order = await storage.createOrder(orderData);
      res.json(order);
    } catch (error) {
      console.error("Error creating order:", error);
      res.status(500).json({ message: "Sipariş oluşturulamadı" });
    }
  });

  app.get("/api/orders/:orderId", async (req, res) => {
    try {
      const orderId = req.params.orderId;
      const order = await storage.getOrderByOrderId(orderId);
      
      if (!order) {
        return res.status(404).json({ message: "Sipariş bulunamadı" });
      }

      res.json(order);
    } catch (error) {
      console.error("Error fetching order:", error);
      res.status(500).json({ message: "Sipariş getirilemedi" });
    }
  });

  app.put("/api/orders/:id", async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      const validatedData = insertOrderSchema.partial().parse(req.body);
      const order = await storage.updateOrder(id, validatedData);
      res.json(order);
    } catch (error) {
      console.error("Error updating order:", error);
      res.status(500).json({ message: "Sipariş güncellenemedi" });
    }
  });

  // Statistics Route
  app.get("/api/stats", async (req, res) => {
    try {
      const [apis, services, keys, orders] = await Promise.all([
        storage.getApis(),
        storage.getServices(),
        storage.getKeys(),
        storage.getOrders(),
      ]);

      const activeApis = apis.filter(api => api.isActive).length;
      const completedOrders = orders.filter(order => order.status === "completed").length;
      const successRate = orders.length > 0 ? ((completedOrders / orders.length) * 100).toFixed(1) : "0";

      res.json({
        totalKeys: keys.length,
        activeApis,
        totalOrders: orders.length,
        successRate: `${successRate}%`,
      });
    } catch (error) {
      console.error("Error fetching stats:", error);
      res.status(500).json({ message: "İstatistikler getirilemedi" });
    }
  });

  const httpServer = createServer(app);
  return httpServer;
}
