<?php
require_once 'connection/connection.php';

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch user data
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full_name = $_POST['full_name'];
  $email = $_POST['email'];
  $phone_number = $_POST['phone_number'];
  $gender = $_POST['gender'];
  $address = $_POST['address'];

  // Check if new image is uploaded
  if (!empty($_FILES['profile_image']['name'])) {
    $target_dir = "../user/uploads/";
    $file_extension = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $profile_image = $target_dir . $new_filename;

    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $profile_image)) {
      $sql = "UPDATE users SET full_name=?, email=?, phone_number=?, gender=?, address=?, profile_image=? WHERE id=?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssssssi", $full_name, $email, $phone_number, $gender, $address, $profile_image, $user_id);
    }
  } else {
    $sql = "UPDATE users SET full_name=?, email=?, phone_number=?, gender=?, address=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $full_name, $email, $phone_number, $gender, $address, $user_id);
  }

  if ($stmt->execute()) {
    $_SESSION['success_message'] = true;
    header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $user_id . "&updated=1");
    exit();
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
</head>

<body class="bg-gray-100">
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
          <i class="text-teal-600 fas fa-user-edit mx-2"></i> Edit User
        </h1>
      </div>

      <div class="overflow-y-scroll container mx-auto p-4 sm:p-6">
        <div class="bg-white rounded-lg shadow p-6">
          <form method="POST" enctype="multipart/form-data" class="w-full max-w-4xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
              <!-- Current Profile Image -->
              <div class="col-span-1 md:col-span-2 flex justify-center py-4">
                <div class="relative">
                  <img src="../<?php echo htmlspecialchars($user['profile_image']); ?>"
                    alt="Current profile"
                    class="w-24 h-24 md:w-32 md:h-32 rounded-full object-cover border-4 border-teal-500" />
                  <label class="absolute bottom-0 right-0 bg-teal-500 rounded-full p-2 cursor-pointer">
                    <i class="fas fa-camera text-white text-sm md:text-base"></i>
                    <input type="file" name="profile_image" class="hidden" accept="image/*" />
                  </label>
                </div>
              </div>

              <!-- Full Name -->
              <div class="form-group">
                <label class="block text-gray-700 text-sm font-bold mb-2">Full Name</label>
                <input type="text"
                  name="full_name"
                  value="<?php echo htmlspecialchars($user['full_name']); ?>"
                  class="w-full px-3 py-2 text-base md:text-lg border rounded-lg focus:outline-none focus:border-teal-500" />
              </div>

              <!-- Email -->
              <div class="form-group">
                <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                <input type="email"
                  name="email"
                  value="<?php echo htmlspecialchars($user['email']); ?>"
                  class="w-full px-3 py-2 text-base md:text-lg border rounded-lg focus:outline-none focus:border-teal-500" />
              </div>

              <!-- Phone Number -->
              <div class="form-group">
                <label class="block text-gray-700 text-sm font-bold mb-2">Phone Number</label>
                <input type="tel"
                  name="phone_number"
                  value="<?php echo htmlspecialchars($user['phone_number']); ?>"
                  class="w-full px-3 py-2 text-base md:text-lg border rounded-lg focus:outline-none focus:border-teal-500" />
              </div>

              <!-- Gender -->
              <div class="form-group">
                <label class="block text-gray-700 text-sm font-bold mb-2">Gender</label>
                <select name="gender"
                  class="w-full px-3 py-2 text-base md:text-lg border rounded-lg focus:outline-none focus:border-teal-500">
                  <option value="Male" <?php echo $user['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                  <option value="Female" <?php echo $user['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                  <option value="Other" <?php echo $user['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
              </div>

              <!-- Address -->
              <div class="col-span-1 md:col-span-2">
                <label class="block text-gray-700 text-sm font-bold mb-2">Address</label>
                <textarea name="address"
                  rows="3"
                  class="w-full px-3 py-2 text-base md:text-lg border rounded-lg focus:outline-none focus:border-teal-500"><?php echo htmlspecialchars($user['address']); ?></textarea>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="flex flex-col md:flex-row justify-end mt-6 space-y-3 md:space-y-0 md:space-x-3">
              <a href="all-users.php"
                class="w-full md:w-auto px-6 py-2 bg-gray-500 text-white text-center rounded-lg hover:bg-gray-600 transition duration-200">
                Cancel
              </a>
              <button type="submit"
                class="w-full md:w-auto px-6 py-2 bg-teal-500 text-white rounded-lg hover:bg-teal-600 transition duration-200">
                Update User
              </button>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/js-links.php'; ?>

  <script>
    // Check for success message
    <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: 'User updated successfully',
        showConfirmButton: false,
        timer: 1500
      }).then(function() {
        window.location.href = 'all-users.php';
      });
    <?php endif; ?>

    // Preview image before upload
    document.querySelector('input[type="file"]').addEventListener('change', function(e) {
      if (e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
          document.querySelector('img').src = e.target.result;
        };
        reader.readAsDataURL(e.target.files[0]);
      }
    });
  </script>
</body>

</html>