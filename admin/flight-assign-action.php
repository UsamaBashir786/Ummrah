<?php
session_start();
include 'connection/connection.php'; // Include database connection

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// Check if request is POST and has the required parameters
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['assign_id'])) {
    $action = $_POST['action'];
    $assign_id = $_POST['assign_id'];
    
    // Get the assignment details
    $get_assign_sql = "SELECT * FROM package_assign WHERE id = ?";
    $get_assign_stmt = $conn->prepare($get_assign_sql);
    $get_assign_stmt->bind_param("i", $assign_id);
    $get_assign_stmt->execute();
    $get_assign_result = $get_assign_stmt->get_result();
    
    if ($get_assign_result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Assignment not found']);
        exit;
    }
    
    $assign_row = $get_assign_result->fetch_assoc();
    $booking_id = $assign_row['booking_id'];
    $flight_id = $assign_row['flight_id'];
    
    if ($action === 'remove') {
        // Remove flight assignment
        $update_assign_sql = "UPDATE package_assign SET flight_id = NULL, seat_type = NULL, seat_number = NULL WHERE id = ?";
        $update_assign_stmt = $conn->prepare($update_assign_sql);
        $update_assign_stmt->bind_param("i", $assign_id);
        
        if ($update_assign_stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Flight assignment removed successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to remove flight assignment']);
        }
    } else if ($action === 'change_seat') {
        // Validate seat type and number
        if (!isset($_POST['seat_type']) || !isset($_POST['seat_number'])) {
            echo json_encode(['status' => 'error', 'message' => 'Seat type and number are required']);
            exit;
        }
        
        $seat_type = $_POST['seat_type'];
        $seat_number = $_POST['seat_number'];
        
        // Update seat information
        $update_assign_sql = "UPDATE package_assign SET seat_type = ?, seat_number = ? WHERE id = ?";
        $update_assign_stmt = $conn->prepare($update_assign_sql);
        $update_assign_stmt->bind_param("ssi", $seat_type, $seat_number, $assign_id);
        
        if ($update_assign_stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Flight seat updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update seat information']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>