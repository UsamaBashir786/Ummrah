<?php

$host = 'localhost';
$dbname = 'ummrah';
$username = 'root';
$password = '';

try {
  $pdo = new PDO(
    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
    $username,
    $password,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false
    ]
  );
} catch (PDOException $e) {
  die("Connection failed: " . $e->getMessage());
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Collect form data
  $hotel_name = trim($_POST['hotel_name']);
  $location = $_POST['location'];
  $room_count = (int)$_POST['room_count'];
  $price = (float)$_POST['price'];
  $rating = (int)$_POST['rating'];
  $description = trim($_POST['description']);
  $amenities = isset($_POST['amenities']) ? json_encode($_POST['amenities']) : json_encode([]);

  try {
    // Start transaction
    $pdo->beginTransaction();

    // Insert hotel data
    $stmt = $pdo->prepare("
            INSERT INTO hotels (hotel_name, location, room_count, price_per_night, rating, description, amenities)
            VALUES (:hotel_name, :location, :room_count, :price, :rating, :description, :amenities)
        ");

    $stmt->execute([
      'hotel_name' => $hotel_name,
      'location' => $location,
      'room_count' => $room_count,
      'price' => $price,
      'rating' => $rating,
      'description' => $description,
      'amenities' => $amenities
    ]);

    $hotel_id = $pdo->lastInsertId();

    // Handle image uploads
    if (!empty($_FILES['hotel_images']['name'][0])) {
      $upload_dir = 'uploads/hotels/';

      // Create directory if it doesn't exist
      if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
      }

      $stmt = $pdo->prepare("
                INSERT INTO hotel_images (hotel_id, image_path)
                VALUES (:hotel_id, :image_path)
            ");

      foreach ($_FILES['hotel_images']['tmp_name'] as $key => $tmp_name) {
        $file_name = $_FILES['hotel_images']['name'][$key];
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $new_file_name = uniqid() . '.' . $file_ext;
        $destination = $upload_dir . $new_file_name;

        if (move_uploaded_file($tmp_name, $destination)) {
          $stmt->execute([
            'hotel_id' => $hotel_id,
            'image_path' => $destination
          ]);
        }
      }
    }

    // Commit transaction
    $pdo->commit();

    // Redirect with success message
    header('Location: view-hotels.php?success=1');
    exit;
  } catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    header('Location: add-hotel.php?error=1');
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-material-ui/material-ui.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
          <i class="text-teal-600 fas fa-hotel mx-2"></i> Add New Hotel
        </h1>
      </div>

      <!-- Form Container -->
      <div class="overflow-auto container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto bg-white p-8 rounded-lg shadow-lg">
          <div class="mb-6">
            <h2 class="text-2xl font-bold text-teal-600">
              <i class="fas fa-plus-circle mr-2"></i>New Hotel Information
            </h2>
            <p class="text-gray-600 mt-2">Add a new hotel to your inventory</p>
          </div>

          <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
            <!-- Hotel Image Upload -->
            <div class="mb-6">
              <label class="block text-gray-700 font-semibold mb-2">Hotel Images</label>
              <div class="flex items-center justify-center w-full">
                <label class="flex flex-col w-full h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                  <div class="flex flex-col items-center justify-center pt-7">
                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                    <p class="text-sm text-gray-500">Click to upload or drag and drop</p>
                    <p class="text-xs text-gray-500">PNG, JPG up to 10MB</p>
                  </div>
                  <input type="file" class="hidden" multiple accept="image/*" name="hotel_images[]">
                </label>
              </div>
            </div>

            <!-- Hotel Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Hotel Name</label>
                <input type="text" name="hotel_name"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="Enter hotel name" required>
              </div>
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Location</label>
                <select name="location"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                  <option value="">Select Location</option>
                  <option value="makkah">Makkah</option>
                  <option value="madinah">Madinah</option>
                </select>
              </div>
              <!-- New Room Count Field -->
            </div>
            <div>
              <label class="block text-gray-700 font-semibold mb-2">Number of Rooms</label>
              <input type="number" name="room_count" min="1"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                placeholder="Enter total number of rooms">
            </div>


            <!-- Price and Rating -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Price per Night ($)</label>
                <input type="number" name="price"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="Enter price per night">
              </div>
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Hotel Rating</label>
                <div class="flex items-center space-x-2">
                  <select name="rating"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    <option value="5">5 Stars</option>
                    <option value="4">4 Stars</option>
                    <option value="3">3 Stars</option>
                    <option value="2">2 Stars</option>
                    <option value="1">1 Star</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Hotel Description -->
            <div>
              <label class="block text-gray-700 font-semibold mb-2">Hotel Description</label>
              <textarea name="description" rows="4"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                placeholder="Enter detailed hotel description..."></textarea>
            </div>

            <!-- Amenities -->
            <div>
              <label class="block text-gray-700 font-semibold mb-2">Hotel Amenities</label>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <label class="flex items-center space-x-2">
                  <input type="checkbox" name="amenities[]" value="wifi" class="text-teal-600">
                  <span>Free WiFi</span>
                </label>
                <label class="flex items-center space-x-2">
                  <input type="checkbox" name="amenities[]" value="parking" class="text-teal-600">
                  <span>Parking</span>
                </label>
                <label class="flex items-center space-x-2">
                  <input type="checkbox" name="amenities[]" value="restaurant" class="text-teal-600">
                  <span>Restaurant</span>
                </label>
                <label class="flex items-center space-x-2">
                  <input type="checkbox" name="amenities[]" value="gym" class="text-teal-600">
                  <span>Gym</span>
                </label>
              </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex gap-4">
              <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700 transition duration-200">
                <i class="fas fa-save mr-2"></i>Save Hotel
              </button>
              <button onclick="window.location.href='view-hotels.php'" type="button" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition duration-200">
                <i class="fas fa-times mr-2"></i>Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/js-links.php'; ?>
  
</body>

</html>