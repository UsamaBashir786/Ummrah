<?php
include "connection/connection.php";
session_start();
require_once('vendor/autoload.php');

\Stripe\Stripe::setApiKey('sk_test_4eC39HqLyjWDarjtT1zdp7dc');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
  die("<div style='
        color: #721c24; 
        background-color: #f8d7da; 
        border: 1px solid #f5c6cb; 
        padding: 15px; 
        border-radius: 5px; 
        font-family: Arial, sans-serif;
        width: 50%;
        margin: 20px auto;
        text-align: center;
      '>
        <strong>Error:</strong> You must be logged in.<br><br>
        <button onclick='window.history.back()' style='
          background-color: #dc3545; 
          color: white; 
          border: none; 
          padding: 10px 15px; 
          border-radius: 5px; 
          cursor: pointer;
        '>Go Back</button>
      </div>");
}

$package = [];
$booking_success = null;

if (isset($_GET['id'])) {
  $package_id = intval($_GET['id']);
  $query = "SELECT * FROM packages WHERE id = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("i", $package_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $package = $result->fetch_assoc() ?? [];
}

// Handle Booking Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_POST['package_id']) || empty($_POST['package_id']) || !isset($_POST['total_price'])) {
    die("Error: Invalid package data.");
  }

  $user_id = $_SESSION['user_id'];
  $package_id = intval($_POST['package_id']);
  $total_price = floatval($_POST['total_price']);

  // ✅ Step 1: Check if the user has already booked this package
  $check_query = "SELECT id FROM package_booking WHERE user_id = ? AND package_id = ? AND payment_status IN ('pending', 'paid')";
  $stmt = $conn->prepare($check_query);
  $stmt->bind_param("ii", $user_id, $package_id);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows > 0) {
    // 🚫 User already booked this package
    echo "<script>
      alert('You have already booked this package.');
      window.location.href = 'index.php';
    </script>";
    exit();
  }

  // ✅ Step 2: Insert new booking with 'pending' payment status
  $query = "INSERT INTO package_booking (user_id, package_id, total_price, payment_status) VALUES (?, ?, ?, 'pending')";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("iid", $user_id, $package_id, $total_price);

  if ($stmt->execute()) {
    $booking_id = $stmt->insert_id; // Get the inserted booking ID

    try {
      // ✅ Step 3: Redirect to Stripe Checkout
      $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
          'price_data' => [
            'currency' => 'usd',
            'product_data' => [
              'name' => 'Umrah Flight Booking',
            ],
            'unit_amount' => intval($total_price * 100),
          ],
          'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => "http://localhost:8000/success.php?booking_id={$booking_id}",
        'cancel_url' => "http://localhost:8000/fail.php",
        'metadata' => [
          'booking_id' => $booking_id,
          'user_id' => $user_id,
          'package_id' => $package_id,
        ],
      ]);

      header("Location: " . $session->url);
      exit();
    } catch (\Stripe\Exception\ApiErrorException $e) {
      echo 'Error: ' . $e->getMessage();
      exit();
    }
  } else {
    $booking_success = false;
  }
}
?>

?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Package Details | AGRSoft Umrah</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert -->
</head>

<body class="bg-gray-50">
  <?php include 'includes/navbar.php'; ?>
  <br><br><br>
  <div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
      <div class="relative h-96">
        <img src="admin/<?= htmlspecialchars($package['package_image'] ?? 'default.jpg') ?>"
          alt="<?= htmlspecialchars($package['title'] ?? 'Package') ?>"
          class="w-full h-full object-cover">
        <div class="absolute top-0 left-0 w-full h-full bg-black bg-opacity-40 flex items-center justify-center">
          <h1 class="text-4xl font-bold text-white">
            <?= htmlspecialchars($package['title'] ?? 'Unknown Package') ?>
          </h1>
        </div>
      </div>

      <div class="p-8">
        <div class="flex items-center justify-between mb-6">
          <div class="text-3xl font-bold text-gray-900">
            $<?= isset($package['price']) ? number_format($package['price'], 2) : '0.00' ?>
          </div>
          <div class="px-4 py-2 bg-teal-500 text-white rounded-full">
            <?= htmlspecialchars($package['package_type'] ?? 'N/A') ?>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
          <div>
            <h3 class="text-lg font-semibold mb-2">Flight Details</h3>
            <div class="space-y-2">
              <p><i class="fas fa-plane text-teal-500 mr-2"></i> Airline: <?= htmlspecialchars($package['airline'] ?? 'N/A') ?></p>
              <p><i class="fas fa-ticket-alt text-teal-500 mr-2"></i> Class: <?= htmlspecialchars($package['flight_class'] ?? 'N/A') ?></p>
            </div>
          </div>
        </div>

        <div class="mb-8">
          <h3 class="text-lg font-semibold mb-2">Description</h3>
          <p class="text-gray-600"><?= nl2br(htmlspecialchars($package['description'] ?? 'No description available.')) ?></p>
        </div>

        <!-- Booking Form -->
        <div class="flex justify-center">
          <form method="POST" action="">
            <input type="hidden" name="package_id" value="<?= htmlspecialchars($package['id'] ?? '0') ?>">
            <input type="hidden" name="total_price" value="<?= htmlspecialchars($package['price'] ?? '0.00') ?>">
            <button type="submit" class="inline-block px-8 py-3 bg-teal-600 text-white font-semibold rounded-lg hover:bg-teal-700 transition duration-300">
              Book Now
            </button>
          </form>
        </div>

      </div>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      <?php if ($booking_success === false) : ?>
        Swal.fire({
          title: "Booking Failed",
          text: "Something went wrong. Please try again.",
          icon: "error",
          confirmButtonText: "OK"
        });
      <?php endif; ?>
    });
  </script>

</body>

</html>