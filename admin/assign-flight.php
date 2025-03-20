<?php
session_start();
include 'connection/connection.php'; // Include database connection

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
  header("Location: login.php");
  exit();
}

// Function to check already assigned seats
function getAssignedSeats($conn, $flight_id, $seat_type, $current_seat = '', $edit_mode = false)
{
  $sql = "SELECT seat_number FROM flight_assign WHERE flight_id = ? AND seat_type = ? AND status != 'cancelled'
          UNION 
          SELECT seat_number FROM package_assign WHERE flight_id = ? AND seat_type = ?";

  $stmt = $conn->prepare($sql);
  $stmt->bind_param("isis", $flight_id, $seat_type, $flight_id, $seat_type);
  $stmt->execute();
  $result = $stmt->get_result();

  $assigned_seats = [];
  while ($row = $result->fetch_assoc()) {
    // If in edit mode, don't include the current seat as "assigned"
    if (!($edit_mode && $row['seat_number'] === $current_seat)) {
      $assigned_seats[] = $row['seat_number'];
    }
  }

  return $assigned_seats;
}

// Handle form submission for assigning flights
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_flight'])) {
  $user_id = $_POST['user_id'];
  $package_booking_id = $_POST['package_booking_id'];
  $flight_id = $_POST['flight_id'];
  $seat_type = $_POST['seat_type'];
  $seat_number = $_POST['seat_number'];
  $admin_notes = $_POST['admin_notes'] ?? '';

  // Check if the seat is already assigned to someone else
  $assigned_seats = getAssignedSeats($conn, $flight_id, $seat_type);

  $is_edit_mode = false;
  $current_seat = '';

  // Check if this is an edit operation
  $check_sql = "SELECT id, seat_number FROM flight_assign WHERE booking_id = ? AND user_id = ?";
  $check_stmt = $conn->prepare($check_sql);
  $check_stmt->bind_param("ii", $package_booking_id, $user_id);
  $check_stmt->execute();
  $check_result = $check_stmt->get_result();

  if ($check_result->num_rows > 0) {
    $assign_row = $check_result->fetch_assoc();
    $is_edit_mode = true;
    $current_seat = $assign_row['seat_number'];
  }

  // Make sure the selected seat is not already assigned
  if (in_array($seat_number, $assigned_seats) && (!$is_edit_mode || $seat_number !== $current_seat)) {
    $error_message = "The selected seat is already assigned to another user. Please select a different seat.";
  } else {
    // Proceed with assignment
    if ($is_edit_mode) {
      // Update existing assignment
      $update_sql = "UPDATE flight_assign SET 
                    flight_id = ?, seat_type = ?, seat_number = ?, admin_notes = ?, updated_at = NOW() 
                    WHERE id = ?";
      $update_stmt = $conn->prepare($update_sql);
      $update_stmt->bind_param("isssi", $flight_id, $seat_type, $seat_number, $admin_notes, $assign_row['id']);

      if ($update_stmt->execute()) {
        $success_message = "Flight assignment updated successfully!";
      } else {
        $error_message = "Error updating flight assignment: " . $update_stmt->error;
      }
    } else {
      // Insert new assignment
      $insert_sql = "INSERT INTO flight_assign 
                    (booking_id, user_id, flight_id, seat_type, seat_number, admin_notes) 
                    VALUES (?, ?, ?, ?, ?, ?)";
      $insert_stmt = $conn->prepare($insert_sql);
      $insert_stmt->bind_param("iiisss", $package_booking_id, $user_id, $flight_id, $seat_type, $seat_number, $admin_notes);

      if ($insert_stmt->execute()) {
        $success_message = "Flight successfully assigned to package booking!";
      } else {
        $error_message = "Error creating flight assignment: " . $insert_stmt->error;
      }
    }

    // Also update the package_assign table if needed
    $check_package_assign_sql = "SELECT id FROM package_assign WHERE booking_id = ? AND user_id = ?";
    $check_package_assign_stmt = $conn->prepare($check_package_assign_sql);
    $check_package_assign_stmt->bind_param("ii", $package_booking_id, $user_id);
    $check_package_assign_stmt->execute();
    $check_package_assign_result = $check_package_assign_stmt->get_result();

    if ($check_package_assign_result->num_rows > 0) {
      // Update existing assignment
      $assign_row = $check_package_assign_result->fetch_assoc();
      $update_assign_sql = "UPDATE package_assign SET flight_id = ?, seat_type = ?, seat_number = ? WHERE id = ?";
      $update_assign_stmt = $conn->prepare($update_assign_sql);
      $update_assign_stmt->bind_param("issi", $flight_id, $seat_type, $seat_number, $assign_row['id']);
      $update_assign_stmt->execute();
    } else {
      // Insert new assignment in package_assign
      $insert_assign_sql = "INSERT INTO package_assign (booking_id, user_id, flight_id, seat_type, seat_number) 
                         VALUES (?, ?, ?, ?, ?)";
      $insert_assign_stmt = $conn->prepare($insert_assign_sql);
      $insert_assign_stmt->bind_param("iiiss", $package_booking_id, $user_id, $flight_id, $seat_type, $seat_number);
      $insert_assign_stmt->execute();
    }
  }
}

// Handle status update for flight assignments
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
  $assignment_id = $_POST['assignment_id'];
  $new_status = $_POST['new_status'];

  $update_status_sql = "UPDATE flight_assign SET status = ? WHERE id = ?";
  $update_status_stmt = $conn->prepare($update_status_sql);
  $update_status_stmt->bind_param("si", $new_status, $assignment_id);

  if ($update_status_stmt->execute()) {
    $success_message = "Assignment status updated successfully!";
  } else {
    $error_message = "Error updating status: " . $update_status_stmt->error;
  }
}

// Get all package bookings excluding those that already have flight assignments
$package_users_sql = "SELECT pb.id as booking_id, pb.user_id, pb.package_id, pb.booking_date, 
                     pb.status, pb.payment_status, pb.total_price,
                     p.title as package_title, p.package_type, p.airline, p.flight_class,
                     p.departure_city, p.departure_date, p.departure_time, p.arrival_city,
                     p.inclusions,
                     u.full_name, u.email, u.phone_number
                     FROM package_booking pb
                     JOIN packages p ON pb.package_id = p.id
                     JOIN users u ON pb.user_id = u.id
                     LEFT JOIN flight_assign fa ON pb.id = fa.booking_id AND pb.user_id = fa.user_id
                     WHERE (pb.status = 'pending' OR pb.status = 'confirmed')
                     AND fa.id IS NULL
                     ORDER BY pb.booking_date DESC";
$package_users_result = $conn->query($package_users_sql);

// Get all flight assignments
$assignments_sql = "SELECT fa.*, 
                   pb.booking_date as package_booking_date, pb.status as package_status,
                   p.title as package_title, p.package_type,
                   f.airline_name, f.flight_number, f.departure_city, f.arrival_city,
                   f.departure_date, f.departure_time,
                   u.full_name, u.email, u.phone_number
                   FROM flight_assign fa
                   JOIN package_booking pb ON fa.booking_id = pb.id
                   JOIN packages p ON pb.package_id = p.id
                   JOIN flights f ON fa.flight_id = f.id
                   JOIN users u ON fa.user_id = u.id
                   ORDER BY fa.created_at DESC";
$assignments_result = $conn->query($assignments_sql);

// Get all flights for selection
$flights_sql = "SELECT * FROM flights 
              WHERE departure_date >= CURDATE() 
              ORDER BY departure_date ASC, departure_time ASC";
$flights_result = $conn->query($flights_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <!-- Include SweetAlert -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
          <i class="fas fa-plane text-blue-600 mx-2"></i> Package Users - Flight Assignment
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
                <a href="#" class="tab-link bg-white inline-block py-2 px-4 text-blue-600 font-semibold border-l border-t border-r rounded-t active"
                  data-tab="package-users-tab">
                  <i class="fas fa-users text-blue-500 mr-2"></i> Package Users
                </a>
              </li>
              <li class="mr-1">
                <a href="#" class="tab-link inline-block py-2 px-4 text-gray-500 hover:text-blue-600 font-semibold"
                  data-tab="assigned-tab">
                  <i class="fas fa-check-circle text-green-500 mr-2"></i> Assigned Flights
                </a>
              </li>
            </ul>
          </div>

          <!-- Package Users Tab -->
          <div id="package-users-tab" class="tab-content block">
            <div class="bg-white p-6 rounded-lg shadow-lg">
              <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-users text-blue-500 mr-2"></i> Package Booking Users
              </h2>

              <?php if ($package_users_result->num_rows > 0): ?>
                <div class="overflow-x-auto">
                  <table class="min-w-full bg-white">
                    <thead class="bg-gray-100">
                      <tr>
                        <th class="py-3 px-4 text-left">Booking ID</th>
                        <th class="py-3 px-4 text-left">Customer</th>
                        <th class="py-3 px-4 text-left">Package</th>
                        <th class="py-3 px-4 text-left">Inclusions</th>
                        <th class="py-3 px-4 text-left">Booking Date</th>
                        <th class="py-3 px-4 text-left">Status</th>
                        <th class="py-3 px-4 text-left">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php while ($user = $package_users_result->fetch_assoc()): ?>
                        <tr class="border-b hover:bg-gray-50">
                          <td class="py-3 px-4"><?php echo $user['booking_id']; ?></td>
                          <td class="py-3 px-4">
                            <?php echo $user['full_name']; ?><br>
                            <span class="text-sm text-gray-600"><?php echo $user['phone_number']; ?></span>
                          </td>
                          <td class="py-3 px-4">
                            <?php echo $user['package_title']; ?>
                            <span class="px-2 py-1 text-xs rounded-full <?php echo ($user['package_type'] == 'vip') ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?> ml-1">
                              <?php echo strtoupper($user['package_type']); ?>
                            </span>
                          </td>
                          <td class="py-3 px-4">
                            <?php
                            $inclusions = explode(',', str_replace(array('[', ']', '"'), '', $user['inclusions']));
                            foreach ($inclusions as $inclusion) {
                              echo '<span class="inline-block px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800 mr-1 mb-1">' .
                                trim($inclusion) . '</span>';
                            }
                            ?>
                          </td>
                          <td class="py-3 px-4">
                            <?php echo date('d M Y', strtotime($user['booking_date'])); ?>
                          </td>
                          <td class="py-3 px-4">
                            <span class="px-2 py-1 text-xs rounded-full 
                              <?php
                              if ($user['status'] == 'pending') echo 'bg-yellow-100 text-yellow-800';
                              else if ($user['status'] == 'confirmed') echo 'bg-green-100 text-green-800';
                              else echo 'bg-red-100 text-red-800';
                              ?>">
                              <?php echo ucfirst($user['status']); ?>
                            </span>
                          </td>
                          <td class="py-3 px-4">
                            <button type="button"
                              class="bg-blue-500 text-white px-3 py-1 rounded-md hover:bg-blue-600 assign-flight-btn"
                              data-booking='<?php echo json_encode($user); ?>'>
                              <i class="fas fa-plane mr-1"></i> Assign Flight
                            </button>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <p class="text-gray-500 italic">No package users found without flight assignments.</p>
              <?php endif; ?>
            </div>
          </div>

          <!-- Assigned Flights Tab -->
          <div id="assigned-tab" class="tab-content hidden">
            <div class="bg-white p-6 rounded-lg shadow-lg">
              <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-2"></i> Flight Assignments
              </h2>

              <?php if ($assignments_result && $assignments_result->num_rows > 0): ?>
                <div class="overflow-x-auto">
                  <table class="min-w-full bg-white">
                    <thead class="bg-gray-100">
                      <tr>
                        <th class="py-3 px-4 text-left">Booking ID</th>
                        <th class="py-3 px-4 text-left">Customer</th>
                        <th class="py-3 px-4 text-left">Package</th>
                        <th class="py-3 px-4 text-left">Flight</th>
                        <th class="py-3 px-4 text-left">Route</th>
                        <th class="py-3 px-4 text-left">Departure</th>
                        <th class="py-3 px-4 text-left">Seat</th>
                        <th class="py-3 px-4 text-left">Status</th>
                        <th class="py-3 px-4 text-left">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php while ($assignment = $assignments_result->fetch_assoc()): ?>
                        <tr class="border-b hover:bg-gray-50">
                          <td class="py-3 px-4"><?php echo $assignment['booking_id']; ?></td>
                          <td class="py-3 px-4">
                            <?php echo $assignment['full_name']; ?><br>
                            <span class="text-sm text-gray-600"><?php echo $assignment['phone_number']; ?></span>
                          </td>
                          <td class="py-3 px-4">
                            <?php echo $assignment['package_title']; ?>
                            <span class="px-2 py-1 text-xs rounded-full <?php echo ($assignment['package_type'] == 'vip') ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?> ml-1">
                              <?php echo strtoupper($assignment['package_type']); ?>
                            </span>
                          </td>
                          <td class="py-3 px-4">
                            <?php echo $assignment['airline_name']; ?><br>
                            <span class="text-sm text-gray-600">Flight <?php echo $assignment['flight_number']; ?></span>
                          </td>
                          <td class="py-3 px-4">
                            <?php echo $assignment['departure_city']; ?> → <?php echo $assignment['arrival_city']; ?>
                          </td>
                          <td class="py-3 px-4">
                            <?php echo date('d M Y', strtotime($assignment['departure_date'])); ?><br>
                            <span class="text-sm text-gray-600"><?php echo date('h:i A', strtotime($assignment['departure_time'])); ?></span>
                          </td>
                          <td class="py-3 px-4">
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                              <?php echo ucfirst($assignment['seat_type']); ?> - <?php echo $assignment['seat_number']; ?>
                            </span>
                          </td>
                          <td class="py-3 px-4">
                            <span class="px-2 py-1 text-xs rounded-full 
                              <?php
                              if ($assignment['status'] == 'assigned') echo 'bg-blue-100 text-blue-800';
                              else if ($assignment['status'] == 'completed') echo 'bg-green-100 text-green-800';
                              else echo 'bg-red-100 text-red-800';
                              ?>">
                              <?php echo ucfirst($assignment['status']); ?>
                            </span>
                          </td>
                          <td class="py-3 px-4">
                            <div class="flex space-x-2">
                              <!-- Edit button -->
                              <button type="button"
                                class="bg-yellow-500 text-white px-2 py-1 rounded-md hover:bg-yellow-600 edit-flight-btn"
                                data-assignment='<?php echo json_encode($assignment); ?>'>
                                <i class="fas fa-edit"></i>
                              </button>

                              <!-- Mark as Complete button -->
                              <?php if ($assignment['status'] == 'assigned'): ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Mark this assignment as completed?');">
                                  <input type="hidden" name="update_status" value="1">
                                  <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                  <input type="hidden" name="new_status" value="completed">
                                  <button type="submit" class="bg-green-500 text-white px-2 py-1 rounded-md hover:bg-green-600">
                                    <i class="fas fa-check"></i>
                                  </button>
                                </form>
                              <?php endif; ?>

                              <!-- Cancel button -->
                              <?php if ($assignment['status'] == 'assigned'): ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to cancel this assignment?');">
                                  <input type="hidden" name="update_status" value="1">
                                  <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                  <input type="hidden" name="new_status" value="cancelled">
                                  <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded-md hover:bg-red-600">
                                    <i class="fas fa-ban"></i>
                                  </button>
                                </form>
                              <?php endif; ?>
                            </div>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <p class="text-gray-500 italic">No flight assignments found.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Flight Assignment Modal -->
  <div id="flightModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full max-h-screen overflow-y-auto">
      <div class="p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-bold text-gray-800">Assign Flight</h3>
          <button type="button" class="text-gray-500 hover:text-gray-700" id="closeModal">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <form id="flightForm" method="POST" action="">
          <input type="hidden" name="assign_flight" value="1">
          <input type="hidden" name="user_id" id="user_id">
          <input type="hidden" name="package_booking_id" id="package_booking_id">

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
              <h4 class="font-semibold mb-2">Package Booking Details</h4>
              <div class="bg-gray-50 p-3 rounded-md">
                <p><span class="font-medium">Booking ID:</span> <span id="modal_booking_id"></span></p>
                <p><span class="font-medium">Customer:</span> <span id="modal_customer"></span></p>
                <p><span class="font-medium">Package:</span> <span id="modal_package"></span></p>
                <p><span class="font-medium">Type:</span> <span id="modal_type"></span></p>
                <p><span class="font-medium">Booking Date:</span> <span id="modal_booking_date"></span></p>
                <p><span class="font-medium">Status:</span> <span id="modal_status"></span></p>
              </div>
            </div>

            <div>
              <h4 class="font-semibold mb-2">Flight Assignment</h4>

              <div class="mb-3">
                <label class="block text-gray-700 text-sm font-bold mb-1" for="flight_id">
                  Select Flight
                </label>
                <select id="flight_id" name="flight_id"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required>
                  <option value="">Select Flight</option>
                  <?php
                  if ($flights_result->num_rows > 0) {
                    $flights_result->data_seek(0); // Reset result pointer
                    while ($flight = $flights_result->fetch_assoc()) {
                      echo '<option value="' . $flight['id'] . '" data-flight=\'' . json_encode($flight) . '\'>' .
                        $flight['airline_name'] . ' ' . $flight['flight_number'] . ' - ' .
                        $flight['departure_city'] . ' to ' . $flight['arrival_city'] . ' - ' .
                        date('d M Y', strtotime($flight['departure_date'])) . ' ' .
                        date('h:i A', strtotime($flight['departure_time'])) .
                        '</option>';
                    }
                  }
                  ?>
                </select>
              </div>

              <div class="mb-3">
                <label class="block text-gray-700 text-sm font-bold mb-1" for="seat_type">
                  Cabin Class
                </label>
                <select id="seat_type" name="seat_type"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required disabled>
                  <option value="">Select Cabin Class</option>
                  <option value="economy">Economy</option>
                  <option value="business">Business</option>
                  <option value="first_class">First Class</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="block text-gray-700 text-sm font-bold mb-1" for="seat_number">
                  Seat Number
                </label>
                <select id="seat_number" name="seat_number"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required disabled>
                  <option value="">Select Seat Number</option>
                  <!-- Seat options will be populated via JavaScript -->
                </select>
              </div>

              <div class="mb-3">
                <label class="block text-gray-700 text-sm font-bold mb-1" for="admin_notes">
                  Admin Notes
                </label>
                <textarea id="admin_notes" name="admin_notes" rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
              </div>
            </div>
          </div>

          <div class="flex justify-end space-x-3">
            <button type="button" id="cancelAssign" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
              Cancel
            </button>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
              <i class="fas fa-save mr-2"></i>Assign Flight
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
            tab.classList.remove('active', 'bg-white', 'border-l', 'border-t', 'border-r', 'rounded-t', 'text-blue-600');
            tab.classList.add('text-gray-500');
          });

          // Add active class to current tab
          this.classList.add('active', 'bg-white', 'border-l', 'border-t', 'border-r', 'rounded-t', 'text-blue-600');
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

      // Flight Assignment Modal
      const flightModal = document.getElementById('flightModal');
      const assignFlightBtns = document.querySelectorAll('.assign-flight-btn');
      const editFlightBtns = document.querySelectorAll('.edit-flight-btn');
      const closeModal = document.getElementById('closeModal');
      const cancelAssign = document.getElementById('cancelAssign');

      // Function to open the flight assignment modal
      function openFlightModal(userData, isEdit = false, assignmentData = null) {
        // Set hidden form values
        document.getElementById('user_id').value = userData.user_id;
        document.getElementById('package_booking_id').value = userData.booking_id;

        // Set booking details in the modal
        document.getElementById('modal_booking_id').textContent = userData.booking_id;
        document.getElementById('modal_customer').textContent = userData.full_name;
        document.getElementById('modal_package').textContent = userData.package_title;
        document.getElementById('modal_type').textContent = userData.package_type.toUpperCase();
        document.getElementById('modal_booking_date').textContent = formatDate(userData.booking_date);
        document.getElementById('modal_status').textContent = userData.status.charAt(0).toUpperCase() + userData.status.slice(1);

        // Reset form before populating
        document.getElementById('flight_id').value = '';
        document.getElementById('seat_type').value = '';
        document.getElementById('seat_type').disabled = true;
        document.getElementById('seat_number').innerHTML = '<option value="">Select Seat Number</option>';
        document.getElementById('seat_number').disabled = true;
        document.getElementById('admin_notes').value = '';

        // If editing existing assignment, populate form with current values
        if (isEdit && assignmentData) {
          const flightSelect = document.getElementById('flight_id');
          const seatTypeSelect = document.getElementById('seat_type');

          // Set selected flight
          flightSelect.value = assignmentData.flight_id;

          // Enable seat type selection
          seatTypeSelect.disabled = false;

          // Set selected seat type
          seatTypeSelect.value = assignmentData.seat_type;

          // Populate seat numbers based on selected flight and seat type
          populateSeats(assignmentData.flight_id, assignmentData.seat_type, true, assignmentData.seat_number);

          // Set admin notes
          document.getElementById('admin_notes').value = assignmentData.admin_notes || '';
        }

        // Show the modal
        flightModal.classList.remove('hidden');
      }

      // Function to populate seat options based on flight and cabin class
      function populateSeats(flightId, seatType, isEdit = false, currentSeat = '') {
        const seatNumberSelect = document.getElementById('seat_number');
        seatNumberSelect.innerHTML = '<option value="">Loading seats...</option>';
        seatNumberSelect.disabled = true;

        if (!flightId || !seatType) return;

        // Get flight data
        const flightSelect = document.getElementById('flight_id');
        const selectedOption = flightSelect.options[flightSelect.selectedIndex];

        if (!selectedOption) return;

        const flightData = JSON.parse(selectedOption.getAttribute('data-flight'));
        let seats;

        try {
          seats = typeof flightData.seats === 'string' ? JSON.parse(flightData.seats) : flightData.seats;
        } catch (e) {
          console.error('Error parsing seats data:', e);
          seatNumberSelect.innerHTML = '<option value="">Error loading seats</option>';
          return;
        }

        if (!seats || !seats[seatType] || !seats[seatType].seat_ids) {
          seatNumberSelect.innerHTML = '<option value="">No seats available</option>';
          return;
        }

        // Get all seats for this cabin class
        const allSeats = seats[seatType].seat_ids;

        // Fetch assigned seats from server
        fetch(`check-assigned-seats.php?flight_id=${flightId}&seat_type=${seatType}&edit_mode=${isEdit}&current_seat=${currentSeat}`)
          .then(response => response.json())
          .then(data => {
            if (data.error) {
              console.error('Error:', data.error);
              seatNumberSelect.innerHTML = '<option value="">Error checking seats</option>';
              return;
            }

            const assignedSeats = data.assigned_seats || [];
            const availableSeats = allSeats.filter(seat => {
              // If editing, include current seat
              if (isEdit && seat === currentSeat) return true;
              // Otherwise, only include unassigned seats
              return !assignedSeats.includes(seat);
            });

            seatNumberSelect.innerHTML = '<option value="">Select Seat Number</option>';

            if (availableSeats.length === 0) {
              seatNumberSelect.innerHTML += '<option value="" disabled>No available seats</option>';
              seatNumberSelect.disabled = true;
              return;
            }

            seatNumberSelect.disabled = false;

            // Add each available seat as an option
            availableSeats.forEach(seatId => {
              const option = document.createElement('option');
              option.value = seatId;
              option.textContent = seatId;
              seatNumberSelect.appendChild(option);
            });

            // If editing and current seat is available, select it
            if (isEdit && currentSeat && availableSeats.includes(currentSeat)) {
              seatNumberSelect.value = currentSeat;
            }
          })
          .catch(error => {
            console.error('Error fetching assigned seats:', error);
            seatNumberSelect.innerHTML = '<option value="">Error loading seats</option>';
          });
      }

      // Format date for display (e.g., Mar 10, 2025)
      function formatDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', {
          month: 'short',
          day: 'numeric',
          year: 'numeric'
        });
      }

      // Event listener for flight selection
      document.getElementById('flight_id').addEventListener('change', function() {
        const seatTypeSelect = document.getElementById('seat_type');
        seatTypeSelect.disabled = !this.value;

        if (this.value) {
          // Reset seat selection
          seatTypeSelect.value = '';
          document.getElementById('seat_number').innerHTML = '<option value="">Select Seat Number</option>';
          document.getElementById('seat_number').disabled = true;
        }
      });

      // Event listener for seat type selection
      document.getElementById('seat_type').addEventListener('change', function() {
        const flightId = document.getElementById('flight_id').value;

        if (flightId && this.value) {
          populateSeats(flightId, this.value);
        } else {
          document.getElementById('seat_number').innerHTML = '<option value="">Select Seat Number</option>';
          document.getElementById('seat_number').disabled = true;
        }
      });

      // Event listeners for opening the assignment modal
      assignFlightBtns.forEach(btn => {
        btn.addEventListener('click', function() {
          const userData = JSON.parse(this.getAttribute('data-booking'));
          openFlightModal(userData);
        });
      });

      // Event listeners for opening the edit modal
      editFlightBtns.forEach(btn => {
        btn.addEventListener('click', function() {
          const assignmentData = JSON.parse(this.getAttribute('data-assignment'));

          // Create a user data object from the assignment data
          const userData = {
            user_id: assignmentData.user_id,
            booking_id: assignmentData.booking_id,
            full_name: assignmentData.full_name,
            package_title: assignmentData.package_title,
            package_type: assignmentData.package_type,
            booking_date: assignmentData.package_booking_date,
            status: assignmentData.package_status
          };

          openFlightModal(userData, true, assignmentData);
        });
      });

      // Event listeners for closing the modal
      closeModal.addEventListener('click', function() {
        flightModal.classList.add('hidden');
      });

      cancelAssign.addEventListener('click', function() {
        flightModal.classList.add('hidden');
      });
    });
  </script>
</body>

</html>