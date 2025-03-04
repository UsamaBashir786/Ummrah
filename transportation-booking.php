<?php
session_start();
include 'connection/connection.php';

// Check if transport ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
  header('Location: transportation.php');
  exit();
}

$transportation_id = intval($_GET['id']); // Ensure it's an integer

// Get transportation details
$sql = "SELECT * FROM transportation WHERE id = ? AND status != 'deleted'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $transportation_id);
$stmt->execute();
$result = $stmt->get_result();
$transport = $result->fetch_assoc();

// Check if transport exists
if (!$transport) {
  echo json_encode(['status' => 'error', 'message' => 'Transport not found.']);
  exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $booking_date = $_POST['booking_date'] ?? '';
  $pickup_location = $_POST['pickup_location'] ?? '';
  $dropoff_location = $_POST['dropoff_location'] ?? '';
  $passengers = $_POST['passengers'] ?? '';
  $special_requests = $_POST['special_requests'] ?? '';
  $user_id = $_SESSION['user_id'] ?? null;

  // Debug information
  error_log("Transport ID: " . $transportation_id);
  error_log("POST data: " . print_r($_POST, true));

  // Validate inputs
  $errors = [];
  if (empty($booking_date)) $errors[] = "Booking date is required";
  if (empty($pickup_location)) $errors[] = "Pickup location is required";
  if (empty($dropoff_location)) $errors[] = "Drop-off location is required";
  if (empty($passengers)) $errors[] = "Number of passengers is required";
  if (empty($transportation_id)) $errors[] = "Transport ID is missing";

  if (empty($errors)) {
    // Insert booking
    $sql = "INSERT INTO transportation_bookings (transportation_id, user_id, booking_date, pickup_location, 
                dropoff_location, passengers, special_requests, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
      "iisssss",
      $transportation_id,
      $user_id,
      $booking_date,
      $pickup_location,
      $dropoff_location,
      $passengers,
      $special_requests
    );

    if ($stmt->execute()) {
      // Update transportation status
      $update_sql = "UPDATE transportation SET status = 'booked' WHERE id = ?";
      $update_stmt = $conn->prepare($update_sql);
      $update_stmt->bind_param("i", $transportation_id);
      $update_stmt->execute();

      echo json_encode(['status' => 'success']);
      exit();
    } else {
      $errors[] = "Failed to process booking. Please try again.";
      error_log("SQL Error: " . $stmt->error);
    }
  }

  if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
    exit();
  }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php' ?>
  <link rel="stylesheet" href="assets/css/transportation.css">
  <link href="https://unpkg.com/boxicons/css/boxicons.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-50">
  <?php include 'includes/navbar.php' ?>

  <div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
      <h1 class="text-3xl font-bold text-teal-700 mb-8">Book Transportation</h1>

      <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Selected Vehicle Details</h2>
        <div class="grid md:grid-cols-2 gap-4 mb-6">
          <div>
            <img src="admin/<?php echo htmlspecialchars($transport['transport_image']); ?>"
              alt="<?php echo htmlspecialchars($transport['transport_name']); ?>"
              class="rounded-lg w-full h-48 object-cover">
          </div>
          <div>
            <h3 class="text-lg font-bold"><?php echo htmlspecialchars($transport['transport_name']); ?></h3>
            <p class="text-gray-600 mb-2"><?php echo htmlspecialchars($transport['details']); ?></p>
            <p class="text-xl font-bold text-teal-600">$<?php echo number_format($transport['price'], 2); ?></p>
          </div>
        </div>

        <form id="bookingForm" method="POST" class="space-y-6">
          <!-- Add hidden input for transportation_id -->
          <input type="hidden" name="transportation_id" value="<?php echo htmlspecialchars($transportation_id); ?>">

          <div class="grid md:grid-cols-2 gap-6">
            <div>
              <label class="block text-gray-700 text-sm font-bold mb-2" for="booking_date">
                Booking Date
              </label>
              <input type="date" id="booking_date" name="booking_date"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                required min="<?php echo date('Y-m-d'); ?>">
            </div>

            <div>
              <label class="block text-gray-700 text-sm font-bold mb-2" for="passengers">
                Number of Passengers (Max: <?php echo $transport['seats']; ?>)
              </label>
              <input type="number"
                id="passengers"
                name="passengers"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                required
                min="1"
                max="<?php echo $transport['seats']; ?>"
                onchange="validatePassengers(this)">
            </div>

          </div>

          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="pickup_location">
              Pickup Location
            </label>
            <input type="text" id="pickup_location" name="pickup_location"
              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
              required>
          </div>

          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="dropoff_location">
              Drop-off Location
            </label>
            <input type="text" id="dropoff_location" name="dropoff_location"
              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
              required>
          </div>

          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="special_requests">
              Special Requests (Optional)
            </label>
            <textarea id="special_requests" name="special_requests"
              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
              rows="4"></textarea>
          </div>

          <div class="flex justify-between items-center">
            <a href="transportation.php" class="text-teal-600 hover:text-teal-800">
              ← Back to Transportation
            </a>
            <button type="submit"
              class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
              Confirm Booking
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    function validatePassengers(input) {
      const maxSeats = <?php echo $transport['seats']; ?>;
      if (input.value > maxSeats) {
        Swal.fire({
          title: 'Warning!',
          text: `Maximum ${maxSeats} seats available`,
          icon: 'warning',
          confirmButtonColor: '#0D9488'
        });
        input.value = maxSeats;
      }
    }

    // Add client-side validation
    document.getElementById('booking_date').min = new Date().toISOString().split('T')[0];

    // Handle form submission
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
      e.preventDefault();

      const formData = new FormData(this);

      fetch('', {
          method: 'POST',
          body: formData
        })
        .then(response => {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.json();
        })
        .then(data => {
          if (data.status === 'success') {
            Swal.fire({
              title: 'Booking Successful!',
              text: 'Your transportation has been booked successfully.',
              icon: 'success',
              confirmButtonText: 'OK',
              confirmButtonColor: '#0D9488'
            }).then((result) => {
              window.location.href = 'index.php';
            });
          } else {
            throw new Error(data.message || 'Something went wrong');
          }
        })
        .catch(error => {
          Swal.fire({
            title: 'Error!',
            text: error.message || 'Something went wrong. Please try again.',
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#0D9488'
          });
        });
    });
  </script>
</body>

</html>