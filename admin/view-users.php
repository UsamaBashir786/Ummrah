<?php
session_name("admin_session");
session_start();
include '../connection/connection.php';

// Verify admin is logged in
if (!isset($_SESSION['admin_email'])) {
  header("Location: login.php");
  exit();
}

// Initialize variables for filtering
$filter_name = isset($_GET['name']) ? $_GET['name'] : '';
$filter_email = isset($_GET['email']) ? $_GET['email'] : '';
$filter_flight = isset($_GET['flight']) ? $_GET['flight'] : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Handle user deletion
if (isset($_GET['delete_id'])) {
  $delete_id = intval($_GET['delete_id']);
  
  try {
    // Check if user has any bookings
    $check_sql = "SELECT COUNT(*) as booking_count FROM flight_bookings WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $delete_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    $booking_count = $row['booking_count'];
    $check_stmt->close();

    if ($booking_count > 0) {
      $error_message = "Cannot delete user with active bookings. This user has {$booking_count} booking(s).";
    } else {
      // Delete user
      $delete_sql = "DELETE FROM users WHERE id = ?";
      $delete_stmt = $conn->prepare($delete_sql);
      $delete_stmt->bind_param("i", $delete_id);
      
      if ($delete_stmt->execute()) {
        $success_message = "User deleted successfully.";
      } else {
        throw new Exception("Failed to delete user: " . $delete_stmt->error);
      }
      
      $delete_stmt->close();
    }
  } catch (Exception $e) {
    $error_message = "Error: " . $e->getMessage();
  }
}

// Base SQL for fetching passengers
$sql = "SELECT 
          u.id, 
          u.full_name, 
          u.email, 
          u.phone_number, 
          u.date_of_birth,
          u.city,
          u.created_at as join_date,
          COUNT(fb.id) as booking_count,
          MAX(fb.booking_date) as last_booking_date,
          SUM(fb.price) as total_spent
        FROM users u
        LEFT JOIN flight_bookings fb ON u.id = fb.user_id
        WHERE 1=1 ";

$params = [];
$param_types = "";

// Apply filters
if (!empty($filter_name)) {
  $sql .= "AND u.full_name LIKE ? ";
  $params[] = "%" . $filter_name . "%";
  $param_types .= "s";
}

if (!empty($filter_email)) {
  $sql .= "AND u.email LIKE ? ";
  $params[] = "%" . $filter_email . "%";
  $param_types .= "s";
}

if (!empty($filter_flight)) {
  $sql .= "AND fb.flight_id = ? ";
  $params[] = $filter_flight;
  $param_types .= "i";
}

if (!empty($filter_date_from)) {
  $sql .= "AND fb.booking_date >= ? ";
  $params[] = $filter_date_from;
  $param_types .= "s";
}

if (!empty($filter_date_to)) {
  $sql .= "AND fb.booking_date <= ? ";
  $params[] = $filter_date_to;
  $param_types .= "s";
}

if (!empty($filter_status)) {
  $sql .= "AND fb.booking_status = ? ";
  $params[] = $filter_status;
  $param_types .= "s";
}

$sql .= "GROUP BY u.id ORDER BY booking_count DESC, last_booking_date DESC";

// Execute the query
$passengers = [];
try {
  $stmt = $conn->prepare($sql);
  
  if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
  }
  
  $stmt->execute();
  $result = $stmt->get_result();
  
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $passengers[] = $row;
    }
  }
  
  $stmt->close();
} catch (Exception $e) {
  $error_message = "Database error: " . $e->getMessage();
}

// Get unique flights for filter dropdown
$flights_sql = "SELECT id, flight_number, departure_city, arrival_city FROM flights ORDER BY departure_date DESC";
$flights_result = $conn->query($flights_sql);
$flights = [];

if ($flights_result && $flights_result->num_rows > 0) {
  while ($row = $flights_result->fetch_assoc()) {
    $flights[] = $row;
  }
}

// Helper function to format dates
function formatDate($date_string, $format = 'M d, Y') {
  if (empty($date_string)) return 'N/A';
  $date = new DateTime($date_string);
  return $date->format($format);
}

// Helper function to calculate age from date of birth
function calculateAge($dob) {
  if (empty($dob)) return 'N/A';
  $dob = new DateTime($dob);
  $now = new DateTime();
  $interval = $now->diff($dob);
  return $interval->y;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <style>
    .passenger-card {
      transition: all 0.3s ease;
      border-left: 4px solid transparent;
    }
    
    .passenger-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    
    .passenger-avatar {
      width: 60px;
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      font-size: 24px;
      font-weight: bold;
    }
    
    .booking-badge {
      font-size: 0.75rem;
      padding: 0.25rem 0.5rem;
    }
    
    .stats-card {
      border-radius: 0.5rem;
      transition: all 0.3s ease;
    }
    
    .stats-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    .filter-form {
      background-color: #f8fafc;
      border-radius: 0.5rem;
    }
    
    .tabs .tab {
      padding: 0.75rem 1rem;
      border-bottom: 3px solid transparent;
      cursor: pointer;
    }
    
    .tabs .tab.active {
      border-bottom-color: #0d9488;
      color: #0d9488;
      font-weight: 600;
    }
    
    .tabs .tab:hover:not(.active) {
      border-bottom-color: #e2e8f0;
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
          <i class="text-teal-600 fas fa-users mx-2"></i> Passenger Management
        </h1>
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

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
          <!-- Total Passengers -->
          <div class="stats-card bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
              <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                <i class="fas fa-users text-2xl"></i>
              </div>
              <div>
                <h3 class="text-sm text-gray-500 uppercase">Total Passengers</h3>
                <p class="text-2xl font-bold text-gray-800"><?php echo count($passengers); ?></p>
              </div>
            </div>
          </div>
          
          <!-- Active Bookers -->
          <div class="stats-card bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
              <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                <i class="fas fa-ticket-alt text-2xl"></i>
              </div>
              <div>
                <h3 class="text-sm text-gray-500 uppercase">Active Bookers</h3>
                <p class="text-2xl font-bold text-gray-800">
                  <?php 
                    $active_bookers = array_filter($passengers, function($p) { return $p['booking_count'] > 0; });
                    echo count($active_bookers);
                  ?>
                </p>
                <p class="text-xs text-gray-500 mt-1"><?php echo round(count($active_bookers) / max(1, count($passengers)) * 100); ?>% of all users</p>
              </div>
            </div>
          </div>
          
          <!-- Total Bookings -->
          <div class="stats-card bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
              <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                <i class="fas fa-plane text-2xl"></i>
              </div>
              <div>
                <h3 class="text-sm text-gray-500 uppercase">Total Bookings</h3>
                <p class="text-2xl font-bold text-gray-800">
                  <?php 
                    $total_bookings = array_sum(array_column($passengers, 'booking_count'));
                    echo $total_bookings;
                  ?>
                </p>
              </div>
            </div>
          </div>
          
          <!-- Total Revenue -->
          <div class="stats-card bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
              <div class="p-3 rounded-full bg-amber-100 text-amber-600 mr-4">
                <i class="fas fa-dollar-sign text-2xl"></i>
              </div>
              <div>
                <h3 class="text-sm text-gray-500 uppercase">Total Revenue</h3>
                <p class="text-2xl font-bold text-gray-800">
                  $<?php 
                    $total_revenue = array_sum(array_column($passengers, 'total_spent'));
                    echo number_format($total_revenue, 2);
                  ?>
                </p>
                <p class="text-xs text-gray-500 mt-1">$<?php echo number_format($total_revenue / max(1, $total_bookings), 2); ?> avg per booking</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
          <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
            <h2 class="text-lg font-bold text-gray-800">Filter Passengers</h2>
            <?php if (!empty($filter_name) || !empty($filter_email) || !empty($filter_flight) || !empty($filter_date_from) || !empty($filter_date_to) || !empty($filter_status)): ?>
              <a href="view-users.php" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-times-circle mr-1"></i> Clear Filters
              </a>
            <?php endif; ?>
          </div>

          <form action="" method="GET" class="p-4 filter-form grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Name Filter -->
            <div class="form-group">
              <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Passenger Name</label>
              <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($filter_name); ?>" 
                     class="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 focus:ring-opacity-50">
            </div>
            
            <!-- Email Filter -->
            <div class="form-group">
              <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <input type="text" name="email" id="email" value="<?php echo htmlspecialchars($filter_email); ?>" 
                     class="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 focus:ring-opacity-50">
            </div>
            
       <!-- Flight Filter -->
<div class="form-group">
  <label for="flight" class="block text-sm font-medium text-gray-700 mb-1">Flight</label>
  <select name="flight" id="flight" class="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 focus:ring-opacity-50">
    <option value="">All Flights</option>
    <?php foreach ($flights as $flight): ?>
      <option value="<?php echo $flight['id']; ?>" <?php echo $filter_flight == $flight['id'] ? 'selected' : ''; ?>>
        <?php echo htmlspecialchars($flight['flight_number'] . ' (' . $flight['departure_city'] . ' → ' . $flight['arrival_city'] . ')'); ?>
      </option>
    <?php endforeach; ?>
  </select>
</div>
            
            <!-- Booking Status Filter -->
            <div class="form-group">
              <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Booking Status</label>
              <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 focus:ring-opacity-50">
                <option value="">All Statuses</option>
                <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="confirmed" <?php echo $filter_status == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
              </select>
            </div>
            
            <!-- Date Range Filters -->
            <div class="form-group">
              <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Booking Date From</label>
              <input type="date" name="date_from" id="date_from" value="<?php echo $filter_date_from; ?>" 
                     class="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 focus:ring-opacity-50">
            </div>
            
            <div class="form-group">
              <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Booking Date To</label>
              <input type="date" name="date_to" id="date_to" value="<?php echo $filter_date_to; ?>" 
                     class="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 focus:ring-opacity-50">
            </div>
            
            <!-- Submit Button -->
            <div class="form-group lg:col-span-4 flex justify-end">
              <button type="submit" class="inline-flex items-center px-4 py-2 bg-teal-600 border border-transparent rounded-md font-semibold text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                <i class="fas fa-filter mr-2"></i> Apply Filters
              </button>
            </div>
          </form>
        </div>

        <!-- Passengers List -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
          <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
            <h2 class="text-lg font-bold text-gray-800">Passenger List</h2>
            <div class="text-sm text-gray-500">
              Showing <?php echo count($passengers); ?> passenger<?php echo count($passengers) != 1 ? 's' : ''; ?>
            </div>
          </div>

          <?php if (empty($passengers)): ?>
            <div class="p-8 text-center text-gray-500">
              <i class="fas fa-users-slash text-4xl mb-4 text-gray-300"></i>
              <p class="text-lg">No passengers found matching your criteria</p>
            </div>
          <?php else: ?>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Passenger</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bookings</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Booking</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Spent</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <?php foreach ($passengers as $passenger): ?>
                    <tr class="hover:bg-gray-50">
                      <!-- Passenger Column -->
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                          <div class="passenger-avatar bg-teal-100 text-teal-800 mr-3">
                            <?php echo strtoupper(substr($passenger['full_name'], 0, 1)); ?>
                          </div>
                          <div>
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($passenger['full_name']); ?></div>
                            <div class="text-xs text-gray-500">
                              <?php echo calculateAge($passenger['date_of_birth']); ?> years
                              <?php if (!empty($passenger['city'])): ?>
                                | <?php echo htmlspecialchars($passenger['city']); ?>
                              <?php endif; ?>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                              Joined: <?php echo formatDate($passenger['join_date']); ?>
                            </div>
                          </div>
                        </div>
                      </td>
                      
                      <!-- Contact Column -->
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($passenger['email']); ?></div>
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($passenger['phone_number']); ?></div>
                      </td>
                      
                      <!-- Bookings Column -->
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                          <?php echo $passenger['booking_count'] > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                          <?php echo $passenger['booking_count']; ?> booking<?php echo $passenger['booking_count'] != 1 ? 's' : ''; ?>
                        </span>
                      </td>
                      
                      <!-- Last Booking Column -->
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo !empty($passenger['last_booking_date']) ? formatDate($passenger['last_booking_date']) : 'Never booked'; ?>
                      </td>
                      
                      <!-- Total Spent Column -->
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        $<?php echo number_format($passenger['total_spent'] ?? 0, 2); ?>
                      </td>
                      
                      <!-- Actions Column -->
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="user-details.php?id=<?php echo $passenger['id']; ?>" class="text-teal-600 hover:text-teal-900 mr-3">
                          <i class="fas fa-eye mr-1"></i> View
                        </a>
                        <a href="view-users.php?delete_id=<?php echo $passenger['id']; ?>" class="text-red-600 hover:text-red-900"
                          onclick="return confirm('Are you sure you want to delete this passenger? This action cannot be undone.');">
                          <i class="fas fa-trash-alt mr-1"></i> Delete
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/js-links.php'; ?>

  <script>
    // Initialize any JavaScript functionality here
    document.addEventListener('DOMContentLoaded', function() {
      // Add any initialization code here
    });
  </script>
</body>

</html>