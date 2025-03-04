<?php
session_start();
include 'connection/connection.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit();
}

// Fetch filter values from GET parameters
$location = isset($_GET['location']) ? $_GET['location'] : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 10000;
$rating = isset($_GET['rating']) ? (float)$_GET['rating'] : 0;

// SQL query to fetch hotels with one image per hotel
$query = "
    SELECT h.*, 
           (SELECT hi.image_path FROM hotel_images hi WHERE hi.hotel_id = h.id LIMIT 1) AS image_path
    FROM hotels h
    WHERE h.price_per_night BETWEEN ? AND ?
";

// Parameters array
$params = [$min_price, $max_price];

if (!empty($location)) {
  $query .= " AND h.location LIKE ?";
  $params[] = "%$location%";
}

if ($rating > 0) {
  $query .= " AND h.rating >= ?";
  $params[] = $rating;
}

$stmt = $conn->prepare($query);
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result();
$hotels = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
</head>

<body class="bg-gray-50">
  <?php include 'includes/navbar.php'; ?>

  <div class="my-15">&nbsp;</div>

  <section class="py-16">
    <div class="container mx-auto px-4 lg:px-12" data-aos="fade-up">
      <h3 class="text-2xl text-teal-600 my-5 lg:ml-8">- Packages</h3>
      <h2 class="text-3xl font-bold text-teal-600 lg:ml-8">Hotel Details</h2>
      <p class="mt-4 text-gray-700 lg:ml-8">Find the best hotels for your Umrah pilgrimage.</p>
    </div>

    <!-- Filter Form -->
    <form method="GET" class="container mx-auto px-4 mt-4 bg-white p-6 rounded-lg shadow-md">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

        <!-- Location Input -->
        <div>
          <label class="block text-gray-700 font-semibold mb-1">Location:</label>
          <input type="text" name="location" placeholder="Enter location"
            class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-teal-500"
            value="<?php echo htmlspecialchars($location); ?>">
        </div>

        <!-- Min Price -->
        <div>
          <label class="block text-gray-700 font-semibold mb-1">Min Price ($):</label>
          <input type="number" name="min_price" placeholder="Min Price"
            class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-teal-500"
            value="<?php echo $min_price; ?>">
        </div>

        <!-- Max Price -->
        <div>
          <label class="block text-gray-700 font-semibold mb-1">Max Price ($):</label>
          <input type="number" name="max_price" placeholder="Max Price"
            class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-teal-500"
            value="<?php echo $max_price; ?>">
        </div>

        <!-- Rating Dropdown -->
        <div>
          <label class="block text-gray-700 font-semibold mb-1">Rating:</label>
          <select name="rating"
            class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-teal-500">
            <option value="0">Any Rating</option>
            <option value="3" <?php if ($rating == 3) echo 'selected'; ?>>3+ Stars</option>
            <option value="4" <?php if ($rating == 4) echo 'selected'; ?>>4+ Stars</option>
            <option value="5" <?php if ($rating == 5) echo 'selected'; ?>>5 Stars</option>
          </select>
        </div>

      </div>

      <!-- Submit Button -->
      <div class="text-center mt-4">
        <button type="submit"
          class="bg-teal-600 text-white py-2 px-8 rounded-lg hover:bg-teal-700 transition font-semibold text-lg">
          Apply Filters
        </button>
      </div>
    </form>

    <div class="container mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-8 mt-8">
      <?php foreach ($hotels as $hotel) : ?>
        <div class="flex flex-col lg:flex-row bg-white p-8 rounded-lg shadow-lg" data-aos="fade-right">
          <div class="lg:w-1/2">
            <img src="admin/<?php echo htmlspecialchars($hotel['image_path'] ?? 'default.jpg'); ?>"
              alt="Hotel Image"
              class="w-full h-64 object-cover rounded-lg">
          </div>
          <div class="mx-4 lg:w-1/2 lg:pl-8 mt-4 lg:mt-0">
            <h3 class="text-xl font-semibold text-teal-600"><?php echo htmlspecialchars($hotel['hotel_name']); ?></h3>
            <p class="mt-2 text-gray-700"><?php echo htmlspecialchars($hotel['description']); ?></p>
            <p class="mt-4 text-gray-700"><strong>Price per Night:</strong> $<?php echo htmlspecialchars($hotel['price_per_night']); ?></p>
            <p class="mt-2 text-gray-700"><strong>Rating:</strong> <?php echo str_repeat('★', (int)$hotel['rating']); ?> (<?php echo htmlspecialchars($hotel['rating']); ?>/5)</p>
            <a href="hotel-bookings.php?id=<?php echo htmlspecialchars($hotel['id']); ?>" class="mt-4 bg-teal-600 text-white py-2 px-6 rounded-lg hover:bg-teal-700 transition inline-block">Book Now</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <footer class="bg-teal-600 text-white py-4" data-aos="fade-up">
    <div class="container mx-auto text-center">
      <p>&copy; 2025 Umrah Luxury Hotel. All rights reserved.</p>
    </div>
  </footer>

  <script src="assets/aos-master/dist/aos.js"></script>
  <?php include 'includes/js-links.php'; ?>
  <script>
    AOS.init({
      duration: 1000
    });
  </script>
</body>

</html>