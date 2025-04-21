<?php
// Include database connection
include 'includes/db-config.php';

// Handle booking cancellation
if (isset($_GET['cancel_id'])) {
  try {
    $booking_id = $_GET['cancel_id'];
    $sql = "UPDATE package_booking SET status = 'canceled' WHERE id = :booking_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':booking_id' => $booking_id]);

    echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Booking cancelled successfully.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'booked-packages.php';
                    });
                });
              </script>";
  } catch (PDOException $e) {
    echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred: " . addslashes($e->getMessage()) . "',
                        confirmButtonText: 'OK'
                    });
                });
              </script>";
  }
}

// Handle status or payment status update
if (isset($_GET['update_id']) && isset($_GET['field']) && isset($_GET['value'])) {
  try {
    $booking_id = $_GET['update_id'];
    $field = $_GET['field'];
    $value = $_GET['value'];

    // Validate field and value
    $valid_fields = ['status', 'payment_status'];
    $valid_status = ['pending', 'confirmed', 'canceled'];
    $valid_payment_status = ['pending', 'paid', 'failed'];

    if (
      !in_array($field, $valid_fields) ||
      ($field === 'status' && !in_array($value, $valid_status)) ||
      ($field === 'payment_status' && !in_array($value, $valid_payment_status))
    ) {
      throw new Exception('Invalid field or value.');
    }

    $sql = "UPDATE package_booking SET $field = :value WHERE id = :booking_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':value' => $value, ':booking_id' => $booking_id]);

    echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: '" . ucfirst($field) . " updated successfully.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'booked-packages.php';
                    });
                });
              </script>";
  } catch (Exception $e) {
    echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to update: " . addslashes($e->getMessage()) . "',
                        confirmButtonText: 'OK'
                    });
                });
              </script>";
  }
}

// Fetch statistics
try {
  $sql_stats = "SELECT 
                    COUNT(*) AS total_bookings,
                    COUNT(DISTINCT user_id) AS unique_users,
                    COALESCE(SUM(CASE WHEN status = 'confirmed' AND payment_status = 'paid' THEN total_price ELSE 0 END), 0) AS total_profit,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) AS pending_bookings,
                    COUNT(CASE WHEN status = 'confirmed' THEN 1 END) AS confirmed_bookings,
                    COUNT(CASE WHEN status = 'canceled' THEN 1 END) AS canceled_bookings,
                    COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) AS pending_payments,
                    COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) AS paid_payments,
                    COUNT(CASE WHEN payment_status = 'failed' THEN 1 END) AS failed_payments
                  FROM package_booking";
  $stmt_stats = $pdo->query($sql_stats);
  $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $stats = [
    'total_bookings' => 0,
    'unique_users' => 0,
    'total_profit' => 0,
    'pending_bookings' => 0,
    'confirmed_bookings' => 0,
    'canceled_bookings' => 0,
    'pending_payments' => 0,
    'paid_payments' => 0,
    'failed_payments' => 0
  ];
  echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to fetch statistics: " . addslashes($e->getMessage()) . "',
                    confirmButtonText: 'OK'
                });
            });
          </script>";
}

// Handle filters
$filters = [
  'status' => isset($_GET['status']) && in_array($_GET['status'], ['pending', 'confirmed', 'canceled']) ? $_GET['status'] : '',
  'payment_status' => isset($_GET['payment_status']) && in_array($_GET['payment_status'], ['pending', 'paid', 'failed']) ? $_GET['payment_status'] : '',
  'start_date' => isset($_GET['start_date']) ? $_GET['start_date'] : '',
  'end_date' => isset($_GET['end_date']) ? $_GET['end_date'] : ''
];

// Fetch all bookings with filters
try {
  $sql = "SELECT b.id AS booking_id, b.package_id, b.booking_date, b.status, b.payment_status, b.total_price, 
                   u.full_name AS customer_name, u.email AS customer_email, p.title 
            FROM package_booking b 
            JOIN users u ON b.user_id = u.id 
            JOIN packages p ON b.package_id = p.id 
            WHERE 1=1";

  $params = [];

  if ($filters['status']) {
    $sql .= " AND b.status = :status";
    $params[':status'] = $filters['status'];
  }
  if ($filters['payment_status']) {
    $sql .= " AND b.payment_status = :payment_status";
    $params[':payment_status'] = $filters['payment_status'];
  }
  if ($filters['start_date']) {
    $sql .= " AND b.booking_date >= :start_date";
    $params[':start_date'] = $filters['start_date'];
  }
  if ($filters['end_date']) {
    $sql .= " AND b.booking_date <= :end_date";
    $params[':end_date'] = $filters['end_date'] . ' 23:59:59';
  }

  $sql .= " ORDER BY b.booking_date DESC";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to fetch bookings: " . addslashes($e->getMessage()) . "',
                    confirmButtonText: 'OK'
                });
            });
          </script>";
  $bookings = [];
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
          <i class="text-teal-600 fas fa-book mx-2"></i> Booked Packages
        </h1>

        <!-- Back Button (Right) -->
        <a href="view-package.php" class="flex items-center text-gray-700 hover:text-gray-900">
          <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
      </div>

      <!-- Content -->
      <div class="overflow-auto container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">
          <h2 class="text-2xl font-bold text-teal-700 mb-6">Umrah Booking Statistics</h2>

          <!-- Statistics Section -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <!-- Total Bookings -->
            <div class="bg-teal-50 p-6 rounded-lg shadow-md">
              <h3 class="text-lg font-semibold text-teal-700">Total Packages Booked</h3>
              <p class="text-3xl font-bold text-teal-600"><?php echo htmlspecialchars($stats['total_bookings']); ?></p>
            </div>
            <!-- Unique Users -->
            <div class="bg-teal-50 p-6 rounded-lg shadow-md">
              <h3 class="text-lg font-semibold text-teal-700">Unique Users</h3>
              <p class="text-3xl font-bold text-teal-600"><?php echo htmlspecialchars($stats['unique_users']); ?></p>
            </div>
            <!-- Total Profit -->
            <div class="bg-teal-50 p-6 rounded-lg shadow-md">
              <h3 class="text-lg font-semibold text-teal-700">Total Profit (PKR)</h3>
              <p class="text-3xl font-bold text-teal-600"><?php echo htmlspecialchars(number_format($stats['total_profit'], 2)); ?> PKR</p>
            </div>
          </div>

          <!-- Status Breakdown -->
          <div class="bg-teal-50 p-6 rounded-lg shadow-md mb-8">
            <h3 class="text-lg font-semibold text-teal-700 mb-4">Booking Status Breakdown</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <p class="text-sm text-gray-600">Pending</p>
                <p class="text-xl font-bold text-yellow-600"><?php echo htmlspecialchars($stats['pending_bookings']); ?></p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Confirmed</p>
                <p class="text-xl font-bold text-green-600"><?php echo htmlspecialchars($stats['confirmed_bookings']); ?></p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Canceled</p>
                <p class="text-xl font-bold text-red-600"><?php echo htmlspecialchars($stats['canceled_bookings']); ?></p>
              </div>
            </div>
          </div>

          <!-- Payment Status Breakdown -->
          <div class="bg-teal-50 p-6 rounded-lg shadow-md mb-8">
            <h3 class="text-lg font-semibold text-teal-700 mb-4">Payment Status Breakdown</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <p class="text-sm text-gray-600">Pending</p>
                <p class="text-xl font-bold text-yellow-600"><?php echo htmlspecialchars($stats['pending_payments']); ?></p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Paid</p>
                <p class="text-xl font-bold text-green-600"><?php echo htmlspecialchars($stats['paid_payments']); ?></p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Failed</p>
                <p class="text-xl font-bold text-red-600"><?php echo htmlspecialchars($stats['failed_payments']); ?></p>
              </div>
            </div>
          </div>

          <!-- Filter Form -->
          <div class="bg-teal-50 p-6 rounded-lg shadow-md mb-8">
            <h3 class="text-lg font-semibold text-teal-700 mb-4">Filter Bookings</h3>
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
              <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
                  <option value="">All</option>
                  <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                  <option value="confirmed" <?php echo $filters['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                  <option value="canceled" <?php echo $filters['status'] === 'canceled' ? 'selected' : ''; ?>>Canceled</option>
                </select>
              </div>
              <div>
                <label for="payment_status" class="block text-sm font-medium text-gray-700">Payment Status</label>
                <select name="payment_status" id="payment_status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
                  <option value="">All</option>
                  <option value="pending" <?php echo $filters['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                  <option value="paid" <?php echo $filters['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                  <option value="failed" <?php echo $filters['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                </select>
              </div>
              <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
              <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
              <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                <input
                  type="text"
                  name="start_date"
                  id="start_date"
                  value="<?php echo htmlspecialchars($filters['start_date']); ?>"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
              </div>
              <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                <input
                  type="text"
                  name="end_date"
                  id="end_date"
                  value="<?php echo htmlspecialchars($filters['end_date']); ?>"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
              </div>

              <script>
                flatpickr("#start_date", {
                  dateFormat: "Y-m-d",
                  allowInput: false // Prevent manual typing
                });

                flatpickr("#end_date", {
                  dateFormat: "Y-m-d",
                  allowInput: false // Prevent manual typing
                });
              </script>
              <div class="sm:col-span-2 md:col-span-4 flex justify-end">
                <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-md hover:bg-teal-700">Apply Filters</button>
              </div>
            </form>
          </div>

          <h2 class="text-2xl font-bold text-teal-700 mb-6">Booked Umrah Packages</h2>
          <?php if (empty($bookings)): ?>
            <p class="text-gray-600">No bookings found.</p>
          <?php else: ?>
            <div class="overflow-x-auto">
              <table class="min-w-full bg-white border">
                <thead>
                  <tr class="bg-teal-600 text-white">
                    <th class="py-2 px-4 border">Booking ID</th>
                    <th class="py-2 px-4 border">Package Title</th>
                    <th class="py-2 px-4 border">Customer Name</th>
                    <th class="py-2 px-4 border">Customer Email</th>
                    <th class="py-2 px-4 border">Booking Date</th>
                    <th class="py-2 px-4 border">Status</th>
                    <th class="py-2 px-4 border">Payment Status</th>
                    <th class="py-2 px-4 border">Total Price</th>
                    <th class="py-2 px-4 border">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($bookings as $booking): ?>
                    <tr class="hover:bg-gray-100">
                      <td class="py-2 px-4 border"><?php echo htmlspecialchars($booking['booking_id']); ?></td>
                      <td class="py-2 px-4 border"><?php echo htmlspecialchars($booking['title']); ?></td>
                      <td class="py-2 px-4 border"><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                      <td class="py-2 px-4 border"><?php echo htmlspecialchars($booking['customer_email']); ?></td>
                      <td class="py-2 px-4 border"><?php echo htmlspecialchars($booking['booking_date']); ?></td>
                      <td class="py-2 px-4 border">
                        <select class="status-select border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500" data-id="<?php echo $booking['booking_id']; ?>">
                          <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                          <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                          <option value="canceled" <?php echo $booking['status'] === 'canceled' ? 'selected' : ''; ?>>Canceled</option>
                        </select>
                      </td>
                      <td class="py-2 px-4 border">
                        <select class="payment-status-select border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500" data-id="<?php echo $booking['booking_id']; ?>">
                          <option value="pending" <?php echo $booking['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                          <option value="paid" <?php echo $booking['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                          <option value="failed" <?php echo $booking['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        </select>
                      </td>
                      <td class="py-2 px-4 border"><?php echo htmlspecialchars(number_format($booking['total_price'], 2)); ?> PKR</td>
                      <td class="py-2 px-4 border">
                        <a href="booking-details.php?id=<?php echo $booking['booking_id']; ?>" class="text-teal-600 hover:text-teal-800 mr-2" title="View Details">
                          <i class="fas fa-eye"></i>
                        </a>
                        <?php if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed'): ?>
                          <a href="#" class="text-red-600 hover:text-red-800 cancel-booking" data-id="<?php echo $booking['booking_id']; ?>" title="Cancel Booking">
                            <i class="fas fa-trash"></i>
                          </a>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php include 'includes/js-links.php'; ?>
      <script>
        // Handle cancel booking with SweetAlert confirmation
        document.querySelectorAll('.cancel-booking').forEach(button => {
          button.addEventListener('click', function(e) {
            e.preventDefault();
            const bookingId = this.getAttribute('data-id');

            Swal.fire({
              title: 'Are you sure?',
              text: 'Do you want to cancel this booking?',
              icon: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#d33',
              cancelButtonColor: '#3085d6',
              confirmButtonText: 'Yes, cancel it!'
            }).then((result) => {
              if (result.isConfirmed) {
                window.location.href = `booked-packages.php?cancel_id=${bookingId}`;
              }
            });
          });
        });

        // Handle status change
        document.querySelectorAll('.status-select').forEach(select => {
          select.addEventListener('change', function() {
            const bookingId = this.getAttribute('data-id');
            const newStatus = this.value;

            Swal.fire({
              title: 'Update Status?',
              text: `Are you sure you want to change the status to ${newStatus}?`,
              icon: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: 'Yes, update it!'
            }).then((result) => {
              if (result.isConfirmed) {
                window.location.href = `booked-packages.php?update_id=${bookingId}&field=status&value=${newStatus}`;
              } else {
                // Revert the select to its original value
                this.value = this.getAttribute('data-original') || this.options[0].value;
              }
            });
          });
        });

        // Handle payment status change
        document.querySelectorAll('.payment-status-select').forEach(select => {
          select.addEventListener('change', function() {
            const bookingId = this.getAttribute('data-id');
            const newStatus = this.value;

            Swal.fire({
              title: 'Update Payment Status?',
              text: `Are you sure you want to change the payment status to ${newStatus}?`,
              icon: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: 'Yes, update it!'
            }).then((result) => {
              if (result.isConfirmed) {
                window.location.href = `booked-packages.php?update_id=${bookingId}&field=payment_status&value=${newStatus}`;
              } else {
                // Revert the select to its original value
                this.value = this.getAttribute('data-original') || this.options[0].value;
              }
            });
          });
        });
      </script>
    </div>
  </div>
</body>

</html>