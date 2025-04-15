<?php
require_once 'connection/connection.php';

// Fetch all users
$sql = "SELECT * FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);

// Get all unique cities for filtering
$cities = array();
if ($result && $result->num_rows > 0) {
  $temp_result = $conn->query($sql);
  while ($row = $temp_result->fetch_assoc()) {
    if (!empty($row['city']) && !in_array($row['city'], $cities)) {
      $cities[] = $row['city'];
    }
  }
  sort($cities);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <style>
    /* Custom scrollbar for better mobile experience */
    .custom-scrollbar::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
      background: #888;
      border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
      background: #555;
    }

    /* Card transitions for mobile */
    .user-card {
      transition: all 0.3s ease;
    }

    .user-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    /* Badge styles */
    .badge {
      font-size: 0.65rem;
      padding: 0.25rem 0.5rem;
      border-radius: 9999px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .badge-city {
      background-color: #e5f2f8;
      color: #0369a1;
    }

    .badge-gender {
      background-color: #f0f9ff;
      color: #0891b2;
    }

    .badge-date {
      background-color: #ecfdf5;
      color: #059669;
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
        <button class="md:hidden text-gray-800" id="menu-btn">
          <i class="fas fa-bars"></i>
        </button>
        <div class="flex items-center">
          <h1 class="text-xl font-semibold"><i class="text-teal-600 fa fa-user mx-2"></i> User Management</h1>
          <?php
          // Get total users count
          $totalUsersQuery = "SELECT COUNT(*) as total FROM users";
          $totalResult = $conn->query($totalUsersQuery);
          $totalUsers = 0;

          if ($totalResult && $totalResult->num_rows > 0) {
            $row = $totalResult->fetch_assoc();
            $totalUsers = $row['total'];
          }
          ?>
          <div class="ml-4 bg-gray-100 px-3 py-1 rounded-full flex items-center">
            <i class="fas fa-users text-teal-600 mr-2"></i>
            <span class="text-sm font-medium"><?php echo $totalUsers; ?> Users</span>
          </div>
        </div>
        <div class="flex items-center">
          <a href="index.php" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back
          </a>
        </div>
      </div>
      <!-- Search and Filter Controls -->
      <div class="bg-white border-b border-gray-200 p-4 no-print">
        <div class="flex flex-col md:flex-row gap-3 mb-3">
          <div class="flex-1">
            <input type="text" id="searchInput" placeholder="Search users..."
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-600 focus:border-transparent">
          </div>
          <div class="flex flex-wrap gap-2">
            <select id="filterCity" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-600 focus:border-transparent">
              <option value="">All Cities</option>
              <?php foreach ($cities as $city): ?>
                <option value="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city); ?></option>
              <?php endforeach; ?>
            </select>
            <select id="filterGender" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-600 focus:border-transparent">
              <option value="">All Genders</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
            <button id="resetFilters" class="px-3 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg">
              <i class="fas fa-redo-alt mr-1"></i> Reset
            </button>
          </div>
        </div>
      </div>

      <div class="flex-1 overflow-auto custom-scrollbar">
        <div class="container mx-auto p-4">
          <!-- Mobile view - Cards -->
          <div class="grid grid-cols-1 md:hidden gap-4">
            <?php if ($result && $result->num_rows > 0): ?>
              <?php
              $result->data_seek(0);
              while ($user = $result->fetch_assoc()):
                $created_date = new DateTime($user['created_at']);
                $formatted_date = $created_date->format('M d, Y');
              ?>
                <div class="user-card bg-white rounded-xl shadow-md p-4 hover:bg-gray-50">
                  <div class="flex items-start justify-between">
                    <div class="flex items-center space-x-4">
                      <div class="relative">
                        <img class="h-16 w-16 rounded-full object-cover border-2 border-teal-500"
                          src="../<?php echo isset($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'user/uploads/default.png'; ?>"
                          alt="User avatar" />
                      </div>
                      <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                          <?php echo isset($user['full_name']) ? htmlspecialchars($user['full_name']) : 'Unknown'; ?>
                        </h3>
                        <div class="flex items-center text-sm text-gray-600">
                          <i class="far fa-envelope mr-1"></i>
                          <?php echo isset($user['email']) ? htmlspecialchars($user['email']) : 'No email'; ?>
                        </div>
                        <div class="flex gap-1 mt-1 flex-wrap">
                          <?php if (!empty($user['city'])): ?>
                            <span class="badge badge-city">
                              <i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($user['city']); ?>
                            </span>
                          <?php endif; ?>
                          <?php if (!empty($user['gender'])): ?>
                            <span class="badge badge-gender">
                              <?php echo htmlspecialchars($user['gender']); ?>
                            </span>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="mt-4 space-y-3">
                    <div class="flex justify-between items-center">
                      <div class="text-sm text-gray-600">
                        <i class="fas fa-phone mr-1"></i> <?php echo isset($user['phone_number']) ? htmlspecialchars($user['phone_number']) : 'N/A'; ?>
                      </div>
                      <div class="badge badge-date">
                        <i class="far fa-calendar-alt mr-1"></i> <?php echo $formatted_date; ?>
                      </div>
                    </div>

                    <div class="flex items-center text-sm text-gray-600">
                      <i class="far fa-calendar mr-1"></i> DOB:
                      <?php echo isset($user['date_of_birth']) ? date('M d, Y', strtotime($user['date_of_birth'])) : 'N/A'; ?>
                    </div>

                    <div class="text-sm text-gray-600 line-clamp-2">
                      <i class="fas fa-map-pin mr-1"></i>
                      <?php echo isset($user['address']) ? htmlspecialchars($user['address']) : 'No address'; ?>
                    </div>

                    <div class="pt-3 mt-3 border-t border-gray-100 flex justify-between items-center">
                      <div class="flex space-x-2">
                        <button onclick="editUser(<?php echo isset($user['id']) ? $user['id'] : 0; ?>)"
                          class="px-3 py-1.5 bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition-colors">
                          <i class="fas fa-edit mr-1"></i> Edit
                        </button>
                        <button onclick="deleteUser(<?php echo isset($user['id']) ? $user['id'] : 0; ?>)"
                          class="px-3 py-1.5 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors">
                          <i class="fas fa-trash-alt mr-1"></i> Delete
                        </button>
                      </div>
                      <a href="user-details.php?id=<?php echo $user['id']; ?>"
                        class="px-3 py-1.5 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors">
                        <i class="fas fa-eye mr-1"></i> Details
                      </a>
                    </div>
                  </div>
                </div>
              <?php endwhile; ?>
            <?php else: ?>
              <div class="text-center p-6 bg-white rounded-lg shadow text-gray-500">
                <i class="fas fa-users text-4xl mb-3 text-gray-400"></i>
                <p class="text-lg">No users found</p>
                <p class="text-sm mt-2">Add new users to see them here</p>
              </div>
            <?php endif; ?>
          </div>

          <!-- Desktop view - Table -->
          <div class="hidden md:block overflow-hidden bg-white rounded-lg shadow">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50 sticky top-0">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Info</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <?php
                if ($result && $result->num_rows > 0) {
                  $result->data_seek(0);
                  while ($user = $result->fetch_assoc()):
                    $created_date = new DateTime($user['created_at']);
                    $formatted_date = $created_date->format('M d, Y');
                ?>
                    <tr class="hover:bg-gray-50">
                      <td class="px-6 py-4">
                        <div class="flex items-center">
                          <div class="flex-shrink-0 h-10 w-10">
                            <img class="h-10 w-10 rounded-full object-cover border border-gray-200"
                              src="../<?php echo isset($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'user/uploads/default.png'; ?>"
                              alt="User avatar" />
                          </div>
                          <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">
                              <?php echo isset($user['full_name']) ? htmlspecialchars($user['full_name']) : 'Unknown'; ?>
                            </div>
                            <div class="text-xs text-gray-500">
                              <span class="badge badge-gender">
                                <?php echo isset($user['gender']) ? htmlspecialchars($user['gender']) : 'Unknown'; ?>
                              </span>
                            </div>
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                          <i class="far fa-envelope mr-1 text-gray-500"></i>
                          <?php echo isset($user['email']) ? htmlspecialchars($user['email']) : 'No email'; ?>
                        </div>
                        <div class="text-sm text-gray-600 mt-1">
                          <i class="fas fa-phone mr-1 text-gray-500"></i>
                          <?php echo isset($user['phone_number']) ? htmlspecialchars($user['phone_number']) : 'No phone'; ?>
                        </div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                          <?php if (!empty($user['city'])): ?>
                            <span class="badge badge-city">
                              <?php echo htmlspecialchars($user['city']); ?>
                            </span>
                          <?php else: ?>
                            <span class="text-gray-500">No city</span>
                          <?php endif; ?>
                        </div>
                        <div class="text-sm text-gray-500 mt-1 max-w-xs truncate">
                          <?php echo isset($user['address']) ? htmlspecialchars($user['address']) : 'No address'; ?>
                        </div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                          <i class="far fa-calendar mr-1 text-gray-500"></i>
                          <?php echo isset($user['date_of_birth']) ? date('M d, Y', strtotime($user['date_of_birth'])) : 'N/A'; ?>
                        </div>
                        <div class="text-sm text-gray-500 mt-1">
                          <i class="far fa-clock mr-1"></i> Joined: <?php echo $formatted_date; ?>
                        </div>
                      </td>
                      <td class="px-6 py-4 text-sm font-medium">
                        <div class="flex space-x-2">
                          <button class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200"
                            onclick="editUser(<?php echo isset($user['id']) ? $user['id'] : 0; ?>)">
                            <i class="fas fa-edit mr-1"></i> Edit
                          </button>
                          <button class="px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200"
                            onclick="deleteUser(<?php echo isset($user['id']) ? $user['id'] : 0; ?>)">
                            <i class="fas fa-trash-alt mr-1"></i> Delete
                          </button>
                          <a href="user-details.php?id=<?php echo $user['id']; ?>"
                            class="px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                            <i class="fas fa-eye mr-1"></i> View
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php
                  endwhile;
                } else {
                  ?>
                  <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                      <i class="fas fa-users text-4xl mb-3 text-gray-400"></i>
                      <p class="text-lg">No users found</p>
                      <p class="text-sm mt-2">Add new users to see them here</p>
                    </td>
                  </tr>
                <?php
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <?php include 'includes/js-links.php'; ?>
    </div>
  </div>
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

      // Search and filtering functionality
      const searchInput = document.getElementById('searchInput');
      const filterCity = document.getElementById('filterCity');
      const filterGender = document.getElementById('filterGender');
      const resetFilters = document.getElementById('resetFilters');
      const userCards = document.querySelectorAll('.user-card');
      const tableRows = document.querySelectorAll('tbody tr');

      function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const cityFilter = filterCity.value.toLowerCase().trim();
        const genderFilter = filterGender.value.toLowerCase().trim();

        // Filter mobile cards
        userCards.forEach(card => {
          // Extract all text content from the card for search
          const cardText = card.textContent.toLowerCase();

          // Get full name
          const fullName = card.querySelector('h3')?.textContent.toLowerCase().trim() || '';

          // Get email - fixing the selector
          const emailEl = card.querySelector('.text-gray-600 i.far.fa-envelope');
          const email = emailEl ? emailEl.parentNode.textContent.toLowerCase().trim() : '';

          // Get phone - fixing the selector
          const phoneEl = card.querySelector('.text-gray-600 i.fas.fa-phone');
          const phone = phoneEl ? phoneEl.parentNode.textContent.toLowerCase().trim() : '';

          // Get city if it exists - extract just the text content
          const cityBadge = card.querySelector('.badge-city');
          const city = cityBadge ? cityBadge.textContent.toLowerCase().trim() : '';

          // Get gender if it exists - extract just the text content
          const genderBadge = card.querySelector('.badge-gender');
          const gender = genderBadge ? genderBadge.textContent.toLowerCase().trim() : '';

          // Check if card matches all filters
          const matchesSearch = searchTerm === '' ||
            fullName.includes(searchTerm) ||
            email.includes(searchTerm) ||
            phone.includes(searchTerm) ||
            cardText.includes(searchTerm);

          const matchesCity = cityFilter === '' || city.includes(cityFilter);
          const matchesGender = genderFilter === '' || gender === genderFilter;

          if (matchesSearch && matchesCity && matchesGender) {
            card.style.display = '';
          } else {
            card.style.display = 'none';
          }
        });

        // Filter table rows
        tableRows.forEach(row => {
          // Skip the "No users found" row
          if (row.cells.length === 1) return;

          // Get the text content of the entire row for thorough searching
          const rowText = row.textContent.toLowerCase();

          // Specific targeting of elements
          const fullName = row.querySelector('.text-sm.font-medium.text-gray-900')?.textContent.toLowerCase().trim() || '';

          // Get email - more robust way to extract it
          let email = '';
          const emailEl = row.querySelector('i.far.fa-envelope');
          if (emailEl && emailEl.parentNode) {
            email = emailEl.parentNode.textContent.toLowerCase().trim();
          }

          // Get phone - more robust way to extract it
          let phone = '';
          const phoneEl = row.querySelector('i.fas.fa-phone');
          if (phoneEl && phoneEl.parentNode) {
            phone = phoneEl.parentNode.textContent.toLowerCase().trim();
          }

          // Get city - extract just the text content
          const cityBadge = row.querySelector('.badge-city');
          const city = cityBadge ? cityBadge.textContent.toLowerCase().trim() : '';

          // Get gender - extract just the text content
          const genderBadge = row.querySelector('.badge-gender');
          const gender = genderBadge ? genderBadge.textContent.toLowerCase().trim() : '';

          // Check if row matches all filters
          const matchesSearch = searchTerm === '' ||
            fullName.includes(searchTerm) ||
            email.includes(searchTerm) ||
            phone.includes(searchTerm) ||
            rowText.includes(searchTerm);

          const matchesCity = cityFilter === '' || city.includes(cityFilter);
          const matchesGender = genderFilter === '' || gender === genderFilter;

          if (matchesSearch && matchesCity && matchesGender) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });
      }

      // Add event listeners
      if (searchInput) searchInput.addEventListener('input', applyFilters);
      if (filterCity) filterCity.addEventListener('change', applyFilters);
      if (filterGender) filterGender.addEventListener('change', applyFilters);

      if (resetFilters) {
        resetFilters.addEventListener('click', function() {
          if (searchInput) searchInput.value = '';
          if (filterCity) filterCity.value = '';
          if (filterGender) filterGender.value = '';
          applyFilters();
        });
      }
    });

    function editUser(userId) {
      if (userId > 0) {
        window.location.href = `edit-user.php?id=${userId}`;
      } else {
        Swal.fire({
          title: 'Error',
          text: 'Invalid user ID',
          icon: 'error'
        });
      }
    }

    function deleteUser(userId) {
      if (userId <= 0) {
        Swal.fire({
          title: 'Error',
          text: 'Invalid user ID',
          icon: 'error'
        });
        return;
      }

      Swal.fire({
        title: 'Are you sure?',
        text: "This will delete the user and all their associated data. This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading state
          Swal.fire({
            title: 'Deleting...',
            text: 'Please wait while we delete the user and associated data',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          fetch(`delete-user.php?id=${userId}`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              }
            })
            .then(response => {
              if (!response.ok) {
                throw new Error('Network response was not ok');
              }
              return response.json();
            })
            .then(data => {
              if (data.success) {
                Swal.fire({
                  title: 'Deleted!',
                  text: 'User and all associated data have been deleted.',
                  icon: 'success',
                  showConfirmButton: false,
                  timer: 1500
                }).then(() => {
                  window.location.reload();
                });
              } else {
                throw new Error(data.message || 'Failed to delete user');
              }
            })
            .catch(error => {
              console.error('Error:', error);
              Swal.fire({
                title: 'Error!',
                text: error.message || 'An error occurred while deleting the user',
                icon: 'error',
                confirmButtonColor: '#3085d6'
              });
            });
        }
      });
    }
  </script>
</body>

</html>