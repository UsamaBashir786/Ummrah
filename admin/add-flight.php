<?php
session_start();
include 'connection/connection.php';



// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Uncomment for debugging
  /*
    echo "<pre>RAW POST DATA:";
    print_r($_POST);
    echo "</pre>";
    exit();
    */

  $airline_name = trim($_POST['airline_name']);
  $flight_number = trim($_POST['flight_number']);
  $departure_city = trim($_POST['departure_city']);
  $arrival_city = trim($_POST['arrival_city']);
  $departure_date = trim($_POST['departure_date']);
  $departure_time = trim($_POST['departure_time']);
  $flight_notes = trim($_POST['flight_notes'] ?? '');

  // Get price values directly from form fields
  $economy_price = !empty($_POST['economy_price']) ? floatval($_POST['economy_price']) : 0.00;
  $business_price = !empty($_POST['business_price']) ? floatval($_POST['business_price']) : 0.00;
  $first_class_price = !empty($_POST['first_class_price']) ? floatval($_POST['first_class_price']) : 0.00;

  // Create properly formatted JSON for prices
  $prices = json_encode([
    'economy' => $economy_price,
    'business' => $business_price,
    'first_class' => $first_class_price
  ], JSON_NUMERIC_CHECK);

  // Get seat values directly from form fields
  $economy_seats = !empty($_POST['economy_seats']) ? intval($_POST['economy_seats']) : 0;
  $business_seats = !empty($_POST['business_seats']) ? intval($_POST['business_seats']) : 0;
  $first_class_seats = !empty($_POST['first_class_seats']) ? intval($_POST['first_class_seats']) : 0;

  // Generate seat IDs for each class
  $economy_seat_ids = [];
  $business_seat_ids = [];
  $first_class_seat_ids = [];

  // Generate economy seat IDs (format: E1, E2, E3, etc.)
  for ($i = 1; $i <= $economy_seats; $i++) {
    $economy_seat_ids[] = "E" . $i;
  }

  // Generate business seat IDs (format: B1, B2, B3, etc.)
  for ($i = 1; $i <= $business_seats; $i++) {
    $business_seat_ids[] = "B" . $i;
  }

  // Generate first class seat IDs (format: F1, F2, F3, etc.)
  for ($i = 1; $i <= $first_class_seats; $i++) {
    $first_class_seat_ids[] = "F" . $i;
  }

  // Create properly formatted JSON for seats with counts and IDs
  $seats = json_encode([
    'economy' => [
      'count' => $economy_seats,
      'seat_ids' => $economy_seat_ids
    ],
    'business' => [
      'count' => $business_seats,
      'seat_ids' => $business_seat_ids
    ],
    'first_class' => [
      'count' => $first_class_seats,
      'seat_ids' => $first_class_seat_ids
    ]
  ], JSON_NUMERIC_CHECK);

  // Cabin classes
  $cabin_class = json_encode(['Economy', 'Business', 'First Class']);

  // Process flight stops
  $has_stops = isset($_POST['has_stops']) ? intval($_POST['has_stops']) : 0;
  $stops = [];

  if ($has_stops == 1 && isset($_POST['stop_city']) && is_array($_POST['stop_city'])) {
    for ($i = 0; $i < count($_POST['stop_city']); $i++) {
      if (!empty($_POST['stop_city'][$i])) {
        $stops[] = [
          'city' => trim($_POST['stop_city'][$i]),
          'duration' => isset($_POST['stop_duration'][$i]) ? trim($_POST['stop_duration'][$i]) : '0',
        ];
      }
    }
  }

  // Set stops_json to "direct" if direct flight is selected, otherwise use the stops data
  $stops_json = ($has_stops == 0) ? json_encode("direct") : (!empty($stops) ? json_encode($stops) : json_encode("direct"));

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
      // Updated SQL Query with stops
      $sql = "INSERT INTO flights (
                airline_name, flight_number, departure_city, arrival_city,
                departure_date, departure_time, prices, seats, cabin_class, flight_notes, stops
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

      $stmt = $conn->prepare($sql);
      $stmt->bind_param(
        "sssssssssss",
        $airline_name,
        $flight_number,
        $departure_city,
        $arrival_city,
        $departure_date,
        $departure_time,
        $prices,
        $seats,
        $cabin_class,
        $flight_notes,
        $stops_json
      );

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
          <i class="text-teal-600 fas fa-plane mx-2"></i> Add New Flight
        </h1>
      </div>

      <!-- Form Container -->
      <div class="overflow-auto container mx-auto px-4 py-8">
        <div class="mx-auto bg-white p-8 rounded-lg shadow-lg">
          <div class="mb-6">
            <h1 class="text-2xl font-bold text-teal-600">
              <i class="fas fa-plane-departure mr-2"></i>Add New Flight
            </h1>
            <p class="text-gray-600 mt-2">Enter flight details for Umrah journey</p>
          </div>

          <form action="" method="POST" class="space-y-6">
            <!-- Airline & Flight Number -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Airline Name</label>
                <select name="airline_name" class="w-full px-4 py-2 border rounded-lg" required>
                  <option value="">Select Airline</option>
                  <option value="PIA">PIA Airlines</option>
                  <option value="Emirates">Emirates</option>
                  <option value="Qatar">Qatar Airways</option>
                  <option value="Saudi">Saudi Airlines</option>
                  <option value="Flynas">Flynas Airlines</option>
                </select>
              </div>
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Flight Number</label>
                <input type="text" name="flight_number" class="w-full px-4 py-2 border rounded-lg" placeholder="e.g., PK-309" required>
              </div>
            </div>

            <!-- Route Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Departure City</label>
                <select name="departure_city" class="w-full px-4 py-2 border rounded-lg" required>
                  <option value="">Select City</option>
                  <option value="Karachi">Karachi</option>
                  <option value="Lahore">Lahore</option>
                  <option value="Islamabad">Islamabad</option>
                </select>
              </div>
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Arrival City</label>
                <select name="arrival_city" class="w-full px-4 py-2 border rounded-lg" required>
                  <option value="">Select City</option>
                  <option value="Jeddah">Jeddah</option>
                  <option value="Medina">Medina</option>
                </select>
              </div>
            </div>

            <!-- Flight Stops -->
            <div class="border p-4 rounded-lg bg-gray-50">
              <div class="flex items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-700">Flight Stops</h3>
                <div class="ml-4">
                  <label class="inline-flex items-center">
                    <input type="radio" name="has_stops" value="0" class="mr-2" checked onchange="toggleStopsSection(false)">
                    <span>Direct Flight</span>
                  </label>
                  <label class="inline-flex items-center ml-4">
                    <input type="radio" name="has_stops" value="1" class="mr-2" onchange="toggleStopsSection(true)">
                    <span>Has Stops</span>
                  </label>
                </div>
              </div>

              <div id="stops-container" class="hidden space-y-4">
                <!-- Initial stop row -->
                <div class="stop-row grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">Stop City</label>
                    <input type="text" name="stop_city[]" class="w-full px-4 py-2 border rounded-lg" placeholder="e.g., Dubai">
                  </div>
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">Stop Duration (hours)</label>
                    <input type="text" name="stop_duration[]" class="w-full px-4 py-2 border rounded-lg" placeholder="e.g., 2">
                  </div>
                </div>

                <div class="flex justify-end">
                  <button type="button" id="add-stop" class="px-4 py-2 bg-teal-500 text-white rounded-lg hover:bg-teal-600">
                    <i class="fas fa-plus mr-2"></i>Add Another Stop
                  </button>
                </div>
              </div>
            </div>

            <!-- Schedule -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Departure Date</label>
                <input type="date" name="departure_date" class="w-full px-4 py-2 border rounded-lg" required>
              </div>
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Departure Time</label>
                <input type="time" name="departure_time" class="w-full px-4 py-2 border rounded-lg" required>
              </div>
            </div>

            <!-- Pricing -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Economy Price ($)</label>
                <input type="number" name="economy_price" class="w-full px-4 py-2 border rounded-lg" placeholder="850" required>
              </div>
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Business Price ($)</label>
                <input type="number" name="business_price" class="w-full px-4 py-2 border rounded-lg" placeholder="1500" required>
              </div>
              <div>
                <label class="block text-gray-700 font-semibold mb-2">First Class Price ($)</label>
                <input type="number" name="first_class_price" class="w-full px-4 py-2 border rounded-lg" placeholder="2500" required>
              </div>
            </div>

            <!-- Capacity -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Economy Seats</label>
                <input type="number" name="economy_seats" class="w-full px-4 py-2 border rounded-lg" placeholder="200" required>
              </div>
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Business Seats</label>
                <input type="number" name="business_seats" class="w-full px-4 py-2 border rounded-lg" placeholder="30" required>
              </div>
              <div>
                <label class="block text-gray-700 font-semibold mb-2">First Class Seats</label>
                <input type="number" name="first_class_seats" class="w-full px-4 py-2 border rounded-lg" placeholder="10" required>
              </div>
            </div>

            <!-- Flight Notes -->
            <div>
              <label class="block text-gray-700 font-semibold mb-2">Flight Notes (Optional)</label>
              <textarea name="flight_notes" class="w-full px-4 py-2 border rounded-lg" rows="3" placeholder="Any additional information about this flight"></textarea>
            </div>

            <!-- Submit Buttons -->
            <div class="flex gap-4">
              <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700">
                <i class="fas fa-save mr-2"></i> Save Flight
              </button>
              <button type="reset" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">
                <i class="fas fa-times mr-2"></i> Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/js-links.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    // Function to toggle stops section visibility
    function toggleStopsSection(show) {
      const stopsContainer = document.getElementById('stops-container');
      if (show) {
        stopsContainer.classList.remove('hidden');
      } else {
        stopsContainer.classList.add('hidden');
      }
    }

    // Add event listener for the "Add Another Stop" button
    document.addEventListener('DOMContentLoaded', function() {
      const addStopBtn = document.getElementById('add-stop');
      const stopsContainer = document.getElementById('stops-container');

      addStopBtn.addEventListener('click', function() {
        const stopRow = document.querySelector('.stop-row').cloneNode(true);
        const inputs = stopRow.querySelectorAll('input');

        // Clear the inputs in the cloned row
        inputs.forEach(input => input.value = '');

        // Insert the new row before the add button
        stopsContainer.insertBefore(stopRow, addStopBtn.parentNode);
      });
    });
  </script>
</body>

</html>