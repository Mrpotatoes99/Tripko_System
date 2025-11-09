# TripAdvisor-Style Features Migration Guide

## Overview
This guide explains how to run the database migrations that add TripAdvisor-style booking features to your Tripko system.

---

## 📋 What's Included

### Part 1: Core Booking Features (`20251020_tripadvisor_features_part1.sql`)
- **Pricing & Booking Fields**: Base price, currency, duration, start time
- **Trust Signals**: Mobile ticket, instant confirmation, free cancellation
- **Traveler Limits**: Min/max travelers configuration
- **Content**: Highlights, what's included/excluded, what to bring
- **Meeting Points**: Pickup details, meeting/end points
- **Pricing Tiers**: Adult, Child, Senior, Infant pricing
- **FAQs**: Frequently asked questions with answers
- **Photo Gallery**: Official and traveler photos
- **Enhanced Reviews**: Traveler type, travel date, verified purchases

### Part 2: Analytics & Booking System (`20251020_tripadvisor_features_part2.sql`)
- **Review Breakdown View**: Rating distribution (5★, 4★, etc.)
- **Booking Detail View**: Complete itinerary info for booking page
- **Auto-update Stats**: Triggers to maintain review counts/ratings
- **Booking Tables**: Full booking system infrastructure
- **Performance Indexes**: Optimized database queries

---

## 🚀 How to Run

### Option 1: phpMyAdmin (Recommended for Beginners)

1. **Open phpMyAdmin**
   - Go to http://localhost/phpmyadmin
   - Login with your credentials

2. **Select Database**
   - Click on `tripko_db` in the left sidebar

3. **Run Part 1**
   - Click "SQL" tab at the top
   - Open `20251020_tripadvisor_features_part1.sql` in a text editor
   - Copy ALL the contents
   - Paste into the SQL query box
   - Click "Go" button
   - Wait for success message

4. **Run Part 2**
   - Stay in the "SQL" tab
   - Open `20251020_tripadvisor_features_part2.sql`
   - Copy ALL the contents
   - Paste into the SQL query box
   - Click "Go" button
   - Wait for success message

### Option 2: Command Line (Faster)

```bash
# Navigate to migrations directory
cd c:\xampp\htdocs\tripko-system\tripko-backend\migrations

# Run Part 1
mysql -u root -p tripko_db < 20251020_tripadvisor_features_part1.sql

# Run Part 2
mysql -u root -p tripko_db < 20251020_tripadvisor_features_part2.sql
```

---

## ✅ Verification

After running both migrations, check that everything is working:

### 1. Check New Tables
```sql
SHOW TABLES LIKE '%itinerary%';
```
You should see:
- `itinerary_pricing_tiers`
- `itinerary_faqs`
- `itinerary_photos`
- `itinerary_bookings`
- `booking_travelers`
- `review_photos`

### 2. Check Updated Itinerary
```sql
SELECT 
    itinerary_id, 
    name, 
    base_price, 
    duration_hours, 
    start_time,
    mobile_ticket,
    instant_confirmation,
    free_cancellation,
    total_reviews,
    average_rating
FROM itineraries 
WHERE itinerary_id = 3;
```

Expected Result:
- base_price: 1200.00
- duration_hours: 8
- start_time: 08:00:00
- mobile_ticket: 1
- instant_confirmation: 1
- free_cancellation: 1

### 3. Check Pricing Tiers
```sql
SELECT * FROM itinerary_pricing_tiers WHERE itinerary_id = 3;
```

Expected Result: 4 rows (Adult, Child, Senior Citizen, Infant)

### 4. Check FAQs
```sql
SELECT question FROM itinerary_faqs WHERE itinerary_id = 3;
```

Expected Result: 8 FAQ questions

### 5. Check Views
```sql
SELECT * FROM vw_itinerary_booking_detail WHERE itinerary_id = 3;
SELECT * FROM vw_itinerary_review_breakdown WHERE itinerary_id = 3;
```

Both should return complete data.

---

## 📊 What Changed

### Database Schema Changes

#### New Columns in `itineraries` table:
- `base_price` - Starting price per person
- `duration_hours` - Tour duration
- `start_time` - Tour start time
- `mobile_ticket` - Mobile ticket available (1/0)
- `instant_confirmation` - Instant confirmation (1/0)
- `free_cancellation` - Free cancellation available (1/0)
- `highlights` - JSON array of tour highlights
- `whats_included` - JSON array of inclusions
- `whats_excluded` - JSON array of exclusions
- `what_to_bring` - JSON array of items to bring
- `meeting_point` - Meeting location details
- `total_reviews` - Cached review count
- `average_rating` - Cached average rating

#### New Columns in `reviews` table:
- `traveler_type` - family, couple, solo, business, friends
- `travel_date` - Date of travel
- `verified_purchase` - Verified booking (1/0)
- `has_photos` - Review has photos (1/0)

#### New Tables:
1. **itinerary_pricing_tiers** - Different prices for Adult/Child/Senior
2. **itinerary_faqs** - Frequently asked questions
3. **itinerary_photos** - Official & traveler photos
4. **itinerary_bookings** - Booking records
5. **booking_travelers** - Individual traveler details
6. **review_photos** - Photos attached to reviews

#### New Views:
1. **vw_itinerary_review_breakdown** - Rating distribution & statistics
2. **vw_itinerary_booking_detail** - Complete booking page data

#### New Stored Procedures:
1. **update_itinerary_review_stats** - Updates review counts/ratings

#### New Triggers:
1. **after_itinerary_review_insert** - Auto-update stats on new review
2. **after_itinerary_review_update** - Auto-update stats on review edit
3. **after_itinerary_review_delete** - Auto-update stats on review delete

---

## 🎯 Sample Data Added

The migration automatically populates the **Hundred Islands Day Tour** (itinerary_id = 3) with:

- **Base Price**: ₱1,200.00 per adult
- **Duration**: 8 hours
- **Start Time**: 8:00 AM
- **Pricing Tiers**:
  - Adult (13-64): ₱1,200
  - Child (4-12): ₱900
  - Senior (65+): ₱1,000
  - Infant (0-3): Free
- **8 FAQs** covering common questions
- **6 Highlights** (snorkeling, islands, guide, etc.)
- **8 Inclusions** (boat, guide, lunch, etc.)
- **6 Exclusions** (hotel pickup, personal expenses, etc.)
- **8 What to Bring** items (swimwear, sunscreen, etc.)
- **Trust Signals**: Mobile ticket ✓, Instant confirmation ✓, Free cancellation ✓
- **3 Official Photos** in gallery

---

## 🔄 Rollback (If Something Goes Wrong)

If you need to undo the changes:

1. **Open phpMyAdmin** → Select `tripko_db` → Click "SQL" tab

2. **Run Part 2 Rollback** (run this first):
```sql
DROP TRIGGER IF EXISTS `after_itinerary_review_insert`;
DROP TRIGGER IF EXISTS `after_itinerary_review_update`;
DROP TRIGGER IF EXISTS `after_itinerary_review_delete`;
DROP PROCEDURE IF EXISTS `update_itinerary_review_stats`;
DROP TABLE IF EXISTS `booking_travelers`;
DROP TABLE IF EXISTS `itinerary_bookings`;
DROP VIEW IF EXISTS `vw_itinerary_booking_detail`;
DROP VIEW IF EXISTS `vw_itinerary_review_breakdown`;
```

3. **Run Part 1 Rollback** (run this second):
```sql
ALTER TABLE `itineraries` 
DROP COLUMN `base_price`,
DROP COLUMN `price_currency`,
DROP COLUMN `duration_hours`,
DROP COLUMN `start_time`,
DROP COLUMN `mobile_ticket`,
DROP COLUMN `instant_confirmation`,
DROP COLUMN `free_cancellation`,
DROP COLUMN `cancellation_hours`,
DROP COLUMN `min_travelers`,
DROP COLUMN `max_travelers`,
DROP COLUMN `highlights`,
DROP COLUMN `whats_included`,
DROP COLUMN `whats_excluded`,
DROP COLUMN `what_to_bring`,
DROP COLUMN `accessibility_info`,
DROP COLUMN `additional_info`,
DROP COLUMN `meeting_point`,
DROP COLUMN `end_point`,
DROP COLUMN `pickup_offered`,
DROP COLUMN `pickup_details`,
DROP COLUMN `total_reviews`,
DROP COLUMN `average_rating`;

DROP TABLE IF EXISTS `review_photos`;
DROP TABLE IF EXISTS `itinerary_photos`;
DROP TABLE IF EXISTS `itinerary_faqs`;
DROP TABLE IF EXISTS `itinerary_pricing_tiers`;

ALTER TABLE `reviews`
DROP COLUMN `traveler_type`,
DROP COLUMN `travel_date`,
DROP COLUMN `verified_purchase`,
DROP COLUMN `has_photos`;
```

---

## 🛠️ Troubleshooting

### Error: "Table already exists"
**Solution**: Some tables were already created. This is safe to ignore if you're re-running the migration.

### Error: "Cannot add foreign key constraint"
**Solution**: Make sure Part 1 completed successfully before running Part 2.

### Error: "Unknown column in field list"
**Solution**: 
1. Check that Part 1 completed successfully
2. Run this to verify columns exist:
```sql
DESCRIBE itineraries;
```

### Error: "Duplicate column name"
**Solution**: You might be running Part 1 twice. Check if columns already exist:
```sql
SHOW COLUMNS FROM itineraries LIKE 'base_price';
```

### JSON Support Issues
**Solution**: Make sure your MySQL/MariaDB version supports JSON (5.7+/10.2+):
```sql
SELECT VERSION();
```

---

## 📝 Next Steps

After running these migrations:

1. **Update Frontend Code**
   - Modify `things-to-do.php` to use new booking page design
   - Create sticky booking widget
   - Add pricing calculator
   - Build review breakdown display
   - Implement FAQ accordion

2. **Update Backend APIs**
   - Create endpoint: `/api/itinerary/booking_details.php`
   - Create endpoint: `/api/itinerary/pricing.php`
   - Create endpoint: `/api/itinerary/faqs.php`
   - Create endpoint: `/api/bookings/create.php`

3. **Test Everything**
   - Test booking detail view loads
   - Test pricing calculator
   - Test review statistics display
   - Test FAQ functionality

---

## 📧 Support

If you encounter issues:
1. Check error logs in `tripko-backend/error_log.txt`
2. Review phpMyAdmin error messages
3. Verify your MySQL/MariaDB version is compatible
4. Check that user has sufficient privileges

---

## 📅 Migration Log

| Date | Version | Description |
|------|---------|-------------|
| 2025-10-20 | 1.0 | Initial TripAdvisor-style features migration |

---

**Status**: ✅ Ready to run  
**Estimated Time**: 2-3 minutes  
**Risk Level**: Low (includes rollback scripts)  
**Backup Required**: ✅ Yes (always backup before major changes)
