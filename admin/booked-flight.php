<?php
session_start();
include '../connection/connection.php';

// Verify admin is logged in
if (!isset($_SESSION['admin_email'])) {
  header("Location: login.php");
  exit();
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

// Prepare base query
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

// Combine clauses if any exist
if (!empty($where_clauses)) {
  $query .= " WHERE " . implode(" AND ", $where_clauses);
}

// Order by most recent first
$query .= " ORDER BY fb.booking_date DESC";

// Fetch all bookings with filters
$bookings = [];
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
    }
  }

  $stmt->close();
} catch (Exception $e) {
  $error_message = "Database error: " . $e->getMessage();
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <style>
    .booking-row:hover {
      background-color: #f0f9ff;
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

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
          <div class="p-4 bg-gray-50 border-b">
            <h2 class="text-lg font-bold text-gray-800">Filter Bookings</h2>
          </div>
          <div class="p-4">
            <form method="GET" action="booked-flight.php" class="flex flex-wrap gap-4">
              <!-- Flight Filter -->
              <div class="w-full md:w-1/4">
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
              <div class="w-full md:w-1/4">
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
              <div class="w-full md:w-1/4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Cabin Class</label>
                <select name="cabin_class" class="w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500">
                  <option value="">All Classes</option>
                  <option value="economy" <?php echo ($filter_cabin_class == 'economy') ? 'selected' : ''; ?>>Economy</option>
                  <option value="business" <?php echo ($filter_cabin_class == 'business') ? 'selected' : ''; ?>>Business</option>
                  <option value="first_class" <?php echo ($filter_cabin_class == 'first_class') ? 'selected' : ''; ?>>First Class</option>
                </select>
              </div>

              <!-- Filter Buttons -->
              <div class="w-full md:w-1/4 flex items-end">
                <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 mr-2">
                  <i class="fas fa-filter mr-2"></i> Filter
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
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seats</th>
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
                    $seats_display = is_array($seats_array) ? implode(', ', $seats_array) : 'No seat assigned';
                    ?>
                    <tr class="booking-row hover:bg-gray-50">
                      <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                          <span class="font-medium">Booking ID:</span> #<?php echo $booking['id']; ?>
                        </div>
                        <div class="text-sm text-gray-500">
                          <span class="font-medium">Date:</span> <?php echo date('M d, Y H:i', strtotime($booking['booking_date'])); ?>
                        </div>
                        <div class="text-sm text-teal-600 font-medium">
                          <?php echo $cabin_class_display; ?> Class
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
                        <div class="text-xs text-gray-500 mt-1">
                          Total seats: <?php echo $booking['adult_count'] + $booking['children_count']; ?>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="view-bookings.php?delete_id=<?php echo $booking['id']; ?>" class="text-red-600 hover:text-red-900"
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

  <?php include '../includes/js-links.php'; ?>
</body>

</html>