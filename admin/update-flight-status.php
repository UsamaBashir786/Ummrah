<?php
require_once 'connection/connection.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
  echo json_encode([
    'success' => false,
    'message' => 'Flight ID is required'
  ]);
  exit;
}

$flight_id = intval($_GET['id']);

// Get request body
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

// Check if status is provided
if (!isset($data['status']) || empty($data['status'])) {
  echo json_encode([
    'success' => false,
    'message' => 'Status is required'
  ]);
  exit;
}

$status = $data['status'];

// Validate status
$valid_statuses = ['scheduled', 'in_air', 'landed', 'delayed', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
  echo json_encode([
    'success' => false,
    'message' => 'Invalid status value'
  ]);
  exit;
}

// First check if a status column exists in the flights table
$check_column_sql = "SHOW COLUMNS FROM flights LIKE 'status'";
$check_result = $conn->query($check_column_sql);

if ($check_result->num_rows === 0) {
  // Status column doesn't exist, we'll need to handle this differently
  // For flights without a status column, we'll just update the bookings
  $success = true;

  if ($status == 'cancelled') {
    // Update all associated bookings
    $update_bookings_sql = "UPDATE flight_bookings SET booking_status = 'cancelled' WHERE flight_id = ? AND booking_status != 'cancelled'";
    $booking_stmt = $conn->prepare($update_bookings_sql);
    $booking_stmt->bind_param("i", $flight_id);
    $success = $booking_stmt->execute();
    $booking_stmt->close();
  }

  if ($success) {
    echo json_encode([
      'success' => true,
      'message' => 'Flight bookings updated successfully',
      'status' => $status,
      'note' => 'No status column in flights table; only bookings were updated'
    ]);
  } else {
    echo json_encode([
      'success' => false,
      'message' => 'Error updating flight bookings'
    ]);
  }
} else {
  // Status column exists, proceed with updating it
  $update_sql = "UPDATE flights SET status = ? WHERE id = ?";
  $stmt = $conn->prepare($update_sql);
  $stmt->bind_param("si", $status, $flight_id);

  if ($stmt->execute()) {
    // If flight is cancelled, update all associated bookings
    if ($status == 'cancelled') {
      $update_bookings_sql = "UPDATE flight_bookings SET booking_status = 'cancelled' WHERE flight_id = ? AND booking_status != 'cancelled'";
      $booking_stmt = $conn->prepare($update_bookings_sql);
      $booking_stmt->bind_param("i", $flight_id);
      $booking_stmt->execute();
      $booking_stmt->close();
    }

    echo json_encode([
      'success' => true,
      'message' => 'Flight status updated successfully',
      'status' => $status
    ]);
  } else {
    echo json_encode([
      'success' => false,
      'message' => 'Error updating flight status: ' . $stmt->error
    ]);
  }
  $stmt->close();
}

$conn->close();
