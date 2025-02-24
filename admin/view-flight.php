<?php
session_start();
include 'connection/connection.php';

// Verify admin is logged in
if (!isset($_SESSION['admin_email'])) {
  header("Location: admin/login.php");
  exit();
}

// Fetch flight details
$query = "SELECT * FROM flights ORDER BY departure_date DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
</head>

<body class="bg-gray-50">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main flex-1 flex flex-col">
      <!-- Navbar -->
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <button class="md:hidden text-gray-800" id="menu-btn">
          <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-xl font-semibold">
          <i class="text-teal-600 fas fa-plane mx-2"></i> View Flights
        </h1>
      </div>

      <div class="overflow-auto container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="mx-auto bg-white p-4 sm:p-8 rounded-lg shadow-lg">
          <h2 class="text-xl sm:text-2xl font-bold text-teal-600">
            <i class="fas fa-list mr-2"></i>Flight List
          </h2>

          <div class="overflow-x-auto -mx-4 sm:mx-0 mt-4">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Flight Details</th>
                  <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Route</th>
                  <th class="hidden sm:table-cell px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Schedule</th>
                  <th class="hidden sm:table-cell px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Capacity</th>
                  <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                  <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                  <tr class="hover:bg-gray-50">
                    <td class="px-3 sm:px-6 py-4">
                      <div class="text-xs sm:text-sm font-medium text-gray-900"><?php echo $row['airline_name']; ?></div>
                      <div class="text-xs sm:text-sm text-gray-500"><?php echo $row['flight_number']; ?></div>
                    </td>
                    <td class="px-3 sm:px-6 py-4">
                      <div class="text-xs sm:text-sm text-gray-900"><?php echo $row['departure_city'] . " → " . $row['arrival_city']; ?></div>
                    </td>
                    <td class="hidden sm:table-cell px-3 sm:px-6 py-4">
                      <div class="text-xs sm:text-sm text-gray-900"><?php echo date("M d, Y", strtotime($row['departure_date'])); ?></div>
                      <div class="text-xs sm:text-sm text-gray-500"><?php echo date("h:i A", strtotime($row['departure_time'])); ?></div>
                    </td>
                    <td class="hidden sm:table-cell px-3 sm:px-6 py-4">
                      <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                        <?php echo $row['economy_seats']; ?> Economy
                      </span>
                    </td>
                    <td class="px-3 sm:px-6 py-4">
                      <div class="text-xs sm:text-sm text-gray-900">$<?php echo number_format($row['economy_price'], 2); ?></div>
                    </td>
                    <td class="px-3 sm:px-6 py-4">
                      <div class="flex space-x-2">
                        <a href="edit_flight.php?id=<?php echo $row['id']; ?>" class="text-teal-600 hover:text-teal-900">
                          <i class="fas fa-edit"></i>
                        </a>
                        <a href="javascript:void(0);" class="text-red-600 hover:text-red-900" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                          <i class="fas fa-trash"></i>
                        </a>

                      </div>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php include 'includes/js-links.php'; ?>
  <script>
    function confirmDelete(flightId) {
      Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, delete it!"
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = "delete_flight.php?id=" + flightId;
        }
      });
    }
  </script>
</body>

</html>