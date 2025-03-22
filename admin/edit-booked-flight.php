<?php
session_start();
include '../connection/connection.php';

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($booking_id <= 0) {
  // Redirect to bookings list if no valid ID provided
  header("Location: my-bookings.php");
  exit();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Process form submission
  $passenger_name = $_POST['passenger_name'] ?? '';
  $passenger_email = $_POST['passenger_email'] ?? '';
  $passenger_phone = $_POST['passenger_phone'] ?? '';
  $adult_count = intval($_POST['adult_count'] ?? 1);
  $children_count = intval($_POST['children_count'] ?? 0);
  $special_requests = $_POST['special_requests'] ?? '';
  
  // Validate inputs
  $errors = [];
  
  if (empty($passenger_name)) {
    $errors[] = "Passenger name is required.";
  }
  
  if (empty($passenger_email) || !filter_var($passenger_email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid passenger email is required.";
  }
  
  if (empty($passenger_phone)) {
    $errors[] = "Passenger phone number is required.";
  }
  
  if ($adult_count < 1) {
    $errors[] = "At least one adult passenger is required.";
  }
  
  // If no errors, update the booking
  if (empty($errors)) {
    try {
      // First, check if booking belongs to user
      $check_query = "SELECT flight_id, cabin_class, seats FROM flight_bookings WHERE id = ? AND user_id = ?";
      $check_stmt = $conn->prepare($check_query);
      $check_stmt->bind_param("ii", $booking_id, $user_id);
      $check_stmt->execute();
      $check_result = $check_stmt->get_result();
      
      if ($check_result->num_rows == 0) {
        // Booking not found or doesn't belong to this user
        header("Location: my-bookings.php");
        exit();
      }
      
      $booking_data = $check_result->fetch_assoc();
      $check_stmt->close();
      
      // Get the existing seats
      $existing_seats = json_decode($booking_data['seats'], true);
      $total_seats = count($existing_seats);
      $new_total = $adult_count + $children_count;
      
      // Check if seat count matches passenger count
      if ($total_seats != $new_total) {
        $errors[] = "The number of seats ($total_seats) doesn't match the total passengers ($new_total). Please contact customer support for seat changes.";
      } else {
        // Update booking information
        $update_query = "UPDATE flight_bookings SET 
                        passenger_name = ?, 
                        passenger_email = ?, 
                        passenger_phone = ?, 
                        adult_count = ?, 
                        children_count = ?, 
                        special_requests = ?, 
                        updated_at = NOW() 
                        WHERE id = ? AND user_id = ?";
                        
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("sssiiisi", $passenger_name, $passenger_email, $passenger_phone, 
                                $adult_count, $children_count, $special_requests, $booking_id, $user_id);
        
        if ($update_stmt->execute()) {
          $success_message = "Booking updated successfully.";
          
          // Redirect to view booking page after successful update
          header("Location: view-booked-flight.php?id=$booking_id&updated=1");
          exit();
        } else {
          $errors[] = "Error updating booking: " . $update_stmt->error;
        }
        
        $update_stmt->close();
      }
    } catch (Exception $e) {
      $errors[] = "Database error: " . $e->getMessage();
    }
  }
}

// Fetch the booking details with related flight information
$query = "SELECT fb.*, f.flight_number, f.airline_name, f.departure_city, f.arrival_city, 
          f.departure_date, f.departure_time, f.arrival_date, f.arrival_time, f.duration,
          f.economy_price, f.business_price, f.first_class_price 
          FROM flight_bookings fb 
          LEFT JOIN flights f ON fb.flight_id = f.id 
          WHERE fb.id = ? AND fb.user_id = ?";

try {
  $stmt = $conn->prepare($query);
  $stmt->bind_param("ii", $booking_id, $user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows == 0) {
    // Booking not found or doesn't belong to this user
    header("Location: my-bookings.php");
    exit();
  }

  $booking = $result->fetch_assoc();
  $stmt->close();
  
  // Parse JSON seats data
  $seats_array = json_decode($booking['seats'], true);
  $seats_display = is_array($seats_array) ? implode(', ', $seats_array) : 'No seat assigned';
  
  // Format cabin class for display
  $cabin_class_display = ucfirst(str_replace('_', ' ', $booking['cabin_class']));
  
  // Calculate total price based on cabin class
  $price_per_seat = 0;
  switch ($booking['cabin_class']) {
    case 'economy':
      $price_per_seat = $booking['economy_price'];
      break;
    case 'business':
      $price_per_seat = $booking['business_price'];
      break;
    case 'first_class':
      $price_per_seat = $booking['first_class_price'];
      break;
  }
  
  $total_passengers = $booking['adult_count'] + $booking['children_count'];
  $total_price = $price_per_seat * $total_passengers;
  
} catch (Exception $e) {
  $error_message = "Database error: " . $e->getMessage();
}

// Calculate days until flight
$days_until_flight = 0;
if (isset($booking['departure_date'])) {
  $departure_date = new DateTime($booking['departure_date']);
  $today = new DateTime();
  $interval = $today->diff($departure_date);
  $days_until_flight = $interval->days;
}

// Check if flight is within 24 hours (for limited editing)
$is_within_24hours = $days_until_flight <= 1;
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Booking - SkyJourney Airlines</title>
  <?php include 'includes/css-links.php'; ?>
</head>

<body class="bg-gray-100">
  <div class="flex h-screen">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main flex-1 flex flex-col overflow-hidden">
    <?php if (!empty($errors)): ?>
      <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
        <h4 class="font-bold mb-2">Please fix the following errors:</h4>
        <ul class="list-disc list-inside">
          <?php foreach ($errors as $error): ?>
            <li><?php echo $error; ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
      <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
        <p><?php echo $error_message; ?></p>
      </div>
    <?php endif; ?>

    <?php if (isset($success_message)): ?>
      <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
        <p><?php echo $success_message; ?></p>
      </div>
    <?php endif; ?>

    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-edit text-teal-600 mr-2"></i> Edit Booking
      </h1>
      <a href="view-booked-flight.php?id=<?php echo $booking_id; ?>" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
        <i class="fas fa-arrow-left mr-2"></i> Back to Booking Details
      </a>
    </div>

    <?php if (isset($booking)): ?>
      <!-- Flight Information Card (Non-Editable) -->
      <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="p-6 bg-gray-50 border-b flex items-center justify-between">
          <h2 class="text-xl font-bold text-gray-800">
            <i class="fas fa-plane text-teal-600 mr-2"></i> Flight Information
          </h2>
          <span class="text-md text-blue-600 font-medium">
            <i class="fas fa-calendar-alt mr-1"></i> 
            <?php echo $days_until_flight > 0 ? "$days_until_flight days until flight" : "Flight is today!"; ?>
          </span>
        </div>
        
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h3 class="text-lg font-semibold text-gray-800 mb-3">Flight Details</h3>
              <p class="text-gray-600 mb-2">
                <span class="font-medium">Airline:</span> <?php echo htmlspecialchars($booking['airline_name']); ?>
              </p>
              <p class="text-gray-600 mb-2">
                <span class="font-medium">Flight Number:</span> <?php echo htmlspecialchars($booking['flight_number']); ?>
              </p>
              <p class="text-gray-600 mb-2">
                <span class="font-medium">Cabin Class:</span> <?php echo $cabin_class_display; ?>
              </p>
              <p class="text-gray-600 mb-2">
                <span class="font-medium">Departure:</span> <?php echo htmlspecialchars($booking['departure_city']); ?> - 
                <?php echo date('M d, Y H:i', strtotime($booking['departure_date'] . ' ' . $booking['departure_time'])); ?>
              </p>
              <p class="text-gray-600 mb-2">
                <span class="font-medium">Arrival:</span> <?php echo htmlspecialchars($booking['arrival_city']); ?> - 
                <?php echo date('M d, Y H:i', strtotime($booking['arrival_date'] . ' ' . $booking['arrival_time'])); ?>
              </p>
              <p class="text-gray-600 mb-2">
                <span class="font-medium">Duration:</span> <?php echo $booking['duration']; ?>
              </p>
            </div>
            
            <div>
              <h3 class="text-lg font-semibold text-gray-800 mb-3">Booking Details</h3>
              <p class="text-gray-600 mb-2">
                <span class="font-medium">Booking ID:</span> #<?php echo $booking['id']; ?>
              </p>
              <p class="text-gray-600 mb-2">
                <span class="font-medium">Booking Date:</span> <?php echo date('M d, Y H:i', strtotime($booking['booking_date'])); ?>
              </p>
              <p class="text-gray-600 mb-2">
                <span class="font-medium">Seats:</span> <?php echo $seats_display; ?>
              </p>
              <p class="text-gray-600 mb-2">
                <span class="font-medium">Price per seat:</span> $<?php echo number_format($price_per_seat, 2); ?>
              </p>
              <p class="text-teal-600 font-bold mt-4">
                <span class="font-medium">Total fare:</span> $<?php echo number_format($total_price, 2); ?>
              </p>
            </div>
          </div>
          
          <?php if ($is_within_24hours): ?>
            <div class="mt-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
              <p class="text-yellow-700">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Limited editing:</strong> Your flight is within 24 hours. Only passenger details can be modified at this time. For any other changes, please contact customer service.
              </p>
            </div>
          <?php endif; ?>
        </div>
      </div>
      
      <!-- Edit Booking Form -->
      <form method="POST" action="" class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
        <div class="p-6 bg-gray-50 border-b">
          <h2 class="text-xl font-bold text-gray-800">
            <i class="fas fa-user-edit text-teal-600 mr-2"></i> Edit Passenger Information
          </h2>
        </div>
        
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div>
              <div class="mb-4">
                <label for="passenger_name" class="block text-gray-700 font-medium mb-2">Passenger Name *</label>
                <input type="text" id="passenger_name" name="passenger_name" 
                      value="<?php echo htmlspecialchars($booking['passenger_name']); ?>" 
                      class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-teal-300 focus:outline-none" 
                      required>
              </div>
              
              <div class="mb-4">
                <label for="passenger_email" class="block text-gray-700 font-medium mb-2">Email Address *</label>
                <input type="email" id="passenger_email" name="passenger_email" 
                      value="<?php echo htmlspecialchars($booking['passenger_email']); ?>" 
                      class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-teal-300 focus:outline-none" 
                      required>
              </div>
              
              <div class="mb-4">
                <label for="passenger_phone" class="block text-gray-700 font-medium mb-2">Phone Number *</label>
                <input type="text" id="passenger_phone" name="passenger_phone" 
                      value="<?php echo htmlspecialchars($booking['passenger_phone']); ?>" 
                      class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-teal-300 focus:outline-none" 
                      required>
              </div>
            </div>
            
            <!-- Right Column -->
            <div>
              <div class="mb-4">
                <label for="adult_count" class="block text-gray-700 font-medium mb-2">Number of Adults *</label>
                <input type="number" id="adult_count" name="adult_count" 
                      value="<?php echo $booking['adult_count']; ?>" 
                      min="1" max="<?php echo $total_passengers; ?>" 
                      class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-teal-300 focus:outline-none"
                      <?php echo $is_within_24hours ? 'readonly' : ''; ?> 
                      required>
                <?php if ($is_within_24hours): ?>
                  <p class="text-sm text-gray-500 mt-1">Cannot be modified within 24 hours of flight.</p>
                <?php endif; ?>
              </div>
              
              <div class="mb-4">
                <label for="children_count" class="block text-gray-700 font-medium mb-2">Number of Children</label>
                <input type="number" id="children_count" name="children_count" 
                      value="<?php echo $booking['children_count']; ?>" 
                      min="0" max="<?php echo $total_passengers - 1; ?>" 
                      class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-teal-300 focus:outline-none"
                      <?php echo $is_within_24hours ? 'readonly' : ''; ?>>
                <?php if ($is_within_24hours): ?>
                  <p class="text-sm text-gray-500 mt-1">Cannot be modified within 24 hours of flight.</p>
                <?php endif; ?>
              </div>
              
              <div class="mb-4">
                <label for="special_requests" class="block text-gray-700 font-medium mb-2">Special Requests</label>
                <textarea id="special_requests" name="special_requests" 
                         class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-teal-300 focus:outline-none" 
                         rows="4"><?php echo htmlspecialchars($booking['special_requests'] ?? ''); ?></textarea>
              </div>
            </div>
          </div>
          
          <!-- Seats Information (Read-only) -->
          <div class="mt-4 p-4 bg-gray-50 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Seat Information</h3>
            <p class="text-gray-600 mb-2">
              <span class="font-medium">Selected Seats:</span> <?php echo $seats_display; ?>
            </p>
            <p class="text-sm text-gray-500">
              <i class="fas fa-info-circle mr-1"></i>
              To change seat assignments, please cancel this booking and create a new one, or contact customer service.
            </p>
          </div>
          
          <!-- Submit Buttons -->
          <div class="flex justify-between mt-8">
            <a href="view-booked-flight.php?id=<?php echo $booking_id; ?>" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">
              <i class="fas fa-times mr-2"></i> Cancel Changes
            </a>
            <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700">
              <i class="fas fa-save mr-2"></i> Save Changes
            </button>
          </div>
        </div>
      </form>
      
      <!-- Important Notes -->
      <div class="bg-blue-50 border-l-4 border-blue-400 text-blue-700 p-4" role="alert">
        <h4 class="font-bold mb-2">Important Notes:</h4>
        <ul class="list-disc list-inside text-sm">
          <li>Changes to passenger names and contact information are allowed at any time.</li>
          <li>Changes to passenger count are only allowed up to 24 hours before departure.</li>
          <li>Seat changes require cancellation and rebooking, or assistance from customer service.</li>
          <li>For any major changes or assistance, please contact our customer service at support@skyjourney.com</li>
        </ul>
      </div>
    <?php endif; ?>
    </main>
  </div>
  <?php include '../includes/footer.php'; ?>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const adultCountInput = document.getElementById('adult_count');
      const childrenCountInput = document.getElementById('children_count');
      const totalSeats = <?php echo count($seats_array ?? []); ?>;
      
      // Function to validate passenger counts
      function validatePassengerCounts() {
        const adultCount = parseInt(adultCountInput.value) || 0;
        const childrenCount = parseInt(childrenCountInput.value) || 0;
        const totalPassengers = adultCount + childrenCount;
        
        // Adjust max values based on total seats
        childrenCountInput.max = totalSeats - 1;
        
        // Check if total exceeds available seats
        if (totalPassengers > totalSeats) {
          if (childrenCountInput === document.activeElement) {
            childrenCountInput.value = Math.max(0, totalSeats - adultCount);
          } else {
            adultCountInput.value = Math.max(1, totalSeats - childrenCount);
          }
          alert(`Total passengers cannot exceed the number of seats (${totalSeats}).`);
        }
        
        // Ensure at least one adult
        if (adultCount < 1) {
          adultCountInput.value = 1;
          alert('At least one adult passenger is required.');
        }
      }
      
      // Add event listeners to inputs
      adultCountInput.addEventListener('change', validatePassengerCounts);
      childrenCountInput.addEventListener('change', validatePassengerCounts);
    });
  </script>
</body>

</html>