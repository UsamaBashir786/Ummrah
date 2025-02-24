<?php
session_start();
include 'connection/connection.php';

// Verify admin is logged in
if (!isset($_SESSION['admin_email'])) {
  header("Location: admin/login.php");
  exit();
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
      $sql = "INSERT INTO flights (
                airline_name, flight_number, departure_city, arrival_city, 
                departure_date, departure_time, economy_price, business_price, 
                first_class_price, economy_seats, business_seats, first_class_seats, 
                flight_notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

      $stmt = $conn->prepare($sql);

      // Bind parameters
      $stmt->bind_param(
        "ssssssdddiiis",
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
        $flight_notes
      );

      // Execute the statement
      if ($stmt->execute()) {
        echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Flight added successfully',
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
          <i class="text-teal-600 fas fa-car mx-2"></i> Add New Flight
        </h1>
      </div>

      <!-- Form Container -->
      <div class="overflow-auto container mx-auto px-4 py-8">
        <div class=" mx-auto bg-white p-8 rounded-lg shadow-lg">
          <!-- <h2 class="text-2xl font-bold text-gray-800 mb-6">View Fligth</h2> -->
          <div class="container mx-auto px-4 ">
            <div class=" mx-auto bg-white rounded-lg shadow-lg p-8">
              <div class="mb-6">
                <h1 class="text-2xl font-bold text-teal-600">
                  <i class="fas fa-plane-departure mr-2"></i>Add New Flight
                </h1>
                <p class="text-gray-600 mt-2">Enter flight details for Umrah journey</p>
              </div>
              <form action="" method="POST" class="space-y-6">
                <!-- Airline Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label for="airline_name" class="block text-gray-700 font-semibold mb-2">Airline Name</label>
                    <select name="airline_name" id="airline_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
                      <option value="">Select Airline</option>
                      <option value="PIA">PIA Airlines</option>
                      <option value="Emirates">Emirates Airlines</option>
                      <option value="Qatar">Qatar Airways</option>
                      <option value="Saudi">Saudi Airlines</option>
                      <option value="Flynas">Flynas Airlines</option>
                    </select>
                  </div>
                  <div>
                    <label for="flight_number" class="block text-gray-700 font-semibold mb-2">Flight Number</label>
                    <input type="text" name="flight_number" id="flight_number" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" placeholder="e.g., PK-309" required>
                  </div>
                </div>

                <!-- Route Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label for="departure_city" class="block text-gray-700 font-semibold mb-2">Departure City</label>
                    <select name="departure_city" id="departure_city" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
                      <option value="">Select City</option>
                      <option value="Karachi">Karachi</option>
                      <option value="Lahore">Lahore</option>
                      <option value="Islamabad">Islamabad</option>
                    </select>
                  </div>
                  <div>
                    <label for="arrival_city" class="block text-gray-700 font-semibold mb-2">Arrival City</label>
                    <select name="arrival_city" id="arrival_city" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
                      <option value="">Select City</option>
                      <option value="Jeddah">Jeddah</option>
                      <option value="Medina">Medina</option>
                    </select>
                  </div>
                </div>

                <!-- Schedule -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label for="departure_date" class="block text-gray-700 font-semibold mb-2">Departure Date</label>
                    <input type="date" name="departure_date" id="departure_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
                  </div>
                  <div>
                    <label for="departure_time" class="block text-gray-700 font-semibold mb-2">Departure Time</label>
                    <input type="time" name="departure_time" id="departure_time" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
                  </div>
                </div>

                <!-- Pricing -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div>
                    <label for="economy_price" class="block text-gray-700 font-semibold mb-2">Economy Price ($)</label>
                    <input type="number" name="economy_price" id="economy_price" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" placeholder="850" required>
                  </div>
                  <div>
                    <label for="business_price" class="block text-gray-700 font-semibold mb-2">Business Price ($)</label>
                    <input type="number" name="business_price" id="business_price" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" placeholder="1500" required>
                  </div>
                  <div>
                    <label for="first_class_price" class="block text-gray-700 font-semibold mb-2">First Class Price ($)</label>
                    <input type="number" name="first_class_price" id="first_class_price" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" placeholder="2500" required>
                  </div>
                </div>

                <!-- Capacity -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div>
                    <label for="economy_seats" class="block text-gray-700 font-semibold mb-2">Economy Seats</label>
                    <input type="number" name="economy_seats" id="economy_seats" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" placeholder="200" required>
                  </div>
                  <div>
                    <label for="business_seats" class="block text-gray-700 font-semibold mb-2">Business Seats</label>
                    <input type="number" name="business_seats" id="business_seats" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" placeholder="30" required>
                  </div>
                  <div>
                    <label for="first_class_seats" class="block text-gray-700 font-semibold mb-2">First Class Seats</label>
                    <input type="number" name="first_class_seats" id="first_class_seats" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" placeholder="10" required>
                  </div>
                </div>

                <!-- Additional Information -->
                <div>
                  <label for="flight_notes" class="block text-gray-700 font-semibold mb-2">Flight Notes</label>
                  <textarea name="flight_notes" id="flight_notes" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" rows="3" placeholder="Enter any additional flight information..."></textarea>
                </div>

                <!-- Submit Buttons -->
                <div class="flex gap-4">
                  <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700 transition duration-200">
                    <i class="fas fa-save mr-2"></i> Save Flight
                  </button>
                  <button type="reset" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition duration-200">
                    <i class="fas fa-times mr-2"></i> Cancel
                  </button>
                </div>
              </form>

            </div>
          </div>


        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/js-links.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>

</html>