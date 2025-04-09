<?php
// Start the session at the very beginning
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

  $target_dir = "user/uploads/";
  $profile_image = $target_dir . basename($_FILES["profile_image"]["name"]);
  $upload_success = move_uploaded_file($_FILES["profile_image"]["tmp_name"], $profile_image);

  if ($upload_success) {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

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
      // Get the user ID of the newly registered user
      $user_id = $conn->insert_id;

      // Set session variables to log the user in
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
              // Redirect to user dashboard or homepage after login
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

  // Get cities for the form in case of error
  $cities = getCities($conn);
  $conn->close();
} else {
  // Just connect to get cities for the initial form display
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
  <div class="my-6">&nbsp;</div>
  <!-- Contact Form Section -->


  <section class="min-h-screen bg-cover " style="background-image: url('https://images.unsplash.com/photo-1563986768609-322da13575f3?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1470&q=80')">
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
                  <input type="text" name="full_name" placeholder="Type Your Full Name"
                    class="block w-full px-5 py-3 mt-2 text-gray-700 bg-white border border-gray-200 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-300 focus:ring-opacity-40 dark:focus:border-teal-300 focus:outline-none focus:ring"
                    required />
                </div>

                <div class="flex-1 mt-4">
                  <label class="block mb-2 text-sm text-gray-600 dark:text-gray-200">Email Address</label>
                  <input type="email" name="email" placeholder="Type Your Email"
                    class="block w-full px-5 py-3 mt-2 text-gray-700 bg-white border border-gray-200 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-300 focus:ring-opacity-40 dark:focus:border-teal-300 focus:outline-none focus:ring"
                    required />
                </div>

                <div class="flex-1 mt-4">
                  <label class="block mb-2 text-sm text-gray-600 dark:text-gray-200">Phone Number</label>
                  <input type="tel" name="phone_number" placeholder="Type Your Phone Number"
                    class="block w-full px-5 py-3 mt-2 text-gray-700 bg-white border border-gray-200 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-300 focus:ring-opacity-40 dark:focus:border-teal-300 focus:outline-none focus:ring"
                    required />
                </div>

                <div class="flex-1 mt-4">
                  <label class="block mb-2 text-sm text-gray-600 dark:text-gray-200">Date of Birth</label>
                  <input type="date" name="date_of_birth"
                    class="block w-full px-5 py-3 mt-2 text-gray-700 bg-white border border-gray-200 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-300 focus:ring-opacity-40 dark:focus:border-teal-300 focus:outline-none focus:ring"
                    required />
                </div>

                <div class="flex-1 mt-4">
                  <label class="block mb-2 text-sm text-gray-600 dark:text-gray-200">Profile Image</label>
                  <input type="file" name="profile_image" accept="image/*"
                    class="block w-full px-5 py-3 mt-2 text-gray-700 bg-white border border-gray-200 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-300 focus:ring-opacity-40 dark:focus:border-teal-300 focus:outline-none focus:ring"
                    required />
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
                </div>

                <!-- City Selection (Updated) -->
                <div class="flex-1 mt-4">
                  <label class="block mb-2 text-sm text-gray-600 dark:text-gray-200">City</label>
                  <div class="mb-3">
                    <label class="inline-flex items-center">
                      <input type="radio" name="city_selection" value="existing" class="text-teal-600" checked onchange="toggleCityFields()">
                      <span class="ml-2 text-sm text-gray-600 dark:text-gray-200">Select from existing cities</span>
                    </label>
                    <label class="inline-flex items-center ml-6">
                      <input type="radio" name="city_selection" value="new" class="text-teal-600" onchange="toggleCityFields()">
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
                    <input type="text" name="new_city" id="new_city" placeholder="Enter new city name" class="block w-full px-5 py-3 mt-2 text-gray-700 bg-white border border-gray-200 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-300 focus:ring-opacity-40 dark:focus:border-teal-300 focus:outline-none focus:ring">
                  </div>
                </div>

                <div class="w-full mt-4">
                  <label class="block mb-2 text-sm text-gray-600 dark:text-gray-200">Address</label>
                  <textarea name="address"
                    class="block w-full h-24 px-5 py-3 mt-2 text-gray-700 placeholder-gray-400 bg-white border border-gray-200 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-300 focus:ring-opacity-40 dark:focus:border-teal-300 focus:outline-none focus:ring"
                    placeholder="Enter Your Address" required></textarea>
                </div>

                <div class="flex-1 mt-4">
                  <label class="block mb-2 text-sm text-gray-600 dark:text-gray-200">Password</label>
                  <div class="relative">
                    <input type="password" name="password" id="password" placeholder="Enter your password"
                      class="block w-full px-5 py-3 mt-2 text-gray-700 bg-white border border-gray-200 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-300 focus:ring-opacity-40 dark:focus:border-teal-300 focus:outline-none focus:ring"
                      required />
                    <button type="button" class="absolute right-2 top-5" onclick="togglePassword()">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" id="eyeIcon">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                  </div>
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
    function togglePassword() {
      const passwordInput = document.getElementById('password');
      const eyeIcon = document.getElementById('eyeIcon');

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
        `;
      } else {
        passwordInput.type = 'password';
        eyeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        `;
      }
    }

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
  </script>

</body>

</html>