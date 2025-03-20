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

// Get filter params
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';

// Build the query
$query = "
  SELECT b.*, h.hotel_name, h.location, h.price_per_night
  FROM hotel_bookings b
  JOIN hotels h ON b.hotel_id = h.id
  WHERE 1=1
";

$params = [];

if (!empty($status)) {
  $query .= " AND b.status = :status";
  $params['status'] = $status;
}

if (!empty($search)) {
  $query .= " AND (b.guest_name LIKE :search OR b.guest_email LIKE :search OR h.hotel_name LIKE :search)";
  $params['search'] = "%$search%";
}

// Date filtering
if (!empty($date_filter)) {
  switch ($date_filter) {
    case 'today':
      $query .= " AND (DATE(b.check_in_date) = CURDATE() OR DATE(b.check_out_date) = CURDATE())";
      break;
    case 'tomorrow':
      $query .= " AND (DATE(b.check_in_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY) OR DATE(b.check_out_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY))";
      break;
    case 'this_week':
      $query .= " AND WEEK(b.check_in_date) = WEEK(CURDATE()) OR WEEK(b.check_out_date) = WEEK(CURDATE())";
      break;
    case 'this_month':
      $query .= " AND MONTH(b.check_in_date) = MONTH(CURDATE()) OR MONTH(b.check_out_date) = MONTH(CURDATE())";
      break;
  }
}

$query .= " ORDER BY b.created_at DESC";

// Fetch bookings
try {
  $stmt = $pdo->prepare($query);
  $stmt->execute($params);
  $bookings = $stmt->fetchAll();
} catch (Exception $e) {
  die("Error: " . $e->getMessage());
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
  $booking_id = (int)$_POST['booking_id'];
  $new_status = $_POST['new_status'];

  try {
    $stmt = $pdo->prepare("UPDATE hotel_bookings SET status = :status WHERE id = :id");
    $stmt->execute([
      'status' => $new_status,
      'id' => $booking_id
    ]);

    // Refresh the page to show updated data
    header("Location: view-bookings.php?status_updated=1");
    exit;
  } catch (Exception $e) {
    $error_message = "Update failed: " . $e->getMessage();
  }
}

// Count bookings by status
$status_counts = [
  'all' => count($bookings),
  'pending' => 0,
  'confirmed' => 0,
  'cancelled' => 0,
  'completed' => 0
];

foreach ($bookings as $booking) {
  if (isset($booking['status']) && isset($status_counts[$booking['status']])) {
    $status_counts[$booking['status']]++;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-material-ui/material-ui.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <style>
    .status-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .status-pending {
      background-color: #FEF9C3;
      color: #854D0E;
    }

    .status-confirmed {
      background-color: #DCFCE7;
      color: #166534;
    }

    .status-cancelled {
      background-color: #FEE2E2;
      color: #991B1B;
    }

    .status-completed {
      background-color: #DBEAFE;
      color: #1E40AF;
    }

    .card-hover {
      transition: all 0.3s ease;
    }

    .card-hover:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .status-card {
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .status-card:hover {
      transform: scale(1.05);
    }

    .status-card.active {
      border-color: #0D9488;
      background-color: #F0FDFA;
    }

    @media (max-width: 768px) {
      .mobile-table-card {
        border: 1px solid #E5E7EB;
        margin-bottom: 1rem;
        border-radius: 0.5rem;
        overflow: hidden;
      }

      .mobile-table-header {
        background-color: #F9FAFB;
        padding: 0.75rem 1rem;
        font-weight: 600;
      }

      .mobile-table-body {
        padding: 1rem;
      }

      .mobile-table-row {
        margin-bottom: 0.5rem;
      }
    }
  </style>
</head>

<body class="bg-gray-50">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/navbar.php'; ?>
    <!-- Main Content -->
    <div class="main flex-1 flex flex-col overflow-auto">
      <br><br><br><br>
      <!-- Navbar -->
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <button class="md:hidden text-gray-800" id="menu-btn">
          <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-xl font-semibold">
          <i class="text-teal-600 fas fa-calendar-check mx-2"></i> Manage Bookings
        </h1>
        <div class="flex items-center">
          <a href="export-bookings.php" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition-colors flex items-center">
            <i class="fas fa-download mr-2"></i> Export
          </a>
        </div>
      </div>

      <!-- Booking Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-6">
        <div class="bg-white rounded-lg shadow p-4 status-card <?php echo empty($status) ? 'active' : ''; ?>"
          onclick="window.location.href='?'">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">All Bookings</p>
              <p class="text-2xl font-bold"><?php echo $status_counts['all']; ?></p>
            </div>
            <div class="bg-gray-100 p-3 rounded-full">
              <i class="fas fa-calendar text-gray-500"></i>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4 status-card <?php echo $status === 'pending' ? 'active' : ''; ?>"
          onclick="window.location.href='?status=pending'">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">Pending</p>
              <p class="text-2xl font-bold"><?php echo $status_counts['pending']; ?></p>
            </div>
            <div class="bg-yellow-100 p-3 rounded-full">
              <i class="fas fa-hourglass-half text-yellow-600"></i>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4 status-card <?php echo $status === 'confirmed' ? 'active' : ''; ?>"
          onclick="window.location.href='?status=confirmed'">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">Confirmed</p>
              <p class="text-2xl font-bold"><?php echo $status_counts['confirmed']; ?></p>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
              <i class="fas fa-check-circle text-green-600"></i>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4 status-card <?php echo $status === 'cancelled' ? 'active' : ''; ?>"
          onclick="window.location.href='?status=cancelled'">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">Cancelled</p>
              <p class="text-2xl font-bold"><?php echo $status_counts['cancelled']; ?></p>
            </div>
            <div class="bg-red-100 p-3 rounded-full">
              <i class="fas fa-times-circle text-red-600"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Filter and Search -->
      <div class="mx-6 bg-white rounded-lg shadow mb-6">
        <div class="p-4 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-800">Filter Bookings</h2>
        </div>
        <div class="p-4">
          <form action="" method="GET" class="md:flex space-y-4 md:space-y-0 md:space-x-4">
            <?php if (!empty($status)): ?>
              <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
            <?php endif; ?>

            <div class="flex-1">
              <label class="block text-gray-700 text-sm font-medium mb-2">Search</label>
              <div class="relative">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                  class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="Guest name, email or hotel...">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <i class="fas fa-search text-gray-400"></i>
                </div>
                <?php if (!empty($search)): ?>
                  <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                    <button type="button" onclick="clearSearch()" class="text-gray-400 hover:text-gray-600">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <div class="flex-1">
              <label class="block text-gray-700 text-sm font-medium mb-2">Date Filter</label>
              <select name="date_filter"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                <option value="">All Dates</option>
                <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                <option value="tomorrow" <?php echo $date_filter === 'tomorrow' ? 'selected' : ''; ?>>Tomorrow</option>
                <option value="this_week" <?php echo $date_filter === 'this_week' ? 'selected' : ''; ?>>This Week</option>
                <option value="this_month" <?php echo $date_filter === 'this_month' ? 'selected' : ''; ?>>This Month</option>
              </select>
            </div>

            <div class="flex items-end space-x-2">
              <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700 transition duration-200">
                <i class="fas fa-filter mr-2"></i>Apply
              </button>

              <?php if (!empty($search) || !empty($date_filter)): ?>
                <a href="<?php echo empty($status) ? '?' : "?status=$status"; ?>"
                  class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition duration-200">
                  <i class="fas fa-times mr-2"></i>Clear
                </a>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>

      <!-- Bookings List/Table -->
      <div class="mx-6 mb-6">
        <?php if (isset($_GET['status_updated'])): ?>
          <div class="bg-green-50 text-green-800 p-4 rounded-lg mb-6 flex items-center">
            <i class="fas fa-check-circle mr-2 text-green-600"></i>
            Booking status has been successfully updated.
          </div>
        <?php endif; ?>

        <?php if (empty($bookings)): ?>
          <div class="bg-white rounded-lg shadow p-8 text-center">
            <div class="inline-flex items-center justify-center bg-gray-100 rounded-full p-6 mb-4">
              <i class="fas fa-calendar-times text-gray-400 text-4xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-800 mb-2">No Bookings Found</h3>
            <p class="text-gray-600 mb-4">There are no bookings matching your current filter criteria.</p>
            <a href="?" class="inline-flex items-center text-teal-600 hover:text-teal-800">
              <i class="fas fa-arrow-left mr-2"></i> View All Bookings
            </a>
          </div>
        <?php else: ?>
          <!-- Desktop Table View -->
          <div class="bg-white rounded-lg shadow overflow-hidden hidden md:block">
            <table class="w-full">
              <thead>
                <tr class="bg-gray-50">
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking Details</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hotel & Room</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guest</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <?php foreach ($bookings as $booking): ?>
                  <?php
                  $check_in = new DateTime($booking['check_in_date']);
                  $check_out = new DateTime($booking['check_out_date']);
                  $nights = $check_in->diff($check_out)->days;
                  $total_price = isset($booking['price_per_night']) ? $booking['price_per_night'] * $nights : 0;

                  $statusClass = '';
                  switch ($booking['status']) {
                    case 'pending':
                      $statusClass = 'status-pending';
                      $statusIcon = 'fa-hourglass-half';
                      break;
                    case 'confirmed':
                      $statusClass = 'status-confirmed';
                      $statusIcon = 'fa-check-circle';
                      break;
                    case 'cancelled':
                      $statusClass = 'status-cancelled';
                      $statusIcon = 'fa-times-circle';
                      break;
                    case 'completed':
                      $statusClass = 'status-completed';
                      $statusIcon = 'fa-check-double';
                      break;
                    default:
                      $statusClass = 'bg-gray-100 text-gray-800';
                      $statusIcon = 'fa-question-circle';
                  }
                  ?>
                  <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                      <div class="text-sm font-medium text-gray-900">#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></div>
                      <div class="text-xs text-gray-500">Created: <?php echo date('M j, Y', strtotime($booking['created_at'])); ?></div>
                      <?php if ($total_price > 0): ?>
                        <div class="text-xs font-semibold text-teal-600 mt-1">
                          $<?php echo number_format($total_price, 2); ?> (<?php echo $nights; ?> nights)
                        </div>
                      <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                      <div class="font-medium text-gray-900"><?php echo htmlspecialchars($booking['hotel_name']); ?></div>
                      <div class="text-xs text-gray-500 capitalize"><?php echo htmlspecialchars($booking['location']); ?></div>
                      <div class="text-xs text-gray-500 mt-1">Room <?php echo htmlspecialchars(str_replace('r', '', $booking['room_id'])); ?></div>
                    </td>
                    <td class="px-6 py-4">
                      <div class="font-medium text-gray-900"><?php echo htmlspecialchars($booking['guest_name']); ?></div>
                      <div class="text-xs text-gray-500"><?php echo htmlspecialchars($booking['guest_email']); ?></div>
                      <?php if (!empty($booking['guest_phone'])): ?>
                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($booking['guest_phone']); ?></div>
                      <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                      <div class="flex items-center">
                        <i class="fas fa-sign-in-alt text-green-600 text-xs mr-1"></i>
                        <span><?php echo date('M j, Y', strtotime($booking['check_in_date'])); ?></span>
                      </div>
                      <div class="flex items-center mt-1">
                        <i class="fas fa-sign-out-alt text-red-600 text-xs mr-1"></i>
                        <span><?php echo date('M j, Y', strtotime($booking['check_out_date'])); ?></span>
                      </div>
                      <div class="text-xs text-gray-500 mt-1">
                        <?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?>
                      </div>
                    </td>
                    <td class="px-6 py-4">
                      <span class="status-badge <?php echo $statusClass; ?>">
                        <i class="fas <?php echo $statusIcon; ?> mr-1"></i> <?php echo ucfirst($booking['status']); ?>
                      </span>
                    </td>
                    <td class="px-6 py-4">
                      <div class="flex space-x-3">
                        <a href="hotel-booking-confirmation.php?id=<?php echo $booking['id']; ?>" class="text-teal-600 hover:text-teal-900 tooltip" title="View Details">
                          <i class="fas fa-eye"></i>
                        </a>
                        <button type="button" onclick="showStatusModal(<?php echo $booking['id']; ?>, '<?php echo $booking['status']; ?>')" class="text-blue-600 hover:text-blue-900 tooltip" title="Update Status">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" onclick="printBooking(<?php echo $booking['id']; ?>)" class="text-purple-600 hover:text-purple-900 tooltip" title="Print Receipt">
                          <i class="fas fa-print"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Mobile Card View -->
          <div class="md:hidden space-y-4">
            <?php foreach ($bookings as $booking): ?>
              <?php
              $check_in = new DateTime($booking['check_in_date']);
              $check_out = new DateTime($booking['check_out_date']);
              $nights = $check_in->diff($check_out)->days;
              $total_price = isset($booking['price_per_night']) ? $booking['price_per_night'] * $nights : 0;

              $statusClass = '';
              switch ($booking['status']) {
                case 'pending':
                  $statusClass = 'status-pending';
                  $statusIcon = 'fa-hourglass-half';
                  break;
                case 'confirmed':
                  $statusClass = 'status-confirmed';
                  $statusIcon = 'fa-check-circle';
                  break;
                case 'cancelled':
                  $statusClass = 'status-cancelled';
                  $statusIcon = 'fa-times-circle';
                  break;
                case 'completed':
                  $statusClass = 'status-completed';
                  $statusIcon = 'fa-check-double';
                  break;
                default:
                  $statusClass = 'bg-gray-100 text-gray-800';
                  $statusIcon = 'fa-question-circle';
              }
              ?>
              <div class="bg-white rounded-lg shadow card-hover">
                <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                  <div>
                    <div class="text-sm font-medium text-gray-900">#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></div>
                    <div class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($booking['created_at'])); ?></div>
                  </div>
                  <span class="status-badge <?php echo $statusClass; ?>">
                    <i class="fas <?php echo $statusIcon; ?> mr-1"></i> <?php echo ucfirst($booking['status']); ?>
                  </span>
                </div>
                <div class="p-4">
                  <div class="mb-3">
                    <div class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($booking['hotel_name']); ?></div>
                    <div class="text-sm text-gray-600 capitalize"><?php echo htmlspecialchars($booking['location']); ?></div>
                    <div class="text-sm text-gray-600">Room <?php echo htmlspecialchars(str_replace('r', '', $booking['room_id'])); ?></div>
                  </div>

                  <div class="mb-3">
                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($booking['guest_name']); ?></div>
                    <div class="text-sm text-gray-600"><?php echo htmlspecialchars($booking['guest_email']); ?></div>
                    <?php if (!empty($booking['guest_phone'])): ?>
                      <div class="text-sm text-gray-600"><?php echo htmlspecialchars($booking['guest_phone']); ?></div>
                    <?php endif; ?>
                  </div>

                  <div class="flex justify-between items-center mb-3">
                    <div>
                      <div class="text-sm font-medium">Stay Duration</div>
                      <div class="text-sm text-gray-600">
                        <?php echo date('M j', strtotime($booking['check_in_date'])); ?> -
                        <?php echo date('M j, Y', strtotime($booking['check_out_date'])); ?>
                        <span class="text-xs text-gray-500">(<?php echo $nights; ?> nights)</span>
                      </div>
                    </div>
                    <?php if ($total_price > 0): ?>
                      <div class="text-lg font-semibold text-teal-600">
                        $<?php echo number_format($total_price, 2); ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="px-4 py-3 bg-gray-50 flex justify-between rounded-b-lg">
                  <a href="hotel-booking-confirmation.php?id=<?php echo $booking['id']; ?>" class="text-teal-600 hover:text-teal-900 flex items-center">
                    <i class="fas fa-eye mr-2"></i> View
                  </a>
                  <button type="button" onclick="showStatusModal(<?php echo $booking['id']; ?>, '<?php echo $booking['status']; ?>')" class="text-blue-600 hover:text-blue-900 flex items-center">
                    <i class="fas fa-edit mr-2"></i> Update
                  </button>
                  <button type="button" onclick="printBooking(<?php echo $booking['id']; ?>)" class="text-purple-600 hover:text-purple-900 flex items-center">
                    <i class="fas fa-print mr-2"></i> Print
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Status Update Modal -->
  <div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6 mx-4">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-semibold text-gray-800">Update Booking Status</h3>
        <button type="button" onclick="hideStatusModal()" class="text-gray-500 hover:text-gray-700">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form action="" method="POST" id="statusForm">
        <input type="hidden" name="booking_id" id="statusBookingId">

        <div class="mb-4">
          <div class="grid grid-cols-2 gap-3">
            <div class="bg-white border rounded-lg p-3 status-option" data-status="pending" onclick="selectStatus('pending')">
              <div class="flex items-center">
                <div class="bg-yellow-100 p-2 rounded-full mr-3">
                  <i class="fas fa-hourglass-half text-yellow-600"></i>
                </div>
                <div>
                  <div class="font-medium">Pending</div>
                  <div class="text-xs text-gray-500">Awaiting action</div>
                </div>
              </div>
            </div>

            <div class="bg-white border rounded-lg p-3 status-option" data-status="confirmed" onclick="selectStatus('confirmed')">
              <div class="flex items-center">
                <div class="bg-green-100 p-2 rounded-full mr-3">
                  <i class="fas fa-check-circle text-green-600"></i>
                </div>
                <div>
                  <div class="font-medium">Confirmed</div>
                  <div class="text-xs text-gray-500">Booking approved</div>
                </div>
              </div>
            </div>

            <div class="bg-white border rounded-lg p-3 status-option" data-status="cancelled" onclick="selectStatus('cancelled')">
              <div class="flex items-center">
                <div class="bg-red-100 p-2 rounded-full mr-3">
                  <i class="fas fa-times-circle text-red-600"></i>
                </div>
                <div>
                  <div class="font-medium">Cancelled</div>
                  <div class="text-xs text-gray-500">Booking cancelled</div>
                </div>
              </div>
            </div>

            <div class="bg-white border rounded-lg p-3 status-option" data-status="completed" onclick="selectStatus('completed')">
              <div class="flex items-center">
                <div class="bg-blue-100 p-2 rounded-full mr-3">
                  <i class="fas fa-check-double text-blue-600"></i>
                </div>
                <div>
                  <div class="font-medium">Completed</div>
                  <div class="text-xs text-gray-500">Stay finished</div>
                </div>
              </div>
            </div>
          </div>

          <input type="hidden" name="new_status" id="statusSelect" value="">
        </div>

        <div class="bg-yellow-50 p-4 rounded-lg mb-4 text-sm">
          <div class="flex items-start">
            <i class="fas fa-info-circle mt-1 mr-2 text-yellow-600"></i>
            <div class="text-yellow-800">
              Updating the booking status will notify the guest via email.
            </div>
          </div>
        </div>

        <div class="flex justify-end space-x-3">
          <button type="button" onclick="hideStatusModal()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition duration-200">
            Cancel
          </button>
          <button type="submit" name="update_status" id="updateStatusBtn" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition duration-200" disabled>
            <i class="fas fa-save mr-2"></i>Update Status
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Email Notification Modal -->
  <div id="emailModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg max-w-lg w-full p-6 mx-4">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-semibold text-gray-800">Send Notification to Guest</h3>
        <button type="button" onclick="hideEmailModal()" class="text-gray-500 hover:text-gray-700">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form action="send-notification.php" method="POST">
        <input type="hidden" name="booking_id" id="emailBookingId">

        <div class="mb-4">
          <label for="emailSubject" class="block text-gray-700 font-medium mb-2">Subject</label>
          <input type="text" name="subject" id="emailSubject" value="Update on Your Hotel Booking"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
        </div>

        <div class="mb-4">
          <label for="emailContent" class="block text-gray-700 font-medium mb-2">Message</label>
          <textarea name="content" id="emailContent" rows="5"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"></textarea>
        </div>

        <div class="flex justify-end space-x-3">
          <button type="button" onclick="hideEmailModal()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition duration-200">
            Cancel
          </button>
          <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition duration-200">
            <i class="fas fa-paper-plane mr-2"></i>Send Email
          </button>
        </div>
      </form>
    </div>
  </div>

  <?php include 'includes/js-links.php'; ?>

  <script>
    // Status modal functions
    function showStatusModal(bookingId, currentStatus) {
      document.getElementById('statusBookingId').value = bookingId;
      document.getElementById('statusSelect').value = currentStatus;
      document.getElementById('statusModal').classList.remove('hidden');

      // Highlight the current status
      document.querySelectorAll('.status-option').forEach(option => {
        option.classList.remove('border-teal-500', 'ring-2', 'ring-teal-500');
        if (option.dataset.status === currentStatus) {
          option.classList.add('border-teal-500', 'ring-2', 'ring-teal-500');
          selectStatus(currentStatus);
        }
      });
    }

    function hideStatusModal() {
      document.getElementById('statusModal').classList.add('hidden');
    }

    function selectStatus(status) {
      // Update hidden input
      document.getElementById('statusSelect').value = status;

      // Update UI
      document.querySelectorAll('.status-option').forEach(option => {
        option.classList.remove('border-teal-500', 'ring-2', 'ring-teal-500');
      });

      const selectedOption = document.querySelector(`.status-option[data-status="${status}"]`);
      if (selectedOption) {
        selectedOption.classList.add('border-teal-500', 'ring-2', 'ring-teal-500');
      }

      // Enable submit button
      document.getElementById('updateStatusBtn').disabled = false;
    }

    // Email notification functions
    function showEmailModal(bookingId) {
      document.getElementById('emailBookingId').value = bookingId;
      document.getElementById('emailModal').classList.remove('hidden');
    }

    function hideEmailModal() {
      document.getElementById('emailModal').classList.add('hidden');
    }

    // Print booking function
    function printBooking(bookingId) {
      window.open(`booking-print.php?id=${bookingId}`, '_blank');
    }

    // Clear search
    function clearSearch() {
      const urlParams = new URLSearchParams(window.location.search);
      urlParams.delete('search');

      // Keep other params
      window.location.href = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
    }

    // Mobile menu toggle
    document.getElementById('menu-btn').addEventListener('click', function() {
      document.querySelector('.sidebar').classList.toggle('-translate-x-full');
    });

    // Tooltips
    document.addEventListener('DOMContentLoaded', function() {
      const tooltips = document.querySelectorAll('.tooltip');
      tooltips.forEach(tooltip => {
        const title = tooltip.getAttribute('title');
        tooltip.addEventListener('mouseenter', function() {
          const tooltipEl = document.createElement('div');
          tooltipEl.className = 'absolute bg-gray-800 text-white text-xs rounded px-2 py-1 -mt-8 -ml-2 z-10';
          tooltipEl.innerHTML = title;

          tooltip.setAttribute('data-original-title', title);
          tooltip.removeAttribute('title');
          tooltip.appendChild(tooltipEl);
        });

        tooltip.addEventListener('mouseleave', function() {
          const originalTitle = tooltip.getAttribute('data-original-title');
          const tooltipEl = tooltip.querySelector('div');

          if (tooltipEl) {
            tooltip.removeChild(tooltipEl);
          }

          tooltip.setAttribute('title', originalTitle);
          tooltip.removeAttribute('data-original-title');
        });
      });
    });

    // Show success message if status was updated
    <?php if (isset($_GET['status_updated'])): ?>
      Swal.fire({
        title: 'Success!',
        text: 'Booking status has been updated successfully.',
        icon: 'success',
        confirmButtonColor: '#0D9488',
        timer: 3000,
        timerProgressBar: true
      });
    <?php endif; ?>
  </script>
</body>

</html>