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
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_class = isset($_GET['class']) ? $_GET['class'] : '';
$filter_airline = isset($_GET['airline']) ? $_GET['airline'] : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$filter_destination = isset($_GET['destination']) ? $_GET['destination'] : '';

// Handle booking status updates
if (isset($_GET['update_status']) && !empty($_GET['booking_id'])) {
  $booking_id = intval($_GET['booking_id']);
  $new_status = $_GET['update_status'];

  // List of valid statuses
  $valid_statuses = ['pending', 'confirmed', 'cancelled', 'completed'];

  if (in_array($new_status, $valid_statuses)) {
    try {
      $update_sql = "UPDATE flight_bookings SET booking_status = ? WHERE id = ?";
      $update_stmt = $conn->prepare($update_sql);
      $update_stmt->bind_param("si", $new_status, $booking_id);

      if ($update_stmt->execute()) {
        $success_message = "Booking status updated successfully.";
      } else {
        $error_message = "Failed to update booking status.";
      }

      $update_stmt->close();
    } catch (Exception $e) {
      $error_message = "Error: " . $e->getMessage();
    }
  } else {
    $error_message = "Invalid status provided.";
  }
}

// Handle booking deletion
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
  $delete_id = intval($_GET['delete_id']);

  try {
    $delete_sql = "DELETE FROM flight_bookings WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $delete_id);

    if ($delete_stmt->execute()) {
      $success_message = "Booking deleted successfully.";
    } else {
      $error_message = "Failed to delete booking.";
    }

    $delete_stmt->close();
  } catch (Exception $e) {
    $error_message = "Error: " . $e->getMessage();
  }
}

// Build the SQL query with filters
$sql = "SELECT 
        fb.*, 
        f.flight_number,
        f.airline_name,
        f.departure_city,
        f.arrival_city,
        f.departure_date,
        f.departure_time
        FROM flight_bookings fb
        JOIN flights f ON fb.flight_id = f.id
        WHERE 1=1 ";

$params = [];
$param_types = "";

if (!empty($filter_status)) {
  $sql .= "AND fb.booking_status = ? ";
  $params[] = $filter_status;
  $param_types .= "s";
}

if (!empty($filter_class)) {
  $sql .= "AND fb.cabin_class = ? ";
  $params[] = $filter_class;
  $param_types .= "s";
}

if (!empty($filter_airline)) {
  $sql .= "AND f.airline_name = ? ";
  $params[] = $filter_airline;
  $param_types .= "s";
}

if (!empty($filter_date_from)) {
  $sql .= "AND fb.booking_date >= ? ";
  $params[] = $filter_date_from . ' 00:00:00';
  $param_types .= "s";
}

if (!empty($filter_date_to)) {
  $sql .= "AND fb.booking_date <= ? ";
  $params[] = $filter_date_to . ' 23:59:59';
  $param_types .= "s";
}

if (!empty($filter_destination)) {
  $sql .= "AND f.arrival_city = ? ";
  $params[] = $filter_destination;
  $param_types .= "s";
}

$sql .= "ORDER BY fb.booking_date DESC";

// Fetch all bookings with the applied filters
$bookings = [];

try {
  $stmt = $conn->prepare($sql);

  if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
  }

  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $bookings[] = $row;
    }
  }

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

// Get booking statistics
$booking_stats = [
  'total' => 0,
  'pending' => 0,
  'confirmed' => 0,
  'cancelled' => 0,
  'completed' => 0,
  'total_value' => 0
];

$class_stats = [
  'economy' => 0,
  'business' => 0,
  'first_class' => 0
];

foreach ($bookings as $booking) {
  $booking_stats['total']++;
  $booking_stats[$booking['booking_status']]++;
  $booking_stats['total_value'] += $booking['price'];

  $class = strtolower(str_replace(' ', '_', $booking['cabin_class']));
  $class_stats[$class]++;
}

$airlines = getUniqueValues($conn, 'flights', 'airline_name');
$destinations = getUniqueValues($conn, 'flights', 'arrival_city');

// Helper function to format dates
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
    .booking-row:hover {
      background-color: #f0f9ff;
    }

    .status-badge {
      display: inline-block;
      padding: 0.25rem 0.5rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: capitalize;
    }

    .status-pending {
      background-color: #fef3c7;
      color: #92400e;
    }

    .status-confirmed {
      background-color: #d1fae5;
      color: #065f46;
    }

    .status-cancelled {
      background-color: #fee2e2;
      color: #b91c1c;
    }

    .status-completed {
      background-color: #dbeafe;
      color: #1e40af;
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

    .passenger-badge {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      margin-right: 0.75rem;
    }

    .dropdown-menu {
      display: none;
      position: absolute;
      right: 0;
      top: 100%;
      z-index: 50;
      min-width: 12rem;
      background-color: white;
      border-radius: 0.375rem;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .dropdown-menu.show {
      display: block;
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
          <i class="text-teal-600 fas fa-ticket-alt mx-2"></i> Booking Management
        </h1>
        <a href="view-flight.php" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700">
          <i class="fas fa-plane-departure mr-2"></i> View Flights
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
          </div>
        <?php endif; ?>

        <!-- Summary Cards Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
          <!-- Total Bookings Card -->
          <div class="summary-card p-6">
            <div class="flex flex-col">
              <h3 class="text-sm text-gray-500 uppercase">Total Bookings</h3>
              <p class="text-2xl font-bold text-gray-800"><?php echo $booking_stats['total']; ?></p>
              <p class="text-sm text-gray-500 mt-1">
                <span class="text-green-600">
                  <i class="fas fa-dollar-sign"></i> $<?php echo number_format($booking_stats['total_value'], 2); ?>
                </span> total value
              </p>
            </div>
          </div>

          <!-- Pending Bookings Card -->
          <div class="summary-card p-6">
            <div class="flex flex-col">
              <h3 class="text-sm text-gray-500 uppercase">Pending</h3>
              <p class="text-2xl font-bold text-yellow-600"><?php echo $booking_stats['pending']; ?></p>
              <p class="text-sm text-gray-500 mt-1">
                <span class="text-yellow-600">
                  <i class="fas fa-clock"></i>
                </span> awaiting confirmation
              </p>
            </div>
          </div>

          <!-- Confirmed Bookings Card -->
          <div class="summary-card p-6">
            <div class="flex flex-col">
              <h3 class="text-sm text-gray-500 uppercase">Confirmed</h3>
              <p class="text-2xl font-bold text-green-600"><?php echo $booking_stats['confirmed']; ?></p>
              <p class="text-sm text-gray-500 mt-1">
                <span class="text-green-600">
                  <i class="fas fa-check-circle"></i>
                </span> ready for travel
              </p>
            </div>
          </div>

          <!-- Cancelled Bookings Card -->
          <div class="summary-card p-6">
            <div class="flex flex-col">
              <h3 class="text-sm text-gray-500 uppercase">Cancelled</h3>
              <p class="text-2xl font-bold text-red-600"><?php echo $booking_stats['cancelled']; ?></p>
              <p class="text-sm text-gray-500 mt-1">
                <span class="text-red-600">
                  <i class="fas fa-ban"></i>
                </span> no longer active
              </p>
            </div>
          </div>

          <!-- Completed Bookings Card -->
          <div class="summary-card p-6">
            <div class="flex flex-col">
              <h3 class="text-sm text-gray-500 uppercase">Completed</h3>
              <p class="text-2xl font-bold text-blue-600"><?php echo $booking_stats['completed']; ?></p>
              <p class="text-sm text-gray-500 mt-1">
                <span class="text-blue-600">
                  <i class="fas fa-check-double"></i>
                </span> journey finished
              </p>
            </div>
          </div>
        </div>

        <!-- Class Breakdown Section -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
          <div class="p-4 bg-gray-50 border-b">
            <h2 class="text-lg font-bold text-gray-800">Booking Class Breakdown</h2>
          </div>
          <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Economy Class -->
            <div class="bg-blue-50 p-4 rounded-lg">
              <div class="flex justify-between items-center">
                <h3 class="text-blue-800 font-bold">Economy</h3>
                <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-800 text-sm font-semibold">
                  <?php echo $class_stats['economy']; ?> bookings
                </span>
              </div>
              <div class="mt-2 h-2 bg-blue-200 rounded-full overflow-hidden">
                <div class="h-full bg-blue-600 rounded-full" style="width: <?php echo $booking_stats['total'] > 0 ? ($class_stats['economy'] / $booking_stats['total'] * 100) : 0; ?>%"></div>
              </div>
              <p class="text-sm text-gray-600 mt-1">
                <?php echo $booking_stats['total'] > 0 ? round(($class_stats['economy'] / $booking_stats['total'] * 100)) : 0; ?>% of total bookings
              </p>
            </div>

            <!-- Business Class -->
            <div class="bg-green-50 p-4 rounded-lg">
              <div class="flex justify-between items-center">
                <h3 class="text-green-800 font-bold">Business</h3>
                <span class="px-3 py-1 rounded-full bg-green-100 text-green-800 text-sm font-semibold">
                  <?php echo $class_stats['business']; ?> bookings
                </span>
              </div>
              <div class="mt-2 h-2 bg-green-200 rounded-full overflow-hidden">
                <div class="h-full bg-green-600 rounded-full" style="width: <?php echo $booking_stats['total'] > 0 ? ($class_stats['business'] / $booking_stats['total'] * 100) : 0; ?>%"></div>
              </div>
              <p class="text-sm text-gray-600 mt-1">
                <?php echo $booking_stats['total'] > 0 ? round(($class_stats['business'] / $booking_stats['total'] * 100)) : 0; ?>% of total bookings
              </p>
            </div>

            <!-- First Class -->
            <div class="bg-purple-50 p-4 rounded-lg">
              <div class="flex justify-between items-center">
                <h3 class="text-purple-800 font-bold">First Class</h3>
                <span class="px-3 py-1 rounded-full bg-purple-100 text-purple-800 text-sm font-semibold">
                  <?php echo $class_stats['first_class']; ?> bookings
                </span>
              </div>
              <div class="mt-2 h-2 bg-purple-200 rounded-full overflow-hidden">
                <div class="h-full bg-purple-600 rounded-full" style="width: <?php echo $booking_stats['total'] > 0 ? ($class_stats['first_class'] / $booking_stats['total'] * 100) : 0; ?>%"></div>
              </div>
              <p class="text-sm text-gray-600 mt-1">
                <?php echo $booking_stats['total'] > 0 ? round(($class_stats['first_class'] / $booking_stats['total'] * 100)) : 0; ?>% of total bookings
              </p>
            </div>
          </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
          <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
            <h2 class="text-lg font-bold text-gray-800">Filter Bookings</h2>
            <?php if (!empty($filter_status) || !empty($filter_class) || !empty($filter_airline) || !empty($filter_date_from) || !empty($filter_date_to) || !empty($filter_destination)): ?>
              <a href="view-bookings.php" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-times-circle mr-1"></i> Clear Filters
              </a>
            <?php endif; ?>
          </div>

          <form action="" method="GET" class="p-4 filter-form grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <!-- Status Filter -->
            <div class="form-group">
              <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
              <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 focus:ring-opacity-50">
                <option value="">All Statuses</option>
                <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="confirmed" <?php echo $filter_status == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
              </select>
            </div>

            <!-- Class Filter -->
            <div class="form-group">
              <label for="class" class="block text-sm font-medium text-gray-700 mb-1">Cabin Class</label>
              <select name="class" id="class" class="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 focus:ring-opacity-50">
                <option value="">All Classes</option>
                <option value="economy" <?php echo $filter_class == 'economy' ? 'selected' : ''; ?>>Economy</option>
                <option value="business" <?php echo $filter_class == 'business' ? 'selected' : ''; ?>>Business</option>
                <option value="first_class" <?php echo $filter_class == 'first_class' ? 'selected' : ''; ?>>First Class</option>
              </select>
            </div>

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

            <!-- Destination Filter -->
            <div class="form-group">
              <label for="destination" class="block text-sm font-medium text-gray-700 mb-1">Destination</label>
              <select name="destination" id="destination" class="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 focus:ring-opacity-50">
                <option value="">All Destinations</option>
                <?php foreach ($destinations as $destination): ?>
                  <option value="<?php echo htmlspecialchars($destination); ?>" <?php echo $filter_destination == $destination ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($destination); ?>
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

            <!-- Submit Button -->
            <div class="form-group lg:col-span-6 mt-2 flex justify-end">
              <button type="submit" class="inline-flex items-center px-4 py-2 bg-teal-600 border border-transparent rounded-md font-semibold text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                <i class="fas fa-filter mr-2"></i> Apply Filters
              </button>
            </div>
          </form>
        </div>

        <!-- Bookings Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
          <div class="p-6 bg-gray-50 border-b flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800">Booking List</h2>
            <span class="text-gray-500">
              <?php echo count($bookings); ?> booking<?php echo count($bookings) != 1 ? 's' : ''; ?> found
            </span>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking Info</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Passenger</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Flight Details</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($bookings)): ?>
                  <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No bookings found.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($bookings as $booking): ?>
                    <tr class="booking-row hover:bg-gray-50">
                      <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">Booking #<?php echo $booking['id']; ?></div>
                        <div class="text-xs text-gray-500">
                          <i class="far fa-calendar-alt mr-1"></i> <?php echo formatDate($booking['booking_date']); ?>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                          <i class="fas fa-couch mr-1"></i>
                          <span class="capitalize"><?php echo htmlspecialchars($booking['cabin_class']); ?></span> Class
                        </div>
                        <?php if (!empty($booking['seat_id'])): ?>
                          <div class="text-xs mt-1">
                            <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 font-medium">
                              Seat: <?php echo htmlspecialchars($booking['seat_id']); ?>
                            </span>
                          </div>
                        <?php endif; ?>
                      </td>

                      <td class="px-6 py-4">
                        <div class="flex items-center">
                          <div class="passenger-badge bg-teal-100 text-teal-800">
                            <?php echo strtoupper(substr($booking['passenger_name'], 0, 1)); ?>
                          </div>
                          <div>
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($booking['passenger_name']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($booking['passenger_email']); ?></div>
                            <?php if (!empty($booking['passenger_phone'])): ?>
                              <div class="text-xs text-gray-500"><?php echo htmlspecialchars($booking['passenger_phone']); ?></div>
                            <?php endif; ?>
                          </div>
                        </div>
                        <?php if ($booking['adult_count'] > 1 || $booking['children_count'] > 0): ?>
                          <div class="mt-2 text-xs text-gray-600">
                            <?php echo $booking['adult_count']; ?> Adult<?php echo $booking['adult_count'] > 1 ? 's' : ''; ?>
                            <?php if ($booking['children_count'] > 0): ?>
                              , <?php echo $booking['children_count']; ?> Child<?php echo $booking['children_count'] > 1 ? 'ren' : ''; ?>
                            <?php endif; ?>
                          </div>
                        <?php endif; ?>
                      </td>

                      <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">
                          <i class="fas fa-plane text-gray-500 mr-1"></i>
                          <?php echo htmlspecialchars($booking['airline_name']); ?>
                          <?php echo htmlspecialchars($booking['flight_number']); ?>
                        </div>
                        <div class="text-sm text-gray-600 mt-1">
                          <?php echo htmlspecialchars($booking['departure_city']); ?> →
                          <?php echo htmlspecialchars($booking['arrival_city']); ?>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                          <i class="far fa-calendar-alt mr-1"></i>
                          <?php echo formatDate($booking['departure_date']); ?>
                          <span class="ml-1">
                            <i class="far fa-clock mr-1"></i>
                            <?php echo date('h:i A', strtotime($booking['departure_time'])); ?>
                          </span>
                        </div>
                      </td>

                      <td class="px-6 py-4">
                        <span class="status-badge status-<?php echo $booking['booking_status']; ?>">
                          <?php echo ucfirst($booking['booking_status']); ?>
                        </span>
                      </td>

                      <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">$<?php echo number_format($booking['price'], 2); ?></div>
                        <div class="text-sm font-medium text-gray-900">$<?php echo number_format($booking['price'], 2); ?></div>
                        <div class="text-xs text-gray-500">
                          <?php if (!empty($booking['payment_status'])): ?>
                            <span class="px-2 py-0.5 rounded <?php echo $booking['payment_status'] == 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                              <?php echo ucfirst($booking['payment_status']); ?>
                            </span>
                          <?php else: ?>
                            <span class="px-2 py-0.5 rounded bg-yellow-100 text-yellow-800">
                              Unpaid
                            </span>
                          <?php endif; ?>
                        </div>
                      </td>

                      <td class="px-6 py-4 text-right text-sm font-medium relative">
                        <button onclick="toggleDropdown('dropdown-<?php echo $booking['id']; ?>')" class="text-gray-500 hover:text-gray-700">
                          <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div id="dropdown-<?php echo $booking['id']; ?>" class="dropdown-menu">
                          <div class="py-1">
                            <!-- <a href="view-booking-details.php?id=<?php echo $booking['id']; ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                              <i class="fas fa-eye mr-2"></i> View Details
                            </a> -->

                            <!-- Status update options -->
                            <?php if ($booking['booking_status'] != 'confirmed'): ?>
                              <a href="view-bookings.php?booking_id=<?php echo $booking['id']; ?>&update_status=confirmed" class="block px-4 py-2 text-sm text-green-700 hover:bg-green-100">
                                <i class="fas fa-check-circle mr-2"></i> Mark as Confirmed
                              </a>
                            <?php endif; ?>

                            <?php if ($booking['booking_status'] != 'pending'): ?>
                              <a href="view-bookings.php?booking_id=<?php echo $booking['id']; ?>&update_status=pending" class="block px-4 py-2 text-sm text-yellow-700 hover:bg-yellow-100">
                                <i class="fas fa-clock mr-2"></i> Mark as Pending
                              </a>
                            <?php endif; ?>

                            <?php if ($booking['booking_status'] != 'completed'): ?>
                              <a href="view-bookings.php?booking_id=<?php echo $booking['id']; ?>&update_status=completed" class="block px-4 py-2 text-sm text-blue-700 hover:bg-blue-100">
                                <i class="fas fa-check-double mr-2"></i> Mark as Completed
                              </a>
                            <?php endif; ?>

                            <?php if ($booking['booking_status'] != 'cancelled'): ?>
                              <a href="view-bookings.php?booking_id=<?php echo $booking['id']; ?>&update_status=cancelled" class="block px-4 py-2 text-sm text-red-700 hover:bg-red-100">
                                <i class="fas fa-ban mr-2"></i> Mark as Cancelled
                              </a>
                            <?php endif; ?>

                            <hr class="my-1 border-gray-200">

                            <!-- Other actions -->
                            <a href="edit-booking.php?id=<?php echo $booking['id']; ?>" class="block px-4 py-2 text-sm text-blue-700 hover:bg-blue-100">
                              <i class="fas fa-edit mr-2"></i> Edit Booking
                            </a>

                            <!-- <a href="#" onclick="printBooking(<?php echo $booking['id']; ?>); return false;" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                              <i class="fas fa-print mr-2"></i> Print Ticket
                            </a> -->

                            <a href="#" onclick="if(confirm('Are you sure you want to delete this booking?')) { window.location.href='view-bookings.php?delete_id=<?php echo $booking['id']; ?>'; } return false;" class="block px-4 py-2 text-sm text-red-700 hover:bg-red-100">
                              <i class="fas fa-trash mr-2"></i> Delete Booking
                            </a>
                          </div>
                        </div>
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
    // Toggle dropdown menu
    function toggleDropdown(dropdownId) {
      const dropdown = document.getElementById(dropdownId);

      // Close all other dropdowns first
      document.querySelectorAll('.dropdown-menu').forEach(menu => {
        if (menu.id !== dropdownId && menu.classList.contains('show')) {
          menu.classList.remove('show');
        }
      });

      // Toggle the selected dropdown
      dropdown.classList.toggle('show');
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
      if (!event.target.matches('.dropdown-menu') && !event.target.closest('button') && !event.target.closest('.dropdown-menu')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
          if (menu.classList.contains('show')) {
            menu.classList.remove('show');
          }
        });
      }
    });


    // Initialize any date pickers or other components
    document.addEventListener('DOMContentLoaded', function() {
      // Add any initialization code here
    });
  </script>
</body>

</html>