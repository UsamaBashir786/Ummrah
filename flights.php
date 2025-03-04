<?php
session_start();
include 'connection/connection.php';

// Initialize variables
$departure_city = '';
$arrival_city = '';
$departure_date = '';
$returnDate = '';
$travelers = 1;
$cabin_class = 'Economy';
$search_results = [];
$is_searching = false;

// Process search form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_flights'])) {
  $departure_city = trim($_POST['departure_city']);
  $arrival_city = trim($_POST['arrival_city']);
  $departure_date = trim($_POST['departure_date']);
  $travelers = intval($_POST['travelers']);
  $cabin_class = trim($_POST['cabin_class']);
  $is_searching = true;

  try {
    // Search for matching flights
    $sql = "SELECT * FROM flights WHERE 
                departure_city = ? AND 
                arrival_city = ? AND 
                departure_date = ?
                ORDER BY departure_time ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $departure_city, $arrival_city, $departure_date);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
        $search_results[] = $row;
      }
    }

    $stmt->close();
  } catch (Exception $e) {
    $error_message = "Search error: " . $e->getMessage();
  }
}

// Process booking form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_flight'])) {
  // In a real application, you would handle payment processing here
  // and save the booking to a bookings database table

  $flight_id = intval($_POST['flight_id']);
  $passenger_name = trim($_POST['passenger_name']);
  $passenger_email = trim($_POST['passenger_email']);
  $passenger_phone = trim($_POST['passenger_phone']);
  $selected_class = trim($_POST['selected_class']);
  $num_travelers = intval($_POST['num_travelers']);

  // You would add code here to:
  // 1. Verify flight availability
  // 2. Calculate total price
  // 3. Process payment
  // 4. Create booking record
  // 5. Send confirmation email

  // For this demonstration, we'll just set a success message
  $booking_success = true;
  $booking_reference = "UMR" . strtoupper(substr(md5(time()), 0, 8));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <style>
    .hero-section {
      background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('assets/images/hero/hero.jpg');
      background-size: cover;
      background-position: center;
    }

    .search-box {
      background-color: rgba(255, 255, 255, 0.9);
      border-radius: 10px;
    }

    .flight-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
  </style>
</head>

<body class="bg-gray-100">
  <!-- Navigation -->
  <nav class="bg-white shadow-md">
    <div class="container mx-auto px-4 py-3 flex justify-between items-center">
      <div class="flex items-center">
        <a href="index.php" class="text-2xl font-bold text-teal-600">
          <i class="fas fa-kaaba mr-2"></i>UmrahFlights
        </a>
      </div>
      <div class="flex items-center space-x-6">
        <a href="#" class="text-gray-700 hover:text-teal-600">Home</a>
        <a href="#" class="text-gray-700 hover:text-teal-600">Flights</a>
        <a href="#" class="text-gray-700 hover:text-teal-600">Packages</a>
        <a href="#" class="text-gray-700 hover:text-teal-600">Contact</a>
        <a href="#" class="bg-teal-600 text-white px-4 py-2 rounded hover:bg-teal-700">
          <i class="fas fa-user mr-1"></i> Login
        </a>
      </div>
    </div>
  </nav>

  <?php if (isset($booking_success) && $booking_success): ?>
    <!-- Booking Success Message -->
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 my-6 container mx-auto" role="alert">
      <div class="flex">
        <div class="py-1"><i class="fas fa-check-circle text-2xl mr-4"></i></div>
        <div>
          <p class="font-bold">Booking Confirmed!</p>
          <p>Your booking has been confirmed. Your reference number is: <strong><?php echo $booking_reference; ?></strong></p>
          <p class="mt-2">A confirmation email has been sent to <?php echo htmlspecialchars($passenger_email); ?></p>
          <a href="index.php" class="mt-4 inline-block bg-teal-600 text-white px-4 py-2 rounded hover:bg-teal-700">
            Return to Homepage
          </a>
        </div>
      </div>
    </div>
  <?php else: ?>

    <!-- Hero Section with Search Form -->
    <!-- Hero Section with Search Form -->
<section class="hero-section py-20 text-white">
  <div class="container mx-auto px-4">
    <div class="max-w-4xl mx-auto text-center mb-8">
      <h1 class="text-4xl font-bold mb-2">Find Flights for Your Sacred Journey</h1>
      <p class="text-xl">Compare prices and book flights for Umrah and Hajj</p>
    </div>

    <div class="search-box max-w-4xl mx-auto p-6">
      <form action="" method="POST" class="space-y-6">
        <div class="flex space-x-4 text-gray-700 mb-4">
          <label class="inline-flex items-center">
            <input type="radio" name="trip_type" value="one_way" class="mr-2" checked>
            <span>One Way</span>
          </label>
          <label class="inline-flex items-center">
            <input type="radio" name="trip_type" value="round_trip" class="mr-2">
            <span>Round Trip</span>
          </label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-gray-700 text-sm font-semibold mb-2">From</label>
            <div class="relative">
              <input type="text" id="departure_city_search" class="w-full px-4 py-2 border rounded focus:border-teal-500 focus:outline-none text-black" placeholder="Search city..." autocomplete="off">
              <input type="hidden" name="departure_city" id="departure_city_hidden">
              <div id="departure_city_results" class="absolute z-10 bg-white border border-gray-300 w-full rounded-b-lg shadow-lg hidden"></div>
            </div>
          </div>
          <div>
            <label class="block text-gray-700 text-sm font-semibold mb-2">To</label>
            <div class="relative">
              <input type="text" id="arrival_city_search" class="w-full px-4 py-2 border rounded focus:border-teal-500 focus:outline-none text-black" placeholder="Search city..." autocomplete="off">
              <input type="hidden" name="arrival_city" id="arrival_city_hidden">
              <div id="arrival_city_results" class="absolute z-10 bg-white border border-gray-300 w-full rounded-b-lg shadow-lg hidden"></div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <style>
              input{
                color: black !important;
              }
              select{
                color: black!important;
              }
              .search-result-item {
                padding: 10px 15px;
                cursor: pointer;
                transition: background-color 0.2s;
              }
              .search-result-item:hover {
                background-color: #f3f4f6;
              }
            </style>
            <label class="block text-gray-700 text-sm font-semibold mb-2">Departure Date</label>
            <input type="date" name="departure_date" value="<?php echo $departure_date; ?>" class="w-full px-4 py-2 border rounded focus:border-teal-500 focus:outline-none" required>
          </div>
          <div>
            <label class="block text-gray-700 text-sm font-semibold mb-2">Travelers</label>
            <div class="relative">
              <input type="text" id="travelers_search" class="w-full px-4 py-2 border rounded focus:border-teal-500 focus:outline-none text-black" placeholder="Number of travelers..." value="1" autocomplete="off">
              <input type="hidden" name="travelers" id="travelers_hidden" value="1">
              <div id="travelers_results" class="absolute z-10 bg-white border border-gray-300 w-full rounded-b-lg shadow-lg hidden"></div>
            </div>
          </div>
          <div>
            <label class="block text-gray-700 text-sm font-semibold mb-2">Cabin Class</label>
            <div class="relative">
              <input type="text" id="cabin_class_search" class="w-full px-4 py-2 border rounded focus:border-teal-500 focus:outline-none text-black" placeholder="Select cabin class..." value="Economy" autocomplete="off">
              <input type="hidden" name="cabin_class" id="cabin_class_hidden" value="Economy">
              <div id="cabin_class_results" class="absolute z-10 bg-white border border-gray-300 w-full rounded-b-lg shadow-lg hidden"></div>
            </div>
          </div>
        </div>

        <div class="text-center">
          <button type="submit" name="search_flights" class="bg-teal-600 text-black px-8 py-3 rounded-lg text-lg hover:bg-teal-700 transition duration-300">
            <i class="fas fa-search mr-2"></i> Search Flights
          </button>
        </div>
      </form>
    </div>
  </div>
</section>

<!-- Add this just before the closing body tag -->
<script>
  // Define available options for dropdowns
  const departureOptions = [
    { id: 'Karachi', name: 'Karachi' },
    { id: 'Lahore', name: 'Lahore' },
    { id: 'Islamabad', name: 'Islamabad' }
  ];
  
  const arrivalOptions = [
    { id: 'Jeddah', name: 'Jeddah' },
    { id: 'Medina', name: 'Medina' }
  ];

  const travelersOptions = Array.from({length: 10}, (_, i) => {
    const num = i + 1;
    return { 
      id: num, 
      name: `${num} Traveler${num > 1 ? 's' : ''}` 
    };
  });

  const cabinClassOptions = [
    { id: 'Economy', name: 'Economy' },
    { id: 'Business', name: 'Business' },
    { id: 'First Class', name: 'First Class' }
  ];

  // Autocomplete function for all search inputs
  function setupAutocomplete(inputId, hiddenInputId, resultsId, options, defaultValue = '') {
    const inputField = document.getElementById(inputId);
    const hiddenInput = document.getElementById(hiddenInputId);
    const resultsDiv = document.getElementById(resultsId);
    
    // Set default value if provided
    if (defaultValue) {
      inputField.value = defaultValue;
      hiddenInput.value = defaultValue;
    }

    // Show dropdown when input is focused
    inputField.addEventListener('focus', () => {
      // Filter options based on current input
      const query = inputField.value.toLowerCase();
      const filteredOptions = options.filter(option => 
        option.name.toLowerCase().includes(query)
      );
      
      // Only show dropdown if we have results or input is empty
      if (filteredOptions.length > 0 || inputField.value === '') {
        displayResults(filteredOptions);
        resultsDiv.classList.remove('hidden');
      }
    });

    // Handle input changes for filtering
    inputField.addEventListener('input', () => {
      const query = inputField.value.toLowerCase();
      const filteredOptions = options.filter(option => 
        option.name.toLowerCase().includes(query)
      );
      
      displayResults(filteredOptions);
      
      if (filteredOptions.length > 0) {
        resultsDiv.classList.remove('hidden');
      } else {
        resultsDiv.classList.add('hidden');
      }
    });

    // Hide dropdown when clicking outside
    document.addEventListener('click', (e) => {
      if (e.target !== inputField && e.target !== resultsDiv) {
        resultsDiv.classList.add('hidden');
      }
    });

    // Display filtered results
    function displayResults(filteredOptions) {
      resultsDiv.innerHTML = '';
      
      filteredOptions.forEach(option => {
        const resultItem = document.createElement('div');
        resultItem.className = 'search-result-item text-black';
        resultItem.textContent = option.name;
        resultItem.addEventListener('click', () => {
          inputField.value = option.name;
          hiddenInput.value = option.id;
          resultsDiv.classList.add('hidden');
        });
        
        resultsDiv.appendChild(resultItem);
      });
    }
  }

  // Set up autocomplete for all search inputs
  document.addEventListener('DOMContentLoaded', () => {
    setupAutocomplete('departure_city_search', 'departure_city_hidden', 'departure_city_results', departureOptions);
    setupAutocomplete('arrival_city_search', 'arrival_city_hidden', 'arrival_city_results', arrivalOptions);
    setupAutocomplete('travelers_search', 'travelers_hidden', 'travelers_results', travelersOptions, '1 Traveler');
    setupAutocomplete('cabin_class_search', 'cabin_class_hidden', 'cabin_class_results', cabinClassOptions, 'Economy');
  });
</script>

    <!-- Search Results Section -->
    <?php if ($is_searching): ?>
      <section class="py-12 bg-gray-100">
        <div class="container mx-auto px-4">
          <h2 class="text-2xl font-bold mb-6">
            <?php if (empty($search_results)): ?>
              <div class="text-center py-8">
                <i class="fas fa-plane-slash text-gray-400 text-5xl mb-4"></i>
                <p class="text-xl text-gray-600">No flights found for your search criteria.</p>
                <p class="text-gray-500 mt-2">Please try different dates or destinations.</p>
              </div>
            <?php else: ?>
              <div class="flex justify-between items-center">
                <span>
                  <i class="fas fa-plane-departure text-teal-600 mr-2"></i>
                  Flights from <?php echo htmlspecialchars($departure_city); ?> to <?php echo htmlspecialchars($arrival_city); ?>
                </span>
                <span class="text-sm text-gray-600">
                  <?php echo count($search_results); ?> flights found • <?php echo date('D, M j, Y', strtotime($departure_date)); ?>
                </span>
              </div>
            <?php endif; ?>
          </h2>

          <div class="space-y-6">
            <?php foreach ($search_results as $flight): ?>
              <?php
              // Parse JSON data
              $prices = json_decode($flight['prices'], true);
              $seats = json_decode($flight['seats'], true);
              $cabin_classes = json_decode($flight['cabin_class'], true);

              // Get price based on selected cabin class
              $price_key = strtolower(str_replace(' ', '_', $cabin_class));
              $price = $prices[$price_key] ?? 0;
              $total_price = $price * $travelers;

              // Handle the stops data
              $stops_data = !empty($flight['stops']) ? json_decode($flight['stops'], true) : null;
              $is_direct = false;

              if ($stops_data === "direct" || $stops_data === null) {
                $is_direct = true;
                $stops = [];
              } elseif (is_array($stops_data)) {
                $stops = $stops_data;
              } else {
                $stops = [];
              }

              // Calculate flight duration (dummy calculation for demonstration)
              $flight_duration = "5h 30m"; // In a real app, calculate based on departure/arrival times
              ?>

              <div class="flight-card bg-white rounded-lg shadow-md overflow-hidden transition-all duration-300">
                <div class="p-6">
                  <div class="flex flex-col md:flex-row md:justify-between md:items-center">
                    <!-- Airline Info -->
                    <div class="flex items-center mb-4 md:mb-0">
                      <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-plane text-2xl text-teal-600"></i>
                      </div>
                      <div>
                        <h3 class="font-bold text-lg"><?php echo htmlspecialchars($flight['airline_name']); ?></h3>
                        <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($flight['flight_number']); ?></p>
                      </div>
                    </div>

                    <!-- Flight Details -->
                    <div class="flex-grow md:px-6">
                      <div class="flex flex-col md:flex-row items-center justify-between">
                        <!-- Departure -->
                        <div class="text-center mb-3 md:mb-0">
                          <p class="text-2xl font-bold"><?php echo date('h:i A', strtotime($flight['departure_time'])); ?></p>
                          <p class="text-gray-600"><?php echo htmlspecialchars($flight['departure_city']); ?></p>
                        </div>

                        <!-- Flight Path -->
                        <div class="flex flex-col items-center px-6 mb-3 md:mb-0">
                          <div class="text-gray-500 text-sm mb-1"><?php echo $flight_duration; ?></div>
                          <div class="flex items-center">
                            <div class="w-2 h-2 rounded-full bg-gray-400"></div>
                            <div class="w-32 h-0.5 bg-gray-300"></div>
                            <?php if (!$is_direct): ?>
                              <?php foreach ($stops as $index => $stop): ?>
                                <div class="w-2 h-2 rounded-full bg-orange-400"></div>
                                <div class="w-32 h-0.5 bg-gray-300"></div>
                              <?php endforeach; ?>
                            <?php endif; ?>
                            <div class="w-2 h-2 rounded-full bg-gray-400"></div>
                          </div>
                          <div class="text-orange-500 text-xs mt-1">
                            <?php if ($is_direct): ?>
                              <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded-full">Direct Flight</span>
                            <?php else: ?>
                              <span class="bg-orange-100 text-orange-800 px-2 py-0.5 rounded-full">
                                <?php echo count($stops); ?> stop<?php echo (count($stops) > 1) ? 's' : ''; ?>
                              </span>
                            <?php endif; ?>
                          </div>
                        </div>

                        <!-- Arrival -->
                        <div class="text-center">
                          <p class="text-2xl font-bold">
                            <!-- In real app, calculate arrival time based on departure + duration -->
                            <?php
                            $arrival_time = date('h:i A', strtotime('+5 hours 30 minutes', strtotime($flight['departure_time'])));
                            echo $arrival_time;
                            ?>
                          </p>
                          <p class="text-gray-600"><?php echo htmlspecialchars($flight['arrival_city']); ?></p>
                        </div>
                      </div>
                    </div>

                    <!-- Price and Book Button -->
                    <div class="text-center md:text-right mt-4 md:mt-0">
                      <p class="text-gray-500 text-sm"><?php echo $cabin_class; ?> Class</p>
                      <p class="text-3xl font-bold text-teal-600">$<?php echo number_format($price, 2); ?></p>
                      <p class="text-gray-500 text-sm">per person</p>
                      <button onclick="showBookingModal('<?php echo $flight['id']; ?>', '<?php echo htmlspecialchars($flight['airline_name']); ?>', '<?php echo htmlspecialchars($flight['flight_number']); ?>', '<?php echo $departure_date; ?>', '<?php echo $flight['departure_time']; ?>', '<?php echo htmlspecialchars($flight['departure_city']); ?>', '<?php echo htmlspecialchars($flight['arrival_city']); ?>', '<?php echo $cabin_class; ?>', '<?php echo $price; ?>', '<?php echo $travelers; ?>')" class="mt-2 bg-teal-600 text-white px-4 py-2 rounded hover:bg-teal-700 transition duration-300">
                        Book Now
                      </button>
                    </div>
                  </div>

                  <!-- Flight Details Expandable Section -->
                  <div class="mt-4 pt-4 border-t border-gray-200">
                    <button class="text-teal-600 hover:text-teal-800 text-sm flex items-center toggle-details" data-target="details-<?php echo $flight['id']; ?>">
                      <i class="fas fa-chevron-down mr-1"></i> Flight Details
                    </button>
                    <div id="details-<?php echo $flight['id']; ?>" class="hidden mt-4">
                      <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold mb-2">Flight Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                          <div>
                            <p class="text-sm text-gray-600">Aircraft</p>
                            <p class="font-medium">Boeing 777</p>
                          </div>
                          <div>
                            <p class="text-sm text-gray-600">Flight Duration</p>
                            <p class="font-medium"><?php echo $flight_duration; ?></p>
                          </div>
                          <div>
                            <p class="text-sm text-gray-600">Distance</p>
                            <p class="font-medium">3,500 km</p>
                          </div>
                        </div>

                        <h4 class="font-semibold mt-4 mb-2">Baggage Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                          <div>
                            <p class="text-sm text-gray-600">Cabin Baggage</p>
                            <p class="font-medium">7 kg</p>
                          </div>
                          <div>
                            <p class="text-sm text-gray-600">Check-in Baggage</p>
                            <p class="font-medium">30 kg</p>
                          </div>
                          <div>
                            <p class="text-sm text-gray-600">Zamzam Water</p>
                            <p class="font-medium">Allowed (5L)</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <!-- Booking Modal (Hidden by default) -->
    <div id="booking-modal" class="overflow-y-scroll fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
      <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full mx-4 overflow-hidden">
        <div class="bg-teal-600 text-white py-4 px-6">
          <div class="flex justify-between items-center">
            <br><br><br>
            <h3 class="text-xl font-bold">Complete Your Booking</h3>
            <button id="close-modal" class="text-white hover:text-gray-200">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>

        <div class="p-6 " >
          <div class="mb-6 bg-gray-50 p-4 rounded-lg">
            <h4 class="font-semibold mb-2">Flight Details</h4>
            <div class="grid grid-cols-2 gap-2 text-sm">
              <div>
                <span class="text-gray-600">Airline:</span>
                <span id="modal-airline" class="font-medium ml-1"></span>
              </div>
              <div>
                <span class="text-gray-600">Flight Number:</span>
                <span id="modal-flight-number" class="font-medium ml-1"></span>
              </div>
              <div>
                <span class="text-gray-600">From:</span>
                <span id="modal-departure" class="font-medium ml-1"></span>
              </div>
              <div>
                <span class="text-gray-600">To:</span>
                <span id="modal-arrival" class="font-medium ml-1"></span>
              </div>
              <div>
                <span class="text-gray-600">Date:</span>
                <span id="modal-date" class="font-medium ml-1"></span>
              </div>
              <div>
                <span class="text-gray-600">Time:</span>
                <span id="modal-time" class="font-medium ml-1"></span>
              </div>
              <div>
                <span class="text-gray-600">Class:</span>
                <span id="modal-class" class="font-medium ml-1"></span>
              </div>
              <div>
                <span class="text-gray-600">Passengers:</span>
                <span id="modal-travelers" class="font-medium ml-1"></span>
              </div>
            </div>
          </div>

          <form action="" method="POST">
            <input type="hidden" id="flight_id" name="flight_id">
            <input type="hidden" id="selected_class" name="selected_class">
            <input type="hidden" id="num_travelers" name="num_travelers">

            <div class="mb-6">
              <h4 class="font-semibold mb-4">Passenger Information</h4>
              <div class="space-y-4">
                <div>
                  <label class="block text-gray-700 text-sm font-medium mb-2">Full Name</label>
                  <input type="text" name="passenger_name" class="w-full px-4 py-2 border rounded focus:border-teal-500 focus:outline-none" required>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Email Address</label>
                    <input type="email" name="passenger_email" class="w-full px-4 py-2 border rounded focus:border-teal-500 focus:outline-none" required>
                  </div>
                  <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Phone Number</label>
                    <input type="tel" name="passenger_phone" class="w-full px-4 py-2 border rounded focus:border-teal-500 focus:outline-none" required>
                  </div>
                </div>
              </div>
            </div>

            <div class="mb-6">
              <h4 class="font-semibold mb-4">Payment Summary</h4>
              <div class="bg-gray-50 p-4 rounded-lg">
                <div class="flex justify-between items-center mb-2">
                  <span>Fare (<span id="modal-price-travelers"></span>)</span>
                  <span id="modal-subtotal" class="font-medium"></span>
                </div>
                <div class="flex justify-between items-center mb-2">
                  <span>Taxes & Fees</span>
                  <span id="modal-taxes" class="font-medium"></span>
                </div>
                <div class="border-t border-gray-300 my-2"></div>
                <div class="flex justify-between items-center text-lg font-bold">
                  <span>Total</span>
                  <span id="modal-total" class="text-teal-600"></span>
                </div>
              </div>
            </div>

            <div class="mb-6">
              <h4 class="font-semibold mb-4">Payment Method</h4>
              <div class="space-y-3">
                <label class="block">
                  <input type="radio" name="payment_method" value="credit_card" class="mr-2" checked>
                  <span>Credit/Debit Card</span>
                </label>

                <div class="border rounded p-4">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                      <label class="block text-gray-700 text-sm font-medium mb-2">Card Number</label>
                      <input type="text" name="card_number" placeholder="1234 5678 9012 3456" class="w-full px-4 py-2 border rounded focus:border-teal-500 focus:outline-none">
                    </div>
                    <div>
                      <label class="block text-gray-700 text-sm font-medium mb-2">Cardholder Name</label>
                      <input type="text" name="card_name" placeholder="John Smith" class="w-full px-4 py-2 border rounded focus:border-teal-500 focus:outline-none">
                    </div>
                  </div>
                  <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div>
                      <label class="block text-gray-700 text-sm font-medium mb-2">Expiry Month</label>
                      <select name="exp_month" class="w-full px-4 py-2 border rounded focus:border-teal-500 focus:outline-none">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                          <option value="<?php echo sprintf('%02d', $i); ?>"><?php echo sprintf('%02d', $i); ?></option>
                        <?php endfor; ?>
                      </select>
                    </div>
                    <div>
                      <label class="block text-gray-700 text-sm font-medium mb-2">Expiry Year</label>
                      <select name="exp_year" class="w-full px-4 py-2 border rounded focus:border-teal-500 focus:outline-none">
                        <?php for ($i = date('Y'); $i <= date('Y') + 10; $i++): ?>
                          <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                      </select>
                    </div>
                    <div>
                      <label class="block text-gray-700 text-sm font-medium mb-2">CVV</label>
                      <input type="text" name="cvv" placeholder="123" class="w-full px-4 py-2 border rounded focus:border-teal-500 focus:outline-none">
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex items-center mb-6">
              <input type="checkbox" name="terms" id="terms" class="mr-2" required>
              <label for="terms" class="text-sm text-gray-700">
                I agree to the <a href="#" class="text-teal-600 hover:underline">Terms and Conditions</a> and <a href="#" class="text-teal-600 hover:underline">Privacy Policy</a>
              </label>
            </div>

            <div class="text-center">
              <button type="submit" name="book_flight" class="bg-teal-600 text-white px-6 py-3 rounded-lg text-lg hover:bg-teal-700 transition duration-300 w-full md:w-auto">
                <i class="fas fa-check-circle mr-2"></i> Confirm and Pay
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Popular Destinations Section -->
  <section class="py-16 bg-white">
    <div class="container mx-auto px-4">
      <h2 class="text-3xl font-bold text-center mb-12">Popular Destinations for Umrah</h2>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="bg-gray-100 rounded-lg overflow-hidden shadow-md hover:shadow-lg transition duration-300">
          <img src="assets/images/featured/meccah.webp" alt="Mecca" class="w-full h-48 object-cover">
          <div class="p-6">
            <h3 class="text-xl font-bold mb-2">Mecca</h3>
            <p class="text-gray-600 mb-4">The holiest city in Islam and the birthplace of Prophet Muhammad.</p>
            <p class="text-teal-600 font-bold text-lg mb-2">Flights from $499</p>
            <a href="#" class="text-teal-600 hover:text-teal-800 inline-flex items-center">
              Explore Flights <i class="fas fa-arrow-right ml-2"></i>
            </a>
          </div>
        </div>

        <div class="bg-gray-100 rounded-lg overflow-hidden shadow-md hover:shadow-lg transition duration-300">
          <img src="assets/images/featured/medinah.webp" alt="Medina" class="w-full h-48 object-cover">
          <div class="p-6">
            <h3 class="text-xl font-bold mb-2">Medina</h3>
            <p class="text-gray-600 mb-4">The second holiest city in Islam, home to the Prophet's Mosque.</p>
            <p class="text-teal-600 font-bold text-lg mb-2">Flights from $529</p>
            <a href="#" class="text-teal-600 hover:text-teal-800 inline-flex items-center">
              Explore Flights <i class="fas fa-arrow-right ml-2"></i>
            </a>
          </div>
        </div>

        <div class="bg-gray-100 rounded-lg overflow-hidden shadow-md hover:shadow-lg transition duration-300">
          <img src="assets/images/featured/jeddah.webp" alt="Jeddah" class="w-full h-48 object-cover">
          <div class="p-6">
            <h3 class="text-xl font-bold mb-2">Jeddah</h3>
            <p class="text-gray-600 mb-4">The gateway to Mecca and a major commercial hub in Saudi Arabia.</p>
            <p class="text-teal-600 font-bold text-lg mb-2">Flights from $479</p>
            <a href="#" class="text-teal-600 hover:text-teal-800 inline-flex items-center">
              Explore Flights <i class="fas fa-arrow-right ml-2"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Special Offers Section -->
  <section class="py-16 bg-gray-100">
    <div class="container mx-auto px-4">
      <h2 class="text-3xl font-bold text-center mb-12">Special Offers</h2>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="bg-white rounded-lg overflow-hidden shadow-md flex flex-col md:flex-row">
          <div class="md:w-2/5 bg-teal-600 text-white p-6 flex flex-col justify-center">
            <h3 class="text-xl font-bold mb-2 text-black">Ramadan Special</h3>
            <p class="mb-4 text-black">Exclusive discounts for Umrah during the blessed month of Ramadan.</p>
            <p class="text-2xl font-bold text-black">Save up to 20%</p>
          </div>
          <div class="md:w-3/5 p-6">
            <div class="mb-4">
              <div class="flex items-center mb-2">
                <i class="fas fa-check-circle text-teal-600 mr-2"></i>
                <span>Direct flights to Jeddah & Medina</span>
              </div>
              <div class="flex items-center mb-2">
                <i class="fas fa-check-circle text-teal-600 mr-2"></i>
                <span>Flexible booking options</span>
              </div>
              <div class="flex items-center mb-2">
                <i class="fas fa-check-circle text-teal-600 mr-2"></i>
                <span>Special Ramadan meals onboard</span>
              </div>
            </div>
            <a href="#" class="text-black inline-block bg-teal-600 px-4 py-2 rounded hover:bg-teal-700 transition duration-300">
              View Offer
            </a>
          </div>
        </div>

        <div class="bg-white rounded-lg overflow-hidden shadow-md flex flex-col md:flex-row">
          <div class="md:w-2/5 bg-blue-600 text-white p-6 flex flex-col justify-center">
            <h3 class="text-xl font-bold mb-2">Family Package</h3>
            <p class="mb-4">Special rates for families traveling together for Umrah.</p>
            <p class="text-2xl font-bold">Extra baggage allowance</p>
          </div>
          <div class="md:w-3/5 p-6">
            <div class="mb-4">
              <div class="flex items-center mb-2">
                <i class="fas fa-check-circle text-blue-600 mr-2"></i>
                <span>Group discounts available</span>
              </div>
              <div class="flex items-center mb-2">
                <i class="fas fa-check-circle text-blue-600 mr-2"></i>
                <span>Family-friendly seating arrangements</span>
              </div>
              <div class="flex items-center mb-2">
                <i class="fas fa-check-circle text-blue-600 mr-2"></i>
                <span>Priority boarding for families</span>
              </div>
            </div>
            <a href="#" class="inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition duration-300">
              View Offer
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Testimonials Section -->
  <section class="py-16 bg-white">
    <div class="container mx-auto px-4">
      <h2 class="text-3xl font-bold text-center mb-12">What Our Pilgrims Say</h2>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="bg-gray-50 p-6 rounded-lg shadow-md">
          <div class="flex items-center mb-4">
            <div class="text-yellow-400 flex">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
            </div>
            <span class="ml-2 text-gray-600">5.0</span>
          </div>
          <p class="text-gray-700 mb-4">"I had a wonderful experience booking my Umrah flight through UmrahFlights. The process was smooth, and their customer service was excellent. Will definitely use their service again!"</p>
          <div class="flex items-center">
            <div class="w-12 h-12 bg-gray-300 rounded-full mr-4"></div>
            <div>
              <h4 class="font-bold">Ahmed Khan</h4>
              <p class="text-gray-600 text-sm">Karachi, Pakistan</p>
            </div>
          </div>
        </div>

        <div class="bg-gray-50 p-6 rounded-lg shadow-md">
          <div class="flex items-center mb-4">
            <div class="text-yellow-400 flex">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star-half-alt"></i>
            </div>
            <span class="ml-2 text-gray-600">4.5</span>
          </div>
          <p class="text-gray-700 mb-4">"The prices were very competitive, and I appreciated the clear information about baggage allowances for Zamzam water. The flight was comfortable and on-time."</p>
          <div class="flex items-center">
            <div class="w-12 h-12 bg-gray-300 rounded-full mr-4"></div>
            <div>
              <h4 class="font-bold">Fatima Ali</h4>
              <p class="text-gray-600 text-sm">Lahore, Pakistan</p>
            </div>
          </div>
        </div>

        <div class="bg-gray-50 p-6 rounded-lg shadow-md">
          <div class="flex items-center mb-4">
            <div class="text-yellow-400 flex">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
            </div>
            <span class="ml-2 text-gray-600">5.0</span>
          </div>
          <p class="text-gray-700 mb-4">"As a first-time Umrah pilgrim, I was nervous about the arrangements, but UmrahFlights made everything easy. Their support team was very helpful in answering all my questions."</p>
          <div class="flex items-center">
            <div class="w-12 h-12 bg-gray-300 rounded-full mr-4"></div>
            <div>
              <h4 class="font-bold">Muhammad Rashid</h4>
              <p class="text-gray-600 text-sm">Islamabad, Pakistan</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="py-16 bg-teal-600 text-black">
    <div class="container mx-auto px-4 text-center">
      <h2 class="text-3xl font-bold mb-4">Ready to Plan Your Sacred Journey?</h2>
      <p class="text-xl mb-8 max-w-3xl mx-auto">Let us help you find the best flights for your Umrah pilgrimage. Our dedicated team is here to ensure a smooth travel experience.</p>
      <div class="flex flex-col md:flex-row justify-center space-y-4 md:space-y-0 md:space-x-4">
        <a href="#" class="bg-white text-blac-600 px-6 py-3 rounded-lg font-bold hover:text-white hover:bg-black transition duration-300">
          Search Flights
        </a>
        <a href="#" class="bg-black border-2 border-white text-white px-6 py-3 rounded-lg font-bold hover:bg-white hover:text-red-600 transition duration-300">
          Contact Support
        </a>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-gray-800 text-white py-12">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        <div>
          <h3 class="text-xl font-bold mb-4">UmrahFlights</h3>
          <p class="text-gray-400 mb-4">Making your journey to the Holy Land easier and more comfortable.</p>
          <div class="flex space-x-4">
            <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter"></i></a>
            <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram"></i></a>
            <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-youtube"></i></a>
          </div>
        </div>

        <div>
          <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
          <ul class="space-y-2">
            <li><a href="#" class="text-gray-400 hover:text-white">Home</a></li>
            <li><a href="#" class="text-gray-400 hover:text-white">Flights</a></li>
            <li><a href="#" class="text-gray-400 hover:text-white">Packages</a></li>
            <li><a href="#" class="text-gray-400 hover:text-white">About Us</a></li>
            <li><a href="#" class="text-gray-400 hover:text-white">Contact</a></li>
          </ul>
        </div>

        <div>
          <h4 class="text-lg font-semibold mb-4">Support</h4>
          <ul class="space-y-2">
            <li><a href="#" class="text-gray-400 hover:text-white">FAQ</a></li>
            <li><a href="#" class="text-gray-400 hover:text-white">Baggage Information</a></li>
            <li><a href="#" class="text-gray-400 hover:text-white">Visa Requirements</a></li>
            <li><a href="#" class="text-gray-400 hover:text-white">Terms & Conditions</a></li>
            <li><a href="#" class="text-gray-400 hover:text-white">Privacy Policy</a></li>
          </ul>
        </div>

        <div>
          <h4 class="text-lg font-semibold mb-4">Contact Us</h4>
          <ul class="space-y-2 text-gray-400">
            <li class="flex items-start">
              <i class="fas fa-map-marker-alt mt-1 mr-2"></i>
              <span>123 Business Avenue, Karachi, Pakistan</span>
            </li>
            <li class="flex items-start">
              <i class="fas fa-phone-alt mt-1 mr-2"></i>
              <span>+92 300 1234567</span>
            </li>
            <li class="flex items-start">
              <i class="fas fa-envelope mt-1 mr-2"></i>
              <span>info@umrahflights.com</span>
            </li>
          </ul>
        </div>
      </div>

      <div class="border-t border-gray-700 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center">
        <p class="text-gray-400 mb-4 md:mb-0">© 2023 UmrahFlights. All rights reserved.</p>
        <div class="flex space-x-4">
          <img src="images/visa.png" alt="Visa" class="h-8">
          <img src="images/mastercard.png" alt="MasterCard" class="h-8">
          <img src="images/amex.png" alt="American Express" class="h-8">
          <img src="images/paypal.png" alt="PayPal" class="h-8">
        </div>
      </div>
    </div>
  </footer>

  <!-- JavaScript for toggling flight details and handling the booking modal -->
  <script>
    // Toggle flight details
    document.querySelectorAll('.toggle-details').forEach(button => {
      button.addEventListener('click', () => {
        const targetId = button.getAttribute('data-target');
        const targetElement = document.getElementById(targetId);

        if (targetElement.classList.contains('hidden')) {
          targetElement.classList.remove('hidden');
          button.innerHTML = '<i class="fas fa-chevron-up mr-1"></i> Hide Details';
        } else {
          targetElement.classList.add('hidden');
          button.innerHTML = '<i class="fas fa-chevron-down mr-1"></i> Flight Details';
        }
      });
    });

    // Booking modal functionality
    const modal = document.getElementById('booking-modal');
    const closeModalBtn = document.getElementById('close-modal');

    if (closeModalBtn) {
      closeModalBtn.addEventListener('click', () => {
        modal.classList.add('hidden');
      });
    }

    function showBookingModal(flightId, airline, flightNumber, date, time, departure, arrival, cabinClass, price, travelers) {
      // Fill in the modal with flight details
      document.getElementById('flight_id').value = flightId;
      document.getElementById('selected_class').value = cabinClass;
      document.getElementById('num_travelers').value = travelers;

      document.getElementById('modal-airline').textContent = airline;
      document.getElementById('modal-flight-number').textContent = flightNumber;
      document.getElementById('modal-departure').textContent = departure;
      document.getElementById('modal-arrival').textContent = arrival;
      document.getElementById('modal-date').textContent = new Date(date).toLocaleDateString('en-US', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
      document.getElementById('modal-time').textContent = time;
      document.getElementById('modal-class').textContent = cabinClass;
      document.getElementById('modal-travelers').textContent = travelers + (travelers > 1 ? ' travelers' : ' traveler');

      // Calculate pricing
      const subtotal = parseFloat(price) * parseInt(travelers);
      const taxes = subtotal * 0.1; // Example: 10% taxes and fees
      const total = subtotal + taxes;

      document.getElementById('modal-price-travelers').textContent = travelers + ' x $' + parseFloat(price).toFixed(2);
      document.getElementById('modal-subtotal').textContent = '$' + subtotal.toFixed(2);
      document.getElementById('modal-taxes').textContent = '$' + taxes.toFixed(2);
      document.getElementById('modal-total').textContent = '$' + total.toFixed(2);

      // Show the modal
      modal.classList.remove('hidden');
    }

    // Close modal when clicking outside the modal content
    window.addEventListener('click', (event) => {
      if (event.target === modal) {
        modal.classList.add('hidden');
      }
    });
  </script>
  <script>
    // Toggle flight details
document.querySelectorAll('.toggle-details').forEach(button => {
  button.addEventListener('click', () => {
    const targetId = button.getAttribute('data-target');
    const targetElement = document.getElementById(targetId);

    if (targetElement.classList.contains('hidden')) {
      targetElement.classList.remove('hidden');
      button.innerHTML = '<i class="fas fa-chevron-up mr-1"></i> Hide Details';
    } else {
      targetElement.classList.add('hidden');
      button.innerHTML = '<i class="fas fa-chevron-down mr-1"></i> Flight Details';
    }
  });
});

// Booking modal functionality
const modal = document.getElementById('booking-modal');
const closeModalBtn = document.getElementById('close-modal');

if (closeModalBtn) {
  closeModalBtn.addEventListener('click', () => {
    modal.classList.add('hidden');
  });
}

function showBookingModal(flightId, airline, flightNumber, date, time, departure, arrival, cabinClass, price, travelers) {
  // Fill in the modal with flight details
  document.getElementById('flight_id').value = flightId;
  document.getElementById('selected_class').value = cabinClass;
  document.getElementById('num_travelers').value = travelers;

  document.getElementById('modal-airline').textContent = airline;
  document.getElementById('modal-flight-number').textContent = flightNumber;
  document.getElementById('modal-departure').textContent = departure;
  document.getElementById('modal-arrival').textContent = arrival;
  document.getElementById('modal-date').textContent = new Date(date).toLocaleDateString('en-US', {
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
  document.getElementById('modal-time').textContent = time;
  document.getElementById('modal-class').textContent = cabinClass;
  document.getElementById('modal-travelers').textContent = travelers + (travelers > 1 ? ' travelers' : ' traveler');

  // Calculate pricing
  const subtotal = parseFloat(price) * parseInt(travelers);
  const taxes = subtotal * 0.1; // Example: 10% taxes and fees
  const total = subtotal + taxes;

  document.getElementById('modal-price-travelers').textContent = travelers + ' x $' + parseFloat(price).toFixed(2);
  document.getElementById('modal-subtotal').textContent = '$' + subtotal.toFixed(2);
  document.getElementById('modal-taxes').textContent = '$' + taxes.toFixed(2);
  document.getElementById('modal-total').textContent = '$' + total.toFixed(2);

  // Show the modal
  modal.classList.remove('hidden');
}

// Close modal when clicking outside the modal content
window.addEventListener('click', (event) => {
  if (event.target === modal) {
    modal.classList.add('hidden');
  }
});
  </script>
</body>

</html>