-- KiWiPazari Database Setup - MySQL Version
-- Bu dosyayı MySQL veritabanınızda çalıştırın

-- Rate limiting tablosu
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL UNIQUE,
    attempts INT DEFAULT 0,
    last_attempt INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- APIs tablosu
CREATE TABLE IF NOT EXISTS apis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
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
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_id INT,
    external_id VARCHAR(255),
    name TEXT NOT NULL,
    platform VARCHAR(255),
    category VARCHAR(255),
    description TEXT,
    min_quantity INT DEFAULT 1,
    max_quantity INT DEFAULT 10000,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (api_id) REFERENCES apis(id) ON DELETE CASCADE
);

-- Keys tablosu
CREATE TABLE IF NOT EXISTS keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_value VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255),
    service_id INT,
    max_amount INT DEFAULT 1000,
    used_amount INT DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- Orders tablosu
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(255) NOT NULL UNIQUE,
    key_id INT,
    service_id INT,
    link TEXT NOT NULL,
    quantity INT NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    external_order_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (key_id) REFERENCES keys(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- Indexes for better performance
CREATE INDEX idx_keys_key_value ON keys(key_value);
CREATE INDEX idx_orders_order_id ON orders(order_id);
CREATE INDEX idx_services_api_id ON services(api_id);
CREATE INDEX idx_keys_service_id ON keys(service_id);
CREATE INDEX idx_rate_limits_identifier ON rate_limits(identifier);

-- Insert sample data (optional)
INSERT IGNORE INTO apis (name, base_url, api_key, services_endpoint, order_endpoint, status_endpoint, is_active) 
VALUES 
('Sample API', 'https://api.example.com', 'sample-key', '/services', '/order', '/status', true);