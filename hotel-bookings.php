<?php
require_once('vendor/autoload.php');

\Stripe\Stripe::setApiKey('sk_test_4eC39HqLyjWDarjtT1zdp7dc'); 

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $name = htmlspecialchars($_POST['full_name']);
  $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
  $phone = htmlspecialchars($_POST['contact_number']);
  $amount = 85000;  // You can adjust this based on your actual pricing
  $hotel = htmlspecialchars($_POST['hotel']);
  $check_in = htmlspecialchars($_POST['check_in']);
  $guests = (int)$_POST['guests'];
  $special_requests = htmlspecialchars($_POST['special_requests'] ?? ''); // Optional field

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
      'success_url' => 'http://localhost:8000/success.php',
      'cancel_url' => 'http://localhost:8000/fail.php',
      'metadata' => [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'hotel' => $hotel,
        'check_in' => $check_in,
        'guests' => $guests,
        'special_requests' => $special_requests,
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
  <?php include 'includes/css-links.php'; ?>
</head>

<body class="bg-gray-50">

  <?php include 'includes/navbar.php'; ?>

  <!-- Booking Form Section -->
  <section class="py-16 px-4 my-3">
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">
      <h2 class="text-3xl font-semibold text-teal-600 text-center">Book Your Stay</h2>
      <p class="text-center mt-4 text-gray-700">Fill in the details below to complete your booking.</p>

      <!-- Booking Form -->
      <form action="" method="POST" class="mt-8 space-y-6">
        <!-- Hotel Selection -->
        <div class="flex flex-col lg:flex-row space-y-4 lg:space-y-0 lg:space-x-4">
          <div class="w-full lg:w-1/2">
            <label for="hotel" class="block text-lg font-medium text-gray-700">Choose a Hotel</label>
            <select id="hotel" name="hotel" required class="mt-2 p-3 border border-gray-300 rounded-lg w-full">
              <option value="umrah_luxury_hotel">Umrah Luxury Hotel</option>
              <option value="makkah_royal_hotel">Makkah Royal Hotel</option>
              <option value="jeddah_grand_hotel">Jeddah Grand Hotel</option>
              <option value="medina_royal_suites">Medina Royal Suites</option>
            </select>
          </div>
          <!-- Date Selection -->
          <div class="w-full lg:w-1/2">
            <label for="check-in" class="block text-lg font-medium text-gray-700">Check-in Date</label>
            <input type="date" id="check-in" name="check_in" required class="mt-2 p-3 border border-gray-300 rounded-lg w-full">
          </div>
        </div>

        <!-- Name and Contact -->
        <div class="flex flex-col lg:flex-row space-y-4 lg:space-y-0 lg:space-x-4">
          <div class="w-full lg:w-1/2">
            <label for="full-name" class="block text-lg font-medium text-gray-700">Full Name</label>
            <input type="text" id="full-name" name="full_name" required placeholder="Enter your full name" class="mt-2 p-3 border border-gray-300 rounded-lg w-full">
          </div>
          <div class="w-full lg:w-1/2">
            <label for="contact-number" class="block text-lg font-medium text-gray-700">Contact Number</label>
            <input type="text" id="contact-number" name="contact_number" required placeholder="Enter your contact number" class="mt-2 p-3 border border-gray-300 rounded-lg w-full">
          </div>
        </div>

        <!-- Number of Guests -->
        <div>
          <label for="guests" class="block text-lg font-medium text-gray-700">Number of Guests</label>
          <input type="number" id="guests" name="guests" required placeholder="Enter number of guests" min="1" class="mt-2 p-3 border border-gray-300 rounded-lg w-full">
        </div>

        <!-- Special Requests -->
        <div>
          <label for="special-requests" class="block text-lg font-medium text-gray-700">Special Requests (Optional)</label>
          <textarea id="special-requests" name="special_requests" rows="4" placeholder="Any special requests or notes" class="mt-2 p-3 border border-gray-300 rounded-lg w-full"></textarea>
        </div>

        <!-- Submit Button -->
        <div class="text-center mt-8">
          <button type="submit" class="bg-teal-600 text-white py-3 px-6 rounded-lg hover:bg-teal-700 transition duration-300">Confirm Booking</button>
        </div>
      </form>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-teal-600 text-white py-4">
    <div class="container mx-auto text-center">
      <p>&copy; 2025 Umrah Luxury Hotel. All rights reserved.</p>
    </div>
  </footer>

</body>

</html>