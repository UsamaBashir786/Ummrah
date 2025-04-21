<?php

// Function to get filtered route data
function getFilteredRouteData($conn, $filterType = null, $searchQuery = null, $yearFilter = null, $routeFilter = null)
{
  // Input validation
  $yearFilter = $yearFilter ? (int)$yearFilter : 2024;
  $filterType = in_array($filterType, ['taxi', 'rentacar']) ? $filterType : null;
  $routeFilter = $routeFilter ? (int)$routeFilter : null;
  $searchQuery = $searchQuery ? trim($searchQuery) : null;

  $data = ['taxi' => [], 'rentacar' => []];

  // Prepare base queries
  $taxiQuery = "SELECT id, route_number, route_name, camry_sonata_price, starex_staria_price, hiace_price 
                  FROM taxi_routes WHERE year = ?";
  $rentacarQuery = "SELECT id, route_number, route_name, gmc_16_19_price, gmc_22_23_price, coaster_price 
                     FROM rentacar_routes WHERE year = ?";

  $params = [$yearFilter];
  $types = "i";

  // Add search filter
  if ($searchQuery) {
    $taxiQuery .= " AND route_name LIKE ?";
    $rentacarQuery .= " AND route_name LIKE ?";
    $params[] = "%$searchQuery%";
    $types .= "s";
  }

  // Add route filter
  if ($routeFilter) {
    $taxiQuery .= " AND route_number = ?";
    $rentacarQuery .= " AND route_number = ?";
    $params[] = $routeFilter;
    $types .= "i";
  }

  $taxiQuery .= " ORDER BY route_number";
  $rentacarQuery .= " ORDER BY route_number";

  // Execute queries based on filter type
  try {
    if (!$filterType || $filterType === 'taxi') {
      $stmt = $conn->prepare($taxiQuery);
      $stmt->bind_param($types, ...$params);
      $stmt->execute();
      $result = $stmt->get_result();
      while ($row = $result->fetch_assoc()) {
        $data['taxi'][] = array_map('htmlspecialchars', $row);
      }
      $stmt->close();
    }

    if (!$filterType || $filterType === 'rentacar') {
      $stmt = $conn->prepare($rentacarQuery);
      $stmt->bind_param($types, ...$params);
      $stmt->execute();
      $result = $stmt->get_result();
      while ($row = $result->fetch_assoc()) {
        $data['rentacar'][] = array_map('htmlspecialchars', $row);
      }
      $stmt->close();
    }
  } catch (Exception $e) {
    error_log("Error in getFilteredRouteData: " . $e->getMessage());
    return $data;
  }

  return $data;
}

// Function to get transport statistics
function getTransportStats($conn, $filterType = null, $searchQuery = null, $yearFilter = null, $routeFilter = null)
{
  $yearFilter = $yearFilter ? (int)$yearFilter : 2024;
  $filterType = in_array($filterType, ['taxi', 'rentacar']) ? $filterType : null;
  $routeFilter = $routeFilter ? (int)$routeFilter : null;
  $searchQuery = $searchQuery ? trim($searchQuery) : null;

  $stats = [
    'taxi' => [
      'total_routes' => 0,
      'total_camry_price' => 0,
      'total_starex_price' => 0,
      'total_hiace_price' => 0,
      'total_vehicles' => 0,
      'vehicle_counts' => ['camry' => 0, 'starex' => 0, 'hiace' => 0],
      'total_price' => 0
    ],
    'rentacar' => [
      'total_routes' => 0,
      'total_gmc_16_19_price' => 0,
      'total_gmc_22_23_price' => 0,
      'total_coaster_price' => 0,
      'total_vehicles' => 0,
      'vehicle_counts' => ['gmc_16_19' => 0, 'gmc_22_23' => 0, 'coaster' => 0],
      'total_price' => 0
    ],
    'combined' => [
      'total_routes' => 0,
      'total_vehicles' => 0,
      'total_price' => 0
    ]
  ];

  $taxiQuery = "SELECT 
        COUNT(*) as total_routes, 
        COALESCE(SUM(camry_sonata_price), 0) as total_camry_price,
        COALESCE(SUM(starex_staria_price), 0) as total_starex_price,
        COALESCE(SUM(hiace_price), 0) as total_hiace_price,
        SUM(CASE WHEN camry_sonata_price > 0 THEN 1 ELSE 0 END) as camry_count,
        SUM(CASE WHEN starex_staria_price > 0 THEN 1 ELSE 0 END) as starex_count,
        SUM(CASE WHEN hiace_price > 0 THEN 1 ELSE 0 END) as hiace_count
        FROM taxi_routes WHERE year = ?";
  $rentacarQuery = "SELECT
COUNT(*) as total_routes,
COALESCE(SUM(gmc_16_19_price), 0) as total_gmc_16_19_price,
COALESCE(SUM(gmc_22_23_price), 0) as total_gmc_22_23_price,
COALESCE(SUM(coaster_price), 0) as total_coaster_price,
SUM(CASE WHEN gmc_16_19_price > 0 THEN 1 ELSE 0 END) as gmc_16_19_count,
SUM(CASE WHEN gmc_22_23_price > 0 THEN 1 ELSE 0 END) as gmc_22_23_count,
SUM(CASE WHEN coaster_price > 0 THEN 1 ELSE 0 END) as coaster_count
FROM rentacar_routes WHERE year = ?";

  $params = [$yearFilter];
  $types = "i";

  if ($searchQuery) {
    $taxiQuery .= " AND route_name LIKE ?";
    $rentacarQuery .= " AND route_name LIKE ?";
    $params[] = "%$searchQuery%";
    $types .= "s";
  }

  if ($routeFilter) {
    $taxiQuery .= " AND route_number = ?";
    $rentacarQuery .= " AND route_number = ?";
    $params[] = $routeFilter;
    $types .= "i";
  }

  try {
    if (!$filterType || $filterType === 'taxi') {
      $stmt = $conn->prepare($taxiQuery);
      $stmt->bind_param($types, ...$params);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($row = $result->fetch_assoc()) {
        $stats['taxi'] = array_merge($stats['taxi'], [
          'total_routes' => (int)$row['total_routes'],
          'total_camry_price' => (float)$row['total_camry_price'],
          'total_starex_price' => (float)$row['total_starex_price'],
          'total_hiace_price' => (float)$row['total_hiace_price'],
          'vehicle_counts' => [
            'camry' => (int)$row['camry_count'],
            'starex' => (int)$row['starex_count'],
            'hiace' => (int)$row['hiace_count']
          ]
        ]);

        $stats['taxi']['total_vehicles'] =
          ($row['camry_count'] > 0 ? 1 : 0) +
          ($row['starex_count'] > 0 ? 1 : 0) +
          ($row['hiace_count'] > 0 ? 1 : 0);

        $stats['taxi']['total_price'] =
          $stats['taxi']['total_camry_price'] +
          $stats['taxi']['total_starex_price'] +
          $stats['taxi']['total_hiace_price'];
      }
      $stmt->close();
    }

    if (!$filterType || $filterType === 'rentacar') {
      $stmt = $conn->prepare($rentacarQuery);
      $stmt->bind_param($types, ...$params);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($row = $result->fetch_assoc()) {
        $stats['rentacar'] = array_merge($stats['rentacar'], [
          'total_routes' => (int)$row['total_routes'],
          'total_gmc_16_19_price' => (float)$row['total_gmc_16_19_price'],
          'total_gmc_22_23_price' => (float)$row['total_gmc_22_23_price'],
          'total_coaster_price' => (float)$row['total_coaster_price'],
          'vehicle_counts' => [
            'gmc_16_19' => (int)$row['gmc_16_19_count'],
            'gmc_22_23' => (int)$row['gmc_22_23_count'],
            'coaster' => (int)$row['coaster_count']
          ]
        ]);

        $stats['rentacar']['total_vehicles'] =
          ($row['gmc_16_19_count'] > 0 ? 1 : 0) +
          ($row['gmc_22_23_count'] > 0 ? 1 : 0) +
          ($row['coaster_count'] > 0 ? 1 : 0);

        $stats['rentacar']['total_price'] =
          $stats['rentacar']['total_gmc_16_19_price'] +
          $stats['rentacar']['total_gmc_22_23_price'] +
          $stats['rentacar']['total_coaster_price'];
      }
      $stmt->close();
    }

    // Calculate combined stats
    $stats['combined'] = [
      'total_routes' => $stats['taxi']['total_routes'] + $stats['rentacar']['total_routes'],
      'total_vehicles' => $stats['taxi']['total_vehicles'] + $stats['rentacar']['total_vehicles'],
      'total_price' => $stats['taxi']['total_price'] + $stats['rentacar']['total_price']
    ];
  } catch (Exception $e) {
    error_log("Error in getTransportStats: " . $e->getMessage());
  }

  return $stats;
}

// Function to get available years
function getAvailableYears($conn)
{
  $years = [];
  try {
    $query = "SELECT DISTINCT year FROM (
            SELECT year FROM taxi_routes 
            UNION 
            SELECT year FROM rentacar_routes
        ) as years ORDER BY year DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
      $years[] = (int)$row['year'];
    }
    $stmt->close();
  } catch (Exception $e) {
    error_log("Error in getAvailableYears: " . $e->getMessage());
  }
  return $years;
}

// Function to get route numbers
function getRouteNumbers($conn)
{
  $routes = [];
  try {
    $query = "SELECT DISTINCT route_number FROM (
            SELECT route_number FROM taxi_routes 
            UNION 
            SELECT route_number FROM rentacar_routes
        ) as routes ORDER BY route_number";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
      $routes[] = (int)$row['route_number'];
    }
    $stmt->close();
  } catch (Exception $e) {
    error_log("Error in getRouteNumbers: " . $e->getMessage());
  }
  return $routes;
}

// Validate and sanitize inputs
$filter_type_raw = filter_input(INPUT_GET, 'filter_type', FILTER_UNSAFE_RAW);
$filter_type = in_array($filter_type_raw, ['taxi', 'rentacar']) ? $filter_type_raw : null;

$search_query_raw = filter_input(INPUT_GET, 'search_query', FILTER_UNSAFE_RAW);
$search_query = $search_query_raw !== null ? trim(strip_tags($search_query_raw)) : null;

$year_filter = filter_input(INPUT_GET, 'year_filter', FILTER_VALIDATE_INT) ?: null;
$route_filter = filter_input(INPUT_GET, 'route_filter', FILTER_VALIDATE_INT) ?: null;

// Validate CSRF token for form submission
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['csrf_token'])) {
  if (!hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
    die('Invalid CSRF token');
  }
}

// Get data
$available_years = getAvailableYears($conn);
$route_numbers = getRouteNumbers($conn);
$transport_stats = getTransportStats($conn, $filter_type, $search_query, $year_filter, $route_filter);
$filtered_routes = getFilteredRouteData($conn, $filter_type, $search_query, $year_filter, $route_filter);
$is_filtered = !empty($filter_type) || !empty($search_query) || !empty($year_filter) || !empty($route_filter);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Transportation Dashboard</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
  <div class="container mx-auto px-4 py-8">
    <!-- Statistics Cards Container -->
    <div class="stats-container mb-8">
      <div class="stats-header mb-6">
        <div class="flex flex-wrap justify-between items-center">
          <h2 class="text-xl font-bold text-gray-800">Transportation Statistics</h2>
          <button id="toggle-filters" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 flex items-center gap-2">
            <i class="fas fa-filter"></i>
            <?php if ($is_filtered): ?>
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-teal-100 text-teal-800">Filters Applied</span>
            <?php else: ?>
              Filters
            <?php endif; ?>
          </button>
        </div>

        <!-- Filters and Search Bar -->
        <div id="filters-container" class="filters mt-4 bg-gray-50 p-4 rounded-lg border border-gray-200 <?php echo $is_filtered ? '' : 'hidden'; ?>">
          <form method="GET" action="" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <?php foreach ($_GET as $key => $value):
              if (!in_array($key, ['filter_type', 'search_query', 'year_filter', 'route_filter', 'csrf_token'])): ?>
                <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
              <?php endif; ?>
            <?php endforeach; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Transportation Type</label>
                <select name="filter_type" class="bg-white border border-gray-300 rounded-md px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-teal-500">
                  <option value="">All Transportation</option>
                  <option value="taxi" <?php echo $filter_type === 'taxi' ? 'selected' : ''; ?>>Taxi Only</option>
                  <option value="rentacar" <?php echo $filter_type === 'rentacar' ? 'selected' : ''; ?>>Rent a Car Only</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                <select name="year_filter" class="bg-white border border-gray-300 rounded-md px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-teal-500">
                  <option value="">All Years</option>
                  <?php foreach ($available_years as $year): ?>
                    <option value="<?php echo $year; ?>" <?php echo $year_filter === $year ? 'selected' : ''; ?>>
                      <?php echo $year; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Route Number</label>
                <select name="route_filter" class="bg-white border border-gray-300 rounded-md px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-teal-500">
                  <option value="">All Routes</option>
                  <?php foreach ($route_numbers as $route): ?>
                    <option value="<?php echo $route; ?>" <?php echo $route_filter === $route ? 'selected' : ''; ?>>
                      Route <?php echo $route; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search Routes</label>
                <div class="relative">
                  <input type="text" name="search_query" value="<?php echo htmlspecialchars($search_query ?? ''); ?>"
                    placeholder="Search by route name"
                    class="bg-white border border-gray-300 rounded-md pl-10 pr-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-teal-500">
                  <span class="absolute left-3 top-2 text-gray-400">
                    <i class="fas fa-search"></i>
                  </span>
                </div>
              </div>
            </div>

            <div class="flex flex-wrap gap-2 justify-end">
              <button type="submit" class="bg-teal-600 text-white rounded-md px-4 py-2 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 flex items-center gap-2">
                <i class="fas fa-filter"></i> Apply Filters
              </button>
              <a href="<?php echo strtok($_SERVER["REQUEST_URI"], '?'); ?>" class="bg-gray-300 text-gray-700 rounded-md px-4 py-2 hover:bg-gray-400 focus:outline-none flex items-center gap-2">
                <i class="fas fa-times"></i> Clear Filters
              </a>
            </div>
          </form>
        </div>

        <!-- Filter Status Display -->
        <?php if ($is_filtered): ?>
          <div class="mt-4 bg-blue-50 border border-blue-200 rounded-md p-3 text-sm text-blue-800">
            <div class="flex items-center">
              <i class="fas fa-info-circle mr-2"></i>
              <span class="font-medium">Filtered Results:</span>
              <div class="flex flex-wrap gap-2 ml-2">
                <?php if ($filter_type): ?>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    Type: <?php echo ucfirst(htmlspecialchars($filter_type)); ?>
                  </span>
                <?php endif; ?>
                <?php if ($year_filter): ?>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    Year: <?php echo htmlspecialchars($year_filter); ?>
                  </span>
                <?php endif; ?>
                <?php if ($route_filter): ?>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    Route: <?php echo htmlspecialchars($route_filter); ?>
                  </span>
                <?php endif; ?>
                <?php if ($search_query): ?>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    Search: "<?php echo htmlspecialchars($search_query); ?>"
                  </span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-1 gap-6">
        <!-- Overall Stats Card -->
        <div class="bg-gradient-to-r from-blue-500 to-teal-500 rounded-lg shadow-lg p-6 text-white">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Overall Statistics</h3>
            <span class="rounded-full bg-white bg-opacity-30 p-2">
              <i class="fas fa-chart-line text-xl"></i>
            </span>
          </div>
          <div class="space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-white text-opacity-80">Total Routes:</span>
              <span class="text-2xl font-bold"><?php echo htmlspecialchars($transport_stats['combined']['total_routes']); ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-white text-opacity-80">Total Vehicle Types:</span>
              <span class="text-2xl font-bold"><?php echo htmlspecialchars($transport_stats['combined']['total_vehicles']); ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-white text-opacity-80">Total Price Value:</span>
              <span class="text-2xl font-bold">PKR <?php echo number_format($transport_stats['combined']['total_price'], 2); ?></span>
            </div>
          </div>
        </div>

        <!-- Taxi Stats Card -->
        <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-teal-500">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Taxi Services</h3>
            <span class="rounded-full bg-teal-100 p-2 text-teal-600">
              <i class="fas fa-taxi text-xl"></i>
            </span>
          </div>
          <div class="space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-gray-600">Total Routes:</span>
              <span class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($transport_stats['taxi']['total_routes']); ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-600">Vehicle Types:</span>
              <span class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($transport_stats['taxi']['total_vehicles']); ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-600">Total Routes by Vehicle:</span>
              <span class="text-sm font-medium text-gray-800">
                <span class="inline-block px-2 mr-1 bg-teal-100 text-teal-800 rounded">Camry: <?php echo htmlspecialchars($transport_stats['taxi']['vehicle_counts']['camry']); ?></span>
                <span class="inline-block px-2 mr-1 bg-teal-100 text-teal-800 rounded">Starex: <?php echo htmlspecialchars($transport_stats['taxi']['vehicle_counts']['starex']); ?></span>
                <span class="inline-block px-2 bg-teal-100 text-teal-800 rounded">Hiace: <?php echo htmlspecialchars($transport_stats['taxi']['vehicle_counts']['hiace']); ?></span>
              </span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-600">Total Price Value:</span>
              <span class="text-xl font-bold text-teal-600">PKR <?php echo number_format($transport_stats['taxi']['total_price'], 2); ?></span>
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="text-sm text-gray-600 mb-2">Price Breakdown:</div>
            <div class="grid grid-cols-3 gap-2 text-center">
              <div class="bg-gray-100 rounded p-2">
                <div class="text-xs text-gray-500">Camry/Sonata</div>
                <div class="font-semibold text-teal-600">PKR <?php echo number_format($transport_stats['taxi']['total_camry_price'], 2); ?></div>
              </div>
              <div class="bg-gray-100 rounded p-2">
                <div class="text-xs text-gray-500">Starex/Staria</div>
                <div class="font-semibold text-teal-600">PKR <?php echo number_format($transport_stats['taxi']['total_starex_price'], 2); ?></div>
              </div>
              <div class="bg-gray-100 rounded p-2">
                <div class="text-xs text-gray-500">Hiace</div>
                <div class="font-semibold text-teal-600">PKR <?php echo number_format($transport_stats['taxi']['total_hiace_price'], 2); ?></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Rent a Car Stats Card -->
        <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-blue-500">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Rent a Car Services</h3>
            <span class="rounded-full bg-blue-100 p-2 text-blue-600">
              <i class="fas fa-shuttle-van text-xl"></i>
            </span>
          </div>
          <div class="space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-gray-600">Total Routes:</span>
              <span class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($transport_stats['rentacar']['total_routes']); ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-600">Vehicle Types:</span>
              <span class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($transport_stats['rentacar']['total_vehicles']); ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-600">Total Routes by Vehicle:</span>
              <span class="text-sm font-medium text-gray-800">
                <span class="inline-block px-2 mr-1 bg-blue-100 text-blue-800 rounded">GMC 16-19: <?php echo htmlspecialchars($transport_stats['rentacar']['vehicle_counts']['gmc_16_19']); ?></span>
                <span class="inline-block px-2 mr-1 bg-blue-100 text-blue-800 rounded">GMC 22-23: <?php echo htmlspecialchars($transport_stats['rentacar']['vehicle_counts']['gmc_22_23']); ?></span>
                <span class="inline-block px-2 bg-blue-100 text-blue-800 rounded">Coaster: <?php echo htmlspecialchars($transport_stats['rentacar']['vehicle_counts']['coaster']); ?></span>
              </span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-600">Total Price Value:</span>
              <span class="text-xl font-bold text-blue-600">PKR <?php echo number_format($transport_stats['rentacar']['total_price'], 2); ?></span>
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="text-sm text-gray-600 mb-2">Price Breakdown:</div>
            <div class="grid grid-cols-3 gap-2 text-center">
              <div class="bg-gray-100 rounded p-2">
                <div class="text-xs text-gray-500">GMC 16-19</div>
                <div class="font-semibold text-blue-600">PKR <?php echo number_format($transport_stats['rentacar']['total_gmc_16_19_price'], 2); ?></div>
              </div>
              <div class="bg-gray-100 rounded p-2">
                <div class="text-xs text-gray-500">GMC 22-23</div>
                <div class="font-semibold text-blue-600">PKR <?php echo number_format($transport_stats['rentacar']['total_gmc_22_23_price'], 2); ?></div>
              </div>
              <div class="bg-gray-100 rounded p-2">
                <div class="text-xs text-gray-500">Coaster</div>
                <div class="font-semibold text-blue-600">PKR <?php echo number_format($transport_stats['rentacar']['total_coaster_price'], 2); ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Route Data Tables Section -->
      <div class="mt-10">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Route Data</h3>

        <!-- Taxi Routes Table -->
        <?php if (!$filter_type || $filter_type === 'taxi'): ?>
          <div class="mb-8">
            <div class="flex items-center mb-3">
              <h4 class="text-md font-semibold text-gray-800">
                <i class="fas fa-taxi mr-2 text-teal-500"></i> Taxi Routes
              </h4>
              <span class="ml-2 px-3 py-1 bg-teal-100 text-teal-800 text-xs rounded-full">
                <?php echo count($filtered_routes['taxi']); ?> routes
              </span>
            </div>

            <div class="overflow-x-auto bg-white rounded-lg shadow-md">
              <?php if (count($filtered_routes['taxi']) > 0): ?>
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route #</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route Name</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Camry/Sonata</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Starex/Staria</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hiace</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($filtered_routes['taxi'] as $route): ?>
                      <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($route['route_number']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($route['route_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">PKR <?php echo number_format($route['camry_sonata_price'], 2); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">PKR <?php echo number_format($route['starex_staria_price'], 2); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">PKR <?php echo number_format($route['hiace_price'], 2); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php else: ?>
                <div class="p-4 text-center text-gray-500">
                  No taxi routes found matching your filters.
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Rent a Car Routes Table -->
        <?php if (!$filter_type || $filter_type === 'rentacar'): ?>
          <div class="mb-8">
            <div class="flex items-center mb-3">
              <h4 class="text-md font-semibold text-gray-800">
                <i class="fas fa-shuttle-van mr-2 text-blue-500"></i> Rent a Car Routes
              </h4>
              <span class="ml-2 px-3 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                <?php echo count($filtered_routes['rentacar']); ?> routes
              </span>
            </div>

            <div class="overflow-x-auto bg-white rounded-lg shadow-md">
              <?php if (count($filtered_routes['rentacar']) > 0): ?>
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route #</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route Name</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">GMC 16-19</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">GMC 22-23</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coaster</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($filtered_routes['rentacar'] as $route): ?>
                      <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($route['route_number']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($route['route_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">PKR <?php echo number_format($route['gmc_16_19_price'], 2); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">PKR <?php echo number_format($route['gmc_22_23_price'], 2); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">PKR <?php echo number_format($route['coaster_price'], 2); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php else: ?>
                <div class="p-4 text-center text-gray-500">
                  No rent-a-car routes found matching your filters.
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
    document.getElementById('toggle-filters').addEventListener('click', function() {
      const filtersContainer = document.getElementById('filters-container');
      filtersContainer.classList.toggle('hidden');
    });

    // Basic client-side input validation
    document.querySelector('form').addEventListener('submit', function(e) {
      const searchInput = this.querySelector('input[name="search_query"]');
      if (searchInput.value.length > 100) {
        e.preventDefault();
        alert('Search query is too long. Maximum 100 characters allowed.');
      }
    });
  </script>
</body>

</html>