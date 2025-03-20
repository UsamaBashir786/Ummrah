<?php
session_start();
include 'connection/connection.php'; // Include database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Check which form was submitted
  if (isset($_POST['form_type']) && $_POST['form_type'] == 'taxi') {
    // Get form data for taxi service
    $service_title = $_POST['serviceTitle'];
    $year = $_POST['year'];
    
    // Prepare for batch insert
    $route_values = [];
    $route_count = count($_POST['route_name']);
    
    for ($i = 0; $i < $route_count; $i++) {
      if (!empty($_POST['route_name'][$i])) {
        $route_name = mysqli_real_escape_string($conn, $_POST['route_name'][$i]);
        $camry_price = isset($_POST['camry_price'][$i]) ? $_POST['camry_price'][$i] : 0;
        $starex_price = isset($_POST['starex_price'][$i]) ? $_POST['starex_price'][$i] : 0;
        $hiace_price = isset($_POST['hiace_price'][$i]) ? $_POST['hiace_price'][$i] : 0;
        
        $route_values[] = "('$service_title', $year, $i+1, '$route_name', $camry_price, $starex_price, $hiace_price, NOW())";
      }
    }
    
    if (!empty($route_values)) {
      // Insert all routes at once
      $sql = "INSERT INTO taxi_routes 
              (service_title, year, route_number, route_name, camry_sonata_price, starex_staria_price, hiace_price, created_at) 
              VALUES " . implode(',', $route_values);
      
      if (mysqli_query($conn, $sql)) {
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Taxi service price list saved successfully!',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'view-price-lists.php';
                    });
                });
              </script>";
      } else {
        $error_message = "Database error: " . mysqli_error($conn);
      }
    } else {
      $error_message = "Please add at least one route with prices.";
    }
  } elseif (isset($_POST['form_type']) && $_POST['form_type'] == 'rentacar') {
    // Get form data for rent a car service
    $service_title = $_POST['serviceTitle'];
    $year = $_POST['year'];
    
    // Prepare for batch insert
    $route_values = [];
    $route_count = count($_POST['route_name']);
    
    for ($i = 0; $i < $route_count; $i++) {
      if (!empty($_POST['route_name'][$i])) {
        $route_name = mysqli_real_escape_string($conn, $_POST['route_name'][$i]);
        $gmc_16_19_price = isset($_POST['gmc_16_19_price'][$i]) ? $_POST['gmc_16_19_price'][$i] : 0;
        $gmc_22_23_price = isset($_POST['gmc_22_23_price'][$i]) ? $_POST['gmc_22_23_price'][$i] : 0;
        $coaster_price = isset($_POST['coaster_price'][$i]) ? $_POST['coaster_price'][$i] : 0;
        
        $route_values[] = "('$service_title', $year, $i+1, '$route_name', $gmc_16_19_price, $gmc_22_23_price, $coaster_price, NOW())";
      }
    }
    
    if (!empty($route_values)) {
      // Insert all routes at once
      $sql = "INSERT INTO rentacar_routes 
              (service_title, year, route_number, route_name, gmc_16_19_price, gmc_22_23_price, coaster_price, created_at) 
              VALUES " . implode(',', $route_values);
      
      if (mysqli_query($conn, $sql)) {
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Rent a car price list saved successfully!',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'view-price-lists.php';
                    });
                });
              </script>";
      } else {
        $error_message = "Database error: " . mysqli_error($conn);
      }
    } else {
      $error_message = "Please add at least one route with prices.";
    }
  }
  
  // Display error message if any
  if (isset($error_message)) {
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Error!',
                    text: '$error_message',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        </script>";
  }
}
?>
<!-- Include SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
</head>

<body class="bg-gray-50">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main flex-1 flex flex-col">
      <!-- Navbar -->
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <button class="md:hidden text-gray-800" id="menu-btn">
          <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-xl font-semibold">
          <i class="text-teal-600 fas fa-list-alt mx-2"></i> Price List Management
        </h1>
      </div>

      <!-- Form Container -->
      <div class="overflow-auto container mx-auto px-4 py-8">
        <!-- Taxi Service Price List Form -->
        <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg mb-8">
          <h2 class="text-2xl font-bold text-gray-800 mb-6">
            <i class="fas fa-taxi text-yellow-500 mr-2"></i> Taxi Service Price List
          </h2>
          <p class="text-gray-600 mb-4">Enter prices for Umrah and Hajj taxi services across different routes</p>

          <form action="" method="POST">
            <input type="hidden" name="form_type" value="taxi">

            <!-- Service Title and Year -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="serviceTitle">
                  Service Title
                </label>
                <input type="text" id="serviceTitle" name="serviceTitle"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500"
                  value="Best Taxi Service for Umrah and Hajj in Makkah, Madinah and Jeddah" required>
              </div>
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="year">
                  Year
                </label>
                <input type="number" id="year" name="year" min="2024" max="2030"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500"
                  value="2024" required>
              </div>
            </div>
            
            <!-- Price List Table -->
            <div class="mb-6 overflow-x-auto">
              <table class="min-w-full bg-white border border-gray-300 mb-4">
                <thead>
                  <tr class="bg-gray-100">
                    <th class="py-2 px-4 border-b w-16 text-center">#</th>
                    <th class="py-2 px-4 border-b">Route</th>
                    <th class="py-2 px-4 border-b text-center">Camry / Sonata</th>
                    <th class="py-2 px-4 border-b text-center">Starex / Staria</th>
                    <th class="py-2 px-4 border-b text-center">Hiace</th>
                    <th class="py-2 px-4 border-b w-16 text-center">Action</th>
                  </tr>
                </thead>
                <tbody id="taxi-routes">
                  <!-- Initial row -->
                  <tr>
                    <td class="py-2 px-4 border-b text-center">1</td>
                    <td class="py-2 px-4 border-b">
                      <input type="text" name="route_name[]" placeholder="Enter route name" 
                        class="w-full px-3 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </td>
                    <td class="py-2 px-4 border-b">
                      <input type="number" name="camry_price[]" min="0" step="0.01" placeholder="Price" 
                        class="w-full px-3 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </td>
                    <td class="py-2 px-4 border-b">
                      <input type="number" name="starex_price[]" min="0" step="0.01" placeholder="Price" 
                        class="w-full px-3 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </td>
                    <td class="py-2 px-4 border-b">
                      <input type="number" name="hiace_price[]" min="0" step="0.01" placeholder="Price" 
                        class="w-full px-3 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </td>
                    <td class="py-2 px-4 border-b text-center">
                      <button type="button" class="text-red-500 hover:text-red-700 delete-row" disabled>
                        <i class="fas fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
              
              <!-- Add Row Button -->
              <button type="button" id="add-taxi-row" class="px-4 py-2 bg-teal-600 text-white rounded-md hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                <i class="fas fa-plus-circle mr-2"></i> Add Route
              </button>
            </div>

            <!-- Submit Buttons -->
            <div class="flex gap-4">
              <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700">
                <i class="fas fa-save mr-2"></i>Save Price List
              </button>
              <button type="button" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">
                <i class="fas fa-times mr-2"></i>Cancel
              </button>
            </div>
          </form>
        </div>
        
        <!-- Rent A Car Price List Form -->
        <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">
          <h2 class="text-2xl font-bold text-gray-800 mb-6">
            <i class="fas fa-car text-blue-500 mr-2"></i> Rent A Car Price List
          </h2>
          <p class="text-gray-600 mb-4">Enter prices for Umrah and Hajj Rent A Car services across different routes</p>

          <form action="" method="POST">
            <input type="hidden" name="form_type" value="rentacar">

            <!-- Service Title and Year -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="serviceTitle2">
                  Service Title
                </label>
                <input type="text" id="serviceTitle2" name="serviceTitle"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  value="Best Umrah and Hajj Rent A Car in Makkah, Madinah and Jeddah" required>
              </div>
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="year2">
                  Year
                </label>
                <input type="number" id="year2" name="year" min="2024" max="2030"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  value="2024" required>
              </div>
            </div>
            
            <!-- Price List Table -->
            <div class="mb-6 overflow-x-auto">
              <table class="min-w-full bg-white border border-gray-300 mb-4">
                <thead>
                  <tr class="bg-gray-100">
                    <th class="py-2 px-4 border-b w-16 text-center">#</th>
                    <th class="py-2 px-4 border-b">Route</th>
                    <th class="py-2 px-4 border-b text-center">GMC 16-19</th>
                    <th class="py-2 px-4 border-b text-center">GMC 22-23</th>
                    <th class="py-2 px-4 border-b text-center">COASTER</th>
                    <th class="py-2 px-4 border-b w-16 text-center">Action</th>
                  </tr>
                </thead>
                <tbody id="rentacar-routes">
                  <!-- Initial row -->
                  <tr>
                    <td class="py-2 px-4 border-b text-center">1</td>
                    <td class="py-2 px-4 border-b">
                      <input type="text" name="route_name[]" placeholder="Enter route name" 
                        class="w-full px-3 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </td>
                    <td class="py-2 px-4 border-b">
                      <input type="number" name="gmc_16_19_price[]" min="0" step="0.01" placeholder="Price" 
                        class="w-full px-3 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </td>
                    <td class="py-2 px-4 border-b">
                      <input type="number" name="gmc_22_23_price[]" min="0" step="0.01" placeholder="Price" 
                        class="w-full px-3 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </td>
                    <td class="py-2 px-4 border-b">
                      <input type="number" name="coaster_price[]" min="0" step="0.01" placeholder="Price" 
                        class="w-full px-3 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </td>
                    <td class="py-2 px-4 border-b text-center">
                      <button type="button" class="text-red-500 hover:text-red-700 delete-row" disabled>
                        <i class="fas fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
              
              <!-- Add Row Button -->
              <button type="button" id="add-rentacar-row" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <i class="fas fa-plus-circle mr-2"></i> Add Route
              </button>
            </div>

            <!-- Submit Buttons -->
            <div class="flex gap-4">
              <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i>Save Price List
              </button>
              <button type="button" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">
                <i class="fas fa-times mr-2"></i>Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/js-links.php'; ?>
  <script>
    // Function to add new rows to taxi table
    document.getElementById('add-taxi-row').addEventListener('click', function() {
      const tbody = document.getElementById('taxi-routes');
      const rowCount = tbody.rows.length;
      const newRow = document.createElement('tr');
      
      newRow.innerHTML = `
        <td class="py-2 px-4 border-b text-center">${rowCount + 1}</td>
        <td class="py-2 px-4 border-b">
          <input type="text" name="route_name[]" placeholder="Enter route name" 
            class="w-full px-3 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-teal-500">
        </td>
        <td class="py-2 px-4 border-b">
          <input type="number" name="camry_price[]" min="0" step="0.01" placeholder="Price" 
            class="w-full px-3 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-teal-500">
        </td>
        <td class="py-2 px-4 border-b">
          <input type="number" name="starex_price[]" min="0" step="0.01" placeholder="Price" 
            class="w-full px-3 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-teal-500">
        </td>
        <td class="py-2 px-4 border-b">
          <input type="number" name="hiace_price[]" min="0" step="0.01" placeholder="Price" 
            class="w-full px-3 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-teal-500">
        </td>
        <td class="py-2 px-4 border-b text-center">
          <button type="button" class="text-red-500 hover:text-red-700 delete-row">
            <i class="fas fa-trash"></i>
          </button>
        </td>
      `;
      
      tbody.appendChild(newRow);
      setupDeleteHandlers();
    });
    
    // Function to add new rows to rentacar table
    document.getElementById('add-rentacar-row').addEventListener('click', function() {
      const tbody = document.getElementById('rentacar-routes');
      const rowCount = tbody.rows.length;
      const newRow = document.createElement('tr');
      
      newRow.innerHTML = `
        <td class="py-2 px-4 border-b text-center">${rowCount + 1}</td>
        <td class="py-2 px-4 border-b">
          <input type="text" name="route_name[]" placeholder="Enter route name" 
            class="w-full px-3 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
        </td>
        <td class="py-2 px-4 border-b">
          <input type="number" name="gmc_16_19_price[]" min="0" step="0.01" placeholder="Price" 
            class="w-full px-3 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
        </td>
        <td class="py-2 px-4 border-b">
          <input type="number" name="gmc_22_23_price[]" min="0" step="0.01" placeholder="Price" 
            class="w-full px-3 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
        </td>
        <td class="py-2 px-4 border-b">
          <input type="number" name="coaster_price[]" min="0" step="0.01" placeholder="Price" 
            class="w-full px-3 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
        </td>
        <td class="py-2 px-4 border-b text-center">
          <button type="button" class="text-red-500 hover:text-red-700 delete-row">
            <i class="fas fa-trash"></i>
          </button>
        </td>
      `;
      
      tbody.appendChild(newRow);
      setupDeleteHandlers();
    });
    
    // Setup delete row handlers
    function setupDeleteHandlers() {
      document.querySelectorAll('.delete-row').forEach(button => {
        if (!button.hasAttribute('disabled')) {
          button.addEventListener('click', function() {
            const row = this.closest('tr');
            const tbody = row.parentNode;
            tbody.removeChild(row);
            
            // Update row numbers
            const rows = tbody.querySelectorAll('tr');
            rows.forEach((row, index) => {
              row.cells[0].textContent = index + 1;
            });
          });
        }
      });
    }
    
    // Initial setup
    setupDeleteHandlers();
  </script>
</body>
</html>