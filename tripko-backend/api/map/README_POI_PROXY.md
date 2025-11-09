# POI Proxy Enhancement Plan

## Current Implementation
The map feature now supports nearby restaurants and lodging via direct frontend calls to Overpass API:

- **Data Source**: OpenStreetMap via Overpass API (overpass-api.de)
- **Queries**: Restaurant/cafe/bar and hotel/lodging amenities within 2km radius
- **Caching**: Frontend localStorage with 6-hour expiry per spot
- **Rate Limiting**: Basic request throttling (one at a time)

## Recommended Backend Proxy

### Benefits
1. **Rate Limit Control**: Implement server-side rate limiting to respect Overpass API limits
2. **Data Sanitization**: Filter and validate POI data before sending to frontend
3. **Enhanced Caching**: Server-side Redis/file cache with longer expiry (24-48 hours)
4. **Error Handling**: Graceful fallbacks and retry logic
5. **Analytics**: Track popular POI queries for insights
6. **Security**: Hide direct Overpass API usage from client

### Implementation Structure
```
tripko-backend/api/map/
├── poi_proxy.php          # Main proxy endpoint
├── classes/
│   ├── OverpassClient.php # Overpass API client with rate limiting
│   └── POICache.php       # Cache management
└── config/
    └── poi_config.php     # POI categories, radius limits, cache settings
```

### Endpoint Design
**GET** `/tripko-backend/api/map/poi_proxy.php`

**Parameters:**
- `lat`: Latitude (required)
- `lon`: Longitude (required) 
- `type`: restaurant|lodging (required)
- `radius`: meters (optional, max 5000, default 2000)

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": "osm_node_123456",
      "name": "Restaurant Name",
      "type": "restaurant",
      "lat": 16.0123,
      "lon": 120.3456,
      "amenity": "restaurant",
      "cuisine": "filipino",
      "phone": "+639123456789"
    }
  ],
  "cached": true,
  "count": 15
}
```

### Rate Limiting Strategy
- **Per IP**: 60 requests/hour
- **Per Session**: 30 requests/hour  
- **Global**: Respect Overpass API guidelines (max 2 concurrent)
- **Backoff**: Exponential backoff on 429/503 responses

### Cache Strategy
1. **Key Format**: `poi_{type}_{lat}_{lon}_{radius}`
2. **TTL**: 24 hours for popular areas, 6 hours for rural
3. **Storage**: File cache or Redis if available
4. **Invalidation**: Manual admin endpoint for data updates

### Error Handling
- **Network Timeout**: Return cached data if available, else error
- **API Limit**: Return friendly message with retry suggestion
- **Invalid Coordinates**: Validate bounds within Philippines
- **Empty Results**: Return empty array with appropriate message

### Future Enhancements
1. **Local POI Database**: Store frequently queried POIs in local DB
2. **Admin Management**: Interface to add/edit/verify POI data
3. **User Reviews**: Allow tourists to rate/review POIs
4. **Real-time Updates**: Webhook integration for POI changes
5. **Clustering**: Server-side marker clustering for dense areas

### Migration Steps
1. Implement `poi_proxy.php` with basic caching
2. Update frontend `map.js` to use proxy endpoint instead of direct Overpass
3. Add rate limiting and error handling
4. Deploy and monitor API usage
5. Gradually enhance with local POI database integration

### Security Considerations
- Validate all input parameters
- Implement CORS headers appropriately  
- Rate limit by session and IP
- Log suspicious activity patterns
- Consider API key requirement for production