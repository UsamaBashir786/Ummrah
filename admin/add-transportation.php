<?php
session_start();
include 'connection/connection.php'; // Include database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Get form data
  $category = $_POST['category'];
  $transport_name = $_POST['transport_name'];
  $transport_id = $_POST['transport_id'];
  $location = $_POST['location'];
  $latitude = $_POST['latitude'];
  $longitude = $_POST['longitude'];
  $details = $_POST['details'];
  $seats = $_POST['seats'];
  $time_from = $_POST['time_from'];
  $time_to = $_POST['time_to'];
  $status = $_POST['status'];
  $price = $_POST['price']; // New price field
  // Get the booking limit from POST data
  $booking_limit = $_POST['booking_limit'];

  // File upload handling
  $target_dir = "uploads/vehicles/";
  if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
  }
  $file_name = basename($_FILES["transport_image"]["name"]);
  $target_file = $target_dir . time() . "_" . $file_name;
  $uploadOk = 1;
  $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

  // Check if file is an actual image
  $check = getimagesize($_FILES["transport_image"]["tmp_name"]);
  if ($check === false) {
    $uploadOk = 0;
    $error_message = "File is not an image.";
  }

  // Allow only specific formats
  if (!in_array($imageFileType, ["jpg", "png", "jpeg", "gif"])) {
    $uploadOk = 0;
    $error_message = "Only JPG, JPEG, PNG & GIF files are allowed.";
  }

  if ($uploadOk == 1) {
    if (move_uploaded_file($_FILES["transport_image"]["tmp_name"], $target_file)) {
      // Modify the SQL INSERT query to include booking_limit
      $sql = "INSERT INTO transportation 
        (category, transport_name, transport_id, location, latitude, longitude, 
         details, seats, time_from, time_to, status, price, transport_image, booking_limit) 
        VALUES 
        ('$category', '$transport_name', '$transport_id', '$location', '$latitude', 
         '$longitude', '$details', '$seats', '$time_from', '$time_to', '$status', 
         '$price', '$target_file', '$booking_limit')";

      if (mysqli_query($conn, $sql)) {
        echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Transportation added successfully!',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = 'view-transportation.php';
                        });
                    });
                </script>";
      } else {
        $error_message = "Database error: " . mysqli_error($conn);
      }
    } else {
      $error_message = "There was an error uploading the file.";
    }
  }

  if (isset($error_message)) {
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Error!',
                    text: '$error_message',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        </script>";
  }
}
?>
<!-- Include SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>



<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>

</head>

<body class="bg-gray-50">
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
          <i class="text-teal-600 fas fa-car mx-2"></i> Add New Vehicle
        </h1>
      </div>

      <!-- Form Container -->
      <div class="overflow-auto container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-lg">
          <h2 class="text-2xl font-bold text-gray-800 mb-6">Vehicle Information</h2>
          <form action="" method="POST" enctype="multipart/form-data">
            <!-- Category Selection -->
            <div class="mb-6">
              <label class="block text-gray-700 text-sm font-bold mb-2" for="category">
                Vehicle Category
              </label>
              <select id="category" name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Select Category</option>
                <option value="luxury">Luxury</option>
                <option value="vip">vip</option>
                <option value="shared">shared</option>
              </select>
            </div>

            <!-- Vehicle Name -->
            <div class="mb-6">
              <label class="block text-gray-700 text-sm font-bold mb-2" for="transport_name">
                Vehicle Name
              </label>
              <input type="text" id="transport_name" name="transport_name"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="e.g., Mercedes-Benz S-Class">
            </div>

            <!-- Vehicle ID -->
            <div class="mb-6">
              <label class="block text-gray-700 text-sm font-bold mb-2" for="transport_id">
                Vehicle ID
              </label>
              <input type="text" id="transport_id" name="transport_id"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="e.g., LX-2023">
            </div>
            <!-- Price Input -->
            <div class="mb-6">
              <label class="block text-gray-700 text-sm font-bold mb-2" for="price">
                Price ($)
              </label>
              <input type="number" id="price" name="price" min="0" step="0.01"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Enter vehicle price">
            </div>
            <!-- Location (City) Input with Map -->
            <div class="mb-6">
              <label class="block text-gray-700 text-sm font-bold mb-2" for="location">
                Location (Search or Click on Map)
              </label>
              <input type="text" id="location" name="location"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Search or select on map">
            </div>

            <!-- Map Container -->
            <div id="map" class="w-full h-64 rounded-md border"></div>

            <!-- Hidden Inputs for Latitude & Longitude -->
            <input type="hidden" id="latitude" name="latitude">
            <input type="hidden" id="longitude" name="longitude">

            <!-- Vehicle Details -->
            <div class="mb-6">
              <label class="block text-gray-700 text-sm font-bold mb-2" for="details">
                Vehicle Details
              </label>
              <textarea id="details" name="details" rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Enter vehicle details and features..."></textarea>
            </div>

            <!-- Available Seats -->
            <div class="mb-6">
              <label class="block text-gray-700 text-sm font-bold mb-2" for="seats">
                Available Seats
              </label>
              <input type="number" id="seats" name="seats" min="1"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Enter number of seats">
            </div>
            <!-- Booking Limit -->
            <div class="mb-6">
              <label class="block text-gray-700 text-sm font-bold mb-2" for="booking_limit">
                Booking Limit
              </label>
              <input type="number"
                id="booking_limit"
                name="booking_limit"
                min="1"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Enter maximum number of bookings allowed">
            </div>

            <!-- Available Time -->
            <div class="grid grid-cols-2 gap-4 mb-6">
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="time_from">
                  Available From
                </label>
                <input type="time" id="time_from" name="time_from"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              </div>
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="time_to">
                  Available To
                </label>
                <input type="time" id="time_to" name="time_to"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              </div>
            </div>

            <!-- Vehicle Image -->
            <div class="mb-6">
              <label class="block text-gray-700 text-sm font-bold mb-2" for="transport_image">
                Vehicle Image
              </label>
              <input type="file" id="transport_image" name="transport_image"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Status -->
            <div class="mb-6">
              <label class="block text-gray-700 text-sm font-bold mb-2">
                Status
              </label>
              <div class="flex gap-4">
                <label class="inline-flex items-center">
                  <input type="radio" name="status" value="available" class="form-radio text-blue-600">
                  <span class="ml-2">Available</span>
                </label>
                <label class="inline-flex items-center">
                  <input type="radio" name="status" value="booked" class="form-radio text-blue-600">
                  <span class="ml-2">Booked</span>
                </label>
              </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex gap-4">
              <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i>Save Vehicle
              </button>
              <button type="button" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">
                <i class="fas fa-times mr-2"></i>Cancel
              </button>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/js-links.php'; ?>
  <script src="assets/js/transportation.js"></script>
</body>

</html>