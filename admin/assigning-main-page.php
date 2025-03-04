<?php
session_start();
include 'connection/connection.php';

if (!isset($_SESSION['admin_email'])) {
  header("Location: admin/login.php");
  exit();
}

// Fetch flight bookings with status updates
$query = "SELECT fb.*, u.full_name, u.email, f.airline_name, f.flight_number, 
          f.departure_city, f.arrival_city, f.departure_date, f.departure_time 
          FROM flight_book fb 
          JOIN users u ON fb.user_id = u.id 
          JOIN flights f ON fb.flight_id = f.id 
          ORDER BY fb.booking_time DESC";

$result = mysqli_query($conn, $query);
$bookings = [];

if ($result) {
  while ($row = mysqli_fetch_assoc($result)) {
    $departure_timestamp = strtotime($row['departure_date'] . ' ' . $row['departure_time']);
    $current_timestamp = time();

    if ($current_timestamp < $departure_timestamp) {
      $new_status = 'Upcoming';
    } elseif ($current_timestamp >= $departure_timestamp && $current_timestamp <= ($departure_timestamp + 2 * 3600)) {
      // Assuming flights take 2 hours to complete (adjust as needed)
      $new_status = 'In Progress';
    } else {
      $new_status = 'Completed';
    }

    // Check if the status has changed
    if ($row['flight_status'] !== $new_status) {
      $update_query = "UPDATE flight_book SET flight_status = '$new_status' WHERE id = " . $row['id'];
      mysqli_query($conn, $update_query);
    }

    $row['flight_status'] = $new_status;
    $bookings[] = $row;
  }
}

// Fetch all users
$user_query = "SELECT id, full_name, email FROM users ORDER BY full_name ASC";
$user_result = mysqli_query($conn, $user_query);
$users = [];

if ($user_result) {
  while ($row = mysqli_fetch_assoc($user_result)) {
    $users[] = $row;
  }
}

// Fetch package bookings with user and package details
$query = "SELECT pb.*, u.full_name, u.email, p.title, p.package_type, p.airline, p.flight_class, 
                 p.departure_city, p.departure_date, p.departure_time, p.arrival_city, p.price, p.package_image
          FROM package_booking pb
          JOIN users u ON pb.user_id = u.id
          JOIN packages p ON pb.package_id = p.id
          ORDER BY pb.booking_date DESC";

$result = mysqli_query($conn, $query);
$package_bookings = [];

if ($result) {
  while ($row = mysqli_fetch_assoc($result)) {
    // Check if the package is already assigned
    $assign_query = "SELECT * FROM package_assign WHERE booking_id = " . $row['id'];
    $assign_result = mysqli_query($conn, $assign_query);
    $row['is_assigned'] = mysqli_num_rows($assign_result) > 0;
    $package_bookings[] = $row;
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel</title>
  <link rel="stylesheet" href="../assets/css/output.css">
  <link rel="stylesheet" href="assets/css/output.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
</head>

<body class="bg-gray-50 font-sans">
  <div class="flex flex-col md:flex-row h-screen">
    <?php include 'includes/sidebar.php' ?>
    <div class="main flex-1 flex flex-col">
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <button class="md:hidden text-gray-800" id="menu-btn">
          <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-xl font-semibold">Dashboard</h1>
        <a href="index.php" class="px-4 py-2  text-dark rounded-lg hover:bg-gray-500">
          <i class="fas fa-arrow-left mr-2"></i>Back
        </a>
      </div>
      <div class="container mx-auto px-4 py-8 overflow-auto">
        <div class="bg-white rounded-lg shadow-md overflow-x-auto">
          <table class="min-w-full bg-white shadow-md rounded-lg overflow-hidden">
            <thead class="bg-gray-200">
              <tr class="text-gray-700 text-sm">
                <th class="px-4 py-3 text-left">User ID</th>
                <th class="px-4 py-3 text-left">Package ID</th>
                <th class="px-4 py-3 text-left hidden sm:table-cell">Booking Date</th>
                <th class="px-4 py-3 text-left">Payment Status</th>
                <th class="px-4 py-3 text-left">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-300">
              <?php foreach ($package_bookings as $booking) : ?>
                <tr class="hover:bg-gray-100">
                  <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($booking['user_id']) ?></td>
                  <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($booking['package_id']) ?></td>
                  <td class="px-4 py-3 text-sm text-gray-900 hidden sm:table-cell"><?= htmlspecialchars($booking['booking_date']) ?></td>
                  <td class="px-4 py-3 text-sm">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold 
              <?= $booking['payment_status'] === 'Paid' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                      <?= htmlspecialchars($booking['payment_status']) ?>
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm">
                    <?php if ($booking['is_assigned']) : ?>
                      <button class="px-3 py-1 bg-gray-300 text-gray-600 rounded cursor-not-allowed flex items-center">
                        <i class="fas fa-check-circle text-gray-500"></i>
                        <span class="hidden sm:inline ml-2">Assigned</span>
                      </button>
                    <?php else : ?>
                      <a href="assigning-action-one.php?booking_id=<?= $booking['id'] ?>"
                        class="px-3 py-1 bg-teal-500 text-white rounded hover:bg-teal-400 items-center">
                        <i class="fas fa-tasks"></i>
                        <span class="hidden sm:inline ml-2 px-2">Assign</span>

                      </a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <br><br>
        <!-- Section Divider -->
        <!-- <hr class="my-5 border-gray-300 border-t-2 border-dashed"> -->
        <div class="bg-white rounded-lg shadow-md overflow-x-auto">
        </div>
      </div>
    </div>
  </div>
  <script src="assets/js/main.js"></script>
</body>

</html>