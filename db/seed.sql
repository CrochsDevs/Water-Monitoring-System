-- ============================================================
-- Seed Data — Water Monitoring System
-- ============================================================

-- Default admin user: admin / Admin123
INSERT INTO users (full_name, username, email, password, role) VALUES
('System Admin', 'admin', 'admin@watermonitor.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE user_id=user_id;
-- Password hash is for 'password' — same as Laravel default hash

-- Sample water level readings for the past 7 days (every 5 minutes = ~2016 rows)
-- Using a stored procedure to generate realistic data
DROP PROCEDURE IF EXISTS seed_readings;
DELIMITER //
CREATE PROCEDURE seed_readings()
BEGIN
    DECLARE counter INT DEFAULT 0;
    DECLARE total_rows INT DEFAULT 500;
    DECLARE base_level DECIMAL(8,2) DEFAULT 12.5;
    DECLARE base_battery DECIMAL(5,2) DEFAULT 12.4;
    DECLARE ts DATETIME;

    -- Check if data already exists
    SET @existing = (SELECT COUNT(*) FROM water_level_readings);
    IF @existing > 1500 THEN
        SELECT CONCAT('Data already exists (', @existing, ' rows). Skipping seed.') AS message;
    ELSE
        -- Seed RF01
        SET @dev = 'RF01';
        SET @base_level = 12.5;
        SET counter = 0;
        SET ts = NOW() - INTERVAL 7 DAY;
        WHILE counter < 500 DO
            SET @hour_factor = SIN(2 * PI() * HOUR(ts) / 24) * 2.0;
            SET @noise = (RAND() - 0.5) * 3.0;
            SET @level = GREATEST(0, @base_level + @hour_factor + @noise);
            SET @batt = GREATEST(11.0, 12.4 - (counter / 500) * 1.2 + (RAND() - 0.5) * 0.3);
            SET @dist = 200.0 - @level;
            SET @sig = FLOOR(15 + RAND() * 12);
            SET @alert = CASE WHEN @level > 18 THEN 'high_water' WHEN @level < 3 THEN 'low_water' ELSE NULL END;
            INSERT INTO water_level_readings (device_id, water_level_cm, distance_cm, battery_v, signal_strength, alert, reading_mode, received_at)
            VALUES (@dev, ROUND(@level, 1), ROUND(@dist, 1), ROUND(@batt, 2), @sig, @alert, 'lte', ts);
            SET ts = ts + INTERVAL 20 MINUTE;
            SET counter = counter + 1;
        END WHILE;

        -- Seed RF02 (slightly different pattern — shallower field, different base level)
        SET @dev = 'RF02';
        SET @base_level = 8.0;
        SET counter = 0;
        SET ts = NOW() - INTERVAL 7 DAY;
        WHILE counter < 500 DO
            SET @hour_factor = SIN(2 * PI() * HOUR(ts) / 24 + 1) * 1.5;
            SET @noise = (RAND() - 0.5) * 2.5;
            SET @level = GREATEST(0, @base_level + @hour_factor + @noise);
            SET @batt = GREATEST(11.0, 12.6 - (counter / 500) * 1.0 + (RAND() - 0.5) * 0.2);
            SET @dist = 200.0 - @level;
            SET @sig = FLOOR(18 + RAND() * 10);
            SET @alert = CASE WHEN @level > 18 THEN 'high_water' WHEN @level < 3 THEN 'low_water' ELSE NULL END;
            INSERT INTO water_level_readings (device_id, water_level_cm, distance_cm, battery_v, signal_strength, alert, reading_mode, received_at)
            VALUES (@dev, ROUND(@level, 1), ROUND(@dist, 1), ROUND(@batt, 2), @sig, @alert, 'lte', ts);
            SET ts = ts + INTERVAL 20 MINUTE;
            SET counter = counter + 1;
        END WHILE;

        -- Seed RF03 (deeper field, higher base level)
        SET @dev = 'RF03';
        SET @base_level = 16.0;
        SET counter = 0;
        SET ts = NOW() - INTERVAL 7 DAY;
        WHILE counter < 500 DO
            SET @hour_factor = SIN(2 * PI() * HOUR(ts) / 24 + 2) * 1.8;
            SET @noise = (RAND() - 0.5) * 2.0;
            SET @level = GREATEST(0, @base_level + @hour_factor + @noise);
            SET @batt = GREATEST(11.0, 12.2 - (counter / 500) * 1.5 + (RAND() - 0.5) * 0.3);
            SET @dist = 200.0 - @level;
            SET @sig = FLOOR(12 + RAND() * 14);
            SET @alert = CASE WHEN @level > 18 THEN 'high_water' WHEN @level < 3 THEN 'low_water' ELSE NULL END;
            INSERT INTO water_level_readings (device_id, water_level_cm, distance_cm, battery_v, signal_strength, alert, reading_mode, received_at)
            VALUES (@dev, ROUND(@level, 1), ROUND(@dist, 1), ROUND(@batt, 2), @sig, @alert, 'lte', ts);
            SET ts = ts + INTERVAL 20 MINUTE;
            SET counter = counter + 1;
        END WHILE;

        SELECT CONCAT('Seeded ', counter, ' rows successfully.') AS message;
    END IF;
END //
DELIMITER ;

CALL seed_readings();
DROP PROCEDURE IF EXISTS seed_readings;
