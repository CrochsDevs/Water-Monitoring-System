-- ============================================================
-- Water Monitoring System — Database Schema
-- ============================================================

-- Users table (for web dashboard authentication)
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'viewer') NOT NULL DEFAULT 'viewer',
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Water level readings from field devices (Arduino + sensor)
CREATE TABLE IF NOT EXISTS water_level_readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id VARCHAR(20) NOT NULL DEFAULT 'RF01',
    water_level_cm DECIMAL(8,2) NOT NULL COMMENT 'Water level in cm (height from field bottom)',
    distance_cm DECIMAL(8,2) NOT NULL COMMENT 'Distance from sensor to water surface',
    battery_v DECIMAL(5,2) NOT NULL COMMENT 'Battery voltage',
    signal INT NOT NULL DEFAULT 0 COMMENT 'GSM/LTE signal strength (0-31)',
    alert VARCHAR(20) DEFAULT NULL COMMENT 'Alert type: high_water, low_water, sensor_error, low_battery',
    reading_mode ENUM('serial_usb', 'lte', 'sms') NOT NULL DEFAULT 'serial_usb',
    received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_device_time (device_id, received_at),
    INDEX idx_received_at (received_at),
    INDEX idx_alert (alert)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
