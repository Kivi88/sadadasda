import { apis, services, keys, orders, type Api, type Service, type Key, type Order, type InsertApi, type InsertService, type InsertKey, type InsertOrder } from "@shared/schema";
import { db } from "./db";
import { eq, desc, and } from "drizzle-orm";

export interface IStorage {
  // API operations
  getApis(): Promise<Api[]>;
  getApi(id: number): Promise<Api | undefined>;
  createApi(api: InsertApi): Promise<Api>;
  updateApi(id: number, api: Partial<InsertApi>): Promise<Api>;
  deleteApi(id: number): Promise<void>;
  
  // Service operations
  getServices(): Promise<Service[]>;
  getServicesByApi(apiId: number): Promise<Service[]>;
  getService(id: number): Promise<Service | undefined>;
  createService(service: InsertService): Promise<Service>;
  updateService(id: number, service: Partial<InsertService>): Promise<Service>;
  deleteService(id: number): Promise<void>;
  
  // Key operations
  getKeys(): Promise<Key[]>;
  getKey(id: number): Promise<Key | undefined>;
  getKeyByValue(keyValue: string): Promise<Key | undefined>;
  createKey(key: InsertKey): Promise<Key>;
  updateKey(id: number, key: Partial<InsertKey>): Promise<Key>;
  deleteKey(id: number): Promise<void>;
  
  // Order operations
  getOrders(): Promise<Order[]>;
  getOrder(id: number): Promise<Order | undefined>;
  getOrderByOrderId(orderId: string): Promise<Order | undefined>;
  createOrder(order: InsertOrder): Promise<Order>;
  updateOrder(id: number, order: Partial<InsertOrder>): Promise<Order>;
  deleteOrder(id: number): Promise<void>;
}

export class DatabaseStorage implements IStorage {
  // API operations
  async getApis(): Promise<Api[]> {
    return await db.select().from(apis).orderBy(desc(apis.createdAt));
  }
  
  async getApi(id: number): Promise<Api | undefined> {
    const [api] = await db.select().from(apis).where(eq(apis.id, id));
    return api;
  }
  
  async createApi(api: InsertApi): Promise<Api> {
    const [newApi] = await db.insert(apis).values(api).returning();
    return newApi;
  }
  
  async updateApi(id: number, api: Partial<InsertApi>): Promise<Api> {
    const [updatedApi] = await db
      .update(apis)
      .set({ ...api, updatedAt: new Date() })
      .where(eq(apis.id, id))
      .returning();
    return updatedApi;
  }
  
  async deleteApi(id: number): Promise<void> {
    await db.delete(apis).where(eq(apis.id, id));
  }
  
  // Service operations
  async getServices(): Promise<Service[]> {
    return await db.select().from(services).orderBy(desc(services.createdAt));
  }
  
  async getServicesByApi(apiId: number): Promise<Service[]> {
    return await db.select().from(services).where(eq(services.apiId, apiId));
  }
  
  async getService(id: number): Promise<Service | undefined> {
    const [service] = await db.select().from(services).where(eq(services.id, id));
    return service;
  }
  
  async createService(service: InsertService): Promise<Service> {
    const [newService] = await db.insert(services).values(service).returning();
    return newService;
  }
  
  async updateService(id: number, service: Partial<InsertService>): Promise<Service> {
    const [updatedService] = await db
      .update(services)
      .set({ ...service, updatedAt: new Date() })
      .where(eq(services.id, id))
      .returning();
    return updatedService;
  }
  
  async deleteService(id: number): Promise<void> {
    await db.delete(services).where(eq(services.id, id));
  }
  
  // Key operations
  async getKeys(): Promise<Key[]> {
    return await db.select().from(keys).orderBy(desc(keys.createdAt));
  }
  
  async getKey(id: number): Promise<Key | undefined> {
    const [key] = await db.select().from(keys).where(eq(keys.id, id));
    return key;
  }
  
  async getKeyByValue(keyValue: string): Promise<Key | undefined> {
    const [key] = await db.select().from(keys).where(eq(keys.keyValue, keyValue));
    return key;
  }
  
  async createKey(key: InsertKey): Promise<Key> {
    const [newKey] = await db.insert(keys).values(key).returning();
    return newKey;
  }
  
  async updateKey(id: number, key: Partial<InsertKey>): Promise<Key> {
    const [updatedKey] = await db
      .update(keys)
      .set({ ...key, updatedAt: new Date() })
      .where(eq(keys.id, id))
      .returning();
    return updatedKey;
  }
  
  async deleteKey(id: number): Promise<void> {
    await db.delete(keys).where(eq(keys.id, id));
  }
  
  // Order operations
  async getOrders(): Promise<Order[]> {
    return await db.select().from(orders).orderBy(desc(orders.createdAt));
  }
  
  async getOrder(id: number): Promise<Order | undefined> {
    const [order] = await db.select().from(orders).where(eq(orders.id, id));
    return order;
  }
  
  async getOrderByOrderId(orderId: string): Promise<Order | undefined> {
    const [order] = await db.select().from(orders).where(eq(orders.orderId, orderId));
    return order;
  }
  
  async createOrder(order: InsertOrder): Promise<Order> {
    const [newOrder] = await db.insert(orders).values(order).returning();
    return newOrder;
  }
  
  async updateOrder(id: number, order: Partial<InsertOrder>): Promise<Order> {
    const [updatedOrder] = await db
      .update(orders)
      .set({ ...order, updatedAt: new Date() })
      .where(eq(orders.id, id))
      .returning();
    return updatedOrder;
  }
  
  async deleteOrder(id: number): Promise<void> {
    await db.delete(orders).where(eq(orders.id, id));
  }
}

export const storage = new DatabaseStorage();
