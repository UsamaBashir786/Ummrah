<?php
session_name("admin_session");
session_start();
include 'connection/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
  header('Content-Type: application/json');
  echo json_encode([
    'success' => false,
    'message' => 'Unauthorized access'
  ]);
  exit();
}

// Check if booking ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
  header('Content-Type: application/json');
  echo json_encode([
    'success' => false,
    'message' => 'Booking ID is required'
  ]);
  exit();
}

$booking_id = $_GET['id'];

// Get booking details
$query = "SELECT tb.*, u.full_name, u.email, u.phone_number 
          FROM transportation_bookings tb
          JOIN users u ON tb.user_id = u.id
          WHERE tb.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  header('Content-Type: application/json');
  echo json_encode([
    'success' => false,
    'message' => 'Booking not found'
  ]);
  exit();
}

$booking = $result->fetch_assoc();

// Return booking details as JSON
header('Content-Type: application/json');
echo json_encode([
  'success' => true,
  'booking' => $booking
]);
exit();
