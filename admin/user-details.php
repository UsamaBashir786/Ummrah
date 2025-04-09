<?php
require_once 'connection/connection.php';

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
  header("Location: manage-users.php");
  exit;
}

$user_id = intval($_GET['id']);

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
      $bookings[] = $row;
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
      $bookings[] = $row;
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
      $bookings[] = $row;
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
      $bookings[] = $row;
      $booking_types['hotel'] = true;
    }
  }
  $stmt->close();
} catch (Exception $e) {
  // Silently handle errors
}

// Sort all bookings by date (newest first)
usort($bookings, function ($a, $b) {
  $date_a = strtotime($a['booking_date'] ?? $a['created_at']);
  $date_b = strtotime($b['booking_date'] ?? $b['created_at']);
  return $date_b - $date_a;
});

// Count active bookings
$active_bookings = 0;
foreach ($bookings as $booking) {
  $status = strtolower($booking['status'] ?? $booking['booking_status'] ?? '');
  if (in_array($status, ['pending', 'confirmed', 'upcoming', 'assigned'])) {
    $active_bookings++;
  }
}

// Format dates for display
$created_date = new DateTime($user['created_at']);
$formatted_created_date = $created_date->format('F d, Y h:i A');

$dob = new DateTime($user['date_of_birth']);
$formatted_dob = $dob->format('F d, Y');
$age = $dob->diff(new DateTime())->y;



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

    .info-card {
      transition: all 0.3s ease;
    }

    .info-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .tab-active {
      border-bottom: 2px solid #0891b2;
      color: #0891b2;
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
            <i class="text-teal-600 fa fa-user-circle mr-2"></i> User Details
          </h1>
        </div>
        <div class="flex space-x-3">
          <button onclick="window.print()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-print mr-2"></i> Print
          </button>
          <button onclick="editUser(<?php echo $user_id; ?>)" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-edit mr-2"></i> Edit
          </button>
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
              <img class="h-32 w-32 object-cover rounded-full border-4 border-white shadow-md"
                src="../<?php echo isset($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'user/uploads/default.png'; ?>"
                alt="<?php echo htmlspecialchars($user['full_name']); ?>'s profile" />
            </div>
            <div class="flex-1 text-center md:text-left">
              <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($user['full_name']); ?></h1>

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

              <div class="mt-3 flex flex-wrap gap-2 justify-center md:justify-start">
                <span class="px-3 py-1 bg-white/20 rounded-full text-sm">
                  <i class="fas fa-venus-mars mr-1"></i> <?php echo htmlspecialchars($user['gender']); ?>
                </span>
                <?php if (!empty($user['city'])): ?>
                  <span class="px-3 py-1 bg-white/20 rounded-full text-sm">
                    <i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($user['city']); ?>
                  </span>
                <?php endif; ?>
                <span class="px-3 py-1 bg-white/20 rounded-full text-sm">
                  <i class="fas fa-birthday-cake mr-1"></i> <?php echo $formatted_dob; ?> (<?php echo $age; ?> years)
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- User Information -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
          <!-- Personal Information -->
          <div class="info-card bg-white rounded-xl shadow-md p-5 col-span-1">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
              <i class="fas fa-user-circle text-teal-600 mr-2"></i> Personal Information
            </h2>
            <div class="space-y-4">
              <div>
                <p class="text-sm text-gray-500">Full Name</p>
                <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($user['full_name']); ?></p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Email Address</p>
                <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($user['email']); ?></p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Phone Number</p>
                <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($user['phone_number']); ?></p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Gender</p>
                <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($user['gender']); ?></p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Date of Birth</p>
                <p class="text-gray-800 font-medium"><?php echo $formatted_dob; ?> (<?php echo $age; ?> years)</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Registered On</p>
                <p class="text-gray-800 font-medium"><?php echo $formatted_created_date; ?></p>
              </div>
            </div>
          </div>

          <!-- Location Information -->
          <div class="info-card bg-white rounded-xl shadow-md p-5 col-span-1">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
              <i class="fas fa-map-marker-alt text-teal-600 mr-2"></i> Location Information
            </h2>
            <div class="space-y-4">
              <div>
                <p class="text-sm text-gray-500">City</p>
                <p class="text-gray-800 font-medium"><?php echo !empty($user['city']) ? htmlspecialchars($user['city']) : 'Not specified'; ?></p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Address</p>
                <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($user['address']); ?></p>
              </div>
            </div>
          </div>

          <!-- Account Info -->
          <div class="info-card bg-white rounded-xl shadow-md p-5 col-span-1">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
              <i class="fas fa-shield-alt text-teal-600 mr-2"></i> Account Information
            </h2>
            <div class="space-y-4">
              <div>
                <p class="text-sm text-gray-500">User ID</p>
                <p class="text-gray-800 font-medium">#<?php echo $user['id']; ?></p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Account Status</p>
                <p class="text-gray-800 font-medium">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <span class="h-2 w-2 mr-1 rounded-full bg-green-500"></span>
                    Active
                  </span>
                </p>
              </div>
              <?php if (count($bookings) > 0): ?>
                <div>
                  <p class="text-sm text-gray-500">Booking Statistics</p>
                  <div class="flex gap-4 mt-2">
                    <div class="bg-blue-50 rounded-lg p-3 text-center flex-1">
                      <p class="text-blue-800 text-xl font-bold"><?php echo count($bookings); ?></p>
                      <p class="text-xs text-blue-600">Total Bookings</p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-3 text-center flex-1">
                      <p class="text-green-800 text-xl font-bold"><?php echo $active_bookings; ?></p>
                      <p class="text-xs text-green-600">Active</p>
                    </div>
                  </div>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Booking Types</p>
                  <div class="flex flex-wrap gap-2 mt-2">
                    <?php if (isset($booking_types['package'])): ?>
                      <span class="booking-type type-package">
                        <i class="fas fa-box mr-1"></i> Package
                      </span>
                    <?php endif; ?>
                    <?php if (isset($booking_types['flight'])): ?>
                      <span class="booking-type type-flight">
                        <i class="fas fa-plane mr-1"></i> Flight
                      </span>
                    <?php endif; ?>
                    <?php if (isset($booking_types['hotel'])): ?>
                      <span class="booking-type type-hotel">
                        <i class="fas fa-hotel mr-1"></i> Hotel
                      </span>
                    <?php endif; ?>
                    <?php if (isset($booking_types['transportation'])): ?>
                      <span class="booking-type type-transportation">
                        <i class="fas fa-car mr-1"></i> Transport
                      </span>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Bookings Section -->
        <?php if (count($bookings) > 0): ?>
          <div class="bg-white rounded-xl shadow-md p-5 mb-6">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-calendar-check text-teal-600 mr-2"></i> User Bookings
              </h2>
              <?php if (count($bookings) > 3): ?>
                <a href="user-bookings.php?user_id=<?php echo $user_id; ?>" class="text-teal-600 hover:text-teal-800 flex items-center text-sm">
                  View All <i class="fas fa-arrow-right ml-1"></i>
                </a>
              <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <?php foreach (array_slice($bookings, 0, 3) as $booking): ?>
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
                        <span class="text-gray-500">Booking ID:</span>
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

            <?php if (count($bookings) > 3): ?>
              <div class="mt-4 text-center">
                <a href="user-bookings.php?user_id=<?php echo $user_id; ?>"
                  class="inline-block px-5 py-2 text-sm font-medium text-teal-600 border border-teal-300 rounded-lg hover:bg-teal-50">
                  View All <?php echo count($bookings); ?> Bookings
                </a>
              </div>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="bg-white rounded-xl shadow-md p-5 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
              <i class="fas fa-calendar-check text-teal-600 mr-2"></i> User Bookings
            </h2>
            <div class="bg-gray-50 rounded-lg p-8 text-center">
              <div class="text-gray-400 mb-4">
                <i class="fas fa-calendar-times text-5xl"></i>
              </div>
              <h3 class="text-gray-600 font-medium mb-2">No Bookings Found</h3>
              <p class="text-gray-500 text-sm">This user hasn't made any bookings yet.</p>
            </div>
          </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="mt-6 bg-white rounded-xl shadow-md p-5 no-print">
          <h2 class="text-xl font-semibold text-gray-800 mb-4">Actions</h2>
          <div class="flex flex-wrap gap-3">
            <button onclick="editUser(<?php echo $user_id; ?>)" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center">
              <i class="fas fa-edit mr-2"></i> Edit User
            </button>
            <!-- <button onclick="resetPassword(<?php echo $user_id; ?>)" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg flex items-center">
              <i class="fas fa-key mr-2"></i> Reset Password
            </button> -->
            <button onclick="deleteUser(<?php echo $user_id; ?>)" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center">
              <i class="fas fa-trash-alt mr-2"></i> Delete User
            </button>
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

    function editUser(userId) {
      if (userId > 0) {
        window.location.href = `edit-user.php?id=${userId}`;
      } else {
        Swal.fire({
          title: 'Error',
          text: 'Invalid user ID',
          icon: 'error'
        });
      }
    }

    function resetPassword(userId) {
      if (userId <= 0) {
        Swal.fire({
          title: 'Error',
          text: 'Invalid user ID',
          icon: 'error'
        });
        return;
      }

      Swal.fire({
        title: 'Reset Password',
        text: "This will generate a new password for the user. Proceed?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, reset it!'
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading state
          Swal.fire({
            title: 'Processing...',
            text: 'Generating new password',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          fetch(`reset-password.php?id=${userId}`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              }
            })
            .then(response => {
              if (!response.ok) {
                throw new Error('Network response was not ok');
              }
              return response.json();
            })
            .then(data => {
              if (data.success) {
                Swal.fire({
                  title: 'Success!',
                  html: `Password has been reset.<br>New password: <strong>${data.new_password}</strong>`,
                  icon: 'success',
                  confirmButtonColor: '#3085d6'
                });
              } else {
                throw new Error(data.message || 'Failed to reset password');
              }
            })
            .catch(error => {
              console.error('Error:', error);
              Swal.fire({
                title: 'Error!',
                text: error.message || 'An error occurred while resetting the password',
                icon: 'error',
                confirmButtonColor: '#3085d6'
              });
            });
        }
      });
    }

    function deleteUser(userId) {
      if (userId <= 0) {
        Swal.fire({
          title: 'Error',
          text: 'Invalid user ID',
          icon: 'error'
        });
        return;
      }

      Swal.fire({
        title: 'Are you sure?',
        text: "This will delete the user and all their associated data. This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading state
          Swal.fire({
            title: 'Deleting...',
            text: 'Please wait while we delete the user and associated data',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          fetch(`delete-user.php?id=${userId}`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              }
            })
            .then(response => {
              if (!response.ok) {
                throw new Error('Network response was not ok');
              }
              return response.json();
            })
            .then(data => {
              if (data.success) {
                Swal.fire({
                  title: 'Deleted!',
                  text: 'User and all associated data have been deleted.',
                  icon: 'success',
                  showConfirmButton: false,
                  timer: 1500
                }).then(() => {
                  window.location.href = 'manage-users.php';
                });
              } else {
                throw new Error(data.message || 'Failed to delete user');
              }
            })
            .catch(error => {
              console.error('Error:', error);
              Swal.fire({
                title: 'Error!',
                text: error.message || 'An error occurred while deleting the user',
                icon: 'error',
                confirmButtonColor: '#3085d6'
              });
            });
        }
      });
    }
  </script>
</body>

</html>