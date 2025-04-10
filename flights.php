<?php
session_start();
include 'connection/connection.php';

// Initialize variables
$search_results = [];
$search_performed = false;
$error_message = "";
$current_date = date('Y-m-d');
$is_round_trip = false;

// Function to load all flights
function loadAllFlights($conn)
{
  $sql = "SELECT * FROM flights ORDER BY departure_date DESC";
  $result = $conn->query($sql);
  $flights = [];

  if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $flights[] = $row;
    }
  }

  return $flights;
}

// Process search form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $search_performed = true;

  // Get form data
  $departure_city = isset($_POST['departure_city']) ? $_POST['departure_city'] : '';
  $arrival_city = isset($_POST['arrival_city']) ? $_POST['arrival_city'] : '';
  $departure_date = isset($_POST['departure_date']) ? $_POST['departure_date'] : '';
  $cabin_class = isset($_POST['cabin_class']) ? $_POST['cabin_class'] : '';
  $passengers = isset($_POST['passengers']) ? intval($_POST['passengers']) : 1;
  $trip_type = isset($_POST['trip_type']) ? $_POST['trip_type'] : 'one_way';
  $return_date = isset($_POST['return_date']) ? $_POST['return_date'] : '';

  // Set round trip flag
  $is_round_trip = ($trip_type === 'round_trip');

  // Validate inputs
  $validation_errors = [];

  if (empty($departure_city)) {
    $validation_errors[] = "Please select a departure city";
  }

  if (empty($arrival_city)) {
    $validation_errors[] = "Please select an arrival city";
  }

  if ($departure_city === $arrival_city && !empty($departure_city)) {
    $validation_errors[] = "Departure and arrival cities cannot be the same";
  }

  // Validate return date for round trips
  if ($is_round_trip && empty($return_date)) {
    $validation_errors[] = "Return date is required for round trips";
  }

  // Check if return date is after departure date for round trips
  if ($is_round_trip && !empty($departure_date) && !empty($return_date)) {
    if (strtotime($return_date) < strtotime($departure_date)) {
      $validation_errors[] = "Return date must be after departure date";
    }
  }

  if (empty($validation_errors)) {
    // Build the SQL query for outbound flights
    $sql = "SELECT * FROM flights WHERE 1=1";

    if (!empty($departure_city)) {
      $sql .= " AND departure_city = '" . $conn->real_escape_string($departure_city) . "'";
    }

    if (!empty($arrival_city)) {
      $sql .= " AND arrival_city = '" . $conn->real_escape_string($arrival_city) . "'";
    }

    if (!empty($departure_date)) {
      $sql .= " AND departure_date = '" . $conn->real_escape_string($departure_date) . "'";
    }

    if (!empty($cabin_class)) {
      $sql .= " AND JSON_CONTAINS(cabin_class, '\"" . $conn->real_escape_string($cabin_class) . "\"')";
    }

    // Execute query
    $result = $conn->query($sql);

    if ($result) {
      if ($result->num_rows > 0) {
        // Fetch all results
        while ($row = $result->fetch_assoc()) {
          // Add a flag to indicate this is an outbound flight
          $row['is_outbound'] = true;

          // Also add return flight information if this flight has return details
          if (!empty($row['return_flight_data'])) {
            $return_data = json_decode($row['return_flight_data'], true);
            if (isset($return_data['has_return']) && $return_data['has_return'] == 1) {
              $row['has_return'] = true;
              $row['return_flight_info'] = $return_data;

              // For round trips, check if the return date matches the requested return date
              if ($is_round_trip && !empty($return_date) && isset($return_data['return_date'])) {
                // Only include flights where the return date matches
                if ($return_data['return_date'] == $return_date) {
                  $search_results[] = $row;
                }
              } else {
                $search_results[] = $row;
              }
            } else {
              $row['has_return'] = false;
              // For one-way trips, include all flights
              if (!$is_round_trip) {
                $search_results[] = $row;
              }
            }
          } else {
            $row['has_return'] = false;
            // For one-way trips, include all flights
            if (!$is_round_trip) {
              $search_results[] = $row;
            }
          }
        }
      }
    } else {
      $error_message = "Error executing query: " . $conn->error;
    }

    // For round trips, search for flights from arrival city to departure city on return date
    if ($is_round_trip && !empty($return_date)) {
      $return_sql = "SELECT * FROM flights WHERE 1=1";

      // For return flights, we swap departure and arrival cities
      if (!empty($arrival_city)) {
        $return_sql .= " AND departure_city = '" . $conn->real_escape_string($arrival_city) . "'";
      }

      if (!empty($departure_city)) {
        $return_sql .= " AND arrival_city = '" . $conn->real_escape_string($departure_city) . "'";
      }

      if (!empty($return_date)) {
        $return_sql .= " AND departure_date = '" . $conn->real_escape_string($return_date) . "'";
      }

      if (!empty($cabin_class)) {
        $return_sql .= " AND JSON_CONTAINS(cabin_class, '\"" . $conn->real_escape_string($cabin_class) . "\"')";
      }

      $return_result = $conn->query($return_sql);

      if ($return_result && $return_result->num_rows > 0) {
        while ($row = $return_result->fetch_assoc()) {
          // Add a flag to indicate this is a return flight
          $row['is_outbound'] = false;
          $row['is_return'] = true;

          // Also check for return flight data
          if (!empty($row['return_flight_data'])) {
            $return_data = json_decode($row['return_flight_data'], true);
            if (isset($return_data['has_return']) && $return_data['has_return'] == 1) {
              $row['has_return'] = true;
              $row['return_flight_info'] = $return_data;
            } else {
              $row['has_return'] = false;
            }
          } else {
            $row['has_return'] = false;
          }

          $search_results[] = $row;
        }
      }
    }
  } else {
    $error_message = implode(", ", $validation_errors);
  }
} else {
  // Load all flights by default when no search is performed
  $search_results = loadAllFlights($conn);
}

// Get unique departure and arrival cities for dropdowns
$departure_cities = [];
$arrival_cities = [];
$cabin_classes = [];

$sql_cities = "SELECT DISTINCT departure_city FROM flights";
$result_departure = $conn->query($sql_cities);
if ($result_departure && $result_departure->num_rows > 0) {
  while ($row = $result_departure->fetch_assoc()) {
    $departure_cities[] = $row['departure_city'];
  }
}

$sql_cities = "SELECT DISTINCT arrival_city FROM flights";
$result_arrival = $conn->query($sql_cities);
if ($result_arrival && $result_arrival->num_rows > 0) {
  while ($row = $result_arrival->fetch_assoc()) {
    $arrival_cities[] = $row['arrival_city'];
  }
}

// Get all possible cabin classes
$sql_cabin = "SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(cabin_class, '$[0]')) as class FROM flights
              UNION
              SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(cabin_class, '$[1]')) as class FROM flights
              UNION
              SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(cabin_class, '$[2]')) as class FROM flights";
$result_cabin = $conn->query($sql_cabin);
if ($result_cabin && $result_cabin->num_rows > 0) {
  while ($row = $result_cabin->fetch_assoc()) {
    if (!empty($row['class'])) {
      $cabin_classes[] = $row['class'];
    }
  }
}

// Group flights by outbound and return
$outbound_flights = [];
$return_flights = [];

foreach ($search_results as $flight) {
  if (isset($flight['is_return']) && $flight['is_return']) {
    $return_flights[] = $flight;
  } else {
    $outbound_flights[] = $flight;
  }
}

// Sort flights by price or departure time
function sortFlights(&$flights, $sort_by = 'price')
{
  if (empty($flights)) return;

  usort($flights, function ($a, $b) use ($sort_by) {
    if ($sort_by === 'price') {
      $prices_a = json_decode($a['prices'], true);
      $prices_b = json_decode($b['prices'], true);

      // Get the lowest price for each flight
      $min_price_a = empty($prices_a) ? PHP_INT_MAX : min(array_values($prices_a));
      $min_price_b = empty($prices_b) ? PHP_INT_MAX : min(array_values($prices_b));

      return $min_price_a - $min_price_b;
    } else if ($sort_by === 'time') {
      return strtotime($a['departure_time']) - strtotime($b['departure_time']);
    }

    return 0;
  });
}

// Sort flights by price by default
if (isset($_GET['sort']) && !empty($_GET['sort'])) {
  sortFlights($outbound_flights, $_GET['sort']);
  sortFlights($return_flights, $_GET['sort']);
} else {
  sortFlights($outbound_flights, 'price');
  sortFlights($return_flights, 'price');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
  <link rel="stylesheet" href="assets/css/flights.css">

</head>

<body class="bg-gray-50 min-h-screen">
  <?php include 'includes/navbar.php'; ?>
  <br><br><br>

  <div class="container mx-auto px-4 py-8">
    <div class="text-center mb-8 animate__animated animate__fadeIn">
      <h1 class="text-3xl md:text-4xl font-bold text-emerald-700 mb-2">
        <i class="fas fa-plane-departure mr-2"></i> Find Your Perfect Flight
      </h1>
      <p class="text-gray-600 max-w-2xl mx-auto">Search and compare flights for your Umrah journey. Book with confidence and enjoy a seamless travel experience.</p>
    </div>

    <!-- Search Form -->
    <div class="search-form p-6 mb-10 shadow-lg animate__animated animate__fadeIn animate__delay-1s">
      <!-- Trip Type Selection -->
      <div class="text-center mb-4">
        <div class="trip-type-selector">
          <label class="trip-type-option <?php echo (!isset($_POST['trip_type']) || $_POST['trip_type'] == 'one_way') ? 'active' : ''; ?>" data-trip-type="one_way">
            <input type="radio" name="trip_type" value="one_way" class="hidden" <?php echo (!isset($_POST['trip_type']) || $_POST['trip_type'] == 'one_way') ? 'checked' : ''; ?>>
            <i class="fas fa-long-arrow-alt-right mr-2"></i> One Way
          </label>
          <label class="trip-type-option <?php echo (isset($_POST['trip_type']) && $_POST['trip_type'] == 'round_trip') ? 'active' : ''; ?>" data-trip-type="round_trip">
            <input type="radio" name="trip_type" value="round_trip" class="hidden" <?php echo (isset($_POST['trip_type']) && $_POST['trip_type'] == 'round_trip') ? 'checked' : ''; ?>>
            <i class="fas fa-exchange-alt mr-2"></i> Round Trip
          </label>
        </div>
      </div>

      <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="flight-search-form">
        <input type="hidden" name="trip_type" id="trip_type" value="<?php echo (isset($_POST['trip_type'])) ? htmlspecialchars($_POST['trip_type']) : 'one_way'; ?>">
        <?php
        $current_date = date('d F Y'); // Format the date as "date month year"
        ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 relative">
          <!-- From City -->
          <div class="lg:col-span-1 city-input">
            <label for="departure_city" class="block text-sm font-medium text-white mb-1">
              From
            </label>
            <div class="icon">
              <i class="fas fa-plane-departure"></i>
            </div>
            <select class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-200 focus:ring-opacity-50 py-3 px-4 border bg-white"
              id="departure_city" name="departure_city" required>
              <option value="">Select departure city</option>
              <?php foreach ($departure_cities as $city): ?>
                <option value="<?php echo htmlspecialchars($city); ?>"
                  <?php echo (isset($_POST['departure_city']) && $_POST['departure_city'] == $city) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($city); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Swap Cities Button -->
          <!-- <div class="swap-cities" id="swap-cities">
            <i class="fas fa-exchange-alt text-emerald-600"></i>
          </div> -->

          <!-- To City -->
          <div class="lg:col-span-1 city-input">
            <label for="arrival_city" class="block text-sm font-medium text-white mb-1">
              To
            </label>
            <div class="icon">
              <i class="fas fa-plane-arrival"></i>
            </div>
            <select class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-200 focus:ring-opacity-50 py-3 px-4 border bg-white"
              id="arrival_city" name="arrival_city" required>
              <option value="">Select arrival city</option>
              <?php foreach ($arrival_cities as $city): ?>
                <option value="<?php echo htmlspecialchars($city); ?>"
                  <?php echo (isset($_POST['arrival_city']) && $_POST['arrival_city'] == $city) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($city); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Departure Date -->
          <div class="lg:col-span-1 city-input">
            <label for="departure_date" class="block text-sm font-medium text-white mb-1">
              Departure Date
            </label>
            <div class="icon">
              <i class="fas fa-calendar-alt"></i>
            </div>
            <input type="date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-200 focus:ring-opacity-50 py-3 px-4 border bg-white"
              id="departure_date" name="departure_date" min="<?php echo $current_date; ?>"
              value="<?php echo isset($_POST['departure_date']) ? htmlspecialchars($_POST['departure_date']) : $current_date; ?>">
          </div>

          <!-- Return Date (shown/hidden based on trip type) -->
          <div class="lg:col-span-1 city-input date-picker-container" id="return-date-container" <?php echo (!isset($_POST['trip_type']) || $_POST['trip_type'] != 'round_trip') ? 'style="display:none;"' : ''; ?>>
            <label for="return_date" class="block text-sm font-medium text-white mb-1">
              Return Date
            </label>
            <div class="icon">
              <i class="fas fa-calendar-check"></i>
            </div>
            <input type="date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-200 focus:ring-opacity-50 py-3 px-4 border bg-white"
              id="return_date" name="return_date" min="<?php echo $current_date; ?>"
              value="<?php echo isset($_POST['return_date']) ? htmlspecialchars($_POST['return_date']) : ''; ?>">
          </div>

        </div>

        <div class="mt-6">
          <button type="submit" id="search-button" class="w-full bg-white hover:bg-gray-100 text-emerald-700 font-bold py-3 px-4 rounded-lg transition duration-300 ease-in-out flex items-center justify-center shadow-md">
            <i class="fas fa-search mr-2"></i> Search Flights
          </button>
        </div>
      </form>
    </div>
    <!-- Refresh Button HTML -->
    <div class="mb-6 flex justify-end">
      <button id="refresh-button" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center">
        <i class="fas fa-sync-alt mr-2"></i> Refresh Results
      </button>
    </div>

    <!-- JavaScript for Refresh Button -->
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const refreshButton = document.getElementById('refresh-button');

        if (refreshButton) {
          refreshButton.addEventListener('click', function() {
            // Add a spinning animation to the refresh icon
            const refreshIcon = this.querySelector('.fa-sync-alt');
            if (refreshIcon) {
              refreshIcon.classList.add('animate-spin');
            }

            // Reload the current page without submitting forms
            // The true parameter ensures the page is reloaded from the server, not from cache
            setTimeout(function() {
              window.location.reload(true);
            }, 300);
          });
        }
      });
    </script>
    <!-- Search Results -->
    <div id="search-results" class="animate__animated animate__fadeIn">
      <?php if (!empty($error_message)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-sm" role="alert">
          <div class="flex">
            <div class="flex-shrink-0">
              <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
            </div>
            <div>
              <p class="font-medium"><?php echo $error_message; ?></p>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <?php if (empty($outbound_flights) && empty($return_flights) && empty($error_message)): ?>
        <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-6 rounded-lg shadow-sm mb-8" role="alert">
          <div class="flex">
            <div class="flex-shrink-0">
              <i class="fas fa-info-circle text-blue-500 text-xl mr-3"></i>
            </div>
            <div>
              <p class="font-medium text-lg">No flights found</p>
              <p class="mt-2">Try adjusting your search parameters or selecting different dates.</p>
              <div class="mt-4">
                <button onclick="document.getElementById('flight-search-form').scrollIntoView({behavior: 'smooth'})" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-150 flex items-center">
                  <i class="fas fa-sync-alt mr-2"></i> Modify Search
                </button>
              </div>
            </div>
          </div>
        </div>
      <?php else: ?>
        <!-- OUTBOUND FLIGHTS -->
        <?php if (!empty($outbound_flights)): ?>
          <!-- Results Header for Outbound Flights -->
          <div class="flight-type-header mb-6">
            <i class="fas fa-plane-departure text-emerald-600 mr-2"></i> Outbound Flights
            <span class="ml-2 text-sm text-gray-500"><?php echo count($outbound_flights); ?> flights found</span>
          </div>

          <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
              <h2 class="text-2xl font-bold text-gray-800 mb-2">
                <i class="fas fa-list-alt text-emerald-600 mr-2"></i>
                <?php echo count($outbound_flights); ?> Outbound Flights Found
              </h2>
              <?php if ($search_performed): ?>
                <p class="text-gray-600 text-sm">
                  <?php echo htmlspecialchars(isset($_POST['departure_city']) ? $_POST['departure_city'] : ''); ?> to
                  <?php echo htmlspecialchars(isset($_POST['arrival_city']) ? $_POST['arrival_city'] : ''); ?>
                  <?php if (isset($_POST['departure_date']) && !empty($_POST['departure_date'])): ?>
                    on <?php echo date('D, M j, Y', strtotime($_POST['departure_date'])); ?>
                  <?php endif; ?>
                </p>
              <?php else: ?>
                <p class="text-gray-600 text-sm">Showing all available flights</p>
              <?php endif; ?>
            </div>
          </div>

          <!-- Outbound Flight Cards -->
          <div class="space-y-6 mb-10">
            <?php foreach ($outbound_flights as $index => $flight):
              // Get minimum price
              $prices = json_decode($flight['prices'], true);
              $min_price = !empty($prices) ? min(array_values($prices)) : 0;

              // Determine if this is the cheapest flight
              $is_cheapest = $index === 0 && isset($_GET['sort']) && $_GET['sort'] === 'price';
            ?>
              <div class="flight-card bg-white shadow-sm hover:shadow-md p-6 relative <?php echo $is_cheapest ? 'animate__animated animate__pulse' : ''; ?>">
                <?php if ($is_cheapest): ?>
                  <div class="price-badge">Best Deal</div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                  <!-- Airline Info -->
                  <div class="md:col-span-3">
                    <div class="flex items-center">
                      <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-plane text-emerald-600 text-xl"></i>
                      </div>
                      <div>
                        <h5 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($flight['airline_name'] ?? 'Airline'); ?></h5>
                        <p class="text-gray-500 text-sm">
                          Flight: <?php echo htmlspecialchars($flight['flight_number']); ?>
                        </p>
                      </div>
                    </div>

                    <!-- Price for mobile view -->
                    <div class="md:hidden mt-4 text-right">
                      <div class="font-bold text-2xl text-emerald-600">$<?php echo $min_price; ?></div>
                      <p class="text-sm text-gray-500">Lowest fare</p>
                    </div>
                  </div>

                  <!-- Flight Info -->
                  <div class="md:col-span-6">
                    <div class="flex items-center justify-between mb-4">
                      <!-- Departure -->
                      <div class="text-center">
                        <p class="text-2xl font-bold text-gray-800">
                          <?php echo date('H:i', strtotime($flight['departure_time'])); ?>
                        </p>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($flight['departure_city']); ?></p>
                      </div>

                      <!-- Flight Path Visualization -->
                      <div class="flight-route flex-1 mx-4 flex items-center justify-between">
                        <div class="city text-center">
                          <i class="fas fa-circle text-emerald-600 text-xs"></i>
                        </div>
                        <div class="route-line flex-1 mx-2 h-0.5 bg-gray-300 relative overflow-hidden">
                          <div class="plane-icon">
                            <i class="fas fa-plane text-emerald-600"></i>
                          </div>
                        </div>
                        <div class="city text-center">
                          <i class="fas fa-circle text-emerald-600 text-xs"></i>
                        </div>
                      </div>

                      <!-- Arrival -->
                      <div class="text-center">
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($flight['arrival_city']); ?></p>
                      </div>
                    </div>

                    <!-- Secondary Info -->
                    <div class="flex items-center justify-between border-t pt-3">
                      <!-- Date -->
                      <div>
                        <p class="text-sm text-gray-600">
                          <i class="far fa-calendar-alt text-emerald-600 mr-1"></i>
                          <?php echo date('D, M j, Y', strtotime($flight['departure_date'])); ?>
                        </p>
                      </div>

                      <!-- Flight Duration -->
                      <?php if (!empty($flight['flight_duration'])): ?>
                        <div>
                          <p class="text-sm text-gray-600">
                            <i class="far fa-clock text-emerald-600 mr-1"></i>
                            Duration: <?php echo htmlspecialchars($flight['flight_duration']); ?> hours
                          </p>
                        </div>
                      <?php endif; ?>

                      <!-- Stops Badge -->
                      <?php
                      $stops = json_decode($flight['stops'], true);
                      if (empty($stops) || $stops === "direct" || !is_array($stops)):
                      ?>
                        <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                          <i class="fas fa-check-circle mr-1"></i> Direct Flight
                        </div>
                      <?php else: ?>
                        <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                          <i class="fas fa-map-marker-alt mr-1"></i>
                          <?php echo count($stops) . ' ' . (count($stops) > 1 ? 'Stops' : 'Stop'); ?>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>

                  <!-- Price & Action -->
                  <div class="md:col-span-3 flex flex-col justify-between items-end">
                    <!-- Price -->
                    <div class="hidden md:block">
                      <div class="font-bold text-2xl text-emerald-600">$<?php echo $min_price; ?></div>
                      <p class="text-sm text-gray-500">Lowest fare</p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="w-full md:w-auto">
                      <button type="button" class="view-details-btn w-full md:w-auto bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-medium py-2 px-4 rounded-lg text-sm transition" data-flight-id="<?php echo $flight['id']; ?>">
                        <i class="fas fa-chevron-down mr-1 details-icon-<?php echo $flight['id']; ?>"></i> View Details
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Expandable Details Section -->
                <div id="details-<?php echo $flight['id']; ?>" class="details-transition mt-4 pt-4 border-t border-gray-200">
                  <!-- Tabs Navigation -->
                  <div class="tabs mb-4">
                    <div class="tab active" data-tab="pricing-<?php echo $flight['id']; ?>">
                      <i class="fas fa-tag mr-1"></i> Pricing & Classes
                    </div>
                    <div class="tab" data-tab="route-<?php echo $flight['id']; ?>">
                      <i class="fas fa-route mr-1"></i> Flight Route
                    </div>
                    <div class="tab" data-tab="info-<?php echo $flight['id']; ?>">
                      <i class="fas fa-info-circle mr-1"></i> Flight Information
                    </div>
                    <?php if (isset($flight['has_return']) && $flight['has_return']): ?>
                      <div class="tab" data-tab="return-<?php echo $flight['id']; ?>">
                        <i class="fas fa-undo-alt mr-1"></i> Return Flight
                      </div>
                    <?php endif; ?>
                  </div>

                  <!-- Tab Content -->
                  <div class="tab-content">
                    <!-- Pricing & Classes Tab -->
                    <div id="pricing-<?php echo $flight['id']; ?>" class="tab-pane active">
                      <h6 class="font-medium text-gray-700 mb-3">
                        Available Classes and Pricing
                      </h6>
                      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <?php
                        $prices = json_decode($flight['prices'], true);
                        $seats = json_decode($flight['seats'], true);
                        $classes = json_decode($flight['cabin_class']);

                        if (!is_array($classes)) {
                          $classes = [];
                        }

                        foreach ($classes as $class):
                          $class_key = strtolower(str_replace(' ', '_', $class));

                          // Determine icon based on class
                          $classIcon = 'fa-chair';
                          $bgColor = 'bg-gray-50';
                          $textColor = 'text-gray-700';

                          if (stripos($class, 'business') !== false) {
                            $classIcon = 'fa-briefcase';
                            $bgColor = 'bg-purple-50';
                            $textColor = 'text-purple-700';
                          } elseif (stripos($class, 'first') !== false) {
                            $classIcon = 'fa-crown';
                            $bgColor = 'bg-blue-50';
                            $textColor = 'text-blue-700';
                          } elseif (stripos($class, 'economy') !== false) {
                            $classIcon = 'fa-chair';
                            $bgColor = 'bg-green-50';
                            $textColor = 'text-green-700';
                          }
                        ?>
                          <div class="<?php echo $bgColor; ?> border border-gray-200 rounded-xl p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-center mb-3">
                              <div class="w-8 h-8 rounded-full <?php echo str_replace('bg-', 'bg-', $bgColor); ?> border border-gray-200 flex items-center justify-center mr-2">
                                <i class="fas <?php echo $classIcon; ?> <?php echo $textColor; ?>"></i>
                              </div>
                              <span class="font-medium <?php echo $textColor; ?>"><?php echo htmlspecialchars(ucwords($class)); ?></span>
                            </div>
                            <div class="space-y-2">
                              <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Price:</span>
                                <span class="font-bold <?php echo $textColor; ?>">$<?php echo isset($prices[$class_key]) ? number_format($prices[$class_key], 2) : 'N/A'; ?></span>
                              </div>
                              <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Available Seats:</span>
                                <span class="font-medium">
                                  <?php
                                  // Get total seats from the seats JSON for this class
                                  $total_seats = isset($seats[$class_key]['count']) ? $seats[$class_key]['count'] : 0;

                                  // Normalize the class name to match the database (lowercase, no spaces)
                                  $normalized_class = strtolower(str_replace(' ', '_', $class));

                                  // Query to count how many seats have been booked for this flight and class
                                  $booked_query = "SELECT COUNT(*) as booked_count FROM flight_bookings 
            WHERE flight_id = {$flight['id']} 
            AND cabin_class = '{$normalized_class}'
            AND booking_status != 'cancelled'";
                                  $booked_result = $conn->query($booked_query);
                                  $booked_seats = 0;
                                  if ($booked_result && $booked_result->num_rows > 0) {
                                    $booked_row = $booked_result->fetch_assoc();
                                    $booked_seats = $booked_row['booked_count'];
                                  }

                                  // Calculate remaining seats
                                  $remaining_seats = $total_seats - $booked_seats;
                                  echo $remaining_seats . ' of ' . $total_seats;
                                  ?>
                                </span>
                              </div>
                            </div>

                            <!-- <div class="mt-4">
                              <a href="booking-flight.php?flight_id=<?php echo $flight['id']; ?>&cabin_class=<?php echo urlencode($class); ?>"
                                class="block w-full text-center py-2 px-4 border border-emerald-600 text-emerald-600 rounded-lg hover:bg-emerald-600 hover:text-white transition-colors text-sm font-medium">
                                Select
                              </a>
                            </div> -->
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>

                    <!-- Flight Route Tab -->
                    <div id="route-<?php echo $flight['id']; ?>" class="tab-pane hidden">
                      <div class="bg-gray-50 rounded-xl p-6">
                        <div class="relative">
                          <!-- Timeline Line -->
                          <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-300"></div>

                          <div class="space-y-8">
                            <!-- Departure -->
                            <div class="flex">
                              <div class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center z-10">
                                <i class="fas fa-plane-departure text-white text-sm"></i>
                              </div>
                              <div class="ml-6">
                                <p class="font-bold text-gray-800"><?php echo htmlspecialchars($flight['departure_city']); ?></p>
                                <p class="text-gray-600"><?php echo date('h:i A', strtotime($flight['departure_time'])); ?></p>
                                <p class="text-sm text-gray-500"><?php echo date('l, F j, Y', strtotime($flight['departure_date'])); ?></p>
                              </div>
                            </div>

                            <!-- Stops -->
                            <?php
                            $stops = json_decode($flight['stops'], true);
                            if (is_array($stops) && !empty($stops)):
                              foreach ($stops as $stop):
                            ?>
                                <div class="flex">
                                  <div class="flex-shrink-0 w-8 h-8 rounded-full bg-amber-500 flex items-center justify-center z-10">
                                    <i class="fas fa-map-marker-alt text-white text-sm"></i>
                                  </div>
                                  <div class="ml-6">
                                    <p class="font-bold text-gray-800"><?php echo htmlspecialchars($stop['city']); ?></p>
                                    <p class="text-gray-600">Layover: <?php echo htmlspecialchars($stop['duration']); ?> hours</p>
                                    <?php if (isset($stop['airport'])): ?>
                                      <p class="text-sm text-gray-500"><?php echo htmlspecialchars($stop['airport']); ?></p>
                                    <?php endif; ?>
                                  </div>
                                </div>
                            <?php
                              endforeach;
                            endif;
                            ?>

                            <!-- Arrival -->
                            <div class="flex">
                              <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center z-10">
                                <i class="fas fa-plane-arrival text-white text-sm"></i>
                              </div>
                              <div class="ml-6">
                                <p class="font-bold text-gray-800"><?php echo htmlspecialchars($flight['arrival_city']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo date('l, F j, Y', strtotime($flight['departure_date'])); ?></p>
                              </div>
                            </div>
                          </div>
                        </div>

                        <!-- Flight Summary -->
                        <div class="mt-8 p-4 bg-white rounded-lg border border-gray-200">
                          <h6 class="font-medium text-gray-700 mb-2">Flight Summary</h6>
                          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                              <p class="text-sm text-gray-500">Total Duration</p>
                              <p class="font-medium"><?php echo !empty($flight['flight_duration']) ? htmlspecialchars($flight['flight_duration']) . ' hours' : 'Approx. 6h'; ?></p>
                            </div>
                            <div>
                              <p class="text-sm text-gray-500">Distance</p>
                              <p class="font-medium"><?php echo isset($flight['distance']) ? htmlspecialchars($flight['distance']) . ' km' : 'Varies by route'; ?></p>
                            </div>
                            <div>
                              <p class="text-sm text-gray-500">Flight Type</p>
                              <p class="font-medium">
                                <?php
                                if (empty($stops) || $stops === "direct" || !is_array($stops)) {
                                  echo 'Direct Flight';
                                } else {
                                  echo count($stops) . ' ' . (count($stops) > 1 ? 'Stops' : 'Stop');
                                }
                                ?>
                              </p>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Flight Information Tab -->
                    <div id="info-<?php echo $flight['id']; ?>" class="tab-pane hidden">
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Airline Info -->
                        <div class="bg-gray-50 rounded-xl p-4">
                          <h6 class="font-medium text-gray-700 mb-3">Airline Information</h6>
                          <div class="space-y-3">
                            <div class="flex items-center">
                              <div class="w-8 text-emerald-600">
                                <i class="fas fa-plane"></i>
                              </div>
                              <div>
                                <p class="text-sm text-gray-500">Airline</p>
                                <p class="font-medium"><?php echo htmlspecialchars($flight['airline_name'] ?? 'N/A'); ?></p>
                              </div>
                            </div>
                            <div class="flex items-center">
                              <div class="w-8 text-emerald-600">
                                <i class="fas fa-id-card"></i>
                              </div>
                              <div>
                                <p class="text-sm text-gray-500">Flight Number</p>
                                <p class="font-medium"><?php echo htmlspecialchars($flight['flight_number']); ?></p>
                              </div>
                            </div>
                            <?php if (isset($flight['aircraft_type']) && !empty($flight['aircraft_type'])): ?>
                              <div class="flex items-center">
                                <div class="w-8 text-emerald-600">
                                  <i class="fas fa-plane-departure"></i>
                                </div>
                                <div>
                                  <p class="text-sm text-gray-500">Aircraft Type</p>
                                  <p class="font-medium"><?php echo htmlspecialchars($flight['aircraft_type']); ?></p>
                                </div>
                              </div>
                            <?php endif; ?>
                          </div>
                        </div>

                        <!-- Additional Info -->
                        <div class="bg-gray-50 rounded-xl p-4">
                          <h6 class="font-medium text-gray-700 mb-3">Additional Information</h6>

                          <?php if (!empty($flight['flight_notes'])): ?>
                            <div class="mb-4">
                              <p class="text-sm text-gray-500 mb-1">Flight Notes</p>
                              <p class="bg-white p-3 rounded-lg border border-gray-200 text-sm">
                                <?php echo htmlspecialchars($flight['flight_notes']); ?>
                              </p>
                            </div>
                          <?php endif; ?>

                          <!-- Flight Features -->
                          <div>
                            <p class="text-sm text-gray-500 mb-2">Flight Features</p>
                            <div class="flex flex-wrap gap-2">
                              <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                <i class="fas fa-wifi mr-1"></i> Wi-Fi
                              </span>
                              <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700">
                                <i class="fas fa-utensils mr-1"></i> Meals
                              </span>
                              <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-pink-50 text-pink-700">
                                <i class="fas fa-tv mr-1"></i> Entertainment
                              </span>
                              <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700">
                                <i class="fas fa-plug mr-1"></i> Power Outlets
                              </span>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Return Flight Information Panel -->
                      <?php
                      if (isset($flight['return_flight_data']) && !empty($flight['return_flight_data'])):
                        $return_data = json_decode($flight['return_flight_data'], true);
                        if ($return_data && isset($return_data['has_return']) && $return_data['has_return'] == 1):
                      ?>
                          <div class="mt-6 bg-purple-50 rounded-lg p-4 border border-purple-200">
                            <h3 class="text-lg font-medium text-purple-700 mb-2">
                              <i class="fas fa-plane-arrival mr-2"></i> Return Flight Information
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                              <div>
                                <!-- Return flight details -->
                                <div class="mb-3">
                                  <p class="text-sm text-gray-600 mb-1">Return Route</p>
                                  <p class="font-medium">
                                    <?php echo htmlspecialchars($flight['arrival_city']); ?> to <?php echo htmlspecialchars($flight['departure_city']); ?>
                                  </p>
                                </div>

                                <?php if (isset($return_data['return_date'])): ?>
                                  <div class="mb-3">
                                    <p class="text-sm text-gray-600 mb-1">Return Date</p>
                                    <p class="font-medium">
                                      <?php echo date('D, M j, Y', strtotime($return_data['return_date'])); ?>
                                    </p>
                                  </div>
                                <?php endif; ?>

                                <?php if (isset($return_data['return_time'])): ?>
                                  <div class="mb-3">
                                    <p class="text-sm text-gray-600 mb-1">Return Time</p>
                                    <p class="font-medium">
                                      <?php echo date('h:i A', strtotime($return_data['return_time'])); ?>
                                    </p>
                                  </div>
                                <?php endif; ?>
                              </div>

                              <div>
                                <?php if (isset($return_data['return_flight_number'])): ?>
                                  <div class="mb-3">
                                    <p class="text-sm text-gray-600 mb-1">Return Flight Number</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($return_data['return_flight_number']); ?></p>
                                  </div>
                                <?php endif; ?>

                                <?php if (isset($return_data['return_flight_duration'])): ?>
                                  <div class="mb-3">
                                    <p class="text-sm text-gray-600 mb-1">Return Flight Duration</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($return_data['return_flight_duration']); ?> hours</p>
                                  </div>
                                <?php endif; ?>

                                <?php if (isset($return_data['return_airline'])): ?>
                                  <div class="mb-3">
                                    <p class="text-sm text-gray-600 mb-1">Return Airline</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($return_data['return_airline']); ?></p>
                                  </div>
                                <?php endif; ?>
                              </div>
                            </div>

                            <?php
                            if (isset($return_data['return_stops']) && !empty($return_data['return_stops']) && $return_data['return_stops'] != 'direct'):
                              $stops_data = json_decode($return_data['return_stops'], true);
                              if (is_array($stops_data) && !empty($stops_data)):
                            ?>
                                <div class="mt-3">
                                  <p class="text-sm text-gray-600 mb-1">Return Flight Stops</p>
                                  <div class="bg-white rounded p-3 border border-gray-200">
                                    <?php foreach ($stops_data as $stop): ?>
                                      <div class="mb-2">
                                        <i class="fas fa-map-marker-alt text-amber-500 mr-2"></i>
                                        <?php echo htmlspecialchars($stop['city']); ?>
                                        (<?php echo htmlspecialchars($stop['duration']); ?> hrs)
                                      </div>
                                    <?php endforeach; ?>
                                  </div>
                                </div>
                            <?php
                              endif;
                            endif;
                            ?>
                          </div>
                      <?php endif;
                      endif; ?>
                    </div>

                    <!-- Return Flight Tab -->
                    <?php if (isset($flight['has_return']) && $flight['has_return']): ?>
                      <div id="return-<?php echo $flight['id']; ?>" class="tab-pane hidden">
                        <div class="bg-purple-50 rounded-xl p-6">
                          <h6 class="font-bold text-purple-700 mb-4">
                            <i class="fas fa-plane-arrival mr-2"></i> Return Flight Details
                          </h6>

                          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-white rounded-lg shadow-sm p-4">
                              <h6 class="font-medium text-gray-700 mb-3">Return Journey</h6>

                              <div class="space-y-3">
                                <div class="flex items-center">
                                  <div class="w-8 text-purple-600">
                                    <i class="fas fa-calendar-day"></i>
                                  </div>
                                  <div>
                                    <p class="text-sm text-gray-500">Return Date</p>
                                    <p class="font-medium">
                                      <?php echo !empty($flight['return_flight_info']['return_date'])
                                        ? date('D, M j, Y', strtotime($flight['return_flight_info']['return_date']))
                                        : 'Not specified'; ?>
                                    </p>
                                  </div>
                                </div>

                                <div class="flex items-center">
                                  <div class="w-8 text-purple-600">
                                    <i class="fas fa-clock"></i>
                                  </div>
                                  <div>
                                    <p class="text-sm text-gray-500">Return Time</p>
                                    <p class="font-medium">
                                      <?php echo !empty($flight['return_flight_info']['return_time'])
                                        ? date('h:i A', strtotime($flight['return_flight_info']['return_time']))
                                        : 'Not specified'; ?>
                                    </p>
                                  </div>
                                </div>

                                <?php if (!empty($flight['return_flight_info']['return_flight_number'])): ?>
                                  <div class="flex items-center">
                                    <div class="w-8 text-purple-600">
                                      <i class="fas fa-plane"></i>
                                    </div>
                                    <div>
                                      <p class="text-sm text-gray-500">Flight Number</p>
                                      <p class="font-medium"><?php echo htmlspecialchars($flight['return_flight_info']['return_flight_number']); ?></p>
                                    </div>
                                  </div>
                                <?php endif; ?>

                                <?php if (!empty($flight['return_flight_info']['return_flight_duration'])): ?>
                                  <div class="flex items-center">
                                    <div class="w-8 text-purple-600">
                                      <i class="fas fa-hourglass-half"></i>
                                    </div>
                                    <div>
                                      <p class="text-sm text-gray-500">Flight Duration</p>
                                      <p class="font-medium"><?php echo htmlspecialchars($flight['return_flight_info']['return_flight_duration']); ?> hours</p>
                                    </div>
                                  </div>
                                <?php endif; ?>

                                <?php if (!empty($flight['return_flight_info']['return_airline']) && $flight['return_flight_info']['return_airline'] != 'same'): ?>
                                  <div class="flex items-center">
                                    <div class="w-8 text-purple-600">
                                      <i class="fas fa-building"></i>
                                    </div>
                                    <div>
                                      <p class="text-sm text-gray-500">Airline</p>
                                      <p class="font-medium"><?php echo htmlspecialchars($flight['return_flight_info']['return_airline']); ?></p>
                                    </div>
                                  </div>
                                <?php endif; ?>
                              </div>
                            </div>

                            <div class="bg-white rounded-lg shadow-sm p-4">
                              <h6 class="font-medium text-gray-700 mb-3">Return Flight Route</h6>

                              <?php
                              // Check return stops
                              $return_stops_json = isset($flight['return_flight_info']['return_stops']) ? $flight['return_flight_info']['return_stops'] : null;
                              $return_stops = $return_stops_json ? json_decode($return_stops_json, true) : null;
                              $has_return_stops = (!empty($return_stops) && $return_stops !== "direct" && is_array($return_stops));
                              ?>

                              <div class="relative pb-6">
                                <!-- Timeline Line -->
                                <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-300"></div>

                                <div class="space-y-8">
                                  <!-- Departure (now the arrival city of outbound) -->
                                  <div class="flex">
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-purple-500 flex items-center justify-center z-10">
                                      <i class="fas fa-plane-departure text-white text-sm"></i>
                                    </div>
                                    <div class="ml-6">
                                      <p class="font-bold text-gray-800"><?php echo htmlspecialchars($flight['arrival_city']); ?></p>
                                      <?php if (!empty($flight['return_flight_info']['return_time'])): ?>
                                        <p class="text-gray-600"><?php echo date('h:i A', strtotime($flight['return_flight_info']['return_time'])); ?></p>
                                      <?php endif; ?>
                                      <?php if (!empty($flight['return_flight_info']['return_date'])): ?>
                                        <p class="text-sm text-gray-500"><?php echo date('l, F j, Y', strtotime($flight['return_flight_info']['return_date'])); ?></p>
                                      <?php endif; ?>
                                    </div>
                                  </div>

                                  <!-- Return Stops -->
                                  <?php
                                  if ($has_return_stops):
                                    foreach ($return_stops as $stop):
                                  ?>
                                      <div class="flex">
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-amber-500 flex items-center justify-center z-10">
                                          <i class="fas fa-map-marker-alt text-white text-sm"></i>
                                        </div>
                                        <div class="ml-6">
                                          <p class="font-bold text-gray-800"><?php echo htmlspecialchars($stop['city']); ?></p>
                                          <p class="text-gray-600">Layover: <?php echo htmlspecialchars($stop['duration']); ?> hours</p>
                                        </div>
                                      </div>
                                  <?php
                                    endforeach;
                                  endif;
                                  ?>

                                  <!-- Arrival (now the departure city of outbound) -->
                                  <div class="flex">
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center z-10">
                                      <i class="fas fa-plane-arrival text-white text-sm"></i>
                                    </div>
                                    <div class="ml-6">
                                      <p class="font-bold text-gray-800"><?php echo htmlspecialchars($flight['departure_city']); ?></p>
                                    </div>
                                  </div>
                                </div>
                              </div>

                              <!-- Route Type -->
                              <div class="mt-4 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?php echo $has_return_stops ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800'; ?>">
                                  <i class="fas <?php echo $has_return_stops ? 'fa-map-marker-alt' : 'fa-check-circle'; ?> mr-1"></i>
                                  <?php echo $has_return_stops ? (count($return_stops) . ' ' . (count($return_stops) > 1 ? 'Stops' : 'Stop')) : 'Direct Return Flight'; ?>
                                </span>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    <?php endif; ?>
                  </div>

                  <!-- Book Now Button -->
                  <div class="mt-6 flex justify-center">
                    <a href="booking-flight.php?flight_id=<?php echo $flight['id']; ?>"
                      class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2.5 px-6 rounded-lg transition duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-1 flex items-center">
                      <i class="fas fa-ticket-alt mr-2"></i> Book Now
                    </a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- RETURN FLIGHTS (only show if this is a separate search for return flights) -->
        <?php if ($is_round_trip && !empty($return_flights)): ?>
          <!-- Results Header for Return Flights -->
          <div class="flight-type-header mb-6">
            <i class="fas fa-plane-arrival text-purple-600 mr-2"></i> Return Flights
            <span class="ml-2 text-sm text-gray-500"><?php echo count($return_flights); ?> flights found</span>
          </div>

          <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">
              <i class="fas fa-undo-alt text-purple-600 mr-2"></i>
              <?php echo count($return_flights); ?> Return Flights Found
            </h2>
            <?php if ($search_performed): ?>
              <p class="text-gray-600 text-sm">
                Return from <?php echo htmlspecialchars(isset($_POST['arrival_city']) ? $_POST['arrival_city'] : ''); ?> to
                <?php echo htmlspecialchars(isset($_POST['departure_city']) ? $_POST['departure_city'] : ''); ?>
                <?php if (isset($_POST['return_date']) && !empty($_POST['return_date'])): ?>
                  on <?php echo date('D, M j, Y', strtotime($_POST['return_date'])); ?>
                <?php endif; ?>
              </p>
            <?php endif; ?>
          </div>

          <!-- Return Flight Cards -->
          <div class="space-y-6">
            <?php foreach ($return_flights as $index => $flight):
              // Get minimum price
              $prices = json_decode($flight['prices'], true);
              $min_price = !empty($prices) ? min(array_values($prices)) : 0;

              // Determine if this is the cheapest flight
              $is_cheapest = $index === 0 && isset($_GET['sort']) && $_GET['sort'] === 'price';
            ?>
              <div class="flight-card return-flight-card bg-white shadow-sm hover:shadow-md p-6 relative <?php echo $is_cheapest ? 'animate__animated animate__pulse' : ''; ?>">
                <div class="return-flight-badge">Return Flight</div>

                <?php if ($is_cheapest): ?>
                  <div class="price-badge">Best Deal</div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                  <!-- Airline Info -->
                  <div class="md:col-span-3">
                    <div class="flex items-center">
                      <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-plane-arrival text-purple-600 text-xl"></i>
                      </div>
                      <div>
                        <h5 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($flight['airline_name'] ?? 'Airline'); ?></h5>
                        <p class="text-gray-500 text-sm">
                          Flight: <?php echo htmlspecialchars($flight['flight_number']); ?>
                        </p>
                      </div>
                    </div>

                    <!-- Price for mobile view -->
                    <div class="md:hidden mt-4 text-right">
                      <div class="font-bold text-2xl text-purple-600">$<?php echo $min_price; ?></div>
                      <p class="text-sm text-gray-500">Lowest fare</p>
                    </div>
                  </div>

                  <!-- Flight Info -->
                  <div class="md:col-span-6">
                    <div class="flex items-center justify-between mb-4">
                      <!-- Departure -->
                      <div class="text-center">
                        <p class="text-2xl font-bold text-gray-800">
                          <?php echo date('H:i', strtotime($flight['departure_time'])); ?>
                        </p>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($flight['departure_city']); ?></p>
                      </div>

                      <!-- Flight Path Visualization -->
                      <div class="flight-route flex-1 mx-4 flex items-center justify-between">
                        <div class="city text-center">
                          <i class="fas fa-circle text-purple-600 text-xs"></i>
                        </div>
                        <div class="route-line flex-1 mx-2 h-0.5 bg-gray-300 relative overflow-hidden">
                          <div class="plane-icon">
                            <i class="fas fa-plane text-purple-600"></i>
                          </div>
                        </div>
                        <div class="city text-center">
                          <i class="fas fa-circle text-purple-600 text-xs"></i>
                        </div>
                      </div>

                      <!-- Arrival -->
                      <div class="text-center">
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($flight['arrival_city']); ?></p>
                      </div>
                    </div>

                    <!-- Secondary Info -->
                    <div class="flex items-center justify-between border-t pt-3">
                      <!-- Date -->
                      <div>
                        <p class="text-sm text-gray-600">
                          <i class="far fa-calendar-alt text-purple-600 mr-1"></i>
                          <?php echo date('D, M j, Y', strtotime($flight['departure_date'])); ?>
                        </p>
                      </div>

                      <!-- Flight Duration -->
                      <?php if (!empty($flight['flight_duration'])): ?>
                        <div>
                          <p class="text-sm text-gray-600">
                            <i class="far fa-clock text-purple-600 mr-1"></i>
                            Duration: <?php echo htmlspecialchars($flight['flight_duration']); ?> hours
                          </p>
                        </div>
                      <?php endif; ?>

                      <!-- Stops Badge -->
                      <?php
                      $stops = json_decode($flight['stops'], true);
                      if (empty($stops) || $stops === "direct" || !is_array($stops)):
                      ?>
                        <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                          <i class="fas fa-check-circle mr-1"></i> Direct Flight
                        </div>
                      <?php else: ?>
                        <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                          <i class="fas fa-map-marker-alt mr-1"></i>
                          <?php echo count($stops) . ' ' . (count($stops) > 1 ? 'Stops' : 'Stop'); ?>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>

                  <!-- Price & Action -->
                  <div class="md:col-span-3 flex flex-col justify-between items-end">
                    <!-- Price -->
                    <div class="hidden md:block">
                      <div class="font-bold text-2xl text-purple-600">$<?php echo $min_price; ?></div>
                      <p class="text-sm text-gray-500">Lowest fare</p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="w-full md:w-auto">
                      <button type="button" class="view-details-btn w-full md:w-auto bg-purple-50 hover:bg-purple-100 text-purple-700 font-medium py-2 px-4 rounded-lg text-sm transition" data-flight-id="<?php echo $flight['id']; ?>-return">
                        <i class="fas fa-chevron-down mr-1 details-icon-<?php echo $flight['id']; ?>-return"></i> View Details
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Expandable Details Section for Return Flight -->
                <div id="details-<?php echo $flight['id']; ?>-return" class="details-transition mt-4 pt-4 border-t border-gray-200">
                  <!-- Return Flight Details... (Similar to outbound, with color differences) -->
                  <!-- Tab Navigation -->
                  <div class="tabs mb-4">
                    <div class="tab active" data-tab="pricing-<?php echo $flight['id']; ?>-return">
                      <i class="fas fa-tag mr-1"></i> Pricing & Classes
                    </div>
                    <div class="tab" data-tab="route-<?php echo $flight['id']; ?>-return">
                      <i class="fas fa-route mr-1"></i> Flight Route
                    </div>
                    <div class="tab" data-tab="info-<?php echo $flight['id']; ?>-return">
                      <i class="fas fa-info-circle mr-1"></i> Flight Information
                    </div>
                  </div>

                  <!-- Tab Content -->
                  <div class="tab-content">
                    <!-- Pricing & Classes Tab -->
                    <div id="pricing-<?php echo $flight['id']; ?>-return" class="tab-pane active">
                      <!-- Similar pricing content to outbound flights... -->
                      <h6 class="font-medium text-gray-700 mb-3">
                        Available Classes and Pricing
                      </h6>
                      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <!-- Similar pricing display... -->
                        <?php
                        $prices = json_decode($flight['prices'], true);
                        $seats = json_decode($flight['seats'], true);
                        $classes = json_decode($flight['cabin_class']);

                        if (!is_array($classes)) {
                          $classes = [];
                        }

                        foreach ($classes as $class):
                          $class_key = strtolower(str_replace(' ', '_', $class));

                          // Determine icon based on class
                          $classIcon = 'fa-chair';
                          $bgColor = 'bg-gray-50';
                          $textColor = 'text-gray-700';

                          if (stripos($class, 'business') !== false) {
                            $classIcon = 'fa-briefcase';
                            $bgColor = 'bg-purple-50';
                            $textColor = 'text-purple-700';
                          } elseif (stripos($class, 'first') !== false) {
                            $classIcon = 'fa-crown';
                            $bgColor = 'bg-blue-50';
                            $textColor = 'text-blue-700';
                          } elseif (stripos($class, 'economy') !== false) {
                            $classIcon = 'fa-chair';
                            $bgColor = 'bg-green-50';
                            $textColor = 'text-green-700';
                          }
                        ?>
                          <div class="<?php echo $bgColor; ?> border border-gray-200 rounded-xl p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-center mb-3">
                              <div class="w-8 h-8 rounded-full <?php echo str_replace('bg-', 'bg-', $bgColor); ?> border border-gray-200 flex items-center justify-center mr-2">
                                <i class="fas <?php echo $classIcon; ?> <?php echo $textColor; ?>"></i>
                              </div>
                              <span class="font-medium <?php echo $textColor; ?>"><?php echo htmlspecialchars(ucwords($class)); ?></span>
                            </div>
                            <div class="space-y-2">
                              <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Price:</span>
                                <span class="font-bold <?php echo $textColor; ?>">$<?php echo isset($prices[$class_key]) ? number_format($prices[$class_key], 2) : 'N/A'; ?></span>
                              </div>
                              <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Available Seats:</span>
                                <span class="font-medium">
                                  <?php
                                  $total_seats = isset($seats[$class_key]['count']) ? $seats[$class_key]['count'] : 0;
                                  $booked_seats = isset($seats[$class_key]['booked']) ? $seats[$class_key]['booked'] : 0;
                                  $remaining_seats = $total_seats - $booked_seats;
                                  echo $remaining_seats . ' of ' . $total_seats;
                                  ?>
                                </span>
                              </div>
                            </div>

                            <div class="mt-4">
                              <a href="booking-flight.php?flight_id=<?php echo $flight['id']; ?>&cabin_class=<?php echo urlencode($class); ?>&is_return=1"
                                class="block w-full text-center py-2 px-4 border border-purple-600 text-purple-600 rounded-lg hover:bg-purple-600 hover:text-white transition-colors text-sm font-medium">
                                Select
                              </a>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>

                    <!-- Other tabs similar to outbound flights but with purple theme... -->
                    <!-- Route tab -->
                    <div id="route-<?php echo $flight['id']; ?>-return" class="tab-pane hidden">
                      <!-- Similar route display... -->
                      <div class="bg-purple-50 rounded-xl p-6">
                        <!-- Similar to outbound with route visualization... -->
                        <div class="relative">
                          <!-- Timeline Line -->
                          <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-300"></div>

                          <!-- Similar timeline structure... -->
                          <div class="space-y-8">
                            <!-- Departure -->
                            <div class="flex">
                              <div class="flex-shrink-0 w-8 h-8 rounded-full bg-purple-500 flex items-center justify-center z-10">
                                <i class="fas fa-plane-departure text-white text-sm"></i>
                              </div>
                              <div class="ml-6">
                                <p class="font-bold text-gray-800"><?php echo htmlspecialchars($flight['departure_city']); ?></p>
                                <p class="text-gray-600"><?php echo date('h:i A', strtotime($flight['departure_time'])); ?></p>
                                <p class="text-sm text-gray-500"><?php echo date('l, F j, Y', strtotime($flight['departure_date'])); ?></p>
                              </div>
                            </div>

                            <!-- Stops -->
                            <?php
                            if (is_array($stops) && !empty($stops)):
                              foreach ($stops as $stop):
                            ?>
                                <div class="flex">
                                  <div class="flex-shrink-0 w-8 h-8 rounded-full bg-amber-500 flex items-center justify-center z-10">
                                    <i class="fas fa-map-marker-alt text-white text-sm"></i>
                                  </div>
                                  <div class="ml-6">
                                    <p class="font-bold text-gray-800"><?php echo htmlspecialchars($stop['city']); ?></p>
                                    <p class="text-gray-600">Layover: <?php echo htmlspecialchars($stop['duration']); ?> hours</p>
                                  </div>
                                </div>
                            <?php
                              endforeach;
                            endif;
                            ?>

                            <!-- Arrival -->
                            <div class="flex">
                              <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center z-10">
                                <i class="fas fa-plane-arrival text-white text-sm"></i>
                              </div>
                              <div class="ml-6">
                                <p class="font-bold text-gray-800"><?php echo htmlspecialchars($flight['arrival_city']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo date('l, F j, Y', strtotime($flight['departure_date'])); ?></p>
                              </div>
                            </div>
                          </div>
                        </div>

                        <!-- Flight Summary -->
                        <div class="mt-8 p-4 bg-white rounded-lg border border-gray-200">
                          <h6 class="font-medium text-gray-700 mb-2">Flight Summary</h6>
                          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                              <p class="text-sm text-gray-500">Total Duration</p>
                              <p class="font-medium"><?php echo !empty($flight['flight_duration']) ? htmlspecialchars($flight['flight_duration']) . ' hours' : 'Approx. 6h'; ?></p>
                            </div>
                            <div>
                              <p class="text-sm text-gray-500">Distance</p>
                              <p class="font-medium"><?php echo isset($flight['distance']) ? htmlspecialchars($flight['distance']) . ' km' : 'Varies by route'; ?></p>
                            </div>
                            <div>
                              <p class="text-sm text-gray-500">Flight Type</p>
                              <p class="font-medium">
                                <?php
                                if (empty($stops) || $stops === "direct" || !is_array($stops)) {
                                  echo 'Direct Flight';
                                } else {
                                  echo count($stops) . ' ' . (count($stops) > 1 ? 'Stops' : 'Stop');
                                }
                                ?>
                              </p>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Info tab -->
                    <div id="info-<?php echo $flight['id']; ?>-return" class="tab-pane hidden">
                      <!-- Similar info display... -->
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Airline Info -->
                        <div class="bg-purple-50 rounded-xl p-4">
                          <h6 class="font-medium text-gray-700 mb-3">Airline Information</h6>
                          <div class="space-y-3">
                            <div class="flex items-center">
                              <div class="w-8 text-purple-600">
                                <i class="fas fa-plane"></i>
                              </div>
                              <div>
                                <p class="text-sm text-gray-500">Airline</p>
                                <p class="font-medium"><?php echo htmlspecialchars($flight['airline_name'] ?? 'N/A'); ?></p>
                              </div>
                            </div>
                            <div class="flex items-center">
                              <div class="w-8 text-purple-600">
                                <i class="fas fa-id-card"></i>
                              </div>
                              <div>
                                <p class="text-sm text-gray-500">Flight Number</p>
                                <p class="font-medium"><?php echo htmlspecialchars($flight['flight_number']); ?></p>
                              </div>
                            </div>
                          </div>
                        </div>

                        <!-- Additional Info -->
                        <div class="bg-purple-50 rounded-xl p-4">
                          <h6 class="font-medium text-gray-700 mb-3">Additional Information</h6>

                          <?php if (!empty($flight['flight_notes'])): ?>
                            <div class="mb-4">
                              <p class="text-sm text-gray-500 mb-1">Flight Notes</p>
                              <p class="bg-white p-3 rounded-lg border border-gray-200 text-sm">
                                <?php echo htmlspecialchars($flight['flight_notes']); ?>
                              </p>
                            </div>
                          <?php endif; ?>

                          <!-- Flight Features -->
                          <div>
                            <p class="text-sm text-gray-500 mb-2">Flight Features</p>
                            <div class="flex flex-wrap gap-2">
                              <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                <i class="fas fa-wifi mr-1"></i> Wi-Fi
                              </span>
                              <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700">
                                <i class="fas fa-utensils mr-1"></i> Meals
                              </span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Book Now Button -->
                  <div class="mt-6 flex justify-center">
                    <a href="booking-flight.php?flight_id=<?php echo $flight['id']; ?>&is_return=1"
                      class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2.5 px-6 rounded-lg transition duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-1 flex items-center">
                      <i class="fas fa-ticket-alt mr-2"></i> Book Return
                    </a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Trip type selector
      const tripTypeOptions = document.querySelectorAll('.trip-type-option');
      const tripTypeInput = document.getElementById('trip_type');
      const returnDateContainer = document.getElementById('return-date-container');
      const passengersInput = document.querySelector('.passengers-input');

      tripTypeOptions.forEach(option => {
        option.addEventListener('click', function() {
          // Remove active class from all options
          tripTypeOptions.forEach(opt => opt.classList.remove('active'));

          // Add active class to clicked option
          this.classList.add('active');

          // Update hidden input value
          const tripType = this.getAttribute('data-trip-type');
          tripTypeInput.value = tripType;

          // Show/hide return date based on trip type
          if (tripType === 'round_trip') {
            returnDateContainer.style.display = 'block';
            // If trip type is round trip, rearrange the layout
            if (passengersInput) {
              passengersInput.style.display = 'none';
            }
          } else {
            returnDateContainer.style.display = 'none';
            // If trip type is one way, restore the layout
            if (passengersInput) {
              passengersInput.style.display = 'block';
            }
          }

          // Set the radio button as checked
          const radioInput = this.querySelector('input[type="radio"]');
          if (radioInput) {
            radioInput.checked = true;
          }
        });
      });

      // Show loading overlay when searching
      const searchForm = document.getElementById('flight-search-form');
      const loadingOverlay = document.getElementById('loading-overlay');

      if (searchForm && loadingOverlay) {
        searchForm.addEventListener('submit', function() {
          loadingOverlay.classList.remove('hidden');
        });
      }

      // Handle swap cities functionality
      const swapButton = document.getElementById('swap-cities');
      const departureSelect = document.getElementById('departure_city');
      const arrivalSelect = document.getElementById('arrival_city');

      if (swapButton && departureSelect && arrivalSelect) {
        swapButton.addEventListener('click', function() {
          const tempDeparture = departureSelect.value;
          departureSelect.value = arrivalSelect.value;
          arrivalSelect.value = tempDeparture;

          // Add animation
          swapButton.classList.add('animate__animated', 'animate__rotateIn');
          setTimeout(() => {
            swapButton.classList.remove('animate__animated', 'animate__rotateIn');
          }, 1000);
        });
      }

      // Handle view details buttons
      const viewDetailsButtons = document.querySelectorAll('.view-details-btn');

      viewDetailsButtons.forEach(button => {
        button.addEventListener('click', function() {
          const flightId = this.getAttribute('data-flight-id');
          const detailsDiv = document.getElementById('details-' + flightId);
          const icon = document.querySelector('.details-icon-' + flightId);

          // Check if the button is for outbound or return flight
          const isReturn = flightId.includes('-return');
          const baseColor = isReturn ? 'purple' : 'emerald';

          if (detailsDiv.classList.contains('open')) {
            detailsDiv.classList.remove('open');
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
            button.classList.remove(`bg-${baseColor}-100`);
            button.classList.add(`bg-${baseColor}-50`);
          } else {
            detailsDiv.classList.add('open');
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
            button.classList.remove(`bg-${baseColor}-50`);
            button.classList.add(`bg-${baseColor}-100`);
          }
        });
      });

      // Handle tabs
      const tabs = document.querySelectorAll('.tab');

      tabs.forEach(tab => {
        tab.addEventListener('click', function() {
          const tabId = this.getAttribute('data-tab');
          const flightId = tabId.split('-')[1];
          const isReturn = tabId.includes('-return');
          const suffix = isReturn ? '-return' : '';

          // Get all tabs and panes for this flight
          const flightTabs = document.querySelectorAll(`[data-tab$="-${flightId}${suffix}"]`);
          const tabPanes = document.querySelectorAll(`[id$="-${flightId}${suffix}"].tab-pane`);

          // Remove active class from all tabs and panes
          flightTabs.forEach(t => t.classList.remove('active'));
          tabPanes.forEach(p => {
            p.classList.add('hidden');
            p.classList.remove('active');
          });

          // Add active class to current tab and pane
          this.classList.add('active');
          document.getElementById(tabId).classList.remove('hidden');
          document.getElementById(tabId).classList.add('active');
        });
      });

      // Function to remove filters
      window.removeFilter = function(filterName) {
        const form = document.getElementById('flight-search-form');
        const input = form.querySelector(`[name="${filterName}"]`);

        if (input) {
          input.value = '';
          form.submit();
        }
      };
    });
  </script>
</body>

</html>

<?php
// Close connection
$conn->close();
?>