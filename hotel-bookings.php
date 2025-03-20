<?php
$host = 'localhost';
$dbname = 'ummrah';
$username = 'root';
$password = '';

try {
  $pdo = new PDO(
    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
    $username,
    $password,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false
    ]
  );
} catch (PDOException $e) {
  die("Connection failed: " . $e->getMessage());
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Check if user is logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
$user_phone = isset($_SESSION['user_phone']) ? $_SESSION['user_phone'] : '';

// Get hotel ID from query string
$hotel_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no hotel ID provided, redirect to hotels listing
if ($hotel_id === 0) {
  header('Location: view-hotel-bookings.php');
  exit;
}

// Fetch hotel details
try {
  $stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = :id");
  $stmt->execute(['id' => $hotel_id]);
  $hotel = $stmt->fetch();

  if (!$hotel) {
    // Hotel not found
    header('Location: view-hotel-bookings.php?error=hotel_not_found');
    exit;
  }

  // Decode JSON data
  $hotel['amenities'] = json_decode($hotel['amenities'], true) ?: [];
  $hotel['room_ids'] = json_decode($hotel['room_ids'], true) ?: [];

  // Fetch hotel images
  $stmt = $pdo->prepare("SELECT image_path FROM hotel_images WHERE hotel_id = :hotel_id");
  $stmt->execute(['hotel_id' => $hotel_id]);
  $hotel_images = $stmt->fetchAll(PDO::FETCH_COLUMN);

  // Default date range (today to tomorrow)
  $check_in = isset($_GET['check_in']) ? $_GET['check_in'] : date('Y-m-d');
  $check_out = isset($_GET['check_out']) ? $_GET['check_out'] : date('Y-m-d', strtotime('+1 day'));

  // Calculate length of stay
  $check_in_date = new DateTime($check_in);
  $check_out_date = new DateTime($check_out);
  $interval = $check_in_date->diff($check_out_date);
  $days = $interval->days;
  $total_price = $days * $hotel['price_per_night'];

  // Fetch booked rooms for this hotel
  $stmt = $pdo->prepare("
    SELECT room_id FROM hotel_bookings 
    WHERE hotel_id = :hotel_id 
    AND (
      (check_in_date <= :check_out AND check_out_date >= :check_in) 
      OR status = 'confirmed'
    )
  ");

  $stmt->execute([
    'hotel_id' => $hotel_id,
    'check_in' => $check_in,
    'check_out' => $check_out
  ]);

  $booked_rooms = $stmt->fetchAll(PDO::FETCH_COLUMN);

  // Calculate available rooms
  $available_rooms = array_diff($hotel['room_ids'], $booked_rooms);

  // Fetch room details if available
  $room_details = [];
  if (!empty($available_rooms)) {
    $room_types = [
      'standard' => ['description' => 'Comfortable room with basic amenities', 'capacity' => '2 guests'],
      'deluxe' => ['description' => 'Spacious room with premium amenities', 'capacity' => '2-3 guests'],
      'suite' => ['description' => 'Luxury suite with separate living area', 'capacity' => '4 guests'],
    ];

    // Assign room types randomly for demonstration
    foreach ($available_rooms as $room) {
      $type_keys = array_keys($room_types);
      $random_type = $type_keys[array_rand($type_keys)];
      $room_details[$room] = [
        'type' => $random_type,
        'description' => $room_types[$random_type]['description'],
        'capacity' => $room_types[$random_type]['capacity'],
        'image' => !empty($hotel_images) ? $hotel_images[array_rand($hotel_images)] : ''
      ];
    }
  }
} catch (Exception $e) {
  die("Error: " . $e->getMessage());
}

// Handle booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_room'])) {
  $room_id = $_POST['room_id'];
  $guest_name = trim($_POST['guest_name']);
  $guest_email = trim($_POST['guest_email']);
  $guest_phone = trim($_POST['guest_phone']);
  $check_in_date = $_POST['check_in_date'];
  $check_out_date = $_POST['check_out_date'];

  // Validate inputs
  if (empty($guest_name) || empty($guest_email) || empty($guest_phone)) {
    $error_message = "Please fill in all required fields.";
  } else if (!in_array($room_id, $available_rooms)) {
    $error_message = "Selected room is not available.";
  } else if (!$user_id) {
    $error_message = "You must be logged in to book a room.";
  } else {
    try {
      // Insert booking with user_id
      $stmt = $pdo->prepare("
        INSERT INTO hotel_bookings (
            hotel_id, 
            room_id, 
            user_id, 
            guest_name, 
            guest_email, 
            guest_phone, 
            check_in_date, 
            check_out_date, 
            status, 
            created_at
        )
        VALUES (
            :hotel_id, 
            :room_id, 
            :user_id, 
            :guest_name, 
            :guest_email, 
            :guest_phone, 
            :check_in_date, 
            :check_out_date, 
            'confirmed', 
            NOW()
        )
      ");

      $stmt->execute([
        'hotel_id' => $hotel_id,
        'room_id' => $room_id,
        'user_id' => $user_id,
        'guest_name' => $guest_name,
        'guest_email' => $guest_email,
        'guest_phone' => $guest_phone,
        'check_in_date' => $check_in_date,
        'check_out_date' => $check_out_date
      ]);

      // Redirect to confirmation page
      header("Location: hotel-booking-confirmation.php?id=" . $pdo->lastInsertId());
      exit;
    } catch (Exception $e) {
      $error_message = "Booking failed: " . $e->getMessage();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <style>
    .room-card {
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .room-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .image-gallery {
      position: relative;
      height: 300px;
      overflow: hidden;
    }

    .gallery-nav {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(255, 255, 255, 0.7);
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      z-index: 10;
    }

    .gallery-nav.prev {
      left: 10px;
    }

    .gallery-nav.next {
      right: 10px;
    }

    .steps {
      display: flex;
      margin-bottom: 1rem;
    }

    .step {
      flex: 1;
      text-align: center;
      padding: 1rem;
      position: relative;
    }

    .step:not(:last-child):after {
      content: '';
      position: absolute;
      top: 50%;
      right: 0;
      width: 100%;
      height: 2px;
      background: #e2e8f0;
      z-index: 0;
    }

    .step-number {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 30px;
      height: 30px;
      border-radius: 50%;
      background: #e2e8f0;
      color: #4a5568;
      font-weight: bold;
      margin-bottom: 0.5rem;
      position: relative;
      z-index: 1;
    }

    .step.active .step-number {
      background: #38b2ac;
      color: white;
    }
  </style>
</head>

<body class="bg-gray-50">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/navbar.php'; ?>
    <!-- Main Content -->
    <div class="main flex-1 flex flex-col overflow-auto">
      <br><br><br><br>
      <!-- Navbar -->
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <button class="md:hidden text-gray-800" id="menu-btn">
          <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-xl font-semibold">
          <i class="text-teal-600 fas fa-concierge-bell mx-2"></i> Hotel Booking
        </h1>
        <nav class="text-sm breadcrumbs">
          <ul class="flex">
            <li><a href="index.php" class="text-teal-600 hover:underline">Home</a></li>
            <li class="mx-2">/</li>
            <li><a href="view-hotel-bookings.php" class="text-teal-600 hover:underline">Hotels</a></li>
            <li class="mx-2">/</li>
            <li class="text-gray-500"><?php echo htmlspecialchars($hotel['hotel_name']); ?></li>
          </ul>
        </nav>
      </div>

      <!-- Booking Process Steps -->
      <div class="container mx-auto px-4 py-4">
        <div class="steps bg-white rounded-lg shadow p-4">
          <div class="step active">
            <div class="step-number">1</div>
            <div class="step-label">Choose Dates</div>
          </div>
          <div class="step active">
            <div class="step-number">2</div>
            <div class="step-label">Select Room</div>
          </div>
          <div class="step">
            <div class="step-number">3</div>
            <div class="step-label">Complete Booking</div>
          </div>
          <div class="step">
            <div class="step-number">4</div>
            <div class="step-label">Confirmation</div>
          </div>
        </div>
      </div>

      <!-- Hotel Details and Booking Form -->
      <div class="container mx-auto px-4 pb-8">
        <?php if (!$user_id): ?>
          <div class="bg-yellow-50 text-yellow-800 p-4 rounded-lg mb-4 flex items-center shadow-sm">
            <i class="fas fa-exclamation-circle mr-2 text-xl"></i>
            <div>
              <p class="font-semibold">You must be logged in to complete a booking.</p>
              <p>Please <a href="login.php" class="underline font-semibold hover:text-yellow-900">log in here</a> to continue.</p>
            </div>
          </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
          <!-- Hotel Information -->
          <div class="md:flex">
            <div class="md:w-1/2">
              <?php if (!empty($hotel_images)): ?>
                <div class="image-gallery">
                  <?php foreach ($hotel_images as $index => $image): ?>
                    <img src="admin/<?php echo htmlspecialchars($image); ?>"
                      alt="<?php echo htmlspecialchars($hotel['hotel_name']); ?> image <?php echo $index + 1; ?>"
                      class="w-full h-full object-cover gallery-image <?php echo $index > 0 ? 'hidden' : ''; ?>"
                      data-index="<?php echo $index; ?>">
                  <?php endforeach; ?>
                  <?php if (count($hotel_images) > 1): ?>
                    <div class="gallery-nav prev" onclick="changeImage(-1)"><i class="fas fa-chevron-left"></i></div>
                    <div class="gallery-nav next" onclick="changeImage(1)"><i class="fas fa-chevron-right"></i></div>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <div class="bg-gray-200 h-64 md:h-full flex items-center justify-center">
                  <i class="fas fa-hotel text-5xl text-gray-400"></i>
                </div>
              <?php endif; ?>
            </div>
            <div class="md:w-1/2 p-6">
              <div class="flex justify-between items-start">
                <div>
                  <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($hotel['hotel_name']); ?></h2>
                  <div class="flex items-center mt-1">
                    <i class="fas fa-map-marker-alt mr-2 text-teal-600"></i>
                    <p class="text-gray-600 capitalize"><?php echo htmlspecialchars($hotel['location']); ?></p>
                  </div>
                </div>
                <div class="flex items-center bg-teal-50 px-3 py-1 rounded-full">
                  <?php for ($i = 0; $i < $hotel['rating']; $i++): ?>
                    <i class="fas fa-star text-yellow-400"></i>
                  <?php endfor; ?>
                  <?php for ($i = $hotel['rating']; $i < 5; $i++): ?>
                    <i class="far fa-star text-yellow-400"></i>
                  <?php endfor; ?>
                  <span class="ml-1 text-teal-700 font-medium"><?php echo $hotel['rating']; ?>/5</span>
                </div>
              </div>

              <div class="mt-4">
                <h3 class="font-semibold text-gray-800">About This Hotel</h3>
                <p class="text-gray-600 mt-1"><?php echo nl2br(htmlspecialchars($hotel['description'])); ?></p>
              </div>

              <?php if (!empty($hotel['amenities'])): ?>
                <div class="mt-4">
                  <h3 class="font-semibold text-gray-800">Amenities</h3>
                  <div class="flex flex-wrap mt-1">
                    <?php foreach ($hotel['amenities'] as $amenity): ?>
                      <span class="flex items-center bg-teal-100 text-teal-800 px-3 py-1 rounded-full text-sm mr-2 mb-2 capitalize">
                        <?php
                        $icon = 'fa-concierge-bell';
                        if (strpos($amenity, 'wifi') !== false) $icon = 'fa-wifi';
                        elseif (strpos($amenity, 'pool') !== false) $icon = 'fa-swimming-pool';
                        elseif (strpos($amenity, 'breakfast') !== false) $icon = 'fa-coffee';
                        elseif (strpos($amenity, 'parking') !== false) $icon = 'fa-parking';
                        elseif (strpos($amenity, 'gym') !== false) $icon = 'fa-dumbbell';
                        elseif (strpos($amenity, 'spa') !== false) $icon = 'fa-spa';
                        elseif (strpos($amenity, 'air') !== false) $icon = 'fa-snowflake';
                        ?>
                        <i class="fas <?php echo $icon; ?> mr-1"></i>
                        <?php echo htmlspecialchars($amenity); ?>
                      </span>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>

              <div class="mt-4 p-4 bg-teal-50 rounded-lg">
                <div class="flex items-center">
                  <span class="text-3xl font-bold text-teal-600">$<?php echo number_format($hotel['price_per_night'], 2); ?></span>
                  <span class="text-gray-600 ml-2">per night</span>
                </div>
                <div class="flex items-center mt-2">
                  <i class="fas fa-door-open mr-2 text-teal-600"></i>
                  <span><span class="text-teal-600 font-semibold"><?php echo count($available_rooms); ?></span> rooms available for your dates</span>
                </div>
                <?php if ($days > 1): ?>
                  <div class="mt-2 text-sm text-gray-600">
                    <div class="flex justify-between">
                      <span>$<?php echo number_format($hotel['price_per_night'], 2); ?> × <?php echo $days; ?> nights</span>
                      <span>$<?php echo number_format($total_price, 2); ?></span>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Date Selection and Room Availability -->
          <div class="p-6 bg-gray-50 border-t border-gray-200">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Check Availability</h3>
            <form action="" method="GET" class="md:flex items-end space-y-4 md:space-y-0 md:space-x-4">
              <input type="hidden" name="id" value="<?php echo $hotel_id; ?>">
              <div class="flex-1">
                <label class="block text-gray-700 font-medium mb-2">
                  <i class="far fa-calendar-alt mr-1"></i> Check-in Date
                </label>
                <input type="date" name="check_in" id="check_in" value="<?php echo $check_in; ?>"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  min="<?php echo date('Y-m-d'); ?>">
              </div>
              <div class="flex-1">
                <label class="block text-gray-700 font-medium mb-2">
                  <i class="far fa-calendar-alt mr-1"></i> Check-out Date
                </label>
                <input type="date" name="check_out" id="check_out" value="<?php echo $check_out; ?>"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
              </div>
              <div>
                <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700 transition duration-200 w-full md:w-auto flex items-center justify-center">
                  <i class="fas fa-search mr-2"></i>Update Dates
                </button>
              </div>
            </form>
          </div>

          <!-- Available Rooms -->
          <div class="p-6">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Available Rooms</h3>

            <?php if (!empty($available_rooms)): ?>
              <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($available_rooms as $room): ?>
                  <div class="border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition duration-200 room-card">
                    <?php if (!empty($room_details[$room]['image'])): ?>
                      <div class="h-40 overflow-hidden">
                        <img src="admin/<?php echo htmlspecialchars($room_details[$room]['image']); ?>" class="w-full h-full object-cover">
                      </div>
                    <?php endif; ?>
                    <div class="p-4">
                      <div class="flex justify-between items-start">
                        <div>
                          <h4 class="font-semibold text-lg">Room <?php echo htmlspecialchars(str_replace('r', '', $room)); ?></h4>
                          <p class="text-gray-600 capitalize"><?php echo isset($room_details[$room]) ? $room_details[$room]['type'] : 'Standard'; ?> Room</p>
                        </div>
                        <span class="bg-teal-100 text-teal-800 text-xs px-2 py-1 rounded">
                          <i class="fas fa-user mr-1"></i> <?php echo isset($room_details[$room]) ? $room_details[$room]['capacity'] : '2 guests'; ?>
                        </span>
                      </div>

                      <p class="text-gray-600 text-sm mt-2">
                        <?php echo isset($room_details[$room]) ? $room_details[$room]['description'] : 'Comfortable room with all basic amenities.'; ?>
                      </p>

                      <div class="flex flex-wrap mt-2">
                        <span class="inline-flex items-center text-xs text-gray-700 mr-2 mb-1">
                          <i class="fas fa-wifi mr-1 text-teal-600"></i> Free WiFi
                        </span>
                        <span class="inline-flex items-center text-xs text-gray-700 mr-2 mb-1">
                          <i class="fas fa-tv mr-1 text-teal-600"></i> Flat-screen TV
                        </span>
                        <span class="inline-flex items-center text-xs text-gray-700 mr-2 mb-1">
                          <i class="fas fa-shower mr-1 text-teal-600"></i> Private bathroom
                        </span>
                      </div>

                      <div class="flex justify-between items-center mt-4">
                        <div>
                          <span class="text-teal-600 font-semibold text-lg">$<?php echo number_format($hotel['price_per_night'], 2); ?></span>
                          <span class="text-gray-600 text-sm">/night</span>
                        </div>
                        <button type="button" onclick="showBookingForm('<?php echo htmlspecialchars($room); ?>')"
                          class="bg-teal-600 text-white px-4 py-2 rounded hover:bg-teal-700 transition duration-200 flex items-center"
                          <?php echo !$user_id ? 'disabled' : ''; ?>>
                          <i class="fas fa-check-circle mr-1"></i> Book Now
                        </button>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="bg-yellow-50 text-yellow-800 p-4 rounded-lg flex items-center">
                <i class="fas fa-exclamation-circle mr-3 text-yellow-600 text-xl"></i>
                <div>
                  <p class="font-semibold">No rooms available for the selected dates.</p>
                  <p>Please try different dates or check other hotels.</p>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Hotel Policies and FAQ -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
          <div class="p-6">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Hotel Policies</h3>

            <div class="grid md:grid-cols-3 gap-4 mb-6">
              <div class="flex">
                <div class="mr-3 text-teal-600 text-xl">
                  <i class="fas fa-clock"></i>
                </div>
                <div>
                  <h4 class="font-semibold">Check-in/out Times</h4>
                  <p class="text-gray-600 text-sm">Check-in: 2:00 PM - 12:00 AM</p>
                  <p class="text-gray-600 text-sm">Check-out: Before 12:00 PM</p>
                </div>
              </div>

              <div class="flex">
                <div class="mr-3 text-teal-600 text-xl">
                  <i class="fas fa-credit-card"></i>
                </div>
                <div>
                  <h4 class="font-semibold">Payment Methods</h4>
                  <p class="text-gray-600 text-sm">Credit/Debit cards, Cash</p>
                  <p class="text-gray-600 text-sm">Full payment upon arrival</p>
                </div>
              </div>

              <div class="flex">
                <div class="mr-3 text-teal-600 text-xl">
                  <i class="fas fa-ban"></i>
                </div>
                <div>
                  <h4 class="font-semibold">Cancellation Policy</h4>
                  <p class="text-gray-600 text-sm">Free cancellation up to 24 hours before check-in</p>
                </div>
              </div>
            </div>

            <h3 class="text-xl font-semibold text-gray-800 mb-4">Frequently Asked Questions</h3>

            <div class="space-y-3">
              <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="font-semibold">Is breakfast included in the room price?</h4>
                <p class="text-gray-600 mt-1">Yes, breakfast is included with all room bookings.</p>
              </div>

              <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="font-semibold">Is there free WiFi available?</h4>
                <p class="text-gray-600 mt-1">Yes, complimentary high-speed WiFi is available throughout the hotel.</p>
              </div>

              <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="font-semibold">Is parking available?</h4>
                <p class="text-gray-600 mt-1">Yes, free self-parking is available on the premises.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Booking Modal -->
  <div id="bookingModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6 max-h-screen overflow-y-auto mx-4">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-semibold text-gray-800">Complete Your Booking</h3>
        <button type="button" onclick="hideBookingForm()" class="text-gray-500 hover:text-gray-700">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <?php if (isset($error_message)): ?>
        <div class="bg-red-50 text-red-800 p-4 rounded-lg mb-4">
          <i class="fas fa-exclamation-circle mr-2"></i>
          <?php echo htmlspecialchars($error_message); ?>
        </div>
      <?php endif; ?>

      <form action="" method="POST">
        <input type="hidden" name="room_id" id="bookingRoomId">

        <div class="bg-gray-50 p-4 rounded-lg mb-4">
          <div class="flex justify-between items-center">
            <span class="font-bold"><?php echo htmlspecialchars($hotel['hotel_name']); ?></span>
            <div class="flex">
              <?php for ($i = 0; $i < $hotel['rating']; $i++): ?>
                <i class="fas fa-star text-yellow-400 text-sm"></i>
              <?php endfor; ?>
            </div>
          </div>
          <div class="flex justify-between items-center mt-2">
            <div class="text-sm text-gray-600">
              <div><i class="far fa-calendar-alt mr-1"></i> Check-in: <?php echo date('M d, Y', strtotime($check_in)); ?></div>
              <div><i class="far fa-calendar-alt mr-1"></i> Check-out: <?php echo date('M d, Y', strtotime($check_out)); ?></div>
              <div><i class="fas fa-moon mr-1"></i> <?php echo $days; ?> night<?php echo $days > 1 ? 's' : ''; ?></div>
            </div>
            <div class="text-right">
              <div class="text-teal-600 font-bold">$<?php echo number_format($total_price, 2); ?></div>
              <div class="text-xs text-gray-600">Total price</div>
            </div>
          </div>
        </div>

        <div class="mb-4">
          <label for="guest_name" class="block text-gray-700 font-medium mb-2">Full Name*</label>
          <input type="text" name="guest_name" id="guest_name" required
            value="<?php echo htmlspecialchars($user_name); ?>"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
        </div>

        <div class="mb-4">
          <label for="guest_email" class="block text-gray-700 font-medium mb-2">Email Address*</label>
          <input type="email" name="guest_email" id="guest_email" required
            value="<?php echo htmlspecialchars($user_email); ?>"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
        </div>

        <div class="mb-4">
          <label for="guest_phone" class="block text-gray-700 font-medium mb-2">Phone Number*</label>
          <input type="tel" name="guest_phone" id="guest_phone" required
            value="<?php echo htmlspecialchars($user_phone); ?>"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
        </div>

        <input type="hidden" name="check_in_date" value="<?php echo htmlspecialchars($check_in); ?>">
        <input type="hidden" name="check_out_date" value="<?php echo htmlspecialchars($check_out); ?>">

        <div class="bg-yellow-50 p-4 rounded-lg mb-4 text-sm">
          <div class="flex items-start">
            <i class="fas fa-info-circle mt-1 mr-2 text-yellow-600"></i>
            <div>
              <p class="text-yellow-800">Please note:</p>
              <ul class="list-disc list-inside text-yellow-700 mt-1">
                <li>Check-in time is after 2:00 PM</li>
                <li>Check-out time is before 12:00 PM</li>
                <li>Free cancellation up to 24 hours before check-in</li>
              </ul>
            </div>
          </div>
        </div>

        <button type="submit" name="book_room" class="w-full bg-teal-600 text-white py-3 rounded-lg hover:bg-teal-700 transition duration-200 flex items-center justify-center">
          <i class="fas fa-check-circle mr-2"></i> Complete Booking
        </button>
      </form>
    </div>
  </div>

  <?php include 'includes/js-links.php'; ?>

  <script>
    // Date picker initialization
    flatpickr("#check_in", {
      minDate: "today",
      onChange: function(selectedDates, dateStr, instance) {
        // Set min date for checkout to be the day after check-in
        document.getElementById("check_out").setAttribute("min",
          new Date(selectedDates[0].getTime() + 86400000).toISOString().split('T')[0]);

        // If check-out date is earlier than new check-in date, update it
        const checkOutDate = new Date(document.getElementById("check_out").value);
        if (checkOutDate <= selectedDates[0]) {
          const nextDay = new Date(selectedDates[0].getTime() + 86400000);
          document.getElementById("check_out").value = nextDay.toISOString().split('T')[0];
        }
      }
    });

    flatpickr("#check_out", {
      minDate: new Date().fp_incr(1), // Tomorrow
    });

    // Image gallery functionality
    let currentImageIndex = 0;
    const galleryImages = document.querySelectorAll('.gallery-image');

    function changeImage(direction) {
      if (galleryImages.length <= 1) return;

      // Hide current image
      galleryImages[currentImageIndex].classList.add('hidden');

      // Calculate new index
      currentImageIndex = (currentImageIndex + direction + galleryImages.length) % galleryImages.length;

      // Show new image
      galleryImages[currentImageIndex].classList.remove('hidden');
    }

    // Booking modal functionality
    function showBookingForm(roomId) {
      document.getElementById('bookingRoomId').value = roomId;
      document.getElementById('bookingModal').classList.remove('hidden');
      document.body.style.overflow = 'hidden'; // Prevent scrolling
    }

    function hideBookingForm() {
      document.getElementById('bookingModal').classList.add('hidden');
      document.body.style.overflow = 'auto'; // Restore scrolling
    }

    // Close modal when clicking outside
    document.getElementById('bookingModal').addEventListener('click', function(e) {
      if (e.target === this) {
        hideBookingForm();
      }
    });

    // Mobile menu toggle
    document.getElementById('menu-btn').addEventListener('click', function() {
      document.querySelector('.sidebar').classList.toggle('-translate-x-full');
    });
  </script>
</body>

</html>