<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}

require_once '../connection/connection.php';

// Get user details
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if profile image exists and is accessible
$profile_image = $user['profile_image'];
if (!empty($profile_image) && file_exists("../" . $profile_image)) {
  $profile_image = "../" . $profile_image;
}

// Helper function to display flight row - defined BEFORE it's used
function displayFlightRow($flight)
{
  // Check if flight has return data
  $has_return = false;
  $return_data = null;
  if (isset($flight['return_flight_data']) && !empty($flight['return_flight_data'])) {
    $return_data = json_decode($flight['return_flight_data'], true);
    if (isset($return_data['has_return']) && $return_data['has_return'] == 1) {
      $has_return = true;
    }
  }
?>
  <tr class="border-b hover:bg-gray-100 transition">
    <td class="p-4">
      <?php echo htmlspecialchars($flight['airline_name']); ?>
    </td>
    <td class="p-4">
      <?php echo htmlspecialchars($flight['flight_number']); ?>
    </td>
    <td class="p-4">
      <?php echo htmlspecialchars($flight['departure_city'] . ' → ' . $flight['arrival_city']); ?>
      <?php if ($has_return): ?>
        <br><span class="text-xs text-purple-600">Return: <?php echo htmlspecialchars($flight['arrival_city'] . ' → ' . $flight['departure_city']); ?></span>
      <?php endif; ?>
    </td>
    <td class="p-4">
      <?php echo htmlspecialchars($flight['departure_date'] . ' ' . $flight['departure_time']); ?>
      <?php if ($has_return): ?>
        <br><span class="text-xs text-purple-600">Return: <?php echo htmlspecialchars($return_data['return_date'] . ' ' . $return_data['return_time']); ?></span>
      <?php endif; ?>
    </td>
    <td class="p-4">
      <?php
      // Display seat information
      if (isset($flight['seats']) && !empty($flight['seats'])) {
        // For JSON array of seats
        $seats = json_decode($flight['seats'], true);
        if (is_array($seats) && !empty($seats)) {
          echo implode(', ', array_slice($seats, 0, 3));
          if (count($seats) > 3) {
            echo " <span class='text-xs text-gray-500'>+" . (count($seats) - 3) . " more</span>";
          }
        } else {
          echo htmlspecialchars($flight['seat_number'] ?? 'Not assigned');
        }
      } elseif (!empty($flight['seat_number'])) {
        echo htmlspecialchars($flight['seat_number']);
      } elseif (!empty($flight['seat_type'])) {
        echo htmlspecialchars(ucfirst($flight['seat_type']));
      } else {
        echo "Not assigned";
      }
      ?>
    </td>
    <td class="p-4">
      <span class="status-badge px-3 py-1 rounded-full text-white
                <?php
                switch ($flight['flight_status']) {
                  case 'upcoming':
                    echo 'bg-yellow-500';
                    break;
                  case 'in-progress':
                    echo 'bg-green-500';
                    break;
                  case 'completed':
                    echo 'bg-gray-500';
                    break;
                  case 'cancelled':
                    echo 'bg-red-500';
                    break;
                  default:
                    echo 'bg-blue-500';
                }
                ?>">
        <?php echo ucfirst($flight['flight_status']); ?>
      </span>
    </td>
    <td class="p-4">
      <div class="countdown"
        data-departure="<?php echo $flight['departure_date'] . ' ' . $flight['departure_time']; ?>"
        data-flight-id="<?php echo $flight['id']; ?>">
        Calculating...
      </div>
      <?php if ($has_return): ?>
        <div class="text-xs text-purple-600 mt-1">
          (Return: <span class="return-countdown"
            data-departure="<?php echo $return_data['return_date'] . ' ' . $return_data['return_time']; ?>">
            Calculating...</span>)
        </div>
      <?php endif; ?>
    </td>
    <td class="p-4">
      <span class="px-2 py-1 rounded text-xs
                <?php
                switch ($flight['booking_type']) {
                  case 'direct':
                    echo 'bg-blue-100 text-blue-800';
                    break;
                  case 'package':
                    echo 'bg-purple-100 text-purple-800';
                    break;
                  case 'standalone':
                    echo $has_return ? 'bg-green-100 text-green-800' : 'bg-green-100 text-green-800';
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
      <?php if (isset($flight['distance']) && !empty($flight['distance'])): ?>
        <div class="text-xs text-gray-500 mt-1">
          Distance: <?php echo htmlspecialchars($flight['distance']); ?> km
        </div>
      <?php endif; ?>
    </td>
    <td class="p-4">
      <button onclick="viewFlightDetails(<?php echo $flight['id']; ?>)"
        class="bg-blue-500 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs">
        View Details
      </button>
    </td>
  </tr>
<?php
}

// Approach 1: Separate queries for different flight booking types
// Fetch direct flight bookings
$direct_flights_sql = "
    SELECT 
        f.id, f.airline_name, f.flight_number, f.departure_city, f.arrival_city, 
        f.departure_date, f.departure_time, f.flight_duration, f.distance,
        fb.booking_time, fb.flight_status, 
        'direct' AS booking_type,
        NULL AS seat_type,
        NULL AS seat_number,
        NULL AS seats,
        NULL AS package_id,
        NULL AS return_flight_data
    FROM flights f 
    INNER JOIN flight_book fb ON f.id = fb.flight_id 
    WHERE fb.user_id = ?
";
$direct_stmt = $conn->prepare($direct_flights_sql);
$direct_stmt->bind_param("i", $user_id);
$direct_stmt->execute();
$direct_flights = $direct_stmt->get_result();

// Fetch package assigned flights
$package_flights_sql = "
    SELECT 
        f.id, f.airline_name, f.flight_number, f.departure_city, f.arrival_city, 
        f.departure_date, f.departure_time, f.flight_duration, f.distance,
        pb.booking_date AS booking_time, 
        CASE 
            WHEN fa.status = 'assigned' THEN 'upcoming'
            WHEN fa.status = 'completed' THEN 'completed'
            WHEN fa.status = 'cancelled' THEN 'cancelled'
            ELSE 'upcoming'
        END AS flight_status,
        'package' AS booking_type,
        fa.seat_type,
        fa.seat_number,
        NULL AS seats,
        pb.id AS package_id,
        NULL AS return_flight_data
    FROM flights f 
    INNER JOIN flight_assign fa ON f.id = fa.flight_id 
    INNER JOIN package_booking pb ON fa.booking_id = pb.id
    WHERE fa.user_id = ?
";
$package_stmt = $conn->prepare($package_flights_sql);
$package_stmt->bind_param("i", $user_id);
$package_stmt->execute();
$package_flights = $package_stmt->get_result();

// Fetch standalone flight bookings with return flight data
$standalone_flights_sql = "
    SELECT 
        f.id, f.airline_name, f.flight_number, f.departure_city, f.arrival_city, 
        f.departure_date, f.departure_time, f.flight_duration, f.distance,
        fb.booking_date AS booking_time,
        fb.return_flight_data,
        fb.seats,
        CASE 
            WHEN CURDATE() < f.departure_date THEN 'upcoming'
            WHEN CURDATE() = f.departure_date THEN 'in-progress'
            ELSE 'completed'
        END AS flight_status,
        'standalone' AS booking_type,
        fb.cabin_class AS seat_type,
        NULL AS seat_number,
        NULL AS package_id
    FROM flights f 
    INNER JOIN flight_bookings fb ON f.id = fb.flight_id 
    WHERE fb.user_id = ?
";
$standalone_stmt = $conn->prepare($standalone_flights_sql);
$standalone_stmt->bind_param("i", $user_id);
$standalone_stmt->execute();
$standalone_flights = $standalone_stmt->get_result();

// Fetch user's hotel bookings
$hotel_bookings_sql = "
    SELECT 
        h.*, 
        hb.id as booking_id,
        hb.room_id,
        hb.check_in_date,
        hb.check_out_date,
        hb.status as booking_status,
        'direct' as booking_type
    FROM hotels h 
    INNER JOIN hotel_bookings hb ON h.id = hb.hotel_id 
    WHERE hb.user_id = ?
    ORDER BY hb.check_in_date
";
$hotel_stmt = $conn->prepare($hotel_bookings_sql);
$hotel_stmt->bind_param("i", $user_id);
$hotel_stmt->execute();
$hotel_bookings = $hotel_stmt->get_result();

// Fetch user's transportation bookings
$transport_bookings_sql = "
    SELECT 
        tb.*,
        ta.driver_name,
        ta.driver_contact,
        ta.status as assign_status,
        ta.vehicle_id
    FROM transportation_bookings tb
    LEFT JOIN transportation_assign ta ON tb.id = ta.booking_id AND tb.booking_reference = ta.booking_reference
    WHERE tb.user_id = ?
    ORDER BY tb.booking_date, tb.booking_time
";
$transport_stmt = $conn->prepare($transport_bookings_sql);
$transport_stmt->bind_param("i", $user_id);
$transport_stmt->execute();
$transport_bookings = $transport_stmt->get_result();



// Fetch user's package bookings
$package_bookings_sql = "
    SELECT 
        pb.id as booking_id,
        pb.package_id,
        p.title as package_title,
        p.description,
        p.package_type,
        p.price,
        p.airline,
        p.flight_class,
        p.departure_city,
        p.departure_time,
        p.departure_date,
        p.arrival_city,
        p.return_time,
        p.return_date,
        pb.booking_date,
        pb.status as booking_status,
        pb.payment_status,
        pa.hotel_id,
        pa.transport_id,
        pa.flight_id,
        pa.seat_type,
        pa.seat_number,
        pa.transport_seat_number,
        h.hotel_name,
        h.location as hotel_location
    FROM package_booking pb 
    INNER JOIN packages p ON pb.package_id = p.id
    LEFT JOIN package_assign pa ON pb.id = pa.booking_id
    LEFT JOIN hotels h ON pa.hotel_id = h.id
    WHERE pb.user_id = ?
    ORDER BY pb.booking_date DESC
";
$package_stmt = $conn->prepare($package_bookings_sql);
$package_stmt->bind_param("i", $user_id);
$package_stmt->execute();
$package_bookings = $package_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include '../includes/css-links.php' ?>
  <link rel="stylesheet" href="../assets/css/output.css">
  <title>My Bookings</title>
  <style>
    .tab-content {
      display: none;
    }

    .tab-content.active {
      display: block;
    }

    .tab {
      cursor: pointer;
      padding: 10px 20px;
      border-bottom: 2px solid transparent;
      transition: all 0.3s ease;
    }

    .tab.active {
      border-bottom: 2px solid #3B82F6;
      color: #3B82F6;
      font-weight: bold;
    }

    .flight-details-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background-color: white;
      padding: 20px;
      border-radius: 8px;
      max-width: 600px;
      width: 90%;
      max-height: 90vh;
      overflow-y: auto;
    }

    /* New table styling for better user experience */
    .booking-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .booking-table thead {
      background: linear-gradient(135deg, #4F46E5, #3B82F6);
    }

    .booking-table th {
      color: white;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.85rem;
      padding: 16px;
      text-align: left;
      letter-spacing: 0.5px;
    }

    .booking-table tbody tr {
      border-bottom: 1px solid #F3F4F6;
      transition: all 0.2s ease;
    }

    .booking-table tbody tr:hover {
      background-color: #F9FAFB;
      transform: translateY(-2px);
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .booking-table td {
      padding: 16px;
      vertical-align: middle;
      color: #374151;
    }

    .status-badge {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 50px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .action-button {
      background: linear-gradient(135deg, #3B82F6, #2563EB);
      color: white;
      font-weight: 600;
      padding: 8px 16px;
      border-radius: 6px;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
      box-shadow: 0 2px 4px rgba(37, 99, 235, 0.3);
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.5px;
    }

    .action-button:hover {
      background: linear-gradient(135deg, #2563EB, #1D4ED8);
      transform: translateY(-2px);
      box-shadow: 0 4px 6px rgba(37, 99, 235, 0.4);
    }

    /* Responsive design improvements */
    @media (max-width: 1024px) {
      .booking-table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
      }
    }

    @media (max-width: 768px) {
      .tab {
        padding: 8px 12px;
        font-size: 0.9rem;
      }

      .booking-table th,
      .booking-table td {
        padding: 12px 8px;
        font-size: 0.9rem;
      }

      .status-badge {
        padding: 4px 8px;
        font-size: 0.7rem;
      }
    }

    /* Animated countdown styling */
    .countdown,
    .return-countdown {
      font-family: 'Courier New', monospace;
      font-weight: bold;
      color: #4B5563;
      background-color: #F3F4F6;
      padding: 4px 8px;
      border-radius: 4px;
      display: inline-block;
    }

    /* Card-based mobile view for smaller screens */
    @media (max-width: 640px) {
      .mobile-card-view {
        display: flex;
        flex-direction: column;
      }

      .mobile-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-bottom: 16px;
        padding: 16px;
      }

      .mobile-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #E5E7EB;
        padding-bottom: 12px;
        margin-bottom: 12px;
      }

      .mobile-card-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
      }

      .mobile-card-label {
        font-size: 0.75rem;
        color: #6B7280;
        font-weight: 600;
        text-transform: uppercase;
      }

      .mobile-card-value {
        font-size: 0.875rem;
        color: #1F2937;
        margin-top: 4px;
      }

      .mobile-card-footer {
        display: flex;
        justify-content: flex-end;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #E5E7EB;
      }
    }

    /* Tab bar improvements */
    .tabs-container {
      display: flex;
      border-bottom: 1px solid #E5E7EB;
      margin-bottom: 24px;
      overflow-x: auto;
      white-space: nowrap;
      -ms-overflow-style: none;
      scrollbar-width: none;
    }

    .tabs-container::-webkit-scrollbar {
      display: none;
    }

    .tab {
      position: relative;
      padding: 12px 20px;
      font-weight: 500;
    }

    .tab.active::after {
      content: '';
      position: absolute;
      bottom: -1px;
      left: 0;
      width: 100%;
      height: 3px;
      background-color: #3B82F6;
      border-radius: 3px 3px 0 0;
    }

    .booking-stats {
      display: flex;
      gap: 16px;
      margin-bottom: 24px;
    }

    .stat-card {
      background: white;
      border-radius: 8px;
      padding: 16px;
      flex: 1;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .stat-number {
      font-size: 2rem;
      font-weight: bold;
      color: #3B82F6;
    }

    .stat-label {
      font-size: 0.875rem;
      color: #6B7280;
      margin-top: 4px;
    }
  </style>
</head>

<body class="bg-gray-100">
  <?php include 'includes/sidebar.php'; ?>

  <!-- Flight Details Modal -->
  <div id="flightDetailsModal" class="flight-details-modal">
    <div class="modal-content">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Flight Details</h2>
        <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      <div id="flightDetailsContent">
        <!-- Flight details will be loaded here -->
        <div class="flex justify-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="main-content p-8">
    <div class="container mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold mb-6">My Bookings</h1>

      <!-- Improved tab navigation -->
      <div class="flex border-b border-gray-200 mb-8 overflow-x-auto whitespace-nowrap">
        <div class="tab active" data-target="flights">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
          </svg>
          Flight Bookings
        </div>
        <div class="tab" data-target="hotels">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          Hotel Bookings
        </div>
        <div class="tab" data-target="transport">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
          </svg>
          Transportation Bookings
        </div>
        <!-- Add this after the transportation tab -->
        <div class="tab" data-target="packages">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
          </svg>
          Package Bookings
        </div>
      </div>

      <!-- Flights Tab Content -->
      <div id="flights" class="tab-content active">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
          <h2 class="text-2xl font-bold mb-4">Your Booked Flights</h2>

          <?php
          $has_flights = ($direct_flights->num_rows > 0 || $package_flights->num_rows > 0 || $standalone_flights->num_rows > 0);
          if ($has_flights) {
          ?>
            <!-- Stats cards for flights -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
              <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-4 text-white">
                <div class="text-lg opacity-80 mb-1">Total Flights</div>
                <div class="text-3xl font-bold">
                  <?php echo $direct_flights->num_rows + $package_flights->num_rows + $standalone_flights->num_rows; ?>
                </div>
              </div>
              <div class="bg-gradient-to-r from-yellow-400 to-yellow-500 rounded-lg p-4 text-white">
                <div class="text-lg opacity-80 mb-1">Upcoming</div>
                <div class="text-3xl font-bold">
                  <?php
                  $upcoming = 0;
                  $direct_flights->data_seek(0);
                  $package_flights->data_seek(0);
                  $standalone_flights->data_seek(0);

                  while ($flight = $direct_flights->fetch_assoc()) {
                    if ($flight['flight_status'] == 'upcoming') $upcoming++;
                  }
                  while ($flight = $package_flights->fetch_assoc()) {
                    if ($flight['flight_status'] == 'upcoming') $upcoming++;
                  }
                  while ($flight = $standalone_flights->fetch_assoc()) {
                    if ($flight['flight_status'] == 'upcoming') $upcoming++;
                  }

                  $direct_flights->data_seek(0);
                  $package_flights->data_seek(0);
                  $standalone_flights->data_seek(0);

                  echo $upcoming;
                  ?>
                </div>
              </div>
              <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-4 text-white">
                <div class="text-lg opacity-80 mb-1">Completed</div>
                <div class="text-3xl font-bold">
                  <?php
                  $completed = 0;
                  $direct_flights->data_seek(0);
                  $package_flights->data_seek(0);
                  $standalone_flights->data_seek(0);

                  while ($flight = $direct_flights->fetch_assoc()) {
                    if ($flight['flight_status'] == 'completed') $completed++;
                  }
                  while ($flight = $package_flights->fetch_assoc()) {
                    if ($flight['flight_status'] == 'completed') $completed++;
                  }
                  while ($flight = $standalone_flights->fetch_assoc()) {
                    if ($flight['flight_status'] == 'completed') $completed++;
                  }

                  $direct_flights->data_seek(0);
                  $package_flights->data_seek(0);
                  $standalone_flights->data_seek(0);

                  echo $completed;
                  ?>
                </div>
              </div>
            </div>

            <div class="overflow-x-auto rounded-lg shadow">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-blue-600 to-blue-700">
                  <tr>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Airline</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Flight</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Route</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Departure</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Seat</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Countdown</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Type</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <?php
                  // Debug code - Check if flights are being retrieved properly
                  $direct_flights->data_seek(0);
                  while ($flight = $direct_flights->fetch_assoc()) {
                  ?>
                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($flight['airline_name']); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($flight['flight_number']); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($flight['departure_city'] . ' → ' . $flight['arrival_city']); ?></div>
                        <?php if (isset($flight['return_flight_data']) && !empty($flight['return_flight_data'])):
                          $return_data = json_decode($flight['return_flight_data'], true);
                          if (isset($return_data['has_return']) && $return_data['has_return'] == 1):
                        ?>
                            <div class="text-xs text-purple-600 mt-1">Return: <?php echo htmlspecialchars($flight['arrival_city'] . ' → ' . $flight['departure_city']); ?></div>
                        <?php endif;
                        endif; ?>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($flight['departure_date'] . ' ' . $flight['departure_time']); ?></div>
                        <?php if (isset($flight['return_flight_data']) && !empty($flight['return_flight_data'])):
                          $return_data = json_decode($flight['return_flight_data'], true);
                          if (isset($return_data['has_return']) && $return_data['has_return'] == 1):
                        ?>
                            <div class="text-xs text-purple-600 mt-1">Return: <?php echo htmlspecialchars($return_data['return_date'] . ' ' . $return_data['return_time']); ?></div>
                        <?php endif;
                        endif; ?>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                          <?php
                          if (isset($flight['seats']) && !empty($flight['seats'])) {
                            $seats = json_decode($flight['seats'], true);
                            if (is_array($seats) && !empty($seats)) {
                              echo implode(', ', array_slice($seats, 0, 3));
                              if (count($seats) > 3) {
                                echo " <span class='text-xs text-gray-500'>+" . (count($seats) - 3) . " more</span>";
                              }
                            } else {
                              echo htmlspecialchars($flight['seat_number'] ?? 'Not assigned');
                            }
                          } elseif (!empty($flight['seat_number'])) {
                            echo htmlspecialchars($flight['seat_number']);
                          } elseif (!empty($flight['seat_type'])) {
                            echo htmlspecialchars(ucfirst($flight['seat_type']));
                          } else {
                            echo "Not assigned";
                          }
                          ?>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                      <?php
                      switch ($flight['flight_status']) {
                        case 'upcoming':
                          echo 'bg-yellow-100 text-yellow-800';
                          break;
                        case 'in-progress':
                          echo 'bg-green-100 text-green-800';
                          break;
                        case 'completed':
                          echo 'bg-gray-100 text-gray-800';
                          break;
                        case 'cancelled':
                          echo 'bg-red-100 text-red-800';
                          break;
                        default:
                          echo 'bg-blue-100 text-blue-800';
                      }
                      ?>">
                          <?php echo ucfirst($flight['flight_status']); ?>
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="countdown bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs font-mono"
                          data-departure="<?php echo $flight['departure_date'] . ' ' . $flight['departure_time']; ?>"
                          data-flight-id="<?php echo $flight['id']; ?>">
                          Calculating...
                        </div>
                        <?php if (isset($flight['return_flight_data']) && !empty($flight['return_flight_data'])):
                          $return_data = json_decode($flight['return_flight_data'], true);
                          if (isset($return_data['has_return']) && $return_data['has_return'] == 1):
                        ?>
                            <div class="text-xs text-purple-600 mt-1">
                              (Return: <span class="return-countdown bg-gray-100 text-gray-800 px-1 rounded font-mono"
                                data-departure="<?php echo $return_data['return_date'] . ' ' . $return_data['return_time']; ?>">
                                Calculating...</span>)
                            </div>
                        <?php endif;
                        endif; ?>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded
                      <?php
                      switch ($flight['booking_type']) {
                        case 'direct':
                          echo 'bg-blue-100 text-blue-800';
                          break;
                        case 'package':
                          echo 'bg-purple-100 text-purple-800';
                          break;
                        case 'standalone':
                          echo isset($return_data['has_return']) && $return_data['has_return'] == 1 ? 'bg-green-100 text-green-800' : 'bg-green-100 text-green-800';
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
                              echo isset($return_data['has_return']) && $return_data['has_return'] == 1 ? 'Round Trip' : 'One Way';
                              break;
                          }
                          ?>
                        </span>
                        <?php if (isset($flight['distance']) && !empty($flight['distance'])): ?>
                          <div class="text-xs text-gray-500 mt-1">
                            Distance: <?php echo htmlspecialchars($flight['distance']); ?> km
                          </div>
                        <?php endif; ?>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="viewFlightDetails(<?php echo $flight['id']; ?>)"
                          class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition duration-200 shadow-sm">
                          View Details
                        </button>
                      </td>
                    </tr>
                  <?php
                  }

                  // Display package flights
                  $package_flights->data_seek(0);
                  while ($flight = $package_flights->fetch_assoc()) {
                  ?>
                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($flight['airline_name']); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($flight['flight_number']); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($flight['departure_city'] . ' → ' . $flight['arrival_city']); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($flight['departure_date'] . ' ' . $flight['departure_time']); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                          <?php
                          if (!empty($flight['seat_number'])) {
                            echo htmlspecialchars($flight['seat_number']);
                          } elseif (!empty($flight['seat_type'])) {
                            echo htmlspecialchars(ucfirst($flight['seat_type']));
                          } else {
                            echo "Not assigned";
                          }
                          ?>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                      <?php
                      switch ($flight['flight_status']) {
                        case 'upcoming':
                          echo 'bg-yellow-100 text-yellow-800';
                          break;
                        case 'completed':
                          echo 'bg-gray-100 text-gray-800';
                          break;
                        case 'cancelled':
                          echo 'bg-red-100 text-red-800';
                          break;
                        default:
                          echo 'bg-blue-100 text-blue-800';
                      }
                      ?>">
                          <?php echo ucfirst($flight['flight_status']); ?>
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="countdown bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs font-mono"
                          data-departure="<?php echo $flight['departure_date'] . ' ' . $flight['departure_time']; ?>"
                          data-flight-id="<?php echo $flight['id']; ?>">
                          Calculating...
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded bg-purple-100 text-purple-800">
                          Package Booking
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="viewFlightDetails(<?php echo $flight['id']; ?>)"
                          class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition duration-200 shadow-sm">
                          View Details
                        </button>
                      </td>
                    </tr>
                  <?php
                  }

                  // Display standalone flights
                  $standalone_flights->data_seek(0);
                  while ($flight = $standalone_flights->fetch_assoc()) {
                    $return_data = null;
                    $has_return = false;
                    if (isset($flight['return_flight_data']) && !empty($flight['return_flight_data'])) {
                      $return_data = json_decode($flight['return_flight_data'], true);
                      if (isset($return_data['has_return']) && $return_data['has_return'] == 1) {
                        $has_return = true;
                      }
                    }
                  ?>
                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($flight['airline_name']); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($flight['flight_number']); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($flight['departure_city'] . ' → ' . $flight['arrival_city']); ?></div>
                        <?php if ($has_return): ?>
                          <div class="text-xs text-purple-600 mt-1">Return: <?php echo htmlspecialchars($flight['arrival_city'] . ' → ' . $flight['departure_city']); ?></div>
                        <?php endif; ?>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($flight['departure_date'] . ' ' . $flight['departure_time']); ?></div>
                        <?php if ($has_return): ?>
                          <div class="text-xs text-purple-600 mt-1">Return: <?php echo htmlspecialchars($return_data['return_date'] . ' ' . $return_data['return_time']); ?></div>
                        <?php endif; ?>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                          <?php
                          if (isset($flight['seats']) && !empty($flight['seats'])) {
                            $seats = json_decode($flight['seats'], true);
                            if (is_array($seats) && !empty($seats)) {
                              echo implode(', ', array_slice($seats, 0, 3));
                              if (count($seats) > 3) {
                                echo " <span class='text-xs text-gray-500'>+" . (count($seats) - 3) . " more</span>";
                              }
                            } else {
                              echo htmlspecialchars($flight['seat_type'] ?? 'Not assigned');
                            }
                          } else {
                            echo htmlspecialchars($flight['seat_type'] ?? 'Not assigned');
                          }
                          ?>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                      <?php
                      switch ($flight['flight_status']) {
                        case 'upcoming':
                          echo 'bg-yellow-100 text-yellow-800';
                          break;
                        case 'in-progress':
                          echo 'bg-green-100 text-green-800';
                          break;
                        case 'completed':
                          echo 'bg-gray-100 text-gray-800';
                          break;
                        case 'cancelled':
                          echo 'bg-red-100 text-red-800';
                          break;
                        default:
                          echo 'bg-blue-100 text-blue-800';
                      }
                      ?>">
                          <?php echo ucfirst($flight['flight_status']); ?>
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="countdown bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs font-mono"
                          data-departure="<?php echo $flight['departure_date'] . ' ' . $flight['departure_time']; ?>"
                          data-flight-id="<?php echo $flight['id']; ?>">
                          Calculating...
                        </div>
                        <?php if ($has_return): ?>
                          <div class="text-xs text-purple-600 mt-1">
                            (Return: <span class="return-countdown bg-gray-100 text-gray-800 px-1 rounded font-mono"
                              data-departure="<?php echo $return_data['return_date'] . ' ' . $return_data['return_time']; ?>">
                              Calculating...</span>)
                          </div>
                        <?php endif; ?>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded bg-green-100 text-green-800">
                          <?php echo $has_return ? 'Round Trip' : 'One Way'; ?>
                        </span>
                        <?php if (isset($flight['distance']) && !empty($flight['distance'])): ?>
                          <div class="text-xs text-gray-500 mt-1">
                            Distance: <?php echo htmlspecialchars($flight['distance']); ?> km
                          </div>
                        <?php endif; ?>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="viewFlightDetails(<?php echo $flight['id']; ?>)"
                          class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition duration-200 shadow-sm">
                          View Details
                        </button>
                      </td>
                    </tr>
                  <?php
                  }
                  ?>
                </tbody>
              </table>
            </div>

            <!-- Mobile view for smaller screens -->
            <div class="block md:hidden mt-6">
              <?php
              $direct_flights->data_seek(0);
              $package_flights->data_seek(0);
              $standalone_flights->data_seek(0);

              // Display direct flights in card format
              while ($flight = $direct_flights->fetch_assoc()) {
              ?>
                <div class="bg-white rounded-lg shadow mb-4 overflow-hidden">
                  <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-4 py-2 flex justify-between items-center">
                    <div class="text-white font-bold"><?php echo htmlspecialchars($flight['airline_name']); ?></div>
                    <span class="px-2 py-1 rounded-full text-xs text-white bg-opacity-80 
                  <?php
                  switch ($flight['flight_status']) {
                    case 'upcoming':
                      echo 'bg-yellow-500';
                      break;
                    case 'in-progress':
                      echo 'bg-green-500';
                      break;
                    case 'completed':
                      echo 'bg-gray-600';
                      break;
                    case 'cancelled':
                      echo 'bg-red-500';
                      break;
                    default:
                      echo 'bg-blue-700';
                  }
                  ?>">
                      <?php echo ucfirst($flight['flight_status']); ?>
                    </span>
                  </div>
                  <div class="p-4">
                    <div class="flex justify-between mb-3">
                      <div>
                        <div class="text-xs text-gray-500">Flight</div>
                        <div class="font-medium"><?php echo htmlspecialchars($flight['flight_number']); ?></div>
                      </div>
                      <div>
                        <div class="text-xs text-gray-500">Type</div>
                        <div class="font-medium">Direct</div>
                      </div>
                    </div>

                    <div class="flex items-center mb-3">
                      <div class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                      </div>
                      <div class="flex-1">
                        <div class="text-sm"><?php echo htmlspecialchars($flight['departure_city'] . ' → ' . $flight['arrival_city']); ?></div>
                      </div>
                    </div>

                    <div class="flex items-center mb-3">
                      <div class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </div>
                      <div class="flex-1">
                        <div class="text-sm"><?php echo htmlspecialchars($flight['departure_date'] . ' ' . $flight['departure_time']); ?></div>
                        <div class="countdown text-xs bg-gray-100 px-2 py-1 rounded mt-1 font-mono inline-block"
                          data-departure="<?php echo $flight['departure_date'] . ' ' . $flight['departure_time']; ?>"
                          data-flight-id="<?php echo $flight['id']; ?>">
                          Calculating...
                        </div>
                      </div>
                    </div>

                    <div class="flex items-center mb-4">
                      <div class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                      </div>
                      <div class="flex-1">
                        <div class="text-sm">
                          <?php
                          if (isset($flight['seats']) && !empty($flight['seats'])) {
                            $seats = json_decode($flight['seats'], true);
                            if (is_array($seats) && !empty($seats)) {
                              echo implode(', ', array_slice($seats, 0, 3));
                              if (count($seats) > 3) {
                                echo " <span class='text-xs text-gray-500'>+" . (count($seats) - 3) . " more</span>";
                              }
                            } else {
                              echo htmlspecialchars($flight['seat_number'] ?? 'Not assigned');
                            }
                          } elseif (!empty($flight['seat_number'])) {
                            echo htmlspecialchars($flight['seat_number']);
                          } elseif (!empty($flight['seat_type'])) {
                            echo htmlspecialchars(ucfirst($flight['seat_type']));
                          } else {
                            echo "Not assigned";
                          }
                          ?>
                        </div>
                      </div>
                    </div>

                    <button onclick="viewFlightDetails(<?php echo $flight['id']; ?>)"
                      class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      View Details
                    </button>
                  </div>
                </div>
              <?php
              }

              // Display package flights in card format
              $package_flights->data_seek(0);
              while ($flight = $package_flights->fetch_assoc()) {
              ?>
                <div class="bg-white rounded-lg shadow mb-4 overflow-hidden">
                  <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-4 py-2 flex justify-between items-center">
                    <div class="text-white font-bold"><?php echo htmlspecialchars($flight['airline_name']); ?></div>
                    <span class="px-2 py-1 rounded-full text-xs text-white bg-opacity-80 
                  <?php
                  switch ($flight['flight_status']) {
                    case 'upcoming':
                      echo 'bg-yellow-500';
                      break;
                    case 'completed':
                      echo 'bg-gray-600';
                      break;
                    case 'cancelled':
                      echo 'bg-red-500';
                      break;
                    default:
                      echo 'bg-blue-700';
                  }
                  ?>">
                      <?php echo ucfirst($flight['flight_status']); ?>
                    </span>
                  </div>
                  <div class="p-4">
                    <!-- Similar structure as direct flights but for package flights -->
                    <div class="flex justify-between mb-3">
                      <div>
                        <div class="text-xs text-gray-500">Flight</div>
                        <div class="font-medium"><?php echo htmlspecialchars($flight['flight_number']); ?></div>
                      </div>
                      <div>
                        <div class="text-xs text-gray-500">Type</div>
                        <div class="font-medium">Package</div>
                      </div>
                    </div>

                    <!-- Route info -->
                    <div class="flex items-center mb-3">
                      <div class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                      </div>
                      <div class="flex-1">
                        <div class="text-sm"><?php echo htmlspecialchars($flight['departure_city'] . ' → ' . $flight['arrival_city']); ?></div>
                      </div>
                    </div>

                    <!-- Departure info and countdown -->
                    <div class="flex items-center mb-3">
                      <div class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </div>
                      <div class="flex-1">
                        <div class="text-sm"><?php echo htmlspecialchars($flight['departure_date'] . ' ' . $flight['departure_time']); ?></div>
                        <div class="countdown text-xs bg-gray-100 px-2 py-1 rounded mt-1 font-mono inline-block"
                          data-departure="<?php echo $flight['departure_date'] . ' ' . $flight['departure_time']; ?>"
                          data-flight-id="<?php echo $flight['id']; ?>">
                          Calculating...
                        </div>
                      </div>
                    </div>

                    <!-- Seat info -->
                    <div class="flex items-center mb-4">
                      <div class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                      </div>
                      <div class="flex-1">
                        <div class="text-sm">
                          <?php
                          if (!empty($flight['seat_number'])) {
                            echo htmlspecialchars($flight['seat_number']);
                          } elseif (!empty($flight['seat_type'])) {
                            echo htmlspecialchars(ucfirst($flight['seat_type']));
                          } else {
                            echo "Not assigned";
                          }
                          ?>
                        </div>
                      </div>
                    </div>

                    <button onclick="viewFlightDetails(<?php echo $flight['id']; ?>)"
                      class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      View Details
                    </button>
                  </div>
                </div>
              <?php
              }

              // Display standalone flights in card format
              $standalone_flights->data_seek(0);
              while ($flight = $standalone_flights->fetch_assoc()) {
                $return_data = null;
                $has_return = false;
                if (isset($flight['return_flight_data']) && !empty($flight['return_flight_data'])) {
                  $return_data = json_decode($flight['return_flight_data'], true);
                  if (isset($return_data['has_return']) && $return_data['has_return'] == 1) {
                    $has_return = true;
                  }
                }
              ?>
                <div class="bg-white rounded-lg shadow mb-4 overflow-hidden">
                  <div class="bg-gradient-to-r from-green-500 to-green-600 px-4 py-2 flex justify-between items-center">
                    <div class="text-white font-bold"><?php echo htmlspecialchars($flight['airline_name']); ?></div>
                    <span class="px-2 py-1 rounded-full text-xs text-white bg-opacity-80 
                  <?php
                  switch ($flight['flight_status']) {
                    case 'upcoming':
                      echo 'bg-yellow-500';
                      break;
                    case 'in-progress':
                      echo 'bg-green-700';
                      break;
                    case 'completed':
                      echo 'bg-gray-600';
                      break;
                    case 'cancelled':
                      echo 'bg-red-500';
                      break;
                    default:
                      echo 'bg-blue-700';
                  }
                  ?>">
                      <?php echo ucfirst($flight['flight_status']); ?>
                    </span>
                  </div>
                  <div class="p-4">
                    <!-- Similar structure with return flight info -->
                    <div class="flex justify-between mb-3">
                      <div>
                        <div class="text-xs text-gray-500">Flight</div>
                        <div class="font-medium"><?php echo htmlspecialchars($flight['flight_number']); ?></div>
                      </div>
                      <div>
                        <div class="text-xs text-gray-500">Type</div>
                        <div class="font-medium"><?php echo $has_return ? 'Round Trip' : 'One Way'; ?></div>
                      </div>
                    </div>

                    <!-- Rest of the card with similar structure -->
                    <button onclick="viewFlightDetails(<?php echo $flight['id']; ?>)"
                      class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      View Details
                    </button>
                  </div>
                </div>
              <?php
              }
              ?>
            </div>
          <?php } else { ?>
            <div class="bg-gray-50 rounded-lg p-8 text-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
              </svg>
              <p class="text-xl text-gray-600 mb-4">You haven't booked any flights yet.</p>
              <p class="text-gray-500 mb-6">Start your journey by booking your first flight!</p>
              <a href="book-flight.php" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200 transform hover:scale-105 shadow-md">
                Book Your First Flight
              </a>
            </div>
          <?php } ?>
        </div>
      </div>

      <!-- Hotels Tab Content -->
      <div id="hotels" class="tab-content">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
          <h2 class="text-2xl font-bold mb-4">Your Hotel Bookings</h2>

          <?php if ($hotel_bookings->num_rows > 0) { ?>
            <div class="overflow-x-auto rounded-lg shadow">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-blue-600 to-blue-700">
                  <tr>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Hotel</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Location</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Room</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Check-in</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Check-out</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Price/Night</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Rating</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <?php while ($hotel = $hotel_bookings->fetch_assoc()) { ?>
                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($hotel['hotel_name']); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo ucfirst(htmlspecialchars($hotel['location'])); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($hotel['room_id']); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($hotel['check_in_date']); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($hotel['check_out_date']); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                      <?php
                      switch ($hotel['booking_status']) {
                        case 'pending':
                          echo 'bg-yellow-100 text-yellow-800';
                          break;
                        case 'confirmed':
                          echo 'bg-green-100 text-green-800';
                          break;
                        case 'cancelled':
                          echo 'bg-red-100 text-red-800';
                          break;
                        case 'completed':
                          echo 'bg-gray-100 text-gray-800';
                          break;
                      }
                      ?>">
                          <?php echo ucfirst($hotel['booking_status']); ?>
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-green-600">$<?php echo htmlspecialchars($hotel['price_per_night']); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                          <?php
                          $rating = (int)$hotel['rating'];
                          for ($i = 0; $i < $rating; $i++) {
                            echo '<span class="text-yellow-400">★</span>';
                          }
                          for ($i = $rating; $i < 5; $i++) {
                            echo '<span class="text-gray-300">★</span>';
                          }
                          ?>
                        </div>
                      </td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>

            <!-- Mobile view for hotels -->
            <div class="block md:hidden mt-6">
              <?php
              $hotel_bookings->data_seek(0);
              while ($hotel = $hotel_bookings->fetch_assoc()) {
              ?>
                <div class="bg-white rounded-lg shadow mb-4 overflow-hidden">
                  <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-4 py-2">
                    <div class="text-white font-bold"><?php echo htmlspecialchars($hotel['hotel_name']); ?></div>
                  </div>
                  <div class="p-4">
                    <div class="flex justify-between mb-3">
                      <div>
                        <div class="text-xs text-gray-500">Location</div>
                        <div class="font-medium"><?php echo ucfirst(htmlspecialchars($hotel['location'])); ?></div>
                      </div>
                      <div>
                        <div class="text-xs text-gray-500">Room</div>
                        <div class="font-medium"><?php echo htmlspecialchars($hotel['room_id']); ?></div>
                      </div>
                    </div>

                    <div class="flex justify-between mb-3">
                      <div>
                        <div class="text-xs text-gray-500">Check-in</div>
                        <div class="font-medium"><?php echo htmlspecialchars($hotel['check_in_date']); ?></div>
                      </div>
                      <div>
                        <div class="text-xs text-gray-500">Check-out</div>
                        <div class="font-medium"><?php echo htmlspecialchars($hotel['check_out_date']); ?></div>
                      </div>
                    </div>

                    <div class="flex justify-between mb-3">
                      <div>
                        <div class="text-xs text-gray-500">Price/Night</div>
                        <div class="font-medium text-green-600">$<?php echo htmlspecialchars($hotel['price_per_night']); ?></div>
                      </div>
                      <div>
                        <div class="text-xs text-gray-500">Rating</div>
                        <div class="flex">
                          <?php
                          $rating = (int)$hotel['rating'];
                          for ($i = 0; $i < $rating; $i++) {
                            echo '<span class="text-yellow-400">★</span>';
                          }
                          for ($i = $rating; $i < 5; $i++) {
                            echo '<span class="text-gray-300">★</span>';
                          }
                          ?>
                        </div>
                      </div>
                    </div>

                    <div class="mt-4 flex justify-center">
                      <span class="px-4 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                    <?php
                    switch ($hotel['booking_status']) {
                      case 'pending':
                        echo 'bg-yellow-100 text-yellow-800';
                        break;
                      case 'confirmed':
                        echo 'bg-green-100 text-green-800';
                        break;
                      case 'cancelled':
                        echo 'bg-red-100 text-red-800';
                        break;
                      case 'completed':
                        echo 'bg-gray-100 text-gray-800';
                        break;
                    }
                    ?>">
                        <?php echo ucfirst($hotel['booking_status']); ?>
                      </span>
                    </div>
                  </div>
                </div>
              <?php } ?>
            </div>
          <?php } else { ?>
            <div class="bg-gray-50 rounded-lg p-8 text-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
              <p class="text-xl text-gray-600 mb-4">No hotel bookings yet</p>
              <p class="text-gray-500 mb-6">Find your perfect stay for your next trip</p>
              <a href="book-hotel.php" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200 transform hover:scale-105 shadow-md">
                Book a Hotel
              </a>
            </div>
          <?php } ?>
        </div>
      </div>

      <!-- Transportation Tab Content -->
      <div id="transport" class="tab-content">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
          <h2 class="text-2xl font-bold mb-4">Your Transportation Bookings</h2>

          <?php if ($transport_bookings->num_rows > 0) { ?>
            <div class="overflow-x-auto rounded-lg shadow">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-blue-600 to-blue-700">
                  <tr>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Booking Ref</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Service Type</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Route</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Vehicle</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Date & Time</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Price</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Driver</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <?php while ($transport = $transport_bookings->fetch_assoc()) { ?>
                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($transport['booking_reference']); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo ucfirst(htmlspecialchars($transport['service_type'])); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                          <?php
                          if (!empty($transport['route_name'])) {
                            echo htmlspecialchars($transport['route_name']);
                          } else {
                            echo htmlspecialchars($transport['pickup_location'] . ' to ' . $transport['dropoff_location']);
                          }
                          ?>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($transport['vehicle_name'] . ' (' . ucfirst($transport['vehicle_type']) . ')'); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($transport['booking_date'] . ' ' . $transport['booking_time']); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-green-600">$<?php echo htmlspecialchars($transport['price']); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                      <?php
                      switch ($transport['booking_status']) {
                        case 'pending':
                          echo 'bg-yellow-100 text-yellow-800';
                          break;
                        case 'confirmed':
                          echo 'bg-blue-100 text-blue-800';
                          break;
                        case 'completed':
                          echo 'bg-green-100 text-green-800';
                          break;
                        case 'cancelled':
                          echo 'bg-red-100 text-red-800';
                          break;
                      }
                      ?>">
                          <?php echo ucfirst($transport['booking_status']); ?>
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                          <?php
                          if (!empty($transport['driver_name'])) {
                            echo htmlspecialchars($transport['driver_name']);
                            if (!empty($transport['driver_contact'])) {
                              echo '<br><span class="text-xs text-gray-500">' . htmlspecialchars($transport['driver_contact']) . '</span>';
                            }
                          } else {
                            echo '<span class="text-gray-400">Not assigned yet</span>';
                          }
                          ?>
                        </div>
                      </td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>

            <!-- Mobile view for transportation -->
            <div class="block md:hidden mt-6">
              <?php
              $transport_bookings->data_seek(0);
              while ($transport = $transport_bookings->fetch_assoc()) {
              ?>
                <div class="bg-white rounded-lg shadow mb-4 overflow-hidden">
                  <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-4 py-2 flex justify-between items-center">
                    <div class="text-white font-bold"><?php echo htmlspecialchars($transport['booking_reference']); ?></div>
                    <span class="px-2 py-1 rounded-full text-xs text-white bg-opacity-80 
                  <?php
                  switch ($transport['booking_status']) {
                    case 'pending':
                      echo 'bg-yellow-500';
                      break;
                    case 'confirmed':
                      echo 'bg-blue-700';
                      break;
                    case 'completed':
                      echo 'bg-green-500';
                      break;
                    case 'cancelled':
                      echo 'bg-red-500';
                      break;
                  }
                  ?>">
                      <?php echo ucfirst($transport['booking_status']); ?>
                    </span>
                  </div>
                  <div class="p-4">
                    <div class="grid grid-cols-2 gap-3">
                      <div>
                        <div class="text-xs text-gray-500">Service Type</div>
                        <div class="font-medium"><?php echo ucfirst(htmlspecialchars($transport['service_type'])); ?></div>
                      </div>
                      <div>
                        <div class="text-xs text-gray-500">Vehicle</div>
                        <div class="font-medium"><?php echo htmlspecialchars($transport['vehicle_name'] . ' (' . ucfirst($transport['vehicle_type']) . ')'); ?></div>
                      </div>
                      <div>
                        <div class="text-xs text-gray-500">Route</div>
                        <div class="font-medium">
                          <?php
                          if (!empty($transport['route_name'])) {
                            echo htmlspecialchars($transport['route_name']);
                          } else {
                            echo htmlspecialchars($transport['pickup_location'] . ' to ' . $transport['dropoff_location']);
                          }
                          ?>
                        </div>
                      </div>
                      <div>
                        <div class="text-xs text-gray-500">Date & Time</div>
                        <div class="font-medium"><?php echo htmlspecialchars($transport['booking_date'] . ' ' . $transport['booking_time']); ?></div>
                      </div>
                      <div>
                        <div class="text-xs text-gray-500">Price</div>
                        <div class="font-medium text-green-600">$<?php echo htmlspecialchars($transport['price']); ?></div>
                      </div>
                      <div>
                        <div class="text-xs text-gray-500">Driver</div>
                        <div class="font-medium">
                          <?php
                          if (!empty($transport['driver_name'])) {
                            echo htmlspecialchars($transport['driver_name']);
                            if (!empty($transport['driver_contact'])) {
                              echo '<br><span class="text-xs text-gray-500">' . htmlspecialchars($transport['driver_contact']) . '</span>';
                            }
                          } else {
                            echo '<span class="text-gray-400">Not assigned yet</span>';
                          }
                          ?>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              <?php } ?>
            </div>
          <?php } else { ?>
            <div class="bg-gray-50 rounded-lg p-8 text-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
              </svg>
              <p class="text-xl text-gray-600 mb-4">No transportation bookings</p>
              <p class="text-gray-500 mb-6">Book transportation to get around during your trip</p>
              <a href="book-transport.php" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200 transform hover:scale-105 shadow-md">
                Book Transportation
              </a>
            </div>
          <?php } ?>
        </div>
      </div>


      <!-- Packages Tab Content -->
      <div id="packages" class="tab-content">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
          <h2 class="text-2xl font-bold mb-4">Your Package Bookings</h2>

          <?php if ($package_bookings->num_rows > 0) { ?>
            <!-- Stats cards for package bookings -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
              <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-4 text-white">
                <div class="text-lg opacity-80 mb-1">Total Packages</div>
                <div class="text-3xl font-bold">
                  <?php echo $package_bookings->num_rows; ?>
                </div>
              </div>
              <div class="bg-gradient-to-r from-blue-400 to-blue-500 rounded-lg p-4 text-white">
                <div class="text-lg opacity-80 mb-1">Upcoming</div>
                <div class="text-3xl font-bold">
                  <?php
                  $upcoming = 0;
                  $package_bookings->data_seek(0);

                  while ($package = $package_bookings->fetch_assoc()) {
                    if ($package['booking_status'] == 'pending' || $package['booking_status'] == 'confirmed') $upcoming++;
                  }

                  $package_bookings->data_seek(0);
                  echo $upcoming;
                  ?>
                </div>
              </div>
              <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-4 text-white">
                <div class="text-lg opacity-80 mb-1">Paid</div>
                <div class="text-3xl font-bold">
                  <?php
                  $paid = 0;
                  $package_bookings->data_seek(0);

                  while ($package = $package_bookings->fetch_assoc()) {
                    if ($package['payment_status'] == 'paid') $paid++;
                  }

                  $package_bookings->data_seek(0);
                  echo $paid;
                  ?>
                </div>
              </div>
            </div>

            <div class="overflow-x-auto rounded-lg shadow">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-purple-600 to-purple-700">
                  <tr>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Package</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Type</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Travel Info</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Date</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Price</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Assigned</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <?php
                  $package_bookings->data_seek(0);
                  while ($package = $package_bookings->fetch_assoc()) {
                  ?>
                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($package['package_title']); ?></div>
                        <div class="text-xs text-gray-500">ID: <?php echo htmlspecialchars($package['booking_id']); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($package['package_type']); ?></div>
                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($package['flight_class']); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($package['departure_city'] . ' → ' . $package['arrival_city']); ?></div>
                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($package['airline']); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                          <?php echo htmlspecialchars(date('M d, Y', strtotime($package['departure_date']))); ?>
                        </div>
                        <div class="text-xs text-gray-500">
                          <?php
                          if (!empty($package['return_date'])) {
                            echo 'Return: ' . htmlspecialchars(date('M d, Y', strtotime($package['return_date'])));
                          } else {
                            echo 'One Way';
                          }
                          ?>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-green-600">$<?php echo htmlspecialchars($package['price']); ?></div>
                        <div class="text-xs text-gray-500">
                          <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                    <?php echo $package['payment_status'] == 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                            <?php echo ucfirst($package['payment_status']); ?>
                          </span>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                  <?php
                    switch ($package['booking_status']) {
                      case 'pending':
                        echo 'bg-yellow-100 text-yellow-800';
                        break;
                      case 'confirmed':
                        echo 'bg-blue-100 text-blue-800';
                        break;
                      case 'canceled':
                        echo 'bg-red-100 text-red-800';
                        break;
                    }
                  ?>">
                          <?php echo ucfirst($package['booking_status']); ?>
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                          <?php
                          $assignments = [];

                          if (!empty($package['hotel_id'])) {
                            $assignments[] = 'Hotel: ' . htmlspecialchars($package['hotel_name'] ?? 'Assigned');
                          }

                          if (!empty($package['flight_id'])) {
                            $seat_info = '';
                            if (!empty($package['seat_type']) || !empty($package['seat_number'])) {
                              $seat_info = ' (' . htmlspecialchars(ucfirst($package['seat_type'] ?? '')) .
                                (!empty($package['seat_number']) ? ' - ' . $package['seat_number'] : '') . ')';
                            }
                            $assignments[] = 'Flight' . $seat_info;
                          }

                          if (!empty($package['transport_id'])) {
                            $seat_info = !empty($package['transport_seat_number']) ?
                              ' (Seat: ' . htmlspecialchars($package['transport_seat_number']) . ')' : '';
                            $assignments[] = 'Transport' . $seat_info;
                          }

                          if (empty($assignments)) {
                            echo '<span class="text-orange-500 text-xs">Not assigned yet</span>';
                          } else {
                            echo '<ul class="list-disc list-inside text-xs">';
                            foreach ($assignments as $assignment) {
                              echo '<li>' . $assignment . '</li>';
                            }
                            echo '</ul>';
                          }
                          ?>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="viewPackageDetails(<?php echo $package['booking_id']; ?>)"
                          class="bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition duration-200 shadow-sm">
                          View Details
                        </button>
                      </td>
                    </tr>
                  <?php
                  }
                  ?>
                </tbody>
              </table>
            </div>

            <!-- Mobile view for packages -->
            <div class="block md:hidden mt-6">
              <?php
              $package_bookings->data_seek(0);
              while ($package = $package_bookings->fetch_assoc()) {
              ?>
                <div class="bg-white rounded-lg shadow mb-4 overflow-hidden">
                  <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-4 py-2 flex justify-between items-center">
                    <div class="text-white font-bold"><?php echo htmlspecialchars($package['package_title']); ?></div>
                    <span class="px-2 py-1 rounded-full text-xs text-white bg-opacity-80 
                <?php
                switch ($package['booking_status']) {
                  case 'pending':
                    echo 'bg-yellow-500';
                    break;
                  case 'confirmed':
                    echo 'bg-blue-500';
                    break;
                  case 'canceled':
                    echo 'bg-red-500';
                    break;
                }
                ?>">
                      <?php echo ucfirst($package['booking_status']); ?>
                    </span>
                  </div>
                  <div class="p-4">
                    <div class="flex justify-between mb-3">
                      <div>
                        <div class="text-xs text-gray-500">Package Type</div>
                        <div class="font-medium"><?php echo htmlspecialchars($package['package_type']); ?></div>
                      </div>
                      <div>
                        <div class="text-xs text-gray-500">Price</div>
                        <div class="font-medium text-green-600">$<?php echo htmlspecialchars($package['price']); ?></div>
                      </div>
                    </div>

                    <div class="flex items-center mb-3">
                      <div class="w-5 h-5 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                      </div>
                      <div class="flex-1">
                        <div class="text-sm"><?php echo htmlspecialchars($package['departure_city'] . ' → ' . $package['arrival_city']); ?></div>
                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($package['airline'] . ' - ' . $package['flight_class']); ?></div>
                      </div>
                    </div>

                    <div class="flex items-center mb-3">
                      <div class="w-5 h-5 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                      </div>
                      <div class="flex-1">
                        <div class="text-sm"><?php echo htmlspecialchars(date('M d, Y', strtotime($package['departure_date']))); ?>
                          <?php if (!empty($package['departure_time'])) echo htmlspecialchars(date('g:i A', strtotime($package['departure_time']))); ?>
                        </div>
                        <?php if (!empty($package['return_date'])) { ?>
                          <div class="text-xs text-gray-500">
                            Return: <?php echo htmlspecialchars(date('M d, Y', strtotime($package['return_date']))); ?>
                            <?php if (!empty($package['return_time'])) echo htmlspecialchars(date('g:i A', strtotime($package['return_time']))); ?>
                          </div>
                        <?php } ?>
                      </div>
                    </div>

                    <div class="flex items-center mb-4">
                      <div class="w-5 h-5 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                      </div>
                      <div class="flex-1">
                        <div class="flex items-center">
                          <span class="text-sm mr-2">Payment: </span>
                          <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                    <?php echo $package['payment_status'] == 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                            <?php echo ucfirst($package['payment_status']); ?>
                          </span>
                        </div>
                      </div>
                    </div>

                    <!-- Assignments Section -->
                    <?php if (!empty($package['hotel_id']) || !empty($package['flight_id']) || !empty($package['transport_id'])) { ?>
                      <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                        <div class="text-sm font-medium text-gray-700 mb-2">Assignments:</div>
                        <ul class="text-xs text-gray-600 space-y-1">
                          <?php if (!empty($package['hotel_id'])) { ?>
                            <li class="flex items-center">
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                              </svg>
                              Hotel: <?php echo htmlspecialchars($package['hotel_name'] ?? 'Assigned'); ?>
                              <?php if (!empty($package['hotel_location'])) echo ' (' . htmlspecialchars(ucfirst($package['hotel_location'])) . ')'; ?>
                            </li>
                          <?php } ?>
                          <?php if (!empty($package['flight_id'])) { ?>
                            <li class="flex items-center">
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                              </svg>
                              Flight
                              <?php
                              if (!empty($package['seat_type']) || !empty($package['seat_number'])) {
                                echo ': ' . htmlspecialchars(ucfirst($package['seat_type'] ?? ''));
                                if (!empty($package['seat_number'])) echo ' - ' . htmlspecialchars($package['seat_number']);
                              }
                              ?>
                            </li>
                          <?php } ?>
                          <?php if (!empty($package['transport_id'])) { ?>
                            <li class="flex items-center">
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                              </svg>
                              Transport
                              <?php if (!empty($package['transport_seat_number'])) echo ': Seat ' . htmlspecialchars($package['transport_seat_number']); ?>
                            </li>
                          <?php } ?>
                        </ul>
                      </div>
                    <?php } ?>

                    <button onclick="viewPackageDetails(<?php echo $package['booking_id']; ?>)"
                      class="w-full bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      View Details
                    </button>
                  </div>
                </div>
              <?php } ?>
            </div>
          <?php } else { ?>
            <div class="bg-gray-50 rounded-lg p-8 text-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
              </svg>
              <p class="text-xl text-gray-600 mb-4">No package bookings found</p>
              <p class="text-gray-500 mb-6">Book a complete travel package for a hassle-free journey!</p>
              <a href="packages.php" class="mt-4 inline-block bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200 transform hover:scale-105 shadow-md">
                Book a Package
              </a>
            </div>
          <?php } ?>
        </div>
      </div>
    </div>
  </div>
  <script>
    // Tab switching
    document.querySelectorAll('.tab').forEach(tab => {
      tab.addEventListener('click', function() {
        // Remove active class from all tabs and tab contents
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

        // Add active class to clicked tab and its content
        this.classList.add('active');
        document.getElementById(this.dataset.target).classList.add('active');
      });
    });

    // Flight countdown function
    function updateCountdown() {
      document.querySelectorAll('.countdown, .return-countdown').forEach(timer => {
        const departureDateStr = timer.dataset.departure;
        const departureDate = new Date(departureDateStr);
        const now = new Date();
        const timeLeft = departureDate - now;

        if (timeLeft <= 0) {
          timer.innerHTML = 'Departed';
          return;
        }

        const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
        const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

        timer.innerHTML = `${days}d ${hours}h ${minutes}m ${seconds}s`;
      });
    }

    // Update countdown every second
    setInterval(updateCountdown, 1000);
    updateCountdown(); // Initial call

    // View Flight Details
    function viewFlightDetails(flightId) {
      const modal = document.getElementById('flightDetailsModal');
      const contentDiv = document.getElementById('flightDetailsContent');

      modal.style.display = 'flex';

      // Fetch flight details via AJAX
      fetch(`get_flight_details.php?flight_id=${flightId}`)
        .then(response => response.text())
        .then(data => {
          contentDiv.innerHTML = data;
        })
        .catch(error => {
          contentDiv.innerHTML = `
                        <div class="bg-red-100 p-4 rounded-lg text-red-700">
                            <p>Error loading flight details. Please try again later.</p>
                        </div>
                    `;
          console.error('Error fetching flight details:', error);
        });
    }

    // Close modal
    function closeModal() {
      const modal = document.getElementById('flightDetailsModal');
      modal.style.display = 'none';
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
      const modal = document.getElementById('flightDetailsModal');
      if (event.target === modal) {
        modal.style.display = 'none';
      }
    }
    // View Package Details Function
    function viewPackageDetails(packageId) {
      const modal = document.getElementById('flightDetailsModal');
      const contentDiv = document.getElementById('flightDetailsContent');

      // Change the modal title
      const modalTitle = modal.querySelector('h2');
      if (modalTitle) {
        modalTitle.textContent = 'Package Details';
      }

      modal.style.display = 'flex';

      // Fetch package details via AJAX
      fetch(`get_package_details.php?package_id=${packageId}`)
        .then(response => response.text())
        .then(data => {
          contentDiv.innerHTML = data;
        })
        .catch(error => {
          contentDiv.innerHTML = `
        <div class="bg-red-100 p-4 rounded-lg text-red-700">
          <p>Error loading package details. Please try again later.</p>
        </div>
      `;
          console.error('Error fetching package details:', error);
        });
    }
  </script>
</body>

</html>