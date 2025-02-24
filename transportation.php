<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php' ?>
  <link rel="stylesheet" href="assets/css/transportation.css">
  <link href="https://unpkg.com/boxicons/css/boxicons.min.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.1/gsap.min.js"></script>
  <script>
    function filterTransport(type) {
      document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
      document.getElementById(type + '-btn').classList.add('active');

      document.querySelectorAll('.transport-card').forEach(card => {
        card.classList.add('hidden');
      });
      document.querySelectorAll('.' + type).forEach(card => {
        card.classList.remove('hidden');
        gsap.fromTo(card, {
          opacity: 0,
          y: 100
        }, {
          opacity: 1,
          y: 0,
          duration: 0.5
        });
      });
    }
  </script>
</head>

<body class="bg-gray-50">
  <!-- <?php include 'includes/navbar.php' ?> -->
  <!-- Hero Section -->
  <div class="my-5">&nbsp;</div>
  <section class="hero">
    <div class="container mx-auto px-6">
      <h1 class="text-5xl font-bold mb-4">Umrah Transportation</h1>
      <p class="text-xl mb-8">Seamless travel solutions for your sacred journey.</p>
    </div>
  </section>

  <!-- Services Section -->
  <section class="py-20 bg-gray-100">
    <div class="container mx-auto px-6 text-center">
      <h2 class="text-4xl font-semibold text-teal-700 mb-12">Our Services</h2>
      <!-- Filter Section -->
      <section class="text-center py-10">
        <div class="filter-btns">
          <button id="luxury-btn" class="filter-btn active bg-teal-600" onclick="filterTransport('luxury')">Luxury Sedan</button>
          <button id="vip-btn" class="filter-btn bg-teal-600" onclick="filterTransport('vip')">VIP Shuttle</button>
          <button id="shared-btn" class="filter-btn bg-teal-600" onclick="filterTransport('shared')">Shared Bus</button>
        </div>
      </section>
      <div class="grid md:grid-cols-3 gap-8">
        <!-- Luxury Sedan Card -->
        <div class="p-6 bg-white shadow-lg rounded-lg card transport-card luxury" data-aos="fade-up">
          <div class="badge">Luxury</div>
          <img src="assets/images/transportation/luxury sedan/1.jpg" alt="Luxury Sedan 1" class="rounded-lg mb-4 w-full h-48 object-cover">
          <h3 class="text-2xl font-bold mb-2">Luxury Sedan 1</h3>
          <p class="text-gray-600">Comfortable ride with premium facilities.</p>
          <div class="status available">Available</div>
          <p class="timing">Available from 9:00 AM to 7:00 PM</p>
          <button class="mt-4 btn-primary text-white bg-teal-600" onclick="window.location.href='transportation-booking.php'">Book Now</button>
        </div>
        <!-- Luxury Sedan Card -->
        <div class="p-6 bg-white shadow-lg rounded-lg card transport-card luxury" data-aos="fade-up">
          <div class="badge">Luxury</div>
          <img src="assets/images/transportation/luxury sedan/2.jpg" alt="Luxury Sedan 2" class="rounded-lg mb-4 w-full h-48 object-cover">
          <h3 class="text-2xl font-bold mb-2">Luxury Sedan 2</h3>
          <p class="text-gray-600">Smooth and elegant travel experience.</p>
          <div class="status open-to-book">Open to Book</div>
          <p class="timing">Available from 10:00 AM to 6:00 PM</p>
          <button class="mt-4 btn-primary text-white bg-teal-600" onclick="window.location.href='transportation-booking.php'">Book Now</button>
        </div>
        <!-- VIP Shuttle Card -->
        <div class="p-6 bg-white shadow-lg rounded-lg card transport-card vip hidden" data-aos="fade-up">
          <div class="badge">VIP</div>
          <img src="assets/images/transportation/vip shuttle/1.jpg" alt="VIP Shuttle 1" class="rounded-lg mb-4 w-full h-48 object-cover">
          <h3 class="text-2xl font-bold mb-2">VIP Shuttle 1</h3>
          <p class="text-gray-600">Luxury shuttle service for premium comfort.</p>
          <div class="status booked">Booked</div>
          <p class="timing">Unavailable</p>
          <button class="mt-4 btn-primary text-white bg-teal-600" onclick="window.location.href='transportation-booking.php'">Book Now</button>
        </div>
        <!-- VIP Shuttle Card -->
        <div class="p-6 bg-white shadow-lg rounded-lg card transport-card vip hidden" data-aos="fade-up">
          <div class="badge">VIP</div>
          <img src="assets/images/transportation/shared bus/1.jpg" alt="VIP Shuttle 2" class="rounded-lg mb-4 w-full h-48 object-cover">
          <h3 class="text-2xl font-bold mb-2">VIP Shuttle 2</h3>
          <p class="text-gray-600">Spacious and comfortable travel.</p>
          <div class="status available">Available</div>
          <p class="timing">Available from 8:00 AM to 5:00 PM</p>
          <button class="mt-4 btn-primary text-white bg-teal-600" onclick="window.location.href='transportation-booking.php'">Book Now</button>
        </div>
        <!-- Shared Bus Card -->
        <div class="p-6 bg-white shadow-lg rounded-lg card transport-card shared hidden" data-aos="fade-up">
          <div class="badge">Shared</div>
          <img src="assets/images/transportation/shared bus/2.jpg" alt="Shared Bus 1" class="rounded-lg mb-4 w-full h-48 object-cover">
          <h3 class="text-2xl font-bold mb-2">Shared Bus 1</h3>
          <p class="text-gray-600">Affordable group transport.</p>
          <div class="status open-to-book">Open to Book</div>
          <p class="timing">Available from 7:00 AM to 9:00 PM</p>
          <button class="mt-4 btn-primary text-white bg-teal-600" onclick="window.location.href='transportation-booking.php'">Book Now</button>
        </div>
        <!-- Shared Bus Card -->
        <div class="p-6 bg-white shadow-lg rounded-lg card transport-card shared hidden" data-aos="fade-up">
          <div class="badge">Shared</div>
          <img src="assets/images/transportation/shared bus/2.jpg" alt="Shared Bus 2" class="rounded-lg mb-4 w-full h-48 object-cover">
          <h3 class="text-2xl font-bold mb-2">Shared Bus 2</h3>
          <p class="text-gray-600">Convenient and budget-friendly option.</p>
          <div class="status booked">Booked</div>
          <p class="timing">Unavailable</p>
          <button class="mt-4 btn-primary text-white bg-teal-600" onclick="window.location.href='transportation-booking.php'">Book Now</button>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact Section -->
  <section class="py-20 text-center">
    <div class="container mx-auto px-6">
      <h2 class="text-4xl font-semibold text-teal-700 mb-6">Get in Touch</h2>
      <p class="text-lg text-gray-600">Need assistance? Contact us for bookings and inquiries.</p>
    </div>
  </section>

  <!-- Include AOS library -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  <script>
    AOS.init({
      duration: 1000,
      // once: true
    });
  </script>

</body>

</html>