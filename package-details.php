<?php
include "connection/connection.php";
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
  exit();
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

  if (empty($package)) {
    die("Package not found");
  }
}

// Handle Booking Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_POST['package_id']) || empty($_POST['package_id']) || !isset($_POST['total_price'])) {
    $_SESSION['error'] = "Invalid package data";
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
  }

  $user_id = $_SESSION['user_id'];
  $package_id = intval($_POST['package_id']);
  $total_price = floatval($_POST['total_price']);

  // Check for existing booking
  $check_query = "SELECT id FROM package_booking WHERE user_id = ? AND package_id = ? AND payment_status IN ('pending', 'paid', 'confirmed')";
  $stmt = $conn->prepare($check_query);
  $stmt->bind_param("ii", $user_id, $package_id);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows > 0) {
    $_SESSION['error'] = "You have already booked this package.";
    header("Location: my_bookings.php");
    exit();
  }

  // Insert new booking
  $query = "INSERT INTO package_booking (user_id, package_id, total_price, payment_status, booking_date) 
              VALUES (?, ?, ?, 'confirmed', NOW())";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("iid", $user_id, $package_id, $total_price);

  if ($stmt->execute()) {
    $booking_id = $conn->insert_id;
    $_SESSION['success'] = "Booking confirmed successfully!";
    header("Location: booking_confirmation.php?id=" . $booking_id);
    exit();
  } else {
    $_SESSION['error'] = "Booking failed. Please try again.";
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Package Details | AGRSoft Umrah</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f5f7fa;
    }

    .hero-gradient {
      background: linear-gradient(to right, rgba(0, 128, 128, 0.9), rgba(0, 0, 0, 0.6));
    }

    .package-card {
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
    }

    .package-card:hover {
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
      transform: translateY(-5px);
    }

    .feature-icon {
      background: linear-gradient(135deg, #20c997, #0d6efd);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .book-btn {
      background: linear-gradient(135deg, #20c997, #0d6efd);
      transition: all 0.3s ease;
    }

    .book-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 7px 15px rgba(13, 110, 253, 0.3);
    }

    .section-title::after {
      content: '';
      display: block;
      width: 50px;
      height: 3px;
      background: linear-gradient(to right, #20c997, #0d6efd);
      margin-top: 10px;
    }

    .tab-btn {
      position: relative;
      transition: all 0.3s ease;
    }

    .tab-btn.active::after {
      content: '';
      position: absolute;
      bottom: -2px;
      left: 0;
      width: 100%;
      height: 3px;
      background: linear-gradient(to right, #20c997, #0d6efd);
    }

    .dot-separator {
      display: inline-block;
      width: 4px;
      height: 4px;
      border-radius: 50%;
      background-color: currentColor;
      margin: 0 10px;
      opacity: 0.5;
    }

    .glass-card {
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .info-card {
      border-left: 4px solid #20c997;
      transition: all 0.3s ease;
    }

    .info-card:hover {
      transform: translateX(5px);
    }
  </style>
</head>

<body>
  <?php include 'includes/navbar.php'; ?>

  <!-- Display SweetAlert notifications -->
  <?php if (isset($_SESSION['error'])): ?>
    <script>
      Swal.fire({
        title: 'Error',
        text: '<?= $_SESSION['error'] ?>',
        icon: 'error',
        confirmButtonText: 'OK'
      });
    </script>
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>

  <!-- Hero Section with Parallax Effect -->
  <div class="relative h-[500px] overflow-hidden -mt-2">
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('admin/<?= htmlspecialchars($package['package_image'] ?? 'default.jpg') ?>'); transform: translateZ(-1px) scale(1.2);"></div>
    <div class="absolute inset-0 hero-gradient"></div>
    <div class="absolute inset-0 flex flex-col justify-end p-8 md:p-16">
      <div class="container mx-auto max-w-6xl">
        <div class="glass-card p-6 md:p-8 rounded-xl max-w-2xl">
          <span class="inline-block px-4 py-1 bg-gradient-to-r from-teal-500 to-blue-500 text-white rounded-full text-sm font-medium mb-4">
            <?= htmlspecialchars($package['package_type'] ?? 'N/A') ?>
          </span>
          <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-3">
            <?= htmlspecialchars($package['title'] ?? 'Unknown Package') ?>
          </h1>
          <div class="flex flex-wrap items-center text-gray-600 mt-4">
            <div class="flex items-center mr-6 mb-2">
              <i class="fas fa-plane-departure text-teal-500 mr-2"></i>
              <span><?= htmlspecialchars($package['airline'] ?? 'N/A') ?></span>
            </div>
            <div class="flex items-center mr-6 mb-2">
              <i class="fas fa-ticket-alt text-teal-500 mr-2"></i>
              <span><?= htmlspecialchars($package['flight_class'] ?? 'N/A') ?></span>
            </div>
            <div class="flex items-center mb-2">
              <i class="fas fa-tag text-teal-500 mr-2"></i>
              <span class="text-2xl font-bold text-teal-600">Rs<?= isset($package['price']) ? number_format($package['price'], 2) : '0.00' ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <div class="container mx-auto max-w-6xl px-4 -mt-16 relative z-10 mb-16">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <!-- Main Package Information Column -->
      <div class="md:col-span-2">
        <!-- Package Details Content -->
        <div class="bg-white rounded-b-xl p-6 md:p-8 shadow-md mb-8">
          <!-- Overview Section -->
          <section id="overview">
            <h2 class="section-title text-2xl font-bold text-gray-800 mb-6">Package Overview</h2>

            <!-- Flight Details -->
            <div class="bg-gray-50 rounded-xl p-6 mb-8">
              <h3 class="flex items-center text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-plane text-2xl feature-icon mr-3"></i>
                Flight Details
              </h3>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="info-card bg-white p-4 rounded-lg shadow-sm">
                  <p class="text-gray-500 text-sm mb-1">Airline</p>
                  <p class="font-semibold"><?= htmlspecialchars($package['airline'] ?? 'N/A') ?></p>
                </div>
                <div class="info-card bg-white p-4 rounded-lg shadow-sm">
                  <p class="text-gray-500 text-sm mb-1">Flight Class</p>
                  <p class="font-semibold"><?= htmlspecialchars($package['flight_class'] ?? 'N/A') ?></p>
                </div>
              </div>
            </div>

            <!-- Description Section -->
            <div class="mb-8">
              <h3 class="flex items-center text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-info-circle text-2xl feature-icon mr-3"></i>
                Package Description
              </h3>
              <div class="bg-gray-50 rounded-xl p-6">
                <p class="text-gray-700 leading-relaxed">
                  <?= nl2br(htmlspecialchars($package['description'] ?? 'No description available.')) ?>
                </p>
              </div>
            </div>

            <!-- Inclusions Section -->
            <div class="mb-8">
              <h3 class="flex items-center text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-clipboard-check text-2xl feature-icon mr-3"></i>
                What's Included
              </h3>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-xl p-5">
                  <ul class="space-y-3">
                    <li class="flex items-start">
                      <div class="flex-shrink-0 w-6 h-6 bg-gradient-to-r from-teal-500 to-blue-500 rounded-full flex items-center justify-center mt-0.5 mr-3">
                        <i class="fas fa-check text-white text-xs"></i>
                      </div>
                      <span class="text-gray-700">Return flights with <?= htmlspecialchars($package['airline'] ?? 'N/A') ?></span>
                    </li>
                    <li class="flex items-start">
                      <div class="flex-shrink-0 w-6 h-6 bg-gradient-to-r from-teal-500 to-blue-500 rounded-full flex items-center justify-center mt-0.5 mr-3">
                        <i class="fas fa-check text-white text-xs"></i>
                      </div>
                      <span class="text-gray-700">Hotel accommodation</span>
                    </li>
                    <li class="flex items-start">
                      <div class="flex-shrink-0 w-6 h-6 bg-gradient-to-r from-teal-500 to-blue-500 rounded-full flex items-center justify-center mt-0.5 mr-3">
                        <i class="fas fa-check text-white text-xs"></i>
                      </div>
                      <span class="text-gray-700">Airport transfers</span>
                    </li>
                  </ul>
                </div>
                <div class="bg-gray-50 rounded-xl p-5">
                  <ul class="space-y-3">
                    <li class="flex items-start">
                      <div class="flex-shrink-0 w-6 h-6 bg-gradient-to-r from-teal-500 to-blue-500 rounded-full flex items-center justify-center mt-0.5 mr-3">
                        <i class="fas fa-check text-white text-xs"></i>
                      </div>
                      <span class="text-gray-700">Transport between holy sites</span>
                    </li>
                    <li class="flex items-start">
                      <div class="flex-shrink-0 w-6 h-6 bg-gradient-to-r from-teal-500 to-blue-500 rounded-full flex items-center justify-center mt-0.5 mr-3">
                        <i class="fas fa-check text-white text-xs"></i>
                      </div>
                      <span class="text-gray-700">Experienced guide assistance</span>
                    </li>
                    <li class="flex items-start">
                      <div class="flex-shrink-0 w-6 h-6 bg-gradient-to-r from-teal-500 to-blue-500 rounded-full flex items-center justify-center mt-0.5 mr-3">
                        <i class="fas fa-check text-white text-xs"></i>
                      </div>
                      <span class="text-gray-700">24/7 Support throughout your journey</span>
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- Additional Features -->
            <div class="bg-gradient-to-r from-teal-50 to-blue-50 rounded-xl p-6 border border-teal-100">
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Special Features</h3>
              <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                <div class="flex items-start">
                  <div class="flex-shrink-0 text-2xl feature-icon mr-3">
                    <i class="fas fa-pray"></i>
                  </div>
                  <div>
                    <h4 class="font-medium text-gray-800">Guided Prayers</h4>
                    <p class="text-sm text-gray-600">Assistance during rituals</p>
                  </div>
                </div>
                <div class="flex items-start">
                  <div class="flex-shrink-0 text-2xl feature-icon mr-3">
                    <i class="fas fa-utensils"></i>
                  </div>
                  <div>
                    <h4 class="font-medium text-gray-800">Halal Food</h4>
                    <p class="text-sm text-gray-600">Authentic cuisine</p>
                  </div>
                </div>
                <div class="flex items-start">
                  <div class="flex-shrink-0 text-2xl feature-icon mr-3">
                    <i class="fas fa-language"></i>
                  </div>
                  <div>
                    <h4 class="font-medium text-gray-800">Multilingual Guide</h4>
                    <p class="text-sm text-gray-600">For your convenience</p>
                  </div>
                </div>
              </div>
            </div>
          </section>
        </div>
      </div>

      <!-- Sidebar Column -->
      <div class="md:col-span-1">
        <!-- Booking Card -->
        <div class="sticky top-24 bg-white rounded-xl shadow-md p-6 md:p-8 mb-8">
          <div class="mb-6">
            <h3 class="text-xl font-bold text-gray-800 mb-2">Book This Package</h3>
            <p class="text-gray-600 text-sm">Secure your spot for this blessed journey</p>
          </div>

          <div class="mb-6">
            <p class="text-gray-500 text-sm mb-1">Price per person</p>
            <div class="text-3xl font-bold text-teal-600">
              Rs<?= isset($package['price']) ? number_format($package['price'], 2) : '0.00' ?>
            </div>
          </div>

          <div class="mb-6">
            <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
              <span>Airline</span>
              <span class="font-medium"><?= htmlspecialchars($package['airline'] ?? 'N/A') ?></span>
            </div>
            <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
              <span>Class</span>
              <span class="font-medium"><?= htmlspecialchars($package['flight_class'] ?? 'N/A') ?></span>
            </div>
            <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
              <span>Type</span>
              <span class="font-medium"><?= htmlspecialchars($package['package_type'] ?? 'N/A') ?></span>
            </div>
          </div>

          <form method="POST" action="">
            <input type="hidden" name="package_id" value="<?= htmlspecialchars($package['id'] ?? '0') ?>">
            <input type="hidden" name="total_price" value="<?= htmlspecialchars($package['price'] ?? '0.00') ?>">
            <button type="submit" class="book-btn w-full py-4 text-white font-semibold rounded-lg shadow-md flex items-center justify-center">
              <i class="fas fa-check-circle mr-2"></i> Book Now
            </button>
          </form>

          <div class="mt-6 text-center">
            <p class="text-sm text-gray-500">Instant booking confirmation</p>
          </div>
        </div>

        <!-- Need Help Card -->
        <!-- <div class="bg-white rounded-xl shadow-md p-6">
          <h3 class="text-xl font-bold text-gray-800 mb-4">Need Help?</h3>
          <div class="flex items-start mb-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-r from-teal-500 to-blue-500 rounded-full flex items-center justify-center mr-3">
              <i class="fas fa-phone-alt text-white"></i>
            </div>
            <div>
              <p class="text-sm text-gray-500">Call us at</p>
              <p class="font-medium">+1 (800) 123-4567</p>
            </div>
          </div>
          <div class="flex items-start">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-r from-teal-500 to-blue-500 rounded-full flex items-center justify-center mr-3">
              <i class="fas fa-envelope text-white"></i>
            </div>
            <div>
              <p class="text-sm text-gray-500">Email us at</p>
              <p class="font-medium">support@agrsoft.com</p>
            </div>
          </div>
        </div> -->
      </div>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      // Tab functionality
      const tabButtons = document.querySelectorAll('.tab-btn');

      tabButtons.forEach(button => {
        button.addEventListener('click', () => {
          tabButtons.forEach(btn => {
            btn.classList.remove('active');
            btn.classList.add('text-gray-500');
            btn.classList.remove('text-gray-800');
          });

          button.classList.add('active');
          button.classList.remove('text-gray-500');
          button.classList.add('text-gray-800');
        });
      });
    });
  </script>
</body>

</html>