<?php
session_name("admin_session");
session_start();
include 'connection/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Not authorized']);
  exit();
}

// Get parameters
if (!isset($_GET['hotel_id']) || empty($_GET['hotel_id'])) {
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Hotel ID is required']);
  exit();
}

$hotel_id = $_GET['hotel_id'];
$check_in_date = isset($_GET['check_in_date']) ? $_GET['check_in_date'] : null;
$check_out_date = isset($_GET['check_out_date']) ? $_GET['check_out_date'] : null;

// Validate dates if provided
if ($check_in_date && $check_out_date) {
  if (strtotime($check_in_date) >= strtotime($check_out_date)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid date range']);
    exit();
  }
}

// Get hotel by ID
function getHotelById($hotel_id)
{
  global $conn;
  $sql = "SELECT * FROM hotels WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $hotel_id);
  $stmt->execute();
  $result = $stmt->get_result();
  return $result->fetch_assoc();
}

// Get already booked rooms for a hotel within a date range
function getBookedRooms($hotel_id, $check_in_date, $check_out_date)
{
  global $conn;
  $sql = "SELECT room_id 
          FROM hotel_bookings 
          WHERE hotel_id = ? 
          AND status IN ('pending', 'confirmed')";

  if ($check_in_date && $check_out_date) {
    $sql .= " AND (
                (check_in_date <= ? AND check_out_date >= ?) 
                OR (check_in_date <= ? AND check_out_date >= ?)
                OR (? <= check_in_date AND ? >= check_out_date)
              )";
  }

  $stmt = $conn->prepare($sql);
  if ($check_in_date && $check_out_date) {
    $stmt->bind_param(
      "issssss",
      $hotel_id,
      $check_out_date,
      $check_in_date,
      $check_out_date,
      $check_in_date,
      $check_in_date,
      $check_out_date
    );
  } else {
    $stmt->bind_param("i", $hotel_id);
  }
  $stmt->execute();
  $result = $stmt->get_result();
  $booked_rooms = [];
  while ($row = $result->fetch_assoc()) {
    $booked_rooms[] = $row['room_id'];
  }
  return $booked_rooms;
}

// Get hotel data
$hotel = getHotelById($hotel_id);
if (!$hotel) {
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Hotel not found']);
  exit();
}

// Get all rooms from the hotel
$all_rooms = json_decode($hotel['room_ids'], true);
if (!$all_rooms || !is_array($all_rooms)) {
  $all_rooms = [];
}

// Get booked rooms
$booked_rooms = getBookedRooms($hotel_id, $check_in_date, $check_out_date);

// Filter out booked rooms to get available rooms
$available_rooms = array_diff($all_rooms, $booked_rooms);
sort($available_rooms, SORT_NATURAL);

// Return results
header('Content-Type: application/json');
echo json_encode([
  'hotel' => [
    'id' => $hotel['id'],
    'name' => $hotel['hotel_name'],
    'location' => $hotel['location']
  ],
  'rooms' => array_values($available_rooms),
  'total_rooms' => count($all_rooms),
  'booked_rooms' => count($booked_rooms),
  'available_rooms' => count($available_rooms)
]);
