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

// Fetch user's package bookings
$package_bookings_sql = "
    SELECT 
        pb.id as booking_id,
        pb.package_id,
        p.title as package_title,
        p.description,
        p.package_type,
        p.price,
        p.airline,
        p.flight_class,
        p.departure_city,
        p.departure_time,
        p.departure_date,
        p.arrival_city,
        p.return_time,
        p.return_date,
        pb.booking_date,
        pb.status as booking_status,
        pb.payment_status,
        pa.hotel_id,
        pa.transport_id,
        pa.flight_id,
        pa.seat_type,
        pa.seat_number,
        pa.transport_seat_number,
        h.hotel_name,
        h.location as hotel_location
    FROM package_booking pb 
    INNER JOIN packages p ON pb.package_id = p.id
    LEFT JOIN package_assign pa ON pb.id = pa.booking_id
    LEFT JOIN hotels h ON pa.hotel_id = h.id
    WHERE pb.user_id = ?
    ORDER BY pb.booking_date DESC
";
$package_stmt = $conn->prepare($package_bookings_sql);
$package_stmt->bind_param("i", $user_id);
$package_stmt->execute();
$package_bookings = $package_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include '../includes/css-links.php' ?>
  <link rel="stylesheet" href="../assets/css/output.css">
  <title>My Package Bookings</title>
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

    /* Package Details Modal */
    .package-details-modal {
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

  <!-- Package Details Modal -->
  <div id="packageDetailsModal" class="package-details-modal">
    <div class="modal-content">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Package Details</h2>
        <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      <div id="packageDetailsContent">
        <!-- Package details will be loaded here -->
        <div class="flex justify-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="main-content p-8">
    <div class="container mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold mb-6">My Package Bookings</h1>

      <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">Your Package Bookings</h2>

        <?php if ($package_bookings->num_rows > 0) { ?>
          <!-- Stats cards for package bookings -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-4 text-white">
              <div class="text-lg opacity-80 mb-1">Total Packages</div>
              <div class="text-3xl font-bold">
                <?php echo $package_bookings->num_rows; ?>
              </div>
            </div>
            <div class="bg-gradient-to-r from-blue-400 to-blue-500 rounded-lg p-4 text-white">
              <div class="text-lg opacity-80 mb-1">Upcoming</div>
              <div class="text-3xl font-bold">
                <?php
                $upcoming = 0;
                $package_bookings->data_seek(0);

                while ($package = $package_bookings->fetch_assoc()) {
                  if ($package['booking_status'] == 'pending' || $package['booking_status'] == 'confirmed') $upcoming++;
                }

                $package_bookings->data_seek(0);
                echo $upcoming;
                ?>
              </div>
            </div>
            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-4 text-white">
              <div class="text-lg opacity-80 mb-1">Paid</div>
              <div class="text-3xl font-bold">
                <?php
                $paid = 0;
                $package_bookings->data_seek(0);

                while ($package = $package_bookings->fetch_assoc()) {
                  if ($package['payment_status'] == 'paid') $paid++;
                }

                $package_bookings->data_seek(0);
                echo $paid;
                ?>
              </div>
            </div>
          </div>

          <div class="overflow-x-auto rounded-lg shadow">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gradient-to-r from-purple-600 to-purple-700">
                <tr>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Package</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Type</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Travel Info</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Date</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Price</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Assigned</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <?php
                $package_bookings->data_seek(0);
                while ($package = $package_bookings->fetch_assoc()) {
                ?>
                  <tr class="hover:bg-gray-50 transition-colors duration-200">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="font-medium text-gray-900"><?php echo htmlspecialchars($package['package_title']); ?></div>
                      <div class="text-xs text-gray-500">ID: <?php echo htmlspecialchars($package['booking_id']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900"><?php echo htmlspecialchars($package['package_type']); ?></div>
                      <div class="text-xs text-gray-500"><?php echo htmlspecialchars($package['flight_class']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900"><?php echo htmlspecialchars($package['departure_city'] . ' → ' . $package['arrival_city']); ?></div>
                      <div class="text-xs text-gray-500"><?php echo htmlspecialchars($package['airline']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900">
                        <?php echo htmlspecialchars(date('M d, Y', strtotime($package['departure_date']))); ?>
                      </div>
                      <div class="text-xs text-gray-500">
                        <?php
                        if (!empty($package['return_date'])) {
                          echo 'Return: ' . htmlspecialchars(date('M d, Y', strtotime($package['return_date'])));
                        } else {
                          echo 'One Way';
                        }
                        ?>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-green-600">$<?php echo htmlspecialchars($package['price']); ?></div>
                      <div class="text-xs text-gray-500">
                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                    <?php echo $package['payment_status'] == 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                          <?php echo ucfirst($package['payment_status']); ?>
                        </span>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                  <?php
                  switch ($package['booking_status']) {
                    case 'pending':
                      echo 'bg-yellow-100 text-yellow-800';
                      break;
                    case 'confirmed':
                      echo 'bg-blue-100 text-blue-800';
                      break;
                    case 'canceled':
                      echo 'bg-red-100 text-red-800';
                      break;
                  }
                  ?>">
                        <?php echo ucfirst($package['booking_status']); ?>
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900">
                        <?php
                        $assignments = [];

                        if (!empty($package['hotel_id'])) {
                          $assignments[] = 'Hotel: ' . htmlspecialchars($package['hotel_name'] ?? 'Assigned');
                        }

                        if (!empty($package['flight_id'])) {
                          $seat_info = '';
                          if (!empty($package['seat_type']) || !empty($package['seat_number'])) {
                            $seat_info = ' (' . htmlspecialchars(ucfirst($package['seat_type'] ?? '')) .
                              (!empty($package['seat_number']) ? ' - ' . $package['seat_number'] : '') . ')';
                          }
                          $assignments[] = 'Flight' . $seat_info;
                        }

                        if (!empty($package['transport_id'])) {
                          $seat_info = !empty($package['transport_seat_number']) ?
                            ' (Seat: ' . htmlspecialchars($package['transport_seat_number']) . ')' : '';
                          $assignments[] = 'Transport' . $seat_info;
                        }

                        if (empty($assignments)) {
                          echo '<span class="text-orange-500 text-xs">Not assigned yet</span>';
                        } else {
                          echo '<ul class="list-disc list-inside text-xs">';
                          foreach ($assignments as $assignment) {
                            echo '<li>' . $assignment . '</li>';
                          }
                          echo '</ul>';
                        }
                        ?>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <button onclick="viewPackageDetails(<?php echo $package['booking_id']; ?>)"
                        class="bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition duration-200 shadow-sm">
                        View Details
                      </button>
                    </td>
                  </tr>
                <?php
                }
                ?>
              </tbody>
            </table>
          </div>

          <!-- Mobile view for packages -->
          <div class="block md:hidden mt-6">
            <?php
            $package_bookings->data_seek(0);
            while ($package = $package_bookings->fetch_assoc()) {
            ?>
              <div class="bg-white rounded-lg shadow mb-4 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-4 py-2 flex justify-between items-center">
                  <div class="text-white font-bold"><?php echo htmlspecialchars($package['package_title']); ?></div>
                  <span class="px-2 py-1 rounded-full text-xs text-white bg-opacity-80 
                <?php
                switch ($package['booking_status']) {
                  case 'pending':
                    echo 'bg-yellow-500';
                    break;
                  case 'confirmed':
                    echo 'bg-blue-500';
                    break;
                  case 'canceled':
                    echo 'bg-red-500';
                    break;
                }
                ?>">
                    <?php echo ucfirst($package['booking_status']); ?>
                  </span>
                </div>
                <div class="p-4">
                  <div class="flex justify-between mb-3">
                    <div>
                      <div class="text-xs text-gray-500">Package Type</div>
                      <div class="font-medium"><?php echo htmlspecialchars($package['package_type']); ?></div>
                    </div>
                    <div>
                      <div class="text-xs text-gray-500">Price</div>
                      <div class="font-medium text-green-600">$<?php echo htmlspecialchars($package['price']); ?></div>
                    </div>
                  </div>

                  <div class="flex items-center mb-3">
                    <div class="w-5 h-5 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 mr-2">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                      </svg>
                    </div>
                    <div class="flex-1">
                      <div class="text-sm"><?php echo htmlspecialchars($package['departure_city'] . ' → ' . $package['arrival_city']); ?></div>
                      <div class="text-xs text-gray-500"><?php echo htmlspecialchars($package['airline'] . ' - ' . $package['flight_class']); ?></div>
                    </div>
                  </div>

                  <div class="flex items-center mb-3">
                    <div class="w-5 h-5 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 mr-2">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                    </div>
                    <div class="flex-1">
                      <div class="text-sm"><?php echo htmlspecialchars(date('M d, Y', strtotime($package['departure_date']))); ?>
                        <?php if (!empty($package['departure_time'])) echo htmlspecialchars(date('g:i A', strtotime($package['departure_time']))); ?>
                      </div>
                      <?php if (!empty($package['return_date'])) { ?>
                        <div class="text-xs text-gray-500">
                          Return: <?php echo htmlspecialchars(date('M d, Y', strtotime($package['return_date']))); ?>
                          <?php if (!empty($package['return_time'])) echo htmlspecialchars(date('g:i A', strtotime($package['return_time']))); ?>
                        </div>
                      <?php } ?>
                    </div>
                  </div>

                  <div class="flex items-center mb-4">
                    <div class="w-5 h-5 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 mr-2">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                      </svg>
                    </div>
                    <div class="flex-1">
                      <div class="flex items-center">
                        <span class="text-sm mr-2">Payment: </span>
                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                    <?php echo $package['payment_status'] == 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                          <?php echo ucfirst($package['payment_status']); ?>
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Assignments Section -->
                  <?php if (!empty($package['hotel_id']) || !empty($package['flight_id']) || !empty($package['transport_id'])) { ?>
                    <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                      <div class="text-sm font-medium text-gray-700 mb-2">Assignments:</div>
                      <ul class="text-xs text-gray-600 space-y-1">
                        <?php if (!empty($package['hotel_id'])) { ?>
                          <li class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            Hotel: <?php echo htmlspecialchars($package['hotel_name'] ?? 'Assigned'); ?>
                            <?php if (!empty($package['hotel_location'])) echo ' (' . htmlspecialchars(ucfirst($package['hotel_location'])) . ')'; ?>
                          </li>
                        <?php } ?>
                        <?php if (!empty($package['flight_id'])) { ?>
                          <li class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            Flight
                            <?php
                            if (!empty($package['seat_type']) || !empty($package['seat_number'])) {
                              echo ': ' . htmlspecialchars(ucfirst($package['seat_type'] ?? ''));
                              if (!empty($package['seat_number'])) echo ' - ' . htmlspecialchars($package['seat_number']);
                            }
                            ?>
                          </li>
                        <?php } ?>
                        <?php if (!empty($package['transport_id'])) { ?>
                          <li class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            Transport
                            <?php if (!empty($package['transport_seat_number'])) echo ': Seat ' . htmlspecialchars($package['transport_seat_number']); ?>
                          </li>
                        <?php } ?>
                      </ul>
                    </div>
                  <?php } ?>

                  <button onclick="viewPackageDetails(<?php echo $package['booking_id']; ?>)"
                    class="w-full bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    View Details
                  </button>
                </div>
              </div>
            <?php } ?>
          </div>
        <?php } else { ?>
          <div class="bg-gray-50 rounded-lg p-8 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
            </svg>
            <p class="text-xl text-gray-600 mb-4">No package bookings found</p>
            <p class="text-gray-500 mb-6">Book a complete travel package for a hassle-free journey!</p>
            <a href="../packages.php" class="mt-4 inline-block bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200 transform hover:scale-105 shadow-md">
              Book a Package
            </a>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>

  <script>
    // View Package Details
    function viewPackageDetails(packageId) {
      const modal = document.getElementById('packageDetailsModal');
      const contentDiv = document.getElementById('packageDetailsContent');

      modal.style.display = 'flex';

      // Fetch package details via AJAX
      fetch(`get_package_details.php?package_id=${packageId}`)
        .then(response => response.text())
        .then(data => {
          contentDiv.innerHTML = data;
        })
        .catch(error => {
          contentDiv.innerHTML = `
            <div class="bg-red-100 p-4 rounded-lg text-red-700">
              <p>Error loading package details. Please try again later.</p>
            </div>
          `;
          console.error('Error fetching package details:', error);
        });
    }

    // Close modal
    function closeModal() {
      const modal = document.getElementById('packageDetailsModal');
      modal.style.display = 'none';
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
      const modal = document.getElementById('packageDetailsModal');
      if (event.target === modal) {
        modal.style.display = 'none';
      }
    }
  </script>
</body>

</html>