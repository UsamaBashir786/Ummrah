<?php
require_once 'connection/connection.php'; // Ensure database connection is available

/**
 * Get package bookings that need hotel assignment
 * @return array List of package bookings
 */
function getPackageBookings()
{
  global $conn;
  $sql = "SELECT pb.id, pb.user_id, pb.package_id, pb.booking_date, pb.status, pb.total_price, 
                 p.title as package_title, u.full_name as user_name, u.email as user_email, 
                 u.phone_number as user_phone
          FROM package_booking pb
          LEFT JOIN packages p ON pb.package_id = p.id
          LEFT JOIN users u ON pb.user_id = u.id
          WHERE pb.status = 'pending' OR pb.status = 'confirmed'
          ORDER BY pb.booking_date DESC";

  $result = $conn->query($sql);
  return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get hotels from database
 * @param string|null $location Optional location filter (e.g., 'makkah', 'madinah')
 * @return array List of hotels
 */
function getHotels($location = null)
{
  global $conn;
  $sql = "SELECT * FROM hotels";
  if ($location) {
    $sql .= " WHERE location = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $location);
    $stmt->execute();
    $result = $stmt->get_result();
  } else {
    $result = $conn->query($sql);
  }
  return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get hotel bookings for a specific package booking
 * @param int $package_booking_id Package booking ID
 * @return array List of hotel bookings
 */
function getHotelBookings($package_booking_id)
{
  global $conn;
  $sql = "SELECT hb.*, h.hotel_name, h.location 
          FROM hotel_bookings hb
          LEFT JOIN hotels h ON hb.hotel_id = h.id
          WHERE hb.package_booking_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $package_booking_id);
  $stmt->execute();
  $result = $stmt->get_result();
  return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get booked rooms for a hotel
 * @param int $hotel_id Hotel ID
 * @return array List of booked room IDs
 */
function getBookedRooms($hotel_id)
{
  global $conn;
  $sql = "SELECT room_id FROM hotel_bookings WHERE hotel_id = ? AND status IN ('pending', 'confirmed')";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $hotel_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $booked_rooms = [];
  while ($row = $result->fetch_assoc()) {
    $booked_rooms[] = $row['room_id'];
  }
  return $booked_rooms;
}

/**
 * Get available rooms for a hotel
 * @param int $hotel_id Hotel ID
 * @return array List of available room IDs
 */
function getAvailableRooms($hotel_id)
{
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

  $booked_rooms = getBookedRooms($hotel_id);
  return array_diff($all_rooms, $booked_rooms);
}

/**
 * Get hotel details by ID
 * @param int $hotel_id Hotel ID
 * @return array|null Hotel details or null if not found
 */
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
?>