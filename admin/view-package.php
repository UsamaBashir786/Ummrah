<?php
include 'includes/db-config.php';

// Initialize filters
$packageTypeFilter = $_GET['package_type'] ?? '';
$minPriceFilter = $_GET['min_price'] ?? '';
$maxPriceFilter = $_GET['max_price'] ?? '';

// Build the query dynamically based on filters
$sql = "SELECT id, title, description, package_type, departure_city, departure_date, departure_time, arrival_city, return_date, return_time, price, package_image FROM packages WHERE 1=1";

if ($packageTypeFilter) {
  $sql .= " AND package_type = :package_type";
}
if ($minPriceFilter !== '') {
  $sql .= " AND price >= :min_price";
}
if ($maxPriceFilter !== '') {
  $sql .= " AND price <= :max_price";
}

$stmt = $pdo->prepare($sql);

// Bind parameters
if ($packageTypeFilter) {
  $stmt->bindValue(':package_type', $packageTypeFilter);
}
if ($minPriceFilter !== '') {
  $stmt->bindValue(':min_price', $minPriceFilter, PDO::PARAM_INT);
}
if ($maxPriceFilter !== '') {
  $stmt->bindValue(':max_price', $maxPriceFilter, PDO::PARAM_INT);
}

// Execute the query and fetch results
$stmt->execute();
$packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate the total price without discounts
$totalPrice = 0;
foreach ($packages as $package) {
  $totalPrice += $package['price'];
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
</head>

<body class="bg-gray-100">
  <div class="flex h-screen">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main flex-1 flex flex-col">
      <div class="bg-white shadow-md py-4 px-4 sm:px-6 flex justify-between items-center">
        <!-- Title -->
        <h1 class="text-lg sm:text-xl font-semibold">
          <i class="text-teal-600 fas fa-box mx-2"></i> Umrah Packages
        </h1>

        <!-- Add Package Button -->
        <a href="add-packages.php" class="flex items-center text-gray-700 hover:text-gray-900">
          <i class="fas fa-plus mr-2"></i> Add Package
        </a>
      </div>

      <div class="overflow-auto container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="mx-auto bg-white p-4 sm:p-8 rounded-lg shadow-lg">
          <!-- Filter Form -->
          <form method="GET" class="p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center border-b space-y-4 sm:space-y-0">
            <div class="flex flex-wrap gap-2">
              <select name="package_type" class="border rounded-md px-3 py-2" onchange="this.form.submit()">
                <option value="">All Package Types</option>
                <option value="Economy" <?= $packageTypeFilter === 'Economy' ? 'selected' : '' ?>>Economy</option>
                <option value="Standard" <?= $packageTypeFilter === 'Standard' ? 'selected' : '' ?>>Standard</option>
                <option value="Premium" <?= $packageTypeFilter === 'Premium' ? 'selected' : '' ?>>Premium</option>
              </select>
              <input type="number" name="min_price" placeholder="Min Price" value="<?= htmlspecialchars($minPriceFilter) ?>" class="border rounded-md px-3 py-2" onchange="this.form.submit()">
              <input type="number" name="max_price" placeholder="Max Price" value="<?= htmlspecialchars($maxPriceFilter) ?>" class="border rounded-md px-3 py-2" onchange="this.form.submit()">
            </div>
            <div class="text-right">
              <p class="text-lg font-semibold text-gray-900">Total Price: <span class="text-teal-600">$<?= number_format($totalPrice, 2) ?></span></p>
            </div>
          </form>

          <!-- Table Content -->
          <div class="overflow-x-auto" id="packageTableContainer">
            <table class="min-w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Package Info</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Package Type</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Departure & Arrival City</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
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