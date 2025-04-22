<?php
session_name("admin_session");
session_start();
if (!isset($_SESSION['admin_email'])) {
  header("Location: login.php");
  exit();
}

// Database connection
include 'connection/connection.php';

// Fetch package bookings with user, package, destination, and duration details
$bookings_query = "
    SELECT pb.id, pb.user_id, pb.package_id, pb.booking_date, pb.status, pb.payment_status,
           u.full_name, u.email, p.title as package_title, p.destination, p.duration,
           hb.id as hotel_booking_id, hb.room_id, h.hotel_name
    FROM package_booking pb
    JOIN users u ON pb.user_id = u.id
    JOIN packages p ON pb.package_id = p.id
    LEFT JOIN hotel_bookings hb ON hb.package_booking_id = pb.id
    LEFT JOIN hotels h ON hb.hotel_id = h.id
    ORDER BY pb.booking_date DESC";
$bookings_result = $conn->query($bookings_query);
$bookings = [];
while ($row = $bookings_result->fetch_assoc()) {
  $bookings[] = $row;
}

// Variables for modal
$package_booking_id = isset($_POST['package_booking_id']) ? (int)$_POST['package_booking_id'] : 0;
$hotel_id = isset($_POST['hotel_id']) ? (int)$_POST['hotel_id'] : 0;
$check_in = isset($_POST['check_in']) ? $_POST['check_in'] : date('Y-m-d');
$check_out = isset($_POST['check_out']) ? $_POST['check_out'] : date('Y-m-d', strtotime('+1 day'));
$package_destination = '';
$package_duration = 0;
$has_searched = false;
$available_rooms = [];
$room_details = [];
$error_message = '';
$success_message = '';

// Fetch hotels matching the package destination when checking availability
$hotels = [];
if ($package_booking_id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $stmt = $conn->prepare("SELECT destination, duration FROM packages p JOIN package_booking pb ON pb.package_id = p.id WHERE pb.id = ?");
  $stmt->bind_param("i", $package_booking_id);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();
  $package_destination = $result['destination'] ?? '';
  $package_duration = $result['duration'] ?? 0;

  if ($package_destination) {
    $hotels_query = "SELECT id, hotel_name, location FROM hotels WHERE location = ? ORDER BY hotel_name";
    $stmt = $conn->prepare($hotels_query);
    $stmt->bind_param("s", $package_destination);
    $stmt->execute();
    $hotels_result = $stmt->get_result();
    while ($row = $hotels_result->fetch_assoc()) {
      $hotels[] = $row;
    }
  }

  // Set default check-out date based on duration
  if ($package_duration > 0 && !isset($_POST['check_out'])) {
    $check_out = date('Y-m-d', strtotime($check_in . " + $package_duration days"));
  }
}

// Handle room assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_room'])) {
  $room_id = $_POST['room_id'];
  $user_id = (int)$_POST['user_id'];

  // Fetch user details
  $stmt = $conn->prepare("SELECT full_name, email, phone_number FROM users WHERE id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc();

  if (!$user) {
    $error_message = "User not found.";
  } else {
    // Fetch hotel details
    $stmt = $conn->prepare("SELECT * FROM hotels WHERE id = ?");
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $hotel = $stmt->get_result()->fetch_assoc();

    if (!$hotel) {
      $error_message = "Hotel not found.";
    } else {
      $hotel['room_ids'] = json_decode($hotel['room_ids'], true) ?: [];

      // Check room availability
      $stmt = $conn->prepare("
                SELECT room_id FROM hotel_bookings 
                WHERE hotel_id = ? 
                AND (
                    (check_in_date < ? AND check_out_date > ?)
                    OR (check_in_date = ?)
                    OR (check_out_date = ?)
                )
                AND check_out_date >= CURDATE()
                AND status != 'cancelled'
            ");
      $stmt->bind_param("issss", $hotel_id, $check_out, $check_in, $check_in, $check_out);
      $stmt->execute();
      $booked_rooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
      $booked_room_ids = array_column($booked_rooms, 'room_id');

      $available_rooms = array_diff($hotel['room_ids'], $booked_room_ids);

      if (!in_array($room_id, $available_rooms)) {
        $error_message = "Selected room is not available for the chosen dates.";
      } else {
        try {
          // Insert hotel booking
          $stmt = $conn->prepare("
                        INSERT INTO hotel_bookings (
                            hotel_id, room_id, user_id, guest_name, guest_email, guest_phone, 
                            check_in_date, check_out_date, status, package_booking_id
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', ?)
                    ");
          $stmt->bind_param(
            "iissssssi",
            $hotel_id,
            $room_id,
            $user_id,
            $user['full_name'],
            $user['email'],
            $user['phone_number'],
            $check_in,
            $check_out,
            $package_booking_id
          );
          $stmt->execute();

          $success_message = "Room successfully assigned for package booking ID $package_booking_id.";
          header("Location: admin-view-package-bookings.php?success=" . urlencode($success_message));
          exit();
        } catch (Exception $e) {
          $error_message = "Assignment failed: " . $e->getMessage();
        }
      }
    }
  }
}

// Check for room availability in modal
if (isset($_POST['check_availability']) && $hotel_id > 0) {
  $has_searched = true;

  $stmt = $conn->prepare("SELECT * FROM hotels WHERE id = ?");
  $stmt->bind_param("i", $hotel_id);
  $stmt->execute();
  $hotel = $stmt->get_result()->fetch_assoc();

  if ($hotel) {
    $hotel['room_ids'] = json_decode($hotel['room_ids'], true) ?: [];

    $stmt = $conn->prepare("
            SELECT room_id FROM hotel_bookings 
            WHERE hotel_id = ? 
            AND (
                (check_in_date < ? AND check_out_date > ?)
                OR (check_in_date = ?)
                OR (check_out_date = ?)
            )
            AND check_out_date >= CURDATE()
            AND status != 'cancelled'
        ");
    $stmt->bind_param("issss", $hotel_id, $check_out, $check_in, $check_in, $check_out);
    $stmt->execute();
    $booked_rooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $booked_room_ids = array_column($booked_rooms, 'room_id');

    $available_rooms = array_diff($hotel['room_ids'], $booked_room_ids);

    $room_types = [
      'standard' => ['description' => 'Comfortable room with basic amenities', 'capacity' => '2 guests'],
      'deluxe' => ['description' => 'Spacious room with premium amenities', 'capacity' => '2-3 guests'],
      'suite' => ['description' => 'Luxury suite with separate living area', 'capacity' => '4 guests'],
    ];

    foreach ($available_rooms as $room) {
      $type_keys = array_keys($room_types);
      $random_type = $type_keys[array_rand($type_keys)];
      $room_details[$room] = [
        'type' => $random_type,
        'description' => $room_types[$random_type]['description'],
        'capacity' => $room_types[$random_type]['capacity'],
      ];
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Package Bookings - Umrah & Hajj Travel Admin</title>
  <link rel="stylesheet" href="../assets/css/output.css">
  <link rel="stylesheet" href="assets/css/output.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>

<body class="bg-gray-50 font-sans text-gray-800">
  <?php include 'notifications.php'; ?>
  <div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/preloader.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Navbar -->
      <header class="bg-white border-b border-gray-200 sticky top-0 z-30 shadow-sm">
        <div class="px-6 py-3 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <button class="lg:hidden text-gray-600 hover:text-gray-900 transition-colors" id="menu-btn">
              <i class="fas fa-bars text-lg"></i>
            </button>
            <h1 class="text-xl font-bold text-emerald-700">Umrah & Hajj Admin</h1>
          </div>
          <div class="flex items-center gap-3">
            <div class="relative">
              <button id="notif-bell" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-all relative">
                <i class="fas fa-bell text-lg"></i>
                <span id="notif-count" class="absolute top-0 right-0 h-4 w-4 rounded-full bg-red-500 text-white text-xs flex items-center justify-center hidden">0</span>
              </button>
              <div id="notif-dropdown" class="absolute right-0 mt-2 w-80 bg-white border border-gray-200 rounded-lg shadow-lg hidden max-h-96 overflow-y-auto z-40">
                <div class="p-4 border-b border-gray-200">
                  <h3 class="text-sm font-semibold text-gray-700">Notifications</h3>
                </div>
                <div id="notif-list" class="divide-y divide-gray-200"></div>
                <div class="p-2 text-center">
                  <a href="notifications_page.php" class="text-sm text-emerald-600 hover:underline">View All Notifications</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Content Area -->
      <main class="flex-1 overflow-y-auto p-6">
        <div class="max-w-7xl mx-auto">
          <!-- Header -->
          <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800">Package Bookings</h2>
            <p class="text-gray-600">View and manage hotel room assignments for package bookings.</p>
          </div>

          <!-- Success/Error Messages -->
          <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-50 text-green-800 p-4 rounded-lg mb-6 flex items-center shadow-sm">
              <i class="fas fa-check-circle mr-2 text-xl"></i>
              <p><?php echo htmlspecialchars($_GET['success']); ?></p>
            </div>
          <?php endif; ?>
          <?php if ($error_message): ?>
            <div class="bg-red-50 text-red-800 p-4 rounded-lg mb-6 flex items-center shadow-sm">
              <i class="fas fa-exclamation-circle mr-2 text-xl"></i>
              <p><?php echo $error_message; ?></p>
            </div>
          <?php endif; ?>

          <!-- Package Bookings Table -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="overflow-x-auto">
              <table class="w-full text-left">
                <thead>
                  <tr class="border-b border-gray-200">
                    <th class="py-3 px-4 text-sm font-semibold text-gray-700">Booking ID</th>
                    <th class="py-3 px-4 text-sm font-semibold text-gray-700">User</th>
                    <th class="py-3 px-4 text-sm font-semibold text-gray-700">Package</th>
                    <th class="py-3 px-4 text-sm font-semibold text-gray-700">Destination</th>
                    <th class="py-3 px-4 text-sm font-semibold text-gray-700">Booking Date</th>
                    <th class="py-3 px-4 text-sm font-semibold text-gray-700">Status</th>
                    <th class="py-3 px-4 text-sm font-semibold text-gray-700">Hotel Assignment</th>
                    <th class="py-3 px-4 text-sm font-semibold text-gray-700">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($bookings as $booking): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                      <td class="py-3 px-4 text-sm"><?php echo $booking['id']; ?></td>
                      <td class="py-3 px-4 text-sm">
                        <?php echo htmlspecialchars($booking['full_name']); ?><br>
                        <span class="text-xs text-gray-500"><?php echo htmlspecialchars($booking['email']); ?></span>
                      </td>
                      <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($booking['package_title']); ?></td>
                      <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($booking['destination'] ?? 'Not Set'); ?></td>
                      <td class="py-3 px-4 text-sm"><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                      <td class="py-3 px-4 text-sm">
                        <span class="inline-block px-2 py-1 rounded-full text-xs font-medium
                                                    <?php echo $booking['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : ($booking['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                          <?php echo htmlspecialchars($booking['status']); ?>
                        </span>
                      </td>
                      <td class="py-3 px-4 text-sm">
                        <?php if ($booking['hotel_booking_id']): ?>
                          <span class="text-emerald-600">
                            Assigned: <?php echo htmlspecialchars($booking['hotel_name']); ?> (Room <?php echo htmlspecialchars(str_replace('r', '', $booking['room_id'])); ?>)
                          </span>
                        <?php else: ?>
                          <span class="text-gray-500">Not Assigned</span>
                        <?php endif; ?>
                      </td>
                      <td class="py-3 px-4 text-sm">
                        <?php if (!$booking['hotel_booking_id']): ?>
                          <button onclick="showAssignmentForm(<?php echo $booking['id']; ?>, <?php echo $booking['user_id']; ?>)"
                            class="bg-emerald-600 text-white px-3 py-1 rounded-lg hover:bg-emerald-700 transition duration-200 flex items-center text-xs">
                            <i class="fas fa-hotel mr-1"></i> Assign Room
                          </button>
                        <?php else: ?>
                          <span class="text-gray-400 text-xs">Room Assigned</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if (empty($bookings)): ?>
                    <tr>
                      <td colspan="8" class="py-3 px-4 text-center text-gray-500">No package bookings found.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- Assignment Modal -->
  <div id="assignmentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6 max-h-screen overflow-y-auto mx-4">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-semibold text-gray-800">Assign Hotel Room</h3>
        <button type="button" onclick="hideAssignmentForm()" class="text-gray-500 hover:text-gray-700">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form action="" method="POST" id="assignmentForm">
        <input type="hidden" name="package_booking_id" id="packageBookingId">
        <input type="hidden" name="user_id" id="userId">

        <!-- Package Destination Info -->
        <div class="mb-4">
          <p class="text-sm text-gray-700">
            <strong>Package Destination:</strong>
            <span id="packageDestination"><?php echo htmlspecialchars($package_destination ?: 'Not Set'); ?></span>
          </p>
        </div>

        <!-- Hotel Selection -->
        <div class="mb-4">
          <label for="hotel_id" class="block text-sm font-medium text-gray-700 mb-2">
            <i class="fas fa-hotel mr-2"></i>Select Hotel
          </label>
          <select name="hotel_id" id="hotel_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" required>
            <option value="">-- Select a Hotel --</option>
            <?php foreach ($hotels as $hotel): ?>
              <option value="<?php echo $hotel['id']; ?>" <?php echo $hotel_id == $hotel['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($hotel['hotel_name']) . " (" . htmlspecialchars($hotel['location']) . ")"; ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (empty($hotels) && $package_destination): ?>
            <p class="text-sm text-red-600 mt-2">No hotels available in <?php echo htmlspecialchars($package_destination); ?>.</p>
          <?php endif; ?>
        </div>

        <!-- Date Selection -->
        <div class="md:flex md:space-x-4 mb-4">
          <div class="flex-1">
            <label for="check_in" class="block text-sm font-medium text-gray-700 mb-2">
              <i class="far fa-calendar-alt mr-2"></i>Check-in Date
            </label>
            <input type="date" name="check_in" id550
              id="check_in" value="<?php echo htmlspecialchars($check_in); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" min="<?php echo date('Y-m-d'); ?>" required>
          </div>
          <div class="flex-1">
            <label for="check_out" class="block text-sm font-medium text-gray-700 mb-2">
              <i class="far fa-calendar-alt mr-2"></i>Check-out Date
            </label>
            <input type="date" name="check_out" id="check_out" value="<?php echo htmlspecialchars($check_out); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
          </div>
        </div>

        <!-- Check Availability Button -->
        <button type="submit" name="check_availability" class="w-full bg-emerald-600 text-white py-3 rounded-lg hover:bg-emerald-700 transition duration-200 flex items-center justify-center mb-4">
          <i class="fas fa-search mr-2"></i>Check Room Availability
        </button>

        <!-- Available Rooms -->
        <?php if ($has_searched && $hotel_id > 0): ?>
          <div class="mb-4">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">Available Rooms (<?php echo count($available_rooms); ?>)</h4>
            <?php if (!empty($available_rooms)): ?>
              <select name="room_id" id="room_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" required>
                <option value="">-- Select a Room --</option>
                <?php foreach ($available_rooms as $room): ?>
                  <option value="<?php echo htmlspecialchars($room); ?>">
                    Room <?php echo htmlspecialchars(str_replace('r', '', $room)); ?> (<?php echo isset($room_details[$room]) ? $room_details[$room]['type'] : 'Standard'; ?>, <?php echo isset($room_details[$room]) ? $room_details[$room]['capacity'] : '2 guests'; ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <p class="text-sm text-red-600">No rooms available for the selected dates.</p>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <!-- Submit Assignment -->
        <?php if ($has_searched && !empty($available_rooms)): ?>
          <button type="submit" name="assign_room" class="w-full bg-emerald-600 text-white py-3 rounded-lg hover:bg-emerald-700 transition duration-200 flex items-center justify-center">
            <i class="fas fa-check-circle mr-2"></i>Confirm Assignment
          </button>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <script>
    // Date picker initialization
    flatpickr("#check_in", {
      minDate: "today",
      onChange: function(selectedDates, dateStr, instance) {
        const duration = <?php echo $package_duration ?: 1; ?>;
        const minCheckOut = new Date(selectedDates[0].getTime() + duration * 86400000).toISOString().split('T')[0];
        document.getElementById("check_out").setAttribute("min", minCheckOut);
        const checkOutDate = new Date(document.getElementById("check_out").value);
        if (checkOutDate <= selectedDates[0]) {
          document.getElementById("check_out").value = minCheckOut;
        }
      }
    });

    flatpickr("#check_out", {
      minDate: new Date().fp_incr(<?php echo $package_duration ?: 1; ?>),
    });

    // Assignment modal functionality
    function showAssignmentForm(packageBookingId, userId) {
      document.getElementById('packageBookingId').value = packageBookingId;
      document.getElementById('userId').value = userId;
      document.getElementById('assignmentModal').classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }

    function hideAssignmentForm() {
      document.getElementById('assignmentModal').classList.add('hidden');
      document.body.style.overflow = 'auto';
      // Reset form
      document.getElementById('assignmentForm').reset();
      document.getElementById('check_in').value = '<?php echo date('Y-m-d'); ?>';
      document.getElementById('check_out').value = '<?php echo $check_out; ?>';
    }

    // Close modal when clicking outside
    document.getElementById('assignmentModal').addEventListener('click', function(e) {
      if (e.target === this) {
        hideAssignmentForm();
      }
    });

    // Mobile menu toggle
    document.getElementById('menu-btn').addEventListener('click', function() {
      document.querySelector('.sidebar').classList.toggle('-translate-x-full');
    });

    // Notification Bell
    const bell = document.getElementById('notif-bell');
    const dropdown = document.getElementById('notif-dropdown');
    const notifList = document.getElementById('notif-list');
    const notifCount = document.getElementById('notif-count');

    bell.addEventListener('click', () => {
      dropdown.classList.toggle('hidden');
      if (!dropdown.classList.contains('hidden')) {
        fetchNotifications();
      }
    });

    document.addEventListener('click', (e) => {
      if (!bell.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.add('hidden');
      }
    });

    function fetchNotifications() {
      fetch('notifications.php', {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(response => response.json())
        .then(data => {
          notifList.innerHTML = '';
          const unreadCount = Array.isArray(data) ? data.length : (data.id ? 1 : 0);

          if (unreadCount > 0) {
            notifCount.textContent = unreadCount;
            notifCount.classList.remove('hidden');
          } else {
            notifCount.classList.add('hidden');
          }

          const notifications = Array.isArray(data) ? data : (data.id ? [data] : []);
          if (notifications.length === 0) {
            notifList.innerHTML = '<p class="p-4 text-sm text-gray-500">No new notifications</p>';
            return;
          }

          notifications.forEach(notif => {
            const div = document.createElement('div');
            div.className = 'p-4 hover:bg-gray-50 transition-colors';
            div.innerHTML = `
                        <p class="text-sm text-gray-700">${notif.message}</p>
                        <p class="text-xs text-gray-500">${new Date(notif.created_at).toLocaleString()}</p>
                    `;
            notifList.appendChild(div);
          });
        })
        .catch(error => console.error('Error fetching notifications:', error));
    }

    setInterval(fetchNotifications, 5000);
    fetchNotifications();
  </script>
</body>

</html>