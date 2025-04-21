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

// Get hotel_id from request
if (!isset($_GET['hotel_id']) || empty($_GET['hotel_id'])) {
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Hotel ID is required']);
  exit();
}

$hotel_id = $_GET['hotel_id'];

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

// Get already booked rooms for a hotel
function getBookedRooms($hotel_id)
{
  global $conn;
  $sql = "SELECT seat_number FROM package_assign WHERE hotel_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $hotel_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $booked_rooms = [];
  while ($row = $result->fetch_assoc()) {
    $booked_rooms[] = $row['seat_number'];
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
  $all_rooms = []; // Ensure it's an array even if empty or invalid JSON
}

// Get booked rooms
$booked_rooms = getBookedRooms($hotel_id);

// Filter out booked rooms to get available rooms
$available_rooms = array_diff($all_rooms, $booked_rooms);

// Sort rooms for better presentation
sort($available_rooms, SORT_NATURAL);

// Return results
header('Content-Type: application/json');
echo json_encode([
  'hotel' => [
    'id' => $hotel['id'],
    'name' => $hotel['hotel_name'],
    'location' => $hotel['location']
  ],
  'rooms' => array_values($available_rooms), // Convert to indexed array
  'total_rooms' => count($all_rooms),
  'booked_rooms' => count($booked_rooms),
  'available_rooms' => count($available_rooms)
]);
