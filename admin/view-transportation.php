<?php
session_name("admin_session");
session_start();
include 'connection/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
  header("Location: admin-login.php");
  exit();
}

// Handle booking status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
  $booking_id = $_POST['booking_id'];
  $new_status = $_POST['new_status'];
  $admin_notes = $_POST['admin_notes'] ?? '';

  $update_query = "UPDATE transportation_bookings SET 
                   booking_status = ?, 
                   admin_notes = ?,
                   updated_at = NOW() 
                   WHERE id = ?";
  $stmt = $conn->prepare($update_query);
  $stmt->bind_param("ssi", $new_status, $admin_notes, $booking_id);

  if ($stmt->execute()) {
    $success_message = "Booking #" . $booking_id . " updated successfully!";
  } else {
    $error_message = "Error updating booking: " . $conn->error;
  }
}

// Add search and filter functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$service_filter = isset($_GET['service_type']) ? $_GET['service_type'] : '';

// Build the query for bookings
$query = "SELECT tb.*, u.full_name, u.email, u.phone_number 
          FROM transportation_bookings tb
          JOIN users u ON tb.user_id = u.id
          WHERE 1=1";

if ($search) {
  $query .= " AND (tb.booking_reference LIKE '%$search%' 
              OR tb.route_name LIKE '%$search%' 
              OR u.full_name LIKE '%$search%' 
              OR u.email LIKE '%$search%')";
}

if ($status_filter) {
  $query .= " AND tb.booking_status = '$status_filter'";
}

if ($date_filter) {
  $query .= " AND DATE(tb.booking_date) = '$date_filter'";
}

if ($service_filter) {
  $query .= " AND tb.service_type = '$service_filter'";
}

$query .= " ORDER BY tb.created_at DESC";

$result = mysqli_query($conn, $query);

// Count bookings by status
$status_counts = [];
$count_query = "SELECT booking_status, COUNT(*) as count FROM transportation_bookings GROUP BY booking_status";
$count_result = mysqli_query($conn, $count_query);
while ($row = mysqli_fetch_assoc($count_result)) {
  $status_counts[$row['booking_status']] = $row['count'];
}

$total_bookings = array_sum($status_counts);
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel</title>
  <link rel="stylesheet" href="../assets/css/output.css">
  <link rel="stylesheet" href="assets/css/output.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .status-badge {
      padding: 0.25rem 0.5rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: capitalize;
    }

    .status-pending {
      background-color: #FEF3C7;
      color: #92400E;
    }

    .status-confirmed {
      background-color: #D1FAE5;
      color: #065F46;
    }

    .status-completed {
      background-color: #DBEAFE;
      color: #1E40AF;
    }

    .status-cancelled {
      background-color: #FEE2E2;
      color: #991B1B;
    }

    .payment-badge {
      padding: 0.25rem 0.5rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: capitalize;
    }

    .payment-unpaid {
      background-color: #F3F4F6;
      color: #4B5563;
    }

    .payment-paid {
      background-color: #D1FAE5;
      color: #065F46;
    }

    .payment-refunded {
      background-color: #E0E7FF;
      color: #4338CA;
    }

    .booking-details-row:nth-child(even) {
      background-color: #F9FAFB;
    }
  </style>
</head>

<body class="bg-gray-50">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    <!-- Navbar -->

    <!-- Main Content -->
    <div class="overflow-y-auto main flex-1 flex flex-col">
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <h1 class="text-xl font-semibold">
          <i class="text-teal-600 fas fa-ticket-alt mx-2"></i> Transportation Bookings
        </h1>
        <div class="flex items-center space-x-4">
          <button class="md:hidden text-gray-800" id="menu-btn">
            <i class="fas fa-bars"></i>
          </button>
        </div>
      </div>

      <div class="container mx-auto px-4 py-8">
        <?php if (isset($success_message)): ?>
          <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
            <p><?php echo $success_message; ?></p>
          </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
          <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
            <p><?php echo $error_message; ?></p>
          </div>
        <?php endif; ?>

        <!-- Dashboard Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-white p-4 rounded-lg shadow flex items-center">
            <div class="rounded-full bg-blue-100 p-3 mr-4">
              <i class="fas fa-ticket-alt text-blue-600 text-xl"></i>
            </div>
            <div>
              <p class="text-gray-500 text-sm">Total Bookings</p>
              <p class="text-2xl font-bold"><?php echo $total_bookings; ?></p>
            </div>
          </div>

          <div class="bg-white p-4 rounded-lg shadow flex items-center">
            <div class="rounded-full bg-yellow-100 p-3 mr-4">
              <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
            <div>
              <p class="text-gray-500 text-sm">Pending</p>
              <p class="text-2xl font-bold"><?php echo $status_counts['pending'] ?? 0; ?></p>
            </div>
          </div>

          <div class="bg-white p-4 rounded-lg shadow flex items-center">
            <div class="rounded-full bg-green-100 p-3 mr-4">
              <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
            <div>
              <p class="text-gray-500 text-sm">Confirmed</p>
              <p class="text-2xl font-bold"><?php echo $status_counts['confirmed'] ?? 0; ?></p>
            </div>
          </div>

          <div class="bg-white p-4 rounded-lg shadow flex items-center">
            <div class="rounded-full bg-red-100 p-3 mr-4">
              <i class="fas fa-ban text-red-600 text-xl"></i>
            </div>
            <div>
              <p class="text-gray-500 text-sm">Cancelled</p>
              <p class="text-2xl font-bold"><?php echo $status_counts['cancelled'] ?? 0; ?></p>
            </div>
          </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
          <!-- Search and Filters -->
          <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label for="searchInput" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <i class="fas fa-search text-gray-400"></i>
                </div>
                <input id="searchInput" type="text" placeholder="Booking ID, Name, Email..."
                  value="<?php echo htmlspecialchars($search); ?>"
                  class="pl-10 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md p-2 border">
              </div>
            </div>

            <div>
              <label for="statusFilter" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
              <select id="statusFilter" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md p-2 border">
                <option value="">All Statuses</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
              </select>
            </div>

            <div>
              <label for="dateFilter" class="block text-sm font-medium text-gray-700 mb-1">Booking Date</label>
              <input type="date" id="dateFilter"
                value="<?php echo htmlspecialchars($date_filter); ?>"
                class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md p-2 border">
            </div>

            <div>
              <label for="serviceFilter" class="block text-sm font-medium text-gray-700 mb-1">Service Type</label>
              <select id="serviceFilter" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md p-2 border">
                <option value="">All Services</option>
                <option value="taxi" <?php echo $service_filter === 'taxi' ? 'selected' : ''; ?>>Taxi</option>
                <option value="rentacar" <?php echo $service_filter === 'rentacar' ? 'selected' : ''; ?>>Rent A Car</option>
              </select>
            </div>
          </div>

          <!-- Bookings Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking ID</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route & Vehicle</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <?php if (mysqli_num_rows($result) > 0): ?>
                  <?php while ($booking = mysqli_fetch_assoc($result)): ?>
                    <tr class="hover:bg-gray-50">
                      <td class="px-6 py-4">
                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($booking['booking_reference']); ?></div>
                        <div class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($booking['created_at'])); ?></div>
                      </td>

                      <td class="px-6 py-4">
                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($booking['full_name']); ?></div>
                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($booking['email']); ?></div>
                        <?php if (!empty($booking['phone_number'])): ?>
                          <div class="text-xs text-gray-500"><?php echo htmlspecialchars($booking['phone_number']); ?></div>
                        <?php endif; ?>
                      </td>

                      <td class="px-6 py-4">
                        <div class="font-medium text-gray-900"><?php echo ucfirst($booking['service_type']); ?></div>
                        <div class="text-xs text-gray-500">
                          <span class="payment-badge 
                          <?php echo 'payment-' . $booking['payment_status']; ?>">
                            <?php echo ucfirst($booking['payment_status']); ?>
                          </span>
                        </div>
                      </td>

                      <td class="px-6 py-4">
                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($booking['route_name'] ?? 'N/A'); ?></div>
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['vehicle_name'] ?? 'N/A'); ?></div>
                        <div class="text-xs text-gray-500"><?php echo $booking['price']; ?> SR</div>
                      </td>

                      <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">
                          <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?>
                        </div>
                        <div class="text-sm text-gray-500">
                          <?php echo date('h:i A', strtotime($booking['booking_time'])); ?>
                        </div>
                        <div class="text-xs text-gray-500">
                          <?php echo $booking['passengers']; ?> passengers
                        </div>
                      </td>

                      <td class="px-6 py-4">
                        <span class="status-badge 
                        <?php echo 'status-' . $booking['booking_status']; ?>">
                          <?php echo ucfirst($booking['booking_status']); ?>
                        </span>
                      </td>

                      <td class="px-6 py-4">
                        <div class="flex space-x-3">
                          <button class="text-blue-600 hover:text-blue-900 view-details-btn"
                            data-id="<?php echo $booking['id']; ?>">
                            <i class="fas fa-eye"></i>
                          </button>

                          <?php if ($booking['booking_status'] == 'pending'): ?>
                            <button class="text-green-600 hover:text-green-900 confirm-booking-btn"
                              data-id="<?php echo $booking['id']; ?>"
                              data-reference="<?php echo $booking['booking_reference']; ?>">
                              <i class="fas fa-check"></i>
                            </button>
                          <?php endif; ?>

                          <?php if ($booking['booking_status'] != 'cancelled' && $booking['booking_status'] != 'completed'): ?>
                            <button class="text-red-600 hover:text-red-900 cancel-booking-btn"
                              data-id="<?php echo $booking['id']; ?>"
                              data-reference="<?php echo $booking['booking_reference']; ?>">
                              <i class="fas fa-times"></i>
                            </button>
                          <?php endif; ?>

                          <button class="text-gray-600 hover:text-gray-900 print-btn"
                            data-id="<?php echo $booking['id']; ?>"
                            data-reference="<?php echo $booking['booking_reference']; ?>">
                            <i class="fas fa-print"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No bookings found</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Booking Details Modal -->
  <div id="bookingDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto">
      <div class="flex justify-between items-center border-b px-6 py-4">
        <h3 class="text-lg font-medium text-gray-900">Booking Details</h3>
        <button id="closeModal" class="text-gray-400 hover:text-gray-500">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="p-6" id="bookingDetailsContent">
        <!-- Content will be loaded dynamically -->
        <div class="animate-pulse">
          <div class="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
          <div class="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
          <div class="h-4 bg-gray-200 rounded w-5/6 mb-4"></div>
          <div class="h-4 bg-gray-200 rounded w-2/3 mb-4"></div>
        </div>
      </div>

      <div class="border-t px-6 py-4 bg-gray-50 flex justify-end space-x-3" id="bookingDetailsActions">
        <!-- Action buttons will be loaded dynamically -->
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Search and Filter Implementation
      const searchInput = document.getElementById('searchInput');
      const statusFilter = document.getElementById('statusFilter');
      const dateFilter = document.getElementById('dateFilter');
      const serviceFilter = document.getElementById('serviceFilter');

      function updateURL() {
        const searchValue = searchInput.value;
        const statusValue = statusFilter.value;
        const dateValue = dateFilter.value;
        const serviceValue = serviceFilter.value;
        const url = new URL(window.location.href);

        if (searchValue) url.searchParams.set('search', searchValue);
        else url.searchParams.delete('search');

        if (statusValue) url.searchParams.set('status', statusValue);
        else url.searchParams.delete('status');

        if (dateValue) url.searchParams.set('date', dateValue);
        else url.searchParams.delete('date');

        if (serviceValue) url.searchParams.set('service_type', serviceValue);
        else url.searchParams.delete('service_type');

        window.location.href = url.toString();
      }

      searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') updateURL();
      });

      statusFilter.addEventListener('change', updateURL);
      dateFilter.addEventListener('change', updateURL);
      serviceFilter.addEventListener('change', updateURL);

      // View Booking Details
      const detailsModal = document.getElementById('bookingDetailsModal');
      const closeModal = document.getElementById('closeModal');
      const detailsContent = document.getElementById('bookingDetailsContent');
      const detailsActions = document.getElementById('bookingDetailsActions');

      // Close modal when clicking close button or outside the modal
      closeModal.addEventListener('click', function() {
        detailsModal.classList.add('hidden');
      });

      window.addEventListener('click', function(e) {
        if (e.target === detailsModal) {
          detailsModal.classList.add('hidden');
        }
      });

      // Show booking details
      document.querySelectorAll('.view-details-btn').forEach(button => {
        button.addEventListener('click', function() {
          const bookingId = this.getAttribute('data-id');

          // Show modal with loading state
          detailsModal.classList.remove('hidden');

          // Load booking details
          fetch(`get-booking-details.php?id=${bookingId}`)
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                const booking = data.booking;

                // Format dates
                const bookingDate = new Date(booking.booking_date);
                const formattedDate = new Intl.DateTimeFormat('en-US', {
                  year: 'numeric',
                  month: 'long',
                  day: 'numeric'
                }).format(bookingDate);

                const bookingTime = new Date(`2000-01-01T${booking.booking_time}`);
                const formattedTime = new Intl.DateTimeFormat('en-US', {
                  hour: 'numeric',
                  minute: 'numeric',
                  hour12: true
                }).format(bookingTime);

                // Determine status class
                let statusClass = '';
                switch (booking.booking_status) {
                  case 'pending':
                    statusClass = 'status-pending';
                    break;
                  case 'confirmed':
                    statusClass = 'status-confirmed';
                    break;
                  case 'completed':
                    statusClass = 'status-completed';
                    break;
                  case 'cancelled':
                    statusClass = 'status-cancelled';
                    break;
                }

                // Determine payment status class
                let paymentClass = '';
                switch (booking.payment_status) {
                  case 'unpaid':
                    paymentClass = 'payment-unpaid';
                    break;
                  case 'paid':
                    paymentClass = 'payment-paid';
                    break;
                  case 'refunded':
                    paymentClass = 'payment-refunded';
                    break;
                }

                // Build details content
                let content = `
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Booking Information -->
                    <div>
                      <h4 class="font-medium text-lg text-gray-900 mb-4">Booking Information</h4>
                      <div class="space-y-3">
                        <div class="grid grid-cols-2 booking-details-row py-2">
                          <div class="text-sm text-gray-500">Reference</div>
                          <div class="text-sm font-medium">${booking.booking_reference}</div>
                        </div>
                        <div class="grid grid-cols-2 booking-details-row py-2">
                          <div class="text-sm text-gray-500">Service Type</div>
                          <div class="text-sm font-medium">${booking.service_type.charAt(0).toUpperCase() + booking.service_type.slice(1)}</div>
                        </div>
                        <div class="grid grid-cols-2 booking-details-row py-2">
                          <div class="text-sm text-gray-500">Status</div>
                          <div class="text-sm">
                            <span class="status-badge ${statusClass}">
                              ${booking.booking_status.charAt(0).toUpperCase() + booking.booking_status.slice(1)}
                            </span>
                          </div>
                        </div>
                        <div class="grid grid-cols-2 booking-details-row py-2">
                          <div class="text-sm text-gray-500">Payment</div>
                          <div class="text-sm">
                            <span class="payment-badge ${paymentClass}">
                              ${booking.payment_status.charAt(0).toUpperCase() + booking.payment_status.slice(1)}
                            </span>
                          </div>
                        </div>
                        <div class="grid grid-cols-2 booking-details-row py-2">
                          <div class="text-sm text-gray-500">Price</div>
                          <div class="text-sm font-medium">${booking.price} SR</div>
                        </div>
                        <div class="grid grid-cols-2 booking-details-row py-2">
                          <div class="text-sm text-gray-500">Booking Date</div>
                          <div class="text-sm font-medium">${formattedDate}</div>
                        </div>
                        <div class="grid grid-cols-2 booking-details-row py-2">
                          <div class="text-sm text-gray-500">Pickup Time</div>
                          <div class="text-sm font-medium">${formattedTime}</div>
                        </div>
                        <div class="grid grid-cols-2 booking-details-row py-2">
                          <div class="text-sm text-gray-500">Created</div>
                          <div class="text-sm font-medium">${new Date(booking.created_at).toLocaleString()}</div>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Trip & Customer Details -->
                    <div>
                      <h4 class="font-medium text-lg text-gray-900 mb-4">Trip & Customer Details</h4>
                      <div class="space-y-3">
                        <div class="grid grid-cols-2 booking-details-row py-2">
                          <div class="text-sm text-gray-500">Route</div>
                          <div class="text-sm font-medium">${booking.route_name || 'N/A'}</div>
                        </div>
                        <div class="grid grid-cols-2 booking-details-row py-2">
                          <div class="text-sm text-gray-500">Vehicle</div>
                          <div class="text-sm font-medium">${booking.vehicle_name || 'N/A'}</div>
                        </div>
                        <div class="grid grid-cols-2 booking-details-row py-2">
                          <div class="text-sm text-gray-500">Pickup Location</div>
                          <div class="text-sm font-medium">${booking.pickup_location}</div>
                        </div>
                        <div class="grid grid-cols-2 booking-details-row py-2">
                          <div class="text-sm text-gray-500">Drop-off Location</div>
                          <div class="text-sm font-medium">${booking.dropoff_location}</div>
                        </div>
                        <div class="grid grid-cols-2 booking-details-row py-2">
                          <div class="text-sm text-gray-500">Passengers</div>
                          <div class="text-sm font-medium">${booking.passengers}</div>
                        </div>
                        <div class="grid grid-cols-2 booking-details-row py-2">
                          <div class="text-sm text-gray-500">Customer</div>
                          <div class="text-sm font-medium">${booking.full_name}</div>
                        </div>
                        <div class="grid grid-cols-2 booking-details-row py-2">
                          <div class="text-sm text-gray-500">Contact</div>
                          <div class="text-sm">
                            <div>${booking.email}</div>
                            ${booking.phone_number ? `<div>${booking.phone_number}</div>` : ''}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                `;

                // Add special requests if any
                if (booking.special_requests) {
                  content += `
                    <div class="mt-6">
                      <h4 class="font-medium text-lg text-gray-900 mb-2">Special Requests</h4>
                      <p class="text-gray-700 bg-gray-50 p-3 rounded border">${booking.special_requests}</p>
                    </div>
                  `;
                }

                // Add admin notes section
                content += `
                  <div class="mt-6">
                    <h4 class="font-medium text-lg text-gray-900 mb-2">Admin Notes</h4>
                    <textarea id="adminNotes" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" 
                      rows="3" placeholder="Add notes about this booking...">${booking.admin_notes || ''}</textarea>
                  </div>
                `;

                // Update the modal content
                detailsContent.innerHTML = content;

                // Build action buttons based on status
                let actions = `
                  <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300" onclick="printBookingDetails(${booking.id}, '${booking.booking_reference}')">
                    <i class="fas fa-print mr-2"></i> Print
                  </button>
                `;

                if (booking.booking_status === 'pending') {
                  actions += `
                    <button class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700" onclick="updateBookingStatus(${booking.id}, 'confirmed')">
                      <i class="fas fa-check mr-2"></i> Confirm Booking
                    </button>
                  `;
                }

                if (booking.booking_status !== 'cancelled' && booking.booking_status !== 'completed') {
                  actions += `
                    <button class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700" onclick="updateBookingStatus(${booking.id}, 'cancelled')">
                      <i class="fas fa-times mr-2"></i> Cancel Booking
                    </button>
                  `;
                }

                if (booking.booking_status === 'confirmed') {
                  actions += `
                    <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" onclick="updateBookingStatus(${booking.id}, 'completed')">
                      <i class="fas fa-check-double mr-2"></i> Mark as Completed
                    </button>
                  `;
                }

                // Update action buttons
                detailsActions.innerHTML = actions;
              } else {
                detailsContent.innerHTML = `
                  <div class="text-center text-red-600">
                    <i class="fas fa-exclamation-circle text-4xl mb-2"></i>
                    <p>${data.message || 'Error loading booking details'}</p>
                  </div>
                `;
                detailsActions.innerHTML = '';
              }
            })
            .catch(error => {
              console.error('Error fetching booking details:', error);
              detailsContent.innerHTML = `
                <div class="text-center text-red-600">
                  <i class="fas fa-exclamation-circle text-4xl mb-2"></i>
                  <p>Failed to load booking details. Please try again.</p>
                </div>
              `;
              detailsActions.innerHTML = '';
            });
        });
      });

      // Confirm booking directly from list
      document.querySelectorAll('.confirm-booking-btn').forEach(button => {
        button.addEventListener('click', function() {
          const bookingId = this.getAttribute('data-id');
          const reference = this.getAttribute('data-reference');

          Swal.fire({
            title: 'Confirm Booking?',
            text: `Are you sure you want to confirm booking ${reference}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Yes, confirm it!'
          }).then((result) => {
            if (result.isConfirmed) {
              updateBookingStatus(bookingId, 'confirmed');
            }
          });
        });
      });

      // Cancel booking directly from list
      document.querySelectorAll('.cancel-booking-btn').forEach(button => {
        button.addEventListener('click', function() {
          const bookingId = this.getAttribute('data-id');
          const reference = this.getAttribute('data-reference');

          Swal.fire({
            title: 'Cancel Booking?',
            text: `Are you sure you want to cancel booking ${reference}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Yes, cancel it!'
          }).then((result) => {
            if (result.isConfirmed) {
              updateBookingStatus(bookingId, 'cancelled');
            }
          });
        });
      });

      // Print booking directly from list
      document.querySelectorAll('.print-btn').forEach(button => {
        button.addEventListener('click', function() {
          const bookingId = this.getAttribute('data-id');
          const reference = this.getAttribute('data-reference');
          printBookingDetails(bookingId, reference);
        });
      });
    });

    // Function to update booking status
    function updateBookingStatus(bookingId, newStatus) {
      // Get admin notes if modal is open
      let adminNotes = '';
      const notesElement = document.getElementById('adminNotes');
      if (notesElement) {
        adminNotes = notesElement.value;
      }

      // Create form data
      const formData = new FormData();
      formData.append('update_status', '1');
      formData.append('booking_id', bookingId);
      formData.append('new_status', newStatus);
      formData.append('admin_notes', adminNotes);

      // Submit form
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '';

      for (const [key, value] of formData.entries()) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
      }

      document.body.appendChild(form);
      form.submit();
    }

    // Function to print booking details
    function printBookingDetails(bookingId, reference) {
      window.open(`print-booking.php?id=${bookingId}&reference=${reference}`, '_blank');
    }
  </script>

  <?php include 'includes/js-links.php'; ?>
  <script src="assets/js/main.js"></script>

</body>

</html>