<?php
session_start();
if (!isset($_SESSION['admin_email'])) {
  header("Location: admin/login.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel</title>
  <link rel="stylesheet" href="../assets/css/output.css">
  <link rel="stylesheet" href="assets/css/output.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
</head>

<body class="bg-gray-100">

  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>


    <!-- Main Content -->
    <div class="flex-1 flex flex-col">
      <!-- Navbar -->
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <button class="md:hidden text-gray-800" id="menu-btn">
          <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-xl font-semibold">Dashboard</h1>
      </div>

      <!-- Content Area -->
      <div class="p-6" style="overflow-y: scroll;">
        <!-- Statistic Boxes -->
        <div class="overflow-y-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Total Users -->
          <div class="bg-gray-100 text-gray-800 p-6 rounded-lg shadow-md">
            <i class="fas fa-users text-3xl text-blue-500"></i>
            <h3 class="text-lg font-semibold mt-2">Total Users</h3>
            <p class="text-2xl font-bold">1,250</p>
          </div>

          <!-- Total Packages -->
          <div class="bg-gray-100 text-gray-800 p-6 rounded-lg shadow-md">
            <i class="fas fa-box text-3xl text-green-500"></i>
            <h3 class="text-lg font-semibold mt-2">Total Packages</h3>
            <p class="text-2xl font-bold">320</p>
          </div>

          <!-- Total Transportation -->
          <div class="bg-gray-100 text-gray-800 p-6 rounded-lg shadow-md">
            <i class="fas fa-bus text-3xl text-yellow-500"></i>
            <h3 class="text-lg font-semibold mt-2">Total Transportations</h3>
            <p class="text-2xl font-bold">85</p>
          </div>

          <!-- Total Flights -->
          <div class="bg-gray-100 text-gray-800 p-6 rounded-lg shadow-md">
            <i class="fas fa-plane text-3xl text-red-500"></i>
            <h3 class="text-lg font-semibold mt-2">Total Flights</h3>
            <p class="text-2xl font-bold">45</p>
          </div>

          <!-- Total Hotels -->
          <div class="bg-gray-100 text-gray-800 p-6 rounded-lg shadow-md">
            <i class="fas fa-hotel text-3xl text-purple-500"></i>
            <h3 class="text-lg font-semibold mt-2">Total Hotels</h3>
            <p class="text-2xl font-bold">60</p>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="assets/js/main.js"></script>

</body>

</html>