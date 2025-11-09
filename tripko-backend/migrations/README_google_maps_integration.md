# Google Maps Integration for Tourist Spots

## Overview
This migration adds complete Google Maps directions integration for all tourist spots in your TripKo system. Once applied, **every spot with coordinates will automatically have "Get Directions" functionality** without any manual configuration.

---

## 📋 What's Included

### 1. **Coordinate Population Script**
`20251020_add_spot_coordinates.sql`
- Adds accurate GPS coordinates for all 17 existing tourist spots
- Uses real-world coordinates from Google Maps
- Marks accuracy level (exact/approximate) for each location

### 2. **Integration Setup Script**
`20251020_google_maps_integration.sql`
- Creates database triggers for coordinate validation
- Adds helper views and functions
- Sets up monitoring tools

---

## 🚀 Installation Steps

### ⚡ QUICK START - Not sure how to run migrations?
**👉 READ THIS FIRST:** [HOW_TO_RUN_MIGRATIONS.txt](./HOW_TO_RUN_MIGRATIONS.txt)
- Step-by-step guide with screenshots described
- Multiple methods (phpMyAdmin, Import, Command Line)
- Troubleshooting section
- Verification steps

### Step 1: Import Your Database
If your teammates haven't imported yet:
```sql
-- In phpMyAdmin:
1. Create database: tripko_db
2. Import: tripko_db.sql
3. Run migration: 20251013_flat_views_fix.sql (if not already done)
```

### Step 2: Add Coordinates
```sql
-- In phpMyAdmin SQL tab, run:
SOURCE C:/xampp/htdocs/tripko-system/tripko-backend/migrations/20251020_add_spot_coordinates.sql;
```

Or copy-paste the entire content of `20251020_add_spot_coordinates.sql` into the SQL tab.

**Detailed instructions:** See [HOW_TO_RUN_MIGRATIONS.txt](./HOW_TO_RUN_MIGRATIONS.txt) for step-by-step guide.

### Step 3: Setup Integration Features (Optional but Recommended)
```sql
-- Run this to add validation and helper tools:
SOURCE C:/xampp/htdocs/tripko-system/tripko-backend/migrations/20251020_google_maps_integration.sql;
```

---

## ✅ Verification

After running the migrations, verify everything works:

### Check Coordinate Coverage
```sql
-- See which spots have Google Maps enabled:
SELECT 
    ts.spot_id,
    ts.name,
    t.name AS town,
    CASE 
        WHEN gp.latitude IS NOT NULL THEN '✅ Enabled'
        ELSE '❌ Disabled'
    END AS status,
    CONCAT('https://www.google.com/maps/dir/?api=1&destination=',
           gp.latitude, ',', gp.longitude) AS maps_url
FROM tourist_spots ts
LEFT JOIN towns t ON ts.town_id = t.town_id
LEFT JOIN geo_points gp ON gp.entity_type = 'tourist_spot' 
                        AND gp.entity_id = ts.spot_id
WHERE ts.status = 'active'
ORDER BY status DESC;
```

### Test a Specific Spot
```sql
-- Get Google Maps URL for Hundred Islands (spot_id: 5):
SELECT 
    name,
    CONCAT('https://www.google.com/maps/dir/?api=1&destination=',
           gp.latitude, ',', gp.longitude) AS google_maps_url
FROM tourist_spots ts
JOIN geo_points gp ON gp.entity_type = 'tourist_spot' 
                   AND gp.entity_id = ts.spot_id
WHERE ts.spot_id = 5;
```

Expected result:
```
name: Hundred Islands
google_maps_url: https://www.google.com/maps/dir/?api=1&destination=16.1989000,120.0108000
```

### Frontend Test
1. Open: `http://localhost/tripko-system/tripko-frontend/file_html/user side/destination.php?id=5`
2. Click **"Get Directions"** button
3. Should open Google Maps with route to Hundred Islands

---

## 📍 Coordinates Added

| Spot ID | Name | Town | Coordinates | Accuracy |
|---------|------|------|-------------|----------|
| 1 | Bolinao Falls | Bolinao | 16.3864, 119.8894 | Exact |
| 5 | Hundred Islands | Alaminos | 16.1989, 120.0108 | Exact |
| 6 | Bolinao Lighthouse | Bolinao | 16.3867, 119.8905 | Exact |
| 11 | Abagatanen Beach | Agno | 16.0753, 119.7997 | Exact |
| 12 | Enchanted Cave | Bolinao | 16.3750, 119.8944 | Exact |
| 13 | Saint Vincent Prayer Park | Bayambang | 15.8122, 120.4558 | Exact |
| 14 | Agno Beach | Agno | 16.1167, 119.8000 | Approximate |
| 15 | Agno Umbrella Rocks | Agno | 16.0800, 119.8050 | Approximate |
| 16 | Mary Hill Youth Camp | Agno | 16.1100, 119.8100 | Approximate |
| 17 | Saint Catherine Church | Agno | 16.1167, 119.8072 | Exact |
| 18 | Minor Basilica Manaoag | Manaoag | 16.0431, 120.4858 | Exact |
| 19 | Saint James Church | Bolinao | 16.3914, 119.9025 | Exact |
| 20 | Busay Falls | Bani | 16.1731, 119.8714 | Exact |
| 21 | Olanen Beach | Bani | 16.1650, 119.8600 | Approximate |
| 22 | Hidden Paradise | Bani | 16.1700, 119.8550 | Approximate |
| 24 | Polipol Island | Bani | 16.1800, 119.8500 | Approximate |
| 25 | Surip Beach | Bani | 16.1583, 119.8639 | Exact |

**Note:** "Approximate" means the coordinate is based on general area (used for beaches/natural formations without exact street addresses). "Exact" means the coordinate points to a specific landmark.

---

## 🔧 How It Works Automatically

### When Tourism Office Adds New Spot:

1. **Admin Form Captures Coordinates**
   - Tourism officer creates spot in admin panel
   - Provides latitude/longitude (manual entry or map picker)

2. **Coordinates Saved to Database**
   ```sql
   INSERT INTO geo_points (entity_type, entity_id, latitude, longitude, accuracy)
   VALUES ('tourist_spot', 26, 16.1234, 120.5678, 'exact');
   ```

3. **User Visits Spot Page**
   - API `/tripko-backend/api/map/markers.php` fetches spot data
   - Includes coordinates from `geo_points` table

4. **"Get Directions" Button Works Automatically**
   - JavaScript reads `currentDestination.geometry.coordinates`
   - Builds Google Maps URL dynamically
   - Opens in new tab

**No manual Google Maps link configuration needed!**

---

## 🛠️ Admin Interface (Tourism Office)

### Current Spot Creation Flow
Check if your admin form (`tripko-backend/admin/spot-management/`) includes coordinate input fields:

```html
<!-- These fields should exist in your admin form: -->
<input type="number" step="0.0000001" name="latitude" placeholder="16.1234567">
<input type="number" step="0.0000001" name="longitude" placeholder="120.1234567">
```

### If Fields Are Missing
Add this to your admin spot creation form (see optional enhancement script).

### Getting Coordinates for New Spots
**Option 1:** Right-click on Google Maps → "What's here?" → Copy coordinates

**Option 2:** Use map picker (we can add this if needed)

---

## 📊 Database Tools

### Check Spots Without Coordinates
```sql
CALL check_spots_missing_coordinates();
```

### Get Google Maps URL Programmatically
```sql
-- In PHP/API:
SELECT get_google_maps_url(5) AS maps_url;
-- Returns: https://www.google.com/maps/dir/?api=1&destination=16.1989000,120.0108000
```

### View All Spots with Integration Status
```sql
SELECT * FROM vw_spots_with_maps;
```

---

## 🔍 Troubleshooting

### "Get Directions" Button Not Working
1. **Check if coordinates exist:**
   ```sql
   SELECT * FROM geo_points WHERE entity_type='tourist_spot' AND entity_id=YOUR_SPOT_ID;
   ```

2. **Check frontend JavaScript console** for errors

3. **Verify API response:**
   ```
   http://localhost/tripko-system/tripko-backend/api/map/markers.php
   ```
   Should return GeoJSON with coordinates

### New Spots Not Getting Coordinates
1. **Check admin form** has latitude/longitude fields
2. **Check INSERT query** in spot creation PHP script includes:
   ```php
   $stmt = $pdo->prepare("INSERT INTO geo_points (entity_type, entity_id, latitude, longitude) VALUES (?, ?, ?, ?)");
   $stmt->execute(['tourist_spot', $spot_id, $latitude, $longitude]);
   ```

---

## 🎯 Next Steps

### Optional Enhancements (Let me know if you want these!)

1. **Map Picker for Admin Panel**
   - Click map to select coordinates instead of manual entry
   - Visual confirmation of spot location

2. **User Location as Origin**
   - Detect user's current position
   - Add to Google Maps URL: `&origin=USER_LAT,USER_LNG`

3. **Travel Mode Selection**
   - Let users choose: driving, walking, bicycling, transit
   - Add to URL: `&travelmode=driving`

4. **Multiple Navigation Apps**
   - Waze: `https://waze.com/ul?ll=LAT,LNG&navigate=yes`
   - Apple Maps: `https://maps.apple.com/?daddr=LAT,LNG`

5. **Coordinate Import Tool**
   - Bulk upload coordinates via CSV
   - Auto-geocode from addresses

---

## 📝 Summary

✅ **All existing spots now have coordinates**  
✅ **Google Maps integration works automatically**  
✅ **New spots added by tourism offices will work the same way** (if coordinates are provided)  
✅ **No manual configuration needed per spot**  
✅ **Database validation ensures coordinate quality**  

---

## 🆘 Support

If you encounter any issues:
1. Run verification queries above
2. Check phpMyAdmin for errors
3. Test with Hundred Islands (spot_id: 5) first
4. Ask me for help with specific error messages!

---

**Migration Created:** October 20, 2025  
**Compatible With:** TripKo System v1.0 (MariaDB 10.4.32)
