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

// Fetch package assignments for the current user
$assignments_query = "SELECT pa.*, h.hotel_name, t.transport_name, f.flight_number, pa.seat_type, pa.seat_number, pa.transport_seat_number
                      FROM package_assign pa
                      LEFT JOIN hotels h ON pa.hotel_id = h.id
                      LEFT JOIN transportation t ON pa.transport_id = t.id
                      LEFT JOIN flights f ON pa.flight_id = f.id
                      WHERE pa.user_id = ?";
$assignments_stmt = $conn->prepare($assignments_query);
$assignments_stmt->bind_param("i", $user_id);
$assignments_stmt->execute();
$assignments_result = $assignments_stmt->get_result();
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
      <!-- Assigned Packages Section -->
      <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">Assigned Packages</h2>
        <div class="overflow-x-auto">
          <?php if ($assignments_result->num_rows > 0) : ?>
            <table class="min-w-full bg-white">
              <thead>
                <tr>
                  <th class="px-6 py-3 border-b-2 border-gray-300 text-left leading-4 text-gray-600">Hotel</th>
                  <th class="px-6 py-3 border-b-2 border-gray-300 text-left leading-4 text-gray-600">Transportation</th>
                  <th class="px-6 py-3 border-b-2 border-gray-300 text-left leading-4 text-gray-600">Transport Seat</th>
                  <th class="px-6 py-3 border-b-2 border-gray-300 text-left leading-4 text-gray-600">Flight</th>
                  <th class="px-6 py-3 border-b-2 border-gray-300 text-left leading-4 text-gray-600">Flight Seat Type</th>
                  <th class="px-6 py-3 border-b-2 border-gray-300 text-left leading-4 text-gray-600">Flight Seat Number</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($assignment = $assignments_result->fetch_assoc()) : ?>
                  <tr>
                    <td class="px-6 py-4 border-b border-gray-300"><?php echo htmlspecialchars($assignment['hotel_name']); ?></td>
                    <td class="px-6 py-4 border-b border-gray-300"><?php echo htmlspecialchars($assignment['transport_name']); ?></td>
                    <td class="px-6 py-4 border-b border-gray-300"><?php echo htmlspecialchars($assignment['transport_seat_number']); ?></td>
                    <td class="px-6 py-4 border-b border-gray-300"><?php echo htmlspecialchars($assignment['flight_number']); ?></td>
                    <td class="px-6 py-4 border-b border-gray-300"><?php echo htmlspecialchars($assignment['seat_type']); ?></td>
                    <td class="px-6 py-4 border-b border-gray-300"><?php echo htmlspecialchars($assignment['seat_number']); ?></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          <?php else : ?>
            <p class="text-gray-600">No assigned packages found.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

</body>

</html>