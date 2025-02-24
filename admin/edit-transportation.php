<?php
session_start();
include 'connection/connection.php';

// Get transportation ID from URL
$transport_id = $_GET['id'];

// Fetch existing transportation data
$sql = "SELECT * FROM transportation WHERE transport_id = '$transport_id'";
$result = mysqli_query($conn, $sql);
$transport = mysqli_fetch_assoc($result);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $category = $_POST['category'];
    $transport_name = $_POST['transport_name'];
    $location = $_POST['location'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $details = $_POST['details'];
    $seats = $_POST['seats'];
    $time_from = $_POST['time_from'];
    $time_to = $_POST['time_to'];
    $status = $_POST['status'];

    $update_sql = "UPDATE transportation SET 
                    category = '$category',
                    transport_name = '$transport_name',
                    location = '$location',
                    latitude = '$latitude',
                    longitude = '$longitude',
                    details = '$details',
                    seats = '$seats',
                    time_from = '$time_from',
                    time_to = '$time_to',
                    status = '$status'";

    // Handle image upload if new image is selected
    if ($_FILES["transport_image"]["size"] > 0) {
        $target_dir = "uploads/vehicles/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_name = basename($_FILES["transport_image"]["name"]);
        $target_file = $target_dir . time() . "_" . $file_name;
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if file is an actual image
        $check = getimagesize($_FILES["transport_image"]["tmp_name"]);
        if ($check !== false && in_array($imageFileType, ["jpg", "png", "jpeg", "gif"])) {
            if (move_uploaded_file($_FILES["transport_image"]["tmp_name"], $target_file)) {
                // Delete old image if exists
                if (!empty($transport['transport_image']) && file_exists($transport['transport_image'])) {
                    unlink($transport['transport_image']);
                }
                $update_sql .= ", transport_image = '$target_file'";
            }
        }
    }

    $update_sql .= " WHERE transport_id = '$transport_id'";

    if (mysqli_query($conn, $update_sql)) {
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Transportation updated successfully!',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'view-transportation.php';
                    });
                });
            </script>";
    } else {
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Database error: " . mysqli_error($conn) . "',
                        icon: 'error',
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
                    <i class="text-teal-600 fas fa-car mx-2"></i> Edit Vehicle
                </h1>
            </div>

            <!-- Form Container -->
            <div class="overflow-auto container mx-auto px-4 py-8">
                <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-lg">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Edit Vehicle Information</h2>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <!-- Category Selection -->
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="category">
                                Vehicle Category
                            </label>
                            <select id="category" name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Category</option>
                                <option value="luxury" <?php echo ($transport['category'] == 'luxury') ? 'selected' : ''; ?>>Luxury</option>
                                <option value="standard" <?php echo ($transport['category'] == 'standard') ? 'selected' : ''; ?>>Standard</option>
                                <option value="economy" <?php echo ($transport['category'] == 'economy') ? 'selected' : ''; ?>>Economy</option>
                            </select>
                        </div>

                        <!-- Vehicle Name -->
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="transport_name">
                                Vehicle Name
                            </label>
                            <input type="text" id="transport_name" name="transport_name" value="<?php echo $transport['transport_name']; ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Location Input with Map -->
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="location">
                                Location (Search or Click on Map)
                            </label>
                            <input type="text" id="location" name="location" value="<?php echo $transport['location']; ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Map Container -->
                        <div id="map" class="w-full h-64 rounded-md border mb-6"></div>

                        <!-- Hidden Inputs for Latitude & Longitude -->
                        <input type="hidden" id="latitude" name="latitude" value="<?php echo $transport['latitude']; ?>">
                        <input type="hidden" id="longitude" name="longitude" value="<?php echo $transport['longitude']; ?>">

                        <!-- Vehicle Details -->
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="details">
                                Vehicle Details
                            </label>
                            <textarea id="details" name="details" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo $transport['details']; ?></textarea>
                        </div>

                        <!-- Available Seats -->
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="seats">
                                Available Seats
                            </label>
                            <input type="number" id="seats" name="seats" min="1" value="<?php echo $transport['seats']; ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Available Time -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="time_from">
                                    Available From
                                </label>
                                <input type="time" id="time_from" name="time_from" value="<?php echo $transport['time_from']; ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="time_to">
                                    Available To
                                </label>
                                <input type="time" id="time_to" name="time_to" value="<?php echo $transport['time_to']; ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <!-- Current Image Preview -->
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Current Image
                            </label>
                            <img src="<?php echo $transport['transport_image']; ?>" alt="Current vehicle image" class="w-48 h-auto mb-2">
                        </div>

                        <!-- Vehicle Image Upload -->
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="transport_image">
                                Update Vehicle Image
                            </label>
                            <input type="file" id="transport_image" name="transport_image"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Status -->
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Status
                            </label>
                            <div class="flex gap-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="status" value="available" <?php echo ($transport['status'] == 'available') ? 'checked' : ''; ?> class="form-radio text-blue-600">
                                    <span class="ml-2">Available</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="status" value="booked" <?php echo ($transport['status'] == 'booked') ? 'checked' : ''; ?> class="form-radio text-blue-600">
                                    <span class="ml-2">Booked</span>
                                </label>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex gap-4">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                                <i class="fas fa-save mr-2"></i>Update Vehicle
                            </button>
                            <a href="view-transportation.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/js-links.php'; ?>
    <script src="assets/js/transportation.js"></script>
</body>
</html>
