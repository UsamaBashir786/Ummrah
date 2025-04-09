<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}

require_once '../connection/connection.php';

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$profile_image = $user['profile_image'];
if (!empty($profile_image) && file_exists("../" . $profile_image)) {
  $profile_image = "../" . $profile_image;
}

// Direct flights query
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

// Package flights query
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

// Standalone flights query
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

// Combine all flights into an array for easier processing
$all_flights = [];
while ($row = $direct_flights->fetch_assoc()) $all_flights[] = $row;
while ($row = $package_flights->fetch_assoc()) $all_flights[] = $row;
while ($row = $standalone_flights->fetch_assoc()) $all_flights[] = $row;

// Calculate stats
$total_flights = count($all_flights);
$upcoming_flights = count(array_filter($all_flights, fn($f) => $f['flight_status'] === 'upcoming'));
$completed_flights = count(array_filter($all_flights, fn($f) => $f['flight_status'] === 'completed'));
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include '../includes/css-links.php' ?>
  <link rel="stylesheet" href="../assets/css/output.css">
  <title>My Flight Bookings</title>
  <style>
    .booking-table {
      width: 100%;
      border-collapse: collapse;
    }

    .booking-table th,
    .booking-table td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    .booking-table th {
      background-color: #f8f9fa;
      font-weight: 600;
    }

    .status-badge {
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 500;
      text-transform: capitalize;
    }

    .status-badge.upcoming {
      background-color: #fef3c7;
      color: #d97706;
    }

    .status-badge.in-progress {
      background-color: #dbeafe;
      color: #2563eb;
    }

    .status-badge.completed {
      background-color: #d1fae5;
      color: #059669;
    }

    .status-badge.cancelled {
      background-color: #fee2e2;
      color: #dc2626;
    }

    .flight-card {
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      padding: 16px;
      margin-bottom: 16px;
    }

    .flight-details-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background: white;
      padding: 20px;
      border-radius: 8px;
      width: 90%;
      max-width: 500px;
    }

    .flight-details-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      justify-content: center;
      align-items: center;
      z-index: 1000;
      overflow-y: auto;
      /* Allow scrolling if content overflows */
      padding: 20px;
      /* Add padding to prevent content from touching edges */
    }

    .modal-content {
      background: white;
      padding: 20px;
      border-radius: 8px;
      width: 90%;
      max-width: 500px;
      max-height: 80vh;
      /* Limit height to 80% of viewport height */
      overflow-y: auto;
      /* Enable scrolling within the modal if content overflows */
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    /* Ensure modal content sections are properly spaced */
    .modal-content h3 {
      color: #1e40af;
      font-size: 1.25rem;
      font-weight: 600;
      margin-top: 1.5rem;
      margin-bottom: 0.5rem;
    }

    .modal-content p {
      margin: 0.25rem 0;
      color: #4b5563;
    }

    .modal-content .flight-route {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin: 1rem 0;
    }

    .modal-content .flight-route .time {
      font-size: 1.5rem;
      font-weight: 600;
    }

    .modal-content .flight-route .city {
      font-size: 1rem;
      color: #6b7280;
    }

    .modal-content .flight-route .duration {
      text-align: center;
      color: #6b7280;
    }

    .modal-content .status-badge {
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 500;
      text-transform: capitalize;
    }

    .modal-content .status-badge.upcoming {
      background-color: #fef3c7;
      color: #d97706;
    }
  </style>
</head>

<body class="bg-gray-100">
  <?php include 'includes/sidebar.php'; ?>

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
        <div class="flex justify-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="main-content p-8">
    <div class="container mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold mb-6">My Flight Bookings</h1>
      <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <?php
        $has_flights = ($direct_flights->num_rows > 0 || $package_flights->num_rows > 0 || $standalone_flights->num_rows > 0);
        if ($has_flights) {
        ?>
          <!-- Stats Cards -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg shadow">
              <p class="text-sm text-gray-600">Total Flights</p>
              <p class="text-2xl font-bold text-blue-600"><?php echo $total_flights; ?></p>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg shadow">
              <p class="text-sm text-gray-600">Upcoming Flights</p>
              <p class="text-2xl font-bold text-yellow-600"><?php echo $upcoming_flights; ?></p>
            </div>
            <div class="bg-green-50 p-4 rounded-lg shadow">
              <p class="text-sm text-gray-600">Completed Flights</p>
              <p class="text-2xl font-bold text-green-600"><?php echo $completed_flights; ?></p>
            </div>
          </div>

          <!-- Desktop Table -->
          <div class="overflow-x-auto hidden md:block">
            <table class="booking-table">
              <thead>
                <tr>
                  <th>Flight Number</th>
                  <th>Airline</th>
                  <th>Departure</th>
                  <th>Arrival</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Details</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($all_flights as $flight) { ?>
                  <tr>
                    <td><?php echo htmlspecialchars($flight['flight_number']); ?></td>
                    <td><?php echo htmlspecialchars($flight['airline_name']); ?></td>
                    <td><?php echo htmlspecialchars($flight['departure_city']); ?></td>
                    <td><?php echo htmlspecialchars($flight['arrival_city']); ?></td>
                    <td><?php echo htmlspecialchars($flight['departure_date'] . ' ' . $flight['departure_time']); ?></td>
                    <td><span class="status-badge <?php echo $flight['flight_status']; ?>"><?php echo $flight['flight_status']; ?></span></td>
                    <td>
                      <button onclick="viewFlightDetails(<?php echo $flight['id']; ?>)" class="text-blue-600 hover:underline">View</button>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>

          <!-- Mobile View -->
          <div class="block md:hidden">
            <?php foreach ($all_flights as $flight) { ?>
              <div class="flight-card">
                <div class="flex justify-between">
                  <p class="font-bold"><?php echo htmlspecialchars($flight['flight_number']); ?></p>
                  <span class="status-badge <?php echo $flight['flight_status']; ?>"><?php echo $flight['flight_status']; ?></span>
                </div>
                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($flight['airline_name']); ?></p>
                <p class="text-sm mt-2"><strong>From:</strong> <?php echo htmlspecialchars($flight['departure_city']); ?></p>
                <p class="text-sm"><strong>To:</strong> <?php echo htmlspecialchars($flight['arrival_city']); ?></p>
                <p class="text-sm"><strong>Date:</strong> <?php echo htmlspecialchars($flight['departure_date'] . ' ' . $flight['departure_time']); ?></p>
                <button onclick="viewFlightDetails(<?php echo $flight['id']; ?>)" class="mt-2 text-blue-600 hover:underline">View Details</button>
              </div>
            <?php } ?>
          </div>
        <?php
        } else {
        ?>
          <div class="bg-gray-50 rounded-lg p-8 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
            <p class="text-xl text-gray-600 mb-4">You haven't booked any flights yet.</p>
            <p class="text-gray-500 mb-6">Start your journey by booking your first flight!</p>
            <a href="../flights.php" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200 transform hover:scale-105 shadow-md">
              Book Your First Flight
            </a>
          </div>
        <?php
        }
        ?>
      </div>
    </div>
  </div>

  <script>
    function updateCountdown() {
      // Add countdown logic if needed (e.g., for upcoming flights)
    }
    setInterval(updateCountdown, 1000);
    updateCountdown();

    function viewFlightDetails(flightId) {
      const modal = document.getElementById('flightDetailsModal');
      const contentDiv = document.getElementById('flightDetailsContent');
      modal.style.display = 'flex';
      fetch(`get_flight_details.php?flight_id=${flightId}`)
        .then(response => response.text())
        .then(data => contentDiv.innerHTML = data)
        .catch(error => {
          contentDiv.innerHTML = `<div class="bg-red-100 p-4 rounded-lg text-red-700">Error loading flight details.</div>`;
          console.error('Error:', error);
        });
    }

    function closeModal() {
      document.getElementById('flightDetailsModal').style.display = 'none';
    }

    window.onclick = function(event) {
      const modal = document.getElementById('flightDetailsModal');
      if (event.target === modal) modal.style.display = 'none';
    }
  </script>
</body>

</html>