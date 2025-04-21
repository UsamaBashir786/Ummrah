<?php
session_name("admin_session");
session_start();
include 'connection/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
  header("Location: admin-login.php");
  exit();
}

// Get package bookings that need hotel assignment
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

// Get hotels from database
function getHotels($location = null)
{
  global $conn;
  $sql = "SELECT * FROM hotels";
  if ($location) {
    $sql .= " WHERE location = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $location);
    $stmt->execute();
    $result = $stmt->get_result();
  } else {
    $result = $conn->query($sql);
  }
  return $result->fetch_all(MYSQLI_ASSOC);
}

// Get existing hotel assignments
function getHotelAssignments($booking_id, $user_id = null)
{
  global $conn;

  // First, check if the user_id column exists in the table
  $check_column = $conn->query("SHOW COLUMNS FROM package_assign LIKE 'user_id'");
  $column_exists = $check_column->num_rows > 0;

  if ($column_exists && $user_id) {
    // If user_id is provided and the column exists, filter by both booking_id and user_id
    $sql = "SELECT pa.*, h.hotel_name, h.location FROM package_assign pa
            LEFT JOIN hotels h ON pa.hotel_id = h.id
            WHERE pa.booking_id = ? AND pa.user_id = ? AND pa.hotel_id IS NOT NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $booking_id, $user_id);
  } else {
    // Otherwise, just filter by booking_id
    $sql = "SELECT pa.*, h.hotel_name, h.location FROM package_assign pa
            LEFT JOIN hotels h ON pa.hotel_id = h.id
            WHERE pa.booking_id = ? AND pa.hotel_id IS NOT NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $booking_id);
  }

  $stmt->execute();
  $result = $stmt->get_result();
  return $result->fetch_all(MYSQLI_ASSOC);
}

// Get already booked rooms for a hotel
function getBookedRooms($hotel_id)
{
  global $conn;
  $sql = "SELECT seat_number FROM package_assign WHERE hotel_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $hotel_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $booked_rooms = [];
  while ($row = $result->fetch_assoc()) {
    $booked_rooms[] = $row['seat_number'];
  }
  return $booked_rooms;
}

// Get available rooms for a hotel
function getAvailableRooms($hotel_id)
{
  $hotel = getHotelById($hotel_id);
  if (!$hotel) {
    return [];
  }

  // Get all rooms from the hotel
  $all_rooms = json_decode($hotel['room_ids'], true);
  if (!$all_rooms) {
    return [];
  }

  // Get booked rooms
  $booked_rooms = getBookedRooms($hotel_id);

  // Filter out booked rooms
  $available_rooms = array_diff($all_rooms, $booked_rooms);

  return $available_rooms;
}

// Get hotel by ID
function getHotelById($hotel_id)
{
  global $conn;
  $sql = "SELECT * FROM hotels WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $hotel_id);
  $stmt->execute();
  $result = $stmt->get_result();
  return $result->fetch_assoc();
}

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_hotel'])) {
  $booking_id = $_POST['booking_id'];
  $user_id = $_POST['user_id'];
  $hotel_id = $_POST['hotel_id'];
  $room_number = $_POST['room_number'];

  // Check if this user already has a hotel assignment for this booking
  $existing_assignments = getHotelAssignments($booking_id, $user_id);

  if (count($existing_assignments) > 0) {
    $error_message = "Hotel has already been assigned to this user for booking #$booking_id";
  } else {
    // No existing assignment, proceed with insert
    // Validate inputs
    if (empty($booking_id) || empty($user_id) || empty($hotel_id) || empty($room_number)) {
      $error_message = "All fields are required.";
    } else {
      // Insert assignment with user_id included
      $sql = "INSERT INTO package_assign 
              (booking_id, user_id, hotel_id, transport_id, flight_id, seat_type, seat_number, transport_seat_number) 
              VALUES (?, ?, ?, NULL, NULL, NULL, ?, NULL)";

      $stmt = $conn->prepare($sql);
      $stmt->bind_param("iiis", $booking_id, $user_id, $hotel_id, $room_number);

      if ($stmt->execute()) {
        $success_message = "Hotel successfully assigned to booking #$booking_id";
      } else {
        $error_message = "Error assigning hotel: " . $conn->error;
      }
    }
  }
}

// Delete assignment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_assignment'])) {
  $assignment_id = $_POST['assignment_id'];

  $sql = "DELETE FROM package_assign WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $assignment_id);

  if ($stmt->execute()) {
    $success_message = "Hotel assignment successfully deleted";
  } else {
    $error_message = "Error deleting assignment: " . $conn->error;
  }
}

// Get data
$bookings = getPackageBookings();
$makkah_hotels = getHotels('makkah');
$madinah_hotels = getHotels('madinah');
$all_hotels = getHotels();

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
    $current_assignments = getHotelAssignments($booking_id);
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hotel Assignment | Admin Panel</title>
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

    .makkah-card {
      border-left: 4px solid #8b5cf6;
    }

    .madinah-card {
      border-left: 4px solid #3b82f6;
    }

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
      background-color: #8b5cf6;
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
          <i class="text-purple-600 fas fa-hotel mx-2"></i> Hotel Assignment
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
                <a href="hotel-assign.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
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
                <h3 class="font-semibold text-lg mb-2">Current Hotel Assignments</h3>

                <?php if (count($current_assignments) > 0): ?>
                  <div class="space-y-4">
                    <?php foreach ($current_assignments as $assignment): ?>
                      <div class="assignment-card <?php echo isset($assignment['location']) && $assignment['location'] == 'makkah' ? 'makkah-card' : 'madinah-card'; ?>">
                        <div class="flex justify-between">
                          <h4 class="font-medium text-md"><?php echo isset($assignment['hotel_name']) ? htmlspecialchars($assignment['hotel_name']) : 'Unknown Hotel'; ?> - <?php echo isset($assignment['location']) ? ucfirst($assignment['location']) : 'Unknown Location'; ?></h4>
                          <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this assignment?');">
                            <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                            <input type="hidden" name="delete_assignment" value="1">
                            <button type="submit" class="text-red-500 hover:text-red-700">
                              <i class="fas fa-trash"></i>
                            </button>
                          </form>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mt-2">
                          <p><span class="text-gray-600">Room Number:</span> <?php echo htmlspecialchars($assignment['seat_number']); ?></p>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Assigned: <?php echo date('d M Y H:i', strtotime($assignment['created_at'])); ?></p>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <p class="text-gray-500 italic">No hotel assignments yet.</p>
                <?php endif; ?>
              </div>

              <!-- Check if user already has hotel assigned -->
              <?php
              $user_has_assignment = false;
              foreach ($current_assignments as $assignment) {
                if (isset($assignment['user_id']) && $assignment['user_id'] == $current_booking['user_id']) {
                  $user_has_assignment = true;
                  break;
                }
              }
              ?>

              <!-- Add New Assignment only if user doesn't have one already -->
              <?php if (!$user_has_assignment): ?>
                <div class="mt-6">
                  <h3 class="font-semibold text-lg mb-2">Add New Hotel Assignment</h3>

                  <div class="tab-buttons flex">
                    <button class="tab-btn active" onclick="switchTab('makkah')">Makkah Hotels</button>
                    <button class="tab-btn" onclick="switchTab('madinah')">Madinah Hotels</button>
                  </div>

                  <!-- Makkah Hotels Form -->
                  <div id="makkah-tab" class="tab-content active">
                    <form method="POST" action="" class="space-y-4">
                      <input type="hidden" name="assign_hotel" value="1">
                      <input type="hidden" name="booking_id" value="<?php echo $current_booking['id']; ?>">
                      <input type="hidden" name="user_id" value="<?php echo $current_booking['user_id']; ?>">

                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <label class="block text-gray-700 text-sm font-bold mb-2" for="makkah_hotel">
                            Makkah Hotel
                          </label>
                          <select id="makkah_hotel" name="hotel_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" required onchange="updateRooms('makkah')">
                            <option value="">Select Hotel</option>
                            <?php foreach ($makkah_hotels as $hotel): ?>
                              <option value="<?php echo $hotel['id']; ?>">
                                <?php echo htmlspecialchars($hotel['hotel_name']); ?> (<?php echo $hotel['rating']; ?> Star)
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>

                        <div>
                          <label class="block text-gray-700 text-sm font-bold mb-2" for="makkah_room">
                            Room Number
                          </label>
                          <select id="makkah_room" name="room_number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                            <option value="">Select Room</option>
                            <!-- Rooms will be populated by JavaScript -->
                          </select>
                        </div>
                      </div>

                      <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700">
                        <i class="fas fa-plus-circle mr-2"></i>Assign Makkah Hotel
                      </button>
                    </form>
                  </div>

                  <!-- Madinah Hotels Form -->
                  <div id="madinah-tab" class="tab-content">
                    <form method="POST" action="" class="space-y-4">
                      <input type="hidden" name="assign_hotel" value="1">
                      <input type="hidden" name="booking_id" value="<?php echo $current_booking['id']; ?>">
                      <input type="hidden" name="user_id" value="<?php echo $current_booking['user_id']; ?>">

                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <label class="block text-gray-700 text-sm font-bold mb-2" for="madinah_hotel">
                            Madinah Hotel
                          </label>
                          <select id="madinah_hotel" name="hotel_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required onchange="updateRooms('madinah')">
                            <option value="">Select Hotel</option>
                            <?php foreach ($madinah_hotels as $hotel): ?>
                              <option value="<?php echo $hotel['id']; ?>">
                                <?php echo htmlspecialchars($hotel['hotel_name']); ?> (<?php echo $hotel['rating']; ?> Star)
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>

                        <div>
                          <label class="block text-gray-700 text-sm font-bold mb-2" for="madinah_room">
                            Room Number
                          </label>
                          <select id="madinah_room" name="room_number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">Select Room</option>
                            <!-- Rooms will be populated by JavaScript -->
                          </select>
                        </div>
                      </div>

                      <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-plus-circle mr-2"></i>Assign Madinah Hotel
                      </button>
                    </form>
                  </div>
                </div>
              <?php else: ?>
                <div class="mt-6 bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                  <p class="text-yellow-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    Hotel has already been assigned to this user. You can delete the existing assignment above if you want to assign a different hotel.
                  </p>
                </div>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <!-- Bookings List View -->
            <div class="mb-6">
              <h2 class="text-2xl font-bold">Package Bookings</h2>
              <p class="text-gray-600 mt-2">Assign hotels to package bookings</p>
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
                      <th class="py-2 px-4 border-b">Hotel Status</th>
                      <th class="py-2 px-4 border-b">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($bookings as $booking): ?>
                      <?php
                      // Check if this booking already has hotel assigned
                      $user_assignments = getHotelAssignments($booking['id'], $booking['user_id']);
                      $has_hotel = count($user_assignments) > 0;
                      ?>
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
                          <?php if ($has_hotel): ?>
                            <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">
                              <i class="fas fa-check-circle mr-1"></i> Assigned
                            </span>
                          <?php else: ?>
                            <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800">
                              <i class="fas fa-times-circle mr-1"></i> Not Assigned
                            </span>
                          <?php endif; ?>
                        </td>
                        <td class="py-2 px-4 border-b">
                          <a href="hotel-assign.php?booking_id=<?php echo $booking['id']; ?>" class="text-purple-600 hover:text-purple-800">
                            <i class="fas fa-hotel"></i> <?php echo $has_hotel ? 'View' : 'Assign'; ?>
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

    // Update room dropdowns when hotel is selected
    function updateRooms(location) {
      const hotelSelect = document.getElementById(`${location}_hotel`);
      const roomSelect = document.getElementById(`${location}_room`);

      if (!hotelSelect.value) {
        // If no hotel is selected, clear room dropdown
        roomSelect.innerHTML = '<option value="">Select Room</option>';
        return;
      }

      // Fetch available rooms for the selected hotel
      fetch(`get-hotel-rooms.php?hotel_id=${hotelSelect.value}`)
        .then(response => response.json())
        .then(data => {
          // Clear existing options
          roomSelect.innerHTML = '<option value="">Select Room</option>';

          // Add new options based on available rooms
          if (data.rooms && data.rooms.length > 0) {
            data.rooms.forEach(room => {
              const option = document.createElement('option');
              option.value = room;
              option.textContent = room;
              roomSelect.appendChild(option);
            });
          } else {
            const option = document.createElement('option');
            option.value = "";
            option.textContent = "No rooms available";
            roomSelect.appendChild(option);
          }
        })
        .catch(error => {
          console.error('Error fetching rooms:', error);
          roomSelect.innerHTML = '<option value="">Error loading rooms</option>';
        });
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