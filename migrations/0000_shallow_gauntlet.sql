CREATE TABLE "apis" (
	"id" serial PRIMARY KEY NOT NULL,
	"name" text NOT NULL,
	"url" text NOT NULL,
	"api_key" text NOT NULL,
	"is_active" boolean DEFAULT true,
	"last_sync" timestamp,
	"response_time" integer,
	"service_count" integer DEFAULT 0,
	"created_at" timestamp DEFAULT now(),
	"updated_at" timestamp DEFAULT now()
);
--> statement-breakpoint
CREATE TABLE "keys" (
	"id" serial PRIMARY KEY NOT NULL,
	"key_value" text NOT NULL,
	"service_id" integer,
	"name" text NOT NULL,
	"prefix" text DEFAULT 'KIWIPAZARI',
	"max_amount" integer DEFAULT 1000,
	"used_amount" integer DEFAULT 0,
	"is_active" boolean DEFAULT true,
	"is_hidden" boolean DEFAULT false,
	"created_at" timestamp DEFAULT now(),
	"updated_at" timestamp DEFAULT now(),
	CONSTRAINT "keys_key_value_unique" UNIQUE("key_value")
);
--> statement-breakpoint
CREATE TABLE "orders" (
	"id" serial PRIMARY KEY NOT NULL,
	"order_id" text NOT NULL,
	"key_id" integer,
	"service_id" integer,
	"link" text NOT NULL,
	"quantity" integer NOT NULL,
	"status" text DEFAULT 'pending',
	"external_order_id" text,
	"created_at" timestamp DEFAULT now(),
	"updated_at" timestamp DEFAULT now(),
	CONSTRAINT "orders_order_id_unique" UNIQUE("order_id")
);
--> statement-breakpoint
CREATE TABLE "services" (
	"id" serial PRIMARY KEY NOT NULL,
	"api_id" integer,
	"external_id" text NOT NULL,
	"name" text NOT NULL,
	"platform" text NOT NULL,
	"category" text NOT NULL,
	"min_quantity" integer DEFAULT 1,
	"max_quantity" integer DEFAULT 10000,
	"is_active" boolean DEFAULT true,
	"created_at" timestamp DEFAULT now(),
	"updated_at" timestamp DEFAULT now()
);
--> statement-breakpoint
ALTER TABLE "keys" ADD CONSTRAINT "keys_service_id_services_id_fk" FOREIGN KEY ("service_id") REFERENCES "public"."services"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "orders" ADD CONSTRAINT "orders_key_id_keys_id_fk" FOREIGN KEY ("key_id") REFERENCES "public"."keys"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "orders" ADD CONSTRAINT "orders_service_id_services_id_fk" FOREIGN KEY ("service_id") REFERENCES "public"."services"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "services" ADD CONSTRAINT "services_api_id_apis_id_fk" FOREIGN KEY ("api_id") REFERENCES "public"."apis"("id") ON DELETE no action ON UPDATE no action;