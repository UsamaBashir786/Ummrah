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
          $_SESSION['success_message'] = "Hotel booking successfully created for booking #$booking_id with Room ID: $room_id";
          header("Location: hotel-assign.php?booking_id=" . urlencode($booking_id));
          exit();
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
  error_log("Delete booking request received");
  $hotel_booking_id = $_POST['hotel_booking_id'] ?? 0;
  $original_booking_id = $_POST['original_booking_id'] ?? 0;

  // Debug: Log all POST data
  error_log("POST data: " . print_r($_POST, true));

  // Validate input
  if (empty($hotel_booking_id) || !is_numeric($hotel_booking_id)) {
    $_SESSION['error_message'] = "Invalid booking ID provided.";
    error_log("Invalid booking ID: " . $hotel_booking_id);
    header("Location: hotel-assign.php" . ($original_booking_id ? "?booking_id=" . urlencode($original_booking_id) : ""));
    exit();
  }

  // Check if booking exists
  $check_sql = "SELECT id FROM hotel_bookings WHERE id = ?";
  $check_stmt = $conn->prepare($check_sql);

  if (!$check_stmt) {
    $_SESSION['error_message'] = "Database error: " . $conn->error;
    error_log("Prepare failed: " . $conn->error);
    header("Location: hotel-assign.php" . ($original_booking_id ? "?booking_id=" . urlencode($original_booking_id) : ""));
    exit();
  }

  $check_stmt->bind_param("i", $hotel_booking_id);
  $check_stmt->execute();
  $check_result = $check_stmt->get_result();

  if ($check_result->num_rows === 0) {
    $_SESSION['error_message'] = "No hotel booking found with ID #$hotel_booking_id";
    error_log("No booking found with ID: " . $hotel_booking_id);
    header("Location: hotel-assign.php" . ($original_booking_id ? "?booking_id=" . urlencode($original_booking_id) : ""));
    exit();
  }

  // Delete booking
  $sql = "DELETE FROM hotel_bookings WHERE id = ?";
  $stmt = $conn->prepare($sql);

  if (!$stmt) {
    $_SESSION['error_message'] = "Database error: " . $conn->error;
    error_log("Prepare failed: " . $conn->error);
    header("Location: hotel-assign.php" . ($original_booking_id ? "?booking_id=" . urlencode($original_booking_id) : ""));
    exit();
  }

  $stmt->bind_param("i", $hotel_booking_id);

  if ($stmt->execute()) {
    $_SESSION['success_message'] = "Hotel booking #$hotel_booking_id successfully deleted";
    error_log("Successfully deleted booking ID: " . $hotel_booking_id);
  } else {
    $_SESSION['error_message'] = "Error deleting booking: " . $conn->error;
    error_log("Delete failed: " . $conn->error);
  }

  $stmt->close();
  $check_stmt->close();

  header("Location: hotel-assign.php" . ($original_booking_id ? "?booking_id=" . urlencode($original_booking_id) : ""));
  exit();
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
              const successAlert = document.getElementById('success-alert');
              if (successAlert) successAlert.style.display = 'none';
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
              const errorAlert = document.getElementById('error-alert');
              if (errorAlert) errorAlert.style.display = 'none';
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
              const successAlertLocal = document.getElementById('success-alert-local');
              if (successAlertLocal) successAlertLocal.style.display = 'none';
            }, 5000);
          </script>
        <?php endif; ?>

        <?php if ($error_message): ?>
          <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" id="error-alert-local">
            <p><?php echo htmlspecialchars($error_message); ?></p>
          </div>
          <script>
            setTimeout(() => {
              const errorAlertLocal = document.getElementById('error-alert-local');
              if (errorAlertLocal) errorAlertLocal.style.display = 'none';
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

              