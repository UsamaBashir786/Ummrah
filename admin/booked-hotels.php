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
$filter_hotel = isset($_GET['hotel']) ? $_GET['hotel'] : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$filter_search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Base SQL query with better booking statistics
$sql = "SELECT hb.*, u.full_name, u.email, u.phone_number, h.hotel_name, h.location as hotel_location, 
        h.price_per_night, h.room_count, h.rating, h.amenities,
        (SELECT COUNT(*) FROM hotel_bookings WHERE hotel_id = h.id AND 
         status IN ('pending', 'confirmed', 'completed') AND
         ((check_in_date <= CURDATE() AND check_out_date >= CURDATE()) OR 
          (check_in_date >= CURDATE() AND check_in_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)))
        ) as current_bookings,
        (SELECT SUM(DATEDIFF(check_out_date, check_in_date) * h.price_per_night) 
         FROM hotel_bookings 
         WHERE hotel_id = h.id AND status IN ('confirmed', 'completed')
        ) as hotel_revenue
        FROM hotel_bookings hb
        LEFT JOIN users u ON hb.user_id = u.id
        LEFT JOIN hotels h ON hb.hotel_id = h.id
        WHERE 1=1";

// Apply filters
if (!empty($filter_status)) {
  $sql .= " AND hb.status = '" . mysqli_real_escape_string($conn, $filter_status) . "'";
}

if (!empty($filter_hotel)) {
  $sql .= " AND hb.hotel_id = '" . mysqli_real_escape_string($conn, $filter_hotel) . "'";
}

if (!empty($filter_date_from)) {
  $sql .= " AND hb.check_in_date >= '" . mysqli_real_escape_string($conn, $filter_date_from) . "'";
}

if (!empty($filter_date_to)) {
  $sql .= " AND hb.check_out_date <= '" . mysqli_real_escape_string($conn, $filter_date_to) . "'";
}

if (!empty($filter_search)) {
  $search_term = mysqli_real_escape_string($conn, $filter_search);
  $sql .= " AND (hb.guest_name LIKE '%$search_term%'
           OR hb.guest_email LIKE '%$search_term%'
           OR hb.guest_phone LIKE '%$search_term%'
           OR h.hotel_name LIKE '%$search_term%')";
}

// Count total items and get stats
$count_sql = "SELECT 
                COUNT(*) as total, 
                COUNT(CASE WHEN hb.status = 'completed' THEN 1 END) as completed,
                SUM(DATEDIFF(hb.check_out_date, hb.check_in_date) * h.price_per_night) as total_revenue
              FROM hotel_bookings hb
              LEFT JOIN hotels h ON hb.hotel_id = h.id
              WHERE 1=1";

// Apply filters to count query
if (!empty($filter_status)) {
  $count_sql .= " AND hb.status = '" . mysqli_real_escape_string($conn, $filter_status) . "'";
}

if (!empty($filter_hotel)) {
  $count_sql .= " AND hb.hotel_id = '" . mysqli_real_escape_string($conn, $filter_hotel) . "'";
}

if (!empty($filter_date_from)) {
  $count_sql .= " AND hb.check_in_date >= '" . mysqli_real_escape_string($conn, $filter_date_from) . "'";
}

if (!empty($filter_date_to)) {
  $count_sql .= " AND hb.check_out_date <= '" . mysqli_real_escape_string($conn, $filter_date_to) . "'";
}

if (!empty($filter_search)) {
  $search_term = mysqli_real_escape_string($conn, $filter_search);
  $count_sql .= " AND (hb.guest_name LIKE '%$search_term%'
                 OR hb.guest_email LIKE '%$search_term%'
                 OR hb.guest_phone LIKE '%$search_term%'
                 OR h.hotel_name LIKE '%$search_term%')";
}

$count_result = $conn->query($count_sql);
$count_row = $count_result->fetch_assoc();
$total_items = $count_row['total'];
$total_revenue = $count_row['total_revenue'] ?? 0;
$completed_bookings = $count_row['completed'] ?? 0;
$total_pages = ceil($total_items / $items_per_page);

// Finalize query with pagination and ordering
$sql .= " ORDER BY hb.check_in_date DESC, hb.created_at DESC LIMIT $offset, $items_per_page";
$result = $conn->query($sql);

// Get hotels for filter dropdown
$hotels_sql = "SELECT id, hotel_name FROM hotels ORDER BY hotel_name ASC";
$hotels_result = $conn->query($hotels_sql);

// Handle booking status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
  $booking_id = $_POST['booking_id'];
  $new_status = $_POST['new_status'];

  $update_sql = "UPDATE hotel_bookings SET 
                 status = '" . mysqli_real_escape_string($conn, $new_status) . "',
                 updated_at = NOW()
                 WHERE id = " . intval($booking_id);

  if ($conn->query($update_sql)) {
    $success_message = "Booking status updated successfully!";

    // Redirect to avoid form resubmission
    header("Location: booked-hotels.php?status=$filter_status&hotel=$filter_hotel&date_from=$filter_date_from&date_to=$filter_date_to&search=$filter_search&page=$page&success=1");
    exit();
  } else {
    $error_message = "Error updating status: " . $conn->error;
  }
}

// Handle booking deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_booking'])) {
  $booking_id = $_POST['booking_id'];

  $delete_sql = "DELETE FROM hotel_bookings WHERE id = " . intval($booking_id);

  if ($conn->query($delete_sql)) {
    $success_message = "Booking deleted successfully!";

    // Redirect to avoid form resubmission
    header("Location: booked-hotels.php?status=$filter_status&hotel=$filter_hotel&date_from=$filter_date_from&date_to=$filter_date_to&search=$filter_search&page=$page&success=2");
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
          <i class="text-teal-600 fas fa-hotel mx-2"></i> Hotel Bookings
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

            <!-- Add payment status to the table -->
            <style>
              .payment-status-paid {
                color: #047857;
                font-weight: 600;
              }

              .payment-status-unpaid {
                color: #b45309;
                font-weight: 600;
              }

              .payment-status-refunded {
                color: #4b5563;
                font-weight: 600;
              }
            </style>
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
              <i class="fas fa-filter text-teal-600 mr-2"></i> Filter Hotel Bookings
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

              <!-- Hotel Filter -->
              <div>
                <label for="hotel" class="block text-sm font-medium text-gray-700 mb-1">Hotel</label>
                <select id="hotel" name="hotel" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
                  <option value="">All Hotels</option>
                  <?php if ($hotels_result && $hotels_result->num_rows > 0): ?>
                    <?php while ($hotel = $hotels_result->fetch_assoc()): ?>
                      <option value="<?php echo $hotel['id']; ?>" <?php echo ($filter_hotel == $hotel['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($hotel['hotel_name']); ?>
                      </option>
                    <?php endwhile; ?>
                  <?php endif; ?>
                </select>
              </div>

              <!-- Date Range Filter -->
              <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Check-in Date (From)</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo $filter_date_from; ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
              </div>

              <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Check-out Date (To)</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo $filter_date_to; ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
              </div>

              <!-- Search Box -->
              <div class="md:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search (Guest, Email, Phone, Hotel)</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($filter_search); ?>" placeholder="Search by guest name, email, phone or hotel name" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
              </div>

              <!-- Filter Buttons -->
              <div class="md:col-span-2 flex items-end space-x-4">
                <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-md flex items-center">
                  <i class="fas fa-search mr-2"></i> Apply Filters
                </button>
                <a href="booked-hotels.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md flex items-center">
                  <i class="fas fa-undo mr-2"></i> Reset
                </a>
              </div>
            </form>
          </div>

          <!-- Results Section -->
          <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
              <h2 class="text-lg font-semibold text-gray-700">
                <i class="fas fa-list text-teal-600 mr-2"></i> Hotel Bookings
              </h2>
              <p class="text-sm text-gray-600">
                Showing <?php echo ($result && $result->num_rows > 0) ? ($offset + 1) : 0; ?> -
                <?php echo min($offset + $items_per_page, $total_items); ?> of <?php echo $total_items; ?> bookings
              </p>
            </div>

            <!-- Stats Section -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
              <!-- Total Bookings -->
              <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-4 text-white">
                <div class="text-lg opacity-80 mb-1">Total Bookings</div>
                <div class="text-3xl font-bold"><?php echo $total_items; ?></div>
              </div>

              <!-- Total Revenue -->
              <div class="bg-gradient-to-r from-green-400 to-green-500 rounded-lg p-4 text-white">
                <div class="text-lg opacity-80 mb-1">Total Revenue</div>
                <div class="text-3xl font-bold">PKR <?php echo number_format($total_revenue, 2); ?></div>
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
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guest</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hotel</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check-in</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check-out</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                    <th class="py-3 px-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($booking = $result->fetch_assoc()): ?>
                      <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4 whitespace-nowrap">
                          <div class="text-sm font-medium text-gray-900"><?php echo $booking['id']; ?></div>
                        </td>
                        <td class="py-3 px-4">
                          <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($booking['guest_name']); ?></div>
                          <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['guest_phone']); ?></div>
                          <div class="text-xs text-gray-500"><?php echo htmlspecialchars($booking['guest_email']); ?></div>
                        </td>
                        <td class="py-3 px-4">
                          <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($booking['hotel_name'] ?? 'N/A'); ?></div>
                          <div class="text-xs text-gray-500"><?php echo htmlspecialchars(ucfirst($booking['hotel_location'] ?? '')); ?></div>
                        </td>
                        <td class="py-3 px-4">
                          <div class="text-sm text-gray-900">Room <?php echo htmlspecialchars($booking['room_id']); ?></div>
                          <div class="text-xs text-gray-500">
                            <?php if (isset($booking['price_per_night'])): ?>
                              PKR <?php echo number_format($booking['price_per_night'], 2); ?>/night
                            <?php endif; ?>
                          </div>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                          <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></div>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                          <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></div>
                          <div class="text-xs text-gray-500">
                            <?php
                            $nights = (strtotime($booking['check_out_date']) - strtotime($booking['check_in_date'])) / (60 * 60 * 24);
                            echo $nights . ' ' . ($nights == 1 ? 'night' : 'nights');
                            ?>
                          </div>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                          <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php
                            switch ($booking['status']) {
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
                            <?php echo ucfirst($booking['status']); ?>
                          </span>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                          <?php
                          // We'll add a mock payment status field since the hotel_bookings table doesn't have one
                          $paymentStatus = ($booking['status'] == 'completed') ? 'paid' : 'unpaid';
                          $paymentStatusClass = 'payment-status-' . $paymentStatus;
                          ?>
                          <span class="<?php echo $paymentStatusClass; ?>">
                            <?php echo ucfirst($paymentStatus); ?>
                          </span>
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
                              data-guest="<?php echo htmlspecialchars($booking['guest_name']); ?>"
                              data-status="<?php echo $booking['status']; ?>">
                              <i class="fas fa-edit"></i>
                            </button>

                            <!-- Delete Button -->
                            <button type="button" class="text-red-600 hover:text-red-900 delete-booking"
                              data-id="<?php echo $booking['id']; ?>"
                              data-guest="<?php echo htmlspecialchars($booking['guest_name']); ?>">
                              <i class="fas fa-trash"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="9" class="py-6 text-center text-gray-500 italic">No hotel bookings found matching your criteria.</td>
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
                      <a href="?status=<?php echo $filter_status; ?>&hotel=<?php echo $filter_hotel; ?>&date_from=<?php echo $filter_date_from; ?>&date_to=<?php echo $filter_date_to; ?>&search=<?php echo $filter_search; ?>&page=<?php echo $page - 1; ?>"
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
                        <a href="?status=<?php echo $filter_status; ?>&hotel=<?php echo $filter_hotel; ?>&date_from=<?php echo $filter_date_from; ?>&date_to=<?php echo $filter_date_to; ?>&search=<?php echo $filter_search; ?>&page=<?php echo $i; ?>"
                          class="px-3 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                          <?php echo $i; ?>
                        </a>
                      <?php endif; ?>
                    </li>
                  <?php endfor; ?>

                  <!-- Next Page Link -->
                  <li>
                    <?php if ($page < $total_pages): ?>
                      <a href="?status=<?php echo $filter_status; ?>&hotel=<?php echo $filter_hotel; ?>&date_from=<?php echo $filter_date_from; ?>&date_to=<?php echo $filter_date_to; ?>&search=<?php echo $filter_search; ?>&page=<?php echo $page + 1; ?>"
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
        <h3 class="text-xl font-semibold text-gray-800">Hotel Booking Details</h3>
        <button type="button" class="text-gray-400 hover:text-gray-500" id="closeDetailsModal">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Guest Information -->
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-lg font-semibold text-gray-700 mb-4">Guest Information</h4>
            <div class="space-y-2">
              <p><span class="font-medium text-gray-600">Name:</span> <span id="guest-name"></span></p>
              <p><span class="font-medium text-gray-600">Email:</span> <span id="guest-email"></span></p>
              <p><span class="font-medium text-gray-600">Phone:</span> <span id="guest-phone"></span></p>
            </div>
          </div>

          <!-- Booking Information -->
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-lg font-semibold text-gray-700 mb-4">Booking Information</h4>
            <div class="space-y-2">
              <p><span class="font-medium text-gray-600">Booking ID:</span> <span id="booking-id"></span></p>
              <p><span class="font-medium text-gray-600">Status:</span> <span id="booking-status"></span></p>
              <p><span class="font-medium text-gray-600">Booking Date:</span> <span id="created-at"></span></p>
              <p><span class="font-medium text-gray-600">Last Updated:</span> <span id="updated-at"></span></p>
            </div>
          </div>

          <!-- Hotel Details -->
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-lg font-semibold text-gray-700 mb-4">Hotel Details</h4>
            <div class="space-y-2">
              <p><span class="font-medium text-gray-600">Hotel:</span> <span id="hotel-name"></span></p>
              <p><span class="font-medium text-gray-600">Location:</span> <span id="hotel-location"></span></p>
              <p><span class="font-medium text-gray-600">Room ID:</span> <span id="room-id"></span></p>
              <p><span class="font-medium text-gray-600">Price Per Night:</span> PKR <span id="price-per-night"></span></p>
              <p><span class="font-medium text-gray-600">Hotel Rating:</span> <span id="hotel-rating"></span></p>
              <p><span class="font-medium text-gray-600">Amenities:</span> <span id="hotel-amenities"></span></p>
            </div>
          </div>

          <!-- Booking Statistics -->
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-lg font-semibold text-gray-700 mb-4">Booking Statistics</h4>
            <div class="space-y-2">
              <p><span class="font-medium text-gray-600">Total Hotel Rooms:</span> <span id="total-rooms"></span></p>
              <p><span class="font-medium text-gray-600">Currently Booked Rooms:</span> <span id="current-bookings"></span></p>
              <p><span class="font-medium text-gray-600">Available Rooms:</span> <span id="available-rooms"></span></p>
              <p><span class="font-medium text-gray-600">Occupancy Rate:</span> <span id="occupancy-rate"></span></p>
              <p><span class="font-medium text-gray-600">Room Revenue:</span> PKR <span id="room-revenue"></span></p>
              <p><span class="font-medium text-gray-600">Hotel Total Revenue:</span> PKR <span id="hotel-total-revenue"></span></p>
            </div>
          </div>

          <!-- Stay Information -->
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-lg font-semibold text-gray-700 mb-4">Stay Information</h4>
            <div class="space-y-2">
              <p><span class="font-medium text-gray-600">Check-in Date:</span> <span id="check-in-date"></span></p>
              <p><span class="font-medium text-gray-600">Check-out Date:</span> <span id="check-out-date"></span></p>
              <p><span class="font-medium text-gray-600">Duration:</span> <span id="stay-duration"></span></p>
              <p><span class="font-medium text-gray-600">Total Price:</span> PKR <span id="total-price"></span></p>
              <p><span class="font-medium text-gray-600">Payment Status:</span> <span id="payment-status"></span></p>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="mt-6 flex justify-end space-x-4">
          <button type="button" id="closeDetailsBtn" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
            Close
          </button>
          <button type="button" id="printDetailsBtn" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
            <i class="fas fa-print mr-2"></i> Print Details
          </button>
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
            <p class="text-sm text-gray-600 mb-2">Guest: <span id="status-guest-name" class="font-medium"></span></p>
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

      // Function to display star ratings
      function displayStarRating(rating) {
        let stars = '';
        for (let i = 0; i < 5; i++) {
          if (i < rating) {
            stars += '<i class="fas fa-star text-yellow-400"></i>';
          } else {
            stars += '<i class="far fa-star text-gray-300"></i>';
          }
        }
        return stars;
      }

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
          document.getElementById('guest-name').textContent = bookingData.guest_name;
          document.getElementById('guest-email').textContent = bookingData.guest_email;
          document.getElementById('guest-phone').textContent = bookingData.guest_phone;

          document.getElementById('booking-id').textContent = bookingData.id;

          // Format the status with badge
          let statusHTML = '';
          switch (bookingData.status) {
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
              statusHTML = bookingData.status;
          }
          document.getElementById('booking-status').innerHTML = statusHTML;

          // Format dates
          const createdDate = new Date(bookingData.created_at);
          document.getElementById('created-at').textContent = createdDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
          });

          const updatedDate = new Date(bookingData.updated_at);
          document.getElementById('updated-at').textContent = updatedDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
          });

          document.getElementById('hotel-name').textContent = bookingData.hotel_name || 'N/A';
          document.getElementById('hotel-location').textContent = (bookingData.hotel_location ? bookingData.hotel_location.charAt(0).toUpperCase() + bookingData.hotel_location.slice(1) : 'N/A');
          document.getElementById('room-id').textContent = bookingData.room_id || 'N/A';
          document.getElementById('total-rooms').textContent = bookingData.room_count || 'N/A';
          document.getElementById('price-per-night').textContent = bookingData.price_per_night ? parseFloat(bookingData.price_per_night).toFixed(2) : 'N/A';

          // Display rating as stars
          const ratingElement = document.getElementById('hotel-rating');
          if (bookingData.rating) {
            ratingElement.innerHTML = displayStarRating(bookingData.rating);
          } else {
            ratingElement.textContent = 'N/A';
          }

          // Show amenities if available
          if (bookingData.amenities) {
            try {
              const amenities = JSON.parse(bookingData.amenities);
              if (amenities && amenities.length > 0) {
                const amenitiesList = amenities.map(a => a.charAt(0).toUpperCase() + a.slice(1)).join(', ');
                document.getElementById('hotel-amenities').textContent = amenitiesList;
              } else {
                document.getElementById('hotel-amenities').textContent = 'No amenities listed';
              }
            } catch (e) {
              document.getElementById('hotel-amenities').textContent = 'No amenities listed';
            }
          } else {
            document.getElementById('hotel-amenities').textContent = 'No amenities listed';
          }

          const checkInDate = new Date(bookingData.check_in_date);
          document.getElementById('check-in-date').textContent = checkInDate.toLocaleDateString('en-US', {
            weekday: 'long',
            year: "numeric",
            month: 'long',
            day: 'numeric'
          });

          const checkOutDate = new Date(bookingData.check_out_date);
          document.getElementById('check-out-date').textContent = checkOutDate.toLocaleDateString('en-US', {
            weekday: 'long',
            year: "numeric",
            month: 'long',
            day: 'numeric'
          });

          // Calculate duration and total price
          const nights = (checkOutDate - checkInDate) / (1000 * 60 * 60 * 24);
          document.getElementById('stay-duration').textContent = nights + ' ' + (nights == 1 ? 'night' : 'nights');

          if (bookingData.price_per_night) {
            const roomRevenue = nights * parseFloat(bookingData.price_per_night);
            document.getElementById('total-price').textContent = roomRevenue.toFixed(2);
            document.getElementById('room-revenue').textContent = roomRevenue.toFixed(2);
          } else {
            document.getElementById('total-price').textContent = 'N/A';
            document.getElementById('room-revenue').textContent = 'N/A';
          }

          // Display payment status
          const paymentStatusElement = document.getElementById('payment-status');
          const paymentStatus = (bookingData.status === 'completed') ? 'paid' : 'unpaid';
          paymentStatusElement.textContent = paymentStatus.charAt(0).toUpperCase() + paymentStatus.slice(1);
          paymentStatusElement.className = 'payment-status-' + paymentStatus;

          // Display booking statistics
          if (bookingData.current_bookings) {
            document.getElementById('current-bookings').textContent = bookingData.current_bookings;
            const availableRooms = bookingData.room_count ? (bookingData.room_count - bookingData.current_bookings) : 0;
            document.getElementById('available-rooms').textContent = availableRooms || 'N/A';

            // Calculate occupancy rate
            if (bookingData.room_count) {
              const occupancyRate = (bookingData.current_bookings / bookingData.room_count * 100).toFixed(1);
              document.getElementById('occupancy-rate').textContent = occupancyRate + '%';
            } else {
              document.getElementById('occupancy-rate').textContent = 'N/A';
            }

            // Show hotel total revenue if available
            if (bookingData.hotel_revenue) {
              document.getElementById('hotel-total-revenue').textContent = parseFloat(bookingData.hotel_revenue).toFixed(2);
            } else if (bookingData.price_per_night) {
              // Estimate total revenue based on current bookings and average stay length
              const estimatedTotalRevenue = bookingData.current_bookings * parseFloat(bookingData.price_per_night) * nights;
              document.getElementById('hotel-total-revenue').textContent = estimatedTotalRevenue.toFixed(2);
            } else {
              document.getElementById('hotel-total-revenue').textContent = 'N/A';
            }
          } else {
            document.getElementById('current-bookings').textContent = '1';
            document.getElementById('available-rooms').textContent =
              bookingData.room_count ? (bookingData.room_count - 1) : 'N/A';
            document.getElementById('occupancy-rate').textContent =
              bookingData.room_count ? ((1 / bookingData.room_count * 100).toFixed(1) + '%') : 'N/A';

            // For revenue calculations with only this booking
            if (bookingData.price_per_night) {
              const roomRevenue = nights * parseFloat(bookingData.price_per_night);
              document.getElementById('hotel-total-revenue').textContent = roomRevenue.toFixed(2);
            } else {
              document.getElementById('hotel-total-revenue').textContent = 'N/A';
            }
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
          const guestName = this.getAttribute('data-guest');
          const currentStatus = this.getAttribute('data-status');

          document.getElementById('status-booking-id').value = bookingId;
          document.getElementById('status-guest-name').textContent = guestName;

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
          const guestName = this.getAttribute('data-guest');

          Swal.fire({
            title: 'Delete Booking?',
            html: `Are you sure you want to delete booking for <strong>${guestName}</strong>?<br><br>This action cannot be undone.`,
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
      #printDetailsBtn {
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

    /* Payment status styling */
    .payment-status-paid {
      color: #047857;
      font-weight: 600;
    }

    .payment-status-unpaid {
      color: #b45309;
      font-weight: 600;
    }

    .payment-status-refunded {
      color: #4b5563;
      font-weight: 600;
    }
  </style>
</body>

</html>