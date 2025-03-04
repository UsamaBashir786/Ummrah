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

// Fetch flight bookings for the current user
$flights_query = "SELECT fb.*, f.flight_number, f.departure_city, f.arrival_city, f.departure_date, f.departure_time, 
                         f.economy_seats, f.business_seats, f.first_class_seats
                  FROM flight_book fb
                  JOIN flights f ON fb.flight_id = f.id
                  WHERE fb.user_id = ?";
$flights_stmt = $conn->prepare($flights_query);
$flights_stmt->bind_param("i", $user_id);
$flights_stmt->execute();
$flights_result = $flights_stmt->get_result();

// Fetch transportation bookings for the current user
$transport_query = "SELECT tb.*, t.transport_name
                    FROM transportation_bookings tb
                    JOIN transportation t ON tb.transport_id = t.id
                    WHERE tb.user_id = ?";
$transport_stmt = $conn->prepare($transport_query);
$transport_stmt->bind_param("i", $user_id);
$transport_stmt->execute();
$transport_result = $transport_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include '../includes/css-links.php' ?>
  <link rel="stylesheet" href="../assets/css/output.css">
  <title>User Dashboard</title>
</head>

<body class="bg-gray-100">
  <?php include 'includes/sidebar.php'; ?>

  <div class="main-content p-8">
    <div class="container mx-auto px-4 py-8">
      <!-- Booked Flights Section -->
      <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">Booked Flights</h2>
        <div class="overflow-x-auto">
          <?php if ($flights_result->num_rows > 0) : ?>
            <table class="min-w-full bg-white">
              <thead>
                <tr>
                  <th class="px-6 py-3 border-b-2 border-gray-300 text-left leading-4 text-gray-600">Flight Number</th>
                  <th class="px-6 py-3 border-b-2 border-gray-300 text-left leading-4 text-gray-600">Departure City</th>
                  <th class="px-6 py-3 border-b-2 border-gray-300 text-left leading-4 text-gray-600">Arrival City</th>
                  <th class="px-6 py-3 border-b-2 border-gray-300 text-left leading-4 text-gray-600">Departure Date</th>
                  <th class="px-6 py-3 border-b-2 border-gray-300 text-left leading-4 text-gray-600">Departure Time</th>
                  <th class="px-6 py-3 border-b-2 border-gray-300 text-left leading-4 text-gray-600">Economy Seats</th>
                  <th class="px-6 py-3 border-b-2 border-gray-300 text-left leading-4 text-gray-600">Business Seats</th>
                  <th class="px-6 py-3 border-b-2 border-gray-300 text-left leading-4 text-gray-600">First Class Seats</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($flight = $flights_result->fetch_assoc()) : ?>
                  <tr>
                    <td class="px-6 py-4 border-b border-gray-300"><?php echo htmlspecialchars($flight['flight_number']); ?></td>
                    <td class="px-6 py-4 border-b border-gray-300"><?php echo htmlspecialchars($flight['departure_city']); ?></td>
                    <td class="px-6 py-4 border-b border-gray-300"><?php echo htmlspecialchars($flight['arrival_city']); ?></td>
                    <td class="px-6 py-4 border-b border-gray-300"><?php echo htmlspecialchars($flight['departure_date']); ?></td>
                    <td class="px-6 py-4 border-b border-gray-300"><?php echo htmlspecialchars($flight['departure_time']); ?></td>
                    <td class="px-6 py-4 border-b border-gray-300"><?php echo htmlspecialchars($flight['economy_seats']); ?></td>
                    <td class="px-6 py-4 border-b border-gray-300"><?php echo htmlspecialchars($flight['business_seats']); ?></td>
                    <td class="px-6 py-4 border-b border-gray-300"><?php echo htmlspecialchars($flight['first_class_seats']); ?></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          <?php else : ?>
            <p class="text-gray-600">No booked flights found.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Booked Transportation Section -->
      <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">Booked Transportation</h2>
        <div class="overflow-x-auto">
          <?php if ($transport_result->num_rows > 0) : ?>
            <table class="min-w-full bg-white">
              <thead>
                <tr>
                  <th class="px-6 py-3 border-b-2 border-gray-300 text-left leading-4 text-gray-600">Transportation Name</th>
                  <th class="px-6 py-3 border-b-2 border-gray-300 text-left leading-4 text-gray-600">Booking Date</th>
                  <th class="px-6 py-3 border-b-2 border-gray-300 text-left leading-4 text-gray-600">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($transport = $transport_result->fetch_assoc()) : ?>
                  <tr>
                    <td class="px-6 py-4 border-b border-gray-300"><?php echo htmlspecialchars($transport['transport_name']); ?></td>
                    <td class="px-6 py-4 border-b border-gray-300"><?php echo htmlspecialchars($transport['booking_date']); ?></td>
                    <td class="px-6 py-4 border-b border-gray-300"><?php echo htmlspecialchars($transport['status']); ?></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          <?php else : ?>
            <p class="text-gray-600">No booked transportation found.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

</body>

</html>