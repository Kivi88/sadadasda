import { pgTable, text, serial, integer, boolean, timestamp, jsonb, decimal } from "drizzle-orm/pg-core";
import { createInsertSchema } from "drizzle-zod";
import { z } from "zod";

export const apis = pgTable("apis", {
  id: serial("id").primaryKey(),
  name: text("name").notNull(),
  url: text("url").notNull(),
  apiKey: text("api_key").notNull(),
  isActive: boolean("is_active").default(true),
  lastSync: timestamp("last_sync"),
  responseTime: integer("response_time"), // in milliseconds
  serviceCount: integer("service_count").default(0),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

export const services = pgTable("services", {
  id: serial("id").primaryKey(),
  apiId: integer("api_id").references(() => apis.id),
  externalId: text("external_id").notNull(), // Service ID from external API
  name: text("name").notNull(),
  platform: text("platform").notNull(), // instagram, tiktok, youtube, twitter, etc.
  category: text("category").notNull(), // followers, likes, views, etc.
  minQuantity: integer("min_quantity").default(1),
  maxQuantity: integer("max_quantity").default(10000),
  isActive: boolean("is_active").default(true),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

export const keys = pgTable("keys", {
  id: serial("id").primaryKey(),
  keyValue: text("key_value").notNull().unique(),
  serviceId: integer("service_id").references(() => services.id),
  name: text("name").notNull(),
  prefix: text("prefix").default("KIWIPAZARI"),
  maxAmount: integer("max_amount").default(1000),
  usedAmount: integer("used_amount").default(0),
  isActive: boolean("is_active").default(true),
  isHidden: boolean("is_hidden").default(false),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

export const orders = pgTable("orders", {
  id: serial("id").primaryKey(),
  orderId: text("order_id").notNull().unique(),
  keyId: integer("key_id").references(() => keys.id),
  serviceId: integer("service_id").references(() => services.id),
  link: text("link").notNull(),
  quantity: integer("quantity").notNull(),
  status: text("status").default("pending"), // pending, processing, completed, cancelled
  externalOrderId: text("external_order_id"), // Order ID from external API
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

export const insertApiSchema = createInsertSchema(apis).omit({
  id: true,
  createdAt: true,
  updatedAt: true,
  lastSync: true,
  responseTime: true,
  serviceCount: true,
});

export const insertServiceSchema = createInsertSchema(services).omit({
  id: true,
  createdAt: true,
  updatedAt: true,
});

export const insertKeySchema = createInsertSchema(keys).omit({
  id: true,
  createdAt: true,
  updatedAt: true,
});

export const insertOrderSchema = createInsertSchema(orders).omit({
  id: true,
  createdAt: true,
  updatedAt: true,
});

export type Api = typeof apis.$inferSelect;
export type InsertApi = z.infer<typeof insertApiSchema>;
export type Service = typeof services.$inferSelect;
export type InsertService = z.infer<typeof insertServiceSchema>;
export type Key = typeof keys.$inferSelect;
export type InsertKey = z.infer<typeof insertKeySchema>;
export type Order = typeof orders.$inferSelect;
export type InsertOrder = z.infer<typeof insertOrderSchema>;
