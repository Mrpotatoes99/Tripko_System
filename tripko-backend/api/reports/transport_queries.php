<?php
// Popular route query
function getPopularRouteQuery() {
    return "SELECT 
        CONCAT(tl1.location_name, ' to ', tl2.location_name) as route_name,
        tl1.location_name as from_town,
        tl2.location_name as to_town,
        COUNT(rtt.route_id) as usage_count,
        tr.created_at
        FROM transport_route tr
        JOIN terminal_locations tl1 ON tl1.terminal_id = tr.origin_terminal_id
        JOIN terminal_locations tl2 ON tl2.terminal_id = tr.destination_terminal_id
        LEFT JOIN route_transport_types rtt ON tr.route_id = rtt.route_id
        WHERE tr.status = 'active'
        GROUP BY tr.route_id, tl1.location_name, tl2.location_name, tr.created_at
        ORDER BY usage_count DESC, tr.created_at DESC
        LIMIT 1";
}

// Transport type distribution query
function getTypeDistributionQuery() {
    return "SELECT 
        tt.type as type_name,
        COUNT(DISTINCT tr.route_id) as count
        FROM transportation_type tt
        INNER JOIN route_transport_types rtt ON tt.transport_type_id = rtt.type_id
        INNER JOIN transport_route tr ON rtt.route_id = tr.route_id
        WHERE tr.status = 'active'
        GROUP BY tt.transport_type_id, tt.type
        ORDER BY count DESC";
}

// Recent routes query
function getRecentRoutesQuery() {
    return "SELECT 
        CONCAT(tl1.location_name, ' to ', tl2.location_name) as name,
        tl1.location_name as from_town,
        tl2.location_name as to_town,
        DATE_FORMAT(COALESCE(tr.created_at, CURRENT_TIMESTAMP), '%Y-%m-%d') as added_date,
        GROUP_CONCAT(tt.type ORDER BY tt.type SEPARATOR ', ') as transport_types
        FROM transport_route tr
        JOIN terminal_locations tl1 ON tr.origin_terminal_id = tl1.terminal_id
        JOIN terminal_locations tl2 ON tr.destination_terminal_id = tl2.terminal_id
        LEFT JOIN route_transport_types rtt ON tr.route_id = rtt.route_id
        LEFT JOIN transportation_type tt ON rtt.type_id = tt.transport_type_id
        WHERE tr.status = 'active'
        GROUP BY tr.route_id, tl1.location_name, tl2.location_name, tr.created_at
        ORDER BY tr.created_at DESC
        LIMIT 5";
}
