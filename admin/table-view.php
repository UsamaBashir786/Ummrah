<?php
// Database connection configuration
$host = "localhost";
$username = "root";  // Update with your database username
$password = "";      // Update with your database password
$database = "ummrah";

// Create database connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Function to get overview statistics
function getOverviewStats($conn)
{
  // Initialize result array
  $result = [
    'flights' => 0,
    'flight_bookings' => 0,
    'transportation_bookings' => 0,
    'package_bookings' => 0,
    'users' => 0
  ];

  // Get flights count
  $sql = "SELECT COUNT(*) as count FROM flights";
  $query = $conn->query($sql);
  if ($query) {
    $row = $query->fetch_assoc();
    $result['flights'] = (int)$row['count'];
  }

  // Get flight bookings count
  $sql = "SELECT COUNT(*) as count FROM flight_bookings";
  $query = $conn->query($sql);
  if ($query) {
    $row = $query->fetch_assoc();
    $result['flight_bookings'] = (int)$row['count'];
  }

  // Get transportation bookings count
  $sql = "SELECT COUNT(*) as count FROM transportation_bookings";
  $query = $conn->query($sql);
  if ($query) {
    $row = $query->fetch_assoc();
    $result['transportation_bookings'] = (int)$row['count'];
  }

  // Get package bookings count
  $sql = "SELECT COUNT(*) as count FROM package_booking";
  $query = $conn->query($sql);
  if ($query) {
    $row = $query->fetch_assoc();
    $result['package_bookings'] = (int)$row['count'];
  }

  // Get users count
  $sql = "SELECT COUNT(*) as count FROM users";
  $query = $conn->query($sql);
  if ($query) {
    $row = $query->fetch_assoc();
    $result['users'] = (int)$row['count'];
  }

  return $result;
}

// Function to get flight routes data
function getFlightRoutes($conn)
{
  $sql = "SELECT departure_city, arrival_city, COUNT(*) as count 
            FROM flights 
            GROUP BY departure_city, arrival_city 
            ORDER BY count DESC";

  $result = $conn->query($sql);

  $routes = [];
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $routes[] = [
        'route' => $row['departure_city'] . ' to ' . $row['arrival_city'],
        'count' => (int)$row['count']
      ];
    }
  }

  return $routes;
}

// Function to get airlines distribution
function getAirlinesDistribution($conn)
{
  $sql = "SELECT airline_name, COUNT(*) as count 
            FROM flights 
            GROUP BY airline_name 
            ORDER BY count DESC";

  $result = $conn->query($sql);

  $airlines = [];
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $airlines[] = [
        'airline' => $row['airline_name'],
        'count' => (int)$row['count']
      ];
    }
  }

  return $airlines;
}

// Function to get booking status distribution
function getBookingsByStatus($conn)
{
  // Flight bookings status
  $sql = "SELECT booking_status, COUNT(*) as count 
            FROM flight_bookings 
            GROUP BY booking_status";

  $result = $conn->query($sql);

  $flightStatus = [];
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $flightStatus[] = [
        'status' => 'Flight - ' . ucfirst($row['booking_status']),
        'count' => (int)$row['count']
      ];
    }
  }

  // Transportation bookings status
  $sql = "SELECT booking_status, COUNT(*) as count 
            FROM transportation_bookings 
            GROUP BY booking_status";

  $result = $conn->query($sql);

  $transportStatus = [];
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $transportStatus[] = [
        'status' => 'Transport - ' . ucfirst($row['booking_status']),
        'count' => (int)$row['count']
      ];
    }
  }

  // Package bookings status
  $sql = "SELECT status, COUNT(*) as count 
            FROM package_booking 
            GROUP BY status";

  $result = $conn->query($sql);

  $packageStatus = [];
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $packageStatus[] = [
        'status' => 'Package - ' . ucfirst($row['status']),
        'count' => (int)$row['count']
      ];
    }
  }

  // Combine all status data
  return array_merge($flightStatus, $transportStatus, $packageStatus);
}

// Function to get transportation by vehicle type
function getTransportationByType($conn)
{
  $sql = "SELECT vehicle_type, COUNT(*) as count 
            FROM transportation_bookings 
            GROUP BY vehicle_type";

  $result = $conn->query($sql);

  $vehicleTypes = [];
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $vehicleTypes[] = [
        'type' => ucfirst(str_replace('_', ' ', $row['vehicle_type'])),
        'count' => (int)$row['count']
      ];
    }
  }

  return $vehicleTypes;
}

// Function to get bookings by month
function getBookingsByMonth($conn)
{
  // Flight bookings by month
  $sql = "SELECT DATE_FORMAT(booking_date, '%Y-%m') as month, COUNT(*) as count 
            FROM flight_bookings 
            GROUP BY DATE_FORMAT(booking_date, '%Y-%m')
            ORDER BY month";

  $result = $conn->query($sql);

  $flightBookings = [];
  $months = [];

  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $flightBookings[$row['month']] = (int)$row['count'];
      if (!in_array($row['month'], $months)) {
        $months[] = $row['month'];
      }
    }
  }

  // Transportation bookings by month
  $sql = "SELECT DATE_FORMAT(booking_date, '%Y-%m') as month, COUNT(*) as count 
            FROM transportation_bookings 
            GROUP BY DATE_FORMAT(booking_date, '%Y-%m')
            ORDER BY month";

  $result = $conn->query($sql);

  $transportBookings = [];

  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $transportBookings[$row['month']] = (int)$row['count'];
      if (!in_array($row['month'], $months)) {
        $months[] = $row['month'];
      }
    }
  }

  // Package bookings by month
  $sql = "SELECT DATE_FORMAT(booking_date, '%Y-%m') as month, COUNT(*) as count 
            FROM package_booking 
            GROUP BY DATE_FORMAT(booking_date, '%Y-%m')
            ORDER BY month";

  $result = $conn->query($sql);

  $packageBookings = [];

  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $packageBookings[$row['month']] = (int)$row['count'];
      if (!in_array($row['month'], $months)) {
        $months[] = $row['month'];
      }
    }
  }

  // Format data for Chart.js
  $chartData = [];

  foreach ($months as $month) {
    $chartData[] = [
      'month' => $month,
      'flights' => isset($flightBookings[$month]) ? $flightBookings[$month] : 0,
      'transport' => isset($transportBookings[$month]) ? $transportBookings[$month] : 0,
      'packages' => isset($packageBookings[$month]) ? $packageBookings[$month] : 0
    ];
  }

  return $chartData;
}

// Function to get payment status distribution
function getPaymentStatus($conn)
{
  // Transportation payment status
  $sql = "SELECT payment_status, COUNT(*) as count 
            FROM transportation_bookings 
            GROUP BY payment_status";

  $result = $conn->query($sql);

  $transportPayment = [];
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $transportPayment[] = [
        'status' => 'Transport - ' . ucfirst($row['payment_status']),
        'count' => (int)$row['count']
      ];
    }
  }

  // Package payment status
  $sql = "SELECT payment_status, COUNT(*) as count 
            FROM package_booking 
            GROUP BY payment_status";

  $result = $conn->query($sql);

  $packagePayment = [];
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $packagePayment[] = [
        'status' => 'Package - ' . ucfirst($row['payment_status']),
        'count' => (int)$row['count']
      ];
    }
  }

  // Combine all payment status data
  return array_merge($transportPayment, $packagePayment);
}

// Function to get taxi routes analysis
function getTaxiRoutesAnalysis($conn)
{
  $sql = "SELECT route_name, camry_sonata_price, starex_staria_price, hiace_price 
            FROM taxi_routes 
            ORDER BY route_number";

  $result = $conn->query($sql);

  $taxiRoutes = [];
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $taxiRoutes[] = [
        'route' => $row['route_name'],
        'camry' => (float)$row['camry_sonata_price'],
        'starex' => (float)$row['starex_staria_price'],
        'hiace' => (float)$row['hiace_price']
      ];
    }
  }

  return $taxiRoutes;
}

// Function to get recent bookings
function getRecentBookings($conn)
{
  $sql = "SELECT fb.id, fb.passenger_name, fb.booking_date, fb.booking_status, f.airline_name, f.flight_number, 
            f.departure_city, f.arrival_city, f.departure_date, fb.cabin_class
            FROM flight_bookings fb
            JOIN flights f ON fb.flight_id = f.id
            ORDER BY fb.booking_date DESC
            LIMIT 5";

  $result = $conn->query($sql);

  $recentBookings = [];
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $recentBookings[] = [
        'id' => $row['id'],
        'passenger' => $row['passenger_name'],
        'booking_date' => $row['booking_date'],
        'status' => $row['booking_status'],
        'airline' => $row['airline_name'],
        'flight' => $row['flight_number'],
        'route' => $row['departure_city'] . ' to ' . $row['arrival_city'],
        'departure' => $row['departure_date'],
        'cabin' => $row['cabin_class']
      ];
    }
  }

  return $recentBookings;
}

// Fetch all data for the dashboard
$overviewStats = getOverviewStats($conn);
$flightRoutes = getFlightRoutes($conn);
$airlinesDistribution = getAirlinesDistribution($conn);
$bookingsByStatus = getBookingsByStatus($conn);
$transportationByType = getTransportationByType($conn);
$bookingsByMonth = getBookingsByMonth($conn);
$paymentStatus = getPaymentStatus($conn);
$taxiRoutesAnalysis = getTaxiRoutesAnalysis($conn);
$recentBookings = getRecentBookings($conn);

// Close the database connection
$conn->close();

// Generate JSON data for JavaScript charts
$overviewStatsJSON = json_encode($overviewStats);
$flightRoutesJSON = json_encode($flightRoutes);
$airlinesDistributionJSON = json_encode($airlinesDistribution);
$bookingsByStatusJSON = json_encode($bookingsByStatus);
$transportationByTypeJSON = json_encode($transportationByType);
$bookingsByMonthJSON = json_encode($bookingsByMonth);
$paymentStatusJSON = json_encode($paymentStatus);
$taxiRoutesAnalysisJSON = json_encode($taxiRoutesAnalysis);
$recentBookingsJSON = json_encode($recentBookings);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ummrah Travel Analytics Dashboard</title>
  <!-- Tailwind CSS via CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Chart.js -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .dashboard-card {
      transition: all 0.3s ease;
    }

    .dashboard-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .chart-container {
      position: relative;
      height: 300px;
      width: 100%;
    }

    .stat-card {
      border-radius: 0.75rem;
      overflow: hidden;
      transition: all 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }

    .progress-bar {
      height: 8px;
      border-radius: 4px;
      margin-top: 8px;
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }

    ::-webkit-scrollbar-track {
      background: #f1f5f9;
      border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }
  </style>
</head>

<body class="bg-gray-50 font-sans">
  <!-- Loader -->
  <div id="loader" class="fixed inset-0 z-50 flex items-center justify-center bg-white">
    <div class="flex flex-col items-center">
      <div class="w-16 h-16 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
      <p class="mt-4 text-lg text-gray-700">Loading Analytics Dashboard...</p>
    </div>
  </div>

  <!-- Header -->
  <header class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white shadow-lg">
    <div class="container mx-auto px-4 py-4 md:py-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-3 sm:space-y-0">
        <!-- Left Section with Back Button and Title -->
        <div class="w-full sm:w-auto">
          <div class="flex items-center justify-between sm:justify-start w-full">
            <a href="javascript:history.back()" class="bg-white/20 hover:bg-white/30 text-white p-2 rounded-lg transition-all duration-300 flex items-center">
              <i class="fas fa-arrow-left mr-1"></i>
              <!-- <span class="hidden sm:inline">Back</span> -->
            </a>

            <!-- Mobile Menu Button - Only visible on small screens -->
            <div class="flex sm:hidden space-x-2">
              <button id="mobile-filter" class="bg-white/20 p-2 rounded-lg">
                <i class="fas fa-calendar-alt"></i>
              </button>
              <button id="mobile-export" class="bg-white text-indigo-700 p-2 rounded-lg">
                <i class="fas fa-file-export"></i>
              </button>
            </div>
          </div>

          <h1 class="text-2xl sm:text-3xl font-bold mt-3">Ummrah Travel Analytics</h1>
          <p class="text-blue-100 text-sm sm:text-base">Advanced Real-Time Insights</p>
        </div>

        <div class="hidden sm:flex items-center space-x-3 md:space-x-4">
          <!-- <div class="bg-white/20 rounded-lg px-3 py-2">
            <span class="text-xs md:text-sm">Last Updated: <?php echo date('F d, Y H:i'); ?></span>
          </div>
          <div class="relative">
            <button class="flex items-center space-x-1 bg-white/20 rounded-lg px-3 py-2 hover:bg-white/30 transition text-sm">
              <i class="fas fa-calendar-alt"></i>
              <span class="hidden md:inline">Filter by Date</span>
            </button>
          </div>
          <button id="export-pdf" class="bg-white text-indigo-700 rounded-lg px-3 py-2 text-sm font-medium hover:bg-blue-50 transition">
            <i class="fas fa-file-export mr-1"></i>
            <span class="hidden md:inline">Export</span>
          </button> -->
          <a href="javascript:history.back()" class="bg-white/20 hover:bg-white/30 text-white p-2 rounded-lg transition-all duration-300 flex items-center">
            <i class="fas fa-arrow-left mr-1"></i>
            <span class="hidden sm:inline">Back</span>
          </a>
        </div>
      </div>
    </div>
  </header>
  <!-- Main Dashboard -->
  <main class="container mx-auto px-4 py-8">
    <!-- Key Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
      <div class="dashboard-card bg-white rounded-xl shadow-md p-6">
        <div class="flex justify-between items-start">
          <div>
            <h3 class="text-gray-500 text-sm font-medium">Total Flights</h3>
            <p class="text-3xl font-bold text-gray-800 mt-1" id="total-flights">-</p>
          </div>
          <div class="p-3 rounded-full bg-blue-100 text-blue-600">
            <i class="fas fa-plane"></i>
          </div>
        </div>
        <div class="mt-4">
          <div class="flex items-center">
            <span class="text-green-500 flex items-center text-sm font-medium">
              <i class="fas fa-arrow-up mr-1"></i> 25%
            </span>
            <span class="text-gray-400 text-sm ml-2">vs previous month</span>
          </div>
        </div>
      </div>

      <div class="dashboard-card bg-white rounded-xl shadow-md p-6">
        <div class="flex justify-between items-start">
          <div>
            <h3 class="text-gray-500 text-sm font-medium">Flight Bookings</h3>
            <p class="text-3xl font-bold text-gray-800 mt-1" id="total-flight-bookings">-</p>
          </div>
          <div class="p-3 rounded-full bg-purple-100 text-purple-600">
            <i class="fas fa-ticket-alt"></i>
          </div>
        </div>
        <div class="mt-4">
          <div class="flex items-center">
            <span class="text-green-500 flex items-center text-sm font-medium">
              <i class="fas fa-arrow-up mr-1"></i> 10%
            </span>
            <span class="text-gray-400 text-sm ml-2">vs previous month</span>
          </div>
        </div>
      </div>

      <div class="dashboard-card bg-white rounded-xl shadow-md p-6">
        <div class="flex justify-between items-start">
          <div>
            <h3 class="text-gray-500 text-sm font-medium">Transportation</h3>
            <p class="text-3xl font-bold text-gray-800 mt-1" id="total-transportation">-</p>
          </div>
          <div class="p-3 rounded-full bg-green-100 text-green-600">
            <i class="fas fa-bus"></i>
          </div>
        </div>
        <div class="mt-4">
          <div class="flex items-center">
            <span class="text-green-500 flex items-center text-sm font-medium">
              <i class="fas fa-arrow-up mr-1"></i> 18%
            </span>
            <span class="text-gray-400 text-sm ml-2">vs previous month</span>
          </div>
        </div>
      </div>

      <div class="dashboard-card bg-white rounded-xl shadow-md p-6">
        <div class="flex justify-between items-start">
          <div>
            <h3 class="text-gray-500 text-sm font-medium">Package Bookings</h3>
            <p class="text-3xl font-bold text-gray-800 mt-1" id="total-packages">-</p>
          </div>
          <div class="p-3 rounded-full bg-amber-100 text-amber-600">
            <i class="fas fa-box"></i>
          </div>
        </div>
        <div class="mt-4">
          <div class="flex items-center">
            <span class="text-red-500 flex items-center text-sm font-medium">
              <i class="fas fa-arrow-down mr-1"></i> 5%
            </span>
            <span class="text-gray-400 text-sm ml-2">vs previous month</span>
          </div>
        </div>
      </div>

      <div class="dashboard-card bg-white rounded-xl shadow-md p-6">
        <div class="flex justify-between items-start">
          <div>
            <h3 class="text-gray-500 text-sm font-medium">Total Users</h3>
            <p class="text-3xl font-bold text-gray-800 mt-1" id="total-users">-</p>
          </div>
          <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
            <i class="fas fa-users"></i>
          </div>
        </div>
        <div class="mt-4">
          <div class="flex items-center">
            <span class="text-green-500 flex items-center text-sm font-medium">
              <i class="fas fa-arrow-up mr-1"></i> 12%
            </span>
            <span class="text-gray-400 text-sm ml-2">vs previous month</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Section Row 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
      <!-- Bookings by Month Chart -->
      <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Bookings by Month</h2>
        <div class="chart-container">
          <canvas id="bookingsByMonthChart"></canvas>
        </div>
      </div>

      <!-- Flight Routes Chart -->
      <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Popular Flight Routes</h2>
        <div class="chart-container">
          <canvas id="flightRoutesChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Charts Section Row 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
      <!-- Airlines Distribution Chart -->
      <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Airlines Distribution</h2>
        <div class="chart-container">
          <canvas id="airlinesChart"></canvas>
        </div>
      </div>

      <!-- Booking Status Chart -->
      <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Booking Status</h2>
        <div class="chart-container">
          <canvas id="bookingStatusChart"></canvas>
        </div>
      </div>

      <!-- Transportation Types Chart -->
      <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Transportation by Type</h2>
        <div class="chart-container">
          <canvas id="transportationTypeChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Charts Section Row 3 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
      <!-- Payment Status Chart -->
      <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Payment Status</h2>
        <div class="chart-container">
          <canvas id="paymentStatusChart"></canvas>
        </div>
      </div>

      <!-- Taxi Routes Price Comparison -->
      <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Taxi Routes Price Comparison</h2>
        <div class="chart-container">
          <canvas id="taxiRoutesChart"></canvas>
        </div>
      </div>
    </div>

  </main>

  <!-- Footer -->
  <footer class="bg-gray-800 text-white py-8">
    <div class="container mx-auto px-4">
      <div class="flex flex-col md:flex-row justify-between items-center">
        <div class="mb-4 md:mb-0">
          <h3 class="text-xl font-bold">Ummrah Travel Analytics</h3>
          <p class="text-gray-400">Real-time analytics for your travel business</p>
        </div>
        <div class="flex space-x-4">
          <a href="#" class="hover:text-blue-400 transition"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="hover:text-blue-400 transition"><i class="fab fa-twitter"></i></a>
          <a href="#" class="hover:text-blue-400 transition"><i class="fab fa-instagram"></i></a>
          <a href="#" class="hover:text-blue-400 transition"><i class="fab fa-linkedin-in"></i></a>
        </div>
      </div>
      <hr class="border-gray-700 my-6">
      <div class="flex flex-col md:flex-row justify-between items-center">
        <p class="text-gray-400">&copy; <?php echo date('Y'); ?> Ummrah Travel. All rights reserved.</p>
        <div class="flex space-x-4 mt-4 md:mt-0">
          <a href="#" class="text-gray-400 hover:text-white transition">Privacy Policy</a>
          <a href="#" class="text-gray-400 hover:text-white transition">Terms of Service</a>
          <a href="#" class="text-gray-400 hover:text-white transition">Contact Us</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- JavaScript for the charts -->
  <script>
    // Parse JSON data from PHP
    const overviewStats = <?php echo $overviewStatsJSON; ?>;
    const flightRoutes = <?php echo $flightRoutesJSON; ?>;
    const airlinesDistribution = <?php echo $airlinesDistributionJSON; ?>;
    const bookingsByStatus = <?php echo $bookingsByStatusJSON; ?>;
    const transportationByType = <?php echo $transportationByTypeJSON; ?>;
    const bookingsByMonth = <?php echo $bookingsByMonthJSON; ?>;
    const paymentStatus = <?php echo $paymentStatusJSON; ?>;
    const taxiRoutesAnalysis = <?php echo $taxiRoutesAnalysisJSON; ?>;
    const recentBookings = <?php echo $recentBookingsJSON; ?>;

    // Chart color palettes
    const primaryColors = [
      '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
      '#ec4899', '#06b6d4', '#84cc16', '#6366f1', '#f97316'
    ];

    const secondaryColors = [
      '#93c5fd', '#6ee7b7', '#fcd34d', '#fca5a5', '#c4b5fd',
      '#f9a8d4', '#67e8f9', '#bef264', '#a5b4fc', '#fdba74'
    ];

    // Helper to format dates
    const formatMonth = (monthStr) => {
      if (!monthStr || monthStr.length < 7) return monthStr;
      const [year, month] = monthStr.split('-');
      const date = new Date(year, month - 1);
      return date.toLocaleDateString('en-US', {
        month: 'short',
        year: 'numeric'
      });
    };

    // Wait for DOM content to be loaded
    document.addEventListener('DOMContentLoaded', function() {
      // Hide loader when page is ready
      setTimeout(() => {
        document.getElementById('loader').style.display = 'none';
      }, 800);

      // Update overview stats
      document.getElementById('total-flights').textContent = overviewStats.flights;
      document.getElementById('total-flight-bookings').textContent = overviewStats.flight_bookings;
      document.getElementById('total-transportation').textContent = overviewStats.transportation_bookings;
      document.getElementById('total-packages').textContent = overviewStats.package_bookings;
      document.getElementById('total-users').textContent = overviewStats.users;

      // Create Bookings by Month Chart
      const bookingsByMonthCtx = document.getElementById('bookingsByMonthChart').getContext('2d');
      new Chart(bookingsByMonthCtx, {
        type: 'line',
        data: {
          labels: bookingsByMonth.map(item => formatMonth(item.month)),
          datasets: [{
              label: 'Flight Bookings',
              data: bookingsByMonth.map(item => item.flights),
              borderColor: '#3b82f6',
              backgroundColor: 'rgba(59, 130, 246, 0.1)',
              tension: 0.4,
              fill: true
            },
            {
              label: 'Transportation Bookings',
              data: bookingsByMonth.map(item => item.transport),
              borderColor: '#10b981',
              backgroundColor: 'rgba(16, 185, 129, 0.1)',
              tension: 0.4,
              fill: true
            },
            {
              label: 'Package Bookings',
              data: bookingsByMonth.map(item => item.packages),
              borderColor: '#f59e0b',
              backgroundColor: 'rgba(245, 158, 11, 0.1)',
              tension: 0.4,
              fill: true
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'top',
            },
            tooltip: {
              mode: 'index',
              intersect: false
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                precision: 0
              }
            }
          }
        }
      });

      // Create Flight Routes Chart
      const flightRoutesCtx = document.getElementById('flightRoutesChart').getContext('2d');
      new Chart(flightRoutesCtx, {
        type: 'bar',
        data: {
          labels: flightRoutes.map(route => route.route),
          datasets: [{
            label: 'Number of Flights',
            data: flightRoutes.map(route => route.count),
            backgroundColor: primaryColors,
            borderColor: 'rgba(255, 255, 255, 0.5)',
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                precision: 0
              }
            }
          }
        }
      });

      // Create Airlines Distribution Chart
      const airlinesCtx = document.getElementById('airlinesChart').getContext('2d');
      new Chart(airlinesCtx, {
        type: 'doughnut',
        data: {
          labels: airlinesDistribution.map(airline => airline.airline),
          datasets: [{
            data: airlinesDistribution.map(airline => airline.count),
            backgroundColor: primaryColors,
            borderColor: 'white',
            borderWidth: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom'
            }
          },
          cutout: '65%'
        }
      });

      // Create Booking Status Chart
      const bookingStatusCtx = document.getElementById('bookingStatusChart').getContext('2d');
      new Chart(bookingStatusCtx, {
        type: 'pie',
        data: {
          labels: bookingsByStatus.map(status => status.status),
          datasets: [{
            data: bookingsByStatus.map(status => status.count),
            backgroundColor: primaryColors,
            borderColor: 'white',
            borderWidth: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                boxWidth: 12
              }
            }
          }
        }
      });

      // Create Transportation Type Chart
      const transportationTypeCtx = document.getElementById('transportationTypeChart').getContext('2d');
      new Chart(transportationTypeCtx, {
        type: 'polarArea',
        data: {
          labels: transportationByType.map(type => type.type),
          datasets: [{
            data: transportationByType.map(type => type.count),
            backgroundColor: primaryColors.map(color => color + '99')
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom'
            }
          },
          scales: {
            r: {
              ticks: {
                display: false
              }
            }
          }
        }
      });

      // Create Payment Status Chart
      const paymentStatusCtx = document.getElementById('paymentStatusChart').getContext('2d');
      new Chart(paymentStatusCtx, {
        type: 'bar',
        data: {
          labels: paymentStatus.map(status => status.status),
          datasets: [{
            label: 'Count',
            data: paymentStatus.map(status => status.count),
            backgroundColor: secondaryColors
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          indexAxis: 'y',
          plugins: {
            legend: {
              display: false
            }
          },
          scales: {
            x: {
              beginAtZero: true,
              ticks: {
                precision: 0
              }
            }
          }
        }
      });

      // Create Taxi Routes Chart
      const taxiRoutesCtx = document.getElementById('taxiRoutesChart').getContext('2d');
      new Chart(taxiRoutesCtx, {
        type: 'bar',
        data: {
          labels: taxiRoutesAnalysis.slice(0, 7).map(route => route.route),
          datasets: [{
              label: 'Camry/Sonata',
              data: taxiRoutesAnalysis.slice(0, 7).map(route => route.camry),
              backgroundColor: '#3b82f6',
              stack: 'Stack 0'
            },
            {
              label: 'Starex/Staria',
              data: taxiRoutesAnalysis.slice(0, 7).map(route => route.starex),
              backgroundColor: '#10b981',
              stack: 'Stack 0'
            },
            {
              label: 'Hiace',
              data: taxiRoutesAnalysis.slice(0, 7).map(route => route.hiace),
              backgroundColor: '#f59e0b',
              stack: 'Stack 0'
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'top'
            },
            tooltip: {
              mode: 'index',
              intersect: false
            }
          },
          scales: {
            x: {
              stacked: true
            },
            y: {
              stacked: false
            }
          }
        }
      });

      // Populate recent bookings table
      const tableBody = document.getElementById('recent-bookings-table');
      tableBody.innerHTML = '';

      if (recentBookings.length === 0) {
        tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No bookings found</td>
                    </tr>
                `;
      } else {
        recentBookings.forEach(booking => {
          const statusClass = booking.status === 'pending' ?
            'bg-yellow-100 text-yellow-800' :
            booking.status === 'confirmed' ?
            'bg-green-100 text-green-800' :
            'bg-red-100 text-red-800';

          tableBody.innerHTML += `
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-medium">#${booking.id}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                ${booking.passenger}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                ${booking.route}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                ${booking.airline} (${booking.flight})
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                ${new Date(booking.departure).toLocaleDateString()}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}">
                                    ${booking.status}
                                </span>
                            </td>
                        </tr>
                    `;
        });
      }

      // Export functionality
      document.getElementById('export-pdf').addEventListener('click', function() {
        alert('Exporting analytics dashboard as PDF...');
        // Here you would implement actual PDF export functionality
        // This typically would involve server-side processing
      });
    });

    // Real-time data refresh - every 5 minutes
    setInterval(function() {
      window.location.reload();
    }, 300000); // 5 minutes
  </script>