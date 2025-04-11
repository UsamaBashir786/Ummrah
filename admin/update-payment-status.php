<?php
session_name("admin_session");
session_start();
include '../connection/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
  echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
  exit();
}

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'message' => 'Invalid request method']);
  exit();
}

// Get the booking ID and new payment status from the POST data
$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
$new_payment_status = isset($_POST['payment_status']) ? $_POST['payment_status'] : '';

if ($booking_id <= 0 || !in_array($new_payment_status, ['paid', 'unpaid'])) {
  echo json_encode(['success' => false, 'message' => 'Invalid booking ID or payment status']);
  exit();
}

// Update the payment status in the database
$update_sql = "UPDATE transportation_bookings 
               SET payment_status = ?, updated_at = NOW() 
               WHERE id = ?";

$stmt = $conn->prepare($update_sql);
if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
  exit();
}

$stmt->bind_param('si', $new_payment_status, $booking_id);

if ($stmt->execute()) {
  echo json_encode(['success' => true, 'message' => 'Payment status updated successfully']);
} else {
  echo json_encode(['success' => false, 'message' => 'Error updating payment status: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
