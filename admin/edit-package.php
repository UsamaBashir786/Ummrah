<?php
include 'includes/db-config.php';

// Fetch package details
$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT id, package_type, title, description, airline, flight_class, departure_city, departure_time, departure_date, arrival_city, inclusions, price, package_image FROM packages WHERE id = ?");
$stmt->execute([$id]);
$package = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Handle image upload
  if (isset($_FILES['package_image']) && $_FILES['package_image']['error'] === 0) {
    $upload_dir = 'uploads/packages/';
    $file_name = time() . '_' . $_FILES['package_image']['name'];
    move_uploaded_file($_FILES['package_image']['tmp_name'], $upload_dir . $file_name);
    $package_image = $upload_dir . $file_name;
  } else {
    $package_image = $package['package_image'];
  }

  $sql = "UPDATE packages SET 
            package_type = ?,
            title = ?,
            description = ?,
            airline = ?,
            flight_class = ?,
            departure_city = ?,
            departure_time = ?,
            departure_date = ?,
            arrival_city = ?,
            inclusions = ?,
            price = ?,
            package_image = ?
            WHERE id = ?";

  $stmt = $pdo->prepare($sql);

  $result = $stmt->execute([
    $_POST['package_type'],
    $_POST['title'],
    $_POST['description'],
    $_POST['airline'],
    $_POST['flight_class'],
    $_POST['departure_city'],
    $_POST['departure_time'],
    $_POST['departure_date'],
    $_POST['arrival_city'],
    $_POST['inclusions'],
    $_POST['price'],
    $package_image,
    $id
  ]);

  if ($result) {
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Success!',
                    text: 'Package updated successfully',
                    icon: 'success',
                    confirmButtonColor: '#0D9488'
                }).then(() => {
                    window.location.href = 'view-package.php';
                });
            });
        </script>";
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Package</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-100">
  <div class="flex h-screen">
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden">
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <h1 class="text-xl font-semibold">
          <i class="fas fa-edit text-teal-600 mr-2"></i>Edit Package
        </h1>
        <a href="view-package.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
          <i class="fas fa-arrow-left mr-2"></i>Back
        </a>
      </div>

      <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md p-6">
          <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

              <!-- Package Type -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Package Type</label>
                <select name="package_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                  <option value="Economy" <?= $package['package_type'] === 'Economy' ? 'selected' : '' ?>>Economy</option>
                  <option value="Standard" <?= $package['package_type'] === 'Standard' ? 'selected' : '' ?>>Standard</option>
                  <option value="Premium" <?= $package['package_type'] === 'Premium' ? 'selected' : '' ?>>Premium</option>
                </select>
              </div>

              <!-- Title -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Title</label>
                <input type="text" name="title" value="<?= htmlspecialchars($package['title']) ?>" required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
              </div>

              <!-- Airline -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Airline</label>
                <input type="text" name="airline" value="<?= htmlspecialchars($package['airline']) ?>" required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
              </div>

              <!-- Flight Class -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Flight Class</label>
                <select name="flight_class" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                  <option value="Economy Class" <?= $package['flight_class'] === 'Economy Class' ? 'selected' : '' ?>>Economy Class</option>
                  <option value="Business Class" <?= $package['flight_class'] === 'Business Class' ? 'selected' : '' ?>>Business Class</option>
                  <option value="First Class" <?= $package['flight_class'] === 'First Class' ? 'selected' : '' ?>>First Class</option>
                  <option value="Premium Economy" <?= $package['flight_class'] === 'Premium Economy' ? 'selected' : '' ?>>Premium Economy</option>
                </select>
              </div>

              <!-- Departure City -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Departure City</label>
                <input type="text" name="departure_city" value="<?= htmlspecialchars($package['departure_city']) ?>" required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
              </div>

              <!-- Arrival City -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Arrival City</label>
                <input type="text" name="arrival_city" value="<?= htmlspecialchars($package['arrival_city']) ?>" required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
              </div>

              <!-- Departure Date -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Departure Date</label>
                <input type="date" name="departure_date" value="<?= htmlspecialchars($package['departure_date']) ?>" required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
              </div>

              <!-- Departure Time -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Departure Time</label>
                <input type="time" name="departure_time" value="<?= htmlspecialchars($package['departure_time']) ?>" required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
              </div>

              <!-- Price -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Price</label>
                <input type="number" name="price" value="<?= htmlspecialchars($package['price']) ?>" required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
              </div>

              <!-- Package Image -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Package Image</label>
                <input type="file" name="package_image" accept="image/*"
                  class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                <?php if ($package['package_image']): ?>
                  <img src="<?= htmlspecialchars($package['package_image']) ?>" alt="Current Package Image" class="mt-2 h-20 w-20 object-cover rounded">
                <?php endif; ?>
              </div>
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-medium text-gray-700">Description</label>
              <textarea name="description" rows="4" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"><?= htmlspecialchars($package['description']) ?></textarea>
            </div>

            <!-- Inclusions -->
            <div>
              <label class="block text-sm font-medium text-gray-700">Inclusions</label>
              <textarea name="inclusions" rows="4" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"><?= htmlspecialchars($package['inclusions']) ?></textarea>
            </div>

            <!-- Buttons -->
            <div class="flex justify-end space-x-3">
              <button type="button" onclick="window.location.href='packages.php'"
                class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                Cancel
              </button>
              <button type="submit"
                class="px-4 py-2 bg-teal-600 text-white rounded-md hover:bg-teal-700">
                Update Package
              </button>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>

  <script>
    if (new URLSearchParams(window.location.search).has('success')) {
      Swal.fire({
        title: 'Success!',
        text: 'Package updated successfully',
        icon: 'success',
        confirmButtonColor: '#0D9488'
      }).then(() => {
        window.location.href = 'view-package.php';
      });
    }

    document.querySelector('input[type="file"]').addEventListener('change', function(e) {
      if (e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
          document.querySelector('img').src = e.target.result;
        };
        reader.readAsDataURL(e.target.files[0]);
      }
    });
  </script>

</body>

</html>