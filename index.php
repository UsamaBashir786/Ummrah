<?php
session_start();

include 'connection/connection.php';

if (isset($_SESSION['user_id'])) {
  $user_id = $_SESSION['user_id'];
  $full_name = $_SESSION['full_name'];
}
?>
<!DOCTYPE html>

<html lang="en">
<meta http-equiv="content-type" content="text/html;charset=utf-8" />

<head>
  <?php include 'includes/css-links.php' ?>
</head>

<body class="bg-gray-100">
  <!----
  --------------- >navbar
  --->

  <?php include 'includes/navbar.php' ?>
  <?php include 'includes/bttom-to-top.php' ?>
  <!-- <?php include 'includes/pre-loader.php' ?> -->
  <?php include 'includes/chatbot.php' ?>
  <!----
  --------------- >HERO Section
  --->
  <section class="my-5 relative bg-cover bg-center h-screen" style="background-image: url('assets/images/hero/hero.jpg');">
    <div class="absolute inset-0 bg-black opacity-50"></div>
    <div class="container mx-auto h-full flex items-center justify-center text-center text-white px-6">
      <div class="z-10 space-y-6">
        <h1 class="hero-h1-media-query text-5xl sm:text-6xl font-extrabold leading-tight">
          <br />
          <br />
          Experience the Sacred Journey of Umrah
        </h1>
        <p class="hero-p-media-query text-lg sm:text-xl max-w-3xl mx-auto">
          Embark on a transformative spiritual journey with our comprehensive Umrah packages. Let us help you make the most of your pilgrimage experience with our tailored services.
        </p>
        <a href="#packages" class="hero-a-media-query inline-block bg-teal-600 hover:bg-teal-500 text-lg font-semibold py-3 px-6 rounded-lg transition duration-300">
          Explore Packages
        </a>
      </div>
    </div>
  </section>

  <!----
  --------------- >Packages Section
  --->
  <section id="packages" class="py-20 bg-gray-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <h3 class="text-2xl text-teal-600 my-5">- Packages</h3>
      <div class="flex flex-col lg:flex-row justify-between items-center mb-8 px-4">
        <h2 class="packages-heading-media-query text-4xl text-teal-600 mb-4 lg:mb-0" style="font-family: 'Times New Roman', Times, serif;" data-aos="fade-up" id="title">
          Choose Your Umrah Package
        </h2>
        <a href="#packages" class="flex items-center text-teal-500 text-2xl py-2 px-4 rounded-lg transition duration-300 hover:underline" data-aos="zoom-in-up">
          View Packages <i class="bx bx-chevron-right ml-2 text-2xl"></i>
        </a>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 px-4">
        <?php
        // Fetch packages from database
        $query = "SELECT * FROM packages";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
        ?>
            <div class="bg-white p-6 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 package-card relative border border-gray-200">
              <div class="mb-6">
                <img data-aos="zoom-in-right" src="admin/<?= htmlspecialchars($row['package_image']) ?>" loading="lazy" alt="<?php echo htmlspecialchars($row['title']); ?>" class="w-full h-48 object-cover rounded-2xl mb-4 shadow-sm" />
                <div data-aos="zoom-in-right" class="absolute top-4 right-4 bg-teal-600 text-white text-sm font-semibold px-4 py-1 rounded-full shadow-md">
                  Limited Offer
                </div>
                <p data-aos="zoom-in-right" class="text-4xl font-extrabold text-teal-600 mb-2">$<?php echo number_format($row['price'], 2); ?></p>
                <h3 data-aos="zoom-in-right" class="text-xl font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($row['title']); ?></h3>
                <p data-aos="zoom-in-right" class="text-gray-500 text-sm mb-4"><?php echo htmlspecialchars($row['departure_city']); ?> - <?php echo htmlspecialchars($row['arrival_city']); ?></p>
              </div>

              <ul class="text-left space-y-4 text-gray-700 mb-6">
                <li class="flex items-center space-x-3">
                  <div class="bg-teal-100 text-teal-600 p-2 rounded-full shadow-sm">
                    <i data-aos="zoom-in-right" class="bx bx-book-alt text-xl"></i>
                  </div>
                  <span data-aos="zoom-in-right">Document Guide</span>
                </li>
                <li class="flex items-center space-x-3">
                  <div class="bg-teal-100 text-teal-600 p-2 rounded-full shadow-sm">
                    <i data-aos="zoom-in-right" class="bx bx-hotel text-xl"></i>
                  </div>
                  <span data-aos="zoom-in-right"><?php echo htmlspecialchars($row['flight_class']); ?> Class Flight</span>
                </li>
                <li class="flex items-center space-x-3">
                  <div class="bg-teal-100 text-teal-600 p-2 rounded-full shadow-sm">
                    <i data-aos="zoom-in-right" class="bx bx-food-menu text-xl"></i>
                  </div>
                  <span data-aos="zoom-in-right">Local Meals</span>
                </li>
                <li class="flex items-center space-x-3">
                  <div class="bg-teal-100 text-teal-600 p-2 rounded-full shadow-sm">
                    <i data-aos="zoom-in-right" class="bx bx-check-circle text-xl"></i>
                  </div>
                  <span data-aos="zoom-in-right">Visa Included</span>
                </li>
              </ul>

              <a data-aos="zoom-in-right" href="package-details.php?id=<?php echo $row['id']; ?>" class="inline-block bg-gradient-to-r from-teal-600 to-teal-400 hover:from-teal-700 hover:to-teal-500 text-white font-medium py-3 px-5 rounded-xl shadow-md transition-all duration-300">
                Learn More
              </a>
            </div>
        <?php
          }
        } else {
          echo "<p class='text-gray-500 text-center col-span-4'>No packages available at the moment.</p>";
        }
        ?>
      </div>
    </div>
  </section>

  <!----
  --------------- >Features Section
  --->
  <!-- Features Section -->
  <section class="features bg-gray-100 py-16">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <h3 class="text-2xl text-teal-600 my-5">- Features</h3>
      <div class="flex flex-col lg:flex-row justify-between items-center mb-8 px-4">
        <h2 id="features" class="packages-heading-media-query text-4xl text-teal-600 mb-4 lg:mb-0" style="font-family: 'Times New Roman', Times, serif;" data-aos="fade-up" id="title">
          Elevate Your Faith
        </h2>
        <a href="#packages" class="flex items-center text-teal-500 text-2xl py-2 px-4 rounded-lg transition duration-300 hover:underline"> View Packages <i class="bx bx-chevron-right ml-2 text-2xl"></i> </a>
      </div>

      <div class="flex flex-wrap lg:flex-nowrap items-center justify-between space-x-12">
        <div class="w-full lg:w-1/3 mb-6 lg:mb-0">
          <img src="assets/images/features.webp" alt="Feature Image" class="w-full h-auto rounded-lg shadow-lg" />
        </div>

        <div class="w-full lg:w-2/3 grid grid-cols-1 lg:grid-cols-2 gap-8">
          <div class="flex items-center space-x-4 p-6 transition duration-300" data-aos="zoom-in-up">
            <img src="assets/images/features/1.webp" alt="Tawaf" class="f-fix mx-auto mb-4 w-16 h-16 object-cover" />
            <div class="mx-3">
              <h3 class="text-xl font-semibold text-teal-600 mb-2">Tawaf</h3>
              <p class="text-gray-500">Circumambulating the Kaaba in unity.</p>
            </div>
          </div>

          <div class="flex items-center space-x-4 p-6 transition duration-300" data-aos="zoom-in-up">
            <img src="assets/images/features/2.webp" alt="Ihram" class="f-fix mx-auto mb-4 w-16 h-16 object-cover" />
            <div class="mx-2">
              <h3 class="text-xl font-semibold text-teal-600 mb-2">Ihram</h3>
              <p class="text-gray-500">Sacred attire signifying purity.</p>
            </div>
          </div>

          <div class="flex items-center space-x-4 p-6 transition duration-300" data-aos="zoom-in-up">
            <img src="assets/images/features/3.webp" alt="Mina" class="f-fix mx-auto mb-4 w-16 h-16 object-cover" />
            <div class="mx-4">
              <h3 class="text-xl font-semibold text-teal-600 mb-2">Mina</h3>
              <p class="text-gray-500">Sacred desert valley for pilgrims.</p>
            </div>
          </div>

          <div class="flex items-center space-x-4 p-6 transition duration-300" data-aos="zoom-in-up">
            <img src="assets/images/features/4.webp" alt="Jamarat" class="f-fix mx-auto mb-4 w-16 h-16 object-cover" />
            <div class="mx-2">
              <h3 class="text-xl font-semibold text-teal-600 mb-2">Jamarat</h3>
              <p class="text-gray-500">Symbolic act of rejecting Satan.</p>
            </div>
          </div>

          <div class="flex items-center space-x-4 p-6 transition duration-300" data-aos="zoom-in-up">
            <img src="assets/images/features/5.webp" alt="Zam-Zam" class="f-fix mx-auto mb-4 w-16 h-16 object-cover" />
            <div class="mx-2">
              <h3 class="text-xl font-semibold text-teal-600 mb-2">Zam-Zam</h3>
              <p class="text-gray-500">Holy water with miraculous origins.</p>
            </div>
          </div>

          <div class="flex items-center space-x-4 p-6 transition duration-300" data-aos="zoom-in-up">
            <img src="assets/images/features/6.webp" alt="Prayer Mat" class="f-fix mx-auto mb-4 w-16 h-16 object-cover" />
            <div class="mx-2">
              <h3 class="text-xl font-semibold text-teal-600 mb-2">Prayer Mat</h3>
              <p class="text-gray-500">Sacred space for performing Salah.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!----
  --------------- >Testimonials Section
  --->
  <section class="testimonial bg-gray-100 py-16">
    <div class="relative">
      <img src="assets/images/testimonials/1.jpg" alt="Testimonial Image" class="w-full h-96 object-cover sm:h-64 md:h-80 lg:h-96" />

      <div class="absolute top-8 left-8 bg-white p-6 rounded-lg shadow-lg w-full sm:w-3/4 md:w-2/3 lg:w-1/3" data-aos="zoom-in-up">
        <h3 class="text-xl font-semibold text-teal-600 mb-4">Customer Testimonials</h3>

        <div id="testimonialSlider" class="slick-slider">
          <div>
            <div class="items-center">
              <div class="text-yellow-500">
                <i class="bx bx-star"></i>
                <i class="bx bx-star"></i>
                <i class="bx bx-star"></i>
                <i class="bx bx-star"></i>
                <i class="bx bx-star"></i>
              </div>
              <div class="flex items-center">
                <i class="bx bx-quote-left text-teal-600 mr-2 text-xl"></i>
                <p>"This was the most incredible journey of my life!"</p>
              </div>
            </div>
            <span>- Ayesha B.</span>
            <div class="flex items-center mt-4">
              <img src="assets/images/profile/1.jpg" alt="Ayesha B." class="w-10 h-10 rounded-full mr-3" />
              <div>
                <span class="block text-teal-600">Ayesha B.</span>
                <div class="flex space-x-3 text-teal-600">
                  <a href="#" class="text-xl"><i class="bx bxl-facebook"></i></a>
                  <a href="#" class="text-xl"><i class="bx bxl-twitter"></i></a>
                  <a href="#" class="text-xl"><i class="bx bxl-instagram"></i></a>
                </div>
              </div>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="absolute bottom-4 right-4 text-teal-600 w-10 h-10" viewBox="0 0 975.036 975.036">
              <path
                d="M925.036 57.197h-304c-27.6 0-50 22.4-50 50v304c0 27.601 22.4 50 50 50h145.5c-1.9 79.601-20.4 143.3-55.4 191.2-27.6 37.8-69.399 69.1-125.3 93.8-25.7 11.3-36.8 41.7-24.8 67.101l36 76c11.6 24.399 40.3 35.1 65.1 24.399 66.2-28.6 122.101-64.8 167.7-108.8 55.601-53.7 93.7-114.3 114.3-181.9 20.601-67.6 30.9-159.8 30.9-276.8v-239c0-27.599-22.401-50-50-50zM106.036 913.497c65.4-28.5 121-64.699 166.9-108.6 56.1-53.7 94.4-114.1 115-181.2 20.6-67.1 30.899-159.6 30.899-277.5v-239c0-27.6-22.399-50-50-50h-304c-27.6 0-50 22.4-50 50v304c0 27.601 22.4 50 50 50h145.5c-1.9 79.601-20.4 143.3-55.4 191.2-27.6 37.8-69.4 69.1-125.3 93.8-25.7 11.3-36.8 41.7-24.8 67.101l35.9 75.8c11.601 24.399 40.501 35.2 65.301 24.399z"></path>
            </svg>
          </div>

          <div>
            <div class="items-center">
              <div class="text-yellow-500">
                <i class="bx bx-star"></i>
                <i class="bx bx-star"></i>
                <i class="bx bx-star"></i>
                <i class="bx bx-star"></i>
                <i class="bx bx-star"></i>
              </div>
              <div class="flex items-center">
                <i class="bx bx-quote-left text-teal-600 mr-2 text-xl"></i>
                <p>"Everything was well-organized, highly recommend!"</p>
              </div>
            </div>
            <span>- Ahmed R.</span>
            <div class="flex items-center mt-4">
              <img src="assets/images/profile/2.jpg" alt="Ahmed R." class="w-10 h-10 rounded-full mr-3" />
              <div>
                <span class="block text-teal-600">Ahmed R.</span>
                <div class="flex space-x-3 text-teal-600">
                  <a href="#" class="text-xl"><i class="bx bxl-facebook"></i></a>
                  <a href="#" class="text-xl"><i class="bx bxl-twitter"></i></a>
                  <a href="#" class="text-xl"><i class="bx bxl-instagram"></i></a>
                </div>
              </div>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="absolute bottom-4 right-4 text-teal-600 w-10 h-10" viewBox="0 0 975.036 975.036">
              <path
                d="M925.036 57.197h-304c-27.6 0-50 22.4-50 50v304c0 27.601 22.4 50 50 50h145.5c-1.9 79.601-20.4 143.3-55.4 191.2-27.6 37.8-69.399 69.1-125.3 93.8-25.7 11.3-36.8 41.7-24.8 67.101l36 76c11.6 24.399 40.3 35.1 65.1 24.399 66.2-28.6 122.101-64.8 167.7-108.8 55.601-53.7 93.7-114.3 114.3-181.9 20.601-67.6 30.9-159.8 30.9-276.8v-239c0-27.599-22.401-50-50-50zM106.036 913.497c65.4-28.5 121-64.699 166.9-108.6 56.1-53.7 94.4-114.1 115-181.2 20.6-67.1 30.899-159.6 30.899-277.5v-239c0-27.6-22.399-50-50-50h-304c-27.6 0-50 22.4-50 50v304c0 27.601 22.4 50 50 50h145.5c-1.9 79.601-20.4 143.3-55.4 191.2-27.6 37.8-69.4 69.1-125.3 93.8-25.7 11.3-36.8 41.7-24.8 67.101l35.9 75.8c11.601 24.399 40.501 35.2 65.301 24.399z"></path>
            </svg>
          </div>

          <div>
            <div class="items-center">
              <div class="text-yellow-500">
                <i class="bx bx-star"></i>
                <i class="bx bx-star"></i>
                <i class="bx bx-star"></i>
                <i class="bx bx-star"></i>
                <i class="bx bx-star"></i>
              </div>
              <div class="flex items-center">
                <i class="bx bx-quote-left text-teal-600 mr-2 text-xl"></i>
                <p>"Amazing experience, felt spiritually fulfilled!"</p>
              </div>
            </div>
            <span>- Furqan F.</span>
            <div class="flex items-center mt-4">
              <img src="assets/images/profile/3.jpg" alt="Furqan F." class="w-10 h-10 rounded-full mr-3" />
              <div>
                <span class="block text-teal-600">Furqan F.</span>
                <div class="flex space-x-3 text-teal-600">
                  <a href="#" class="text-xl"><i class="bx bxl-facebook"></i></a>
                  <a href="#" class="text-xl"><i class="bx bxl-twitter"></i></a>
                  <a href="#" class="text-xl"><i class="bx bxl-instagram"></i></a>
                </div>
              </div>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="absolute bottom-4 right-4 text-teal-600 w-10 h-10" viewBox="0 0 975.036 975.036">
              <path
                d="M925.036 57.197h-304c-27.6 0-50 22.4-50 50v304c0 27.601 22.4 50 50 50h145.5c-1.9 79.601-20.4 143.3-55.4 191.2-27.6 37.8-69.399 69.1-125.3 93.8-25.7 11.3-36.8 41.7-24.8 67.101l36 76c11.6 24.399 40.3 35.1 65.1 24.399 66.2-28.6 122.101-64.8 167.7-108.8 55.601-53.7 93.7-114.3 114.3-181.9 20.601-67.6 30.9-159.8 30.9-276.8v-239c0-27.599-22.401-50-50-50zM106.036 913.497c65.4-28.5 121-64.699 166.9-108.6 56.1-53.7 94.4-114.1 115-181.2 20.6-67.1 30.899-159.6 30.899-277.5v-239c0-27.6-22.399-50-50-50h-304c-27.6 0-50 22.4-50 50v304c0 27.601 22.4 50 50 50h145.5c-1.9 79.601-20.4 143.3-55.4 191.2-27.6 37.8-69.4 69.1-125.3 93.8-25.7 11.3-36.8 41.7-24.8 67.101l35.9 75.8c11.601 24.399 40.501 35.2 65.301 24.399z"></path>
            </svg>
          </div>
        </div>
      </div>
    </div>
  </section>
  <style>

  </style>
  <!----
  --------------- >Testimonials Section
  --->
  <?php include 'includes/footer.php' ?>

  <!----
  --------------- >JS Links
  --->
  <script src="assets/aos-master/dist/aos.js"></script>
  <!----
  --------------- >AOS Initialization
  --->
  <script>
    AOS.init({
      duration: 1000,
      // once: true
    });
  </script>
  <!------------- AOS end -------------->

  <!----
  --------------- >Slick Slider Start
  --->
  <script>
    $(document).ready(function() {
      $("#testimonialSlider").slick({
        autoplay: true,
        autoplaySpeed: 3000,
        arrows: false,
        dots: true,
        fade: true,
      });
    });
  </script>
  <!----
    --------------- >Slick Slider End
    --->
</body>

</html>