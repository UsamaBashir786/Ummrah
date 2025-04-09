<?php
session_start();
include 'connection/connection.php';

// Security: Prevent session fixation
if (!isset($_SESSION['initiated'])) {
  session_regenerate_id(true);
  $_SESSION['initiated'] = true;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

// Input sanitization for flight_id
$flight_id = filter_input(INPUT_GET, 'flight_id', FILTER_VALIDATE_INT);
if (!$flight_id) {
  header("Location: flights.php");
  exit();
}

$user_id = (int)$_SESSION['user_id'];

// Fetch user details with error handling
try {
  $user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
  $user_stmt->bind_param("i", $user_id);
  $user_stmt->execute();
  $user_result = $user_stmt->get_result();
  $user_details = $user_result->num_rows > 0 ? $user_result->fetch_assoc() : null;
  $user_stmt->close();
} catch (Exception $e) {
  error_log("User fetch error: " . $e->getMessage());
  $user_details = null;
}

$error_message = "";
$success_message = "";
$info_message = "";
$flight_details = null;
$seats_data = null;
$booked_seats = [];

// Check existing booking
try {
  $booking_exists_stmt = $conn->prepare("SELECT * FROM flight_bookings WHERE flight_id = ? AND user_id = ?");
  $booking_exists_stmt->bind_param("ii", $flight_id, $user_id);
  $booking_exists_stmt->execute();
  $booking_exists_result = $booking_exists_stmt->get_result();

  $already_booked = $booking_exists_result->num_rows > 0;
  if ($already_booked) {
    $booking_details = $booking_exists_result->fetch_assoc();
    $booked_seats_arr = json_decode($booking_details['seats'], true) ?? [];
    $booked_seats_str = implode(', ', $booked_seats_arr);
    $info_message = "You have already booked this flight. Your booking includes seat(s): " . htmlspecialchars($booked_seats_str);
  }
  $booking_exists_stmt->close();
} catch (Exception $e) {
  error_log("Booking check error: " . $e->getMessage());
  $already_booked = false;
}

// Fetch flight details
try {
  $stmt = $conn->prepare("SELECT * FROM flights WHERE id = ?");
  $stmt->bind_param("i", $flight_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $flight_details = $result->fetch_assoc();
    $seats_data = json_decode($flight_details['seats'], true) ?? [];

    // Fetch booked seats
    $booked_stmt = $conn->prepare("SELECT seats FROM flight_bookings WHERE flight_id = ?");
    $booked_stmt->bind_param("i", $flight_id);
    $booked_stmt->execute();
    $booked_result = $booked_stmt->get_result();

    while ($booked_row = $booked_result->fetch_assoc()) {
      $seats = json_decode($booked_row['seats'], true) ?? [];
      $booked_seats = array_merge($booked_seats, $seats);
    }
    $booked_stmt->close();
  } else {
    $error_message = "Flight not found.";
  }
  $stmt->close();
} catch (Exception $e) {
  error_log("Flight fetch error: " . $e->getMessage());
  $error_message = "Error fetching flight details.";
}

// Process booking form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_flight']) && !$already_booked) {
  // Validate CSRF token
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $error_message = "Invalid CSRF token.";
  } else {
    // Sanitize and validate inputs
    $passenger_name = filter_input(INPUT_POST, 'passenger_name', FILTER_SANITIZE_STRING);
    $passenger_email = filter_input(INPUT_POST, 'passenger_email', FILTER_VALIDATE_EMAIL);
    $passenger_phone = filter_input(INPUT_POST, 'passenger_phone', FILTER_SANITIZE_STRING);
    $selected_cabin = filter_input(INPUT_POST, 'cabin_class', FILTER_SANITIZE_STRING);
    $adult_count = filter_input(INPUT_POST, 'adult_count', FILTER_VALIDATE_INT) ?: 1;
    $children_count = filter_input(INPUT_POST, 'children_count', FILTER_VALIDATE_INT) ?: 0;
    $selected_seats = isset($_POST['selected_seats']) ? array_map('trim', (array)$_POST['selected_seats']) : [];

    // Validate form data
    if (empty($passenger_name) || !$passenger_email || empty($passenger_phone) || empty($selected_cabin)) {
      $error_message = "All passenger information fields are required.";
    } elseif (count($selected_seats) != ($adult_count + $children_count)) {
      $error_message = "Please select seats for all passengers.";
    } else {
      $conn->begin_transaction();
      try {
        // Validate seats
        $valid_seats = [];
        foreach ($seats_data as $cabin => $cabin_data) {
          $valid_seats = array_merge($valid_seats, $cabin_data['seat_ids']);
        }

        foreach ($selected_seats as $seat) {
          if (!in_array($seat, $valid_seats)) {
            throw new Exception("Invalid seat selection detected.");
          }
          if (in_array($seat, $booked_seats)) {
            throw new Exception("Some selected seats have been booked by another user. Please try again.");
          }
        }

        // Calculate price
        $prices = json_decode($flight_details['prices'], true);
        $cabin_price = isset($prices[$selected_cabin]) ? (float)$prices[$selected_cabin] : 0;

        // Assuming children get a 50% discount (adjust as needed)
        $adult_price = $cabin_price;
        $child_price = $cabin_price * 0.5; // 50% discount for children
        $total_price = ($adult_count * $adult_price) + ($children_count * $child_price);

        // Prepare booking data
        $seats_json = json_encode($selected_seats);
        $return_flight_data = $flight_details['return_flight_data'] ?? null;

        // Update the SQL query to set booking_status as 'pending'
        $booking_sql = "INSERT INTO flight_bookings (
                    flight_id, user_id, passenger_name, passenger_email, passenger_phone,
                    cabin_class, adult_count, children_count, seats, return_flight_data,
                    booking_date, price, booking_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, 'pending')";

        $booking_stmt = $conn->prepare($booking_sql);
        $booking_stmt->bind_param(
          "iissssiissd",
          $flight_id,
          $user_id,
          $passenger_name,
          $passenger_email,
          $passenger_phone,
          $selected_cabin,
          $adult_count,
          $children_count,
          $seats_json,
          $return_flight_data,
          $total_price
        );
        $booking_stmt->execute();
        $booking_stmt->close();

        $conn->commit();
        $success_message = "Flight booking submitted successfully! Your booking is pending confirmation.";
        header("Location: thankyou.php?flight_id=" . $flight_id);
        exit();
      } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error booking flight: " . $e->getMessage();
        error_log("Booking error: " . $e->getMessage());
      }
    }
  }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Helper functions
function calculateArrivalTime($departure_time, $flight_duration)
{
  if (empty($departure_time) || empty($flight_duration)) {
    return null;
  }

  try {
    $departure = new DateTime($departure_time);
    // Flight duration is in hours (e.g., "5" for 5 hours)
    $duration_hours = (float)$flight_duration;
    $departure->modify("+{$duration_hours} hours");
    return $departure->format('H:i:s');
  } catch (Exception $e) {
    error_log("Error calculating arrival time: " . $e->getMessage());
    return null;
  }
}

function formatDate($date)
{
  try {
    $dateObj = new DateTime($date);
    return $dateObj->format('D, M j, Y');
  } catch (Exception $e) {
    return $date;
  }
}

function formatTime($time)
{
  if (empty($time)) {
    return 'N/A';
  }

  try {
    $timeObj = new DateTime($time);
    return $timeObj->format('g:i A');
  } catch (Exception $e) {
    return $time;
  }
}

// Calculate arrival time and format dates/times
if ($flight_details) {
  $arrival_time = calculateArrivalTime($flight_details['departure_time'], $flight_details['flight_duration']);
  $flight_duration = $flight_details['flight_duration'] ? $flight_details['flight_duration'] . 'h' : 'N/A';
  $departure_date_formatted = formatDate($flight_details['departure_date']);
  $departure_time_formatted = formatTime($flight_details['departure_time']);
  $arrival_time_formatted = formatTime($arrival_time);
} else {
  $flight_duration = 'N/A';
  $departure_date_formatted = '';
  $departure_time_formatted = '';
  $arrival_time_formatted = '';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <style>
    .seat {
      background: #a48d8d;
      padding: 5px;
      cursor: pointer;
      color: white;
      border-radius: 2px;
    }

    .seat:hover {
      background-color: #462c2c;
    }

    /* Updated styles for the seat selection section */
    .seat-selection-section {
      @apply p-6 bg-gray-50 rounded-lg shadow-sm;
    }

    .seat {
      @apply w-14 h-14 m-2 flex items-center justify-center cursor-pointer border-2 rounded-xl transition-all duration-300 font-medium text-sm;
    }

    .seat.available {
      @apply border-blue-300 bg-blue-50 text-blue-700 hover:bg-blue-100 hover:border-blue-400;
    }

    .seat.selected {
      @apply border-blue-600 bg-blue-600 text-white shadow-lg transform scale-105;
    }

    .seat.booked {
      @apply border-gray-300 bg-gray-200 text-gray-500 cursor-not-allowed opacity-50 line-through;
    }

    .cabin-tab {
      @apply px-5 py-2 rounded-full cursor-pointer transition-all duration-200 font-medium text-sm;
    }

    .cabin-tab.active {
      @apply bg-blue-600 text-white shadow-md;
    }

    .cabin-tab:not(.active) {
      @apply bg-gray-200 text-gray-700 hover:bg-gray-300;
    }

    .seat-cabin {
      @apply mt-4 p-6 bg-white rounded-xl shadow-md;
    }

    .selected-seats-chips .chip {
      @apply px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm flex items-center space-x-2;
    }

    .selected-seats-chips .chip button {
      @apply text-blue-600 hover:text-blue-800;
    }

    .toast-notification {
      @apply fixed top-6 right-6 p-4 rounded-lg shadow-lg z-50 transition-transform duration-300;
    }

    .toast-notification.show {
      @apply translate-x-0;
    }
  </style>
</head>

<body class="bg-gray-100 min-h-screen py-6">
  <?php include 'includes/navbar.php'; ?>

  <div class="max-w-5xl mx-auto px-4">
    <!-- Booking Steps -->
    <div class="flex justify-between mb-8 relative">
      <div class="absolute top-4 left-12 right-12 h-0.5 bg-gray-200 z-0"></div>
      <?php
      $steps = ['Flight Details', 'Passenger Info', 'Seat Selection', 'Confirmation'];
      foreach ($steps as $index => $step):
      ?>
        <div class="booking-step flex flex-col items-center z-10 <?php echo $index === 0 ? 'active' : ''; ?>" id="step<?php echo $index + 1; ?>">
          <div class="step-number w-8 h-8 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center font-semibold mb-2 transition-all">
            <?php echo $index + 1; ?>
          </div>
          <div class="step-title text-sm text-gray-600"><?php echo $step; ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-24 right-4 z-50"></div>

    <!-- Messages -->
    <?php if ($error_message): ?>
      <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg mb-6">
        <p class="text-sm text-red-700"><?php echo htmlspecialchars($error_message); ?></p>
      </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
      <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg mb-6">
        <p class="text-sm text-green-700"><?php echo htmlspecialchars($success_message); ?></p>
      </div>
    <?php endif; ?>

    <?php if ($already_booked): ?>
      <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-lg mb-6">
        <p class="text-sm text-yellow-700"><?php echo htmlspecialchars($info_message); ?></p>
        <a href="my_bookings.php" class="text-sm text-yellow-700 underline hover:text-yellow-800 mt-2 inline-block">
          View your bookings
        </a>
      </div>
    <?php endif; ?>

    <?php if ($flight_details): ?>
      <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <!-- Step 1: Flight Details -->
        <div id="flight-details-section" class="p-6 border-b border-gray-200">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Flight Details</h2>
          <!-- Flight details display -->
          <div class="bg-white shadow-md rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 mr-2 text-blue-500">
                  <path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 012.25-2.25h16.5A2.25 2.25 0 0122.5 6v12a2.25 2.25 0 01-2.25 2.25H3.75A2.25 2.25 0 011.5 18V6zM4.5 7.5A.75.75 0 015.25 6.75h3.975a.75.75 0 01.75.75v1.5a.75.75 0 01-.75.75H5.25a.75.75 0 01-.75-.75v-1.5zm0 5.25a.75.75 0 01.75-.75h3.975a.75.75 0 01.75.75v1.5a.75.75 0 01-.75.75H5.25a.75.75 0 01-.75-.75v-1.5zm0 5.25a.75.75 0 01.75-.75h3.975a.75.75 0 01.75.75v1.5a.75.75 0 01-.75.75H5.25a.75.75 0 01-.75-.75v-1.5zM15 9.75a.75.75 0 01.75-.75h2.25a.75.75 0 010 1.5H15.75a.75.75 0 01-.75-.75zm.75 2.25a.75.75 0 00-.75.75v1.5a.75.75 0 00.75.75h2.25a.75.75 0 000-1.5H15.75v-1.5zm0 5.25a.75.75 0 01.75-.75h2.25a.75.75 0 010 1.5H15.75a.75.75 0 01-.75-.75v-1.5z" clip-rule="evenodd" />
                </svg>
                <div>
                  <p class="font-semibold text-lg"><?php echo htmlspecialchars($flight_details['airline_name'] ?? 'N/A'); ?></p>
                  <p class="text-sm text-gray-600">Flight #<?php echo htmlspecialchars($flight_details['flight_number']); ?></p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-sm text-gray-600"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 inline-block align-middle mr-1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>Departure: <?php echo htmlspecialchars($flight_details['departure_city']); ?>, <?php echo $departure_time_formatted; ?></p>
                <p class="text-sm text-gray-600"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 inline-block align-middle mr-1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>Arrival: <?php echo htmlspecialchars($flight_details['arrival_city']); ?>, <?php echo $arrival_time_formatted; ?></p>
                <p class="text-sm text-gray-600"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 inline-block align-middle mr-1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12.75 15l3-3m0 0l-3-3m3 3h-7.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>Duration: <?php echo $flight_duration; ?></p>
              </div>
            </div>
          </div>

          <div class="mt-6 flex justify-end">
            <button type="button" class="next-step px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors <?php echo $already_booked ? 'opacity-50 cursor-not-allowed' : ''; ?>"
              data-next="passenger-info-section" <?php echo $already_booked ? 'disabled' : ''; ?>>
              Continue to Passenger Information
            </button>
          </div>
        </div>

        <!-- Booking Form -->
        <form method="post" action="" id="booking-form" class="divide-y divide-gray-200">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

          <!-- Step 2: Passenger Information -->
          <div id="passenger-info-section" class="p-6 hidden">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Passenger Information</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="passenger_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                <input type="text" id="passenger_name" name="passenger_name" required
                  value="<?php echo htmlspecialchars($user_details['full_name'] ?? ''); ?>"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
              </div>

              <div>
                <label for="passenger_email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                <input type="email" id="passenger_email" name="passenger_email" required
                  value="<?php echo htmlspecialchars($user_details['email'] ?? ''); ?>"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
              </div>

              <div>
                <label for="passenger_phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                <input type="tel" id="passenger_phone" name="passenger_phone" required
                  value="<?php echo htmlspecialchars($user_details['phone_number'] ?? ''); ?>"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
              </div>

              <div>
                <label for="cabin_class" class="block text-sm font-medium text-gray-700 mb-2">Select Cabin Class</label>
                <select id="cabin_class" name="cabin_class" required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                  <?php
                  $prices = json_decode($flight_details['prices'] ?? '{}', true);
                  foreach ($prices as $class => $price):
                    $display_class = ucwords(str_replace('_', ' ', $class));
                  ?>
                    <option value="<?php echo htmlspecialchars($class); ?>" data-price="<?php echo htmlspecialchars($price); ?>">
                      <?php echo htmlspecialchars($display_class . " ($" . $price . ")"); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <!-- Passenger Counter -->
            <div class="mt-8">
              <h3 class="text-lg font-medium text-gray-800 mb-4">Number of Passengers</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="p-4 bg-gray-50 rounded-lg">
                  <label class="block text-sm font-medium text-gray-700 mb-3">Adults (12+ years)</label>
                  <div class="flex items-center space-x-2">
                    <button type="button" class="decrease-adults px-3 py-1 bg-gray-200 rounded">-</button>
                    <input type="number" name="adult_count" id="adult_count" value="1" min="1" max="8" readonly
                      class="w-16 text-center border border-gray-300 rounded-lg">
                    <button type="button" class="increase-adults px-3 py-1 bg-gray-200 rounded">+</button>
                  </div>
                </div>

                <div class="p-4 bg-gray-50 rounded-lg">
                  <label class="block text-sm font-medium text-gray-700 mb-3">Children (2-11 years)</label>
                  <div class="flex items-center space-x-2">
                    <button type="button" class="decrease-children px-3 py-1 bg-gray-200 rounded">-</button>
                    <input type="number" name="children_count" id="children_count" value="0" min="0" max="8" readonly
                      class="w-16 text-center border border-gray-300 rounded-lg">
                    <button type="button" class="increase-children px-3 py-1 bg-gray-200 rounded">+</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-8 flex justify-between">
              <button type="button" class="prev-step px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors" data-prev="flight-details-section">
                Back
              </button>
              <button type="button" class="next-step px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors" data-next="seat-selection-section">
                Continue to Seat Selection
              </button>
            </div>
          </div>

          <!-- Step 3: Seat Selection -->
          <div id="seat-selection-section" class="p-6 hidden seat-selection-section">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Seat Selection</h2>
            <p class="mb-4">Please select <span id="seats-to-select" class="font-bold">1</span> seat(s).</p>

            <div class="mb-6" id="cabin-tabs">
              <?php foreach (array_keys($seats_data) as $index => $cabin_class): ?>
                <div class="cabin-tab <?php echo $index === 0 ? 'active' : ''; ?>" data-cabin="<?php echo htmlspecialchars($cabin_class); ?>">
                  <?php echo ucwords(str_replace('_', ' ', $cabin_class)); ?> Cabin
                </div>
              <?php endforeach; ?>
            </div>

            <div id="seats-container">
              <?php foreach ($seats_data as $cabin_class => $cabin_data): ?>
                <div class="seat-cabin <?php echo $cabin_class !== array_key_first($seats_data) ? 'hidden' : ''; ?>" data-cabin="<?php echo htmlspecialchars($cabin_class); ?>">
                  <div class="flex flex-wrap gap-2 p-4 bg-white rounded-lg shadow-sm">
                    <?php foreach ($cabin_data['seat_ids'] as $seat_id):
                      $is_booked = in_array($seat_id, $booked_seats);
                      $seat_status = $is_booked ? 'booked' : 'available';
                    ?>
                      <div class="seat <?php echo $seat_status; ?>"
                        data-seat-id="<?php echo htmlspecialchars($seat_id); ?>"
                        data-cabin-class="<?php echo htmlspecialchars($cabin_class); ?>"
                        <?php echo $is_booked ? 'disabled' : ''; ?>>
                        <?php echo htmlspecialchars($seat_id); ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <div class="mt-6">
              <h4 class="font-medium text-gray-800 mb-2">Selected Seats</h4>
              <div id="selected-seats-chips" class="flex flex-wrap gap-2 selected-seats-chips">
                <span id="no-seats-selected" class="text-gray-500">No seats selected yet</span>
              </div>
              <div id="selected-seats-inputs"></div>
            </div>

            <div class="mt-8 flex justify-between">
              <button type="button" class="prev-step px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors" data-prev="passenger-info-section">
                Back
              </button>
              <button type="button" class="next-step px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors" data-next="confirmation-section">
                Continue to Confirmation
              </button>
            </div>
          </div>

          <!-- Step 4: Confirmation -->
          <div id="confirmation-section" class="p-6 hidden">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Booking Confirmation</h2>

            <div class="p-6 bg-gray-50 rounded-lg">
              <h3 class="text-lg font-medium text-gray-800 mb-4">Flight Summary</h3>
              <div class="bg-white rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <p class="text-sm text-gray-500">From</p>
                    <p class="font-medium"><?php echo htmlspecialchars($flight_details['departure_city']); ?></p>
                    <p class="text-sm"><?php echo $departure_time_formatted; ?>, <?php echo $departure_date_formatted; ?></p>
                  </div>
                  <div class="text-center">
                    <p class="text-sm text-gray-500">Duration</p>
                    <p><?php echo $flight_duration; ?></p>
                  </div>
                  <div class="text-right">
                    <p class="text-sm text-gray-500">To</p>
                    <p class="font-medium"><?php echo htmlspecialchars($flight_details['arrival_city']); ?></p>
                    <p class="text-sm"><?php echo $arrival_time_formatted; ?>, <?php echo $departure_date_formatted; ?></p>
                  </div>
                </div>
              </div>

              <h3 class="text-lg font-medium text-gray-800 mt-6 mb-4">Passenger Information</h3>
              <div class="bg-white rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <p class="text-sm text-gray-500">Adults</p>
                    <p class="font-medium" id="summary-adult-count"><?php echo htmlspecialchars($adult_count ?? '1'); ?></p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Children</p>
                    <p class="font-medium" id="summary-children-count"><?php echo htmlspecialchars($children_count ?? '0'); ?></p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Selected Seats</p>
                    <p class="font-medium" id="summary-selected-seats">-</p>
                  </div>
                </div>
              </div>

              <!-- Cost Summary -->
              <h3 class="text-lg font-medium text-gray-800 mt-6 mb-4">Cost Summary</h3>
              <div class="bg-white rounded-lg p-4">
                <div class="grid grid-cols-1 gap-2">
                  <div class="flex justify-between">
                    <p class="text-sm text-gray-500">Base Price per Adult (Cabin: <span id="summary-cabin-class-name">-</span>)</p>
                    <p class="font-medium" id="summary-adult-price">-</p>
                  </div>
                  <div class="flex justify-between">
                    <p class="text-sm text-gray-500">Base Price per Child (50% discount)</p>
                    <p class="font-medium" id="summary-child-price">-</p>
                  </div>
                  <div class="flex justify-between">
                    <p class="text-sm text-gray-500">Adults (<span id="summary-adult-count-cost"><?php echo htmlspecialchars($adult_count ?? '1'); ?></span> x <span id="summary-adult-price-unit">-</span>)</p>
                    <p class="font-medium" id="summary-adult-total">-</p>
                  </div>
                  <div class="flex justify-between">
                    <p class="text-sm text-gray-500">Children (<span id="summary-children-count-cost"><?php echo htmlspecialchars($children_count ?? '0'); ?></span> x <span id="summary-child-price-unit">-</span>)</p>
                    <p class="font-medium" id="summary-children-total">-</p>
                  </div>
                  <div class="flex justify-between border-t pt-2 mt-2">
                    <p class="text-sm font-semibold text-gray-700">Total Cost</p>
                    <p class="font-semibold text-green-600" id="summary-total-cost">-</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-8 flex justify-between">
              <button type="button" class="prev-step px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors" data-prev="seat-selection-section">
                Back
              </button>
              <button type="submit" name="book_flight"
                class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors <?php echo $already_booked ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                <?php echo $already_booked ? 'disabled' : ''; ?>>
                Confirm and Pay
              </button>
            </div>
          </div>
        </form>
      </div>
    <?php else: ?>
      <div class="text-center p-12 bg-white rounded-xl shadow-md">
        <p class="text-red-600 font-bold text-xl mb-2">Flight not found</p>
        <a href="flights.php" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
          Return to Flight Search
        </a>
      </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script>
    // Pass PHP data to JavaScript
    const seatsData = <?php echo json_encode($seats_data); ?>;
    const bookedSeats = <?php echo json_encode($booked_seats); ?>;
    const alreadyBooked = <?php echo json_encode($already_booked); ?>;

    document.addEventListener('DOMContentLoaded', function() {
      const steps = ['step1', 'step2', 'step3', 'step4'];
      const sections = ['flight-details-section', 'passenger-info-section', 'seat-selection-section', 'confirmation-section'];
      let currentStep = 0;

      // Elements
      const adultCount = document.getElementById('adult_count');
      const childrenCount = document.getElementById('children_count');
      const seatsToSelect = document.getElementById('seats-to-select');
      const cabinTabsContainer = document.getElementById('cabin-tabs');
      const cabinTabs = document.querySelectorAll('.cabin-tab');
      const seatCabins = document.querySelectorAll('.seat-cabin');
      const availableSeats = document.querySelectorAll('.seat.available');
      const selectedSeatsInputs = document.getElementById('selected-seats-inputs');
      const selectedSeatsChips = document.getElementById('selected-seats-chips');
      const noSeatsSelected = document.getElementById('no-seats-selected');
      const passengerNameInput = document.getElementById('passenger_name');
      const passengerEmailInput = document.getElementById('passenger_email');
      const passengerPhoneInput = document.getElementById('passenger_phone');
      const cabinClassSelect = document.getElementById('cabin_class');
      const summaryAdultCount = document.getElementById('summary-adult-count');
      const summaryChildrenCount = document.getElementById('summary-children-count');
      const summarySelectedSeats = document.getElementById('summary-selected-seats');
      const summaryAdultPrice = document.getElementById('summary-adult-price');
      const summaryChildPrice = document.getElementById('summary-child-price');
      const summaryAdultTotal = document.getElementById('summary-adult-total');
      const summaryChildrenTotal = document.getElementById('summary-children-total');
      const summaryTotalCost = document.getElementById('summary-total-cost');
      const summaryAdultCountCost = document.getElementById('summary-adult-count-cost');
      const summaryChildrenCountCost = document.getElementById('summary-children-count-cost');
      const summaryAdultPriceUnit = document.getElementById('summary-adult-price-unit');
      const summaryChildPriceUnit = document.getElementById('summary-child-price-unit');
      const summaryCabinClassName = document.getElementById('summary-cabin-class-name');

      let selectedSeats = [];

      function showStep(stepIndex) {
        if (stepIndex === currentStep) return;

        const currentSection = document.getElementById(sections[currentStep]);
        const nextSection = document.getElementById(sections[stepIndex]);

        currentSection.classList.add('hidden');
        nextSection.classList.remove('hidden');

        steps.forEach((step, index) => {
          const stepElement = document.getElementById(step);
          stepElement.classList.remove('active');
          if (index < stepIndex) stepElement.classList.add('completed');
          if (index === stepIndex) stepElement.classList.add('active');
        });

        currentStep = stepIndex;
        window.scrollTo({
          top: 0,
          behavior: 'smooth'
        });

        if (stepIndex === 2) {
          filterSeatsByCabinClass();
          if (!validatePassengerCountAgainstSeats()) return;
        }

        if (stepIndex === 3) {
          updateConfirmationDetails();
        }
      }

      function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast-notification px-4 py-3 rounded-lg shadow-lg text-white transform transition-transform duration-300 ease-in-out translate-x-full`;
        toast.classList.add(type === 'error' ? 'bg-red-500' : type === 'success' ? 'bg-green-500' : 'bg-blue-500');
        toast.innerHTML = `
        <div class="flex items-center">
          <span>${message}</span>
          <button class="ml-4" onclick="this.parentNode.parentNode.remove()">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>
      `;

        document.getElementById('toast-container').appendChild(toast);
        setTimeout(() => toast.classList.remove('translate-x-full'), 10);
        setTimeout(() => {
          toast.classList.add('translate-x-full');
          setTimeout(() => toast.remove(), 300);
        }, 4000);
      }

      // Navigation
      document.querySelectorAll('.next-step').forEach(button => {
        button.addEventListener('click', function() {
          if (alreadyBooked) {
            showToast('You have already booked this flight.', 'error');
            return;
          }

          const nextSection = this.dataset.next;
          const nextIndex = sections.indexOf(nextSection);

          if (nextIndex === 2 && !validatePassengerInfo()) return;
          if (nextIndex === 2 && !validatePassengerCountAgainstSeats()) {
            showStep(1); // Stay on passenger info step
            return;
          }
          if (nextIndex === 3 && !validateSeatSelection()) return;

          showStep(nextIndex);
        });
      });

      document.querySelectorAll('.prev-step').forEach(button => {
        button.addEventListener('click', function() {
          const prevSection = this.dataset.prev;
          showStep(sections.indexOf(prevSection));
        });
      });

      // Passenger counter
      document.querySelector('.increase-adults')?.addEventListener('click', () => {
        if (parseInt(adultCount.value) < 8) {
          adultCount.value = parseInt(adultCount.value) + 1;
          updateSeatsToSelect();
          resetSeatSelections();
          validatePassengerCountAgainstSeats();
          updateConfirmationDetails();
        }
      });

      document.querySelector('.decrease-adults')?.addEventListener('click', () => {
        if (parseInt(adultCount.value) > 1) {
          adultCount.value = parseInt(adultCount.value) - 1;
          updateSeatsToSelect();
          resetSeatSelections();
          validatePassengerCountAgainstSeats();
          updateConfirmationDetails();
        }
      });

      document.querySelector('.increase-children')?.addEventListener('click', () => {
        if (parseInt(childrenCount.value) < 8) {
          childrenCount.value = parseInt(childrenCount.value) + 1;
          updateSeatsToSelect();
          resetSeatSelections();
          validatePassengerCountAgainstSeats();
          updateConfirmationDetails();
        }
      });

      document.querySelector('.decrease-children')?.addEventListener('click', () => {
        if (parseInt(childrenCount.value) > 0) {
          childrenCount.value = parseInt(childrenCount.value) - 1;
          updateSeatsToSelect();
          resetSeatSelections();
          validatePassengerCountAgainstSeats();
          updateConfirmationDetails();
        }
      });

      function updateSeatsToSelect() {
        const total = parseInt(adultCount.value) + parseInt(childrenCount.value);
        seatsToSelect.textContent = total;
      }

      function resetSeatSelections() {
        selectedSeats = [];
        document.querySelectorAll('.seat.selected').forEach(seat => seat.classList.remove('selected'));
        updateSelectedSeatsUI();
        showToast('Seat selection reset due to passenger count change.', 'info');
      }

      function validatePassengerInfo() {
        const isValid = [
          passengerNameInput.value.trim(),
          isValidEmail(passengerEmailInput.value),
          passengerPhoneInput.value.trim()
        ].every(Boolean);

        if (!isValid) {
          showToast('Please fill in all passenger information correctly.', 'error');
        }

        return isValid;
      }

      function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
      }

      function getAvailableSeatsForCabin(cabinClass) {
        if (!seatsData[cabinClass]) return 0;
        const totalSeats = seatsData[cabinClass].seat_ids.length;
        const bookedInCabin = bookedSeats.filter(seat => seatsData[cabinClass].seat_ids.includes(seat)).length;
        return totalSeats - bookedInCabin;
      }

      function validatePassengerCountAgainstSeats() {
        const selectedCabinClass = cabinClassSelect.value;
        const totalPassengers = parseInt(adultCount.value) + parseInt(childrenCount.value);
        const availableSeats = getAvailableSeatsForCabin(selectedCabinClass);

        if (totalPassengers > availableSeats) {
          showToast(
            `Only ${availableSeats} seat(s) available in ${selectedCabinClass.replace('_', ' ').toUpperCase()} cabin. Please adjust the number of passengers.`,
            'error'
          );
          return false;
        }
        return true;
      }

      function validateSeatSelection() {
        const requiredSeats = parseInt(adultCount.value) + parseInt(childrenCount.value);
        if (selectedSeats.length !== requiredSeats) {
          showToast(`Please select exactly ${requiredSeats} seat(s).`, 'error');
          return false;
        }

        const selectedCabinClass = cabinClassSelect.value;
        const allSeatsValid = selectedSeats.every(seatId => {
          const seat = document.querySelector(`.seat[data-seat-id="${seatId}"]`);
          return seat && seat.dataset.cabinClass === selectedCabinClass;
        });

        if (!allSeatsValid) {
          showToast('Selected seats must match the chosen cabin class.', 'error');
          return false;
        }

        return true;
      }

      function filterSeatsByCabinClass() {
        const selectedCabinClass = cabinClassSelect.value;

        cabinTabs.forEach(tab => {
          tab.classList.add('hidden');
          tab.classList.remove('active');
        });
        seatCabins.forEach(cabin => {
          cabin.classList.add('hidden');
        });

        const selectedTab = document.querySelector(`.cabin-tab[data-cabin="${selectedCabinClass}"]`);
        const selectedCabin = document.querySelector(`.seat-cabin[data-cabin="${selectedCabinClass}"]`);

        if (selectedTab && selectedCabin) {
          selectedTab.classList.remove('hidden');
          selectedTab.classList.add('active');
          selectedCabin.classList.remove('hidden');
        } else {
          showToast('No seats available for the selected cabin class.', 'error');
        }

        resetSeatSelections();
      }

      availableSeats.forEach(seat => {
        seat.addEventListener('click', function() {
          if (alreadyBooked) return;

          const seatId = this.dataset.seatId;
          const requiredSeats = parseInt(adultCount.value) + parseInt(childrenCount.value);

          if (this.classList.contains('selected')) {
            this.classList.remove('selected');
            selectedSeats = selectedSeats.filter(id => id !== seatId);
          } else if (selectedSeats.length < requiredSeats) {
            this.classList.add('selected');
            selectedSeats.push(seatId);
          } else {
            showToast('You have selected the maximum number of seats.', 'error');
          }

          updateSelectedSeatsUI();
        });
      });

      function updateSelectedSeatsUI() {
        selectedSeatsInputs.innerHTML = selectedSeats.map(id =>
          `<input type="hidden" name="selected_seats[]" value="${id}">`
        ).join('');

        selectedSeatsChips.innerHTML = selectedSeats.length > 0 ?
          selectedSeats.map(id => `
          <div class="chip">
            ${id}
            <button type="button" data-seat-id="${id}">
              <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
        `).join('') :
          '<span id="no-seats-selected" class="text-gray-500">No seats selected yet</span>';

        selectedSeatsChips.querySelectorAll('button').forEach(btn => {
          btn.addEventListener('click', function() {
            const seatId = this.dataset.seatId;
            const seat = document.querySelector(`.seat[data-seat-id="${seatId}"]`);
            seat.classList.remove('selected');
            selectedSeats = selectedSeats.filter(id => id !== seatId);
            updateSelectedSeatsUI();
          });
        });

        summarySelectedSeats.textContent = selectedSeats.join(', ') || '-';
      }

      function updateConfirmationDetails() {
        const adults = parseInt(adultCount.value);
        const children = parseInt(childrenCount.value);
        const selectedCabinClass = cabinClassSelect.value;
        const cabinPrice = parseFloat(cabinClassSelect.options[cabinClassSelect.selectedIndex].dataset.price);

        summaryAdultCount.textContent = adults;
        summaryChildrenCount.textContent = children;
        summaryAdultCountCost.textContent = adults;
        summaryChildrenCountCost.textContent = children;

        const adultPrice = cabinPrice;
        const childPrice = cabinPrice * 0.5;
        const adultTotal = adultPrice * adults;
        const childTotal = childPrice * children;
        const totalCost = adultTotal + childTotal;

        summaryCabinClassName.textContent = selectedCabinClass.charAt(0).toUpperCase() + selectedCabinClass.slice(1).replace('_', ' ');
        summaryAdultPrice.textContent = `$${adultPrice.toFixed(2)}`;
        summaryChildPrice.textContent = `$${childPrice.toFixed(2)}`;
        summaryAdultTotal.textContent = `$${adultTotal.toFixed(2)}`;
        summaryChildrenTotal.textContent = `$${childTotal.toFixed(2)}`;
        summaryTotalCost.textContent = `$${totalCost.toFixed(2)}`;
        summaryAdultPriceUnit.textContent = `$${adultPrice.toFixed(2)}`;
        summaryChildPriceUnit.textContent = `$${childPrice.toFixed(2)}`;
      }

      cabinClassSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (currentStep === 2 || currentStep === 3) {
          filterSeatsByCabinClass();
          validatePassengerCountAgainstSeats();
        }
        if (currentStep === 3) updateConfirmationDetails();
      });

      adultCount.addEventListener('input', function() {
        if (currentStep === 3) updateConfirmationDetails();
      });

      childrenCount.addEventListener('input', function() {
        if (currentStep === 3) updateConfirmationDetails();
      });

      $(document).ready(function() {
        $('#cabin_class').select2({
          width: '100%'
        }).on('change', function() {
          const selectedOption = this.options[this.selectedIndex];
          if (currentStep === 2 || currentStep === 3) {
            filterSeatsByCabinClass();
            validatePassengerCountAgainstSeats();
          }
          if (currentStep === 3) updateConfirmationDetails();
        });
      });

      updateSeatsToSelect();
    });
  </script>
</body>

</html>