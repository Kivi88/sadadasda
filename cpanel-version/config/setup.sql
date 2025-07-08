-- MySQL database setup for cPanel hosting
-- Run this SQL in phpMyAdmin or cPanel MySQL interface

CREATE TABLE IF NOT EXISTS `apis` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `url` VARCHAR(500) NOT NULL,
    `api_key` VARCHAR(255) NOT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `last_sync` DATETIME NULL,
    `response_time` INT NULL,
    `service_count` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `services` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `api_id` INT NOT NULL,
    `external_id` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `platform` VARCHAR(100) DEFAULT 'Social Media',
    `category` VARCHAR(100) DEFAULT 'Genel',
    `min_quantity` INT DEFAULT 1,
    `max_quantity` INT DEFAULT 10000,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`api_id`) REFERENCES `apis`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_api_service` (`api_id`, `external_id`)
);

CREATE TABLE IF NOT EXISTS `keys` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `key_value` VARCHAR(255) UNIQUE NOT NULL,
    `service_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `prefix` VARCHAR(50) DEFAULT 'KIWIPAZARI',
    `max_amount` INT DEFAULT 1000,
    `used_amount` INT DEFAULT 0,
    `is_active` BOOLEAN DEFAULT TRUE,
    `is_hidden` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` VARCHAR(255) UNIQUE NOT NULL,
    `service_id` INT NOT NULL,
    `key_id` INT NOT NULL,
    `external_order_id` VARCHAR(255) NULL,
    `link` VARCHAR(500) NOT NULL,
    `quantity` INT NOT NULL,
    `status` ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    `start_count` INT DEFAULT 0,
    `remains` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`key_id`) REFERENCES `keys`(`id`) ON DELETE CASCADE
);

-- Insert sample data
INSERT INTO `apis` (`name`, `url`, `api_key`, `is_active`) VALUES
('MedyaBayim', 'https://medyabayim.com/api/v2', 'your_api_key_here', TRUE),
('ResellerProvider', 'https://resellerprovider.ru/api/v2', 'your_api_key_here', TRUE);