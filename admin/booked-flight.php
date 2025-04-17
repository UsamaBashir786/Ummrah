<?php
session_name("admin_session");
session_start();
include '../connection/connection.php';

// Verify admin is logged in
if (!isset($_SESSION['admin_email'])) {
  header("Location: login.php");
  exit();
}

// Process status update if submitted
if (isset($_POST['update_status']) && isset($_POST['booking_id']) && isset($_POST['new_status'])) {
  $booking_id = intval($_POST['booking_id']);
  $new_status = $_POST['new_status'];

  // Validate status value
  $valid_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
  if (in_array($new_status, $valid_statuses)) {
    try {
      $update_sql = "UPDATE flight_bookings SET booking_status = ? WHERE id = ?";
      $update_stmt = $conn->prepare($update_sql);
      $update_stmt->bind_param("si", $new_status, $booking_id);

      if ($update_stmt->execute()) {
        $success_message = "Booking #$booking_id status updated to " . ucfirst($new_status);
      } else {
        $error_message = "Error updating booking status.";
      }

      $update_stmt->close();
    } catch (Exception $e) {
      $error_message = "Database error: " . $e->getMessage();
    }
  }
}

// Delete booking if requested
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
  $delete_id = intval($_GET['delete_id']);

  try {
    $delete_sql = "DELETE FROM flight_bookings WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $delete_id);

    if ($delete_stmt->execute()) {
      $success_message = "Booking deleted successfully.";
    } else {
      $error_message = "Error deleting booking.";
    }

    $delete_stmt->close();
  } catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
  }
}

// Initialize variables for filtering
$filter_flight_id = isset($_GET['flight_id']) ? intval($_GET['flight_id']) : 0;
$filter_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$filter_cabin_class = isset($_GET['cabin_class']) ? $_GET['cabin_class'] : '';
$filter_status = isset($_GET['booking_status']) ? $_GET['booking_status'] : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Prepare base query for bookings
$query = "SELECT fb.*, f.flight_number, f.airline_name, f.departure_city, f.arrival_city, 
            f.departure_date, f.departure_time, u.full_name as user_full_name, u.email as user_email 
            FROM flight_bookings fb 
            LEFT JOIN flights f ON fb.flight_id = f.id 
            LEFT JOIN users u ON fb.user_id = u.id";

// Add filters if provided
$where_clauses = [];
$params = [];
$types = "";

if ($filter_flight_id > 0) {
  $where_clauses[] = "fb.flight_id = ?";
  $params[] = $filter_flight_id;
  $types .= "i";
}

if ($filter_user_id > 0) {
  $where_clauses[] = "fb.user_id = ?";
  $params[] = $filter_user_id;
  $types .= "i";
}

if (!empty($filter_cabin_class)) {
  $where_clauses[] = "fb.cabin_class = ?";
  $params[] = $filter_cabin_class;
  $types .= "s";
}

if (!empty($filter_status)) {
  $where_clauses[] = "fb.booking_status = ?";
  $params[] = $filter_status;
  $types .= "s";
}

if (!empty($filter_date_from)) {
  $where_clauses[] = "DATE(fb.booking_date) >= ?";
  $params[] = $filter_date_from;
  $types .= "s";
}

if (!empty($filter_date_to)) {
  $where_clauses[] = "DATE(fb.booking_date) <= ?";
  $params[] = $filter_date_to;
  $types .= "s";
}

// Combine clauses if any exist
if (!empty($where_clauses)) {
  $query .= " WHERE " . implode(" AND ", $where_clauses);
}

// Order by most recent first
$query .= " ORDER BY fb.booking_date DESC";

// Fetch all bookings with filters
$bookings = [];
$total_bookings = 0;
$total_revenue = 0;
$total_seats = 0;
$total_adults = 0;
$total_children = 0;
$completed_revenue = 0;
$class_distribution = [
  'economy' => 0,
  'business' => 0,
  'first_class' => 0
];
$class_revenue = [
  'economy' => 0,
  'business' => 0,
  'first_class' => 0
];
$status_distribution = [
  'pending' => 0,
  'confirmed' => 0,
  'cancelled' => 0,
  'completed' => 0
];

try {
  $stmt = $conn->prepare($query);

  // Bind parameters if any
  if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
  }

  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $bookings[] = $row;

      // Calculate totals
      $total_bookings++;
      $total_revenue += floatval($row['price']);
      $total_adults += intval($row['adult_count']);
      $total_children += intval($row['children_count']);
      $total_seats += (intval($row['adult_count']) + intval($row['children_count']));

      // Count by cabin class and track revenue
      if (isset($row['cabin_class'])) {
        $class_distribution[$row['cabin_class']]++;
        $class_revenue[$row['cabin_class']] += floatval($row['price']);
      }

      // Count by status and track completed revenue
      if (isset($row['booking_status'])) {
        $status_distribution[$row['booking_status']]++;

        if ($row['booking_status'] == 'completed') {
          $completed_revenue += floatval($row['price']);
        }
      }
    }
  }

  $stmt->close();
} catch (Exception $e) {
  $error_message = "Database error: " . $e->getMessage();
}

// Calculate percentage distributions
$class_percentages = [];
$status_percentages = [];

if ($total_bookings > 0) {
  foreach ($class_distribution as $class => $count) {
    $class_percentages[$class] = round(($count / $total_bookings) * 100);
  }

  foreach ($status_distribution as $status => $count) {
    $status_percentages[$status] = round(($count / $total_bookings) * 100);
  }
}

// Fetch all flights for filter dropdown
$flights = [];
try {
  $flights_sql = "SELECT id, flight_number, airline_name, departure_city, arrival_city FROM flights ORDER BY departure_date DESC";
  $flights_result = $conn->query($flights_sql);

  if ($flights_result->num_rows > 0) {
    while ($row = $flights_result->fetch_assoc()) {
      $flights[] = $row;
    }
  }
} catch (Exception $e) {
  // Just skip if there's an error
}

// Fetch all users for filter dropdown
$users = [];
try {
  $users_sql = "SELECT id, full_name, email FROM users ORDER BY full_name";
  $users_result = $conn->query($users_sql);

  if ($users_result->num_rows > 0) {
    while ($row = $users_result->fetch_assoc()) {
      $users[] = $row;
    }
  }
} catch (Exception $e) {
  // Just skip if there's an error
}

// Function to get formatted class name
function formatClassName($class)
{
  return ucfirst(str_replace('_', ' ', $class));
}

// Function to get class color
function getClassColor($class, $type = 'bg')
{
  $colors = [
    'economy' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800'],
    'business' => ['bg' => 'bg-green-100', 'text' => 'text-green-800'],
    'first_class' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800']
  ];

  return isset($colors[$class][$type]) ? $colors[$class][$type] : 'bg-gray-100';
}

// Function to get status color
function getStatusColor($status, $type = 'bg')
{
  $colors = [
    'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800'],
    'confirmed' => ['bg' => 'bg-green-100', 'text' => 'text-green-800'],
    'completed' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800'],
    'cancelled' => ['bg' => 'bg-red-100', 'text' => 'text-red-800']
  ];

  return isset($colors[$status][$type]) ? $colors[$status][$type] : 'bg-gray-100';
}

// Function to format seats display
function formatSeatsDisplay($seats_array)
{
  if (empty($seats_array)) {
    return 'No seat assigned';
  }

  // If it's a structure from create-flight-booking.php
  if (isset($seats_array['passengers'])) {
    $passenger_count = isset($seats_array['count']) ? $seats_array['count'] : count($seats_array['passengers']);
    return $passenger_count . ' passenger' . ($passenger_count > 1 ? 's' : '');
  }

  // If it's a simple array of seat IDs
  if (isset($seats_array[0]) && !is_array($seats_array[0])) {
    return implode(', ', $seats_array);
  }

  // For other complex structures
  return 'Seats assigned';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <style>
    .booking-row:hover {
      background-color: #f0f9ff;
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

    .progress-bar {
      height: 8px;
      border-radius: 4px;
      background-color: #e5e7eb;
      overflow: hidden;
    }

    .progress-fill {
      height: 100%;
      border-radius: 4px;
    }

    .status-select {
      padding: 2px 4px;
      font-size: 0.75rem;
      border-radius: 0.25rem;
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
          <i class="text-teal-600 fas fa-ticket-alt mx-2"></i> Flight Bookings
        </h1>
        <div class="flex items-center gap-3">
          <button id="refresh-btn" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">
            <i class="fas fa-sync-alt mr-2"></i> Refresh
          </button>
          <a href="view-flight.php" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700">
            <i class="fas fa-plane-departure mr-2"></i> View Flights
          </a>
        </div>
      </div>

      <script>
        // Immediately attach the event handler to the refresh button
        document.addEventListener('DOMContentLoaded', function() {
          const refreshBtn = document.getElementById('refresh-btn');
          if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
              // Add spinning animation to the refresh icon
              const refreshIcon = this.querySelector('.fa-sync-alt');
              if (refreshIcon) {
                refreshIcon.classList.add('fa-spin');
              }

              // Refresh the page after a small delay
              setTimeout(() => {
                window.location.reload(true); // true forces reload from server, not cache
              }, 300);
            });
          }
        });
      </script>

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
          </div>
        <?php endif; ?>

        <!-- Summary Cards Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2` gap-4 mb-6">
          <!-- Total Bookings Card -->
          <div class="summary-card p-6">
            <div class="flex items-center">
              <div class="summary-icon bg-blue-100 text-blue-600">
                <i class="fas fa-ticket-alt text-2xl"></i>
              </div>
              <div class="ml-4">
                <h3 class="text-sm text-gray-500 uppercase">Total Bookings</h3>
                <p class="text-2xl font-bold text-gray-800"><?php echo $total_bookings; ?></p>
              </div>
            </div>
          </div>

          <!-- Total Revenue Card -->
          <div class="summary-card p-6">
            <div class="flex items-center">
              <div class="summary-icon bg-green-100 text-green-600">
                <i class="fas fa-dollar-sign text-2xl"></i>
              </div>
              <div class="ml-4">
                <h3 class="text-sm text-gray-500 uppercase">Total Revenue</h3>
                <p class="text-2xl font-bold text-gray-800">$<?php echo number_format($total_revenue, 2); ?></p>
              </div>
            </div>
          </div>

          <!-- Completed Revenue Card -->
          <div class="summary-card p-6">
            <div class="flex items-center">
              <div class="summary-icon bg-indigo-100 text-indigo-600">
                <i class="fas fa-check-circle text-2xl"></i>
              </div>
              <div class="ml-4">
                <h3 class="text-sm text-gray-500 uppercase">Completed Revenue</h3>
                <p class="text-2xl font-bold text-gray-800">$<?php echo number_format($completed_revenue, 2); ?></p>
                <div class="text-xs text-gray-500 mt-1">
                  <?php echo $status_distribution['completed'] ?? 0; ?> bookings
                </div>
              </div>
            </div>
          </div>

          <!-- Total Passengers Card -->
          <div class="summary-card p-6">
            <div class="flex items-center">
              <div class="summary-icon bg-purple-100 text-purple-600">
                <i class="fas fa-users text-2xl"></i>
              </div>
              <div class="ml-4">
                <h3 class="text-sm text-gray-500 uppercase">Total Passengers</h3>
                <p class="text-2xl font-bold text-gray-800"><?php echo $total_seats; ?></p>
                <div class="text-xs text-gray-500 mt-1">
                  Adults: <?php echo $total_adults; ?> |
                  Children: <?php echo $total_children; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Average Booking Value Card -->
          <div class="summary-card p-6">
            <div class="flex items-center">
              <div class="summary-icon bg-amber-100 text-amber-600">
                <i class="fas fa-chart-line text-2xl"></i>
              </div>
              <div class="ml-4">
                <h3 class="text-sm text-gray-500 uppercase">Average Booking Value</h3>
                <p class="text-2xl font-bold text-gray-800">
                  $<?php echo $total_bookings > 0 ? number_format($total_revenue / $total_bookings, 2) : '0.00'; ?>
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Distribution Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <!-- Class Distribution Card -->
          <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4 bg-gray-50 border-b">
              <h3 class="text-lg font-bold text-gray-800">Class Distribution</h3>
            </div>
            <div class="p-4">
              <div class="space-y-4">
                <!-- Economy -->
                <div>
                  <div class="flex justify-between items-center mb-1">
                    <span class="text-sm font-medium text-gray-700">
                      Economy
                    </span>
                    <span class="text-sm text-gray-600">
                      <?php echo $class_distribution['economy']; ?> bookings
                      (<?php echo $class_percentages['economy'] ?? 0; ?>%)
                    </span>
                  </div>
                  <div class="progress-bar">
                    <div class="progress-fill bg-blue-500" style="width: <?php echo $class_percentages['economy'] ?? 0; ?>%"></div>
                  </div>
                </div>

                <!-- Business -->
                <div>
                  <div class="flex justify-between items-center mb-1">
                    <span class="text-sm font-medium text-gray-700">
                      Business
                    </span>
                    <span class="text-sm text-gray-600">
                      <?php echo $class_distribution['business']; ?> bookings
                      (<?php echo $class_percentages['business'] ?? 0; ?>%)
                    </span>
                  </div>
                  <div class="progress-bar">
                    <div class="progress-fill bg-green-500" style="width: <?php echo $class_percentages['business'] ?? 0; ?>%"></div>
                  </div>
                </div>

                <!-- First Class -->
                <div>
                  <div class="flex justify-between items-center mb-1">
                    <span class="text-sm font-medium text-gray-700">
                      First Class
                    </span>
                    <span class="text-sm text-gray-600">
                      <?php echo $class_distribution['first_class']; ?> bookings
                      (<?php echo $class_percentages['first_class'] ?? 0; ?>%)
                    </span>
                  </div>
                  <div class="progress-bar">
                    <div class="progress-fill bg-purple-500" style="width: <?php echo $class_percentages['first_class'] ?? 0; ?>%"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Revenue by Cabin Class Card -->
          <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4 bg-gray-50 border-b">
              <h3 class="text-lg font-bold text-gray-800">Revenue by Class</h3>
            </div>
            <div class="p-4">
              <div class="space-y-4">
                <!-- Economy -->
                <div>
                  <div class="flex justify-between items-center mb-1">
                    <span class="text-sm font-medium text-gray-700">
                      Economy
                    </span>
                    <span class="text-sm text-gray-600">
                      $<?php echo number_format($class_revenue['economy'], 2); ?>
                      (<?php echo $total_revenue > 0 ? round(($class_revenue['economy'] / $total_revenue) * 100) : 0; ?>%)
                    </span>
                  </div>
                  <div class="progress-bar">
                    <div class="progress-fill bg-blue-500" style="width: <?php echo $total_revenue > 0 ? round(($class_revenue['economy'] / $total_revenue) * 100) : 0; ?>%"></div>
                  </div>
                </div>

                <!-- Business -->
                <div>
                  <div class="flex justify-between items-center mb-1">
                    <span class="text-sm font-medium text-gray-700">
                      Business
                    </span>
                    <span class="text-sm text-gray-600">
                      $<?php echo number_format($class_revenue['business'], 2); ?>
                      (<?php echo $total_revenue > 0 ? round(($class_revenue['business'] / $total_revenue) * 100) : 0; ?>%)
                    </span>
                  </div>
                  <div class="progress-bar">
                    <div class="progress-fill bg-green-500" style="width: <?php echo $total_revenue > 0 ? round(($class_revenue['business'] / $total_revenue) * 100) : 0; ?>%"></div>
                  </div>
                </div>

                <!-- First Class -->
                <div>
                  <div class="flex justify-between items-center mb-1">
                    <span class="text-sm font-medium text-gray-700">
                      First Class
                    </span>
                    <span class="text-sm text-gray-600">
                      $<?php echo number_format($class_revenue['first_class'], 2); ?>
                      (<?php echo $total_revenue > 0 ? round(($class_revenue['first_class'] / $total_revenue) * 100) : 0; ?>%)
                    </span>
                  </div>
                  <div class="progress-bar">
                    <div class="progress-fill bg-purple-500" style="width: <?php echo $total_revenue > 0 ? round(($class_revenue['first_class'] / $total_revenue) * 100) : 0; ?>%"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Status Distribution Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
          <div class="p-4 bg-gray-50 border-b">
            <h3 class="text-lg font-bold text-gray-800">Booking Status</h3>
          </div>
          <div class="p-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <?php foreach ($status_distribution as $status => $count): ?>
                <div class="bg-white border rounded-lg p-3 flex items-center">
                  <div class="w-3 h-3 rounded-full <?php echo getStatusColor($status); ?> mr-2"></div>
                  <div>
                    <span class="block text-sm font-medium text-gray-700">
                      <?php echo ucfirst($status); ?>
                    </span>
                    <span class="block text-xs text-gray-500">
                      <?php echo $count; ?> bookings (<?php echo $status_percentages[$status] ?? 0; ?>%)
                    </span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
          <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
            <h2 class="text-lg font-bold text-gray-800">Filter Bookings</h2>
            <?php if (!empty($filter_flight_id) || !empty($filter_user_id) || !empty($filter_cabin_class) || !empty($filter_status) || !empty($filter_date_from) || !empty($filter_date_to)): ?>
              <a href="booked-flight.php" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-times-circle mr-1"></i> Clear All Filters
              </a>
            <?php endif; ?>
          </div>
          <div class="p-4">
            <form method="GET" action="booked-flight.php" class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <!-- Flight Filter -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Flight</label>
                <select name="flight_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500">
                  <option value="">All Flights</option>
                  <?php foreach ($flights as $flight): ?>
                    <option value="<?php echo $flight['id']; ?>" <?php echo ($filter_flight_id == $flight['id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($flight['flight_number'] . ' - ' . $flight['departure_city'] . ' to ' . $flight['arrival_city']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- User Filter -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                <select name="user_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500">
                  <option value="">All Users</option>
                  <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['id']; ?>" <?php echo ($filter_user_id == $user['id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($user['full_name'] . ' (' . $user['email'] . ')'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- Cabin Class Filter -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cabin Class</label>
                <select name="cabin_class" class="w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500">
                  <option value="">All Classes</option>
                  <option value="economy" <?php echo ($filter_cabin_class == 'economy') ? 'selected' : ''; ?>>Economy</option>
                  <option value="business" <?php echo ($filter_cabin_class == 'business') ? 'selected' : ''; ?>>Business</option>
                  <option value="first_class" <?php echo ($filter_cabin_class == 'first_class') ? 'selected' : ''; ?>>First Class</option>
                </select>
              </div>

              <!-- Status Filter -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Booking Status</label>
                <select name="booking_status" class="w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500">
                  <option value="">All Statuses</option>
                  <option value="pending" <?php echo ($filter_status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                  <option value="confirmed" <?php echo ($filter_status == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                  <option value="completed" <?php echo ($filter_status == 'completed') ? 'selected' : ''; ?>>Completed</option>
                  <option value="cancelled" <?php echo ($filter_status == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                </select>
              </div>

              <!-- Date Range -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                <input type="date" name="date_from" value="<?php echo $filter_date_from; ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500">
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                <input type="date" name="date_to" value="<?php echo $filter_date_to; ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500">
              </div>

              <!-- Filter Buttons -->
              <div class="flex items-end space-x-2">
                <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700">
                  <i class="fas fa-filter mr-2"></i> Apply Filters
                </button>
                <a href="booked-flight.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                  <i class="fas fa-times mr-2"></i> Clear
                </a>
              </div>
            </form>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
          <div class="p-6 bg-gray-50 border-b flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800">Booking List</h2>
            <span class="text-gray-600">Total: <?php echo count($bookings); ?> bookings</span>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking Info</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Flight Details</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Passenger Info</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seats & Price</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($bookings)): ?>
                  <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No bookings found.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($bookings as $booking): ?>
                    <?php
                    // Format cabin class for display
                    $cabin_class_display = ucfirst(str_replace('_', ' ', $booking['cabin_class']));

                    // Parse JSON seats data
                    $seats_array = json_decode($booking['seats'], true);
                    $seats_display = formatSeatsDisplay($seats_array);

                    // Get status color classes for the select
                    $status_bg_class = '';
                    switch ($booking['booking_status']) {
                      case 'pending':
                        $status_bg_class = 'bg-yellow-100';
                        break;
                      case 'confirmed':
                        $status_bg_class = 'bg-green-100';
                        break;
                      case 'completed':
                        $status_bg_class = 'bg-blue-100';
                        break;
                      case 'cancelled':
                        $status_bg_class = 'bg-red-100';
                        break;
                    }

                    // Get booking status badge color
                    $status_class = getStatusColor($booking['booking_status'], 'bg') . ' ' . getStatusColor($booking['booking_status'], 'text');
                    ?>
                    <tr class="booking-row hover:bg-gray-50">
                      <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                          <span class="font-medium">Booking ID:</span> #<?php echo $booking['id']; ?>
                        </div>
                        <div class="text-sm text-gray-500">
                          <span class="font-medium">Date:</span> <?php echo date('M d, Y H:i', strtotime($booking['booking_date'])); ?>
                        </div>
                        <div class="flex flex-wrap gap-1 mt-1">
                          <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php echo getClassColor($booking['cabin_class'], 'bg'); ?> <?php echo getClassColor($booking['cabin_class'], 'text'); ?>">
                            <?php echo $cabin_class_display; ?>
                          </span>

                          <form method="POST" class="status-update-form inline-block ml-1" data-booking-id="<?php echo $booking['id']; ?>">
                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                            <input type="hidden" name="update_status" value="1">
                            <div class="flex items-center">
                              <select name="new_status" class="status-select text-xs rounded border border-gray-300 py-1 px-2 focus:border-teal-500 focus:ring-teal-500 <?php echo $status_bg_class; ?>">
                                <option value="pending" <?php echo ($booking['booking_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo ($booking['booking_status'] == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="completed" <?php echo ($booking['booking_status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo ($booking['booking_status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                              </select>
                              <span class="status-indicator ml-1"></span>
                            </div>
                          </form>
                        </div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">
                          <?php echo htmlspecialchars($booking['airline_name']); ?>
                        </div>
                        <div class="text-sm text-gray-600">
                          Flight #<?php echo htmlspecialchars($booking['flight_number']); ?>
                        </div>
                        <div class="text-sm text-gray-600 mt-1">
                          <?php echo htmlspecialchars($booking['departure_city']); ?> → <?php echo htmlspecialchars($booking['arrival_city']); ?>
                        </div>
                        <div class="text-sm text-gray-500 mt-1">
                          <?php echo date('M d, Y', strtotime($booking['departure_date'])); ?> at
                          <?php echo date('h:i A', strtotime($booking['departure_time'])); ?>
                        </div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">
                          <?php echo htmlspecialchars($booking['passenger_name']); ?>
                        </div>
                        <div class="text-sm text-gray-500">
                          <?php echo htmlspecialchars($booking['passenger_email']); ?>
                        </div>
                        <div class="text-sm text-gray-500">
                          <?php echo htmlspecialchars($booking['passenger_phone']); ?>
                        </div>
                        <div class="text-sm text-gray-600 mt-1">
                          <span class="font-medium">Adults:</span> <?php echo $booking['adult_count']; ?> |
                          <span class="font-medium">Children:</span> <?php echo $booking['children_count']; ?>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                          <span class="font-medium">Booked by:</span>
                          <?php echo htmlspecialchars($booking['user_full_name'] ?? 'Unknown'); ?>
                        </div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="text-sm text-gray-600">
                          <?php echo $seats_display; ?>
                        </div>
                        <div class="text-sm text-gray-500 mt-1">
                          <span class="font-medium">Total Seats:</span> <?php echo $booking['adult_count'] + $booking['children_count']; ?>
                        </div>
                        <div class="text-sm font-medium text-teal-600 mt-1">
                          <span class="font-medium">Price:</span> $<?php echo number_format($booking['price'], 2); ?>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="flight-booking-details.php?id=<?php echo $booking['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                          <i class="fas fa-eye"></i> View
                        </a>
                        <!-- <a href="edit-booking.php?id=<?php echo $booking['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                          <i class="fas fa-edit"></i> Edit
                        </a> -->
                        <a href="booked-flight.php?delete_id=<?php echo $booking['id']; ?>" class="text-red-600 hover:text-red-900"
                          onclick="return confirm('Are you sure you want to delete this booking? This action cannot be undone.');">
                          <i class="fas fa-trash-alt"></i> Delete
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

      // Status update handling
      const statusForms = document.querySelectorAll('.status-update-form');
      statusForms.forEach(form => {
        const select = form.querySelector('.status-select');
        const indicator = form.querySelector('.status-indicator');

        select.addEventListener('change', function() {
          // Show loading spinner
          indicator.innerHTML = '<i class="fas fa-spinner fa-spin text-gray-500"></i>';

          // Change select background based on selected status
          const selectedStatus = this.value;
          select.className = select.className.replace(/bg-\w+-100/g, '');

          switch (selectedStatus) {
            case 'pending':
              select.classList.add('bg-yellow-100');
              break;
            case 'confirmed':
              select.classList.add('bg-green-100');
              break;
            case 'completed':
              select.classList.add('bg-blue-100');
              break;
            case 'cancelled':
              select.classList.add('bg-red-100');
              break;
          }

          // Submit the form
          setTimeout(() => form.submit(), 300);
        });
      });
    });
  </script>
</body>

</html>