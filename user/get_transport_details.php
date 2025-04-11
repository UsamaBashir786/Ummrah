<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  http_response_code(403); // Forbidden
  echo '<div class="bg-red-100 p-4 rounded-lg text-red-700"><p>Please log in to view booking details.</p></div>';
  exit();
}

require_once '../connection/connection.php';

// Get booking ID from GET request
if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
  http_response_code(400); // Bad Request
  echo '<div class="bg-red-100 p-4 rounded-lg text-red-700"><p>Invalid booking ID.</p></div>';
  exit();
}

$booking_id = (int)$_GET['booking_id'];
$user_id = $_SESSION['user_id'];

// Fetch transportation booking details (removed driver-related join)
$sql = "
    SELECT 
        tb.*
    FROM transportation_bookings tb
    WHERE tb.id = ? AND tb.user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  http_response_code(404); // Not Found
  echo '<div class="bg-yellow-100 p-4 rounded-lg text-yellow-700"><p>No booking found with this ID.</p></div>';
  exit();
}

$booking = $result->fetch_assoc();

// Determine booking status class using switch
$booking_status_class = '';
switch ($booking['booking_status']) {
  case 'pending':
    $booking_status_class = 'bg-yellow-100 text-yellow-800';
    break;
  case 'confirmed':
    $booking_status_class = 'bg-blue-100 text-blue-800';
    break;
  case 'completed':
    $booking_status_class = 'bg-green-100 text-green-800';
    break;
  case 'cancelled':
    $booking_status_class = 'bg-red-100 text-red-800';
    break;
  default:
    $booking_status_class = 'bg-gray-100 text-gray-800';
}

// Determine payment status class using switch
$payment_status_class = '';
switch ($booking['payment_status']) {
  case 'paid':
    $payment_status_class = 'bg-green-100 text-green-800';
    break;
  case 'unpaid':
    $payment_status_class = 'bg-gray-100 text-gray-800';
    break;
  case 'refunded':
    $payment_status_class = 'bg-purple-100 text-purple-800';
    break;
  default:
    $payment_status_class = 'bg-gray-100 text-gray-800';
}

// Format the booking details as HTML
$output = '
  <div class="space-y-6">
    <div>
      <h3 class="text-lg font-semibold text-gray-900">Booking Information</h3>
      <div class="mt-2 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <span class="text-sm text-gray-500">Booking Reference:</span>
          <p class="text-gray-900 font-medium">' . htmlspecialchars($booking['booking_reference']) . '</p>
        </div>
        <div>
          <span class="text-sm text-gray-500">Service Type:</span>
          <p class="text-gray-900 font-medium">' . ucfirst(htmlspecialchars($booking['service_type'])) . '</p>
        </div>
        <div>
          <span class="text-sm text-gray-500">Route:</span>
          <p class="text-gray-900 font-medium">' .
  (empty($booking['route_name'])
    ? htmlspecialchars($booking['pickup_location'] . ' to ' . $booking['dropoff_location'])
    : htmlspecialchars($booking['route_name'])) .
  '</p>
        </div>
        <div>
          <span class="text-sm text-gray-500">Vehicle:</span>
          <p class="text-gray-900 font-medium">' . htmlspecialchars($booking['vehicle_name'] . ' (' . ucfirst($booking['vehicle_type']) . ')') . '</p>
        </div>
        <div>
          <span class="text-sm text-gray-500">Date & Time:</span>
          <p class="text-gray-900 font-medium">' . htmlspecialchars($booking['booking_date'] . ' ' . $booking['booking_time']) . '</p>
        </div>
        <div>
          <span class="text-sm text-gray-500">Passengers:</span>
          <p class="text-gray-900 font-medium">' . htmlspecialchars($booking['passengers']) . ' person(s)</p>
        </div>
        <div>
          <span class="text-sm text-gray-500">Price:</span>
          <p class="text-green-600 font-medium">$' . number_format($booking['price'], 2) . '</p>
        </div>
        <div>
          <span class="text-sm text-gray-500">Booking Status:</span>
          <p class="font-medium">
            <span class="px-2 py-1 rounded-full text-xs ' . $booking_status_class . '">
              ' . ucfirst($booking['booking_status']) . '
            </span>
          </p>
        </div>
        <div>
          <span class="text-sm text-gray-500">Payment Status:</span>
          <p class="font-medium">
            <span class="px-2 py-1 rounded-full text-xs ' . $payment_status_class . '">
              ' . ucfirst($booking['payment_status']) . '
            </span>
          </p>
        </div>';

if (!empty($booking['special_requests'])) {
  $output .= '
        <div class="sm:col-span-2">
          <span class="text-sm text-gray-500">Special Requests:</span>
          <p class="text-gray-900 font-medium">' . htmlspecialchars($booking['special_requests']) . '</p>
        </div>';
}

$output .= '
      </div>
    </div>
    <div class="mt-4 flex justify-end space-x-2">
      <a href="../booking-confirmation.php?booking_id=' . $booking['id'] . '&reference=' . urlencode($booking['booking_reference']) . '"
         class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
        View Confirmation
      </a>';

if ($booking['booking_status'] === 'pending' || $booking['booking_status'] === 'confirmed') {
  $output .= '
      <button onclick="cancelBooking(' . $booking['id'] . ', \'' . htmlspecialchars($booking['booking_reference']) . '\')"
              class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
        Cancel Booking
      </button>';
}

$output .= '
    </div>
  </div>';

echo $output;

$stmt->close();
$conn->close();
?>

<script>
  function cancelBooking(bookingId, bookingReference) {
    if (confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
      window.location.href = '../cancel-booking.php?booking_id=' + bookingId + '&reference=' + bookingReference;
    }
  }
</script>