<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}

require_once '../connection/connection.php';

// Get user details
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if profile image exists and is accessible
$profile_image = $user['profile_image'];
if (!empty($profile_image) && file_exists("../" . $profile_image)) {
  $profile_image = "../" . $profile_image;
}

// Fetch user's transportation bookings
$transport_bookings_sql = "
    SELECT 
        tb.*
    FROM transportation_bookings tb
    WHERE tb.user_id = ?
    ORDER BY tb.booking_date, tb.booking_time
";
$transport_stmt = $conn->prepare($transport_bookings_sql);
$transport_stmt->bind_param("i", $user_id);
$transport_stmt->execute();
$transport_bookings = $transport_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include '../includes/css-links.php' ?>
  <link rel="stylesheet" href="../assets/css/output.css">
  <title>My Transportation Bookings</title>
  <style>
    /* New table styling for better user experience */
    .booking-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .booking-table thead {
      background: linear-gradient(135deg, #4F46E5, #3B82F6);
    }

    .booking-table th {
      color: white;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.85rem;
      padding: 16px;
      text-align: left;
      letter-spacing: 0.5px;
    }

    .booking-table tbody tr {
      border-bottom: 1px solid #F3F4F6;
      transition: all 0.2s ease;
    }

    .booking-table tbody tr:hover {
      background-color: #F9FAFB;
      transform: translateY(-2px);
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .booking-table td {
      padding: 16px;
      vertical-align: middle;
      color: #374151;
    }

    .status-badge {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 50px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .action-button {
      background: linear-gradient(135deg, #3B82F6, #2563EB);
      color: white;
      font-weight: 600;
      padding: 8px 16px;
      border-radius: 6px;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
      box-shadow: 0 2px 4px rgba(37, 99, 235, 0.3);
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.5px;
    }

    .action-button:hover {
      background: linear-gradient(135deg, #2563EB, #1D4ED8);
      transform: translateY(-2px);
      box-shadow: 0 4px 6px rgba(37, 99, 235, 0.4);
    }

    /* Responsive design improvements */
    @media (max-width: 1024px) {
      .booking-table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
      }
    }

    @media (max-width: 768px) {

      .booking-table th,
      .booking-table td {
        padding: 12px 8px;
        font-size: 0.9rem;
      }

      .status-badge {
        padding: 4px 8px;
        font-size: 0.7rem;
      }
    }

    /* Card-based mobile view for smaller screens */
    @media (max-width: 640px) {
      .mobile-card-view {
        display: flex;
        flex-direction: column;
      }

      .mobile-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-bottom: 16px;
        padding: 16px;
      }

      .mobile-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #E5E7EB;
        padding-bottom: 12px;
        margin-bottom: 12px;
      }

      .mobile-card-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
      }

      .mobile-card-label {
        font-size: 0.75rem;
        color: #6B7280;
        font-weight: 600;
        text-transform: uppercase;
      }

      .mobile-card-value {
        font-size: 0.875rem;
        color: #1F2937;
        margin-top: 4px;
      }

      .mobile-card-footer {
        display: flex;
        justify-content: flex-end;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #E5E7EB;
      }
    }

    .booking-stats {
      display: flex;
      gap: 16px;
      margin-bottom: 24px;
    }

    .stat-card {
      background: white;
      border-radius: 8px;
      padding: 16px;
      flex: 1;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .stat-number {
      font-size: 2rem;
      font-weight: bold;
      color: #3B82F6;
    }

    .stat-label {
      font-size: 0.875rem;
      color: #6B7280;
      margin-top: 4px;
    }

    /* Transportation Details Modal */
    .transport-details-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background-color: white;
      padding: 20px;
      border-radius: 8px;
      max-width: 600px;
      width: 90%;
      max-height: 90vh;
      overflow-y: auto;
    }
  </style>
</head>

<body class="bg-gray-100">
  <?php include 'includes/sidebar.php'; ?>

  <!-- Transportation Details Modal -->
  <div id="transportDetailsModal" class="transport-details-modal">
    <div class="modal-content">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Transportation Booking Details</h2>
        <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      <div id="transportDetailsContent">
        <!-- Transportation details will be loaded here -->
        <div class="flex justify-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="main-content p-8">
    <div class="container mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold mb-6">My Transportation Bookings</h1>

      <!-- Display success/error messages -->
      <?php if (isset($_GET['success']) && $_GET['success'] === 'booking_cancelled') { ?>
        <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-6">
          Booking successfully cancelled.
        </div>
      <?php } elseif (isset($_GET['error'])) { ?>
        <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-6">
          <?php
          switch ($_GET['error']) {
            case 'booking_not_found':
              echo 'Booking not found.';
              break;
            case 'booking_not_cancellable':
              echo 'This booking cannot be cancelled.';
              break;
            case 'cancellation_failed':
              echo 'Failed to cancel the booking. Please try again.';
              break;
            default:
              echo 'An error occurred.';
          }
          ?>
        </div>
      <?php } ?>

      <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">Your Transportation Bookings</h2>

        <?php if ($transport_bookings->num_rows > 0) { ?>
          <!-- Stats cards for transportation bookings -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-4 text-white">
              <div class="text-lg opacity-80 mb-1">Total Bookings</div>
              <div class="text-3xl font-bold">
                <?php echo $transport_bookings->num_rows; ?>
              </div>
            </div>
            <div class="bg-gradient-to-r from-green-400 to-green-500 rounded-lg p-4 text-white">
              <div class="text-lg opacity-80 mb-1">Confirmed</div>
              <div class="text-3xl font-bold">
                <?php
                $confirmed = 0;
                $transport_bookings->data_seek(0);

                while ($transport = $transport_bookings->fetch_assoc()) {
                  if ($transport['booking_status'] == 'confirmed') $confirmed++;
                }

                $transport_bookings->data_seek(0);
                echo $confirmed;
                ?>
              </div>
            </div>
            <div class="bg-gradient-to-r from-yellow-400 to-yellow-500 rounded-lg p-4 text-white">
              <div class="text-lg opacity-80 mb-1">Upcoming</div>
              <div class="text-3xl font-bold">
                <?php
                $upcoming = 0;
                $transport_bookings->data_seek(0);
                $current_date = date('Y-m-d');

                while ($transport = $transport_bookings->fetch_assoc()) {
                  if ($transport['booking_date'] > $current_date && $transport['booking_status'] != 'cancelled') $upcoming++;
                }

                $transport_bookings->data_seek(0);
                echo $upcoming;
                ?>
              </div>
            </div>
          </div>

          <div class="overflow-x-auto rounded-lg shadow">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gradient-to-r from-blue-600 to-blue-700">
                <tr>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Booking Ref</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Service Type</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Route</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Vehicle</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Date & Time</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Price</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <?php while ($transport = $transport_bookings->fetch_assoc()) {
                  // Skip if the booking ID is invalid
                  if (empty($transport['id']) || !is_numeric($transport['id'])) {
                    continue;
                  }
                ?>
                  <tr class="hover:bg-gray-50 transition-colors duration-200">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="font-medium text-gray-900"><?php echo htmlspecialchars($transport['booking_reference']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900"><?php echo ucfirst(htmlspecialchars($transport['service_type'])); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900">
                        <?php
                        if (!empty($transport['route_name'])) {
                          echo htmlspecialchars($transport['route_name']);
                        } else {
                          echo htmlspecialchars($transport['pickup_location'] . ' to ' . $transport['dropoff_location']);
                        }
                        ?>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900"><?php echo htmlspecialchars($transport['vehicle_name'] . ' (' . ucfirst($transport['vehicle_type']) . ')'); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900"><?php echo htmlspecialchars($transport['booking_date'] . ' ' . $transport['booking_time']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-green-600">$<?php echo htmlspecialchars($transport['price']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                    <?php
                    switch ($transport['booking_status']) {
                      case 'pending':
                        echo 'bg-yellow-100 text-yellow-800';
                        break;
                      case 'confirmed':
                        echo 'bg-blue-100 text-blue-800';
                        break;
                      case 'completed':
                        echo 'bg-green-100 text-green-800';
                        break;
                      case 'cancelled':
                        echo 'bg-red-100 text-red-800';
                        break;
                    }
                    ?>">
                        <?php echo ucfirst($transport['booking_status']); ?>
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <button onclick="viewTransportDetails(<?php echo $transport['id']; ?>)"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition duration-200 shadow-sm">
                        View Details
                      </button>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>

          <!-- Mobile view for transportation -->
          <div class="block md:hidden mt-6">
            <?php
            $transport_bookings->data_seek(0);
            while ($transport = $transport_bookings->fetch_assoc()) {
              // Skip if the booking ID is invalid
              if (empty($transport['id']) || !is_numeric($transport['id'])) {
                continue;
              }
            ?>
              <div class="bg-white rounded-lg shadow mb-4 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-4 py-2 flex justify-between items-center">
                  <div class="text-white font-bold"><?php echo htmlspecialchars($transport['booking_reference']); ?></div>
                  <span class="px-2 py-1 rounded-full text-xs text-white bg-opacity-80 
                <?php
                switch ($transport['booking_status']) {
                  case 'pending':
                    echo 'bg-yellow-500';
                    break;
                  case 'confirmed':
                    echo 'bg-blue-700';
                    break;
                  case 'completed':
                    echo 'bg-green-500';
                    break;
                  case 'cancelled':
                    echo 'bg-red-500';
                    break;
                }
                ?>">
                    <?php echo ucfirst($transport['booking_status']); ?>
                  </span>
                </div>
                <div class="p-4">
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <div class="text-xs text-gray-500">Service Type</div>
                      <div class="font-medium"><?php echo ucfirst(htmlspecialchars($transport['service_type'])); ?></div>
                    </div>
                    <div>
                      <div class="text-xs text-gray-500">Vehicle</div>
                      <div class="font-medium"><?php echo htmlspecialchars($transport['vehicle_name'] . ' (' . ucfirst($transport['vehicle_type']) . ')'); ?></div>
                    </div>
                    <div>
                      <div class="text-xs text-gray-500">Route</div>
                      <div class="font-medium">
                        <?php
                        if (!empty($transport['route_name'])) {
                          echo htmlspecialchars($transport['route_name']);
                        } else {
                          echo htmlspecialchars($transport['pickup_location'] . ' to ' . $transport['dropoff_location']);
                        }
                        ?>
                      </div>
                    </div>
                    <div>
                      <div class="text-xs text-gray-500">Date & Time</div>
                      <div class="font-medium"><?php echo htmlspecialchars($transport['booking_date'] . ' ' . $transport['booking_time']); ?></div>
                    </div>
                    <div>
                      <div class="text-xs text-gray-500">Price</div>
                      <div class="font-medium text-green-600">$<?php echo htmlspecialchars($transport['price']); ?></div>
                    </div>
                  </div>
                  <div class="mt-4 flex justify-center">
                    <button onclick="viewTransportDetails(<?php echo $transport['id']; ?>)"
                      class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition duration-200 shadow-sm">
                      View Details
                    </button>
                  </div>
                </div>
              </div>
            <?php } ?>
          </div>
        <?php } else { ?>
          <div class="bg-gray-50 rounded-lg p-8 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
            <p class="text-xl text-gray-600 mb-4">No transportation bookings</p>
            <p class="text-gray-500 mb-6">Book transportation to get around during your trip</p>
            <a href="../transportation.php" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200 transform hover:scale-105 shadow-md">
              Book Transportation
            </a>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>

  <script>
    // View Transportation Details
    function viewTransportDetails(bookingId) {
      const modal = document.getElementById('transportDetailsModal');
      const contentDiv = document.getElementById('transportDetailsContent');

      // Validate bookingId
      if (!bookingId || isNaN(bookingId)) {
        contentDiv.innerHTML = `
          <div class="bg-red-100 p-4 rounded-lg text-red-700">
            <p>Invalid booking ID. Please try again.</p>
          </div>
        `;
        modal.style.display = 'flex';
        return;
      }

      modal.style.display = 'flex';

      // Fetch transportation details via AJAX
      fetch(`get_transport_details.php?booking_id=${bookingId}`)
        .then(response => response.text())
        .then(data => {
          contentDiv.innerHTML = data;
        })
        .catch(error => {
          contentDiv.innerHTML = `
            <div class="bg-red-100 p-4 rounded-lg text-red-700">
              <p>Error loading transportation details. Please try again later.</p>
            </div>
          `;
          console.error('Error fetching transportation details:', error);
        });
    }

    // Cancel Booking
    function cancelBooking(bookingId, bookingReference) {
      if (confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
        window.location.href = '../cancel-booking.php?booking_id=' + bookingId + '&reference=' + bookingReference;
      }
    }

    // Close modal
    function closeModal() {
      const modal = document.getElementById('transportDetailsModal');
      modal.style.display = 'none';
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
      const modal = document.getElementById('transportDetailsModal');
      if (event.target === modal) {
        modal.style.display = 'none';
      }
    }
  </script>
</body>

</html>