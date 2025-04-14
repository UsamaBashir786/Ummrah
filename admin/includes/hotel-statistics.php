<?php
require_once 'db-config.php';

// Function to calculate total hotel value
function calculateTotalHotelValue($pdo)
{
  $query = "SELECT 
                COUNT(*) as total_hotels,
                SUM(room_count) as total_rooms,
                SUM(room_count * price_per_night) as total_value
              FROM hotels";

  $stmt = $pdo->query($query);
  return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to get hotels by location
function getHotelsByLocation($pdo)
{
  $query = "SELECT 
                location,
                COUNT(*) as count,
                SUM(room_count) as rooms,
                SUM(room_count * price_per_night) as value
              FROM hotels
              GROUP BY location";

  $stmt = $pdo->query($query);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get price distribution
function getPriceDistribution($pdo)
{
  $query = "SELECT 
                CASE 
                    WHEN price_per_night <= 100 THEN '0-100'
                    WHEN price_per_night <= 200 THEN '101-200'
                    ELSE '201+'
                END as price_range,
                COUNT(*) as count,
                SUM(room_count) as rooms
              FROM hotels
              GROUP BY price_range
              ORDER BY price_range";

  $stmt = $pdo->query($query);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all statistics
$totalStats = calculateTotalHotelValue($pdo);
$locationStats = getHotelsByLocation($pdo);
$priceStats = getPriceDistribution($pdo);
?>

<!-- Statistics Display -->
<div class="bg-white p-4 rounded-lg shadow-md mb-6">
  <h2 class="text-xl font-bold text-teal-600 mb-4">
    <i class="fas fa-chart-bar mr-2"></i>Hotels Statistics
  </h2>

  <!-- Summary Cards -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-blue-600">Total Hotels</p>
          <h3 class="text-2xl font-bold text-blue-800"><?= $totalStats['total_hotels'] ?></h3>
        </div>
        <i class="fas fa-hotel text-blue-400 text-3xl"></i>
      </div>
    </div>

    <div class="bg-green-50 p-4 rounded-lg border border-green-100">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-green-600">Total Rooms</p>
          <h3 class="text-2xl font-bold text-green-800"><?= $totalStats['total_rooms'] ?></h3>
        </div>
        <i class="fas fa-bed text-green-400 text-3xl"></i>
      </div>
    </div>

    <div class="bg-purple-50 p-4 rounded-lg border border-purple-100">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-purple-600">Total Inventory Value</p>
          <h3 class="text-2xl font-bold text-purple-800">$<?= number_format($totalStats['total_value'] ?? 0, 2) ?></h3>
        </div>
        <i class="fas fa-dollar-sign text-purple-400 text-3xl"></i>
      </div>
    </div>
  </div>

  <!-- Detailed Stats -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Location Distribution -->
    <div class="bg-white p-4 rounded-lg border border-gray-200">
      <h3 class="font-semibold text-gray-700 mb-3 flex items-center">
        <i class="fas fa-map-marker-alt text-teal-500 mr-2"></i>
        By Location
      </h3>
      <div class="space-y-3">
        <?php foreach ($locationStats as $location): ?>
          <div>
            <div class="flex justify-between text-sm mb-1">
              <span class="font-medium capitalize"><?= $location['location'] ?></span>
              <span><?= $location['count'] ?> hotels (<?= $location['rooms'] ?> rooms)</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div class="bg-teal-500 h-2 rounded-full"
                style="width: <?= ($location['count'] / $totalStats['total_hotels']) * 100 ?>%"></div>
            </div>
            <div class="text-right text-xs text-gray-500 mt-1">
              Value: $<?= number_format($location['value'], 2) ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Price Distribution -->
    <div class="bg-white p-4 rounded-lg border border-gray-200">
      <h3 class="font-semibold text-gray-700 mb-3 flex items-center">
        <i class="fas fa-tags text-teal-500 mr-2"></i>
        By Price Range
      </h3>
      <div class="space-y-3">
        <?php foreach ($priceStats as $price): ?>
          <div>
            <div class="flex justify-between text-sm mb-1">
              <span class="font-medium">$<?= $price['price_range'] ?></span>
              <span><?= $price['count'] ?> hotels (<?= $price['rooms'] ?> rooms)</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div class="bg-blue-500 h-2 rounded-full"
                style="width: <?= ($price['count'] / $totalStats['total_hotels']) * 100 ?>%"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>