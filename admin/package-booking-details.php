<?php
require_once 'connection/connection.php';

// Check if booking ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
  header("Location: manage-bookings.php");
  exit;
}

$booking_id = intval($_GET['id']);

// Fetch booking details with package information - fixed image column issue
$sql = "SELECT pb.*, p.title as package_name, p.description, p.package_type, p.price as base_price, 
               p.duration, p.destination, 
               u.id as user_id, u.full_name, u.email, u.phone_number, u.profile_image
        FROM package_booking pb 
        JOIN packages p ON pb.package_id = p.id 
        JOIN users u ON pb.user_id = u.id
        WHERE pb.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  header("Location: manage-bookings.php");
  exit;
}

$booking = $result->fetch_assoc();
$stmt->close();

// Check if package has an image field and fetch it separately if needed
$package_image = null;
$image_sql = "SHOW COLUMNS FROM packages LIKE 'image'";
$image_result = $conn->query($image_sql);
if ($image_result->num_rows > 0) {
  // If the image column exists, fetch it
  $image_sql = "SELECT image FROM packages WHERE id = ?";
  $stmt = $conn->prepare($image_sql);
  $stmt->bind_param("i", $booking['package_id']);
  $stmt->execute();
  $image_result = $stmt->get_result();
  if ($image_result->num_rows > 0) {
    $image_row = $image_result->fetch_assoc();
    $package_image = $image_row['image'];
  }
  $stmt->close();
}

// Format dates for display
$booking_date = new DateTime($booking['booking_date']);
$formatted_booking_date = $booking_date->format('F d, Y h:i A');

// Check if travel_date is set before attempting to use it
$formatted_travel_date = 'Not specified';
$formatted_return_date = 'Not specified';

if (!empty($booking['travel_date'])) {
  $travel_date = new DateTime($booking['travel_date']);
  $formatted_travel_date = $travel_date->format('F d, Y');

  // Check if return_date is set
  if (!empty($booking['return_date'])) {
    $return_date = new DateTime($booking['return_date']);
    $formatted_return_date = $return_date->format('F d, Y');
  } else if (!empty($booking['duration']) && is_numeric($booking['duration'])) {
    // If return date is not set, calculate based on duration
    $return_date = clone $travel_date;
    $return_date->modify('+' . $booking['duration'] . ' days');
    $formatted_return_date = $return_date->format('F d, Y');
  }
}

// Get travelers information
$travelers = [];
if (!empty($booking['travelers_info'])) {
  $travelers = json_decode($booking['travelers_info'], true);
}

// Check if payments table exists before trying to access it
$has_payments_table = false;
$payment_info = null;
$table_check_sql = "SHOW TABLES LIKE 'payments'";
$table_check_result = $conn->query($table_check_sql);
if ($table_check_result && $table_check_result->num_rows > 0) {
  $has_payments_table = true;

  // Get payment information
  $payment_sql = "SELECT * FROM payments WHERE booking_id = ? AND booking_type = 'package'";
  $stmt = $conn->prepare($payment_sql);
  $stmt->bind_param("i", $booking_id);
  $stmt->execute();
  $payment_result = $stmt->get_result();

  if ($payment_result && $payment_result->num_rows > 0) {
    $payment_info = $payment_result->fetch_assoc();
  }
  $stmt->close();
}

// Format status class
$status = strtolower($booking['status']);
$status_class = 'bg-yellow-100 text-yellow-800';
$status_icon = 'fa-clock';

switch ($status) {
  case 'confirmed':
    $status_class = 'bg-green-100 text-green-800';
    $status_icon = 'fa-check-circle';
    break;
  case 'completed':
    $status_class = 'bg-blue-100 text-blue-800';
    $status_icon = 'fa-flag-checkered';
    break;
  case 'cancelled':
  case 'canceled':
    $status_class = 'bg-red-100 text-red-800';
    $status_icon = 'fa-times-circle';
    break;
}

// Format payment status
$payment_status = $booking['payment_status'] ?? 'pending';
$payment_class = 'bg-yellow-100 text-yellow-800';
$payment_icon = 'fa-clock';

switch (strtolower($payment_status)) {
  case 'paid':
    $payment_class = 'bg-green-100 text-green-800';
    $payment_icon = 'fa-check-circle';
    break;
  case 'refunded':
    $payment_class = 'bg-blue-100 text-blue-800';
    $payment_icon = 'fa-undo';
    break;
  case 'failed':
    $payment_class = 'bg-red-100 text-red-800';
    $payment_icon = 'fa-times-circle';
    break;
}

// Format inclusions and exclusions
// First check if these columns exist in the packages table
$has_inclusions = false;
$has_exclusions = false;
$inclusions = [];
$exclusions = [];

$incl_sql = "SHOW COLUMNS FROM packages LIKE 'inclusions'";
$incl_result = $conn->query($incl_sql);
if ($incl_result->num_rows > 0) {
  $has_inclusions = true;
  // Fetch inclusions
  $incl_sql = "SELECT inclusions FROM packages WHERE id = ?";
  $stmt = $conn->prepare($incl_sql);
  $stmt->bind_param("i", $booking['package_id']);
  $stmt->execute();
  $incl_result = $stmt->get_result();
  if ($incl_result->num_rows > 0) {
    $incl_row = $incl_result->fetch_assoc();
    if (!empty($incl_row['inclusions'])) {
      $inclusions = explode(',', $incl_row['inclusions']);
    }
  }
  $stmt->close();
}

$excl_sql = "SHOW COLUMNS FROM packages LIKE 'exclusions'";
$excl_result = $conn->query($excl_sql);
if ($excl_result->num_rows > 0) {
  $has_exclusions = true;
  // Fetch exclusions
  $excl_sql = "SELECT exclusions FROM packages WHERE id = ?";
  $stmt = $conn->prepare($excl_sql);
  $stmt->bind_param("i", $booking['package_id']);
  $stmt->execute();
  $excl_result = $stmt->get_result();
  if ($excl_result->num_rows > 0) {
    $excl_row = $excl_result->fetch_assoc();
    if (!empty($excl_row['exclusions'])) {
      $exclusions = explode(',', $excl_row['exclusions']);
    }
  }
  $stmt->close();
}

// Get package customizations if any
$customizations = [];
if (!empty($booking['customizations'])) {
  $customizations = json_decode($booking['customizations'], true);
}

// Check if booking_notes table exists
$has_notes_table = false;
$table_check_sql = "SHOW TABLES LIKE 'booking_notes'";
$table_check_result = $conn->query($table_check_sql);
if ($table_check_result && $table_check_result->num_rows > 0) {
  $has_notes_table = true;
}

// Check if booking_status_history table exists
$has_status_history_table = false;
$table_check_sql = "SHOW TABLES LIKE 'booking_status_history'";
$table_check_result = $conn->query($table_check_sql);
if ($table_check_result && $table_check_result->num_rows > 0) {
  $has_status_history_table = true;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <style>
    .booking-header {
      background-image: linear-gradient(to right, #0891b2, #0e7490);
    }

    .info-card {
      transition: all 0.3s ease;
    }

    .info-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .traveler-card {
      transition: all 0.2s ease;
    }

    .traveler-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    .timeline-container {
      padding-left: 30px;
      margin-left: 10px;
      border-left: 2px solid #e5e7eb;
      position: relative;
    }

    .timeline-dot {
      width: 16px;
      height: 16px;
      border-radius: 50%;
      position: absolute;
      left: -9px;
      background-color: #0891b2;
    }

    .timeline-item {
      margin-bottom: 24px;
      position: relative;
    }

    .status-pill {
      font-size: 0.75rem;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-weight: 600;
    }

    .payment-method-card {
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 12px;
      margin-bottom: 8px;
    }

    /* Print styles */
    @media print {
      .no-print {
        display: none !important;
      }

      .print-only {
        display: block !important;
      }

      body {
        font-size: 12pt;
        color: #000;
      }
    }
  </style>
</head>

<body class="bg-gray-100">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main flex-1 flex flex-col overflow-hidden">
      <!-- Navbar -->
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center no-print">
        <div class="flex items-center">
          <button class="md:hidden text-gray-800 mr-4" id="menu-btn">
            <i class="fas fa-bars"></i>
          </button>
          <h1 class="text-xl font-semibold">
            <i class="text-teal-600 fa fa-box mr-2"></i> Package Booking Details
          </h1>
        </div>
        <div class="flex space-x-3">
          <button onclick="printBookingDetails()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-print mr-2"></i> Print
          </button>
          <button onclick="updateBookingStatus(<?php echo $booking_id; ?>)" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-edit mr-2"></i> Update Status
          </button>
          <button onclick="window.history.back()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back
          </button>
        </div>
      </div>

      <div class="flex-1 overflow-auto p-4 md:p-6">
        <!-- Booking Header -->
        <div class="booking-header bg-gradient-to-r from-cyan-600 to-teal-700 rounded-xl shadow-lg p-6 mb-6 text-white">
          <div class="flex flex-col md:flex-row justify-between">
            <div>
              <div class="flex items-center">
                <div class="bg-white/20 rounded-full p-2 mr-3">
                  <i class="fas fa-box text-2xl"></i>
                </div>
                <div>
                  <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($booking['package_name']); ?></h1>
                  <p class="text-white/80"><?php echo htmlspecialchars($booking['destination']); ?></p>
                </div>
              </div>
              <div class="mt-4 flex flex-wrap gap-3">
                <div class="bg-white/20 rounded-lg px-3 py-2 flex items-center">
                  <i class="fas fa-calendar-alt mr-2"></i>
                  <div>
                    <p class="text-xs text-white/80">Travel Date</p>
                    <p class="font-medium"><?php echo $formatted_travel_date; ?></p>
                  </div>
                </div>
                <div class="bg-white/20 rounded-lg px-3 py-2 flex items-center">
                  <i class="fas fa-calendar-check mr-2"></i>
                  <div>
                    <p class="text-xs text-white/80">Return Date</p>
                    <p class="font-medium"><?php echo $formatted_return_date; ?></p>
                  </div>
                </div>
                <div class="bg-white/20 rounded-lg px-3 py-2 flex items-center">
                  <i class="fas fa-users mr-2"></i>
                  <div>
                    <p class="text-xs text-white/80">Travelers</p>
                    <p class="font-medium"><?php echo intval($booking['number_of_travelers'] ?? 1); ?> persons</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="mt-4 md:mt-0 md:text-right">
              <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $status_class; ?>">
                <i class="fas <?php echo $status_icon; ?> mr-1"></i>
                <?php echo ucfirst($booking['status']); ?>
              </div>
              <div class="mt-2">
                <p class="text-white/80">Booking Reference</p>
                <p class="text-xl font-bold"><?php echo $booking['booking_reference'] ?? 'N/A'; ?></p>
              </div>
              <div class="mt-2">
                <p class="text-white/80">Total Amount</p>
                <p class="text-2xl font-bold">$<?php echo number_format((float)($booking['total_price'] ?? 0), 2); ?></p>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Left Column - Booking and Package Details -->
          <div class="lg:col-span-2">
            <!-- Package Details -->
            <div class="info-card bg-white rounded-xl shadow-md p-5 mb-6">
              <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-box-open text-teal-600 mr-2"></i> Package Details
              </h2>

              <?php if (!empty($package_image)): ?>
                <div class="mb-4">
                  <img src="../<?php echo htmlspecialchars($package_image); ?>" alt="<?php echo htmlspecialchars($booking['package_name']); ?>"
                    class="w-full h-64 object-cover rounded-lg">
                </div>
              <?php endif; ?>

              <div class="mt-4">
                <h3 class="font-medium text-lg text-gray-800"><?php echo htmlspecialchars($booking['package_name']); ?></h3>
                <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($booking['description'] ?? 'No description available.'); ?></p>
              </div>

              <div class="mt-4 grid grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-gray-500">Package Type</p>
                  <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($booking['package_type'] ?? 'Standard'); ?></p>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Destination</p>
                  <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($booking['destination'] ?? 'Not specified'); ?></p>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Duration</p>
                  <p class="text-gray-800 font-medium"><?php echo intval($booking['duration'] ?? 0); ?> days</p>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Base Price</p>
                  <p class="text-gray-800 font-medium">$<?php echo number_format((float)($booking['base_price'] ?? 0), 2); ?></p>
                </div>
              </div>

              <?php if ($has_inclusions || $has_exclusions): ?>
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                  <?php if ($has_inclusions && !empty($inclusions)): ?>
                    <div>
                      <h4 class="font-medium text-gray-800 mb-2">Inclusions</h4>
                      <ul class="space-y-1">
                        <?php foreach ($inclusions as $inclusion): ?>
                          <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span class="text-gray-600"><?php echo htmlspecialchars(trim($inclusion)); ?></span>
                          </li>
                        <?php endforeach; ?>
                      </ul>
                    </div>
                  <?php endif; ?>

                  <?php if ($has_exclusions && !empty($exclusions)): ?>
                    <div>
                      <h4 class="font-medium text-gray-800 mb-2">Exclusions</h4>
                      <ul class="space-y-1">
                        <?php foreach ($exclusions as $exclusion): ?>
                          <li class="flex items-start">
                            <i class="fas fa-times-circle text-red-500 mt-1 mr-2"></i>
                            <span class="text-gray-600"><?php echo htmlspecialchars(trim($exclusion)); ?></span>
                          </li>
                        <?php endforeach; ?>
                      </ul>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>

            <!-- Booking Details -->
            <div class="info-card bg-white rounded-xl shadow-md p-5 mb-6">
              <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-calendar-check text-teal-600 mr-2"></i> Booking Details
              </h2>

              <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div>
                  <p class="text-sm text-gray-500">Booking ID</p>
                  <p class="text-gray-800 font-medium">#<?php echo $booking['id']; ?></p>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Booking Reference</p>
                  <p class="text-gray-800 font-medium"><?php echo $booking['booking_reference'] ?? 'N/A'; ?></p>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Booking Date</p>
                  <p class="text-gray-800 font-medium"><?php echo $formatted_booking_date; ?></p>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Status</p>
                  <p class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                    <i class="fas <?php echo $status_icon; ?> mr-1"></i>
                    <?php echo ucfirst($booking['status']); ?>
                  </p>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Payment Status</p>
                  <p class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $payment_class; ?>">
                    <i class="fas <?php echo $payment_icon; ?> mr-1"></i>
                    <?php echo ucfirst($payment_status); ?>
                  </p>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Payment Method</p>
                  <p class="text-gray-800 font-medium">
                    <?php echo ucfirst($booking['payment_method'] ?? 'Not specified'); ?>
                  </p>
                </div>
              </div>

              <!-- Price breakdown -->
              <div class="mt-6">
                <h4 class="font-medium text-gray-800 mb-2">Price Breakdown</h4>
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="space-y-2">
                    <div class="flex justify-between">
                      <span class="text-gray-600">Base Price</span>
                      <span class="text-gray-800">$<?php echo number_format((float)($booking['base_price'] ?? 0), 2); ?></span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600">Number of Travelers</span>
                      <span class="text-gray-800"><?php echo intval($booking['number_of_travelers'] ?? 1); ?></span>
                    </div>
                    <?php if (!empty($customizations)): ?>
                      <div class="border-t border-gray-200 my-2 pt-2">
                        <h5 class="text-sm font-medium text-gray-700 mb-1">Customizations:</h5>
                        <?php foreach ($customizations as $item => $price): ?>
                          <div class="flex justify-between">
                            <span class="text-gray-600"><?php echo htmlspecialchars($item); ?></span>
                            <span class="text-gray-800">$<?php echo number_format((float)$price, 2); ?></span>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                    <?php if (!empty($booking['discount_amount']) && $booking['discount_amount'] > 0): ?>
                      <div class="flex justify-between text-green-600">
                        <span>Discount</span>
                        <span>-$<?php echo number_format((float)$booking['discount_amount'], 2); ?></span>
                      </div>
                    <?php endif; ?>
                    <div class="border-t border-gray-200 my-2 pt-2 flex justify-between font-bold">
                      <span>Total Amount</span>
                      <span>$<?php echo number_format((float)($booking['total_price'] ?? 0), 2); ?></span>
                    </div>
                  </div>
                </div>
              </div>

              <?php if (!empty($booking['special_requests'])): ?>
                <div class="mt-6">
                  <h4 class="font-medium text-gray-800 mb-2">Special Requests</h4>
                  <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?></p>
                  </div>
                </div>
              <?php endif; ?>
            </div>

            <!-- Travelers Information -->
            <?php if (!empty($travelers)): ?>
              <div class="info-card bg-white rounded-xl shadow-md p-5 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                  <i class="fas fa-users text-teal-600 mr-2"></i> Travelers Information
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <?php foreach ($travelers as $index => $traveler): ?>
                    <div class="traveler-card bg-gray-50 rounded-lg p-4 border border-gray-100">
                      <div class="flex items-center mb-3">
                        <div class="bg-teal-100 text-teal-700 rounded-full w-8 h-8 flex items-center justify-center mr-3">
                          <i class="fas fa-user"></i>
                        </div>
                        <div>
                          <h5 class="font-medium text-gray-800">
                            <?php echo htmlspecialchars($traveler['name'] ?? 'Traveler ' . ($index + 1)); ?>
                          </h5>
                          <p class="text-xs text-gray-500">
                            <?php echo $index === 0 ? 'Lead Traveler' : 'Co-traveler'; ?>
                          </p>
                        </div>
                      </div>

                      <div class="space-y-2 text-sm">
                        <?php if (!empty($traveler['email'])): ?>
                          <div class="flex justify-between">
                            <span class="text-gray-500">Email:</span>
                            <span class="text-gray-800"><?php echo htmlspecialchars($traveler['email']); ?></span>
                          </div>
                        <?php endif; ?>
                        <?php if (!empty($traveler['phone'])): ?>
                          <div class="flex justify-between">
                            <span class="text-gray-500">Phone:</span>
                            <span class="text-gray-800"><?php echo htmlspecialchars($traveler['phone']); ?></span>
                          </div>
                        <?php endif; ?>
                        <?php if (!empty($traveler['age'])): ?>
                          <div class="flex justify-between">
                            <span class="text-gray-500">Age:</span>
                            <span class="text-gray-800"><?php echo htmlspecialchars($traveler['age']); ?> years</span>
                          </div>
                        <?php endif; ?>
                        <?php if (!empty($traveler['nationality'])): ?>
                          <div class="flex justify-between">
                            <span class="text-gray-500">Nationality:</span>
                            <span class="text-gray-800"><?php echo htmlspecialchars($traveler['nationality']); ?></span>
                          </div>
                        <?php endif; ?>
                        <?php if (!empty($traveler['passport'])): ?>
                          <div class="flex justify-between">
                            <span class="text-gray-500">Passport No:</span>
                            <span class="text-gray-800"><?php echo htmlspecialchars($traveler['passport']); ?></span>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

            <!-- Payment Details -->
            <?php if (!empty($payment_info)): ?>
              <div class="info-card bg-white rounded-xl shadow-md p-5 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                  <i class="fas fa-money-check-alt text-teal-600 mr-2"></i> Payment Details
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                  <div>
                    <p class="text-sm text-gray-500">Payment ID</p>
                    <p class="text-gray-800 font-medium">#<?php echo $payment_info['id']; ?></p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Transaction ID</p>
                    <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($payment_info['transaction_id']); ?></p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Payment Date</p>
                    <p class="text-gray-800 font-medium">
                      <?php
                      $payment_date = new DateTime($payment_info['payment_date']);
                      echo $payment_date->format('F d, Y h:i A');
                      ?>
                    </p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Amount</p>
                    <p class="text-gray-800 font-medium">$<?php echo number_format((float)$payment_info['amount'], 2); ?></p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Payment Method</p>
                    <p class="text-gray-800 font-medium"><?php echo ucfirst($payment_info['payment_method']); ?></p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <p class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $payment_class; ?>">
                      <i class="fas <?php echo $payment_icon; ?> mr-1"></i>
                      <?php echo ucfirst($payment_info['status']); ?>
                    </p>
                  </div>
                </div>

                <?php if (!empty($payment_info['payment_details'])): ?>
                  <?php
                  $payment_details = json_decode($payment_info['payment_details'], true);
                  if ($payment_details && is_array($payment_details)):
                  ?>
                    <div class="mt-4">
                      <h4 class="font-medium text-gray-800 mb-2">Payment Information</h4>
                      <div class="payment-method-card">
                        <?php foreach ($payment_details as $key => $value): ?>
                          <?php if (!empty($value) && $key !== 'cvv' && $key !== 'card_number'): ?>
                            <div class="flex justify-between text-sm mb-1">
                              <span class="text-gray-500"><?php echo ucwords(str_replace('_', ' ', $key)); ?>:</span>
                              <span class="text-gray-800">
                                <?php
                                if ($key === 'card_number' && strlen($value) > 4) {
                                  echo '************' . substr($value, -4);
                                } else {
                                  echo htmlspecialchars($value);
                                }
                                ?>
                              </span>
                            </div>
                          <?php endif; ?>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Right Column - User Info, Status, Timeline -->
          <div class="lg:col-span-1">
            <!-- Customer Information -->
            <div class="info-card bg-white rounded-xl shadow-md p-5 mb-6">
              <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-user text-teal-600 mr-2"></i> Customer Information
              </h2>

              <div class="flex items-center mb-4">
                <img class="h-14 w-14 object-cover rounded-full border border-gray-200 mr-4"
                  src="../<?php echo isset($booking['profile_image']) ? htmlspecialchars($booking['profile_image']) : 'user/uploads/default.png'; ?>"
                  alt="<?php echo htmlspecialchars($booking['full_name']); ?>'s profile" />
                <div>
                  <h3 class="font-medium text-gray-800"><?php echo htmlspecialchars($booking['full_name']); ?></h3>
                  <a href="user-details.php?id=<?php echo $booking['user_id']; ?>" class="text-sm text-teal-600 hover:text-teal-800">
                    View User Profile
                  </a>
                </div>
              </div>

              <div class="space-y-3">
                <div class="flex items-center">
                  <div class="w-6 text-center text-gray-400 mr-2">
                    <i class="fas fa-envelope"></i>
                  </div>
                  <span class="text-gray-800"><?php echo htmlspecialchars($booking['email']); ?></span>
                </div>
                <div class="flex items-center">
                  <div class="w-6 text-center text-gray-400 mr-2">
                    <i class="fas fa-phone"></i>
                  </div>
                  <span class="text-gray-800"><?php echo htmlspecialchars($booking['phone_number']); ?></span>
                </div>
              </div>
            </div>

            <!-- Booking Status -->
            <div class="info-card bg-white rounded-xl shadow-md p-5 mb-6">
              <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-tasks text-teal-600 mr-2"></i> Booking Status
              </h2>

              <div class="flex items-center mb-4">
                <span class="status-pill <?php echo $status_class; ?> flex items-center">
                  <i class="fas <?php echo $status_icon; ?> mr-1"></i>
                  <?php echo ucfirst($booking['status']); ?>
                </span>
              </div>

              <!-- Status Timeline -->
              <div class="timeline-container mt-6">
                <?php
                $timeline = [];

                // Add booking created event
                $timeline[] = [
                  'date' => $booking['booking_date'],
                  'event' => 'Booking Created',
                  'description' => 'Package booking was created successfully',
                  'icon' => 'fa-calendar-plus',
                  'color' => 'bg-blue-500'
                ];

                // Add payment event if available
                if (!empty($payment_info)) {
                  $timeline[] = [
                    'date' => $payment_info['payment_date'],
                    'event' => 'Payment ' . ucfirst($payment_info['status']),
                    'description' => 'Payment of $' . number_format((float)$payment_info['amount'], 2) . ' via ' . ucfirst($payment_info['payment_method']),
                    'icon' => 'fa-money-bill-wave',
                    'color' => $payment_info['status'] === 'paid' ? 'bg-green-500' : 'bg-red-500'
                  ];
                }

                // Add status change events if available from booking_status_history table
                $status_sql = "SELECT * FROM booking_status_history 
                              WHERE booking_id = ? AND booking_type = 'package' 
                              ORDER BY created_at ASC";
                $stmt = $conn->prepare($status_sql);
                $stmt->bind_param("i", $booking_id);
                $stmt->execute();
                $status_result = $stmt->get_result();

                if ($status_result && $status_result->num_rows > 0) {
                  while ($status_row = $status_result->fetch_assoc()) {
                    $icon = 'fa-info-circle';
                    $color = 'bg-blue-500';

                    switch (strtolower($status_row['status'])) {
                      case 'confirmed':
                        $icon = 'fa-check-circle';
                        $color = 'bg-green-500';
                        break;
                      case 'cancelled':
                      case 'canceled':
                        $icon = 'fa-times-circle';
                        $color = 'bg-red-500';
                        break;
                      case 'completed':
                        $icon = 'fa-flag-checkered';
                        $color = 'bg-purple-500';
                        break;
                    }

                    $timeline[] = [
                      'date' => $status_row['created_at'],
                      'event' => 'Status Changed to ' . ucfirst($status_row['status']),
                      'description' => !empty($status_row['notes']) ? $status_row['notes'] : 'Booking status was updated',
                      'icon' => $icon,
                      'color' => $color
                    ];
                  }
                }
                $stmt->close();

                // Sort timeline by date (oldest first)
                usort($timeline, function ($a, $b) {
                  return strtotime($a['date']) - strtotime($b['date']);
                });

                // Display timeline
                foreach ($timeline as $item):
                  $item_date = new DateTime($item['date']);
                  $formatted_item_date = $item_date->format('M d, Y h:i A');
                ?>
                  <div class="timeline-item">
                    <div class="timeline-dot <?php echo $item['color']; ?>"></div>
                    <div class="ml-2">
                      <div class="font-medium text-gray-800"><?php echo $item['event']; ?></div>
                      <div class="text-sm text-gray-600"><?php echo $item['description']; ?></div>
                      <div class="text-xs text-gray-500 mt-1"><?php echo $formatted_item_date; ?></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Actions -->
            <div class="info-card bg-white rounded-xl shadow-md p-5 mb-6 no-print">
              <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-cog text-teal-600 mr-2"></i> Actions
              </h2>

              <div class="space-y-3">
                <button onclick="updateBookingStatus(<?php echo $booking_id; ?>)" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center justify-center">
                  <i class="fas fa-edit mr-2"></i> Update Status
                </button>

                <?php if ($booking['status'] !== 'cancelled' && $booking['status'] !== 'completed'): ?>
                  <button onclick="cancelBooking(<?php echo $booking_id; ?>)" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center justify-center">
                    <i class="fas fa-times-circle mr-2"></i> Cancel Booking
                  </button>
                <?php endif; ?>

                <?php if ($payment_status !== 'paid'): ?>
                  <button onclick="recordPayment(<?php echo $booking_id; ?>)" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center justify-center">
                    <i class="fas fa-money-bill-wave mr-2"></i> Record Payment
                  </button>
                <?php endif; ?>

                <button onclick="sendBookingConfirmation(<?php echo $booking_id; ?>)" class="w-full bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-lg flex items-center justify-center">
                  <i class="fas fa-envelope mr-2"></i> Send Confirmation
                </button>

                <button onclick="printVoucher(<?php echo $booking_id; ?>)" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center justify-center">
                  <i class="fas fa-file-alt mr-2"></i> Print Voucher
                </button>
              </div>
            </div>

            <!-- Admin Notes -->
            <div class="info-card bg-white rounded-xl shadow-md p-5 mb-6 no-print">
              <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-sticky-note text-teal-600 mr-2"></i> Admin Notes
              </h2>

              <?php
              // Fetch admin notes
              $notes_sql = "SELECT * FROM booking_notes 
                           WHERE booking_id = ? AND booking_type = 'package' 
                           ORDER BY created_at DESC";
              $stmt = $conn->prepare($notes_sql);
              $stmt->bind_param("i", $booking_id);
              $stmt->execute();
              $notes_result = $stmt->get_result();
              ?>

              <?php if ($notes_result && $notes_result->num_rows > 0): ?>
                <div class="space-y-4 mb-4">
                  <?php while ($note = $notes_result->fetch_assoc()): ?>
                    <div class="bg-gray-50 rounded-lg p-3 border-l-4 border-teal-500">
                      <p class="text-sm text-gray-700"><?php echo nl2br(htmlspecialchars($note['note'])); ?></p>
                      <div class="flex justify-between items-center mt-2 text-xs text-gray-500">
                        <span>
                          By: <?php echo htmlspecialchars($note['added_by']); ?>
                        </span>
                        <span>
                          <?php
                          $note_date = new DateTime($note['created_at']);
                          echo $note_date->format('M d, Y h:i A');
                          ?>
                        </span>
                      </div>
                    </div>
                  <?php endwhile; ?>
                </div>
              <?php else: ?>
                <p class="text-gray-500 italic mb-4">No notes have been added yet.</p>
              <?php endif; ?>
              <?php $stmt->close(); ?>

              <!-- Add Note Form -->
              <form id="addNoteForm" class="mt-3">
                <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                <input type="hidden" name="booking_type" value="package">
                <div class="mb-3">
                  <textarea name="note" id="admin_note" rows="3" class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent" placeholder="Add a note..."></textarea>
                </div>
                <button type="button" onclick="addNote()" class="w-full bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg flex items-center justify-center">
                  <i class="fas fa-plus mr-2"></i> Add Note
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/js-links.php'; ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Mobile menu toggle
      const menuBtn = document.getElementById('menu-btn');
      const sidebar = document.querySelector('.sidebar');

      if (menuBtn && sidebar) {
        menuBtn.addEventListener('click', function() {
          sidebar.classList.toggle('hidden');
        });
      }
    });

    // Print booking details
    function printBookingDetails() {
      window.print();
    }

    // Print voucher
    function printVoucher(bookingId) {
      window.open('print-voucher.php?id=' + bookingId + '&type=package', '_blank');
    }

    // Update booking status
    function updateBookingStatus(bookingId) {
      Swal.fire({
        title: 'Update Booking Status',
        html: `
          <div class="mb-3">
            <select id="booking-status" class="w-full border border-gray-300 rounded-lg p-2">
              <option value="pending">Pending</option>
              <option value="confirmed">Confirmed</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <div class="mb-3">
            <textarea id="status-notes" class="w-full border border-gray-300 rounded-lg p-2" placeholder="Add notes (optional)" rows="3"></textarea>
          </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Update',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        preConfirm: () => {
          const status = document.getElementById('booking-status').value;
          const notes = document.getElementById('status-notes').value;
          return {
            status,
            notes
          };
        }
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading
          Swal.fire({
            title: 'Updating...',
            html: 'Please wait while we update the booking status',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          // Make AJAX request to update status
          const data = {
            id: bookingId,
            status: result.value.status,
            notes: result.value.notes,
            type: 'package'
          };

          fetch('update-booking-status.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                Swal.fire({
                  title: 'Success!',
                  text: 'Booking status has been updated',
                  icon: 'success',
                  confirmButtonColor: '#3085d6'
                }).then(() => {
                  // Reload page to show new status
                  location.reload();
                });
              } else {
                throw new Error(data.message || 'Failed to update booking status');
              }
            })
            .catch(error => {
              console.error('Error:', error);
              Swal.fire({
                title: 'Error!',
                text: error.message || 'An error occurred while updating the booking status',
                icon: 'error',
                confirmButtonColor: '#3085d6'
              });
            });
        }
      });
    }

    // Cancel booking
    function cancelBooking(bookingId) {
      Swal.fire({
        title: 'Cancel Booking',
        text: 'Are you sure you want to cancel this booking?',
        icon: 'warning',
        input: 'textarea',
        inputPlaceholder: 'Reason for cancellation (optional)',
        showCancelButton: true,
        confirmButtonText: 'Yes, cancel it!',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        cancelButtonText: 'No, keep it'
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading
          Swal.fire({
            title: 'Cancelling...',
            html: 'Please wait while we cancel the booking',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          // Make AJAX request to cancel booking
          const data = {
            id: bookingId,
            status: 'cancelled',
            notes: result.value,
            type: 'package'
          };

          fetch('update-booking-status.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                Swal.fire({
                  title: 'Cancelled!',
                  text: 'Booking has been cancelled',
                  icon: 'success',
                  confirmButtonColor: '#3085d6'
                }).then(() => {
                  // Reload page to show new status
                  location.reload();
                });
              } else {
                throw new Error(data.message || 'Failed to cancel booking');
              }
            })
            .catch(error => {
              console.error('Error:', error);
              Swal.fire({
                title: 'Error!',
                text: error.message || 'An error occurred while cancelling the booking',
                icon: 'error',
                confirmButtonColor: '#3085d6'
              });
            });
        }
      });
    }

    // Record payment
    function recordPayment(bookingId) {
      Swal.fire({
        title: 'Record Payment',
        html: `
          <div class="mb-3">
            <label for="payment-amount" class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
            <input type="number" id="payment-amount" class="w-full border border-gray-300 rounded-lg p-2" step="0.01" min="0">
          </div>
          <div class="mb-3">
            <label for="payment-method" class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
            <select id="payment-method" class="w-full border border-gray-300 rounded-lg p-2">
              <option value="cash">Cash</option>
              <option value="credit_card">Credit Card</option>
              <option value="bank_transfer">Bank Transfer</option>
              <option value="paypal">PayPal</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="transaction-id" class="block text-sm font-medium text-gray-700 mb-1">Transaction ID</label>
            <input type="text" id="transaction-id" class="w-full border border-gray-300 rounded-lg p-2">
          </div>
          <div class="mb-3">
            <label for="payment-notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea id="payment-notes" class="w-full border border-gray-300 rounded-lg p-2" rows="2"></textarea>
          </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Record Payment',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        preConfirm: () => {
          const amount = document.getElementById('payment-amount').value;
          const method = document.getElementById('payment-method').value;
          const transactionId = document.getElementById('transaction-id').value;
          const notes = document.getElementById('payment-notes').value;

          if (!amount || amount <= 0) {
            Swal.showValidationMessage('Please enter a valid amount');
            return false;
          }

          return {
            amount,
            method,
            transactionId,
            notes
          };
        }
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading
          Swal.fire({
            title: 'Processing...',
            html: 'Please wait while we record the payment',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          // Make AJAX request to record payment
          const data = {
            booking_id: bookingId,
            booking_type: 'package',
            amount: result.value.amount,
            payment_method: result.value.method,
            transaction_id: result.value.transactionId,
            notes: result.value.notes
          };

          fetch('record-payment.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                Swal.fire({
                  title: 'Success!',
                  text: 'Payment has been recorded',
                  icon: 'success',
                  confirmButtonColor: '#3085d6'
                }).then(() => {
                  // Reload page to show new payment
                  location.reload();
                });
              } else {
                throw new Error(data.message || 'Failed to record payment');
              }
            })
            .catch(error => {
              console.error('Error:', error);
              Swal.fire({
                title: 'Error!',
                text: error.message || 'An error occurred while recording the payment',
                icon: 'error',
                confirmButtonColor: '#3085d6'
              });
            });
        }
      });
    }

    // Send booking confirmation
    function sendBookingConfirmation(bookingId) {
      Swal.fire({
        title: 'Send Confirmation',
        text: 'Send booking confirmation email to the customer?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, send it!',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33'
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading
          Swal.fire({
            title: 'Sending...',
            html: 'Please wait while we send the confirmation email',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          // Make AJAX request to send confirmation
          fetch('send-booking-confirmation.php?id=' + bookingId + '&type=package', {
              method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                Swal.fire({
                  title: 'Sent!',
                  text: 'Confirmation email has been sent to the customer',
                  icon: 'success',
                  confirmButtonColor: '#3085d6'
                });
              } else {
                throw new Error(data.message || 'Failed to send confirmation email');
              }
            })
            .catch(error => {
              console.error('Error:', error);
              Swal.fire({
                title: 'Error!',
                text: error.message || 'An error occurred while sending the confirmation email',
                icon: 'error',
                confirmButtonColor: '#3085d6'
              });
            });
        }
      });
    }

    // Add admin note
    function addNote() {
      const form = document.getElementById('addNoteForm');
      const formData = new FormData(form);
      const note = formData.get('note');

      if (!note.trim()) {
        Swal.fire({
          title: 'Error!',
          text: 'Please enter a note',
          icon: 'error',
          confirmButtonColor: '#3085d6'
        });
        return;
      }

      // Show loading
      Swal.fire({
        title: 'Saving...',
        html: 'Please wait while we save your note',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      // Make AJAX request to add note
      fetch('add-booking-note.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            Swal.fire({
              title: 'Success!',
              text: 'Note has been added',
              icon: 'success',
              confirmButtonColor: '#3085d6'
            }).then(() => {
              // Reload page to show new note
              location.reload();
            });
          } else {
            throw new Error(data.message || 'Failed to add note');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire({
            title: 'Error!',
            text: error.message || 'An error occurred while adding the note',
            icon: 'error',
            confirmButtonColor: '#3085d6'
          });
        });
    }
  </script>
</body>

</html>