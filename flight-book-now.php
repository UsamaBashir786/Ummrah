<?php
// Include the Stripe PHP library
require_once('vendor/autoload.php');

// Set your Stripe Secret Key
\Stripe\Stripe::setApiKey('sk_test_4eC39HqLyjWDarjtT1zdp7dc');  // Replace with your secret key

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Get data from the frontend
  $name = $_POST['name'];
  $email = $_POST['email'];
  $phone = $_POST['phone'];
  $passport_number = $_POST['passport-number'];
  $nationality = $_POST['nationality'];
  $meal_preference = $_POST['meal-preference'];
  $special_requests = $_POST['special-requests'];
  $emergency_contact = $_POST['emergency-contact'];
  $emergency_phone = $_POST['emergency-phone'];

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
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'passport_number' => $passport_number,
        'nationality' => $nationality,
        'meal_preference' => $meal_preference,
        'special_requests' => $special_requests,
        'emergency_contact' => $emergency_contact,
        'emergency_phone' => $emergency_phone
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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book Your Umrah Flight - Confirmation</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-teal-50 font-sans">

  <?php include 'includes/navbar.php' ?>
  <div class="my-6">&nbsp;</div>
  <!-- Booking Form Section -->
  <section class="py-16 bg-teal-100">
    <div class="container mx-auto text-center">
      <h2 class="text-3xl font-bold text-teal-600">Confirm Your Flight Booking</h2>
      <p class="mt-4 text-gray-700">Please fill in your details to confirm the booking for your Umrah flight.</p>

      <div class="mt-8 max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md">
        <!-- Flight Details -->
        <div class="mb-8 text-left">
          <h3 class="text-2xl font-semibold text-teal-600">Flight Details</h3>
          <div class="mt-4">
            <p><strong>Flight:</strong> PIA Airlines</p>
            <p><strong>Departure City:</strong> Karachi</p>
            <p><strong>Arrival City:</strong> Jeddah</p>
            <p><strong>Departure Date:</strong> 2025-03-10</p>
            <p><strong>Price:</strong> $850</p>
          </div>
        </div>

        <!-- Passenger Information Form -->
        <div class="mb-8 text-left">
          <h3 class="text-2xl font-semibold text-teal-600">Passenger Information</h3>
          <form action="" method="POST" class="space-y-6">
            <!-- Full Name -->
            <div>
              <label for="name" class="block text-gray-700">Full Name</label>
              <input type="text" id="name" name="name" class="w-full p-3 border border-gray-300 rounded-lg" placeholder="Enter your full name" required>
            </div>

            <!-- Email -->
            <div>
              <label for="email" class="block text-gray-700">Email</label>
              <input type="email" id="email" name="email" class="w-full p-3 border border-gray-300 rounded-lg" placeholder="Enter your email" required>
            </div>

            <!-- Phone Number -->
            <div>
              <label for="phone" class="block text-gray-700">Phone Number</label>
              <input type="tel" id="phone" name="phone" class="w-full p-3 border border-gray-300 rounded-lg" placeholder="Enter your phone number" required>
            </div>

            <!-- Passport Number -->
            <div>
              <label for="passport-number" class="block text-gray-700">Passport Number</label>
              <input type="text" id="passport-number" name="passport-number" class="w-full p-3 border border-gray-300 rounded-lg" placeholder="Enter your passport number" required>
            </div>

            <!-- Nationality -->
            <div>
              <label for="nationality" class="block text-gray-700">Nationality</label>
              <select id="nationality" name="nationality" class="w-full p-3 border border-gray-300 rounded-lg" required>
                <option value="" disabled selected>Select your nationality</option>
                <option value="Pakistani">Pakistani</option>
                <option value="Indian">Indian</option>
                <option value="Bangladeshi">Bangladeshi</option>
                <!-- Add other options as needed -->
              </select>
            </div>

            <!-- Meal Preference -->
            <div>
              <label for="meal-preference" class="block text-gray-700">Meal Preference</label>
              <select id="meal-preference" name="meal-preference" class="w-full p-3 border border-gray-300 rounded-lg">
                <option value="" disabled selected>Select meal preference</option>
                <option value="vegetarian">Vegetarian</option>
                <option value="non-vegetarian">Non-Vegetarian</option>
                <option value="halal">Halal</option>
                <option value="kosher">Kosher</option>
              </select>
            </div>

            <!-- Special Requests -->
            <div>
              <label for="special-requests" class="block text-gray-700">Special Requests</label>
              <textarea id="special-requests" name="special-requests" class="w-full p-3 border border-gray-300 rounded-lg" rows="4" placeholder="Any special requests or information (e.g., wheelchair, assistance, etc.)"></textarea>
            </div>

            <!-- Emergency Contact -->
            <div>
              <label for="emergency-contact" class="block text-gray-700">Emergency Contact Name</label>
              <input type="text" id="emergency-contact" name="emergency-contact" class="w-full p-3 border border-gray-300 rounded-lg" placeholder="Enter emergency contact name" required>
            </div>

            <div>
              <label for="emergency-phone" class="block text-gray-700">Emergency Contact Phone</label>
              <input type="tel" id="emergency-phone" name="emergency-phone" class="w-full p-3 border border-gray-300 rounded-lg" placeholder="Enter emergency contact phone" required>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="w-full px-6 py-3 bg-teal-600 text-white font-semibold rounded-lg hover:bg-teal-500 focus:outline-none">
              Confirm Booking
            </button>
          </form>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-teal-600 py-6 text-white">
    <div class="container mx-auto text-center">
      <p>&copy; 2025 Umrah Journey. All Rights Reserved.</p>
    </div>
  </footer>

</body>

</html>