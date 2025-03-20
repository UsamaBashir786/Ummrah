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
  $route_name = $_POST['route_name'];
  $vehicle_type = $_POST['vehicle_type'];
  $vehicle_name = $_POST['vehicle_name'];
  $price = $_POST['price'];
  $booking_date = $_POST['booking_date'];
  $booking_time = $_POST['booking_time'];
  $pickup_location = $_POST['pickup_location'];
  $dropoff_location = $_POST['dropoff_location'];
  $passengers = $_POST['passengers'];
  $special_requests = $_POST['special_requests'];

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

  if (empty($dropoff_location)) {
    $errors[] = "Drop-off location is required";
  }

  if (empty($passengers) || !is_numeric($passengers) || $passengers < 1) {
    $errors[] = "Number of passengers must be at least 1";
  }

  // If no errors, save booking to database
  if (empty($errors)) {
    $booking_reference = generateBookingReference();
    $booking_status = "pending";
    $booking_timestamp = date("Y-m-d H:i:s");

    $insert_query = "INSERT INTO transportation_bookings 
                     (user_id, booking_reference, service_type, route_id, route_name, 
                      vehicle_type, vehicle_name, price, booking_date, booking_time, 
                      pickup_location, dropoff_location, passengers, special_requests, 
                      booking_status, created_at) 
                     VALUES 
                     (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param(
      "issiisssssssisss",
      $user_id,
      $booking_reference,
      $service_type,
      $route_id,
      $route_name,
      $vehicle_type,
      $vehicle_name,
      $price,
      $booking_date,
      $booking_time,
      $pickup_location,
      $dropoff_location,
      $passengers,
      $special_requests,
      $booking_status,
      $booking_timestamp
    );

    if ($stmt->execute()) {
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
  $route_name = $_GET['route_name'] ?? '';
  $vehicle_type = $_GET['vehicle_type'];
  $vehicle_name = $_GET['vehicle_name'] ?? '';
  $price = $_GET['price'] ?? 0;

  // If any parameter is missing, redirect back to price list
  if (empty($service_type) || empty($route_id) || empty($vehicle_type) || empty($price)) {
    header("Location: transportation-price-lists.php");
    exit();
  }
} else if (isset($_POST['service_type']) && isset($_POST['route_id']) && isset($_POST['vehicle_type'])) {
  // If coming from price list page with POST parameters
  $service_type = $_POST['service_type'];
  $route_id = $_POST['route_id'];
  $route_name = $_POST['route_name'] ?? '';
  $vehicle_type = $_POST['vehicle_type'];
  $vehicle_name = $_POST['vehicle_name'] ?? '';
  $price = $_POST['price'] ?? 0;

  // If any parameter is missing, redirect back to price list
  if (empty($service_type) || empty($route_id) || empty($vehicle_type) || empty($price)) {
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
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                  <p class="text-sm text-gray-600">Price:</p>
                  <p class="font-medium text-teal-600"><?php echo $price; ?> SR</p>
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

            <!-- Booking Form -->
            <form method="POST" action="">
              <input type="hidden" name="service_type" value="<?php echo $service_type; ?>">
              <input type="hidden" name="route_id" value="<?php echo $route_id; ?>">
              <input type="hidden" name="route_name" value="<?php echo htmlspecialchars($route_name); ?>">
              <input type="hidden" name="vehicle_type" value="<?php echo $vehicle_type; ?>">
              <input type="hidden" name="vehicle_name" value="<?php echo htmlspecialchars($vehicle_name); ?>">
              <input type="hidden" name="price" value="<?php echo $price; ?>">

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
                <div>
                  <label class="block text-gray-700 text-sm font-bold mb-2" for="booking_date">
                    Booking Date *
                  </label>
                  <input type="date" id="booking_date" name="booking_date" min="<?php echo date('Y-m-d'); ?>"
                    value="<?php echo isset($_POST['booking_date']) ? $_POST['booking_date'] : ''; ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500" required>
                </div>

                <div>
                  <label class="block text-gray-700 text-sm font-bold mb-2" for="booking_time">
                    Pickup Time *
                  </label>
                  <input type="time" id="booking_time" name="booking_time"
                    value="<?php echo isset($_POST['booking_time']) ? $_POST['booking_time'] : ''; ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500" required>
                </div>
              </div>

              <div class="mb-6">
                <!-- Pickup and Dropoff Locations -->
                <div class="mb-4">
                  <label class="block text-gray-700 text-sm font-bold mb-2" for="pickup_location">
                    Pickup Location *
                  </label>
                  <input type="text" id="pickup_location" name="pickup_location"
                    placeholder="Enter full pickup address"
                    value="<?php echo isset($_POST['pickup_location']) ? htmlspecialchars($_POST['pickup_location']) : ''; ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500" required>
                </div>

                <div>
                  <label class="block text-gray-700 text-sm font-bold mb-2" for="dropoff_location">
                    Drop-off Location *
                  </label>
                  <input type="text" id="dropoff_location" name="dropoff_location"
                    placeholder="Enter full destination address"
                    value="<?php echo isset($_POST['dropoff_location']) ? htmlspecialchars($_POST['dropoff_location']) : ''; ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500" required>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Number of Passengers -->
                <div>
                  <label class="block text-gray-700 text-sm font-bold mb-2" for="passengers">
                    Number of Passengers *
                  </label>
                  <input type="number" id="passengers" name="passengers" min="1"
                    value="<?php echo isset($_POST['passengers']) ? $_POST['passengers'] : '1'; ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500" required>
                </div>

                <?php if ($service_type === 'rentacar'): ?>
                  <!-- Additional options for rentacar -->
                  <div>
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
              </div>

              <!-- Special Requests -->
              <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="special_requests">
                  Special Requests (Optional)
                </label>
                <textarea id="special_requests" name="special_requests" rows="3"
                  placeholder="Enter any special requirements or requests..."
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500"><?php echo isset($_POST['special_requests']) ? htmlspecialchars($_POST['special_requests']) : ''; ?></textarea>
              </div>

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
                <a href="transportation-price-lists.php" class="px-6 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition">
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

    // Prefill default time if empty
    if (!document.getElementById('booking_time').value) {
      const now = new Date();
      now.setHours(now.getHours() + 2); // Default to 2 hours from now
      const hours = String(now.getHours()).padStart(2, '0');
      const minutes = String(now.getMinutes()).padStart(2, '0');
      document.getElementById('booking_time').value = `${hours}:${minutes}`;
    }
  </script>
</body>

</html>