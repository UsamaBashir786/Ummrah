<?php
session_start();
include '../connection/connection.php';

// Verify admin is logged in
if (!isset($_SESSION['admin_email'])) {
  header("Location: login.php");
  exit();
}

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

// Fetch all flights
$flights = [];
try {
  // Join with flight_bookings to get booking count for each flight
  $sql = "SELECT f.*, COUNT(fb.id) as booking_count 
          FROM flights f 
          LEFT JOIN flight_bookings fb ON f.id = fb.flight_id 
          GROUP BY f.id 
          ORDER BY f.departure_date DESC";
  $result = $conn->query($sql);

  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $flights[] = $row;
    }
  }
} catch (Exception $e) {
  $error_message = "Database error: " . $e->getMessage();
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
                          <?php echo date('M d, Y', strtotime($flight['departure_date'])); ?>
                          <span class="text-xs ml-2"><?php echo date('h:i A', strtotime($flight['departure_time'])); ?></span>
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
                            <?php echo date('M d, Y', strtotime($return_flight_data['return_date'])); ?>
                            <?php if (!empty($return_flight_data['return_time'])): ?>
                              <span class="text-xs ml-2"><?php echo date('h:i A', strtotime($return_flight_data['return_time'])); ?></span>
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

  <?php include '../includes/js-links.php'; ?>
</body>

</html>