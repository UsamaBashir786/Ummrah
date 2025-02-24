<?php
require_once 'includes/db-config.php';

// Fetch hotel data
if (isset($_GET['id'])) {
  $stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = ?");
  $stmt->execute([$_GET['id']]);
  $hotel = $stmt->fetch(PDO::FETCH_ASSOC);

  // Fetch hotel images
  $stmt = $pdo->prepare("SELECT * FROM hotel_images WHERE hotel_id = ?");
  $stmt->execute([$_GET['id']]);
  $hotelImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Add this to your existing PHP section
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $pdo->beginTransaction();

    // Update hotel details
    $stmt = $pdo->prepare("
            UPDATE hotels 
            SET hotel_name = ?, location = ?, room_count = ?, 
                price_per_night = ?, rating = ?, description = ?
            WHERE id = ?
        ");

    $stmt->execute([
      $_POST['hotel_name'],
      $_POST['location'],
      $_POST['room_count'],
      $_POST['price_per_night'],
      $_POST['rating'],
      $_POST['description'],
      $_POST['hotel_id']
    ]);

    // Handle image uploads
    if (!empty($_FILES['images']['name'][0])) {
      // Delete existing images
      $stmt = $pdo->prepare("SELECT image_path FROM hotel_images WHERE hotel_id = ?");
      $stmt->execute([$_POST['hotel_id']]);
      $oldImages = $stmt->fetchAll(PDO::FETCH_COLUMN);

      // Delete physical files
      foreach ($oldImages as $oldImage) {
        if (file_exists($oldImage)) {
          unlink($oldImage);
        }
      }

      // Delete old image records
      $stmt = $pdo->prepare("DELETE FROM hotel_images WHERE hotel_id = ?");
      $stmt->execute([$_POST['hotel_id']]);

      // Upload new images
      $uploadDir = 'uploads/hotels/';
      if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
      }

      foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        $fileName = uniqid() . '_' . $_FILES['images']['name'][$key];
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($tmp_name, $filePath)) {
          $stmt = $pdo->prepare("INSERT INTO hotel_images (hotel_id, image_path) VALUES (?, ?)");
          $stmt->execute([$_POST['hotel_id'], $filePath]);
        }
      }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);
    exit;
  } catch (Exception $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-material-ui/material-ui.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-50">
  <div class="flex h-screen">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main flex-1 flex flex-col">
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <button class="md:hidden text-gray-800" id="menu-btn">
          <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-xl font-semibold">
          <i class="text-teal-600 fas fa-edit mx-2"></i> Edit Hotel
        </h1>
      </div>

      <div class="overflow-auto container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">
          <form id="editHotelForm" class="space-y-6">
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Current Images</label>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($hotelImages as $image): ?>
                  <div class="relative">
                    <img src="<?php echo htmlspecialchars($image['image_path']); ?>"
                      class="w-full h-32 object-cover rounded-lg">
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Upload New Images</label>
              <input type="file" name="images[]" multiple accept="image/*"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
              <p class="text-sm text-gray-500 mt-1">Uploading new images will replace all existing images</p>
            </div>
            <input type="hidden" name="hotel_id" value="<?php echo $hotel['id']; ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Hotel Name</label>
                <input type="text" name="hotel_name" value="<?php echo htmlspecialchars($hotel['hotel_name']); ?>"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                  required>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                <select name="location" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" required>
                  <option value="makkah" <?php echo $hotel['location'] == 'makkah' ? 'selected' : ''; ?>>Makkah</option>
                  <option value="madinah" <?php echo $hotel['location'] == 'madinah' ? 'selected' : ''; ?>>Madinah</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Room Count</label>
                <input type="number" name="room_count" value="<?php echo $hotel['room_count']; ?>"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                  required min="1">
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Price per Night</label>
                <input type="number" name="price_per_night" value="<?php echo $hotel['price_per_night']; ?>"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                  required min="0" step="0.01">
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                <select name="rating" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" required>
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo $hotel['rating'] == $i ? 'selected' : ''; ?>>
                      <?php echo $i; ?> Stars
                    </option>
                  <?php endfor; ?>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
              <textarea name="description" rows="4"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                required><?php echo htmlspecialchars($hotel['description']); ?></textarea>
            </div>

            <div class="flex justify-end space-x-4">
              <a href="view-hotels.php" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                Cancel
              </a>
              <button type="submit" class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                Save Changes
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/js-links.php'; ?>

  <script>
    document.getElementById('editHotelForm').addEventListener('submit', function(e) {
      e.preventDefault();

      const formData = new FormData(this);

      Swal.fire({
        title: 'Updating Hotel',
        text: 'Please wait while we update the hotel details...',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      fetch('edit-hotel.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Success!',
              text: 'Hotel details and images updated successfully',
              timer: 1500,
              showConfirmButton: false,
              timerProgressBar: true
            }).then(() => {
              window.location.href = 'view-hotels.php';
            });
          } else {
            throw new Error(data.error || 'Failed to update hotel');
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
    });
  </script>
</body>

</html>