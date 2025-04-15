<?php
// Function to get filtered route data
function getFilteredRouteData($conn, $filterType = null, $searchQuery = null, $yearFilter = null, $routeFilter = null)
{
  // Set default year if not provided
  if (!$yearFilter) {
    $yearFilter = 2024;
  }

  $data = [
    'taxi' => [],
    'rentacar' => []
  ];

  // Only query what we need based on filter type
  if (!$filterType || $filterType == 'taxi') {
    $taxi_query = "SELECT id, route_number, route_name, camry_sonata_price, starex_staria_price, hiace_price 
                   FROM taxi_routes WHERE year = $yearFilter";

    if ($searchQuery) {
      $search = $conn->real_escape_string($searchQuery);
      $taxi_query .= " AND route_name LIKE '%$search%'";
    }

    if ($routeFilter) {
      $routeNum = (int)$routeFilter;
      $taxi_query .= " AND route_number = $routeNum";
    }

    $taxi_query .= " ORDER BY route_number";

    $result = $conn->query($taxi_query);
    if ($result) {
      while ($row = $result->fetch_assoc()) {
        $data['taxi'][] = $row;
      }
    }
  }

  if (!$filterType || $filterType == 'rentacar') {
    $rentacar_query = "SELECT id, route_number, route_name, gmc_16_19_price, gmc_22_23_price, coaster_price 
                      FROM rentacar_routes WHERE year = $yearFilter";

    if ($searchQuery) {
      $search = $conn->real_escape_string($searchQuery);
      $rentacar_query .= " AND route_name LIKE '%$search%'";
    }

    if ($routeFilter) {
      $routeNum = (int)$routeFilter;
      $rentacar_query .= " AND route_number = $routeNum";
    }

    $rentacar_query .= " ORDER BY route_number";

    $result = $conn->query($rentacar_query);
    if ($result) {
      while ($row = $result->fetch_assoc()) {
        $data['rentacar'][] = $row;
      }
    }
  }

  return $data;
}

// Function to get transport statistics
function getTransportStats($conn, $filterType = null, $searchQuery = null, $yearFilter = null, $routeFilter = null)
{
  // Set default year if not provided
  if (!$yearFilter) {
    $yearFilter = 2024;
  }

  // Base queries for taxi and rentacar with vehicle counts
  $taxi_query = "SELECT 
                  COUNT(*) as total_routes, 
                  SUM(camry_sonata_price) as total_camry_price, 
                  SUM(starex_staria_price) as total_starex_price,
                  SUM(hiace_price) as total_hiace_price,
                  SUM(CASE WHEN camry_sonata_price > 0 THEN 1 ELSE 0 END) as camry_count,
                  SUM(CASE WHEN starex_staria_price > 0 THEN 1 ELSE 0 END) as starex_count,
                  SUM(CASE WHEN hiace_price > 0 THEN 1 ELSE 0 END) as hiace_count
                  FROM taxi_routes WHERE year = $yearFilter";

  $rentacar_query = "SELECT 
                      COUNT(*) as total_routes, 
                      SUM(gmc_16_19_price) as total_gmc_16_19_price,
                      SUM(gmc_22_23_price) as total_gmc_22_23_price,
                      SUM(coaster_price) as total_coaster_price,
                      SUM(CASE WHEN gmc_16_19_price > 0 THEN 1 ELSE 0 END) as gmc_16_19_count,
                      SUM(CASE WHEN gmc_22_23_price > 0 THEN 1 ELSE 0 END) as gmc_22_23_count,
                      SUM(CASE WHEN coaster_price > 0 THEN 1 ELSE 0 END) as coaster_count
                      FROM rentacar_routes WHERE year = $yearFilter";

  // Apply search filter if provided
  if ($searchQuery) {
    $search = $conn->real_escape_string($searchQuery);
    $taxi_query .= " AND route_name LIKE '%$search%'";
    $rentacar_query .= " AND route_name LIKE '%$search%'";
  }

  // Apply route number filter if provided
  if ($routeFilter) {
    $routeNum = (int)$routeFilter;
    $taxi_query .= " AND route_number = $routeNum";
    $rentacar_query .= " AND route_number = $routeNum";
  }

  $stats = [
    'taxi' => [
      'total_routes' => 0,
      'total_camry_price' => 0,
      'total_starex_price' => 0,
      'total_hiace_price' => 0,
      'total_vehicles' => 0, // Will be calculated dynamically
      'vehicle_counts' => [
        'camry' => 0,
        'starex' => 0,
        'hiace' => 0
      ],
      'total_price' => 0
    ],
    'rentacar' => [
      'total_routes' => 0,
      'total_gmc_16_19_price' => 0,
      'total_gmc_22_23_price' => 0,
      'total_coaster_price' => 0,
      'total_vehicles' => 0, // Will be calculated dynamically
      'vehicle_counts' => [
        'gmc_16_19' => 0,
        'gmc_22_23' => 0,
        'coaster' => 0
      ],
      'total_price' => 0
    ],
    'combined' => [
      'total_routes' => 0,
      'total_vehicles' => 0, // Will be calculated dynamically
      'total_price' => 0
    ]
  ];

  // Get taxi stats
  if (!$filterType || $filterType == 'taxi') {
    $result = $conn->query($taxi_query);
    if ($result && $row = $result->fetch_assoc()) {
      $stats['taxi']['total_routes'] = $row['total_routes'];
      $stats['taxi']['total_camry_price'] = $row['total_camry_price'] ?? 0;
      $stats['taxi']['total_starex_price'] = $row['total_starex_price'] ?? 0;
      $stats['taxi']['total_hiace_price'] = $row['total_hiace_price'] ?? 0;
      $stats['taxi']['vehicle_counts']['camry'] = $row['camry_count'] ?? 0;
      $stats['taxi']['vehicle_counts']['starex'] = $row['starex_count'] ?? 0;
      $stats['taxi']['vehicle_counts']['hiace'] = $row['hiace_count'] ?? 0;

      // Count unique vehicle types (those that have at least one route with price > 0)
      $stats['taxi']['total_vehicles'] =
        ($stats['taxi']['vehicle_counts']['camry'] > 0 ? 1 : 0) +
        ($stats['taxi']['vehicle_counts']['starex'] > 0 ? 1 : 0) +
        ($stats['taxi']['vehicle_counts']['hiace'] > 0 ? 1 : 0);

      $stats['taxi']['total_price'] = $stats['taxi']['total_camry_price'] +
        $stats['taxi']['total_starex_price'] +
        $stats['taxi']['total_hiace_price'];
    }
  }

  // Get rentacar stats
  if (!$filterType || $filterType == 'rentacar') {
    $result = $conn->query($rentacar_query);
    if ($result && $row = $result->fetch_assoc()) {
      $stats['rentacar']['total_routes'] = $row['total_routes'];
      $stats['rentacar']['total_gmc_16_19_price'] = $row['total_gmc_16_19_price'] ?? 0;
      $stats['rentacar']['total_gmc_22_23_price'] = $row['total_gmc_22_23_price'] ?? 0;
      $stats['rentacar']['total_coaster_price'] = $row['total_coaster_price'] ?? 0;
      $stats['rentacar']['vehicle_counts']['gmc_16_19'] = $row['gmc_16_19_count'] ?? 0;
      $stats['rentacar']['vehicle_counts']['gmc_22_23'] = $row['gmc_22_23_count'] ?? 0;
      $stats['rentacar']['vehicle_counts']['coaster'] = $row['coaster_count'] ?? 0;

      // Count unique vehicle types (those that have at least one route with price > 0)
      $stats['rentacar']['total_vehicles'] =
        ($stats['rentacar']['vehicle_counts']['gmc_16_19'] > 0 ? 1 : 0) +
        ($stats['rentacar']['vehicle_counts']['gmc_22_23'] > 0 ? 1 : 0) +
        ($stats['rentacar']['vehicle_counts']['coaster'] > 0 ? 1 : 0);

      $stats['rentacar']['total_price'] = $stats['rentacar']['total_gmc_16_19_price'] +
        $stats['rentacar']['total_gmc_22_23_price'] +
        $stats['rentacar']['total_coaster_price'];
    }
  }

  // Calculate combined stats
  $stats['combined']['total_routes'] = $stats['taxi']['total_routes'] + $stats['rentacar']['total_routes'];
  $stats['combined']['total_price'] = $stats['taxi']['total_price'] + $stats['rentacar']['total_price'];
  $stats['combined']['total_vehicles'] = $stats['taxi']['total_vehicles'] + $stats['rentacar']['total_vehicles'];

  return $stats;
}

// Function to get available years
function getAvailableYears($conn)
{
  $years = [];

  // Get years from taxi routes
  $taxi_years_query = "SELECT DISTINCT year FROM taxi_routes ORDER BY year DESC";
  $result = $conn->query($taxi_years_query);
  if ($result) {
    while ($row = $result->fetch_assoc()) {
      $years[] = $row['year'];
    }
  }

  // Get years from rentacar routes
  $rentacar_years_query = "SELECT DISTINCT year FROM rentacar_routes ORDER BY year DESC";
  $result = $conn->query($rentacar_years_query);
  if ($result) {
    while ($row = $result->fetch_assoc()) {
      if (!in_array($row['year'], $years)) {
        $years[] = $row['year'];
      }
    }
  }

  sort($years);
  return $years;
}

// Function to get route numbers
function getRouteNumbers($conn)
{
  $routes = [];

  // Get route numbers from taxi routes
  $taxi_routes_query = "SELECT DISTINCT route_number FROM taxi_routes ORDER BY route_number";
  $result = $conn->query($taxi_routes_query);
  if ($result) {
    while ($row = $result->fetch_assoc()) {
      $routes[] = $row['route_number'];
    }
  }

  // Get route numbers from rentacar routes
  $rentacar_routes_query = "SELECT DISTINCT route_number FROM rentacar_routes ORDER BY route_number";
  $result = $conn->query($rentacar_routes_query);
  if ($result) {
    while ($row = $result->fetch_assoc()) {
      if (!in_array($row['route_number'], $routes)) {
        $routes[] = $row['route_number'];
      }
    }
  }

  sort($routes);
  return $routes;
}

// Get filter values
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : null;
$search_query = isset($_GET['search_query']) ? $_GET['search_query'] : null;
$year_filter = isset($_GET['year_filter']) ? $_GET['year_filter'] : null;
$route_filter = isset($_GET['route_filter']) ? $_GET['route_filter'] : null;

// Get available years and routes for filter dropdowns
$available_years = getAvailableYears($conn);
$route_numbers = getRouteNumbers($conn);

// Get stats based on filters
$transport_stats = getTransportStats($conn, $filter_type, $search_query, $year_filter, $route_filter);

// Get filtered route data for display in tables
$filtered_routes = getFilteredRouteData($conn, $filter_type, $search_query, $year_filter, $route_filter);

// Set filter status
$is_filtered = ($filter_type || $search_query || $year_filter || $route_filter);
?>

<!-- Statistics Cards Container -->
<div class="stats-container mb-8">
  <div class="stats-header mb-6">
    <div class="flex flex-wrap justify-between items-center">
      <h2 class="text-xl font-bold text-gray-800">Transportation Statistics</h2>

      <!-- Filter Toggle Button -->
      <button id="toggle-filters" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 flex items-center gap-2">
        <i class="fas fa-filter"></i>
        <?php echo $is_filtered ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-teal-100 text-teal-800">Filters Applied</span>' : 'Filters'; ?>
      </button>
    </div>

    <!-- Enhanced Filters and Search Bar -->
    <div id="filters-container" class="filters mt-4 bg-gray-50 p-4 rounded-lg border border-gray-200 <?php echo $is_filtered ? '' : 'hidden'; ?>">
      <form method="GET" action="" class="space-y-4">
        <!-- Preserve any existing query parameters that shouldn't be affected by the form -->
        <?php foreach ($_GET as $key => $value) {
          if (!in_array($key, ['filter_type', 'search_query', 'year_filter', 'route_filter'])) {
            echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
          }
        } ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Transportation Type Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Transportation Type</label>
            <select name="filter_type" class="bg-white border border-gray-300 rounded-md px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-teal-500">
              <option value="">All Transportation</option>
              <option value="taxi" <?php echo ($filter_type == 'taxi') ? 'selected' : ''; ?>>Taxi Only</option>
              <option value="rentacar" <?php echo ($filter_type == 'rentacar') ? 'selected' : ''; ?>>Rent a Car Only</option>
            </select>
          </div>

          <!-- Year Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
            <select name="year_filter" class="bg-white border border-gray-300 rounded-md px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-teal-500">
              <option value="">All Years</option>
              <?php foreach ($available_years as $year): ?>
                <option value="<?php echo $year; ?>" <?php echo ($year_filter == $year) ? 'selected' : ''; ?>>
                  <?php echo $year; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Route Number Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Route Number</label>
            <select name="route_filter" class="bg-white border border-gray-300 rounded-md px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-teal-500">
              <option value="">All Routes</option>
              <?php foreach ($route_numbers as $route): ?>
                <option value="<?php echo $route; ?>" <?php echo ($route_filter == $route) ? 'selected' : ''; ?>>
                  Route <?php echo $route; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Search Query -->
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

        <!-- Filter Buttons -->
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
                Type: <?php echo ucfirst($filter_type); ?>
              </span>
            <?php endif; ?>

            <?php if ($year_filter): ?>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                Year: <?php echo $year_filter; ?>
              </span>
            <?php endif; ?>

            <?php if ($route_filter): ?>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                Route: <?php echo $route_filter; ?>
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
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
          <span class="text-2xl font-bold"><?php echo $transport_stats['combined']['total_routes']; ?></span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-white text-opacity-80">Total Vehicle Types:</span>
          <span class="text-2xl font-bold"><?php echo $transport_stats['combined']['total_vehicles']; ?></span>
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
          <span class="text-xl font-bold text-gray-800"><?php echo $transport_stats['taxi']['total_routes']; ?></span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-gray-600">Vehicle Types:</span>
          <span class="text-xl font-bold text-gray-800"><?php echo $transport_stats['taxi']['total_vehicles']; ?></span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-gray-600">Total Routes by Vehicle:</span>
          <span class="text-sm font-medium text-gray-800">
            <span class="inline-block px-2 mr-1 bg-teal-100 text-teal-800 rounded">Camry: <?php echo $transport_stats['taxi']['vehicle_counts']['camry']; ?></span>
            <span class="inline-block px-2 mr-1 bg-teal-100 text-teal-800 rounded">Starex: <?php echo $transport_stats['taxi']['vehicle_counts']['starex']; ?></span>
            <span class="inline-block px-2 bg-teal-100 text-teal-800 rounded">Hiace: <?php echo $transport_stats['taxi']['vehicle_counts']['hiace']; ?></span>
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
          <span class="text-xl font-bold text-gray-800"><?php echo $transport_stats['rentacar']['total_routes']; ?></span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-gray-600">Vehicle Types:</span>
          <span class="text-xl font-bold text-gray-800"><?php echo $transport_stats['rentacar']['total_vehicles']; ?></span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-gray-600">Total Routes by Vehicle:</span>
          <span class="text-sm font-medium text-gray-800">
            <span class="inline-block px-2 mr-1 bg-blue-100 text-blue-800 rounded">GMC 16-19: <?php echo $transport_stats['rentacar']['vehicle_counts']['gmc_16_19']; ?></span>
            <span class="inline-block px-2 mr-1 bg-blue-100 text-blue-800 rounded">GMC 22-23: <?php echo $transport_stats['rentacar']['vehicle_counts']['gmc_22_23']; ?></span>
            <span class="inline-block px-2 bg-blue-100 text-blue-800 rounded">Coaster: <?php echo $transport_stats['rentacar']['vehicle_counts']['coaster']; ?></span>
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

    <!-- Taxi Routes Table - Only displayed if filter type is not 'rentacar' -->
    <?php if (!$filter_type || $filter_type == 'taxi'): ?>
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
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $route['route_number']; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $route['route_name']; ?></td>
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

    <!-- Rent a Car Routes Table - Only displayed if filter type is not 'taxi' -->
    <?php if (!$filter_type || $filter_type == 'rentacar'): ?>
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
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $route['route_number']; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $route['route_name']; ?></td>
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

<!-- JavaScript for Toggle Filters -->
<script>
  document.getElementById('toggle-filters').addEventListener('click', function() {
    const filtersContainer = document.getElementById('filters-container');
    filtersContainer.classList.toggle('hidden');
  });
</script>