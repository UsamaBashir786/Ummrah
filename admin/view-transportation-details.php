<?php
session_name("admin_session");
session_start();
include 'connection/connection.php';

// Fetch transportation details based on the given ID
$transport_id = isset($_GET['id']) ? $_GET['id'] : '';
if ($transport_id) {
  $query = "SELECT * FROM transportation WHERE transport_id = '$transport_id'";
  $result = mysqli_query($conn, $query);
  $transport = mysqli_fetch_assoc($result);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Transportation Details</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-y-scroll">
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <h1 class="text-xl font-semibold">
          <i class="text-teal-600 fas fa-car mx-2"></i> Transportation Details
        </h1>
        <a href="view-transportation.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
          <i class="fas fa-arrow-left mr-2"></i>Back to List
        </a>
      </div>

      <div class="container mx-auto px-4 py-8">
        <div class="bg-white p-6 rounded-lg shadow-lg">
          <?php if ($transport): ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
              <div class="lg:col-span-1">
                <img src="<?php echo htmlspecialchars($transport['transport_image']); ?>" alt="Vehicle Image"
                  class="rounded-lg shadow-lg w-full object-cover">
              </div>
              <div class="lg:col-span-2">
                <h2 class="text-2xl font-semibold mb-4"><?php echo htmlspecialchars($transport['transport_name']); ?></h2>
                <div class="mb-4">
                  <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold
                      <?php echo match ($transport['category']) {
                        'luxury' => 'bg-purple-100 text-purple-800',
                        'standard' => 'bg-blue-100 text-blue-800',
                        'economy' => 'bg-green-100 text-green-800',
                        default => 'bg-gray-100 text-gray-800'
                      }; ?>">
                    <?php echo ucfirst($transport['category']); ?>
                  </span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <p class="text-gray-700"><strong>Location:</strong> <?php echo htmlspecialchars($transport['location']); ?></p>
                    <p class="text-gray-700"><strong>Seats:</strong> <?php echo htmlspecialchars($transport['seats']); ?></p>
                    <p class="text-gray-700"><strong>Available Time:</strong>
                      <?php echo date('h:i A', strtotime($transport['time_from'])); ?> -
                      <?php echo date('h:i A', strtotime($transport['time_to'])); ?>
                    </p>
                  </div>
                  <div>
                    <p class="text-gray-700"><strong>ID:</strong> <?php echo htmlspecialchars($transport['transport_id']); ?></p>
                    <p class="text-gray-700"><strong>Status:</strong> <span
                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $transport['status'] == 'available' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo ucfirst($transport['status']); ?>
                      </span>
                    </p>
                  </div>
                </div>
                <div class="mt-4">
                  <p class="text-gray-700"><strong>Details:</strong></p>
                  <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($transport['details'])); ?></p>
                </div>
              </div>
            </div>
          <?php else: ?>
            <p class="text-red-600">Transportation details not found.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</body>

</html>