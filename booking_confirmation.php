<?php
include "connection/connection.php";
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

// Check if booking ID is provided
if (!isset($_GET['id'])) {
  header("Location: index.php");
  exit();
}

$booking_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Get booking details
$query = "SELECT pb.*, p.title, p.package_type, p.airline, p.flight_class, p.package_image 
          FROM package_booking pb
          JOIN packages p ON pb.package_id = p.id
          WHERE pb.id = ? AND pb.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
  header("Location: index.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Booking Confirmation | AGRSoft Umrah</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f5f7fa;
    }

    .confirmation-card {
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
    }

    .confirmation-header {
      background: linear-gradient(135deg, #20c997, #0d6efd);
    }

    .checkmark-circle {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.2);
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .info-card {
      border-left: 4px solid #20c997;
      transition: all 0.3s ease;
    }

    .info-card:hover {
      transform: translateX(5px);
    }

    .timeline {
      position: relative;
      padding-left: 30px;
    }

    .timeline::before {
      content: '';
      position: absolute;
      left: 10px;
      top: 0;
      bottom: 0;
      width: 2px;
      background: #20c997;
    }

    .timeline-item {
      position: relative;
      margin-bottom: 20px;
    }

    .timeline-item::before {
      content: '';
      position: absolute;
      left: -30px;
      top: 5px;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      background: #20c997;
      border: 4px solid #fff;
    }
  </style>
</head>

<body>
  <?php include 'includes/navbar.php'; ?>

  <!-- Main Content -->
  <div class="container mx-auto px-4 py-12">
    <div class="max-w-4xl mx-auto">
      <!-- Confirmation Card -->
      <div class="confirmation-card bg-white mb-12">
        <!-- Header -->
        <div class="confirmation-header text-white p-8 text-center">
          <div class="flex justify-center mb-4">
            <div class="checkmark-circle">
              <i class="fas fa-check text-white text-4xl"></i>
            </div>
          </div>
          <h1 class="text-3xl font-bold mb-2">Booking Confirmed!</h1>
          <p class="text-white/90">Your Umrah package has been successfully booked</p>
        </div>

        <!-- Body -->
        <div class="p-8">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <!-- Booking Summary -->
            <div>
              <h2 class="text-xl font-bold text-gray-800 mb-4">Booking Summary</h2>
              <div class="space-y-4">
                <div class="info-card bg-gray-50 p-4 rounded-lg">
                  <p class="text-gray-500 text-sm mb-1">Booking Reference</p>
                  <p class="font-semibold">#<?= str_pad($booking['id'], 6, '0', STR_PAD_LEFT) ?></p>
                </div>
                <div class="info-card bg-gray-50 p-4 rounded-lg">
                  <p class="text-gray-500 text-sm mb-1">Package Name</p>
                  <p class="font-semibold"><?= htmlspecialchars($booking['title']) ?></p>
                </div>
                <div class="info-card bg-gray-50 p-4 rounded-lg">
                  <p class="text-gray-500 text-sm mb-1">Package Type</p>
                  <p class="font-semibold"><?= htmlspecialchars($booking['package_type']) ?></p>
                </div>
              </div>
            </div>

            <!-- Payment Details -->
            <div>
              <h2 class="text-xl font-bold text-gray-800 mb-4">Payment Details</h2>
              <div class="space-y-4">
                <div class="info-card bg-gray-50 p-4 rounded-lg">
                  <p class="text-gray-500 text-sm mb-1">Total Amount</p>
                  <p class="font-semibold text-teal-600">$<?= number_format($booking['total_price'], 2) ?></p>
                </div>
                <div class="info-card bg-gray-50 p-4 rounded-lg">
                  <p class="text-gray-500 text-sm mb-1">Payment Status</p>
                  <p class="font-semibold capitalize"><?= htmlspecialchars($booking['payment_status']) ?></p>
                </div>
                <div class="info-card bg-gray-50 p-4 rounded-lg">
                  <p class="text-gray-500 text-sm mb-1">Booking Date</p>
                  <p class="font-semibold"><?= date('F j, Y', strtotime($booking['booking_date'])) ?></p>
                </div>
              </div>
            </div>
          </div>

          <!-- Package Image -->
          <div class="mb-8">
            <div class="rounded-xl overflow-hidden">
              <img src="admin/<?= htmlspecialchars($booking['package_image']) ?>" alt="Package Image" class="w-full h-64 object-cover">
            </div>
          </div>

          <!-- Next Steps -->
          <div class="mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">What's Next?</h2>
            <div class="timeline">
              <div class="timeline-item">
                <h3 class="font-semibold text-gray-800 mb-1">1. Payment Confirmation</h3>
                <p class="text-gray-600">Our team will contact you within 24 hours to confirm payment details.</p>
              </div>
              <div class="timeline-item">
                <h3 class="font-semibold text-gray-800 mb-1">2. Document Submission</h3>
                <p class="text-gray-600">You'll receive an email with instructions for submitting required travel documents.</p>
              </div>
              <div class="timeline-item">
                <h3 class="font-semibold text-gray-800 mb-1">3. Travel Itinerary</h3>
                <p class="text-gray-600">Your complete travel itinerary will be sent 2 weeks before departure.</p>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-col sm:flex-row gap-4">
            <a href="user/bookings-packages.php" class="bg-gray-800 hover:bg-gray-700 text-white font-semibold py-3 px-6 rounded-lg text-center transition">
              <i class="fas fa-list mr-2"></i> View All Bookings
            </a>
            <a href="index.php" class="bg-gradient-to-r from-teal-500 to-blue-500 hover:from-teal-600 hover:to-blue-600 text-white font-semibold py-3 px-6 rounded-lg text-center transition">
              <i class="fas fa-home mr-2"></i> Back to Home
            </a>
            <button onclick="window.print()" class="border border-gray-300 hover:bg-gray-100 text-gray-800 font-semibold py-3 px-6 rounded-lg transition">
              <i class="fas fa-print mr-2"></i> Print Confirmation
            </button>
          </div>
        </div>
      </div>

      <!-- Help Section -->
      <div class="bg-white rounded-xl shadow-md p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Need Help?</h2>
        <p class="text-gray-600 mb-6">Our customer service team is available to assist you with any questions about your booking.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="bg-gray-50 rounded-xl p-6">
            <div class="flex items-start">
              <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-r from-teal-500 to-blue-500 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-phone-alt text-white"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-800 mb-1">Call Us</h3>
                <p class="text-gray-600 mb-2">+1 (800) 123-4567</p>
                <p class="text-sm text-gray-500">Mon-Fri: 9AM-6PM (GMT)</p>
              </div>
            </div>
          </div>

          <div class="bg-gray-50 rounded-xl p-6">
            <div class="flex items-start">
              <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-r from-teal-500 to-blue-500 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-envelope text-white"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-800 mb-1">Email Us</h3>
                <p class="text-gray-600 mb-2">bookings@agrsoft.com</p>
                <p class="text-sm text-gray-500">Response within 12 hours</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>

  <script>
    // Show success message if redirected from booking page
    document.addEventListener("DOMContentLoaded", function() {
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.has('success')) {
        Swal.fire({
          title: 'Booking Successful!',
          text: 'Your Umrah package has been booked successfully.',
          icon: 'success',
          confirmButtonText: 'OK'
        });
      }
    });
  </script>
</body>

</html>