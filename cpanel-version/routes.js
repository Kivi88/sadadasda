const { validationResult, body } = require('express-validator');
const bcrypt = require('bcryptjs');
const { storage } = require('./storage');

// Utility functions
function generateRandomKey(prefix = "KIWIPAZARI") {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  let result = prefix + '-';
  for (let i = 0; i < 8; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return result;
}

function generateOrderId() {
  return '#' + Math.floor(Math.random() * 9000000 + 1000000).toString();
}

async function checkOrderStatus(order, api) {
  try {
    if (!api || !api.statusEndpoint || !order.externalOrderId) {
      return order.status;
    }

    const response = await fetch(`${api.statusEndpoint}?key=${api.apiKey}&order=${order.externalOrderId}`);
    if (!response.ok) {
      console.log(`Status check failed for order ${order.orderId}`);
      return order.status;
    }

    const data = await response.json();
    
    // Map external status to internal status
    if (data.status === 'Completed' || data.status === 'completed') {
      return 'completed';
    } else if (data.status === 'Processing' || data.status === 'processing') {
      return 'processing';
    } else if (data.status === 'Pending' || data.status === 'pending') {
      return 'pending';
    }
    
    return order.status;
  } catch (error) {
    console.error('Error checking order status:', error);
    return order.status;
  }
}

async function registerRoutes(app) {
  // Admin Authentication Routes
  app.post("/api/admin/login", [
    body('password').isLength({ min: 1, max: 100 }).trim()
  ], async (req, res) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ message: "Geçersiz veri" });
      }

      const { password } = req.body;
      const adminPasswordHash = "ucFMkvJ5Tngq7QCN9Dl31edSWaPAmIRxfGwL62ih4U8jb0VosKHtO";

      if (password === adminPasswordHash) {
        req.session.isAdmin = true;
        res.json({ success: true, message: "Admin oturum başarıyla açıldı" });
      } else {
        res.status(401).json({ message: "Geçersiz şifre" });
      }
    } catch (error) {
      console.error("Admin login error:", error);
      res.status(500).json({ message: "Sunucu hatası" });
    }
  });

  app.post("/api/admin/logout", (req, res) => {
    req.session.destroy();
    res.json({ message: "Oturum sonlandırıldı" });
  });

  // Admin middleware
  const requireAdmin = (req, res, next) => {
    if (!req.session.isAdmin) {
      return res.status(401).json({ message: "Yetki gerekli" });
    }
    next();
  };

  // Stats Route
  app.get("/api/stats", requireAdmin, async (req, res) => {
    try {
      const [keys, apis, orders, services] = await Promise.all([
        storage.getKeys(),
        storage.getApis(),
        storage.getOrders(),
        storage.getServices()
      ]);

      const stats = {
        totalKeys: keys.length,
        activeApis: apis.filter(api => api.isActive).length,
        totalOrders: orders.length,
        totalServices: services.length
      };

      res.json(stats);
    } catch (error) {
      console.error("Error fetching stats:", error);
      res.status(500).json({ message: "İstatistikler getirilemedi" });
    }
  });

  // API Management Routes
  app.get("/api/apis", requireAdmin, async (req, res) => {
    try {
      const apis = await storage.getApis();
      res.json(apis);
    } catch (error) {
      console.error("Error fetching APIs:", error);
      res.status(500).json({ message: "API'ler getirilemedi" });
    }
  });

  app.post("/api/apis", [
    body('name').isLength({ min: 1, max: 100 }).trim().escape(),
    body('baseUrl').isURL().isLength({ max: 500 }),
    body('apiKey').isLength({ min: 1, max: 200 }).trim().escape(),
  ], requireAdmin, async (req, res) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ message: "Geçersiz veri" });
      }

      const api = await storage.createApi(req.body);
      res.status(201).json(api);
    } catch (error) {
      console.error("Error creating API:", error);
      res.status(500).json({ message: "API oluşturulamadı" });
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

  // Key Management Routes
  app.get("/api/keys", requireAdmin, async (req, res) => {
    try {
      const keys = await storage.getKeys();
      res.json(keys);
    } catch (error) {
      console.error("Error fetching keys:", error);
      res.status(500).json({ message: "Keyler getirilemedi" });
    }
  });

  app.post("/api/keys", [
    body('name').isLength({ min: 1, max: 100 }).trim().escape(),
    body('serviceId').isInt({ min: 1 }),
    body('count').isInt({ min: 1, max: 100 }),
    body('maxAmount').isInt({ min: 1, max: 1000000 }),
  ], requireAdmin, async (req, res) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ message: "Geçersiz veri" });
      }

      const { name, serviceId, count, maxAmount } = req.body;
      const keys = [];

      for (let i = 0; i < count; i++) {
        const keyValue = generateRandomKey();
        const key = await storage.createKey({
          keyValue,
          name,
          serviceId,
          maxAmount,
          usedAmount: 0,
          isActive: true
        });
        keys.push(key);
      }

      res.status(201).json(keys);
    } catch (error) {
      console.error("Error creating keys:", error);
      res.status(500).json({ message: "Keyler oluşturulamadı" });
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

      const service = await storage.getService(key.serviceId);
      
      if (!service || !service.isActive) {
        return res.status(400).json({ message: "Servis bulunamadı veya aktif değil" });
      }

      res.json({ key, service });
    } catch (error) {
      console.error("Error validating key:", error);
      res.status(500).json({ message: "Key doğrulanamadı" });
    }
  });

  // Key download route - servis adına göre tüm keyleri indir
  app.post("/api/keys/download", [
    body('serviceName').isLength({ min: 1, max: 500 }).trim().escape(),
  ], requireAdmin, async (req, res) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ message: "Geçersiz servis adı" });
      }

      const { serviceName } = req.body;
      
      // Önce servis adına göre servisi bul
      const allServices = await storage.getServices();
      const targetService = allServices.find(service => service.name === serviceName);
      
      if (!targetService) {
        return res.status(404).json({ message: "Bu servis adına sahip servis bulunamadı" });
      }
      
      // Servise ait tüm keyleri bul
      const allKeys = await storage.getKeys();
      const matchingKeys = allKeys.filter(key => key.serviceId === targetService.id);
      
      if (matchingKeys.length === 0) {
        return res.status(404).json({ message: "Bu servis için oluşturulmuş key bulunamadı" });
      }

      // TXT formatında hazırla - sadece key değerleri
      const txtContent = matchingKeys.map(key => key.keyValue).join('\n');

      // Dosya adı için servis adını temizle
      const cleanServiceName = serviceName.replace(/[^a-zA-Z0-9]/g, '_');
      
      res.setHeader('Content-Type', 'text/plain');
      res.setHeader('Content-Disposition', `attachment; filename="${cleanServiceName}_keys.txt"`);
      res.send(txtContent);
      
    } catch (error) {
      console.error("Error downloading keys:", error);
      res.status(500).json({ message: "Keyler indirilemedi" });
    }
  });

  // Order Management Routes
  app.get("/api/orders", requireAdmin, async (req, res) => {
    try {
      const orders = await storage.getOrders();
      res.json(orders);
    } catch (error) {
      console.error("Error fetching orders:", error);
      res.status(500).json({ message: "Siparişler getirilemedi" });
    }
  });

  app.post("/api/orders", [
    body('keyValue').isLength({ min: 1, max: 100 }).trim().escape(),
    body('serviceId').isInt({ min: 1 }),
    body('link').isURL().isLength({ max: 500 }),
    body('quantity').isInt({ min: 1, max: 1000000000 }),
  ], async (req, res) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ message: "Geçersiz veri" });
      }

      const { keyValue, link, quantity, serviceId } = req.body;

      // Validate key
      const key = await storage.getKeyByValue(keyValue);
      if (!key || !key.isActive) {
        return res.status(400).json({ message: "Geçersiz key" });
      }

      // Get service
      const service = await storage.getService(serviceId);
      if (!service || !service.isActive) {
        return res.status(400).json({ message: "Servis aktif değil" });
      }

      // Validate quantity
      const remainingAmount = (key.maxAmount || 1000) - (key.usedAmount || 0);
      if (quantity > remainingAmount) {
        return res.status(400).json({ 
          message: `Bu key için kalan miktar: ${remainingAmount}. Maksimum ${remainingAmount} adet sipariş verebilirsiniz.` 
        });
      }

      const orderId = generateOrderId();
      
      // Create order
      const order = await storage.createOrder({
        orderId,
        keyId: key.id,
        serviceId: service.id,
        link,
        quantity,
        status: 'processing',
        externalOrderId: null
      });

      // Update key usage
      await storage.updateKey(key.id, {
        usedAmount: (key.usedAmount || 0) + quantity,
        isActive: (key.usedAmount || 0) + quantity < (key.maxAmount || 1000)
      });

      res.status(201).json(order);
    } catch (error) {
      console.error("Error creating order:", error);
      res.status(500).json({ message: "Sipariş oluşturulamadı" });
    }
  });

  // Order search route
  app.get("/api/orders/search", async (req, res) => {
    try {
      const { orderId } = req.query;
      
      if (!orderId) {
        return res.status(400).json({ message: "Sipariş ID gerekli" });
      }

      // Remove # prefix if present
      const cleanOrderId = orderId.toString().replace(/^#/, '');
      const searchOrderId = cleanOrderId.startsWith('#') ? cleanOrderId : `#${cleanOrderId}`;
      
      const order = await storage.getOrderByOrderId(searchOrderId);
      
      if (!order) {
        return res.status(404).json({ message: "Sipariş bulunamadı" });
      }

      res.json(order);
    } catch (error) {
      console.error("Error searching order:", error);
      res.status(500).json({ message: "Sipariş aranırken hata oluştu" });
    }
  });

  console.log("✅ All routes registered successfully");
}

module.exports = { registerRoutes };