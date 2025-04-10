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

// Format dates and times
$booking_date = new DateTime($booking['booking_date']);
$formatted_booking_date = $booking_date->format('F d, Y h:i A');

$departure_datetime = DateTime::createFromFormat(
  'Y-m-d H:i:s',
  $booking['departure_date'] . ' ' . $booking['departure_time']
);
$formatted_departure = $departure_datetime ? $departure_datetime->format('F d, Y h:i A') : 'N/A';

$arrival_datetime = null;
$formatted_arrival = 'N/A';
if ($departure_datetime && !empty($booking['flight_duration'])) {
  $arrival_datetime = clone $departure_datetime;
  $arrival_datetime->modify("+{$booking['flight_duration']} hours");
  $formatted_arrival = $arrival_datetime->format('F d, Y h:i A');
}

// Format duration
$duration = !empty($booking['flight_duration']) ?
  sprintf('%dh %dm', floor($booking['flight_duration']), ($booking['flight_duration'] * 60) % 60) :
  'N/A';

// Get seat information
$seatInfo = getAvailableSeats($booking['flight_id'], $conn);

// Get price information - Fixed version
$flight_prices = json_decode($booking['prices'], true) ?? [];
$base_price = 0;
if (!empty($flight_prices)) {
  // Normalize cabin class to match JSON keys (e.g., "Economy" to "economy")
  $cabin_class = strtolower($booking['cabin_class']);
  $base_price = isset($flight_prices[$cabin_class]) ? (float)$flight_prices[$cabin_class] : 0;
}

// Use the price from flight_bookings as the total price
$total_price = isset($booking['price']) ? (float)$booking['price'] : $base_price;

// Calculate passenger count (adult_count + children_count)
$passenger_count = ($booking['adult_count'] ?? 1) + ($booking['children_count'] ?? 0);

// Define status classes
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

// Get passenger details (if any additional passengers are stored in JSON)
$passenger_info = json_decode($booking['passenger_details'] ?? '[]', true);

// Update booking status
if (isset($_POST['update_status'])) {
  $new_status = $_POST['status'];
  $update_sql = "UPDATE flight_bookings SET booking_status = ? WHERE id = ?";
  $stmt = $conn->prepare($update_sql);
  $stmt->bind_param("si", $new_status, $booking_id);

  if ($stmt->execute()) {
    $booking['booking_status'] = $new_status;
    $status = strtolower($new_status);
    $status_class = $status === 'confirmed' || $status === 'completed' ? 'status-confirmed' : ($status === 'cancelled' ? 'status-cancelled' : 'status-pending');
    $success_message = "Booking status updated successfully.";
  } else {
    $error_message = "Failed to update booking status.";
  }
  $stmt->close();
}

// Update payment status
if (isset($_POST['update_payment'])) {
  $new_payment_status = $_POST['payment_status'];
  $update_sql = "UPDATE flight_bookings SET payment_status = ? WHERE id = ?";
  $stmt = $conn->prepare($update_sql);
  $stmt->bind_param("si", $new_payment_status, $booking_id);

  if ($stmt->execute()) {
    $booking['payment_status'] = $new_payment_status;
    $payment_status = strtolower($new_payment_status);
    $payment_class = $payment_status === 'paid' || $payment_status === 'completed' ? 'status-confirmed' : ($payment_status === 'refunded' ? 'status-cancelled' : 'status-pending');
    $payment_success_message = "Payment status updated successfully.";
  } else {
    $payment_error_message = "Failed to update payment status.";
  }
  $stmt->close();
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
    <?php include 'includes/sidebar.php'; ?>
    <div class="main flex-1 flex flex-col overflow-hidden">
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
                  <i class="fas fa-users mr-1"></i> <?php echo $passenger_count; ?> Passenger(s)
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
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
                <div class="flight-path mb-6">
                  <div class="grid grid-cols-2">
                    <div class="ticket-segment text-center">
                      <div class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($booking['departure_city']); ?></div>
                      <div class="text-base font-medium text-teal-600 mt-1">
                        <?php echo $departure_datetime ? $departure_datetime->format('h:i A') : 'N/A'; ?>
                      </div>
                      <div class="text-xs text-gray-500">
                        <?php echo $departure_datetime ? $departure_datetime->format('d M Y') : 'N/A'; ?>
                      </div>
                    </div>
                    <div class="ticket-segment text-center">
                      <div class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($booking['arrival_city']); ?></div>
                      <div class="text-base font-medium text-teal-600 mt-1">
                        <?php echo $arrival_datetime ? $arrival_datetime->format('h:i A') : 'N/A'; ?>
                      </div>
                      <div class="text-xs text-gray-500">
                        <?php echo $arrival_datetime ? $arrival_datetime->format('d M Y') : 'N/A'; ?>
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
                      <p class="text-sm text-gray-500">Distance</p>
                      <p class="text-base font-medium text-gray-800">
                        <i class="fas fa-ruler text-teal-600 mr-1"></i> <?php echo htmlspecialchars($booking['distance'] ?? 'N/A'); ?> km
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
                      <p class="text-sm text-gray-500">Flight Notes</p>
                      <p class="text-base font-medium text-gray-800"><?php echo htmlspecialchars($booking['flight_notes'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                      <p class="text-sm text-gray-500">Booking Reference</p>
                      <p class="text-base font-medium text-gray-800"><?php echo htmlspecialchars($booking['id']); ?></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

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
                      <span class="font-medium text-gray-800">$<?php echo number_format($base_price, 2); ?></span>
                    </div>
                    <!-- Add taxes if applicable -->
                    <div class="flex justify-between pt-2 border-t border-gray-100">
                      <span class="text-gray-800 font-semibold">Total Price</span>
                      <span class="font-bold text-teal-600">$<?php echo number_format($total_price, 2); ?></span>
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
                      <p class="text-base font-medium text-gray-800"><?php echo $passenger_count; ?> Person(s)</p>
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

        <?php if (!empty($passenger_info)): ?>
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
                          <div class="text-sm text-gray-500"><?php echo htmlspecialchars($passenger['age'] ?? 'N/A'); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="text-sm text-gray-500"><?php echo htmlspecialchars($passenger['gender'] ?? 'N/A'); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="text-sm text-gray-500"><?php echo htmlspecialchars($passenger['document_id'] ?? 'N/A'); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="text-sm text-gray-500"><?php echo htmlspecialchars($passenger['special_requirements'] ?? 'None'); ?></div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
          <div class="bg-gray-800 text-white p-4">
            <h2 class="text-xl font-semibold flex items-center">
              <i class="fas fa-concierge-bell mr-2"></i> Flight Services & Amenities
            </h2>
          </div>
          <div class="p-5">
            <?php
            $amenities = [];
            switch (strtolower($booking['cabin_class'])) {
              case 'first class':
                $amenities = [
                  'baggage' => '2 checked bags (32kg each), 2 cabin bags',
                  'meal' => 'Premium dining experience with gourmet meals',
                  'entertainment' => 'Premium entertainment, noise-canceling headphones',
                  'wifi' => 'Complimentary high-speed Wi-Fi',
                  'seat' => 'Fully-reclining seats with extended legroom',
                  'additional' => 'Priority boarding, VIP lounge access'
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
            ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="bg-gray-50 p-4 rounded-lg">
                <div class="text-teal-600 mb-2"><i class="fas fa-suitcase text-xl"></i></div>
                <h3 class="font-semibold text-gray-800 mb-1">Baggage Allowance</h3>
                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($amenities['baggage']); ?></p>
              </div>
              <div class="bg-gray-50 p-4 rounded-lg">
                <div class="text-teal-600 mb-2"><i class="fas fa-utensils text-xl"></i></div>
                <h3 class="font-semibold text-gray-800 mb-1">Meal Service</h3>
                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($amenities['meal']); ?></p>
              </div>
              <div class="bg-gray-50 p-4 rounded-lg">
                <div class="text-teal-600 mb-2"><i class="fas fa-film text-xl"></i></div>
                <h3 class="font-semibold text-gray-800 mb-1">Entertainment</h3>
                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($amenities['entertainment']); ?></p>
              </div>
              <div class="bg-gray-50 p-4 rounded-lg">
                <div class="text-teal-600 mb-2"><i class="fas fa-wifi text-xl"></i></div>
                <h3 class="font-semibold text-gray-800 mb-1">Wi-Fi</h3>
                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($amenities['wifi']); ?></p>
              </div>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
              <div class="text-teal-600 mb-2"><i class="fas fa-plus-circle text-xl"></i></div>
              <h3 class="font-semibold text-gray-800 mb-1">Additional Services</h3>
              <p class="text-sm text-gray-600"><?php echo htmlspecialchars($amenities['additional']); ?></p>
            </div>
          </div>
        </div>
        <div class="mt-6 bg-white rounded-xl shadow-md p-5 no-print">
          <h2 class="text-xl font-semibold text-gray-800 mb-4">Actions</h2>
          <div class="flex flex-wrap gap-3">
            <button onclick="printTicket()" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg flex items-center">
              <i class="fas fa-print mr-2"></i> Print Ticket
            </button>
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
  </div>

  <?php include 'includes/js-links.php'; ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
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
          fetch(`update-flight-status.php?id=${bookingId}`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                status: 'cancelled'
              })
            })
            .then(response => response.ok ? response.json() : Promise.reject('Network error'))
            .then(data => {
              if (data.success) {
                Swal.fire({
                    title: 'Cancelled!',
                    text: 'The booking has been cancelled.',
                    icon: 'success',
                    timer: 1500
                  })
                  .then(() => window.location.reload());
              } else {
                throw new Error(data.message || 'Failed to cancel booking');
              }
            })
            .catch(error => {
              console.error('Error:', error);
              Swal.fire({
                title: 'Error!',
                text: error.message,
                icon: 'error'
              });
            });
        }
      });
    }
  </script>
</body>

</html>