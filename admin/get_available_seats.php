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



<?php
require_once 'connection/connection.php';

function getAvailableRooms($hotel_id) {
  global $conn;
  $sql = "SELECT room_ids FROM hotels WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $hotel_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $hotel = $result->fetch_assoc();

  if (!$hotel || empty($hotel['room_ids'])) {
    return [];
  }

  $all_rooms = json_decode($hotel['room_ids'], true);
  if (!is_array($all_rooms)) {
    return [];
  }

  $sql = "SELECT room_id FROM hotel_bookings WHERE hotel_id = ? AND status IN ('pending', 'confirmed')";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $hotel_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $booked_rooms = [];
  while ($row = $result->fetch_assoc()) {
    $booked_rooms[] = $row['room_id'];
  }

  return array_diff($all_rooms, $booked_rooms);
}

$hotel_id = isset($_GET['hotel_id']) ? intval($_GET['hotel_id']) : 0;
$rooms = getAvailableRooms($hotel_id);

header('Content-Type: application/json');
echo json_encode(['rooms' => $rooms]);
exit;
?>







