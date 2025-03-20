<?php
include "connection/connection.php";
session_start();

// Secret key for password reset
$secret_key = "111222";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST['verify_key'])) {
    $email = $_POST['email'];
    $entered_key = $_POST['secret_key'];
    
    // First verify that the email exists in the database
    $query = "SELECT * FROM admin WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
      // Email exists, now check if secret key matches
      if ($entered_key === $secret_key) {
        // Both email and secret key are correct
        $_SESSION['reset_email'] = $email;
        $_SESSION['key_verified'] = true;
        
        // Show password change form
        $show_password_form = true;
      } else {
        // Email exists but key is wrong
        echo "<script>
                Swal.fire({
                  icon: 'error',
                  title: 'Invalid Secret Key',
                  text: 'The secret key you entered is incorrect.',
                  confirmButtonColor: '#3085d6'
                });
              </script>";
      }
    } else {
      // Email does not exist
      echo "<script>
              Swal.fire({
                icon: 'error',
                title: 'Email Not Found',
                text: 'The email address is not registered in our system.',
                confirmButtonColor: '#3085d6'
              });
            </script>";
    }
    
    $stmt->close();
  } elseif (isset($_POST['change_password'])) {
    // Make sure user is verified
    if (!isset($_SESSION['key_verified']) || $_SESSION['key_verified'] !== true) {
      echo "<script>
              Swal.fire({
                icon: 'error',
                title: 'Unauthorized Access',
                text: 'Please verify your identity first.',
                confirmButtonColor: '#3085d6'
              }).then(() => {
                window.location.href = 'forgot_password.php';
              });
            </script>";
      exit;
    }
    
    $email = $_SESSION['reset_email'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate passwords
    if ($new_password !== $confirm_password) {
      echo "<script>
              Swal.fire({
                icon: 'error',
                title: 'Passwords Do Not Match',
                text: 'Please make sure both passwords are identical.',
                confirmButtonColor: '#3085d6'
              });
            </script>";
      $show_password_form = true;
    } elseif (strlen($new_password) < 8) {
      echo "<script>
              Swal.fire({
                icon: 'error',
                title: 'Password Too Short',
                text: 'Password must be at least 8 characters long.',
                confirmButtonColor: '#3085d6'
              });
            </script>";
      $show_password_form = true;
    } else {
      // Hash the new password
      $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
      
      // Double-check that the email still exists in the database
      $checkQuery = "SELECT * FROM admin WHERE email = ?";
      $checkStmt = $conn->prepare($checkQuery);
      $checkStmt->bind_param("s", $email);
      $checkStmt->execute();
      $checkResult = $checkStmt->get_result();
      
      if ($checkResult->num_rows === 1) {
        // Update the password in the database
        $updateQuery = "UPDATE admin SET password = ? WHERE email = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ss", $hashed_password, $email);
        $updateResult = $updateStmt->execute();
        
        if ($updateResult) {
          // Clear the session variables
          unset($_SESSION['reset_email']);
          unset($_SESSION['key_verified']);
          
          echo "<script>
                  Swal.fire({
                    icon: 'success',
                    title: 'Password Updated Successfully!',
                    text: 'You can now login with your new password.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Go to Login'
                  }).then(() => {
                    window.location.href = 'login.php';
                  });
                </script>";
        } else {
          echo "<script>
                  Swal.fire({
                    icon: 'error',
                    title: 'System Error',
                    text: 'Unable to update password. Please try again later.',
                    confirmButtonColor: '#3085d6'
                  });
                </script>";
          $show_password_form = true;
        }
        
        $updateStmt->close();
      } else {
        echo "<script>
                Swal.fire({
                  icon: 'error',
                  title: 'Account Error',
                  text: 'The account no longer exists or has been modified.',
                  confirmButtonColor: '#3085d6'
                });
              </script>";
      }
      
      $checkStmt->close();
    }
  }
}

// Show password form if the key is verified
$show_password_form = isset($_SESSION['key_verified']) && $_SESSION['key_verified'] === true;
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password | Travel Management System</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .form-input:focus {
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
    }
    .animated-bg {
      background-size: 400% 400%;
      animation: gradient 15s ease infinite;
    }
    @keyframes gradient {
      0% {
        background-position: 0% 50%;
      }
      50% {
        background-position: 100% 50%;
      }
      100% {
        background-position: 0% 50%;
      }
    }
    .password-strength {
      height: 5px;
      border-radius: 5px;
      margin-top: 8px;
      transition: all 0.3s ease;
    }
  </style>
</head>

<body class="bg-gradient-to-br from-blue-900 via-indigo-800 to-blue-700 animated-bg">
  <div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-2xl p-8">
      <div class="text-center mb-8">
        <img src="assets/images/icon.png" alt="Logo" class="h-16 mx-auto mb-4">
        <?php if (!$show_password_form): ?>
          <h2 class="text-3xl font-bold text-gray-800">Forgot Password</h2>
          <p class="text-gray-500 mt-2">Enter your email and the secret key to reset your password</p>
        <?php else: ?>
          <h2 class="text-3xl font-bold text-gray-800">Reset Password</h2>
          <p class="text-gray-500 mt-2">Create a new secure password for your account</p>
        <?php endif; ?>
      </div>

      <?php if (!$show_password_form): ?>
        <!-- Secret Key Verification Form -->
        <form action="" method="POST" class="space-y-6">
          <div>
            <label for="email" class="block text-gray-700 font-medium mb-2">Email Address</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                <i class="fas fa-envelope text-gray-400"></i>
              </span>
              <input type="email" name="email" id="email" required autofocus
                class="form-input w-full pl-12 pr-4 py-3.5 border border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none transition-colors"
                placeholder="Enter your admin email">
            </div>
          </div>
          
          <div>
            <label for="secret_key" class="block text-gray-700 font-medium mb-2">Secret Key</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                <i class="fas fa-key text-gray-400"></i>
              </span>
              <input type="password" name="secret_key" id="secret_key" required
                class="form-input w-full pl-12 pr-4 py-3.5 border border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none transition-colors"
                placeholder="Enter the secret key">
            </div>
            <div class="text-xs text-gray-500 mt-2">
              Enter the 6-digit secret key provided by your administrator
            </div>
          </div>
          
          <button type="submit" name="verify_key"
            class="w-full bg-blue-600 text-white py-3.5 rounded-lg hover:bg-blue-700 transition duration-300 font-medium shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
            <i class="fas fa-unlock-alt mr-2"></i> Verify & Continue
          </button>
        </form>
      <?php else: ?>
        <!-- New Password Form -->
        <form action="" method="POST" class="space-y-6" id="passwordForm">
          <div>
            <label for="new_password" class="block text-gray-700 font-medium mb-2">New Password</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                <i class="fas fa-lock text-gray-400"></i>
              </span>
              <input type="password" name="new_password" id="new_password" required
                class="form-input w-full pl-12 pr-10 py-3.5 border border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none transition-colors"
                placeholder="Create new password" oninput="checkPasswordStrength()">
              <span class="absolute inset-y-0 right-3 flex items-center cursor-pointer" onclick="togglePasswordVisibility('new_password', 'toggleIcon1')">
                <i class="fas fa-eye text-gray-400" id="toggleIcon1"></i>
              </span>
            </div>
            <div class="password-strength w-full bg-gray-200" id="passwordStrength"></div>
            <div class="text-xs text-gray-500 mt-2" id="passwordFeedback">
              Password must be at least 8 characters long
            </div>
          </div>
          
          <div>
            <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm Password</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                <i class="fas fa-lock text-gray-400"></i>
              </span>
              <input type="password" name="confirm_password" id="confirm_password" required
                class="form-input w-full pl-12 pr-10 py-3.5 border border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none transition-colors"
                placeholder="Confirm new password" oninput="checkPasswordMatch()">
              <span class="absolute inset-y-0 right-3 flex items-center cursor-pointer" onclick="togglePasswordVisibility('confirm_password', 'toggleIcon2')">
                <i class="fas fa-eye text-gray-400" id="toggleIcon2"></i>
              </span>
            </div>
            <div class="text-xs mt-2" id="passwordMatch"></div>
          </div>
          
          <button type="submit" name="change_password" id="changePasswordBtn" 
            class="w-full bg-blue-600 text-white py-3.5 rounded-lg hover:bg-blue-700 transition duration-300 font-medium shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
            <i class="fas fa-check-circle mr-2"></i> Change Password
          </button>
        </form>
      <?php endif; ?>
      
      <div class="text-center mt-6">
        <a href="login.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium">
          <i class="fas fa-arrow-left mr-2"></i> Back to Login
        </a>
      </div>
      
      <div class="mt-10 pt-6 border-t border-gray-200 text-center">
        <div class="flex justify-center space-x-4">
          <a href="#" class="text-gray-500 hover:text-blue-600">
            <i class="fas fa-question-circle text-lg"></i>
            <span class="text-sm ml-1">Help</span>
          </a>
          <a href="#" class="text-gray-500 hover:text-blue-600">
            <i class="fas fa-headset text-lg"></i>
            <span class="text-sm ml-1">Support</span>
          </a>
        </div>
        <p class="text-xs text-gray-500 mt-4">
          If you're still having trouble, please contact your system administrator
        </p>
      </div>
    </div>
  </div>

  <script>
    // Toggle password visibility
    function togglePasswordVisibility(inputId, iconId) {
      const input = document.getElementById(inputId);
      const icon = document.getElementById(iconId);
      
      if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
      } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
      }
    }
    
    // Check password strength
    function checkPasswordStrength() {
      const password = document.getElementById('new_password').value;
      const strengthBar = document.getElementById('passwordStrength');
      const feedback = document.getElementById('passwordFeedback');
      
      // Reset strength bar
      strengthBar.style.width = '0%';
      strengthBar.style.backgroundColor = '#EDF2F7';
      
      if (password.length === 0) {
        feedback.textContent = 'Password must be at least 8 characters long';
        return;
      }
      
      // Calculate strength
      let strength = 0;
      
      // Length check
      if (password.length >= 8) strength += 25;
      
      // Character variety checks
      if (/[A-Z]/.test(password)) strength += 25;
      if (/[0-9]/.test(password)) strength += 25;
      if (/[^A-Za-z0-9]/.test(password)) strength += 25;
      
      // Update UI
      strengthBar.style.width = strength + '%';
      
      if (strength < 25) {
        strengthBar.style.backgroundColor = '#F56565';
        feedback.textContent = 'Very weak - Use at least 8 characters';
        feedback.className = 'text-xs text-red-600 mt-2';
      } else if (strength < 50) {
        strengthBar.style.backgroundColor = '#ED8936';
        feedback.textContent = 'Weak - Try adding numbers';
        feedback.className = 'text-xs text-orange-600 mt-2';
      } else if (strength < 75) {
        strengthBar.style.backgroundColor = '#ECC94B';
        feedback.textContent = 'Medium - Try adding special characters';
        feedback.className = 'text-xs text-yellow-600 mt-2';
      } else {
        strengthBar.style.backgroundColor = '#48BB78';
        feedback.textContent = 'Strong password!';
        feedback.className = 'text-xs text-green-600 mt-2';
      }
    }
    
    // Check if passwords match
    function checkPasswordMatch() {
      const password = document.getElementById('new_password').value;
      const confirmPassword = document.getElementById('confirm_password').value;
      const matchText = document.getElementById('passwordMatch');
      
      if (confirmPassword.length === 0) {
        matchText.textContent = '';
        return;
      }
      
      if (password === confirmPassword) {
        matchText.textContent = 'Passwords match';
        matchText.className = 'text-xs text-green-600 mt-2';
      } else {
        matchText.textContent = 'Passwords do not match';
        matchText.className = 'text-xs text-red-600 mt-2';
      }
    }
    
    // Add focus animation to input fields
    document.querySelectorAll('.form-input').forEach(input => {
      input.addEventListener('focus', () => {
        input.parentElement.classList.add('ring-2', 'ring-blue-200', 'ring-opacity-50');
      });
      input.addEventListener('blur', () => {
        input.parentElement.classList.remove('ring-2', 'ring-blue-200', 'ring-opacity-50');
      });
    });
  </script>
</body>

</html>