<?php
session_start();
include 'connection/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
  header("Location: login.php?message=" . urlencode("Please login to complete your booking"));
  exit();
}

$user_id = $_SESSION['user_id'];

// Get user details
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Check if we have booking data in POST or GET
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_booking'])) {
  // Process form submission
  $service_type = $_POST['service_type'];
  $route_id = $_POST['route_id'];
  $route_name = $_POST['route_name']; // This comes from the database/pre-selected
  $vehicle_type = $_POST['vehicle_type'];
  $vehicle_name = $_POST['vehicle_name'];
  $base_price = $_POST['base_price'];
  $booking_date = $_POST['booking_date'];
  $booking_time = $_POST['booking_time'];
  $pickup_location = $_POST['pickup_location']; // This is entered by user
  $passengers = $_POST['passengers'];
  $special_requests = $_POST['special_requests'] ?? '';
  $duration = isset($_POST['duration']) ? $_POST['duration'] : null;

  // Calculate final price based on passengers
  $price = $base_price;
  if ($passengers > 1) {
    $price = $base_price * $passengers;
  }

  // Validate inputs
  $errors = [];

  if (empty($booking_date)) {
    $errors[] = "Booking date is required";
  }

  if (empty($booking_time)) {
    $errors[] = "Booking time is required";
  }

  if (empty($pickup_location)) {
    $errors[] = "Pickup location is required";
  }

  if (empty($passengers) || !is_numeric($passengers) || $passengers < 1) {
    $errors[] = "Number of passengers must be at least 1";
  }

  // If no errors, save booking to database
  if (empty($errors)) {
    $booking_reference = generateBookingReference();
    $booking_status = "pending";
    $booking_timestamp = date("Y-m-d H:i:s");

    // Create a direct SQL query with explicit values for debugging
    $sql = "INSERT INTO transportation_bookings 
           (user_id, booking_reference, service_type, route_id, route_name, 
            vehicle_type, vehicle_name, price, booking_date, booking_time, 
            pickup_location, passengers, special_requests, duration,
            booking_status, created_at) 
           VALUES 
           ('$user_id', '$booking_reference', '$service_type', '$route_id', '$route_name', 
            '$vehicle_type', '$vehicle_name', '$price', '$booking_date', '$booking_time', 
            '$pickup_location', '$passengers', '$special_requests', " . ($duration ? "'$duration'" : "NULL") . ", 
            '$booking_status', '$booking_timestamp')";

    if ($conn->query($sql)) {
      // Booking successful
      $booking_id = $conn->insert_id;
      // Redirect to confirmation page
      header("Location: booking-confirmation.php?booking_id=$booking_id&reference=$booking_reference");
      exit();
    } else {
      $errors[] = "Database error: " . $conn->error;
    }
  }
} else if (isset($_GET['service_type']) && isset($_GET['route_id']) && isset($_GET['vehicle_type'])) {
  // If coming from price list page with GET parameters
  $service_type = $_GET['service_type'];
  $route_id = $_GET['route_id'];
  $route_name = $_GET['route_name'] ?? '';  // This is from the database
  $vehicle_type = $_GET['vehicle_type'];
  $vehicle_name = $_GET['vehicle_name'] ?? '';
  $base_price = $_GET['price'] ?? 0;

  // If any parameter is missing, redirect back to price list
  if (empty($service_type) || empty($route_id) || empty($vehicle_type) || empty($base_price)) {
    header("Location: transportation-price-lists.php");
    exit();
  }
} else if (isset($_POST['service_type']) && isset($_POST['route_id']) && isset($_POST['vehicle_type'])) {
  // If coming from price list page with POST parameters
  $service_type = $_POST['service_type'];
  $route_id = $_POST['route_id'];
  $route_name = $_POST['route_name'] ?? '';  // This is from the database
  $vehicle_type = $_POST['vehicle_type'];
  $vehicle_name = $_POST['vehicle_name'] ?? '';
  $base_price = $_POST['price'] ?? 0;

  // If any parameter is missing, redirect back to price list
  if (empty($service_type) || empty($route_id) || empty($vehicle_type) || empty($base_price)) {
    header("Location: transportation-price-lists.php");
    exit();
  }
} else {
  // No booking parameters, redirect to price list
  header("Location: transportation-price-lists.php");
  exit();
}

// Function to generate a unique booking reference
function generateBookingReference()
{
  $prefix = 'TR';
  $middle = strtoupper(substr(uniqid(), 0, 6));
  $suffix = rand(1000, 9999);
  return $prefix . $middle . $suffix;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <link href="https://unpkg.com/boxicons/css/boxicons.min.css" rel="stylesheet">
  <script PKRc="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-50">
  <?php include 'includes/navbar.php'; ?>
  <div class="mt-15"></div>
  <section class="py-10 bg-gray-50">
    <div class="container mx-auto px-4">
      <div class="max-w-4xl mx-auto">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
          <div class="px-6 py-4 bg-teal-600 text-white">
            <h1 class="text-2xl font-bold">
              <?php echo $service_type === 'taxi' ? 'Taxi Service Booking' : 'Rent A Car Booking'; ?>
            </h1>
            <p class="text-sm opacity-80">Complete your transportation booking details below</p>
          </div>

          <div class="p-6">
            <!-- Booking Summary -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
              <h2 class="text-lg font-semibold text-gray-800 mb-2">Booking Summary</h2>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-gray-600">Route:</p>
                  <p class="font-medium"><?php echo htmlspecialchars($route_name); ?></p>
                </div>

                <div>
                  <p class="text-sm text-gray-600">Vehicle:</p>
                  <p class="font-medium"><?php echo htmlspecialchars($vehicle_name); ?></p>
                </div>

                <div>
                  <p class="text-sm text-gray-600">Base Price:</p>
                  <p class="font-medium text-teal-600"><?php echo $base_price; ?> PKR</p>
                </div>

                <div>
                  <p class="text-sm text-gray-600">Service Type:</p>
                  <p class="font-medium"><?php echo ucfirst($service_type); ?> Service</p>
                </div>
              </div>
            </div>

            <!-- Error Messages -->
            <?php if (isset($errors) && !empty($errors)): ?>
              <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                <ul class="list-disc pl-5">
                  <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <!-- Debug Information - Remove in production -->
            <?php /*if (true): ?>
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-3 mb-4 rounded">
              <h3 class="font-bold">Debug Info:</h3>
              <p>Route ID: <?php echo $route_id; ?></p>
              <p>Route Name: <?php echo htmlspecialchars($route_name); ?></p>
            </div>
            <?php endif;*/ ?>

            <!-- Booking Form -->
            <form method="POST" action="">
              <input type="hidden" name="service_type" value="<?php echo htmlspecialchars($service_type); ?>">
              <input type="hidden" name="route_id" value="<?php echo htmlspecialchars($route_id); ?>">
              <input type="hidden" name="route_name" value="<?php echo htmlspecialchars($route_name); ?>">
              <input type="hidden" name="vehicle_type" value="<?php echo htmlspecialchars($vehicle_type); ?>">
              <input type="hidden" name="vehicle_name" value="<?php echo htmlspecialchars($vehicle_name); ?>">
              <input type="hidden" name="base_price" value="<?php echo htmlspecialchars($base_price); ?>">

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- User Information (pre-filled, read-only) -->
                <div>
                  <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                    Your Name
                  </label>
                  <input type="text" id="full_name" value="<?php echo isset($user['full_name']) ? htmlspecialchars($user['full_name']) : ''; ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                </div>

                <div>
                  <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                    Email Address
                  </label>
                  <input type="email" id="email" value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Date and Time Selection -->
                <!-- <div>
                  <label class="block text-gray-700 text-sm font-bold mb-2" for="booking_date">
                    Booking Date *
                  </label>
                  <input type="date" id="booking_date" name="booking_date" min="<?php echo date('Y-m-d'); ?>"
                    value="<?php echo isset($_POST['booking_date']) ? $_POST['booking_date'] : ''; ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500" required>
                </div> -->

                <div>
                  <div class="mb-4">
                    <label for="booking_time" class="block text-sm font-medium text-gray-700 mb-1">
                      🚕 Pickup Time <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                      <input
                        type="text"
                        id="booking_time"
                        name="booking_time"
                        placeholder="HH:MM"
                        maxlength="5"
                        class="w-full px-4 py-2 text-gray-700 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition duration-150 ease-in-out"
                        oninput="formatTimeInput(this)"
                        onkeydown="handleTimeNavigation(event)"
                        required />
                      <svg class="w-5 h-5 text-gray-400 absolute right-3 top-2.5 pointer-events-none" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </div>
                    <div id="time_error" class="text-red-500 text-xs mt-1 hidden">Please enter valid time (00:00 to 23:59)</div>
                  </div>

                  <script>
                    function formatTimeInput(input) {
                      let value = input.value.replace(/\D/g, ''); // Remove non-digits
                      const errorElement = document.getElementById('time_error');

                      // Auto-insert colon after 2 digits
                      if (value.length > 2) {
                        value = value.substring(0, 2) + ':' + value.substring(2);
                      }

                      // Limit to 4 digits (HH:MM)
                      if (value.length > 5) {
                        value = value.substring(0, 5);
                      }

                      input.value = value;

                      // Validate time
                      const timeRegex = /^([01]?[0-9]|2[0-3]):?([0-5][0-9])?$/;
                      if (value.length > 0 && !timeRegex.test(value)) {
                        errorElement.classList.remove('hidden');
                        input.setCustomValidity('Invalid time format');
                      } else {
                        errorElement.classList.add('hidden');
                        input.setCustomValidity('');
                      }

                      input.reportValidity();
                    }

                    function handleTimeNavigation(event) {
                      const input = event.target;
                      const key = event.key;
                      const cursorPos = input.selectionStart;

                      // Allow navigation and deletion
                      if ([
                          'Backspace', 'Delete',
                          'ArrowLeft', 'ArrowRight',
                          'ArrowUp', 'ArrowDown',
                          'Tab', 'Home', 'End'
                        ].includes(key)) {
                        // Special handling for Backspace at colon position
                        if (key === 'Backspace' && cursorPos === 3 && input.value.includes(':')) {
                          input.value = input.value.substring(0, 2) + input.value.substring(4);
                          input.setSelectionRange(2, 2);
                          event.preventDefault();
                        }
                        return true;
                      }

                      // Allow only numbers
                      if (!/\d/.test(key)) {
                        event.preventDefault();
                        return false;
                      }

                      // Handle typing at colon position
                      if (cursorPos === 3 && input.value.includes(':')) {
                        input.value = input.value.substring(0, 3) + key + input.value.substring(4);
                        input.setSelectionRange(4, 4);
                        event.preventDefault();
                      }
                    }

                    // Make colon position editable
                    document.getElementById('booking_time').addEventListener('click', function(e) {
                      const cursorPos = this.selectionStart;
                      if (cursorPos === 3 && this.value.length >= 3) {
                        // Move cursor to after colon if clicking on it
                        this.setSelectionRange(4, 4);
                      }
                    });
                  </script>
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Date and Time Selection -->
                <div>
                  <label class="block text-gray-700 text-sm font-bold mb-2" for="booking_date">
                    Booking Date *
                  </label>
                  <input type="date" id="booking_date" name="booking_date" min="<?php echo date('Y-m-d'); ?>"
                    value="<?php echo isset($_POST['booking_date']) ? $_POST['booking_date'] : ''; ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500"
                    oninput="validateDateInput(this)"
                    onchange="validateDateInput(this)"
                    onblur="validateDateInput(this)"
                    required>
                  <div id="booking_date_error" class="text-red-500 text-xs mt-1 hidden">Please select a valid future date</div>
                </div>

                <script>
                  function validateDateInput(input) {
                    const errorElement = document.getElementById('booking_date_error');
                    const today = new Date();
                    today.setHours(0, 0, 0, 0); // Compare dates without time

                    // Check if input is empty or invalid
                    if (!input.value) {
                      errorElement.classList.remove('hidden');
                      input.setCustomValidity('Please select a booking date');
                      return;
                    }

                    const selectedDate = new Date(input.value);
                    selectedDate.setHours(0, 0, 0, 0); // Compare dates without time

                    // Check if date is in the past
                    if (selectedDate < today) {
                      errorElement.textContent = 'Please select a date today or in the future';
                      errorElement.classList.remove('hidden');
                      input.setCustomValidity('Date must be today or in the future');
                    }
                    // Check if date is valid
                    else if (isNaN(selectedDate.getTime())) {
                      errorElement.textContent = 'Please enter a valid date';
                      errorElement.classList.remove('hidden');
                      input.setCustomValidity('Invalid date format');
                    }
                    // Valid date
                    else {
                      errorElement.classList.add('hidden');
                      input.setCustomValidity('');
                    }

                    // Force revalidation
                    input.reportValidity();
                  }

                  // Add click event to handle calendar popup
                  document.getElementById('booking_date').addEventListener('click', function() {
                    this.showPicker(); // Opens the native date picker
                  });

                  // Validate on page load if there's a value
                  document.addEventListener('DOMContentLoaded', function() {
                    const dateInput = document.getElementById('booking_date');
                    if (dateInput.value) {
                      validateDateInput(dateInput);
                    }
                  });
                </script>

              </div>

              <div class="mb-6">
                <!-- Pickup Location -->
                <div>
                  <label class="block text-gray-700 text-sm font-bold mb-2" for="pickup_location">
                    Pickup Location *
                  </label>
                  <input type="text" id="pickup_location" name="pickup_location"
                    placeholder="Enter full pickup address"
                    value="<?php echo isset($_POST['pickup_location']) ? htmlspecialchars($_POST['pickup_location']) : ''; ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500"
                    maxlength="30"
                    oninput="validatePickupLocation(this)"
                    required>
                  <div id="pickup_location_error" class="text-red-500 text-xs mt-1 hidden">
                    Pickup location must be 30 characters or less
                  </div>
                  <div class="text-xs text-gray-500 mt-1">
                    <span id="location_char_count">0</span>/30 characters
                  </div>
                </div>

                <script>
                  function validatePickupLocation(input) {
                    const errorElement = document.getElementById('pickup_location_error');
                    const charCountElement = document.getElementById('location_char_count');
                    const currentLength = input.value.length;

                    // Update character count
                    charCountElement.textContent = currentLength;

                    // Validate length
                    if (currentLength > 30) {
                      errorElement.classList.remove('hidden');
                      input.setCustomValidity('Location must be 30 characters or less');
                      // Trim to 30 characters
                      input.value = input.value.substring(0, 30);
                      charCountElement.textContent = 30;
                    } else {
                      errorElement.classList.add('hidden');
                      input.setCustomValidity('');
                    }

                    // Force validation update
                    input.reportValidity();
                  }

                  // Initialize character count
                  document.addEventListener('DOMContentLoaded', function() {
                    const locationInput = document.getElementById('pickup_location');
                    document.getElementById('location_char_count').textContent = locationInput.value.length;
                    validatePickupLocation(locationInput);
                  });
                </script>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Number of Passengers -->
                <div>
                  <label class="block text-gray-700 text-sm font-bold mb-2" for="passengers">
                    Number of Passengers *
                  </label>
                  <input type="number" id="passengers" name="passengers" min="1" max="5"
                    value="<?php echo isset($_POST['passengers']) ? max(1, min(5, (int)$_POST['passengers'])) : '1'; ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500"
                    oninput="validatePassengers(this)"
                    required>
                  <div id="passengers_error" class="text-red-500 text-xs mt-1 hidden">
                    Please enter a number between 1-5
                  </div>
                </div>

                <script>
                  function validatePassengers(input) {
                    const errorElement = document.getElementById('passengers_error');
                    const value = parseInt(input.value);

                    if (isNaN(value) || value < 1 || value > 5) {
                      errorElement.classList.remove('hidden');
                      input.setCustomValidity('Number of passengers must be between 1-5');
                    } else {
                      errorElement.classList.add('hidden');
                      input.setCustomValidity('');
                    }

                    // Force immediate validation feedback
                    input.reportValidity();

                    // Ensure value stays within bounds
                    if (value < 1) input.value = 1;
                    if (value > 5) input.value = 5;
                  }

                  // Initialize validation
                  document.addEventListener('DOMContentLoaded', function() {
                    const passengersInput = document.getElementById('passengers');
                    validatePassengers(passengersInput);
                  });
                </script>

                <!-- Price Calculation Display -->
                <div>
                  <label class="block text-gray-700 text-sm font-bold mb-2">
                    Total Price
                  </label>
                  <div class="px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                    <span id="priceDisplay"><?php echo $base_price; ?></span> PKR
                  </div>
                </div>
              </div>

              <?php if ($service_type === 'rentacar'): ?>
                <!-- Additional options for rentacar -->
                <div class="mb-6">
                  <label class="block text-gray-700 text-sm font-bold mb-2">
                    Duration
                  </label>
                  <select name="duration"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                    <option value="one_way">One Way</option>
                    <option value="round_trip">Round Trip</option>
                    <option value="full_day">Full Day</option>
                  </select>
                </div>
              <?php endif; ?>

              <!-- Special Requests -->
              <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="special_requests">
                  Special Requests (Optional)
                </label>
                <textarea id="special_requests" name="special_requests" rows="3"
                  placeholder="Enter any special requirements or requests..."
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500"
                  oninput="validateSpecialRequests(this)"
                  onkeypress="return allowOnlyEnglish(event)"
                  maxlength="500"><?php echo isset($_POST['special_requests']) ? htmlspecialchars($_POST['special_requests']) : ''; ?></textarea>
                <div id="special_requests_error" class="text-red-500 text-xs mt-1 hidden">
                  Only English letters, numbers, and basic punctuation (.,!?) are allowed
                </div>
                <div class="text-xs text-gray-500 mt-1">
                  <span id="char_count">0</span>/500 characters
                </div>
              </div>

              <script>
                function validateSpecialRequests(textarea) {
                  const errorElement = document.getElementById('special_requests_error');
                  const charCountElement = document.getElementById('char_count');

                  // Remove any special characters (keeps English letters, numbers, spaces, and basic punctuation)
                  textarea.value = textarea.value.replace(/[^a-zA-Z0-9 .,!?]/g, '');

                  // Update character count
                  charCountElement.textContent = textarea.value.length;

                  // Show error if any special characters were removed
                  if (/[^a-zA-Z0-9 .,!?]/.test(textarea.value)) {
                    errorElement.classList.remove('hidden');
                  } else {
                    errorElement.classList.add('hidden');
                  }
                }

                function allowOnlyEnglish(event) {
                  const key = event.key;
                  // Allow: letters A-Z (both cases), numbers, space, and basic punctuation
                  if (/[a-zA-Z0-9 .,!?]/.test(key) ||
                    key === 'Backspace' ||
                    key === 'Delete' ||
                    key === 'ArrowLeft' ||
                    key === 'ArrowRight') {
                    return true;
                  }
                  event.preventDefault();
                  return false;
                }

                // Initialize character count
                document.addEventListener('DOMContentLoaded', function() {
                  const textarea = document.getElementById('special_requests');
                  document.getElementById('char_count').textContent = textarea.value.length;
                });
              </script>

              <!-- Terms and Conditions -->
              <div class="mb-6">
                <div class="flex items-center">
                  <input type="checkbox" id="terms" name="terms" class="h-4 w-4 text-teal-600" required>
                  <label for="terms" class="ml-2 text-sm text-gray-700">
                    I agree to the <a href="#" class="text-teal-600 hover:underline">Terms and Conditions</a>
                  </label>
                </div>
              </div>

              <!-- Submit Button -->
              <div class="flex justify-between">
                <a href="transportation.php" class="px-6 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition">
                  <i class="bx bx-arrow-back mr-2"></i> Back to Prices
                </a>
                <button type="submit" name="submit_booking" class="px-6 py-2 bg-teal-600 text-white rounded-md hover:bg-teal-700 transition">
                  <i class="bx bx-check mr-2"></i> Confirm Booking
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php include 'includes/footer.php'; ?>

  <script>
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('booking_date').min = today;

    // Prefill default time if empty (24-hour format)
    if (!document.getElementById('booking_time').value) {
      const now = new Date();
      now.setHours(now.getHours() + 2); // Default to 2 hours from now
      const hours = String(now.getHours()).padStart(2, '0');
      const minutes = String(now.getMinutes()).padStart(2, '0');
      document.getElementById('booking_time').value = `${hours}:${minutes}`;
    }

    // Price calculation based on passengers
    const basePrice = <?php echo $base_price; ?>;
    const passengersInput = document.getElementById('passengers');
    const priceDisplay = document.getElementById('priceDisplay');

    passengersInput.addEventListener('input', function() {
      const passengers = parseInt(this.value) || 1;
      const totalPrice = basePrice * passengers;
      priceDisplay.textContent = totalPrice;
    });
  </script>
</body>

</html>