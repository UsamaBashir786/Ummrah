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
  <!-- Main Content -->
  <div class="main-content p-8">
    <div class="container mx-auto px-4 py-8">
      <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center space-x-4 mb-6">
          <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="w-20 h-20 rounded-full object-cover">
          <div>
            <h1 class="text-2xl font-bold text-gray-800">Welcome, <?php echo htmlspecialchars($user['full_name']); ?></h1>
            <p class="text-gray-600">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="bg-gray-50 p-4 rounded-lg">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Personal Information</h2>
            <div class="space-y-3">
              <p><span class="font-medium">Email:</span> <?php echo htmlspecialchars($user['email']); ?></p>
              <p><span class="font-medium">Phone:</span> <?php echo htmlspecialchars($user['phone_number']); ?></p>
              <p><span class="font-medium">Date of Birth:</span> <?php echo date('F d, Y', strtotime($user['date_of_birth'])); ?></p>
              <p><span class="font-medium">Gender:</span> <?php echo htmlspecialchars($user['gender']); ?></p>
              <p><span class="font-medium">Address:</span> <?php echo htmlspecialchars($user['address']); ?></p>
            </div>
          </div>

          <div class="bg-gray-50 p-4 rounded-lg">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Quick Actions</h2>
            <div class="space-y-3">
              <a href="edit-profile.php" class="block w-full bg-teal-600 text-white text-center py-2 rounded-lg hover:bg-teal-700 transition duration-300">Edit Profile</a>
              <a href="my-bookings.php" class="block w-full bg-blue-600 text-white text-center py-2 rounded-lg hover:bg-blue-700 transition duration-300">My Bookings</a>
              <a href="../logout.php" class="block w-full bg-red-600 text-white text-center py-2 rounded-lg hover:bg-red-700 transition duration-300">Logout</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>



</body>
</html>
