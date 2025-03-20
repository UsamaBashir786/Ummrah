<?php
session_start();
include 'connection/connection.php';

// Set character set
$conn->set_charset("utf8mb4");

// Get search parameters
$departure_city = isset($_POST['departure']) ? $conn->real_escape_string($_POST['departure']) : '';
$arrival_city = isset($_POST['arrival']) ? $conn->real_escape_string($_POST['arrival']) : '';
$departure_date = isset($_POST['departDate']) ? $conn->real_escape_string($_POST['departDate']) : '';
$cabin_class = isset($_POST['cabin']) ? $conn->real_escape_string($_POST['cabin']) : 'economy';
$return_date = isset($_POST['returnDate']) ? $conn->real_escape_string($_POST['returnDate']) : null;
$passengers = isset($_POST['passengers']) ? intval($_POST['passengers']) : 1;

// Initialize response array
$response = ['status' => 'success', 'flights' => []];

// Build base SQL query for outbound flights
$sql = "SELECT * FROM flights WHERE departure_city LIKE ? AND arrival_city LIKE ? AND departure_date = ?";

// Prepare statement
$stmt = $conn->prepare($sql);
$departure_city_param = "%$departure_city%";
$arrival_city_param = "%$arrival_city%";
$stmt->bind_param("sss", $departure_city_param, $arrival_city_param, $departure_date);

// Execute query
$stmt->execute();
$result = $stmt->get_result();

// Check if we have results
if ($result->num_rows > 0) {
    // Fetch all flights
    while ($row = $result->fetch_assoc()) {
        // Convert JSON fields from strings to PHP arrays
        $cabin_class_data = json_decode($row['cabin_class'], true);
        $prices_data = json_decode($row['prices'], true);
        $seats_data = json_decode($row['seats'], true);
        $stops_data = $row['stops'] ? json_decode($row['stops'], true) : null;

        // Only include flights that have the requested cabin class available
        if (isset($cabin_class_data[$cabin_class]) && $cabin_class_data[$cabin_class]) {
            // Calculate arrival time (this is a simplified calculation, adjust as needed)
            // In a real application, you might want to store arrival_time in your database
            // or use a more complex calculation based on flight duration and timezones
            $departure_timestamp = strtotime($row['departure_time']);
            $duration_hours = $stops_data ? 3 + rand(1, 3) : 2 + rand(1, 2); // Simplified: direct flights 2-4h, flights with stops 3-6h
            $arrival_timestamp = $departure_timestamp + ($duration_hours * 3600);
            $arrival_time = date('H:i:s', $arrival_timestamp);

            // Calculate price based on selected cabin class
            $price = isset($prices_data[$cabin_class]) ? $prices_data[$cabin_class] : $prices_data['economy'];

            // Check available seats
            $available_seats = isset($seats_data[$cabin_class]) ? $seats_data[$cabin_class] : 0;
            
            // Only include flights with enough available seats
            if ($available_seats >= $passengers) {
                // Add flight to response
                $flight = $row;
                $flight['arrival_time'] = $arrival_time; // Add calculated arrival time
                $flight['cabin_class'] = $row['cabin_class']; // Keep as JSON string for frontend
                $flight['prices'] = $row['prices']; // Keep as JSON string for frontend
                $flight['seats'] = $row['seats']; // Keep as JSON string for frontend
                $flight['stops'] = $row['stops']; // Keep as JSON string for frontend
                $response['flights'][] = $flight;
            }
        }
    }
}

// If we're looking for return flights as well
if ($return_date) {
    $response['return_flights'] = [];
    
    // Build SQL query for return flights
    $sql = "SELECT * FROM flights WHERE departure_city LIKE ? AND arrival_city LIKE ? AND departure_date = ?";
    
    // Prepare statement
    $stmt = $conn->prepare($sql);
    $return_departure_city_param = "%$arrival_city%"; // For return flight, departure is original arrival
    $return_arrival_city_param = "%$departure_city%"; // For return flight, arrival is original departure
    $stmt->bind_param("sss", $return_departure_city_param, $return_arrival_city_param, $return_date);
    
    // Execute query
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Check if we have results
    if ($result->num_rows > 0) {
        // Fetch all return flights
        while ($row = $result->fetch_assoc()) {
            // Convert JSON fields from strings to PHP arrays
            $cabin_class_data = json_decode($row['cabin_class'], true);
            $prices_data = json_decode($row['prices'], true);
            $seats_data = json_decode($row['seats'], true);
            $stops_data = $row['stops'] ? json_decode($row['stops'], true) : null;
            
            // Only include flights that have the requested cabin class available
            if (isset($cabin_class_data[$cabin_class]) && $cabin_class_data[$cabin_class]) {
                // Calculate arrival time
                $departure_timestamp = strtotime($row['departure_time']);
                $duration_hours = $stops_data ? 3 + rand(1, 3) : 2 + rand(1, 2);
                $arrival_timestamp = $departure_timestamp + ($duration_hours * 3600);
                $arrival_time = date('H:i:s', $arrival_timestamp);
                
                // Check available seats
                $available_seats = isset($seats_data[$cabin_class]) ? $seats_data[$cabin_class] : 0;
                
                // Only include flights with enough available seats
                if ($available_seats >= $passengers) {
                    // Add flight to response
                    $flight = $row;
                    $flight['arrival_time'] = $arrival_time;
                    $flight['cabin_class'] = $row['cabin_class'];
                    $flight['prices'] = $row['prices'];
                    $flight['seats'] = $row['seats'];
                    $flight['stops'] = $row['stops'];
                    $response['return_flights'][] = $flight;
                }
            }
        }
    }
}

// Close statement and connection
$stmt->close();
$conn->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);