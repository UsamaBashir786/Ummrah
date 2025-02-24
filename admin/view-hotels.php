<?php
session_start();
require_once 'includes/db-config.php';
// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = json_decode(file_get_contents('php://input'), true);

  if ($data['action'] === 'delete' && !empty($data['id'])) {
    try {
      $pdo->beginTransaction();

      // Delete hotel images
      $stmt = $pdo->prepare("DELETE FROM hotel_images WHERE hotel_id = ?");
      $stmt->execute([$data['id']]);

      // Delete hotel
      $stmt = $pdo->prepare("DELETE FROM hotels WHERE id = ?");
      $stmt->execute([$data['id']]);

      $pdo->commit();

      header('Content-Type: application/json');
      echo json_encode(['success' => true]);
      exit;
    } catch (Exception $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }

      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'error' => $e->getMessage()]);
      exit;
    }
  }
}
$query = "SELECT h.*, hi.image_path, h.amenities 
          FROM hotels h 
          LEFT JOIN hotel_images hi ON h.id = hi.hotel_id";
// Base query without GROUP BY initially
$query = "SELECT h.*, hi.image_path 
          FROM hotels h 
          LEFT JOIN hotel_images hi ON h.id = hi.hotel_id";

// Initialize filters array
$filters = [];
$params = [];

// Handle search
if (isset($_GET['search']) && !empty($_GET['search'])) {
  $filters[] = "h.hotel_name LIKE ?";
  $params[] = "%" . $_GET['search'] . "%";
}

// Handle location filter
if (isset($_GET['location']) && !empty($_GET['location'])) {
  $filters[] = "h.location = ?";
  $params[] = $_GET['location'];
}

// Handle price range filter
if (isset($_GET['price_range']) && !empty($_GET['price_range'])) {
  $range = explode('-', $_GET['price_range']);
  if (count($range) == 2) {
    $filters[] = "h.price_per_night BETWEEN ? AND ?";
    $params[] = $range[0];
    $params[] = $range[1];
  }
}

// Handle rating filter
if (isset($_GET['rating']) && !empty($_GET['rating'])) {
  $filters[] = "h.rating = ?";
  $params[] = $_GET['rating'];
}

// Add WHERE clause if filters exist
if (!empty($filters)) {
  $query .= " WHERE " . implode(" AND ", $filters);
}

// Add GROUP BY after WHERE
$query .= " GROUP BY h.id";

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Count total records
$countQuery = "SELECT COUNT(DISTINCT h.id) as total FROM hotels h";
if (!empty($filters)) {
  $countQuery .= " WHERE " . implode(" AND ", $filters);
}

// Execute count query
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRecords / $limit);

// Add pagination to main query
$query .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Execute main query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);



?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <!-- Add this in your css-links.php or head section -->
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
          <i class="text-teal-600 fas fa-hotel mx-2"></i> View Hotels
        </h1>
      </div>

      <!-- Content Container -->
      <div class="overflow-auto container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="mx-auto bg-white p-4 sm:p-8 rounded-lg shadow-lg">
          <!-- Search and Filter Section -->
          <div class="mb-4 sm:mb-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
              <h2 class="text-xl sm:text-2xl font-bold text-teal-600">
                <i class="fas fa-list mr-2"></i>Hotel List
              </h2>
              <div class="flex flex-col sm:flex-row w-full sm:w-auto gap-4">
                <form method="GET" class="flex flex-col sm:flex-row w-full sm:w-auto gap-4">
                  <div class="relative w-full sm:w-auto">
                    <input type="text" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                      placeholder="Search hotels..."
                      class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                  </div>
                </form>
                <a href="add-hotels.php" class="w-full sm:w-auto bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 text-center">
                  <i class="fas fa-plus mr-2"></i>Add New Hotel
                </a>
              </div>
            </div>
          </div>

          <!-- Filters Row -->
          <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <select name="location" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
              <option value="">All Locations</option>
              <option value="makkah" <?php echo isset($_GET['location']) && $_GET['location'] == 'makkah' ? 'selected' : ''; ?>>Makkah</option>
              <option value="madinah" <?php echo isset($_GET['location']) && $_GET['location'] == 'madinah' ? 'selected' : ''; ?>>Madinah</option>
            </select>

            <select name="price_range" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
              <option value="">Price Range</option>
              <option value="0-100">$0 - $100</option>
              <option value="101-200">$101 - $200</option>
              <option value="201-99999">$201+</option>
            </select>

            <select name="rating" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
              <option value="">Star Rating</option>
              <option value="5">5 Stars</option>
              <option value="4">4 Stars</option>
              <option value="3">3 Stars</option>
            </select>

            <button type="submit" class="w-full px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
              Apply Filters
            </button>
          </form>

          <!-- Hotels Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <?php foreach ($hotels as $hotel): ?>
              <div class="bg-white rounded-lg shadow-md overflow-hidden" data-hotel-id="<?php echo $hotel['id']; ?>">
                <div class="relative">
                  <img src="<?php echo htmlspecialchars($hotel['image_path'] ?? 'images/default-hotel.jpg'); ?>"
                    alt="<?php echo htmlspecialchars($hotel['hotel_name']); ?>"
                    class="w-full h-40 sm:h-48 object-cover">
                  <div class="absolute top-2 right-2 bg-white px-2 py-1 rounded-full shadow">
                    <span class="text-yellow-400"><i class="fas fa-star"></i></span>
                    <span class="text-sm font-semibold"><?php echo htmlspecialchars($hotel['rating']); ?></span>
                  </div>
                </div>

                <div class="p-4">
                  <h3 class="text-lg sm:text-xl font-semibold mb-2"><?php echo htmlspecialchars($hotel['hotel_name']); ?></h3>

                  <div class="space-y-2 mb-4">
                    <p class="text-gray-600 text-sm flex items-center">
                      <i class="fas fa-map-marker-alt mr-2"></i>
                      <?php echo ucfirst(htmlspecialchars($hotel['location'])); ?>
                    </p>

                    <p class="text-gray-600 text-sm flex items-center">
                      <i class="fas fa-bed mr-2"></i>
                      <?php echo htmlspecialchars($hotel['room_count']); ?> Rooms
                    </p>

                    <p class="text-teal-600 font-bold text-sm flex items-center">
                      <i class="fas fa-dollar-sign mr-2"></i>
                      <?php echo number_format($hotel['price_per_night'], 2); ?>/night
                    </p>
                  </div>

                  <div class="mb-4">
                    <p class="text-gray-700 text-sm line-clamp-2">
                      <?php echo htmlspecialchars($hotel['description']); ?>
                    </p>
                  </div>

                  <?php if (!empty($hotel['amenities'])): ?>
                    <div class="mb-4">
                      <h4 class="text-sm font-semibold mb-2">Amenities:</h4>
                      <div class="flex flex-wrap gap-2">
                        <?php
                        $amenities = explode(',', $hotel['amenities']);
                        foreach ($amenities as $amenity): ?>
                          <span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded">
                            <?php echo htmlspecialchars(trim($amenity)); ?>
                          </span>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <div class="flex justify-end gap-2 mt-4">
                    <a href="edit-hotel.php?id=<?php echo $hotel['id']; ?>"
                      class="text-blue-600 hover:text-blue-800 p-1">
                      <i class="fas fa-edit"></i>
                    </a>
                    <button onclick="deleteHotel(<?php echo $hotel['id']; ?>)"
                      class="text-red-600 hover:text-red-800 p-1">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Pagination -->
          <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-xs sm:text-sm text-gray-700 order-2 sm:order-1">
              Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $totalRecords); ?> of <?php echo $totalRecords; ?> hotels
            </div>
            <div class="flex gap-2 order-1 sm:order-2">
              <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">Previous</a>
              <?php endif; ?>

              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>"
                  class="px-3 py-1 <?php echo $i === $page ? 'bg-teal-600 text-white' : 'border border-gray-300 hover:bg-gray-50'; ?> rounded-lg text-sm">
                  <?php echo $i; ?>
                </a>
              <?php endfor; ?>

              <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">Next</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/js-links.php'; ?>

  <script>
    function deleteHotel(hotelId) {
      Swal.fire({
        title: 'Delete Hotel',
        text: 'Are you sure you want to delete this hotel?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0D9488',
        cancelButtonColor: '#DC2626',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          fetch('view-hotels.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                action: 'delete',
                id: hotelId
              })
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                Swal.fire({
                  icon: 'success',
                  title: 'Deleted!',
                  text: 'Hotel has been deleted successfully',
                  timer: 1500,
                  showConfirmButton: false,
                  timerProgressBar: true
                }).then(() => {
                  window.location.reload();
                });
              } else {
                throw new Error(data.error || 'Failed to delete hotel');
              }
            })
            .catch(error => {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message,
                confirmButtonColor: '#0D9488'
              });
            });
        }
      });
    }
  </script>
</body>

</html>