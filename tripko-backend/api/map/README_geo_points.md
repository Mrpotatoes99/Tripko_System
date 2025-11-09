Geo Points Storage & Map Picker Integration
===========================================

Purpose
-------
Store precise or approximate coordinates for heterogeneous entities (tourist_spot, festival, itinerary, terminal, town) without altering original tables immediately.

DDL
---
```sql
CREATE TABLE IF NOT EXISTS geo_points (
  geo_id INT AUTO_INCREMENT PRIMARY KEY,
  entity_type ENUM('tourist_spot','festival','itinerary','terminal','town') NOT NULL,
  entity_id INT NOT NULL,
  latitude DECIMAL(10,8) NOT NULL,
  longitude DECIMAL(11,8) NOT NULL,
  accuracy ENUM('exact','approx','geocoded') DEFAULT 'approx',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_entity (entity_type, entity_id),
  KEY idx_geo_lat_lng (latitude, longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Usage Rules
-----------
- Insert row when user sets location via map picker.
- If auto-approximating (e.g., using town centroid), insert with `accuracy='approx'`.
- When user drags marker & saves precise pin, update same row with `accuracy='exact'`.

Endpoint Contracts
------------------
- `POST api/map/save_point.php`
  - Body JSON: `{ entity_type: 'tourist_spot', entity_id: 25, latitude: 16.123456, longitude: 120.987654, accuracy: 'exact' }`
  - Response: `{ success: true }` or error object.

Security Considerations
-----------------------
- Validate user role (tourism_officer can only modify their town's entities; admin can modify all).
- Sanitize entity_type; whitelist only expected enums.
- Enforce lat/lng ranges.

Fallback Logic (markers.php)
----------------------------
1. Try geo_points for entity.
2. Else if entity_type in (tourist_spot, festival) and town centroid present in future, optionally approximate (NOT yet implemented — town table lacks centroid columns currently).
3. Skip if no coordinates.

Future Enhancements
-------------------
- Add audit trail table for coordinate changes.
- Support polygon/area geometry via auxiliary table later.
