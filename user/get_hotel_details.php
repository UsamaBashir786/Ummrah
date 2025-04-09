<?php
require_once '../connection/connection.php';

if (!isset($_GET['booking_id'])) {
  echo '<div class="bg-red-100 p-4 rounded-lg text-red-700">Invalid booking ID.</div>';
  exit();
}

$booking_id = (int)$_GET['booking_id'];

// Fetch hotel booking details
$sql = "
    SELECT 
        hb.id AS booking_id,
        h.hotel_name,
        h.location,
        h.price AS price_per_night,
        h.rating,
        h.image AS hotel_image,
        hb.check_in_date,
        hb.check_out_date,
        hb.total_price,
        hb.status AS booking_status,
        hb.rooms,
        hb.adults,
        hb.children,
        DATEDIFF(hb.check_out_date, hb.check_in_date) AS duration
    FROM hotel_bookings hb
    INNER JOIN hotels h ON hb.hotel_id = h.id
    WHERE hb.id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$hotel = $result->fetch_assoc();

if (!$hotel) {
  echo '<div class="bg-red-100 p-4 rounded-lg text-red-700">Hotel booking not found.</div>';
  exit();
}
?>

<div class="hotel-details">
  <h3 class="text-lg font-semibold text-blue-600">Hotel Information</h3>
  <p><strong>Hotel Name:</strong> <?php echo htmlspecialchars($hotel['hotel_name']); ?></p>
  <p><strong>Location:</strong> <?php echo htmlspecialchars($hotel['location']); ?></p>
  <?php if (!empty($hotel['hotel_image']) && file_exists("../" . $hotel['hotel_image'])) { ?>
    <img src="../<?php echo htmlspecialchars($hotel['hotel_image']); ?>" alt="<?php echo htmlspecialchars($hotel['hotel_name']); ?>" class="w-full h-48 object-cover rounded-lg my-2">
  <?php } ?>
  <p><strong>Rating:</strong>
    <?php
    $rating = (int)$hotel['rating'];
    for ($i = 0; $i < $rating; $i++) {
      echo '<span class="text-yellow-400">★</span>';
    }
    for ($i = $rating; $i < 5; $i++) {
      echo '<span class="text-gray-300">★</span>';
    }
    ?>
  </p>

  <h3 class="text-lg font-semibold text-blue-600 mt-4">Booking Details</h3>
  <p><strong>Check-In Date:</strong> <?php echo htmlspecialchars($hotel['check_in_date']); ?></p>
  <p><strong>Check-Out Date:</strong> <?php echo htmlspecialchars($hotel['check_out_date']); ?></p>
  <p><strong>Duration:</strong> <?php echo htmlspecialchars($hotel['duration']); ?> days</p>
  <p><strong>Rooms:</strong> <?php echo htmlspecialchars($hotel['rooms']); ?></p>
  <p><strong>Adults:</strong> <?php echo htmlspecialchars($hotel['adults']); ?></p>
  <p><strong>Children:</strong> <?php echo htmlspecialchars($hotel['children']); ?></p>
  <p><strong>Total Price:</strong> <?php echo number_format($hotel['total_price'], 2); ?></p>
  <p><strong>Status:</strong> <span class="status-badge <?php echo $hotel['booking_status']; ?>"><?php echo ucfirst($hotel['booking_status']); ?></span></p>
</div>