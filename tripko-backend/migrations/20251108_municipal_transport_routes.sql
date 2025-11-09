-- Municipal-level transport mapping & transport type extensions
-- This migration aligns with the live schema (MariaDB 10.4) after inspection.
-- Provides:
--   1. Table: municipal_transport_routes (link town <-> terminal <-> transport type)
--   2. Transport type fare & speed columns (rule-based estimator support)
--   3. Currency column for fares (default PHP)
--   4. Helpful indexes for terminal lookup
--   5. Aggregation view: vw_municipal_terminals
--   6. Seed sample municipal mappings & baseline transport type economic data
-- Idempotent via IF NOT EXISTS / CREATE OR REPLACE VIEW / INSERT IGNORE.

START TRANSACTION;

-- 1. Core mapping table (multi-mode per terminal per town)
CREATE TABLE IF NOT EXISTS municipal_transport_routes (
  mtr_id INT AUTO_INCREMENT PRIMARY KEY,
  town_id INT NOT NULL,
  terminal_id INT NOT NULL,
  type_id INT NOT NULL,
  is_primary TINYINT(1) DEFAULT 0 COMMENT 'Flag: primary terminal for town',
  notes VARCHAR(255) DEFAULT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_town_terminal_type (town_id, terminal_id, type_id),
  KEY idx_mtr_town (town_id),
  KEY idx_mtr_terminal (terminal_id),
  KEY idx_mtr_type (type_id),
  CONSTRAINT fk_mtr_town FOREIGN KEY (town_id) REFERENCES towns(town_id) ON DELETE CASCADE,
  CONSTRAINT fk_mtr_terminal FOREIGN KEY (terminal_id) REFERENCES terminal_locations(terminal_id) ON DELETE CASCADE,
  CONSTRAINT fk_mtr_type FOREIGN KEY (type_id) REFERENCES transport_types(type_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Extend transport_types with estimator-support columns (if not present)
ALTER TABLE transport_types
  ADD COLUMN IF NOT EXISTS base_fare DECIMAL(10,2) NULL AFTER type_name,
  ADD COLUMN IF NOT EXISTS per_km_rate DECIMAL(10,2) NULL AFTER base_fare,
  ADD COLUMN IF NOT EXISTS min_fare DECIMAL(10,2) NULL AFTER per_km_rate,
  ADD COLUMN IF NOT EXISTS avg_speed_kph DECIMAL(5,2) NULL AFTER min_fare,
  ADD COLUMN IF NOT EXISTS road_factor DECIMAL(4,2) NOT NULL DEFAULT 1.30 COMMENT 'Multiplier to approximate road vs straight-line distance' AFTER avg_speed_kph,
  ADD COLUMN IF NOT EXISTS comfort_level ENUM('low','medium','high') DEFAULT 'medium' AFTER road_factor,
  ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER comfort_level;

-- 3. Add currency to fares if missing
ALTER TABLE fares
  ADD COLUMN IF NOT EXISTS currency VARCHAR(3) NOT NULL DEFAULT 'PHP' AFTER amount;

-- 4. Helpful indexes for terminal geo & status (conditionally create for MariaDB 10.4)
SET @idx_exists := (
  SELECT COUNT(1) FROM information_schema.statistics 
  WHERE table_schema = DATABASE() AND table_name = 'terminal_locations' AND index_name = 'idx_terminal_status'
);
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_terminal_status ON terminal_locations(status)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx2_exists := (
  SELECT COUNT(1) FROM information_schema.statistics 
  WHERE table_schema = DATABASE() AND table_name = 'terminal_locations' AND index_name = 'idx_terminal_lat_lng'
);
SET @sql2 := IF(@idx2_exists = 0, 'CREATE INDEX idx_terminal_lat_lng ON terminal_locations(latitude, longitude)', 'SELECT 1');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

-- 5. Aggregation view: terminals + available modes per municipality
CREATE OR REPLACE VIEW vw_municipal_terminals AS
SELECT 
  t.town_id,
  t.name       AS town_name,
  tl.terminal_id,
  tl.location_name,
  tl.address,
  tl.latitude,
  tl.longitude,
  GROUP_CONCAT(DISTINCT tt.type_name ORDER BY tt.type_name SEPARATOR ', ') AS transport_modes,
  GROUP_CONCAT(DISTINCT tt.type_id ORDER BY tt.type_id SEPARATOR ',')      AS transport_mode_ids,
  MAX(CASE WHEN mtr.is_primary = 1 THEN 1 ELSE 0 END) AS has_primary_flag,
  MAX(mtr.is_primary) AS any_primary_present,
  COUNT(DISTINCT tt.type_id) AS mode_count
FROM municipal_transport_routes mtr
JOIN towns t ON t.town_id = mtr.town_id AND t.status = 'active'
JOIN terminal_locations tl ON tl.terminal_id = mtr.terminal_id AND tl.status = 'active'
JOIN transport_types tt ON tt.type_id = mtr.type_id AND (tt.is_active IS NULL OR tt.is_active = 1)
WHERE mtr.active = 1
GROUP BY t.town_id, tl.terminal_id, tl.location_name, tl.address, tl.latitude, tl.longitude;

-- 6. Seed baseline economic + speed data for existing transport_types (only fills NULLs)
UPDATE transport_types SET 
  base_fare      = COALESCE(base_fare, 20.00),
  per_km_rate    = COALESCE(per_km_rate, CASE 
                        WHEN type_name LIKE '%Air-conditioned%' THEN 2.20
                        WHEN type_name LIKE '%Ordinary%' THEN 1.80
                        WHEN type_name LIKE '%Mini Bus%' THEN 2.00
                        WHEN type_name LIKE '%Van%' THEN 2.50
                        WHEN type_name LIKE '%Jeepney%' THEN 1.50
                        ELSE 2.00 END),
  min_fare       = COALESCE(min_fare, 15.00),
  avg_speed_kph  = COALESCE(avg_speed_kph, CASE 
                        WHEN type_name LIKE '%Jeepney%' THEN 30
                        WHEN type_name LIKE '%Ordinary%' THEN 45
                        WHEN type_name LIKE '%Air-conditioned%' THEN 55
                        WHEN type_name LIKE '%Van%' THEN 50
                        WHEN type_name LIKE '%Mini Bus%' THEN 40
                        ELSE 40 END),
  road_factor    = COALESCE(road_factor, 1.30),
  comfort_level  = COALESCE(comfort_level, CASE 
                        WHEN type_name LIKE '%Air-conditioned%' THEN 'high'
                        WHEN type_name LIKE '%Van%' THEN 'high'
                        WHEN type_name LIKE '%Mini Bus%' THEN 'medium'
                        WHEN type_name LIKE '%Ordinary%' THEN 'medium'
                        WHEN type_name LIKE '%Jeepney%' THEN 'low'
                        ELSE 'medium' END),
  is_active      = COALESCE(is_active, 1);

-- 7. Seed example municipal-terminal-mode mappings (skip if already present)
-- Dagupan (town_id=19) terminals 1 & 2; Bolinao (14) terminal 3; Alaminos (3) terminal 4
INSERT IGNORE INTO municipal_transport_routes (town_id, terminal_id, type_id, is_primary, notes)
VALUES
  (19, 1, 1, 1, 'Dagupan primary A/C Bus'),
  (19, 1, 2, 0, 'Dagupan ordinary bus'),
  (19, 2, 1, 1, 'Dagupan Victory A/C Bus'),
  (19, 2, 2, 0, 'Dagupan Victory ordinary bus'),
  (14, 3, 1, 1, 'Bolinao main terminal A/C Bus'),
  (14, 3, 2, 0, 'Bolinao ordinary bus'),
  (3, 4, 1, 1, 'Alaminos main terminal A/C Bus'),
  (3, 4, 2, 0, 'Alaminos ordinary bus');

COMMIT;

-- Rollback hint (manual): to remove all new artifacts
-- DROP VIEW IF EXISTS vw_municipal_terminals;
-- DROP TABLE IF EXISTS municipal_transport_routes;
-- ALTER TABLE fares DROP COLUMN currency; -- only if added here
-- ALTER TABLE transport_types 
--   DROP COLUMN base_fare, DROP COLUMN per_km_rate, DROP COLUMN min_fare,
--   DROP COLUMN avg_speed_kph, DROP COLUMN road_factor, DROP COLUMN comfort_level, DROP COLUMN is_active;
