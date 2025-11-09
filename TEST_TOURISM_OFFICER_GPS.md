# 🧪 Quick Test Guide: Tourism Officer GPS Coordinates

## ✅ Quick Test Checklist

### Phase 1: Test Adding New Spot with Coordinates

**Step 1: Login as Tourism Officer**
- [ ] Navigate to your tourism officer login page
- [ ] Login with your tourism officer credentials
- [ ] Verify you're on the dashboard

**Step 2: Open Add New Spot Form**
- [ ] Click "Tourist Spots" in sidebar
- [ ] Click "Add New Tourist Spot" button
- [ ] Verify the form loads

**Step 3: Find GPS Coordinates Section**
- [ ] Scroll down to find the GPS Coordinates section
- [ ] You should see a **gradient-styled box** with:
  - 🗺️ Icon and "GPS Coordinates" title
  - Three input fields: Latitude, Longitude, Accuracy
  - Blue info panel with instructions

**Step 4: Fill in Test Spot**
Use these test values:

```
Name: Test Waterfall
Category: Nature
Description: Beautiful waterfall for testing GPS feature
Location: Bolinao, Pangasinan
Contact Info: 0917-123-4567
Operating Hours: 8:00 AM - 5:00 PM
Entrance Fee: ₱50

GPS COORDINATES:
Latitude: 16.3864
Longitude: 119.8894
Accuracy: exact
```

- [ ] Fill in all the fields above
- [ ] Upload at least one test image
- [ ] Click "Create Tourist Spot"
- [ ] Wait for success message

**Step 5: Verify Database Save**

Open phpMyAdmin and run:

```sql
-- Find your test spot
SELECT 
    ts.spot_id,
    ts.name,
    gp.latitude,
    gp.longitude,
    gp.accuracy
FROM tourist_spots ts
LEFT JOIN geo_points gp ON gp.entity_type = 'tourist_spot' AND gp.entity_id = ts.spot_id
WHERE ts.name = 'Test Waterfall';
```

Expected Result:
```
spot_id | name            | latitude  | longitude  | accuracy
--------|-----------------|-----------|------------|----------
XX      | Test Waterfall  | 16.3864000| 119.8894000| exact
```

- [ ] Verify coordinates appear in query result
- [ ] Verify accuracy is set correctly

---

### Phase 2: Test Editing Existing Spot

**Step 1: Edit the Test Spot**
- [ ] In Tourist Spots list, find "Test Waterfall"
- [ ] Click "Edit" button
- [ ] Form should load with existing data

**Step 2: Verify Coordinates Load**
- [ ] Scroll to GPS Coordinates section
- [ ] **Latitude field should show**: 16.3864
- [ ] **Longitude field should show**: 119.8894
- [ ] **Accuracy dropdown should show**: exact

**Step 3: Update Coordinates**
Change to new test values:
```
Latitude: 16.3865
Longitude: 119.8895
Accuracy: approximate
```

- [ ] Change the values
- [ ] Click "Update Tourist Spot"
- [ ] Wait for success message

**Step 4: Verify Update in Database**
Run the same query:
```sql
SELECT latitude, longitude, accuracy
FROM geo_points
WHERE entity_type = 'tourist_spot' 
AND entity_id = (SELECT spot_id FROM tourist_spots WHERE name = 'Test Waterfall');
```

Expected:
```
latitude  | longitude  | accuracy
----------|------------|------------
16.3865000| 119.8895000| approximate
```

- [ ] Verify coordinates updated
- [ ] Verify accuracy changed to 'approximate'

---

### Phase 3: Test User-Side "How to Get There?" Button

**Step 1: Logout from Tourism Officer**
- [ ] Click "Sign Out" in tourism officer interface
- [ ] Verify you're logged out

**Step 2: Visit User-Side Category Page**
Navigate to the category where your test spot should appear:
```
tripko-frontend/file_html/user side/beaches.php
(or the appropriate category page)
```

- [ ] Find "Test Waterfall" card
- [ ] Click on it to open modal

**Step 3: Verify Button Appears**
In the modal, look for:
- [ ] **"How to Get There?"** button with gradient styling
- [ ] Button should have a 🧭 navigation icon
- [ ] Button should have gradient colors (teal/cyan)

**Step 4: Test Button Functionality**
- [ ] Click the "How to Get There?" button
- [ ] A new tab should open
- [ ] Google Maps should load with directions to: `16.3865, 119.8895`
- [ ] Map should show correct location (Bolinao area)

---

### Phase 4: Test Spot Without Coordinates

**Step 1: Create Spot Without Coordinates**
- [ ] Login as tourism officer again
- [ ] Add new tourist spot: "Test No Coords"
- [ ] Fill in all required fields EXCEPT coordinates
- [ ] Leave Latitude, Longitude empty
- [ ] Submit the form
- [ ] Should save successfully

**Step 2: Verify No Button Shows**
- [ ] Logout and visit user-side
- [ ] Find "Test No Coords" spot
- [ ] Open modal
- [ ] **"How to Get There?" button should NOT appear**
- [ ] This proves optional functionality works

---

## 🎯 Success Criteria

All tests pass if:

✅ **GPS coordinate fields appear** in tourism officer form
✅ **Info panel with instructions** is visible and helpful
✅ **Coordinates save to database** when creating new spots
✅ **Coordinates load correctly** when editing existing spots
✅ **Coordinates update in database** when editing
✅ **"How to Get There?" button appears** on user-side for spots with coordinates
✅ **Button opens Google Maps** with correct location
✅ **Spots without coordinates work fine** but don't show button

---

## 🐛 Common Issues & Solutions

### Issue: Coordinates not saving
**Check:**
```sql
-- Does geo_points table exist?
SHOW TABLES LIKE 'geo_points';

-- Check table structure
DESCRIBE geo_points;
```

**Fix:** If table missing, run:
```sql
-- Import: tripko-backend/migrations/20251020_google_maps_integration.sql
```

---

### Issue: Form fields not showing
**Check:**
- Clear browser cache (Ctrl+Shift+R)
- Verify file saved: `tripko-frontend/file_html/tourism_offices/tourist_spots.php`
- Check browser console for JavaScript errors

---

### Issue: Button not appearing on user-side
**Check:**
```sql
-- Verify coordinates exist
SELECT * FROM geo_points 
WHERE entity_type = 'tourist_spot' 
AND entity_id = [YOUR_SPOT_ID];
```

**Debug:**
- Open browser console on user-side
- Check Network tab for API call to `get_coordinates.php`
- Look for error messages

---

## 📊 Test Data: Real Pangasinan Coordinates

Use these for additional testing:

```javascript
// Bolinao Area
{ name: "Bolinao Falls", lat: 16.3864, lng: 119.8894, accuracy: "exact" }
{ name: "Enchanted Cave", lat: 16.3845, lng: 119.9012, accuracy: "exact" }
{ name: "Patar Beach", lat: 16.4012, lng: 119.8756, accuracy: "approximate" }

// Alaminos
{ name: "Hundred Islands", lat: 16.1989, lng: 120.0108, accuracy: "approximate" }

// Manaoag
{ name: "Manaoag Church", lat: 16.0437, lng: 120.4855, accuracy: "exact" }
```

---

## 🔍 Browser Console Commands

### Test Coordinate Fetch
Open user-side modal and run in console:
```javascript
fetch('http://localhost/tripko-system/tripko-backend/api/spots/get_coordinates.php?spot_id=1')
  .then(r => r.json())
  .then(data => console.log(data));
```

Expected output:
```json
{
  "success": true,
  "latitude": "16.3865000",
  "longitude": "119.8895000",
  "accuracy": "approximate",
  "google_maps_url": "https://www.google.com/maps/dir/?api=1&destination=16.3865,119.8895"
}
```

---

## ✨ Visual Indicators

### What to Look For in Tourism Officer Form:

```
✓ Gradient background (light blue/beige) around GPS section
✓ Icons: 🗺️ (map), 🧭 (compass), 🎯 (crosshairs)
✓ Blue info panel with bullet points
✓ Number input fields with step="0.0000001"
✓ Dropdown with "Exact" and "Approximate" options
```

### What to Look For on User-Side:

```
✓ Gradient button with teal/cyan colors
✓ Navigation icon with pulse animation
✓ Button text: "How to Get There?"
✓ Smooth hover effect
✓ Opens in new tab when clicked
```

---

## 📱 Mobile Testing

Don't forget to test on mobile:

1. **Tourism Officer Form:**
   - [ ] GPS section responsive (stacks on mobile)
   - [ ] Input fields easy to tap
   - [ ] Info panel readable on small screen

2. **User-Side Button:**
   - [ ] Button visible and tappable
   - [ ] Opens Google Maps app (not browser)
   - [ ] Navigation starts automatically

---

## 🎉 Cleanup After Testing

When done testing:

```sql
-- Delete test spots
DELETE FROM tourist_spots WHERE name LIKE 'Test%';

-- Clean up orphaned coordinates (if any)
DELETE FROM geo_points 
WHERE entity_type = 'tourist_spot' 
AND entity_id NOT IN (SELECT spot_id FROM tourist_spots);
```

---

**Happy Testing! 🧪✨**

If all tests pass, congratulations! Your tourism officer interface now captures GPS coordinates seamlessly, and visitors can navigate to any spot with one click! 🎊
