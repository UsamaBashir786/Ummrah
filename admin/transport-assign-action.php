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
    $get_assign_sql = "SELECT * FROM transportation_assign WHERE id = ?";
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
    $booking_type = $assign_row['booking_type'] ?? 'transportation';
    $package_booking_id = $assign_row['package_booking_id'] ?? null;
    
    if ($action === 'complete') {
        // Update the assignment status to completed
        $update_assign_sql = "UPDATE transportation_assign SET status = 'completed', updated_at = NOW() WHERE id = ?";
        $update_assign_stmt = $conn->prepare($update_assign_sql);
        $update_assign_stmt->bind_param("i", $assign_id);
        
        // Update the booking status to completed
        $update_booking_sql = "UPDATE transportation_bookings SET booking_status = 'completed' WHERE id = ?";
        $update_booking_stmt = $conn->prepare($update_booking_sql);
        $update_booking_stmt->bind_param("i", $booking_id);
        
        // Execute both updates
        if ($update_assign_stmt->execute() && $update_booking_stmt->execute()) {
            // If this was a package transportation, update the package status if needed
            if ($booking_type === 'package' && $package_booking_id) {
                // You could add logic here to check if all package services are completed
                // and update the package status accordingly
            }
            
            echo json_encode(['status' => 'success', 'message' => 'Transportation service marked as completed']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update status']);
        }
    } elseif ($action === 'cancel') {
        // Update the assignment status to cancelled
        $update_assign_sql = "UPDATE transportation_assign SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
        $update_assign_stmt = $conn->prepare($update_assign_sql);
        $update_assign_stmt->bind_param("i", $assign_id);
        
        // Update the booking status based on the booking type
        if ($booking_type === 'transportation') {
            $update_booking_sql = "UPDATE transportation_bookings SET booking_status = 'pending' WHERE id = ?";
            $update_booking_stmt = $conn->prepare($update_booking_sql);
            $update_booking_stmt->bind_param("i", $booking_id);
        } else {
            // For package bookings, mark the transportation booking as cancelled
            $update_booking_sql = "UPDATE transportation_bookings SET booking_status = 'cancelled' WHERE id = ?";
            $update_booking_stmt = $conn->prepare($update_booking_sql);
            $update_booking_stmt->bind_param("i", $booking_id);
            
            // If this was a package transportation, update the package_assign table
            if ($package_booking_id) {
                $update_package_assign_sql = "UPDATE package_assign SET transport_id = NULL, transport_seat_number = NULL 
                                              WHERE booking_id = ? AND transport_id = ?";
                $update_package_assign_stmt = $conn->prepare($update_package_assign_sql);
                $update_package_assign_stmt->bind_param("ii", $package_booking_id, $booking_id);
                $update_package_assign_stmt->execute();
            }
        }
        
        // Execute both updates
        if ($update_assign_stmt->execute() && $update_booking_stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Transportation assignment cancelled']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update status']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>