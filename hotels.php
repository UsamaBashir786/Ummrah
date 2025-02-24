<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
</head>

<body class="bg-gray-50">

  <?php include 'includes/navbar.php'; ?>
  <div class="my-15">&nbsp;</div>

  <section class="py-16">
    <div class="container mx-auto px-4 lg:px-12" data-aos="fade-up">
      <h3 class="text-2xl text-teal-600 my-5 lg:ml-8">- Packages</h3>
      <h2 class="text-3xl font-bold text-teal-600 lg:ml-8" style="font-family: 'Times New Roman', Times, serif;">Hotel Details</h2>
      <p class="mt-4 text-gray-700 lg:ml-8">Experience the best amenities, location, and service for a comfortable stay during your Umrah pilgrimage.</p>
    </div>

    <div class="container mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-8 mt-8">
      <!-- First Hotel -->
      <div class="flex flex-col lg:flex-row bg-white p-8 rounded-lg shadow-lg" data-aos="fade-right">
        <div class="lg:w-1/2">
          <img src="assets/images/hotels/1.jpg" alt="Hotel Image" class="w-full h-64 object-cover rounded-lg">
        </div>
        <div class="mx-4 lg:w-1/2 lg:pl-8 mt-4 lg:mt-0">
          <h3 class="text-xl font-semibold text-teal-600">Umrah Luxury Hotel</h3>
          <p class="mt-2 text-gray-700">Located in the heart of Jeddah, this 5-star hotel offers top-notch amenities and easy access to the holy sites of Umrah.</p>
          <p class="mt-4 text-gray-700"><strong>Price per Night:</strong> $120</p>
          <p class="mt-2 text-gray-700"><strong>Rating:</strong> ★★★★☆ (4.5/5)</p>
          <a href="hotel-bookings.php" class="mt-4 bg-teal-600 text-white py-2 px-6 rounded-lg hover:bg-teal-700 transition inline-block">Book Now</a>
        </div>
      </div>

      <!-- Second Hotel -->
      <div class="flex flex-col lg:flex-row bg-white p-8 rounded-lg shadow-lg" data-aos="fade-left">
        <div class="lg:w-1/2">
          <img src="assets/images/hotels/2.jpg" alt="Hotel Image" class="w-full h-64 object-cover rounded-lg">
        </div>
        <div class="mx-4 lg:w-1/2 lg:pl-8 mt-4 lg:mt-0">
          <h3 class="text-xl font-semibold text-teal-600">Makkah Royal Hotel</h3>
          <p class="mt-2 text-gray-700">Experience unparalleled luxury with breathtaking views of the Masjid al-Haram in Makkah.</p>
          <p class="mt-4 text-gray-700"><strong>Price per Night:</strong> $150</p>
          <p class="mt-2 text-gray-700"><strong>Rating:</strong> ★★★★★ (5/5)</p>
          <a href="hotel-bookings.php" class="mt-4 bg-teal-600 text-white py-2 px-6 rounded-lg hover:bg-teal-700 transition inline-block">Book Now</a>
        </div>
      </div>

      <!-- Third Hotel -->
      <div class="flex flex-col lg:flex-row bg-white p-8 rounded-lg shadow-lg" data-aos="fade-right">
        <div class="lg:w-1/2">
          <img src="assets/images/hotels/3.jpg" alt="Hotel Image" class="w-full h-64 object-cover rounded-lg">
        </div>
        <div class="mx-4 lg:w-1/2 lg:pl-8 mt-4 lg:mt-0">
          <h3 class="text-xl font-semibold text-teal-600">Jeddah Grand Hotel</h3>
          <p class="mt-2 text-gray-700">A luxurious hotel in Jeddah offering a premium stay with convenient access to the King Abdulaziz International Airport and the holy cities.</p>
          <p class="mt-4 text-gray-700"><strong>Price per Night:</strong> $110</p>
          <p class="mt-2 text-gray-700"><strong>Rating:</strong> ★★★★☆ (4.3/5)</p>
          <a href="hotel-bookings.php" class="mt-4 bg-teal-600 text-white py-2 px-6 rounded-lg hover:bg-teal-700 transition inline-block">Book Now</a>
        </div>
      </div>

      <!-- Fourth Hotel -->
      <div class="flex flex-col lg:flex-row bg-white p-8 rounded-lg shadow-lg" data-aos="fade-left">
        <div class="lg:w-1/2">
          <img src="assets/images/hotels/4.jpg" alt="Hotel Image" class="w-full h-64 object-cover rounded-lg">
        </div>
        <div class="mx-4 lg:w-1/2 lg:pl-8 mt-4 lg:mt-0">
          <h3 class="text-xl font-semibold text-teal-600">Medina Royal Suites</h3>
          <p class="mt-2 text-gray-700">Situated near the Prophet's Mosque in Medina, this hotel offers the perfect place to relax and rejuvenate during your Umrah journey.</p>
          <p class="mt-4 text-gray-700"><strong>Price per Night:</strong> $135</p>
          <p class="mt-2 text-gray-700"><strong>Rating:</strong> ★★★★☆ (4.7/5)</p>
          <a href="hotel-bookings.php" class="mt-4 bg-teal-600 text-white py-2 px-6 rounded-lg hover:bg-teal-700 transition inline-block">Book Now</a>
        </div>
      </div>

    </div>
  </section>

  <footer class="bg-teal-600 text-white py-4" data-aos="fade-up">
    <div class="container mx-auto text-center">
      <p>&copy; 2025 Umrah Luxury Hotel. All rights reserved.</p>
    </div>
  </footer>

  <script src="assets/aos-master/dist/aos.js"></script>

  <?php include 'includes/js-links.php'; ?>
  <script>
    AOS.init({
      duration: 1000,
      // once: true
    });
  </script>
</body>

</html>
