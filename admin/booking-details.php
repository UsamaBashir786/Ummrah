<?php
// Include database connection
include 'includes/db-config.php';

// Check if booking ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Invalid booking ID.',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = 'booked-packages.php';
                });
            });
          </script>";
  exit;
}

$booking_id = (int)$_GET['id'];

try {
  // Fetch booking details
  $sql = "SELECT b.id AS booking_id, b.package_id, b.booking_date, b.status, b.payment_status, b.total_price,
                   u.full_name AS customer_name, u.email AS customer_email, u.phone_number,
                   p.title, p.package_type, p.description, p.airline, p.flight_class, p.departure_city,
                   p.departure_time, p.departure_date, p.arrival_city, p.return_time, p.return_date, p.inclusions, p.price, p.package_image
            FROM package_booking b
            JOIN users u ON b.user_id = u.id
            JOIN packages p ON b.package_id = p.id
            WHERE b.id = :booking_id";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':booking_id' => $booking_id]);
  $booking = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$booking) {
    echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Booking not found.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'booked-packages.php';
                    });
                });
              </script>";
    exit;
  }

  // Fetch assigned resources (flight, hotel, transport)
  $sql_assign = "SELECT seat_type, seat_number, transport_seat_number, hotel_id, transport_id, flight_id
                   FROM package_assign
                   WHERE booking_id = :booking_id";
  $stmt_assign = $pdo->prepare($sql_assign);
  $stmt_assign->execute([':booking_id' => $booking_id]);
  $assignments = $stmt_assign->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to fetch booking details: " . addslashes($e->getMessage()) . "',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = 'booked-packages.php';
                });
            });
          </script>";
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <!-- Include SweetAlert CSS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-100">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main flex-1 flex flex-col">
      <!-- Navbar -->
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <!-- Menu Button (Left) -->
        <button class="md:hidden text-gray-800" id="menu-btn">
          <i class="fas fa-bars"></i>
        </button>

        <!-- Title -->
        <h1 class="text-xl font-semibold">
          <i class="text-teal-600 fas fa-book-open mx-2"></i> Booking Details
        </h1>

        <!-- Back Button (Right) -->
        <a href="booked-packages.php" class="flex items-center text-gray-700 hover:text-gray-900">
          <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
      </div>

      <!-- Content -->
      <div class="overflow-auto container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">
          <h2 class="text-2xl font-bold text-teal-700 mb-6">Booking Details (ID: <?php echo htmlspecialchars($booking['booking_id']); ?>)</h2>

          <!-- Booking Information -->
          <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Booking Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <p><strong>Booking ID:</strong> <?php echo htmlspecialchars($booking['booking_id']); ?></p>
                <p><strong>Booking Date:</strong> <?php echo htmlspecialchars($booking['booking_date']); ?></p>
                <p><strong>Status:</strong>
                  <span class="px-2 py-1 rounded <?php
                                                  echo $booking['status'] === 'confirmed' ? 'bg-green-100 text-green-700' : ($booking['status'] === 'pending' ? 'bg-yellow-100 text-yellow-700' :
                                                      'bg-red-100 text-red-700'); ?>">
                    <?php echo htmlspecialchars(ucfirst($booking['status'])); ?>
                  </span>
                </p>
                <p><strong>Payment Status:</strong>
                  <span class="px-2 py-1 rounded <?php
                                                  echo $booking['payment_status'] === 'paid' ? 'bg-green-100 text-green-700' : ($booking['payment_status'] === 'pending' ? 'bg-yellow-100 text-yellow-700' :
                                                      'bg-red-100 text-red-700'); ?>">
                    <?php echo htmlspecialchars(ucfirst($booking['payment_status'])); ?>
                  </span>
                </p>
                <p><strong>Total Price:</strong> <?php echo htmlspecialchars(number_format($booking['total_price'], 2)); ?> PKR</p>
              </div>
            </div>
          </div>

          <!-- Customer Information -->
          <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Customer Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($booking['customer_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($booking['customer_email']); ?></p>
                <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($booking['phone_number']); ?></p>
              </div>
            </div>
          </div>

          <!-- Package Information -->
          <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Package Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <p><strong>Title:</strong> <?php echo htmlspecialchars($booking['title']); ?></p>
                <p><strong>Package Type:</strong> <?php echo htmlspecialchars(ucfirst($booking['package_type'])); ?></p>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($booking['description']); ?></p>
                <p><strong>Inclusions:</strong> <?php echo htmlspecialchars($booking['inclusions']); ?></p>
                <p><strong>Price:</strong> <?php echo htmlspecialchars(number_format($booking['price'], 2)); ?> PKR</p>
              </div>
              <div>
                <p><strong>Airline:</strong> <?php echo htmlspecialchars($booking['airline']); ?></p>
                <p><strong>Flight Class:</strong> <?php echo htmlspecialchars(ucfirst($booking['flight_class'])); ?></p>
                <p><strong>Departure City:</strong> <?php echo htmlspecialchars($booking['departure_city']); ?></p>
                <p><strong>Departure Date:</strong> <?php echo htmlspecialchars($booking['departure_date']); ?></p>
                <p><strong>Departure Time:</strong> <?php echo htmlspecialchars($booking['departure_time']); ?></p>
                <p><strong>Arrival City:</strong> <?php echo htmlspecialchars($booking['arrival_city']); ?></p>
                <p><strong>Return Date:</strong> <?php echo htmlspecialchars($booking['return_date']); ?></p>
                <p><strong>Return Time:</strong> <?php echo htmlspecialchars($booking['return_time']); ?></p>
              </div>
            </div>
            <?php if ($booking['package_image']): ?>
              <div class="mt-4">
                <p><strong>Package Image:</strong></p>
                <img src="<?php echo htmlspecialchars($booking['package_image']); ?>" alt="Package Image" class="max-w-xs rounded-lg shadow-md">
              </div>
            <?php endif; ?>
          </div>

          <!-- Assigned Resources -->
          <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Assigned Resources</h3>
            <?php if (empty($assignments)): ?>
              <p class="text-gray-600">No resources assigned to this booking.</p>
            <?php else: ?>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($assignments as $assignment): ?>
                  <div>
                    <?php if ($assignment['flight_id']): ?>
                      <p><strong>Flight ID:</strong> <?php echo htmlspecialchars($assignment['flight_id']); ?></p>
                      <p><strong>Seat Type:</strong> <?php echo htmlspecialchars(ucfirst($assignment['seat_type'])); ?></p>
                      <p><strong>Seat Number:</strong> <?php echo htmlspecialchars($assignment['seat_number']); ?></p>
                    <?php endif; ?>
                    <?php if ($assignment['hotel_id']): ?>
                      <p><strong>Hotel ID:</strong> <?php echo htmlspecialchars($assignment['hotel_id']); ?></p>
                    <?php endif; ?>
                    <?php if ($assignment['transport_id']): ?>
                      <p><strong>Transport ID:</strong> <?php echo htmlspecialchars($assignment['transport_id']); ?></p>
                      <p><strong>Transport Seat Number:</strong> <?php echo htmlspecialchars($assignment['transport_seat_number']); ?></p>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

        </div>
      </div>

      <?php include 'includes/js-links.php'; ?>
    </div>
  </div>
</body>

</html>