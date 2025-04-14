<?php
include 'includes/db-config.php';

// Filter functions
function filterPackages($pdo, $filters = [])
{
  $baseSql = "SELECT id, title, description, package_type, departure_city, departure_date, departure_time, 
                arrival_city, return_date, return_time, price, package_image FROM packages WHERE 1=1";

  $params = [];

  // Apply filters
  if (!empty($filters['package_type'])) {
    $baseSql .= " AND package_type = :package_type";
    $params[':package_type'] = $filters['package_type'];
  }

  if (!empty($filters['departure_city'])) {
    $baseSql .= " AND departure_city = :departure_city";
    $params[':departure_city'] = $filters['departure_city'];
  }

  if (!empty($filters['min_price'])) {
    $baseSql .= " AND price >= :min_price";
    $params[':min_price'] = $filters['min_price'];
  }

  if (!empty($filters['max_price'])) {
    $baseSql .= " AND price <= :max_price";
    $params[':max_price'] = $filters['max_price'];
  }

  if (!empty($filters['departure_date'])) {
    $baseSql .= " AND departure_date >= :departure_date";
    $params[':departure_date'] = $filters['departure_date'];
  }

  // Add sorting
  $sortOptions = [
    'price_asc' => 'price ASC',
    'price_desc' => 'price DESC',
    'date_asc' => 'departure_date ASC',
    'date_desc' => 'departure_date DESC'
  ];

  $defaultSort = 'departure_date ASC';
  $sort = $defaultSort;

  if (!empty($filters['sort']) && isset($sortOptions[$filters['sort']])) {
    $sort = $sortOptions[$filters['sort']];
  }

  $baseSql .= " ORDER BY " . $sort;

  // Prepare and execute the query
  $stmt = $pdo->prepare($baseSql);
  $stmt->execute($params);

  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFilterValues($pdo)
{
  $filterValues = [];

  // Package types
  $stmt = $pdo->query("SELECT DISTINCT package_type FROM packages");
  $filterValues['package_types'] = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

  // Departure cities
  $stmt = $pdo->query("SELECT DISTINCT departure_city FROM packages");
  $filterValues['departure_cities'] = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

  return $filterValues;
}

// Get filter values for dropdowns
$filterValues = getFilterValues($pdo);

// Get filters from request
$filters = [
  'package_type' => $_GET['package_type'] ?? '',
  'departure_city' => $_GET['departure_city'] ?? '',
  'min_price' => $_GET['min_price'] ?? '',
  'max_price' => $_GET['max_price'] ?? '',
  'departure_date' => $_GET['departure_date'] ?? '',
  'sort' => $_GET['sort'] ?? ''
];

// Filter packages
$packages = filterPackages($pdo, $filters);

// Calculate total price
$totalPrice = 0;
foreach ($packages as $package) {
  $totalPrice += $package['price'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_package_id'])) {
  $deleteId = $_POST['delete_package_id'];
  $deleteStmt = $pdo->prepare("DELETE FROM packages WHERE id = :id");
  $deleteStmt->execute([':id' => $deleteId]);

  // Optional: redirect to prevent resubmission
  header("Location: " . $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);
  exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Umrah Packages - Detailed View</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-100">
  <div class="flex h-screen">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main flex-1 flex flex-col">
      <div class="bg-white shadow-md py-4 px-4 sm:px-6 flex justify-between items-center">
        <h1 class="text-lg sm:text-xl font-semibold">
          <i class="text-teal-600 fas fa-box mx-2"></i> Umrah Packages
        </h1>
        <a href="add-packages.php" class="flex items-center text-gray-700 hover:text-gray-900">
          <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
      </div>

      <div class="overflow-auto container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="mx-auto bg-white p-4 sm:p-8 rounded-lg shadow-lg">
          <!-- Filter Section -->
          <div class="p-4 border-b">
            <form method="get" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
              <!-- Package Type Filter -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Package Type</label>
                <select name="package_type" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm rounded-md">
                  <option value="">All Types</option>
                  <?php foreach ($filterValues['package_types'] as $type): ?>
                    <option value="<?= htmlspecialchars($type) ?>" <?= $filters['package_type'] === $type ? 'selected' : '' ?>>
                      <?= htmlspecialchars($type) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- Departure City Filter -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Departure City</label>
                <select name="departure_city" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm rounded-md">
                  <option value="">All Cities</option>
                  <?php foreach ($filterValues['departure_cities'] as $city): ?>
                    <option value="<?= htmlspecialchars($city) ?>" <?= $filters['departure_city'] === $city ? 'selected' : '' ?>>
                      <?= htmlspecialchars($city) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- Price Range Filter -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Price Range</label>
                <div class="flex space-x-2">
                  <input type="number" name="min_price" placeholder="Min" value="<?= htmlspecialchars($filters['min_price']) ?>"
                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm rounded-md">
                  <input type="number" name="max_price" placeholder="Max" value="<?= htmlspecialchars($filters['max_price']) ?>"
                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm rounded-md">
                </div>
              </div>

              <!-- Departure Date Filter -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Departure After</label>
                <input type="date" name="departure_date" value="<?= htmlspecialchars($filters['departure_date']) ?>"
                  class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm rounded-md">
              </div>

              <!-- Sort Filter -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Sort By</label>
                <select name="sort" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm rounded-md">
                  <option value="">Default</option>
                  <option value="price_asc" <?= $filters['sort'] === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                  <option value="price_desc" <?= $filters['sort'] === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                  <option value="date_asc" <?= $filters['sort'] === 'date_asc' ? 'selected' : '' ?>>Date: Earliest First</option>
                  <option value="date_desc" <?= $filters['sort'] === 'date_desc' ? 'selected' : '' ?>>Date: Latest First</option>
                </select>
              </div>

              <!-- Filter Buttons -->
              <div class="flex items-end space-x-2">
                <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded-md hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                  Apply Filters
                </button>
                <a href="?" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                  Reset
                </a>
              </div>
            </form>
          </div>

          <!-- Total Price Section -->
          <div class="p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center border-b space-y-4 sm:space-y-0">
            <div class="text-lg font-medium text-gray-800">
              <i class="fas fa-calculator text-teal-600 mr-2"></i>Total Price
            </div>
            <div class="text-right">
              <p class="text-lg font-semibold text-gray-900">Total: <span class="text-teal-600">$<?= number_format($totalPrice, 2) ?></span></p>
            </div>
          </div>

          <!-- Table Content -->
          <div class="overflow-x-auto" id="packageTableContainer">
            <table class="min-w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Package Info</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Departure & Arrival City</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($packages as $package): ?>
                  <tr class="hover:bg-gray-50">
                    <td class="px-4 py-4 text-sm text-gray-900">
                      #<?= htmlspecialchars($package['id']) ?>
                    </td>
                    <td class="px-4 py-4">
                      <div class="flex items-center">
                        <img class="h-10 w-10 rounded-full object-cover"
                          src="<?= htmlspecialchars($package['package_image']) ?>"
                          alt="Package Image">
                        <div class="ml-4">
                          <div class="text-sm font-medium text-gray-900">
                            <?= htmlspecialchars($package['title']) ?>
                          </div>
                          <div class="text-sm text-gray-500">
                            <?= nl2br(htmlspecialchars(substr($package['description'], 0, 100))) ?>...
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="px-4 py-4">
                      <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-teal-100 text-teal-800">
                        <?= htmlspecialchars($package['package_type']) ?>
                      </span>
                    </td>
                    <td class="px-4 py-4">
                      <div class="text-sm">
                        <div class="text-gray-900">
                          <i class="fas fa-plane-departure text-gray-400 mr-1"></i>
                          <?= htmlspecialchars($package['departure_city']) ?>
                          <span class="text-gray-500 text-xs ml-1">
                            (<?= date('M d, Y', strtotime($package['departure_date'])) ?>
                            <?= date('h:i A', strtotime($package['departure_time'])) ?>)
                          </span>
                        </div>
                        <div class="text-gray-900">
                          <i class="fas fa-plane-arrival text-gray-400 mr-1"></i>
                          <?= htmlspecialchars($package['arrival_city']) ?>
                          <span class="text-gray-500 text-xs ml-1">
                            (<?= date('M d, Y', strtotime($package['return_date'])) ?>
                            <?= date('h:i A', strtotime($package['return_time'])) ?>)
                          </span>
                        </div>
                      </div>
                    </td>
                    <td class="px-4 py-4">
                      <div class="text-sm font-medium text-gray-900">
                        $<?= number_format($package['price'], 2) ?>
                      </div>
                    </td>
                    <td class="px-4 py-3">
                      <form method="post" onsubmit="return confirm('Are you sure you want to delete this package?');">
                        <input type="hidden" name="delete_package_id" value="<?= $package['id'] ?>">
                        <button type="submit" class="text-red-600 hover:text-red-800 font-semibold">
                          <i class="fas fa-trash-alt"></i> Remove
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>