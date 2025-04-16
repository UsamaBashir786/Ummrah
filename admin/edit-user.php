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
  // Server-side validation
  $errors = [];

  // Full Name
  if (empty($_POST['full_name']) || !preg_match("/^[a-zA-Z\s]{1,20}$/", $_POST['full_name'])) {
    $errors[] = "Full name must be letters only, max 20 characters.";
  }

  // Email
  if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email address.";
  } else {
    // Check for duplicate email (excluding current user)
    $email = $_POST['email'];
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
      $errors[] = "Email already registered.";
    }
    $stmt->close();
  }

  // Phone Number
  if (empty($_POST['phone_number']) || !preg_match("/^\d{11}$/", $_POST['phone_number'])) {
    $errors[] = "Phone number must be exactly 11 digits.";
  }

  // Date of Birth
  if (empty($_POST['date_of_birth']) || !strtotime($_POST['date_of_birth'])) {
    $errors[] = "Invalid date of birth.";
  }

  // Profile Image (optional, but validate if provided)
  $profile_image = '';
  $update_image = false;
  if (!empty($_FILES['profile_image']['name'])) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB
    if (!in_array($_FILES['profile_image']['type'], $allowed_types) || $_FILES['profile_image']['size'] > $max_size) {
      $errors[] = "Profile image must be an image (JPG, PNG, GIF) under 2MB.";
    } else {
      $target_dir = "../user/uploads/";
      $file_extension = pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION);
      $new_filename = uniqid() . '.' . $file_extension;
      $target_file = $target_dir . $new_filename;

      if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
        $profile_image = 'user/uploads/' . $new_filename;
        $update_image = true;
      } else {
        $errors[] = "Failed to upload profile image.";
      }
    }
  }

  // Gender
  if (empty($_POST['gender']) || !in_array($_POST['gender'], ['Male', 'Female', 'Other'])) {
    $errors[] = "Invalid gender selection.";
  }

  // City
  $city = "";
  if ($_POST['city_selection'] === 'existing' && !empty($_POST['city'])) {
    $city = $_POST['city'];
    $stmt = $conn->prepare("SELECT name FROM cities WHERE name = ?");
    $stmt->bind_param("s", $city);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
      $errors[] = "Selected city is invalid.";
    }
    $stmt->close();
  } elseif ($_POST['city_selection'] === 'new' && !empty($_POST['new_city'])) {
    $new_city = $_POST['new_city'];
    if (!preg_match("/^[a-zA-Z\s]{1,15}$/", $new_city)) {
      $errors[] = "New city must be letters only, max 15 characters.";
    } else {
      $city = $new_city;
      // Insert new city
      $city_stmt = $conn->prepare("INSERT INTO cities (name) VALUES (?)");
      $city_stmt->bind_param("s", $new_city);
      $city_stmt->execute();
      $city_stmt->close();
    }
  } else {
    $errors[] = "City is required.";
  }

  // Address
  if (empty($_POST['address'])) {
    $errors[] = "Address is required.";
  }

  // If there are errors, display them
  if (!empty($errors)) {
    $error_message = implode("<br>", $errors);
  } else {
    // Update user information
    try {
      if ($update_image) {
        $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone_number=?, date_of_birth=?, profile_image=?, gender=?, address=?, city=? WHERE id=?");
        $stmt->bind_param("ssssssssi", $_POST['full_name'], $email, $_POST['phone_number'], $_POST['date_of_birth'], $profile_image, $_POST['gender'], $_POST['address'], $city, $user_id);
      } else {
        $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone_number=?, date_of_birth=?, gender=?, address=?, city=? WHERE id=?");
        $stmt->bind_param("sssssssi", $_POST['full_name'], $email, $_POST['phone_number'], $_POST['date_of_birth'], $_POST['gender'], $_POST['address'], $city, $user_id);
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

  // Refresh cities after potential new city addition
  $cities = getCities($conn);
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
                  <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>"
                    class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md focus:border-teal-500 focus:ring-teal-500 focus:ring-opacity-40 focus:outline-none focus:ring" required />
                  <p id="full-name-error" class="mt-1 text-xs text-red-500 hidden">Full name must be letters only, max 20 characters.</p>
                </div>

                <div class="mb-4">
                  <label class="block mb-2 text-sm font-medium text-gray-700">Email Address</label>
                  <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                    class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md focus:border-teal-500 focus:ring-teal-500 focus:ring-opacity-40 focus:outline-none focus:ring" required />
                  <p id="email-error" class="mt-1 text-xs text-red-500 hidden">Please enter a valid email address (e.g., user@example.com)</p>
                </div>

                <div class="mb-4">
                  <label class="block mb-2 text-sm font-medium text-gray-700">Phone Number</label>
                  <input type="tel" name="phone_number" id="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>"
                    class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md focus:border-teal-500 focus:ring-teal-500 focus:ring-opacity-40 focus:outline-none focus:ring" maxlength="11" required />
                  <p id="phone-error" class="mt-1 text-xs text-red-500 hidden">Please enter exactly 11 digits (numbers only)</p>
                </div>

                <div class="mb-4">
                  <label class="block mb-2 text-sm font-medium text-gray-700">Date of Birth</label>
                  <input type="date" name="date_of_birth" id="date_of_birth" value="<?php echo htmlspecialchars($user['date_of_birth']); ?>"
                    class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md focus:border-teal-500 focus:ring-teal-500 focus:ring-opacity-40 focus:outline-none focus:ring" required />
                </div>

                <div class="mb-4">
                  <label class="block mb-2 text-sm font-medium text-gray-700">Gender</label>
                  <select name="gender" id="gender" class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md focus:border-teal-500 focus:ring-teal-500 focus:ring-opacity-40 focus:outline-none focus:ring" required>
                    <option value="">Select Gender</option>
                    <option value="Male" <?php echo ($user['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo ($user['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo ($user['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                  </select>
                  <p id="gender-error" class="mt-1 text-xs text-red-500 hidden">Please select a gender.</p>
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
                    <input type="text" name="new_city" id="new_city" placeholder="Enter new city name" maxlength="15"
                      class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md focus:border-teal-500 focus:ring-teal-500 focus:ring-opacity-40 focus:outline-none focus:ring">
                  </div>
                  <p id="city-error" class="mt-1 text-xs text-red-500 hidden">Please select a city or enter a valid new city name.</p>
                </div>

                <div class="mb-4">
                  <label class="block mb-2 text-sm font-medium text-gray-700">Address</label>
                  <textarea name="address" id="address" rows="3" class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md focus:border-teal-500 focus:ring-teal-500 focus:ring-opacity-40 focus:outline-none focus:ring" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                  <p id="address-error" class="mt-1 text-xs text-red-500 hidden">Address is required.</p>
                </div>

                <div class="mb-4">
                  <label class="block mb-2 text-sm font-medium text-gray-700">Profile Image</label>
                  <div class="flex items-center space-x-4">
                    <img src="../<?php echo isset($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'user/uploads/default.png'; ?>"
                      alt="Current profile" class="h-12 w-12 rounded-full object-cover border border-gray-300" />
                    <div class="flex-1">
                      <input type="file" name="profile_image" id="profile_image" accept="image/*"
                        class="block w-full px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md focus:outline-none" />
                      <p id="file-error" class="mt-1 text-xs text-red-500 hidden">Please select an image (JPG, PNG, GIF) under 2MB.</p>
                      <p class="text-xs text-gray-500 mt-1">Upload a new image to change the profile picture. Max size: 2MB.</p>
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

      // Form validation
      const form = document.querySelector("form");
      const fullNameInput = document.getElementById("full_name");
      const emailInput = document.getElementById("email");
      const phoneInput = document.getElementById("phone_number");
      const dobInput = document.getElementById("date_of_birth");
      const fileInput = document.getElementById("profile_image");
      const genderInput = document.getElementById("gender");
      const citySelect = document.getElementById("city");
      const newCityInput = document.getElementById("new_city");
      const addressInput = document.getElementById("address");

      const fullNameError = document.getElementById("full-name-error");
      const emailError = document.getElementById("email-error");
      const phoneError = document.getElementById("phone-error");
      const fileError = document.getElementById("file-error");
      const genderError = document.getElementById("gender-error");
      const cityError = document.getElementById("city-error");
      const addressError = document.getElementById("address-error");

      // Toggle city fields
      function toggleCityFields() {
        const selection = document.querySelector('input[name="city_selection"]:checked').value;
        const existingCityField = document.getElementById("existing-city-field");
        const newCityField = document.getElementById("new-city-field");

        if (selection === "existing") {
          existingCityField.style.display = "block";
          newCityField.style.display = "none";
          citySelect.setAttribute("required", "required");
          newCityInput.removeAttribute("required");
        } else {
          existingCityField.style.display = "none";
          newCityField.style.display = "block";
          citySelect.removeAttribute("required");
          newCityInput.setAttribute("required", "required");
        }
      }

      // Validate Full Name
      fullNameInput.addEventListener("input", () => {
        fullNameInput.value = fullNameInput.value.replace(/[0-9]/g, "");
        if (fullNameInput.value.length > 20 || !/^[a-zA-Z\s]*$/.test(fullNameInput.value)) {
          fullNameError.classList.remove("hidden");
        } else {
          fullNameError.classList.add("hidden");
        }
      });

      fullNameInput.addEventListener("paste", (e) => {
        const pasteData = e.clipboardData.getData('text');
        if (/\d/.test(pasteData)) {
          e.preventDefault();
        }
      });

      // Validate Email
      emailInput.addEventListener("input", () => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailInput.value.trim()) && emailInput.value.trim() !== "") {
          emailError.classList.remove("hidden");
        } else {
          emailError.classList.add("hidden");
        }
      });

      // Validate Phone Number
      phoneInput.addEventListener("input", () => {
        phoneInput.value = phoneInput.value.replace(/\D/g, "");
        if (phoneInput.value.length !== 11 && phoneInput.value.length > 0) {
          phoneError.classList.remove("hidden");
        } else {
          phoneError.classList.add("hidden");
        }
      });

      // Validate Date of Birth
      dobInput.addEventListener("change", () => {
        if (!dobInput.value) {
          dobInput.classList.add("border-red-500");
        } else {
          dobInput.classList.remove("border-red-500");
        }
      });

      dobInput.addEventListener("keydown", (e) => {
        e.preventDefault();
      });

      dobInput.addEventListener("paste", (e) => {
        e.preventDefault();
      });

      dobInput.addEventListener("click", function() {
        this.showPicker();
      });

      // Validate File Input
      fileInput.addEventListener("change", () => {
        const file = fileInput.files[0];
        const maxSize = 2 * 1024 * 1024; // 2MB
        if (file) {
          if (!file.type.match("image.*")) {
            fileError.textContent = "Only image files are allowed.";
            fileError.classList.remove("hidden");
            fileInput.value = "";
          } else if (file.size > maxSize) {
            fileError.textContent = "File is too large (max 2MB).";
            fileError.classList.remove("hidden");
            fileInput.value = "";
          } else {
            fileError.classList.add("hidden");
          }
        }
      });

      // Validate Gender
      genderInput.addEventListener("change", () => {
        if (!genderInput.value) {
          genderError.classList.remove("hidden");
        } else {
          genderError.classList.add("hidden");
        }
      });

      // Validate City
      newCityInput.addEventListener("input", function() {
        if (this.value.length > 15) {
          this.value = this.value.slice(0, 15);
        }
      });

      newCityInput.addEventListener("keypress", function(e) {
        const char = String.fromCharCode(e.which);
        const isLetter = /^[a-zA-Z\s]$/.test(char);
        if (!isLetter) {
          e.preventDefault();
        }
      });

      function validateCity() {
        const selection = document.querySelector('input[name="city_selection"]:checked').value;
        if (selection === "existing" && !citySelect.value) {
          cityError.classList.remove("hidden");
          return false;
        } else if (selection === "new" && (!newCityInput.value || !/^[a-zA-Z\s]{1,15}$/.test(newCityInput.value))) {
          cityError.textContent = "New city must be letters only, max 15 characters.";
          cityError.classList.remove("hidden");
          return false;
        } else {
          cityError.classList.add("hidden");
          return true;
        }
      }

      // Validate Address
      addressInput.addEventListener("input", () => {
        if (!addressInput.value.trim()) {
          addressError.classList.remove("hidden");
        } else {
          addressError.classList.add("hidden");
        }
      });

      // Form Submission Validation
      form.addEventListener("submit", (e) => {
        let isValid = true;

        // Full Name
        if (!fullNameInput.value || fullNameInput.value.length > 20 || !/^[a-zA-Z\s]*$/.test(fullNameInput.value)) {
          fullNameError.classList.remove("hidden");
          isValid = false;
        }

        // Email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailInput.value.trim())) {
          emailError.classList.remove("hidden");
          isValid = false;
        }

        // Phone Number
        if (phoneInput.value.length !== 11) {
          phoneError.classList.remove("hidden");
          isValid = false;
        }

        // Date of Birth
        if (!dobInput.value) {
          dobInput.classList.add("border-red-500");
          isValid = false;
        }

        // File Input
        const file = fileInput.files[0];
        const maxSize = 2 * 1024 * 1024;
        if (file && (!file.type.match("image.*") || file.size > maxSize)) {
          fileError.textContent = "Please select a valid image under 2MB.";
          fileError.classList.remove("hidden");
          isValid = false;
        }

        // Gender
        if (!genderInput.value) {
          genderError.classList.remove("hidden");
          isValid = false;
        }

        // City
        if (!validateCity()) {
          isValid = false;
        }

        // Address
        if (!addressInput.value.trim()) {
          addressError.classList.remove("hidden");
          isValid = false;
        }

        if (!isValid) {
          e.preventDefault();
          Swal.fire({
            title: "Error!",
            text: "Please correct the errors in the form.",
            icon: "error",
            confirmButtonColor: "#0891b2",
          });
        }
      });

      // Initialize city field toggle
      toggleCityFields();

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
    });
  </script>
</body>

</html>