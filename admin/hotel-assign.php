<?php
// Start session
session_name("admin_session");
session_start();

// Debug: Log all POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  error_log("POST request received: " . print_r($_POST, true));
}

// Include database connection and shared functions
require_once 'connection/connection.php';
require_once 'includes/functions.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
  error_log("Session check failed: Redirecting to admin-login.php");
  header("Location: admin-login.php");
  exit();
}

// Handle Form Submissions
$success_message = '';
$error_message = '';

// Assign hotel booking
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_hotel'])) {
  $booking_id = $_POST['booking_id'];
  $user_id = $_POST['user_id'];
  $hotel_id = $_POST['hotel_id'];
  $room_id = $_POST['room_id']; // This should be a string like "r1"
  $guest_name = $_POST['guest_name'];
  $guest_email = $_POST['guest_email'];
  $guest_phone = $_POST['guest_phone'];
  $check_in_date = $_POST['check_in_date'];
  $check_out_date = $_POST['check_out_date'];
  $status = 'pending';

  // Debug: Log the received room_id
  error_log("Received room_id: " . $room_id);

  // Validate inputs
  if (
    empty($booking_id) || empty($user_id) || empty($hotel_id) || empty($room_id) ||
    empty($guest_name) || empty($guest_email) || empty($guest_phone) ||
    empty($check_in_date) || empty($check_out_date)
  ) {
    $error_message = "All fields are required.";
  } elseif (strtotime($check_in_date) >= strtotime($check_out_date)) {
    $error_message = "Check-out date must be after check-in date.";
  } else {
    // Validate room_id
    $available_rooms = getAvailableRooms($hotel_id);
    if (!in_array($room_id, $available_rooms)) {
      $error_message = "Invalid room ID selected: " . htmlspecialchars($room_id);
      error_log("Invalid room ID: " . $room_id . " not in " . print_r($available_rooms, true));
    } else {
      // Check for existing hotel bookings
      $existing_bookings = getHotelBookings($booking_id);
      if (count($existing_bookings) > 0) {
        $error_message = "Hotel booking already exists for booking #$booking_id";
      } else {
        // Insert new hotel booking
        $sql = "INSERT INTO hotel_bookings 
                (hotel_id, room_id, user_id, package_booking_id, guest_name, guest_email, guest_phone, 
                 check_in_date, check_out_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
          "isisssssss", // Ensure room_id is treated as a string
          $hotel_id,
          $room_id,
          $user_id,
          $booking_id,
          $guest_name,
          $guest_email,
          $guest_phone,
          $check_in_date,
          $check_out_date,
          $status
        );

        if ($stmt->execute()) {
          $success_message = "Hotel booking successfully created for booking #$booking_id with Room ID: $room_id";
        } else {
          $error_message = "Error creating hotel booking: " . $conn->error;
          error_log("SQL Error: " . $conn->error);
        }
        $stmt->close();
      }
    }
  }
}

// Delete hotel booking
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_booking'])) {
  $hotel_booking_id = $_POST['booking_id'];

  // Debug: Log POST data
  error_log("Delete attempt: hotel_booking_id = " . $hotel_booking_id);
  error_log("POST data: " . print_r($_POST, true));

  // Validate input
  if (empty($hotel_booking_id) || !is_numeric($hotel_booking_id)) {
    $_SESSION['error_message'] = "Invalid booking ID.";
    error_log("Error: Invalid booking ID");
    header("Location: hotel-assign.php" . (isset($_GET['booking_id']) ? "?booking_id=" . urlencode($_GET['booking_id']) : ""));
    exit;
  } else {
    // Check if booking exists
    $check_sql = "SELECT id FROM hotel_bookings WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $hotel_booking_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
      $_SESSION['error_message'] = "No hotel booking found with ID #$hotel_booking_id";
      error_log("Error: No booking found with ID #$hotel_booking_id");
      header("Location: hotel-assign.php" . (isset($_GET['booking_id']) ? "?booking_id=" . urlencode($_GET['booking_id']) : ""));
      exit;
    } else {
      // Delete booking
      $sql = "DELETE FROM hotel_bookings WHERE id = ?";
      $stmt = $conn->prepare($sql);
      if (!$stmt) {
        $_SESSION['error_message'] = "Prepare failed: " . $conn->error;
        error_log("Prepare failed: " . $conn->error);
        header("Location: hotel-assign.php" . (isset($_GET['booking_id']) ? "?booking_id=" . urlencode($_GET['booking_id']) : ""));
        exit;
      } else {
        $stmt->bind_param("i", $hotel_booking_id);
        if ($stmt->execute()) {
          if ($stmt->affected_rows > 0) {
            $_SESSION['success_message'] = "Hotel booking successfully deleted";
            error_log("Success: Deleted hotel booking ID #$hotel_booking_id");
          } else {
            $_SESSION['error_message'] = "No hotel booking found with ID #$hotel_booking_id";
            error_log("Error: No booking found with ID #$hotel_booking_id");
          }
        } else {
          $_SESSION['error_message'] = "Error deleting booking: " . $conn->error;
          error_log("Error: " . $conn->error);
        }
        $stmt->close();
        header("Location: hotel-assign.php" . (isset($_GET['booking_id']) ? "?booking_id=" . urlencode($_GET['booking_id']) : ""));
        exit;
      }
    }
    $check_stmt->close();
  }
}

// Fetch Data
$bookings = getPackageBookings();
$makkah_hotels = getHotels('makkah');
$madinah_hotels = getHotels('madinah');
$all_hotels = getHotels();

// Handle specific booking view
$current_booking = null;
$current_bookings = [];
if (isset($_GET['booking_id']) && is_numeric($_GET['booking_id'])) {
  $booking_id = $_GET['booking_id'];
  foreach ($bookings as $booking) {
    if ($booking['id'] == $booking_id) {
      $current_booking = $booking;
      break;
    }
  }

  if ($current_booking) {
    $current_bookings = getHotelBookings($booking_id);
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hotel Booking | Admin Panel</title>
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
          <i class="text-purple-600 fas fa-hotel mx-2"></i> Hotel Booking
        </h1>
        <div class="flex items-center space-x-4">
          <button class="md:hidden text-gray-800" id="menu-btn">
            <i class="fas fa-bars"></i>
          </button>
        </div>
      </div>

      <div class="container mx-auto px-4 py-8">
        <!-- Success Message -->
        <?php if (isset($_SESSION['success_message'])): ?>
          <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" id="success-alert">
            <p><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
          </div>
          <?php unset($_SESSION['success_message']); ?>
          <script>
            setTimeout(() => {
              document.getElementById('success-alert').style.display = 'none';
            }, 5000);
          </script>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if (isset($_SESSION['error_message'])): ?>
          <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" id="error-alert">
            <p><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
          </div>
          <?php unset($_SESSION['error_message']); ?>
          <script>
            setTimeout(() => {
              document.getElementById('error-alert').style.display = 'none';
            }, 5000);
          </script>
        <?php endif; ?>

        <!-- Local Messages (for assignment) -->
        <?php if ($success_message): ?>
          <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" id="success-alert-local">
            <p><?php echo htmlspecialchars($success_message); ?></p>
          </div>
          <script>
            setTimeout(() => {
              document.getElementById('success-alert-local').style.display = 'none';
            }, 5000);
          </script>
        <?php endif; ?>

        <?php if ($error_message): ?>
          <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" id="error-alert-local">
            <p><?php echo htmlspecialchars($error_message); ?></p>
          </div>
          <script>
            setTimeout(() => {
              document.getElementById('error-alert-local').style.display = 'none';
            }, 5000);
          </script>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-lg shadow-lg">
          <?php if ($current_booking): ?>
            <!-- Single Booking View -->
            <div class="mb-6">
              <div class="flex justify-between items-center">
                <h2 class="text-2xl font-bold">Booking #<?php echo htmlspecialchars($current_booking['id']); ?> Details</h2>
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
                  <p><span class="font-medium">Price:</span> <?php echo htmlspecialchars($current_booking['total_price']); ?> PKR</p>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                  <h3 class="font-semibold text-lg mb-2">Customer Information</h3>
                  <p><span class="font-medium">Name:</span> <?php echo htmlspecialchars($current_booking['user_name']); ?></p>
                  <p><span class="font-medium">Email:</span> <?php echo htmlspecialchars($current_booking['user_email']); ?></p>
                  <p><span class="font-medium">Phone:</span> <?php echo htmlspecialchars($current_booking['user_phone']); ?></p>
                </div>
              </div>

              <!-- Current Hotel Bookings -->
              <div class="mt-6">
                <h3 class="font-semibold text-lg mb-2">Current Hotel Bookings</h3>

                <?php if (count($current_bookings) > 0): ?>
                  <div class="space-y-4">
                    <?php foreach ($current_bookings as $booking): ?>
                      <div class="assignment-card <?php echo isset($booking['location']) && $booking['location'] == 'makkah' ? 'makkah-card' : 'madinah-card'; ?>">
                        <div class="flex justify-between">
                          <h4 class="font-medium text-md"><?php echo isset($booking['hotel_name']) ? htmlspecialchars($booking['hotel_name']) : 'Unknown Hotel'; ?> - <?php echo isset($booking['location']) ? ucfirst($booking['location']) : 'Unknown Location'; ?></h4>
                          <form method="POST" action="" onsubmit="return handleDelete(event, <?php echo $booking['id']; ?>)">
                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                            <input type="hidden" name="delete_booking" value="1">
                            <button type="submit" class="text-red-500 hover:text-red-700" title="Delete Booking #<?php echo $booking['id']; ?>">
                              <i class="fas fa-trash"></i>
                            </button>
                          </form>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mt-2">
                          <p><span class="text-gray-600">Room ID:</span> <?php echo htmlspecialchars($booking['room_id']); ?></p>
                          <p><span class="text-gray-600">Guest Name:</span> <?php echo htmlspecialchars($booking['guest_name']); ?></p>
                          <p><span class="text-gray-600">Check-in:</span> <?php echo date('d M Y', strtotime($booking['check_in_date'])); ?></p>
                          <p><span class="text-gray-600">Check-out:</span> <?php echo date('d M Y', strtotime($booking['check_out_date'])); ?></p>
                          <p><span class="text-gray-600">Status:</span> <?php echo ucfirst($booking['status']); ?></p>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Created: <?php echo date('d M Y H:i', strtotime($booking['created_at'])); ?></p>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <p class="text-gray-500 italic">No hotel bookings yet.</p>
                <?php endif; ?>
              </div>

              <!-- Check if user already has hotel booking -->
              <?php $user_has_booking = count($current_bookings) > 0; ?>

              <!-- Add New Hotel Booking -->
              <?php if (!$user_has_booking): ?>
                <div class="mt-6">
                  <h3 class="font-semibold text-lg mb-2">Add New Hotel Booking</h3>

                  <div class="tab-buttons flex">
                    <button class="tab-btn active" onclick="switchTab('makkah')">Makkah Hotels</button>
                    <button class="tab-btn" onclick="switchTab('madinah')">Madinah Hotels</button>
                  </div>

                  <!-- Makkah Hotels Form -->
                  <div id="makkah-tab" class="tab-content active">
                    <form method="POST" action="" class="space-y-4">
                      <input type="hidden" name="assign_hotel" value="1">
                      <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($current_booking['id']); ?>">
                      <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($current_booking['user_id']); ?>">

                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <label class="block text-gray-700 text-sm font-bold mb-2" for="makkah_hotel">Makkah Hotel</label>
                          <select id="makkah_hotel" name="hotel_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" required onchange="updateRooms('makkah', 'makkah_room')">
                            <option value="">Select Hotel</option>
                            <?php foreach ($makkah_hotels as $hotel): ?>
                              <option value="<?php echo $hotel['id']; ?>">
                                <?php echo htmlspecialchars($hotel['hotel_name']); ?> (<?php echo $hotel['rating']; ?> Star)
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>

                        <div>
                          <label class="block text-gray-700 text-sm font-bold mb-2" for="makkah_room">Room ID</label>
                          <select id="makkah_room" name="room_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                            <option value="">Select Room</option>
                          </select>
                        </div>

                        <div>
                          <label class="block text-gray-700 text-sm font-bold mb-2" for="guest_name">Guest Name</label>
                          <input type="text" id="guest_name" name="guest_name" value="<?php echo htmlspecialchars($current_booking['user_name']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                        </div>

                        <div>
                          <label class="block text-gray-700 text-sm font-bold mb-2" for="guest_email">Guest Email</label>
                          <input type="email" id="guest_email" name="guest_email" value="<?php echo htmlspecialchars($current_booking['user_email']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                        </div>

                        <div>
                          <label class="block text-gray-700 text-sm font-bold mb-2" for="guest_phone">Guest Phone</label>
                          <input type="tel" id="guest_phone" name="guest_phone" value="<?php echo htmlspecialchars($current_booking['user_phone']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                        </div>

                        <div>
                          <label class="block text-gray-700 text-sm font-bold mb-2" for="check_in_date">Check-in Date</label>
                          <input type="date" id="check_in_date" name="check_in_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                        </div>

                        <div>
                          <label class="block text-gray-700 text-sm font-bold mb-2" for="check_out_date">Check-out Date</label>
                          <input type="date" id="check_out_date" name="check_out_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                        </div>
                      </div>

                      <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700">
                        <i class="fas fa-plus-circle mr-2"></i>Book Makkah Hotel
                      </button>
                    </form>
                  </div>

                  <!-- Madinah Hotels Form -->
                  <div id="madinah-tab" class="tab-content">
                    <form method="POST" action="" class="space-y-4">
                      <input type="hidden" name="assign_hotel" value="1">
                      <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($current_booking['id']); ?>">
                      <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($current_booking['user_id']); ?>">

                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <label class="block text-gray-700 text-sm font-bold mb-2" for="madinah_hotel">Madinah Hotel</label>
                          <select id="madinah_hotel" name="hotel_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required onchange="updateRooms('madinah', 'madinah_room')">
                            <option value="">Select Hotel</option>
                            <?php foreach ($madinah_hotels as $hotel): ?>
                              <option value="<?php echo $hotel['id']; ?>">
                                <?php echo htmlspecialchars($hotel['hotel_name']); ?> (<?php echo $hotel['rating']; ?> Star)
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>

                        <div>
                          <label class="block text-gray-700 text-sm font-bold mb-2" for="madinah_room">Room ID</label>
                          <select id="madinah_room" name="room_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">Select Room</option>
                          </select>
                        </div>

                        <div>
                          <label class="block text-gray-700 text-sm font-bold mb-2" for="guest_name">Guest Name</label>
                          <input type="text" id="guest_name" name="guest_name" value="<?php echo htmlspecialchars($current_booking['user_name']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>

                        <div>
                          <label class="block text-gray-700 text-sm font-bold mb-2" for="guest_email">Guest Email</label>
                          <input type="email" id="guest_email" name="guest_email" value="<?php echo htmlspecialchars($current_booking['user_email']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>

                        <div>
                          <label class="block text-gray-700 text-sm font-bold mb-2" for="guest_phone">Guest Phone</label>
                          <input type="tel" id="guest_phone" name="guest_phone" value="<?php echo htmlspecialchars($current_booking['user_phone']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>

                        <div>
                          <label class="block text-gray-700 text-sm font-bold mb-2" for="check_in_date">Check-in Date</label>
                          <input type="date" id="check_in_date" name="check_in_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>

                        <div>
                          <label class="block text-gray-700 text-sm font-bold mb-2" for="check_out_date">Check-out Date</label>
                          <input type="date" id="check_out_date" name="check_out_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                      </div>

                      <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-plus-circle mr-2"></i>Book Madinah Hotel
                      </button>
                    </form>
                  </div>
                </div>
              <?php else: ?>
                <div class="mt-6 bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                  <p class="text-yellow-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    Hotel already assigned for this booking. View or delete the existing booking above.
                  </p>
                </div>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <!-- Bookings List View -->
            <div class="mb-6">
              <h2 class="text-2xl font-bold">Package Bookings</h2>
              <p class="text-gray-600 mt-2">Create hotel bookings for package bookings</p>
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
                      <th class="py-2 px-4 border-b">Hotel Booking</th>
                      <th class="py-2 px-4 border-b">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($bookings as $booking): ?>
                      <?php
                      $user_bookings = getHotelBookings($booking['id']);
                      $has_booking = count($user_bookings) > 0;
                      ?>
                      <tr>
                        <td class="py-2 px-4 border-b">#<?php echo htmlspecialchars($booking['id']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['user_name']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['package_title']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['total_price']); ?> PKR</td>
                        <td class="py-2 px-4 border-b"><?php echo date('d M Y', strtotime($booking['booking_date'])); ?></td>
                        <td class="py-2 px-4 border-b">
                          <span class="px-2 py-1 rounded-full text-xs <?php echo $booking['status'] == 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                            <?php echo ucfirst($booking['status']); ?>
                          </span>
                        </td>
                        <td class="py-2 px-4 border-b">
                          <?php if ($has_booking): ?>
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
                          <a href="hotel-assign.php?booking_id=<?php echo htmlspecialchars($booking['id']); ?>" class="text-purple-600 hover:text-purple-800">
                            <i class="fas fa-hotel"></i> <?php echo $has_booking ? 'View Booking' : 'Assign Hotel'; ?>
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
      document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
      });
      document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
      });
      document.getElementById(tabName + '-tab').classList.add('active');
      document.querySelector(`.tab-btn[onclick="switchTab('${tabName}')"]`).classList.add('active');
    }

    // Update room dropdowns when hotel is selected
    function updateRooms(location, roomSelectId) {
      const hotelSelect = document.getElementById(`${location}_hotel`);
      const roomSelect = document.getElementById(roomSelectId);
      const hotelId = hotelSelect.value;

      if (!hotelId) {
        roomSelect.innerHTML = '<option value="">Select Room</option>';
        return;
      }

      fetch(`get-hotel-rooms.php?hotel_id=${hotelId}`)
        .then(response => {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.json();
        })
        .then(data => {
          roomSelect.innerHTML = '<option value="">Select Room</option>';
          if (data.rooms && data.rooms.length > 0) {
            data.rooms.forEach(room => {
              const option = document.createElement('option');
              option.value = room;
              option.textContent = room;
              roomSelect.appendChild(option);
            });
          } else {
            roomSelect.innerHTML = '<option value="">No rooms available</option>';
          }
        })
        .catch(error => {
          console.error('Error fetching rooms:', error);
          roomSelect.innerHTML = '<option value="">Error loading rooms</option>';
        });
    }

    // Delete confirmation with SweetAlert2
    function handleDelete(event, bookingId) {
      event.preventDefault();
      Swal.fire({
        title: 'Are you sure?',
        text: `You are about to delete booking #${bookingId}. This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          event.target.submit();
        }
      });
      return false;
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

    // Disable form if booking exists
    document.addEventListener('DOMContentLoaded', function() {
      const hasBooking = <?php echo json_encode($user_has_booking); ?>;
      if (hasBooking) {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
          form.querySelectorAll('input, select, button').forEach(element => {
            if (element.type !== 'hidden') { // Allow hidden inputs to remain enabled
              element.disabled = true;
            }
          });
        });
      }
    });
  </script>

  <?php include 'includes/js-links.php'; ?>
</body>

</html>