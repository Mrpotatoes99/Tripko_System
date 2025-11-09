-- Migration: Update / normalize tourist spot coordinates in geo_points
-- Date: 2025-11-08
-- Notes:
--   Idempotent: relies on geo_points PK (entity_type, entity_id). Uses INSERT ... ON DUPLICATE KEY UPDATE.
--   Accuracy set to 'exact' when full DMS (degrees/minutes/seconds) or authoritative landmark; else 'approximate'.
--   Source: User-provided coordinate list (mixed DMS and decimal) + conversions.
--   Adjust any approximations later with a follow-up migration if you obtain survey-grade data.

START TRANSACTION;

INSERT INTO geo_points (entity_type, entity_id, latitude, longitude, accuracy)
VALUES
  -- Bolinao area
  ('tourist_spot', 1, 16.305833, 119.860278, 'exact'),       -- Bolinao Falls 1 (16°18′21″N 119°51′37″E)
  ('tourist_spot', 6, 16.307222, 119.785556, 'exact'),       -- Cape Bolinao Lighthouse (16°18′26″N 119°47′08″E)
  ('tourist_spot', 12, 16.333333, 119.800000, 'approximate'),-- Enchanted Cave (16°20′N 119°48′E)
  ('tourist_spot', 19, 16.388333, 119.893611, 'exact'),      -- Saint James the Great Parish Church (16°23′18″N 119°53′37″E)
  -- Alaminos City
  ('tourist_spot', 5, 16.200000, 120.033333, 'approximate'), -- Hundred Islands National Park (16°12′N 120°2′E)
  -- Agno municipality cluster
  ('tourist_spot', 11, 16.160000, 119.770000, 'approximate'),-- Abagatanen Beach (decimal)
  ('tourist_spot', 14, 16.150000, 119.775000, 'approximate'),-- Agno Beach (estimated within same coastal stretch)
  ('tourist_spot', 15, 16.130000, 119.780000, 'approximate'),-- Umbrella Rocks (decimal)
  ('tourist_spot', 16, 16.000000, 119.760000, 'approximate'),-- Mary Hill Youth Camp (16°N 119.76°E)
  ('tourist_spot', 17, 16.160000, 119.770000, 'approximate'),-- Saint Catherine of Alexandria Parish (decimal)
  -- Bani municipality cluster
  ('tourist_spot', 20, 16.220000, 119.830000, 'approximate'),-- Busay Falls (decimal)
  ('tourist_spot', 21, 16.240000, 119.780000, 'approximate'),-- Olanen Beach (decimal)
  ('tourist_spot', 22, 16.270000, 119.770000, 'approximate'),-- Hidden Paradise Beach Resort (approximate)
  ('tourist_spot', 24, 16.250000, 119.790000, 'approximate'),-- Polipol Island (approximate)
  ('tourist_spot', 25, 16.250000, 119.780000, 'approximate'),-- Surip Beach (decimal)
  -- Bayambang
  ('tourist_spot', 13, 15.819722, 120.444167, 'exact'),      -- St. Vincent Ferrer Prayer Park (15°49′11″N 120°26′39″E)
  -- Manaoag
  ('tourist_spot', 18, 16.043889, 120.488889, 'exact')       -- Minor Basilica of Our Lady of Manaoag (16°02′38″N 120°29′20″E)
ON DUPLICATE KEY UPDATE
  latitude = VALUES(latitude),
  longitude = VALUES(longitude),
  accuracy = VALUES(accuracy),
  updated_at = CURRENT_TIMESTAMP;

COMMIT;

-- Verification (optional run after migration):
-- SELECT ts.spot_id, ts.name, gp.latitude, gp.longitude, gp.accuracy
-- FROM tourist_spots ts
-- LEFT JOIN geo_points gp ON gp.entity_type='tourist_spot' AND gp.entity_id=ts.spot_id
-- WHERE ts.spot_id IN (1,5,6,11,12,13,14,15,16,17,18,19,20,21,22,24,25)
-- ORDER BY ts.spot_id;
