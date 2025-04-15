<?php
session_name("admin_session");
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
  $flight_duration = trim($_POST['flight_duration']);
  $distance = trim($_POST['distance'] ?? ''); // New distance field
  $flight_notes = trim($_POST['flight_notes'] ?? '');

  // Return flight information
  $has_return = isset($_POST['has_return']) ? intval($_POST['has_return']) : 0;
  $return_date = $has_return ? trim($_POST['return_date']) : '';
  $return_time = $has_return ? trim($_POST['return_time']) : '';
  $return_flight_number = $has_return && !empty($_POST['return_flight_number']) ? trim($_POST['return_flight_number']) : '';
  $return_flight_duration = $has_return && !empty($_POST['return_flight_duration']) ? trim($_POST['return_flight_duration']) : '';
  $return_airline = $has_return && !empty($_POST['return_airline']) ? trim($_POST['return_airline']) : $airline_name;

  // Return flight stops
  $has_return_stops = isset($_POST['has_return_stops']) ? intval($_POST['has_return_stops']) : 0;
  $return_stops = [];

  if ($has_return && $has_return_stops == 1 && isset($_POST['return_stop_city']) && is_array($_POST['return_stop_city'])) {
    for ($i = 0; $i < count($_POST['return_stop_city']); $i++) {
      if (!empty($_POST['return_stop_city'][$i])) {
        $return_stops[] = [
          'city' => trim($_POST['return_stop_city'][$i]),
          'duration' => isset($_POST['return_stop_duration'][$i]) ? trim($_POST['return_stop_duration'][$i]) : '0',
        ];
      }
    }
  }

  // Set return_stops_json
  $return_stops_json = ($has_return_stops == 0) ? json_encode("direct") : (!empty($return_stops) ? json_encode($return_stops) : json_encode("direct"));

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

  // Create properly formatted JSON for return flight data
  $return_flight_json = json_encode([
    'has_return' => $has_return,
    'return_date' => $return_date,
    'return_time' => $return_time,
    'return_flight_number' => $return_flight_number,
    'return_flight_duration' => $return_flight_duration,
    'return_airline' => $return_airline,
    'has_return_stops' => $has_return_stops,
    'return_stops' => $return_stops_json
  ]);

  // Form validation
  $errors = [];

  if (empty($airline_name)) {
    $errors[] = "Airline name is required";
  }

  if (empty($flight_number)) {
    $errors[] = "Flight number is required";
  }

  if (empty($departure_city)) {
    $errors[] = "Departure city is required";
  }

  if (empty($arrival_city)) {
    $errors[] = "Arrival city is required";
  }

  if (empty($departure_date)) {
    $errors[] = "Departure date is required";
  }

  if (empty($departure_time)) {
    $errors[] = "Departure time is required";
  }

  if (empty($flight_duration)) {
    $errors[] = "Flight duration is required";
  }

  if (empty($distance)) {
    $errors[] = "Flight distance is required";
  }

  // Validate return flight fields if round trip is selected
  if ($has_return) {
    if (empty($return_date)) {
      $errors[] = "Return date is required for round trips";
    }

    if (empty($return_time)) {
      $errors[] = "Return time is required for round trips";
    }

    if (empty($return_flight_number)) {
      $errors[] = "Return flight number is required for round trips";
    }

    if (empty($return_flight_duration)) {
      $errors[] = "Return flight duration is required for round trips";
    }

    // If return has stops, validate return stops
    if ($has_return_stops && empty($return_stops)) {
      $errors[] = "Return stop information is incomplete";
    }
  }

  if (!empty($errors)) {
    $error_message = implode(", ", $errors);
    echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Missing Fields',
                        text: '{$error_message}',
                        confirmButtonText: 'OK'
                    });
                });
            </script>";
  } else {
    try {
      // Updated SQL Query with flight duration, distance, and enhanced return flight data
      $sql = "INSERT INTO flights (
                airline_name, flight_number, departure_city, arrival_city,
                departure_date, departure_time, flight_duration, distance, prices, seats, 
                cabin_class, flight_notes, stops, return_flight_data
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

      $stmt = $conn->prepare($sql);
      $stmt->bind_param(
        "ssssssssssssss",
        $airline_name,
        $flight_number,
        $departure_city,
        $arrival_city,
        $departure_date,
        $departure_time,
        $flight_duration,
        $distance,
        $prices,
        $seats,
        $cabin_class,
        $flight_notes,
        $stops_json,
        $return_flight_json
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
        <h1 class="text-xl font-semibold flex items-center">
          <i class="text-teal-600 fas fa-plane mx-2"></i> Add New Flight
        </h1>

        <div class="flex items-center gap-4">
          <button onclick="history.back()" class="text-gray-800 hover:text-teal-600">
            <i class="fas fa-arrow-left mr-1"></i> Back
          </button>
          <button class="md:hidden text-gray-800" id="menu-btn">
            <i class="fas fa-bars"></i>
          </button>
        </div>
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

          <form action="" method="POST" class="space-y-6" id="flightForm">
            <!-- Outbound Flight Section Title -->
            <div class="border-b border-gray-200 pb-2 mb-4">
              <h2 class="text-xl font-bold text-teal-700">
                <i class="fas fa-plane-departure mr-2"></i>Outbound Flight Details
              </h2>
            </div>

            <!-- Airline & Flight Number -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Airline Name <span class="text-red-500">*</span></label>
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
                <label class="block text-gray-700 font-semibold mb-2">Flight Number <span class="text-red-500">*</span></label>
                <input type="text" name="flight_number" class="w-full px-4 py-2 border rounded-lg" placeholder="e.g., PK-309" required>
              </div>
            </div>

            <!-- Route Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Departure City <span class="text-red-500">*</span></label>
                <select name="departure_city" class="w-full px-4 py-2 border rounded-lg" required>
                  <option value="">Select City</option>
                  <option value="Karachi">Karachi</option>
                  <option value="Lahore">Lahore</option>
                  <option value="Islamabad">Islamabad</option>
                </select>
              </div>
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Arrival City <span class="text-red-500">*</span></label>
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
                    <label class="block text-gray-700 font-semibold mb-2">Stop City <span class="text-red-500">*</span></label>
                    <input type="text" name="stop_city[]" class="w-full px-4 py-2 border rounded-lg" placeholder="e.g., Dubai">
                  </div>
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">Stop Duration (hours) <span class="text-red-500">*</span></label>
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

            <!-- Schedule and Duration -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Departure Date <span class="text-red-500">*</span></label>
                <input type="date" name="departure_date" class="w-full px-4 py-2 border rounded-lg" required>
              </div>
              <!-- Replace your existing time input with this -->
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Departure Time <span class="text-red-500">*</span></label>
                <input
                  type="text"
                  name="departure_time"
                  class="w-full px-4 py-2 border rounded-lg"
                  placeholder="HH:MM (24-hour format)"
                  pattern="([01]?[0-9]|2[0-3]):[0-5][0-9]"
                  required>
                <small class="text-gray-500">Enter time in 24-hour format (00:00 to 23:59)</small>
              </div>

              <script>
                document.addEventListener('DOMContentLoaded', function() {
                  // Get the time input
                  const timeInput = document.querySelector('input[name="departure_time"]');

                  // Add validation and formatting
                  timeInput.addEventListener('input', function(e) {
                    let value = e.target.value;

                    // Only allow digits and colon
                    value = value.replace(/[^0-9:]/g, '');

                    // Auto-add colon after 2 digits if not already there
                    if (value.length === 2 && !value.includes(':')) {
                      value += ':';
                    }

                    // Limit to 5 chars (HH:MM)
                    if (value.length > 5) {
                      value = value.substring(0, 5);
                    }

                    // Validate hours (00-23)
                    if (value.includes(':') && value.split(':')[0].length === 2) {
                      const hours = parseInt(value.split(':')[0]);
                      if (hours > 23) {
                        value = '23' + value.substring(2);
                      }
                    }

                    // Update the input value
                    e.target.value = value;
                  });

                  // Do the same for return time if it exists
                  const returnTimeInput = document.querySelector('input[name="return_time"]');
                  if (returnTimeInput) {
                    // Apply the same pattern and processing
                    returnTimeInput.type = 'text';
                    returnTimeInput.setAttribute('pattern', '([01]?[0-9]|2[0-3]):[0-5][0-9]');
                    returnTimeInput.setAttribute('placeholder', 'HH:MM (24-hour format)');

                    returnTimeInput.addEventListener('input', function(e) {
                      let value = e.target.value;
                      value = value.replace(/[^0-9:]/g, '');
                      if (value.length === 2 && !value.includes(':')) {
                        value += ':';
                      }
                      if (value.length > 5) {
                        value = value.substring(0, 5);
                      }
                      if (value.includes(':') && value.split(':')[0].length === 2) {
                        const hours = parseInt(value.split(':')[0]);
                        if (hours > 23) {
                          value = '23' + value.substring(2);
                        }
                      }
                      e.target.value = value;
                    });
                  }
                });
              </script>
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Flight Duration (hours) <span class="text-red-500">*</span></label>
                <input type="text" name="flight_duration" class="w-full px-4 py-2 border rounded-lg" placeholder="e.g., 5.5" required>
              </div>
            </div>

            <!-- Distance Field -->
            <div>
              <label class="block text-gray-700 font-semibold mb-2">Distance (km) <span class="text-red-500">*</span></label>
              <input type="text" name="distance" class="w-full px-4 py-2 border rounded-lg" placeholder="e.g., 3500" required>
            </div>

            <!-- Return Flight Section -->
            <div class="border-t border-gray-200 pt-6 mt-6">
              <div class="mb-4">
                <h2 class="text-xl font-bold text-purple-700">
                  <i class="fas fa-plane-arrival mr-2"></i>Return Flight Details
                </h2>
              </div>

              <div class="flex items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-700">Journey Type</h3>
                <div class="ml-4">
                  <label class="inline-flex items-center">
                    <input type="radio" name="has_return" value="0" class="mr-2" checked onchange="toggleReturnSection(false)">
                    <span>One-way Flight</span>
                  </label>
                  <label class="inline-flex items-center ml-4">
                    <input type="radio" name="has_return" value="1" class="mr-2" onchange="toggleReturnSection(true)">
                    <span>Round Trip</span>
                  </label>
                </div>
              </div>

              <div id="return-container" class="hidden border p-4 rounded-lg bg-gray-50 space-y-6">
                <!-- Return Flight Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">Return Airline <span class="text-red-500">*</span></label>
                    <select name="return_airline" class="w-full px-4 py-2 border rounded-lg return-required">
                      <option value="">Select Airline</option>
                      <option value="PIA">PIA Airlines</option>
                      <option value="Emirates">Emirates</option>
                      <option value="Qatar">Qatar Airways</option>
                      <option value="Saudi">Saudi Airlines</option>
                      <option value="Flynas">Flynas Airlines</option>
                      <option value="same">Same as Outbound</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">Return Flight Number <span class="text-red-500">*</span></label>
                    <input type="text" name="return_flight_number" class="w-full px-4 py-2 border rounded-lg return-required" placeholder="e.g., PK-310">
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">Return Date <span class="text-red-500">*</span></label>
                    <input type="date" name="return_date" class="w-full px-4 py-2 border rounded-lg return-required">
                  </div>
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">Return Time <span class="text-red-500">*</span></label>
                    <input type="time" name="return_time" class="w-full px-4 py-2 border rounded-lg return-required">
                  </div>
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">Return Flight Duration (hours) <span class="text-red-500">*</span></label>
                    <input type="text" name="return_flight_duration" class="w-full px-4 py-2 border rounded-lg return-required" placeholder="e.g., 5.5">
                  </div>
                </div>

                <!-- Return Flight Stops -->
                <div class="mt-4">
                  <div class="flex items-center mb-4">
                    <h4 class="text-md font-semibold text-gray-700">Return Flight Stops</h4>
                    <div class="ml-4">
                      <label class="inline-flex items-center">
                        <input type="radio" name="has_return_stops" value="0" class="mr-2" checked onchange="toggleReturnStopsSection(false)">
                        <span>Direct Return Flight</span>
                      </label>
                      <label class="inline-flex items-center ml-4">
                        <input type="radio" name="has_return_stops" value="1" class="mr-2" onchange="toggleReturnStopsSection(true)">
                        <span>Has Stops</span>
                      </label>
                    </div>
                  </div>

                  <div id="return-stops-container" class="hidden space-y-4">
                    <!-- Initial return stop row -->
                    <div class="return-stop-row grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div>
                        <label class="block text-gray-700 font-semibold mb-2">Return Stop City <span class="text-red-500">*</span></label>
                        <input type="text" name="return_stop_city[]" class="w-full px-4 py-2 border rounded-lg return-stop-required" placeholder="e.g., Dubai">
                      </div>
                      <div>
                        <label class="block text-gray-700 font-semibold mb-2">Return Stop Duration (hours) <span class="text-red-500">*</span></label>
                        <input type="text" name="return_stop_duration[]" class="w-full px-4 py-2 border rounded-lg return-stop-required" placeholder="e.g., 2">
                      </div>
                    </div>

                    <div class="flex justify-end">
                      <button type="button" id="add-return-stop" class="px-4 py-2 bg-teal-500 text-white rounded-lg hover:bg-teal-600">
                        <i class="fas fa-plus mr-2"></i>Add Another Return Stop
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pricing Section -->
            <div class="border-t border-gray-200 pt-6 mt-6">
              <div class="mb-4">
                <h2 class="text-xl font-bold text-teal-700">
                  <i class="fas fa-tags mr-2"></i>Pricing Information
                </h2>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                  <label class="block text-gray-700 font-semibold mb-2">Economy Price ($) <span class="text-red-500">*</span></label>
                  <input type="number" name="economy_price" class="w-full px-4 py-2 border rounded-lg" placeholder="850" required>
                </div>
                <div>
                  <label class="block text-gray-700 font-semibold mb-2">Business Price ($) <span class="text-red-500">*</span></label>
                  <input type="number" name="business_price" class="w-full px-4 py-2 border rounded-lg" placeholder="1500" required>
                </div>
                <div>
                  <label class="block text-gray-700 font-semibold mb-2">First Class Price ($) <span class="text-red-500">*</span></label>
                  <input type="number" name="first_class_price" class="w-full px-4 py-2 border rounded-lg" placeholder="2500" required>
                </div>
              </div>
            </div>

            <!-- Capacity Section -->
            <div class="border-t border-gray-200 pt-6 mt-6">
              <div class="mb-4">
                <h2 class="text-xl font-bold text-teal-700">
                  <i class="fas fa-chair mr-2"></i>Seat Information
                </h2>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                  <label class="block text-gray-700 font-semibold mb-2">Economy Seats <span class="text-red-500">*</span></label>
                  <input type="number" name="economy_seats" class="w-full px-4 py-2 border rounded-lg" placeholder="200" required>
                </div>
                <div>
                  <label class="block text-gray-700 font-semibold mb-2">Business Seats <span class="text-red-500">*</span></label>
                  <input type="number" name="business_seats" class="w-full px-4 py-2 border rounded-lg" placeholder="30" required>
                </div>
                <div>
                  <label class="block text-gray-700 font-semibold mb-2">First Class Seats <span class="text-red-500">*</span></label>
                  <input type="number" name="first_class_seats" class="w-full px-4 py-2 border rounded-lg" placeholder="10" required>
                </div>
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
                <i class="fas fa-times mr-2"></i>Reset
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
        document.querySelectorAll('input[name="stop_city[]"]').forEach(input => {
          input.setAttribute('required', 'required');
        });
        document.querySelectorAll('input[name="stop_duration[]"]').forEach(input => {
          input.setAttribute('required', 'required');
        });
      } else {
        stopsContainer.classList.add('hidden');
        document.querySelectorAll('input[name="stop_city[]"]').forEach(input => {
          input.removeAttribute('required');
        });
        document.querySelectorAll('input[name="stop_duration[]"]').forEach(input => {
          input.removeAttribute('required');
        });
      }
    }

    // Function to toggle return flight section visibility
    function toggleReturnSection(show) {
      const returnContainer = document.getElementById('return-container');
      const returnRequiredFields = document.querySelectorAll('.return-required');

      if (show) {
        returnContainer.classList.remove('hidden');
        returnRequiredFields.forEach(field => {
          field.setAttribute('required', 'required');
        });
      } else {
        returnContainer.classList.add('hidden');
        returnRequiredFields.forEach(field => {
          field.removeAttribute('required');
        });

        // Also uncheck and disable return stops
        document.querySelector('input[name="has_return_stops"][value="0"]').checked = true;
        toggleReturnStopsSection(false);
      }
    }

    // Function to toggle return stops section visibility
    function toggleReturnStopsSection(show) {
      const returnStopsContainer = document.getElementById('return-stops-container');
      const returnStopRequiredFields = document.querySelectorAll('.return-stop-required');

      if (show) {
        returnStopsContainer.classList.remove('hidden');
        returnStopRequiredFields.forEach(field => {
          field.setAttribute('required', 'required');
        });
      } else {
        returnStopsContainer.classList.add('hidden');
        returnStopRequiredFields.forEach(field => {
          field.removeAttribute('required');
        });
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
        inputs.forEach(input => {
          input.value = '';
          if (document.querySelector('input[name="has_stops"]:checked').value === "1") {
            input.setAttribute('required', 'required');
          }
        });

        // Insert the new row before the add button
        stopsContainer.insertBefore(stopRow, addStopBtn.parentNode);
      });

      // Add event listener for the "Add Another Return Stop" button
      const addReturnStopBtn = document.getElementById('add-return-stop');
      const returnStopsContainer = document.getElementById('return-stops-container');

      if (addReturnStopBtn) {
        addReturnStopBtn.addEventListener('click', function() {
          const returnStopRow = document.querySelector('.return-stop-row').cloneNode(true);
          const inputs = returnStopRow.querySelectorAll('input');

          // Clear the inputs in the cloned row
          inputs.forEach(input => {
            input.value = '';
            if (document.querySelector('input[name="has_return_stops"]:checked').value === "1" &&
              document.querySelector('input[name="has_return"]:checked').value === "1") {
              input.setAttribute('required', 'required');
            }
          });

          // Insert the new row before the add button
          returnStopsContainer.insertBefore(returnStopRow, addReturnStopBtn.parentNode);
        });
      }

      // Handle return airline dropdown
      const returnAirlineSelect = document.querySelector('select[name="return_airline"]');
      if (returnAirlineSelect) {
        returnAirlineSelect.addEventListener('change', function() {
          if (this.value === 'same') {
            // Get the outbound airline value
            const outboundAirline = document.querySelector('select[name="airline_name"]').value;
            // Just show a message without actually changing the value
            if (outboundAirline) {
              Swal.fire({
                icon: 'info',
                title: 'Return Airline',
                text: 'Return airline will be set to: ' + outboundAirline,
                confirmButtonText: 'OK'
              });
            } else {
              Swal.fire({
                icon: 'warning',
                title: 'Outbound Airline Not Selected',
                text: 'Please select an outbound airline first',
                confirmButtonText: 'OK'
              });
              // Reset to empty option
              this.value = '';
            }
          }
        });
      }

      // Form validation
      const flightForm = document.getElementById('flightForm');
      if (flightForm) {
        flightForm.addEventListener('submit', function(event) {
          // Check return date is after departure date if round trip
          if (document.querySelector('input[name="has_return"]:checked').value === "1") {
            const departureDate = new Date(document.querySelector('input[name="departure_date"]').value);
            const returnDate = new Date(document.querySelector('input[name="return_date"]').value);

            if (returnDate <= departureDate) {
              event.preventDefault();
              Swal.fire({
                icon: 'error',
                title: 'Invalid Return Date',
                text: 'Return date must be after departure date',
                confirmButtonText: 'OK'
              });
            }
          }
        });
      }
    });
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const flightForm = document.getElementById('flightForm');

      // Validate Departure Date
      const departureDateInput = document.querySelector('input[name="departure_date"]');
      const returnDateInput = document.querySelector('input[name="return_date"]');

      // Set min date to today
      const today = new Date().toISOString().split('T')[0];
      departureDateInput.setAttribute('min', today);

      if (returnDateInput) {
        returnDateInput.setAttribute('min', today);
      }

      // Validate Flight Duration
      const flightDurationInput = document.querySelector('input[name="flight_duration"]');
      const returnFlightDurationInput = document.querySelector('input[name="return_flight_duration"]');

      function validateFlightDuration(input) {
        input.addEventListener('input', function() {
          const duration = parseFloat(this.value);
          if (duration > 8) {
            this.setCustomValidity('Flight duration cannot exceed 8 hours');
            this.reportValidity();
          } else if (duration <= 0) {
            this.setCustomValidity('Flight duration must be a positive number');
            this.reportValidity();
          } else {
            this.setCustomValidity('');
          }
        });
      }

      validateFlightDuration(flightDurationInput);
      if (returnFlightDurationInput) {
        validateFlightDuration(returnFlightDurationInput);
      }

      // Validate Distance
      const distanceInput = document.querySelector('input[name="distance"]');

      distanceInput.addEventListener('input', function() {
        const distance = parseFloat(this.value);
        // Typical long-haul flight distance range (approximately 3000-6000 km)
        if (distance > 6000 || distance <= 0) {
          this.setCustomValidity('Distance must be between 1 and 6000 km');
          this.reportValidity();
        } else {
          this.setCustomValidity('');
        }
      });

      // Validate Pricing
      const economyPriceInput = document.querySelector('input[name="economy_price"]');
      const businessPriceInput = document.querySelector('input[name="business_price"]');
      const firstClassPriceInput = document.querySelector('input[name="first_class_price"]');

      function validatePricing() {
        const economyPrice = parseFloat(economyPriceInput.value);
        const businessPrice = parseFloat(businessPriceInput.value);
        const firstClassPrice = parseFloat(firstClassPriceInput.value);

        // Validate prices are positive
        if (economyPrice <= 0 || businessPrice <= 0 || firstClassPrice <= 0) {
          Swal.fire({
            icon: 'error',
            title: 'Invalid Pricing',
            text: 'Prices must be positive numbers',
            confirmButtonText: 'OK'
          });
          return false;
        }

        // Validate price hierarchy
        if (businessPrice <= economyPrice || firstClassPrice <= businessPrice) {
          Swal.fire({
            icon: 'error',
            title: 'Invalid Pricing',
            text: 'Prices must increase: Economy < Business < First Class',
            confirmButtonText: 'OK'
          });
          return false;
        }

        return true;
      }

      // Validate Seats
      const economySeatsInput = document.querySelector('input[name="economy_seats"]');
      const businessSeatsInput = document.querySelector('input[name="business_seats"]');
      const firstClassSeatsInput = document.querySelector('input[name="first_class_seats"]');

      function validateSeats() {
        const validateSeatInput = (input, className) => {
          const seats = parseInt(input.value);

          // Check if seats are a reasonable number for a typical aircraft
          if (seats <= 0 || seats > 500) {
            input.setCustomValidity(`Invalid number of ${className} seats`);
            input.reportValidity();
            return false;
          }

          input.setCustomValidity('');
          return true;
        };

        const economyValid = validateSeatInput(economySeatsInput, 'economy');
        const businessValid = validateSeatInput(businessSeatsInput, 'business');
        const firstClassValid = validateSeatInput(firstClassSeatsInput, 'first class');

        return economyValid && businessValid && firstClassValid;
      }

      // Form submission validation
      flightForm.addEventListener('submit', function(event) {
        // Stop form submission if validations fail
        if (!validatePricing() || !validateSeats()) {
          event.preventDefault();
        }

        // Additional validations already handled by HTML5 validation and previous event listeners
      });

      // Optional: Real-time price validation on input
      [economyPriceInput, businessPriceInput, firstClassPriceInput].forEach(input => {
        input.addEventListener('change', validatePricing);
      });

      // Optional: Real-time seats validation on input
      [economySeatsInput, businessSeatsInput, firstClassSeatsInput].forEach(input => {
        input.addEventListener('change', validateSeats);
      });
    });
  </script>
</body>

</html>