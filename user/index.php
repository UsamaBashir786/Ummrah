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

// Get current date for comparison
$current_date = date('Y-m-d');

// Fetch counts for booking stats
// 1. Flight Bookings
$flight_sql = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN booking_status = 'upcoming' OR 
                 (CURDATE() < departure_date AND booking_status != 'cancelled') 
            THEN 1 ELSE 0 END) as upcoming
    FROM (
        SELECT 
            f.departure_date, 
            CASE 
                WHEN CURDATE() < f.departure_date THEN 'upcoming'
                WHEN CURDATE() = f.departure_date THEN 'in-progress'
                ELSE 'completed'
            END as booking_status
        FROM flights f 
        INNER JOIN flight_bookings fb ON f.id = fb.flight_id 
        WHERE fb.user_id = ?
        UNION ALL
        SELECT 
            f.departure_date, 
            fb.flight_status as booking_status
        FROM flights f 
        INNER JOIN flight_book fb ON f.id = fb.flight_id 
        WHERE fb.user_id = ?
        UNION ALL
        SELECT 
            f.departure_date, 
            CASE 
                WHEN fa.status = 'assigned' THEN 'upcoming'
                WHEN fa.status = 'completed' THEN 'completed'
                WHEN fa.status = 'cancelled' THEN 'cancelled'
                ELSE 'upcoming'
            END as booking_status
        FROM flights f 
        INNER JOIN flight_assign fa ON f.id = fa.flight_id 
        INNER JOIN package_booking pb ON fa.booking_id = pb.id
        WHERE fa.user_id = ?
    ) AS all_flights
";
$flight_stmt = $conn->prepare($flight_sql);
$flight_stmt->bind_param("iii", $user_id, $user_id, $user_id);
$flight_stmt->execute();
$flight_result = $flight_stmt->get_result()->fetch_assoc();
$total_flights = $flight_result['total'] ?? 0;
$upcoming_flights = $flight_result['upcoming'] ?? 0;

// 2. Hotel Bookings
$hotel_sql = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN check_in_date > CURDATE() AND status != 'cancelled' THEN 1 ELSE 0 END) as upcoming
    FROM hotel_bookings
    WHERE user_id = ?
";
$hotel_stmt = $conn->prepare($hotel_sql);
$hotel_stmt->bind_param("i", $user_id);
$hotel_stmt->execute();
$hotel_result = $hotel_stmt->get_result()->fetch_assoc();
$total_hotels = $hotel_result['total'] ?? 0;
$upcoming_hotels = $hotel_result['upcoming'] ?? 0;

// 3. Transportation Bookings
$transport_sql = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN booking_date > CURDATE() AND booking_status != 'cancelled' THEN 1 ELSE 0 END) as upcoming
    FROM transportation_bookings
    WHERE user_id = ?
";
$transport_stmt = $conn->prepare($transport_sql);
$transport_stmt->bind_param("i", $user_id);
$transport_stmt->execute();
$transport_result = $transport_stmt->get_result()->fetch_assoc();
$total_transports = $transport_result['total'] ?? 0;
$upcoming_transports = $transport_result['upcoming'] ?? 0;

// 4. Package Bookings
$package_sql = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status IN ('pending', 'confirmed') THEN 1 ELSE 0 END) as upcoming
    FROM package_booking
    WHERE user_id = ?
";
$package_stmt = $conn->prepare($package_sql);
$package_stmt->bind_param("i", $user_id);
$package_stmt->execute();
$package_result = $package_stmt->get_result()->fetch_assoc();
$total_packages = $package_result['total'] ?? 0;
$upcoming_packages = $package_result['upcoming'] ?? 0;

// Get upcoming trips (combining flights, hotels, and packages within next 30 days)
$upcoming_sql = "
    (SELECT 
        'flight' as type,
        f.departure_date as trip_date,
        f.departure_time as trip_time,
        f.airline_name as provider,
        CONCAT(f.departure_city, ' to ', f.arrival_city) as destination,
        f.flight_number as reference,
        fb.id as booking_id
    FROM flights f 
    INNER JOIN flight_bookings fb ON f.id = fb.flight_id 
    WHERE fb.user_id = ? AND f.departure_date >= CURDATE() AND f.departure_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY))
    
    UNION ALL
    
    (SELECT 
        'flight' as type,
        f.departure_date as trip_date,
        f.departure_time as trip_time,
        f.airline_name as provider,
        CONCAT(f.departure_city, ' to ', f.arrival_city) as destination,
        f.flight_number as reference,
        fb.id as booking_id
    FROM flights f 
    INNER JOIN flight_book fb ON f.id = fb.flight_id 
    WHERE fb.user_id = ? AND f.departure_date >= CURDATE() AND f.departure_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY))
    
    UNION ALL
    
    (SELECT 
        'hotel' as type,
        hb.check_in_date as trip_date,
        '' as trip_time,
        h.hotel_name as provider,
        h.location as destination,
        hb.id as reference,
        hb.id as booking_id
    FROM hotels h
    INNER JOIN hotel_bookings hb ON h.id = hb.hotel_id
    WHERE hb.user_id = ? AND hb.check_in_date >= CURDATE() AND hb.check_in_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY))
    
    UNION ALL
    
    (SELECT 
        'package' as type,
        p.departure_date as trip_date,
        p.departure_time as trip_time,
        p.title as provider,
        CONCAT(p.departure_city, ' to ', p.arrival_city) as destination,
        pb.id as reference,
        pb.id as booking_id
    FROM packages p
    INNER JOIN package_booking pb ON p.id = pb.package_id
    WHERE pb.user_id = ? AND p.departure_date >= CURDATE() AND p.departure_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY))
    
    ORDER BY trip_date ASC, trip_time ASC
    LIMIT 5
";
$upcoming_stmt = $conn->prepare($upcoming_sql);
$upcoming_stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$upcoming_stmt->execute();
$upcoming_trips = $upcoming_stmt->get_result();

// Get recent booking activities
$activity_sql = "
    (SELECT 
        'Flight booking' as activity_type,
        CONCAT('Booked flight from ', f.departure_city, ' to ', f.arrival_city) as description,
        fb.booking_date as activity_date,
        'flight' as icon
    FROM flights f 
    INNER JOIN flight_bookings fb ON f.id = fb.flight_id 
    WHERE fb.user_id = ?)
    
    UNION ALL
    
    (SELECT 
        'Flight booking' as activity_type,
        CONCAT('Booked flight from ', f.departure_city, ' to ', f.arrival_city) as description,
        fb.booking_time as activity_date,
        'flight' as icon
    FROM flights f 
    INNER JOIN flight_book fb ON f.id = fb.flight_id 
    WHERE fb.user_id = ?)
    
    UNION ALL
    
    (SELECT 
        'Hotel reservation' as activity_type,
        CONCAT('Reserved at ', h.hotel_name, ' in ', h.location) as description,
        hb.check_in_date as activity_date,
        'hotel' as icon
    FROM hotels h
    INNER JOIN hotel_bookings hb ON h.id = hb.hotel_id
    WHERE hb.user_id = ?)
    
    UNION ALL
    
    (SELECT 
        'Transportation booking' as activity_type,
        CONCAT('Booked ', tb.vehicle_name, ' transportation') as description,
        tb.booking_date as activity_date,
        'transport' as icon
    FROM transportation_bookings tb
    WHERE tb.user_id = ?)
    
    UNION ALL
    
    (SELECT 
        'Package booking' as activity_type,
        CONCAT('Booked travel package: ', p.title) as description,
        pb.booking_date as activity_date,
        'package' as icon
    FROM packages p
    INNER JOIN package_booking pb ON p.id = pb.package_id
    WHERE pb.user_id = ?)
    
    ORDER BY activity_date DESC
    LIMIT 10
";
$activity_stmt = $conn->prepare($activity_sql);
$activity_stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
$activity_stmt->execute();
$recent_activities = $activity_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include '../includes/css-links.php' ?>
  <link rel="stylesheet" href="../assets/css/output.css">
  <title>Dashboard | Travel Agency</title>
  <style>
    .stat-card {
      transition: all 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .quick-action {
      transition: all 0.3s ease;
    }

    .quick-action:hover {
      transform: scale(1.05);
    }

    .trip-item {
      transition: all 0.2s ease;
    }

    .trip-item:hover {
      background-color: #F9FAFB;
      transform: translateX(5px);
    }

    .activity-item {
      transition: all 0.2s ease;
    }

    .activity-item:hover {
      background-color: #F9FAFB;
    }

    .countdown {
      font-variant-numeric: tabular-nums;
    }

    /* Weather widget styling */
    .weather-widget {
      background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
      color: white;
      border-radius: 1rem;
      overflow: hidden;
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    @media (max-width: 768px) {
      .dashboard-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body class="bg-gray-100">
  <?php include 'includes/sidebar.php'; ?>

  <!-- Mobile Menu Button -->
  <div class="md:hidden fixed top-4 left-4 z-50">
    <button id="sidebarToggle" class="bg-white p-2 rounded-full shadow-md">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
      </svg>
    </button>
  </div>

  <div class="main-content p-4 sm:p-8">
    <div class="container mx-auto">
      <!-- Welcome Section -->
      <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-800">Welcome back, <?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?>!</h1>
          <p class="text-gray-600">Here's an overview of your travel plans and bookings.</p>
        </div>
        <div class="mt-4 md:mt-0">
          <div class="bg-white rounded-lg shadow-md px-4 py-3 flex items-center">
            <div class="mr-3">
              <div class="text-xs text-gray-500">Today's Date</div>
              <div class="font-medium"><?php echo date('F d, Y'); ?></div>
            </div>
            <div class="h-10 w-px bg-gray-200 mx-2"></div>
            <div class="ml-3">
              <div class="text-xs text-gray-500">Local Time</div>
              <div class="font-medium" id="currentTime">--:--:-- --</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats Overview -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-md p-4 text-white stat-card">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-blue-100">Flight Bookings</p>
              <div class="text-3xl font-bold mt-2"><?php echo $total_flights; ?></div>
              <div class="text-sm mt-2"><?php echo $upcoming_flights; ?> upcoming</div>
            </div>
            <div class="rounded-full bg-white bg-opacity-20 p-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
              </svg>
            </div>
          </div>
          <div class="mt-3">
            <a href="bookings-flights.php" class="text-xs text-blue-100 flex items-center hover:text-white">
              View Details
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </a>
          </div>
        </div>

        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-md p-4 text-white stat-card">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-green-100">Hotel Bookings</p>
              <div class="text-3xl font-bold mt-2"><?php echo $total_hotels; ?></div>
              <div class="text-sm mt-2"><?php echo $upcoming_hotels; ?> upcoming</div>
            </div>
            <div class="rounded-full bg-white bg-opacity-20 p-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
          </div>
          <div class="mt-3">
            <a href="bookings-hotels.php" class="text-xs text-green-100 flex items-center hover:text-white">
              View Details
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </a>
          </div>
        </div>

        <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-lg shadow-md p-4 text-white stat-card">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-yellow-100">Transportation</p>
              <div class="text-3xl font-bold mt-2"><?php echo $total_transports; ?></div>
              <div class="text-sm mt-2"><?php echo $upcoming_transports; ?> upcoming</div>
            </div>
            <div class="rounded-full bg-white bg-opacity-20 p-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
              </svg>
            </div>
          </div>
          <div class="mt-3">
            <a href="bookings-transport.php" class="text-xs text-yellow-100 flex items-center hover:text-white">
              View Details
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </a>
          </div>
        </div>

        <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg shadow-md p-4 text-white stat-card">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-purple-100">Package Bookings</p>
              <div class="text-3xl font-bold mt-2"><?php echo $total_packages; ?></div>
              <div class="text-sm mt-2"><?php echo $upcoming_packages; ?> upcoming</div>
            </div>
            <div class="rounded-full bg-white bg-opacity-20 p-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
              </svg>
            </div>
          </div>
          <div class="mt-3">
            <a href="bookings-packages.php" class="text-xs text-purple-100 flex items-center hover:text-white">
              View Details
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </a>
          </div>
        </div>
      </div>

      <!-- Quick Actions and Upcoming Trips -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 dashboard-grid">
        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-xl font-bold text-gray-800 mb-4">Quick Actions</h2>
          <div class="grid grid-cols-2 gap-4">
            <a href="../flights.php" class="bg-blue-50 rounded-lg p-4 text-center hover:bg-blue-100 transition quick-action">
              <div class="rounded-full bg-blue-100 h-12 w-12 flex items-center justify-center mx-auto mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
              </div>
              <p class="text-sm font-medium text-gray-700">Book Flight</p>
            </a>
            <a href="../hotels.php" class="bg-green-50 rounded-lg p-4 text-center hover:bg-green-100 transition quick-action">
              <div class="rounded-full bg-green-100 h-12 w-12 flex items-center justify-center mx-auto mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
              </div>
              <p class="text-sm font-medium text-gray-700">Book Hotel</p>
            </a>
            <a href="../transportation.php" class="bg-yellow-50 rounded-lg p-4 text-center hover:bg-yellow-100 transition quick-action">
              <div class="rounded-full bg-yellow-100 h-12 w-12 flex items-center justify-center mx-auto mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
              </div>
              <p class="text-sm font-medium text-gray-700">Transportation</p>
            </a>
            <a href="../packages.php" class="bg-purple-50 rounded-lg p-4 text-center hover:bg-purple-100 transition quick-action">
              <div class="rounded-full bg-purple-100 h-12 w-12 flex items-center justify-center mx-auto mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                </svg>
              </div>
              <p class="text-sm font-medium text-gray-700">Travel Packages</p>
            </a>
          </div>

          <!-- Weather Widget Preview -->
          <div class="weather-widget mt-8 p-4">
            <div class="flex justify-between items-center">
              <div>
                <h3 class="font-medium text-white text-lg">Weather</h3>
                <p class="text-xs text-blue-100">Current location</p>
              </div>
              <div class="text-right">
                <div class="text-3xl font-bold">24°C</div>
                <div class="text-xs text-blue-100">Partly Cloudy</div>
              </div>
            </div>
            <div class="mt-4 flex justify-between text-center text-xs">
              <div>
                <div>Wed</div>
                <div class="text-sm">26°</div>
              </div>
              <div>
                <div>Thu</div>
                <div class="text-sm">24°</div>
              </div>
              <div>
                <div>Fri</div>
                <div class="text-sm">23°</div>
              </div>
              <div>
                <div>Sat</div>
                <div class="text-sm">25°</div>
              </div>
              <div>
                <div>Sun</div>
                <div class="text-sm">27°</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Upcoming Trips -->
        <div class="bg-white rounded-lg shadow-md p-6 lg:col-span-2">
          <h2 class="text-xl font-bold text-gray-800 mb-4">Upcoming Trips</h2>

          <?php if ($upcoming_trips->num_rows > 0): ?>
            <div class="divide-y">
              <?php while ($trip = $upcoming_trips->fetch_assoc()):
                $days_left = (strtotime($trip['trip_date']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
                $days_left = floor($days_left);

                // Determine label color based on trip type
                $label_color = '';
                switch ($trip['type']) {
                  case 'flight':
                    $label_color = 'bg-blue-100 text-blue-800';
                    $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>';
                    $detail_url = "viewFlightDetails(" . $trip["booking_id"] . ")";
                    break;
                  case 'hotel':
                    $label_color = 'bg-green-100 text-green-800';
                    $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>';
                    $detail_url = "viewHotelDetails(" . $trip["booking_id"] . ")";
                    break;
                  case 'package':
                    $label_color = 'bg-purple-100 text-purple-800';
                    $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>';
                    $detail_url = "viewPackageDetails(" . $trip["booking_id"] . ")";
                    break;
                  default:
                    $label_color = 'bg-gray-100 text-gray-800';
                    $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
                    $detail_url = "#";
                    break;
                }
              ?>
                <div class="py-4 trip-item">
                  <div class="flex items-start">
                    <div class="mr-4 flex-shrink-0">
                      <?php echo $icon; ?>
                    </div>
                    <div class="flex-1">
                      <div class="flex justify-between">
                        <div>
                          <h3 class="font-medium text-gray-800"><?php echo htmlspecialchars($trip['destination']); ?></h3>
                          <p class="text-sm text-gray-600"><?php echo htmlspecialchars($trip['provider']); ?></p>
                        </div>
                        <div class="text-right">
                          <span class="px-2 py-1 rounded-full text-xs <?php echo $label_color; ?>"><?php echo ucfirst($trip['type']); ?></span>
                          <?php if ($days_left == 0): ?>
                            <div class="mt-1 text-sm font-medium text-red-600">Today!</div>
                          <?php elseif ($days_left == 1): ?>
                            <div class="mt-1 text-sm font-medium text-orange-600">Tomorrow</div>
                          <?php else: ?>
                            <div class="mt-1 text-sm font-medium text-gray-600">In <?php echo $days_left; ?> days</div>
                          <?php endif; ?>
                        </div>
                      </div>
                      <div class="mt-2 flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                          <?php
                          echo date('D, M j, Y', strtotime($trip['trip_date']));
                          if (!empty($trip['trip_time'])) {
                            echo ' at ' . date('g:i A', strtotime($trip['trip_time']));
                          }
                          ?>
                        </div>
                        <div>
                          <?php if ($days_left <= 3): ?>
                            <div class="countdown text-sm bg-red-50 text-red-700 px-2 py-1 rounded">
                              <span id="countdown-<?php echo $trip['booking_id']; ?>"
                                data-date="<?php echo $trip['trip_date']; ?>"
                                data-time="<?php echo $trip['trip_time']; ?>">
                                --:--:--
                              </span>
                            </div>
                          <?php endif; ?>
                        </div>
                      </div>
                      <div class="mt-3">
                        <button onclick="<?php echo $detail_url; ?>" class="text-blue-600 text-sm hover:text-blue-800 transition">
                          <!-- View Details -->
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endwhile; ?>
            </div>
          <?php else: ?>
            <div class="bg-gray-50 rounded-lg p-8 text-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
              </svg>
              <p class="text-xl text-gray-600 mb-4">No upcoming trips</p>
              <p class="text-gray-500 mb-6">Start planning your journey today!</p>
              <a href="../packages.php" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200 transform hover:scale-105 shadow-md">
                Explore Travel Packages
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Recent Activities and Account Section -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Activities -->
        <div class="bg-white rounded-lg shadow-md p-6 lg:col-span-2">
          <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Activities</h2>

          <?php if ($recent_activities->num_rows > 0): ?>
            <div class="divide-y">
              <?php while ($activity = $recent_activities->fetch_assoc()):
                // Determine icon based on activity type
                $activity_icon = '';
                switch ($activity['icon']) {
                  case 'flight':
                    $activity_icon = '<div class="rounded-full bg-blue-100 p-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg></div>';
                    break;
                  case 'hotel':
                    $activity_icon = '<div class="rounded-full bg-green-100 p-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg></div>';
                    break;
                  case 'transport':
                    $activity_icon = '<div class="rounded-full bg-yellow-100 p-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg></div>';
                    break;
                  case 'package':
                    $activity_icon = '<div class="rounded-full bg-purple-100 p-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg></div>';
                    break;
                  default:
                    $activity_icon = '<div class="rounded-full bg-gray-100 p-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>';
                }
              ?>
                <div class="py-3 activity-item">
                  <div class="flex items-center">
                    <div class="mr-4 flex-shrink-0">
                      <?php echo $activity_icon; ?>
                    </div>
                    <div class="flex-1">
                      <div class="flex justify-between">
                        <div>
                          <p class="font-medium text-gray-800"><?php echo htmlspecialchars($activity['activity_type']); ?></p>
                          <p class="text-sm text-gray-600"><?php echo htmlspecialchars($activity['description']); ?></p>
                        </div>
                        <div class="text-right">
                          <p class="text-xs text-gray-500"><?php echo date('M j, g:i A', strtotime($activity['activity_date'])); ?></p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endwhile; ?>
            </div>
          <?php else: ?>
            <div class="bg-gray-50 rounded-lg p-6 text-center">
              <p class="text-gray-600">No recent activities to show.</p>
            </div>
          <?php endif; ?>
        </div>

        <!-- Account Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <div class="flex items-center mb-6">
            <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="w-16 h-16 rounded-full object-cover mr-4">
            <div>
              <h2 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($user['full_name']); ?></h2>
              <p class="text-sm text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
          </div>

          <div class="space-y-3">
            <a href="profile.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              <span class="text-gray-700">My Profile</span>
            </a>
            <a href="under-development.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
              <span class="text-gray-700">Payment Methods</span>
            </a>
            <a href="under-development.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              <span class="text-gray-700">Notifications</span>
              <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">3</span>
            </a>
            <a href="change-password.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
              </svg>
              <span class="text-gray-700">Change Password</span>
            </a>
          </div>

          <div class="mt-6 pt-6 border-t">
            <a href="../logout.php" class="w-full bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-lg transition flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              Logout
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Flight Details Modal -->
  <div id="flightDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
      <div class="flex justify-between items-center p-4 border-b">
        <h3 class="text-lg font-bold">Flight Details</h3>
        <button onclick="closeFlightModal()" class="text-gray-500 hover:text-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      <div id="flightDetailsContent" class="p-4">
        <div class="flex justify-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Hotel Details Modal -->
  <div id="hotelDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
      <div class="flex justify-between items-center p-4 border-b">
        <h3 class="text-lg font-bold">Hotel Details</h3>
        <button onclick="closeHotelModal()" class="text-gray-500 hover:text-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      <div id="hotelDetailsContent" class="p-4">
        <div class="flex justify-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-500"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Package Details Modal -->
  <div id="packageDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
      <div class="flex justify-between items-center p-4 border-b">
        <h3 class="text-lg font-bold">Package Details</h3>
        <button onclick="closePackageModal()" class="text-gray-500 hover:text-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      <div id="packageDetailsContent" class="p-4">
        <div class="flex justify-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500"></div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Update current time
    function updateCurrentTime() {
      const now = new Date();
      const timeString = now.toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      });
      document.getElementById('currentTime').textContent = timeString;
    }

    setInterval(updateCurrentTime, 1000);
    updateCurrentTime();

    // Countdown timer for upcoming trips
    function updateCountdowns() {
      document.querySelectorAll('[id^="countdown-"]').forEach(element => {
        const targetDate = element.getAttribute('data-date');
        const targetTime = element.getAttribute('data-time') || '00:00:00';

        const target = new Date(`${targetDate}T${targetTime}`);
        const now = new Date();

        const diff = target - now;

        if (diff <= 0) {
          element.innerHTML = 'Departed';
          return;
        }

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        if (days > 0) {
          element.innerHTML = `${days}d ${hours}h ${minutes}m`;
        } else {
          element.innerHTML = `${hours}h ${minutes}m ${seconds}s`;
        }
      });
    }

    setInterval(updateCountdowns, 1000);
    updateCountdowns();

    // Flight details modal
    function viewFlightDetails(flightId) {
      const modal = document.getElementById('flightDetailsModal');
      const contentDiv = document.getElementById('flightDetailsContent');

      modal.classList.remove('hidden');
      modal.classList.add('flex');

      // Fetch flight details
      fetch(`get_flight_details.php?flight_id=${flightId}`)
        .then(response => response.text())
        .then(data => {
          contentDiv.innerHTML = data;
        })
        .catch(error => {
          contentDiv.innerHTML = `<div class="bg-red-100 p-4 rounded-lg text-red-700">Error loading flight details.</div>`;
        });
    }

    function closeFlightModal() {
      const modal = document.getElementById('flightDetailsModal');
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    }

    // Hotel details modal
    function viewHotelDetails(bookingId) {
      const modal = document.getElementById('hotelDetailsModal');
      const contentDiv = document.getElementById('hotelDetailsContent');

      modal.classList.remove('hidden');
      modal.classList.add('flex');

      // Fetch hotel details
      fetch(`get_hotel_details.php?booking_id=${bookingId}`)
        .then(response => response.text())
        .then(data => {
          contentDiv.innerHTML = data;
        })
        .catch(error => {
          contentDiv.innerHTML = `<div class="bg-red-100 p-4 rounded-lg text-red-700">Error loading hotel details.</div>`;
        });
    }

    function closeHotelModal() {
      const modal = document.getElementById('hotelDetailsModal');
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    }

    // Package details modal
    function viewPackageDetails(packageId) {
      const modal = document.getElementById('packageDetailsModal');
      const contentDiv = document.getElementById('packageDetailsContent');

      modal.classList.remove('hidden');
      modal.classList.add('flex');

      // Fetch package details
      fetch(`get_package_details.php?package_id=${packageId}`)
        .then(response => response.text())
        .then(data => {
          contentDiv.innerHTML = data;
        })
        .catch(error => {
          contentDiv.innerHTML = `<div class="bg-red-100 p-4 rounded-lg text-red-700">Error loading package details.</div>`;
        });
    }

    function closePackageModal() {
      const modal = document.getElementById('packageDetailsModal');
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    }
  </script>
</body>

</html>