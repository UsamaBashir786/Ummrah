<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php
if (isset($_SESSION['profile_updated']) && $_SESSION['profile_updated']) {
  unset($_SESSION['profile_updated']);
  ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      Swal.fire({
        title: 'Success!',
        text: 'Profile updated successfully',
        icon: 'success',
        confirmButtonColor: '#0D9488',
        timer: 2000
      });
    });
  </script>
  <?php
}
?>

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
  <title>My Profile</title>
</head>

<body class="bg-gray-100">
  <?php include 'includes/sidebar.php'; ?>
  
  <!-- Main Content -->
  <div class="main-content p-8">
    <div class="container mx-auto px-4 py-8">
      <div class="bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">My Profile</h1>
        
        <div class="flex flex-col md:flex-row gap-8">
          <!-- Profile Image Section -->
          <div class="w-full md:w-1/3">
            <div class="bg-gray-50 p-6 rounded-lg text-center">
              <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="w-48 h-48 rounded-full object-cover mx-auto mb-4">
              <h2 class="text-xl font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($user['full_name']); ?></h2>
              <p class="text-gray-600 mb-4">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
              <a href="edit-profile.php" class="inline-flex items-center bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700 transition duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit Profile
              </a>
            </div>
          </div>

          <!-- Profile Details Section -->
          <div class="w-full md:w-2/3">
            <div class="bg-gray-50 p-6 rounded-lg">
              <h2 class="text-xl font-semibold text-gray-800 mb-4">Personal Information</h2>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                  <div>
                    <label class="block text-gray-600 text-sm font-medium mb-1">Full Name</label>
                    <p class="text-gray-800"><?php echo htmlspecialchars($user['full_name']); ?></p>
                  </div>
                  <div>
                    <label class="block text-gray-600 text-sm font-medium mb-1">Email Address</label>
                    <p class="text-gray-800"><?php echo htmlspecialchars($user['email']); ?></p>
                  </div>
                  <div>
                    <label class="block text-gray-600 text-sm font-medium mb-1">Phone Number</label>
                    <p class="text-gray-800"><?php echo htmlspecialchars($user['phone_number']); ?></p>
                  </div>
                </div>
                
                <div class="space-y-4">
                  <div>
                    <label class="block text-gray-600 text-sm font-medium mb-1">Date of Birth</label>
                    <p class="text-gray-800"><?php echo date('F d, Y', strtotime($user['date_of_birth'])); ?></p>
                  </div>
                  <div>
                    <label class="block text-gray-600 text-sm font-medium mb-1">Gender</label>
                    <p class="text-gray-800"><?php echo htmlspecialchars($user['gender']); ?></p>
                  </div>
                  <div>
                    <label class="block text-gray-600 text-sm font-medium mb-1">Address</label>
                    <p class="text-gray-800"><?php echo htmlspecialchars($user['address']); ?></p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Account Actions -->
            <div class="mt-6 flex gap-4">
              <a href="change-password.php" class="flex-1 bg-blue-600 text-white text-center py-2 rounded-lg hover:bg-blue-700 transition duration-300 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                Change Password
              </a>
              <a href="#" onclick="confirmLogout()" class="flex-1 bg-red-600 text-white text-center py-2 rounded-lg hover:bg-red-700 transition duration-300 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Logout
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    function confirmLogout() {
      Swal.fire({
        title: 'Are you sure?',
        text: "You will be logged out of your account!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, logout!'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = '../logout.php';
        }
      })
    }
  </script>
</body>
</html>
