<?php
session_start();
include 'connection/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php?message=" . urlencode("Please login to view your booking"));
  exit();
}

$user_id = $_SESSION['user_id'];

// Check if we have booking ID and reference
if (!isset($_GET['booking_id']) || !isset($_GET['reference'])) {
  header("Location: my-bookings.php?message=" . urlencode("Invalid booking information"));
  exit();
}

$booking_id = $_GET['booking_id'];
$booking_reference = $_GET['reference'];

// Get booking details with complete fields from transportation_bookings
$booking_query = "SELECT 
                    tb.*,
                    u.full_name, u.email, u.phone_number,
                    CASE 
                      WHEN tb.route_name = '0' OR tb.route_name = '' THEN 
                        (SELECT route_name FROM taxi_routes WHERE id = tb.route_id)
                      ELSE tb.route_name
                    END AS display_route_name
                 FROM transportation_bookings tb
                 JOIN users u ON tb.user_id = u.id
                 WHERE tb.id = ? AND tb.booking_reference = ? AND tb.user_id = ?";

$stmt = $conn->prepare($booking_query);
$stmt->bind_param("isi", $booking_id, $booking_reference, $user_id);
$stmt->execute();
$booking_result = $stmt->get_result();

if ($booking_result->num_rows === 0) {
  header("Location: my-bookings.php?message=" . urlencode("Booking not found"));
  exit();
}

$booking = $booking_result->fetch_assoc();

// Debug: Log the pickup location value
error_log("Original pickup location value: " . $booking['pickup_location']);

// Only set a default value if the pickup location is literally "0" or completely empty
if ($booking['pickup_location'] === '0' || $booking['pickup_location'] === '') {
  // Check if we can retrieve the pickup location from POST data
  if (isset($_SESSION['last_pickup_location']) && !empty($_SESSION['last_pickup_location'])) {
    $booking['pickup_location'] = $_SESSION['last_pickup_location'];
  } else {
    $booking['pickup_location'] = 'Not specified';
  }
}

// Handle PDF generation
if (isset($_GET['generate_pdf']) && $_GET['generate_pdf'] === '1') {
  require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';

  // Create new PDF document
  $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

  // Set document information
  $pdf->SetCreator(PDF_CREATOR);
  $pdf->SetAuthor('Ummrah Transportation');
  $pdf->SetTitle('Transportation Booking Voucher');
  $pdf->SetSubject('Booking Confirmation Voucher');
  $pdf->SetKeywords('Booking, Voucher, Transportation, Ummrah');

  // Set default header data
  $pdf->SetHeaderData('', 0, 'Ummrah Transportation', 'Booking Voucher - Ref: ' . $booking['booking_reference']);

  // Set header and footer fonts
  $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
  $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);

  // Set default monospaced font
  $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

  // Set margins
  $pdf->SetMargins(15, 20, 15);
  $pdf->SetHeaderMargin(10);
  $pdf->SetFooterMargin(10);

  // Set auto page breaks
  $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

  // Set image scale factor
  $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

  // Add a page
  $pdf->AddPage();

  // Set font
  $pdf->SetFont('helvetica', '', 12);

  // Format duration for display if it exists
  $durationDisplay = '';
  if (!empty($booking['duration'])) {
    $durationDisplay = ucwords(str_replace('_', ' ', $booking['duration']));
  } else {
    $durationDisplay = 'One Way'; // Default value
  }

  // Use the display_route_name for the route display
  $routeDisplay = !empty($booking['display_route_name']) ? $booking['display_route_name'] : 'Not specified';

  // Voucher content
  $html = '
  <h1 style="text-align: center; color: #0d9488;">Transportation Booking Voucher</h1>
  <p style="text-align: center; font-size: 14px;">Thank you for choosing Ummrah Transportation!</p>
  
  <h2 style="background-color: #f0fdfa; padding: 10px; color: #0d9488;">Booking Details</h2>
  <table border="0" cellpadding="5">
    <tr>
      <td width="30%"><strong>Reference:</strong></td>
      <td width="70%">' . htmlspecialchars($booking['booking_reference']) . '</td>
    </tr>
    <tr>
      <td><strong>Service Type:</strong></td>
      <td>' . ucfirst($booking['service_type']) . ' Service</td>
    </tr>
    <tr>
      <td><strong>Route:</strong></td>
      <td>' . htmlspecialchars($routeDisplay) . '</td>
    </tr>
    <tr>
      <td><strong>Vehicle:</strong></td>
      <td>' . htmlspecialchars($booking['vehicle_name']) . '</td>
    </tr>
    <tr>
      <td><strong>Price:</strong></td>
      <td>' . number_format($booking['price'], 2) . ' SR</td>
    </tr>
    <tr>
      <td><strong>Date & Time:</strong></td>
      <td>' . date('F j, Y', strtotime($booking['booking_date'])) . ' at ' . date('h:i A', strtotime($booking['booking_time'])) . '</td>
    </tr>
    <tr>
      <td><strong>Passengers:</strong></td>
      <td>' . $booking['passengers'] . ' person(s)</td>
    </tr>
    <tr>
      <td><strong>Pickup Location:</strong></td>
      <td>' . htmlspecialchars($booking['pickup_location']) . '</td>
    </tr>';

  if (!empty($booking['special_requests'])) {
    $html .= '
    <tr>
      <td><strong>Special Requests:</strong></td>
      <td>' . htmlspecialchars($booking['special_requests']) . '</td>
    </tr>';
  }

  $html .= '
    <tr>
      <td><strong>Duration:</strong></td>
      <td>' . $durationDisplay . '</td>
    </tr>
    <tr>
      <td><strong>Status:</strong></td>
      <td>' . ucfirst($booking['booking_status']) . '</td>
    </tr>
    <tr>
      <td><strong>Payment Status:</strong></td>
      <td>' . ucfirst($booking['payment_status']) . '</td>
    </tr>
  </table>
  
  <h2 style="background-color: #f0fdfa; padding: 10px; color: #0d9488;">Customer Information</h2>
  <table border="0" cellpadding="5">
    <tr>
      <td width="30%"><strong>Name:</strong></td>
      <td width="70%">' . htmlspecialchars($booking['full_name']) . '</td>
    </tr>
    <tr>
      <td><strong>Email:</strong></td>
      <td>' . htmlspecialchars($booking['email']) . '</td>
    </tr>';

  if (!empty($booking['phone_number'])) {
    $html .= '
    <tr>
      <td><strong>Phone:</strong></td>
      <td>' . htmlspecialchars($booking['phone_number']) . '</td>
    </tr>';
  }

  $html .= '
  </table>
  
  <h2 style="background-color: #f0fdfa; padding: 10px; color: #0d9488;">Important Information</h2>
  <ul style="font-size: 10px;">
    <li>Please be ready at the pickup location at least 15 minutes before the scheduled time.</li>
    <li>The driver will contact you approximately 30 minutes before pickup.</li>
    <li>Luggage allowance depends on the vehicle type and number of passengers.</li>
    <li>For any changes to your booking, please contact us at least 24 hours in advance.</li>
    <li>Payment is required to fully confirm your booking.</li>
  </ul>
  
  <p style="text-align: center; font-size: 10px; margin-top: 20px;">
    Ummrah Transportation - Your Trusted Travel Partner<br>
    Contact: support@ummrah.com | Phone: +123-456-7890
  </p>';

  // Write HTML content
  $pdf->writeHTML($html, true, false, true, false, '');

  // Close and output PDF
  $pdf->Output('voucher_' . $booking['booking_reference'] . '.pdf', 'D');
  exit();
}

// Function to determine status badge colors
function getStatusBadgeClass($status, $type = 'booking')
{
  if ($type === 'booking') {
    switch ($status) {
      case 'pending':
        return 'bg-yellow-100 text-yellow-800';
      case 'confirmed':
        return 'bg-green-100 text-green-800';
      case 'completed':
        return 'bg-blue-100 text-blue-800';
      case 'cancelled':
        return 'bg-red-100 text-red-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  } else { // payment status
    switch ($status) {
      case 'unpaid':
        return 'bg-gray-100 text-gray-800';
      case 'paid':
        return 'bg-green-100 text-green-800';
      case 'refunded':
        return 'bg-purple-100 text-purple-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  }
}
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

    .badge {
      display: inline-block;
      padding: 0.25rem 0.5rem;
      font-size: 0.75rem;
      font-weight: 500;
      border-radius: 0.25rem;
    }

    .booking-item {
      display: flex;
      flex-direction: column;
      margin-bottom: 0.5rem;
    }

    .booking-label {
      font-size: 0.875rem;
      color: #6b7280;
    }

    .booking-value {
      font-weight: 500;
      color: #111827;
    }

    .booking-details .grid>div {
      padding: 10px;
      border-radius: 4px;
      background-color: #f9fafb;
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
                  <div class="booking-item">
                    <span class="booking-label">Service Type</span>
                    <span class="booking-value"><?php echo ucfirst($booking['service_type']); ?> Service</span>
                  </div>

                  <div class="booking-item">
                    <span class="booking-label">Route</span>
                    <span class="booking-value">
                      <?php echo htmlspecialchars($booking['display_route_name'] ?? $booking['route_name']); ?>
                    </span>
                  </div>

                  <div class="booking-item">
                    <span class="booking-label">Vehicle</span>
                    <span class="booking-value"><?php echo htmlspecialchars($booking['vehicle_name']); ?></span>
                  </div>

                  <div class="booking-item">
                    <span class="booking-label">Price</span>
                    <span class="booking-value text-teal-600 font-semibold"><?php echo number_format($booking['price'], 2); ?> SR</span>
                  </div>

                  <div class="booking-item">
                    <span class="booking-label">Date & Time</span>
                    <span class="booking-value">
                      <?php
                      echo date('F j, Y', strtotime($booking['booking_date'])) . ' at ' .
                        date('h:i A', strtotime($booking['booking_time']));
                      ?>
                    </span>
                  </div>

                  <div class="booking-item">
                    <span class="booking-label">Passengers</span>
                    <span class="booking-value"><?php echo $booking['passengers']; ?> person(s)</span>
                  </div>

                  <div class="booking-item">
                    <span class="booking-label">Pickup Location</span>
                    <span class="booking-value"><?php echo htmlspecialchars($booking['pickup_location']); ?></span>
                  </div>

                  <div class="booking-item">
                    <span class="booking-label">Duration</span>
                    <span class="booking-value">
                      <?php
                      echo !empty($booking['duration'])
                        ? ucwords(str_replace('_', ' ', $booking['duration']))
                        : 'One Way';
                      ?>
                    </span>
                  </div>

                  <?php if (!empty($booking['special_requests'])): ?>
                    <div class="booking-item md:col-span-2">
                      <span class="booking-label">Special Requests</span>
                      <span class="booking-value"><?php echo htmlspecialchars($booking['special_requests']); ?></span>
                    </div>
                  <?php endif; ?>

                  <div class="booking-item">
                    <span class="booking-label">Booking Status</span>
                    <span class="booking-value">
                      <span class="badge <?php echo getStatusBadgeClass($booking['booking_status']); ?>">
                        <?php echo ucfirst($booking['booking_status']); ?>
                      </span>
                    </span>
                  </div>

                  <div class="booking-item">
                    <span class="booking-label">Payment Status</span>
                    <span class="booking-value">
                      <span class="badge <?php echo getStatusBadgeClass($booking['payment_status'], 'payment'); ?>">
                        <?php echo ucfirst($booking['payment_status']); ?>
                      </span>
                    </span>
                  </div>

                  <div class="booking-item">
                    <span class="booking-label">Booking Date</span>
                    <span class="booking-value">
                      <?php echo date('F j, Y g:i A', strtotime($booking['created_at'])); ?>
                    </span>
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
                  <div class="booking-item">
                    <span class="booking-label">Name</span>
                    <span class="booking-value"><?php echo htmlspecialchars($booking['full_name'] ?? 'N/A'); ?></span>
                  </div>

                  <div class="booking-item">
                    <span class="booking-label">Email</span>
                    <span class="booking-value"><?php echo htmlspecialchars($booking['email'] ?? 'N/A'); ?></span>
                  </div>

                  <?php if (!empty($booking['phone_number'])): ?>
                    <div class="booking-item">
                      <span class="booking-label">Phone</span>
                      <span class="booking-value"><?php echo htmlspecialchars($booking['phone_number']); ?></span>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap justify-between gap-4 no-print">
              <div>
                <a href="?booking_id=<?php echo $booking_id; ?>&reference=<?php echo $booking_reference; ?>&generate_pdf=1"
                  class="inline-block px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                  <i class="bx bx-download mr-2"></i> Download PDF Voucher
                </a>
                <button onclick="printVoucher()"
                  class="inline-block px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition ml-2">
                  <i class="bx bx-printer mr-2"></i> Print
                </button>
              </div>

              <div>
                <?php if ($booking['payment_status'] === 'unpaid'): ?>
                  <a href="payment.php?booking_id=<?php echo $booking_id; ?>&reference=<?php echo $booking_reference; ?>&type=transportation"
                    class="px-6 py-2 bg-teal-600 text-white rounded-md hover:bg-teal-700 transition">
                    <i class="bx bx-credit-card mr-2"></i> Pay Now
                  </a>
                <?php elseif (in_array($booking['booking_status'], ['pending', 'confirmed'])): ?>
                  <button onclick="cancelBooking(<?php echo $booking_id; ?>, '<?php echo $booking_reference; ?>')"
                    class="px-6 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
                    <i class="bx bx-x-circle mr-2"></i> Cancel Booking
                  </button>
                <?php endif; ?>

                <a href="user/bookings-transport.php"
                  class="inline-block px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition ml-2">
                  <i class="bx bx-list-ul mr-2"></i> My Bookings
                </a>
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
        window.location.href = 'cancel-booking.php?booking_id=' + bookingId + '&reference=' + bookingReference + '&type=transportation';
      }
    }

    // Print function
    function printVoucher() {
      window.print();
    }
  </script>
</body>

</html>