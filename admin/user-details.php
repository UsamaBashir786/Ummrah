<?php
require_once 'connection/connection.php';

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
  // Redirect to users list if no ID provided
  header("Location: all-users.php");
  exit();
}

$userId = intval($_GET['id']);

// Fetch user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

// Check if user exists
if ($result->num_rows === 0) {
  // User not found
  header("Location: all-users.php");
  exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Fetch user's flight bookings
$stmt = $conn->prepare("
    SELECT fb.*, f.flight_number, f.departure_city, f.arrival_city, f.departure_date
    FROM flight_bookings fb
    JOIN flights f ON fb.flight_id = f.id
    WHERE fb.user_id = ?
    ORDER BY fb.booking_date DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$bookingsResult = $stmt->get_result();
$bookings = $bookingsResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <title>User Details - <?php echo htmlspecialchars($user['full_name']); ?></title>
</head>

<body class="bg-gray-100">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main flex-1 flex flex-col overflow-auto">
      <!-- Navbar -->
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <button class="md:hidden text-gray-800" id="menu-btn">
          <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-xl font-semibold">
          <i class="text-teal-600 fa fa-user mx-2"></i> User Details
        </h1>
        <a href="all-users.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 px-4 rounded">
          <i class="fas fa-arrow-left mr-2"></i> Back to Users
        </a>
      </div>

      <div class="container mx-auto p-4 sm:p-6 overflow-y-auto">
        <div class="bg-white rounded-lg shadow p-6 mb-6">
          <!-- User Profile Header -->
          <div class="flex flex-col sm:flex-row items-center sm:items-start mb-6 pb-6 border-b">
            <div class="w-32 h-32 mb-4 sm:mb-0 sm:mr-6">
              <img class="w-full h-full rounded-full object-cover border-4 border-teal-500"
                src="../<?php echo isset($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'user/uploads/default.png'; ?>"
                alt="<?php echo htmlspecialchars($user['full_name']); ?>" />
            </div>
            <div class="text-center sm:text-left">
              <h2 class="text-2xl font-bold text-gray-900">
                <?php echo htmlspecialchars($user['full_name']); ?>
              </h2>
              <p class="text-gray-600 mb-2">
                <i class="fas fa-envelope mr-2"></i>
                <?php echo htmlspecialchars($user['email']); ?>
              </p>
              <p class="text-gray-600 mb-2">
                <i class="fas fa-phone mr-2"></i>
                <?php echo htmlspecialchars($user['phone_number']); ?>
              </p>
              <div class="mt-4">
                <button class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded mr-2"
                  onclick="editUser(<?php echo $user['id']; ?>)">
                  <i class="fas fa-edit mr-2"></i> Edit User
                </button>
                <button class="bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded"
                  onclick="deleteUser(<?php echo $user['id']; ?>)">
                  <i class="fas fa-trash mr-2"></i> Delete User
                </button>
              </div>
            </div>
          </div>

          <!-- User Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
              <h3 class="text-lg font-semibold border-b pb-2">Personal Information</h3>

              <div class="flex">
                <span class="font-medium w-32">Full Name:</span>
                <span><?php echo htmlspecialchars($user['full_name']); ?></span>
              </div>

              <div class="flex">
                <span class="font-medium w-32">Gender:</span>
                <span><?php echo htmlspecialchars($user['gender']); ?></span>
              </div>

              <div class="flex">
                <span class="font-medium w-32">Date of Birth:</span>
                <span><?php echo date('F j, Y', strtotime($user['date_of_birth'])); ?></span>
              </div>

              <div class="flex">
                <span class="font-medium w-32">Address:</span>
                <span><?php echo htmlspecialchars($user['address']); ?></span>
              </div>
            </div>

            <div class="space-y-4">
              <h3 class="text-lg font-semibold border-b pb-2">Contact Information</h3>

              <div class="flex">
                <span class="font-medium w-32">Email:</span>
                <span><?php echo htmlspecialchars($user['email']); ?></span>
              </div>

              <div class="flex">
                <span class="font-medium w-32">Phone:</span>
                <span><?php echo htmlspecialchars($user['phone_number']); ?></span>
              </div>

              <div class="flex">
                <span class="font-medium w-32">User ID:</span>
                <span><?php echo $user['id']; ?></span>
              </div>

              <div class="flex">
                <span class="font-medium w-32">Joined:</span>
                <span><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Flight Bookings Section -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-xl font-semibold mb-4 border-b pb-2">
            <i class="fas fa-plane-departure text-teal-600 mr-2"></i> Flight Bookings
          </h3>

          <?php if (empty($bookings)): ?>
            <div class="bg-blue-50 text-blue-700 p-4 rounded-lg">
              <i class="fas fa-info-circle mr-2"></i> No flight bookings found for this user.
            </div>
          <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <?php foreach ($bookings as $booking): ?>
                <div class="bg-white border rounded-lg shadow-sm hover:shadow-md transition-shadow duration-300 overflow-hidden">
                  <div class="bg-teal-600 text-white px-4 py-2">
                    <div class="flex justify-between items-center">
                      <h4 class="font-semibold">Booking #<?php echo $booking['id']; ?></h4>
                      <span class="text-xs bg-teal-700 px-2 py-1 rounded">
                        <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?>
                      </span>
                    </div>
                  </div>
                  <div class="p-4">
                    <div class="mb-3">
                      <span class="font-semibold text-gray-700">Flight:</span>
                      <span class="text-gray-600 ml-2"><?php echo htmlspecialchars($booking['flight_number']); ?></span>
                    </div>

                    <div class="mb-3">
                      <div class="flex items-center">
                        <div class="w-5/12 text-right text-gray-800">
                          <?php echo htmlspecialchars($booking['departure_city']); ?>
                        </div>
                        <div class="w-2/12 flex justify-center">
                          <div class="w-full h-px bg-gray-300 relative flex items-center">
                            <i class="fas fa-plane text-teal-600 absolute"></i>
                          </div>
                        </div>
                        <div class="w-5/12 text-left text-gray-800">
                          <?php echo htmlspecialchars($booking['arrival_city']); ?>
                        </div>
                      </div>
                    </div>

                    <div class="flex justify-between items-center text-sm">
                      <div>
                        <span class="font-semibold text-gray-700">Departure:</span>
                        <span class="text-gray-600 ml-1">
                          <?php echo date('F j, Y', strtotime($booking['departure_date'])); ?>
                        </span>
                      </div>

                      <a href="booking-details.php?id=<?php echo $booking['id']; ?>"
                        class="text-teal-600 hover:text-teal-700">
                        <!-- <i class="fas fa-eye mr-1"></i> Details -->
                      </a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php include 'includes/js-links.php'; ?>
    </div>
  </div>

  <script>
    function editUser(userId) {
      window.location.href = `edit-user.php?id=${userId}`;
    }

    function deleteUser(userId) {
      Swal.fire({
        title: 'Are you sure?',
        text: "This will delete the user and all their associated data (bookings, etc.). This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading state
          Swal.fire({
            title: 'Deleting...',
            text: 'Please wait while we delete the user and associated data',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          fetch(`delete-user.php?id=${userId}`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              }
            })
            .then(response => {
              if (!response.ok) {
                throw new Error('Network response was not ok');
              }
              return response.json();
            })
            .then(data => {
              if (data.success) {
                Swal.fire({
                  title: 'Deleted!',
                  text: 'User and all associated data have been deleted.',
                  icon: 'success',
                  showConfirmButton: false,
                  timer: 1500
                }).then(() => {
                  window.location.href = 'all-users.php';
                });
              } else {
                throw new Error(data.message || 'Failed to delete user');
              }
            })
            .catch(error => {
              console.error('Error:', error);
              Swal.fire({
                title: 'Error!',
                text: error.message || 'An error occurred while deleting the user',
                icon: 'error',
                confirmButtonColor: '#3085d6'
              });
            });
        }
      });
    }
  </script>
</body>

</html>