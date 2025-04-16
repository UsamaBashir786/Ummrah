<?php
session_name("admin_session");
session_start();
include 'connection/connection.php';

// Handle AJAX validation requests
if (isset($_POST['validation_check']) && $_POST['validation_check'] === 'true') {
  $response = array('status' => 'success', 'errors' => array());

  // Extract the field being validated
  $field_name = $_POST['field_name'] ?? '';
  $field_value = $_POST['field_value'] ?? '';

  // Validate based on field
  switch ($field_name) {
    case 'airline_name':
      if (empty($field_value)) {
        $response['errors'][] = 'Airline name is required';
        $response['status'] = 'error';
      }
      break;

    case 'flight_number':
      if (empty($field_value)) {
        $response['errors'][] = 'Flight number is required';
        $response['status'] = 'error';
      } else if (!preg_match('/^[A-Z]{2,3}-\d{1,4}$/', $field_value)) {
        $response['errors'][] = 'Flight number must be in format: 2-3 letters, dash, 1-4 numbers (e.g., PK-309)';
        $response['status'] = 'error';
      }
      break;

    case 'departure_city':
      if (empty($field_value)) {
        $response['errors'][] = 'Departure city is required';
        $response['status'] = 'error';
      }
      break;

    case 'arrival_city':
      if (empty($field_value)) {
        $response['errors'][] = 'Arrival city is required';
        $response['status'] = 'error';
      }
      break;

    case 'departure_date':
      if (empty($field_value)) {
        $response['errors'][] = 'Departure date is required';
        $response['status'] = 'error';
      }
      break;

    case 'departure_time':
      if (empty($field_value)) {
        $response['errors'][] = 'Departure time is required';
        $response['status'] = 'error';
      } else if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $field_value)) {
        $response['errors'][] = 'Departure time must be in HH:MM (24-hour) format';
        $response['status'] = 'error';
      }
      break;

    case 'flight_duration':
      if (empty($field_value)) {
        $response['errors'][] = 'Flight duration is required';
        $response['status'] = 'error';
      } else if (floatval($field_value) <= 0 || floatval($field_value) > 8) {
        $response['errors'][] = 'Flight duration must be between 0 and 8 hours';
        $response['status'] = 'error';
      }
      break;

    case 'distance':
      if (empty($field_value)) {
        $response['errors'][] = 'Distance is required';
        $response['status'] = 'error';
      } else if (intval($field_value) <= 0 || intval($field_value) > 20000) {
        $response['errors'][] = 'Distance must be between 1 and 20,000 kilometers';
        $response['status'] = 'error';
      }
      break;

    case 'return_date':
      if (!empty($_POST['has_return']) && $_POST['has_return'] === '1' && empty($field_value)) {
        $response['errors'][] = 'Return date is required for round trips';
        $response['status'] = 'error';
      }
      if (!empty($field_value) && !empty($_POST['departure_date'])) {
        $dep_date = strtotime($_POST['departure_date']);
        $ret_date = strtotime($field_value);
        if ($ret_date <= $dep_date) {
          $response['errors'][] = 'Return date must be after departure date';
          $response['status'] = 'error';
        }
      }
      break;

    case 'return_time':
      if (!empty($_POST['has_return']) && $_POST['has_return'] === '1' && empty($field_value)) {
        $response['errors'][] = 'Return time is required for round trips';
        $response['status'] = 'error';
      }
      if (!empty($field_value) && !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $field_value)) {
        $response['errors'][] = 'Return time must be in HH:MM (24-hour) format';
        $response['status'] = 'error';
      }
      break;

    case 'return_flight_number':
      if (!empty($_POST['has_return']) && $_POST['has_return'] === '1' && empty($field_value)) {
        $response['errors'][] = 'Return flight number is required for round trips';
        $response['status'] = 'error';
      }
      if (!empty($field_value) && !preg_match('/^[A-Z]{2,3}-\d{1,4}$/', $field_value)) {
        $response['errors'][] = 'Return flight number must be in format: 2-3 letters, dash, 1-4 numbers (e.g., PK-310)';
        $response['status'] = 'error';
      }
      break;

    case 'return_flight_duration':
      if (!empty($_POST['has_return']) && $_POST['has_return'] === '1' && empty($field_value)) {
        $response['errors'][] = 'Return flight duration is required for round trips';
        $response['status'] = 'error';
      }
      if (!empty($field_value) && (floatval($field_value) <= 0 || floatval($field_value) > 8)) {
        $response['errors'][] = 'Return flight duration must be between 0 and 8 hours';
        $response['status'] = 'error';
      }
      break;

    case 'economy_price':
      if (empty($field_value)) {
        $response['errors'][] = 'Economy price is required';
        $response['status'] = 'error';
      } else if (floatval($field_value) < 242250 || floatval($field_value) > 342000) {
        $response['errors'][] = 'Economy price must be between 242,250 PKR and 342,000 PKR';
        $response['status'] = 'error';
      }

      // Check price relationship with business class
      if (
        isset($_POST['business_price']) && !empty($_POST['business_price']) &&
        floatval($field_value) >= floatval($_POST['business_price'])
      ) {
        $response['errors'][] = 'Economy price must be less than business price';
        $response['status'] = 'error';
      }
      break;

    case 'business_price':
      if (empty($field_value)) {
        $response['errors'][] = 'Business price is required';
        $response['status'] = 'error';
      } else if (floatval($field_value) < 427500 || floatval($field_value) > 513000) {
        $response['errors'][] = 'Business price must be between 427,500 PKR and 513,000 PKR';
        $response['status'] = 'error';
      }

      // Check price relationships
      if (
        isset($_POST['economy_price']) && !empty($_POST['economy_price']) &&
        floatval($field_value) <= floatval($_POST['economy_price'])
      ) {
        $response['errors'][] = 'Business price must be greater than economy price';
        $response['status'] = 'error';
      }

      if (
        isset($_POST['first_class_price']) && !empty($_POST['first_class_price']) &&
        floatval($field_value) >= floatval($_POST['first_class_price'])
      ) {
        $response['errors'][] = 'Business price must be less than first class price';
        $response['status'] = 'error';
      }
      break;

    case 'first_class_price':
      if (empty($field_value)) {
        $response['errors'][] = 'First class price is required';
        $response['status'] = 'error';
      } else if (floatval($field_value) < 712500 || floatval($field_value) > 855000) {
        $response['errors'][] = 'First class price must be between 712,500 PKR and 855,000 PKR';
        $response['status'] = 'error';
      }

      // Check price relationship with business class
      if (
        isset($_POST['business_price']) && !empty($_POST['business_price']) &&
        floatval($field_value) <= floatval($_POST['business_price'])
      ) {
        $response['errors'][] = 'First class price must be greater than business price';
        $response['status'] = 'error';
      }
      break;

    case 'economy_seats':
      if (empty($field_value)) {
        $response['errors'][] = 'Economy seats is required';
        $response['status'] = 'error';
      } else if (intval($field_value) < 100 || intval($field_value) > 500) {
        $response['errors'][] = 'Economy seats must be between 100 and 500';
        $response['status'] = 'error';
      }
      break;

    case 'business_seats':
      if (empty($field_value)) {
        $response['errors'][] = 'Business seats is required';
        $response['status'] = 'error';
      } else if (intval($field_value) < 10 || intval($field_value) > 100) {
        $response['errors'][] = 'Business seats must be between 10 and 100';
        $response['status'] = 'error';
      }
      break;

    case 'first_class_seats':
      if (empty($field_value)) {
        $response['errors'][] = 'First class seats is required';
        $response['status'] = 'error';
      } else if (intval($field_value) < 5 || intval($field_value) > 50) {
        $response['errors'][] = 'First class seats must be between 5 and 50';
        $response['status'] = 'error';
      }
      break;
  }

  // Return JSON response
  header('Content-Type: application/json');
  echo json_encode($response);
  exit;
}

// Process form submission (your original code with some minor adjustments to work with AJAX)
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['validation_check'])) {
  $airline_name = trim($_POST['airline_name']);
  $flight_number = trim($_POST['flight_number']);
  $departure_city = trim($_POST['departure_city']);
  $arrival_city = trim($_POST['arrival_city']);
  $departure_date = trim($_POST['departure_date']);
  $departure_time = trim($_POST['departure_time']);
  $flight_duration = trim($_POST['flight_duration']);
  $distance = trim($_POST['distance'] ?? '');
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
  <style>
    .error-feedback {
      color: #dc3545;
      font-size: 0.875rem;
      margin-top: 0.25rem;
      display: none;
    }

    .is-invalid {
      border-color: #dc3545 !important;
    }

    .is-valid {
      border-color: #198754 !important;
    }
  </style>
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
                <select name="airline_name" id="airline_name" class="w-full px-4 py-2 border rounded-lg validate-field" required data-validate="true">
                  <option value="">Select Airline</option>
                  <option value="PIA">PIA Airlines</option>
                  <option value="Emirates">Emirates</option>
                  <option value="Qatar">Qatar Airways</option>
                  <option value="Saudi">Saudi Airlines</option>
                  <option value="Flynas">Flynas Airlines</option>
                </select>
                <div class="error-feedback" id="airline_name-error"></div>
              </div>
              <div>
                <label class="block text-gray-700 font-semibold mb-2">
                  Flight Number <span class="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  name="flight_number"
                  id="flight_number"
                  class="w-full px-4 py-2 border rounded-lg validate-field"
                  placeholder="e.g., PK-309"
                  required
                  maxlength="9"
                  data-validate="true" />
                <div class="error-feedback" id="flight_number-error"></div>
              </div>
              <script>
                document.getElementById('flight_number').addEventListener('input', function(e) {
                  let value = e.target.value;

                  // Allow only 2-3 English letters at the beginning
                  value = value.replace(/[^A-Za-z-0-9]/g, ''); // Removes non-alphanumeric characters (except for '-')

                  // Limit the first part to 2-3 letters
                  let firstPart = value.match(/^[A-Za-z]{0,3}/);
                  firstPart = firstPart ? firstPart[0] : '';

                  // Automatically insert dash after the first 2-3 letters
                  let secondPart = value.slice(firstPart.length).replace(/[^0-9]/g, ''); // Only allow numbers after the dash

                  if (firstPart.length >= 2) {
                    secondPart = secondPart.slice(0, 3); // Limit to 3 digits after the dash
                  }

                  // Combine the two parts
                  e.target.value = firstPart + (firstPart.length >= 2 ? '-' : '') + secondPart;

                  // Provide error message if the input doesn't follow the required format
                  if (firstPart.length < 2 || secondPart.length < 3) {
                    document.getElementById('flight_number-error').textContent = "Please enter a valid flight number (e.g., PK-309).";
                  } else {
                    document.getElementById('flight_number-error').textContent = "";
                  }
                });
              </script>
            </div>

            <!-- Route Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Departure City <span class="text-red-500">*</span></label>
                <select name="departure_city" id="departure_city" class="w-full px-4 py-2 border rounded-lg validate-field" required data-validate="true">
                  <option value="">Select City</option>
                  <option value="Karachi">Karachi</option>
                  <option value="Lahore">Lahore</option>
                  <option value="Islamabad">Islamabad</option>
                </select>
                <div class="error-feedback" id="departure_city-error"></div>
              </div>
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Arrival City <span class="text-red-500">*</span></label>
                <select name="arrival_city" id="arrival_city" class="w-full px-4 py-2 border rounded-lg validate-field" required data-validate="true">
                  <option value="">Select City</option>
                  <option value="Jeddah">Jeddah</option>
                  <option value="Medina">Medina</option>
                </select>
                <div class="error-feedback" id="arrival_city-error"></div>
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
                    <label class="block text-gray-700 font-semibold mb-2">
                      Stop City <span class="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      name="stop_city[]"
                      class="stop-city w-full px-4 py-2 border rounded-lg"
                      maxlength="12"
                      placeholder="e.g., Dubai">
                  </div>
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                      Stop Duration (hours) <span class="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      name="stop_duration[]"
                      class="stop-duration-input w-full px-4 py-2 border rounded-lg"
                      placeholder="e.g., 4">
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
                <label class="block text-gray-700 font-semibold mb-2">
                  Departure Date <span class="text-red-500">*</span>
                </label>
                <input
                  type="date"
                  name="departure_date"
                  id="departure_date"
                  class="w-full px-4 py-2 border rounded-lg validate-field"
                  min="1940-01-01"
                  required
                  data-validate="true"
                  onkeydown="return false;">
                <div class="error-feedback" id="departure_date-error"></div>
              </div>
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Departure Time <span class="text-red-500">*</span></label>
                <input
                  type="text"
                  name="departure_time"
                  id="departure_time"
                  class="w-full px-4 py-2 border rounded-lg validate-field"
                  placeholder="HH:MM (24-hour format)"
                  pattern="([01]?[0-9]|2[0-3]):[0-5][0-9]"
                  required
                  data-validate="true">
                <small class="text-gray-500">Enter time in 24-hour format (00:00 to 23:59)</small>
                <div class="error-feedback" id="departure_time-error"></div>
              </div>
              <div>
                <label class="block text-gray-700 font-semibold mb-2">
                  Flight Duration (hours) <span class="text-red-500">*</span>
                </label>
                <input
                  type="number"
                  name="flight_duration"
                  id="flight_duration"
                  class="w-full px-4 py-2 border rounded-lg validate-field"
                  placeholder="e.g., 5.5"
                  step="0.1"
                  min="0"
                  max="8"
                  required
                  data-validate="true">
                <div class="error-feedback" id="flight_duration-error"></div>
              </div>
              <script>
                document.getElementById('flight_duration').addEventListener('input', function() {
                  let inputValue = parseFloat(this.value);

                  if (inputValue > 5) {
                    this.value = 5;
                    document.getElementById('flight_duration-error').textContent = "Flight duration cannot exceed 5 hours.";
                  } else {
                    document.getElementById('flight_duration-error').textContent = "";
                  }
                });
              </script>
            </div>

            <!-- Distance Field -->
            <div>
              <label class="block text-gray-700 font-semibold mb-2">
                Distance (km) <span class="text-red-500">*</span>
              </label>
              <input
                type="number"
                name="distance"
                id="distance"
                class="w-full px-4 py-2 border rounded-lg validate-field"
                placeholder="e.g., 3500"
                step="1"
                min="0"
                max="20000"
                required
                data-validate="true">
              <div class="error-feedback" id="distance-error"></div>
            </div>
            <script>
              document.getElementById('distance').addEventListener('input', function() {
                let inputValue = parseInt(this.value);

                if (inputValue > 20000) {
                  this.value = 20000;
                  document.getElementById('distance-error').textContent = "Distance cannot exceed 20,000 km.";
                } else {
                  document.getElementById('distance-error').textContent = "";
                }
              });
            </script>
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
                    <select name="return_airline" id="return_airline" class="w-full px-4 py-2 border rounded-lg return-required validate-field" data-validate="true">
                      <option value="">Select Airline</option>
                      <option value="PIA">PIA Airlines</option>
                      <option value="Emirates">Emirates</option>
                      <option value="Qatar">Qatar Airways</option>
                      <option value="Saudi">Saudi Airlines</option>
                      <option value="Flynas">Flynas Airlines</option>
                      <option value="same">Same as Outbound</option>
                    </select>
                    <div class="error-feedback" id="return_airline-error"></div>
                  </div>
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">Return Flight Number <span class="text-red-500">*</span></label>
                    <input type="text" name="return_flight_number" id="return_flight_number" class="w-full px-4 py-2 border rounded-lg return-required validate-field" placeholder="e.g., PK-310" data-validate="true" maxlength="7">
                    <div class="error-feedback" id="return_flight_number-error"></div>
                  </div>
                  <script>
                    document.getElementById('return_flight_number').addEventListener('input', function(e) {
                      let value = e.target.value;

                      // Allow only 2-3 English letters at the beginning
                      value = value.replace(/[^A-Za-z-0-9]/g, ''); // Removes non-alphanumeric characters (except for '-')

                      // Limit the first part to 2-3 letters
                      let firstPart = value.match(/^[A-Za-z]{0,3}/);
                      firstPart = firstPart ? firstPart[0] : '';

                      // Automatically insert dash after the first 2-3 letters
                      let secondPart = value.slice(firstPart.length).replace(/[^0-9]/g, ''); // Only allow numbers after the dash

                      if (firstPart.length >= 2) {
                        secondPart = secondPart.slice(0, 3); // Limit to 3 digits after the dash
                      }

                      // Combine the two parts
                      e.target.value = firstPart + (firstPart.length >= 2 ? '-' : '') + secondPart;

                      // Provide error message if the input doesn't follow the required format
                      if (firstPart.length < 2 || secondPart.length < 3) {
                        document.getElementById('return_flight_number-error').textContent = "Please enter a valid flight number (e.g., PK-310).";
                      } else {
                        document.getElementById('return_flight_number-error').textContent = "";
                      }
                    });
                  </script>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">Return Date <span class="text-red-500">*</span></label>
                    <input type="date" name="return_date" id="return_date" class="w-full px-4 py-2 border rounded-lg return-required validate-field" data-validate="true">
                    <div class="error-feedback" id="return_date-error"></div>
                  </div>
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">Return Time <span class="text-red-500">*</span></label>
                    <input type="text" name="return_time" id="return_time" class="w-full px-4 py-2 border rounded-lg return-required validate-field" placeholder="HH:MM (24-hour format)" data-validate="true">
                    <div class="error-feedback" id="return_time-error"></div>
                  </div>
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">Return Flight Duration (hours) <span class="text-red-500">*</span></label>
                    <input
                      type="text"
                      name="return_flight_duration"
                      id="return_flight_duration"
                      class="w-full px-4 py-2 border rounded-lg return-required validate-field return-duration-input"
                      placeholder="e.g., 5.5"
                      data-validate="true">
                    <div class="error-feedback" id="return_flight_duration-error"></div>
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
                        <input type="text" name="return_stop_city[]" class="w-full px-4 py-2 border rounded-lg return-stop-city" placeholder="e.g., Dubai" maxlength="12">
                      </div>
                      <div>
                        <label class="block text-gray-700 font-semibold mb-2">Return Stop Duration (hours) <span class="text-red-500">*</span></label>
                        <input type="text" name="return_stop_duration[]" class="w-full px-4 py-2 border rounded-lg return-stop-duration" placeholder="e.g., 2">
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
                  <label class="block text-gray-700 font-semibold mb-2">Economy Price (PKR) <span class="text-red-500">*</span></label>
                  <input type="number" name="economy_price" id="economy_price" class="w-full px-4 py-2 border rounded-lg validate-field economy-price" placeholder="242,250" required data-validate="true">
                  <div class="error-feedback" id="economy_price-error"></div>
                </div>
                <div>
                  <label class="block text-gray-700 font-semibold mb-2">Business Price (PKR) <span class="text-red-500">*</span></label>
                  <input type="number" name="business_price" id="business_price" class="w-full px-4 py-2 border rounded-lg validate-field business-price" placeholder="427,500" required data-validate="true">
                  <div class="error-feedback" id="business_price-error"></div>
                </div>
                <div>
                  <label class="block text-gray-700 font-semibold mb-2">First Class Price (PKR) <span class="text-red-500">*</span></label>
                  <input type="number" name="first_class_price" id="first_class_price" class="w-full px-4 py-2 border rounded-lg validate-field first-class-price" placeholder="712,500" required data-validate="true">
                  <div class="error-feedback" id="first_class_price-error"></div>
                </div>
              </div>
            </div>

            <!-- Seat Information -->
            <div class="border-t border-gray-200 pt-6 mt-6">
              <div class="mb-4">
                <h2 class="text-xl font-bold text-teal-700">
                  <i class="fas fa-chair mr-2"></i>Seat Information
                </h2>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                  <label class="block text-gray-700 font-semibold mb-2">Economy Seats <span class="text-red-500">*</span></label>
                  <input type="number" name="economy_seats" id="economy_seats" class="w-full px-4 py-2 border rounded-lg validate-field" placeholder="200" min="100" max="500" required data-validate="true">
                  <div class="error-feedback" id="economy_seats-error"></div>
                </div>
                <div>
                  <label class="block text-gray-700 font-semibold mb-2">Business Seats <span class="text-red-500">*</span></label>
                  <input type="number" name="business_seats" id="business_seats" class="w-full px-4 py-2 border rounded-lg validate-field" placeholder="30" min="10" max="100" required data-validate="true">
                  <div class="error-feedback" id="business_seats-error"></div>
                </div>
                <div>
                  <label class="block text-gray-700 font-semibold mb-2">First Class Seats <span class="text-red-500">*</span></label>
                  <input type="number" name="first_class_seats" id="first_class_seats" class="w-full px-4 py-2 border rounded-lg validate-field" placeholder="10" min="5" max="50" required data-validate="true">
                  <div class="error-feedback" id="first_class_seats-error"></div>
                </div>
              </div>
            </div>

            <!-- Flight Notes -->
            <div>
              <label class="block text-gray-700 font-semibold mb-2">Flight Notes (Optional)</label>
              <textarea name="flight_notes" id="flight_notes" class="w-full px-4 py-2 border rounded-lg" rows="3" placeholder="Any additional information about this flight"></textarea>
            </div>

            <!-- Submit Buttons -->
            <div class="flex gap-4">
              <button type="submit" id="submit-btn" class="bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700">
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
    document.addEventListener('DOMContentLoaded', function() {
      // Set default date to today
      const dateField = document.querySelector('input[name="departure_date"]');
      if (dateField) {
        const today = new Date();
        const formattedDate = today.toISOString().split('T')[0];
        dateField.value = formattedDate;
      }

      // Function to toggle stops section visibility
      window.toggleStopsSection = function(show) {
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
      };

      // Function to toggle return flight section visibility
      window.toggleReturnSection = function(show) {
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
      };

      // Function to toggle return stops section visibility
      window.toggleReturnStopsSection = function(show) {
        const returnStopsContainer = document.getElementById('return-stops-container');

        if (show) {
          returnStopsContainer.classList.remove('hidden');
          document.querySelectorAll('input[name="return_stop_city[]"]').forEach(input => {
            input.setAttribute('required', 'required');
          });
          document.querySelectorAll('input[name="return_stop_duration[]"]').forEach(input => {
            input.setAttribute('required', 'required');
          });
        } else {
          returnStopsContainer.classList.add('hidden');
          document.querySelectorAll('input[name="return_stop_city[]"]').forEach(input => {
            input.removeAttribute('required');
          });
          document.querySelectorAll('input[name="return_stop_duration[]"]').forEach(input => {
            input.removeAttribute('required');
          });
        }
      };

      // Flight Number Validation
      const flightInput = document.getElementById('flight_number');
      const flightError = document.getElementById('flight_number-error');

      flightInput.addEventListener('input', function() {
        let raw = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        let formatted = '';

        // Extract letters first
        const letters = raw.match(/^[A-Z]{0,3}/)?.[0] || '';
        const numbers = raw.slice(letters.length).replace(/\D/g, ''); // Only digits after letters

        // Auto-insert dash if letters are 2 or 3
        if (letters.length >= 2) {
          formatted = letters + '-' + numbers;
        } else {
          formatted = letters;
        }

        // Apply formatted value
        this.value = formatted;

        // Validate the field
        validateField(this);
      });

      // Prevent user from typing numbers first
      flightInput.addEventListener('keypress', function(e) {
        const value = this.value.toUpperCase();
        const char = e.key.toUpperCase();

        // Block number if user hasn't typed 2 letters yet
        if (!value.includes('-') && /[0-9]/.test(char)) {
          e.preventDefault();
        }

        // Block non-letter characters before dash
        if (!value.includes('-') && !/[A-Z]/.test(char)) {
          e.preventDefault();
        }

        // After dash, only allow numbers
        if (value.includes('-') && !/[0-9]/.test(char)) {
          e.preventDefault();
        }
      });

      // Stop Duration Validation
      function validateStopDuration(inputElement) {
        let value = inputElement.value;

        if (!/^[1-5]$/.test(value)) {
          inputElement.value = value.replace(/[^1-5]/g, ''); // Only allow numbers 1 to 5
        }

        if (parseInt(value) > 5) {
          inputElement.value = "5";
        }
      }

      // Apply validation to all stop duration inputs
      document.addEventListener('input', function(e) {
        if (e.target.classList.contains('stop-duration-input')) {
          validateStopDuration(e.target);
        }

        // Allow only letters in Stop City (max 12 characters)
        if (e.target.classList.contains('stop-city')) {
          e.target.value = e.target.value.replace(/[^a-zA-Z\s]/g, '').slice(0, 12);
        }
      });

      // Dynamically add more stops
      const addStopBtn = document.getElementById('add-stop');
      addStopBtn.addEventListener('click', function() {
        const stopRow = document.querySelector('.stop-row').cloneNode(true);

        stopRow.querySelectorAll('input').forEach(input => {
          input.value = '';
          if (document.querySelector('input[name="has_stops"]:checked').value === "1") {
            input.setAttribute('required', 'required');
          }
        });

        document.getElementById('stops-container').insertBefore(stopRow, this.closest('.flex'));
      });

      // Return flight number validation
      const returnFlightInput = document.getElementById('return_flight_number');
      if (returnFlightInput) {
        returnFlightInput.addEventListener('input', function() {
          let raw = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
          let formatted = '';

          // Extract letters first
          const letters = raw.match(/^[A-Z]{0,3}/)?.[0] || '';
          const numbers = raw.slice(letters.length).replace(/\D/g, ''); // Only digits after letters

          // Auto-insert dash if letters are 2 or 3
          if (letters.length >= 2) {
            formatted = letters + '-' + numbers;
          } else {
            formatted = letters;
          }

          // Apply formatted value
          this.value = formatted;

          // Validate the field
          validateField(this);
        });

        // Prevent user from typing numbers first
        returnFlightInput.addEventListener('keypress', function(e) {
          const value = this.value.toUpperCase();
          const char = e.key.toUpperCase();

          // Block number if user hasn't typed 2 letters yet
          if (!value.includes('-') && /[0-9]/.test(char)) {
            e.preventDefault();
          }

          // Block non-letter characters before dash
          if (!value.includes('-') && !/[A-Z]/.test(char)) {
            e.preventDefault();
          }

          // After dash, only allow numbers
          if (value.includes('-') && !/[0-9]/.test(char)) {
            e.preventDefault();
          }
        });
      }

      // Return flight duration validation
      function validateReturnFlightDuration(inputElement) {
        let value = inputElement.value;

        // Allow only numbers and one decimal point
        if (!/^\d*\.?\d{0,1}$/.test(value)) {
          inputElement.value = value.slice(0, -1); // Remove last character if invalid
        }

        // Allow maximum value of 8
        if (parseFloat(value) > 8) {
          inputElement.value = "8"; // Set value to 8 if it exceeds 8
        }
      }

      // Apply validation to return duration inputs
      document.addEventListener('input', function(e) {
        if (e.target.classList.contains('return-duration-input')) {
          validateReturnFlightDuration(e.target);
        }
      });

      // Add event listener for the "Add Another Return Stop" button
      const addReturnStopBtn = document.getElementById('add-return-stop');
      if (addReturnStopBtn) {
        addReturnStopBtn.addEventListener('click', function() {
          const returnStopRow = document.querySelector('.return-stop-row').cloneNode(true);
          const inputs = returnStopRow.querySelectorAll('input');

          inputs.forEach(input => {
            input.value = '';
            if (document.querySelector('input[name="has_return_stops"]:checked').value === "1" &&
              document.querySelector('input[name="has_return"]:checked').value === "1") {
              input.setAttribute('required', 'required');
            }
          });

          document.getElementById('return-stops-container').insertBefore(returnStopRow, this.closest('.flex'));
        });
      }

      // Return stop city and duration validation
      document.addEventListener('input', function(e) {
        // Validate Return Stop City (letters and spaces only, max length 12)
        if (e.target.classList.contains('return-stop-city')) {
          e.target.value = e.target.value.replace(/[^a-zA-Z\s]/g, '').slice(0, 12);
        }

        // Validate Return Stop Duration (only numbers 1-5)
        if (e.target.classList.contains('return-stop-duration')) {
          let value = e.target.value;

          if (!/^[1-5]$/.test(value)) {
            e.target.value = value.replace(/[^1-5]/g, '');
          }

          if (parseInt(value) > 5) {
            e.target.value = "5";
          }
        }
      });

      // Time format validation
      const timeInputs = document.querySelectorAll('input[name="departure_time"], input[name="return_time"]');
      timeInputs.forEach(input => {
        input.addEventListener('input', function(e) {
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

          // Validate the field if it has validation
          if (e.target.classList.contains('validate-field')) {
            validateField(e.target);
          }
        });
      });

      // Auto-open date pickers
      const dateInputs = document.querySelectorAll('input[type="date"]');
      dateInputs.forEach(input => {
        input.addEventListener('focus', function() {
          this.showPicker && this.showPicker();
        });

        input.addEventListener('click', function() {
          this.showPicker && this.showPicker();
        });
      });

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

      // Flight notes validation
      document.getElementById('flight_notes').addEventListener('input', function() {
        // Allow only letters, numbers, and spaces
        const cleanText = this.value.replace(/[^a-zA-Z0-9\s]/g, '');
        if (this.value !== cleanText) {
          this.value = cleanText;
        }
      });

      // Price validation
      function validatePricing(inputElement) {
        const className = inputElement.id;
        const value = parseInt(inputElement.value);
        let minAmount, maxAmount;

        // Set price limits in PKR based on class
        if (className === 'economy_price') {
          minAmount = 242250; // Minimum PKR for economy
          maxAmount = 342000; // Maximum PKR for economy
        } else if (className === 'business_price') {
          minAmount = 427500; // Minimum PKR for business
          maxAmount = 513000; // Maximum PKR for business
        } else if (className === 'first_class_price') {
          minAmount = 712500; // Minimum PKR for first class
          maxAmount = 855000; // Maximum PKR for first class
        }

        // Check if the entered value is less than the minimum or greater than the maximum
        if (value < minAmount) {
          inputElement.value = minAmount; // Set to min amount
        } else if (value > maxAmount) {
          inputElement.value = maxAmount; // Set to max amount
        }

        // Validate the field via AJAX
        validateField(inputElement);
      }

      // Apply price validation to price inputs
      document.querySelectorAll('#economy_price, #business_price, #first_class_price').forEach(input => {
        input.addEventListener('input', function() {
          validatePricing(this);
        });

        input.addEventListener('blur', function() {
          validatePricing(this);
        });
      });

      // Seat count validation
      function validateSeats(inputElement) {
        const id = inputElement.id;
        let min, max;

        if (id === 'economy_seats') {
          min = 100;
          max = 500;
        } else if (id === 'business_seats') {
          min = 10;
          max = 100;
        } else if (id === 'first_class_seats') {
          min = 5;
          max = 50;
        }

        let value = parseInt(inputElement.value);
        if (isNaN(value)) return;

        if (value < min) inputElement.value = min;
        if (value > max) inputElement.value = max;

        // Validate via AJAX
        validateField(inputElement);
      }

      // Apply seat validation
      document.querySelectorAll('#economy_seats, #business_seats, #first_class_seats').forEach(input => {
        input.addEventListener('input', function() {
          validateSeats(this);
        });

        input.addEventListener('blur', function() {
          validateSeats(this);
        });
      });

      // AJAX validation for fields
      function validateField(field) {
        if (!field.dataset.validate) return;

        const fieldName = field.name;
        const fieldValue = field.value;
        const errorElement = document.getElementById(`${field.id}-error`);

        // Create FormData object to send all form data for context
        const formData = new FormData(document.getElementById('flightForm'));
        formData.append('validation_check', 'true');
        formData.append('field_name', fieldName);
        formData.append('field_value', fieldValue);

        // Send AJAX request
        fetch(window.location.href, {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.status === 'error') {
              field.classList.add('is-invalid');
              field.classList.remove('is-valid');
              errorElement.style.display = 'block';
              errorElement.textContent = data.errors.join(', ');
            } else {
              field.classList.remove('is-invalid');
              field.classList.add('is-valid');
              errorElement.style.display = 'none';
              errorElement.textContent = '';
            }
          })
          .catch(error => {
            console.error('Error validating field:', error);
          });
      }

      // Validate all fields on form submission
      const form = document.getElementById('flightForm');
      form.addEventListener('submit', function(e) {
        let hasErrors = false;

        // Validate all required fields
        document.querySelectorAll('.validate-field').forEach(field => {
          if (field.required || field.value !== '') {
            validateField(field);

            // Check if this field has errors
            const errorElement = document.getElementById(`${field.id}-error`);
            if (errorElement && errorElement.style.display === 'block') {
              hasErrors = true;
            }
          }
        });

        // Special case for return date - check if it's after departure date
        if (document.querySelector('input[name="has_return"]:checked').value === "1") {
          const departureDate = new Date(document.querySelector('input[name="departure_date"]').value);
          const returnDate = new Date(document.querySelector('input[name="return_date"]').value);

          if (returnDate <= departureDate) {
            e.preventDefault();
            const returnDateField = document.getElementById('return_date');
            const returnDateError = document.getElementById('return_date-error');

            returnDateField.classList.add('is-invalid');
            returnDateField.classList.remove('is-valid');
            returnDateError.style.display = 'block';
            returnDateError.textContent = 'Return date must be after departure date';

            hasErrors = true;
          }
        }

        // If there are validation errors, prevent form submission
        if (hasErrors) {
          e.preventDefault();

          // Scroll to the first error
          const firstErrorField = document.querySelector('.is-invalid');
          if (firstErrorField) {
            firstErrorField.scrollIntoView({
              behavior: 'smooth',
              block: 'center'
            });
          }

          Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please fix all errors before submitting',
            confirmButtonText: 'OK'
          });
        }
      });

      // Add event listeners for validation on field change/blur
      document.querySelectorAll('.validate-field').forEach(field => {
        field.addEventListener('blur', function() {
          validateField(this);
        });

        field.addEventListener('change', function() {
          validateField(this);
        });
      });
    });
  </script>
</body>

</html>