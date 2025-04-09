<?php
require_once 'connection/connection.php';

// Initialize variables
$flight_id = '';
$flight = null;
$users = [];
$seat_info = [];
$available_classes = [];
$prices = [];

// Check if flight ID is provided
if (isset($_GET['flight_id']) && !empty($_GET['flight_id'])) {
  $flight_id = intval($_GET['flight_id']);

  // Fetch flight details
  $sql = "SELECT * FROM flights WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $flight_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $flight = $result->fetch_assoc();

    // Get seat information
    if (isset($flight['seats']) && !empty($flight['seats'])) {
      $seats = json_decode($flight['seats'], true);

      if ($seats) {
        foreach ($seats as $class => $data) {
          // Get booked seats for this class
          $book_sql = "SELECT COUNT(*) as booked FROM flight_bookings WHERE flight_id = ? AND cabin_class = ?";
          $book_stmt = $conn->prepare($book_sql);
          $book_stmt->bind_param("is", $flight_id, $class);
          $book_stmt->execute();
          $book_result = $book_stmt->get_result();
          $booked = $book_result->fetch_assoc()['booked'];
          $book_stmt->close();

          $total = $data['count'] ?? 0;
          $available = $total - $booked;

          if ($available > 0) {
            $available_classes[$class] = $available;
          }
        }
      }
    }

    // Get prices
    if (isset($flight['prices']) && !empty($flight['prices'])) {
      $prices = json_decode($flight['prices'], true);
    }
  }
  $stmt->close();
}

// Fetch all users for selection
$user_sql = "SELECT id, full_name, email, phone_number FROM users ORDER BY full_name";
$user_result = $conn->query($user_sql);
if ($user_result && $user_result->num_rows > 0) {
  while ($row = $user_result->fetch_assoc()) {
    $users[] = $row;
  }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_booking'])) {
  // Validate the form data
  $user_id = intval($_POST['user_id']);
  $cabin_class = $_POST['cabin_class'];
  $passenger_count = intval($_POST['passenger_count']);
  $booking_date = date('Y-m-d H:i:s');
  $booking_status = $_POST['booking_status'];
  // Note: payment_status is not in the flight_bookings table
  // but we'll keep the form field for potential future use
  $payment_status = $_POST['payment_status'] ?? 'pending';
  $special_requests = $_POST['special_requests'] ?? '';

  // Calculate ticket price
  $ticket_price = 0;
  if (isset($prices[$cabin_class])) {
    $ticket_price = $prices[$cabin_class];
  }

  // Calculate total price (ticket price * passenger count)
  $total_price = $ticket_price * $passenger_count;

  // Get passenger details if provided
  $passenger_details = [];

  if ($passenger_count > 1 && isset($_POST['passenger_name']) && is_array($_POST['passenger_name'])) {
    for ($i = 0; $i < count($_POST['passenger_name']); $i++) {
      if (!empty($_POST['passenger_name'][$i])) {
        $passenger = [
          'name' => $_POST['passenger_name'][$i],
          'age' => $_POST['passenger_age'][$i] ?? '',
          'gender' => $_POST['passenger_gender'][$i] ?? '',
          'document_id' => $_POST['passenger_document'][$i] ?? '',
          'special_requirements' => $_POST['passenger_requirements'][$i] ?? ''
        ];
        $passenger_details[] = $passenger;
      }
    }
  }

  // Get user information for passenger details
  $user_sql = "SELECT full_name, email, phone_number FROM users WHERE id = ?";
  $user_stmt = $conn->prepare($user_sql);
  $user_stmt->bind_param("i", $user_id);
  $user_stmt->execute();
  $user_result = $user_stmt->get_result();
  $user_data = $user_result->fetch_assoc();
  $user_stmt->close();

  $passenger_name = $user_data['full_name'];
  $passenger_email = $user_data['email'];
  $passenger_phone = $user_data['phone_number'];

  // Store additional passenger details as JSON in seats field
  $seats_data = [
    'passengers' => $passenger_details,
    'count' => $passenger_count,
    'special_requests' => $special_requests
  ];
  $seats_json = json_encode($seats_data);

  // Insert booking data into database
  $insert_sql = "INSERT INTO flight_bookings (
    user_id, flight_id, passenger_name, passenger_email, passenger_phone,
    cabin_class, adult_count, seats, booking_date, booking_status, price
  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

  $stmt = $conn->prepare($insert_sql);

  $stmt->bind_param(
    "iissssisssd",
    $user_id,
    $flight_id,
    $passenger_name,
    $passenger_email,
    $passenger_phone,
    $cabin_class,
    $passenger_count,
    $seats_json,
    $booking_date,
    $booking_status,
    $total_price
  );

  if ($stmt->execute()) {
    $booking_id = $stmt->insert_id;
    $success_message = "Booking created successfully!";

    // Redirect to the booking details page
    header("Location: flight-booking-details.php?id=" . $booking_id . "&success=1");
    exit;
  } else {
    $error_message = "Error creating booking: " . $stmt->error;
  }

  $stmt->close();
}

// Format departure and arrival dates
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <style>
    .page-header {
      background-image: linear-gradient(to right, #0891b2, #0e7490);
    }

    .form-section {
      transition: all 0.3s ease;
    }

    .form-section:hover {
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .passenger-card {
      transition: all 0.3s ease;
    }

    .passenger-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
    }

    @media (max-width: 768px) {
      .flight-info {
        flex-direction: column;
      }

      .flight-info>div {
        width: 100%;
        margin-bottom: 1rem;
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
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <div class="flex items-center">
          <button class="md:hidden text-gray-800 mr-4" id="menu-btn">
            <i class="fas fa-bars"></i>
          </button>
          <h1 class="text-xl font-semibold">
            <i class="text-teal-600 fa fa-plus-circle mr-2"></i> Create Flight Booking
          </h1>
        </div>
        <div class="flex space-x-3">
          <button onclick="window.history.back()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back
          </button>
        </div>
      </div>

      <div class="flex-1 overflow-auto p-4 md:p-6">
        <?php if (!$flight): ?>
          <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded">
            <div class="flex">
              <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle"></i>
              </div>
              <div class="ml-3">
                <p class="text-sm">No flight selected or invalid flight ID.</p>
                <div class="mt-3">
                  <a href="manage-flights.php" class="text-yellow-700 underline font-medium">
                    Go to Flights Management
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php elseif (empty($available_classes)): ?>
          <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded">
            <div class="flex">
              <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle"></i>
              </div>
              <div class="ml-3">
                <p class="text-sm">This flight has no available seats in any class.</p>
                <div class="mt-3">
                  <a href="flight-details.php?id=<?php echo $flight_id; ?>" class="text-yellow-700 underline font-medium">
                    View Flight Details
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php else: ?>
          <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
              <p><i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_message; ?></p>
            </div>
          <?php endif; ?>

          <!-- Flight Information Header -->
          <div class="page-header bg-gradient-to-r from-cyan-600 to-teal-700 rounded-xl shadow-lg p-5 mb-6 text-white">
            <h2 class="text-2xl font-bold mb-3">Flight Information</h2>
            <div class="flight-info flex flex-wrap gap-6">
              <div>
                <p class="text-white/70 text-sm">Flight</p>
                <p class="text-xl font-semibold">
                  <?php echo htmlspecialchars($flight['airline_name'] ?? 'Airline'); ?> -
                  #<?php echo htmlspecialchars($flight['flight_number'] ?? 'N/A'); ?>
                </p>
              </div>
              <div>
                <p class="text-white/70 text-sm">Route</p>
                <p class="text-xl font-semibold">
                  <?php echo htmlspecialchars($flight['departure_city'] ?? 'Origin'); ?> to
                  <?php echo htmlspecialchars($flight['arrival_city'] ?? 'Destination'); ?>
                </p>
              </div>
              <div>
                <p class="text-white/70 text-sm">Departure</p>
                <p class="text-xl font-semibold"><?php echo $formatted_departure_date; ?></p>
              </div>
              <div>
                <p class="text-white/70 text-sm">Arrival</p>
                <p class="text-xl font-semibold"><?php echo $formatted_arrival_date; ?></p>
              </div>
            </div>
          </div>

          <!-- Booking Form -->
          <form method="POST" id="bookingForm">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
              <!-- Main Form (3 columns) -->
              <div class="md:col-span-3 space-y-6">
                <!-- Passenger Details -->
                <div class="form-section bg-white rounded-xl shadow-md p-5">
                  <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-user-circle text-teal-600 mr-2"></i> Passenger Details
                  </h3>

                  <div class="mb-4">
                    <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">Select Passenger</label>
                    <select id="user_id" name="user_id" required class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring focus:ring-teal-200">
                      <option value="">-- Select Passenger --</option>
                      <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>">
                          <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <p class="text-gray-500 text-xs mt-1">Primary passenger who is making the booking</p>
                  </div>

                  <div class="mb-4">
                    <label for="passenger_count" class="block text-sm font-medium text-gray-700 mb-1">Number of Passengers</label>
                    <input type="number" id="passenger_count" name="passenger_count" min="1" max="9" value="1" required
                      class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring focus:ring-teal-200">
                    <p class="text-gray-500 text-xs mt-1">Include the primary passenger in this count</p>
                  </div>

                  <div id="additional_passengers" class="space-y-4 mt-6">
                    <!-- Additional passenger forms will be added here dynamically -->
                  </div>
                </div>

                <!-- Booking Details -->
                <div class="form-section bg-white rounded-xl shadow-md p-5">
                  <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-ticket-alt text-teal-600 mr-2"></i> Booking Details
                  </h3>

                  <div class="mb-4">
                    <label for="cabin_class" class="block text-sm font-medium text-gray-700 mb-1">Cabin Class</label>
                    <select id="cabin_class" name="cabin_class" required
                      class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring focus:ring-teal-200">
                      <option value="">-- Select Class --</option>
                      <?php foreach ($available_classes as $class => $available): ?>
                        <option value="<?php echo $class; ?>" data-price="<?php echo $prices[$class] ?? 0; ?>">
                          <?php echo ucfirst($class); ?> Class (<?php echo $available; ?> seats available) -
                          $<?php echo number_format((float)($prices[$class] ?? 0), 2); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="mb-4">
                    <label for="booking_status" class="block text-sm font-medium text-gray-700 mb-1">Booking Status</label>
                    <select id="booking_status" name="booking_status" required
                      class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring focus:ring-teal-200">
                      <option value="pending">Pending</option>
                      <option value="confirmed">Confirmed</option>
                      <option value="completed">Completed</option>
                      <option value="cancelled">Cancelled</option>
                    </select>
                  </div>

                  <div class="mb-4">
                    <label for="payment_status" class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
                    <select id="payment_status" name="payment_status" required
                      class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring focus:ring-teal-200">
                      <option value="unpaid">Unpaid</option>
                      <option value="paid">Paid</option>
                      <option value="refunded">Refunded</option>
                    </select>
                  </div>

                  <div class="mb-4">
                    <label for="special_requests" class="block text-sm font-medium text-gray-700 mb-1">Special Requests</label>
                    <textarea id="special_requests" name="special_requests" rows="3"
                      class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring focus:ring-teal-200"
                      placeholder="Any special requests or requirements..."></textarea>
                  </div>
                </div>
              </div>

              <!-- Sidebar (2 columns) -->
              <div class="md:col-span-2 space-y-6">
                <!-- Price Summary -->
                <div class="bg-white rounded-xl shadow-md p-5 sticky top-4">
                  <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-receipt text-teal-600 mr-2"></i> Price Summary
                  </h3>

                  <div class="space-y-3 mb-4">
                    <div class="flex justify-between">
                      <span class="text-gray-600">Ticket Price:</span>
                      <span class="font-medium text-gray-800" id="ticket_price">$0.00</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600">Number of Passengers:</span>
                      <span class="font-medium text-gray-800" id="passenger_count_display">1</span>
                    </div>
                    <div class="flex justify-between pt-3 border-t border-gray-100">
                      <span class="text-gray-800 font-semibold">Total Price:</span>
                      <span class="font-bold text-teal-600 text-xl" id="total_price">$0.00</span>
                    </div>
                  </div>

                  <button type="submit" name="create_booking"
                    class="w-full bg-teal-600 hover:bg-teal-700 text-white py-3 px-4 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle mr-2"></i> Create Booking
                  </button>
                </div>

                <!-- Flight Information -->
                <div class="bg-white rounded-xl shadow-md p-5">
                  <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-info-circle text-teal-600 mr-2"></i> Flight Information
                  </h3>

                  <div class="space-y-3">
                    <div>
                      <p class="text-sm text-gray-500">Flight Number</p>
                      <p class="font-medium text-gray-800">
                        <?php echo htmlspecialchars($flight['flight_number'] ?? 'N/A'); ?>
                      </p>
                    </div>
                    <div>
                      <p class="text-sm text-gray-500">Airline</p>
                      <p class="font-medium text-gray-800">
                        <?php echo htmlspecialchars($flight['airline_name'] ?? 'N/A'); ?>
                      </p>
                    </div>
                    <div>
                      <p class="text-sm text-gray-500">Aircraft Type</p>
                      <p class="font-medium text-gray-800">
                        <?php echo htmlspecialchars($flight['aircraft_type'] ?? 'N/A'); ?>
                      </p>
                    </div>

                    <?php
                    $baggage_info = '';
                    if (isset($flight['baggage_allowance']) && !empty($flight['baggage_allowance'])) {
                      $baggage_info = is_string($flight['baggage_allowance']) ?
                        $flight['baggage_allowance'] : json_encode($flight['baggage_allowance']);
                    }
                    if (!empty($baggage_info)):
                    ?>
                      <div class="pt-3 border-t border-gray-100">
                        <p class="text-sm text-gray-500">Baggage Allowance</p>
                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($baggage_info); ?></p>
                      </div>
                    <?php endif; ?>

                    <?php if (isset($flight['cancellation_policy']) && !empty($flight['cancellation_policy'])): ?>
                      <div class="pt-3 border-t border-gray-100">
                        <p class="text-sm text-gray-500">Cancellation Policy</p>
                        <p class="font-medium text-gray-800">
                          <?php echo htmlspecialchars($flight['cancellation_policy']); ?>
                        </p>
                      </div>
                    <?php endif; ?>
                  </div>

                  <div class="mt-4 text-center">
                    <a href="flight-details.php?id=<?php echo $flight_id; ?>"
                      class="text-teal-600 hover:text-teal-800 text-sm font-medium">
                      View Complete Flight Details <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </form>
        <?php endif; ?>
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

      // Handle passenger count changes
      const passengerCountInput = document.getElementById('passenger_count');
      const additionalPassengersContainer = document.getElementById('additional_passengers');
      const passengerCountDisplay = document.getElementById('passenger_count_display');

      if (passengerCountInput && additionalPassengersContainer) {
        passengerCountInput.addEventListener('change', function() {
          const count = parseInt(this.value) || 1;

          // Update passenger count display
          if (passengerCountDisplay) {
            passengerCountDisplay.textContent = count;
          }

          // Generate additional passenger forms
          renderAdditionalPassengers(count);

          // Update total price
          updateTotalPrice();
        });
      }

      // Handle class selection (price updates)
      const cabinClassSelect = document.getElementById('cabin_class');
      const ticketPriceDisplay = document.getElementById('ticket_price');
      const totalPriceDisplay = document.getElementById('total_price');

      if (cabinClassSelect && ticketPriceDisplay && totalPriceDisplay) {
        cabinClassSelect.addEventListener('change', function() {
          updateTotalPrice();
        });
      }

      // Function to update price displays
      function updateTotalPrice() {
        const selectedOption = cabinClassSelect.options[cabinClassSelect.selectedIndex];
        const price = selectedOption ? parseFloat(selectedOption.dataset.price) || 0 : 0;
        const passengerCount = parseInt(passengerCountInput.value) || 1;

        const totalPrice = price * passengerCount;

        ticketPriceDisplay.textContent = '$' + price.toFixed(2);
        totalPriceDisplay.textContent = '$' + totalPrice.toFixed(2);
      }

      // Function to render additional passenger forms
      function renderAdditionalPassengers(count) {
        // Clear existing passenger forms
        additionalPassengersContainer.innerHTML = '';

        // If there's only one passenger (the primary one), don't add additional forms
        if (count <= 1) {
          return;
        }

        // Add a heading
        const heading = document.createElement('h4');
        heading.className = 'text-lg font-medium text-gray-800 mb-3';
        heading.textContent = 'Additional Passengers';
        additionalPassengersContainer.appendChild(heading);

        // Create forms for additional passengers (excluding the primary one)
        for (let i = 1; i < count; i++) {
          const passengerCard = document.createElement('div');
          passengerCard.className = 'passenger-card bg-gray-50 rounded-lg p-4 border border-gray-200';

          passengerCard.innerHTML = `
            <h5 class="font-medium text-gray-700 mb-3">Passenger #${i + 1}</h5>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" name="passenger_name[]" 
                       class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring focus:ring-teal-200"
                       placeholder="Full Name" required>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Age</label>
                <input type="number" name="passenger_age[]" min="0" max="120"
                       class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring focus:ring-teal-200"
                       placeholder="Age">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                <select name="passenger_gender[]" 
                        class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring focus:ring-teal-200">
                  <option value="">-- Select Gender --</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Passport/ID</label>
                <input type="text" name="passenger_document[]" 
                       class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring focus:ring-teal-200"
                       placeholder="Passport or ID Number">
              </div>
              
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Special Requirements</label>
                <input type="text" name="passenger_requirements[]" 
                       class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring focus:ring-teal-200"
                       placeholder="Any special requirements or assistance needed">
              </div>
            </div>
          `;

          additionalPassengersContainer.appendChild(passengerCard);
        }
      }

      // Form validation
      const bookingForm = document.getElementById('bookingForm');
      if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
          const user = document.getElementById('user_id').value;
          const cabinClass = document.getElementById('cabin_class').value;

          if (!user) {
            e.preventDefault();
            alert('Please select a passenger');
            return;
          }

          if (!cabinClass) {
            e.preventDefault();
            alert('Please select a cabin class');
            return;
          }
        });
      }

      // Initialize values
      updateTotalPrice();
    });
  </script>
</body>

</html>