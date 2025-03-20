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
$amenities = isset($_GET['amenities']) ? $_GET['amenities'] : [];
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'price_asc';

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

// Apply sorting
switch ($sort_by) {
  case 'price_asc':
    $query .= " ORDER BY h.price_per_night ASC";
    break;
  case 'price_desc':
    $query .= " ORDER BY h.price_per_night DESC";
    break;
  case 'name_asc':
    $query .= " ORDER BY h.hotel_name ASC";
    break;
  default:
    $query .= " ORDER BY h.price_per_night ASC";
}

$stmt = $conn->prepare($query);
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result();
$hotels = $result->fetch_all(MYSQLI_ASSOC);

// Get all unique locations for the filter dropdown
$location_query = "SELECT DISTINCT location FROM hotels ORDER BY location";
$location_result = $conn->query($location_query);
$locations = [];
while ($row = $location_result->fetch_assoc()) {
  $locations[] = $row['location'];
}

// Get min and max prices for range slider defaults
$price_query = "SELECT MIN(price_per_night) as min_price, MAX(price_per_night) as max_price FROM hotels";
$price_result = $conn->query($price_query);
$price_range = $price_result->fetch_assoc();
$db_min_price = $price_range['min_price'] ?? 0;
$db_max_price = $price_range['max_price'] ?? 1000;

// Common amenities list
$common_amenities = [
  'wifi' => 'Wi-Fi',
  'pool' => 'Swimming Pool',
  'spa' => 'Spa & Wellness',
  'gym' => 'Fitness Center',
  'restaurant' => 'Restaurant',
  'parking' => 'Free Parking',
  'ac' => 'Air Conditioning',
  'breakfast' => 'Free Breakfast'
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <link href="https://cdn.jsdelivr.net/npm/nouislider@14.6.3/distribute/nouislider.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
  <style>
    /* Custom Styles */
    .hero-section {
      background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('assets/images/hotel-banner.jpg');
      background-size: cover;
      background-position: center;
      height: 300px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .hotel-card {
      transition: all 0.3s ease;
      overflow: hidden;
      border-radius: 12px;
    }

    .hotel-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .hotel-image {
      height: 240px;
      object-fit: cover;
      width: 100%;
      transition: transform 0.5s ease;
    }

    .hotel-card:hover .hotel-image {
      transform: scale(1.05);
    }

    .price-badge {
      position: absolute;
      top: 20px;
      right: 20px;
      background-color: rgba(0, 0, 0, 0.6);
      color: white;
      padding: 8px 12px;
      border-radius: 20px;
      font-weight: 600;
    }

    .location-badge {
      position: absolute;
      top: 20px;
      left: 20px;
      background-color: rgba(20, 184, 166, 0.9);
      color: white;
      padding: 6px 12px;
      border-radius: 20px;
      font-weight: 500;
      font-size: 0.875rem;
      display: flex;
      align-items: center;
    }

    .filter-sidebar {
      position: sticky;
      top: 20px;
    }

    .amenity-checkbox {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 8px;
    }

    .amenity-checkbox input[type="checkbox"] {
      width: 18px;
      height: 18px;
    }

    .star-rating {
      color: #FBC02D;
      font-size: 1.2rem;
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      background: #f9fafb;
      border-radius: 12px;
    }

    .skeleton {
      background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
      background-size: 200% 100%;
      animation: skeleton-loading 1.5s infinite;
      border-radius: 4px;
    }

    @keyframes skeleton-loading {
      0% {
        background-position: 200% 0;
      }

      100% {
        background-position: -200% 0;
      }
    }

    .sort-option {
      padding: 8px 16px;
      border-radius: 20px;
      background-color: #f3f4f6;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .sort-option.active {
      background-color: #14b8a6;
      color: white;
    }

    .mobile-filter-toggle {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 50;
      background-color: #14b8a6;
      color: white;
      border-radius: 50%;
      width: 60px;
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    /* Tooltip styles */
    .tooltip {
      position: relative;
      display: inline-block;
    }

    .tooltip .tooltip-text {
      visibility: hidden;
      width: 120px;
      background-color: rgba(0, 0, 0, 0.8);
      color: #fff;
      text-align: center;
      border-radius: 6px;
      padding: 5px;
      position: absolute;
      z-index: 1;
      bottom: 125%;
      left: 50%;
      margin-left: -60px;
      opacity: 0;
      transition: opacity 0.3s;
      font-size: 0.75rem;
    }

    .tooltip:hover .tooltip-text {
      visibility: visible;
      opacity: 1;
    }

    /* Animation for showing filter on mobile */
    @keyframes slideIn {
      from {
        transform: translateX(100%);
      }

      to {
        transform: translateX(0);
      }
    }

    .slide-in {
      animation: slideIn 0.3s forwards;
    }

    /* Styles for the view options */
    .view-option {
      cursor: pointer;
      padding: 0.5rem;
      display: flex;
      align-items: center;
      border-radius: 0.375rem;
    }

    .view-option.active {
      background-color: #f3f4f6;
    }

    /* Styles for list view */
    .grid-view {
      display: grid;
      grid-template-columns: repeat(1, 1fr);
      gap: 1.5rem;
    }

    @media (min-width: 768px) {
      .grid-view {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    .list-view {
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
    }

    .list-view .hotel-card {
      display: grid;
      grid-template-columns: 1fr;
    }

    @media (min-width: 768px) {
      .list-view .hotel-card {
        grid-template-columns: 300px 1fr;
      }
    }

    .list-view .hotel-image-container {
      height: 200px;
    }

    .list-view .hotel-details {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .heart-button {
      transition: all 0.3s ease;
    }

    .heart-button:hover,
    .heart-button.active {
      color: #e11d48;
      transform: scale(1.2);
    }

    .sliding-panel {
      position: fixed;
      top: 0;
      right: -100%;
      width: 100%;
      height: 100%;
      background-color: white;
      z-index: 1000;
      transition: right 0.3s ease-in-out;
      overflow-y: auto;
      box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
    }

    @media (min-width: 640px) {
      .sliding-panel {
        width: 400px;
      }
    }

    .sliding-panel.open {
      right: 0;
    }

    .overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 999;
      display: none;
    }

    .overlay.show {
      display: block;
    }

    /* Pill badges for amenities */
    .amenity-badge {
      display: inline-block;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      background-color: #f3f4f6;
      color: #4b5563;
      font-size: 0.75rem;
      font-weight: 500;
      margin-right: 0.5rem;
      margin-bottom: 0.5rem;
    }
  </style>
</head>

<body class="bg-gray-50">
  <?php include 'includes/navbar.php'; ?>

  <!-- <div class="my-16">&nbsp;</div> -->

  <!-- Hero Section -->
  <section class="hero-section mb-12 animate__animated animate__fadeIn" style="background: url('assets/images/hero/hero.jpg') no-repeat center center/cover; height: 400px;">
    <div class="text-center px-4">
      <h1 class="text-4xl md:text-5xl font-bold mb-2">Find Your Perfect Stay</h1>
      <p class="text-xl">Discover premium accommodations for your Umrah journey</p>
    </div>
  </section>

  <div class="container mx-auto px-4 max-w-7xl mb-16">
    <!-- Mobile Filter Toggle Button -->
    <button id="mobile-filter-toggle" class="mobile-filter-toggle md:hidden">
      <i class="fas fa-sliders-h text-xl"></i>
    </button>

    <!-- Filter Overlay for Mobile -->
    <div id="filter-overlay" class="overlay"></div>

    <!-- Mobile Filter Panel -->
    <div id="mobile-filter-panel" class="sliding-panel p-6">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-bold">Filters</h3>
        <button id="close-filter" class="text-gray-500 hover:text-gray-700">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>

      <!-- Mobile Filter Form (duplicate of desktop) -->
      <form method="GET" action="" id="mobile-filter-form" class="space-y-6">
        <!-- Location Filter -->
        <div>
          <label class="block text-gray-700 font-semibold mb-2">Location</label>
          <select name="location" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
            <option value="">All Locations</option>
            <?php foreach ($locations as $loc): ?>
              <option value="<?php echo htmlspecialchars($loc); ?>" <?php if ($location === $loc) echo 'selected'; ?>>
                <?php echo htmlspecialchars($loc); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Price Range -->
        <div>
          <label class="block text-gray-700 font-semibold mb-2">Price Range (per night)</label>
          <div class="px-2">
            <div id="mobile-price-slider" class="mb-4"></div>
            <div class="flex justify-between">
              <span id="mobile-min-price-display">$<?php echo $min_price; ?></span>
              <span id="mobile-max-price-display">$<?php echo $max_price; ?></span>
            </div>
            <input type="hidden" name="min_price" id="mobile-min-price" value="<?php echo $min_price; ?>">
            <input type="hidden" name="max_price" id="mobile-max-price" value="<?php echo $max_price; ?>">
          </div>
        </div>

        <!-- Amenities Filter -->
        <div>
          <label class="block text-gray-700 font-semibold mb-2">Amenities</label>
          <div class="grid grid-cols-2 gap-2">
            <?php foreach ($common_amenities as $key => $label): ?>
              <label class="flex items-center space-x-2">
                <input type="checkbox" name="amenities[]" value="<?php echo $key; ?>" class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500" <?php if (in_array($key, $amenities)) echo 'checked'; ?>>
                <span class="text-sm"><?php echo $label; ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex space-x-2">
          <button type="submit" class="w-full bg-teal-600 text-white py-3 px-4 rounded-lg hover:bg-teal-700 transition font-semibold">
            Apply Filters
          </button>
          <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="block text-center bg-gray-200 text-gray-700 py-3 px-4 rounded-lg hover:bg-gray-300 transition font-semibold">
            Clear All
          </a>
        </div>
      </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
      <!-- Desktop Filter Sidebar -->
      <div class="hidden md:block">
        <div class="filter-sidebar bg-white p-6 rounded-xl shadow-md">
          <h3 class="text-xl font-bold text-gray-800 mb-6">Filters</h3>

          <form method="GET" action="" id="filter-form">
            <!-- Location Filter -->
            <div class="mb-6">
              <label class="block text-gray-700 font-semibold mb-2">Location</label>
              <select name="location" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                <option value="">All Locations</option>
                <?php foreach ($locations as $loc): ?>
                  <option value="<?php echo htmlspecialchars($loc); ?>" <?php if ($location === $loc) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($loc); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Price Range -->
            <div class="mb-6">
              <label class="block text-gray-700 font-semibold mb-2">Price Range (per night)</label>
              <div class="px-2">
                <div id="price-slider" class="mb-4"></div>
                <div class="flex justify-between">
                  <span id="min-price-display">$<?php echo $min_price; ?></span>
                  <span id="max-price-display">$<?php echo $max_price; ?></span>
                </div>
                <input type="hidden" name="min_price" id="min-price" value="<?php echo $min_price; ?>">
                <input type="hidden" name="max_price" id="max-price" value="<?php echo $max_price; ?>">
              </div>
            </div>

            <!-- Amenities Filter -->
            <div class="mb-6">
              <label class="block text-gray-700 font-semibold mb-2">Amenities</label>
              <div class="space-y-2">
                <?php foreach ($common_amenities as $key => $label): ?>
                  <label class="flex items-center space-x-2">
                    <input type="checkbox" name="amenities[]" value="<?php echo $key; ?>" class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500" <?php if (in_array($key, $amenities)) echo 'checked'; ?>>
                    <span><?php echo $label; ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Sort By (Hidden) -->
            <input type="hidden" name="sort_by" id="sort-by-input" value="<?php echo htmlspecialchars($sort_by); ?>">

            <!-- Submit Buttons -->
            <div class="flex space-x-2">
              <button type="submit" class="w-full bg-teal-600 text-white py-3 px-4 rounded-lg hover:bg-teal-700 transition font-semibold">
                Apply Filters
              </button>
              <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="block text-center p-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                <i class="fas fa-redo"></i>
              </a>
            </div>
          </form>
        </div>
      </div>

      <!-- Hotel Listings -->
      <div class="md:col-span-3">
        <!-- Results Header -->
        <div class="bg-white p-4 rounded-xl shadow-sm mb-6">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
              <h2 class="text-xl font-bold text-gray-800">
                <?php echo count($hotels); ?> Hotels Found
              </h2>
              <p class="text-gray-500 text-sm mt-1">
                <?php
                $filter_text = [];
                if (!empty($location)) $filter_text[] = "in " . htmlspecialchars($location);
                echo !empty($filter_text) ? implode(", ", $filter_text) : "Showing all hotels";
                ?>
              </p>
            </div>

            <div class="mt-4 md:mt-0 flex flex-col sm:flex-row gap-4">
              <!-- View Options -->
              <div class="flex items-center space-x-2 p-2 bg-gray-100 rounded-lg">
                <span class="text-gray-700 text-sm mr-2">View:</span>
                <div id="grid-view-btn" class="view-option active" title="Grid View">
                  <i class="fas fa-th-large"></i>
                </div>
                <div id="list-view-btn" class="view-option" title="List View">
                  <i class="fas fa-list"></i>
                </div>
              </div>

              <!-- Sort Options -->
              <div class="flex items-center space-x-2">
                <span class="text-gray-700 text-sm hidden sm:inline">Sort by:</span>
                <select id="sort-select" class="p-2 border border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500">
                  <option value="price_asc" <?php if ($sort_by === 'price_asc') echo 'selected'; ?>>Price: Low to High</option>
                  <option value="price_desc" <?php if ($sort_by === 'price_desc') echo 'selected'; ?>>Price: High to Low</option>
                  <option value="name_asc" <?php if ($sort_by === 'name_asc') echo 'selected'; ?>>Name (A-Z)</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <?php if (empty($hotels)): ?>
          <!-- Empty State -->
          <div class="empty-state">
            <img src="assets/images/no-results.svg" alt="No Results" class="w-48 h-48 mx-auto mb-4">
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No Hotels Found</h3>
            <p class="text-gray-500 mb-6">We couldn't find any hotels matching your criteria.</p>
            <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="bg-teal-600 text-white py-2 px-6 rounded-lg hover:bg-teal-700 transition">
              Reset Filters
            </a>
          </div>
        <?php else: ?>
          <!-- Hotel Cards -->
          <div id="hotel-container" class="grid-view">
            <?php foreach ($hotels as $hotel):
              // Parse the amenities JSON
              $hotel_amenities = [];
              if (!empty($hotel['amenities'])) {
                $hotel_amenities = json_decode($hotel['amenities'], true) ?: [];
              }
            ?>
              <div class="hotel-card bg-white shadow-md overflow-hidden animate__animated animate__fadeIn">
                <!-- Card for Grid View -->
                <div class="grid-card">
                  <div class="relative hotel-image-container overflow-hidden">
                    <img src="admin/<?php echo htmlspecialchars($hotel['image_path'] ?? 'assets/images/hotel-placeholder.jpg'); ?>"
                      alt="<?php echo htmlspecialchars($hotel['hotel_name']); ?>"
                      class="hotel-image object-cover w-full">
                    <div class="price-badge">
                      $<?php echo number_format($hotel['price_per_night'], 0); ?> <span class="text-xs font-normal">/ night</span>
                    </div>
                    <div class="location-badge">
                      <i class="fas fa-map-marker-alt mr-1"></i>
                      <?php echo htmlspecialchars($hotel['location']); ?>
                    </div>
                  </div>

                  <div class="p-6">
                    <div class="flex justify-between items-start">
                      <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($hotel['hotel_name']); ?></h3>
                      <button class="heart-button text-gray-400 hover:text-red-500">
                        <i class="far fa-heart text-xl"></i>
                      </button>
                    </div>

                    <div class="flex items-center mb-3">
                      <div class="star-rating mr-2">
                        <?php echo str_repeat('★', (int)$hotel['rating']); ?>
                        <?php echo str_repeat('☆', 5 - (int)$hotel['rating']); ?>
                      </div>
                      <span class="text-sm text-gray-600">(<?php echo htmlspecialchars($hotel['rating']); ?>/5)</span>
                    </div>

                    <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                      <?php echo htmlspecialchars($hotel['description']); ?>
                    </p>

                    <?php if (!empty($hotel_amenities)): ?>
                      <div class="mb-4">
                        <?php
                        $displayed_amenities = array_slice($hotel_amenities, 0, 3);
                        foreach ($displayed_amenities as $amenity):
                        ?>
                          <span class="amenity-badge">
                            <i class="fas fa-check text-teal-500 mr-1"></i>
                            <?php echo htmlspecialchars(ucfirst($amenity)); ?>
                          </span>
                        <?php endforeach; ?>

                        <?php if (count($hotel_amenities) > 3): ?>
                          <span class="amenity-badge tooltip">
                            +<?php echo count($hotel_amenities) - 3; ?> more
                            <span class="tooltip-text">
                              <?php echo htmlspecialchars(implode(', ', array_slice($hotel_amenities, 3))); ?>
                            </span>
                          </span>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>

                    <div class="mt-4">
                      <a href="hotel-bookings.php?id=<?php echo htmlspecialchars($hotel['id']); ?>"
                        class="block w-full bg-teal-600 text-white text-center py-3 px-6 rounded-lg hover:bg-teal-700 transition font-medium">
                        Book Now
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <footer class="bg-teal-600 text-white py-8 mt-16">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        <div>
          <h3 class="text-xl font-bold mb-4">Umrah Luxury Hotels</h3>
          <p class="text-sm">Find the perfect accommodation for your sacred journey.</p>
        </div>
        <div>
          <h4 class="font-semibold mb-4">Quick Links</h4>
          <ul class="space-y-2 text-sm">
            <li><a href="#" class="hover:text-teal-200 transition">Home</a></li>
            <li><a href="#" class="hover:text-teal-200 transition">Hotels</a></li>
            <li><a href="#" class="hover:text-teal-200 transition">Flights</a></li>
            <li><a href="#" class="hover:text-teal-200 transition">Packages</a></li>
          </ul>
        </div>
        <div>
          <h4 class="font-semibold mb-4">Help & Support</h4>
          <ul class="space-y-2 text-sm">
            <li><a href="#" class="hover:text-teal-200 transition">FAQs</a></li>
            <li><a href="#" class="hover:text-teal-200 transition">Contact Us</a></li>
            <li><a href="#" class="hover:text-teal-200 transition">Terms & Conditions</a></li>
            <li><a href="#" class="hover:text-teal-200 transition">Privacy Policy</a></li>
          </ul>
        </div>
        <div>
          <h4 class="font-semibold mb-4">Connect With Us</h4>
          <div class="flex space-x-4">
            <a href="#" class="text-white hover:text-teal-200 transition"><i class="fab fa-facebook-f text-xl"></i></a>
            <a href="#" class="text-white hover:text-teal-200 transition"><i class="fab fa-twitter text-xl"></i></a>
            <a href="#" class="text-white hover:text-teal-200 transition"><i class="fab fa-instagram text-xl"></i></a>
            <a href="#" class="text-white hover:text-teal-200 transition"><i class="fab fa-linkedin-in text-xl"></i></a>
          </div>
          <p class="mt-4 text-sm">Subscribe to our newsletter</p>
          <div class="flex mt-2">
            <input type="email" placeholder="Your email" class="p-2 text-gray-800 rounded-l-lg w-full focus:outline-none">
            <button class="bg-white text-teal-600 px-4 rounded-r-lg font-medium hover:bg-teal-100 transition">
              <i class="fas fa-paper-plane"></i>
            </button>
          </div>
        </div>
      </div>
      <div class="border-t border-teal-500 mt-6 pt-6 text-center text-sm">
        <p>&copy; 2025 Umrah Luxury Hotels. All rights reserved.</p>
      </div>
    </div>
  </footer>

  <?php include 'includes/js-links.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/nouislider@14.6.3/distribute/nouislider.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize loading state
      let isLoading = false;

      // Initialize view state (grid or list)
      let viewMode = 'grid';

      // Function to initialize the price slider
      function initPriceSlider(sliderId, minPriceId, maxPriceId, minPriceDisplayId, maxPriceDisplayId) {
        const slider = document.getElementById(sliderId);

        if (!slider) return;

        const minPriceInput = document.getElementById(minPriceId);
        const maxPriceInput = document.getElementById(maxPriceId);
        const minPriceDisplay = document.getElementById(minPriceDisplayId);
        const maxPriceDisplay = document.getElementById(maxPriceDisplayId);

        noUiSlider.create(slider, {
          start: [
            parseInt(minPriceInput.value) || <?php echo $db_min_price; ?>,
            parseInt(maxPriceInput.value) || <?php echo $db_max_price; ?>
          ],
          connect: true,
          step: 10,
          range: {
            'min': <?php echo $db_min_price; ?>,
            'max': <?php echo $db_max_price; ?>
          },
          format: {
            to: value => Math.round(value),
            from: value => Math.round(value)
          }
        });

        slider.noUiSlider.on('update', function(values, handle) {
          const min = values[0];
          const max = values[1];

          minPriceInput.value = min;
          maxPriceInput.value = max;
          minPriceDisplay.textContent = '$' + min;
          maxPriceDisplay.textContent = '$' + max;
        });
      }

      // Initialize both price sliders (desktop and mobile)
      initPriceSlider('price-slider', 'min-price', 'max-price', 'min-price-display', 'max-price-display');
      initPriceSlider('mobile-price-slider', 'mobile-min-price', 'mobile-max-price', 'mobile-min-price-display', 'mobile-max-price-display');

      // Mobile filter panel toggle
      const mobileFilterToggle = document.getElementById('mobile-filter-toggle');
      const mobileFilterPanel = document.getElementById('mobile-filter-panel');
      const closeFilterBtn = document.getElementById('close-filter');
      const filterOverlay = document.getElementById('filter-overlay');

      if (mobileFilterToggle && mobileFilterPanel) {
        mobileFilterToggle.addEventListener('click', function() {
          mobileFilterPanel.classList.add('open');
          filterOverlay.classList.add('show');
          document.body.style.overflow = 'hidden';
        });
      }

      if (closeFilterBtn) {
        closeFilterBtn.addEventListener('click', function() {
          mobileFilterPanel.classList.remove('open');
          filterOverlay.classList.remove('show');
          document.body.style.overflow = '';
        });
      }

      if (filterOverlay) {
        filterOverlay.addEventListener('click', function() {
          mobileFilterPanel.classList.remove('open');
          filterOverlay.classList.remove('show');
          document.body.style.overflow = '';
        });
      }

      // Sort select handler
      const sortSelect = document.getElementById('sort-select');
      const sortByInput = document.getElementById('sort-by-input');

      if (sortSelect && sortByInput) {
        sortSelect.addEventListener('change', function() {
          sortByInput.value = this.value;
          document.getElementById('filter-form').submit();
        });
      }

      // View toggle functionality
      const gridViewBtn = document.getElementById('grid-view-btn');
      const listViewBtn = document.getElementById('list-view-btn');
      const hotelContainer = document.getElementById('hotel-container');

      if (gridViewBtn && listViewBtn && hotelContainer) {
        gridViewBtn.addEventListener('click', function() {
          if (viewMode === 'grid') return;

          viewMode = 'grid';
          hotelContainer.classList.remove('list-view');
          hotelContainer.classList.add('grid-view');

          // Update active state
          listViewBtn.classList.remove('active');
          gridViewBtn.classList.add('active');

          // Store preference in localStorage
          localStorage.setItem('hotelViewMode', 'grid');
        });

        listViewBtn.addEventListener('click', function() {
          if (viewMode === 'list') return;

          viewMode = 'list';
          hotelContainer.classList.remove('grid-view');
          hotelContainer.classList.add('list-view');

          // Update active state
          gridViewBtn.classList.remove('active');
          listViewBtn.classList.add('active');

          // Store preference in localStorage
          localStorage.setItem('hotelViewMode', 'list');
        });

        // Load saved preference
        const savedViewMode = localStorage.getItem('hotelViewMode');
        if (savedViewMode === 'list') {
          listViewBtn.click();
        }
      }

      // Favorite button functionality
      const heartButtons = document.querySelectorAll('.heart-button');

      heartButtons.forEach(button => {
        button.addEventListener('click', function() {
          const icon = this.querySelector('i');

          if (icon.classList.contains('far')) {
            icon.classList.remove('far');
            icon.classList.add('fas');
            this.classList.add('active');
          } else {
            icon.classList.remove('fas');
            icon.classList.add('far');
            this.classList.remove('active');
          }

          // You would typically send an AJAX request here to update favorites
        });
      });

      // AOS initialization if available
      if (typeof AOS !== 'undefined') {
        AOS.init({
          duration: 800,
          once: true
        });
      }
    });
  </script>
</body>

</html>