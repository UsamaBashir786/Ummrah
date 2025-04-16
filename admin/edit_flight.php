<?php
session_name("admin_session");
session_start();
include 'connection/connection.php';

// Verify admin is logged in
if (!isset($_SESSION['admin_email'])) {
  header("Location: admin/login.php");
  exit();
}

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
      } else if (floatval($field_value) <= 0 || floatval($field_value) > 5) {
        $response['errors'][] = 'Flight duration must be between 0 and 5 hours';
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
      if (!empty($field_value) && (floatval($field_value) <= 0 || floatval($field_value) > 5)) {
        $response['errors'][] = 'Return flight duration must be between 0 and 5 hours';
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

// Fetch flight data
if (isset($_GET['id'])) {
  $stmt = $conn->prepare("SELECT * FROM flights WHERE id = ?");
  $stmt->bind_param("i", $_GET['id']);
  $stmt->execute();
  $flight = $stmt->get_result()->fetch_assoc();

  // Decode JSON data
  $cabin_classes = json_decode($flight['cabin_class'], true);
  $prices = json_decode($flight['prices'], true);
  $seats = json_decode($flight['seats'], true);
  $stops = json_decode($flight['stops'], true);

  // Decode return flight data if it exists
  $return_flight_data = !empty($flight['return_flight_data']) ? json_decode($flight['return_flight_data'], true) : null;
  $has_return = $return_flight_data && isset($return_flight_data['has_return']) ? $return_flight_data['has_return'] : 0;
  $return_flight_number = $has_return && !empty($return_flight_data['return_flight_number']) ? $return_flight_data['return_flight_number'] : '';
  $return_date = $has_return && !empty($return_flight_data['return_date']) ? $return_flight_data['return_date'] : '';
  $return_time = $has_return && !empty($return_flight_data['return_time']) ? $return_flight_data['return_time'] : '';
  $return_flight_duration = $has_return && !empty($return_flight_data['return_flight_duration']) ? $return_flight_data['return_flight_duration'] : '';
  $return_airline = $has_return && !empty($return_flight_data['return_airline']) ? $return_flight_data['return_airline'] : '';
  $has_return_stops = $has_return && isset($return_flight_data['has_return_stops']) ? $return_flight_data['has_return_stops'] : 0;
  $return_stops = $has_return && !empty($return_flight_data['return_stops']) ? json_decode($return_flight_data['return_stops'], true) : 'direct';
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['validation_check'])) {
  // Sanitize and validate inputs
  $airline_name = trim($_POST['airline_name']);
  $flight_number = trim($_POST['flight_number']);
  $departure_city = trim($_POST['departure_city']);
  $arrival_city = trim($_POST['arrival_city']);
  $departure_date = trim($_POST['departure_date']);
  $departure_time = trim($_POST['departure_time']);
  $flight_duration = trim($_POST['flight_duration'] ?? '');
  $distance = trim($_POST['distance'] ?? '');
  $flight_notes = trim($_POST['flight_notes'] ?? '');
  $flight_id = intval($_POST['flight_id']);

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

  for ($i = 1; $i <= $economy_seats; $i++) {
    $economy_seat_ids[] = "E" . $i;
  }
  for ($i = 1; $i <= $business_seats; $i++) {
    $business_seat_ids[] = "B" . $i;
  }
  for ($i = 1; $i <= $first_class_seats; $i++) {
    $first_class_seat_ids[] = "F" . $i;
  }

  $seats = json_encode([
    'economy' => ['count' => $economy_seats, 'seat_ids' => $economy_seat_ids],
    'business' => ['count' => $business_seats, 'seat_ids' => $business_seat_ids],
    'first_class' => ['count' => $first_class_seats, 'seat_ids' => $first_class_seat_ids]
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
      $sql = "UPDATE flights SET 
                airline_name = ?, flight_number = ?, departure_city = ?, 
                arrival_city = ?, departure_date = ?, departure_time = ?, 
                flight_duration = ?, distance = ?, flight_notes = ?, 
                cabin_class = ?, prices = ?, seats = ?, stops = ?, 
                return_flight_data = ?
              WHERE id = ?";

      $stmt = $conn->prepare($sql);
      $stmt->bind_param(
        "ssssssssssssssi",
        $airline_name,
        $flight_number,
        $departure_city,
        $arrival_city,
        $departure_date,
        $departure_time,
        $flight_duration,
        $distance,
        $flight_notes,
        $cabin_class,
        $prices,
        $seats,
        $stops_json,
        $return_flight_json,
        $flight_id
      );

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

// Set default values if there's no flight data
if (empty($flight)) {
  $flight = [
    'id' => '',
    'airline_name' => '',
    'flight_number' => '',
    'departure_city' => '',
    'arrival_city' => '',
    'departure_date' => '',
    'departure_time' => '',
    'flight_duration' => '',
    'distance' => '',
    'flight_notes' => ''
  ];
  $cabin_classes = ['Economy', 'Business', 'First Class'];
  $prices = ['economy' => 0, 'business' => 0, 'first_class' => 0];
  $seats = [
    'economy' => ['count' => 0, 'seat_ids' => []],
    'business' => ['count' => 0, 'seat_ids' => []],
    'first_class' => ['count' => 0, 'seat_ids' => []]
  ];
  $stops = "direct";
  $has_return = 0;
  $return_flight_number = '';
  $return_date = '';
  $return_time = '';
  $return_flight_duration = '';
  $return_airline = '';
  $has_return_stops = 0;
  $return_stops = "direct";
}

$has_stops = ($stops === "direct") ? 0 : 1;
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
    <?php include 'includes/sidebar.php'; ?>
    <div class="main flex-1 flex flex-col">
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <h1 class="text-xl font-semibold flex items-center">
          <i class="text-teal-600 fas fa-plane mx-2"></i> Edit Flight
        </h1>
        <div class="flex items-center gap-4">
          <a href="view-flight.php" class="text-gray-800 hover:text-teal-600">
            <i class="fas fa-arrow-left mr-1"></i> Back
          </a>
          <button class="md:hidden text-gray-800" id="menu-btn">
            <i class="fas fa-bars"></i>
          </button>
        </div>
      </div>
      <div class="overflow-auto container mx-auto px-4 py-8">
        <div class="mx-auto bg-white p-8 rounded-lg shadow-lg">
          <div class="mb-6">
            <h1 class="text-2xl font-bold text-teal-600">
              <i class="fas fa-plane-departure mr-2"></i>Edit Flight
            </h1>
            <p class="text-gray-600 mt-2">Update flight details for Umrah journey</p>
          </div>
          <form action="" method="POST" class="space-y-6" id="flightForm">
            <input type="hidden" name="flight_id" value="<?php echo $flight['id']; ?>">
            <div class="border-b border-gray-200 pb-2 mb-4">
              <h2 class="text-xl font-bold text-teal-700">
                <i class="fas fa-plane-departure mr-2"></i>Outbound Flight Details
              </h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Airline Name <span class="text-red-500">*</span></label>
                <select name="airline_name" id="airline_name" class="w-full px-4 py-2 border rounded-lg validate-field" required data-validate="true">
                  <option value="">Select Airline</option>
                  <option value="PIA" <?php echo $flight['airline_name'] == 'PIA' ? 'selected' : ''; ?>>PIA Airlines</option>
                  <option value="Emirates" <?php echo $flight['airline_name'] == 'Emirates' ? 'selected' : ''; ?>>Emirates</option>
                  <option value="Qatar" <?php echo $flight['airline_name'] == 'Qatar' ? 'selected' : ''; ?>>Qatar Airways</option>
                  <option value="Saudi" <?php echo $flight['airline_name'] == 'Saudi' ? 'selected' : ''; ?>>Saudi Airlines</option>
                  <option value="Flynas" <?php echo $flight['airline_name'] == 'Flynas' ? 'selected' : ''; ?>>Flynas Airlines</option>
                </select>
                <div class="error-feedback" id="airline_name-error"></div>
              </div>
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Flight Number <span class="text-red-500">*</span></label>
                <input type="text" name="flight_number" id="flight_number" class="w-full px-4 py-2 border rounded-lg validate-field" placeholder="e.g., PK-309" value="<?php echo $flight['flight_number']; ?>" required maxlength="9" data-validate="true">
                <div class="error-feedback" id="flight_number-error"></div>
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Departure City <span class="text-red-500">*</span></label>
                <select name="departure_city" id="departure_city" class="w-full px-4 py-2 border rounded-lg validate-field" required data-validate="true">
                  <option value="">Select City</option>
                  <option value="Karachi" <?php echo $flight['departure_city'] == 'Karachi' ? 'selected' : ''; ?>>Karachi</option>
                  <option value="Lahore" <?php echo $flight['departure_city'] == 'Lahore' ? 'selected' : ''; ?>>Lahore</option>
                  <option value="Islamabad" <?php echo $flight['departure_city'] == 'Islamabad' ? 'selected' : ''; ?>>Islamabad</option>
                </select>
                <div class="error-feedback" id="departure_city-error"></div>
              </div>
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Arrival City <span class="text-red-500">*</span></label>
                <select name="arrival_city" id="arrival_city" class="w-full px-4 py-2 border rounded-lg validate-field" required data-validate="true">
                  <option value="">Select City</option>
                  <option value="Jeddah" <?php echo $flight['arrival_city'] == 'Jeddah' ? 'selected' : ''; ?>>Jeddah</option>
                  <option value="Medina" <?php echo $flight['arrival_city'] == 'Medina' ? 'selected' : ''; ?>>Medina</option>
                </select>
                <div class="error-feedback" id="arrival_city-error"></div>
              </div>
            </div>
            <div class="border p-4 rounded-lg bg-gray-50">
              <div class="flex items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-700">Flight Stops</h3>
                <div class="ml-4">
                  <label class="inline-flex items-center">
                    <input type="radio" name="has_stops" value="0" class="mr-2" <?php echo $has_stops == 0 ? 'checked' : ''; ?> onchange="toggleStopsSection(false)">
                    <span>Direct Flight</span>
                  </label>
                  <label class="inline-flex items-center ml-4">
                    <input type="radio" name="has_stops" value="1" class="mr-2" <?php echo $has_stops == 1 ? 'checked' : ''; ?> onchange="toggleStopsSection(true)">
                    <span>Has Stops</span>
                  </label>
                </div>
              </div>
              <div id="stops-container" class="<?php echo $has_stops == 0 ? 'hidden' : ''; ?> space-y-4">
                <?php if ($has_stops == 1 && is_array($stops) && !empty($stops)): ?>
                  <?php foreach ($stops as $stop): ?>
                    <div class="stop-row grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div>
                        <label class="block text-gray-700 font-semibold mb-2">Stop City <span class="text-red-500">*</span></label>
                        <input type="text" name="stop_city[]" class="stop-city w-full px-4 py-2 border rounded-lg" placeholder="e.g., Dubai" value="<?php echo $stop['city']; ?>" maxlength="12">
                      </div>
                      <div>
                        <label class="block text-gray-700 font-semibold mb-2">Stop Duration (hours) <span class="text-red-500">*</span></label>
                        <input type="text" name="stop_duration[]" class="stop-duration-input w-full px-4 py-2 border rounded-lg" placeholder="e.g., 4" value="<?php echo $stop['duration']; ?>">
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="stop-row grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label class="block text-gray-700 font-semibold mb-2">Stop City <span class="text-red-500">*</span></label>
                      <input type="text" name="stop_city[]" class="stop-city w-full px-4 py-2 border rounded-lg" placeholder="e.g., Dubai" maxlength="12">
                    </div>
                    <div>
                      <label class="block text-gray-700 font-semibold mb-2">Stop Duration (hours) <span class="text-red-500">*</span></label>
                      <input type="text" name="stop_duration[]" class="stop-duration-input w-full px-4 py-2 border rounded-lg" placeholder="e.g., 4">
                    </div>
                  </div>
                <?php endif; ?>
                <div class="flex justify-end">
                  <button type="button" id="add-stop" class="px-4 py-2 bg-teal-500 text-white rounded-lg hover:bg-teal-600">
                    <i class="fas fa-plus mr-2"></i>Add Another Stop
                  </button>
                </div>
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Departure Date <span class="text-red-500">*</span></label>
                <input type="date" name="departure_date" id="departure_date" class="w-full px-4 py-2 border rounded-lg validate-field" value="<?php echo $flight['departure_date']; ?>" required data-validate="true" onkeydown="return false;">
                <div class="error-feedback" id="departure_date-error"></div>
              </div>
              <div>
                <label class="block text-gray-700 font-semibold mb-2">
                  Departure Time <span class="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  name="departure_time"
                  id="departure_time"
                  class="w-full px-4 py-2 border rounded-lg validate-field departure-time-input"
                  placeholder="HH:MM (24-hour format)"
                  pattern="([01]?[0-9]|2[0-3]):[0-5][0-9]"
                  value="<?php echo date('H:i', strtotime($flight['departure_time'])); ?>"
                  required
                  data-validate="true">
                <small class="text-gray-500">Enter time in 24-hour format (e.g., 14:30)</small>
                <div class="error-feedback" id="departure_time-error"></div>
              </div>

              <script>
                document.querySelectorAll('.departure-time-input').forEach(input => {
                  input.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/[^0-9]/g, ''); // Remove non-digits

                    if (value.length >= 3) {
                      let hours = parseInt(value.substring(0, 2));
                      let minutes = parseInt(value.substring(2, 4));

                      if (isNaN(hours)) hours = 0;
                      if (isNaN(minutes)) minutes = 0;

                      if (hours > 23) hours = 23;
                      if (minutes > 59) minutes = 59;

                      // Format with leading zeros
                      value = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
                    } else if (value.length >= 1 && value.length <= 2) {
                      value = value;
                    }

                    e.target.value = value;

                    // Trigger validation if needed
                    if (e.target.classList.contains('validate-field')) {
                      validateField(e.target);
                    }
                  });
                });
              </script>

              <div>
                <label class="block text-gray-700 font-semibold mb-2">Flight Duration (hours) <span class="text-red-500">*</span></label>
                <input type="number" name="flight_duration" id="flight_duration" class="w-full px-4 py-2 border rounded-lg validate-field" placeholder="e.g., 5.5" step="0.1" min="0" max="5" value="<?php echo $flight['flight_duration']; ?>" required data-validate="true">
                <div class="error-feedback" id="flight_duration-error"></div>
              </div>
            </div>
            <div>
              <label class="block text-gray-700 font-semibold mb-2">Distance (km) <span class="text-red-500">*</span></label>
              <input type="number" name="distance" id="distance" class="w-full px-4 py-2 border rounded-lg validate-field" placeholder="e.g., 3500" step="1" min="0" max="20000" value="<?php echo $flight['distance'] ?? ''; ?>" required data-validate="true">
              <div class="error-feedback" id="distance-error"></div>
            </div>
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
                    <input type="radio" name="has_return" value="0" class="mr-2" <?php echo $has_return == 0 ? 'checked' : ''; ?> onchange="toggleReturnSection(false)">
                    <span>One-way Flight</span>
                  </label>
                  <label class="inline-flex items-center ml-4">
                    <input type="radio" name="has_return" value="1" class="mr-2" <?php echo $has_return == 1 ? 'checked' : ''; ?> onchange="toggleReturnSection(true)">
                    <span>Round Trip</span>
                  </label>
                </div>
              </div>
              <div id="return-container" class="<?php echo $has_return == 0 ? 'hidden' : ''; ?> border p-4 rounded-lg bg-gray-50 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">Return Airline <span class="text-red-500">*</span></label>
                    <select name="return_airline" id="return_airline" class="w-full px-4 py-2 border rounded-lg return-required validate-field" data-validate="true">
                      <option value="">Select Airline</option>
                      <option value="PIA" <?php echo $return_airline == 'PIA' ? 'selected' : ''; ?>>PIA Airlines</option>
                      <option value="Emirates" <?php echo $return_airline == 'Emirates' ? 'selected' : ''; ?>>Emirates</option>
                      <option value="Qatar" <?php echo $return_airline == 'Qatar' ? 'selected' : ''; ?>>Qatar Airways</option>
                      <option value="Saudi" <?php echo $return_airline == 'Saudi' ? 'selected' : ''; ?>>Saudi Airlines</option>
                      <option value="Flynas" <?php echo $return_airline == 'Flynas' ? 'selected' : ''; ?>>Flynas Airlines</option>
                      <option value="same" <?php echo ($return_airline == 'same' || $return_airline == $flight['airline_name']) ? 'selected' : ''; ?>>Same as Outbound</option>
                    </select>
                    <div class="error-feedback" id="return_airline-error"></div>
                  </div>
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">Return Flight Number <span class="text-red-500">*</span></label>
                    <input type="text" name="return_flight_number" id="return_flight_number" class="w-full px-4 py-2 border rounded-lg return-required validate-field" placeholder="e.g., PK-310" value="<?php echo $return_flight_number; ?>" data-validate="true" maxlength="7">
                    <div class="error-feedback" id="return_flight_number-error"></div>
                  </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">Return Date <span class="text-red-500">*</span></label>
                    <input type="date" name="return_date" id="return_date" class="w-full px-4 py-2 border rounded-lg return-required validate-field" value="<?php echo $return_date; ?>" data-validate="true" onkeydown="return false;">
                    <div class="error-feedback" id="return_date-error"></div>
                  </div>
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">Return Time <span class="text-red-500">*</span></label>
                    <input type="text" name="return_time" id="return_time" class="w-full px-4 py-2 border rounded-lg return-required validate-field" placeholder="HH:MM (24-hour format)" value="<?php echo $return_time; ?>" data-validate="true">
                    <small class="text-gray-500">Enter time in 24-hour format (e.g., 14:30)</small>
                    <div class="error-feedback" id="return_time-error"></div>
                  </div>
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">Return Flight Duration (hours) <span class="text-red-500">*</span></label>
                    <input type="text" name="return_flight_duration" id="return_flight_duration" class="w-full px-4 py-2 border rounded-lg return-required validate-field return-duration-input" placeholder="e.g., 5.5" value="<?php echo $return_flight_duration; ?>" data-validate="true">
                    <div class="error-feedback" id="return_flight_duration-error"></div>
                  </div>
                </div>
                <div class="mt-4">
                  <div class="flex items-center mb-4">
                    <h4 class="text-md font-semibold text-gray-700">Return Flight Stops</h4>
                    <div class="ml-4">
                      <label class="inline-flex items-center">
                        <input type="radio" name="has_return_stops" value="0" class="mr-2" <?php echo $has_return_stops == 0 ? 'checked' : ''; ?> onchange="toggleReturnStopsSection(false)">
                        <span>Direct Return Flight</span>
                      </label>
                      <label class="inline-flex items-center ml-4">
                        <input type="radio" name="has_return_stops" value="1" class="mr-2" <?php echo $has_return_stops == 1 ? 'checked' : ''; ?> onchange="toggleReturnStopsSection(true)">
                        <span>Has Stops</span>
                      </label>
                    </div>
                  </div>
                  <div id="return-stops-container" class="<?php echo $has_return_stops == 0 ? 'hidden' : ''; ?> space-y-4">
                    <?php if ($has_return_stops == 1 && is_array($return_stops) && !empty($return_stops)): ?>
                      <?php foreach ($return_stops as $stop): ?>
                        <div class="return-stop-row grid grid-cols-1 md:grid-cols-2 gap-6">
                          <div>
                            <label class="block text-gray-700 font-semibold mb-2">Return Stop City <span class="text-red-500">*</span></label>
                            <input type="text" name="return_stop_city[]" class="w-full px-4 py-2 border rounded-lg return-stop-city" placeholder="e.g., Dubai" value="<?php echo $stop['city']; ?>" maxlength="12">
                          </div>
                          <div>
                            <label class="block text-gray-700 font-semibold mb-2">Return Stop Duration (hours) <span class="text-red-500">*</span></label>
                            <input type="text" name="return_stop_duration[]" class="w-full px-4 py-2 border rounded-lg return-stop-duration" placeholder="e.g., 2" value="<?php echo $stop['duration']; ?>">
                          </div>
                        </div>
                      <?php endforeach; ?>
                    <?php else: ?>
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
                    <?php endif; ?>
                    <div class="flex justify-end">
                      <button type="button" id="add-return-stop" class="px-4 py-2 bg-teal-500 text-white rounded-lg hover:bg-teal-600">
                        <i class="fas fa-plus mr-2"></i>Add Another Return Stop
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="border-t border-gray-200 pt-6 mt-6">
              <div class="mb-4">
                <h2 class="text-xl font-bold text-teal-700">
                  <i class="fas fa-tags mr-2"></i>Pricing Information
                </h2>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                  <label class="block text-gray-700 font-semibold mb-2">Economy Price (PKR) <span class="text-red-500">*</span></label>
                  <input type="number" name="economy_price" id="economy_price" class="w-full px-4 py-2 border rounded-lg validate-field economy-price" placeholder="242,250" value="<?php echo $prices['economy'] ?? 0; ?>" required data-validate="true">
                  <div class="error-feedback" id="economy_price-error"></div>
                </div>
                <div>
                  <label class="block text-gray-700 font-semibold mb-2">Business Price (PKR) <span class="text-red-500">*</span></label>
                  <input type="number" name="business_price" id="business_price" class="w-full px-4 py-2 border rounded-lg validate-field business-price" placeholder="427,500" value="<?php echo $prices['business'] ?? 0; ?>" required data-validate="true">
                  <div class="error-feedback" id="business_price-error"></div>
                </div>
                <div>
                  <label class="block text-gray-700 font-semibold mb-2">First Class Price (PKR) <span class="text-red-500">*</span></label>
                  <input type="number" name="first_class_price" id="first_class_price" class="w-full px-4 py-2 border rounded-lg validate-field first-class-price" placeholder="712,500" value="<?php echo $prices['first_class'] ?? 0; ?>" required data-validate="true">
                  <div class="error-feedback" id="first_class_price-error"></div>
                </div>
              </div>
            </div>
            <div class="border-t border-gray-200 pt-6 mt-6">
              <div class="mb-4">
                <h2 class="text-xl font-bold text-teal-700">
                  <i class="fas fa-chair mr-2"></i>Seat Information
                </h2>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                  <label class="block text-gray-700 font-semibold mb-2">Economy Seats <span class="text-red-500">*</span></label>
                  <input type="number" name="economy_seats" id="economy_seats" class="w-full px-4 py-2 border rounded-lg validate-field" placeholder="200" min="100" max="500" value="<?php echo $seats['economy']['count'] ?? 0; ?>" required data-validate="true">
                  <div class="error-feedback" id="economy_seats-error"></div>
                </div>
                <div>
                  <label class="block text-gray-700 font-semibold mb-2">Business Seats <span class="text-red-500">*</span></label>
                  <input type="number" name="business_seats" id="business_seats" class="w-full px-4 py-2 border rounded-lg validate-field" placeholder="30" min="10" max="100" value="<?php echo $seats['business']['count'] ?? 0; ?>" required data-validate="true">
                  <div class="error-feedback" id="business_seats-error"></div>
                </div>
                <div>
                  <label class="block text-gray-700 font-semibold mb-2">First Class Seats <span class="text-red-500">*</span></label>
                  <input type="number" name="first_class_seats" id="first_class_seats" class="w-full px-4 py-2 border rounded-lg validate-field" placeholder="10" min="5" max="50" value="<?php echo $seats['first_class']['count'] ?? 0; ?>" required data-validate="true">
                  <div class="error-feedback" id="first_class_seats-error"></div>
                </div>
              </div>
            </div>
            <div>
              <label class="block text-gray-700 font-semibold mb-2">Flight Notes (Optional)</label>
              <textarea name="flight_notes" id="flight_notes" class="w-full px-4 py-2 border rounded-lg" rows="3" placeholder="Any additional information about this flight"><?php echo $flight['flight_notes']; ?></textarea>
            </div>
            <div class="flex gap-4">
              <button type="submit" id="submit-btn" class="bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700">
                <i class="fas fa-save mr-2"></i> Update Flight
              </button>
              <a href="view-flight.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 text-center">
                <i class="fas fa-times mr-2"></i> Cancel
              </a>
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
      // Set min date to today
      const today = new Date().toISOString().split('T')[0];
      document.querySelector('input[name="departure_date"]').setAttribute('min', today);
      const returnDateInput = document.querySelector('input[name="return_date"]');
      if (returnDateInput) {
        returnDateInput.setAttribute('min', today);
      }

      // Flight Number Validation
      const flightInput = document.getElementById('flight_number');
      const flightError = document.getElementById('flight_number-error');
      flightInput.addEventListener('input', function(e) {
        let raw = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        let formatted = '';
        const letters = raw.match(/^[A-Z]{0,3}/)?.[0] || '';
        const numbers = raw.slice(letters.length).replace(/\D/g, '');
        if (letters.length >= 2) {
          formatted = letters + '-' + numbers;
        } else {
          formatted = letters;
        }
        this.value = formatted;
        validateField(this);
      });
      flightInput.addEventListener('keypress', function(e) {
        const value = this.value.toUpperCase();
        const char = e.key.toUpperCase();
        if (!value.includes('-') && /[0-9]/.test(char)) {
          e.preventDefault();
        }
        if (!value.includes('-') && !/[A-Z]/.test(char)) {
          e.preventDefault();
        }
        if (value.includes('-') && !/[0-9]/.test(char)) {
          e.preventDefault();
        }
      });

      // Return Flight Number Validation
      const returnFlightInput = document.getElementById('return_flight_number');
      if (returnFlightInput) {
        returnFlightInput.addEventListener('input', function(e) {
          let raw = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
          let formatted = '';
          const letters = raw.match(/^[A-Z]{0,3}/)?.[0] || '';
          const numbers = raw.slice(letters.length).replace(/\D/g, '');
          if (letters.length >= 2) {
            formatted = letters + '-' + numbers;
          } else {
            formatted = letters;
          }
          this.value = formatted;
          validateField(this);
        });
        returnFlightInput.addEventListener('keypress', function(e) {
          const value = this.value.toUpperCase();
          const char = e.key.toUpperCase();
          if (!value.includes('-') && /[0-9]/.test(char)) {
            e.preventDefault();
          }
          if (!value.includes('-') && !/[A-Z]/.test(char)) {
            e.preventDefault();
          }
          if (value.includes('-') && !/[0-9]/.test(char)) {
            e.preventDefault();
          }
        });
      }

      // Time Format Validation (Strictly HH:MM)
      const timeInputs = document.querySelectorAll('input[name="departure_time"], input[name="return_time"]');
      timeInputs.forEach(input => {
        input.addEventListener('input', function(e) {
          let value = e.target.value;
          // Remove any non-numeric characters except colon
          value = value.replace(/[^0-9:]/g, '');
          // Remove any seconds portion if present
          if (value.includes(':')) {
            const parts = value.split(':');
            if (parts.length > 2) {
              value = parts[0] + ':' + parts[1];
            }
          }
          // Auto-add colon after HH
          if (value.length === 2 && !value.includes(':')) {
            value += ':';
          }
          // Limit length to 5 characters (HH:MM)
          if (value.length > 5) {
            value = value.substring(0, 5);
          }
          // Validate and correct hours
          if (value.includes(':')) {
            const [hours, minutes] = value.split(':');
            if (hours.length === 2) {
              const hoursNum = parseInt(hours);
              if (hoursNum > 23) {
                value = '23:' + (minutes || '');
              }
            }
            // Validate and correct minutes
            if (minutes && minutes.length === 2) {
              const minutesNum = parseInt(minutes);
              if (minutesNum > 59) {
                value = hours + ':59';
              }
            }
          }
          e.target.value = value;
          if (e.target.classList.contains('validate-field')) {
            validateField(e.target);
          }
        });
        // Prevent pasting invalid formats
        input.addEventListener('paste', function(e) {
          e.preventDefault();
          const pastedData = (e.clipboardData || window.clipboardData).getData('text');
          const cleanData = pastedData.replace(/[^0-9:]/g, '').substring(0, 5);
          if (cleanData.match(/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/)) {
            this.value = cleanData;
          }
        });
      });

      // Stop Duration Validation
      function validateStopDuration(inputElement) {
        let value = inputElement.value;
        if (!/^[1-5]$/.test(value)) {
          inputElement.value = value.replace(/[^1-5]/g, '');
        }
        if (parseInt(value) > 5) {
          inputElement.value = "5";
        }
      }
      document.addEventListener('input', function(e) {
        if (e.target.classList.contains('stop-duration-input')) {
          validateStopDuration(e.target);
        }
        if (e.target.classList.contains('stop-city')) {
          e.target.value = e.target.value.replace(/[^a-zA-Z\s]/g, '').slice(0, 12);
        }
        if (e.target.classList.contains('return-stop-city')) {
          e.target.value = e.target.value.replace(/[^a-zA-Z\s]/g, '').slice(0, 12);
        }
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

      // Flight Duration Validation
      document.getElementById('flight_duration').addEventListener('input', function() {
        let inputValue = parseFloat(this.value);
        if (inputValue > 5) {
          this.value = 5;
          document.getElementById('flight_duration-error').textContent = "Flight duration cannot exceed 5 hours.";
        } else {
          document.getElementById('flight_duration-error').textContent = "";
        }
        validateField(this);
      });

      // Return Flight Duration Validation
      const returnFlightDuration = document.getElementById('return_flight_duration');
      if (returnFlightDuration) {
        returnFlightDuration.addEventListener('input', function() {
          let value = this.value;
          if (!/^\d*\.?\d{0,1}$/.test(value)) {
            this.value = value.slice(0, -1);
          }
          if (parseFloat(value) > 5) {
            this.value = "5";
          }
          validateField(this);
        });
      }

      // Distance Validation
      document.getElementById('distance').addEventListener('input', function() {
        let inputValue = parseInt(this.value);
        if (inputValue > 20000) {
          this.value = 20000;
          document.getElementById('distance-error').textContent = "Distance cannot exceed 20,000 km.";
        } else {
          document.getElementById('distance-error').textContent = "";
        }
        validateField(this);
      });

      // Price Validation
      function validatePricing(inputElement) {
        const className = inputElement.id;
        const value = parseInt(inputElement.value);
        let minAmount, maxAmount;
        if (className === 'economy_price') {
          minAmount = 242250;
          maxAmount = 342000;
        } else if (className === 'business_price') {
          minAmount = 427500;
          maxAmount = 513000;
        } else if (className === 'first_class_price') {
          minAmount = 712500;
          maxAmount = 855000;
        }
        if (value < minAmount) {
          inputElement.value = minAmount;
        } else if (value > maxAmount) {
          inputElement.value = maxAmount;
        }
        validateField(inputElement);
      }
      document.querySelectorAll('#economy_price, #business_price, #first_class_price').forEach(input => {
        input.addEventListener('input', function() {
          validatePricing(this);
        });
        input.addEventListener('blur', function() {
          validatePricing(this);
        });
      });

      // Seat Validation
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
        validateField(inputElement);
      }
      document.querySelectorAll('#economy_seats, #business_seats, #first_class_seats').forEach(input => {
        input.addEventListener('input', function() {
          validateSeats(this);
        });
        input.addEventListener('blur', function() {
          validateSeats(this);
        });
      });

      // Flight Notes Validation
      document.getElementById('flight_notes').addEventListener('input', function() {
        this.value = this.value.replace(/[^a-zA-Z0-9\s]/g, '');
      });

      // Auto-open Date Pickers
      const dateInputs = document.querySelectorAll('input[type="date"]');
      dateInputs.forEach(input => {
        input.addEventListener('focus', function() {
          this.showPicker && this.showPicker();
        });
        input.addEventListener('click', function() {
          this.showPicker && this.showPicker();
        });
      });

      // Toggle Stops Section
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

      // Toggle Return Flight Section
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
          document.querySelector('input[name="has_return_stops"][value="0"]').checked = true;
          toggleReturnStopsSection(false);
        }
      };

      // Toggle Return Stops Section
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

      // Add Stop
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

      // Add Return Stop
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

      // Return Airline Dropdown
      const returnAirlineSelect = document.querySelector('select[name="return_airline"]');
      if (returnAirlineSelect) {
        returnAirlineSelect.addEventListener('change', function() {
          if (this.value === 'same') {
            const outboundAirline = document.querySelector('select[name="airline_name"]').value;
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
              this.value = '';
            }
          }
        });
      }

      // AJAX Validation
      function validateField(field) {
        if (!field.dataset.validate) return;
        const fieldName = field.name;
        const fieldValue = field.value;
        const errorElement = document.getElementById(`${field.id}-error`);
        const formData = new FormData(document.getElementById('flightForm'));
        formData.append('validation_check', 'true');
        formData.append('field_name', fieldName);
        formData.append('field_value', fieldValue);
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

      // Form Submission Validation
      const form = document.getElementById('flightForm');
      form.addEventListener('submit', function(e) {
        let hasErrors = false;
        document.querySelectorAll('.validate-field').forEach(field => {
          if (field.required || field.value !== '') {
            validateField(field);
            const errorElement = document.getElementById(`${field.id}-error`);
            if (errorElement && errorElement.style.display === 'block') {
              hasErrors = true;
            }
          }
        });
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
        if (hasErrors) {
          e.preventDefault();
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

      // Add Validation Event Listeners
      document.querySelectorAll('.validate-field').forEach(field => {
        field.addEventListener('blur', function() {
          validateField(this);
        });
        field.addEventListener('change', function() {
          validateField(this);
        });
      });

      // Initial Section Visibility
      if (document.querySelector('input[name="has_stops"]:checked').value === "1") {
        toggleStopsSection(true);
      }
      if (document.querySelector('input[name="has_return"]:checked').value === "1") {
        toggleReturnSection(true);
        if (document.querySelector('input[name="has_return_stops"]:checked').value === "1") {
          toggleReturnStopsSection(true);
        }
      }
    });
  </script>
</body>

</html>