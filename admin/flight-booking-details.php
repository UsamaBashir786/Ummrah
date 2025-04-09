<?php
require_once 'connection/connection.php';

// Check if booking ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
  header("Location: manage-bookings.php");
  exit;
}

$booking_id = intval($_GET['id']);

// Fetch flight booking details with joined flight information
$sql = "SELECT fb.*, f.*, 
        u.full_name as passenger_name, u.email as passenger_email, u.phone_number as passenger_phone,
        u.id as user_id
        FROM flight_bookings fb 
        JOIN flights f ON fb.flight_id = f.id
        JOIN users u ON fb.user_id = u.id
        WHERE fb.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  header("Location: manage-bookings.php");
  exit;
}

$booking = $result->fetch_assoc();
$stmt->close();

// Get available seats
function getAvailableSeats($flightId, $conn)
{
  $sql = "SELECT seats FROM flights WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $flightId);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $stmt->close();

  if ($row && $row['seats']) {
    $seats = json_decode($row['seats'], true);
    $total = 0;
    $booked = 0;

    // Count total and booked seats from flight_bookings
    foreach ($seats as $class => $data) {
      $total += $data['count'];
      $sql = "SELECT COUNT(*) as booked FROM flight_bookings WHERE flight_id = ? AND cabin_class = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("is", $flightId, $class);
      $stmt->execute();
      $result = $stmt->get_result();
      $booked += $result->fetch_assoc()['booked'];
      $stmt->close();
    }

    return ['available' => $total - $booked, 'total' => $total];
  }
  return ['available' => 0, 'total' => 0];
}

// Format dates
$booking_date = new DateTime($booking['booking_date']);
$formatted_booking_date = $booking_date->format('F d, Y h:i A');

// Format departure date
$departure_date = null;
$formatted_departure_date = 'N/A';
if (isset($booking['departure_date']) && !empty($booking['departure_date'])) {
  $departure_date = new DateTime($booking['departure_date']);
  $formatted_departure_date = $departure_date->format('F d, Y h:i A');
}

// Format arrival date
$arrival_date = null;
$formatted_arrival_date = 'N/A';
if (isset($booking['arrival_date']) && !empty($booking['arrival_date'])) {
  $arrival_date = new DateTime($booking['arrival_date']);
  $formatted_arrival_date = $arrival_date->format('F d, Y h:i A');
}

// Get flight duration
$duration = 'N/A';
if ($departure_date instanceof DateTime && $arrival_date instanceof DateTime) {
  $interval = $departure_date->diff($arrival_date);
  $duration = '';

  if ($interval->days > 0) {
    $duration .= $interval->days . 'd ';
  }
  $duration .= $interval->h . 'h ' . $interval->i . 'm';
}

// Get seat information
$seatInfo = getAvailableSeats($booking['flight_id'], $conn);

// Get price from the prices JSON field
$prices = json_decode($booking['prices'], true);
$price = $prices[$booking['cabin_class']] ?? $booking['ticket_price'] ?? 0;

// Define status class for styling
$status = strtolower($booking['booking_status'] ?? 'pending');
$status_class = 'status-pending';

switch ($status) {
  case 'confirmed':
  case 'completed':
    $status_class = 'status-confirmed';
    break;
  case 'cancelled':
  case 'canceled':
    $status_class = 'status-cancelled';
    break;
}

// Define payment status class
$payment_status = strtolower($booking['payment_status'] ?? 'unpaid');
$payment_class = 'status-pending';

switch ($payment_status) {
  case 'paid':
  case 'completed':
    $payment_class = 'status-confirmed';
    break;
  case 'refunded':
    $payment_class = 'status-cancelled';
    break;
}

// Get passenger details
$passenger_info = [];
if (isset($booking['passenger_details']) && !empty($booking['passenger_details'])) {
  $passenger_info = json_decode($booking['passenger_details'], true);
}

// Update booking status
if (isset($_POST['update_status'])) {
  $new_status = $_POST['status'];

  $update_sql = "UPDATE flight_bookings SET booking_status = ? WHERE id = ?";
  $update_stmt = $conn->prepare($update_sql);
  $update_stmt->bind_param("si", $new_status, $booking_id);

  if ($update_stmt->execute()) {
    $booking['booking_status'] = $new_status;
    $status = strtolower($new_status);

    // Update status class
    switch ($status) {
      case 'confirmed':
      case 'completed':
        $status_class = 'status-confirmed';
        break;
      case 'cancelled':
      case 'canceled':
        $status_class = 'status-cancelled';
        break;
      default:
        $status_class = 'status-pending';
    }

    $success_message = "Booking status updated successfully.";
  } else {
    $error_message = "Failed to update booking status.";
  }

  $update_stmt->close();
}

// Update payment status
if (isset($_POST['update_payment'])) {
  $new_payment_status = $_POST['payment_status'];

  $update_sql = "UPDATE flight_bookings SET payment_status = ? WHERE id = ?";
  $update_stmt = $conn->prepare($update_sql);
  $update_stmt->bind_param("si", $new_payment_status, $booking_id);

  if ($update_stmt->execute()) {
    $booking['payment_status'] = $new_payment_status;
    $payment_status = strtolower($new_payment_status);

    // Update payment status class
    switch ($payment_status) {
      case 'paid':
      case 'completed':
        $payment_class = 'status-confirmed';
        break;
      case 'refunded':
        $payment_class = 'status-cancelled';
        break;
      default:
        $payment_class = 'status-pending';
    }

    $payment_success_message = "Payment status updated successfully.";
  } else {
    $payment_error_message = "Failed to update payment status.";
  }

  $update_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <style>
    .profile-header {
      background-image: linear-gradient(to right, #0891b2, #0e7490);
    }

    .info-card {
      transition: all 0.3s ease;
    }

    .info-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .booking-status {
      font-size: 0.75rem;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-weight: 600;
    }

    .status-confirmed,
    .status-completed,
    .status-paid {
      background-color: #ecfdf5;
      color: #059669;
    }

    .status-pending,
    .status-assigned,
    .status-upcoming,
    .status-unpaid {
      background-color: #fffbeb;
      color: #d97706;
    }

    .status-cancelled,
    .status-refunded {
      background-color: #fef2f2;
      color: #dc2626;
    }

    .flight-path {
      position: relative;
      padding: 2rem 0;
    }

    .flight-path::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 5%;
      right: 5%;
      height: 2px;
      background: #e5e7eb;
      z-index: 1;
    }

    .flight-path::after {
      content: '✈';
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 1.5rem;
      color: #0891b2;
      background: white;
      padding: 0.5rem;
      border-radius: 50%;
      z-index: 2;
    }

    .ticket-segment {
      position: relative;
      z-index: 3;
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
            <i class="text-teal-600 fa fa-plane mr-2"></i> Flight Booking Details
          </h1>
        </div>
        <div class="flex space-x-3">
          <button onclick="window.print()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-print mr-2"></i> Print
          </button>
          <button onclick="window.history.back()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back
          </button>
        </div>
      </div>

      <div class="flex-1 overflow-auto p-4 md:p-6">
        <?php if (isset($success_message)): ?>
          <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">
            <p><i class="fas fa-check-circle mr-2"></i> <?php echo $success_message; ?></p>
          </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
          <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
            <p><i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_message; ?></p>
          </div>
        <?php endif; ?>

        <?php if (isset($payment_success_message)): ?>
          <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">
            <p><i class="fas fa-check-circle mr-2"></i> <?php echo $payment_success_message; ?></p>
          </div>
        <?php endif; ?>

        <?php if (isset($payment_error_message)): ?>
          <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
            <p><i class="fas fa-exclamation-circle mr-2"></i> <?php echo $payment_error_message; ?></p>
          </div>
        <?php endif; ?>

        <!-- Booking Header -->
        <div class="profile-header bg-gradient-to-r from-cyan-600 to-teal-700 rounded-xl shadow-lg p-6 mb-6 text-white">
          <div class="flex flex-col md:flex-row items-center md:items-start justify-between">
            <div>
              <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($booking['airline_name']); ?> - Flight #<?php echo htmlspecialchars($booking['flight_number']); ?></h1>
              <div class="mt-2 flex flex-col md:flex-row md:items-center space-y-2 md:space-y-0 md:space-x-4">
                <div class="flex items-center">
                  <i class="fas fa-calendar-check mr-2"></i>
                  <span>Booking Date: <?php echo $formatted_booking_date; ?></span>
                </div>
                <div class="flex items-center">
                  <i class="fas fa-ticket-alt mr-2"></i>
                  <span>Booking ID: #<?php echo $booking['id']; ?></span>
                </div>
              </div>
              <div class="mt-3 flex flex-wrap gap-2">
                <span class="booking-status <?php echo $status_class; ?> bg-white/20">
                  <i class="fas fa-clipboard-check mr-1"></i> <?php echo ucfirst($booking['booking_status']); ?>
                </span>
                <span class="booking-status <?php echo $payment_class; ?> bg-white/20">
                  <i class="fas fa-credit-card mr-1"></i> <?php echo ucfirst($booking['payment_status'] ?? 'Unpaid'); ?>
                </span>
                <span class="booking-status bg-white/20">
                  <i class="fas fa-users mr-1"></i> <?php echo isset($booking['passenger_count']) ? $booking['passenger_count'] : '1'; ?> Passenger(s)
                </span>
              </div>
            </div>
            <div class="mt-4 md:mt-0">
              <a href="user-details.php?id=<?php echo $booking['user_id']; ?>" class="bg-white/10 hover:bg-white/20 rounded-lg px-4 py-2 inline-flex items-center text-white">
                <i class="fas fa-user mr-2"></i> View Passenger
              </a>
            </div>
          </div>
        </div>

        <!-- Flight Information -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
          <!-- Flight Details -->
          <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
              <div class="bg-gray-800 text-white p-4 flex justify-between items-center">
                <h2 class="text-xl font-semibold flex items-center">
                  <i class="fas fa-plane mr-2"></i> Flight Details
                </h2>
                <span class="text-sm font-medium px-3 py-1 bg-blue-500 rounded-full">
                  <?php echo htmlspecialchars($booking['cabin_class']); ?> Class
                </span>
              </div>

              <div class="p-5">
                <!-- Flight Path Visualization -->
                <div class="flight-path mb-6">
                  <div class="grid grid-cols-2">
                    <div class="ticket-segment text-center">
                      <div class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($booking['departure_city'] ?? 'N/A'); ?></div>
                      <div class="text-sm text-gray-500"><?php echo isset($booking['departure_airport']) ? htmlspecialchars($booking['departure_airport']) : 'N/A'; ?></div>
                      <div class="text-base font-medium text-teal-600 mt-1">
                        <?php echo ($departure_date instanceof DateTime) ? $departure_date->format('h:i A') : 'N/A'; ?>
                      </div>
                      <div class="text-xs text-gray-500">
                        <?php echo ($departure_date instanceof DateTime) ? $departure_date->format('d M Y') : 'N/A'; ?>
                      </div>
                    </div>
                    <div class="ticket-segment text-center">
                      <div class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($booking['arrival_city'] ?? 'N/A'); ?></div>
                      <div class="text-sm text-gray-500"><?php echo isset($booking['arrival_airport']) ? htmlspecialchars($booking['arrival_airport']) : 'N/A'; ?></div>
                      <div class="text-base font-medium text-teal-600 mt-1">
                        <?php echo ($arrival_date instanceof DateTime) ? $arrival_date->format('h:i A') : 'N/A'; ?>
                      </div>
                      <div class="text-xs text-gray-500">
                        <?php echo ($arrival_date instanceof DateTime) ? $arrival_date->format('d M Y') : 'N/A'; ?>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="border-t border-gray-100 pt-4">
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 p-3 rounded-lg">
                      <p class="text-sm text-gray-500">Flight Duration</p>
                      <p class="text-base font-medium text-gray-800">
                        <i class="far fa-clock text-teal-600 mr-1"></i> <?php echo $duration; ?>
                      </p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg">
                      <p class="text-sm text-gray-500">Aircraft</p>
                      <p class="text-base font-medium text-gray-800">
                        <i class="fas fa-plane-departure text-teal-600 mr-1"></i> <?php echo htmlspecialchars($booking['aircraft_type'] ?? 'N/A'); ?>
                      </p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg">
                      <p class="text-sm text-gray-500">Available Seats</p>
                      <p class="text-base font-medium text-gray-800">
                        <i class="fas fa-chair text-teal-600 mr-1"></i> <?php echo $seatInfo['available'] . '/' . $seatInfo['total']; ?>
                      </p>
                    </div>
                  </div>
                </div>

                <div class="mt-6 border-t border-gray-100 pt-4">
                  <h3 class="text-lg font-semibold text-gray-800 mb-3">Flight Information</h3>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <p class="text-sm text-gray-500">Airline</p>
                      <p class="text-base font-medium text-gray-800"><?php echo htmlspecialchars($booking['airline_name']); ?></p>
                    </div>
                    <div>
                      <p class="text-sm text-gray-500">Flight Number</p>
                      <p class="text-base font-medium text-gray-800"><?php echo htmlspecialchars($booking['flight_number']); ?></p>
                    </div>
                    <div>
                      <p class="text-sm text-gray-500">Departure Terminal</p>
                      <p class="text-base font-medium text-gray-800"><?php echo htmlspecialchars($booking['departure_terminal'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                      <p class="text-sm text-gray-500">Arrival Terminal</p>
                      <p class="text-base font-medium text-gray-800"><?php echo htmlspecialchars($booking['arrival_terminal'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                      <p class="text-sm text-gray-500">Flight Policy</p>
                      <p class="text-base font-medium text-gray-800"><?php echo !empty($booking['flight_policy']) ? htmlspecialchars($booking['flight_policy']) : 'Standard Policy'; ?></p>
                    </div>
                    <div>
                      <p class="text-sm text-gray-500">Booking Reference</p>
                      <p class="text-base font-medium text-gray-800"><?php echo htmlspecialchars($booking['booking_reference'] ?? $booking['id']); ?></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Booking Summary -->
          <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
              <div class="bg-gray-800 text-white p-4">
                <h2 class="text-xl font-semibold flex items-center">
                  <i class="fas fa-receipt mr-2"></i> Booking Summary
                </h2>
              </div>

              <div class="p-5">
                <div class="mb-4 pb-4 border-b border-gray-100">
                  <h3 class="text-lg font-semibold text-gray-800 mb-3">Pricing Details</h3>
                  <div class="space-y-2">
                    <div class="flex justify-between">
                      <span class="text-gray-600">Base Fare</span>
                      <span class="font-medium text-gray-800">$<?php echo number_format((float)$price, 2); ?></span>
                    </div>
                    <?php if (isset($booking['taxes']) && $booking['taxes'] > 0): ?>
                      <div class="flex justify-between">
                        <span class="text-gray-600">Taxes & Fees</span>
                        <span class="font-medium text-gray-800">$<?php echo number_format((float)$booking['taxes'], 2); ?></span>
                      </div>
                    <?php endif; ?>
                    <?php if (isset($booking['additional_services']) && $booking['additional_services'] > 0): ?>
                      <div class="flex justify-between">
                        <span class="text-gray-600">Additional Services</span>
                        <span class="font-medium text-gray-800">$<?php echo number_format((float)$booking['additional_services'], 2); ?></span>
                      </div>
                    <?php endif; ?>
                    <div class="flex justify-between pt-2 border-t border-gray-100">
                      <span class="text-gray-800 font-semibold">Total Price</span>
                      <span class="font-bold text-teal-600">$<?php echo number_format((float)($booking['total_price'] ?? $price), 2); ?></span>
                    </div>
                  </div>
                </div>

                <div class="mb-4 pb-4 border-b border-gray-100">
                  <h3 class="text-lg font-semibold text-gray-800 mb-3">Passenger Information</h3>
                  <div class="space-y-3">
                    <div>
                      <p class="text-sm text-gray-500">Primary Passenger</p>
                      <p class="text-base font-medium text-gray-800"><?php echo htmlspecialchars($booking['passenger_name']); ?></p>
                    </div>
                    <div>
                      <p class="text-sm text-gray-500">Email</p>
                      <p class="text-base font-medium text-gray-800"><?php echo htmlspecialchars($booking['passenger_email']); ?></p>
                    </div>
                    <div>
                      <p class="text-sm text-gray-500">Phone</p>
                      <p class="text-base font-medium text-gray-800"><?php echo htmlspecialchars($booking['passenger_phone']); ?></p>
                    </div>
                    <div>
                      <p class="text-sm text-gray-500">Passenger Count</p>
                      <p class="text-base font-medium text-gray-800"><?php echo isset($booking['passenger_count']) ? $booking['passenger_count'] : '1'; ?> Person(s)</p>
                    </div>
                  </div>
                </div>

                <div>
                  <h3 class="text-lg font-semibold text-gray-800 mb-3">Booking Status</h3>
                  <div class="space-y-3">
                    <div>
                      <p class="text-sm text-gray-500">Status</p>
                      <p class="text-base">
                        <span class="booking-status <?php echo $status_class; ?>">
                          <?php echo ucfirst($booking['booking_status']); ?>
                        </span>
                      </p>
                    </div>
                    <div>
                      <p class="text-sm text-gray-500">Payment Status</p>
                      <p class="text-base">
                        <span class="booking-status <?php echo $payment_class; ?>">
                          <?php echo ucfirst($booking['payment_status'] ?? 'Unpaid'); ?>
                        </span>
                      </p>
                    </div>
                  </div>

                  <!-- Status Update Form -->
                  <div class="mt-4 no-print">
                    <form method="POST" class="mb-3">
                      <div class="flex gap-2">
                        <select name="status" class="flex-1 rounded-lg border-gray-300">
                          <option value="pending" <?php echo ($status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                          <option value="confirmed" <?php echo ($status == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                          <option value="completed" <?php echo ($status == 'completed') ? 'selected' : ''; ?>>Completed</option>
                          <option value="cancelled" <?php echo ($status == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <button type="submit" name="update_status" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg">
                          Update Status
                        </button>
                      </div>
                    </form>

                    <form method="POST">
                      <div class="flex gap-2">
                        <select name="payment_status" class="flex-1 rounded-lg border-gray-300">
                          <option value="unpaid" <?php echo ($payment_status == 'unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                          <option value="paid" <?php echo ($payment_status == 'paid') ? 'selected' : ''; ?>>Paid</option>
                          <option value="refunded" <?php echo ($payment_status == 'refunded') ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                        <button type="submit" name="update_payment" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                          Update Payment
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Additional Passengers (if available) -->
        <?php if (!empty($passenger_info) && count($passenger_info) > 0): ?>
          <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
            <div class="bg-gray-800 text-white p-4">
              <h2 class="text-xl font-semibold flex items-center">
                <i class="fas fa-users mr-2"></i> Additional Passengers
              </h2>
            </div>

            <div class="p-5">
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gender</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Passport/ID</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Special Requirements</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($passenger_info as $passenger): ?>
                      <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($passenger['name'] ?? 'N/A'); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="text-sm text-gray-500"><?php echo isset($passenger['age']) ? htmlspecialchars($passenger['age']) : 'N/A'; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="text-sm text-gray-500"><?php echo isset($passenger['gender']) ? htmlspecialchars($passenger['gender']) : 'N/A'; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="text-sm text-gray-500"><?php echo isset($passenger['document_id']) ? htmlspecialchars($passenger['document_id']) : 'N/A'; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="text-sm text-gray-500"><?php echo isset($passenger['special_requirements']) ? htmlspecialchars($passenger['special_requirements']) : 'None'; ?></div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- Flight Services and Amenities -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
          <div class="bg-gray-800 text-white p-4">
            <h2 class="text-xl font-semibold flex items-center">
              <i class="fas fa-concierge-bell mr-2"></i> Flight Services & Amenities
            </h2>
          </div>

          <div class="p-5">
            <?php
            // Check if amenities information exists
            $has_amenities = false;
            $amenities = [];

            if (isset($booking['amenities']) && !empty($booking['amenities'])) {
              $amenities = json_decode($booking['amenities'], true);
              $has_amenities = true;
            }

            // Use basic amenities for the class if none specified
            if (!$has_amenities) {
              switch (strtolower($booking['cabin_class'])) {
                case 'first class':
                  $amenities = [
                    'baggage' => '2 checked bags (32kg each), 2 cabin bags',
                    'meal' => 'Premium dining experience with gourmet meals',
                    'entertainment' => 'Premium entertainment, noise-canceling headphones',
                    'wifi' => 'Complimentary high-speed Wi-Fi',
                    'seat' => 'Fully-reclining seats with extended legroom',
                    'additional' => 'Priority boarding, VIP lounge access, Dedicated check-in'
                  ];
                  break;
                case 'business':
                  $amenities = [
                    'baggage' => '2 checked bags (23kg each), 2 cabin bags',
                    'meal' => 'Premium meal service with multiple options',
                    'entertainment' => 'Advanced entertainment system',
                    'wifi' => 'Complimentary Wi-Fi',
                    'seat' => 'Reclining seats with extra legroom',
                    'additional' => 'Priority boarding, Lounge access'
                  ];
                  break;
                case 'premium economy':
                  $amenities = [
                    'baggage' => '1 checked bag (23kg), 1 cabin bag',
                    'meal' => 'Enhanced meal service',
                    'entertainment' => 'Standard entertainment system',
                    'wifi' => 'Wi-Fi available for purchase',
                    'seat' => 'Extra legroom seats',
                    'additional' => 'Priority boarding'
                  ];
                  break;
                default: // Economy
                  $amenities = [
                    'baggage' => '1 checked bag (20kg), 1 cabin bag',
                    'meal' => 'Standard meal service',
                    'entertainment' => 'Basic entertainment system',
                    'wifi' => 'Wi-Fi available for purchase',
                    'seat' => 'Standard seats',
                    'additional' => 'Standard boarding'
                  ];
              }
              $has_amenities = true;
            }
            ?>

            <?php if ($has_amenities): ?>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                  <div class="text-teal-600 mb-2">
                    <i class="fas fa-suitcase text-xl"></i>
                  </div>
                  <h3 class="font-semibold text-gray-800 mb-1">Baggage Allowance</h3>
                  <p class="text-sm text-gray-600"><?php echo htmlspecialchars($amenities['baggage'] ?? 'Standard baggage policy'); ?></p>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                  <div class="text-teal-600 mb-2">
                    <i class="fas fa-utensils text-xl"></i>
                  </div>
                  <h3 class="font-semibold text-gray-800 mb-1">Meal Service</h3>
                  <p class="text-sm text-gray-600"><?php echo htmlspecialchars($amenities['meal'] ?? 'Standard meal service'); ?></p>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                  <div class="text-teal-600 mb-2">
                    <i class="fas fa-film text-xl"></i>
                  </div>
                  <h3 class="font-semibold text-gray-800 mb-1">Entertainment</h3>
                  <p class="text-sm text-gray-600"><?php echo htmlspecialchars($amenities['entertainment'] ?? 'Basic entertainment system'); ?></p>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                  <div class="text-teal-600 mb-2">
                    <i class="fas fa-wifi text-xl"></i>
                  </div>
                  <h3 class="font-semibold text-gray-800 mb-1">Wi-Fi</h3>
                  <p class="text-sm text-gray-600"><?php echo htmlspecialchars($amenities['wifi'] ?? 'Wi-Fi available for purchase'); ?></p>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                  <div class="text-teal-600 mb-2">
                    <i class="fas fa-chair text-xl"></i>
                  </div>
                  <h3 class="font-semibold text-gray-800 mb-1">Seat Features</h3>
                  <p class="text-sm text-gray-600"><?php echo htmlspecialchars($amenities['seat'] ?? 'Standard seating'); ?></p>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                  <div class="text-teal-600 mb-2">
                    <i class="fas fa-plus-circle text-xl"></i>
                  </div>
                  <h3 class="font-semibold text-gray-800 mb-1">Additional Services</h3>
                  <p class="text-sm text-gray-600"><?php echo htmlspecialchars($amenities['additional'] ?? 'Standard boarding and services'); ?></p>
                </div>
              </div>
            <?php else: ?>
              <div class="bg-gray-50 p-6 rounded-lg text-center">
                <p class="text-gray-600">No specific amenities information available for this flight.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Actions -->
        <div class="mt-6 bg-white rounded-xl shadow-md p-5 no-print">
          <h2 class="text-xl font-semibold text-gray-800 mb-4">Actions</h2>
          <div class="flex flex-wrap gap-3">
            <button onclick="printTicket()" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg flex items-center">
              <i class="fas fa-print mr-2"></i> Print Ticket
            </button>
            <!-- <button onclick="sendConfirmation(<?php echo $booking_id; ?>)" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
              <i class="fas fa-envelope mr-2"></i> Send Confirmation
            </button> -->
            <?php if ($status !== 'cancelled'): ?>
              <button onclick="cancelBooking(<?php echo $booking_id; ?>)" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-times mr-2"></i> Cancel Booking
              </button>
            <?php endif; ?>
            <a href="flight-details.php?id=<?php echo $booking['flight_id']; ?>" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center">
              <i class="fas fa-plane mr-2"></i> View Flight Details
            </a>
            <a href="user-details.php?id=<?php echo $booking['user_id']; ?>" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
              <i class="fas fa-user mr-2"></i> View Passenger Details
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

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
    });

    function printTicket() {
      window.print();
    }

    function sendConfirmation(bookingId) {
      if (bookingId <= 0) {
        Swal.fire({
          title: 'Error',
          text: 'Invalid booking ID',
          icon: 'error'
        });
        return;
      }

      Swal.fire({
        title: 'Send Confirmation Email',
        text: "Send a booking confirmation email to the passenger?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, send it!'
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading state
          Swal.fire({
            title: 'Sending...',
            text: 'Sending confirmation email to passenger',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          // Send the request to the backend
          fetch(`send-flight-confirmation.php?id=${bookingId}`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              }
            })
            .then(response => {
              if (!response.ok) {
                throw new Error('Network response was not ok');
              }
              return response.json();
            })
            .then(data => {
              if (data.success) {
                Swal.fire({
                  title: 'Success!',
                  text: 'Confirmation email has been sent to the passenger.',
                  icon: 'success',
                  confirmButtonColor: '#3085d6'
                });
              } else {
                throw new Error(data.message || 'Failed to send email');
              }
            })
            .catch(error => {
              console.error('Error:', error);
              Swal.fire({
                title: 'Error!',
                text: error.message || 'An error occurred while sending the confirmation email',
                icon: 'error',
                confirmButtonColor: '#3085d6'
              });
            });
        }
      });
    }

    function cancelBooking(bookingId) {
      if (bookingId <= 0) {
        Swal.fire({
          title: 'Error',
          text: 'Invalid booking ID',
          icon: 'error'
        });
        return;
      }

      Swal.fire({
        title: 'Cancel Booking',
        text: "Are you sure you want to cancel this booking? This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, cancel it!'
      }).then((result) => {
        if (result.isConfirmed) {
          // Update booking status
          fetch(`update-flight-status.php?id=${bookingId}`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                status: 'cancelled'
              })
            })
            .then(response => {
              if (!response.ok) {
                throw new Error('Network response was not ok');
              }
              return response.json();
            })
            .then(data => {
              if (data.success) {
                Swal.fire({
                  title: 'Cancelled!',
                  text: 'The booking has been cancelled.',
                  icon: 'success',
                  showConfirmButton: false,
                  timer: 1500
                }).then(() => {
                  // Reload the page to reflect changes
                  window.location.reload();
                });
              } else {
                throw new Error(data.message || 'Failed to cancel booking');
              }
            })
            .catch(error => {
              console.error('Error:', error);
              Swal.fire({
                title: 'Error!',
                text: error.message || 'An error occurred while cancelling the booking',
                icon: 'error',
                confirmButtonColor: '#3085d6'
              });
            });
        }
      });
    }
  </script>
</body>

</html>