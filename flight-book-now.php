<?php
session_start();
include 'connection/connection.php';
require_once('vendor/autoload.php');

\Stripe\Stripe::setApiKey('sk_test_4eC39HqLyjWDarjtT1zdp7dc'); 
// Check if flight_id is provided
if (!isset($_GET['flight_id']) || empty($_GET['flight_id'])) {
  header('Location: index.php');
  exit;
}

$flight_id = mysqli_real_escape_string($conn, $_GET['flight_id']);

// Get flight details
$query = "SELECT * FROM flights WHERE id = '$flight_id'";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
  header('Location: index.php');
  exit;
}

$flight = mysqli_fetch_assoc($result);

// Process booking form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Check if user is logged in
  if (!isset($_SESSION['user_id'])) {
    $error = "Please log in to book a flight.";
  } else {
    $user_id = $_SESSION['user_id'];
    $booking_time = date('Y-m-d H:i:s');
    $flight_status = 'Pending';

    // Insert into flight_book table
    $insert_query = "INSERT INTO flight_book (user_id, flight_id, booking_time, flight_status) 
                        VALUES ('$user_id', '$flight_id', '$booking_time', '$flight_status')";

    if (mysqli_query($conn, $insert_query)) {
      $success = "Flight booked successfully! Your booking is pending confirmation.";
    } else {
      $error = "Error booking flight: " . mysqli_error($conn);
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php' ?>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 font-sans">
  <?php include 'includes/navbar.php' ?>
  <br><br><br>
  <section class="py-16">
    <div class="container mx-auto px-4">
      <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-md p-8">
        <h1 class="text-3xl font-bold text-teal-600 mb-6">Book Your Flight</h1>

        <?php if (isset($error)): ?>
          <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error; ?>
          </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
          <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo $success; ?>
            <p class="mt-2">
              <a href="index.php" class="text-teal-600 hover:underline">Return to Flights</a>
            </p>
          </div>
        <?php else: ?>
          <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4">Flight Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <p><span class="font-medium">Airline:</span> <?php echo htmlspecialchars($flight['airline_name']); ?></p>
                <p><span class="font-medium">Flight Number:</span> <?php echo htmlspecialchars($flight['flight_number']); ?></p>
                <p><span class="font-medium">From:</span> <?php echo htmlspecialchars($flight['departure_city']); ?></p>
                <p><span class="font-medium">To:</span> <?php echo htmlspecialchars($flight['arrival_city']); ?></p>
              </div>
              <div>
                <p><span class="font-medium">Departure Date:</span> <?php echo htmlspecialchars($flight['departure_date']); ?></p>
                <p><span class="font-medium">Departure Time:</span> <?php echo htmlspecialchars($flight['departure_time']); ?></p>
                <p><span class="font-medium">Economy Price:</span> $<?php echo htmlspecialchars($flight['economy_price']); ?></p>
                <p><span class="font-medium">Business Price:</span> $<?php echo htmlspecialchars($flight['business_price']); ?></p>
              </div>
            </div>
          </div>

          <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4">Select Ticket Type</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="border rounded-lg p-4 hover:shadow-md cursor-pointer">
                <input type="radio" id="economy" name="ticket_type" value="economy" checked>
                <label for="economy" class="cursor-pointer">
                  <h3 class="text-lg font-medium">Economy</h3>
                  <p class="text-xl font-bold text-teal-600">$<?php echo htmlspecialchars($flight['economy_price']); ?></p>
                  <p class="text-sm text-gray-500">Standard seating</p>
                </label>
              </div>

              <div class="border rounded-lg p-4 hover:shadow-md cursor-pointer">
                <input type="radio" id="business" name="ticket_type" value="business">
                <label for="business" class="cursor-pointer">
                  <h3 class="text-lg font-medium">Business</h3>
                  <p class="text-xl font-bold text-teal-600">$<?php echo htmlspecialchars($flight['business_price']); ?></p>
                  <p class="text-sm text-gray-500">Premium seating with extra legroom</p>
                </label>
              </div>

              <div class="border rounded-lg p-4 hover:shadow-md cursor-pointer">
                <input type="radio" id="first_class" name="ticket_type" value="first_class">
                <label for="first_class" class="cursor-pointer">
                  <h3 class="text-lg font-medium">First Class</h3>
                  <p class="text-xl font-bold text-teal-600">$<?php echo htmlspecialchars($flight['first_class_price']); ?></p>
                  <p class="text-sm text-gray-500">Luxury experience with all amenities</p>
                </label>
              </div>
            </div>
          </div>

          <form method="POST" action="">
            <?php if (!isset($_SESSION['user_id'])): ?>
              <div class="mb-6 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                Please <a href="login.php" class="text-teal-600 hover:underline">log in</a> to book this flight.
              </div>
            <?php else: ?>
              <button type="submit" class="w-full px-6 py-3 bg-teal-600 text-white font-semibold rounded-lg hover:bg-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-300">
                Confirm Booking
              </button>
            <?php endif; ?>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-teal-600 py-6 text-white">
    <div class="container mx-auto text-center">
      <p>&copy; 2025 Umrah Journey. All Rights Reserved.</p>
    </div>
  </footer>

  <script>
    // Make the entire div clickable for ticket type selection
    document.querySelectorAll('.ticket_type_container').forEach(container => {
      container.addEventListener('click', function() {
        const radio = this.querySelector('input[type="radio"]');
        radio.checked = true;
      });
    });
  </script>

</body>

</html>