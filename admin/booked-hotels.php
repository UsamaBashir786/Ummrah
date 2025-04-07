<?php
session_name("admin_session");
session_start();
require_once 'includes/db-config.php';
if (!isset($_SESSION['admin_email'])) {
  header("Location: admin/login.php");
  exit();
}

// Handle booking status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = json_decode(file_get_contents('php://input'), true);

  if (isset($data['action']) && $data['action'] === 'updateStatus' && !empty($data['id']) && !empty($data['status'])) {
    try {
      // Update booking status
      $stmt = $pdo->prepare("UPDATE hotel_bookings SET status = ? WHERE id = ?");
      $stmt->execute([$data['status'], $data['id']]);

      header('Content-Type: application/json');
      echo json_encode(['success' => true]);
      exit;
    } catch (Exception $e) {
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'error' => $e->getMessage()]);
      exit;
    }
  } elseif (isset($data['action']) && $data['action'] === 'delete' && !empty($data['id'])) {
    try {
      // Delete booking
      $stmt = $pdo->prepare("DELETE FROM hotel_bookings WHERE id = ?");
      $stmt->execute([$data['id']]);

      header('Content-Type: application/json');
      echo json_encode(['success' => true]);
      exit;
    } catch (Exception $e) {
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'error' => $e->getMessage()]);
      exit;
    }
  }
}

// Base query for hotel bookings with hotel information - removed users table join
$query = "
    SELECT hb.*, h.hotel_name, h.location, h.price_per_night, 
           (SELECT hi.image_path FROM hotel_images hi WHERE hi.hotel_id = h.id LIMIT 1) AS hotel_image
    FROM hotel_bookings hb
    JOIN hotels h ON hb.hotel_id = h.id
";

// Initialize filters array
$filters = [];
$params = [];

// Handle search
if (isset($_GET['search']) && !empty($_GET['search'])) {
  $filters[] = "(h.hotel_name LIKE ? OR hb.guest_name LIKE ? OR hb.guest_email LIKE ?)";
  $searchTerm = "%" . $_GET['search'] . "%";
  $params[] = $searchTerm;
  $params[] = $searchTerm;
  $params[] = $searchTerm;
}

// Handle status filter
if (isset($_GET['status']) && !empty($_GET['status'])) {
  $filters[] = "hb.status = ?";
  $params[] = $_GET['status'];
}

// Handle date range filter
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
  $filters[] = "hb.check_in_date >= ?";
  $params[] = $_GET['date_from'];
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
  $filters[] = "hb.check_out_date <= ?";
  $params[] = $_GET['date_to'];
}

// Handle location filter
if (isset($_GET['location']) && !empty($_GET['location'])) {
  $filters[] = "h.location = ?";
  $params[] = $_GET['location'];
}

// Add WHERE clause if filters exist
if (!empty($filters)) {
  $query .= " WHERE " . implode(" AND ", $filters);
}

// Add ORDER BY
$query .= " ORDER BY hb.created_at DESC";

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Count total records
$countQuery = "SELECT COUNT(*) as total FROM hotel_bookings hb JOIN hotels h ON hb.hotel_id = h.id";
if (!empty($filters)) {
  $countQuery .= " WHERE " . implode(" AND ", $filters);
}

// Execute count query
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRecords / $limit);

// Add pagination to main query
$query .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Execute main query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate days and total amount for each booking
foreach ($bookings as &$booking) {
  $checkIn = new DateTime($booking['check_in_date']);
  $checkOut = new DateTime($booking['check_out_date']);
  $interval = $checkIn->diff($checkOut);
  $booking['nights'] = $interval->days;
  $booking['total_amount'] = $booking['nights'] * $booking['price_per_night'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <!-- Add this in your css-links.php or head section -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-material-ui/material-ui.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>

<body class="bg-gray-50">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main flex-1 flex flex-col">
      <!-- Navbar -->
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <button class="md:hidden text-gray-800" id="menu-btn">
          <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-xl font-semibold">
          <i class="text-teal-600 fas fa-calendar-check mx-2"></i> Hotel Bookings
        </h1>
      </div>

      <!-- Content Container -->
      <div class="overflow-auto container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="mx-auto bg-white p-4 sm:p-8 rounded-lg shadow-lg">
          <!-- Search and Filter Section -->
          <div class="mb-4 sm:mb-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
              <h2 class="text-xl sm:text-2xl font-bold text-teal-600">
                <i class="fas fa-list mr-2"></i>Booking List
              </h2>
              <div class="flex flex-col sm:flex-row w-full sm:w-auto gap-4">
                <form method="GET" class="flex flex-col sm:flex-row w-full sm:w-auto gap-4">
                  <div class="relative w-full sm:w-auto">
                    <input type="text" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                      placeholder="Search bookings..."
                      class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                  </div>
                </form>
                <a href="export-bookings.php" class="w-full sm:w-auto bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 text-center">
                  <i class="fas fa-file-export mr-2"></i>Export to CSV
                </a>
              </div>
            </div>
          </div>

          <!-- Filters Row -->
          <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <div class="relative">
              <input type="text" id="date_from" name="date_from" placeholder="Check-in From"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                value="<?php echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : ''; ?>">
            </div>

            <div class="relative">
              <input type="text" id="date_to" name="date_to" placeholder="Check-out To"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                value="<?php echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : ''; ?>">
            </div>

            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
              <option value="">All Statuses</option>
              <option value="pending" <?php echo isset($_GET['status']) && $_GET['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
              <option value="confirmed" <?php echo isset($_GET['status']) && $_GET['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
              <option value="cancelled" <?php echo isset($_GET['status']) && $_GET['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
              <option value="completed" <?php echo isset($_GET['status']) && $_GET['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
            </select>

            <select name="location" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
              <option value="">All Locations</option>
              <option value="makkah" <?php echo isset($_GET['location']) && $_GET['location'] == 'makkah' ? 'selected' : ''; ?>>Makkah</option>
              <option value="madinah" <?php echo isset($_GET['location']) && $_GET['location'] == 'madinah' ? 'selected' : ''; ?>>Madinah</option>
            </select>

            <div class="flex gap-2">
              <button type="submit" class="flex-1 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                <i class="fas fa-filter mr-2"></i>Filter
              </button>
              <a href="booked-hotels.php" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-center">
                <i class="fas fa-sync-alt mr-2"></i>Reset
              </a>
            </div>
          </form>

          <!-- Bookings Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-200 rounded-lg">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Booking ID
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Hotel
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Guest
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Dates
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Amount
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($bookings)): ?>
                  <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                      No bookings found
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($bookings as $booking): ?>
                    <tr class="hover:bg-gray-50">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                          #<?php echo $booking['id']; ?>
                        </div>
                        <div class="text-xs text-gray-500">
                          <?php echo date('M d, Y', strtotime($booking['created_at'])); ?>
                        </div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="flex items-center">
                          <div class="flex-shrink-0 h-10 w-10">
                            <img class="h-10 w-10 rounded-md object-cover"
                              src="<?php echo !empty($booking['hotel_image']) ? $booking['hotel_image'] : 'images/default-hotel.jpg'; ?>"
                              alt="<?php echo htmlspecialchars($booking['hotel_name']); ?>">
                          </div>
                          <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">
                              <?php echo htmlspecialchars($booking['hotel_name']); ?>
                            </div>
                            <div class="text-xs text-gray-500 flex items-center">
                              <i class="fas fa-map-marker-alt mr-1"></i>
                              <?php echo ucfirst(htmlspecialchars($booking['location'])); ?>
                            </div>
                            <div class="text-xs text-gray-500">
                              Room: <?php echo htmlspecialchars($booking['room_id']); ?>
                            </div>
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                          <?php echo htmlspecialchars($booking['guest_name']); ?>
                        </div>
                        <div class="text-xs text-gray-500">
                          <i class="fas fa-envelope mr-1"></i>
                          <?php echo htmlspecialchars($booking['guest_email']); ?>
                        </div>
                        <div class="text-xs text-gray-500">
                          <i class="fas fa-phone mr-1"></i>
                          <?php echo htmlspecialchars($booking['guest_phone']); ?>
                        </div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                          <i class="fas fa-calendar-check text-green-500 mr-1"></i>
                          <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?>
                        </div>
                        <div class="text-sm text-gray-900">
                          <i class="fas fa-calendar-times text-red-500 mr-1"></i>
                          <?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?>
                        </div>
                        <div class="text-xs text-gray-500">
                          <?php echo $booking['nights']; ?> Night(s)
                        </div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">
                          $<?php echo number_format($booking['total_amount'], 2); ?>
                        </div>
                        <div class="text-xs text-gray-500">
                          $<?php echo number_format($booking['price_per_night'], 2); ?> × <?php echo $booking['nights']; ?> nights
                        </div>
                      </td>
                      <td class="px-6 py-4">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                          <?php
                          switch ($booking['status']) {
                            case 'pending':
                              echo 'bg-yellow-100 text-yellow-800';
                              break;
                            case 'confirmed':
                              echo 'bg-green-100 text-green-800';
                              break;
                            case 'cancelled':
                              echo 'bg-red-100 text-red-800';
                              break;
                            case 'completed':
                              echo 'bg-blue-100 text-blue-800';
                              break;
                            default:
                              echo 'bg-gray-100 text-gray-800';
                          }
                          ?>">
                          <?php echo ucfirst($booking['status']); ?>
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex gap-2 justify-end">
                          <button onclick="changeStatus(<?php echo $booking['id']; ?>)"
                            class="text-indigo-600 hover:text-indigo-900" title="Change Status">
                            <i class="fas fa-exchange-alt"></i>
                          </button>
                          <a href="view-booking.php?id=<?php echo $booking['id']; ?>"
                            class="text-teal-600 hover:text-teal-900" title="View Details">
                            <i class="fas fa-eye"></i>
                          </a>
                          <button onclick="deleteBooking(<?php echo $booking['id']; ?>)"
                            class="text-red-600 hover:text-red-900" title="Delete">
                            <i class="fas fa-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-xs sm:text-sm text-gray-700 order-2 sm:order-1">
              Showing <?php echo min($totalRecords, 1 + $offset); ?> to <?php echo min($offset + $limit, $totalRecords); ?> of <?php echo $totalRecords; ?> bookings
            </div>
            <div class="flex gap-2 order-1 sm:order-2">
              <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['location']) ? '&location=' . urlencode($_GET['location']) : ''; ?><?php echo isset($_GET['date_from']) ? '&date_from=' . urlencode($_GET['date_from']) : ''; ?><?php echo isset($_GET['date_to']) ? '&date_to=' . urlencode($_GET['date_to']) : ''; ?>"
                  class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
                  Previous
                </a>
              <?php endif; ?>

              <?php
              // Determine page range to display
              $startPage = max(1, min($page - 2, $totalPages - 4));
              $endPage = min($totalPages, max(5, $page + 2));

              for ($i = $startPage; $i <= $endPage; $i++):
              ?>
                <a href="?page=<?php echo $i; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['location']) ? '&location=' . urlencode($_GET['location']) : ''; ?><?php echo isset($_GET['date_from']) ? '&date_from=' . urlencode($_GET['date_from']) : ''; ?><?php echo isset($_GET['date_to']) ? '&date_to=' . urlencode($_GET['date_to']) : ''; ?>"
                  class="px-3 py-1 <?php echo $i === $page ? 'bg-teal-600 text-white' : 'border border-gray-300 hover:bg-gray-50'; ?> rounded-lg text-sm">
                  <?php echo $i; ?>
                </a>
              <?php endfor; ?>

              <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['location']) ? '&location=' . urlencode($_GET['location']) : ''; ?><?php echo isset($_GET['date_from']) ? '&date_from=' . urlencode($_GET['date_from']) : ''; ?><?php echo isset($_GET['date_to']) ? '&date_to=' . urlencode($_GET['date_to']) : ''; ?>"
                  class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
                  Next
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/js-links.php'; ?>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize date pickers
      flatpickr("#date_from", {
        dateFormat: "Y-m-d",
        allowInput: true
      });

      flatpickr("#date_to", {
        dateFormat: "Y-m-d",
        allowInput: true
      });
    });

    function changeStatus(bookingId) {
      Swal.fire({
        title: 'Change Booking Status',
        input: 'select',
        inputOptions: {
          'pending': 'Pending',
          'confirmed': 'Confirmed',
          'cancelled': 'Cancelled',
          'completed': 'Completed'
        },
        inputPlaceholder: 'Select a status',
        showCancelButton: true,
        confirmButtonColor: '#0D9488',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Update',
        preConfirm: (status) => {
          if (!status) {
            Swal.showValidationMessage('Please select a status');
          }
          return status;
        }
      }).then((result) => {
        if (result.isConfirmed) {
          updateBookingStatus(bookingId, result.value);
        }
      });
    }

    function updateBookingStatus(bookingId, status) {
      fetch('booked-hotels.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            action: 'updateStatus',
            id: bookingId,
            status: status
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Status Updated',
              text: 'Booking status has been updated successfully',
              timer: 1500,
              showConfirmButton: false,
              timerProgressBar: true
            }).then(() => {
              window.location.reload();
            });
          } else {
            throw new Error(data.error || 'Failed to update booking status');
          }
        })
        .catch(error => {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message,
            confirmButtonColor: '#0D9488'
          });
        });
    }

    function deleteBooking(bookingId) {
      Swal.fire({
        title: 'Delete Booking',
        text: 'Are you sure you want to delete this booking? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          fetch('booked-hotels.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                action: 'delete',
                id: bookingId
              })
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                Swal.fire({
                  icon: 'success',
                  title: 'Deleted!',
                  text: 'Booking has been deleted successfully',
                  timer: 1500,
                  showConfirmButton: false,
                  timerProgressBar: true
                }).then(() => {
                  window.location.reload();
                });
              } else {
                throw new Error(data.error || 'Failed to delete booking');
              }
            })
            .catch(error => {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message,
                confirmButtonColor: '#0D9488'
              });
            });
        }
      });
    }
  </script>
</body>

</html>