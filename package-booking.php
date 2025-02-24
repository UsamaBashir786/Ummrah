<?php
// Include the Stripe PHP library
require_once('vendor/autoload.php');

// Set your Stripe Secret Key
\Stripe\Stripe::setApiKey('sk_test_4eC39HqLyjWDarjtT1zdp7dc');  // Replace with your secret key

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Get data from the frontend
  $hotel = $_POST['hotel'];
  $check_in = $_POST['check_in'];
  $firstName = $_POST['firstName'];
  $lastName = $_POST['lastName'];
  $email = $_POST['email'];
  $phone = $_POST['phone'];
  $packageType = $_POST['packageType'];
  $address = $_POST['address'];
  $profileImage = $_FILES['profileImage'];  // Handle file upload

  $amount = 85000;  // The amount in cents (e.g., $850 = 85000 cents)

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

      'metadata' => [
        'hotel' => $hotel,
        'check_in' => $check_in,
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email,
        'phone' => $phone,
        'packageType' => $packageType,
        'address' => $address,
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
  <script src="https://js.stripe.com/v3/"></script>
</head>

<body class="bg-gray-50">
<?php include 'includes/navbar.php'; ?>

  <!-- Booking Form Section -->
  <section class="py-16 px-4">
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">
      <h2 class="text-3xl font-semibold text-teal-600 text-center">Book Your Stay</h2>
      <p class="text-center mt-4 text-gray-700">Fill in the details below to complete your booking.</p>

      <!-- Booking Form -->
      <form id="booking-form" action="" method="POST" class="mt-8 space-y-6" enctype="multipart/form-data">
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
            <label for="firstName" class="block text-lg font-medium text-gray-700">First Name</label>
            <input type="text" id="firstName" name="firstName" required placeholder="Enter your first name" class="mt-2 p-3 border border-gray-300 rounded-lg w-full">
          </div>
          <div class="w-full lg:w-1/2">
            <label for="lastName" class="block text-lg font-medium text-gray-700">Last Name</label>
            <input type="text" id="lastName" name="lastName" required placeholder="Enter your last name" class="mt-2 p-3 border border-gray-300 rounded-lg w-full">
          </div>
        </div>

        <div class="flex flex-col lg:flex-row space-y-4 lg:space-y-0 lg:space-x-4">
          <div class="w-full lg:w-1/2">
            <label for="email" class="block text-lg font-medium text-gray-700">Email</label>
            <input type="email" id="email" name="email" required placeholder="Enter your email" class="mt-2 p-3 border border-gray-300 rounded-lg w-full">
          </div>
          <div class="w-full lg:w-1/2">
            <label for="phone" class="block text-lg font-medium text-gray-700">Phone</label>
            <input type="text" id="phone" name="phone" required placeholder="Enter your phone number" class="mt-2 p-3 border border-gray-300 rounded-lg w-full">
          </div>
        </div>

        <!-- Package Type -->
        <div class="w-full">
          <label for="packageType" class="block text-lg font-medium text-gray-700">Package Type</label>
          <select id="packageType" name="packageType" required class="mt-2 p-3 border border-gray-300 rounded-lg w-full">
            <option value="single">Single Umrah Package</option>
            <option value="group">Group Umrah Package</option>
          </select>
        </div>

        <!-- Special Requests -->
        <div>
          <label for="address" class="block text-lg font-medium text-gray-700">Address</label>
          <input type="text" id="address" name="address" required placeholder="Enter your address" class="mt-2 p-3 border border-gray-300 rounded-lg w-full">
        </div>

        <!-- Profile Image -->
        <div>
          <label for="profileImage" class="block text-lg font-medium text-gray-700">Profile Image</label>
          <input type="file" id="profileImage" name="profileImage" accept="image/*" class="mt-2 p-3 border border-gray-300 rounded-lg w-full">
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