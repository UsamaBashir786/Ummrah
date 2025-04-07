<?php
// Save this as check-assigned-seats.php
session_name("admin_session");
session_start();
include 'connection/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Check if we have the required parameters
if (!isset($_GET['flight_id']) || !isset($_GET['seat_type'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$flight_id = $_GET['flight_id'];
$seat_type = $_GET['seat_type'];
$edit_mode = isset($_GET['edit_mode']) && $_GET['edit_mode'] === 'true';
$current_seat = isset($_GET['current_seat']) ? $_GET['current_seat'] : '';

// Get already assigned seats
$assigned_seats_sql = "SELECT seat_number FROM flight_assign 
                      WHERE flight_id = ? AND seat_type = ? AND status != 'cancelled'
                      UNION 
                      SELECT seat_number FROM package_assign 
                      WHERE flight_id = ? AND seat_type = ?";

$assigned_seats_stmt = $conn->prepare($assigned_seats_sql);
$assigned_seats_stmt->bind_param("isis", $flight_id, $seat_type, $flight_id, $seat_type);
$assigned_seats_stmt->execute();
$assigned_seats_result = $assigned_seats_stmt->get_result();

$assigned_seats = [];
while ($row = $assigned_seats_result->fetch_assoc()) {
    // If we're in edit mode and this is the current seat, don't include it as "assigned"
    if (!($edit_mode && $row['seat_number'] === $current_seat)) {
        $assigned_seats[] = $row['seat_number'];
    }
}

header('Content-Type: application/json');
echo json_encode(['assigned_seats' => $assigned_seats]);
?>