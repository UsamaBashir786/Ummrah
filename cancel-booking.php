<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

require_once 'connection/connection.php';

// Validate GET parameters
if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id']) || !isset($_GET['reference'])) {
  header("Location: user/index.php?error=invalid_parameters");
  exit();
}

$booking_id = (int)$_GET['booking_id'];
$booking_reference = $_GET['reference'];
$user_id = $_SESSION['user_id'];

// Verify the booking belongs to the user and is cancellable
$sql = "SELECT booking_status FROM transportation_bookings WHERE id = ? AND booking_reference = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isi", $booking_id, $booking_reference, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  header("Location: user/index.php?error=booking_not_found");
  exit();
}

$booking = $result->fetch_assoc();
if ($booking['booking_status'] !== 'pending' && $booking['booking_status'] !== 'confirmed') {
  header("Location: user/index.php?error=booking_not_cancellable");
  exit();
}

// Update the booking status to 'cancelled'
$update_sql = "UPDATE transportation_bookings SET booking_status = 'cancelled' WHERE id = ? AND booking_reference = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("is", $booking_id, $booking_reference);

if ($update_stmt->execute()) {
  header("Location: user/index.php?success=booking_cancelled");
} else {
  header("Location: user/index.php?error=cancellation_failed");
}

$update_stmt->close();
$stmt->close();
$conn->close();
