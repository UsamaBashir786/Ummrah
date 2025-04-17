<?php
session_name("admin_session");
session_start();
include 'connection/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
  header("Location: admin-login.php");
  exit();
}

// Create a table for transportation settings if it doesn't exist
$create_settings_table_sql = "
CREATE TABLE IF NOT EXISTS transportation_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_type VARCHAR(20) NOT NULL,
    service_title VARCHAR(255) NOT NULL,
    year INT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($create_settings_table_sql)) {
  // Check if we need to insert default values
  $check_settings_sql = "SELECT COUNT(*) as count FROM transportation_settings WHERE service_type='taxi'";
  $result = $conn->query($check_settings_sql);
  $row = $result->fetch_assoc();

  if ($row['count'] == 0) {
    // Insert default taxi settings
    $insert_taxi_sql = "INSERT INTO transportation_settings (service_type, service_title, year) 
                           VALUES ('taxi', 'Best Taxi Service for Umrah and Hajj in Makkah, Madinah and Jeddah', 2024)";
    $conn->query($insert_taxi_sql);
  }

  $check_settings_sql = "SELECT COUNT(*) as count FROM transportation_settings WHERE service_type='rentacar'";
  $result = $conn->query($check_settings_sql);
  $row = $result->fetch_assoc();

  if ($row['count'] == 0) {
    // Insert default rentacar settings
    $insert_rentacar_sql = "INSERT INTO transportation_settings (service_type, service_title, year) 
                               VALUES ('rentacar', 'Best Umrah and Hajj Rent A Car in Makkah, Madinah and Jeddah', 2024)";
    $conn->query($insert_rentacar_sql);
  }
}

// Function to get taxi routes
function getTaxiRoutes()
{
  global $conn;
  $sql = "SELECT * FROM taxi_routes WHERE year = 2024 ORDER BY route_number";
  $result = $conn->query($sql);
  $routes = array();

  if ($result) {
    while ($row = $result->fetch_assoc()) {
      $routes[] = $row;
    }
  }

  return $routes;
}

// Function to get rent a car routes
function getRentacarRoutes()
{
  global $conn;
  $sql = "SELECT * FROM rentacar_routes WHERE year = 2024 ORDER BY route_number";
  $result = $conn->query($sql);
  $routes = array();

  if ($result) {
    while ($row = $result->fetch_assoc()) {
      $routes[] = $row;
    }
  }

  return $routes;
}

// Function to get the service title and year for taxi routes
function getTaxiServiceInfo()
{
  global $conn;
  $result = array(
    'service_title' => 'Best Taxi Service for Umrah and Hajj in Makkah, Madinah and Jeddah', // Default
    'year' => 2024 // Default
  );

  $sql = "SELECT service_title, year FROM transportation_settings WHERE service_type='taxi' LIMIT 1";
  $query = $conn->query($sql);

  if ($query && $query->num_rows > 0) {
    $data = $query->fetch_assoc();
    $result['service_title'] = $data['service_title'];
    $result['year'] = $data['year'];
  }

  return $result;
}

// Function to get the service title and year for rentacar routes
function getRentacarServiceInfo()
{
  global $conn;
  $result = array(
    'service_title' => 'Best Umrah and Hajj Rent A Car in Makkah, Madinah and Jeddah', // Default
    'year' => 2024 // Default
  );

  $sql = "SELECT service_title, year FROM transportation_settings WHERE service_type='rentacar' LIMIT 1";
  $query = $conn->query($sql);

  if ($query && $query->num_rows > 0) {
    $data = $query->fetch_assoc();
    $result['service_title'] = $data['service_title'];
    $result['year'] = $data['year'];
  }

  return $result;
}

// Handling CRUD operations
$success_message = '';
$error_message = '';

// UPDATE TAXI ROUTES
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_taxi_routes'])) {
  // Get service title and year
  $service_title = $conn->real_escape_string($_POST['serviceTitle']);
  $year = (int)$_POST['year'];

  // Update the settings table first
  $update_settings_sql = "UPDATE transportation_settings SET 
                         service_title = '$service_title', 
                         year = $year 
                         WHERE service_type = 'taxi'";
  if (!$conn->query($update_settings_sql)) {
    $error_message = "Error updating service settings: " . $conn->error;
  }

  // Clear existing routes for the year if requested
  if (isset($_POST['replace_existing']) && $_POST['replace_existing'] == 'yes') {
    $delete_sql = "DELETE FROM taxi_routes WHERE year = $year";
    if (!$conn->query($delete_sql)) {
      $error_message = "Error clearing existing routes: " . $conn->error;
    }
  }

  // Handle updates for existing routes
  if (isset($_POST['route_id']) && is_array($_POST['route_id'])) {
    foreach ($_POST['route_id'] as $key => $id) {
      if (empty($id)) continue; // Skip if no ID (means new route)

      $route_name = $conn->real_escape_string($_POST['route_name'][$key]);
      $route_number = $_POST['route_number'][$key];
      $camry_price = $_POST['camry_price'][$key];
      $starex_price = $_POST['starex_price'][$key];
      $hiace_price = $_POST['hiace_price'][$key];

      $update_sql = "UPDATE taxi_routes SET 
                     service_title = '$service_title',
                     year = $year,
                     route_name = '$route_name',
                     route_number = $route_number,
                     camry_sonata_price = $camry_price,
                     starex_staria_price = $starex_price,
                     hiace_price = $hiace_price,
                     updated_at = NOW()
                     WHERE id = $id";

      if (!$conn->query($update_sql)) {
        $error_message = "Error updating route: " . $conn->error;
        break;
      }
    }
  }

  // Handle new routes
  $new_routes = [];
  if (isset($_POST['new_route_name']) && is_array($_POST['new_route_name'])) {
    foreach ($_POST['new_route_name'] as $key => $route_name) {
      if (empty($route_name)) continue;

      $route_name = $conn->real_escape_string($route_name);
      $route_number = $_POST['new_route_number'][$key];
      $camry_price = $_POST['new_camry_price'][$key];
      $starex_price = $_POST['new_starex_price'][$key];
      $hiace_price = $_POST['new_hiace_price'][$key];

      $new_routes[] = "('$service_title', $year, $route_number, '$route_name', $camry_price, $starex_price, $hiace_price, NOW())";
    }
  }

  if (!empty($new_routes)) {
    $insert_sql = "INSERT INTO taxi_routes 
                  (service_title, year, route_number, route_name, camry_sonata_price, starex_staria_price, hiace_price, created_at) 
                  VALUES " . implode(',', $new_routes);

    if (!$conn->query($insert_sql)) {
      $error_message = "Error adding new routes: " . $conn->error;
    }
  }

  if (empty($error_message)) {
    $success_message = "Taxi routes updated successfully!";
  }
}

// UPDATE RENTACAR ROUTES
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_rentacar_routes'])) {
  // Get service title and year
  $service_title = $conn->real_escape_string($_POST['serviceTitle']);
  $year = (int)$_POST['year'];

  // Update the settings table first
  $update_settings_sql = "UPDATE transportation_settings SET 
                         service_title = '$service_title', 
                         year = $year 
                         WHERE service_type = 'rentacar'";
  if (!$conn->query($update_settings_sql)) {
    $error_message = "Error updating service settings: " . $conn->error;
  }

  // Clear existing routes for the year if requested
  if (isset($_POST['replace_existing']) && $_POST['replace_existing'] == 'yes') {
    $delete_sql = "DELETE FROM rentacar_routes WHERE year = $year";
    if (!$conn->query($delete_sql)) {
      $error_message = "Error clearing existing routes: " . $conn->error;
    }
  }

  // Handle updates for existing routes
  if (isset($_POST['route_id']) && is_array($_POST['route_id'])) {
    foreach ($_POST['route_id'] as $key => $id) {
      if (empty($id)) continue; // Skip if no ID (means new route)

      $route_name = $conn->real_escape_string($_POST['route_name'][$key]);
      $route_number = $_POST['route_number'][$key];
      $gmc_16_19_price = $_POST['gmc_16_19_price'][$key];
      $gmc_22_23_price = $_POST['gmc_22_23_price'][$key];
      $coaster_price = $_POST['coaster_price'][$key];

      $update_sql = "UPDATE rentacar_routes SET 
                     service_title = '$service_title',
                     year = $year,
                     route_name = '$route_name',
                     route_number = $route_number,
                     gmc_16_19_price = $gmc_16_19_price,
                     gmc_22_23_price = $gmc_22_23_price,
                     coaster_price = $coaster_price,
                     updated_at = NOW()
                     WHERE id = $id";

      if (!$conn->query($update_sql)) {
        $error_message = "Error updating route: " . $conn->error;
        break;
      }
    }
  }

  // Handle new routes
  $new_routes = [];
  if (isset($_POST['new_route_name']) && is_array($_POST['new_route_name'])) {
    foreach ($_POST['new_route_name'] as $key => $route_name) {
      if (empty($route_name)) continue;

      $route_name = $conn->real_escape_string($route_name);
      $route_number = $_POST['new_route_number'][$key];
      $gmc_16_19_price = $_POST['new_gmc_16_19_price'][$key];
      $gmc_22_23_price = $_POST['new_gmc_22_23_price'][$key];
      $coaster_price = $_POST['new_coaster_price'][$key];

      $new_routes[] = "('$service_title', $year, $route_number, '$route_name', $gmc_16_19_price, $gmc_22_23_price, $coaster_price, NOW())";
    }
  }

  if (!empty($new_routes)) {
    $insert_sql = "INSERT INTO rentacar_routes 
                  (service_title, year, route_number, route_name, gmc_16_19_price, gmc_22_23_price, coaster_price, created_at) 
                  VALUES " . implode(',', $new_routes);

    if (!$conn->query($insert_sql)) {
      $error_message = "Error adding new routes: " . $conn->error;
    }
  }

  if (empty($error_message)) {
    $success_message = "Rent a car routes updated successfully!";
  }
}

// DELETE TAXI ROUTE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_taxi_route'])) {
  $id = $_POST['taxi_route_id'];

  $sql = "DELETE FROM taxi_routes WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);

  if ($stmt->execute()) {
    $success_message = "Taxi route deleted successfully!";
  } else {
    $error_message = "Error deleting taxi route: " . $conn->error;
  }
}

// DELETE RENTACAR ROUTE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_rentacar_route'])) {
  $id = $_POST['rentacar_route_id'];

  $sql = "DELETE FROM rentacar_routes WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);

  if ($stmt->execute()) {
    $success_message = "Rent a car route deleted successfully!";
  } else {
    $error_message = "Error deleting rent a car route: " . $conn->error;
  }
}

// Get data
$taxi_routes = getTaxiRoutes();
$rentacar_routes = getRentacarRoutes();

// Get service info
$taxi_service_info = getTaxiServiceInfo();
$rentacar_service_info = getRentacarServiceInfo();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Transportation Management | Admin Panel</title>
  <link rel="stylesheet" href="../assets/css/output.css">
  <link rel="stylesheet" href="assets/css/output.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .tab-buttons {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
    }

    .tab-btn {
      padding: 10px 20px;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      border: none;
    }

    .tab-btn.active {
      background-color: #0d9488;
      color: white;
    }

    .tab-btn:not(.active) {
      background-color: #e2e8f0;
      color: #1e293b;
    }

    .tab-btn:hover:not(.active) {
      background-color: #cbd5e1;
    }

    .tab-content {
      display: none;
    }

    .tab-content.active {
      display: block;
      animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .price-table {
      border-collapse: separate;
      border-spacing: 0;
      width: 100%;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      background-color: white;
    }

    .price-table th {
      background-color: #0d9488;
      color: white;
      font-weight: 600;
      text-align: center;
      padding: 12px;
    }

    .price-table td {
      border-top: 1px solid #e2e8f0;
      padding: 12px;
      text-align: center;
    }

    .price-table tr:nth-child(even) {
      background-color: #f8fafc;
    }

    .price-table tr:hover {
      background-color: #e6fffa;
    }

    .action-btn {
      padding: 6px 12px;
      border-radius: 4px;
      font-size: 0.875rem;
      font-weight: 500;
      margin-right: 5px;
    }

    .edit-btn {
      background-color: #3b82f6;
      color: white;
    }

    .edit-btn:hover {
      background-color: #2563eb;
    }

    .delete-btn {
      background-color: #ef4444;
      color: white;
    }

    .delete-btn:hover {
      background-color: #dc2626;
    }

    .add-btn {
      background-color: #10b981;
      color: white;
      padding: 10px 16px;
      border-radius: 6px;
      font-weight: 500;
      margin-bottom: 20px;
    }

    .add-btn:hover {
      background-color: #059669;
    }

    /* Rentacar styles */
    .rentacar-th {
      background-color: #1d4ed8;
    }

    .rentacar-row:hover {
      background-color: #eff6ff !important;
    }

    /* Input Fields */
    .price-input {
      width: 100%;
      padding: 0.375rem 0.75rem;
      border: 1px solid #d1d5db;
      border-radius: 0.375rem;
      font-size: 0.875rem;
    }

    .price-input:focus {
      outline: 2px solid #0d9488;
      border-color: #0d9488;
    }

    .rentacar-input:focus {
      outline: 2px solid #1d4ed8;
      border-color: #1d4ed8;
    }
  </style>
</head>

<body class="bg-gray-50">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="overflow-y-auto flex-1 flex flex-col">
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">

        <h1 class="text-xl font-semibold">
          <i class="text-teal-600 fas fa-car mx-2"></i> Transportation Management
        </h1>
        <div class="flex items-center space-x-4">
          <button class="md:hidden text-gray-800" id="menu-btn">
            <i class="fas fa-bars"></i>
          </button>
        </div>
      </div>
      <div class="contianer w-full p-5">
        <?php include 'includes/transport-stats.php'; ?>
      </div>
      <div class="container mx-auto px-4 py-8">
        <?php if ($success_message): ?>
          <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" id="success-alert">
            <p><?php echo $success_message; ?></p>
          </div>
          <script>
            setTimeout(() => {
              document.getElementById('success-alert').style.display = 'none';
            }, 5000);
          </script>
        <?php endif; ?>

        <?php if ($error_message): ?>
          <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" id="error-alert">
            <p><?php echo $error_message; ?></p>
          </div>
          <script>
            setTimeout(() => {
              document.getElementById('error-alert').style.display = 'none';
            }, 5000);
          </script>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-lg shadow-lg">
          <div class="tab-buttons flex justify-center">
            <button class="tab-btn active" onclick="switchTab('taxi')">Taxi Routes</button>
            <button class="tab-btn" onclick="switchTab('rentacar')">Rent A Car Routes</button>
          </div>

          <!-- Taxi Routes Tab -->
          <div id="taxi-tab" class="tab-content active">
            <div class="mb-6">
              <h2 class="text-2xl font-bold">Taxi Routes Management</h2>
              <p class="text-gray-600 mt-2">Manage your taxi service routes and prices</p>
            </div>

            <form action="" method="POST" id="taxi-routes-form">
              <input type="hidden" name="update_taxi_routes" value="1">

              <!-- Service Title and Year -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <!-- For taxi form -->
                <input type="text" id="taxi-service-title" name="serviceTitle"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500"
                  value="<?php echo htmlspecialchars($taxi_service_info['service_title']); ?>" required>

                <input type="number" id="taxi-year" name="year" min="2024" max="2030"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500"
                  value="<?php echo $taxi_service_info['year']; ?>" required>

              </div>

              <!-- Existing Routes Table -->
              <div class="mb-6 overflow-x-auto">
                <h3 class="font-semibold text-lg mb-3">Existing Routes</h3>

                <table class="min-w-full bg-white border border-gray-300 mb-4">
                  <thead>
                    <tr class="bg-teal-600 text-white">
                      <th class="py-2 px-4 border-b w-16 text-center">#</th>
                      <th class="py-2 px-4 border-b text-left">Route</th>
                      <th class="py-2 px-4 border-b text-center">Camry / Sonata (PKR)</th>
                      <th class="py-2 px-4 border-b text-center">Starex / Staria (PKR)</th>
                      <th class="py-2 px-4 border-b text-center">Hiace (PKR)</th>
                      <th class="py-2 px-4 border-b w-16 text-center">Action</th>
                    </tr>
                  </thead>
                  <tbody id="taxi-routes-body">
                    <?php
                    if (count($taxi_routes) > 0):
                      foreach ($taxi_routes as $index => $route):
                    ?>
                        <tr>
                          <td class="py-2 px-4 border-b text-center">
                            <input type="hidden" name="route_id[<?php echo $index; ?>]" value="<?php echo $route['id']; ?>">
                            <input type="number" name="route_number[<?php echo $index; ?>]" value="<?php echo $route['route_number']; ?>" class="price-input w-16 text-center" required>
                          </td>
                          <td class="py-2 px-4 border-b">
                            <input type="text" name="route_name[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($route['route_name']); ?>" class="price-input w-full" required>
                          </td>
                          <td class="py-2 px-4 border-b">
                            <input type="number" name="camry_price[<?php echo $index; ?>]" value="<?php echo $route['camry_sonata_price']; ?>" min="0" step="0.01" class="price-input w-full text-center" required>
                            <span class="text-xs text-gray-500">PKR</span>
                          </td>
                          <td class="py-2 px-4 border-b">
                            <input type="number" name="starex_price[<?php echo $index; ?>]" value="<?php echo $route['starex_staria_price']; ?>" min="0" step="0.01" class="price-input w-full text-center" required>
                            <span class="text-xs text-gray-500">PKR</span>
                          </td>
                          <td class="py-2 px-4 border-b">
                            <input type="number" name="hiace_price[<?php echo $index; ?>]" value="<?php echo $route['hiace_price']; ?>" min="0" step="0.01" class="price-input w-full text-center" required>
                            <span class="text-xs text-gray-500">PKR</span>
                          </td>
                          <td class="py-2 px-4 border-b text-center">
                            <button type="button" class="text-red-500 hover:text-red-700" onclick="confirmDeleteTaxiRoute(<?php echo $route['id']; ?>)">
                              <i class="fas fa-trash"></i>
                            </button>
                          </td>
                        </tr>
                      <?php
                      endforeach;
                    else:
                      ?>
                      <tr>
                        <td colspan="6" class="py-4 text-center text-gray-500">No taxi routes found</td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>

              <!-- Add New Routes Section -->
              <div class="mb-6">
                <h3 class="font-semibold text-lg mb-3">Add New Routes</h3>

                <table class="min-w-full bg-white border border-gray-300 mb-4">
                  <thead>
                    <tr class="bg-teal-600 text-white">
                      <th class="py-2 px-4 border-b w-16 text-center">#</th>
                      <th class="py-2 px-4 border-b text-left">Route</th>
                      <th class="py-2 px-4 border-b text-center">Camry / Sonata (PKR)</th>
                      <th class="py-2 px-4 border-b text-center">Starex / Staria (PKR)</th>
                      <th class="py-2 px-4 border-b text-center">Hiace (PKR)</th>
                      <th class="py-2 px-4 border-b w-16 text-center">Action</th>
                    </tr>
                  </thead>
                  <tbody id="new-taxi-routes-body">
                    <tr>
                      <td class="py-2 px-4 border-b text-center">
                        <input type="number" name="new_route_number[0]" value="<?php echo count($taxi_routes) + 1; ?>" class="price-input w-16 text-center">
                      </td>
                      <td class="py-2 px-4 border-b">
                        <input type="text" name="new_route_name[0]" placeholder="Enter route name" class="price-input w-full">
                      </td>
                      <td class="py-2 px-4 border-b">
                        <input type="number" name="new_camry_price[0]" placeholder="Price" min="0" step="0.01" class="price-input w-full text-center">
                        <span class="text-xs text-gray-500">PKR</span>
                      </td>
                      <td class="py-2 px-4 border-b">
                        <input type="number" name="new_starex_price[0]" placeholder="Price" min="0" step="0.01" class="price-input w-full text-center">
                        <span class="text-xs text-gray-500">PKR</span>
                      </td>
                      <td class="py-2 px-4 border-b">
                        <input type="number" name="new_hiace_price[0]" placeholder="Price" min="0" step="0.01" class="price-input w-full text-center">
                        <span class="text-xs text-gray-500">PKR</span>
                      </td>
                      <td class="py-2 px-4 border-b text-center">
                        <button type="button" class="text-red-500 hover:text-red-700 delete-new-row" disabled>
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                  <script>
                    document.addEventListener('DOMContentLoaded', function() {
                      const tableBody = document.getElementById('new-taxi-routes-body');

                      // ======================
                      // ROUTE NAME VALIDATION
                      // ======================
                      tableBody.addEventListener('input', function(e) {
                        // Route name validation
                        if (e.target.name && e.target.name.startsWith('new_route_name')) {
                          const input = e.target;
                          const originalValue = input.value;

                          // Remove any numbers or special characters
                          input.value = originalValue.replace(/[^a-zA-Z\s]/g, '');

                          // Enforce 15 character limit
                          if (input.value.length > 15) {
                            input.value = input.value.substring(0, 15);
                            showError(input, "Maximum 15 characters allowed", 'route-error');
                          }

                          // If we removed characters, show temporary warning
                          if (originalValue !== input.value) {
                            showError(input, "Only English letters allowed", 'route-error', 2000);
                          }
                        }

                        // Price input validation
                        const priceInputs = ['new_camry_price', 'new_starex_price', 'new_hiace_price'];
                        if (priceInputs.some(prefix => e.target.name && e.target.name.startsWith(prefix))) {
                          const input = e.target;

                          // Clean price input (numbers and decimal only)
                          input.value = input.value.replace(/[^0-9.]/g, '');

                          // Validate price progression
                          validatePriceProgression(input.closest('tr'));
                        }
                      });

                      // ======================
                      // PRICE VALIDATION LOGIC
                      // ======================
                      function validatePriceProgression(row) {
                        const camryInput = row.querySelector('input[name^="new_camry_price"]');
                        const starexInput = row.querySelector('input[name^="new_starex_price"]');
                        const hiaceInput = row.querySelector('input[name^="new_hiace_price"]');

                        clearError(row, 'price-error');

                        // CAMRY PRICE VALIDATION
                        if (camryInput.value) {
                          starexInput.disabled = false;
                          starexInput.placeholder = `Must be > ${camryInput.value}`;

                          // STREX PRICE VALIDATION
                          if (starexInput.value) {
                            if (parseFloat(starexInput.value) <= parseFloat(camryInput.value)) {
                              showError(starexInput, "Must be greater than Camry price", 'price-error');
                              disableNextFields(starexInput, hiaceInput, "Fix Starex price first");
                            } else {
                              hiaceInput.disabled = false;
                              hiaceInput.placeholder = `Must be > ${starexInput.value}`;
                            }
                          } else {
                            disableNextFields(starexInput, hiaceInput, "Enter Starex price first");
                          }
                        } else {
                          // If Camry price is empty, disable other fields
                          disableNextFields(camryInput, [starexInput, hiaceInput], "Enter Camry price first");
                        }
                      }

                      function disableNextFields(currentInput, nextInputs, placeholder) {
                        const fields = Array.isArray(nextInputs) ? nextInputs : [nextInputs];
                        fields.forEach(input => {
                          input.disabled = true;
                          input.value = '';
                          input.placeholder = placeholder;
                        });
                      }

                      // ======================
                      // ERROR HANDLING UTILS
                      // ======================
                      function showError(input, message, errorClass, timeout = null) {
                        input.classList.add('border-red-500');

                        let errorElement = input.parentNode.querySelector(`.${errorClass}`);
                        if (!errorElement) {
                          errorElement = document.createElement('span');
                          errorElement.className = `${errorClass} text-red-500 text-xs block mt-1`;
                          input.parentNode.appendChild(errorElement);
                        }

                        errorElement.textContent = message;

                        if (timeout) {
                          setTimeout(() => {
                            if (errorElement.textContent === message) {
                              clearError(input, errorClass);
                            }
                          }, timeout);
                        }
                      }

                      function clearError(element, errorClass) {
                        if (element.classList) {
                          element.classList.remove('border-red-500');
                        }

                        const container = element.classList ? element.parentNode : element;
                        const errorElement = container.querySelector(`.${errorClass}`);
                        if (errorElement) {
                          errorElement.remove();
                        }
                      }

                      // ======================
                      // FORM SUBMISSION VALIDATION
                      // ======================
                      document.querySelector('form').addEventListener('submit', function(e) {
                        let isValid = true;

                        // Validate all route names
                        document.querySelectorAll('input[name^="new_route_name"]').forEach(input => {
                          if (!/^[a-zA-Z\s]{1,15}$/.test(input.value)) {
                            showError(input, "Route name must contain only letters (max 15)", 'route-error');
                            isValid = false;
                          }
                        });

                        // Validate all price progressions
                        document.querySelectorAll('tr').forEach(row => {
                          const camry = row.querySelector('input[name^="new_camry_price"]');
                          const starex = row.querySelector('input[name^="new_starex_price"]');
                          const hiace = row.querySelector('input[name^="new_hiace_price"]');

                          if (camry.value && starex.value && hiace.value) {
                            if (!(parseFloat(hiace.value) > parseFloat(starex.value) &&
                                parseFloat(starex.value) > parseFloat(camry.value))) {
                              showError(hiace, "Prices must be in increasing order", 'price-error');
                              isValid = false;
                            }
                          } else {
                            showError(row, "All price fields are required", 'price-error');
                            isValid = false;
                          }
                        });

                        if (!isValid) {
                          e.preventDefault();
                          alert('Please fix all validation errors before submitting.');
                        }
                      });
                    });
                  </script>
                </table>

                <!-- Add Row Button -->
                <button type="button" id="add-taxi-row" class="px-4 py-2 bg-teal-600 text-white rounded-md hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                  <i class="fas fa-plus-circle mr-2"></i> Add Another Route
                </button>
              </div>

              <!-- Submit Buttons -->
              <div class="flex flex-wrap gap-4 text-center mx-auto">
                <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700">
                  <i class="fas fa-save mr-2"></i>Save All Changes
                </button>
                <!-- <a href="transportation-management.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 inline-flex items-center">
                  <i class="fas fa-times mr-2"></i>Cancel
                </a> -->
              </div>
            </form>
          </div>

          <!-- Rent A Car Routes Tab -->
          <div id="rentacar-tab" class="tab-content">
            <div class="mb-6">
              <h2 class="text-2xl font-bold">Rent A Car Routes Management</h2>
              <p class="text-gray-600 mt-2">Manage your rent a car service routes and prices</p>
            </div>

            <form action="" method="POST" id="rentacar-routes-form">
              <input type="hidden" name="update_rentacar_routes" value="1">

              <!-- Service Title and Year -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <!-- For rentacar routes -->
                <input type="text" id="rentacar-service-title" name="serviceTitle"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  value="<?php echo htmlspecialchars($rentacar_service_info['service_title']); ?>" required>

                <input type="number" id="rentacar-year" name="year" min="2024" max="2030"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  value="<?php echo $rentacar_service_info['year']; ?>" required>
              </div>

              <!-- Existing Routes Table -->
              <div class="mb-6 overflow-x-auto">
                <h3 class="font-semibold text-lg mb-3">Existing Routes</h3>

                <table class="min-w-full bg-white border border-gray-300 mb-4">
                  <thead>
                    <tr class="bg-blue-600 text-white">
                      <th class="py-2 px-4 border-b w-16 text-center">#</th>
                      <th class="py-2 px-4 border-b text-left">Route</th>
                      <th class="py-2 px-4 border-b text-center">GMC 16-19 (PKR)</th>
                      <th class="py-2 px-4 border-b text-center">GMC 22-23 (PKR)</th>
                      <th class="py-2 px-4 border-b text-center">COASTER (PKR)</th>
                      <th class="py-2 px-4 border-b w-16 text-center">Action</th>
                    </tr>
                  </thead>
                  <tbody id="rentacar-routes-body">
                    <?php
                    if (count($rentacar_routes) > 0):
                      foreach ($rentacar_routes as $index => $route):
                    ?>
                        <tr>
                          <td class="py-2 px-4 border-b text-center">
                            <input type="hidden" name="route_id[<?php echo $index; ?>]" value="<?php echo $route['id']; ?>">
                            <input type="number" name="route_number[<?php echo $index; ?>]" value="<?php echo $route['route_number']; ?>" class="price-input rentacar-input w-16 text-center" required>
                          </td>
                          <td class="py-2 px-4 border-b">
                            <input type="text" name="route_name[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($route['route_name']); ?>" class="price-input rentacar-input w-full" required>
                          </td>
                          <td class="py-2 px-4 border-b">
                            <input type="number" name="gmc_16_19_price[<?php echo $index; ?>]" value="<?php echo $route['gmc_16_19_price']; ?>" min="0" step="0.01" class="price-input rentacar-input w-full text-center" required>
                            <span class="text-xs text-gray-500">PKR</span>
                          </td>
                          <td class="py-2 px-4 border-b">
                            <input type="number" name="gmc_22_23_price[<?php echo $index; ?>]" value="<?php echo $route['gmc_22_23_price']; ?>" min="0" step="0.01" class="price-input rentacar-input w-full text-center" required>
                            <span class="text-xs text-gray-500">PKR</span>
                          </td>
                          <td class="py-2 px-4 border-b">
                            <input type="number" name="coaster_price[<?php echo $index; ?>]" value="<?php echo $route['coaster_price']; ?>" min="0" step="0.01" class="price-input rentacar-input w-full text-center" required>
                            <span class="text-xs text-gray-500">PKR</span>
                          </td>
                          <td class="py-2 px-4 border-b text-center">
                            <button type="button" class="text-red-500 hover:text-red-700" onclick="confirmDeleteRentacarRoute(<?php echo $route['id']; ?>)">
                              <i class="fas fa-trash"></i>
                            </button>
                          </td>
                        </tr>
                      <?php
                      endforeach;
                    else:
                      ?>
                      <tr>
                        <td colspan="6" class="py-4 text-center text-gray-500">No rent a car routes found</td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                  <script>

                  </script>
                </table>
              </div>

              <!-- Add New Routes Section -->
              <div class="mb-6">
                <h3 class="font-semibold text-lg mb-3">Add New Routes</h3>

                <table class="min-w-full bg-white border border-gray-300 mb-4">
                  <thead>
                    <tr class="bg-blue-600 text-white">
                      <th class="py-2 px-4 border-b w-16 text-center">#</th>
                      <th class="py-2 px-4 border-b text-left">Route</th>
                      <th class="py-2 px-4 border-b text-center">GMC 16-19 (PKR)</th>
                      <th class="py-2 px-4 border-b text-center">GMC 22-23 (PKR)</th>
                      <th class="py-2 px-4 border-b text-center">COASTER (PKR)</th>
                      <th class="py-2 px-4 border-b w-16 text-center">Action</th>
                    </tr>
                  </thead>
                  <tbody id="new-rentacar-routes-body">
                    <tr class="price-validation-row">
                      <td class="py-2 px-4 border-b text-center">
                        <input type="number" name="new_route_number[0]" value="<?php echo count($rentacar_routes) + 1; ?>" class="price-input rentacar-input w-16 text-center" required>
                      </td>
                      <td class="py-2 px-4 border-b">
                        <input type="text" name="new_route_name[0]" placeholder="Enter route name"
                          class="price-input rentacar-input w-full route-name"
                          pattern="[A-Za-z\s]+"
                          title="Only letters are allowed"
                          maxlength="15"
                          oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')"
                          required>
                        <div class="text-red-500 text-xs error-msg-name hidden">Only letters allowed (max 15 chars)</div>
                      </td>
                      <td class="py-2 px-4 border-b">
                        <input type="number" name="new_gmc_16_19_price[0]" placeholder="Price" min="0" step="0.01"
                          class="price-input rentacar-input w-full text-center base-price"
                          oninput="validateNewCarPrices(this)"
                          required>
                        <span class="text-xs text-gray-500">PKR</span>
                        <div class="text-red-500 text-xs error-msg-base hidden">Must be greater than 0</div>
                      </td>
                      <td class="py-2 px-4 border-b">
                        <input type="number" name="new_gmc_22_23_price[0]" placeholder="Price" min="0" step="0.01"
                          class="price-input rentacar-input w-full text-center mid-price"
                          oninput="validateNewCarPrices(this)"
                          disabled
                          required>
                        <span class="text-xs text-gray-500">PKR</span>
                        <div class="text-red-500 text-xs error-msg-mid hidden">Must be higher than base price</div>
                      </td>
                      <td class="py-2 px-4 border-b">
                        <input type="number" name="new_coaster_price[0]" placeholder="Price" min="0" step="0.01"
                          class="price-input rentacar-input w-full text-center premium-price"
                          oninput="validateNewCarPrices(this)"
                          disabled
                          required>
                        <span class="text-xs text-gray-500">PKR</span>
                        <div class="text-red-500 text-xs error-msg-premium hidden">Must be higher than mid price</div>
                      </td>
                      <td class="py-2 px-4 border-b text-center">
                        <button type="button" class="text-red-500 hover:text-red-700 delete-new-row" disabled>
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>

                  <script>
                    function validateNewCarPrices(input) {
                      const row = input.closest('.price-validation-row');
                      const basePriceInput = row.querySelector('.base-price');
                      const midPriceInput = row.querySelector('.mid-price');
                      const premiumPriceInput = row.querySelector('.premium-price');

                      const basePrice = parseFloat(basePriceInput.value) || 0;
                      const midPrice = parseFloat(midPriceInput.value) || 0;
                      const premiumPrice = parseFloat(premiumPriceInput.value) || 0;

                      // Reset all error messages
                      row.querySelector('.error-msg-base').classList.add('hidden');
                      row.querySelector('.error-msg-mid').classList.add('hidden');
                      row.querySelector('.error-msg-premium').classList.add('hidden');

                      // Validate base price
                      if (input.classList.contains('base-price')) {
                        if (basePrice <= 0) {
                          row.querySelector('.error-msg-base').classList.remove('hidden');
                          midPriceInput.disabled = true;
                          premiumPriceInput.disabled = true;
                          midPriceInput.value = '';
                          premiumPriceInput.value = '';
                        } else {
                          midPriceInput.disabled = false;
                        }
                      }

                      // Validate mid price
                      if (input.classList.contains('mid-price')) {
                        if (midPrice <= basePrice) {
                          row.querySelector('.error-msg-mid').classList.remove('hidden');
                          premiumPriceInput.disabled = true;
                          premiumPriceInput.value = '';
                        } else {
                          premiumPriceInput.disabled = false;
                        }
                      }

                      // Validate premium price
                      if (input.classList.contains('premium-price')) {
                        if (premiumPrice <= midPrice) {
                          row.querySelector('.error-msg-premium').classList.remove('hidden');
                        }
                      }
                    }

                    // Route name validation
                    document.addEventListener('DOMContentLoaded', function() {
                      document.querySelectorAll('.route-name').forEach(input => {
                        input.addEventListener('input', function() {
                          const errorMsg = this.closest('td').querySelector('.error-msg-name');
                          if (!/^[A-Za-z\s]{0,15}$/.test(this.value)) {
                            errorMsg.classList.remove('hidden');
                          } else {
                            errorMsg.classList.add('hidden');
                          }
                        });
                      });

                      // Form submission validation
                      document.querySelector('form').addEventListener('submit', function(e) {
                        let isValid = true;

                        document.querySelectorAll('.price-validation-row').forEach(row => {
                          // Validate route name
                          const routeName = row.querySelector('.route-name').value;
                          if (!/^[A-Za-z\s]{1,15}$/.test(routeName)) {
                            row.querySelector('.error-msg-name').classList.remove('hidden');
                            isValid = false;
                          }

                          // Validate prices
                          const basePrice = parseFloat(row.querySelector('.base-price').value) || 0;
                          const midPrice = parseFloat(row.querySelector('.mid-price').value) || 0;
                          const premiumPrice = parseFloat(row.querySelector('.premium-price').value) || 0;

                          if (basePrice <= 0) {
                            row.querySelector('.error-msg-base').classList.remove('hidden');
                            isValid = false;
                          }

                          if (midPrice <= basePrice) {
                            row.querySelector('.error-msg-mid').classList.remove('hidden');
                            isValid = false;
                          }

                          if (premiumPrice <= midPrice) {
                            row.querySelector('.error-msg-premium').classList.remove('hidden');
                            isValid = false;
                          }
                        });

                        if (!isValid) {
                          e.preventDefault();
                          alert('Please fix all validation errors before submitting.');
                        }
                      });
                    });
                  </script>
                </table>

                <!-- Add Row Button -->
                <button type="button" id="add-rentacar-row" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                  <i class="fas fa-plus-circle mr-2"></i> Add Another Route
                </button>
              </div>

              <!-- Submit Buttons -->
              <div class="flex flex-wrap gap-4">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                  <i class="fas fa-save mr-2"></i>Save All Changes
                </button>
                <!-- <a href="transportation-management.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 inline-flex items-center">
                  <i class="fas fa-times mr-2"></i>Cancel
                </a> -->
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Taxi Route Form (Hidden) -->
  <form id="deleteTaxiRouteForm" method="POST" action="" style="display: none;">
    <input type="hidden" id="delete_taxi_route_id" name="taxi_route_id">
    <input type="hidden" name="delete_taxi_route" value="1">
  </form>

  <!-- Delete Rentacar Route Form (Hidden) -->
  <form id="deleteRentacarRouteForm" method="POST" action="" style="display: none;">
    <input type="hidden" id="delete_rentacar_route_id" name="rentacar_route_id">
    <input type="hidden" name="delete_rentacar_route" value="1">
  </form>

  <script>
    // Tab switching
    function switchTab(tabName) {
      // Hide all tabs
      document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
      });

      // Remove active class from all buttons
      document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
      });

      // Show selected tab
      document.getElementById(tabName + '-tab').classList.add('active');

      // Add active class to the correct button
      document.querySelector(`.tab-btn[onclick="switchTab('${tabName}')"]`).classList.add('active');

      // Save current tab to localStorage
      localStorage.setItem('activeTransportTab', tabName);
    }

    // Document ready function
    document.addEventListener('DOMContentLoaded', function() {
      // Check for stored tab on page load
      const storedTab = localStorage.getItem('activeTransportTab');
      if (storedTab) {
        // Show the stored tab
        document.querySelectorAll('.tab-content').forEach(tab => {
          tab.classList.remove('active');
        });

        const tabContentEl = document.getElementById(storedTab + '-tab');
        if (tabContentEl) {
          tabContentEl.classList.add('active');

          // Update the tab buttons
          document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
          });

          const tabBtn = document.querySelector(`.tab-btn[onclick="switchTab('${storedTab}')"]`);
          if (tabBtn) {
            tabBtn.classList.add('active');
          }
        }
      }

      // Function to add new rows to taxi table
      document.getElementById('add-taxi-row').addEventListener('click', function() {
        const tbody = document.getElementById('new-taxi-routes-body');
        const rowCount = tbody.rows.length;
        const newIndex = rowCount;
        const newRowNumber = <?php echo count($taxi_routes); ?> + 1 + rowCount;

        const newRow = document.createElement('tr');
        newRow.innerHTML = `
      <td class="py-2 px-4 border-b text-center">
        <input type="number" name="new_route_number[${newIndex}]" value="${newRowNumber}" class="price-input w-16 text-center">
      </td>
      <td class="py-2 px-4 border-b">
        <input type="text" name="new_route_name[${newIndex}]" placeholder="Enter route name" class="price-input w-full">
      </td>
      <td class="py-2 px-4 border-b">
        <input type="number" name="new_camry_price[${newIndex}]" placeholder="Price" min="0" step="0.01" class="price-input w-full text-center">
        <span class="text-xs text-gray-500">PKR</span>
      </td>
      <td class="py-2 px-4 border-b">
        <input type="number" name="new_starex_price[${newIndex}]" placeholder="Price" min="0" step="0.01" class="price-input w-full text-center">
        <span class="text-xs text-gray-500">PKR</span>
      </td>
      <td class="py-2 px-4 border-b">
        <input type="number" name="new_hiace_price[${newIndex}]" placeholder="Price" min="0" step="0.01" class="price-input w-full text-center">
        <span class="text-xs text-gray-500">PKR</span>
      </td>
      <td class="py-2 px-4 border-b text-center">
        <button type="button" class="text-red-500 hover:text-red-700 delete-new-row">
          <i class="fas fa-trash"></i>
        </button>
      </td>
    `;

        tbody.appendChild(newRow);
        setupDeleteHandlers();
      });

      // Function to add new rows to rentacar table
      document.getElementById('add-rentacar-row').addEventListener('click', function() {
        const tbody = document.getElementById('new-rentacar-routes-body');
        const rowCount = tbody.rows.length;
        const newIndex = rowCount;
        const newRowNumber = <?php echo count($rentacar_routes); ?> + 1 + rowCount;

        const newRow = document.createElement('tr');
        newRow.innerHTML = `
      <td class="py-2 px-4 border-b text-center">
        <input type="number" name="new_route_number[${newIndex}]" value="${newRowNumber}" class="price-input rentacar-input w-16 text-center">
      </td>
      <td class="py-2 px-4 border-b">
        <input type="text" name="new_route_name[${newIndex}]" placeholder="Enter route name" class="price-input rentacar-input w-full">
      </td>
      <td class="py-2 px-4 border-b">
        <input type="number" name="new_gmc_16_19_price[${newIndex}]" placeholder="Price" min="0" step="0.01" class="price-input rentacar-input w-full text-center">
        <span class="text-xs text-gray-500">PKR</span>
      </td>
      <td class="py-2 px-4 border-b">
        <input type="number" name="new_gmc_22_23_price[${newIndex}]" placeholder="Price" min="0" step="0.01" class="price-input rentacar-input w-full text-center">
        <span class="text-xs text-gray-500">PKR</span>
      </td>
      <td class="py-2 px-4 border-b">
        <input type="number" name="new_coaster_price[${newIndex}]" placeholder="Price" min="0" step="0.01" class="price-input rentacar-input w-full text-center">
        <span class="text-xs text-gray-500">PKR</span>
      </td>
      <td class="py-2 px-4 border-b text-center">
        <button type="button" class="text-red-500 hover:text-red-700 delete-new-row">
          <i class="fas fa-trash"></i>
        </button>
      </td>
    `;

        tbody.appendChild(newRow);
        setupDeleteHandlers();
      });

      // Mobile menu toggle
      const menuBtn = document.getElementById('menu-btn');
      const sidebar = document.querySelector('.sidebar');

      if (menuBtn && sidebar) {
        menuBtn.addEventListener('click', function() {
          sidebar.classList.toggle('hidden');
          sidebar.classList.toggle('flex');
        });
      }

      // Initial setup
      setupDeleteHandlers();
    });

    // Setup delete handlers for new rows
    function setupDeleteHandlers() {
      document.querySelectorAll('.delete-new-row').forEach(button => {
        if (!button.hasAttribute('disabled')) {
          button.addEventListener('click', function() {
            const row = this.closest('tr');
            const tbody = row.parentNode;
            tbody.removeChild(row);

            // Update row numbers
            const rows = tbody.querySelectorAll('tr');
            rows.forEach((row, index) => {
              const input = row.querySelector('input[type="number"]');
              if (input && input.name.includes('new_route_number')) {
                input.value = <?php echo count($taxi_routes); ?> + 1 + index;
              }
            });
          });
        }
      });
    }

    // Delete existing taxi route
    function confirmDeleteTaxiRoute(id) {
      Swal.fire({
        title: 'Are you sure?',
        text: "This will permanently delete this taxi route!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          document.getElementById('delete_taxi_route_id').value = id;
          document.getElementById('deleteTaxiRouteForm').submit();
        }
      });
    }

    // Delete existing rentacar route
    function confirmDeleteRentacarRoute(id) {
      Swal.fire({
        title: 'Are you sure?',
        text: "This will permanently delete this rent a car route!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          document.getElementById('delete_rentacar_route_id').value = id;
          document.getElementById('deleteRentacarRouteForm').submit();
        }
      });
    }
  </script>

  <?php include 'includes/js-links.php'; ?>
</body>

</html>