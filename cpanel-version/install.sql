-- KiWiPazari Database Setup
-- Bu dosyayı PostgreSQL veya MySQL veritabanınızda çalıştırın

-- APIs tablosu
CREATE TABLE IF NOT EXISTS apis (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL,
    base_url TEXT NOT NULL,
    api_key TEXT NOT NULL,
    services_endpoint TEXT,
    order_endpoint TEXT,
    status_endpoint TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Services tablosu
CREATE TABLE IF NOT EXISTS services (
    id SERIAL PRIMARY KEY,
    api_id INTEGER REFERENCES apis(id),
    external_id TEXT,
    name TEXT NOT NULL,
    platform TEXT,
    category TEXT,
    description TEXT,
    min_quantity INTEGER DEFAULT 1,
    max_quantity INTEGER DEFAULT 10000,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Keys tablosu
CREATE TABLE IF NOT EXISTS keys (
    id SERIAL PRIMARY KEY,
    key_value TEXT NOT NULL UNIQUE,
    name TEXT,
    service_id INTEGER REFERENCES services(id),
    max_amount INTEGER DEFAULT 1000,
    used_amount INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders tablosu
CREATE TABLE IF NOT EXISTS orders (
    id SERIAL PRIMARY KEY,
    order_id TEXT NOT NULL UNIQUE,
    key_id INTEGER REFERENCES keys(id),
    service_id INTEGER REFERENCES services(id),
    link TEXT NOT NULL,
    quantity INTEGER NOT NULL,
    status TEXT DEFAULT 'pending',
    external_order_id TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for better performance
CREATE INDEX IF NOT EXISTS idx_keys_key_value ON keys(key_value);
CREATE INDEX IF NOT EXISTS idx_orders_order_id ON orders(order_id);
CREATE INDEX IF NOT EXISTS idx_services_api_id ON services(api_id);
CREATE INDEX IF NOT EXISTS idx_keys_service_id ON keys(service_id);

-- Insert sample data (optional)
INSERT INTO apis (name, base_url, api_key, services_endpoint, order_endpoint, status_endpoint, is_active) 
VALUES 
('Sample API', 'https://api.example.com', 'sample-key', '/services', '/order', '/status', true)
ON CONFLICT DO NOTHING;

COMMIT;