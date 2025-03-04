<?php
include 'connection/connection.php';

$seat_type = $_GET['seat_type'];
$flight_id = $_GET['flight_id'];

$seat_column = '';
switch ($seat_type) {
  case 'economy':
    $seat_column = 'economy_seats';
    break;
  case 'business':
    $seat_column = 'business_seats';
    break;
  case 'first_class':
    $seat_column = 'first_class_seats';
    break;
}

$query = "SELECT $seat_column - COUNT(pa.id) AS available_seats
          FROM flights f
          LEFT JOIN package_assign pa ON f.id = pa.flight_id AND pa.seat_type = '$seat_type'
          WHERE f.id = $flight_id
          GROUP BY f.id";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$available_seats = [];
if ($row) {
  for ($i = 1; $i <= $row['available_seats']; $i++) {
    $available_seats[] = $i;
  }
}

echo json_encode(['availableSeats' => $available_seats]);
?>