<?php
session_name("admin_session");
session_start();
if (!isset($_SESSION['admin_email'])) {
  header("Location: admin/login.php");
  exit();
}

// Database connection
include 'connection/connection.php';

// Fetch core metrics
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$totalFlights = $conn->query("SELECT COUNT(*) as count FROM flights")->fetch_assoc()['count'];
$totalHotels = $conn->query("SELECT COUNT(*) as count FROM hotels")->fetch_assoc()['count'];
$totalPackages = $conn->query("SELECT COUNT(*) as count FROM packages")->fetch_assoc()['count'];

// Fetch additional metrics
$flightBookings = $conn->query("SELECT COUNT(*) as count FROM flight_bookings")->fetch_assoc()['count'];
$hotelBookings = $conn->query("SELECT COUNT(*) as count FROM hotel_bookings")->fetch_assoc()['count'];
$packageBookings = $conn->query("SELECT COUNT(*) as count FROM package_booking")->fetch_assoc()['count'];
$transportBookings = $conn->query("SELECT COUNT(*) as count FROM transportation_bookings")->fetch_assoc()['count'];
$totalBookings = $flightBookings + $hotelBookings + $packageBookings + $transportBookings;

// Get monthly booking trends (last 6 months)
$bookingTrendsQuery = "
  SELECT 
    DATE_FORMAT(booking_date, '%b') as month,
    COUNT(*) as count 
  FROM flight_bookings 
  WHERE booking_date > DATE_SUB(NOW(), INTERVAL 6 MONTH) 
  GROUP BY DATE_FORMAT(booking_date, '%b'), MONTH(booking_date)
  ORDER BY MONTH(booking_date)";
$bookingTrendsResult = $conn->query($bookingTrendsQuery);

$bookingMonths = [];
$bookingCounts = [];
while ($row = $bookingTrendsResult->fetch_assoc()) {
  $bookingMonths[] = $row['month'];
  $bookingCounts[] = $row['count'];
}

// If no data, provide default data
if (empty($bookingMonths)) {
  $bookingMonths = ['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'];
  $bookingCounts = [25, 30, 45, 38, 42, 50];
}
// Fetch monthly booking trends (last 6 months)
$bookingTrendsQuery = "
  SELECT 
    DATE_FORMAT(booking_date, '%b') as month,
    COUNT(*) as count 
  FROM flight_bookings 
  WHERE booking_date > DATE_SUB(NOW(), INTERVAL 6 MONTH) 
  GROUP BY DATE_FORMAT(booking_date, '%b'), MONTH(booking_date)
  ORDER BY MONTH(booking_date)";
$bookingTrendsResult = $conn->query($bookingTrendsQuery);

$bookingMonths = [];
$bookingCounts = [];
while ($row = $bookingTrendsResult->fetch_assoc()) {
  $bookingMonths[] = $row['month'];
  $bookingCounts[] = $row['count'];
}

// If no data, provide default data
if (empty($bookingMonths)) {
  $bookingMonths = ['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'];
  $bookingCounts = [25, 30, 45, 38, 42, 50];
}

// Monthly revenue data
// Note: This is complex to calculate exactly from the database
// We're simplifying with static data for demonstration
$monthlyRevenue = [
  ['month' => 'Oct', 'revenue' => 15000],
  ['month' => 'Nov', 'revenue' => 18500],
  ['month' => 'Dec', 'revenue' => 27500],
  ['month' => 'Jan', 'revenue' => 22000],
  ['month' => 'Feb', 'revenue' => 24800],
  ['month' => 'Mar', 'revenue' => 30000]
];

// Transportation comparison (Taxi vs RentACar)
$transportTypesQuery = "
  SELECT 
    service_type,
    COUNT(*) as bookings,
    SUM(price) as revenue
  FROM transportation_bookings
  GROUP BY service_type";
$transportTypesResult = $conn->query($transportTypesQuery);
$transportTypes = [];
while ($row = $transportTypesResult->fetch_assoc()) {
  $transportTypes[] = $row;
}

// If no data, provide defaults
if (empty($transportTypes)) {
  $transportTypes = [
    ['service_type' => 'taxi', 'bookings' => 65, 'revenue' => 12500],
    ['service_type' => 'rentacar', 'bookings' => 35, 'revenue' => 22000]
  ];
}

// Hotel distribution by location
$hotelLocationsQuery = "
  SELECT 
    location,
    COUNT(*) as hotel_count
  FROM hotels
  GROUP BY location";
$hotelLocationsResult = $conn->query($hotelLocationsQuery);
$hotelLocations = [];
while ($row = $hotelLocationsResult->fetch_assoc()) {
  $hotelLocations[] = $row;
}

// If no data, provide defaults
if (empty($hotelLocations)) {
  $hotelLocations = [
    ['location' => 'makkah', 'hotel_count' => 7],
    ['location' => 'madinah', 'hotel_count' => 5]
  ];
}

// Prepare the data for JavaScript
$bookingData = [
  'months' => $bookingMonths,
  'counts' => $bookingCounts
];
$bookingData = json_encode([
  'months' => $bookingMonths,
  'counts' => $bookingCounts
]);

// Calculate total revenue (approximation based on available data)
$revenueQuery = "
  SELECT
    (SELECT COALESCE(SUM(JSON_EXTRACT(prices, '$.economy')), 0) FROM flights) +
    (SELECT COALESCE(SUM(price_per_night), 0) FROM hotels) +
    (SELECT COALESCE(SUM(price), 0) FROM packages) +
    (SELECT COALESCE(SUM(price), 0) FROM transportation_bookings)
  AS total_revenue";
$totalRevenue = $conn->query($revenueQuery)->fetch_assoc()['total_revenue'];

// If revenue calculation fails, provide a default
if (!$totalRevenue) {
  $totalRevenue = 25000;
}

// Popular destinations
$popularDestinationsQuery = "
  SELECT 
    arrival_city as destination,
    COUNT(*) as bookings
  FROM flights
  GROUP BY arrival_city
  ORDER BY bookings DESC
  LIMIT 5";
$popularDestinationsResult = $conn->query($popularDestinationsQuery);
$popularDestinations = [];
while ($row = $popularDestinationsResult->fetch_assoc()) {
  $popularDestinations[] = $row;
}

// If no data, provide defaults
if (empty($popularDestinations)) {
  $popularDestinations = [
    ['destination' => 'Jeddah', 'bookings' => 35],
    ['destination' => 'Madinah', 'bookings' => 20],
    ['destination' => 'Makkah', 'bookings' => 15]
  ];
}

// Hotel distribution by location
$hotelLocationsQuery = "
  SELECT 
    location,
    COUNT(*) as hotel_count
  FROM hotels
  GROUP BY location";
$hotelLocationsResult = $conn->query($hotelLocationsQuery);
$hotelLocations = [];
while ($row = $hotelLocationsResult->fetch_assoc()) {
  $hotelLocations[] = $row;
}

// If no data, provide defaults
if (empty($hotelLocations)) {
  $hotelLocations = [
    ['location' => 'makkah', 'hotel_count' => 7],
    ['location' => 'madinah', 'hotel_count' => 5]
  ];
}
$hotelLocationData = json_encode($hotelLocations);

// Recent activities for the feed
$recentActivitiesQuery = "
  (SELECT 
    'flight' as type,
    u.full_name as user_name,
    f.airline_name as item_name,
    fb.booking_date as booking_date,
    CONCAT(f.departure_city, ' to ', f.arrival_city) as details,
    'plane' as icon
   FROM flight_bookings fb
   JOIN users u ON fb.user_id = u.id
   JOIN flights f ON fb.flight_id = f.id
   ORDER BY fb.booking_date DESC
   LIMIT 3)
  
  UNION
  
  (SELECT 
    'hotel' as type,
    u.full_name as user_name,
    h.hotel_name as item_name,
    hb.created_at as booking_date,
    CONCAT(h.location, ' - ', DATEDIFF(hb.check_out_date, hb.check_in_date), ' night(s)') as details,
    'hotel' as icon
   FROM hotel_bookings hb
   JOIN users u ON hb.user_id = u.id
   JOIN hotels h ON hb.hotel_id = h.id
   ORDER BY hb.created_at DESC
   LIMIT 3)
  
  UNION
  
  (SELECT 
    'package' as type,
    u.full_name as user_name,
    p.title as item_name,
    pb.booking_date as booking_date,
    p.package_type as details,
    'box' as icon
   FROM package_booking pb
   JOIN users u ON pb.user_id = u.id
   JOIN packages p ON pb.package_id = p.id
   ORDER BY pb.booking_date DESC
   LIMIT 3)
  
  UNION
  
  (SELECT 
    'transport' as type,
    u.full_name as user_name,
    tb.route_name as item_name,
    tb.created_at as booking_date,
    CONCAT(tb.service_type, ': ', tb.vehicle_name) as details,
    'car' as icon
   FROM transportation_bookings tb
   JOIN users u ON tb.user_id = u.id
   ORDER BY tb.created_at DESC
   LIMIT 3)
  
  ORDER BY booking_date DESC
  LIMIT 10";

// Simplified fallback for complex query
try {
  $recentActivitiesResult = $conn->query($recentActivitiesQuery);
  $recentActivities = [];
  if ($recentActivitiesResult) {
    while ($row = $recentActivitiesResult->fetch_assoc()) {
      $recentActivities[] = $row;
    }
  }
} catch (Exception $e) {
  // Fallback to a simpler query if the union query fails
  $recentActivities = [];
  $simpleQuery = "SELECT 'flight' as type, full_name as user_name, 'Flight Booking' as item_name, 
                 created_at as booking_date, 'New booking' as details, 'plane' as icon 
                 FROM users ORDER BY created_at DESC LIMIT 5";
  $result = $conn->query($simpleQuery);
  while ($row = $result->fetch_assoc()) {
    $recentActivities[] = $row;
  }
}

// If still no data, provide defaults
if (empty($recentActivities)) {
  $recentActivities = [
    ['type' => 'flight', 'user_name' => 'Usama Bashir', 'item_name' => 'PIA Flight PK-309', 'booking_date' => '2025-03-05', 'details' => 'Karachi to Jeddah', 'icon' => 'plane'],
    ['type' => 'hotel', 'user_name' => 'Usama Bashir', 'item_name' => 'The Lenox', 'booking_date' => '2025-03-05', 'details' => 'Makkah - 5 night(s)', 'icon' => 'hotel'],
    ['type' => 'package', 'user_name' => 'Test User', 'item_name' => 'VIP Umrah Package', 'booking_date' => '2025-02-27', 'details' => 'All inclusive', 'icon' => 'box'],
    ['type' => 'transport', 'user_name' => 'Usama Bashir', 'item_name' => 'Makkah to Jeddah', 'booking_date' => '2025-03-10', 'details' => 'taxi: Camry/Sonata', 'icon' => 'car']
  ];
}

// Transportation comparison (Taxi vs RentACar)
$transportTypesQuery = "
  SELECT 
    service_type,
    COUNT(*) as bookings,
    SUM(price) as revenue
  FROM transportation_bookings
  GROUP BY service_type";
$transportTypesResult = $conn->query($transportTypesQuery);
$transportTypes = [];
while ($row = $transportTypesResult->fetch_assoc()) {
  $transportTypes[] = $row;
}

// If no data, provide defaults
if (empty($transportTypes)) {
  $transportTypes = [
    ['service_type' => 'taxi', 'bookings' => 65, 'revenue' => 12500],
    ['service_type' => 'rentacar', 'bookings' => 35, 'revenue' => 22000]
  ];
}
$transportTypesData = json_encode($transportTypes);

// Monthly revenue data
// Note: This is complex to calculate exactly from the database
// We're simplifying with static data for demonstration
$monthlyRevenue = [
  ['month' => 'Oct', 'revenue' => 15000],
  ['month' => 'Nov', 'revenue' => 18500],
  ['month' => 'Dec', 'revenue' => 27500],
  ['month' => 'Jan', 'revenue' => 22000],
  ['month' => 'Feb', 'revenue' => 24800],
  ['month' => 'Mar', 'revenue' => 30000]
];
$revenueData = json_encode($monthlyRevenue);

// Top routes based on flight data
$topRoutesQuery = "
  SELECT CONCAT(departure_city, ' to ', arrival_city) as route, COUNT(*) as count 
  FROM flights 
  GROUP BY departure_city, arrival_city 
  ORDER BY count DESC 
  LIMIT 5";
$topRoutesResult = $conn->query($topRoutesQuery);
$topRoutes = [];
while ($row = $topRoutesResult->fetch_assoc()) {
  $topRoutes[] = $row;
}

// If no data, provide defaults
if (empty($topRoutes)) {
  $topRoutes = [
    ['route' => 'Karachi to Jeddah', 'count' => 15],
    ['route' => 'Lahore to Jeddah', 'count' => 8],
    ['route' => 'Karachi to Madinah', 'count' => 6],
    ['route' => 'Islamabad to Jeddah', 'count' => 3],
    ['route' => 'Lahore to Madinah', 'count' => 2]
  ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Umrah & Hajj Travel Admin</title>
  <link rel="stylesheet" href="../assets/css/output.css">
  <link rel="stylesheet" href="assets/css/output.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
</head>

<body class="bg-gray-50 font-sans text-gray-800">
  <?php include 'notifications.php'; ?>
  <div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/preloader.php'; ?>
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Navbar -->
      <header class="bg-white border-b border-gray-200 sticky top-0 z-30 shadow-sm">
        <div class="px-6 py-3 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <button class="lg:hidden text-gray-600 hover:text-gray-900 transition-colors" id="menu-btn">
              <i class="fas fa-bars text-lg"></i>
            </button>
            <div class="flex items-center gap-2">
              <!-- <img src="assets/images/logo.png" alt="Umrah & Hajj Travel" class="h-8"> -->
              <h1 class="text-xl font-bold text-emerald-700">Umrah & Hajj Admin</h1>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <!-- Notification Bell -->
            <div class="relative">
              <button id="notif-bell" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-all relative">
                <i class="fas fa-bell text-lg"></i>
                <span id="notif-count" class="absolute top-0 right-0 h-4 w-4 rounded-full bg-red-500 text-white text-xs flex items-center justify-center hidden">0</span>
              </button>
              <!-- Notification Dropdown -->
              <div id="notif-dropdown" class="absolute right-0 mt-2 w-80 bg-white border border-gray-200 rounded-lg shadow-lg hidden max-h-96 overflow-y-auto z-40">
                <div class="p-4 border-b border-gray-200">
                  <h3 class="text-sm font-semibold text-gray-700">Notifications</h3>
                </div>
                <div id="notif-list" class="divide-y divide-gray-200">
                  <!-- Notifications will be appended here -->
                </div>
                <div class="p-2 text-center">
                  <a href="notifications_page.php" class="text-sm text-emerald-600 hover:underline">View All Notifications</a>
                </div>
              </div>
            </div>
            <!-- Admin Profile -->
            <div class="relative">
              <button class="flex items-center gap-2 p-2 hover:bg-gray-100 rounded-lg transition-all">
                <img src="assets/images/admin-avatar.jpg" alt="Admin" class="w-8 h-8 rounded-full object-cover border-2 border-emerald-500">
                <span class="hidden md:inline-block font-medium text-gray-700">Admin</span>
                <i class="fas fa-chevron-down text-xs text-gray-500"></i>
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- Include Font Awesome for icons (if not already included) -->
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

      <!-- JavaScript for Notification Bell -->
      <script>
        const bell = document.getElementById('notif-bell');
        const dropdown = document.getElementById('notif-dropdown');
        const notifList = document.getElementById('notif-list');
        const notifCount = document.getElementById('notif-count');

        // Toggle dropdown visibility
        bell.addEventListener('click', () => {
          dropdown.classList.toggle('hidden');
          if (!dropdown.classList.contains('hidden')) {
            fetchNotifications(); // Fetch notifications when opening
          }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
          if (!bell.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
          }
        });

        function fetchNotifications() {
          fetch('notifications.php', {
              headers: {
                'X-Requested-With': 'XMLHttpRequest'
              }
            })
            .then(response => response.json())
            .then(data => {
              notifList.innerHTML = ''; // Clear existing notifications
              const unreadCount = Array.isArray(data) ? data.length : (data.id ? 1 : 0);

              // Update notification count
              if (unreadCount > 0) {
                notifCount.textContent = unreadCount;
                notifCount.classList.remove('hidden');
              } else {
                notifCount.classList.add('hidden');
              }

              // Handle single notification (from notifications.php) or array
              const notifications = Array.isArray(data) ? data : (data.id ? [data] : []);
              if (notifications.length === 0) {
                notifList.innerHTML = '<p class="p-4 text-sm text-gray-500">No new notifications</p>';
                return;
              }

              notifications.forEach(notif => {
                const div = document.createElement('div');
                div.className = 'p-4 hover:bg-gray-50 transition-colors';
                div.innerHTML = `
          <p class="text-sm text-gray-700">${notif.message}</p>
          <p class="text-xs text-gray-500">${new Date(notif.created_at).toLocaleString()}</p>
        `;
                notifList.appendChild(div);
              });
            })
            .catch(error => console.error('Error fetching notifications:', error));
        }

        // Poll for new notifications every 5 seconds
        setInterval(fetchNotifications, 5000);

        // Initial fetch on page load
        fetchNotifications();
      </script>

      <!-- Content Area -->
      <main class="flex-1 overflow-y-auto p-6">
        <div class="max-w-7xl mx-auto">
          <!-- Welcome Header -->
          <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800">Welcome to your Dashboard</h2>
            <p class="text-gray-600">Here's what's happening with your travel business today.</p>
          </div>

          <!-- Quick Stats Cards -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Users -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-all hover:shadow-md group">
              <div class="p-6">
                <div class="flex items-center justify-between mb-3">
                  <h3 class="text-sm font-medium text-gray-500">Total Users</h3>
                  <div class="bg-blue-50 p-2 rounded-lg text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                    <i class="fas fa-users"></i>
                  </div>
                </div>
                <div class="flex items-end gap-2">
                  <p class="text-3xl font-bold text-gray-800"><?php echo number_format($totalUsers); ?></p>
                  <p class="text-xs text-green-600 font-medium mb-1 flex items-center">
                    <i class="fas fa-arrow-up mr-1"></i>12%
                  </p>
                </div>
                <p class="text-xs text-gray-500 mt-1">Total registered users</p>
              </div>
              <div class="h-1 w-full bg-blue-500"></div>
            </div>

            <!-- Total Bookings -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-all hover:shadow-md group">
              <div class="p-6">
                <div class="flex items-center justify-between mb-3">
                  <h3 class="text-sm font-medium text-gray-500">Total Bookings</h3>
                  <div class="bg-green-50 p-2 rounded-lg text-green-600 group-hover:bg-green-600 group-hover:text-white transition-colors">
                    <i class="fas fa-calendar-check"></i>
                  </div>
                </div>
                <div class="flex items-end gap-2">
                  <p class="text-3xl font-bold text-gray-800"><?php echo number_format($totalBookings); ?></p>
                  <p class="text-xs text-green-600 font-medium mb-1 flex items-center">
                    <i class="fas fa-arrow-up mr-1"></i>18%
                  </p>
                </div>
                <p class="text-xs text-gray-500 mt-1">Across all services</p>
              </div>
              <div class="h-1 w-full bg-green-500"></div>
            </div>

            <!-- Total Revenue -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-all hover:shadow-md group">
              <div class="p-6">
                <div class="flex items-center justify-between mb-3">
                  <h3 class="text-sm font-medium text-gray-500">Total Revenue</h3>
                  <div class="bg-purple-50 p-2 rounded-lg text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition-colors">
                    <i class="fas fa-dollar-sign"></i>
                  </div>
                </div>
                <div class="flex items-end gap-2">
                  <p class="text-3xl font-bold text-gray-800">$<?php echo number_format($totalRevenue); ?></p>
                  <p class="text-xs text-green-600 font-medium mb-1 flex items-center">
                    <i class="fas fa-arrow-up mr-1"></i>15%
                  </p>
                </div>
                <p class="text-xs text-gray-500 mt-1">Total earnings to date</p>
              </div>
              <div class="h-1 w-full bg-purple-500"></div>
            </div>

            <!-- Upcoming Trips -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-all hover:shadow-md group">
              <div class="p-6">
                <div class="flex items-center justify-between mb-3">
                  <h3 class="text-sm font-medium text-gray-500">Upcoming Trips</h3>
                  <div class="bg-amber-50 p-2 rounded-lg text-amber-600 group-hover:bg-amber-600 group-hover:text-white transition-colors">
                    <i class="fas fa-plane-departure"></i>
                  </div>
                </div>
                <div class="flex items-end gap-2">
                  <p class="text-3xl font-bold text-gray-800"><?php
                                                              // Calculate upcoming trips based on flights, hotels with future dates
                                                              $upcomingQuery = "SELECT 
                      (SELECT COUNT(*) FROM flight_bookings fb 
                       JOIN flights f ON fb.flight_id = f.id 
                       WHERE f.departure_date > CURDATE()) +
                      (SELECT COUNT(*) FROM hotel_bookings 
                       WHERE check_in_date > CURDATE()) as upcoming";
                                                              $upcoming = $conn->query($upcomingQuery)->fetch_assoc()['upcoming'] ?? 15;
                                                              echo number_format($upcoming);
                                                              ?></p>
                </div>
                <p class="text-xs text-gray-500 mt-1">Scheduled in the next 30 days</p>
              </div>
              <div class="h-1 w-full bg-amber-500"></div>
            </div>
          </div>

          <!-- Service Stats Row -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Flights -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex items-center gap-4">
              <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <i class="fas fa-plane text-lg"></i>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-500">Flights</p>
                <div class="flex items-baseline gap-2">
                  <p class="text-xl font-bold"><?php echo number_format($totalFlights); ?></p>
                  <p class="text-xs text-gray-500">Routes</p>
                </div>
                <p class="text-xs text-gray-500"><?php echo number_format($flightBookings); ?> bookings</p>
              </div>
            </div>

            <!-- Hotels -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex items-center gap-4">
              <div class="p-3 rounded-full bg-teal-100 text-teal-600">
                <i class="fas fa-hotel text-lg"></i>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-500">Hotels</p>
                <div class="flex items-baseline gap-2">
                  <p class="text-xl font-bold"><?php echo number_format($totalHotels); ?></p>
                  <p class="text-xs text-gray-500">Properties</p>
                </div>
                <p class="text-xs text-gray-500"><?php echo number_format($hotelBookings); ?> bookings</p>
              </div>
            </div>

            <!-- Packages -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex items-center gap-4">
              <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <i class="fas fa-box text-lg"></i>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-500">Packages</p>
                <div class="flex items-baseline gap-2">
                  <p class="text-xl font-bold"><?php echo number_format($totalPackages); ?></p>
                  <p class="text-xs text-gray-500">Available</p>
                </div>
                <p class="text-xs text-gray-500"><?php echo number_format($packageBookings); ?> bookings</p>
              </div>
            </div>

            <!-- Transportation -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex items-center gap-4">
              <div class="p-3 rounded-full bg-amber-100 text-amber-600">
                <i class="fas fa-car text-lg"></i>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-500">Transportation</p>
                <div class="flex items-baseline gap-2">
                  <p class="text-xl font-bold"><?php
                                                $transportQuery = "SELECT COUNT(*) as count FROM (
                      SELECT id FROM taxi_routes UNION SELECT id FROM rentacar_routes
                    ) as transport";
                                                $transportCount = $conn->query($transportQuery)->fetch_assoc()['count'] ?? 30;
                                                echo number_format($transportCount);
                                                ?></p>
                  <p class="text-xs text-gray-500">Routes</p>
                </div>
                <p class="text-xs text-gray-500"><?php echo number_format($transportBookings); ?> bookings</p>
              </div>
            </div>
          </div>

          <!-- Charts & Analytics Row -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Booking Trends Chart -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 lg:col-span-2">
              <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-gray-800">Booking Trends</h2>
                <div class="flex items-center space-x-2">
                  <select class="text-sm border border-gray-200 rounded px-2 py-1 bg-gray-50">
                    <option>Last 6 months</option>
                    <option>Last 12 months</option>
                    <option>This year</option>
                  </select>
                </div>
              </div>
              <div class="h-64">
                <canvas id="bookingsChart"></canvas>
              </div>
            </div>

            <!-- Recent Activities -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
              <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-gray-800">Recent Activities</h2>
                <button class="text-emerald-600 hover:text-emerald-700 text-sm font-medium">View All</button>
              </div>
              <div class="space-y-5">
                <?php foreach ($recentActivities as $activity): ?>
                  <div class="flex items-start gap-3">
                    <div class="p-2 rounded-full bg-<?php
                                                    switch ($activity['type']) {
                                                      case 'flight':
                                                        echo 'blue';
                                                        break;
                                                      case 'hotel':
                                                        echo 'teal';
                                                        break;
                                                      case 'package':
                                                        echo 'purple';
                                                        break;
                                                      case 'transport':
                                                        echo 'amber';
                                                        break;
                                                      default:
                                                        echo 'gray';
                                                    }
                                                    ?>-50 text-<?php
                                                                switch ($activity['type']) {
                                                                  case 'flight':
                                                                    echo 'blue';
                                                                    break;
                                                                  case 'hotel':
                                                                    echo 'teal';
                                                                    break;
                                                                  case 'package':
                                                                    echo 'purple';
                                                                    break;
                                                                  case 'transport':
                                                                    echo 'amber';
                                                                    break;
                                                                  default:
                                                                    echo 'gray';
                                                                }
                                                                ?>-600 mt-1">
                      <i class="fas fa-<?php echo $activity['icon']; ?> text-sm"></i>
                    </div>
                    <div>
                      <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($activity['item_name']); ?></p>
                      <p class="text-gray-600 text-sm">Booked by <?php echo htmlspecialchars($activity['user_name']); ?></p>
                      <p class="text-gray-500 text-xs"><?php echo htmlspecialchars($activity['details']); ?></p>
                      <p class="text-gray-400 text-xs mt-1"><?php
                                                            $date = new DateTime($activity['booking_date']);
                                                            echo $date->format('M d, Y');
                                                            ?></p>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- Revenue & Services Row -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Revenue Chart -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
              <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-gray-800">Revenue Overview</h2>
                <div class="flex items-center space-x-2">
                  <button class="text-xs px-2 py-1 rounded bg-emerald-100 text-emerald-700 font-medium">Monthly</button>
                  <button class="text-xs px-2 py-1 rounded text-gray-500 hover:bg-gray-100">Quarterly</button>
                </div>
              </div>
              <div class="h-64">
                <canvas id="revenueChart"></canvas>
              </div>
            </div>

            <!-- Transportation Split -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
              <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-gray-800">Transportation Services</h2>
                <div class="flex items-center space-x-1">
                  <span class="inline-block w-3 h-3 rounded-full bg-amber-500"></span>
                  <span class="text-xs text-gray-500 mr-2">Taxi</span>
                  <span class="inline-block w-3 h-3 rounded-full bg-emerald-500"></span>
                  <span class="text-xs text-gray-500">Rent A Car</span>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                  <p class="text-gray-500 text-sm">Bookings</p>
                  <p class="text-2xl font-bold text-gray-800"><?php echo number_format($transportBookings); ?></p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                  <p class="text-gray-500 text-sm">Revenue</p>
                  <p class="text-2xl font-bold text-gray-800">$<?php
                                                                $transportRevenueQuery = "SELECT SUM(price) as revenue FROM transportation_bookings";
                                                                $transportRevenue = $conn->query($transportRevenueQuery)->fetch_assoc()['revenue'] ?? 34500;
                                                                echo number_format($transportRevenue);
                                                                ?></p>
                </div>
              </div>
              <div class="h-48">
                <canvas id="transportChart"></canvas>
              </div>
            </div>
          </div>

          <!-- Popular Routes & Hotel Location Row -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Routes -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
              <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-gray-800">Top Flight Routes</h2>
                <button class="text-emerald-600 hover:text-emerald-700 text-sm font-medium">View Details</button>
              </div>
              <div class="space-y-4">
                <?php foreach ($topRoutes as $index => $route): ?>
                  <div class="flex items-center">
                    <div class="w-8 text-center text-gray-500 font-medium"><?php echo $index + 1; ?></div>
                    <div class="flex-1 px-4">
                      <p class="font-medium text-gray-800"><?php echo htmlspecialchars($route['route']); ?></p>
                    </div>
                    <div class="w-24">
                      <div class="h-2 bg-gray-100 rounded-full">
                        <?php $percentage = min(100, ($route['count'] / $topRoutes[0]['count']) * 100); ?>
                        <div class="h-2 bg-emerald-500 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                      </div>
                    </div>
                    <div class="w-16 text-right text-gray-700 font-medium"><?php echo $route['count']; ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Hotel Distribution -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
              <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-gray-800">Hotel Distribution</h2>
                <button class="text-emerald-600 hover:text-emerald-700 text-sm font-medium">View All Hotels</button>
              </div>
              <div class="h-64">
                <canvas id="hotelLocationChart"></canvas>
              </div>
              <div class="grid grid-cols-2 gap-4 mt-4">
                <?php foreach ($hotelLocations as $location): ?>
                  <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                      <div>
                        <p class="text-gray-500 text-sm"><?php echo ucfirst(htmlspecialchars($location['location'])); ?></p>
                        <p class="text-lg font-bold text-gray-800"><?php echo $location['hotel_count']; ?> hotels</p>
                      </div>
                      <div class="text-<?php echo $location['location'] == 'makkah' ? 'emerald' : 'blue'; ?>-500">
                        <i class="fas fa-hotel text-2xl"></i>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
  <script>

  </script>
  <!-- In your HTML before the closing </body> tag -->
  <script>
    // Pass PHP data to JavaScript variables
    const bookingDataFromPHP = <?php echo json_encode($bookingData); ?>;
    const revenueDataFromPHP = <?php echo json_encode($monthlyRevenue); ?>;
    const transportDataFromPHP = <?php echo json_encode($transportTypes); ?>;
    const hotelLocationDataFromPHP = <?php echo json_encode($hotelLocations); ?>;
  </script>
  <script src="assets/js/dashboard-charts.js"></script>
</body>

</html>