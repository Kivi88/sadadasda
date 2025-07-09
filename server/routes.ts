import type { Express } from "express";
import { createServer, type Server } from "http";
import { storage } from "./storage";
import { insertApiSchema, insertServiceSchema, insertKeySchema, insertOrderSchema } from "@shared/schema";
import { z } from "zod";
import rateLimit from "express-rate-limit";
import { body, validationResult } from "express-validator";
import bcrypt from "bcryptjs";

// Admin credentials with hashed password
const ADMIN_USERNAME = "admin";
const ADMIN_PASSWORD_HASH = "$2b$10$rYc25.W8WPXfRyn2rNeMK.quvLbz.8UehyGWISBk9yZY79.1KyvUa"; // ucFMkvJ5Tngq7QCN9Dl31edSWaPAmIRxfGwL62ih4U8jb0VosKHtO

// Rate limiting for admin login
const adminLoginLimiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 5, // limit each IP to 5 requests per windowMs
  message: "Too many login attempts, please try again later.",
  standardHeaders: true,
  legacyHeaders: false,
});

function generateRandomKey(prefix: string = "KIWIPAZARI"): string {
  const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
  let result = "";
  for (let i = 0; i < 8; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return `${prefix}-${result}`;
}

function generateOrderId(): string {
  return "#" + Math.floor(1000000 + Math.random() * 9000000).toString();
}

async function checkOrderStatus(order: any, api: any) {
  console.log(`📡 Checking real-time status for order ${order.orderId} (External ID: ${order.externalOrderId})`);
  console.log(`📡 API URL: ${api.url}, API Key: ${api.apiKey ? 'Present' : 'Missing'}`);
  
  const statusUrl = `${api.url}?action=status&key=${api.apiKey}&order=${order.externalOrderId}`;
  console.log(`📡 Status URL: ${statusUrl}`);
  
  const statusResponse = await fetch(statusUrl);
  console.log(`📡 Status Response Status: ${statusResponse.status}`);
  
  if (statusResponse.ok) {
    const statusData = await statusResponse.json();
    console.log(`📊 Status response:`, statusData);
    
    // Map external status to our internal status
    let newStatus = order.status;
    if (statusData.status === "Completed" || statusData.status === "completed" || statusData.status === "Complete") {
      newStatus = "completed";
    } else if (statusData.status === "In progress" || statusData.status === "Processing" || statusData.status === "processing") {
      newStatus = "processing";
    } else if (statusData.status === "Pending" || statusData.status === "pending" || statusData.status === "Waiting") {
      newStatus = "pending";
    } else if (statusData.status === "Cancelled" || statusData.status === "cancelled" || statusData.status === "Canceled") {
      newStatus = "cancelled";
    }
    
    return newStatus;
  } else {
    console.log(`❌ Status check failed: ${statusResponse.status} ${statusResponse.statusText}`);
    return order.status;
  }
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
      
      // Önce bu API'ye bağlı servisleri sil
      const services = await storage.getServicesByApi(id);
      for (const service of services) {
        await storage.deleteService(service.id);
      }
      
      // Sonra API'yi sil
      await storage.deleteApi(id);
      res.json({ message: "API ve bağlı servisler silindi" });
    } catch (error) {
      console.error("Error deleting API:", error);
      res.status(500).json({ message: "API silinemedi" });
    }
  });

  // Clear all services for an API
  app.delete("/api/apis/:id/services", async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      const services = await storage.getServicesByApi(id);
      
      console.log(`🗑️ API ${id} için ${services.length} servis siliniyor...`);
      
      for (const service of services) {
        await storage.deleteService(service.id);
      }
      
      console.log(`✅ ${services.length} servis başarıyla silindi`);
      
      res.json({ 
        message: `${services.length} servis silindi`,
        deletedCount: services.length 
      });
    } catch (error) {
      console.error("Error clearing services:", error);
      res.status(500).json({ message: "Servisler silinemedi" });
    }
  });

  // API'den servisleri çekme
  app.post("/api/apis/:id/fetch-services", async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      const { limit } = req.body; // Limit parametresi al
      const api = await storage.getApi(id);
      
      if (!api) {
        return res.status(404).json({ message: "API bulunamadı" });
      }

      // Eğer MedyaBayim API'si ise ve environment variable varsa onu kullan
      let apiKey = api.apiKey;
      if (api.name.toLowerCase().includes('medya') && process.env.MEDYABAYIM_API_KEY) {
        apiKey = process.env.MEDYABAYIM_API_KEY;
        console.log('MedyaBayim API key from environment variable loaded');
      } else {
        console.log('Using API key from database:', apiKey);
      }

      console.log(`API ${api.name} için servis çekme başlatıldı. Limit: ${limit || 'sınırsız'}`);

      // MedyaBayim API için doğru endpoint'leri kullan
      const possibleEndpoints = [
        `${api.url}/services`,
        `${api.url}`,
        `${api.url}/service`
      ];
      
      let apiResponse = null;
      let lastError = null;
      
      for (const endpoint of possibleEndpoints) {
        try {
          console.log(`Denenen endpoint: ${endpoint}`);
          
          // Farklı request formatlarını dene
          const requestFormats = [
            // POST ile action parametresi
            {
              method: "POST",
              headers: { "Content-Type": "application/json", "User-Agent": "KiwiPazari/1.0" },
              body: JSON.stringify({ key: apiKey, action: "services" })
            },
            // POST sadece key ile
            {
              method: "POST", 
              headers: { "Content-Type": "application/json", "User-Agent": "KiwiPazari/1.0" },
              body: JSON.stringify({ key: apiKey })
            },
            // GET ile query parametreleri
            {
              method: "GET",
              headers: { "User-Agent": "KiwiPazari/1.0" },
              url: `${endpoint}?key=${apiKey}&action=services`
            },
            // GET sadece key ile
            {
              method: "GET",
              headers: { "User-Agent": "KiwiPazari/1.0" },
              url: `${endpoint}?key=${apiKey}`
            }
          ];
          
          for (const format of requestFormats) {
            const requestUrl = format.url || endpoint;
            const requestOptions = {
              method: format.method,
              headers: format.headers,
              ...(format.body && { body: format.body })
            };
            
            try {
              const response = await fetch(requestUrl, requestOptions);
              console.log(`${format.method} ${requestUrl} - ${response.status}`);
              
              if (response.ok) {
                const text = await response.text();
                console.log(`Başarılı! Response uzunluk: ${text.length} karakter`);
                
                try {
                  // JSON parse dene
                  const data = JSON.parse(text);
                  apiResponse = response;
                  // Response'u tekrar kullanabilmek için mock response object oluştur
                  apiResponse.json = () => Promise.resolve(data);
                  console.log(`Başarılı endpoint: ${requestUrl} (${format.method})`);
                  break;
                } catch (parseError) {
                  console.log(`JSON parse hatası: ${parseError.message}`);
                  continue;
                }
              }
            } catch (fetchError) {
              console.log(`Fetch hata: ${fetchError.message}`);
              continue;
            }
          }
          
          if (apiResponse && apiResponse.ok) {
            break;
          }
          
        } catch (error) {
          console.log(`Hata endpoint: ${endpoint} - ${error.message}`);
          lastError = error;
        }
      }

      if (!apiResponse || !apiResponse.ok) {
        console.log("Gerçek API'den veri çekilemiyor. Hata:", lastError?.message || "Bilinmeyen hata");
        
        return res.status(500).json({ 
          message: "API'den servis çekilemedi: " + (lastError?.message || "Bilinmeyen hata"),
          error: lastError?.message || "API bağlantı hatası"
        });
      }

      const servicesData = await apiResponse.json();
      console.log("API yanıt tipi:", typeof servicesData);
      console.log("API yanıt array mi:", Array.isArray(servicesData));
      console.log("API yanıt ilk 3 anahtar:", Object.keys(servicesData).slice(0, 3));
      
      let services = servicesData;
      
      // Farklı API formatlarını kontrol et
      if (!Array.isArray(servicesData)) {
        if (servicesData.services && Array.isArray(servicesData.services)) {
          services = servicesData.services;
          console.log("API yanıt 'services' anahtarında array bulundu");
        } else if (servicesData.data && Array.isArray(servicesData.data)) {
          services = servicesData.data;
          console.log("API yanıt 'data' anahtarında array bulundu");
        } else if (servicesData.result && Array.isArray(servicesData.result)) {
          services = servicesData.result;
          console.log("API yanıt 'result' anahtarında array bulundu");
        } else {
          console.log("API yanıt yapısı:", JSON.stringify(servicesData).substring(0, 500) + "...");
          throw new Error("API geçersiz veri formatı döndürdü - servis listesi bulunamadı");
        }
      }

      let addedCount = 0;
      let processedCount = 0;
      let skippedCount = 0;
      const totalServices = services.length;
      const existingServices = await storage.getServicesByApi(id);
      
      console.log(`API'den ${totalServices} servis alındı, işleme başlanıyor...`);
      console.log(`API ${id} için mevcut servis sayısı: ${existingServices.length}`);
      
      // Limit uygula
      const servicesToProcess = limit && limit > 0 ? services.slice(0, limit) : services;
      const limitedTotal = servicesToProcess.length;
      
      if (limit && limit > 0) {
        console.log(`Limit uygulandı: ${limitedTotal} servis işlenecek (toplam ${totalServices} servis var)`);
      }
      
      // Paralel batch işleme için servisleri gruplara ayır
      const BATCH_SIZE = 50; // Her batch'te 50 servis
      const PARALLEL_BATCHES = 4; // Aynı anda 4 batch işle
      
      const batches = [];
      for (let i = 0; i < servicesToProcess.length; i += BATCH_SIZE) {
        batches.push(servicesToProcess.slice(i, i + BATCH_SIZE));
      }
      
      console.log(`${batches.length} batch oluşturuldu (batch başına ${BATCH_SIZE} servis)`);
      
      // Batch'leri paralel olarak işle
      for (let i = 0; i < batches.length; i += PARALLEL_BATCHES) {
        const currentBatches = batches.slice(i, i + PARALLEL_BATCHES);
        
        const batchPromises = currentBatches.map(async (batch, batchIndex) => {
          const servicesToCreate = [];
          
          for (const serviceData of batch) {
            processedCount++;
            
            try {
              // Servis ID'sini farklı alanlardan al
              const serviceId = serviceData.service?.toString() || 
                               serviceData.id?.toString() || 
                               serviceData.serviceId?.toString() ||
                               serviceData.service_id?.toString();
              
              if (!serviceId) {
                console.log(`⚠️  Service ID bulunamadı, servis atlanıyor:`, JSON.stringify(serviceData).substring(0, 200));
                continue; // Service ID yoksa atla
              }
              
              const exists = existingServices.find(s => s.externalId === serviceId);
              
              if (!exists) {
                console.log(`✅ Yeni servis: ${serviceId} - ${serviceData.name || 'İsimsiz'}`);
              } else {
                skippedCount++;
                console.log(`⏭️  Servis zaten mevcut: ${serviceId} - ${serviceData.name || 'İsimsiz'}`);
              }
              
              if (!exists) {
                // Platform belirle
                let platform = 'Social Media';
                const serviceName = serviceData.name || `Servis ${serviceId}`;
                
                if (serviceName.toLowerCase().includes('instagram')) platform = 'Instagram';
                else if (serviceName.toLowerCase().includes('tiktok')) platform = 'TikTok';
                else if (serviceName.toLowerCase().includes('youtube')) platform = 'YouTube';
                else if (serviceName.toLowerCase().includes('twitter')) platform = 'Twitter';
                else if (serviceName.toLowerCase().includes('facebook')) platform = 'Facebook';
                else if (serviceName.toLowerCase().includes('telegram')) platform = 'Telegram';
                else if (serviceName.toLowerCase().includes('linkedin')) platform = 'LinkedIn';
                else if (serviceName.toLowerCase().includes('snapchat')) platform = 'Snapchat';
                else if (serviceName.toLowerCase().includes('pinterest')) platform = 'Pinterest';
                else if (serviceName.toLowerCase().includes('reddit')) platform = 'Reddit';
                else if (serviceName.toLowerCase().includes('discord')) platform = 'Discord';
                else if (serviceName.toLowerCase().includes('twitch')) platform = 'Twitch';
                else if (serviceName.toLowerCase().includes('soundcloud')) platform = 'SoundCloud';
                else if (serviceName.toLowerCase().includes('spotify')) platform = 'Spotify';
                
                const serviceToCreate = {
                  apiId: id,
                  externalId: serviceId,
                  name: serviceName,
                  platform: platform,
                  category: serviceData.type || serviceData.category || 'Genel',
                  minQuantity: parseInt(serviceData.min) || 1,
                  maxQuantity: parseInt(serviceData.max) || 10000,
                  isActive: true
                };
                
                console.log(`📝 Servis oluşturulacak:`, serviceToCreate);
                servicesToCreate.push(serviceToCreate);
              }
              
            } catch (serviceError) {
              console.error(`Servis işlenirken hata (ID: ${serviceData.service || serviceData.id}):`, serviceError.message);
              continue; // Hata durumunda sonraki servise geç
            }
          }
          
          // Bulk insert ile tüm batch'i tek seferde ekle
          if (servicesToCreate.length > 0) {
            console.log(`🔄 Batch ${i + batchIndex}: ${servicesToCreate.length} servis veritabanına kaydediliyor...`);
            try {
              const createdServices = await storage.createServicesBulk(servicesToCreate);
              console.log(`✅ Batch ${i + batchIndex}: ${createdServices.length} servis başarıyla kaydedildi`);
              return createdServices.length;
            } catch (error) {
              console.error(`❌ Batch ${i + batchIndex} kaydedilirken hata:`, error);
              throw error;
            }
          }
          
          return 0;
        });
        
        // Batch'leri bekle ve sonuçları topla
        const batchResults = await Promise.all(batchPromises);
        const batchTotal = batchResults.reduce((sum, count) => sum + count, 0);
        addedCount += batchTotal;
        
        // Progress güncellemesi
        const processedBatches = Math.min(i + PARALLEL_BATCHES, batches.length);
        console.log(`İşlenen batch: ${processedBatches}/${batches.length} - Toplam eklenen: ${addedCount} (%${Math.round(processedCount/limitedTotal*100)})`);
      }
      
      console.log(`Tamamlandı: ${addedCount} yeni servis eklendi, ${skippedCount} servis atlandı (zaten var), toplam ${processedCount} servis işlendi.`);
      
      if (limit && limit > 0) {
        console.log(`Not: Limit nedeniyle sadece ${limitedTotal}/${totalServices} servis işlendi.`);
      }

      // API'nin son senkronizasyon zamanını güncelle
      await storage.updateApi(id, { lastSync: new Date() });

      res.json({ 
        message: `${addedCount} yeni servis eklendi${limit ? ` (${limitedTotal}/${totalServices} işlendi)` : ''}`, 
        addedCount,
        totalFromAPI: services.length,
        processedCount: limitedTotal,
        skippedCount: limit ? totalServices - limitedTotal : 0
      });
    } catch (error) {
      console.error("Error fetching services:", error);
      res.status(500).json({ 
        message: "Servisler çekilemedi: " + error.message 
      });
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
      const { serviceId, name, prefix = "KIWIPAZARI", count = 1, maxAmount = 1000 } = req.body;
      
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
          maxAmount,
          usedAmount: 0,
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

  // Key download route - keyName'e göre tüm keyleri indir
  app.post("/api/keys/download", [
    body('keyName').isLength({ min: 1, max: 100 }).trim().escape(),
  ], async (req, res) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ message: "Geçersiz key adı" });
      }

      const { keyName } = req.body;
      const allKeys = await storage.getKeys();
      
      // Aynı isme sahip keyleri filtrele
      const matchingKeys = allKeys.filter(key => key.name === keyName);
      
      if (matchingKeys.length === 0) {
        return res.status(404).json({ message: "Bu isimde key bulunamadı" });
      }

      // Key verilerini hazırla
      const keyData = matchingKeys.map(key => ({
        keyValue: key.keyValue,
        name: key.name,
        maxAmount: key.maxAmount,
        usedAmount: key.usedAmount,
        remainingAmount: (key.maxAmount || 1000) - (key.usedAmount || 0),
        isActive: key.isActive,
        createdAt: key.createdAt
      }));

      // TXT formatında hazırla - sadece key değerleri
      const txtContent = keyData.map(key => key.keyValue).join('\n');

      res.setHeader('Content-Type', 'text/plain');
      res.setHeader('Content-Disposition', `attachment; filename="${keyName}_keys.txt"`);
      res.send(txtContent);
      
    } catch (error) {
      console.error("Error downloading keys:", error);
      res.status(500).json({ message: "Keyler indirilemedi" });
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

      // Get service using the provided serviceId
      const service = await storage.getService(serviceId);
      if (!service || !service.isActive) {
        return res.status(400).json({ message: "Servis aktif değil" });
      }

      // Verify the key is valid for this service (optional additional validation)
      if (key.serviceId && key.serviceId !== serviceId) {
        return res.status(400).json({ message: "Bu key seçilen servis için geçerli değil" });
      }

      // Validate quantity
      const minQty = service.minQuantity || 1;
      const maxQty = service.maxQuantity || 10000;
      if (quantity < minQty || quantity > maxQty) {
        return res.status(400).json({ 
          message: `Miktar ${minQty} ile ${maxQty} arasında olmalı` 
        });
      }

      // Key miktar kontrolü
      const remainingAmount = (key.maxAmount || 1000) - (key.usedAmount || 0);
      if (quantity > remainingAmount) {
        return res.status(400).json({ 
          message: `Bu key için kalan miktar: ${remainingAmount}. Maksimum ${remainingAmount} adet sipariş verebilirsiniz.` 
        });
      }

      // Get API info
      const api = await storage.getApi(service.apiId!);
      if (!api) {
        return res.status(400).json({ message: "API bulunamadı" });
      }

      // API'ye sipariş gönder
      let externalOrderId = null;
      let orderStatus = "processing";
      
      try {
        // Gerçek API'ye sipariş gönder
        const orderData = {
          key: api.apiKey,
          action: 'add',
          service: service.externalId,
          link: link,
          quantity: quantity
        };

        console.log(`🚀 API'ye sipariş gönderiliyor:`, orderData);

        const response = await fetch(`${api.url}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(orderData),
        });

        const responseData = await response.json();
        console.log(`📦 API yanıtı:`, responseData);

        if (response.ok && responseData.order) {
          externalOrderId = responseData.order.toString();
          orderStatus = "processing";
          console.log(`✅ Sipariş API'ye başarıyla gönderildi! External ID: ${externalOrderId}`);
        } else {
          console.error("❌ API sipariş hatası:", responseData);
          orderStatus = "failed";
          externalOrderId = null;
        }
        
      } catch (error) {
        console.error("❌ API bağlantı hatası:", error);
        orderStatus = "failed";
        externalOrderId = null;
      }

      // Key'in kullanılan miktarını güncelle ve tek kullanımlıksa deaktive et
      const newUsedAmount = (key.usedAmount || 0) + quantity;
      const shouldDeactivate = newUsedAmount >= (key.maxAmount || 1000);
      
      await storage.updateKey(key.id, {
        usedAmount: newUsedAmount,
        isActive: !shouldDeactivate // Eğer maksimum miktara ulaştıysa key'i deaktive et
      });
      
      if (shouldDeactivate) {
        console.log(`🔒 Key deaktive edildi - maksimum kullanım miktarına ulaşıldı`);
      }
      
      console.log(`✅ Key kullanım miktarı güncellendi. Kullanılan: ${newUsedAmount}`);
      
      // Eğer API'ye sipariş gönderilememişse hata dön
      if (!externalOrderId) {
        return res.status(500).json({ 
          message: "Sipariş oluşturulamadı - API'ye bağlanılamadı" 
        });
      }

      const orderId = generateOrderId();
      const orderData = {
        orderId,
        keyId: key.id,
        serviceId: service.id,
        link,
        quantity,
        status: orderStatus,
        externalOrderId,
      };

      const order = await storage.createOrder(orderData);
      res.json(order);
    } catch (error) {
      console.error("Error creating order:", error);
      res.status(500).json({ message: "Sipariş oluşturulamadı" });
    }
  });

  // Order Search Route (must be before parameterized route)
  app.get("/api/orders/search", [
    body('orderId').optional().isLength({ min: 1, max: 50 }).trim().escape(),
  ], async (req, res) => {
    try {
      const { orderId } = req.query;
      console.log(`🔍 Search request received for: "${orderId}"`);
      
      if (!orderId) {
        return res.status(400).json({ message: "Sipariş ID gerekli" });
      }

      // Sanitize input to prevent XSS
      const searchId = orderId.toString().replace(/[<>\"'&]/g, '');
      console.log(`🔍 Sipariş aranıyor: "${searchId}"`);
      
      // If the search ID doesn't start with #, add it
      const fullOrderId = searchId.startsWith('#') ? searchId : `#${searchId}`;
      console.log(`🔍 Tam sipariş ID: "${fullOrderId}"`);
      
      const order = await storage.getOrderByOrderId(fullOrderId);
      
      if (!order) {
        console.log(`❌ Sipariş bulunamadı: "${fullOrderId}"`);
        return res.status(404).json({ message: "Sipariş bulunamadı" });
      }

      // Check real-time status from external API if we have externalOrderId
      if (order.externalOrderId && order.service) {
        try {
          const api = await storage.getApi(order.service.apiId);
          if (api && api.isActive) {
            console.log(`📡 Checking real-time status for order ${order.orderId} (External ID: ${order.externalOrderId})`);
            console.log(`📡 API URL: ${api.url}, API Key: ${api.apiKey ? 'Present' : 'Missing'}`);
            
            const statusUrl = `${api.url}?action=status&key=${api.apiKey}&order=${order.externalOrderId}`;
            console.log(`📡 Status URL: ${statusUrl}`);
            
            const statusResponse = await fetch(statusUrl);
            console.log(`📡 Status Response Status: ${statusResponse.status}`);
            
            if (statusResponse.ok) {
              const statusData = await statusResponse.json();
              console.log(`📊 Status response:`, statusData);
              
              // Map external status to our internal status
              let newStatus = order.status;
              if (statusData.status === "Completed" || statusData.status === "completed") {
                newStatus = "completed";
              } else if (statusData.status === "Processing" || statusData.status === "processing") {
                newStatus = "processing";
              } else if (statusData.status === "Pending" || statusData.status === "pending") {
                newStatus = "pending";
              } else if (statusData.status === "Cancelled" || statusData.status === "cancelled") {
                newStatus = "cancelled";
              }
              
              // Update order status if it changed
              if (newStatus !== order.status) {
                console.log(`🔄 Status updated from ${order.status} to ${newStatus}`);
                await storage.updateOrder(order.id, { status: newStatus });
                order.status = newStatus;
              } else {
                console.log(`✅ Status unchanged: ${order.status}`);
              }
            } else {
              console.log(`❌ Status check failed: ${statusResponse.status} ${statusResponse.statusText}`);
            }
          } else {
            console.log(`❌ API not found or inactive for service ${order.service.apiId}`);
          }
        } catch (statusError) {
          console.error("Error checking real-time status:", statusError);
          // Continue without failing the request
        }
      } else {
        console.log(`❌ No external order ID or service info for order ${order.orderId}`);
      }

      console.log(`✅ Sipariş bulundu: ${order.orderId}`);
      res.json(order);
    } catch (error) {
      console.error("Error searching order:", error);
      res.status(500).json({ message: "Sipariş aranamadı" });
    }
  });

  // Individual order fetch route with real-time status check
  app.get("/api/orders/:orderId", async (req, res) => {
    try {
      const orderId = req.params.orderId;
      const order = await storage.getOrderByOrderId(orderId);
      
      if (!order) {
        return res.status(404).json({ message: "Sipariş bulunamadı" });
      }

      // Check real-time status from external API if we have externalOrderId
      if (order.externalOrderId && order.service) {
        try {
          const api = await storage.getApi(order.service.apiId);
          if (api && api.isActive) {
            console.log(`📡 Checking real-time status for order ${order.orderId} (External ID: ${order.externalOrderId})`);
            console.log(`📡 API URL: ${api.url}, API Key: ${api.apiKey ? 'Present' : 'Missing'}`);
            
            const statusUrl = `${api.url}?action=status&key=${api.apiKey}&order=${order.externalOrderId}`;
            console.log(`📡 Status URL: ${statusUrl}`);
            
            const statusResponse = await fetch(statusUrl);
            console.log(`📡 Status Response Status: ${statusResponse.status}`);
            
            if (statusResponse.ok) {
              const statusData = await statusResponse.json();
              console.log(`📊 Status response:`, statusData);
              
              // Map external status to our internal status
              let newStatus = order.status;
              if (statusData.status === "Completed" || statusData.status === "completed") {
                newStatus = "completed";
              } else if (statusData.status === "Processing" || statusData.status === "processing") {
                newStatus = "processing";
              } else if (statusData.status === "Pending" || statusData.status === "pending") {
                newStatus = "pending";
              } else if (statusData.status === "Cancelled" || statusData.status === "cancelled") {
                newStatus = "cancelled";
              }
              
              // Update order status if it changed
              if (newStatus !== order.status) {
                console.log(`🔄 Status updated from ${order.status} to ${newStatus}`);
                await storage.updateOrder(order.id, { status: newStatus });
                order.status = newStatus;
              } else {
                console.log(`✅ Status unchanged: ${order.status}`);
              }
            } else {
              console.log(`❌ Status check failed: ${statusResponse.status} ${statusResponse.statusText}`);
            }
          } else {
            console.log(`❌ API not found or inactive for service ${order.service.apiId}`);
          }
        } catch (statusError) {
          console.error("Error checking real-time status:", statusError);
          // Continue without failing the request
        }
      } else {
        console.log(`❌ No external order ID or service info for order ${order.orderId}`);
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

  // Admin Login Route with security
  app.post("/api/admin/login", 
    adminLoginLimiter,
    [
      body('username').isLength({ min: 1 }).trim().escape(),
      body('password').isLength({ min: 1 }),
    ],
    async (req, res) => {
      try {
        const errors = validationResult(req);
        if (!errors.isEmpty()) {
          return res.status(400).json({ success: false, message: "Geçersiz giriş" });
        }

        const { username, password } = req.body;
        
        // Check username
        if (username !== ADMIN_USERNAME) {
          return res.status(401).json({ success: false, message: "Geçersiz kullanıcı adı veya şifre" });
        }

        // Check password with bcrypt
        const passwordMatch = await bcrypt.compare(password, ADMIN_PASSWORD_HASH);
        if (!passwordMatch) {
          return res.status(401).json({ success: false, message: "Geçersiz kullanıcı adı veya şifre" });
        }

        res.json({ success: true, message: "Admin oturum başarıyla açıldı" });
      } catch (error) {
        console.error("Admin login error:", error);
        res.status(500).json({ success: false, message: "Sunucu hatası" });
      }
    }
  );

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
