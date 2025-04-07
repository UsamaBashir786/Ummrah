<?php
session_name("admin_session");
session_start();
include 'includes/db-config.php';

// Get package ID from URL
$package_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch package details from the database
$sql = "SELECT * FROM packages WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $package_id]);
$package = $stmt->fetch(PDO::FETCH_ASSOC);

// If package not found, redirect to the package list page
if (!$package) {
  header('Location: view-package.php');
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Package Details</title>
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
          <i class="text-teal-600 fas fa-box mx-2"></i> Package Details
        </h1>
        <a href="view-package.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
          <i class="fas fa-arrow-left mr-2"></i>Back to List
        </a>
      </div>

      <div class="container mx-auto px-4 py-8">
        <div class="bg-white p-6 rounded-lg shadow-lg">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1">
              <img class="rounded-lg object-cover" src="<?= htmlspecialchars($package['package_image']) ?>" alt="Package Image">
            </div>
            <div class="lg:col-span-2">
              <div class="mb-4">
                <h2 class="text-2xl font-bold mb-2"><?= htmlspecialchars($package['title']) ?></h2>
                <p class="text-gray-700"><?= nl2br(htmlspecialchars($package['description'])) ?></p>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <h3 class="text-lg font-semibold">Package Type</h3>
                  <p class="text-gray-600"><?= htmlspecialchars($package['package_type']) ?></p>
                </div>
                <div>
                  <h3 class="text-lg font-semibold">Price</h3>
                  <p class="text-gray-600">$<?= number_format($package['price'], 2) ?></p>
                </div>
                <div>
                  <h3 class="text-lg font-semibold">Airline</h3>
                  <p class="text-gray-600"><?= htmlspecialchars($package['airline']) ?></p>
                </div>
                <div>
                  <h3 class="text-lg font-semibold">Flight Class</h3>
                  <p class="text-gray-600"><?= htmlspecialchars($package['flight_class']) ?></p>
                </div>
                <div>
                  <h3 class="text-lg font-semibold">Departure City</h3>
                  <p class="text-gray-600"><?= htmlspecialchars($package['departure_city']) ?></p>
                </div>
                <div>
                  <h3 class="text-lg font-semibold">Arrival City</h3>
                  <p class="text-gray-600"><?= htmlspecialchars($package['arrival_city']) ?></p>
                </div>
                <div>
                  <h3 class="text-lg font-semibold">Departure Date</h3>
                  <p class="text-gray-600"><?= htmlspecialchars($package['departure_date']) ?></p>
                </div>
                <div>
                  <h3 class="text-lg font-semibold">Departure Time</h3>
                  <p class="text-gray-600"><?= htmlspecialchars($package['departure_time']) ?></p>
                </div>
              </div>
              <div class="mt-4">
                <button onclick="editPackage(<?= $package['id'] ?>)" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                  <i class="fas fa-edit mr-2"></i>Edit Package
                </button>
                <button onclick="deletePackage(<?= $package['id'] ?>)" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                  <i class="fas fa-trash mr-2"></i>Delete Package
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    function editPackage(id) {
      window.location.href = `edit-package.php?id=${id}`;
    }

    function deletePackage(id) {
      Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          fetch(`delete-package.php?id=${id}`, {
              method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                Swal.fire({
                  title: 'Deleted!',
                  text: 'Package has been deleted.',
                  icon: 'success'
                }).then(() => {
                  window.location.href = 'view-package.php'; // Redirect to package list page
                });
              }
            });
        }
      });
    }
  </script>
</body>

</html>