<?php
// Include your database connection file
include 'includes/db-config.php';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    // Retrieve form data
    $package_type = $_POST['package_type'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $airline = $_POST['airline'];
    $flight_class = $_POST['flight_class'];
    $departure_city = $_POST['departure_city'];
    $departure_time = $_POST['departure_time'];
    $departure_date = $_POST['departure_date'];
    $arrival_city = $_POST['arrival_city'];
    $inclusions = implode(', ', $_POST['inclusions']); // Convert array to string
    $price = $_POST['price'];

    // Handle file upload
    $package_image = '';
    if (isset($_FILES['package_image']) && $_FILES['package_image']['error'] === UPLOAD_ERR_OK) {
      $upload_dir = 'uploads/packages/'; // Directory to store uploaded images
      if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true); // Create the directory if it doesn't exist
      }
      $package_image = $upload_dir . basename($_FILES['package_image']['name']);
      move_uploaded_file($_FILES['package_image']['tmp_name'], $package_image);
    }

    // Prepare and execute the SQL query
    $sql = "INSERT INTO packages (package_type, title, description, airline, flight_class, departure_city, departure_time, departure_date, arrival_city, inclusions, price, package_image)
            VALUES (:package_type, :title, :description, :airline, :flight_class, :departure_city, :departure_time, :departure_date, :arrival_city, :inclusions, :price, :package_image)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':package_type' => $package_type,
      ':title' => $title,
      ':description' => $description,
      ':airline' => $airline,
      ':flight_class' => $flight_class,
      ':departure_city' => $departure_city,
      ':departure_time' => $departure_time,
      ':departure_date' => $departure_date,
      ':arrival_city' => $arrival_city,
      ':inclusions' => $inclusions,
      ':price' => $price,
      ':package_image' => $package_image
    ]);

    // Set a success message for SweetAlert
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
              Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Package created successfully.',
                confirmButtonText: 'OK'
              }).then(() => {
                window.location.href = 'view-package.php'; // Redirect after success
              });
            });
          </script>";
  } catch (PDOException $e) {
    // Set an error message for SweetAlert
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
              Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred: " . addslashes($e->getMessage()) . "',
                confirmButtonText: 'OK'
              });
            });
          </script>";
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <!-- Include SweetAlert CSS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-100">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main flex-1 flex flex-col">
      <!-- Navbar -->
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <!-- Menu Button (Left) -->
        <button class="md:hidden text-gray-800" id="menu-btn">
          <i class="fas fa-bars"></i>
        </button>

        <!-- Title -->
        <h1 class="text-xl font-semibold">
          <i class="text-teal-600 fas fa-box mx-2"></i> Add Packages
        </h1>

        <!-- Back Button (Right) -->
        <a href="view-package.php" class="flex items-center text-gray-700 hover:text-gray-900">
          <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
      </div>


      <div class="overflow-auto container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-lg">
          <h2 class="text-2xl font-bold text-teal-700 mb-6">Create New Umrah Package</h2>
          <form action="" method="POST" enctype="multipart/form-data">
            <!-- Package Type -->
            <div class="mb-6">
              <label class="block text-gray-700 text-sm font-bold mb-2">Package Type</label>
              <select name="package_type" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500">
                <option value="single">Single Umrah Package</option>
                <option value="group">Group Umrah Package</option>
                <option value="vip">VIP Umrah Package</option>
              </select>
            </div>

            <!-- Package Title -->
            <div class="mb-6">
              <label class="block text-gray-700 text-sm font-bold mb-2">Package Title</label>
              <input type="text" name="title" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500" required>
            </div>

            <!-- Package Description -->
            <div class="mb-6">
              <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
              <textarea name="description" rows="3" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500" required></textarea>
            </div>

            <!-- Flight Details -->
            <div class="grid grid-cols-2 gap-4 mb-6">
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Airline</label>
                <input type="text" name="airline" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500" required>
              </div>
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Flight Class</label>
                <select name="flight_class" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500">
                  <option value="economy">Economy</option>
                  <option value="business">Business</option>
                  <option value="first">First Class</option>
                </select>
              </div>
            </div>

            <!-- Location Details -->
            <div class="grid grid-cols-2 gap-4 mb-6">
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Departure City</label>
                <input type="text" name="departure_city" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500" required>
              </div>
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Arrival City</label>
                <input type="text" name="arrival_city" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500" required>
              </div>
            </div>

            <!-- Departure Date & Time -->
            <div class="grid grid-cols-2 gap-4 mb-6">
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Departure Date</label>
                <input type="date" name="departure_date" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500" required>
              </div>
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Departure Time</label>
                <input type="time" name="departure_time" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500" required>
              </div>
            </div>

            <!-- Package Inclusions -->
            <div class="mb-6">
              <label class="block text-gray-700 text-sm font-bold mb-2">Package Inclusions</label>
              <div class="space-y-2">
                <label class="flex items-center">
                  <input type="checkbox" name="inclusions[]" value="flight" class="mr-2">
                  Flight
                </label>
                <label class="flex items-center">
                  <input type="checkbox" name="inclusions[]" value="hotel" class="mr-2">
                  Hotel
                </label>
                <label class="flex items-center">
                  <input type="checkbox" name="inclusions[]" value="transport" class="mr-2">
                  Transport
                </label>
                <label class="flex items-center">
                  <input type="checkbox" name="inclusions[]" value="guide" class="mr-2">
                  Guide
                </label>
                <label class="flex items-center">
                  <input type="checkbox" name="inclusions[]" value="vip_services" class="mr-2">
                  VIP Services
                </label>
              </div>
            </div>

            <!-- Price -->
            <div class="mb-6">
              <label class="block text-gray-700 text-sm font-bold mb-2">Price (USD)</label>
              <input type="number" name="price" min="0" step="0.01" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500" required>
            </div>

            <!-- Package Image -->
            <div class="mb-6">
              <label class="block text-gray-700 text-sm font-bold mb-2">Package Image</label>
              <input type="file" name="package_image" accept="image/*" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500">
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
              <button type="submit" class="bg-teal-600 text-white px-6 py-3 rounded-lg hover:bg-teal-500 transition-all duration-300">
                Create Package
              </button>
            </div>
          </form>


        </div>
      </div>

      <?php include 'includes/js-links.php'; ?>
    </div>
  </div>
</body>

</html>