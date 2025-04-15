<?php

/************************************************
 * PHP CODE SECTION - TOP
 ************************************************/
session_name("admin_session");
session_start();
include '../connection/connection.php';

// Verify admin is logged in
if (!isset($_SESSION['admin_email'])) {
  header("Location: login.php");
  exit();
}

// Initialize variables for filtering
$filter_airline = isset($_GET['airline']) ? $_GET['airline'] : '';
$filter_departure = isset($_GET['departure']) ? $_GET['departure'] : '';
$filter_arrival = isset($_GET['arrival']) ? $_GET['arrival'] : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Handle flight deletion requests
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
  $delete_id = intval($_GET['delete_id']);
  $force_delete = isset($_GET['force_delete']) && $_GET['force_delete'] == 1;

  try {
    // First check if there are any bookings for this flight
    $check_sql = "SELECT COUNT(*) as booking_count FROM flight_bookings WHERE flight_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $delete_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    $booking_count = $row['booking_count'];
    $check_stmt->close();

    if ($booking_count > 0 && !$force_delete) {
      // Flight has bookings, but force delete wasn't requested
      $error_message = "This flight has {$booking_count} booking(s). To delete this flight and all its bookings, click the 'Force Delete' button.";
      $show_force_delete = true;
      $force_delete_id = $delete_id;
    } else {
      // Begin transaction to ensure data consistency
      $conn->begin_transaction();

      // If force delete requested, first delete all associated bookings
      if ($booking_count > 0 && $force_delete) {
        $delete_bookings_sql = "DELETE FROM flight_bookings WHERE flight_id = ?";
        $delete_bookings_stmt = $conn->prepare($delete_bookings_sql);
        $delete_bookings_stmt->bind_param("i", $delete_id);

        if (!$delete_bookings_stmt->execute()) {
          throw new Exception("Failed to delete the associated bookings: " . $delete_bookings_stmt->error);
        }

        $deleted_bookings_count = $delete_bookings_stmt->affected_rows;
        $delete_bookings_stmt->close();
      }

      // Now delete the flight
      $delete_flight_sql = "DELETE FROM flights WHERE id = ?";
      $delete_flight_stmt = $conn->prepare($delete_flight_sql);
      $delete_flight_stmt->bind_param("i", $delete_id);

      if (!$delete_flight_stmt->execute()) {
        throw new Exception("Failed to delete the flight: " . $delete_flight_stmt->error);
      }

      $delete_flight_stmt->close();

      // Commit the transaction
      $conn->commit();

      if (isset($deleted_bookings_count) && $deleted_bookings_count > 0) {
        $success_message = "Flight deleted successfully along with {$deleted_bookings_count} booking(s).";
      } else {
        $success_message = "Flight deleted successfully.";
      }
    }
  } catch (Exception $e) {
    // Roll back the transaction on error
    $conn->rollback();
    $error_message = "Error: " . $e->getMessage();
  }
}

// Increase GROUP_CONCAT max length to handle many bookings per flight
try {
  $conn->query("SET SESSION group_concat_max_len = 1000000");
} catch (Exception $e) {
  // If this fails, we'll still work with the default length
}

$sql = "SELECT f.*, COUNT(fb.id) as booking_count,
        GROUP_CONCAT(DISTINCT CONCAT(fb.user_id, ':', fb.passenger_name, ':', fb.passenger_email) SEPARATOR '||') as booked_users 
        FROM flights f 
        LEFT JOIN flight_bookings fb ON f.id = fb.flight_id 
        WHERE 1=1 ";

$params = [];
$param_types = "";

if (!empty($filter_airline)) {
  $sql .= "AND f.airline_name = ? ";
  $params[] = $filter_airline;
  $param_types .= "s";
}

if (!empty($filter_departure)) {
  $sql .= "AND f.departure_city = ? ";
  $params[] = $filter_departure;
  $param_types .= "s";
}

if (!empty($filter_arrival)) {
  $sql .= "AND f.arrival_city = ? ";
  $params[] = $filter_arrival;
  $param_types .= "s";
}

if (!empty($filter_date_from)) {
  $sql .= "AND f.departure_date >= ? ";
  $params[] = $filter_date_from;
  $param_types .= "s";
}

if (!empty($filter_date_to)) {
  $sql .= "AND f.departure_date <= ? ";
  $params[] = $filter_date_to;
  $param_types .= "s";
}

$sql .= "GROUP BY f.id ORDER BY f.departure_date DESC";

// Fetch all flights with the applied filters
$flights = [];
$total_flights = 0;
$total_economy_seats = 0;
$total_business_seats = 0;
$total_first_class_seats = 0;
$total_economy_price = 0;
$total_business_price = 0;
$total_first_class_price = 0;
$total_seat_value = 0;

try {
  $stmt = $conn->prepare($sql);

  if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
  }

  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $flights[] = $row;

      // Parse JSON data for totals calculation
      $prices = json_decode($row['prices'], true);
      $seats = json_decode($row['seats'], true);

      // Calculate totals
      $total_flights++;

      // Check which format is being used for seats
      if (isset($seats['economy']) && is_array($seats['economy']) && isset($seats['economy']['count'])) {
        // New format with seat_ids
        $economy_seats = $seats['economy']['count'];
        $business_seats = $seats['business']['count'];
        $first_class_seats = $seats['first_class']['count'];
      } else {
        // Old format (direct values)
        $economy_seats = $seats['economy'];
        $business_seats = $seats['business'];
        $first_class_seats = $seats['first_class'];
      }

      $total_economy_seats += $economy_seats;
      $total_business_seats += $business_seats;
      $total_first_class_seats += $first_class_seats;

      // Calculate the total value of all available seats
      $total_economy_price += $economy_seats * $prices['economy'];
      $total_business_price += $business_seats * $prices['business'];
      $total_first_class_price += $first_class_seats * $prices['first_class'];
    }
  }

  // Calculate the total seat value
  $total_seat_value = $total_economy_price + $total_business_price + $total_first_class_price;

  $stmt->close();
} catch (Exception $e) {
  $error_message = "Database error: " . $e->getMessage();
}

// Get unique values for the filter dropdowns
function getUniqueValues($conn, $table, $column)
{
  $values = [];
  $sql = "SELECT DISTINCT $column FROM $table WHERE $column IS NOT NULL AND $column != '' ORDER BY $column";
  $result = $conn->query($sql);

  if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $values[] = $row[$column];
    }
  }

  return $values;
}

$airlines = getUniqueValues($conn, 'flights', 'airline_name');
$departure_cities = getUniqueValues($conn, 'flights', 'departure_city');
$arrival_cities = getUniqueValues($conn, 'flights', 'arrival_city');

$passenger_stats_sql = "SELECT 
    COUNT(DISTINCT user_id) as total_passengers,
    COUNT(id) as total_bookings,
    (SELECT COUNT(DISTINCT user_id) FROM flight_bookings) as registered_users,
    ROUND(AVG(CASE WHEN id IS NOT NULL THEN 1 ELSE 0 END), 2) as booking_ratio
    FROM flight_bookings";

try {
  $passenger_stats_result = $conn->query($passenger_stats_sql);
  $passenger_stats = $passenger_stats_result->fetch_assoc();
} catch (Exception $e) {
  $error_message = "Database error: " . $e->getMessage();
  $passenger_stats = [
    'total_passengers' => 0,
    'total_bookings' => 0,
    'registered_users' => 0,
    'booking_ratio' => 0
  ];
}

$top_passengers_sql = "SELECT 
    fb.user_id as id, 
    fb.passenger_name as full_name, 
    fb.passenger_email as email, 
    COUNT(fb.id) as booking_count,
    MAX(fb.booking_date) as last_booking
    FROM flight_bookings fb
    GROUP BY fb.user_id, fb.passenger_name, fb.passenger_email
    ORDER BY booking_count DESC
    LIMIT 5";

try {
  $top_passengers_result = $conn->query($top_passengers_sql);
  $top_passengers = [];

  if ($top_passengers_result && $top_passengers_result->num_rows > 0) {
    while ($row = $top_passengers_result->fetch_assoc()) {
      $top_passengers[] = $row;
    }
  }
} catch (Exception $e) {
  $error_message = "Database error: " . $e->getMessage();
  $top_passengers = [];
}
$recent_bookings_sql = "SELECT 
    fb.id as booking_id,
    fb.booking_date,
    fb.booking_status,
    fb.cabin_class as seat_class,
    fb.price as booking_price,
    fb.passenger_name as passenger_name,
    fb.user_id as passenger_id,
    f.flight_number,
    f.departure_city,
    f.arrival_city,
    f.departure_date,
    f.id as flight_id
    FROM flight_bookings fb
    JOIN flights f ON fb.flight_id = f.id
    ORDER BY fb.booking_date DESC
    LIMIT 10";

try {
  $recent_bookings_result = $conn->query($recent_bookings_sql);
  $recent_bookings = [];

  if ($recent_bookings_result && $recent_bookings_result->num_rows > 0) {
    while ($row = $recent_bookings_result->fetch_assoc()) {
      $recent_bookings[] = $row;
    }
  }
} catch (Exception $e) {
  $error_message = "Database error: " . $e->getMessage();
  $recent_bookings = [];
}

// Helper function to format dates consistently
function formatDate($date_string, $format = 'M d, Y')
{
  $date = new DateTime($date_string);
  return $date->format($format);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <style>
    .flight-row:hover {
      background-color: #f0f9ff;
    }

    .has-bookings {
      background-color: #fffbeb;
    }

    .route-separator {
      height: 1px;
      background: linear-gradient(to right, transparent, #cbd5e0, transparent);
      margin: 12px 0;
      width: 100%;
    }

    .filter-form {
      background-color: #f8fafc;
      border-radius: 0.5rem;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .filter-form .form-group {
      margin-bottom: 0;
    }

    .summary-card {
      background-color: #fff;
      border-radius: 0.5rem;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      transition: transform 0.2s ease;
    }

    .summary-card:hover {
      transform: translateY(-2px);
    }

    .summary-icon {
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
    }

    /* NEW: Added styles for passenger badges */
    .passenger-badge {
      display: inline-flex;
      align-items: center;
      margin-right: 8px;
      margin-bottom: 8px;
      transition: all 0.2s;
    }

    .passenger-badge:hover {
      transform: translateY(-2px);
    }

    .passenger-initial {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background-color: #e0f2fe;
      color: #0369a1;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      margin-right: 8px;
    }

    .user-list {
      max-height: 200px;
      overflow-y: auto;
      border-radius: 6px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      transition: all 0.3s ease;
    }
  </style>
</head>

<body class="bg-gray-50">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main flex-1 flex flex-col overflow-hidden">
      <!-- Navbar -->
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <button class="md:hidden text-gray-800" id="menu-btn">
          <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-xl font-semibold">
          <i class="text-teal-600 fas fa-plane-departure mx-2"></i> Flight Management
        </h1>
        <a href="add-flight.php" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700">
          <i class="fas fa-plus mr-2"></i> Add Flight
        </a>
      </div>

      <!-- Content Container -->
      <div class="overflow-auto flex-1 container mx-auto px-4 py-8">
        <?php if (isset($success_message)): ?>
          <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p><?php echo $success_message; ?></p>
          </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
          <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?php echo $error_message; ?></p>

            <?php if (isset($show_force_delete) && $show_force_delete): ?>
              <div class="mt-3">
                <a href="view-flight.php?delete_id=<?php echo $force_delete_id; ?>&force_delete=1"
                  class="bg-red-600 text-white py-2 px-4 rounded hover:bg-red-700 mr-2"
                  onclick="return confirm('WARNING: This will delete the flight AND ALL associated bookings. This action cannot be undone. Are you sure you want to proceed?');">
                  <i class="fas fa-exclamation-triangle mr-1"></i> Force Delete with All Bookings
                </a>
                <a href="view-flight.php" class="text-gray-600 hover:text-gray-800">
                  Cancel
                </a>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <!-- Summary Cards Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
          <!-- Total Flights Card -->
          <div class="summary-card p-6">
            <div class="flex items-center">
              <div class="summary-icon bg-blue-100 text-blue-600">
                <i class="fas fa-plane-departure text-2xl"></i>
              </div>
              <div class="ml-4">
                <h3 class="text-sm text-gray-500 uppercase">Total Flights</h3>
                <p class="text-2xl font-bold text-gray-800"><?php echo $total_flights; ?></p>
              </div>
            </div>
          </div>

          <!-- Total Seats Card -->
          <div class="summary-card p-6">
            <div class="flex items-center">
              <div class="summary-icon bg-green-100 text-green-600">
                <i class="fas fa-chair text-2xl"></i>
              </div>
              <div class="ml-4">
                <h3 class="text-sm text-gray-500 uppercase">Total Seats</h3>
                <p class="text-2xl font-bold text-gray-800"><?php echo $total_economy_seats + $total_business_seats + $total_first_class_seats; ?></p>
                <div class="text-xs text-gray-500 mt-1">
                  Economy: <?php echo $total_economy_seats; ?> |
                  Business: <?php echo $total_business_seats; ?> |
                  First: <?php echo $total_first_class_seats; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Total Seat Value Card -->
          <div class="summary-card p-6">
            <div class="flex items-center">
              <div class="summary-icon bg-purple-100 text-purple-600">
                <i class="fas fa-dollar-sign text-2xl"></i>
              </div>
              <div class="ml-4">
                <h3 class="text-sm text-gray-500 uppercase">Total Seat Value</h3>
                <p class="text-2xl font-bold text-gray-800">$<?php echo number_format($total_seat_value, 2); ?></p>
              </div>
            </div>
          </div>

          <!-- Average Seat Price Card -->
          <div class="summary-card p-6">
            <div class="flex items-center">
              <div class="summary-icon bg-amber-100 text-amber-600">
                <i class="fas fa-tags text-2xl"></i>
              </div>
              <div class="ml-4">
                <h3 class="text-sm text-gray-500 uppercase">Average Seat Price</h3>
                <?php
                $total_seats = $total_economy_seats + $total_business_seats + $total_first_class_seats;
                $avg_price = $total_seats > 0 ? $total_seat_value / $total_seats : 0;
                ?>
                <p class="text-2xl font-bold text-gray-800">$<?php echo number_format($avg_price, 2); ?></p>
              </div>
            </div>
          </div>
        </div>

        <!-- Detailed Summary Section -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
          <div class="p-4 bg-gray-50 border-b">
            <h2 class="text-lg font-bold text-gray-800">Detailed Summary</h2>
          </div>
          <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Economy Class Summary -->
            <div class="bg-blue-50 p-4 rounded-lg">
              <h3 class="text-blue-800 font-bold mb-2">Economy Class</h3>
              <div class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-gray-600">Total Seats:</span>
                  <span class="font-semibold"><?php echo $total_economy_seats; ?></span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Total Value:</span>
                  <span class="font-semibold">$<?php echo number_format($total_economy_price, 2); ?></span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Average Price:</span>
                  <span class="font-semibold">$<?php echo $total_economy_seats > 0 ? number_format($total_economy_price / $total_economy_seats, 2) : '0.00'; ?></span>
                </div>
              </div>
            </div>

            <!-- Business Class Summary -->
            <div class="bg-green-50 p-4 rounded-lg">
              <h3 class="text-green-800 font-bold mb-2">Business Class</h3>
              <div class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-gray-600">Total Seats:</span>
                  <span class="font-semibold"><?php echo $total_business_seats; ?></span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Total Value:</span>
                  <span class="font-semibold">$<?php echo number_format($total_business_price, 2); ?></span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Average Price:</span>
                  <span class="font-semibold">$<?php echo $total_business_seats > 0 ? number_format($total_business_price / $total_business_seats, 2) : '0.00'; ?></span>
                </div>
              </div>
            </div>

            <!-- First Class Summary -->
            <div class="bg-purple-50 p-4 rounded-lg">
              <h3 class="text-purple-800 font-bold mb-2">First Class</h3>
              <div class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-gray-600">Total Seats:</span>
                  <span class="font-semibold"><?php echo $total_first_class_seats; ?></span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Total Value:</span>
                  <span class="font-semibold">$<?php echo number_format($total_first_class_price, 2); ?></span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Average Price:</span>
                  <span class="font-semibold">$<?php echo $total_first_class_seats > 0 ? number_format($total_first_class_price / $total_first_class_seats, 2) : '0.00'; ?></span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- NEW: Passenger Statistics Section -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
          <div class="p-4 bg-gray-50 border-b">
            <h2 class="text-lg font-bold text-gray-800">Passenger Statistics</h2>
          </div>
          <div class="p-4 grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Total Passengers -->
            <div class="bg-blue-50 p-4 rounded-lg">
              <h3 class="text-blue-800 font-bold mb-2">Active Passengers</h3>
              <div class="text-3xl font-bold text-gray-800"><?php echo number_format($passenger_stats['total_passengers']); ?></div>
              <p class="text-sm text-gray-600 mt-1">Users who have made at least one booking</p>
            </div>

            <!-- Total Bookings -->
            <div class="bg-green-50 p-4 rounded-lg">
              <h3 class="text-green-800 font-bold mb-2">Total Bookings</h3>
              <div class="text-3xl font-bold text-gray-800"><?php echo number_format($passenger_stats['total_bookings']); ?></div>
              <p class="text-sm text-gray-600 mt-1">All bookings across all flights</p>
            </div>

            <!-- Registered Users -->
            <div class="bg-purple-50 p-4 rounded-lg">
              <h3 class="text-purple-800 font-bold mb-2">Registered Users</h3>
              <div class="text-3xl font-bold text-gray-800"><?php echo number_format($passenger_stats['registered_users']); ?></div>
              <p class="text-sm text-gray-600 mt-1">Total customer accounts in system</p>
            </div>

            <!-- Booking Ratio -->
            <div class="bg-amber-50 p-4 rounded-lg">
              <h3 class="text-amber-800 font-bold mb-2">Booking Ratio</h3>
              <div class="text-3xl font-bold text-gray-800"><?php echo round($passenger_stats['booking_ratio'] * 100); ?>%</div>
              <p class="text-sm text-gray-600 mt-1">Percentage of users who book flights</p>
            </div>
          </div>

          <!-- Top Passengers Table -->
          <div class="p-4 mt-2">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Top Passengers</h3>

            <?php if (!empty($top_passengers)): ?>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Passenger</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bookings</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Booking</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($top_passengers as $passenger): ?>
                      <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-full bg-teal-100 text-teal-800">
                              <?php echo strtoupper(substr($passenger['full_name'], 0, 1)); ?>
                            </div>
                            <div class="ml-4">
                              <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($passenger['full_name']); ?></div>
                            </div>
                          </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="text-sm text-gray-500"><?php echo htmlspecialchars($passenger['email']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                            <?php echo $passenger['booking_count']; ?> bookings
                          </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          <?php echo formatDate($passenger['last_booking']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                          <a href="user-details.php?id=<?php echo $passenger['id']; ?>" class="text-teal-600 hover:text-teal-900">View Profile</a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <div class="text-right mt-4">
                <a href="view-users.php" class="text-sm text-teal-600 hover:text-teal-800">View All Passengers <i class="fas fa-arrow-right ml-1"></i></a>
              </div>
            <?php else: ?>
              <p class="text-gray-500 text-center py-4">No passenger data available.</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- NEW: Recent Bookings Section with activity timeline -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
          <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
            <h2 class="text-lg font-bold text-gray-800">Recent Booking Activity</h2>
            <a href="view-bookings.php" class="text-teal-600 hover:text-teal-800">
              <i class="fas fa-list mr-1"></i> View All Bookings
            </a>
          </div>

          <div class="p-4">
            <?php if (!empty($recent_bookings)): ?>
              <div class="flow-root">
                <ul role="list" class="-mb-8">
                  <?php foreach ($recent_bookings as $index => $booking): ?>
                    <li>
                      <div class="relative pb-8">
                        <?php if ($index !== count($recent_bookings) - 1): ?>
                          <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                        <?php endif; ?>
                        <div class="relative flex space-x-3">
                          <div>
                            <?php
                            $status_colors = [
                              'confirmed' => 'bg-green-500',
                              'pending' => 'bg-yellow-500',
                              'cancelled' => 'bg-red-500',
                              'completed' => 'bg-blue-500'
                            ];
                            $status_color = isset($status_colors[strtolower($booking['booking_status'])]) ?
                              $status_colors[strtolower($booking['booking_status'])] : 'bg-gray-400';
                            ?>
                            <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white <?php echo $status_color; ?>">
                              <i class="fas fa-ticket-alt text-white"></i>
                            </span>
                          </div>
                          <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                            <div>
                              <p class="text-sm text-gray-500">
                                <a href="user-details.php?id=<?php echo $booking['passenger_id']; ?>" class="font-medium text-gray-900">
                                  <?php echo htmlspecialchars($booking['passenger_name']); ?>
                                </a> booked
                                <a href="view-flight-details.php?id=<?php echo $booking['flight_id']; ?>" class="font-medium text-teal-600 hover:text-teal-800">
                                  flight <?php echo htmlspecialchars($booking['flight_number']); ?>
                                </a>
                                from <?php echo htmlspecialchars($booking['departure_city']); ?> to
                                <?php echo htmlspecialchars($booking['arrival_city']); ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                  <?php echo ucfirst(htmlspecialchars($booking['seat_class'])); ?> Class
                                </span>
                              </p>
                            </div>
                            <div class="text-right text-sm whitespace-nowrap text-gray-500">
                              <span class="whitespace-nowrap"><?php echo formatDate($booking['booking_date']); ?></span>
                              <div class="text-xs mt-1">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php
                                switch (strtolower($booking['booking_status'])) {
                                  case 'confirmed':
                                    echo 'bg-green-100 text-green-800';
                                    break;
                                  case 'pending':
                                    echo 'bg-yellow-100 text-yellow-800';
                                    break;
                                  case 'cancelled':
                                    echo 'bg-red-100 text-red-800';
                                    break;
                                  case 'completed':
                                    echo 'bg-blue-100 text-blue-800';
                                    break;
                                  default:
                                    echo 'bg-gray-100 text-gray-800';
                                }
                                ?>">
                                  <?php echo ucfirst(htmlspecialchars($booking['booking_status'])); ?>
                                </span>
                                <span class="ml-1 text-gray-600">$<?php echo number_format($booking['booking_price'], 2); ?></span>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php else: ?>
              <p class="text-gray-500 text-center py-8">No recent booking activity found.</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
          <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
            <h2 class="text-lg font-bold text-gray-800">Filter Flights</h2>
            <?php if (!empty($filter_airline) || !empty($filter_departure) || !empty($filter_arrival) || !empty($filter_date_from) || !empty($filter_date_to)): ?>
              <a href="view-flight.php" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-times-circle mr-1"></i> Clear Filters
              </a>
            <?php endif; ?>
          </div>

          <form action="" method="GET" class="p-4 filter-form grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <!-- Airline Filter -->
            <div class="form-group">
              <label for="airline" class="block text-sm font-medium text-gray-700 mb-1">Airline</label>
              <select name="airline" id="airline" class="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 focus:ring-opacity-50">
                <option value="">All Airlines</option>
                <?php foreach ($airlines as $airline): ?>
                  <option value="<?php echo htmlspecialchars($airline); ?>" <?php echo $filter_airline == $airline ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($airline); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Departure City Filter -->
            <div class="form-group">
              <label for="departure" class="block text-sm font-medium text-gray-700 mb-1">Departure City</label>
              <select name="departure" id="departure" class="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 focus:ring-opacity-50">
                <option value="">All Departure Cities</option>
                <?php foreach ($departure_cities as $city): ?>
                  <option value="<?php echo htmlspecialchars($city); ?>" <?php echo $filter_departure == $city ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($city); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Arrival City Filter -->
            <div class="form-group">
              <label for="arrival" class="block text-sm font-medium text-gray-700 mb-1">Arrival City</label>
              <select name="arrival" id="arrival" class="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 focus:ring-opacity-50">
                <option value="">All Arrival Cities</option>
                <?php foreach ($arrival_cities as $city): ?>
                  <option value="<?php echo htmlspecialchars($city); ?>" <?php echo $filter_arrival == $city ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($city); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Date Range Filters -->
            <div class="form-group">
              <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
              <input type="date" name="date_from" id="date_from" value="<?php echo $filter_date_from; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 focus:ring-opacity-50">
            </div>

            <div class="form-group">
              <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
              <input type="date" name="date_to" id="date_to" value="<?php echo $filter_date_to; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 focus:ring-opacity-50">
            </div>

            <!-- Submit Button - Place at the end of the form -->
            <div class="form-group lg:col-span-5 mt-2 flex justify-end">
              <button type="submit" class="inline-flex items-center px-4 py-2 bg-teal-600 border border-transparent rounded-md font-semibold text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                <i class="fas fa-filter mr-2"></i> Apply Filters
              </button>
            </div>
          </form>
        </div>

        <!-- Flight List Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
          <div class="p-6 bg-gray-50 border-b flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800">Flight List</h2>
            <a href="add-flight.php" class="text-teal-600 hover:text-teal-800">
              <i class="fas fa-plus mr-1"></i> Add Flight
            </a>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Flight Details</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Schedule</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cabin Classes</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($flights)): ?>
                  <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No flights found.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($flights as $flight): ?>
                    <?php
                    // Parse JSON data
                    $prices = json_decode($flight['prices'], true);
                    $seats = json_decode($flight['seats'], true);
                    $cabin_classes = json_decode($flight['cabin_class'], true);

                    // Parse return flight data if available
                    $return_flight_data = !empty($flight['return_flight_data']) ? json_decode($flight['return_flight_data'], true) : null;
                    $has_return = $return_flight_data && isset($return_flight_data['has_return']) ? $return_flight_data['has_return'] : 0;

                    // Handle the outbound stops data properly
                    $stops_data = !empty($flight['stops']) ? json_decode($flight['stops'], true) : null;
                    $is_direct = false;

                    // Check if it's a direct flight
                    if ($stops_data === "direct" || $stops_data === null) {
                      $is_direct = true;
                      $stops = [];
                    } elseif (is_array($stops_data)) {
                      $stops = $stops_data;
                    } else {
                      $stops = [];
                    }

                    // Handle return stops data if available
                    $has_return_stops = false;
                    $return_stops = [];
                    if ($has_return && isset($return_flight_data['has_return_stops'])) {
                      $has_return_stops = $return_flight_data['has_return_stops'];
                      $return_stops_data = isset($return_flight_data['return_stops']) ? json_decode($return_flight_data['return_stops'], true) : null;

                      if ($return_stops_data === "direct" || $return_stops_data === null) {
                        $return_is_direct = true;
                      } elseif (is_array($return_stops_data)) {
                        $return_stops = $return_stops_data;
                        $return_is_direct = false;
                      }
                    }

                    // Check if this flight has bookings
                    $has_bookings = isset($flight['booking_count']) && $flight['booking_count'] > 0;
                    ?>
                    <tr class="flight-row hover:bg-gray-50 <?php echo $has_bookings ? 'has-bookings' : ''; ?>">
                      <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($flight['airline_name']); ?></div>
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($flight['flight_number']); ?></div>
                        <?php if ($has_return && !empty($return_flight_data['return_flight_number'])): ?>
                          <div class="text-xs text-gray-500 mt-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                              <i class="fas fa-exchange-alt mr-1"></i> Return: <?php echo htmlspecialchars($return_flight_data['return_flight_number']); ?>
                            </span>
                          </div>
                        <?php endif; ?>
                        <?php if ($has_bookings): ?>
                          <div class="text-xs text-gray-500 mt-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                              <i class="fas fa-users mr-1"></i> <?php echo $flight['booking_count']; ?> Booking<?php echo $flight['booking_count'] > 1 ? 's' : ''; ?>
                            </span>
                          </div>

                          <!-- NEW: Passenger List Toggle Button -->
                          <div class="mt-2">
                            <button onclick="toggleUserList('<?php echo $flight['id']; ?>')" class="text-blue-600 hover:text-blue-800 text-xs underline">
                              <i class="fas fa-users mr-1"></i> View Booked Passengers
                            </button>

                            <div id="user-list-<?php echo $flight['id']; ?>" class="hidden mt-2 max-h-40 overflow-y-auto user-list bg-white p-2" style="scrollbar-width: thin;">
                              <?php
                              // Parse the booked users string
                              $booked_users_str = isset($flight['booked_users']) ? $flight['booked_users'] : '';
                              $booked_users = [];

                              if (!empty($booked_users_str)) {
                                $users_array = explode('||', $booked_users_str);
                                foreach ($users_array as $user_str) {
                                  $user_parts = explode(':', $user_str);
                                  if (count($user_parts) >= 3) {
                                    $booked_users[] = [
                                      'id' => $user_parts[0],
                                      'name' => $user_parts[1],
                                      'email' => $user_parts[2]
                                    ];
                                  }
                                }
                              }

                              if (!empty($booked_users)):
                              ?>
                                <div class="space-y-1">
                                  <?php foreach ($booked_users as $user): ?>
                                    <a href="user-details.php?id=<?php echo $user['id']; ?>" class="flex items-center p-2 rounded-md hover:bg-gray-100 transition-colors">
                                      <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-teal-100 text-teal-800 mr-2">
                                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                      </span>
                                      <div class="flex flex-col">
                                        <span class="text-xs font-medium"><?php echo htmlspecialchars($user['name']); ?></span>
                                        <span class="text-xs text-gray-500"><?php echo htmlspecialchars($user['email']); ?></span>
                                      </div>
                                    </a>
                                  <?php endforeach; ?>
                                </div>
                              <?php else: ?>
                                <p class="text-xs text-gray-500 italic">No passenger details available.</p>
                              <?php endif; ?>
                            </div>
                          </div>
                        <?php endif; ?>
                      </td>
                      <td class="px-6 py-4">
                        <!-- Outbound Route -->
                        <div class="text-sm text-gray-900 font-medium">
                          <?php echo htmlspecialchars($flight['departure_city']); ?> → <?php echo htmlspecialchars($flight['arrival_city']); ?>
                        </div>

                        <!-- Flight type display -->
                        <div class="text-xs text-gray-500 mt-1 flex flex-wrap gap-1">
                          <?php if ($is_direct): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                              <i class="fas fa-plane mr-1"></i> Direct Flight
                            </span>
                          <?php elseif (!empty($stops)): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                              <i class="fas fa-plane-arrival mr-1"></i> <?php echo count($stops); ?> stop<?php echo count($stops) > 1 ? 's' : ''; ?>
                            </span>
                          <?php endif; ?>

                          <?php if ($has_return): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                              <i class="fas fa-undo-alt mr-1"></i> Round Trip
                            </span>
                          <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                              <i class="fas fa-long-arrow-alt-right mr-1"></i> One Way
                            </span>
                          <?php endif; ?>
                        </div>

                        <!-- Visual representation of outbound flight route with dots -->
                        <div class="flex items-center mt-2">
                          <!-- Departure city dot -->
                          <div class="flex flex-col items-center">
                            <div class="w-3 h-3 rounded-full bg-teal-600"></div>
                            <div class="text-xs text-gray-500 mt-0.5 text-center w-16 truncate">
                              <?php echo htmlspecialchars($flight['departure_city']); ?>
                            </div>
                          </div>

                          <!-- If there are stops, show them -->
                          <?php if (!empty($stops)): ?>
                            <?php foreach ($stops as $stop): ?>
                              <div class="h-0.5 w-10 bg-gray-300 mx-1"></div>
                              <div class="flex flex-col items-center">
                                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                <div class="text-xs text-gray-500 mt-0.5 text-center w-16 truncate">
                                  <?php echo isset($stop['city']) ? htmlspecialchars($stop['city']) : ''; ?>
                                </div>
                              </div>
                            <?php endforeach; ?>
                          <?php endif; ?>

                          <!-- Line to arrival city (wider if direct flight) -->
                          <div class="h-0.5 <?php echo $is_direct ? 'w-16' : 'w-10'; ?> bg-gray-300 mx-1"></div>

                          <!-- Arrival city dot -->
                          <div class="flex flex-col items-center">
                            <div class="w-3 h-3 rounded-full bg-teal-600"></div>
                            <div class="text-xs text-gray-500 mt-0.5 text-center w-16 truncate">
                              <?php echo htmlspecialchars($flight['arrival_city']); ?>
                            </div>
                          </div>
                        </div>

                        <!-- Return route if it exists -->
                        <?php if ($has_return): ?>
                          <div class="route-separator"></div>

                          <div class="text-sm text-gray-900 font-medium">
                            <i class="fas fa-undo-alt text-xs mr-1 text-gray-500"></i>
                            Return: <?php echo htmlspecialchars($flight['arrival_city']); ?> → <?php echo htmlspecialchars($flight['departure_city']); ?>
                          </div>

                          <!-- Return flight type display -->
                          <div class="text-xs text-gray-500 mt-1">
                            <?php if (empty($return_stops)): ?>
                              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-plane mr-1"></i> Direct Return
                              </span>
                            <?php else: ?>
                              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                <i class="fas fa-plane-arrival mr-1"></i> <?php echo count($return_stops); ?> stop<?php echo count($return_stops) > 1 ? 's' : ''; ?>
                              </span>
                            <?php endif; ?>
                          </div>

                          <!-- Visual representation of return flight route with dots -->
                          <div class="flex items-center mt-2">
                            <!-- Return departure city dot (original arrival city) -->
                            <div class="flex flex-col items-center">
                              <div class="w-3 h-3 rounded-full bg-purple-600"></div>
                              <div class="text-xs text-gray-500 mt-0.5 text-center w-16 truncate">
                                <?php echo htmlspecialchars($flight['arrival_city']); ?>
                              </div>
                            </div>

                            <!-- If there are return stops, show them -->
                            <?php if (!empty($return_stops)): ?>
                              <?php foreach ($return_stops as $stop): ?>
                                <div class="h-0.5 w-10 bg-gray-300 mx-1"></div>
                                <div class="flex flex-col items-center">
                                  <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                  <div class="text-xs text-gray-500 mt-0.5 text-center w-16 truncate">
                                    <?php echo isset($stop['city']) ? htmlspecialchars($stop['city']) : ''; ?>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            <?php endif; ?>

                            <!-- Line to return arrival city (wider if direct flight) -->
                            <div class="h-0.5 <?php echo empty($return_stops) ? 'w-16' : 'w-10'; ?> bg-gray-300 mx-1"></div>

                            <!-- Return arrival city dot (original departure city) -->
                            <div class="flex flex-col items-center">
                              <div class="w-3 h-3 rounded-full bg-purple-600"></div>
                              <div class="text-xs text-gray-500 mt-0.5 text-center w-16 truncate">
                                <?php echo htmlspecialchars($flight['departure_city']); ?>
                              </div>
                            </div>
                          </div>
                        <?php endif; ?>
                      </td>
                      <td class="px-6 py-4">
                        <!-- Outbound schedule -->
                        <div class="flex items-center text-sm text-gray-900 mb-1">
                          <i class="fas fa-plane-departure text-gray-400 mr-1"></i>
                          <?php echo formatDate($flight['departure_date']); ?>
                          <span class="text-xs ml-2 flight-time-display"><?php echo date('H:i', strtotime($flight['departure_time'])); ?></span>
                        </div>

                        <!-- Flight duration if available -->
                        <?php if (!empty($flight['flight_duration'])): ?>
                          <div class="flex items-center text-xs text-gray-500 mb-2">
                            <i class="far fa-clock text-gray-400 mr-1"></i>
                            <span>Duration: <?php echo htmlspecialchars($flight['flight_duration']); ?> hours</span>
                          </div>
                        <?php endif; ?>

                        <!-- Return schedule if exists -->
                        <?php if ($has_return && !empty($return_flight_data['return_date'])): ?>
                          <div class="flex items-center text-sm text-gray-900 mt-3 pt-2 border-t border-gray-100">
                            <i class="fas fa-plane-arrival text-gray-400 mr-1"></i>
                            <?php echo formatDate($return_flight_data['return_date']); ?>
                            <?php if (!empty($return_flight_data['return_time'])): ?>
                              <span class="text-xs ml-2 flight-time-display"><?php echo date('H:i', strtotime($return_flight_data['return_time'])); ?></span>
                            <?php endif; ?>
                          </div>

                          <!-- Return flight duration if available -->
                          <?php if (!empty($return_flight_data['return_flight_duration'])): ?>
                            <div class="flex items-center text-xs text-gray-500">
                              <i class="far fa-clock text-gray-400 mr-1"></i>
                              <span>Return Duration: <?php echo htmlspecialchars($return_flight_data['return_flight_duration']); ?> hours</span>
                            </div>
                          <?php endif; ?>
                        <?php endif; ?>
                      </td>
                      <td class="px-6 py-4">
                        <div class="text-sm text-gray-500">
                          <?php
                          // Check if using new format (with seat_ids) or old format
                          if (isset($seats['economy']) && is_array($seats['economy']) && isset($seats['economy']['count'])) {
                            // New format with seat_ids
                            echo 'Economy - ' . $seats['economy']['count'] . ' seats $' . number_format($prices['economy'], 2) . '<br>';
                            echo 'Business - ' . $seats['business']['count'] . ' seats $' . number_format($prices['business'], 2) . '<br>';
                            echo 'First Class - ' . $seats['first_class']['count'] . ' seats $' . number_format($prices['first_class'], 2);
                          } else {
                            // Old format (direct values)
                            echo 'Economy - ' . $seats['economy'] . ' seats $' . number_format($prices['economy'], 2) . '<br>';
                            echo 'Business - ' . $seats['business'] . ' seats $' . number_format($prices['business'], 2) . '<br>';
                            echo 'First Class - ' . $seats['first_class'] . ' seats $' . number_format($prices['first_class'], 2);
                          }
                          ?>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="edit_flight.php?id=<?php echo $flight['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                          <i class="fas fa-edit"></i>
                        </a>
                        <a href="view-flight.php?delete_id=<?php echo $flight['id']; ?>" class="text-red-600 hover:text-red-900"
                          onclick="return confirm('Are you sure you want to delete this flight<?php echo $has_bookings ? '? This will require additional confirmation since it has bookings' : '?'; ?>');">
                          <i class="fas fa-trash-alt"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/js-links.php'; ?>

  <!-- JavaScript Section -->
  <script>
    // Toggle user list for each flight
    function toggleUserList(flightId) {
      const userList = document.getElementById(`user-list-${flightId}`);

      // Close any other open user lists first
      document.querySelectorAll('[id^="user-list-"]').forEach(el => {
        if (el.id !== `user-list-${flightId}`) {
          el.classList.add('hidden');
        }
      });

      // Toggle the selected user list
      if (userList.classList.contains('hidden')) {
        userList.classList.remove('hidden');

        // Add a slight animation
        userList.style.opacity = '0';
        userList.style.transform = 'translateY(-10px)';

        setTimeout(() => {
          userList.style.opacity = '1';
          userList.style.transform = 'translateY(0)';
        }, 50);
      } else {
        // Add closing animation
        userList.style.opacity = '0';
        userList.style.transform = 'translateY(-10px)';

        setTimeout(() => {
          userList.classList.add('hidden');
        }, 200);
      }
    }

    // Add click listener to close user lists when clicking outside
    document.addEventListener('click', function(event) {
      const isUserListButton = event.target.closest('button') &&
        event.target.closest('button').onclick &&
        event.target.closest('button').onclick.toString().includes('toggleUserList');

      const isInsideUserList = event.target.closest('[id^="user-list-"]');

      if (!isUserListButton && !isInsideUserList) {
        document.querySelectorAll('[id^="user-list-"]').forEach(el => {
          el.classList.add('hidden');
        });
      }
    });

    // Initialize any date pickers
    document.addEventListener('DOMContentLoaded', function() {
      // Add any initialization code here

      // Highlight flights with bookings
      document.querySelectorAll('.has-bookings').forEach(row => {
        row.addEventListener('mouseover', function() {
          this.style.backgroundColor = '#fff7ed';
        });

        row.addEventListener('mouseout', function() {
          this.style.backgroundColor = '#fffbeb';
        });
      });
    });
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Function to format time inputs for 24-hour display
      function setup24HourTimeInput(input) {
        if (!input) return;

        // Add input event handler for formatting
        input.addEventListener('input', function(e) {
          let value = e.target.value;

          // Only allow digits and colon
          value = value.replace(/[^0-9:]/g, '');

          // Auto-add colon after 2 digits if not already there
          if (value.length === 2 && !value.includes(':')) {
            value += ':';
          }

          // Limit to 5 chars (HH:MM)
          if (value.length > 5) {
            value = value.substring(0, 5);
          }

          // Validate hours (00-23)
          if (value.includes(':') && value.split(':')[0].length === 2) {
            const hours = parseInt(value.split(':')[0]);
            if (hours > 23) {
              value = '23' + value.substring(2);
            }
          }

          // Validate minutes (00-59)
          if (value.includes(':') && value.split(':')[1] && value.split(':')[1].length === 2) {
            const minutes = parseInt(value.split(':')[1]);
            if (minutes > 59) {
              value = value.split(':')[0] + ':59';
            }
          }

          // Update the input value
          e.target.value = value;
        });

        // Add blur event to ensure proper format on exit
        input.addEventListener('blur', function(e) {
          let value = e.target.value;

          if (value && !value.includes(':')) {
            // If only hours were entered, add ":00" for minutes
            if (value.length <= 2) {
              const hours = parseInt(value);
              if (hours >= 0 && hours <= 23) {
                value = hours.toString().padStart(2, '0') + ':00';
              }
            }
          } else if (value.includes(':')) {
            // If there's a colon, make sure both parts are valid
            const parts = value.split(':');
            if (parts[0] && parts[1]) {
              // Both hours and minutes exist
              const hours = parseInt(parts[0]);
              const minutes = parseInt(parts[1]);

              if (!isNaN(hours) && !isNaN(minutes) && hours >= 0 && hours <= 23 && minutes >= 0 && minutes <= 59) {
                // Format with leading zeros
                value = hours.toString().padStart(2, '0') + ':' + minutes.toString().padStart(2, '0');
              }
            } else if (parts[0] && parts[1] === '') {
              // Hours with empty minutes
              const hours = parseInt(parts[0]);
              if (!isNaN(hours) && hours >= 0 && hours <= 23) {
                value = hours.toString().padStart(2, '0') + ':00';
              }
            }
          }

          e.target.value = value;
        });
      }

      // Apply to departure time input
      const departureTimeInput = document.querySelector('input[name="departure_time"]');
      setup24HourTimeInput(departureTimeInput);

      // Apply to return time input if it exists
      const returnTimeInput = document.querySelector('input[name="return_time"]');
      if (returnTimeInput) {
        setup24HourTimeInput(returnTimeInput);
      }
    });
  </script>
</body>

</html>