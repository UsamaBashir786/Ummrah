<?php
require_once 'connection/connection.php';

// Check if booking ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
  header("Location: manage-transportation.php");
  exit;
}

$booking_id = intval($_GET['id']);

// Fetch booking details
$sql = "SELECT tb.*, u.full_name, u.email, u.phone_number, u.profile_image, u.id as user_id
        FROM transportation_bookings tb
        LEFT JOIN users u ON tb.user_id = u.id
        WHERE tb.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  header("Location: manage-transportation.php");
  exit;
}

$booking = $result->fetch_assoc();
$stmt->close();

// Get route details
$route_id = $booking['route_id'];
$route_details = null;

if ($booking['service_type'] === 'taxi') {
  $route_sql = "SELECT * FROM taxi_routes WHERE id = ?";
} else {
  $route_sql = "SELECT * FROM rentacar_routes WHERE id = ?";
}

try {
  $stmt = $conn->prepare($route_sql);
  $stmt->bind_param("i", $route_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $route_details = $result->fetch_assoc();
  }
  $stmt->close();
} catch (Exception $e) {
  // Silently handle errors
}

// Get assignment details if any
$assignment = null;
$assignment_sql = "SELECT ta.*, u.full_name as admin_name 
                  FROM transportation_assign ta
                  LEFT JOIN admin u ON ta.admin_notes = u.id
                  WHERE ta.booking_id = ?";

try {
  $stmt = $conn->prepare($assignment_sql);
  $stmt->bind_param("i", $booking_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $assignment = $result->fetch_assoc();
  }
  $stmt->close();
} catch (Exception $e) {
  // Silently handle errors
}

// Format dates for display
$booking_date = new DateTime($booking['booking_date']);
$formatted_booking_date = $booking_date->format('F d, Y');

$booking_time = new DateTime($booking['booking_time']);
$formatted_booking_time = $booking_time->format('h:i A');

$created_date = new DateTime($booking['created_at']);
$formatted_created_date = $created_date->format('F d, Y h:i A');

// Process status update if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
  $new_status = $_POST['booking_status'];
  $payment_status = $_POST['payment_status'];
  $admin_notes = $_POST['admin_notes'];

  $update_sql = "UPDATE transportation_bookings 
                SET booking_status = ?, payment_status = ?, admin_notes = ?
                WHERE id = ?";

  $stmt = $conn->prepare($update_sql);
  $stmt->bind_param("sssi", $new_status, $payment_status, $admin_notes, $booking_id);

  if ($stmt->execute()) {
    // Refresh booking data
    header("Location: transportation-booking-details.php?id=$booking_id&updated=1");
    exit;
  } else {
    $error_message = "Failed to update booking status: " . $stmt->error;
  }
  $stmt->close();
}

// Process vehicle assignment if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_vehicle'])) {
  $vehicle_id = $_POST['vehicle_id'];
  $pickup_time = $_POST['pickup_time'] ? date('Y-m-d H:i:s', strtotime($_POST['pickup_time'])) : null;
  $admin_notes = $_POST['assign_notes'];

  // Check if assignment already exists
  if ($assignment) {
    $assign_sql = "UPDATE transportation_assign 
                  SET vehicle_id = ?, pickup_time = ?, admin_notes = ?
                  WHERE id = ?";
    $stmt = $conn->prepare($assign_sql);
    $stmt->bind_param("sssi", $vehicle_id, $pickup_time, $admin_notes, $assignment['id']);
  } else {
    $assign_sql = "INSERT INTO transportation_assign 
                  (booking_id, booking_reference, user_id, service_type, route_id, vehicle_id, pickup_time, admin_notes) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($assign_sql);
    $stmt->bind_param(
      "isisssss",
      $booking_id,
      $booking['booking_reference'],
      $booking['user_id'],
      $booking['service_type'],
      $booking['route_id'],
      $vehicle_id,
      $pickup_time,
      $admin_notes
    );
  }

  if ($stmt->execute()) {
    // Update booking status to confirmed
    $update_sql = "UPDATE transportation_bookings SET booking_status = 'confirmed' WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();

    // Refresh page
    header("Location: transportation-booking-details.php?id=$booking_id&assigned=1");
    exit;
  } else {
    $error_message = "Failed to assign vehicle: " . $stmt->error;
  }
  $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <style>
    .booking-header {
      background-image: linear-gradient(to right, #0891b2, #0e7490);
    }

    .booking-status {
      font-size: 0.8rem;
      padding: 0.35rem 0.85rem;
      border-radius: 9999px;
      font-weight: 600;
    }

    .status-confirmed,
    .status-completed {
      background-color: #ecfdf5;
      color: #059669;
    }

    .status-pending {
      background-color: #fffbeb;
      color: #d97706;
    }

    .status-cancelled {
      background-color: #fef2f2;
      color: #dc2626;
    }

    .timeline-item {
      position: relative;
      padding-left: 1.5rem;
      padding-bottom: 1.5rem;
      border-left: 2px solid #e5e7eb;
    }

    .timeline-item:last-child {
      border-left-color: transparent;
    }

    .timeline-dot {
      position: absolute;
      left: -8px;
      top: 0;
      width: 16px;
      height: 16px;
      border-radius: 50%;
      background-color: #0891b2;
    }

    @media print {
      .no-print {
        display: none !important;
      }

      .print-only {
        display: block !important;
      }

      body {
        font-size: 12pt;
        color: #000;
      }
    }
  </style>
</head>

<body class="bg-gray-100">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main flex-1 flex flex-col overflow-hidden">
      <!-- Navbar -->
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center no-print">
        <div class="flex items-center">
          <button class="md:hidden text-gray-800 mr-4" id="menu-btn">
            <i class="fas fa-bars"></i>
          </button>
          <h1 class="text-xl font-semibold">
            <i class="text-teal-600 fas fa-car mr-2"></i> Transportation Booking Details
          </h1>
        </div>
        <div class="flex space-x-3">
          <button onclick="window.print()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-print mr-2"></i> Print
          </button>
          <a href="user-details.php?id=<?php echo $booking['user_id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-user mr-2"></i> View User
          </a>
          <button onclick="window.history.back()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back
          </button>
        </div>
      </div>

      <!-- Show success message if booking was updated -->
      <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 m-4 rounded shadow-md no-print" id="success-alert">
          <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <p>Booking status updated successfully!</p>
            <button class="ml-auto" onclick="document.getElementById('success-alert').remove()">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
      <?php endif; ?>

      <!-- Show success message if vehicle was assigned -->
      <?php if (isset($_GET['assigned']) && $_GET['assigned'] == 1): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 m-4 rounded shadow-md no-print" id="assigned-alert">
          <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <p>Vehicle assigned successfully!</p>
            <button class="ml-auto" onclick="document.getElementById('assigned-alert').remove()">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
      <?php endif; ?>

      <!-- Show error message if there was an issue -->
      <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 m-4 rounded shadow-md no-print" id="error-alert">
          <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <p><?php echo $error_message; ?></p>
            <button class="ml-auto" onclick="document.getElementById('error-alert').remove()">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
      <?php endif; ?>

      <div class="flex-1 overflow-auto p-4 md:p-6">
        <!-- Booking Header -->
        <div class="booking-header bg-gradient-to-r from-cyan-600 to-teal-700 rounded-xl shadow-lg p-6 mb-6 text-white">
          <div class="flex flex-col md:flex-row items-center md:items-start justify-between">
            <div class="flex flex-col md:flex-row items-center md:items-start mb-4 md:mb-0">
              <div class="flex-shrink-0 mb-4 md:mb-0 md:mr-6">
                <?php if ($booking['service_type'] === 'taxi'): ?>
                  <div class="h-20 w-20 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-taxi text-4xl"></i>
                  </div>
                <?php else: ?>
                  <div class="h-20 w-20 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-shuttle-van text-4xl"></i>
                  </div>
                <?php endif; ?>
              </div>
              <div class="text-center md:text-left">
                <div class="flex items-center">
                  <h1 class="text-2xl font-bold">
                    <?php echo ucfirst($booking['service_type']); ?> Booking
                  </h1>
                  <?php
                  $status = strtolower($booking['booking_status'] ?? 'pending');
                  $status_class = 'bg-yellow-500';
                  switch ($status) {
                    case 'confirmed':
                      $status_class = 'bg-green-500';
                      break;
                    case 'completed':
                      $status_class = 'bg-blue-500';
                      break;
                    case 'cancelled':
                      $status_class = 'bg-red-500';
                      break;
                  }
                  ?>
                  <span class="ml-3 px-3 py-1 text-xs font-semibold rounded-full <?php echo $status_class; ?>">
                    <?php echo ucfirst($status); ?>
                  </span>
                </div>
                <p class="text-lg mt-1">
                  <?php echo $booking['vehicle_name']; ?> - <?php echo $booking['vehicle_type']; ?>
                </p>
                <div class="flex flex-wrap items-center mt-2 space-x-4">
                  <span class="flex items-center">
                    <i class="fas fa-calendar-day mr-1"></i> <?php echo $formatted_booking_date; ?>
                  </span>
                  <span class="flex items-center">
                    <i class="fas fa-clock mr-1"></i> <?php echo $formatted_booking_time; ?>
                  </span>
                </div>
              </div>
            </div>
            <div class="text-center md:text-right">
              <div class="text-sm text-white/80">Booking Reference</div>
              <div class="text-xl font-mono font-semibold tracking-wider">
                <?php echo $booking['booking_reference']; ?>
              </div>
              <div class="mt-2 text-sm text-white/80">Route #<?php echo $booking['route_id']; ?></div>
              <div class="mt-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $booking['payment_status'] === 'paid' ? 'bg-green-500' : 'bg-amber-500'; ?> text-white">
                  <i class="fas <?php echo $booking['payment_status'] === 'paid' ? 'fa-check-circle' : 'fa-hourglass-half'; ?> mr-1"></i>
                  Payment: <?php echo ucfirst($booking['payment_status']); ?>
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
          <!-- Column 1: Booking & Customer Info -->
          <div class="lg:col-span-2">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Booking Info Card -->
              <div class="bg-white rounded-xl shadow-md p-5">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                  <i class="fas fa-info-circle text-teal-600 mr-2"></i> Booking Information
                </h2>
                <div class="space-y-3">
                  <div class="flex justify-between">
                    <span class="text-gray-500">Booking Type:</span>
                    <span class="text-gray-800 font-medium">
                      <?php echo ucfirst($booking['service_type']); ?>
                    </span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-500">Booking ID:</span>
                    <span class="text-gray-800 font-medium">#<?php echo $booking['id']; ?></span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-500">Reference:</span>
                    <span class="text-gray-800 font-medium">
                      <?php echo $booking['booking_reference']; ?>
                    </span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-500">Date:</span>
                    <span class="text-gray-800 font-medium"><?php echo $formatted_booking_date; ?></span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-500">Time:</span>
                    <span class="text-gray-800 font-medium"><?php echo $formatted_booking_time; ?></span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-500">Vehicle:</span>
                    <span class="text-gray-800 font-medium">
                      <?php echo $booking['vehicle_name']; ?> (<?php echo $booking['vehicle_type']; ?>)
                    </span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-500">Passengers:</span>
                    <span class="text-gray-800 font-medium"><?php echo $booking['passengers']; ?></span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-500">Price:</span>
                    <span class="text-gray-800 font-medium">$<?php echo number_format($booking['price'], 2); ?></span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-500">Created:</span>
                    <span class="text-gray-800 font-medium"><?php echo $formatted_created_date; ?></span>
                  </div>
                </div>
              </div>

              <!-- Customer Info Card -->
              <div class="bg-white rounded-xl shadow-md p-5">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                  <i class="fas fa-user text-teal-600 mr-2"></i> Customer Information
                </h2>

                <div class="flex items-center mb-4">
                  <img class="h-12 w-12 rounded-full object-cover mr-4"
                    src="../<?php echo isset($booking['profile_image']) ? htmlspecialchars($booking['profile_image']) : 'user/uploads/default.png'; ?>"
                    alt="Customer profile" />
                  <div>
                    <h3 class="font-semibold text-gray-800">
                      <?php echo htmlspecialchars($booking['full_name']); ?>
                    </h3>
                    <a href="user-details.php?id=<?php echo $booking['user_id']; ?>"
                      class="text-teal-600 text-sm hover:underline">
                      View Profile
                    </a>
                  </div>
                </div>

                <div class="space-y-3">
                  <div class="flex justify-between">
                    <span class="text-gray-500">Email:</span>
                    <span class="text-gray-800 font-medium">
                      <?php echo htmlspecialchars($booking['email']); ?>
                    </span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-500">Phone:</span>
                    <span class="text-gray-800 font-medium">
                      <?php echo htmlspecialchars($booking['phone_number']); ?>
                    </span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-500">User ID:</span>
                    <span class="text-gray-800 font-medium">#<?php echo $booking['user_id']; ?></span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Trip Details Card -->
            <div class="bg-white rounded-xl shadow-md p-5 mt-6">
              <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-map-marked-alt text-teal-600 mr-2"></i> Trip Details
              </h2>

              <div class="space-y-4">
                <div>
                  <p class="text-sm text-gray-500 mb-1">Route Information</p>
                  <div class="bg-gray-50 p-3 rounded-lg">
                    <?php if ($route_details): ?>
                      <p class="font-medium text-gray-800">
                        <?php echo $route_details['route_name']; ?>
                      </p>
                      <p class="text-sm text-gray-600 mt-1">
                        Route #<?php echo $route_details['route_number']; ?> -
                        <?php echo $route_details['service_title']; ?> (<?php echo $route_details['year']; ?>)
                      </p>
                    <?php else: ?>
                      <p class="text-gray-600">Route details not available</p>
                    <?php endif; ?>
                  </div>
                </div>

                <div>
                  <p class="text-sm text-gray-500 mb-1">Pickup Location</p>
                  <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="font-medium text-gray-800">
                      <?php echo !empty($booking['pickup_location']) ? htmlspecialchars($booking['pickup_location']) : 'No pickup location specified'; ?>
                    </p>
                  </div>
                </div>

                <div>
                  <p class="text-sm text-gray-500 mb-1">Special Requests</p>
                  <div class="bg-gray-50 p-3 rounded-lg">
                    <?php if (!empty($booking['special_requests'])): ?>
                      <p class="text-gray-800">
                        <?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?>
                      </p>
                    <?php else: ?>
                      <p class="text-gray-600">No special requests</p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>

            <!-- Vehicle Assignment Card -->
            <?php if ($assignment): ?>
              <div class="bg-white rounded-xl shadow-md p-5 mt-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                  <i class="fas fa-clipboard-check text-teal-600 mr-2"></i> Vehicle Assignment
                </h2>

                <div class="bg-teal-50 border border-teal-200 rounded-lg p-4 mb-4">
                  <div class="flex items-center">
                    <div class="bg-teal-100 rounded-full p-2 mr-3">
                      <i class="fas fa-car text-teal-600"></i>
                    </div>
                    <div>
                      <h3 class="font-semibold text-teal-800">Vehicle Assigned</h3>
                      <p class="text-sm text-teal-600">
                        ID: <?php echo $assignment['vehicle_id']; ?>
                      </p>
                    </div>
                    <div class="ml-auto">
                      <button onclick="showEditVehicleModal()" class="text-teal-600 hover:text-teal-800 no-print">
                        <i class="fas fa-edit"></i> Edit
                      </button>
                    </div>
                  </div>
                </div>

                <div class="space-y-3">
                  <div class="flex justify-between">
                    <span class="text-gray-500">Pickup Time:</span>
                    <span class="text-gray-800 font-medium">
                      <?php
                      if ($assignment['pickup_time']) {
                        $pickup = new DateTime($assignment['pickup_time']);
                        echo $pickup->format('F d, Y h:i A');
                      } else {
                        echo 'Not specified';
                      }
                      ?>
                    </span>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500 mb-1">Admin Notes</p>
                    <div class="bg-gray-50 p-3 rounded-lg">
                      <?php if (!empty($assignment['admin_notes'])): ?>
                        <p class="text-gray-800">
                          <?php echo nl2br(htmlspecialchars($assignment['admin_notes'])); ?>
                        </p>
                      <?php else: ?>
                        <p class="text-gray-600">No admin notes</p>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <!-- Column 2: Status & Actions -->
          <div class="lg:col-span-1">
            <!-- Status Card -->
            <div class="bg-white rounded-xl shadow-md p-5 mb-6">
              <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-tasks text-teal-600 mr-2"></i> Booking Status
              </h2>

              <div class="bg-gray-50 p-4 rounded-lg mb-4">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm text-gray-500">Current Status</p>
                    <p class="inline-flex mt-1 items-center px-3 py-1 rounded-full text-sm font-semibold 
                        <?php
                        switch ($booking['booking_status']) {
                          case 'confirmed':
                            echo 'bg-green-100 text-green-800';
                            break;
                          case 'completed':
                            echo 'bg-blue-100 text-blue-800';
                            break;
                          case 'cancelled':
                            echo 'bg-red-100 text-red-800';
                            break;
                          default:
                            echo 'bg-amber-100 text-amber-800';
                        }
                        ?>">
                      <i class="fas 
                        <?php
                        switch ($booking['booking_status']) {
                          case 'confirmed':
                            echo 'fa-check-circle';
                            break;
                          case 'completed':
                            echo 'fa-flag-checkered';
                            break;
                          case 'cancelled':
                            echo 'fa-times-circle';
                            break;
                          default:
                            echo 'fa-hourglass-half';
                        }
                        ?> mr-1"></i>
                      <?php echo ucfirst($booking['booking_status']); ?>
                    </p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Payment</p>
                    <p class="inline-flex mt-1 items-center px-3 py-1 rounded-full text-sm font-semibold 
                        <?php echo $booking['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'; ?>">
                      <i class="fas <?php echo $booking['payment_status'] === 'paid' ? 'fa-check-circle' : 'fa-hourglass-half'; ?> mr-1"></i>
                      <?php echo ucfirst($booking['payment_status']); ?>
                    </p>
                  </div>
                </div>
              </div>

              <!-- Status Update Form -->
              <form method="POST" action="" class="mt-4 no-print">
                <div class="space-y-4">
                  <div>
                    <label for="booking_status" class="block text-sm font-medium text-gray-700 mb-1">Update Status</label>
                    <select name="booking_status" id="booking_status" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
                      <option value="pending" <?php echo $booking['booking_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                      <option value="confirmed" <?php echo $booking['booking_status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                      <option value="completed" <?php echo $booking['booking_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                      <option value="cancelled" <?php echo $booking['booking_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                  </div>

                  <div>
                    <label for="payment_status" class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
                    <select name="payment_status" id="payment_status" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
                      <option value="unpaid" <?php echo $booking['payment_status'] === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                      <option value="paid" <?php echo $booking['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                      <option value="refunded" <?php echo $booking['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                    </select>
                  </div>

                  <div>
                    <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-1">Admin Notes</label>
                    <textarea name="admin_notes" id="admin_notes" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500"><?php echo htmlspecialchars($booking['admin_notes'] ?? ''); ?></textarea>
                  </div>

                  <div>
                    <button type="submit" name="update_status" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-medium py-2 px-4 rounded-md">
                      Update Booking Status
                    </button>
                  </div>
                </div>
              </form>
            </div>

            <!-- Assignment Card -->
            <!-- <?php if (!$assignment): ?>
              <div class="bg-white rounded-xl shadow-md p-5 mb-6 no-print">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                  <i class="fas fa-car text-teal-600 mr-2"></i> Vehicle Assignment
                </h2>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                  <div class="flex items-center">
                    <div class="bg-blue-100 rounded-full p-2 mr-3">
                      <i class="fas fa-info-circle text-blue-600"></i>
                    </div>
                    <div>
                      <h3 class="font-semibold text-blue-800">No Vehicle Assigned</h3>
                      <p class="text-sm text-blue-600">
                        Assign a vehicle to confirm this booking
                      </p>
                    </div>
                  </div>
                </div>

                <form method="POST" action="" class="mt-4">
                  <div class="space-y-4">
                    <div>
                      <label for="vehicle_id" class="block text-sm font-medium text-gray-700 mb-1">Vehicle ID</label>
                      <input type="text" name="vehicle_id" id="vehicle_id" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <div>
                      <label for="pickup_time" class="block text-sm font-medium text-gray-700 mb-1">Pickup Time</label>
                      <input type="datetime-local" name="pickup_time" id="pickup_time" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <div>
                      <label for="assign_notes" class="block text-sm font-medium text-gray-700 mb-1">Assignment Notes</label>
                      <textarea name="assign_notes" id="assign_notes" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500"></textarea>
                    </div>

                    <div>
                      <button type="submit" name="assign_vehicle" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">
                        Assign Vehicle
                      </button>
                    </div>
                  </div>
                </form>
              </div>
            <?php endif; ?> -->

            <!-- Action Buttons -->
            <div class="bg-white rounded-xl shadow-md p-5 mb-6 no-print">
              <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-cog text-teal-600 mr-2"></i> Actions
              </h2>

              <div class="space-y-3">
                <a href="mailto:<?php echo htmlspecialchars($booking['email']); ?>" class="block w-full bg-teal-100 hover:bg-teal-200 text-teal-800 text-center font-medium py-2 px-4 rounded-md">
                  <i class="fas fa-envelope mr-2"></i> Email Customer
                </a>

                <button onclick="window.print()" class="block w-full bg-gray-100 hover:bg-gray-200 text-gray-800 text-center font-medium py-2 px-4 rounded-md">
                  <i class="fas fa-print mr-2"></i> Print Details
                </button>

                <a href="user-bookings.php?user_id=<?php echo $booking['user_id']; ?>" class="block w-full bg-blue-100 hover:bg-blue-200 text-blue-800 text-center font-medium py-2 px-4 rounded-md">
                  <i class="fas fa-user-clock mr-2"></i> View User's Bookings
                </a>

                <?php if ($booking['booking_status'] !== 'cancelled'): ?>
                  <button onclick="confirmCancelBooking()" class="block w-full bg-red-100 hover:bg-red-200 text-red-800 text-center font-medium py-2 px-4 rounded-md">
                    <i class="fas fa-ban mr-2"></i> Cancel Booking
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Vehicle Assignment Modal -->
  <?php if ($assignment): ?>
    <div id="editVehicleModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
      <div class="bg-white rounded-lg w-full max-w-md mx-4">
        <div class="p-5 border-b border-gray-200">
          <div class="flex justify-between items-center">
            <h3 class="text-xl font-semibold text-gray-800">Edit Vehicle Assignment</h3>
            <button onclick="closeEditVehicleModal()" class="text-gray-400 hover:text-gray-500">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>

        <form method="POST" action="">
          <div class="p-5 space-y-4">
            <div>
              <label for="edit_vehicle_id" class="block text-sm font-medium text-gray-700 mb-1">Vehicle ID</label>
              <input type="text" name="vehicle_id" id="edit_vehicle_id" value="<?php echo htmlspecialchars($assignment['vehicle_id']); ?>" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
            </div>

            <div>
              <label for="edit_pickup_time" class="block text-sm font-medium text-gray-700 mb-1">Pickup Time</label>
              <input type="datetime-local" name="pickup_time" id="edit_pickup_time"
                value="<?php echo $assignment['pickup_time'] ? date('Y-m-d\TH:i', strtotime($assignment['pickup_time'])) : ''; ?>"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
            </div>

            <div>
              <label for="edit_assign_notes" class="block text-sm font-medium text-gray-700 mb-1">Assignment Notes</label>
              <textarea name="assign_notes" id="edit_assign_notes" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500"><?php echo htmlspecialchars($assignment['admin_notes'] ?? ''); ?></textarea>
            </div>
          </div>

          <div class="p-5 border-t border-gray-200 flex justify-end space-x-3">
            <button type="button" onclick="closeEditVehicleModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-md">
              Cancel
            </button>
            <button type="submit" name="assign_vehicle" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md">
              Update Assignment
            </button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <?php include 'includes/js-links.php'; ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Mobile menu toggle
      const menuBtn = document.getElementById('menu-btn');
      const sidebar = document.querySelector('.sidebar');

      if (menuBtn && sidebar) {
        menuBtn.addEventListener('click', function() {
          sidebar.classList.toggle('hidden');
        });
      }

      // Auto-hide alerts after 5 seconds
      setTimeout(() => {
        const alerts = document.querySelectorAll('#success-alert, #assigned-alert, #error-alert');
        alerts.forEach(alert => {
          if (alert) {
            alert.classList.add('opacity-0', 'transition-opacity', 'duration-500');
            setTimeout(() => {
              alert.remove();
            }, 500);
          }
        });
      }, 5000);
    });

    // Vehicle assignment modal functions
    function showEditVehicleModal() {
      document.getElementById('editVehicleModal').classList.remove('hidden');
    }

    function closeEditVehicleModal() {
      document.getElementById('editVehicleModal').classList.add('hidden');
    }

    // Confirm booking cancellation
    function confirmCancelBooking() {
      Swal.fire({
        title: 'Cancel Booking?',
        text: "Are you sure you want to cancel this booking? This action can't be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, cancel it'
      }).then((result) => {
        if (result.isConfirmed) {
          // Create a form and submit it
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = '';

          const bookingStatusInput = document.createElement('input');
          bookingStatusInput.type = 'hidden';
          bookingStatusInput.name = 'booking_status';
          bookingStatusInput.value = 'cancelled';

          const paymentStatusInput = document.createElement('input');
          paymentStatusInput.type = 'hidden';
          paymentStatusInput.name = 'payment_status';
          paymentStatusInput.value = document.getElementById('payment_status').value;

          const adminNotesInput = document.createElement('input');
          adminNotesInput.type = 'hidden';
          adminNotesInput.name = 'admin_notes';
          adminNotesInput.value = document.getElementById('admin_notes').value + '\n[Auto: Booking cancelled on ' + new Date().toLocaleString() + ']';

          const submitInput = document.createElement('input');
          submitInput.type = 'hidden';
          submitInput.name = 'update_status';
          submitInput.value = '1';

          form.appendChild(bookingStatusInput);
          form.appendChild(paymentStatusInput);
          form.appendChild(adminNotesInput);
          form.appendChild(submitInput);

          document.body.appendChild(form);
          form.submit();
        }
      });
    }
  </script>
</body>

</html>