<?php
$host = 'localhost';
$dbname = 'ummrah';
$username = 'root';
$password = '';

try {
  $pdo = new PDO(
    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
    $username,
    $password,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false
    ]
  );
} catch (PDOException $e) {
  die("Connection failed: " . $e->getMessage());
}

// Get booking ID from query string
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no booking ID provided, redirect to hotels listing
if ($booking_id === 0) {
  header('Location: view-hotels.php');
  exit;
}

// Fetch booking details
try {
  $stmt = $pdo->prepare("
    SELECT b.*, h.hotel_name, h.location, h.price_per_night 
    FROM hotel_bookings b
    JOIN hotels h ON b.hotel_id = h.id
    WHERE b.id = :id
  ");
  $stmt->execute(['id' => $booking_id]);
  $booking = $stmt->fetch();

  if (!$booking) {
    // Booking not found
    header('Location: view-hotels.php?error=booking_not_found');
    exit;
  }

  // Calculate nights and total price
  $check_in = new DateTime($booking['check_in_date']);
  $check_out = new DateTime($booking['check_out_date']);
  $nights = $check_in->diff($check_out)->days;
  $total_price = $nights * $booking['price_per_night'];
} catch (Exception $e) {
  die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  
  <!-- PDF library -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>
</head>

<body class="bg-gray-50">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Main Content -->
    <div class="main flex-1 flex flex-col overflow-auto">
      <!-- Navbar -->
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <button class="md:hidden text-gray-800" id="menu-btn">
          <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-xl font-semibold">
          <i class="text-teal-600 fas fa-check-circle mx-2"></i> Booking Confirmation
        </h1>
        <button onclick="printBooking()" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition duration-200">
          <i class="fas fa-print mr-2"></i>Print
        </button>
      </div>

      <!-- Booking Confirmation -->
      <div class="container mx-auto px-4 py-8">
        <div id="bookingConfirmation" class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
          <!-- Header -->
          <div class="bg-teal-600 text-white p-6">
            <div class="flex items-center">
              <i class="fas fa-check-circle text-3xl mr-4"></i>
              <div>
                <h2 class="text-2xl font-bold">Booking Confirmed</h2>
                <p class="text-teal-100">Your booking has been successfully confirmed</p>
              </div>
            </div>
          </div>

          <!-- Booking Details -->
          <div class="p-6">
            <div class="flex justify-between pb-4 border-b border-gray-200">
              <div>
                <h3 class="font-semibold text-lg text-gray-800">Booking Reference</h3>
                <p class="text-gray-600">#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></p>
              </div>
              <div class="text-right">
                <h3 class="font-semibold text-lg text-gray-800">Booking Date</h3>
                <p class="text-gray-600"><?php echo date('F j, Y', strtotime($booking['created_at'])); ?></p>
              </div>
            </div>

            <div class="mt-6">
              <h3 class="font-semibold text-lg text-gray-800">Hotel Information</h3>
              <div class="mt-2 grid grid-cols-2 gap-4">
                <div>
                  <p class="text-gray-600">Hotel Name</p>
                  <p class="font-medium"><?php echo htmlspecialchars($booking['hotel_name']); ?></p>
                </div>
                <div>
                  <p class="text-gray-600">Location</p>
                  <p class="font-medium capitalize"><?php echo htmlspecialchars($booking['location']); ?></p>
                </div>
                <div>
                  <p class="text-gray-600">Room</p>
                  <p class="font-medium">Room <?php echo htmlspecialchars(str_replace('r', '', $booking['room_id'])); ?></p>
                </div>
                <div>
                  <p class="text-gray-600">Price per Night</p>
                  <p class="font-medium">$<?php echo number_format($booking['price_per_night'], 2); ?></p>
                </div>
              </div>
            </div>

            <div class="mt-6">
              <h3 class="font-semibold text-lg text-gray-800">Guest Information</h3>
              <div class="mt-2 grid grid-cols-2 gap-4">
                <div>
                  <p class="text-gray-600">Guest Name</p>
                  <p class="font-medium"><?php echo htmlspecialchars($booking['guest_name']); ?></p>
                </div>
                <div>
                  <p class="text-gray-600">Email</p>
                  <p class="font-medium"><?php echo htmlspecialchars($booking['guest_email']); ?></p>
                </div>
                <div>
                  <p class="text-gray-600">Phone</p>
                  <p class="font-medium"><?php echo htmlspecialchars($booking['guest_phone']); ?></p>
                </div>
                <div>
                  <p class="text-gray-600">Status</p>
                  <p class="font-medium capitalize"><?php echo htmlspecialchars($booking['status']); ?></p>
                </div>
              </div>
            </div>

            <div class="mt-6">
              <h3 class="font-semibold text-lg text-gray-800">Stay Information</h3>
              <div class="mt-2 grid grid-cols-2 gap-4">
                <div>
                  <p class="text-gray-600">Check-in Date</p>
                  <p class="font-medium"><?php echo date('F j, Y', strtotime($booking['check_in_date'])); ?></p>
                </div>
                <div>
                  <p class="text-gray-600">Check-out Date</p>
                  <p class="font-medium"><?php echo date('F j, Y', strtotime($booking['check_out_date'])); ?></p>
                </div>
                <div>
                  <p class="text-gray-600">Number of Nights</p>
                  <p class="font-medium"><?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?></p>
                </div>
              </div>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-200">
              <div class="flex justify-between items-center">
                <h3 class="font-semibold text-lg text-gray-800">Total Amount</h3>
                <p class="text-2xl font-bold text-teal-600">$<?php echo number_format($total_price, 2); ?></p>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="bg-gray-50 p-6 border-t border-gray-200">
            <p class="text-center text-gray-600">Thank you for choosing our service!</p>
            <p class="text-center text-gray-500 text-sm mt-1">If you have any questions, please contact our support team.</p>
          </div>
        </div>

        <div class="flex justify-center mt-6 gap-3">
          <a href="view-hotel-bookings.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition duration-200">
            <i class="fas fa-arrow-left mr-2"></i>Back to Hotels
          </a>
          
          <!-- Single download button -->
          <button id="downloadBtn" class="bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700 transition duration-200">
            <i class="fas fa-download mr-2"></i>Download PDF
          </button>
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/js-links.php'; ?>

  <script>
    // Print function
    function printBooking() {
      window.print();
    }

    // PDF Download - only the working method
    document.getElementById('downloadBtn').addEventListener('click', function() {
      try {
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Generating PDF...';
        this.disabled = true;
        
        const element = document.getElementById('bookingConfirmation');
        const filename = 'booking-confirmation-<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?>.pdf';
        
        // Use html2pdf with minimal options
        html2pdf()
          .from(element)
          .save(filename)
          .then(() => {
            this.innerHTML = '<i class="fas fa-download mr-2"></i>Download PDF';
            this.disabled = false;
          })
          .catch(err => {
            console.error('PDF generation failed:', err);
            this.innerHTML = '<i class="fas fa-download mr-2"></i>Download PDF';
            this.disabled = false;
            alert('PDF generation failed. Please try again.');
          });
      } catch (error) {
        console.error('Error in PDF generation:', error);
        this.innerHTML = '<i class="fas fa-download mr-2"></i>Download PDF';
        this.disabled = false;
      }
    });
  </script>
</body>

</html>