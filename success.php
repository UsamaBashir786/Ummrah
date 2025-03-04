<?php
include "connection/connection.php";
session_start();

if (!isset($_GET['booking_id'])) {
  die("Error: Invalid request.");
}

$booking_id = intval($_GET['booking_id']);

// Update the booking to 'paid'
$query = "UPDATE package_booking SET payment_status = 'paid' WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $booking_id);

if ($stmt->execute()) {
  echo "<script>
    alert('Payment successful! Your booking is confirmed.');
    window.location.href = 'index.php';
  </script>";
} else {
  echo "Error updating payment status.";
}
