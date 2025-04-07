<?php
session_name("admin_session");
session_start();
require_once 'connection/connection.php';

if (!isset($_SESSION['admin_email'])) {
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit();
}

// Set header to ensure JSON response
header('Content-Type: application/json');

// Debug logging
error_log("Received request with GET parameters: " . print_r($_GET, true));

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $userId = (int)$_GET['id'];
    
    // Debug logging
    error_log("Attempting to delete user with ID: " . $userId);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First, delete all related bookings
        $stmt = $conn->prepare("DELETE FROM flight_bookings WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();

        // Then delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        
        if ($stmt->execute()) {
            $conn->commit();
            echo json_encode(['success' => true]);
        } else {
            throw new Exception($stmt->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error deleting user: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    error_log("No valid user ID provided in request");
    echo json_encode(['success' => false, 'message' => 'No valid user ID provided']);
}

$conn->close();
