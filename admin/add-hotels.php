<?php

$host = 'localhost';
$dbname = 'ummrah';
$username = 'root';
$password = '';

try {
  $pdo = new PDO(
    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
    $username,
    $password,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false
    ]
  );
} catch (PDOException $e) {
  die("Connection failed: " . $e->getMessage());
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Collect form data
  $hotel_name = trim($_POST['hotel_name']);
  $location = $_POST['location'];
  $room_count = (int)$_POST['room_count'];
  $price = (float)$_POST['price'];
  $rating = (int)$_POST['rating'];
  $description = trim($_POST['description']);
  $amenities = isset($_POST['amenities']) ? json_encode($_POST['amenities']) : json_encode([]);

  // Generate room IDs based on room count
  $room_ids = [];
  for ($i = 1; $i <= $room_count; $i++) {
    $room_ids[] = "r" . $i;
  }
  $room_ids_json = json_encode($room_ids);

  try {
    // Start transaction
    $pdo->beginTransaction();

    // Insert hotel data
    $stmt = $pdo->prepare("
            INSERT INTO hotels (hotel_name, location, room_count, price_per_night, rating, description, amenities, room_ids)
            VALUES (:hotel_name, :location, :room_count, :price, :rating, :description, :amenities, :room_ids)
        ");

    $stmt->execute([
      'hotel_name' => $hotel_name,
      'location' => $location,
      'room_count' => $room_count,
      'price' => $price,
      'rating' => $rating,
      'description' => $description,
      'amenities' => $amenities,
      'room_ids' => $room_ids_json
    ]);

    $hotel_id = $pdo->lastInsertId();

    // Handle image uploads
    if (!empty($_FILES['hotel_images']['name'][0])) {
      $upload_dir = 'uploads/hotels/';

      // Create directory if it doesn't exist
      if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
      }

      $stmt = $pdo->prepare("
                INSERT INTO hotel_images (hotel_id, image_path)
                VALUES (:hotel_id, :image_path)
            ");

      foreach ($_FILES['hotel_images']['tmp_name'] as $key => $tmp_name) {
        $file_name = $_FILES['hotel_images']['name'][$key];
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $new_file_name = uniqid() . '.' . $file_ext;
        $destination = $upload_dir . $new_file_name;

        if (move_uploaded_file($tmp_name, $destination)) {
          $stmt->execute([
            'hotel_id' => $hotel_id,
            'image_path' => $destination
          ]);
        }
      }
    }

    // Commit transaction
    $pdo->commit();

    // Redirect with success message
    header('Location: view-hotels.php?success=1');
    exit;
  } catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    header('Location: add-hotel.php?error=1');
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
          <i class="text-teal-600 fas fa-hotel mx-2"></i> Add New Hotel
        </h1>
      </div>

      <!-- Form Container -->
      <div class="overflow-auto container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto bg-white p-8 rounded-lg shadow-lg">
          <div class="mb-6">
            <h2 class="text-2xl font-bold text-teal-600">
              <i class="fas fa-plus-circle mr-2"></i>New Hotel Information
            </h2>
            <p class="text-gray-600 mt-2">Add a new hotel to your inventory</p>
          </div>

          <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
            <!-- Hotel Image Upload -->
            <div class="mb-6">
              <label class="block text-gray-700 font-semibold mb-2">Hotel Images *</label>
              <div class="flex items-center justify-center w-full">
                <label class="flex flex-col w-full h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 relative">
                  <div id="upload-area" class="flex flex-col items-center justify-center pt-7">
                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                    <p class="text-sm text-gray-500">Click to upload or drag and drop</p>
                    <p class="text-xs text-gray-500">PNG, JPG up to 10MB</p>
                  </div>
                  <input type="file" id="hotel_images" class="hidden" multiple accept="image/jpeg,image/png" name="hotel_images[]">
                </label>
              </div>
              <!-- File validation error -->
              <div id="file-error" class="text-red-500 text-xs mt-1 hidden"></div>
              <!-- Selected files list -->
              <div id="file-list" class="mt-3 space-y-2 hidden">
                <p class="text-sm font-medium text-gray-700">Selected Files:</p>
                <ul id="selected-files" class="text-xs text-gray-500 space-y-1"></ul>
                <p id="size-warning" class="text-xs text-red-500 hidden"></p>
              </div>
            </div>

            <script>
              document.getElementById('hotel_images').addEventListener('change', function(e) {
                const MAX_SIZE = 10 * 1024 * 1024; // 10MB
                const fileList = document.getElementById('selected-files');
                const uploadArea = document.getElementById('upload-area');
                const fileError = document.getElementById('file-error');
                const sizeWarning = document.getElementById('size-warning');
                const filesContainer = document.getElementById('file-list');

                fileList.innerHTML = '';
                fileError.classList.add('hidden');
                sizeWarning.classList.add('hidden');

                if (this.files.length > 0) {
                  filesContainer.classList.remove('hidden');
                  uploadArea.classList.add('hidden');

                  let totalSize = 0;
                  let hasInvalidFiles = false;

                  Array.from(this.files).forEach((file, index) => {
                    // Validate file type
                    if (!['image/jpeg', 'image/png'].includes(file.type)) {
                      fileError.textContent = `Invalid file type: ${file.name}. Only JPG/PNG allowed.`;
                      fileError.classList.remove('hidden');
                      hasInvalidFiles = true;
                      return;
                    }

                    // Validate file size
                    if (file.size > MAX_SIZE) {
                      fileError.textContent = `File too large: ${file.name} (${(file.size/1024/1024).toFixed(1)}MB). Max 10MB allowed.`;
                      fileError.classList.remove('hidden');
                      hasInvalidFiles = true;
                      return;
                    }

                    totalSize += file.size;

                    // Add to file list
                    const listItem = document.createElement('li');
                    listItem.className = 'flex items-center justify-between';
                    listItem.innerHTML = `
        <span class="truncate w-40">${index + 1}. ${file.name}</span>
        <span class="text-gray-400">${(file.size/1024/1024).toFixed(1)}MB</span>
        <button type="button" onclick="removeFile(${index})" class="text-red-400 hover:text-red-600">
          <i class="fas fa-times"></i>
        </button>
      `;
                    fileList.appendChild(listItem);
                  });

                  // Show total size warning if over 30MB combined
                  if (totalSize > 30 * 1024 * 1024) {
                    sizeWarning.textContent = `Total size: ${(totalSize/1024/1024).toFixed(1)}MB (recommended under 30MB)`;
                    sizeWarning.classList.remove('hidden');
                  }

                  if (hasInvalidFiles) {
                    this.value = ''; // Clear invalid files
                  }
                } else {
                  filesContainer.classList.add('hidden');
                  uploadArea.classList.remove('hidden');
                }
              });

              function removeFile(index) {
                const input = document.getElementById('hotel_images');
                const files = Array.from(input.files);
                files.splice(index, 1);

                // Create new DataTransfer to update files
                const dataTransfer = new DataTransfer();
                files.forEach(file => dataTransfer.items.add(file));
                input.files = dataTransfer.files;

                // Trigger change event to update UI
                const event = new Event('change');
                input.dispatchEvent(event);
              }

              // Drag and drop functionality
              const dropArea = document.querySelector('label[for="hotel_images"]');
              ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, preventDefaults, false);
              });

              function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
              }

              ['dragenter', 'dragover'].forEach(eventName => {
                dropArea.addEventListener(eventName, highlight, false);
              });

              ['dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, unhighlight, false);
              });

              function highlight() {
                dropArea.classList.add('border-teal-500', 'bg-teal-50');
              }

              function unhighlight() {
                dropArea.classList.remove('border-teal-500', 'bg-teal-50');
              }

              dropArea.addEventListener('drop', handleDrop, false);

              function handleDrop(e) {
                const dt = e.dataTransfer;
                const input = document.getElementById('hotel_images');
                input.files = dt.files;
                const event = new Event('change');
                input.dispatchEvent(event);
              }
            </script>

            <!-- Hotel Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2">Hotel Name *</label>
                <input type="text" name="hotel_name" id="hotel_name"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="Enter hotel name (letters only)"
                  maxlength="25"
                  oninput="validateHotelName(this)"
                  onkeydown="preventHotelNameNumbers(event)"
                  required>
                <div id="hotel_name_error" class="text-red-500 text-xs mt-1 hidden">
                  Hotel name must contain only letters (A-Z) and be 25 characters or less
                </div>
                <div class="text-xs text-gray-500 mt-1">
                  <span id="hotel_name_counter">0</span>/25 characters
                </div>
              </div>

              <script>
                function validateHotelName(input) {
                  const errorElement = document.getElementById('hotel_name_error');
                  const counterElement = document.getElementById('hotel_name_counter');

                  // Remove any numbers or special characters (keep only letters A-Z and spaces)
                  input.value = input.value.replace(/[^A-Za-z\s]/g, '');

                  // Update character counter
                  const currentLength = input.value.length;
                  counterElement.textContent = currentLength;

                  // Enforce 25 character limit
                  if (currentLength > 25) {
                    input.value = input.value.substring(0, 25);
                    counterElement.textContent = 25;
                  }

                  // Show error if invalid characters were attempted
                  if (/[^A-Za-z\s]/.test(input.value)) {
                    errorElement.classList.remove('hidden');
                    input.setCustomValidity('Only letters allowed');
                  } else {
                    errorElement.classList.add('hidden');
                    input.setCustomValidity('');
                  }

                  input.reportValidity();
                }

                function preventHotelNameNumbers(event) {
                  // Allow: letters, backspace, delete, arrows, tab, space
                  if (/[A-Za-z\s]|Backspace|Delete|ArrowLeft|ArrowRight|ArrowUp|ArrowDown|Tab/.test(event.key)) {
                    return true;
                  }
                  event.preventDefault();
                  return false;
                }

                // Initialize counter on page load
                document.addEventListener('DOMContentLoaded', function() {
                  const hotelInput = document.getElementById('hotel_name');
                  document.getElementById('hotel_name_counter').textContent = hotelInput.value.length;
                });
              </script>
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Location</label>
                <select name="location"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                  <option value="">Select Location</option>
                  <option value="makkah">Makkah</option>
                  <option value="madinah">Madinah</option>
                </select>
              </div>
              <!-- New Room Count Field -->
            </div>
            <div class="mb-4">
              <label class="block text-gray-700 font-semibold mb-2">Number of Rooms *</label>
              <input type="number" name="room_count" id="room_count" min="1" max="10"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                placeholder="Enter number of rooms (1-10)"
                oninput="validateRoomCount(this)"
                required>
              <div id="room_count_error" class="text-red-500 text-xs mt-1 hidden">
                Please enter a number between 1 and 10
              </div>
              <p class="text-sm text-gray-500 mt-1">Room IDs (r1, r2, etc.) will be automatically generated based on this count</p>
            </div>

            <script>
              function validateRoomCount(input) {
                const errorElement = document.getElementById('room_count_error');
                const value = parseInt(input.value) || 0;

                // Validate range
                if (value < 1 || value > 10) {
                  errorElement.classList.remove('hidden');
                  input.setCustomValidity('Number must be between 1-10');

                  // Auto-correct out-of-range values
                  if (value < 1) input.value = 1;
                  if (value > 10) input.value = 10;
                } else {
                  errorElement.classList.add('hidden');
                  input.setCustomValidity('');
                }

                input.reportValidity();
              }

              // Initialize validation
              document.addEventListener('DOMContentLoaded', function() {
                const roomInput = document.getElementById('room_count');
                roomInput.addEventListener('blur', function() {
                  validateRoomCount(this);
                });
              });
            </script>


            <!-- Price and Rating -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2">Price per Night ($) *</label>
                <input type="number" name="price" id="price"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="Enter price ($1-$50,000)"
                  oninput="enforcePriceLimit(this)"
                  onkeydown="preventOverTyping(this, event)"
                  required>
                <div id="price_error" class="text-red-500 text-xs mt-1 hidden">
                  Maximum price is $50,000
                </div>
              </div>

              <script>
                function enforcePriceLimit(input) {
                  const errorElement = document.getElementById('price_error');
                  let value = input.value.replace(/[^0-9]/g, ''); // Remove non-digits

                  // Enforce maximum limit
                  if (value > 50000) {
                    value = 50000;
                    errorElement.classList.remove('hidden');
                  } else {
                    errorElement.classList.add('hidden');
                  }

                  // Enforce minimum $1
                  if (value < 1 && value !== '') {
                    value = '';
                    errorElement.textContent = "Price cannot be zero";
                    errorElement.classList.remove('hidden');
                  }

                  input.value = value === '' ? '' : parseInt(value);
                }

                function preventOverTyping(input, event) {
                  const currentValue = input.value.replace(/[^0-9]/g, '');

                  // Block typing if current value is already at max
                  if (currentValue >= 50000 &&
                    !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'].includes(event.key)) {
                    event.preventDefault();
                    document.getElementById('price_error').classList.remove('hidden');
                    return;
                  }

                  // Allow only numbers and control keys
                  if (!/[0-9]|Backspace|Delete|ArrowLeft|ArrowRight|Tab/.test(event.key)) {
                    event.preventDefault();
                  }
                }

                // Initialize
                document.addEventListener('DOMContentLoaded', function() {
                  document.getElementById('price').addEventListener('blur', function() {
                    if (this.value > 50000) {
                      this.value = 50000;
                      document.getElementById('price_error').classList.remove('hidden');
                    }
                  });
                });
              </script>
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Hotel Rating</label>
                <div class="flex items-center space-x-2">
                  <select name="rating"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    <option value="5">5 Stars</option>
                    <option value="4">4 Stars</option>
                    <option value="3">3 Stars</option>
                    <option value="2">2 Stars</option>
                    <option value="1">1 Star</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Hotel Description -->
            <div class="mb-4">
              <label class="block text-gray-700 font-semibold mb-2">Hotel Description *</label>
              <textarea name="description" id="description" rows="6"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                placeholder="Enter hotel description (200 words maximum)"
                oninput="enforceWordLimit(this)"
                onkeydown="preventExtraWords(this, event)"
                required></textarea>
              <div id="desc_error" class="text-red-500 text-xs mt-1 hidden">
                Maximum 200 words reached (backspace to edit)
              </div>
              <div class="text-xs text-gray-500 mt-1">
                <span id="word_count">0</span>/200 words
                <span id="limit_reached" class="text-red-500 font-semibold hidden"> (Limit reached)</span>
              </div>
            </div>

            <script>
              function enforceWordLimit(textarea) {
                const errorElement = document.getElementById('desc_error');
                const wordCountElement = document.getElementById('word_count');
                const limitReachedElement = document.getElementById('limit_reached');

                // Count words (including hyphenated words and contractions)
                const words = textarea.value.match(/\b[\w'-]+\b/g) || [];
                const wordCount = words.length;
                wordCountElement.textContent = wordCount;

                // Check word limit
                if (wordCount >= 200) {
                  // Trim to exactly 200 words
                  if (wordCount > 200) {
                    const trimmedText = words.slice(0, 200).join(' ');
                    textarea.value = trimmedText;
                    wordCountElement.textContent = 200;
                  }
                  errorElement.classList.remove('hidden');
                  limitReachedElement.classList.remove('hidden');
                  textarea.classList.add('border-red-300');
                } else {
                  errorElement.classList.add('hidden');
                  limitReachedElement.classList.add('hidden');
                  textarea.classList.remove('border-red-300');
                }
              }

              function preventExtraWords(textarea, event) {
                const words = textarea.value.match(/\b[\w'-]+\b/g) || [];

                // Block typing if at 200 words (allow deletions and navigation)
                if (words.length >= 200 &&
                  !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab'].includes(event.key)) {
                  event.preventDefault();
                  return;
                }

                // Allow normal typing if under limit
                return true;
              }

              // Initialize
              document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('description').addEventListener('paste', function(e) {
                  const words = this.value.match(/\b[\w'-]+\b/g) || [];
                  if (words.length >= 200) {
                    e.preventDefault();
                  }
                });
              });
            </script>

            <!-- Amenities -->
            <div>
              <label class="block text-gray-700 font-semibold mb-2">Hotel Amenities</label>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <label class="flex items-center space-x-2">
                  <input type="checkbox" name="amenities[]" value="wifi" class="text-teal-600">
                  <span>Free WiFi</span>
                </label>
                <label class="flex items-center space-x-2">
                  <input type="checkbox" name="amenities[]" value="parking" class="text-teal-600">
                  <span>Parking</span>
                </label>
                <label class="flex items-center space-x-2">
                  <input type="checkbox" name="amenities[]" value="restaurant" class="text-teal-600">
                  <span>Restaurant</span>
                </label>
                <label class="flex items-center space-x-2">
                  <input type="checkbox" name="amenities[]" value="gym" class="text-teal-600">
                  <span>Gym</span>
                </label>
              </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex gap-4">
              <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700 transition duration-200">
                <i class="fas fa-save mr-2"></i>Save Hotel
              </button>
              <button onclick="window.location.href='view-hotels.php'" type="button" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition duration-200">
                <i class="fas fa-times mr-2"></i>Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/js-links.php'; ?>

</body>

</html>