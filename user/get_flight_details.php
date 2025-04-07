<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  echo "<p class='text-red-500'>You must be logged in to view flight details.</p>";
  exit();
}

require_once '../connection/connection.php';

// Get flight ID from query parameter
if (!isset($_GET['flight_id']) || empty($_GET['flight_id'])) {
  echo "<p class='text-red-500'>No flight ID provided.</p>";
  exit();
}

$flight_id = intval($_GET['flight_id']);
$user_id = $_SESSION['user_id'];

// First check if this is a standalone flight booking
// Adjust field names according to your actual database structure
$standalone_sql = "
    SELECT 
        f.*, 
        fb.id as booking_id,
        fb.passenger_name,
        fb.passenger_email,
        fb.passenger_phone,
        fb.adult_count,
        fb.children_count,
        fb.booking_date,
        fb.seats,
        fb.return_flight_data,
        'standalone' AS booking_type
    FROM flights f 
    INNER JOIN flight_bookings fb ON f.id = fb.flight_id 
    WHERE f.id = ? AND fb.user_id = ?
";

try {
  $standalone_stmt = $conn->prepare($standalone_sql);
  $standalone_stmt->bind_param("ii", $flight_id, $user_id);
  $standalone_stmt->execute();
  $standalone_result = $standalone_stmt->get_result();

  // If not found, check if it's a direct booking
  if ($standalone_result->num_rows == 0) {
    $direct_sql = "
            SELECT 
                f.*, 
                fb.id as booking_id,
                fb.passenger_name,
                fb.passenger_email,
                fb.passenger_contact as passenger_phone,
                1 as adult_count,
                0 as children_count,
                fb.booking_time as booking_date,
                NULL as seats,
                NULL as return_flight_data,
                'direct' AS booking_type
            FROM flights f 
            INNER JOIN flight_book fb ON f.id = fb.flight_id 
            WHERE f.id = ? AND fb.user_id = ?
        ";
    $direct_stmt = $conn->prepare($direct_sql);
    $direct_stmt->bind_param("ii", $flight_id, $user_id);
    $direct_stmt->execute();
    $direct_result = $direct_stmt->get_result();

    // If not found, check if it's a package flight
    if ($direct_result->num_rows == 0) {
      $package_sql = "
                SELECT 
                    f.*, 
                    fa.seat_type,
                    pb.id as booking_id,
                    pb.passenger_name,
                    pb.passenger_email,
                    pb.passenger_phone,
                    pb.adult_count,
                    pb.children_count,
                    pb.booking_date,
                    NULL as seats,
                    NULL as return_flight_data,
                    'package' AS booking_type
                FROM flights f 
                INNER JOIN flight_assign fa ON f.id = fa.flight_id
                INNER JOIN package_booking pb ON fa.booking_id = pb.id
                WHERE f.id = ? AND fa.user_id = ?
            ";
      $package_stmt = $conn->prepare($package_sql);
      $package_stmt->bind_param("ii", $flight_id, $user_id);
      $package_stmt->execute();
      $package_result = $package_stmt->get_result();

      // Check if flight was found in package
      if ($package_result->num_rows > 0) {
        $flight = $package_result->fetch_assoc();
      } else {
        echo "<p class='text-red-500'>Flight not found or you don't have permission to view it.</p>";
        exit();
      }
    } else {
      $flight = $direct_result->fetch_assoc();
    }
  } else {
    $flight = $standalone_result->fetch_assoc();
  }
} catch (Exception $e) {
  echo "<p class='text-red-500'>Error retrieving flight details: " . htmlspecialchars($e->getMessage()) . "</p>";
  exit();
}

// Check if flight has return data
$has_return = false;
$return_data = null;
if (isset($flight['return_flight_data']) && !empty($flight['return_flight_data'])) {
  $return_data = json_decode($flight['return_flight_data'], true);
  if (isset($return_data['has_return']) && $return_data['has_return'] == 1) {
    $has_return = true;
  }
}

// Format dates
$departure_date = date('D, M j, Y', strtotime($flight['departure_date']));
$departure_time = date('g:i A', strtotime($flight['departure_time']));
$booking_date = date('M j, Y', strtotime($flight['booking_date']));

// Parse stops if available
$stops = [];
if (!empty($flight['stops']) && $flight['stops'] != '"direct"') {
  $stops_data = json_decode($flight['stops'], true);
  if (is_array($stops_data)) {
    $stops = $stops_data;
  }
}

// Parse seats
$seats = [];
if (!empty($flight['seats'])) {
  $seats_data = json_decode($flight['seats'], true);
  if (is_array($seats_data)) {
    $seats = $seats_data;
  }
}
?>

<div class="bg-white rounded-lg p-6">
  <!-- Flight Header -->
  <div class="flex justify-between items-center mb-6 border-b pb-4">
    <div>
      <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($flight['airline_name']); ?></h3>
      <p class="text-gray-600">Flight #<?php echo htmlspecialchars($flight['flight_number']); ?></p>
    </div>
    <div class="text-right">
      <span class="px-3 py-1 rounded-full text-sm
                <?php
                switch ($flight['booking_type']) {
                  case 'direct':
                    echo 'bg-blue-100 text-blue-800';
                    break;
                  case 'package':
                    echo 'bg-purple-100 text-purple-800';
                    break;
                  case 'standalone':
                    echo 'bg-green-100 text-green-800';
                    break;
                }
                ?>">
        <?php
        switch ($flight['booking_type']) {
          case 'direct':
            echo 'Direct Booking';
            break;
          case 'package':
            echo 'Package Booking';
            break;
          case 'standalone':
            echo $has_return ? 'Round Trip' : 'One Way';
            break;
        }
        ?>
      </span>
      <p class="text-gray-500 text-sm mt-1">Booked on <?php echo $booking_date; ?></p>
    </div>
  </div>

  <!-- Flight Details Section -->
  <div class="mb-6">
    <h4 class="text-lg font-semibold mb-3 text-blue-700">Flight Details</h4>

    <div class="bg-blue-50 rounded-lg p-4 mb-4">
      <div class="flex items-start">
        <!-- Departure Info -->
        <div class="w-2/5">
          <div class="text-sm text-gray-500">Departure</div>
          <div class="font-medium text-lg"><?php echo $departure_time; ?></div>
          <div class="text-gray-700"><?php echo $departure_date; ?></div>
          <div class="font-medium"><?php echo htmlspecialchars($flight['departure_city']); ?></div>
        </div>

        <!-- Flight Path -->
        <div class="w-1/5 flex flex-col items-center">
          <div class="text-sm text-gray-500">Duration</div>
          <div class="my-2 text-blue-600"><?php echo htmlspecialchars($flight['flight_duration'] ?? 'N/A'); ?> hours</div>
          <div class="w-full flex items-center">
            <div class="h-0.5 flex-1 bg-gray-300"></div>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500 mx-1" viewBox="0 0 20 20" fill="currentColor">
              <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
            </svg>
            <div class="h-0.5 flex-1 bg-gray-300"></div>
          </div>
          <?php if (isset($flight['distance']) && !empty($flight['distance'])): ?>
            <div class="text-sm text-gray-600 mt-2"><?php echo htmlspecialchars($flight['distance']); ?> km</div>
          <?php endif; ?>
        </div>

        <!-- Arrival Info -->
        <div class="w-2/5 text-right">
          <div class="text-sm text-gray-500">Arrival</div>
          <div class="font-medium text-lg">
            <?php
            // Calculate arrival time based on departure and duration if available
            if (!empty($flight['departure_time']) && !empty($flight['flight_duration'])) {
              $dep_time = new DateTime($flight['departure_time']);
              $duration_parts = explode('.', $flight['flight_duration']);
              $hours = intval($duration_parts[0]);
              $minutes = isset($duration_parts[1]) ? intval(60 * ('0.' . $duration_parts[1])) : 0;
              $dep_time->add(new DateInterval('PT' . $hours . 'H' . $minutes . 'M'));
              echo $dep_time->format('g:i A');
            } else {
              echo 'N/A';
            }
            ?>
          </div>
          <div class="text-gray-700"><?php echo $departure_date; ?></div>
          <div class="font-medium"><?php echo htmlspecialchars($flight['arrival_city']); ?></div>
        </div>
      </div>

      <!-- Stops Information -->
      <?php if (!empty($stops)): ?>
        <div class="mt-4 pt-3 border-t border-gray-200">
          <div class="text-sm font-medium text-gray-700 mb-2">Stops:</div>
          <div class="flex flex-wrap gap-2">
            <?php foreach ($stops as $stop): ?>
              <span class="px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-xs">
                <?php echo htmlspecialchars($stop['city']); ?> (<?php echo htmlspecialchars($stop['duration']); ?> hrs)
              </span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Return Flight Details (if applicable) -->
    <?php if ($has_return): ?>
      <h4 class="text-lg font-semibold mt-6 mb-3 text-purple-700">Return Flight Details</h4>
      <div class="bg-purple-50 rounded-lg p-4 mb-4">
        <div class="flex items-start">
          <!-- Departure Info (Return flight starts from arrival city) -->
          <div class="w-2/5">
            <div class="text-sm text-gray-500">Departure</div>
            <div class="font-medium text-lg">
              <?php
              echo isset($return_data['return_time']) ?
                date('g:i A', strtotime($return_data['return_time'])) : 'N/A';
              ?>
            </div>
            <div class="text-gray-700">
              <?php
              echo isset($return_data['return_date']) ?
                date('D, M j, Y', strtotime($return_data['return_date'])) : 'N/A';
              ?>
            </div>
            <div class="font-medium"><?php echo htmlspecialchars($flight['arrival_city']); ?></div>
          </div>

          <!-- Flight Path -->
          <div class="w-1/5 flex flex-col items-center">
            <div class="text-sm text-gray-500">Duration</div>
            <div class="my-2 text-purple-600">
              <?php echo htmlspecialchars($return_data['return_flight_duration'] ?? 'N/A'); ?> hours
            </div>
            <div class="w-full flex items-center">
              <div class="h-0.5 flex-1 bg-gray-300"></div>
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-500 mx-1" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
              </svg>
              <div class="h-0.5 flex-1 bg-gray-300"></div>
            </div>
          </div>

          <!-- Arrival Info (Return flight arrives at departure city) -->
          <div class="w-2/5 text-right">
            <div class="text-sm text-gray-500">Arrival</div>
            <div class="font-medium text-lg">
              <?php
              // Calculate arrival time for return flight
              if (isset($return_data['return_time']) && isset($return_data['return_flight_duration'])) {
                $ret_time = new DateTime($return_data['return_time']);
                $ret_duration_parts = explode('.', $return_data['return_flight_duration']);
                $ret_hours = intval($ret_duration_parts[0]);
                $ret_minutes = isset($ret_duration_parts[1]) ? intval(60 * ('0.' . $ret_duration_parts[1])) : 0;
                $ret_time->add(new DateInterval('PT' . $ret_hours . 'H' . $ret_minutes . 'M'));
                echo $ret_time->format('g:i A');
              } else {
                echo 'N/A';
              }
              ?>
            </div>
            <div class="text-gray-700">
              <?php
              echo isset($return_data['return_date']) ?
                date('D, M j, Y', strtotime($return_data['return_date'])) : 'N/A';
              ?>
            </div>
            <div class="font-medium"><?php echo htmlspecialchars($flight['departure_city']); ?></div>
          </div>
        </div>

        <!-- Return Flight Stops Information -->
        <?php
        if (isset($return_data['return_stops']) && $return_data['return_stops'] != '"direct"'):
          $return_stops = json_decode($return_data['return_stops'], true);
          if (is_array($return_stops) && !empty($return_stops)):
        ?>
            <div class="mt-4 pt-3 border-t border-gray-200">
              <div class="text-sm font-medium text-gray-700 mb-2">Return Stops:</div>
              <div class="flex flex-wrap gap-2">
                <?php foreach ($return_stops as $stop): ?>
                  <span class="px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-xs">
                    <?php echo htmlspecialchars($stop['city']); ?> (<?php echo htmlspecialchars($stop['duration']); ?> hrs)
                  </span>
                <?php endforeach; ?>
              </div>
            </div>
        <?php
          endif;
        endif;
        ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Passenger Information -->
  <div class="mb-6">
    <h4 class="text-lg font-semibold mb-3 text-blue-700">Passenger Information</h4>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 rounded-lg p-4">
      <div>
        <div class="text-sm text-gray-500">Passenger Name</div>
        <div class="font-medium"><?php echo htmlspecialchars($flight['passenger_name']); ?></div>
      </div>
      <div>
        <div class="text-sm text-gray-500">Contact Email</div>
        <div class="font-medium"><?php echo htmlspecialchars($flight['passenger_email']); ?></div>
      </div>
      <div>
        <div class="text-sm text-gray-500">Contact Phone</div>
        <div class="font-medium"><?php echo htmlspecialchars($flight['passenger_phone']); ?></div>
      </div>
      <div>
        <div class="text-sm text-gray-500">Cabin Class</div>
        <div class="font-medium">
          <?php
          // Try to get cabin class from different possible sources
          if (!empty($flight['seat_type'])) {
            echo ucwords(str_replace('_', ' ', $flight['seat_type']));
          } else {
            // Get from the first seat if available
            if (!empty($seats) && is_array($seats) && count($seats) > 0) {
              $first_seat = $seats[0];
              if (substr($first_seat, 0, 1) === 'E') {
                echo 'Economy';
              } else if (substr($first_seat, 0, 1) === 'B') {
                echo 'Business';
              } else if (substr($first_seat, 0, 1) === 'F') {
                echo 'First Class';
              } else {
                echo 'N/A';
              }
            } else {
              echo 'N/A';
            }
          }
          ?>
        </div>
      </div>
      <div>
        <div class="text-sm text-gray-500">Adults</div>
        <div class="font-medium"><?php echo htmlspecialchars($flight['adult_count']); ?></div>
      </div>
      <div>
        <div class="text-sm text-gray-500">Children</div>
        <div class="font-medium"><?php echo htmlspecialchars($flight['children_count']); ?></div>
      </div>
    </div>
  </div>

  <!-- Seat Information -->
  <?php if (!empty($seats)): ?>
    <div class="mb-6">
      <h4 class="text-lg font-semibold mb-3 text-blue-700">Seat Information</h4>
      <div class="bg-gray-50 rounded-lg p-4">
        <div class="flex flex-wrap gap-2">
          <?php foreach ($seats as $seat): ?>
            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
              <?php echo htmlspecialchars($seat); ?>
            </span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Actions Section -->
  <div class="mt-8 flex justify-end space-x-4">
    <?php if (strtotime($flight['departure_date']) > time()): ?>
      <button class="px-4 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition">
        Request Cancellation
      </button>
    <?php endif; ?>
    <button class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-700 transition" onclick="printFlightDetails()">
      Print Details
    </button>
  </div>
</div>

<script>
  function printFlightDetails() {
    window.print();
  }
</script>