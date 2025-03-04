<?php
  session_start();
  include '../connection/connection.php';

  // Verify admin is logged in
  if (!isset($_SESSION['admin_email'])) {
    header("Location: login.php");
    exit();
  }

  // Delete flight if requested
  if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    try {
      $delete_sql = "DELETE FROM flights WHERE id = ?";
      $delete_stmt = $conn->prepare($delete_sql);
      $delete_stmt->bind_param("i", $delete_id);

      if ($delete_stmt->execute()) {
        $success_message = "Flight deleted successfully.";
      } else {
        $error_message = "Error deleting flight.";
      }

      $delete_stmt->close();
    } catch (Exception $e) {
      $error_message = "Database error: " . $e->getMessage();
    }
  }

  // Fetch all flights
  $flights = [];
  try {
    $sql = "SELECT * FROM flights ORDER BY departure_date DESC";
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

                      // Handle the stops data properly
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
                      ?>
                      <tr class="flight-row hover:bg-gray-50">
                        <td class="px-6 py-4">
                          <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($flight['airline_name']); ?></div>
                          <div class="text-sm text-gray-500"><?php echo htmlspecialchars($flight['flight_number']); ?></div>
                        </td>
                        <td class="px-6 py-4">
                          <div class="text-sm text-gray-900">
                            <?php echo htmlspecialchars($flight['departure_city']); ?> → <?php echo htmlspecialchars($flight['arrival_city']); ?>
                          </div>
                          <!-- Flight type display -->
                          <?php if ($is_direct): ?>
                            <div class="text-xs text-gray-500 mt-1">
                              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-plane mr-1"></i> Direct Flight
                              </span>
                            </div>
                          <?php elseif (!empty($stops)): ?>
                            <div class="text-xs text-gray-500 mt-1">
                              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                <i class="fas fa-plane-arrival mr-1"></i> <?php echo count($stops); ?> stop<?php echo count($stops) > 1 ? 's' : ''; ?>
                              </span>
                            </div>
                          <?php endif; ?>

                          <!-- Visual representation of flight route with dots - NOW CONSISTENT FOR BOTH DIRECT AND STOPS -->
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
                        </td>
                        <td class="px-6 py-4">
                          <div class="text-sm text-gray-900">
                            <?php echo date('M d, Y', strtotime($flight['departure_date'])); ?>
                          </div>
                          <div class="text-sm text-gray-500">
                            <?php echo date('h:i A', strtotime($flight['departure_time'])); ?>
                          </div>
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
                            onclick="return confirm('Are you sure you want to delete this flight?');">
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