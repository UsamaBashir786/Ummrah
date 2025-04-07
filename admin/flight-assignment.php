<?php
session_name("admin_session");
session_start();
include 'connection/connection.php';

if (!isset($_SESSION['admin_email'])) {
  header("Location: admin/login.php");
  exit();
}


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
      </div>
      <div class="container mx-auto px-4 py-8 overflow-auto">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6">
          <h1 class="text-2xl md:text-3xl font-bold text-teal-600 mb-4 md:mb-0">Flight Bookings Management</h1>
          <a href="index.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-500">
            Back to Dashboard
          </a>
        </div>

        <div class="mb-6">
          <div class="bg-white p-4 rounded-lg shadow-sm flex flex-wrap gap-2">
            <span class="font-medium">Filter:</span>
            <button id="filter-all" class="px-4 py-2 bg-teal-600 text-white rounded-lg filter-btn">All</button>
            <button id="filter-upcoming" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg filter-btn">Upcoming</button>
            <button id="filter-in-progress" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg filter-btn">In Progress</button>
            <button id="filter-completed" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg filter-btn">Completed</button>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-teal-600 text-white">
              <tr>
                <th class="px-4 py-3 text-left font-medium uppercase">Booking ID</th>
                <th class="px-4 py-3 text-left font-medium uppercase">User</th>
                <th class="px-4 py-3 text-left font-medium uppercase">Flight</th>
                <th class="px-4 py-3 text-left font-medium uppercase">Journey</th>
                <th class="px-4 py-3 text-left font-medium uppercase">Booking Time</th>
                <th class="px-4 py-3 text-left font-medium uppercase">Status</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php if (count($bookings) > 0): ?>
                <?php foreach ($bookings as $booking): ?>
                  <tr class="booking-row" data-status="<?php echo strtolower($booking['flight_status']); ?>">
                    <td class="px-4 py-2">#<?php echo $booking['id']; ?></td>
                    <td class="px-4 py-2"> <?php echo htmlspecialchars($booking['full_name']); ?> </td>
                    <td class="px-4 py-2"> <?php echo htmlspecialchars($booking['airline_name']); ?> </td>
                    <td class="px-4 py-2"> <?php echo htmlspecialchars($booking['departure_city']); ?> → <?php echo htmlspecialchars($booking['arrival_city']); ?> </td>
                    <td class="px-4 py-2"> <?php echo date('M d, Y H:i', strtotime($booking['booking_time'])); ?> </td>
                    <td class="px-4 py-2">
                      <span class="px-2 inline-flex text-xs font-semibold rounded-full 
                        <?php echo $booking['flight_status'] == 'upcoming' ? 'bg-yellow-100 text-yellow-800' : ($booking['flight_status'] == 'in-progress' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'); ?>">
                        <?php echo $booking['flight_status']; ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="px-4 py-3 text-center text-gray-500">No flight bookings found</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <br><br>
        <!-- Section Divider -->
        <div class="bg-gray-200 text-center py-2 mb-6 rounded-lg">
          <h2 class="text-lg font-semibold text-gray-700">Package Booking Details</h2>
        </div>
        <hr class="my-5 border-gray-300 border-t-2 border-dashed">
        <div class="bg-white rounded-lg shadow-md overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-teal-600 text-white">
              <tr>
                <th class="px-4 py-3 text-left font-medium uppercase">Booking ID</th>
                <th class="px-4 py-3 text-left font-medium uppercase">User</th>
                <th class="px-4 py-3 text-left font-medium uppercase">Package</th>
                <th class="px-4 py-3 text-left font-medium uppercase">Flight</th>
                <th class="px-4 py-3 text-left font-medium uppercase">Journey</th>
                <th class="px-4 py-3 text-left font-medium uppercase">Booking Date</th>
                <th class="px-4 py-3 text-left font-medium uppercase">Price</th>
                <th class="px-4 py-3 text-left font-medium uppercase">Status</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php if (count($package_bookings) > 0): ?>
                <?php foreach ($package_bookings as $booking): ?>
                  <tr>
                    <td class="px-4 py-2">#<?php echo $booking['id']; ?></td>
                    <td class="px-4 py-2"> <?php echo htmlspecialchars($booking['full_name']); ?> </td>
                    <td class="px-4 py-2">
                      <strong><?php echo htmlspecialchars($booking['title']); ?></strong>
                      <br><small><?php echo htmlspecialchars($booking['package_type']); ?></small>
                    </td>
                    <td class="px-4 py-2"> <?php echo htmlspecialchars($booking['airline'] . ' - ' . $booking['flight_class']); ?> </td>
                    <td class="px-4 py-2"> <?php echo htmlspecialchars($booking['departure_city']); ?> → <?php echo htmlspecialchars($booking['arrival_city']); ?> </td>
                    <td class="px-4 py-2"> <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?> </td>
                    <td class="px-4 py-2">$<?php echo number_format($booking['price'], 2); ?></td>
                    <td class="px-4 py-2">
                      <span class="px-2 inline-flex text-xs font-semibold rounded-full 
                                            <?php echo $booking['status'] == 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo $booking['status']; ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8" class="px-4 py-3 text-center text-gray-500">No package bookings found</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <script src="assets/js/main.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const filterButtons = document.querySelectorAll('.filter-btn');
      const bookingRows = document.querySelectorAll('.booking-row');

      filterButtons.forEach(button => {
        button.addEventListener('click', function() {
          filterButtons.forEach(btn => {
            btn.classList.remove('bg-teal-600', 'text-white');
            btn.classList.add('bg-gray-300', 'text-gray-700');
          });

          this.classList.remove('bg-gray-300', 'text-gray-700');
          this.classList.add('bg-teal-600', 'text-white');

          const filter = this.id.replace('filter-', '');
          bookingRows.forEach(row => {
            row.style.display = (filter === 'all' || row.dataset.status === filter) ? '' : 'none';
          });
        });
      });
    });
  </script>
</body>

</html>