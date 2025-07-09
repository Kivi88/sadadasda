const { Pool } = require('pg');
const { drizzle } = require('drizzle-orm/node-postgres');
const { pgTable, serial, text, integer, boolean, timestamp } = require('drizzle-orm/pg-core');
const { eq, and } = require('drizzle-orm');

// Database Schema
const apis = pgTable("apis", {
  id: serial("id").primaryKey(),
  name: text("name").notNull(),
  baseUrl: text("base_url").notNull(),
  apiKey: text("api_key").notNull(),
  servicesEndpoint: text("services_endpoint"),
  orderEndpoint: text("order_endpoint"),
  statusEndpoint: text("status_endpoint"),
  isActive: boolean("is_active").default(true),
  createdAt: timestamp("created_at").defaultNow(),
});

const services = pgTable("services", {
  id: serial("id").primaryKey(),
  apiId: integer("api_id").references(() => apis.id),
  externalId: text("external_id"),
  name: text("name").notNull(),
  platform: text("platform"),
  category: text("category"),
  description: text("description"),
  minQuantity: integer("min_quantity").default(1),
  maxQuantity: integer("max_quantity").default(10000),
  isActive: boolean("is_active").default(true),
  createdAt: timestamp("created_at").defaultNow(),
});

const keys = pgTable("keys", {
  id: serial("id").primaryKey(),
  keyValue: text("key_value").notNull().unique(),
  name: text("name"),
  serviceId: integer("service_id").references(() => services.id),
  maxAmount: integer("max_amount").default(1000),
  usedAmount: integer("used_amount").default(0),
  isActive: boolean("is_active").default(true),
  createdAt: timestamp("created_at").defaultNow(),
});

const orders = pgTable("orders", {
  id: serial("id").primaryKey(),
  orderId: text("order_id").notNull().unique(),
  keyId: integer("key_id").references(() => keys.id),
  serviceId: integer("service_id").references(() => services.id),
  link: text("link").notNull(),
  quantity: integer("quantity").notNull(),
  status: text("status").default("pending"),
  externalOrderId: text("external_order_id"),
  createdAt: timestamp("created_at").defaultNow(),
});

// Database connection
const connectionString = process.env.DATABASE_URL || 'postgresql://username:password@localhost:5432/database';

const pool = new Pool({ 
  connectionString,
  ssl: process.env.NODE_ENV === 'production' ? { rejectUnauthorized: false } : false,
  max: 20,
  idleTimeoutMillis: 30000,
  connectionTimeoutMillis: 2000,
});

const db = drizzle(pool, { 
  schema: { apis, services, keys, orders }
});

// Storage Implementation
class DatabaseStorage {
  // API operations
  async getApis() {
    return await db.select().from(apis);
  }

  async getApi(id) {
    const result = await db.select().from(apis).where(eq(apis.id, id));
    return result[0];
  }

  async createApi(api) {
    const result = await db.insert(apis).values(api).returning();
    return result[0];
  }

  async updateApi(id, api) {
    const result = await db.update(apis).set(api).where(eq(apis.id, id)).returning();
    return result[0];
  }

  async deleteApi(id) {
    await db.delete(apis).where(eq(apis.id, id));
  }

  // Service operations
  async getServices() {
    return await db.select().from(services);
  }

  async getServicesByApi(apiId) {
    return await db.select().from(services).where(eq(services.apiId, apiId));
  }

  async getService(id) {
    const result = await db.select().from(services).where(eq(services.id, id));
    return result[0];
  }

  async createService(service) {
    const result = await db.insert(services).values(service).returning();
    return result[0];
  }

  async createServicesBulk(servicesToCreate) {
    if (servicesToCreate.length === 0) return [];
    const result = await db.insert(services).values(servicesToCreate).returning();
    return result;
  }

  async updateService(id, service) {
    const result = await db.update(services).set(service).where(eq(services.id, id)).returning();
    return result[0];
  }

  async deleteService(id) {
    await db.delete(services).where(eq(services.id, id));
  }

  // Key operations
  async getKeys() {
    return await db.select().from(keys);
  }

  async getKey(id) {
    const result = await db.select().from(keys).where(eq(keys.id, id));
    return result[0];
  }

  async getKeyByValue(keyValue) {
    const result = await db.select().from(keys).where(eq(keys.keyValue, keyValue));
    return result[0];
  }

  async createKey(key) {
    const result = await db.insert(keys).values(key).returning();
    return result[0];
  }

  async updateKey(id, key) {
    const result = await db.update(keys).set(key).where(eq(keys.id, id)).returning();
    return result[0];
  }

  async deleteKey(id) {
    await db.delete(keys).where(eq(keys.id, id));
  }

  // Order operations
  async getOrders() {
    return await db.select().from(orders);
  }

  async getOrder(id) {
    const result = await db.select().from(orders).where(eq(orders.id, id));
    return result[0];
  }

  async getOrderByOrderId(orderId) {
    const result = await db.select().from(orders).where(eq(orders.orderId, orderId));
    return result[0];
  }

  async createOrder(order) {
    const result = await db.insert(orders).values(order).returning();
    return result[0];
  }

  async updateOrder(id, order) {
    const result = await db.update(orders).set(order).where(eq(orders.id, id)).returning();
    return result[0];
  }

  async deleteOrder(id) {
    await db.delete(orders).where(eq(orders.id, id));
  }
}

const storage = new DatabaseStorage();

module.exports = { storage, db, pool };