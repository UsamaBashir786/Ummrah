<?php
session_name("admin_session");
session_start();
include 'connection/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
  header("Location: login.php");
  exit();
}

// Initialize filter variables
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_service = isset($_GET['service']) ? $_GET['service'] : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$filter_search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Base SQL query for the main table
$sql = "SELECT tb.*, u.full_name, u.email, u.phone_number 
        FROM transportation_bookings tb
        LEFT JOIN users u ON tb.user_id = u.id
        WHERE 1=1";

// Apply filters
if (!empty($filter_status)) {
  $sql .= " AND tb.booking_status = '" . mysqli_real_escape_string($conn, $filter_status) . "'";
}

if (!empty($filter_service)) {
  $sql .= " AND tb.service_type = '" . mysqli_real_escape_string($conn, $filter_service) . "'";
}

if (!empty($filter_date_from)) {
  $sql .= " AND tb.booking_date >= '" . mysqli_real_escape_string($conn, $filter_date_from) . "'";
}

if (!empty($filter_date_to)) {
  $sql .= " AND tb.booking_date <= '" . mysqli_real_escape_string($conn, $filter_date_to) . "'";
}

if (!empty($filter_search)) {
  $search_term = mysqli_real_escape_string($conn, $filter_search);
  $sql .= " AND (tb.booking_reference LIKE '%$search_term%'
           OR tb.route_name LIKE '%$search_term%'
           OR u.full_name LIKE '%$search_term%'
           OR u.email LIKE '%$search_term%'
           OR u.phone_number LIKE '%$search_term%')";
}

// Combine queries for total items, total revenue, and completed bookings
$count_sql = "SELECT 
                COUNT(*) as total, 
                SUM(CASE WHEN tb.booking_status != 'cancelled' THEN tb.price ELSE 0 END) as total_revenue, 
                SUM(CASE WHEN tb.booking_status = 'completed' THEN 1 ELSE 0 END) as completed 
              FROM transportation_bookings tb
              LEFT JOIN users u ON tb.user_id = u.id
              WHERE 1=1";

// Apply the same filters as the main query
if (!empty($filter_status)) {
  $count_sql .= " AND tb.booking_status = '" . mysqli_real_escape_string($conn, $filter_status) . "'";
}

if (!empty($filter_service)) {
  $count_sql .= " AND tb.service_type = '" . mysqli_real_escape_string($conn, $filter_service) . "'";
}

if (!empty($filter_date_from)) {
  $count_sql .= " AND tb.booking_date >= '" . mysqli_real_escape_string($conn, $filter_date_from) . "'";
}

if (!empty($filter_date_to)) {
  $count_sql .= " AND tb.booking_date <= '" . mysqli_real_escape_string($conn, $filter_date_to) . "'";
}

if (!empty($filter_search)) {
  $search_term = mysqli_real_escape_string($conn, $filter_search);
  $count_sql .= " AND (tb.booking_reference LIKE '%$search_term%'
                   OR tb.route_name LIKE '%$search_term%'
                   OR u.full_name LIKE '%$search_term%'
                   OR u.email LIKE '%$search_term%'
                   OR u.phone_number LIKE '%$search_term%')";
}

$count_result = $conn->query($count_sql);
$count_row = $count_result->fetch_assoc();
$total_items = $count_row['total'];
$total_revenue = $count_row['total_revenue'] ?? 0; // Default to 0 if no bookings
$completed_bookings = $count_row['completed'] ?? 0; // Default to 0 if no completed bookings
$total_pages = ceil($total_items / $items_per_page);

// Finalize the main query with pagination and ordering
$sql .= " ORDER BY tb.booking_date DESC, tb.booking_time DESC LIMIT $offset, $items_per_page";
$result = $conn->query($sql);

// Handle booking status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
  $booking_id = $_POST['booking_id'];
  $new_status = $_POST['new_status'];

  $update_sql = "UPDATE transportation_bookings SET 
                 booking_status = '" . mysqli_real_escape_string($conn, $new_status) . "',
                 updated_at = NOW()
                 WHERE id = " . intval($booking_id);

  if ($conn->query($update_sql)) {
    $success_message = "Booking status updated successfully!";

    // If status is set to completed, also update payment status to paid
    if ($new_status == 'completed') {
      $update_payment_sql = "UPDATE transportation_bookings SET 
                           payment_status = 'paid'
                           WHERE id = " . intval($booking_id);
      $conn->query($update_payment_sql);
    }

    // Redirect to avoid form resubmission
    header("Location: booked-transportation.php?status=$filter_status&service=$filter_service&date_from=$filter_date_from&date_to=$filter_date_to&search=$filter_search&page=$page&success=1");
    exit();
  } else {
    $error_message = "Error updating status: " . $conn->error;
  }
}

// Handle booking deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_booking'])) {
  $booking_id = $_POST['booking_id'];

  // Check for any related transportation assignments
  $check_sql = "SELECT id FROM transportation_assign WHERE booking_id = " . intval($booking_id);
  $check_result = $conn->query($check_sql);

  if ($check_result->num_rows > 0) {
    // Delete related assignments first
    $delete_assignments_sql = "DELETE FROM transportation_assign WHERE booking_id = " . intval($booking_id);
    $conn->query($delete_assignments_sql);
  }

  // Now delete the booking
  $delete_sql = "DELETE FROM transportation_bookings WHERE id = " . intval($booking_id);

  if ($conn->query($delete_sql)) {
    $success_message = "Booking deleted successfully!";

    // Redirect to avoid form resubmission
    header("Location: booked-transportation.php?status=$filter_status&service=$filter_service&date_from=$filter_date_from&date_to=$filter_date_to&search=$filter_search&page=$page&success=2");
    exit();
  } else {
    $error_message = "Error deleting booking: " . $conn->error;
  }
}

// Display success message from redirect
if (isset($_GET['success'])) {
  if ($_GET['success'] == 1) {
    $success_message = "Booking status updated successfully!";
  } elseif ($_GET['success'] == 2) {
    $success_message = "Booking deleted successfully!";
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <!-- Include SweetAlert -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Include flatpickr for date picking -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>

<body class="bg-gray-50">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Navbar -->
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <button class="md:hidden text-gray-800" id="menu-btn">
          <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-xl font-semibold">
          <i class="text-teal-600 fas fa-car-side mx-2"></i> Booked Transportation
        </h1>
      </div>

      <!-- Content Area -->
      <div class="flex-1 overflow-y-auto p-6">
        <div class="container mx-auto">
          <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" id="success-alert">
              <p><?php echo $success_message; ?></p>
            </div>
            <script>
              setTimeout(() => {
                document.getElementById('success-alert').style.display = 'none';
              }, 5000);
            </script>
          <?php endif; ?>

          <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" id="error-alert">
              <p><?php echo $error_message; ?></p>
            </div>
            <script>
              setTimeout(() => {
                document.getElementById('error-alert').style.display = 'none';
              }, 5000);
            </script>
          <?php endif; ?>

          <!-- Filter Section -->
          <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-700">
              <i class="fas fa-filter text-teal-600 mr-2"></i> Filter Transportation Bookings
            </h2>
            <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <!-- Status Filter -->
              <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Booking Status</label>
                <select id="status" name="status" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
                  <option value="">All Statuses</option>
                  <option value="pending" <?php echo ($filter_status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                  <option value="confirmed" <?php echo ($filter_status == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                  <option value="completed" <?php echo ($filter_status == 'completed') ? 'selected' : ''; ?>>Completed</option>
                  <option value="cancelled" <?php echo ($filter_status == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                </select>
              </div>

              <!-- Service Type Filter -->
              <div>
                <label for="service" class="block text-sm font-medium text-gray-700 mb-1">Service Type</label>
                <select id="service" name="service" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
                  <option value="">All Services</option>
                  <option value="taxi" <?php echo ($filter_service == 'taxi') ? 'selected' : ''; ?>>Taxi</option>
                  <option value="rentacar" <?php echo ($filter_service == 'rentacar') ? 'selected' : ''; ?>>Rent A Car</option>
                </select>
              </div>

              <!-- Date Range Filter -->
              <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo $filter_date_from; ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
              </div>

              <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo $filter_date_to; ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
              </div>

              <!-- Search Box -->
              <div class="md:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search (Ref#, Route, Customer)</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($filter_search); ?>" placeholder="Search by reference, route or customer details" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
              </div>

              <!-- Filter Buttons -->
              <div class="md:col-span-2 flex items-end space-x-4">
                <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-md flex items-center">
                  <i class="fas fa-search mr-2"></i> Apply Filters
                </button>
                <a href="booked-transportation.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md flex items-center">
                  <i class="fas fa-undo mr-2"></i> Reset
                </a>
              </div>
            </form>
          </div>

          <!-- Results Section -->
          <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
              <h2 class="text-lg font-semibold text-gray-700">
                <i class="fas fa-list text-teal-600 mr-2"></i> Transportation Bookings
              </h2>
              <p class="text-sm text-gray-600">
                Showing <?php echo ($result->num_rows > 0) ? ($offset + 1) : 0; ?> -
                <?php echo min($offset + $items_per_page, $total_items); ?> of <?php echo $total_items; ?> bookings
              </p>
            </div>

            <!-- Stats Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
              <!-- Total Bookings -->
              <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-4 text-white">
                <div class="text-lg opacity-80 mb-1">Total Bookings</div>
                <div class="text-3xl font-bold"><?php echo $total_items; ?></div>
              </div>

              <!-- Total Revenue (Lump Sum, excluding cancelled bookings) -->
              <div class="bg-gradient-to-r from-green-400 to-green-500 rounded-lg p-4 text-white">
                <div class="text-lg opacity-80 mb-1">Total Revenue</div>
                <div class="text-3xl font-bold">$<?php echo number_format($total_revenue, 2); ?></div>
              </div>

              <!-- Completed Bookings -->
              <div class="bg-gradient-to-r from-yellow-400 to-yellow-500 rounded-lg p-4 text-white">
                <div class="text-lg opacity-80 mb-1">Completed Bookings</div>
                <div class="text-3xl font-bold"><?php echo $completed_bookings; ?></div>
              </div>
            </div>

            <!-- Bookings Table -->
            <div class="overflow-x-auto">
              <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ref #</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                    <!-- <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route</th> -->
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="py-3 px-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($booking = $result->fetch_assoc()): ?>
                      <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4 whitespace-nowrap">
                          <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($booking['booking_reference']); ?></div>
                        </td>
                        <td class="py-3 px-4">
                          <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($booking['full_name']); ?></div>
                          <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['phone_number']); ?></div>
                          <div class="text-xs text-gray-500"><?php echo htmlspecialchars($booking['email']); ?></div>
                        </td>
                        <td class="py-3 px-4">
                          <div class="flex items-center">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                              <?php echo ($booking['service_type'] == 'taxi') ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'; ?>">
                              <?php echo ucfirst($booking['service_type'] ?? ''); ?>
                            </span>
                          </div>
                          <div class="text-xs text-gray-500 mt-1"><?php echo ucfirst($booking['duration'] ?? 'one_way'); ?></div>
                        </td>
                        <!-- <td class="py-3 px-4">
                          <div class="text-sm text-gray-900"><?php echo htmlspecialchars($booking['route_name']); ?></div>
                          <div class="text-xs text-gray-500 mt-1">
                            <span class="block truncate max-w-xs" title="<?php echo isset($booking['pickup_location']) ? htmlspecialchars($booking['pickup_location']) : ''; ?>">
                              From: <?php echo isset($booking['pickup_location']) ? htmlspecialchars($booking['pickup_location']) : 'N/A'; ?>
                            </span>
                            <span class="block truncate max-w-xs" title="<?php echo isset($booking['dropoff_location']) ? htmlspecialchars($booking['dropoff_location']) : ''; ?>">
                              To: <?php echo isset($booking['dropoff_location']) ? htmlspecialchars($booking['dropoff_location']) : 'N/A'; ?>
                            </span>
                          </div>
                        </td> -->
                        <td class="py-3 px-4">
                          <div class="text-sm text-gray-900"><?php echo htmlspecialchars($booking['vehicle_name']); ?></div>
                          <div class="text-xs text-gray-500">Passengers: <?php echo intval($booking['passengers']); ?></div>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                          <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></div>
                          <div class="text-sm text-gray-500"><?php echo date('h:i A', strtotime($booking['booking_time'])); ?></div>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                          <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php
                            switch ($booking['booking_status']) {
                              case 'pending':
                                echo 'bg-yellow-100 text-yellow-800';
                                break;
                              case 'confirmed':
                                echo 'bg-blue-100 text-blue-800';
                                break;
                              case 'completed':
                                echo 'bg-green-100 text-green-800';
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
                          <div class="text-xs mt-1">
                            Payment:
                            <select class="payment-status-select border-gray-300 rounded-md text-xs focus:ring-teal-500 focus:border-teal-500"
                              data-booking-id="<?php echo $booking['id']; ?>">
                              <option value="paid" <?php echo ($booking['payment_status'] == 'paid') ? 'selected' : ''; ?> class="text-green-600">Paid</option>
                              <option value="unpaid" <?php echo ($booking['payment_status'] == 'unpaid') ? 'selected' : ''; ?> class="text-orange-600">Unpaid</option>
                            </select>
                          </div>
                          <div class="text-xs text-gray-500 mt-1">
                            Price: $<?php echo number_format($booking['price'], 2); ?>
                          </div>
                        </td>
                        <td class="py-3 px-4 text-center">
                          <div class="flex flex-col sm:flex-row items-center justify-center space-y-2 sm:space-y-0 sm:space-x-2">
                            <!-- View Details Button -->
                            <button type="button" class="text-blue-600 hover:text-blue-900 view-details"
                              data-booking='<?php echo json_encode($booking); ?>'>
                              <i class="fas fa-eye"></i>
                            </button>

                            <!-- Update Status Button -->
                            <button type="button" class="text-teal-600 hover:text-teal-900 update-status"
                              data-id="<?php echo $booking['id']; ?>"
                              data-reference="<?php echo htmlspecialchars($booking['booking_reference']); ?>"
                              data-status="<?php echo $booking['booking_status']; ?>">
                              <i class="fas fa-edit"></i>
                            </button>

                            <!-- Assign Button - for pending or confirmed bookings -->
                            <!-- <?php if ($booking['booking_status'] == 'pending' || $booking['booking_status'] == 'confirmed'): ?>
                              <a href="assign-transportation.php?booking_id=<?php echo $booking['id']; ?>" class="text-purple-600 hover:text-purple-900">
                                <i class="fas fa-user-check"></i>
                              </a>
                            <?php endif; ?> -->

                            <!-- Delete Button -->
                            <button type="button" class="text-red-600 hover:text-red-900 delete-booking"
                              data-id="<?php echo $booking['id']; ?>"
                              data-reference="<?php echo htmlspecialchars($booking['booking_reference']); ?>">
                              <i class="fas fa-trash"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="8" class="py-6 text-center text-gray-500 italic">No transportation bookings found matching your criteria.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
              <div class="mt-6 flex justify-center">
                <ul class="flex space-x-2">
                  <!-- Previous Page Link -->
                  <li>
                    <?php if ($page > 1): ?>
                      <a href="?status=<?php echo $filter_status; ?>&service=<?php echo $filter_service; ?>&date_from=<?php echo $filter_date_from; ?>&date_to=<?php echo $filter_date_to; ?>&search=<?php echo $filter_search; ?>&page=<?php echo $page - 1; ?>"
                        class="px-3 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                        <i class="fas fa-chevron-left"></i>
                      </a>
                    <?php else: ?>
                      <span class="px-3 py-2 bg-gray-100 text-gray-400 rounded-md cursor-not-allowed">
                        <i class="fas fa-chevron-left"></i>
                      </span>
                    <?php endif; ?>
                  </li>

                  <!-- Page Numbers -->
                  <?php
                  $start_page = max(1, $page - 2);
                  $end_page = min($total_pages, $start_page + 4);

                  if ($end_page - $start_page < 4 && $total_pages > 5) {
                    $start_page = max(1, $end_page - 4);
                  }

                  for ($i = $start_page; $i <= $end_page; $i++):
                  ?>
                    <li>
                      <?php if ($i == $page): ?>
                        <span class="px-3 py-2 bg-teal-600 text-white rounded-md"><?php echo $i; ?></span>
                      <?php else: ?>
                        <a href="?status=<?php echo $filter_status; ?>&service=<?php echo $filter_service; ?>&date_from=<?php echo $filter_date_from; ?>&date_to=<?php echo $filter_date_to; ?>&search=<?php echo $filter_search; ?>&page=<?php echo $i; ?>"
                          class="px-3 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                          <?php echo $i; ?>
                        </a>
                      <?php endif; ?>
                    </li>
                  <?php endfor; ?>

                  <!-- Next Page Link -->
                  <li>
                    <?php if ($page < $total_pages): ?>
                      <a href="?status=<?php echo $filter_status; ?>&service=<?php echo $filter_service; ?>&date_from=<?php echo $filter_date_from; ?>&date_to=<?php echo $filter_date_to; ?>&search=<?php echo $filter_search; ?>&page=<?php echo $page + 1; ?>"
                        class="px-3 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                        <i class="fas fa-chevron-right"></i>
                      </a>
                    <?php else: ?>
                      <span class="px-3 py-2 bg-gray-100 text-gray-400 rounded-md cursor-not-allowed">
                        <i class="fas fa-chevron-right"></i>
                      </span>
                    <?php endif; ?>
                  </li>
                </ul>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- View Details Modal -->
  <div id="viewDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-4xl max-h-screen overflow-y-auto">
      <div class="border-b border-gray-200 px-6 py-4 flex justify-between items-center">
        <h3 class="text-xl font-semibold text-gray-800">Booking Details</h3>
        <button type="button" class="text-gray-400 hover:text-gray-500" id="closeDetailsModal">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Customer Information -->
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-lg font-semibold text-gray-700 mb-4">Customer Information</h4>
            <div class="space-y-2">
              <p><span class="font-medium text-gray-600">Name:</span> <span id="customer-name"></span></p>
              <p><span class="font-medium text-gray-600">Email:</span> <span id="customer-email"></span></p>
              <p><span class="font-medium text-gray-600">Phone:</span> <span id="customer-phone"></span></p>
              <p><span class="font-medium text-gray-600">Passengers:</span> <span id="passenger-count"></span></p>
            </div>
          </div>

          <!-- Booking Information -->
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-lg font-semibold text-gray-700 mb-4">Booking Information</h4>
            <div class="space-y-2">
              <p><span class="font-medium text-gray-600">Reference:</span> <span id="booking-reference"></span></p>
              <p><span class="font-medium text-gray-600">Status:</span> <span id="booking-status"></span></p>
              <p><span class="font-medium text-gray-600">Payment Status:</span> <span id="payment-status"></span></p>
              <p><span class="font-medium text-gray-600">Price:</span> $<span id="booking-price"></span></p>
              <p><span class="font-medium text-gray-600">Booking Date:</span> <span id="created-at"></span></p>
            </div>
          </div>

          <!-- Transportation Details -->
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-lg font-semibold text-gray-700 mb-4">Transportation Details</h4>
            <div class="space-y-2">
              <p><span class="font-medium text-gray-600">Service Type:</span> <span id="service-type"></span></p>
              <p><span class="font-medium text-gray-600">Route:</span> <span id="route-name"></span></p>
              <p><span class="font-medium text-gray-600">Vehicle Type:</span> <span id="vehicle-type"></span></p>
              <p><span class="font-medium text-gray-600">Vehicle Name:</span> <span id="vehicle-name"></span></p>
              <p><span class="font-medium text-gray-600">Duration:</span> <span id="duration-type"></span></p>
            </div>
          </div>

          <!-- Trip Information -->
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-lg font-semibold text-gray-700 mb-4">Trip Information</h4>
            <div class="space-y-2">
              <p><span class="font-medium text-gray-600">Date:</span> <span id="trip-date"></span></p>
              <p><span class="font-medium text-gray-600">Time:</span> <span id="trip-time"></span></p>
              <p><span class="font-medium text-gray-600">Pickup Location:</span> <span id="pickup-location"></span></p>
              <p><span class="font-medium text-gray-600"></span> <span id="dropoff-location"></span></p>
            </div>
          </div>
        </div>

        <!-- Special Requests -->
        <div class="mt-6 bg-gray-50 p-4 rounded-lg">
          <h4 class="text-lg font-semibold text-gray-700 mb-2">Special Requests</h4>
          <p id="special-requests" class="text-gray-600 italic"></p>
        </div>

        <!-- Actions -->
        <div class="mt-6 flex justify-end space-x-4">
          <button type="button" id="closeDetailsBtn" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
            Close
          </button>
          <button type="button" id="printDetailsBtn" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
            <i class="fas fa-print mr-2"></i> Print Details
          </button>
          <span id="modal-assign-button-container">
            <!-- Assign button will be added here dynamically if booking is pending -->
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Update Status Modal -->
  <div id="updateStatusModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
      <div class="border-b border-gray-200 px-6 py-4 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-800">Update Booking Status</h3>
        <button type="button" class="text-gray-400 hover:text-gray-500" id="closeStatusModal">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <form id="updateStatusForm" method="POST" action="">
        <input type="hidden" name="update_status" value="1">
        <input type="hidden" name="booking_id" id="status-booking-id">
        <div class="p-6">
          <div class="mb-4">
            <p class="text-sm text-gray-600 mb-2">Booking Reference: <span id="status-booking-ref" class="font-medium"></span></p>
            <label for="new_status" class="block text-sm font-medium text-gray-700 mb-2">New Status</label>
            <select id="new_status" name="new_status" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
              <option value="pending">Pending</option>
              <option value="confirmed">Confirmed</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <div class="flex justify-end space-x-3">
            <button type="button" id="cancelStatusUpdate" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
              Cancel
            </button>
            <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-md hover:bg-teal-700">
              Update Status
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Booking Confirmation (Hidden Form) -->
  <form id="deleteBookingForm" method="POST" action="" style="display: none;">
    <input type="hidden" name="delete_booking" value="1">
    <input type="hidden" name="booking_id" id="delete-booking-id">
  </form>

  <?php include 'includes/js-links.php'; ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize date pickers
      flatpickr('#date_from', {
        dateFormat: 'Y-m-d',
        allowInput: true
      });

      flatpickr('#date_to', {
        dateFormat: 'Y-m-d',
        allowInput: true
      });

      // View Details Modal
      const viewDetailsModal = document.getElementById('viewDetailsModal');
      const closeDetailsModal = document.getElementById('closeDetailsModal');
      const closeDetailsBtn = document.getElementById('closeDetailsBtn');
      const printDetailsBtn = document.getElementById('printDetailsBtn');

      // Update Status Modal
      const updateStatusModal = document.getElementById('updateStatusModal');
      const closeStatusModal = document.getElementById('closeStatusModal');
      const cancelStatusUpdate = document.getElementById('cancelStatusUpdate');

      // Get all view details buttons
      const viewDetailsButtons = document.querySelectorAll('.view-details');
      viewDetailsButtons.forEach(button => {
        button.addEventListener('click', function() {
          const bookingData = JSON.parse(this.getAttribute('data-booking'));

          // Fill modal with booking data
          document.getElementById('customer-name').textContent = bookingData.full_name;
          document.getElementById('customer-email').textContent = bookingData.email;
          document.getElementById('customer-phone').textContent = bookingData.phone_number;
          document.getElementById('passenger-count').textContent = bookingData.passengers;

          document.getElementById('booking-reference').textContent = bookingData.booking_reference;

          // Format the status with badge
          let statusHTML = '';
          switch (bookingData.booking_status) {
            case 'pending':
              statusHTML = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>';
              break;
            case 'confirmed':
              statusHTML = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Confirmed</span>';
              break;
            case 'completed':
              statusHTML = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Completed</span>';
              break;
            case 'cancelled':
              statusHTML = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Cancelled</span>';
              break;
            default:
              statusHTML = bookingData.booking_status;
          }
          document.getElementById('booking-status').innerHTML = statusHTML;

          // Format payment status with color
          let paymentHTML = '';
          if (bookingData.payment_status === 'paid') {
            paymentHTML = '<span class="text-green-600 font-medium">Paid</span>';
          } else {
            paymentHTML = '<span class="text-orange-600 font-medium">Unpaid</span>';
          }
          document.getElementById('payment-status').innerHTML = paymentHTML;

          document.getElementById('booking-price').textContent = parseFloat(bookingData.price).toFixed(2);

          // Format dates
          const createdDate = new Date(bookingData.created_at);
          document.getElementById('created-at').textContent = createdDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
          });

          document.getElementById('service-type').textContent = bookingData.service_type.charAt(0).toUpperCase() + bookingData.service_type.slice(1);
          document.getElementById('route-name').textContent = bookingData.route_name;
          document.getElementById('vehicle-type').textContent = bookingData.vehicle_type.replace(/_/g, ' ').split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
          document.getElementById('vehicle-name').textContent = bookingData.vehicle_name;
          document.getElementById('duration-type').textContent = bookingData.duration ?
            bookingData.duration.replace(/_/g, ' ').split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ') :
            'One Way'; // Default value if null
          const tripDate = new Date(bookingData.booking_date);
          document.getElementById('trip-date').textContent = tripDate.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
          });

          const tripTime = new Date(`2000-01-01T${bookingData.booking_time}`);
          document.getElementById('trip-time').textContent = tripTime.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
          });

          document.getElementById('pickup-location').textContent = bookingData.pickup_location;
          document.getElementById('dropoff-location').textContent = bookingData.dropoff_location;

          // Special requests
          document.getElementById('special-requests').textContent = bookingData.special_requests || 'No special requests provided';

          // Show/hide assign button based on status
          const assignContainer = document.getElementById('modal-assign-button-container');
          if (bookingData.booking_status === 'pending' || bookingData.booking_status === 'confirmed') {
            assignContainer.innerHTML = `
              <a href="assign-transportation.php?booking_id=${bookingData.id}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-user-check mr-2"></i> Assign Vehicle
              </a>
            `;
          } else {
            assignContainer.innerHTML = '';
          }

          // Show the modal
          viewDetailsModal.classList.remove('hidden');
        });
      });

      // Close details modal
      [closeDetailsModal, closeDetailsBtn].forEach(element => {
        element.addEventListener('click', function() {
          viewDetailsModal.classList.add('hidden');
        });
      });

      // Print functionality
      printDetailsBtn.addEventListener('click', function() {
        window.print();
      });

      // Update Status Functionality
      const updateStatusButtons = document.querySelectorAll('.update-status');
      updateStatusButtons.forEach(button => {
        button.addEventListener('click', function() {
          const bookingId = this.getAttribute('data-id');
          const bookingRef = this.getAttribute('data-reference');
          const currentStatus = this.getAttribute('data-status');

          document.getElementById('status-booking-id').value = bookingId;
          document.getElementById('status-booking-ref').textContent = bookingRef;

          // Set current status as selected
          const statusSelect = document.getElementById('new_status');
          for (let i = 0; i < statusSelect.options.length; i++) {
            if (statusSelect.options[i].value === currentStatus) {
              statusSelect.selectedIndex = i;
              break;
            }
          }

          // Show the modal
          updateStatusModal.classList.remove('hidden');
        });
      });

      // Close status modal
      [closeStatusModal, cancelStatusUpdate].forEach(element => {
        element.addEventListener('click', function() {
          updateStatusModal.classList.add('hidden');
        });
      });

      // Delete Booking functionality
      const deleteButtons = document.querySelectorAll('.delete-booking');
      deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
          const bookingId = this.getAttribute('data-id');
          const bookingRef = this.getAttribute('data-reference');

          Swal.fire({
            title: 'Delete Booking?',
            html: `Are you sure you want to delete booking <strong>${bookingRef}</strong>?<br><br>This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Yes, delete it!'
          }).then((result) => {
            if (result.isConfirmed) {
              document.getElementById('delete-booking-id').value = bookingId;
              document.getElementById('deleteBookingForm').submit();
            }
          });
        });
      });

      // Update Payment Status functionality
      const paymentStatusSelects = document.querySelectorAll('.payment-status-select');
      paymentStatusSelects.forEach(select => {
        select.addEventListener('change', function() {
          const bookingId = this.getAttribute('data-booking-id');
          const newPaymentStatus = this.value;

          // Confirm the change
          Swal.fire({
            title: 'Update Payment Status?',
            text: `Are you sure you want to change the payment status to "${newPaymentStatus}" for this booking?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Yes, update it!'
          }).then((result) => {
            if (result.isConfirmed) {
              // Send AJAX request to update payment status
              fetch('update-payment-status.php', {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                  },
                  body: `booking_id=${bookingId}&payment_status=${newPaymentStatus}`
                })
                .then(response => response.json())
                .then(data => {
                  if (data.success) {
                    Swal.fire({
                      title: 'Success!',
                      text: data.message,
                      icon: 'success',
                      confirmButtonColor: '#10B981'
                    });

                    // Update the UI (e.g., color of the dropdown)
                    if (newPaymentStatus === 'paid') {
                      this.classList.remove('text-orange-600');
                      this.classList.add('text-green-600');
                    } else {
                      this.classList.remove('text-green-600');
                      this.classList.add('text-orange-600');
                    }
                  } else {
                    Swal.fire({
                      title: 'Error!',
                      text: data.message,
                      icon: 'error',
                      confirmButtonColor: '#EF4444'
                    });
                    // Revert the dropdown to its previous value
                    this.value = newPaymentStatus === 'paid' ? 'unpaid' : 'paid';
                  }
                })
                .catch(error => {
                  Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while updating the payment status.',
                    icon: 'error',
                    confirmButtonColor: '#EF4444'
                  });
                  // Revert the dropdown to its previous value
                  this.value = newPaymentStatus === 'paid' ? 'unpaid' : 'paid';
                });
            } else {
              // Revert the dropdown to its previous value if the user cancels
              this.value = newPaymentStatus === 'paid' ? 'unpaid' : 'paid';
            }
          });
        });
      });

      // Close modals when clicking outside
      window.addEventListener('click', function(event) {
        if (event.target === viewDetailsModal) {
          viewDetailsModal.classList.add('hidden');
        }
        if (event.target === updateStatusModal) {
          updateStatusModal.classList.add('hidden');
        }
      });
    });
  </script>

  <style>
    /* Print Styles */
    @media print {
      body * {
        visibility: hidden;
      }

      #viewDetailsModal,
      #viewDetailsModal * {
        visibility: visible;
      }

      #viewDetailsModal {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: auto;
        background: white;
      }

      #closeDetailsBtn,
      #printDetailsBtn,
      #modal-assign-button-container {
        display: none;
      }
    }

    /* Status badges styling */
    .badge {
      display: inline-flex;
      align-items: center;
      padding: 0.25rem 0.5rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .badge-pending {
      background-color: #FEF3C7;
      color: #92400E;
    }

    .badge-confirmed {
      background-color: #DBEAFE;
      color: #1E40AF;
    }

    .badge-completed {
      background-color: #D1FAE5;
      color: #065F46;
    }

    .badge-cancelled {
      background-color: #FEE2E2;
      color: #B91C1C;
    }
  </style>
</body>

</html>