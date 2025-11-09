<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/db.php';

try {
    // Get ID from query string
    $id = isset($_GET['id']) ? intval($_GET['id']) : die(json_encode([
        "success" => false,
        "message" => "Missing itinerary ID"
    ]));

    // Prepare query
    $query = "SELECT i.*, t.name as town_name 
              FROM itineraries i 
              LEFT JOIN towns t ON i.town_id = t.town_id
              WHERE i.itinerary_id = ?";

              

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $itinerary = $result->fetch_assoc();
            
            // Format the response
            $formatted_itinerary = array(
                'itinerary_id' => intval($itinerary['itinerary_id']),
                'name' => $itinerary['name'],
                'description' => $itinerary['description'],
                'town_id' => intval($itinerary['town_id']),
                'town_name' => $itinerary['town_name'],
                'environmental_fee' => $itinerary['environmental_fee'] ?? '',
                'image_path' => $itinerary['image_path'] ?? null,
                'status' => $itinerary['status'] ?? 'active',
                'created_at' => $itinerary['created_at']
            );

            echo json_encode([
                "success" => true,
                "itinerary" => $formatted_itinerary
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Itinerary not found"
            ]);
        }
    } else {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }
} catch (Exception $e) {
    error_log("Error in read_single.php: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Error fetching itinerary details",
        "error" => $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}