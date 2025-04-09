<?php
require_once 'connection/connection.php';

// Function to get all cities from database
function getCities($conn)
{
  $cities = array();
  $sql = "SELECT name FROM cities ORDER BY name ASC";
  $result = $conn->query($sql);

  if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $cities[] = $row['name'];
    }
  }

  return $cities;
}

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
  header("Location: manage-users.php");
  exit;
}

$user_id = intval($_GET['id']);
$error_message = '';
$success_message = '';

// Connect to database
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "ummrah";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Get all cities for dropdown
$cities = getCities($conn);

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Initialize variables
  $full_name = $_POST['full_name'];
  $email = $_POST['email'];
  $phone_number = $_POST['phone_number'];
  $date_of_birth = $_POST['date_of_birth'];
  $gender = $_POST['gender'];
  $address = $_POST['address'];

  // Handle city - either from select or add new
  $city = "";
  if (isset($_POST['city_selection']) && $_POST['city_selection'] == 'existing' && !empty($_POST['city'])) {
    $city = $_POST['city'];
  } elseif (isset($_POST['city_selection']) && $_POST['city_selection'] == 'new' && !empty($_POST['new_city'])) {
    // Insert new city into cities table
    $new_city = $_POST['new_city'];
    $city = $new_city;

    $city_stmt = $conn->prepare("INSERT INTO cities (name) VALUES (?)");
    $city_stmt->bind_param("s", $new_city);
    $city_stmt->execute();
    $city_stmt->close();
  }

  // Check if a new profile image was uploaded
  $profile_image = '';
  $update_image = false;

  if (!empty($_FILES['profile_image']['name'])) {
    $target_dir = "../user/uploads/";
    $file_extension = pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION);
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    // Upload the file
    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
      $profile_image = 'user/uploads/' . $new_filename;
      $update_image = true;
    } else {
      $error_message = "Failed to upload profile image.";
    }
  }

  // Update user information
  if (empty($error_message)) {
    try {
      if ($update_image) {
        $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone_number=?, date_of_birth=?, profile_image=?, gender=?, address=?, city=? WHERE id=?");
        $stmt->bind_param("ssssssssi", $full_name, $email, $phone_number, $date_of_birth, $profile_image, $gender, $address, $city, $user_id);
      } else {
        $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone_number=?, date_of_birth=?, gender=?, address=?, city=? WHERE id=?");
        $stmt->bind_param("sssssssi", $full_name, $email, $phone_number, $date_of_birth, $gender, $address, $city, $user_id);
      }

      if ($stmt->execute()) {
        $success_message = "User updated successfully.";
      } else {
        $error_message = "Error updating user: " . $stmt->error;
      }

      $stmt->close();
    } catch (Exception $e) {
      $error_message = "Error: " . $e->getMessage();
    }
  }
}

// Fetch user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  header("Location: manage-users.php");
  exit;
}

$user = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-100">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main flex-1 flex flex-col overflow-hidden">
      <!-- Navbar -->
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <div class="flex items-center">
          <button class="md:hidden text-gray-800 mr-4" id="menu-btn">
            <i class="fas fa-bars"></i>
          </button>
          <h1 class="text-xl font-semibold"><i class="text-teal-600 fas fa-user-edit mr-2"></i> Edit User</h1>
        </div>
        <div class="flex space-x-3">
          <a href="user-details.php?id=<?php echo $user_id; ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-eye mr-2"></i> View Details
          </a>
          <button onclick="window.history.back()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back
          </button>
        </div>
      </div>

      <div class="flex-1 overflow-auto p-4 md:p-6">
        <?php if (!empty($error_message)): ?>
          <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-md">
            <div class="flex items-center">
              <i class="fas fa-exclamation-circle mr-2"></i>
              <p><?php echo $error_message; ?></p>
            </div>
          </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
          <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-md">
            <div class="flex items-center">
              <i class="fas fa-check-circle mr-2"></i>
              <p><?php echo $success_message; ?></p>
            </div>
          </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-md p-6">
          <div class="flex items-center mb-6">
            <img class="h-16 w-16 rounded-full object-cover border-2 border-teal-500 mr-4"
              src="../<?php echo isset($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'user/uploads/default.png'; ?>"
              alt="User profile" />
            <div>
              <h2 class="text-xl font-semibold"><?php echo htmlspecialchars($user['full_name']); ?></h2>
              <p class="text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
          </div>

          <form method="POST" action="" enctype="multipart/form-data">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Personal Information -->
              <div>
                <h3 class="text-lg font-semibold mb-4 text-gray-700 border-b pb-2">Personal Information</h3>

                <div class="mb-4">
                  <label class="block mb-2 text-sm font-medium text-gray-700">Full Name</label>
                  <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>"
                    class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md focus:border-teal-500 focus:ring-teal-500 focus:ring-opacity-40 focus:outline-none focus:ring" required />
                </div>

                <div class="mb-4">
                  <label class="block mb-2 text-sm font-medium text-gray-700">Email Address</label>
                  <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                    class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md focus:border-teal-500 focus:ring-teal-500 focus:ring-opacity-40 focus:outline-none focus:ring" required />
                </div>

                <div class="mb-4">
                  <label class="block mb-2 text-sm font-medium text-gray-700">Phone Number</label>
                  <input type="tel" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>"
                    class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md focus:border-teal-500 focus:ring-teal-500 focus:ring-opacity-40 focus:outline-none focus:ring" required />
                </div>

                <div class="mb-4">
                  <label class="block mb-2 text-sm font-medium text-gray-700">Date of Birth</label>
                  <input type="date" name="date_of_birth" value="<?php echo htmlspecialchars($user['date_of_birth']); ?>"
                    class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md focus:border-teal-500 focus:ring-teal-500 focus:ring-opacity-40 focus:outline-none focus:ring" required />
                </div>

                <div class="mb-4">
                  <label class="block mb-2 text-sm font-medium text-gray-700">Gender</label>
                  <select name="gender" class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md focus:border-teal-500 focus:ring-teal-500 focus:ring-opacity-40 focus:outline-none focus:ring" required>
                    <option value="Male" <?php echo ($user['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo ($user['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo ($user['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                  </select>
                </div>
              </div>

              <!-- Location and Profile Information -->
              <div>
                <h3 class="text-lg font-semibold mb-4 text-gray-700 border-b pb-2">Location & Profile</h3>

                <!-- City Selection -->
                <div class="mb-4">
                  <label class="block mb-2 text-sm font-medium text-gray-700">City</label>
                  <div class="mb-3">
                    <label class="inline-flex items-center">
                      <input type="radio" name="city_selection" value="existing" class="text-teal-600" checked onchange="toggleCityFields()">
                      <span class="ml-2 text-sm text-gray-600">Select from existing cities</span>
                    </label>
                    <label class="inline-flex items-center ml-6">
                      <input type="radio" name="city_selection" value="new" class="text-teal-600" onchange="toggleCityFields()">
                      <span class="ml-2 text-sm text-gray-600">Add a new city</span>
                    </label>
                  </div>

                  <div id="existing-city-field">
                    <select name="city" id="city" class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md focus:border-teal-500 focus:ring-teal-500 focus:ring-opacity-40 focus:outline-none focus:ring">
                      <option value="">Select City</option>
                      <?php foreach ($cities as $cityOption): ?>
                        <option value="<?php echo htmlspecialchars($cityOption); ?>" <?php echo ($user['city'] == $cityOption) ? 'selected' : ''; ?>>
                          <?php echo htmlspecialchars($cityOption); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div id="new-city-field" style="display: none;">
                    <input type="text" name="new_city" id="new_city" placeholder="Enter new city name"
                      class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md focus:border-teal-500 focus:ring-teal-500 focus:ring-opacity-40 focus:outline-none focus:ring">
                  </div>
                </div>

                <div class="mb-4">
                  <label class="block mb-2 text-sm font-medium text-gray-700">Address</label>
                  <textarea name="address" rows="3" class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md focus:border-teal-500 focus:ring-teal-500 focus:ring-opacity-40 focus:outline-none focus:ring" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                </div>

                <div class="mb-4">
                  <label class="block mb-2 text-sm font-medium text-gray-700">Profile Image</label>
                  <div class="flex items-center space-x-4">
                    <img src="../<?php echo isset($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'user/uploads/default.png'; ?>"
                      alt="Current profile" class="h-12 w-12 rounded-full object-cover border border-gray-300" />
                    <div class="flex-1">
                      <input type="file" name="profile_image" accept="image/*"
                        class="block w-full px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md focus:outline-none" />
                      <p class="text-xs text-gray-500 mt-1">Upload a new image to change the profile picture.</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-6 border-t pt-6 flex justify-end space-x-4">
              <button type="button" onclick="window.history.back()" class="px-6 py-2.5 bg-gray-400 text-white font-medium rounded-lg hover:bg-gray-500 focus:outline-none focus:ring-4 focus:ring-gray-300">
                Cancel
              </button>
              <button type="submit" class="px-6 py-2.5 bg-teal-600 text-white font-medium rounded-lg hover:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-teal-300">
                Save Changes
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/js-links.php'; ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Mobile menu toggle
      const menuBtn = document.getElementById('menu-btn');
      const sidebar = document.querySelector('.sidebar');

      if (menuBtn && sidebar) {
        menuBtn.addEventListener('click', function() {
          sidebar.classList.toggle('hidden');
        });
      }
    });

    function toggleCityFields() {
      const selection = document.querySelector('input[name="city_selection"]:checked').value;
      const existingCityField = document.getElementById('existing-city-field');
      const newCityField = document.getElementById('new-city-field');

      if (selection === 'existing') {
        existingCityField.style.display = 'block';
        newCityField.style.display = 'none';
        document.getElementById('city').setAttribute('required', 'required');
        document.getElementById('new_city').removeAttribute('required');
      } else {
        existingCityField.style.display = 'none';
        newCityField.style.display = 'block';
        document.getElementById('city').removeAttribute('required');
        document.getElementById('new_city').setAttribute('required', 'required');
      }
    }

    // Show success/error messages with SweetAlert if they exist
    <?php if (!empty($success_message)): ?>
      Swal.fire({
        title: 'Success!',
        text: '<?php echo $success_message; ?>',
        icon: 'success',
        confirmButtonColor: '#0891b2'
      });
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
      Swal.fire({
        title: 'Error!',
        text: '<?php echo $error_message; ?>',
        icon: 'error',
        confirmButtonColor: '#0891b2'
      });
    <?php endif; ?>
  </script>
</body>

</html>