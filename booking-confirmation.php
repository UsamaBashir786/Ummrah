<?php
session_start();
include 'connection/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// Check if we have booking ID and reference
if (!isset($_GET['booking_id']) || !isset($_GET['reference'])) {
  header("Location: my-bookings.php");
  exit();
}

$booking_id = $_GET['booking_id'];
$booking_reference = $_GET['reference'];

// Get booking details
$booking_query = "SELECT tb.*, u.full_name, u.email, u.phone_number 
                 FROM transportation_bookings tb
                 JOIN users u ON tb.user_id = u.id
                 WHERE tb.id = ? AND tb.booking_reference = ? AND tb.user_id = ?";
$stmt = $conn->prepare($booking_query);
$stmt->bind_param("isi", $booking_id, $booking_reference, $user_id);
$stmt->execute();
$booking_result = $stmt->get_result();

if ($booking_result->num_rows === 0) {
  // Booking not found or doesn't belong to this user
  header("Location: my-bookings.php");
  exit();
}

$booking = $booking_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <link href="https://unpkg.com/boxicons/css/boxicons.min.css" rel="stylesheet">
  <style>
    .confirmation-box {
      border: 2px dashed #0d9488;
      background-color: #f0fdfa;
    }

    @media print {
      .no-print {
        display: none;
      }

      body {
        font-size: 12pt;
        color: #000;
      }

      .booking-details {
        border: 1px solid #ccc;
      }
    }
  </style>
</head>

<body class="bg-gray-50">
  <?php include 'includes/navbar.php'; ?>
  <div class="mt-15"></div>
  <section class="py-10 bg-gray-50">
    <div class="container mx-auto px-4">
      <div class="max-w-3xl mx-auto">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
          <div class="px-6 py-4 bg-teal-600 text-white text-center no-print">
            <h1 class="text-2xl font-bold">Booking Confirmation</h1>
            <p class="text-sm opacity-80">Your transportation booking has been received</p>
          </div>

          <div class="p-6">
            <!-- Success Message -->
            <div class="confirmation-box rounded-lg p-4 mb-6 text-center">
              <i class="bx bx-check-circle text-teal-600 text-5xl"></i>
              <h2 class="text-lg font-semibold text-teal-800 mt-2">Thank You! Your Booking is Confirmed</h2>
              <p class="text-gray-600">We have received your transportation booking request.</p>
              <p class="text-gray-600">Your booking reference is: <span class="font-bold text-teal-600"><?php echo $booking['booking_reference']; ?></span></p>
            </div>

            <!-- Booking Details -->
            <div class="booking-details border border-gray-200 rounded-lg mb-6">
              <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                <h3 class="font-semibold text-gray-700">Booking Details</h3>
              </div>

              <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <p class="text-sm text-gray-500">Service Type</p>
                    <p class="font-medium"><?php echo ucfirst($booking['service_type']); ?> Service</p>
                  </div>

                  <div>
                    <p class="text-sm text-gray-500">Route</p>
                    <p class="font-medium"><?php echo htmlspecialchars($booking['route_name'] ?? 'N/A'); ?></p>
                  </div>

                  <div>
                    <p class="text-sm text-gray-500">Vehicle</p>
                    <p class="font-medium"><?php echo htmlspecialchars($booking['vehicle_name'] ?? 'N/A'); ?></p>
                  </div>

                  <div>
                    <p class="text-sm text-gray-500">Price</p>
                    <p class="font-medium text-teal-600"><?php echo $booking['price']; ?> SR</p>
                  </div>

                  <div>
                    <p class="text-sm text-gray-500">Date & Time</p>
                    <p class="font-medium">
                      <?php
                      $date = new DateTime($booking['booking_date']);
                      echo $date->format('F j, Y') . ' at ' . date('h:i A', strtotime($booking['booking_time']));
                      ?>
                    </p>
                  </div>

                  <div>
                    <p class="text-sm text-gray-500">Passengers</p>
                    <p class="font-medium"><?php echo $booking['passengers']; ?> person(s)</p>
                  </div>

                  <div>
                    <p class="text-sm text-gray-500">Pickup Location</p>
                    <p class="font-medium"><?php echo htmlspecialchars($booking['pickup_location']); ?></p>
                  </div>

                  <div>
                    <p class="text-sm text-gray-500">Drop-off Location</p>
                    <p class="font-medium"><?php echo htmlspecialchars($booking['dropoff_location']); ?></p>
                  </div>

                  <?php if (!empty($booking['special_requests'])): ?>
                    <div class="md:col-span-2">
                      <p class="text-sm text-gray-500">Special Requests</p>
                      <p class="font-medium"><?php echo htmlspecialchars($booking['special_requests']); ?></p>
                    </div>
                  <?php endif; ?>

                  <div>
                    <p class="text-sm text-gray-500">Booking Status</p>
                    <p class="font-medium">
                      <span class="px-2 py-1 rounded text-xs 
                      <?php
                      switch ($booking['booking_status']) {
                        case 'pending':
                          echo 'bg-yellow-100 text-yellow-800';
                          break;
                        case 'confirmed':
                          echo 'bg-green-100 text-green-800';
                          break;
                        case 'completed':
                          echo 'bg-blue-100 text-blue-800';
                          break;
                        case 'cancelled':
                          echo 'bg-red-100 text-red-800';
                          break;
                        default:
                          echo 'bg-gray-100 text-gray-800';
                      }
                      ?>">
                        <?php echo ucfirst($booking['booking_status']); ?>
                      </span>
                    </p>
                  </div>

                  <div>
                    <p class="text-sm text-gray-500">Payment Status</p>
                    <p class="font-medium">
                      <span class="px-2 py-1 rounded text-xs 
                      <?php
                      switch ($booking['payment_status']) {
                        case 'unpaid':
                          echo 'bg-gray-100 text-gray-800';
                          break;
                        case 'paid':
                          echo 'bg-green-100 text-green-800';
                          break;
                        case 'refunded':
                          echo 'bg-purple-100 text-purple-800';
                          break;
                        default:
                          echo 'bg-gray-100 text-gray-800';
                      }
                      ?>">
                        <?php echo ucfirst($booking['payment_status']); ?>
                      </span>
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Customer Information -->
            <div class="booking-details border border-gray-200 rounded-lg mb-6">
              <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                <h3 class="font-semibold text-gray-700">Customer Information</h3>
              </div>

              <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <p class="text-sm text-gray-500">Name</p>
                    <p class="font-medium"><?php echo htmlspecialchars($booking['full_name']); ?></p>
                  </div>

                  <div>
                    <p class="text-sm text-gray-500">Email</p>
                    <p class="font-medium"><?php echo htmlspecialchars($booking['email']); ?></p>
                  </div>

                  <?php if (!empty($booking['phone_number'])): ?>
                    <div>
                      <p class="text-sm text-gray-500">Phone</p>
                      <p class="font-medium"><?php echo htmlspecialchars($booking['phone_number']); ?></p>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap justify-between gap-4 no-print">
              <div>
                <button onclick="window.print()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">
                  <i class="bx bx-printer mr-2"></i> Print Confirmation
                </button>
                <a href="my-bookings.php" class="inline-block px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition ml-2">
                  <i class="bx bx-list-ul mr-2"></i> My Bookings
                </a>
              </div>

              <div>
                <?php if ($booking['payment_status'] === 'unpaid'): ?>
                  <a href="payment.php?booking_id=<?php echo $booking_id; ?>&reference=<?php echo $booking_reference; ?>"
                    class="px-6 py-2 bg-teal-600 text-white rounded-md hover:bg-teal-700 transition">
                    <i class="bx bx-credit-card mr-2"></i> Pay Now
                  </a>
                <?php elseif ($booking['booking_status'] === 'pending' || $booking['booking_status'] === 'confirmed'): ?>
                  <button onclick="cancelBooking(<?php echo $booking_id; ?>, '<?php echo $booking_reference; ?>')"
                    class="px-6 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
                    <i class="bx bx-x-circle mr-2"></i> Cancel Booking
                  </button>
                <?php endif; ?>
              </div>
            </div>

            <!-- Information Notes -->
            <div class="mt-6 border-t border-gray-200 pt-4 no-print">
              <h4 class="font-medium text-gray-800 mb-2">Important Information:</h4>
              <ul class="list-disc pl-5 text-gray-600 text-sm space-y-1">
                <li>Please be ready at the pickup location at least 15 minutes before the scheduled time.</li>
                <li>The driver will contact you approximately 30 minutes before pickup.</li>
                <li>Luggage allowance depends on the vehicle type and number of passengers.</li>
                <li>For any changes to your booking, please contact us at least 24 hours in advance.</li>
                <li>Payment is required to fully confirm your booking.</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php include 'includes/footer.php'; ?>

  <script>
    function cancelBooking(bookingId, bookingReference) {
      if (confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
        window.location.href = 'cancel-booking.php?booking_id=' + bookingId + '&reference=' + bookingReference;
      }
    }
  </script>
</body>

</html>