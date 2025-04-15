<?php
require_once 'connection/connection.php';

// Check if user ID is provided
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
  header("Location: manage-users.php");
  exit;
}

$user_id = intval($_GET['user_id']);

// Fetch user details
$sql = "SELECT * FROM users WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  header("Location: manage-users.php");
  exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Get bookings for this user from various booking tables
$bookings = [];
$booking_types = [];

// 1. Check for package bookings
$package_sql = "SELECT pb.*, p.title as package_name, p.package_type, p.price
                FROM package_booking pb 
                JOIN packages p ON pb.package_id = p.id 
                WHERE pb.user_id = ? 
                ORDER BY pb.booking_date DESC";

try {
  $stmt = $conn->prepare($package_sql);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $row['booking_type'] = 'package';
      if (empty($booking_type_filter) || $booking_type_filter === 'package') {
        // Apply status filter if set
        if (empty($status_filter) || strtolower($row['status']) === strtolower($status_filter)) {
          $bookings[] = $row;
        }
      }
      $booking_types['package'] = true;
    }
  }
  $stmt->close();
} catch (Exception $e) {
  // Silently handle errors
}

// 2. Check for flight bookings
$flight_sql = "SELECT fb.*, f.airline_name, f.flight_number, f.departure_city, f.arrival_city
               FROM flight_bookings fb 
               JOIN flights f ON fb.flight_id = f.id 
               WHERE fb.user_id = ? 
               ORDER BY fb.booking_date DESC";

try {
  $stmt = $conn->prepare($flight_sql);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $row['booking_type'] = 'flight';
      if (empty($booking_type_filter) || $booking_type_filter === 'flight') {
        // Apply status filter if set
        if (empty($status_filter) || strtolower($row['booking_status']) === strtolower($status_filter)) {
          $bookings[] = $row;
        }
      }
      $booking_types['flight'] = true;
    }
  }
  $stmt->close();
} catch (Exception $e) {
  // Silently handle errors
}

// 3. Check for transportation bookings
$transport_sql = "SELECT * FROM transportation_bookings 
                 WHERE user_id = ? 
                 ORDER BY booking_date DESC";

try {
  $stmt = $conn->prepare($transport_sql);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $row['booking_type'] = 'transportation';
      if (empty($booking_type_filter) || $booking_type_filter === 'transportation') {
        // Apply status filter if set
        if (empty($status_filter) || strtolower($row['booking_status']) === strtolower($status_filter)) {
          $bookings[] = $row;
        }
      }
      $booking_types['transportation'] = true;
    }
  }
  $stmt->close();
} catch (Exception $e) {
  // Silently handle errors
}

// 4. Check for hotel bookings
$hotel_sql = "SELECT hb.*, h.hotel_name
             FROM hotel_bookings hb 
             JOIN hotels h ON hb.hotel_id = h.id
             WHERE hb.user_id = ? 
             ORDER BY hb.created_at DESC";

try {
  $stmt = $conn->prepare($hotel_sql);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $row['booking_type'] = 'hotel';
      if (empty($booking_type_filter) || $booking_type_filter === 'hotel') {
        // Apply status filter if set
        if (empty($status_filter) || strtolower($row['status']) === strtolower($status_filter)) {
          $bookings[] = $row;
        }
      }
      $booking_types['hotel'] = true;
    }
  }
  $stmt->close();
} catch (Exception $e) {
  // Silently handle errors
}

// Filter bookings by type if specified
$booking_type_filter = '';
if (isset($_GET['type']) && !empty($_GET['type'])) {
  $booking_type_filter = $_GET['type'];
}

// Status filter
$status_filter = '';
if (isset($_GET['status']) && !empty($_GET['status'])) {
  $status_filter = $_GET['status'];
}

// Order by date (default to newest first)
$order_by = 'newest';
if (isset($_GET['order']) && $_GET['order'] === 'oldest') {
  $order_by = 'oldest';
}

// Sort all bookings by date (newest first or oldest first)
usort($bookings, function ($a, $b) use ($order_by) {
  $date_a = strtotime($a['booking_date'] ?? $a['created_at']);
  $date_b = strtotime($b['booking_date'] ?? $b['created_at']);

  if ($order_by === 'oldest') {
    return $date_a - $date_b; // Oldest first
  } else {
    return $date_b - $date_a; // Newest first (default)
  }
});

// Count active bookings
$active_bookings = 0;
foreach ($bookings as $booking) {
  $status = strtolower($booking['status'] ?? $booking['booking_status'] ?? '');
  if (in_array($status, ['pending', 'confirmed', 'upcoming', 'assigned'])) {
    $active_bookings++;
  }
}

// Format date for display
$created_date = new DateTime($user['created_at']);
$formatted_created_date = $created_date->format('F d, Y h:i A');

// Helper function to get available seats
function getAvailableSeats($flightId, $conn)
{
  $sql = "SELECT seats FROM flights WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $flightId);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $stmt->close();

  if ($row && $row['seats']) {
    $seats = json_decode($row['seats'], true);
    $total = 0;
    $booked = 0;

    // Count total and booked seats from flight_bookings
    foreach ($seats as $class => $data) {
      $total += $data['count'];
      $sql = "SELECT COUNT(*) as booked FROM flight_bookings WHERE flight_id = ? AND cabin_class = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("is", $flightId, $class);
      $stmt->execute();
      $result = $stmt->get_result();
      $booked += $result->fetch_assoc()['booked'];
      $stmt->close();
    }

    return ['available' => $total - $booked, 'total' => $total];
  }
  return ['available' => 0, 'total' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <style>
    .profile-header {
      background-image: linear-gradient(to right, #0891b2, #0e7490);
    }

    .booking-card {
      transition: all 0.3s ease;
    }

    .booking-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
    }

    .booking-status {
      font-size: 0.75rem;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-weight: 600;
    }

    .status-confirmed,
    .status-completed {
      background-color: #ecfdf5;
      color: #059669;
    }

    .status-pending,
    .status-assigned,
    .status-upcoming {
      background-color: #fffbeb;
      color: #d97706;
    }

    .status-cancelled {
      background-color: #fef2f2;
      color: #dc2626;
    }

    .booking-type {
      font-size: 0.65rem;
      padding: 0.15rem 0.5rem;
      border-radius: 9999px;
      font-weight: 600;
      text-transform: uppercase;
    }

    .type-package {
      background-color: #e0f2fe;
      color: #0284c7;
    }

    .type-flight {
      background-color: #f0f9ff;
      color: #0369a1;
    }

    .type-hotel {
      background-color: #f0fdfa;
      color: #0f766e;
    }

    .type-transportation {
      background-color: #fef9c3;
      color: #854d0e;
    }

    .filter-badge {
      font-size: 0.7rem;
      padding: 0.2rem 0.6rem;
      border-radius: 9999px;
      margin-right: 0.5rem;
      background-color: #e0f2fe;
      color: #0369a1;
      cursor: pointer;
    }

    .filter-badge.active {
      background-color: #0369a1;
      color: white;
    }

    @media print {
      .no-print {
        display: none !important;
      }

      .print-only {
        display: block !important;
      }

      body {
        font-size: 12pt;
        color: #000;
      }
    }
  </style>
</head>

<body class="bg-gray-100">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main flex-1 flex flex-col overflow-hidden">
      <!-- Navbar -->
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center no-print">
        <div class="flex items-center">
          <button class="md:hidden text-gray-800 mr-4" id="menu-btn">
            <i class="fas fa-bars"></i>
          </button>
          <h1 class="text-xl font-semibold">
            <i class="text-teal-600 fa fa-calendar-check mr-2"></i> User Bookings
          </h1>
        </div>
        <div class="flex space-x-3">
          <button onclick="window.print()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-print mr-2"></i> Print
          </button>
          <a href="user-details.php?id=<?php echo $user_id; ?>" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-user mr-2"></i> User Details
          </a>
          <button onclick="window.history.back()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back
          </button>
        </div>
      </div>

      <div class="flex-1 overflow-auto p-4 md:p-6">
        <!-- User Profile Header -->
        <div class="profile-header bg-gradient-to-r from-cyan-600 to-teal-700 rounded-xl shadow-lg p-6 mb-6 text-white">
          <div class="flex flex-col md:flex-row items-center md:items-start">
            <div class="flex-shrink-0 mb-4 md:mb-0 md:mr-6">
              <img class="h-20 w-20 object-cover rounded-full border-4 border-white shadow-md"
                src="../<?php echo isset($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'user/uploads/default.png'; ?>"
                alt="<?php echo htmlspecialchars($user['full_name']); ?>'s profile" />
            </div>
            <div class="flex-1 text-center md:text-left">
              <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($user['full_name']); ?></h1>
              <div class="mt-2 flex flex-col md:flex-row md:items-center space-y-2 md:space-y-0 md:space-x-4">
                <div class="flex items-center justify-center md:justify-start">
                  <i class="fas fa-envelope mr-2"></i>
                  <span><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="flex items-center justify-center md:justify-start">
                  <i class="fas fa-phone mr-2"></i>
                  <span><?php echo htmlspecialchars($user['phone_number']); ?></span>
                </div>
              </div>
              <div class="mt-3">
                <span class="px-3 py-1 bg-white/20 rounded-full text-sm">
                  User ID: #<?php echo $user['id']; ?>
                </span>
                <span class="px-3 py-1 bg-white/20 rounded-full text-sm ml-2">
                  <i class="fas fa-calendar-alt mr-1"></i> Member since <?php echo date('M Y', strtotime($user['created_at'])); ?>
                </span>
              </div>
            </div>
            <div class="hidden md:flex flex-col items-center justify-center text-center ml-auto">
              <div class="bg-white/10 rounded-xl p-3 min-w-[120px]">
                <p class="text-3xl font-bold"><?php echo count($bookings); ?></p>
                <p class="text-sm text-white/80">Total Bookings</p>
              </div>
              <div class="bg-white/10 rounded-xl p-3 mt-3 min-w-[120px]">
                <p class="text-3xl font-bold"><?php echo $active_bookings; ?></p>
                <p class="text-sm text-white/80">Active Bookings</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Filters Section -->
        <div class="bg-white rounded-xl shadow-md p-5 mb-6 no-print">
          <h2 class="text-lg font-semibold text-gray-800 mb-4">Filter Bookings</h2>

          <form action="" method="GET" class="space-y-4">
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <!-- Booking Type Filter -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Booking Type</label>
                <select name="type" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
                  <option value="">All Types</option>
                  <?php if (isset($booking_types['package'])): ?>
                    <option value="package" <?php echo $booking_type_filter === 'package' ? 'selected' : ''; ?>>Package</option>
                  <?php endif; ?>
                  <?php if (isset($booking_types['flight'])): ?>
                    <option value="flight" <?php echo $booking_type_filter === 'flight' ? 'selected' : ''; ?>>Flight</option>
                  <?php endif; ?>
                  <?php if (isset($booking_types['hotel'])): ?>
                    <option value="hotel" <?php echo $booking_type_filter === 'hotel' ? 'selected' : ''; ?>>Hotel</option>
                  <?php endif; ?>
                  <?php if (isset($booking_types['transportation'])): ?>
                    <option value="transportation" <?php echo $booking_type_filter === 'transportation' ? 'selected' : ''; ?>>Transportation</option>
                  <?php endif; ?>
                </select>
              </div>

              <!-- Status Filter -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
                  <option value="">All Statuses</option>
                  <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                  <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                  <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                  <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
              </div>

              <!-- Sort Order -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                <select name="order" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
                  <option value="newest" <?php echo $order_by === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                  <option value="oldest" <?php echo $order_by === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                </select>
              </div>
            </div>

            <div class="flex justify-between">
              <div>
                <?php if (!empty($booking_type_filter) || !empty($status_filter) || $order_by !== 'newest'): ?>
                  <a href="user-bookings.php?user_id=<?php echo $user_id; ?>" class="text-teal-600 hover:text-teal-800 text-sm">
                    <i class="fas fa-times-circle mr-1"></i> Clear Filters
                  </a>
                <?php endif; ?>
              </div>
              <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-filter mr-2"></i> Apply Filters
              </button>
            </div>
          </form>
        </div>

        <!-- Bookings Results -->
        <div class="bg-white rounded-xl shadow-md p-5 mb-6">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-800 flex items-center">
              <i class="fas fa-calendar-check text-teal-600 mr-2"></i>
              <?php if (count($bookings) === 0): ?>
                No Bookings Found
              <?php else: ?>
                <?php echo count($bookings); ?> Booking<?php echo count($bookings) !== 1 ? 's' : ''; ?> Found
              <?php endif; ?>
            </h2>

            <!-- Applied filters badges -->
            <div class="flex flex-wrap items-center no-print">
              <?php if (!empty($booking_type_filter)): ?>
                <span class="filter-badge active">
                  <i class="fas fa-tag mr-1"></i> <?php echo ucfirst($booking_type_filter); ?>
                </span>
              <?php endif; ?>

              <?php if (!empty($status_filter)): ?>
                <span class="filter-badge active">
                  <i class="fas fa-check-circle mr-1"></i> <?php echo ucfirst($status_filter); ?>
                </span>
              <?php endif; ?>

              <?php if ($order_by !== 'newest'): ?>
                <span class="filter-badge active">
                  <i class="fas fa-sort-amount-down mr-1"></i> Oldest First
                </span>
              <?php endif; ?>
            </div>
          </div>

          <?php if (count($bookings) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <?php foreach ($bookings as $booking): ?>
                <div class="booking-card bg-gray-50 rounded-xl overflow-hidden shadow-sm border border-gray-100">
                  <?php
                  $booking_type = $booking['booking_type'] ?? 'unknown';
                  $icon_class = 'fa-calendar-alt';
                  $title = 'Booking';
                  $price = $booking['price'] ?? 0;
                  $subtitle = '';

                  switch ($booking_type) {
                    case 'package':
                      $icon_class = 'fa-box';
                      $title = $booking['package_name'] ?? 'Package Booking';
                      $subtitle = $booking['package_type'] ?? '';
                      $price = $booking['total_price'] ?? 0;
                      break;
                    case 'flight':
                      $icon_class = 'fa-plane';
                      $title = $booking['airline_name'] ?? 'Flight Booking';
                      $subtitle = $booking['flight_number'] ?? '';
                      // Fetch price from flights table if not set
                      if ($price == 0 && isset($booking['flight_id'])) {
                        $sql = "SELECT prices FROM flights WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $booking['flight_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($row = $result->fetch_assoc()) {
                          $prices = json_decode($row['prices'], true);
                          $price = $prices[$booking['cabin_class']] ?? 0;
                        }
                        $stmt->close();
                      }
                      break;
                    case 'hotel':
                      $icon_class = 'fa-hotel';
                      $title = $booking['hotel_name'] ?? 'Hotel Booking';
                      $subtitle = 'Room ' . ($booking['room_id'] ?? '');
                      break;
                    case 'transportation':
                      $icon_class = 'fa-car';
                      $title = $booking['service_type'] === 'taxi' ? 'Taxi Booking' : 'Rent A Car';
                      $subtitle = $booking['vehicle_name'] ?? $booking['vehicle_type'] ?? '';
                      break;
                  }

                  $status = strtolower($booking['status'] ?? $booking['booking_status'] ?? 'pending');
                  $status_class = 'status-pending';
                  switch ($status) {
                    case 'confirmed':
                    case 'completed':
                      $status_class = 'status-confirmed';
                      break;
                    case 'cancelled':
                    case 'canceled':
                      $status_class = 'status-cancelled';
                      break;
                  }

                  $date = $booking['booking_date'] ?? $booking['created_at'] ?? '';
                  $booking_date = new DateTime($date);
                  $formatted_booking_date = $booking_date->format('M d, Y');

                  $booking_ref = $booking['booking_reference'] ?? $booking['id'] ?? 'N/A';
                  ?>

                  <div class="h-12 bg-gray-800 text-white p-3 flex justify-between items-center">
                    <div class="flex items-center">
                      <i class="fas <?php echo $icon_class; ?> mr-2"></i>
                      <span class="font-medium truncate"><?php echo htmlspecialchars($title); ?></span>
                    </div>
                    <span class="booking-type type-<?php echo $booking_type; ?>">
                      <?php echo ucfirst($booking_type); ?>
                    </span>
                  </div>

                  <div class="p-4">
                    <?php if (!empty($subtitle)): ?>
                      <p class="text-sm text-gray-600"><?php echo htmlspecialchars($subtitle); ?></p>
                    <?php endif; ?>

                    <div class="mt-3 space-y-2">
                      <div class="flex justify-between text-sm">
                        <span class="text-gray-800 font-medium"><?php echo htmlspecialchars($booking_ref); ?></span>
                      </div>
                      <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Booking Date:</span>
                        <span class="text-gray-800 font-medium"><?php echo $formatted_booking_date; ?></span>
                      </div>
                      <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Status:</span>
                        <span class="booking-status <?php echo $status_class; ?>">
                          <?php echo ucfirst($status); ?>
                        </span>
                      </div>
                      <?php if ($price > 0): ?>
                        <div class="flex justify-between text-sm">
                          <span class="text-gray-500">Price:</span>
                          <span class="text-gray-800 font-medium">$<?php echo number_format((float)$price, 2); ?></span>
                        </div>
                      <?php endif; ?>
                      <?php if ($booking_type === 'flight' && isset($booking['flight_id'])):
                        $seatInfo = getAvailableSeats($booking['flight_id'], $conn);
                        if ($seatInfo['total'] > 0):
                      ?>
                          <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Seats Available:</span>
                            <span class="text-gray-800 font-medium">
                              <?php echo $seatInfo['available'] . '/' . $seatInfo['total']; ?>
                            </span>
                          </div>
                      <?php endif;
                      endif; ?>
                      <?php if (isset($booking['payment_status'])): ?>
                        <div class="flex justify-between text-sm">
                          <span class="text-gray-500">Payment:</span>
                          <span class="text-gray-800 font-medium">
                            <?php echo ucfirst($booking['payment_status']); ?>
                          </span>
                        </div>
                      <?php endif; ?>
                    </div>

                    <div class="mt-4 pt-3 border-t border-gray-100 flex justify-end">
                      <?php
                      $details_link = "#";
                      switch ($booking_type) {
                        case 'package':
                          $details_link = 'package-booking-details.php?id=' . $booking['id'];
                          break;
                        case 'flight':
                          $details_link = 'flight-booking-details.php?id=' . $booking['id'];
                          break;
                        case 'hotel':
                          $details_link = 'hotel-booking-details.php?id=' . $booking['id'];
                          break;
                        case 'transportation':
                          $details_link = 'transportation-booking-details.php?id=' . $booking['id'];
                          break;
                      }
                      ?>
                      <a href="<?php echo $details_link; ?>" class="text-teal-600 hover:text-teal-800 text-sm">
                        View Details <i class="fas fa-chevron-right ml-1 text-xs"></i>
                      </a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Pagination could be added here in the future if needed -->

          <?php else: ?>
            <div class="bg-gray-50 rounded-lg p-8 text-center">
              <div class="text-gray-400 mb-4">
                <i class="fas fa-calendar-times text-5xl"></i>
              </div>
              <h3 class="text-gray-600 font-medium mb-2">No Bookings Found</h3>
              <p class="text-gray-500 text-sm">
                <?php if (!empty($booking_type_filter) || !empty($status_filter)): ?>
                  No bookings match your current filter criteria. Try adjusting your filters or <a href="user-bookings.php?user_id=<?php echo $user_id; ?>" class="text-teal-600 hover:underline">view all bookings</a>.
                <?php else: ?>
                  This user hasn't made any bookings yet.
                <?php endif; ?>
              </p>
            </div>
          <?php endif; ?>
        </div>

        <!-- Export Options (could be implemented in the future) -->
        <div class="bg-white rounded-xl shadow-md p-5 mb-6 no-print">
          <h2 class="text-lg font-semibold text-gray-800 mb-4">Booking Actions</h2>
          <div class="flex flex-wrap gap-3">
            <button onclick="window.print()" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg flex items-center">
              <i class="fas fa-print mr-2"></i> Print Bookings List
            </button>
            <!-- Additional actions could be added here -->
          </div>
        </div>

      </div>
    </div>
  </div>

  <?php include 'includes/js-links.php'; ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Mobile menu toggle
      const menuBtn = document.getElementById('menu-btn');
      const sidebar = document.querySelector('.sidebar');

      if (menuBtn && sidebar) {
        menuBtn.addEventListener('click', function() {
          sidebar.classList.toggle('hidden');
        });
      }
    });
  </script>
</body>

</html>