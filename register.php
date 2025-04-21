<?php
session_start();

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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $db_host = "localhost";
  $db_user = "root";
  $db_pass = "";
  $db_name = "ummrah";

  $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

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
    // Check for duplicate email
    $email = $_POST['email'];
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
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

  // Profile Image
  if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] === UPLOAD_ERR_NO_FILE) {
    $errors[] = "Profile image is required.";
  } else {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB
    if (!in_array($_FILES['profile_image']['type'], $allowed_types) || $_FILES['profile_image']['size'] > $max_size) {
      $errors[] = "Profile image must be an image (JPG, PNG, GIF) under 2MB.";
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
    }
  } else {
    $errors[] = "City is required.";
  }

  // Address
  if (empty($_POST['address'])) {
    $errors[] = "Address is required.";
  }

  // Password
  if (empty($_POST['password']) || !preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/", $_POST['password'])) {
    $errors[] = "Password must be at least 8 characters with one lowercase, one uppercase, and one number.";
  }

  // If there are errors, display them and stop
  if (!empty($errors)) {
?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
          title: 'Error!',
          html: '<?php echo implode("<br>", $errors); ?>',
          icon: 'error',
          confirmButtonText: 'OK'
        });
      });
    </script>
    <?php
    $cities = getCities($conn);
    $conn->close();
  } else {
    // Proceed with file upload
    $target_dir = "user/uploads/";
    $profile_image = $target_dir . uniqid() . '_' . basename($_FILES["profile_image"]["name"]);
    $upload_success = move_uploaded_file($_FILES["profile_image"]["tmp_name"], $profile_image);

    if ($upload_success) {
      $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

      // Insert new city if applicable
      if ($_POST['city_selection'] === 'new') {
        $city_stmt = $conn->prepare("INSERT INTO cities (name) VALUES (?)");
        $city_stmt->bind_param("s", $new_city);
        $city_stmt->execute();
        $city_stmt->close();
      }

      // Insert user
      $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone_number, date_of_birth, profile_image, gender, address, city, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param(
        "sssssssss",
        $_POST['full_name'],
        $email,
        $_POST['phone_number'],
        $_POST['date_of_birth'],
        $profile_image,
        $_POST['gender'],
        $_POST['address'],
        $city,
        $password
      );

      if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        $_SESSION['user_id'] = $user_id;
        $_SESSION['full_name'] = $_POST['full_name'];
        $_SESSION['email'] = $email;
        $_SESSION['logged_in'] = true;
    ?>
        <script>
          document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
              title: 'Success!',
              text: 'Registration completed successfully. You are now logged in!',
              icon: 'success',
              confirmButtonText: 'OK'
            }).then((result) => {
              if (result.isConfirmed) {
                window.location.href = 'index.php';
              }
            });
          });
        </script>
      <?php
      } else {
      ?>
        <script>
          document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
              title: 'Error!',
              text: 'Registration failed. Please try again.',
              icon: 'error',
              confirmButtonText: 'OK'
            });
          });
        </script>
      <?php
      }
      $stmt->close();
    } else {
      ?>
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          Swal.fire({
            title: 'Error!',
            text: 'Failed to upload profile image. Please try again.',
            icon: 'error',
            confirmButtonText: 'OK'
          });
        });
      </script>
<?php
    }
    $cities = getCities($conn);
    $conn->close();
  }
} else {
  $db_host = "localhost";
  $db_user = "root";
  $db_pass = "";
  $db_name = "ummrah";

  $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
  $cities = array();

  if (!$conn->connect_error) {
    $cities = getCities($conn);
    $conn->close();
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php' ?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-100 font-sans">
  <?php include 'includes/navbar.php' ?>
  <!-- Header -->
  <div class="my-6"> </div>
  <!-- Contact Form Section -->

  <section class="min-h-screen bg-cover" style="background-image: url('https://images.unsplash.com/photo-1563986768609-322da13575f3?ixlib=rb-1.2.1&ixid=LMho69zXxdxDkrsGLFu8cXS5ME2m3HR56p&auto=format&fit=crop&w=1470&q=80')">
    <div class="flex flex-col min-h-screen bg-black/60">
      <div class="container flex flex-col flex-1 px-6 py-12 mx-auto">
        <div class="flex-1 lg:flex lg:items-center lg:-mx-6">
          <div class="text-white lg:w-1/2 lg:mx-6">
            <h1 class="text-2xl font-semibold capitalize lg:text-3xl">Create an Account</h1>

            <p class="max-w-xl mt-6">
              Register now to access exclusive features and manage your account seamlessly. Fill in your details and get started today!
            </p>

            <button class="px-8 py-3 mt-6 text-sm font-medium tracking-wide text-white capitalize transition-colors duration-300 transform bg-blue-600 rounded-md hover:bg-blue-500 focus:outline-none focus:ring focus:ring-blue-400 focus:ring-opacity-50">
              Register Now
            </button>

            <div class="mt-6 md:mt-8">
              <h3 class="text-gray-300">Connect with us</h3>

              <div class="flex mt-4 -mx-1.5">
                <a class="mx-1.5 text-white transition-colors duration-300 transform hover:text-blue-500" href="#">
                  <svg class="w-10 h-10 fill-current" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18.6668 6.67334C18.0002 7.00001 17.3468 7.13268 16.6668 7.33334C15.9195 6.49001 14.8115 6.44334 13.7468 6.84201C12.6822 7.24068 11.9848 8.21534 12.0002 9.33334V10C9.83683 10.0553 7.91016 9.07001 6.66683 7.33334C6.66683 7.33334 3.87883 12.2887 9.3335 14.6667C8.0855 15.498 6.84083 16.0587 5.3335 16C7.53883 17.202 9.94216 17.6153 12.0228 17.0113C14.4095 16.318 16.3708 14.5293 17.1235 11.85C17.348 11.0351 17.4595 10.1932 17.4548 9.34801C17.4535 9.18201 18.4615 7.50001 18.6668 6.67268V6.67334Z" />
                  </svg>
                </a>

                <a class="mx-1.5 text-white transition-colors duration-300 transform hover:text-blue-500" href="#">
                  <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15.2 8.80005C16.4731 8.80005 17.694 9.30576 18.5941 10.2059C19.4943 11.1061 20 12.327 20 13.6V19.2H16.8V13.6C16.8 13.1757 16.6315 12.7687 16.3314 12.4687C16.0313 12.1686 15.6244 12 15.2 12C14.7757 12 14.3687 12.1686 14.0687 12.4687C13.7686 12.7687 13.6 13.1757 13.6 13.6V19.2H10.4V13.6C10.4 12.327 10.9057 11.1061 11.8059 10.2059C12.7061 9.30576 13.927 8.80005 15.2 8.80005Z" fill="currentColor" />
                  </svg>
                </a>

                <a class="mx-1.5 text-white transition-colors duration-300 transform hover:text-blue-500" href="#">
                  <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M7 10.2222V13.7778H9.66667V20H13.2222V13.7778H15.8889L16.7778 10.2222H13.2222V8.44444C13.2222 8.2087 13.3159 7.9826 13.4826 7.81591C13.6493 7.64921 13.8754 7.55556 14.1111 7.55556H16.7778V4H14.1111C12.9324 4 11.8019 4.46825 10.9684 5.30175C10.1349 6.13524 9.66667 7.2657 9.66667 8.44444V10.2222H7Z" fill="currentColor" />
                  </svg>
                </a>

                <a class="mx-1.5 text-white transition-colors duration-300 transform hover:text-blue-500" href="#">
                  <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11.9294 7.72275C9.65868 7.72275 7.82715 9.55428 7.82715 11.825C7.82715 14.0956 9.65868 15.9271 11.9294 15.9271C14.2 15.9271 16.0316 14.0956 16.0316 11.825C16.0316 9.55428 14.2 7.72275 11.9294 7.72275ZM11.9294 14.4919C10.462 14.4919 9.26239 13.2959 9.26239 11.825C9.26239 10.354 10.4584 9.15799 11.9294 9.15799C13.4003 9.15799 14.5963 10.354 14.5963 11.825C14.5963 13.2959 13.3967 14.4919 11.9294 14.4919Z" fill="currentColor" />
                  </svg>
                </a>
              </div>
            </div>
          </div>

          <div class="mt-8 lg:w-1/2 lg:mx-6">
            <div class="w-full px-8 py-10 mx-auto overflow-hidden bg-white shadow-2xl rounded-xl dark:bg-gray-900 lg:max-w-xl">
              <h1 class="text-xl font-medium text-gray-700 dark:text-gray-200">Register</h1>

              <p class="mt-2 text-gray-500 dark:text-gray-400">
                Create an account to get started
              </p>
              <form class="mt-6" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                <div class="flex-1">
                  <label class="block mb-2 text-sm text-gray-600 dark:text-gray-200">Full Name</label>
                  <input type="text" name="full_name" id="full_name" placeholder="Type Your Full Name"
                    class="block w-full px-5 py-3 mt-2 text-gray-700 bg-white border border-gray-200 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-300 focus:ring-opacity-40 dark:focus:border-teal-300 focus:outline-none focus:ring"
                    maxlength="20"
                    required />
                  <p id="full-name-error" class="mt-1 text-xs text-red-500 dark:text-red-400 hidden">Full name must be letters only, max 20 characters.</p>
                </div>

                <div class="flex-1 mt-4">
                  <label class="block mb-2 text-sm text-gray-600 dark:text-gray-200">Email Address</label>
                  <input type="email" name="email" id="email" placeholder="Type Your Email"
                    class="block w-full px-5 py-3 mt-2 text-gray-700 bg-white border border-gray-200 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-300 focus:ring-opacity-40 dark:focus:border-teal-300 focus:outline-none focus:ring"
                    required />
                  <p id="email-error" class="mt-1 text-xs text-red-500 dark:text-red-400 hidden">Please enter a valid email address (e.g., user@example.com)</p>
                </div>

                <div class="flex-1 mt-4">
                  <label class="block mb-2 text-sm text-gray-600 dark:text-gray-200">Phone Number</label>
                  <input
                    type="tel"
                    name="phone_number"
                    id="phone_number"
                    placeholder="Type Your Phone Number"
                    class="block w-full px-5 py-3 mt-2 text-gray-700 bg-white border border-gray-200 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-300 focus:ring-opacity-40 dark:focus:border-teal-300 focus:outline-none focus:ring"
                    maxlength="11"
                    required />
                  <p id="phone-error" class="mt-1 text-xs text-red-500 dark:text-red-400 hidden">Please enter exactly 11 digits (numbers only)</p>
                </div>

                <div class="flex-1 mt-4">
                  <label class="block mb-2 text-sm text-gray-600 dark:text-gray-200">Date of Birth</label>
                  <input type="date" name="date_of_birth" id="date_of_birth"
                    class="block w-full px-5 py-3 mt-2 text-gray-700 bg-white border border-gray-200 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-300 focus:ring-opacity-40 dark:focus:border-teal-300 focus:outline-none focus:ring"
                    required />
                </div>

                <div class="flex-1 mt-4">
                  <label class="block mb-2 text-sm text-gray-600 dark:text-gray-200">Profile Image</label>
                  <input
                    type="file"
                    name="profile_image"
                    id="profile_image"
                    accept="image/*"
                    class="block w-full px-5 py-3 mt-2 text-gray-700 bg-white border border-gray-200 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-300 focus:ring-opacity-40 dark:focus:border-teal-300 focus:outline-none focus:ring"
                    required />
                  <p id="file-error" class="mt-1 text-xs text-red-500 dark:text-red-400 hidden">
                    Please select an image (JPG, PNG, etc.) under 2MB.
                  </p>
                  <p id="file-size" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Max size: 2MB
                  </p>
                </div>

                <div class="flex-1 mt-4">
                  <label class="block mb-2 text-sm text-gray-600 dark:text-gray-200">Gender</label>
                  <select name="gender"
                    class="block w-full px-5 py-3 mt-2 text-gray-700 bg-white border border-gray-200 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-300 focus:ring-opacity-40 dark:focus:border-teal-300 focus:outline-none focus:ring"
                    required>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                  </select>
                  <p id="gender-error" class="mt-1 text-xs text-red-500 dark:text-red-400 hidden">Please select a gender.</p>
                </div>

                <div class="flex-1 mt-4">
                  <label class="block mb-2 text-sm text-gray-600 dark:text-gray-200">City</label>

                  <div class="mb-3">
                    <label class="inline-flex items-center">
                      <input type="radio" name="city_selection" value="existing" class="text-teal-600" checked>
                      <span class="ml-2 text-sm text-gray-600 dark:text-gray-200">Select from existing cities</span>
                    </label>
                    <label class="inline-flex items-center ml-6">
                      <input type="radio" name="city_selection" value="new" class="text-teal-600">
                      <span class="ml-2 text-sm text-gray-600 dark:text-gray-200">Add a new city</span>
                    </label>
                  </div>

                  <div id="existing-city-field">
                    <select name="city" id="city" class="block w-full px-5 py-3 mt-2 text-gray-700 bg-white border border-gray-200 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-300 focus:ring-opacity-40 dark:focus:border-teal-300 focus:outline-none focus:ring">
                      <option value="">Select City</option>
                      <?php foreach ($cities as $city): ?>
                        <option value="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div id="new-city-field" style="display: none;">
                    <input type="text" name="new_city" id="new_city" placeholder="Enter new city name"
                      maxlength="15"
                      class="block w-full px-5 py-3 mt-2 text-gray-700 bg-white border border-gray-200 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-300 focus:ring-opacity-40 dark:focus:border-teal-300 focus:outline-none focus:ring">
                  </div>
                  <p id="city-error" class="mt-1 text-xs text-red-500 dark:text-red-400 hidden">Please select a city or enter a valid new city name.</p>
                </div>

                <div class="w-full mt-4">
                  <label class="block mb-2 text-sm text-gray-600 dark:text-gray-200">Address</label>
                  <textarea name="address"
                    class="block w-full h-24 px-5 py-3 mt-2 text-gray-700 placeholder-gray-400 bg-white border border-gray-200 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-300 focus:ring-opacity-40 dark:focus:border-teal-300 focus:outline-none focus:ring"
                    placeholder="Enter Your Address" required></textarea>
                  <p id="address-error" class="mt-1 text-xs text-red-500 dark:text-red-400 hidden">Address is required.</p>
                </div>

                <div class="flex-1 mt-4">
                  <label class="block mb-2 text-sm text-gray-600 dark:text-gray-200">Password</label>
                  <div class="relative">
                    <input type="password" name="password" id="password" placeholder="Enter your password"
                      class="block w-full px-5 py-3 mt-2 text-gray-700 bg-white border border-gray-200 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-300 focus:ring-opacity-40 dark:focus:border-teal-300 focus:outline-none focus:ring"
                      required />
                    <button type="button" class="absolute right-2 top-5" onclick="togglePassword()">
                      <!-- Eye icon (visible by default) -->
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500 eye-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      <!-- Eye-slash icon (hidden by default) -->
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500 eye-slash-icon hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                      </svg>
                    </button>
                  </div>
                  <small id="password-error" class="text-red-500 text-sm hidden mt-1">Password must be at least 8 characters long and contain at least one lowercase letter, one uppercase letter, and one number.</small>
                </div>

                <button type="submit"
                  class="w-full px-6 py-3 mt-6 text-sm font-medium tracking-wide text-white capitalize transition-colors duration-300 transform bg-teal-600 rounded-md hover:bg-teal-500 focus:outline-none focus:ring focus:ring-teal-400 focus:ring-opacity-50">
                  Register
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const form = document.querySelector("form");
      const fullNameInput = document.getElementById("full_name");
      const emailInput = document.getElementById("email");
      const phoneInput = document.getElementById("phone_number");
      const dobInput = document.getElementById("date_of_birth");
      const fileInput = document.getElementById("profile_image");
      const genderInput = document.querySelector("select[name='gender']");
      const citySelect = document.getElementById("city");
      const newCityInput = document.getElementById("new_city");
      const addressInput = document.querySelector("textarea[name='address']");
      const passwordInput = document.getElementById("password");

      const fullNameError = document.getElementById("full-name-error");
      const emailError = document.getElementById("email-error");
      const phoneError = document.getElementById("phone-error");
      const fileError = document.getElementById("file-error");
      const genderError = document.getElementById("gender-error");
      const cityError = document.getElementById("city-error");
      const addressError = document.getElementById("address-error");
      const passwordError = document.getElementById("password-error");

      // Toggle city fields
      function toggleCityFields() {
        const selection = document.querySelector('input[name="city_selection"]:checked');
        const existingCityField = document.getElementById("existing-city-field");
        const newCityField = document.getElementById("new-city-field");
        const citySelect = document.getElementById("city");
        const newCityInput = document.getElementById("new_city");

        if (!existingCityField || !newCityField || !citySelect || !newCityInput) {
          console.error("One or more city fields not found!");
          return;
        }

        if (selection && selection.value === "existing") {
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

      // Add event listeners for city selection radio buttons
      const cityRadios = document.querySelectorAll('input[name="city_selection"]');
      cityRadios.forEach(radio => {
        radio.addEventListener("change", toggleCityFields);
      });

      // Initialize city fields
      toggleCityFields();

      // Toggle password visibility
      function togglePassword() {
        const password = document.getElementById("password");
        const eyeIcon = document.querySelector(".eye-icon");
        const eyeSlashIcon = document.querySelector(".eye-slash-icon");

        if (password.type === "password") {
          password.type = "text";
          eyeIcon.classList.add("hidden");
          eyeSlashIcon.classList.remove("hidden");
        } else {
          password.type = "password";
          eyeIcon.classList.remove("hidden");
          eyeSlashIcon.classList.add("hidden");
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

      // Validate Password
      passwordInput.addEventListener("input", () => {
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
        if (!passwordRegex.test(passwordInput.value)) {
          passwordError.classList.remove("hidden");
        } else {
          passwordError.classList.add("hidden");
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
        if (!file || !file.type.match("image.*") || file.size > maxSize) {
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

        // Password
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
        if (!passwordRegex.test(passwordInput.value)) {
          passwordError.classList.remove("hidden");
          isValid = false;
        }

        if (!isValid) {
          e.preventDefault();
          Swal.fire({
            title: "Error!",
            text: "Please correct the errors in the form.",
            icon: "error",
            confirmButtonText: "OK",
          });
        }
      });
    });
  </script>
</body>

</html>