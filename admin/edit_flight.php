<?php
session_start();
include 'connection/connection.php';

// Verify admin is logged in
if (!isset($_SESSION['admin_email'])) {
  header("Location: admin/login.php");
  exit();
}

// Fetch flight data
if (isset($_GET['id'])) {
  $stmt = $conn->prepare("SELECT * FROM flights WHERE id = ?");
  $stmt->bind_param("i", $_GET['id']);
  $stmt->execute();
  $flight = $stmt->get_result()->fetch_assoc();
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Sanitize and validate inputs
  $airline_name = trim($_POST['airline_name']);
  $flight_number = trim($_POST['flight_number']);
  $departure_city = trim($_POST['departure_city']);
  $arrival_city = trim($_POST['arrival_city']);
  $departure_date = trim($_POST['departure_date']);
  $departure_time = trim($_POST['departure_time']);
  $economy_price = floatval($_POST['economy_price']);
  $business_price = floatval($_POST['business_price']);
  $first_class_price = floatval($_POST['first_class_price']);
  $economy_seats = intval($_POST['economy_seats']);
  $business_seats = intval($_POST['business_seats']);
  $first_class_seats = intval($_POST['first_class_seats']);
  $flight_notes = trim($_POST['flight_notes'] ?? '');
  $flight_id = intval($_POST['flight_id']);

  // Validate required fields
  if (empty($airline_name) || empty($flight_number) || empty($departure_city) || empty($arrival_city)) {
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Missing Fields',
                    text: 'Please fill in all required fields.',
                    confirmButtonText: 'OK'
                });
            });
        </script>";
  } else {
    try {
      // Prepare SQL statement
      $sql = "UPDATE flights SET 
                airline_name = ?, flight_number = ?, departure_city = ?, 
                arrival_city = ?, departure_date = ?, departure_time = ?, 
                economy_price = ?, business_price = ?, first_class_price = ?, 
                economy_seats = ?, business_seats = ?, first_class_seats = ?, 
                flight_notes = ? 
              WHERE id = ?";

      $stmt = $conn->prepare($sql);

      // Bind parameters
      $stmt->bind_param(
        "ssssssdddiiisi",
        $airline_name,
        $flight_number,
        $departure_city,
        $arrival_city,
        $departure_date,
        $departure_time,
        $economy_price,
        $business_price,
        $first_class_price,
        $economy_seats,
        $business_seats,
        $first_class_seats,
        $flight_notes,
        $flight_id
      );

      // Execute the statement
      if ($stmt->execute()) {
        echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Flight updated successfully',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'view-flight.php';
                            }
                        });
                    });
                </script>";
      } else {
        throw new Exception($stmt->error);
      }

      $stmt->close();
    } catch (Exception $e) {
      echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Database error: " . addslashes($e->getMessage()) . "',
                        confirmButtonText: 'OK'
                    });
                });
            </script>";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
</head>

<body class="bg-gray-50">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main flex-1 flex flex-col">
      <!-- Navbar -->
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <button class="md:hidden text-gray-800" id="menu-btn">
          <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-xl font-semibold">
          <i class="text-teal-600 fas fa-plane mx-2"></i> Edit Flight
        </h1>
      </div>

      <!-- Form Container -->
      <div class="overflow-auto container mx-auto px-4 py-8">
        <div class="mx-auto bg-white p-8 rounded-lg shadow-lg">
          <h1 class="text-2xl font-bold text-teal-600 mb-6">Edit Flight</h1>
          <form action="" method="POST" class="space-y-6">
            <input type="hidden" name="flight_id" value="<?php echo $flight['id']; ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="airline_name" class="block text-gray-700 font-semibold mb-2">Airline Name</label>
                <select name="airline_name" id="airline_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
                  <option value="">Select Airline</option>
                  <option value="PIA" <?php echo $flight['airline_name'] == 'PIA' ? 'selected' : ''; ?>>PIA Airlines</option>
                  <option value="Emirates" <?php echo $flight['airline_name'] == 'Emirates' ? 'selected' : ''; ?>>Emirates Airlines</option>
                  <option value="Qatar" <?php echo $flight['airline_name'] == 'Qatar' ? 'selected' : ''; ?>>Qatar Airways</option>
                  <option value="Saudi" <?php echo $flight['airline_name'] == 'Saudi' ? 'selected' : ''; ?>>Saudi Airlines</option>
                  <option value="Flynas" <?php echo $flight['airline_name'] == 'Flynas' ? 'selected' : ''; ?>>Flynas Airlines</option>
                </select>
              </div>
              <div>
                <label for="flight_number" class="block text-gray-700 font-semibold mb-2">Flight Number</label>
                <input type="text" name="flight_number" id="flight_number" value="<?php echo $flight['flight_number']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="departure_city" class="block text-gray-700 font-semibold mb-2">Departure City</label>
                <input type="text" name="departure_city" id="departure_city" value="<?php echo $flight['departure_city']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
              </div>
              <div>
                <label for="arrival_city" class="block text-gray-700 font-semibold mb-2">Arrival City</label>
                <input type="text" name="arrival_city" id="arrival_city" value="<?php echo $flight['arrival_city']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="departure_date" class="block text-gray-700 font-semibold mb-2">Departure Date</label>
                <input type="date" name="departure_date" id="departure_date" value="<?php echo $flight['departure_date']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
              </div>
              <div>
                <label for="departure_time" class="block text-gray-700 font-semibold mb-2">Departure Time</label>
                <input type="time" name="departure_time" id="departure_time" value="<?php echo $flight['departure_time']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
              <!-- </div> -->
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="economy_price" class="block text-gray-700 font-semibold mb-2">Economy Price</label>
                <input type="number" name="economy_price" id="economy_price" value="<?php echo $flight['economy_price']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" step="0.01" required>
              </div>
              <div>
                <label for="business_price" class="block text-gray-700 font-semibold mb-2">Business Price</label>
                <input type="number" name="business_price" id="business_price" value="<?php echo $flight['business_price']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" step="0.01" required>
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="first_class_price" class="block text-gray-700 font-semibold mb-2">First Class Price</label>
                <input type="number" name="first_class_price" id="first_class_price" value="<?php echo $flight['first_class_price']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" step="0.01" required>
              </div>
              <div>
                <label for="economy_seats" class="block text-gray-700 font-semibold mb-2">Economy Seats</label>
                <input type="number" name="economy_seats" id="economy_seats" value="<?php echo $flight['economy_seats']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="business_seats" class="block text-gray-700 font-semibold mb-2">Business Seats</label>
                <input type="number" name="business_seats" id="business_seats" value="<?php echo $flight['business_seats']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
              </div>
              <div>
                <label for="first_class_seats" class="block text-gray-700 font-semibold mb-2">First Class Seats</label>
                <input type="number" name="first_class_seats" id="first_class_seats" value="<?php echo $flight['first_class_seats']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
              </div>
            </div>
            <div>
              <label for="flight_notes" class="block text-gray-700 font-semibold mb-2">Flight Notes</label>
              <textarea name="flight_notes" id="flight_notes" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" rows="4"><?php echo $flight['flight_notes']; ?></textarea>
            </div>
            <button type="submit" class="w-full bg-teal-600 text-white font-semibold py-2 rounded-lg hover:bg-teal-700">Update Flight</button>
          </form>
        </div>
      </div>
    </div>
  </div>
  <?php include 'includes/js-links.php'; ?>
</body>

</html>
