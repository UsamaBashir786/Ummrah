<?php
session_start();

include "connection/connection.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
</head>

<body class="bg-gray-50 font-sans">
  <?php include 'includes/navbar.php' ?>

  <div class="my-12">&nbsp;</div>
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

  <!-- Footer -->
  <footer class="bg-teal-800 py-6 text-white">
    <div class="container mx-auto text-center">
      <p>&copy; 2025 Umrah Journey. All Rights Reserved.</p>
    </div>
  </footer>
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