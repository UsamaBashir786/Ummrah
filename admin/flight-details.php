<?php
require_once 'connection/connection.php';

// Check if flight ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
  header("Location: manage-flights.php");
  exit;
}

$flight_id = intval($_GET['id']);

// Fetch flight details
$sql = "SELECT * FROM flights WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  header("Location: manage-flights.php");
  exit;
}

$flight = $result->fetch_assoc();
$stmt->close();

// Get booking count
$booking_sql = "SELECT COUNT(*) as booking_count FROM flight_bookings WHERE flight_id = ?";
$stmt = $conn->prepare($booking_sql);
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$booking_result = $stmt->get_result();
$booking_count = $booking_result->fetch_assoc()['booking_count'];
$stmt->close();

// Get available seats
function getAvailableSeats($flight, $conn)
{
  if (!isset($flight['seats']) || empty($flight['seats'])) {
    return ['total' => 0, 'booked' => 0, 'available' => 0, 'by_class' => []];
  }

  $seats = json_decode($flight['seats'], true);
  if (!$seats) {
    return ['total' => 0, 'booked' => 0, 'available' => 0, 'by_class' => []];
  }

  $total = 0;
  $booked = 0;
  $by_class = [];

  // Count total and booked seats by class
  foreach ($seats as $class => $data) {
    $class_count = $data['count'] ?? 0;
    $total += $class_count;

    $sql = "SELECT COUNT(*) as booked FROM flight_bookings WHERE flight_id = ? AND cabin_class = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $flight['id'], $class);
    $stmt->execute();
    $result = $stmt->get_result();
    $class_booked = $result->fetch_assoc()['booked'] ?? 0;
    $stmt->close();

    $booked += $class_booked;
    $by_class[$class] = [
      'total' => $class_count,
      'booked' => $class_booked,
      'available' => $class_count - $class_booked,
      'price' => isset($flight['prices']) ? (json_decode($flight['prices'], true)[$class] ?? 0) : 0
    ];
  }

  return [
    'total' => $total,
    'booked' => $booked,
    'available' => $total - $booked,
    'by_class' => $by_class
  ];
}

$seat_info = getAvailableSeats($flight, $conn);

// Format dates
$departure_date = null;
$formatted_departure_date = 'N/A';
if (isset($flight['departure_date']) && !empty($flight['departure_date'])) {
  $departure_date = new DateTime($flight['departure_date']);
  $formatted_departure_date = $departure_date->format('F d, Y h:i A');
}

$arrival_date = null;
$formatted_arrival_date = 'N/A';
if (isset($flight['arrival_date']) && !empty($flight['arrival_date'])) {
  $arrival_date = new DateTime($flight['arrival_date']);
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

// Get flight status
$flight_status = 'Scheduled';
$status_class = 'bg-blue-100 text-blue-800';

if (isset($flight['status'])) {
  $flight_status = $flight['status'];

  switch (strtolower($flight_status)) {
    case 'in air':
    case 'in-air':
    case 'in_air':
      $flight_status = 'In Air';
      $status_class = 'bg-purple-100 text-purple-800';
      break;
    case 'landed':
    case 'arrived':
    case 'completed':
      $flight_status = 'Landed';
      $status_class = 'bg-green-100 text-green-800';
      break;
    case 'cancelled':
    case 'canceled':
      $flight_status = 'Cancelled';
      $status_class = 'bg-red-100 text-red-800';
      break;
    case 'delayed':
      $flight_status = 'Delayed';
      $status_class = 'bg-amber-100 text-amber-800';
      break;
    default:
      $flight_status = 'Scheduled';
      $status_class = 'bg-blue-100 text-blue-800';
  }
}

// Check if flight is in the past
$is_past_flight = false;
if ($arrival_date instanceof DateTime) {
  $current_date = new DateTime();
  if ($arrival_date < $current_date) {
    $is_past_flight = true;
    if ($flight_status === 'Scheduled') {
      $flight_status = 'Completed';
      $status_class = 'bg-green-100 text-green-800';
    }
  }
}

// Update flight status if form submitted
if (isset($_POST['update_status'])) {
  $new_status = $_POST['status'];

  $update_sql = "UPDATE flights SET status = ? WHERE id = ?";
  $update_stmt = $conn->prepare($update_sql);
  $update_stmt->bind_param("si", $new_status, $flight_id);

  if ($update_stmt->execute()) {
    // Update the local variables
    $flight_status = $new_status;

    // Set status class
    switch (strtolower($new_status)) {
      case 'in air':
      case 'in-air':
      case 'in_air':
        $flight_status = 'In Air';
        $status_class = 'bg-purple-100 text-purple-800';
        break;
      case 'landed':
      case 'arrived':
      case 'completed':
        $flight_status = 'Landed';
        $status_class = 'bg-green-100 text-green-800';
        break;
      case 'cancelled':
      case 'canceled':
        $flight_status = 'Cancelled';
        $status_class = 'bg-red-100 text-red-800';
        break;
      case 'delayed':
        $flight_status = 'Delayed';
        $status_class = 'bg-amber-100 text-amber-800';
        break;
      default:
        $flight_status = 'Scheduled';
        $status_class = 'bg-blue-100 text-blue-800';
    }

    $success_message = "Flight status updated successfully.";
  } else {
    $error_message = "Failed to update flight status.";
  }

  $update_stmt->close();
}

// Get recent bookings
$recent_bookings = [];
$booking_sql = "SELECT fb.*, u.full_name as passenger_name 
               FROM flight_bookings fb 
               JOIN users u ON fb.user_id = u.id 
               WHERE fb.flight_id = ? 
               ORDER BY fb.booking_date DESC LIMIT 5";

$stmt = $conn->prepare($booking_sql);
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$booking_result = $stmt->get_result();

if ($booking_result && $booking_result->num_rows > 0) {
  while ($row = $booking_result->fetch_assoc()) {
    $recent_bookings[] = $row;
  }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <style>
    .flight-header {
      background-image: linear-gradient(to right, #0891b2, #0e7490);
    }

    .info-card {
      transition: all 0.3s ease;
    }

    .info-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
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

    .seat-availability {
      height: 20px;
      border-radius: 9999px;
      overflow: hidden;
    }

    .seat-filled {
      background-color: #ef4444;
    }

    .seat-available {
      background-color: #10b981;
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
            <i class="text-teal-600 fa fa-plane mr-2"></i> Flight Details
          </h1>
        </div>
        <div class="flex space-x-3">
          <button onclick="window.print()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-print mr-2"></i> Print
          </button>
          <a href="edit-flight.php?id=<?php echo $flight_id; ?>" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-edit mr-2"></i> Edit
          </a>
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

        <!-- Flight Header -->
        <div class="flight-header bg-gradient-to-r from-cyan-600 to-teal-700 rounded-xl shadow-lg p-6 mb-6 text-white">
          <div class="flex flex-col md:flex-row justify-between items-start">
            <div>
              <h1 class="text-3xl font-bold">
                <?php echo htmlspecialchars($flight['airline_name'] ?? 'Airline'); ?> -
                Flight #<?php echo htmlspecialchars($flight['flight_number'] ?? 'N/A'); ?>
              </h1>
              <div class="mt-2 flex flex-col md:flex-row md:items-center space-y-2 md:space-y-0 md:space-x-4">
                <div class="flex items-center">
                  <i class="fas fa-map-marker-alt mr-2"></i>
                  <span>
                    <?php echo htmlspecialchars($flight['departure_city'] ?? 'Origin'); ?> to
                    <?php echo htmlspecialchars($flight['arrival_city'] ?? 'Destination'); ?>
                  </span>
                </div>
                <div class="flex items-center">
                  <i class="far fa-calendar-alt mr-2"></i>
                  <span>
                    <?php
                    echo ($departure_date instanceof DateTime)
                      ? $departure_date->format('M d, Y')
                      : 'Date not available';
                    ?>
                  </span>
                </div>
              </div>
              <div class="mt-3 flex flex-wrap gap-2">
                <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $status_class; ?>">
                  <i class="fas fa-info-circle mr-1"></i> <?php echo $flight_status; ?>
                </span>
                <span class="px-3 py-1 bg-white/20 rounded-full text-sm">
                  <i class="fas fa-users mr-1"></i> <?php echo $booking_count; ?> Bookings
                </span>
                <span class="px-3 py-1 bg-white/20 rounded-full text-sm">
                  <i class="fas fa-chair mr-1"></i>
                  <?php echo $seat_info['available']; ?>/<?php echo $seat_info['total']; ?> Seats Available
                </span>
              </div>
            </div>
            <?php if (!$is_past_flight && $flight_status !== 'Cancelled'): ?>
              <div class="mt-4 md:mt-0">
                <a href="create-flight-booking.php?flight_id=<?php echo $flight_id; ?>"
                  class="bg-white/10 hover:bg-white/20 rounded-lg px-4 py-2 inline-flex items-center text-white">
                  <i class="fas fa-plus mr-2"></i> Create Booking
                </a>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Flight Details -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
          <!-- Main Flight Information -->
          <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
              <div class="bg-gray-800 text-white p-4">
                <h2 class="text-xl font-semibold flex items-center">
                  <i class="fas fa-plane mr-2"></i> Flight Information
                </h2>
              </div>

              <div class="p-5">
                <!-- Flight Path Visualization -->
                <div class="flight-path mb-6">
                  <div class="grid grid-cols-2">
                    <div class="ticket-segment text-center">
                      <div class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($flight['departure_city'] ?? 'N/A'); ?></div>
                      <div class="text-sm text-gray-500"><?php echo isset($flight['departure_airport']) ? htmlspecialchars($flight['departure_airport']) : 'N/A'; ?></div>
                      <div class="text-base font-medium text-teal-600 mt-1">
                        <?php echo ($departure_date instanceof DateTime) ? $departure_date->format('h:i A') : 'N/A'; ?>
                      </div>
                      <div class="text-xs text-gray-500">
                        <?php echo ($departure_date instanceof DateTime) ? $departure_date->format('d M Y') : 'N/A'; ?>
                      </div>
                    </div>
                    <div class="ticket-segment text-center">
                      <div class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($flight['arrival_city'] ?? 'N/A'); ?></div>
                      <div class="text-sm text-gray-500"><?php echo isset($flight['arrival_airport']) ? htmlspecialchars($flight['arrival_airport']) : 'N/A'; ?></div>
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
                        <i class="fas fa-plane-departure text-teal-600 mr-1"></i>
                        <?php echo htmlspecialchars($flight['aircraft_type'] ?? 'N/A'); ?>
                      </p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg">
                      <p class="text-sm text-gray-500">Flight Status</p>
                      <p class="text-base">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                          <?php echo $flight_status; ?>
                        </span>
                      </p>
                    </div>
                  </div>
                </div>

                <div class="mt-6 border-t border-gray-100 pt-4">
                  <h3 class="text-lg font-semibold text-gray-800 mb-3">Flight Details</h3>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <p class="text-sm text-gray-500">Airline</p>
                      <p class="text-base font-medium text-gray-800">
                        <?php echo htmlspecialchars($flight['airline_name'] ?? 'N/A'); ?>
                      </p>
                    </div>
                    <div>
                      <p class="text-sm text-gray-500">Flight Number</p>
                      <p class="text-base font-medium text-gray-800">
                        <?php echo htmlspecialchars($flight['flight_number'] ?? 'N/A'); ?>
                      </p>
                    </div>
                    <div>
                      <p class="text-sm text-gray-500">Departure Terminal</p>
                      <p class="text-base font-medium text-gray-800">
                        <?php echo htmlspecialchars($flight['departure_terminal'] ?? 'N/A'); ?>
                      </p>
                    </div>
                    <div>
                      <p class="text-sm text-gray-500">Arrival Terminal</p>
                      <p class="text-base font-medium text-gray-800">
                        <?php echo htmlspecialchars($flight['arrival_terminal'] ?? 'N/A'); ?>
                      </p>
                    </div>
                    <div>
                      <p class="text-sm text-gray-500">Flight Type</p>
                      <p class="text-base font-medium text-gray-800">
                        <?php echo htmlspecialchars($flight['flight_type'] ?? 'Standard'); ?>
                      </p>
                    </div>
                    <div>
                      <p class="text-sm text-gray-500">Created On</p>
                      <p class="text-base font-medium text-gray-800">
                        <?php
                        if (isset($flight['created_at']) && !empty($flight['created_at'])) {
                          $created_date = new DateTime($flight['created_at']);
                          echo $created_date->format('M d, Y h:i A');
                        } else {
                          echo 'N/A';
                        }
                        ?>
                      </p>
                    </div>
                  </div>
                </div>

                <?php if (!$is_past_flight && $flight_status !== 'Cancelled'): ?>
                  <!-- Update Status Form -->
                  <div class="mt-6 border-t border-gray-100 pt-4 no-print">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Update Flight Status</h3>
                    <form method="POST">
                      <div class="flex gap-2">
                        <select name="status" class="flex-1 rounded-lg border-gray-300">
                          <option value="scheduled" <?php echo ($flight_status === 'Scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                          <option value="in_air" <?php echo ($flight_status === 'In Air') ? 'selected' : ''; ?>>In Air</option>
                          <option value="landed" <?php echo ($flight_status === 'Landed') ? 'selected' : ''; ?>>Landed</option>
                          <option value="delayed" <?php echo ($flight_status === 'Delayed') ? 'selected' : ''; ?>>Delayed</option>
                          <option value="cancelled" <?php echo ($flight_status === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <button type="submit" name="update_status" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg">
                          Update Status
                        </button>
                      </div>
                    </form>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <!-- Bookings Section -->
            <?php if (count($recent_bookings) > 0): ?>
              <div class="bg-white rounded-xl shadow-md overflow-hidden mt-6">
                <div class="bg-gray-800 text-white p-4 flex justify-between items-center">
                  <h2 class="text-xl font-semibold flex items-center">
                    <i class="fas fa-ticket-alt mr-2"></i> Recent Bookings
                  </h2>
                  <?php if ($booking_count > 5): ?>
                    <a href="flight-bookings.php?flight_id=<?php echo $flight_id; ?>" class="text-teal-300 hover:text-white">
                      View All (<?php echo $booking_count; ?>)
                    </a>
                  <?php endif; ?>
                </div>

                <div class="p-5">
                  <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                      <thead class="bg-gray-50">
                        <tr>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking ID</th>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Passenger</th>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking Date</th>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                      </thead>
                      <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($recent_bookings as $booking):
                          // Define status class
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
                        ?>
                          <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                              <div class="text-sm font-medium text-gray-900">
                                #<?php echo $booking['id']; ?>
                              </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                              <div class="text-sm text-gray-900">
                                <?php echo htmlspecialchars($booking['passenger_name'] ?? 'N/A'); ?>
                              </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                              <div class="text-sm text-gray-500">
                                <?php
                                if (isset($booking['booking_date']) && !empty($booking['booking_date'])) {
                                  $booking_date = new DateTime($booking['booking_date']);
                                  echo $booking_date->format('M d, Y');
                                } else {
                                  echo 'N/A';
                                }
                                ?>
                              </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                              <div class="text-sm text-gray-500">
                                <?php echo htmlspecialchars($booking['cabin_class'] ?? 'N/A'); ?>
                              </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                              <span class="booking-status <?php echo $status_class; ?>">
                                <?php echo ucfirst($booking['booking_status'] ?? 'Pending'); ?>
                              </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                              <a href="flight-booking-details.php?id=<?php echo $booking['id']; ?>"
                                class="text-indigo-600 hover:text-indigo-900">
                                View
                              </a>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <!-- Sidebar with Capacity and Pricing -->
          <div class="lg:col-span-1">
            <!-- Seat Capacity -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
              <div class="bg-gray-800 text-white p-4">
                <h2 class="text-xl font-semibold flex items-center">
                  <i class="fas fa-chair mr-2"></i> Seat Availability
                </h2>
              </div>

              <div class="p-5">
                <div class="mb-4">
                  <div class="flex justify-between items-center mb-2">
                    <span class="text-gray-700 font-medium">Total Capacity</span>
                    <span class="text-gray-900 font-semibold"><?php echo $seat_info['total']; ?> seats</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <?php
                    $occupancy_percentage = ($seat_info['total'] > 0) ?
                      ($seat_info['booked'] / $seat_info['total']) * 100 : 0;
                    ?>
                    <div class="bg-teal-600 h-2.5 rounded-full" style="width: <?php echo $occupancy_percentage; ?>%"></div>
                  </div>
                  <div class="flex justify-between text-xs text-gray-500 mt-1">
                    <span><?php echo $seat_info['booked']; ?> booked</span>
                    <span><?php echo $seat_info['available']; ?> available</span>
                  </div>
                </div>

                <div class="mt-4">
                  <h3 class="text-lg font-semibold text-gray-800 mb-3">Availability by Class</h3>

                  <?php if (!empty($seat_info['by_class'])): ?>
                    <div class="space-y-4">
                      <?php foreach ($seat_info['by_class'] as $class => $info): ?>
                        <div>
                          <div class="flex justify-between items-center mb-1">
                            <span class="text-gray-700"><?php echo ucfirst($class); ?> Class</span>
                            <span class="text-sm text-gray-600">
                              <?php echo $info['available']; ?>/<?php echo $info['total']; ?> available
                            </span>
                          </div>
                          <div class="flex w-full h-5 rounded-full overflow-hidden">
                            <?php
                            $class_occupancy = ($info['total'] > 0) ?
                              ($info['booked'] / $info['total']) * 100 : 0;
                            ?>
                            <div class="seat-filled" style="width: <?php echo $class_occupancy; ?>%"></div>
                            <div class="seat-available" style="width: <?php echo 100 - $class_occupancy; ?>%"></div>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php else: ?>
                    <div class="text-gray-500 text-center py-4">
                      No seat class information available
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Pricing Information -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
              <div class="bg-gray-800 text-white p-4">
                <h2 class="text-xl font-semibold flex items-center">
                  <i class="fas fa-tag mr-2"></i> Pricing Information
                </h2>
              </div>

              <div class="p-5">
                <?php
                $prices = [];
                if (isset($flight['prices']) && !empty($flight['prices'])) {
                  $prices = json_decode($flight['prices'], true);
                }

                if (!empty($prices)):
                ?>
                  <div class="space-y-3">
                    <?php foreach ($prices as $class => $price): ?>
                      <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <div>
                          <span class="text-gray-800 font-medium"><?php echo ucfirst($class); ?> Class</span>
                          <?php
                          $availability = $seat_info['by_class'][$class]['available'] ?? 0;
                          $total = $seat_info['by_class'][$class]['total'] ?? 0;
                          if ($total > 0):
                          ?>
                            <div class="text-xs text-gray-500">
                              <?php echo $availability; ?> of <?php echo $total; ?> seats available
                            </div>
                          <?php endif; ?>
                        </div>
                        <div class="text-xl font-bold text-teal-600">
                          $<?php echo number_format((float)$price, 2); ?>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <div class="text-gray-500 text-center py-4">
                    No pricing information available
                  </div>
                <?php endif; ?>

                <?php if (isset($flight['baggage_allowance']) && !empty($flight['baggage_allowance'])): ?>
                  <div class="mt-5 pt-5 border-t border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Baggage Allowance</h3>
                    <div class="bg-gray-50 p-3 rounded-lg">
                      <p class="text-gray-700">
                        <?php
                        $baggage = is_string($flight['baggage_allowance']) ?
                          $flight['baggage_allowance'] : json_encode($flight['baggage_allowance']);
                        echo htmlspecialchars($baggage);
                        ?>
                      </p>
                    </div>
                  </div>
                <?php endif; ?>

                <?php if (isset($flight['cancellation_policy']) && !empty($flight['cancellation_policy'])): ?>
                  <div class="mt-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Cancellation Policy</h3>
                    <div class="bg-gray-50 p-3 rounded-lg">
                      <p class="text-gray-700">
                        <?php echo htmlspecialchars($flight['cancellation_policy']); ?>
                      </p>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <!-- Flight Amenities -->
            <?php
            $amenities = [];
            if (isset($flight['amenities']) && !empty($flight['amenities'])) {
              $amenities = is_array($flight['amenities']) ?
                $flight['amenities'] : json_decode($flight['amenities'], true);
            }

            if (!empty($amenities)):
            ?>
              <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="bg-gray-800 text-white p-4">
                  <h2 class="text-xl font-semibold flex items-center">
                    <i class="fas fa-concierge-bell mr-2"></i> Flight Amenities
                  </h2>
                </div>

                <div class="p-5">
                  <div class="grid grid-cols-2 gap-3">
                    <?php foreach ($amenities as $key => $value):
                      if (empty($value)) continue;

                      $icon = 'fas fa-check';
                      switch (strtolower($key)) {
                        case 'wifi':
                          $icon = 'fas fa-wifi';
                          break;
                        case 'meal':
                        case 'food':
                        case 'meals':
                          $icon = 'fas fa-utensils';
                          break;
                        case 'entertainment':
                          $icon = 'fas fa-film';
                          break;
                        case 'power':
                        case 'power_outlets':
                          $icon = 'fas fa-plug';
                          break;
                        case 'baggage':
                        case 'luggage':
                          $icon = 'fas fa-suitcase';
                          break;
                      }
                    ?>
                      <div class="flex items-start p-2">
                        <div class="flex-shrink-0 h-5 w-5 text-teal-600">
                          <i class="<?php echo $icon; ?>"></i>
                        </div>
                        <div class="ml-3 text-sm">
                          <p class="text-gray-700 font-medium">
                            <?php echo ucwords(str_replace('_', ' ', $key)); ?>
                          </p>
                          <?php if (is_string($value) && !empty($value)): ?>
                            <p class="text-gray-500"><?php echo htmlspecialchars($value); ?></p>
                          <?php endif; ?>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Actions -->
        <div class="mt-6 bg-white rounded-xl shadow-md p-5 no-print">
          <h2 class="text-xl font-semibold text-gray-800 mb-4">Actions</h2>
          <div class="flex flex-wrap gap-3">
            <a href="edit-flight.php?id=<?php echo $flight_id; ?>" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center">
              <i class="fas fa-edit mr-2"></i> Edit Flight
            </a>

            <?php if (!$is_past_flight && $flight_status !== 'Cancelled'): ?>
              <a href="create-flight-booking.php?flight_id=<?php echo $flight_id; ?>" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-plus mr-2"></i> Add Booking
              </a>
            <?php endif; ?>

            <?php if ($booking_count > 0): ?>
              <a href="flight-bookings.php?flight_id=<?php echo $flight_id; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-list mr-2"></i> View All Bookings
              </a>
            <?php endif; ?>

            <?php if ($flight_status !== 'Cancelled'): ?>
              <button onclick="cancelFlight(<?php echo $flight_id; ?>)" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-ban mr-2"></i> Cancel Flight
              </button>
            <?php endif; ?>

            <button onclick="deleteFlight(<?php echo $flight_id; ?>)" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
              <i class="fas fa-trash-alt mr-2"></i> Delete Flight
            </button>
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

    function cancelFlight(flightId) {
      if (flightId <= 0) {
        Swal.fire({
          title: 'Error',
          text: 'Invalid flight ID',
          icon: 'error'
        });
        return;
      }

      Swal.fire({
        title: 'Cancel Flight',
        text: "Are you sure you want to cancel this flight? This will affect all bookings.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, cancel it!'
      }).then((result) => {
        if (result.isConfirmed) {
          // Update flight status to cancelled
          fetch(`update-flight-status.php?id=${flightId}`, {
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
                  text: 'The flight has been cancelled.',
                  icon: 'success',
                  showConfirmButton: false,
                  timer: 1500
                }).then(() => {
                  // Reload the page to reflect changes
                  window.location.reload();
                });
              } else {
                throw new Error(data.message || 'Failed to cancel flight');
              }
            })
            .catch(error => {
              console.error('Error:', error);
              Swal.fire({
                title: 'Error!',
                text: error.message || 'An error occurred while cancelling the flight',
                icon: 'error',
                confirmButtonColor: '#3085d6'
              });
            });
        }
      });
    }

    function deleteFlight(flightId) {
      if (flightId <= 0) {
        Swal.fire({
          title: 'Error',
          text: 'Invalid flight ID',
          icon: 'error'
        });
        return;
      }

      Swal.fire({
        title: 'Delete Flight',
        text: "Are you sure you want to delete this flight? This will delete all associated bookings too. This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading state
          Swal.fire({
            title: 'Deleting...',
            text: 'Please wait while we delete the flight and associated data',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          fetch(`delete-flight.php?id=${flightId}`, {
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
                  title: 'Deleted!',
                  text: 'Flight and all associated bookings have been deleted.',
                  icon: 'success',
                  showConfirmButton: false,
                  timer: 1500
                }).then(() => {
                  window.location.href = 'manage-flights.php';
                });
              } else {
                throw new Error(data.message || 'Failed to delete flight');
              }
            })
            .catch(error => {
              console.error('Error:', error);
              Swal.fire({
                title: 'Error!',
                text: error.message || 'An error occurred while deleting the flight',
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