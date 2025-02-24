<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $db_host = "localhost";
  $db_user = "root";
  $db_pass = "";
  $db_name = "ummrah";

  $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  $email = $_POST['email'];
  $password = $_POST['password'];

  $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
      // Start session and store user data
      session_start();
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['full_name'] = $user['full_name'];
?>
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          Swal.fire({
            title: 'Welcome Back!',
            text: 'Login successful',
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
            text: 'Invalid password',
            icon: 'error',
            confirmButtonText: 'Try Again'
          });
        });
      </script>
    <?php
    }
  } else {
    ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
          title: 'Error!',
          text: 'Email not found',
          icon: 'error',
          confirmButtonText: 'Try Again'
        });
      });
    </script>
<?php
  }
  $stmt->close();
  $conn->close();
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
  <div class="my-6">&nbsp;</div>

  <section class="min-h-screen bg-cover" style="background-image: url('https://images.unsplash.com/photo-1563986768609-322da13575f3?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1470&q=80')">
    <div class="flex flex-col min-h-screen bg-black/60">
      <div class="container flex flex-col flex-1 px-6 py-12 mx-auto">
        <div class="flex-1 lg:flex lg:items-center lg:-mx-6">
          <div class="text-white lg:w-1/2 lg:mx-6">
            <h1 class="text-2xl font-semibold capitalize lg:text-3xl">Welcome Back!</h1>

            <p class="max-w-xl mt-6">
              Login to access your account and manage your profile. Enter your credentials to get started.
            </p>

            <div class="mt-6 md:mt-8">
              <h3 class="text-gray-300">Connect with us</h3>

              <div class="flex mt-4 -mx-1.5">
                <!-- Social media icons from your registration page -->
              </div>
            </div>
          </div>

          <div class="mt-8 lg:w-1/2 lg:mx-6">
            <div class="w-full px-8 py-10 mx-auto overflow-hidden bg-white shadow-2xl rounded-xl dark:bg-gray-900 lg:max-w-xl">
              <h1 class="text-xl font-medium text-gray-700 dark:text-gray-200">Login</h1>

              <p class="mt-2 text-gray-500 dark:text-gray-400">
                Sign in to your account
              </p>

              <form class="mt-6" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="flex-1 mt-4">
                  <label class="block mb-2 text-sm text-gray-600 dark:text-gray-200">Email Address</label>
                  <input type="email" name="email" placeholder="Enter your email"
                    class="block w-full px-5 py-3 mt-2 text-gray-700 bg-white border border-gray-200 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-300 focus:ring-opacity-40 dark:focus:border-teal-300 focus:outline-none focus:ring"
                    required />
                </div>

                <div class="flex-1 mt-4">
                  <label class="block mb-2 text-sm text-gray-600 dark:text-gray-200">Password</label>
                  <input type="password" name="password" placeholder="Enter your password"
                    class="block w-full px-5 py-3 mt-2 text-gray-700 bg-white border border-gray-200 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-300 focus:ring-opacity-40 dark:focus:border-teal-300 focus:outline-none focus:ring"
                    required />
                </div>

                <button type="submit"
                  class="w-full px-6 py-3 mt-6 text-sm font-medium tracking-wide text-white capitalize transition-colors duration-300 transform bg-teal-600 rounded-md hover:bg-teal-500 focus:outline-none focus:ring focus:ring-teal-400 focus:ring-opacity-50">
                  Login
                </button>
              </form>

              <p class="mt-6 text-sm text-center text-gray-400">
                Don't have an account yet?
                <a href="register.php" class="text-teal-500 focus:outline-none focus:underline hover:underline">
                  Sign up
                </a>.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</body>

</html>