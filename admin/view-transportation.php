<?php
session_name("admin_session");
session_start();
include 'connection/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
  header("Location: admin-login.php");
  exit();
}

// Function to get taxi routes
function getTaxiRoutes()
{
  global $conn;
  $sql = "SELECT * FROM taxi_routes WHERE year = 2024 ORDER BY route_number";
  $result = $conn->query($sql);
  return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get rent a car routes
function getRentacarRoutes()
{
  global $conn;
  $sql = "SELECT * FROM rentacar_routes WHERE year = 2024 ORDER BY route_number";
  $result = $conn->query($sql);
  return $result->fetch_all(MYSQLI_ASSOC);
}

// Handling CRUD operations
$success_message = '';
$error_message = '';

// ADD NEW TAXI ROUTE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_taxi_route'])) {
  $service_title = "Best Taxi Service for Umrah and Hajj in Makkah, Madinah and Jeddah";
  $year = 2024;
  $route_number = $_POST['route_number'];
  $route_name = $_POST['route_name'];
  $camry_sonata_price = $_POST['camry_sonata_price'];
  $starex_staria_price = $_POST['starex_staria_price'];
  $hiace_price = $_POST['hiace_price'];
  
  $sql = "INSERT INTO taxi_routes (service_title, year, route_number, route_name, camry_sonata_price, starex_staria_price, hiace_price, created_at) 
          VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
  
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("siisddd", $service_title, $year, $route_number, $route_name, $camry_sonata_price, $starex_staria_price, $hiace_price);
  
  if ($stmt->execute()) {
    $success_message = "New taxi route added successfully!";
  } else {
    $error_message = "Error adding taxi route: " . $conn->error;
  }
}

// ADD NEW RENTACAR ROUTE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_rentacar_route'])) {
  $service_title = "Best Umrah and Hajj Rent A Car in Makkah, Madinah and Jeddah";
  $year = 2024;
  $route_number = $_POST['route_number'];
  $route_name = $_POST['route_name'];
  $gmc_16_19_price = $_POST['gmc_16_19_price'];
  $gmc_22_23_price = $_POST['gmc_22_23_price'];
  $coaster_price = $_POST['coaster_price'];
  
  $sql = "INSERT INTO rentacar_routes (service_title, year, route_number, route_name, gmc_16_19_price, gmc_22_23_price, coaster_price, created_at) 
          VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
  
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("siisddd", $service_title, $year, $route_number, $route_name, $gmc_16_19_price, $gmc_22_23_price, $coaster_price);
  
  if ($stmt->execute()) {
    $success_message = "New rent a car route added successfully!";
  } else {
    $error_message = "Error adding rent a car route: " . $conn->error;
  }
}

// UPDATE TAXI ROUTE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_taxi_route'])) {
  $id = $_POST['taxi_route_id'];
  $route_number = $_POST['route_number'];
  $route_name = $_POST['route_name'];
  $camry_sonata_price = $_POST['camry_sonata_price'];
  $starex_staria_price = $_POST['starex_staria_price'];
  $hiace_price = $_POST['hiace_price'];
  
  $sql = "UPDATE taxi_routes SET 
          route_number = ?, 
          route_name = ?, 
          camry_sonata_price = ?, 
          starex_staria_price = ?, 
          hiace_price = ?,
          updated_at = NOW() 
          WHERE id = ?";
  
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("isdddi", $route_number, $route_name, $camry_sonata_price, $starex_staria_price, $hiace_price, $id);
  
  if ($stmt->execute()) {
    $success_message = "Taxi route updated successfully!";
  } else {
    $error_message = "Error updating taxi route: " . $conn->error;
  }
}

// UPDATE RENTACAR ROUTE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_rentacar_route'])) {
  $id = $_POST['rentacar_route_id'];
  $route_number = $_POST['route_number'];
  $route_name = $_POST['route_name'];
  $gmc_16_19_price = $_POST['gmc_16_19_price'];
  $gmc_22_23_price = $_POST['gmc_22_23_price'];
  $coaster_price = $_POST['coaster_price'];
  
  $sql = "UPDATE rentacar_routes SET 
          route_number = ?, 
          route_name = ?, 
          gmc_16_19_price = ?, 
          gmc_22_23_price = ?, 
          coaster_price = ?,
          updated_at = NOW() 
          WHERE id = ?";
  
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("isdddi", $route_number, $route_name, $gmc_16_19_price, $gmc_22_23_price, $coaster_price, $id);
  
  if ($stmt->execute()) {
    $success_message = "Rent a car route updated successfully!";
  } else {
    $error_message = "Error updating rent a car route: " . $conn->error;
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
            </div>

            <div class="overflow-x-auto">
              <table class="price-table">
                <thead>
                  <tr>
                    <th class="w-16">No.</th>
                    <th class="text-left">Routes</th>
                    <th>Camry / Sonata</th>
                    <th>Starex / Staria</th>
                    <th>Hiace</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (count($taxi_routes) > 0): ?>
                    <?php foreach ($taxi_routes as $route): ?>
                      <tr>
                        <td><?php echo $route['route_number']; ?></td>
                        <td class="text-left font-medium"><?php echo htmlspecialchars($route['route_name']); ?></td>
                        <td><?php echo $route['camry_sonata_price']; ?> SR</td>
                        <td><?php echo $route['starex_staria_price']; ?> SR</td>
                        <td><?php echo $route['hiace_price']; ?> SR</td>
                        <td>
                          <button class="action-btn edit-btn" onclick="showEditTaxiRouteModal(
                            <?php echo $route['id']; ?>, 
                            <?php echo $route['route_number']; ?>, 
                            '<?php echo addslashes(htmlspecialchars($route['route_name'])); ?>', 
                            <?php echo $route['camry_sonata_price']; ?>, 
                            <?php echo $route['starex_staria_price']; ?>, 
                            <?php echo $route['hiace_price']; ?>
                          )">
                            <i class="fas fa-edit"></i> Edit
                          </button>
                          <button class="action-btn delete-btn" onclick="confirmDeleteTaxiRoute(<?php echo $route['id']; ?>)">
                            <i class="fas fa-trash"></i> Delete
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="6" class="py-4 text-center text-gray-500">No taxi routes found</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Rent A Car Routes Tab -->
          <div id="rentacar-tab" class="tab-content">
            <div class="mb-6">
              <h2 class="text-2xl font-bold">Rent A Car Routes Management</h2>
            </div>

            <div class="overflow-x-auto">
              <table class="price-table">
                <thead>
                  <tr>
                    <th class="w-16 rentacar-th">No.</th>
                    <th class="text-left rentacar-th">Routes</th>
                    <th class="rentacar-th">GMC 16-19</th>
                    <th class="rentacar-th">GMC 22-23</th>
                    <th class="rentacar-th">COASTER</th>
                    <th class="rentacar-th">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (count($rentacar_routes) > 0): ?>
                    <?php foreach ($rentacar_routes as $route): ?>
                      <tr class="rentacar-row">
                        <td><?php echo $route['route_number']; ?></td>
                        <td class="text-left font-medium"><?php echo htmlspecialchars($route['route_name']); ?></td>
                        <td><?php echo $route['gmc_16_19_price']; ?> SR</td>
                        <td><?php echo $route['gmc_22_23_price']; ?> SR</td>
                        <td><?php echo $route['coaster_price']; ?> SR</td>
                        <td>
                          <button class="action-btn edit-btn" onclick="showEditRentacarRouteModal(
                            <?php echo $route['id']; ?>, 
                            <?php echo $route['route_number']; ?>, 
                            '<?php echo addslashes(htmlspecialchars($route['route_name'])); ?>', 
                            <?php echo $route['gmc_16_19_price']; ?>, 
                            <?php echo $route['gmc_22_23_price']; ?>, 
                            <?php echo $route['coaster_price']; ?>
                          )">
                            <i class="fas fa-edit"></i> Edit
                          </button>
                          <button class="action-btn delete-btn" onclick="confirmDeleteRentacarRoute(<?php echo $route['id']; ?>)">
                            <i class="fas fa-trash"></i> Delete
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="6" class="py-4 text-center text-gray-500">No rent a car routes found</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Taxi Route Modal -->
  <div id="addTaxiRouteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <div class="flex justify-between items-center border-b px-6 py-4">
        <h3 class="text-lg font-medium text-gray-900">Add New Taxi Route</h3>
        <button onclick="closeAddTaxiRouteModal()" class="text-gray-400 hover:text-gray-500">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form method="POST" action="">
        <div class="p-6 space-y-4">
          <div>
            <label for="taxi_route_number" class="block text-sm font-medium text-gray-700 mb-1">Route Number</label>
            <input type="number" id="taxi_route_number" name="route_number" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm" required>
          </div>

          <div>
            <label for="taxi_route_name" class="block text-sm font-medium text-gray-700 mb-1">Route Name</label>
            <input type="text" id="taxi_route_name" name="route_name" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm" required>
          </div>

          <div>
            <label for="taxi_camry_price" class="block text-sm font-medium text-gray-700 mb-1">Camry/Sonata Price (SR)</label>
            <input type="number" id="taxi_camry_price" name="camry_sonata_price" step="0.01" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm" required>
          </div>

          <div>
            <label for="taxi_starex_price" class="block text-sm font-medium text-gray-700 mb-1">Starex/Staria Price (SR)</label>
            <input type="number" id="taxi_starex_price" name="starex_staria_price" step="0.01" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm" required>
          </div>

          <div>
            <label for="taxi_hiace_price" class="block text-sm font-medium text-gray-700 mb-1">Hiace Price (SR)</label>
            <input type="number" id="taxi_hiace_price" name="hiace_price" step="0.01" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm" required>
          </div>
        </div>

        <div class="border-t px-6 py-4 bg-gray-50 flex justify-end">
          <button type="button" onclick="closeAddTaxiRouteModal()" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 mr-2">
            Cancel
          </button>
          <button type="submit" name="add_taxi_route" class="bg-teal-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
            Add Route
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Taxi Route Modal -->
  <div id="editTaxiRouteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <div class="flex justify-between items-center border-b px-6 py-4">
        <h3 class="text-lg font-medium text-gray-900">Edit Taxi Route</h3>
        <button onclick="closeEditTaxiRouteModal()" class="text-gray-400 hover:text-gray-500">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form method="POST" action="">
        <input type="hidden" id="edit_taxi_route_id" name="taxi_route_id">
        <div class="p-6 space-y-4">
          <div>
            <label for="edit_taxi_route_number" class="block text-sm font-medium text-gray-700 mb-1">Route Number</label>
            <input type="number" id="edit_taxi_route_number" name="route_number" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm" required>
          </div>

          <div>
            <label for="edit_taxi_route_name" class="block text-sm font-medium text-gray-700 mb-1">Route Name</label>
            <input type="text" id="edit_taxi_route_name" name="route_name" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm" required>
          </div>

          <div>
            <label for="edit_taxi_camry_price" class="block text-sm font-medium text-gray-700 mb-1">Camry/Sonata Price (SR)</label>
            <input type="number" id="edit_taxi_camry_price" name="camry_sonata_price" step="0.01" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm" required>
          </div>

          <div>
            <label for="edit_taxi_starex_price" class="block text-sm font-medium text-gray-700 mb-1">Starex/Staria Price (SR)</label>
            <input type="number" id="edit_taxi_starex_price" name="starex_staria_price" step="0.01" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm" required>
          </div>

          <div>
            <label for="edit_taxi_hiace_price" class="block text-sm font-medium text-gray-700 mb-1">Hiace Price (SR)</label>
            <input type="number" id="edit_taxi_hiace_price" name="hiace_price" step="0.01" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm" required>
          </div>
        </div>

        <div class="border-t px-6 py-4 bg-gray-50 flex justify-end">
          <button type="button" onclick="closeEditTaxiRouteModal()" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 mr-2">
            Cancel
          </button>
          <button type="submit" name="update_taxi_route" class="bg-teal-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
            Update Route
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Add Rentacar Route Modal -->
  <div id="addRentacarRouteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <div class="flex justify-between items-center border-b px-6 py-4">
        <h3 class="text-lg font-medium text-gray-900">Add New Rent A Car Route</h3>
        <button onclick="closeAddRentacarRouteModal()" class="text-gray-400 hover:text-gray-500">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form method="POST" action="">
        <div class="p-6 space-y-4">
          <div>
            <label for="rentacar_route_number" class="block text-sm font-medium text-gray-700 mb-1">Route Number</label>
            <input type="number" id="rentacar_route_number" name="route_number" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
          </div>

          <div>
            <label for="rentacar_route_name" class="block text-sm font-medium text-gray-700 mb-1">Route Name</label>
            <input type="text" id="rentacar_route_name" name="route_name" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
          </div>

          <div>
            <label for="rentacar_gmc_16_19_price" class="block text-sm font-medium text-gray-700 mb-1">GMC 16-19 Price (SR)</label>
            <input type="number" id="rentacar_gmc_16_19_price" name="gmc_16_19_price" step="0.01" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
          </div>

          <div>
            <label for="rentacar_gmc_22_23_price" class="block text-sm font-medium text-gray-700 mb-1">GMC 22-23 Price (SR)</label>
            <input type="number" id="rentacar_gmc_22_23_price" name="gmc_22_23_price" step="0.01" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
          </div>

          <div>
            <label for="rentacar_coaster_price" class="block text-sm font-medium text-gray-700 mb-1">Coaster Price (SR)</label>
            <input type="number" id="rentacar_coaster_price" name="coaster_price" step="0.01" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
          </div>
        </div>

        <div class="border-t px-6 py-4 bg-gray-50 flex justify-end">
          <button type="button" onclick="closeAddRentacarRouteModal()" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mr-2">
            Cancel
          </button>
          <button type="submit" name="add_rentacar_route" class="bg-blue-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Add Route
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Rentacar Route Modal -->
  <div id="editRentacarRouteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <div class="flex justify-between items-center border-b px-6 py-4">
        <h3 class="text-lg font-medium text-gray-900">Edit Rent A Car Route</h3>
        <button onclick="closeEditRentacarRouteModal()" class="text-gray-400 hover:text-gray-500">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form method="POST" action="">
        <input type="hidden" id="edit_rentacar_route_id" name="rentacar_route_id">
        <div class="p-6 space-y-4">
          <div>
            <label for="edit_rentacar_route_number" class="block text-sm font-medium text-gray-700 mb-1">Route Number</label>
            <input type="number" id="edit_rentacar_route_number" name="route_number" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
          </div>

          <div>
            <label for="edit_rentacar_route_name" class="block text-sm font-medium text-gray-700 mb-1">Route Name</label>
            <input type="text" id="edit_rentacar_route_name" name="route_name" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
          </div>

          <div>
            <label for="edit_rentacar_gmc_16_19_price" class="block text-sm font-medium text-gray-700 mb-1">GMC 16-19 Price (SR)</label>
            <input type="number" id="edit_rentacar_gmc_16_19_price" name="gmc_16_19_price" step="0.01" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
          </div>

          <div>
            <label for="edit_rentacar_gmc_22_23_price" class="block text-sm font-medium text-gray-700 mb-1">GMC 22-23 Price (SR)</label>
            <input type="number" id="edit_rentacar_gmc_22_23_price" name="gmc_22_23_price" step="0.01" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
          </div>

          <div>
            <label for="edit_rentacar_coaster_price" class="block text-sm font-medium text-gray-700 mb-1">Coaster Price (SR)</label>
            <input type="number" id="edit_rentacar_coaster_price" name="coaster_price" step="0.01" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
          </div>
        </div>

        <div class="border-t px-6 py-4 bg-gray-50 flex justify-end">
          <button type="button" onclick="closeEditRentacarRouteModal()" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mr-2">
            Cancel
          </button>
          <button type="submit" name="update_rentacar_route" class="bg-blue-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Update Route
          </button>
        </div>
      </form>
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

      // Add active class to clicked button
      event.target.classList.add('active');
    }

    // Taxi Route Modal Functions
    function showAddTaxiRouteModal() {
      document.getElementById('addTaxiRouteModal').classList.remove('hidden');
      document.getElementById('addTaxiRouteModal').classList.add('flex');
    }

    function closeAddTaxiRouteModal() {
      document.getElementById('addTaxiRouteModal').classList.remove('flex');
      document.getElementById('addTaxiRouteModal').classList.add('hidden');
    }

    function showEditTaxiRouteModal(id, routeNumber, routeName, camryPrice, starexPrice, hiacePrice) {
      document.getElementById('edit_taxi_route_id').value = id;
      document.getElementById('edit_taxi_route_number').value = routeNumber;
      document.getElementById('edit_taxi_route_name').value = routeName;
      document.getElementById('edit_taxi_camry_price').value = camryPrice;
      document.getElementById('edit_taxi_starex_price').value = starexPrice;
      document.getElementById('edit_taxi_hiace_price').value = hiacePrice;

      document.getElementById('editTaxiRouteModal').classList.remove('hidden');
      document.getElementById('editTaxiRouteModal').classList.add('flex');
    }

    function closeEditTaxiRouteModal() {
      document.getElementById('editTaxiRouteModal').classList.remove('flex');
      document.getElementById('editTaxiRouteModal').classList.add('hidden');
    }

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

    // Rentacar Route Modal Functions
    function showAddRentacarRouteModal() {
      document.getElementById('addRentacarRouteModal').classList.remove('hidden');
      document.getElementById('addRentacarRouteModal').classList.add('flex');
    }

    function closeAddRentacarRouteModal() {
      document.getElementById('addRentacarRouteModal').classList.remove('flex');
      document.getElementById('addRentacarRouteModal').classList.add('hidden');
    }

    function showEditRentacarRouteModal(id, routeNumber, routeName, gmc16Price, gmc22Price, coasterPrice) {
      document.getElementById('edit_rentacar_route_id').value = id;
      document.getElementById('edit_rentacar_route_number').value = routeNumber;
      document.getElementById('edit_rentacar_route_name').value = routeName;
      document.getElementById('edit_rentacar_gmc_16_19_price').value = gmc16Price;
      document.getElementById('edit_rentacar_gmc_22_23_price').value = gmc22Price;
      document.getElementById('edit_rentacar_coaster_price').value = coasterPrice;

      document.getElementById('editRentacarRouteModal').classList.remove('hidden');
      document.getElementById('editRentacarRouteModal').classList.add('flex');
    }

    function closeEditRentacarRouteModal() {
      document.getElementById('editRentacarRouteModal').classList.remove('flex');
      document.getElementById('editRentacarRouteModal').classList.add('hidden');
    }

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

    // Mobile menu toggle
    document.addEventListener('DOMContentLoaded', function() {
      const menuBtn = document.getElementById('menu-btn');
      const sidebar = document.querySelector('.sidebar');

      if (menuBtn && sidebar) {
        menuBtn.addEventListener('click', function() {
          sidebar.classList.toggle('hidden');
          sidebar.classList.toggle('flex');
        });
      }
    });
  </script>

  <?php include 'includes/js-links.php'; ?>
</body>

</html>