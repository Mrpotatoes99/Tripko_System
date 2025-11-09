# Tripko DB migrations

This folder contains SQL migration scripts you can run in phpMyAdmin after importing the main database dump.

## Fix a common import error (views stand-in)

If you see an error like:

- A symbol name was expected! (near ")" ...)
- #1064 syntax error near `CREATE TABLE itineraries_items ( );`

Your dump includes empty "stand-in" tables for some VIEWs. Remove those blocks before import, then run our replacement view script.

### What to delete from the dump file
Open your .sql dump and delete both the "Stand-in" and "Structure for view" blocks for these three views:

- itineraries_items
- vw_itineraries_overview
- vw_itinerary_items_flat

They look like this (examples):

```
-- Stand-in structure for view `itineraries_items`
CREATE TABLE `itineraries_items` (
);
...
-- Structure for view `itineraries_items`
DROP TABLE IF EXISTS `itineraries_items`;
CREATE ... VIEW `itineraries_items` AS ... (references itinerary_days/itinerary_items)
```

Do the same for `vw_itineraries_overview` and `vw_itinerary_items_flat`.

Then import the dump again.

### After import: create compatible views
Run the script in this folder:

- 20251013_flat_views_fix.sql

It will:
- Drop any conflicting stand-in tables/views for the three views
- Re-create them to work with the flattened `itineraries` table
- Avoid DEFINER so it works on any machine

### Verify
In phpMyAdmin, run:

```
SHOW FULL TABLES WHERE Table_type = 'VIEW';
SELECT * FROM vw_itineraries_overview LIMIT 5;
SELECT * FROM itineraries_items LIMIT 5;
SELECT * FROM vw_itinerary_items_flat LIMIT 5;
```

If these SELECTs return rows, you’re good.

### Tip for future exports
When exporting from phpMyAdmin:
- Disable or avoid exporting "stand-in structure for views" (or be ready to remove them)
- Prefer not adding a DEFINER for views/procedures, or replace with `SQL SECURITY INVOKER`

This keeps imports portable for your teammates.
