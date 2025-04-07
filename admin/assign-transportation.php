<?php
session_name("admin_session");
session_start();
include 'connection/connection.php'; // Include database connection

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle form submission for assigning transportation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_transport'])) {
    $booking_type = $_POST['booking_type'];
    $user_id = $_POST['user_id'];
    $vehicle_id = $_POST['vehicle_id'];
    $driver_name = $_POST['driver_name'];
    $driver_contact = $_POST['driver_contact'];
    
    // Format the pickup datetime
    $pickup_date = $_POST['pickup_date'];
    $pickup_time = $_POST['pickup_time'];
    $pickup_datetime = $pickup_date . ' ' . $pickup_time;
    
    $admin_notes = $_POST['admin_notes'];
    
    if ($booking_type == 'transportation') {
        // This is a direct transportation booking
        $booking_id = $_POST['booking_id'];
        $booking_reference = $_POST['booking_reference'];
        $service_type = $_POST['service_type'];
        $route_id = $_POST['route_id'];
        
        // Insert the assignment into the transportation_assign table
        $sql = "INSERT INTO transportation_assign 
                (booking_id, booking_reference, user_id, service_type, route_id, 
                vehicle_id, driver_name, driver_contact, pickup_time, admin_notes, status, booking_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'assigned', 'transportation')";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssssss", $booking_id, $booking_reference, $user_id, $service_type, 
                        $route_id, $vehicle_id, $driver_name, $driver_contact, $pickup_datetime, $admin_notes);
        
        if ($stmt->execute()) {
            // Update the transportation_bookings table status
            $update_sql = "UPDATE transportation_bookings SET booking_status = 'confirmed' WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $booking_id);
            $update_stmt->execute();
            
            $success_message = "Transportation successfully assigned!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
    } else if ($booking_type == 'package') {
        // This is a package booking
        $package_booking_id = $_POST['package_booking_id'];
        $package_id = $_POST['package_id'];
        $route_name = $_POST['route_name'];
        $vehicle_type = $_POST['vehicle_type']; 
        
        // Generate a booking reference for the new transportation
        $booking_reference = 'PKG' . strtoupper(substr(md5(uniqid()), 0, 8));
        
        // First insert a record in transportation_bookings
        $insert_booking_sql = "INSERT INTO transportation_bookings 
                            (user_id, booking_reference, service_type, route_id, route_name, 
                            vehicle_type, vehicle_name, price, booking_date, booking_time, 
                            pickup_location, dropoff_location, passengers, booking_status, payment_status, created_at) 
                            VALUES (?, ?, 'taxi', 0, ?, ?, ?, 0, ?, ?, ?, ?, 1, 'confirmed', 'paid', NOW())";
        
        $insert_booking_stmt = $conn->prepare($insert_booking_sql);
        $vehicle_name = ucfirst($vehicle_type);
        $pickup_location = $_POST['pickup_location'];
        $dropoff_location = $_POST['dropoff_location'];
        
        // Bind 9 parameters to match the 9 placeholders in the SQL query
        $insert_booking_stmt->bind_param("issssssss", 
                            $user_id,              // user_id
                            $booking_reference,    // booking_reference
                            $route_name,           // route_name
                            $vehicle_type,         // vehicle_type
                            $vehicle_name,         // vehicle_name
                            $pickup_date,          // booking_date
                            $pickup_time,          // booking_time
                            $pickup_location,      // pickup_location
                            $dropoff_location);    // dropoff_location
        
        if ($insert_booking_stmt->execute()) {
            $new_booking_id = $conn->insert_id;
            
            // Now insert the transportation assignment
            $sql = "INSERT INTO transportation_assign 
                    (booking_id, booking_reference, user_id, service_type, route_id, 
                    vehicle_id, driver_name, driver_contact, pickup_time, admin_notes, status, booking_type, package_booking_id) 
                    VALUES (?, ?, ?, 'taxi', 0, ?, ?, ?, ?, ?, 'assigned', 'package', ?)";
            
            $stmt = $conn->prepare($sql);
            $taxi_service = 'taxi';
            $stmt->bind_param("ississssi", $new_booking_id, $booking_reference, $user_id, 
                            $vehicle_id, $driver_name, $driver_contact, $pickup_datetime, $admin_notes, $package_booking_id);
            
            if ($stmt->execute()) {
                // Update the package_assign table
                $check_assign_sql = "SELECT id FROM package_assign WHERE booking_id = ? AND user_id = ?";
                $check_assign_stmt = $conn->prepare($check_assign_sql);
                $check_assign_stmt->bind_param("ii", $package_booking_id, $user_id);
                $check_assign_stmt->execute();
                $check_result = $check_assign_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    // Update existing assignment
                    $assign_row = $check_result->fetch_assoc();
                    $update_assign_sql = "UPDATE package_assign SET transport_id = ?, transport_seat_number = ? WHERE id = ?";
                    $update_assign_stmt = $conn->prepare($update_assign_sql);
                    $update_assign_stmt->bind_param("isi", $new_booking_id, $vehicle_id, $assign_row['id']);
                    $update_assign_stmt->execute();
                } else {
                    // Insert new assignment
                    $insert_assign_sql = "INSERT INTO package_assign (booking_id, user_id, transport_id, transport_seat_number) 
                                        VALUES (?, ?, ?, ?)";
                    $insert_assign_stmt = $conn->prepare($insert_assign_sql);
                    $insert_assign_stmt->bind_param("iiis", $package_booking_id, $user_id, $new_booking_id, $vehicle_id);
                    $insert_assign_stmt->execute();
                }
                
                $success_message = "Transportation successfully assigned to package booking!";
            } else {
                $error_message = "Error assigning transportation: " . $stmt->error;
            }
        } else {
            $error_message = "Error creating transportation booking: " . $insert_booking_stmt->error;
        }
    }
}

// Get all pending transportation bookings
$pending_bookings_sql = "SELECT tb.*, u.full_name, u.email, u.phone_number 
                         FROM transportation_bookings tb
                         JOIN users u ON tb.user_id = u.id
                         WHERE tb.booking_status = 'pending'
                         ORDER BY tb.booking_date ASC, tb.booking_time ASC";
$pending_bookings_result = $conn->query($pending_bookings_sql);

// Get all assigned transportation
$assigned_sql = "SELECT ta.*, tb.vehicle_type, tb.vehicle_name, tb.route_name, 
                 tb.booking_date, tb.booking_time, tb.pickup_location, tb.dropoff_location,
                 u.full_name, u.email, u.phone_number
                 FROM transportation_assign ta
                 JOIN transportation_bookings tb ON ta.booking_id = tb.id
                 JOIN users u ON ta.user_id = u.id
                 WHERE ta.status = 'assigned'
                 ORDER BY tb.booking_date ASC, tb.booking_time ASC";
$assigned_result = $conn->query($assigned_sql);

// Get completed and cancelled transportation assignments
$history_sql = "SELECT ta.*, tb.vehicle_type, tb.vehicle_name, tb.route_name, 
                tb.booking_date, tb.booking_time, tb.pickup_location, tb.dropoff_location, 
                u.full_name, u.email, u.phone_number
                FROM transportation_assign ta
                JOIN transportation_bookings tb ON ta.booking_id = tb.id
                JOIN users u ON ta.user_id = u.id
                WHERE ta.status IN ('completed', 'cancelled')
                ORDER BY ta.updated_at DESC
                LIMIT 20";
$history_result = $conn->query($history_sql);

// Get package bookings that need transportation, excluding those that already have transportation assigned
$package_bookings_sql = "SELECT pb.id as booking_id, pb.user_id, pb.package_id, pb.booking_date, 
                         pb.status, pb.payment_status, pb.total_price,
                         p.title as package_title, p.package_type, p.inclusions,
                         u.full_name, u.email, u.phone_number
                         FROM package_booking pb
                         JOIN packages p ON pb.package_id = p.id
                         JOIN users u ON pb.user_id = u.id
                         LEFT JOIN (
                             SELECT package_booking_id 
                             FROM transportation_assign 
                             WHERE package_booking_id IS NOT NULL
                             AND status != 'cancelled'
                         ) ta ON pb.id = ta.package_booking_id
                         WHERE (pb.status = 'pending' OR pb.status = 'confirmed')
                         AND ta.package_booking_id IS NULL
                         ORDER BY pb.booking_date DESC";
$package_bookings_result = $conn->query($package_bookings_sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <!-- Include SweetAlert -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Include Flatpickr for better date/time pickers -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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
          <i class="fas fa-car-side text-teal-600 mx-2"></i> Transportation Assignment
        </h1>
      </div>

      <!-- Content Container -->
      <div class="overflow-auto flex-1">
        <div class="container mx-auto px-4 py-8">
          
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
          
          <!-- Tabs -->
          <div class="mb-6">
            <ul class="flex border-b">
              <li class="mr-1">
                <a href="#" class="tab-link bg-white inline-block py-2 px-4 text-teal-600 font-semibold border-l border-t border-r rounded-t active" 
                  data-tab="pending-tab">
                  <i class="fas fa-clock text-yellow-500 mr-2"></i> Pending Bookings
                </a>
              </li>
              <li class="mr-1">
                <a href="#" class="tab-link inline-block py-2 px-4 text-gray-500 hover:text-teal-600 font-semibold" 
                  data-tab="packages-tab">
                  <i class="fas fa-box text-blue-500 mr-2"></i> Package Bookings
                </a>
              </li>
              <li class="mr-1">
                <a href="#" class="tab-link inline-block py-2 px-4 text-gray-500 hover:text-teal-600 font-semibold" 
                  data-tab="assigned-tab">
                  <i class="fas fa-check-circle text-green-500 mr-2"></i> Assigned Transportation
                </a>
              </li>
              <li class="mr-1">
                <a href="#" class="tab-link inline-block py-2 px-4 text-gray-500 hover:text-teal-600 font-semibold" 
                  data-tab="history-tab">
                  <i class="fas fa-history text-purple-500 mr-2"></i> History
                </a>
              </li>
            </ul>
          </div>
          
          <!-- Pending Bookings Tab -->
          <div id="pending-tab" class="tab-content block">
            <div class="bg-white p-6 rounded-lg shadow-lg">
              <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-clock text-yellow-500 mr-2"></i> Pending Transportation Bookings
              </h2>
              
              <?php if ($pending_bookings_result->num_rows > 0): ?>
              <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                  <thead class="bg-gray-100">
                    <tr>
                      <th class="py-3 px-4 text-left">Reference</th>
                      <th class="py-3 px-4 text-left">Customer</th>
                      <th class="py-3 px-4 text-left">Service</th>
                      <th class="py-3 px-4 text-left">Route</th>
                      <th class="py-3 px-4 text-left">Vehicle</th>
                      <th class="py-3 px-4 text-left">Date & Time</th>
                      <th class="py-3 px-4 text-left">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while ($booking = $pending_bookings_result->fetch_assoc()): ?>
                    <tr class="border-b hover:bg-gray-50">
                      <td class="py-3 px-4"><?php echo $booking['booking_reference']; ?></td>
                      <td class="py-3 px-4">
                        <?php echo $booking['full_name']; ?><br>
                        <span class="text-sm text-gray-600"><?php echo $booking['phone_number']; ?></span>
                      </td>
                      <td class="py-3 px-4">
                        <?php echo ucfirst($booking['service_type']); ?>
                        <span class="text-sm text-gray-600 block">
                          <?php echo ucfirst($booking['duration']); ?>
                        </span>
                      </td>
                      <td class="py-3 px-4">
                        <?php echo $booking['route_name']; ?>
                      </td>
                      <td class="py-3 px-4">
                        <?php echo $booking['vehicle_name']; ?>
                      </td>
                      <td class="py-3 px-4">
                        <?php echo date('d M Y', strtotime($booking['booking_date'])); ?><br>
                        <span class="text-sm text-gray-600"><?php echo date('h:i A', strtotime($booking['booking_time'])); ?></span>
                      </td>
                      <td class="py-3 px-4">
                        <button type="button" 
                                class="bg-teal-500 text-white px-3 py-1 rounded-md hover:bg-teal-600 assign-btn"
                                data-booking='<?php echo json_encode($booking); ?>'
                                data-type="transportation">
                          <i class="fas fa-user-check mr-1"></i> Assign
                        </button>
                      </td>
                    </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
              <?php else: ?>
              <p class="text-gray-500 italic">No pending transportation bookings found.</p>
              <?php endif; ?>
            </div>
          </div>
          
          <!-- Package Bookings Tab -->
          <div id="packages-tab" class="tab-content hidden">
            <div class="bg-white p-6 rounded-lg shadow-lg">
              <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-box text-blue-500 mr-2"></i> Package Bookings
              </h2>
              
              <?php if ($package_bookings_result->num_rows > 0): ?>
              <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                  <thead class="bg-gray-100">
                    <tr>
                      <th class="py-3 px-4 text-left">Booking ID</th>
                      <th class="py-3 px-4 text-left">Customer</th>
                      <th class="py-3 px-4 text-left">Package</th>
                      <th class="py-3 px-4 text-left">Type</th>
                      <th class="py-3 px-4 text-left">Booking Date</th>
                      <th class="py-3 px-4 text-left">Status</th>
                      <th class="py-3 px-4 text-left">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while ($package_booking = $package_bookings_result->fetch_assoc()): ?>
                    <tr class="border-b hover:bg-gray-50">
                      <td class="py-3 px-4"><?php echo $package_booking['booking_id']; ?></td>
                      <td class="py-3 px-4">
                        <?php echo $package_booking['full_name']; ?><br>
                        <span class="text-sm text-gray-600"><?php echo $package_booking['phone_number']; ?></span>
                      </td>
                      <td class="py-3 px-4">
                        <?php echo $package_booking['package_title']; ?>
                      </td>
                      <td class="py-3 px-4">
                        <span class="px-2 py-1 text-xs rounded-full <?php echo ($package_booking['package_type'] == 'vip') ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                          <?php echo strtoupper($package_booking['package_type']); ?>
                        </span>
                      </td>
                      <td class="py-3 px-4">
                        <?php echo date('d M Y', strtotime($package_booking['booking_date'])); ?>
                      </td>
                      <td class="py-3 px-4">
                        <span class="px-2 py-1 text-xs rounded-full 
                          <?php 
                            if($package_booking['status'] == 'pending') echo 'bg-yellow-100 text-yellow-800';
                            else if($package_booking['status'] == 'confirmed') echo 'bg-green-100 text-green-800';
                            else if($package_booking['status'] == 'canceled') echo 'bg-red-100 text-red-800';
                          ?>">
                          <?php echo ucfirst($package_booking['status']); ?>
                        </span>
                      </td>
                      <td class="py-3 px-4">
                        <button type="button" 
                                class="bg-blue-500 text-white px-3 py-1 rounded-md hover:bg-blue-600 assign-package-btn"
                                data-booking='<?php echo json_encode($package_booking); ?>'
                                data-type="package">
                          <i class="fas fa-car mr-1"></i> Assign Transport
                        </button>
                      </td>
                    </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
              <?php else: ?>
              <p class="text-gray-500 italic">No package bookings found.</p>
              <?php endif; ?>
            </div>
          </div>
          
          <!-- Assigned Transportation Tab -->
          <div id="assigned-tab" class="tab-content hidden">
            <div class="bg-white p-6 rounded-lg shadow-lg">
              <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-2"></i> Assigned Transportation
              </h2>
              
              <?php if ($assigned_result->num_rows > 0): ?>
              <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                  <thead class="bg-gray-100">
                    <tr>
                      <th class="py-3 px-4 text-left">Reference</th>
                      <th class="py-3 px-4 text-left">Customer</th>
                      <th class="py-3 px-4 text-left">Service</th>
                      <th class="py-3 px-4 text-left">Vehicle</th>
                      <th class="py-3 px-4 text-left">Driver</th>
                      <th class="py-3 px-4 text-left">Pickup Time</th>
                      <th class="py-3 px-4 text-left">Type</th>
                      <th class="py-3 px-4 text-left">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while ($assigned = $assigned_result->fetch_assoc()): ?>
                    <tr class="border-b hover:bg-gray-50">
                      <td class="py-3 px-4"><?php echo $assigned['booking_reference']; ?></td>
                      <td class="py-3 px-4">
                        <?php echo $assigned['full_name']; ?><br>
                        <span class="text-sm text-gray-600"><?php echo $assigned['phone_number']; ?></span>
                      </td>
                      <td class="py-3 px-4">
                        <?php echo ucfirst($assigned['service_type']); ?>
                        <span class="text-sm text-gray-600 block">
                          <?php echo $assigned['route_name']; ?>
                        </span>
                      </td>
                      <td class="py-3 px-4">
                        <?php echo $assigned['vehicle_name']; ?>
                        <span class="text-sm text-gray-600 block">
                          ID: <?php echo $assigned['vehicle_id']; ?>
                        </span>
                      </td>
                      <td class="py-3 px-4">
                        <?php echo $assigned['driver_name']; ?><br>
                        <span class="text-sm text-gray-600"><?php echo $assigned['driver_contact']; ?></span>
                      </td>
                      <td class="py-3 px-4">
                        <?php 
                          $pickup_datetime = new DateTime($assigned['pickup_time']);
                          echo $pickup_datetime->format('d M Y'); ?><br>
                        <span class="text-sm text-gray-600">
                          <?php echo $pickup_datetime->format('h:i A'); ?>
                        </span>
                      </td>
                      <td class="py-3 px-4">
                        <span class="px-2 py-1 text-xs rounded-full 
                          <?php echo ($assigned['booking_type'] == 'package') ? 'bg-blue-100 text-blue-800' : 'bg-teal-100 text-teal-800'; ?>">
                          <?php echo ucfirst($assigned['booking_type']); ?>
                        </span>
                      </td>
                      <td class="py-3 px-4">
                        <div class="flex space-x-2">
                          <button type="button" 
                                  class="bg-green-500 text-white px-2 py-1 rounded-md hover:bg-green-600 complete-btn"
                                  data-id="<?php echo $assigned['id']; ?>">
                            <i class="fas fa-check"></i>
                          </button>
                          <button type="button" 
                                  class="bg-red-500 text-white px-2 py-1 rounded-md hover:bg-red-600 cancel-btn"
                                  data-id="<?php echo $assigned['id']; ?>">
                            <i class="fas fa-times"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
              <?php else: ?>
              <p class="text-gray-500 italic">No assigned transportation found.</p>
              <?php endif; ?>
            </div>
          </div>
          
          <!-- History Tab -->
          <div id="history-tab" class="tab-content hidden">
            <div class="bg-white p-6 rounded-lg shadow-lg">
              <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-history text-purple-500 mr-2"></i> Transportation History
              </h2>
              
              <?php if ($history_result && $history_result->num_rows > 0): ?>
              <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                  <thead class="bg-gray-100">
                    <tr>
                      <th class="py-3 px-4 text-left">Reference</th>
                      <th class="py-3 px-4 text-left">Customer</th>
                      <th class="py-3 px-4 text-left">Service</th>
                      <th class="py-3 px-4 text-left">Driver</th>
                      <th class="py-3 px-4 text-left">Pickup Time</th>
                      <th class="py-3 px-4 text-left">Status</th>
                      <th class="py-3 px-4 text-left">Type</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while ($history = $history_result->fetch_assoc()): ?>
                    <tr class="border-b hover:bg-gray-50">
                      <td class="py-3 px-4"><?php echo $history['booking_reference']; ?></td>
                      <td class="py-3 px-4">
                        <?php echo $history['full_name']; ?><br>
                        <span class="text-sm text-gray-600"><?php echo $history['phone_number']; ?></span>
                      </td>
                      <td class="py-3 px-4">
                        <?php echo ucfirst($history['service_type']); ?>
                        <span class="text-sm text-gray-600 block">
                          <?php echo $history['route_name']; ?>
                        </span>
                      </td>
                      <td class="py-3 px-4">
                        <?php echo $history['driver_name']; ?><br>
                        <span class="text-sm text-gray-600"><?php echo $history['driver_contact']; ?></span>
                      </td>
                      <td class="py-3 px-4">
                        <?php 
                          $pickup_datetime = new DateTime($history['pickup_time']);
                          echo $pickup_datetime->format('d M Y'); ?><br>
                        <span class="text-sm text-gray-600">
                          <?php echo $pickup_datetime->format('h:i A'); ?>
                        </span>
                      </td>
                      <td class="py-3 px-4">
                        <span class="px-2 py-1 text-xs rounded-full 
                          <?php 
                            if($history['status'] == 'completed') echo 'bg-green-100 text-green-800';
                            else if($history['status'] == 'cancelled') echo 'bg-red-100 text-red-800';
                            else echo 'bg-gray-100 text-gray-800';
                          ?>">
                          <?php echo ucfirst($history['status']); ?>
                        </span>
                      </td>
                      <td class="py-3 px-4">
                        <span class="px-2 py-1 text-xs rounded-full 
                          <?php echo ($history['booking_type'] == 'package') ? 'bg-blue-100 text-blue-800' : 'bg-teal-100 text-teal-800'; ?>">
                          <?php echo ucfirst($history['booking_type'] ?? 'transportation'); ?>
                        </span>
                      </td>
                    </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
              <?php else: ?>
              <p class="text-gray-500 italic">No transportation history found.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Assignment Modal for Direct Transportation -->
  <div id="assignModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full max-h-screen overflow-y-auto">
      <div class="p-6">
        <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-bold text-gray-800">Assign Transportation</h3>
          <button type="button" class="text-gray-500 hover:text-gray-700" id="closeModal">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <form id="assignForm" method="POST" action="">
          <input type="hidden" name="assign_transport" value="1">
          <input type="hidden" name="booking_type" value="transportation">
          <input type="hidden" name="booking_id" id="booking_id">
          <input type="hidden" name="booking_reference" id="booking_reference">
          <input type="hidden" name="user_id" id="user_id">
          <input type="hidden" name="service_type" id="service_type">
          <input type="hidden" name="route_id" id="route_id">
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
              <h4 class="font-semibold mb-2">Booking Details</h4>
              <div class="bg-gray-50 p-3 rounded-md">
                <p><span class="font-medium">Reference:</span> <span id="modal_reference"></span></p>
                <p><span class="font-medium">Customer:</span> <span id="modal_customer"></span></p>
                <p><span class="font-medium">Service:</span> <span id="modal_service"></span></p>
                <p><span class="font-medium">Route:</span> <span id="modal_route"></span></p>
                <p><span class="font-medium">Vehicle:</span> <span id="modal_vehicle"></span></p>
                <p><span class="font-medium">Date:</span> <span id="modal_date"></span></p>
                <p><span class="font-medium">Pickup:</span> <span id="modal_pickup"></span></p>
                <p><span class="font-medium">Dropoff:</span> <span id="modal_dropoff"></span></p>
              </div>
            </div>
            
            <div>
              <h4 class="font-semibold mb-2">Assignment Details</h4>
              
              <div class="mb-3">
                <label class="block text-gray-700 text-sm font-bold mb-1" for="vehicle_id">
                  Vehicle ID/Number
                </label>
                <input type="text" id="vehicle_id" name="vehicle_id"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500" 
                  required>
              </div>
              
              <div class="mb-3">
                <label class="block text-gray-700 text-sm font-bold mb-1" for="driver_name">
                  Driver Name
                </label>
                <input type="text" id="driver_name" name="driver_name"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500"
                  required>
              </div>
              
              <div class="mb-3">
                <label class="block text-gray-700 text-sm font-bold mb-1" for="driver_contact">
                  Driver Contact
                </label>
                <input type="text" id="driver_contact" name="driver_contact"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500"
                  required>
              </div>
              
              <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                  <label class="block text-gray-700 text-sm font-bold mb-1" for="pickup_date">
                    Pickup Date
                  </label>
                  <input type="date" id="pickup_date" name="pickup_date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500"
                    required>
                </div>
                <div>
                  <label class="block text-gray-700 text-sm font-bold mb-1" for="pickup_time">
                    Pickup Time
                  </label>
                  <input type="time" id="pickup_time" name="pickup_time"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500"
                    required>
                </div>
              </div>
              
              <div class="mb-3">
                <label class="block text-gray-700 text-sm font-bold mb-1" for="admin_notes">
                  Admin Notes
                </label>
                <textarea id="admin_notes" name="admin_notes" rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
              </div>
            </div>
          </div>
          
          <div class="flex justify-end space-x-3">
            <button type="button" id="cancelAssign" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
              Cancel
            </button>
            <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded-md hover:bg-teal-700">
              <i class="fas fa-save mr-2"></i>Assign Transportation
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <!-- Assignment Modal for Package Bookings -->
  <div id="packageModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full max-h-screen overflow-y-auto">
      <div class="p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-bold text-gray-800">Assign Transportation for Package</h3>
          <button type="button" class="text-gray-500 hover:text-gray-700" id="closePackageModal">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <form id="packageAssignForm" method="POST" action="">
          <input type="hidden" name="assign_transport" value="1">
          <input type="hidden" name="booking_type" value="package">
          <input type="hidden" name="user_id" id="package_user_id">
          <input type="hidden" name="package_booking_id" id="package_booking_id">
          <input type="hidden" name="package_id" id="package_id">
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
              <h4 class="font-semibold mb-2">Package Booking Details</h4>
              <div class="bg-gray-50 p-3 rounded-md">
                <p><span class="font-medium">Booking ID:</span> <span id="package_booking_id_display"></span></p>
                <p><span class="font-medium">Customer:</span> <span id="package_customer"></span></p>
                <p><span class="font-medium">Package:</span> <span id="package_title"></span></p>
                <p><span class="font-medium">Type:</span> <span id="package_type"></span></p>
                <p><span class="font-medium">Booking Date:</span> <span id="package_date"></span></p>
                <p><span class="font-medium">Status:</span> <span id="package_status"></span></p>
              </div>
            </div>
            
            <div>
              <h4 class="font-semibold mb-2">Transportation Details</h4>
              
              <div class="mb-3">
                <label class="block text-gray-700 text-sm font-bold mb-1" for="route_name">
                  Route/Service
                </label>
                <input type="text" id="route_name" name="route_name"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                  required placeholder="e.g., Makkah to Madinah">
              </div>
              
              <div class="mb-3">
                <label class="block text-gray-700 text-sm font-bold mb-1" for="vehicle_type">
                  Vehicle Type
                </label>
                <select id="vehicle_type" name="vehicle_type"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required>
                  <option value="">Select Vehicle Type</option>
                  <option value="camry">Camry / Sonata</option>
                  <option value="starex">Starex / Staria</option>
                  <option value="hiace">Hiace</option>
                  <option value="gmc_16_19">GMC 16-19</option>
                  <option value="gmc_22_23">GMC 22-23</option>
                  <option value="coaster">Coaster</option>
                </select>
              </div>
              
              <div class="mb-3">
                <label class="block text-gray-700 text-sm font-bold mb-1" for="vehicle_id">
                  Vehicle ID/Number
                </label>
                <input type="text" id="package_vehicle_id" name="vehicle_id"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                  required>
              </div>
              
              <div class="mb-3">
                <label class="block text-gray-700 text-sm font-bold mb-1" for="pickup_location">
                  Pickup Location
                </label>
                <input type="text" id="pickup_location" name="pickup_location"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required placeholder="e.g., Hotel Name">
              </div>
              
              <div class="mb-3">
                <label class="block text-gray-700 text-sm font-bold mb-1" for="dropoff_location">
                  Dropoff Location
                </label>
                <input type="text" id="dropoff_location" name="dropoff_location"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required placeholder="e.g., Airport">
              </div>
              
              <div class="mb-3">
                <label class="block text-gray-700 text-sm font-bold mb-1" for="driver_name">
                  Driver Name
                </label>
                <input type="text" id="package_driver_name" name="driver_name"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required>
              </div>
              
              <div class="mb-3">
                <label class="block text-gray-700 text-sm font-bold mb-1" for="driver_contact">
                  Driver Contact
                </label>
                <input type="text" id="package_driver_contact" name="driver_contact"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required>
              </div>
              
              <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                  <label class="block text-gray-700 text-sm font-bold mb-1" for="package_pickup_date">
                    Pickup Date
                  </label>
                  <input type="date" id="package_pickup_date" name="pickup_date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required>
                </div>
                <div>
                  <label class="block text-gray-700 text-sm font-bold mb-1" for="package_pickup_time">
                    Pickup Time
                  </label>
                  <input type="time" id="package_pickup_time" name="pickup_time"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required>
                </div>
              </div>
              
              <div class="mb-3">
                <label class="block text-gray-700 text-sm font-bold mb-1" for="package_admin_notes">
                  Admin Notes
                </label>
                <textarea id="package_admin_notes" name="admin_notes" rows="2"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
              </div>
            </div>
          </div>
          
          <div class="flex justify-end space-x-3">
            <button type="button" id="cancelPackageAssign" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
              Cancel
            </button>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
              <i class="fas fa-save mr-2"></i>Assign Transportation
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php include 'includes/js-links.php'; ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Tab functionality
      const tabLinks = document.querySelectorAll('.tab-link');
      const tabContents = document.querySelectorAll('.tab-content');
      
      tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
          e.preventDefault();
          
          // Remove active class from all tabs
          tabLinks.forEach(tab => {
            tab.classList.remove('active', 'bg-white', 'border-l', 'border-t', 'border-r', 'rounded-t', 'text-teal-600');
            tab.classList.add('text-gray-500');
          });
          
          // Add active class to current tab
          this.classList.add('active', 'bg-white', 'border-l', 'border-t', 'border-r', 'rounded-t', 'text-teal-600');
          this.classList.remove('text-gray-500');
          
          // Hide all tab contents
          tabContents.forEach(content => {
            content.classList.add('hidden');
            content.classList.remove('block');
          });
          
          // Show current tab content
          const tabId = this.getAttribute('data-tab');
          document.getElementById(tabId).classList.remove('hidden');
          document.getElementById(tabId).classList.add('block');
        });
      });
      
      // Direct Transportation Assignment Modal
      const assignModal = document.getElementById('assignModal');
      const assignBtns = document.querySelectorAll('.assign-btn');
      const closeModal = document.getElementById('closeModal');
      const cancelAssign = document.getElementById('cancelAssign');
      
      // Package Transportation Assignment Modal
      const packageModal = document.getElementById('packageModal');
      const assignPackageBtns = document.querySelectorAll('.assign-package-btn');
      const closePackageModal = document.getElementById('closePackageModal');
      const cancelPackageAssign = document.getElementById('cancelPackageAssign');
      
      // Set current date as default for date pickers
      const today = new Date().toISOString().split('T')[0];
      document.getElementById('pickup_date').value = today;
      document.getElementById('package_pickup_date').value = today;
      
      // Function to open the direct transportation modal with booking details
      function openAssignModal(bookingData) {
        // Set hidden form values
        document.getElementById('booking_id').value = bookingData.id;
        document.getElementById('booking_reference').value = bookingData.booking_reference;
        document.getElementById('user_id').value = bookingData.user_id;
        document.getElementById('service_type').value = bookingData.service_type;
        document.getElementById('route_id').value = bookingData.route_id;
        
        // Set booking details in the modal
        document.getElementById('modal_reference').textContent = bookingData.booking_reference;
        document.getElementById('modal_customer').textContent = bookingData.full_name;
        document.getElementById('modal_service').textContent = bookingData.service_type.charAt(0).toUpperCase() + 
                                                               bookingData.service_type.slice(1);
        document.getElementById('modal_route').textContent = bookingData.route_name;
        document.getElementById('modal_vehicle').textContent = bookingData.vehicle_name;
        document.getElementById('modal_date').textContent = formatDate(bookingData.booking_date) + ' at ' + 
                                                            formatTime(bookingData.booking_time);
        document.getElementById('modal_pickup').textContent = bookingData.pickup_location;
        document.getElementById('modal_dropoff').textContent = bookingData.dropoff_location;
        
        // Set default pickup date/time to the booking date/time
        document.getElementById('pickup_date').value = bookingData.booking_date;
        document.getElementById('pickup_time').value = bookingData.booking_time;
        
        // Show the modal
        assignModal.classList.remove('hidden');
      }
      
      // Function to open the package assignment modal with booking details
      function openPackageModal(packageData) {
        // Set hidden form values
        document.getElementById('package_booking_id').value = packageData.booking_id;
        document.getElementById('package_user_id').value = packageData.user_id;
        document.getElementById('package_id').value = packageData.package_id;
        
        // Set package details in the modal
        document.getElementById('package_booking_id_display').textContent = packageData.booking_id;
        document.getElementById('package_customer').textContent = packageData.full_name;
        document.getElementById('package_title').textContent = packageData.package_title;
        document.getElementById('package_type').textContent = packageData.package_type.toUpperCase();
        document.getElementById('package_date').textContent = formatDate(packageData.booking_date);
        document.getElementById('package_status').textContent = packageData.status.charAt(0).toUpperCase() + 
                                                                packageData.status.slice(1);
        
        // Get current date and set as default
        const currentDate = new Date();
        document.getElementById('package_pickup_date').value = today;
        
        // Set default pickup time (current time + 1 hour)
        const hour = currentDate.getHours() + 1;
        const minute = currentDate.getMinutes();
        document.getElementById('package_pickup_time').value = 
            `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
        
        // Show the modal
        packageModal.classList.remove('hidden');
      }
      
      // Format date for display (e.g., Mar 10, 2025)
      function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
      }
      
      // Format time for display (e.g., 2:30 PM)
      function formatTime(timeStr) {
        const [hours, minutes] = timeStr.split(':');
        let hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        hour = hour % 12;
        hour = hour ? hour : 12; // Convert 0 to 12
        return `${hour}:${minutes} ${ampm}`;
      }
      
      // Event listeners for opening the direct transportation modal
      assignBtns.forEach(btn => {
        btn.addEventListener('click', function() {
          const bookingData = JSON.parse(this.getAttribute('data-booking'));
          openAssignModal(bookingData);
        });
      });
      
      // Event listeners for opening the package assignment modal
      assignPackageBtns.forEach(btn => {
        btn.addEventListener('click', function() {
          const packageData = JSON.parse(this.getAttribute('data-booking'));
          openPackageModal(packageData);
        });
      });
      
      // Event listeners for closing the direct transportation modal
      closeModal.addEventListener('click', function() {
        assignModal.classList.add('hidden');
      });
      
      cancelAssign.addEventListener('click', function() {
        assignModal.classList.add('hidden');
      });
      
      // Event listeners for closing the package assignment modal
      closePackageModal.addEventListener('click', function() {
        packageModal.classList.add('hidden');
      });
      
      cancelPackageAssign.addEventListener('click', function() {
        packageModal.classList.add('hidden');
      });
      
      // Function to send AJAX request for status updates
      function updateTransportationStatus(assignId, action) {
        // Create form data
        const formData = new FormData();
        formData.append('assign_id', assignId);
        formData.append('action', action);
        
        // Send AJAX request
        fetch('transport-assign-action.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            Swal.fire({
              title: 'Success!',
              text: data.message,
              icon: 'success',
              timer: 2000,
              showConfirmButton: false
            }).then(() => {
              location.reload();
            });
          } else {
            Swal.fire({
              title: 'Error!',
              text: data.message || 'Something went wrong',
              icon: 'error',
              confirmButtonText: 'OK'
            });
          }
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire({
            title: 'Error!',
            text: 'An unexpected error occurred',
            icon: 'error',
            confirmButtonText: 'OK'
          });
        });
      }
      
      // Complete button functionality
      const completeBtns = document.querySelectorAll('.complete-btn');
      completeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
          const assignId = this.getAttribute('data-id');
          Swal.fire({
            title: 'Mark as Completed?',
            text: "This will mark the transportation service as completed",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Yes, mark as completed'
          }).then((result) => {
            if (result.isConfirmed) {
              updateTransportationStatus(assignId, 'complete');
            }
          });
        });
      });
      
      // Cancel button functionality
      const cancelBtns = document.querySelectorAll('.cancel-btn');
      cancelBtns.forEach(btn => {
        btn.addEventListener('click', function() {
          const assignId = this.getAttribute('data-id');
          Swal.fire({
            title: 'Cancel Assignment?',
            text: "This will cancel the transportation assignment",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Yes, cancel it'
          }).then((result) => {
            if (result.isConfirmed) {
              updateTransportationStatus(assignId, 'cancel');
            }
          });
        });
      });
    });
  </script>
</body>
</html>