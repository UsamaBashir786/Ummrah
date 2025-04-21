<?php
// Include your database connection file
include 'includes/db-config.php';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    // Retrieve form data
    $package_type = $_POST['package_type'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $airline = $_POST['airline'];
    $flight_class = $_POST['flight_class'];
    $departure_city = $_POST['departure_city'];
    $departure_time = $_POST['departure_time'];
    $departure_date = $_POST['departure_date'];
    $arrival_city = $_POST['arrival_city'];
    $return_time = $_POST['return_time'];
    $return_date = $_POST['return_date'];
    $inclusions = implode(', ', $_POST['inclusions']); // Convert array to string
    $price = $_POST['price'];

    // Handle file upload
    $package_image = '';
    if (isset($_FILES['package_image']) && $_FILES['package_image']['error'] === UPLOAD_ERR_OK) {
      $upload_dir = 'uploads/packages/'; // Directory to store uploaded images
      if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true); // Create the directory if it doesn't exist
      }
      $package_image = $upload_dir . basename($_FILES['package_image']['name']);
      move_uploaded_file($_FILES['package_image']['tmp_name'], $package_image);
    }

    // Prepare and execute the SQL query
    $sql = "INSERT INTO packages (package_type, title, description, airline, flight_class, departure_city, departure_time, departure_date, arrival_city, return_time, return_date, inclusions, price, package_image)
            VALUES (:package_type, :title, :description, :airline, :flight_class, :departure_city, :departure_time, :departure_date, :arrival_city, :return_time, :return_date, :inclusions, :price, :package_image)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':package_type' => $package_type,
      ':title' => $title,
      ':description' => $description,
      ':airline' => $airline,
      ':flight_class' => $flight_class,
      ':departure_city' => $departure_city,
      ':departure_time' => $departure_time,
      ':departure_date' => $departure_date,
      ':arrival_city' => $arrival_city,
      ':return_time' => $return_time,
      ':return_date' => $return_date,
      ':inclusions' => $inclusions,
      ':price' => $price,
      ':package_image' => $package_image
    ]);

    // Set a success message for SweetAlert
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
              Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Package created successfully.',
                confirmButtonText: 'OK'
              }).then(() => {
                window.location.href = 'view-package.php'; // Redirect after success
              });
            });
          </script>";
  } catch (PDOException $e) {
    // Set an error message for SweetAlert
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
              Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred: " . addslashes($e->getMessage()) . "',
                confirmButtonText: 'OK'
              });
            });
          </script>";
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <!-- Include SweetAlert CSS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-100">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main flex-1 flex flex-col">
      <!-- Navbar -->
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <!-- Menu Button (Left) -->
        <button class="md:hidden text-gray-800" id="menu-btn">
          <i class="fas fa-bars"></i>
        </button>

        <!-- Title -->
        <h1 class="text-xl font-semibold">
          <i class="text-teal-600 fas fa-box mx-2"></i> Add Packages
        </h1>

        <!-- Back Button (Right) -->
        <a href="view-package.php" class="flex items-center text-gray-700 hover:text-gray-900">
          <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
      </div>


      <div class="overflow-auto container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-lg">
          <h2 class="text-2xl font-bold text-teal-700 mb-6">Create New Umrah Package</h2>
          <form action="" method="POST" enctype="multipart/form-data">
            <!-- Package Type -->
            <div class="mb-6">
              <label class="block text-gray-700 text-sm font-bold mb-2">Package Type</label>
              <select name="package_type" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500">
                <option value="single">Single Umrah Package</option>
                <option value="group">Group Umrah Package</option>
                <option value="vip">VIP Umrah Package</option>
              </select>
            </div>

            <!-- Package Title -->
            <div class="mb-6">
              <label class="block text-gray-700 text-sm font-bold mb-2">Package Title</label>
              <input
                type="text"
                id="packageTitle"
                name="title"
                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500"
                required
                oninput="validateTitle()">
              <small id="error-message" class="text-red-500"></small>
            </div>

            <script>
              function validateTitle() {
                const input = document.getElementById("packageTitle");
                const errorMessage = document.getElementById("error-message");
                const regex = /^[A-Za-z ]{0,35}$/; // Regular expression for English letters and spaces only, max 20 characters

                if (regex.test(input.value)) {
                  errorMessage.textContent = ""; // Clear error message if valid
                } else {
                  errorMessage.textContent = "Only English letters are allowed, and numbers are not permitted!";
                  input.value = input.value.replace(/[^A-Za-z ]/g, ""); // Remove invalid characters (numbers, special characters, etc.)
                }

                // Enforce maximum length of 20 characters
                if (input.value.length > 35) {
                  input.value = input.value.slice(0, 35);
                }
              }
            </script>

            <!-- Package Description -->
            <div class="mb-6">
              <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
              <textarea
                id="packageDescription"
                name="description"
                rows="3"
                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500"
                required
                oninput="validateDescription()"></textarea>
              <small id="desc-error-message" class="text-red-500"></small>
            </div>

            <script>
              function validateDescription() {
                const textarea = document.getElementById("packageDescription");
                const errorMessage = document.getElementById("desc-error-message");
                const maxWords = 200;

                // Split words and count
                const words = textarea.value.trim().split(/\s+/);

                if (words.length > maxWords) {
                  errorMessage.textContent = "Description cannot exceed 200 words! Please remove extra words.";
                  textarea.value = words.slice(0, maxWords).join(" "); // Truncate to 200 words
                } else {
                  errorMessage.textContent = ""; // Clear error message if valid
                }
              }
            </script>

            <!-- Flight Details -->
            <div class="grid grid-cols-2 gap-4 mb-6">
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Airline</label>
                <input
                  type="text"
                  name="airline"
                  class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500"
                  required
                  id="airlineInput"
                  maxlength="7"
                  placeholder="AB-000 or ABC-000">
              </div>

              <script>
                const airlineInput = document.getElementById('airlineInput');

                airlineInput.addEventListener('input', function(e) {
                  let value = e.target.value.toUpperCase(); // Automatically capitalize input
                  let parts = value.split('-'); // Split into parts before and after dash

                  // Before the dash: Allow only A-Z and limit to 3 characters
                  parts[0] = parts[0].replace(/[^A-Z]/g, '').slice(0, 3);

                  // Automatically add dash if 2 or 3 letters are typed
                  if (parts[0].length >= 2 && !value.includes('-')) {
                    value = parts[0] + '-';
                  } else {
                    value = parts[0]; // Keep only valid letters before the dash
                  }

                  // After the dash: Allow only 0-9 and limit to 3 digits
                  if (parts.length > 1) {
                    parts[1] = parts[1].replace(/[^0-9]/g, '').slice(0, 3);
                    value = parts[0] + '-' + parts[1]; // Combine both parts
                  }

                  e.target.value = value; // Update the input field value
                });
              </script>
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Flight Class</label>
                <select name="flight_class" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500">
                  <option value="economy">Economy</option>
                  <option value="business">Business</option>
                  <option value="first">First Class</option>
                </select>
              </div>
            </div>

            <!-- Location Details -->
            <div class="grid grid-cols-2 gap-4 mb-6">
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Departure City</label>
                <input
                  type="text"
                  name="departure_city"
                  class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500"
                  required
                  id="departureCityInput"
                  maxlength="15"
                  placeholder="Enter Departure City">
              </div>
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Arrival City</label>
                <input
                  type="text"
                  name="arrival_city"
                  class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500"
                  required
                  id="arrivalCityInput"
                  maxlength="15"
                  placeholder="Enter Arrival City">
              </div>
            </div>

            <script>
              // Utility function to restrict input to only letters
              function restrictToLetters(inputField) {
                inputField.addEventListener('input', function(e) {
                  // Remove any character that is not a letter (A-Z or a-z)
                  e.target.value = e.target.value.replace(/[^a-zA-Z]/g, '');
                });
              }

              // Apply the validation to both inputs
              const departureCityInput = document.getElementById('departureCityInput');
              const arrivalCityInput = document.getElementById('arrivalCityInput');

              restrictToLetters(departureCityInput);
              restrictToLetters(arrivalCityInput);
            </script>

            <!-- Departure Date & Time -->
            <div class="grid grid-cols-2 gap-4 mb-6">
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Departure Date</label>
                <input type="date" name="departure_date" id="departure_date" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500" required>
              </div>

              <script>
                const dateInput = document.getElementById('departure_date');

                // Disable manual input
                dateInput.addEventListener('keydown', function(e) {
                  e.preventDefault();
                  return false;
                });

                dateInput.addEventListener('paste', function(e) {
                  e.preventDefault();
                  return false;
                });

                // Show picker on any click
                dateInput.addEventListener('click', function() {
                  this.showPicker();
                });

                // Optional: Also show picker when the label is clicked
                document.querySelector('label[for="departure_date"]').addEventListener('click', function() {
                  dateInput.showPicker();
                });
              </script>
              <!-- Add Flatpickr CSS and JS -->
              <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
              <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

              <!-- Departure Time Input -->
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Departure Time</label>
                <input
                  type="text"
                  id="departureTime"
                  name="departure_time"
                  class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500"
                  placeholder="HH:MM"
                  required>
              </div>
              <script>
                document.getElementById('departureTime').addEventListener('input', function(e) {
                  let val = e.target.value;
                  const cursorPos = e.target.selectionStart;

                  // 1. Sanitize input (only digits and 1 colon allowed)
                  val = val.replace(/[^0-9:]/g, ''); // Remove non-digits/colons
                  val = val.replace(/^:/, ''); // Remove leading colon
                  val = val.replace(/::+/g, ':'); // Remove duplicate colons

                  // 2. Auto-insert colon after 2 digits
                  if (val.length > 2 && !val.includes(':')) {
                    val = val.substring(0, 2) + ':' + val.substring(2);
                  }

                  // 3. Split into hours and minutes
                  const parts = val.split(':');
                  let hours = parts[0] || '';
                  let minutes = parts[1] || '';

                  // 4. Auto-correct hours (00-23)
                  if (hours.length > 0) {
                    const hourNum = parseInt(hours, 10) || 0;

                    // First digit can't be >2 (so you can't type "3" first)
                    if (hours.length === 1 && hourNum > 2) {
                      hours = '0' + hourNum;
                    }
                    // Can't exceed 23 hours
                    else if (hourNum > 23) {
                      hours = '23';
                    }
                    // Pad single digit with leading zero (optional)
                    else if (hours.length === 1 && val.length > 1) {
                      hours = '0' + hours;
                    }
                  }

                  // 5. Auto-correct minutes (00-59)
                  if (minutes.length > 0) {
                    const minuteNum = parseInt(minutes, 10) || 0;

                    // First digit can't be >5 (so you can't type "6" first)
                    if (minutes.length === 1 && minuteNum > 5) {
                      minutes = '0' + minuteNum;
                    }
                    // Can't exceed 59 minutes
                    else if (minuteNum > 59) {
                      minutes = '59';
                    }
                  }

                  // 6. Rebuild the value
                  val = hours;
                  if (minutes.length > 0) val += ':' + minutes;

                  // 7. Limit to 5 characters (HH:MM)
                  if (val.length > 5) val = val.substring(0, 5);

                  // 8. Update input
                  e.target.value = val;

                  // 9. Maintain cursor position
                  setTimeout(() => {
                    let newCursorPos = cursorPos;
                    // Adjust cursor position if we inserted a colon
                    if (cursorPos === 2 && val.length === 3 && val[2] === ':') {
                      newCursorPos = 3;
                    }
                    // Don't let cursor jump before colon
                    else if (val.includes(':') && newCursorPos < 3) {
                      newCursorPos = Math.min(newCursorPos, 2);
                    }
                    e.target.setSelectionRange(newCursorPos, newCursorPos);
                  }, 0);
                });

                // Final validation when leaving the field
                document.getElementById('departureTime').addEventListener('blur', function(e) {
                  const value = e.target.value.trim();
                  const isValid = /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/.test(value);

                  if (!isValid && value !== '') {
                    alert("Please enter a valid time in HH:MM format (00:00 to 23:59)");
                    e.target.value = "";
                    e.target.focus();
                  }
                });

                // Validate on Enter key press
                document.getElementById('departureTime').addEventListener('keydown', function(e) {
                  if (e.key === 'Enter') {
                    e.target.blur(); // Triggers the blur validation
                  }
                });
              </script>
            </div>

            <!-- Return Date & Time -->
            <div class="grid grid-cols-2 gap-4 mb-6">
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Return Date</label>
                <input type="date" name="return_date" id="returnDate" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500" required>
              </div>

              <script>
                // Disable manual typing by adding a 'click' event listener
                document.getElementById('returnDate').addEventListener('click', function(e) {
                  e.target.showPicker(); // Show the calendar picker when clicked
                });

                // Disable manual typing in the input field
                document.getElementById('returnDate').addEventListener('keydown', function(e) {
                  e.preventDefault(); // Prevent typing manually
                });

                // Optional: Validation to make sure a date is selected
                document.getElementById('returnDate').addEventListener('change', function(e) {
                  const value = e.target.value;
                  if (!value) {
                    alert("⛔ Please select a valid return date.");
                  }
                });
              </script>

              <!-- Return Time Input -->
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Return Time</label>
                <input
                  type="text"
                  id="returnTime"
                  name="return_time"
                  class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500"
                  placeholder="HH:MM"
                  required>
                <p id="returnTimeError" class="text-red-500 text-xs mt-1 hidden">⛔ Invalid time format! Please use HH:MM (24-hour).</p> <!-- Error message -->
              </div>

              <script>
                const returnTimeInput = document.getElementById('returnTime');
                const returnTimeError = document.getElementById('returnTimeError');

                // Event listener for input
                returnTimeInput.addEventListener('input', function(e) {
                  let val = e.target.value;

                  // Allow only digits and colon
                  val = val.replace(/[^0-9:]/g, '');
                  if (val.length > 5) val = val.slice(0, 5);

                  // Auto format like 1230 → 12:30 (only when the user has typed enough digits)
                  if (/^\d{3,4}$/.test(val.replace(':', ''))) {
                    const clean = val.replace(':', '');
                    val = clean.slice(0, 2) + ':' + clean.slice(2);
                  }

                  // Ensure hours are between 00 and 23 (24-hour format)
                  let [hours, minutes] = val.split(':');
                  if (hours && parseInt(hours) > 23) {
                    hours = '23'; // Limit hours to 23 if greater
                    val = `${hours}:${minutes || ''}`;
                  }

                  // Ensure minutes are between 00 and 59
                  if (minutes && parseInt(minutes) > 59) {
                    minutes = '59'; // Limit minutes to 59 if greater
                    val = `${hours}:${minutes}`;
                  }

                  e.target.value = val;

                  // Validate time input
                  validateReturnTimeInput(val);
                });

                // Function to validate time format for Return Time
                function validateReturnTimeInput(value) {
                  const isValid = /^([01]\d|2[0-3]):[0-5]\d$/.test(value);

                  if (value && !isValid) {
                    returnTimeError.classList.remove('hidden'); // Show error message
                    returnTimeInput.classList.add('border-red-500'); // Red border on invalid input
                  } else {
                    returnTimeError.classList.add('hidden'); // Hide error message
                    returnTimeInput.classList.remove('border-red-500'); // Remove red border
                  }
                }
              </script>




            </div>

            <!-- Package Inclusions -->
            <div class="mb-6">
              <label class="block text-gray-700 text-sm font-bold mb-2">Package Inclusions</label>
              <div class="space-y-2">
                <label class="flex items-center">
                  <input type="checkbox" name="inclusions[]" value="flight" class="mr-2">
                  Flight
                </label>
                <label class="flex items-center">
                  <input type="checkbox" name="inclusions[]" value="hotel" class="mr-2">
                  Hotel
                </label>
                <label class="flex items-center">
                  <input type="checkbox" name="inclusions[]" value="transport" class="mr-2">
                  Transport
                </label>
                <label class="flex items-center">
                  <input type="checkbox" name="inclusions[]" value="guide" class="mr-2">
                  Guide
                </label>
                <label class="flex items-center">
                  <input type="checkbox" name="inclusions[]" value="vip_services" class="mr-2">
                  VIP Services
                </label>
              </div>
            </div>
            <!-- Price -->
            <div class="mb-6">
              <label class="block text-gray-700 text-sm font-bold mb-2">Price pkr</label>
              <input type="text" name="price" id="price" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500" required>
              <p id="priceError" class="text-red-500 text-xs mt-1 hidden">⛔ Invalid price! Please enter a valid number (e.g., 100.99).</p> <!-- Error message -->
            </div>

            <script>
              const priceInput = document.getElementById('price');
              const priceError = document.getElementById('priceError');
              const maxPrice = 500000; // Maximum allowed price (5 lac)

              // Flag to track whether the input is valid
              let isInputValid = true;

              // Event listener for price input field to allow only numeric and decimal values
              priceInput.addEventListener('input', function(e) {
                let val = e.target.value;

                // Allow only digits and one decimal point
                val = val.replace(/[^0-9.]/g, ''); // Remove any non-numeric and non-dot characters

                // Prevent more than one decimal point
                const parts = val.split('.');
                if (parts.length > 2) {
                  val = parts[0] + '.' + parts[1].slice(0, 2); // Keep only two digits after the decimal point
                }

                // Restrict value to a maximum of 5 lac (500,000)
                if (parseFloat(val) > maxPrice) {
                  val = maxPrice.toString();
                }

                e.target.value = val;

                // Validate the input
                validatePrice(val);
              });

              // Optional: Validation for price to make sure it's not empty or invalid
              priceInput.addEventListener('blur', function(e) {
                const value = e.target.value;
                validatePrice(value);
              });

              // Function to validate the price format
              function validatePrice(value) {
                // Updated regex to validate prices up to 500,000 with up to 2 decimal places
                const isValid = /^([1-9]\d{0,5})(?:\.\d{1,2})?$/.test(value) || /^0(\.\d{1,2})?$/.test(value);

                // Check if value exceeds the max allowed price
                if (parseFloat(value) > maxPrice) {
                  priceError.classList.remove('hidden'); // Show the error message
                  priceInput.classList.add('border-red-500'); // Add red border
                  isInputValid = false; // Mark as invalid input
                } else if (value && !isValid) {
                  priceError.classList.remove('hidden'); // Show the error message
                  priceInput.classList.add('border-red-500'); // Add red border
                  isInputValid = false; // Mark as invalid input
                } else {
                  priceError.classList.add('hidden'); // Hide the error message
                  priceInput.classList.remove('border-red-500'); // Remove red border
                  isInputValid = true; // Mark as valid input
                }
              }

              // Allow backspace and delete, but prevent typing when invalid
              priceInput.addEventListener('keydown', function(e) {
                if (!isInputValid && e.key !== 'Backspace' && e.key !== 'Delete') {
                  e.preventDefault(); // Prevent further typing if the input is invalid
                }
              });
            </script>


            <!-- Package Image -->
            <div class="mb-6">
              <label class="block text-gray-700 text-sm font-bold mb-2">Package Image</label>
              <input type="file" name="package_image" id="package_image" accept="image/*" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-teal-500" required>
              <p id="imageError" class="text-red-500 text-xs mt-1 hidden">⛔ File size must be 2MB or less.</p> <!-- Error message -->
            </div>

            <script>
              const imageInput = document.getElementById('package_image');
              const imageError = document.getElementById('imageError');

              // Add event listener for file input
              imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0]; // Get the selected file
                if (file) {
                  const fileSize = file.size / 1024 / 1024; // Convert size to MB

                  // Check if file size exceeds 2MB
                  if (fileSize > 2) {
                    imageError.classList.remove('hidden'); // Show error message
                    imageInput.value = ''; // Clear the input if file is too large
                  } else {
                    imageError.classList.add('hidden'); // Hide error message if valid file
                  }
                }
              });
            </script>


            <!-- Submit Button -->
            <div class="flex justify-end">
              <button type="submit" class="bg-teal-600 text-white px-6 py-3 rounded-lg hover:bg-teal-500 transition-all duration-300">
                Create Package
              </button>
            </div>
          </form>


        </div>
      </div>

      <?php include 'includes/js-links.php'; ?>
    </div>
  </div>
</body>

</html>