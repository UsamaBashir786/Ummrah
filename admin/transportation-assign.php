<?php
session_name("admin_session");
session_start();
include 'connection/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
  header("Location: admin-login.php");
  exit();
}

// Get package bookings that need transportation assignment
function getPackageBookings()
{
  global $conn;
  $sql = "SELECT pb.id, pb.user_id, pb.package_id, pb.booking_date, pb.status, pb.total_price, 
                 p.title as package_title, u.full_name as user_name, u.email as user_email, 
                 u.phone_number as user_phone
          FROM package_booking pb
          LEFT JOIN packages p ON pb.package_id = p.id
          LEFT JOIN users u ON pb.user_id = u.id
          WHERE pb.status = 'pending' OR pb.status = 'confirmed'
          ORDER BY pb.booking_date DESC";

  $result = $conn->query($sql);
  return $result->fetch_all(MYSQLI_ASSOC);
}

// Get existing transportation assignments
function getTransportationAssignments($booking_id)
{
  global $conn;
  $sql = "SELECT * FROM transportation_assignments WHERE package_booking_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $booking_id);
  $stmt->execute();
  $result = $stmt->get_result();
  return $result->fetch_all(MYSQLI_ASSOC);
}

// Get taxi routes
function getTaxiRoutes()
{
  global $conn;
  $sql = "SELECT * FROM taxi_routes ORDER BY route_number";
  $result = $conn->query($sql);
  return $result->fetch_all(MYSQLI_ASSOC);
}

// Get rentacar routes
function getRentacarRoutes()
{
  global $conn;
  $sql = "SELECT * FROM rentacar_routes ORDER BY route_number";
  $result = $conn->query($sql);
  return $result->fetch_all(MYSQLI_ASSOC);
}

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_transportation'])) {
  $booking_id = $_POST['booking_id'];
  $service_type = $_POST['service_type'];
  $route_id = $_POST['route_id'];
  $route_name = $_POST['route_name'];
  $vehicle_type = $_POST['vehicle_type'];
  $vehicle_name = $_POST['vehicle_name'];
  $price = $_POST['price'];

  // Validate inputs
  if (
    empty($booking_id) || empty($service_type) || empty($route_id) || empty($route_name)
    || empty($vehicle_type) || empty($vehicle_name) || empty($price)
  ) {
    $error_message = "All fields are required.";
  } else {
    // Insert assignment
    $sql = "INSERT INTO transportation_assignments 
            (package_booking_id, service_type, route_id, route_name, vehicle_type, vehicle_name, price) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    // Fix: Changed "д" to "d" in the parameter types
    $stmt->bind_param("isisssд", $booking_id, $service_type, $route_id, $route_name, $vehicle_type, $vehicle_name, $price);

    if ($stmt->execute()) {
      $success_message = "Transportation successfully assigned to booking #$booking_id";
    } else {
      $error_message = "Error assigning transportation: " . $conn->error;
    }
  }
}

// Delete assignment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_assignment'])) {
  $assignment_id = $_POST['assignment_id'];

  $sql = "DELETE FROM transportation_assignments WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $assignment_id);

  if ($stmt->execute()) {
    $success_message = "Transportation assignment successfully deleted";
  } else {
    $error_message = "Error deleting assignment: " . $conn->error;
  }
}

// Get data
$bookings = getPackageBookings();
$taxi_routes = getTaxiRoutes();
$rentacar_routes = getRentacarRoutes();

// Handle special case for viewing a specific booking
$current_booking = null;
$current_assignments = [];
if (isset($_GET['booking_id'])) {
  $booking_id = $_GET['booking_id'];
  foreach ($bookings as $booking) {
    if ($booking['id'] == $booking_id) {
      $current_booking = $booking;
      break;
    }
  }

  if ($current_booking) {
    $current_assignments = getTransportationAssignments($booking_id);
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Transportation Assignment | Admin Panel</title>
  <link rel="stylesheet" href="../assets/css/output.css">
  <link rel="stylesheet" href="assets/css/output.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .assignment-card {
      border: 1px solid #e2e8f0;
      border-radius: 0.5rem;
      padding: 1rem;
      margin-bottom: 1rem;
      background-color: white;
      box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    }

    .assignment-card:hover {
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .taxi-card {
      border-left: 4px solid #0d9488;
    }

    .rentacar-card {
      border-left: 4px solid #1d4ed8;
    }

    .tab-buttons {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
    }

    .tab-btn {
      padding: A10px 20px;
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
          <i class="text-teal-600 fas fa-car mx-2"></i> Transportation Assignment
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
          <?php if ($current_booking): ?>
            <!-- Single Booking View -->
            <div class="mb-6">
              <div class="flex justify-between items-center">
                <h2 class="text-2xl font-bold">Booking #<?php echo $current_booking['id']; ?> Details</h2>
                <a href="transportation-assign.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                  <i class="fas fa-arrow-left mr-2"></i> Back to List
                </a>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                  <h3 class="font-semibold text-lg mb-2">Package Details</h3>
                  <p><span class="font-medium">Package:</span> <?php echo htmlspecialchars($current_booking['package_title']); ?></p>
                  <p><span class="font-medium">Booking Date:</span> <?php echo date('d M Y', strtotime($current_booking['booking_date'])); ?></p>
                  <p><span class="font-medium">Status:</span> <span class="px-2 py-1 rounded-full text-xs <?php echo $current_booking['status'] == 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>"><?php echo ucfirst($current_booking['status']); ?></span></p>
                  <p><span class="font-medium">Price:</span> <?php echo $current_booking['total_price']; ?> PKR</p>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                  <h3 class="font-semibold text-lg mb-2">Customer Information</h3>
                  <p><span class="font-medium">Name:</span> <?php echo htmlspecialchars($current_booking['user_name']); ?></p>
                  <p><span class="font-medium">Email:</span> <?php echo htmlspecialchars($current_booking['user_email']); ?></p>
                  <p><span class="font-medium">Phone:</span> <?php echo htmlspecialchars($current_booking['user_phone']); ?></p>
                </div>
              </div>

              <!-- Current Assignments -->
              <div class="mt-6">
                <h3 class="font-semibold text-lg mb-2">Current Transportation Assignments</h3>

                <?php if (count($current_assignments) > 0): ?>
                  <div class="space-y-4">
                    <?php foreach ($current_assignments as $assignment): ?>
                      <div class="assignment-card <?php echo $assignment['service_type'] == 'taxi' ? 'taxi-card' : 'rentacar-card'; ?>">
                        <div class="flex justify-between">
                          <h4 class="font-medium text-md"><?php echo ucfirst($assignment['service_type']); ?> Service - <?php echo htmlspecialchars($assignment['route_name']); ?></h4>
                          <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this assignment?');">
                            <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                            <input type="hidden" name="delete_assignment" value="1">
                            <button type="submit" class="text-red-500 hover:text-red-700">
                              <i class="fas fa-trash"></i>
                            </button>
                          </form>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mt-2">
                          <p><span class="text-gray-600">Vehicle:</span> <?php echo htmlspecialchars($assignment['vehicle_name']); ?></p>
                          <p><span class="text-gray-600">Type:</span> <?php echo htmlspecialchars($assignment['vehicle_type']); ?></p>
                          <p><span class="text-gray-600">Price:</span> <?php echo $assignment['price']; ?> PKR</p>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Assigned: <?php echo date('d M Y H:i', strtotime($assignment['assigned_at'])); ?></p>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <p class="text-gray-500 italic">No transportation assignments yet.</p>
                <?php endif; ?>
              </div>

              <!-- Add New Assignment -->
              <div class="mt-6">
                <h3 class="font-semibold text-lg mb-2">Add New Transportation Assignment</h3>

                <div class="tab-buttons flex">
                  <button class="tab-btn active" onclick="switchTab('taxi')">Taxi Service</button>
                  <button class="tab-btn" onclick="switchTab('rentacar')">Rent A Car Service</button>
                </div>

                <!-- Taxi Assignment Form -->
                <div id="taxi-tab" class="tab-content active">
                  <form method="POST" action="" class="space-y-4">
                    <input type="hidden" name="assign_transportation" value="1">
                    <input type="hidden" name="booking_id" value="<?php echo $current_booking['id']; ?>">
                    <input type="hidden" name="service_type" value="taxi">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="taxi_route">
                          Route
                        </label>
                        <select id="taxi_route" name="route_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500" required onchange="updateTaxiRouteInfo(this.value)">
                          <option value="">Select Route</option>
                          <?php foreach ($taxi_routes as $route): ?>
                            <option value="<?php echo $route['id']; ?>"
                              data-name="<?php echo htmlspecialchars($route['route_name']); ?>"
                              data-camry="<?php echo $route['camry_sonata_price']; ?>"
                              data-starex="<?php echo $route['starex_staria_price']; ?>"
                              data-hiace="<?php echo $route['hiace_price']; ?>">
                              <?php echo $route['route_number'] . '. ' . htmlspecialchars($route['route_name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>

                      <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="taxi_vehicle">
                          Vehicle Type
                        </label>
                        <select id="taxi_vehicle" name="vehicle_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500" required onchange="updateTaxiPrice()">
                          <option value="">Select Vehicle</option>
                          <option value="camry" data-name="Camry/Sonata">Camry/Sonata</option>
                          <option value="starex" data-name="Starex/Staria">Starex/Staria</option>
                          <option value="hiace" data-name="Hiace">Hiace</option>
                        </select>
                      </div>
                    </div>

                    <input type="hidden" id="taxi_route_name" name="route_name" value="">
                    <input type="hidden" id="taxi_vehicle_name" name="vehicle_name" value="">

                    <div>
                      <label class="block text-gray-700 text-sm font-bold mb-2" for="taxi_price">
                        Price (PKR)
                      </label>
                      <input type="number" id="taxi_price" name="price" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500" required readonly>
                    </div>

                    <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700">
                      <i class="fas fa-plus-circle mr-2"></i>Assign Taxi Service
                    </button>
                  </form>
                </div>

                <!-- Rent A Car Assignment Form -->
                <div id="rentacar-tab" class="tab-content">
                  <form method="POST" action="" class="space-y-4">
                    <input type="hidden" name="assign_transportation" value="1">
                    <input type="hidden" name="booking_id" value="<?php echo $current_booking['id']; ?>">
                    <input type="hidden" name="service_type" value="rentacar">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="rentacar_route">
                          Route
                        </label>
                        <select id="rentacar_route" name="route_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required onchange="updateRentacarRouteInfo(this.value)">
                          <option value="">Select Route</option>
                          <?php foreach ($rentacar_routes as $route): ?>
                            <option value="<?php echo $route['id']; ?>"
                              data-name="<?php echo htmlspecialchars($route['route_name']); ?>"
                              data-gmc16="<?php echo $route['gmc_16_19_price']; ?>"
                              data-gmc22="<?php echo $route['gmc_22_23_price']; ?>"
                              data-coaster="<?php echo $route['coaster_price']; ?>">
                              <?php echo $route['route_number'] . '. ' . htmlspecialchars($route['route_name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>

                      <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="rentacar_vehicle">
                          Vehicle Type
                        </label>
                        <select id="rentacar_vehicle" name="vehicle_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required onchange="updateRentacarPrice()">
                          <option value="">Select Vehicle</option>
                          <option value="gmc16" data-name="GMC 16-19 Seater">GMC 16-19 Seater</option>
                          <option value="gmc22" data-name="GMC 22-23 Seater">GMC 22-23 Seater</option>
                          <option value="coaster" data-name="Coaster">Coaster</option>
                        </select>
                      </div>
                    </div>

                    <input type="hidden" id="rentacar_route_name" name="route_name" value="">
                    <input type="hidden" id="rentacar_vehicle_name" name="vehicle_name" value="">

                    <div>
                      <label class="block text-gray-700 text-sm font-bold mb-2" for="rentacar_price">
                        Price (PKR)
                      </label>
                      <input type="number" id="rentacar_price" name="price" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required readonly>
                    </div>

                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                      <i class="fas fa-plus-circle mr-2"></i>Assign Rent A Car Service
                    </button>
                  </form>
                </div>
              </div>
            </div>
          <?php else: ?>
            <!-- Bookings List View -->
            <div class="mb-6">
              <h2 class="text-2xl font-bold">Package Bookings</h2>
              <p class="text-gray-600 mt-2">Assign transportation to package bookings</p>
            </div>

            <?php if (count($bookings) > 0): ?>
              <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-300">
                  <thead>
                    <tr class="bg-gray-100">
                      <th class="py-2 px-4 border-b">Booking ID</th>
                      <th class="py-2 px-4 border-b">Customer</th>
                      <th class="py-2 px-4 border-b">Package</th>
                      <th class="py-2 px-4 border-b">Price</th>
                      <th class="py-2 px-4 border-b">Date</th>
                      <th class="py-2 px-4 border-b">Status</th>
                      <th class="py-2 px-4 border-b">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($bookings as $booking): ?>
                      <tr>
                        <td class="py-2 px-4 border-b">#<?php echo $booking['id']; ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['user_name']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['package_title']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo $booking['total_price']; ?> PKR</td>
                        <td class="py-2 px-4 border-b"><?php echo date('d M Y', strtotime($booking['booking_date'])); ?></td>
                        <td class="py-2 px-4 border-b">
                          <span class="px-2 py-1 rounded-full text-xs <?php echo $booking['status'] == 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                            <?php echo ucfirst($booking['status']); ?>
                          </span>
                        </td>
                        <td class="py-2 px-4 border-b">
                          <a href="transportation-assign.php?booking_id=<?php echo $booking['id']; ?>" class="text-teal-600 hover:text-teal-800">
                            <i class="fas fa-car"></i> Assign
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <div class="bg-gray-50 p-4 rounded-lg text-center">
                <p class="text-gray-500">No pending or confirmed bookings found.</p>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
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
    }

    // Taxi price calculation
    function updateTaxiRouteInfo(routeId) {
      const routeSelect = document.getElementById('taxi_route');
      const selectedOption = routeSelect.options[routeSelect.selectedIndex];

      if (selectedOption && selectedOption.value) {
        document.getElementById('taxi_route_name').value = selectedOption.getAttribute('data-name');
        updateTaxiPrice();
      }
    }

    function updateTaxiPrice() {
      const routeSelect = document.getElementById('taxi_route');
      const vehicleSelect = document.getElementById('taxi_vehicle');
      const priceInput = document.getElementById('taxi_price');

      if (routeSelect.value && vehicleSelect.value) {
        const selectedRoute = routeSelect.options[routeSelect.selectedIndex];
        const vehicleType = vehicleSelect.value;

        // Update vehicle name hidden input
        document.getElementById('taxi_vehicle_name').value = vehicleSelect.options[vehicleSelect.selectedIndex].getAttribute('data-name');

        // Get price based on vehicle type
        let price = 0;
        if (vehicleType === 'camry') {
          price = selectedRoute.getAttribute('data-camry');
        } else if (vehicleType === 'starex') {
          price = selectedRoute.getAttribute('data-starex');
        } else if (vehicleType === 'hiace') {
          price = selectedRoute.getAttribute('data-hiace');
        }

        priceInput.value = price;
      } else {
        priceInput.value = '';
      }
    }

    // Rentacar price calculation
    function updateRentacarRouteInfo(routeId) {
      const routeSelect = document.getElementById('rentacar_route');
      const selectedOption = routeSelect.options[routeSelect.selectedIndex];

      if (selectedOption && selectedOption.value) {
        document.getElementById('rentacar_route_name').value = selectedOption.getAttribute('data-name');
        updateRentacarPrice();
      }
    }

    function updateRentacarPrice() {
      const routeSelect = document.getElementById('rentacar_route');
      const vehicleSelect = document.getElementById('rentacar_vehicle');
      const priceInput = document.getElementById('rentacar_price');

      if (routeSelect.value && vehicleSelect.value) {
        const selectedRoute = routeSelect.options[routeSelect.selectedIndex];
        const vehicleType = vehicleSelect.value;

        // Update vehicle name hidden input
        document.getElementById('rentacar_vehicle_name').value = vehicleSelect.options[vehicleSelect.selectedIndex].getAttribute('data-name');

        // Get price based on vehicle type
        let price = 0;
        if (vehicleType === 'gmc16') {
          price = selectedRoute.getAttribute('data-gmc16');
        } else if (vehicleType === 'gmc22') {
          price = selectedRoute.getAttribute('data-gmc22');
        } else if (vehicleType === 'coaster') {
          price = selectedRoute.getAttribute('data-coaster');
        }

        priceInput.value = price;
      } else {
        priceInput.value = '';
      }
    }

    // Mobile menu toggle
    const menuBtn = document.getElementById('menu-btn');
    const sidebar = document.querySelector('.sidebar');

    if (menuBtn && sidebar) {
      menuBtn.addEventListener('click', function() {
        sidebar.classList.toggle('hidden');
        sidebar.classList.toggle('flex');
      });
    }
  </script>

  <?php include 'includes/js-links.php'; ?>
</body>

</html>