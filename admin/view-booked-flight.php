<?php
session_name("admin_session");
session_start();
include '../connection/connection.php';

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($booking_id <= 0) {
  // Redirect to bookings list if no valid ID provided
  header("Location: my-bookings.php");
  exit();
}

// Fetch the booking details with related flight and user information
$query = "SELECT fb.*, f.flight_number, f.airline_name, f.departure_city, f.arrival_city, 
          f.departure_date, f.departure_time, f.arrival_time, f.duration, 
          f.economy_price, f.business_price, f.first_class_price
          FROM flight_bookings fb 
          LEFT JOIN flights f ON fb.flight_id = f.id 
          WHERE fb.id = ? AND fb.user_id = ?";

try {
  $stmt = $conn->prepare($query);
  $stmt->bind_param("ii", $booking_id, $user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows == 0) {
    // Booking not found or doesn't belong to this user
    header("Location: my-bookings.php");
    exit();
  }

  $booking = $result->fetch_assoc();
  $stmt->close();

  // Parse JSON seats data
  $seats_array = json_decode($booking['seats'], true);
  $seats_display = is_array($seats_array) ? implode(', ', $seats_array) : 'No seat assigned';

  // Format cabin class for display
  $cabin_class_display = ucfirst(str_replace('_', ' ', $booking['cabin_class']));

  // Calculate total price based on cabin class
  $price_per_seat = 0;
  switch ($booking['cabin_class']) {
    case 'economy':
      $price_per_seat = $booking['economy_price'];
      break;
    case 'business':
      $price_per_seat = $booking['business_price'];
      break;
    case 'first_class':
      $price_per_seat = $booking['first_class_price'];
      break;
  }

  $total_passengers = $booking['adult_count'] + $booking['children_count'];
  $total_price = $price_per_seat * $total_passengers;
} catch (Exception $e) {
  $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Booking Details - SkyJourney Airlines</title>
  <?php include 'includes/css-links.php'; ?>
  <style>
    .boarding-pass {
      background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);
      border-radius: 16px;
      position: relative;
      overflow: hidden;
    }

    .boarding-pass::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('../assets/images/boarding-pass-bg.png') no-repeat;
      background-size: cover;
      opacity: 0.1;
      z-index: 0;
    }

    .boarding-pass-content {
      position: relative;
      z-index: 1;
    }

    .booking-details .item {
      margin-bottom: 1rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid #e5e7eb;
    }

    .booking-details .item:last-child {
      border-bottom: none;
    }
  </style>
</head>

<body class="bg-gray-100">
  <div class="flex h-screen">
    <?php include 'includes/sidebar.php'; ?>


    <main class="main flex-1 flex flex-col overflow-hidden">
      <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
          <p><?php echo $error_message; ?></p>
        </div>
      <?php endif; ?>

      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
          <i class="fas fa-ticket-alt text-teal-600 mr-2"></i> Booking Details
        </h1>
        <a href="my-bookings.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
          <i class="fas fa-arrow-left mr-2"></i> Back to My Bookings
        </a>
      </div>

      <?php if (isset($booking)): ?>
        <!-- Boarding Pass Card -->
        <div class="mb-8 boarding-pass shadow-lg">
          <div class="boarding-pass-content p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div class="col-span-1 md:col-span-2">
                <div class="flex justify-between items-start">
                  <div>
                    <h2 class="text-white text-2xl font-bold"><?php echo htmlspecialchars($booking['airline_name']); ?></h2>
                    <p class="text-white opacity-90">Flight #<?php echo htmlspecialchars($booking['flight_number']); ?></p>
                  </div>
                  <div class="bg-white bg-opacity-20 rounded px-3 py-1">
                    <p class="text-white font-medium"><?php echo $cabin_class_display; ?></p>
                  </div>
                </div>

                <div class="mt-8 flex justify-between items-center">
                  <div class="text-center">
                    <h3 class="text-white text-3xl font-bold"><?php echo htmlspecialchars($booking['departure_city']); ?></h3>
                    <p class="text-white opacity-80"><?php echo date('H:i', strtotime($booking['departure_time'])); ?></p>
                    <p class="text-white opacity-70 text-sm"><?php echo date('d M Y', strtotime($booking['departure_date'])); ?></p>
                  </div>

                  <div class="text-center px-4">
                    <div class="flex items-center justify-center">
                      <div class="h-0.5 w-10 bg-white opacity-50"></div>
                      <i class="fas fa-plane text-white mx-4"></i>
                      <div class="h-0.5 w-10 bg-white opacity-50"></div>
                    </div>
                    <p class="text-white opacity-80 mt-1"><?php echo $booking['duration']; ?></p>
                  </div>

                  <div class="text-center">
                    <h3 class="text-white text-3xl font-bold"><?php echo htmlspecialchars($booking['arrival_city']); ?></h3>
                    <p class="text-white opacity-80"><?php echo date('H:i', strtotime($booking['arrival_time'])); ?></p>
                    <p class="text-white opacity-70 text-sm"><?php echo date('d M Y', strtotime($booking['departure_date'])); ?></p>
                  </div>
                </div>
              </div>

              <div class="border-t md:border-t-0 md:border-l border-white border-opacity-20 pl-0 md:pl-6 pt-6 md:pt-0">
                <h3 class="text-white text-xl font-semibold mb-2">Passenger</h3>
                <p class="text-white"><?php echo htmlspecialchars($booking['passenger_name']); ?></p>

                <div class="mt-4">
                  <p class="text-white opacity-80">
                    <span class="font-medium">Booking ID:</span> #<?php echo $booking['id']; ?>
                  </p>
                  <p class="text-white opacity-80">
                    <span class="font-medium">Booked on:</span> <?php echo date('d M Y', strtotime($booking['booking_date'])); ?>
                  </p>
                  <p class="text-white opacity-80">
                    <span class="font-medium">Seat(s):</span> <?php echo $seats_display; ?>
                  </p>
                </div>

                <div class="mt-6">
                  <p class="text-white opacity-80">
                    <span class="font-medium">Adults:</span> <?php echo $booking['adult_count']; ?>
                  </p>
                  <p class="text-white opacity-80">
                    <span class="font-medium">Children:</span> <?php echo $booking['children_count']; ?>
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
          <!-- Booking Details Card -->
          <div class="col-span-2 bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6 bg-gray-50 border-b">
              <h2 class="text-xl font-bold text-gray-800">
                <i class="fas fa-info-circle text-teal-600 mr-2"></i> Booking Information
              </h2>
            </div>

            <div class="p-6 booking-details">
              <div class="item">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Flight Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <p class="text-gray-600 mb-1"><span class="font-medium">Airline:</span> <?php echo htmlspecialchars($booking['airline_name']); ?></p>
                    <p class="text-gray-600 mb-1"><span class="font-medium">Flight Number:</span> <?php echo htmlspecialchars($booking['flight_number']); ?></p>
                    <p class="text-gray-600 mb-1"><span class="font-medium">Class:</span> <?php echo $cabin_class_display; ?></p>
                  </div>
                  <div>
                    <p class="text-gray-600 mb-1"><span class="font-medium">From:</span> <?php echo htmlspecialchars($booking['departure_city']); ?></p>
                    <p class="text-gray-600 mb-1"><span class="font-medium">To:</span> <?php echo htmlspecialchars($booking['arrival_city']); ?></p>
                    <p class="text-gray-600 mb-1"><span class="font-medium">Duration:</span> <?php echo $booking['duration']; ?></p>
                  </div>
                </div>
              </div>

              <div class="item">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Passenger Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <p class="text-gray-600 mb-1"><span class="font-medium">Name:</span> <?php echo htmlspecialchars($booking['passenger_name']); ?></p>
                    <p class="text-gray-600 mb-1"><span class="font-medium">Email:</span> <?php echo htmlspecialchars($booking['passenger_email']); ?></p>
                    <p class="text-gray-600 mb-1"><span class="font-medium">Phone:</span> <?php echo htmlspecialchars($booking['passenger_phone']); ?></p>
                  </div>
                  <div>
                    <p class="text-gray-600 mb-1"><span class="font-medium">Adults:</span> <?php echo $booking['adult_count']; ?></p>
                    <p class="text-gray-600 mb-1"><span class="font-medium">Children:</span> <?php echo $booking['children_count']; ?></p>
                    <p class="text-gray-600 mb-1"><span class="font-medium">Total Passengers:</span> <?php echo $total_passengers; ?></p>
                  </div>
                </div>
              </div>

              <div class="item">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Seat Information</h3>
                <p class="text-gray-600 mb-1"><span class="font-medium">Assigned Seats:</span> <?php echo $seats_display; ?></p>
                <p class="text-gray-600 mb-1"><span class="font-medium">Special Requests:</span>
                  <?php echo !empty($booking['special_requests']) ? htmlspecialchars($booking['special_requests']) : 'None'; ?>
                </p>
              </div>

              <div class="item">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Payment Information</h3>
                <p class="text-gray-600 mb-1"><span class="font-medium">Price per seat:</span> $<?php echo number_format($price_per_seat, 2); ?></p>
                <p class="text-gray-600 mb-1"><span class="font-medium">Total passengers:</span> <?php echo $total_passengers; ?></p>
                <p class="text-teal-600 text-xl font-bold mt-2"><span class="font-medium">Total paid:</span> $<?php echo number_format($total_price, 2); ?></p>
              </div>
            </div>
          </div>

          <!-- Actions Card -->
          <div class="col-span-1 bg-white rounded-lg shadow-md overflow-hidden h-fit">
            <div class="p-6 bg-gray-50 border-b">
              <h2 class="text-xl font-bold text-gray-800">
                <i class="fas fa-cogs text-teal-600 mr-2"></i> Actions
              </h2>
            </div>

            <div class="p-6">
              <a href="edit-booked-flight.php?id=<?php echo $booking['id']; ?>" class="w-full block bg-teal-600 text-white text-center px-4 py-3 rounded-lg hover:bg-teal-700 mb-4">
                <i class="fas fa-edit mr-2"></i> Edit Booking
              </a>

              <a href="#" onclick="printBookingDetails()" class="w-full block bg-blue-600 text-white text-center px-4 py-3 rounded-lg hover:bg-blue-700 mb-4">
                <i class="fas fa-print mr-2"></i> Print Details
              </a>

              <a href="download-ticket.php?id=<?php echo $booking['id']; ?>" class="w-full block bg-purple-600 text-white text-center px-4 py-3 rounded-lg hover:bg-purple-700 mb-4">
                <i class="fas fa-file-pdf mr-2"></i> Download E-Ticket
              </a>

              <button onclick="cancelBooking(<?php echo $booking['id']; ?>)" class="w-full block bg-red-600 text-white text-center px-4 py-3 rounded-lg hover:bg-red-700">
                <i class="fas fa-times-circle mr-2"></i> Cancel Booking
              </button>
            </div>

            <!-- Important Information -->
            <div class="p-6 bg-yellow-50 border-t">
              <h3 class="text-lg font-semibold text-gray-800 mb-2">
                <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i> Important
              </h3>
              <p class="text-gray-700 text-sm mb-2">
                Please arrive at the airport at least 2 hours before your flight departure time.
              </p>
              <p class="text-gray-700 text-sm">
                For cancellations within 24 hours of departure, a fee may apply.
              </p>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </main>
  </div>
  <?php include '../includes/footer.php'; ?>

  <script>
    function printBookingDetails() {
      window.print();
    }

    function cancelBooking(bookingId) {
      if (confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
        window.location.href = 'cancel-booking.php?id=' + bookingId;
      }
    }
  </script>
</body>

</html>