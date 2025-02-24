<?php
// Include the Stripe PHP library
require_once('vendor/autoload.php');

// Set your Stripe Secret Key
\Stripe\Stripe::setApiKey('sk_test_4eC39HqLyjWDarjtT1zdp7dc');  // Replace with your secret key

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Get data from the frontend
  $transport_category = $_POST['transport-category'];
  $transport_option = $_POST['transport-option'];
  $journey_date = $_POST['date'];
  $journey_time = $_POST['time'];
  $contact_name = $_POST['contact-name'];
  $contact_phone = $_POST['contact-phone'];
  $pickup_location = $_POST['pickup-location'];
  $amount = 85000;

  try {
    // Create a Checkout Session
    $session = \Stripe\Checkout\Session::create([
      'payment_method_types' => ['card'],
      'line_items' => [
        [
          'price_data' => [
            'currency' => 'usd',
            'product_data' => [
              'name' => 'Umrah Flight Booking',
            ],
            'unit_amount' => $amount,
          ],
          'quantity' => 1,
        ],
      ],
      'mode' => 'payment',
      // 'success_url' => 'success.php?session_id={CHECKOUT_SESSION_ID}',
      'success_url' => 'http://localhost:8000/success.php',
      'cancel_url' => 'http://localhost:8000/fail.php',

      'mode' => 'payment',
      'success_url' => 'http://localhost:8000/success.php',
      'cancel_url' => 'http://localhost:8000/fail.php',
      'metadata' => [
        'transport_category' => $transport_category,
        'transport_option' => $transport_option,
        'journey_date' => $journey_date,
        'journey_time' => $journey_time,
        'contact_name' => $contact_name,
        'contact_phone' => $contact_phone,
        'pickup_location' => $pickup_location,
      ],
    ]);

    // Redirect to the Stripe checkout page
    header("Location: " . $session->url);
    exit;
  } catch (\Stripe\Exception\ApiErrorException $e) {
    // Handle errors from Stripe
    echo 'Error: ' . $e->getMessage();
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php' ?>
  <link rel="stylesheet" href="assets/css/transportation-booking.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
  <style>

  </style>
</head>

<body>
  <?php include 'includes/navbar.php' ?>

  <!-- Hero Section -->
  <div class="my-5">&nbsp;</div>
  <section class="hero">
    <div class="container mx-auto px-6">
      <h1 class="text-5xl font-bold mb-4">Book Your Ride</h1>
      <p class="text-xl mb-8">Choose your transport and fill out the details below to book your journey.</p>
    </div>
  </section>

  <!-- Booking Form Section -->
  <section class="py-20 bg-gray-100">
    <div class="container mx-auto px-6 text-center">
      <div class="form-container">
        <h2>Book Your Transport</h2>

        <form action="" method="POST">
          <!-- Transport Category Dropdown -->
          <div class="form-group">
            <label for="transport-type">Choose Transport Type</label>
            <select name="transport-category" id="transport-category" required>
              <option value="">Choose Transport</option>
              <option value="luxury">Luxury</option>
              <option value="vip">VIP</option>
              <option value="shared">Shared</option>
            </select>
          </div>

          <!-- Transport Options Dropdown (This will be updated dynamically) -->
          <div class="form-group">
            <label for="transport-option">Select Specific Transport</label>
            <select name="transport-option" id="transport-option" required>
              <option value="">Please select a category first</option>
            </select>
          </div>

          <!-- Date and Time Section -->
          <div class="form-section-title">Journey Details</div>
          <div class="form-group">
            <label for="date">Select Date</label>
            <input type="date" name="date" id="date" required>
          </div>
          <div class="form-group">
            <label for="time">Select Time</label>
            <input type="time" name="time" id="time" required>
          </div>

          <!-- Contact Information Section -->
          <div class="form-section-title">Contact Information</div>
          <div class="contact-section">
            <div class="form-group">
              <label for="contact-name">Your Name</label>
              <input type="text" name="contact-name" id="contact-name" required placeholder="Enter your name">
            </div>
            <div class="form-group">
              <label for="contact-phone">Phone Number</label>
              <input type="tel" name="contact-phone" id="contact-phone" required placeholder="Enter your phone number">
            </div>
          </div>

          <!-- Pickup Location Section -->
          <div class="form-section-title">Pickup Location</div>
          <div class="form-group">
            <label for="pickup-location">Enter Pickup Location</label>
            <input type="text" id="pickup-location" name="pickup-location" placeholder="Type to search" required>
            <div id="suggestions" class="suggestions"></div>
          </div>

          <!-- Map Section -->
          <div id="map" style="z-index: 1;"></div>

          <!-- Submit Button -->
          <div class="form-group my-5">
            <button type="submit">Submit Booking</button>
          </div>
        </form>
      </div>
    </div>
  </section>

  <!-- Footer Section -->
  <section class="footer-section">
    <p class="text-lg text-gray-600">Need assistance? Contact us for more information.</p>
  </section>

  <script src="assets/js/transportation-booking.js"></script>

</body>

</html>