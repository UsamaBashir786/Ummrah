<?php
include "connection/connection.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $email = $_POST['email'];
  $password = $_POST['password'];

  $query = "SELECT * FROM admin WHERE email = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();

    if (password_verify($password, $row['password'])) {
      $_SESSION['admin_id'] = $row['id'];
      $_SESSION['admin_email'] = $row['email'];

      echo "<script>
              setTimeout(function() {
                Swal.fire({
                  icon: 'success',
                  title: 'Login Successful!',
                  text: 'Redirecting to dashboard...',
                  timer: 2000,
                  showConfirmButton: false
                }).then(() => {
                  window.location.href = 'index.php';
                });
              }, 100);
            </script>";
    } else {
      echo "<script>
              setTimeout(function() {
                Swal.fire({
                  icon: 'error',
                  title: 'Invalid Password!',
                  text: 'Please try again.',
                  confirmButtonColor: '#3085d6'
                });
              }, 100);
            </script>";
    }
  } else {
    echo "<script>
            setTimeout(function() {
              Swal.fire({
                icon: 'error',
                title: 'No Admin Found!',
                text: 'Please check your email.',
                confirmButtonColor: '#3085d6'
              });
            }, 100);
          </script>";
  }

  $stmt->close();
  $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gradient-to-br from-blue-900 to-gray-900">
  <div class="min-h-screen flex">
    <div class="hidden lg:flex lg:w-1/2 bg-blue-800 text-white p-12 flex-col justify-between">
      <div>
        <h1 class="text-4xl font-bold mb-6">Travel Management System</h1>
        <p class="text-xl mb-8">Manage your travel business efficiently with our comprehensive admin dashboard.</p>
        <div class="space-y-6">
          <div class="flex items-center space-x-4">
            <i class="fas fa-chart-line text-2xl"></i>
            <span>Real-time analytics and reporting</span>
          </div>
          <div class="flex items-center space-x-4">
            <i class="fas fa-users text-2xl"></i>
            <span>User management and booking control</span>
          </div>
          <div class="flex items-center space-x-4">
            <i class="fas fa-shield-alt text-2xl"></i>
            <span>Secure and encrypted platform</span>
          </div>
        </div>
      </div>
    </div>

    <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
      <div class="bg-white rounded-xl shadow-2xl p-8 w-full max-w-md">
        <div class="text-center mb-8">
          <img src="assets/images/icon.png" alt="Logo" class="h-16 mx-auto mb-4">
          <h2 class="text-3xl font-bold text-gray-800">Welcome Back</h2>
          <p class="text-gray-600">Please sign in to your admin account</p>
        </div>

        <form action="" method="POST" class="space-y-6">
          <div>
            <label class="block text-gray-700 font-semibold mb-2">Email Address</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-4">
                <i class="fas fa-envelope text-gray-400"></i>
              </span>
              <input type="email" name="email" required autofocus
                class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:outline-none transition-colors"
                placeholder="admin@example.com">
            </div>
          </div>
          <div>
            <label class="block text-gray-700 font-semibold mb-2">Password</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-4">
                <i class="fas fa-lock text-gray-400"></i>
              </span>
              <input type="password" name="password" required id="password"
                class="w-full pl-12 pr-10 py-3 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:outline-none transition-colors"
                placeholder="Enter your password">
              <span class="absolute inset-y-0 right-3 flex items-center cursor-pointer" onclick="togglePassword()">
                <i class="fas fa-eye text-gray-400" id="toggleIcon"></i>
              </span>
            </div>
          </div>
          <div class="flex items-center justify-between">
            <label class="flex items-center space-x-2">
              <input type="checkbox" class="w-4 h-4 text-blue-600">
              <span class="text-gray-600">Keep me signed in</span>
            </label>
            <a href="#" class="text-blue-600 hover:text-blue-800 font-medium">Forgot Password?</a>
          </div>
          <button type="submit" name="login"
            class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition duration-300 font-semibold">
            Sign In
          </button>
        </form>
        <div class="mt-8 text-center">
          <p class="text-gray-600">Having trouble? <a href="#" class="text-blue-600 hover:text-blue-800 font-medium">Contact Support</a></p>
        </div>
      </div>
    </div>
  </div>

  <script>
    function togglePassword() {
      let password = document.getElementById("password");
      let toggleIcon = document.getElementById("toggleIcon");
      if (password.type === "password") {
        password.type = "text";
        toggleIcon.classList.remove("fa-eye");
        toggleIcon.classList.add("fa-eye-slash");
      } else {
        password.type = "password";
        toggleIcon.classList.remove("fa-eye-slash");
        toggleIcon.classList.add("fa-eye");
      }
    }
  </script>
</body>

</html>