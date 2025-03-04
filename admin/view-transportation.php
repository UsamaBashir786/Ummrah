<?php
session_start();
include 'connection/connection.php';

// Add search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// Get current time
$current_time = date('H:i:s');

// Build the query for current transportation
$query_current = "SELECT * FROM transportation WHERE time_to > '$current_time'";
if ($search) {
  $query_current .= " AND (transport_name LIKE '%$search%' OR transport_id LIKE '%$search%' OR location LIKE '%$search%')";
}
if ($category_filter) {
  $query_current .= " AND category = '$category_filter'";
}
$query_current .= " ORDER BY transport_id DESC";

// Build the query for past transportation
$query_past = "SELECT * FROM transportation WHERE time_to <= '$current_time'";
if ($search) {
  $query_past .= " AND (transport_name LIKE '%$search%' OR transport_id LIKE '%$search%' OR location LIKE '%$search%')";
}
if ($category_filter) {
  $query_past .= " AND category = '$category_filter'";
}
$query_past .= " ORDER BY transport_id DESC";

$result_current = mysqli_query($conn, $query_current);
$result_past = mysqli_query($conn, $query_past);
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
</head>

<body class="bg-gray-50">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="overflow-y-scroll main flex-1 flex flex-col">
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <h1 class="text-xl font-semibold">
          <i class="text-teal-600 fas fa-car mx-2"></i> Transportation List
        </h1>
        <a href="add-transportation.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
          <i class="fas fa-plus mr-2"></i>Add New Vehicle
        </a>
      </div>

      <div class="container mx-auto px-4 py-8">
        <div class="bg-white p-6 rounded-lg shadow-lg">
          <!-- Tabs Navigation -->
          <div class="mb-6 flex justify-center space-x-4">
            <button class="tablink flex items-center bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500" onclick="openTab(event, 'current')">
              <i class="fas fa-car-side mr-2"></i>
              Current Transportations
            </button>
            <button class="tablink flex items-center bg-gray-300 text-gray-700 px-4 py-2 rounded-lg shadow hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500" onclick="openTab(event, 'past')">
              <i class="fas fa-history mr-2"></i>
              Past Transportations
            </button>
          </div>

          <!-- Current Transportations Tab -->
          <div id="current" class="tabcontent">
            <div class="overflow-x-auto">
              <table class="min-w-full">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Available Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <?php while ($row = mysqli_fetch_assoc($result_current)) : ?>
                    <tr class="hover:bg-gray-50">
                      <td class="px-6 py-4"><?php echo htmlspecialchars($row['id']); ?></td>
                      <td class="px-6 py-4">
                        <span class="px-2 py-1 text-sm rounded-full 
                        <?php echo match ($row['category']) {
                          'luxury' => 'bg-purple-100 text-purple-800',
                          'standard' => 'bg-blue-100 text-blue-800',
                          'economy' => 'bg-green-100 text-green-800',
                          default => 'bg-gray-100 text-gray-800'
                        }; ?>">
                          <?php echo ucfirst($row['category']); ?>
                        </span>
                      </td>
                      <td class="px-6 py-4">
                        <div class="flex items-center">
                          <!-- <img src="<?php echo htmlspecialchars($row['transport_image']); ?>" alt="Vehicle" class="h-12 w-16 object-cover rounded"> -->
                          <div class="ml-4">
                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($row['transport_name']); ?></div>
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($row['location']); ?></td>
                      <td class="px-6 py-4 text-gray-600">
                        <?php echo date('h:i A', strtotime($row['time_from'])); ?> - <?php echo date('h:i A', strtotime($row['time_to'])); ?>
                      </td>
                      <td class="px-6 py-4">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                        <?php echo $row['status'] == 'available' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                          <?php echo ucfirst($row['status']); ?>
                        </span>
                      </td>
                      <td class="px-6 py-4">
                        <div class="flex space-x-3">
                          <button class="text-blue-600 hover:text-blue-900"
                            onclick="window.location.href='edit-transportation.php?id=<?php echo $row['transport_id']; ?>'">
                            <i class="fas fa-edit"></i>
                          </button>
                          <button class="text-red-600 hover:text-red-900 delete-btn"
                            data-id="<?php echo htmlspecialchars($row['transport_id']); ?>">
                            <i class="fas fa-trash"></i>
                          </button>
                          <button class="text-green-600 hover:text-green-900"
                            onclick="window.location.href='view-transportation-details.php?id=<?php echo $row['transport_id']; ?>'">
                            <i class="fas fa-eye"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Past Transportations Tab -->
          <div id="past" class="tabcontent" style="display:none">
            <div class="overflow-x-auto">
              <table class="min-w-full">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Available Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <?php while ($row = mysqli_fetch_assoc($result_past)) : ?>
                    <tr class="hover:bg-gray-50">
                      <td class="px-6 py-4"><?php echo htmlspecialchars($row['id']); ?></td>
                      <td class="px-6 py-4">
                        <span class="px-2 py-1 text-sm rounded-full 
                        <?php echo match ($row['category']) {
                          'luxury' => 'bg-purple-100 text-purple-800',
                          'standard' => 'bg-blue-100 text-blue-800',
                          'economy' => 'bg-green-100 text-green-800',
                          default => 'bg-gray-100 text-gray-800'
                        }; ?>">
                          <?php echo ucfirst($row['category']); ?>
                        </span>
                      </td>
                      <td class="px-6 py-4">
                        <div class="flex items-center">
                          <!-- <img src="<?php echo htmlspecialchars($row['transport_image']); ?>" alt="Vehicle" class="h-12 w-16 object-cover rounded"> -->
                          <div class="ml-4">
                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($row['transport_name']); ?></div>
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($row['location']); ?></td>
                      <td class="px-6 py-4 text-gray-600">
                        <?php echo date('h:i A', strtotime($row['time_from'])); ?> - <?php echo date('h:i A', strtotime($row['time_to'])); ?>
                      </td>
                      <td class="px-6 py-4">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                        <?php echo $row['status'] == 'available' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                          <?php echo ucfirst($row['status']); ?>
                        </span>
                      </td>
                      <td class="px-6 py-4">
                        <div class="flex space-x-3">
                          <button class="text-blue-600 hover:text-blue-900"
                            onclick="window.location.href='edit-transportation.php?id=<?php echo $row['transport_id']; ?>'">
                            <i class="fas fa-edit"></i>
                          </button>
                          <button class="text-red-600 hover:text-red-900 delete-btn"
                            data-id="<?php echo htmlspecialchars($row['transport_id']); ?>">
                            <i class="fas fa-trash"></i>
                          </button>
                          <button class="text-green-600 hover:text-green-900"
                            onclick="window.location.href='view-transportation-details.php?id=<?php echo $row['transport_id']; ?>'">
                            <i class="fas fa-eye"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script>
    // Enhanced sidebar toggle with animations
    document.getElementById("menu-btn").addEventListener("click", function() {
      const sidebar = document.getElementById("sidebar");

      if (sidebar.classList.contains('hidden')) {
        sidebar.classList.remove('hidden');
        gsap.fromTo("#sidebar", {
          x: -100,
          opacity: 0
        }, {
          duration: 0.5,
          x: 0,
          opacity: 1,
          ease: "power3.out"
        });
      } else {
        gsap.to("#sidebar", {
          duration: 0.5,
          x: -100,
          opacity: 0,
          ease: "power3.in",
          onComplete: () => {
            sidebar.classList.add('hidden');
          }
        });
      }
    });

    // Enhanced close button functionality
    document.getElementById("close-sidebar").addEventListener("click", function() {
      const sidebar = document.getElementById("sidebar");

      gsap.to("#sidebar", {
        duration: 0.5,
        x: -100,
        opacity: 0,
        ease: "power3.in",
        onComplete: () => {
          sidebar.classList.add('hidden');
          // Remove any inline styles added by GSAP
          sidebar.style.transform = '';
          sidebar.style.opacity = '';
        }
      });
    });

    // Function to handle dropdown animations
    function toggleDropdown(dropdownId) {
      const dropdown = document.getElementById(dropdownId);
      const chevron = dropdown.previousElementSibling.querySelector('.fa-chevron-down');

      if (dropdown.classList.contains('hidden')) {
        // Show dropdown
        dropdown.classList.remove('hidden');
        gsap.fromTo(dropdown, {
          height: 0,
          opacity: 0
        }, {
          height: 'auto',
          opacity: 1,
          duration: 0.3,
          ease: "power2.out"
        });

        // Animate dropdown links
        gsap.fromTo(dropdown.querySelectorAll('a'), {
          y: -10,
          opacity: 0
        }, {
          y: 0,
          opacity: 1,
          duration: 0.3,
          stagger: 0.05,
          ease: "power2.out"
        });

        // Rotate chevron down
        gsap.to(chevron, {
          duration: 0.3,
          rotation: 180
        });
      } else {
        // Hide dropdown
        gsap.to(dropdown, {
          height: 0,
          opacity: 0,
          duration: 0.3,
          ease: "power2.in",
          onComplete: () => {
            dropdown.classList.add('hidden');
            // Reset height after animation
            dropdown.style.height = '';
          }
        });

        // Rotate chevron up
        gsap.to(chevron, {
          duration: 0.3,
          rotation: 0
        });
      }
    }

    // Tab functionality
    function openTab(evt, tabName) {
      var i, tabcontent, tablinks;
      tabcontent = document.getElementsByClassName("tabcontent");
      for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
      }
      tablinks = document.getElementsByClassName("tablink");
      for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" bg-blue-600 text-white", " bg-gray-300 text-gray-700");
      }
      document.getElementById(tabName).style.display = "block";
      evt.currentTarget.className += " bg-blue-600 text-white";
    }

    document.addEventListener('DOMContentLoaded', function() {
      // Search and Filter Implementation
      const searchInput = document.getElementById('searchInput');
      const categoryFilter = document.getElementById('categoryFilter');

      function updateURL() {
        const searchValue = searchInput.value;
        const categoryValue = categoryFilter.value;
        const url = new URL(window.location.href);

        if (searchValue) url.searchParams.set('search', searchValue);
        else url.searchParams.delete('search');

        if (categoryValue) url.searchParams.set('category', categoryValue);
        else url.searchParams.delete('category');

        window.location.href = url.toString();
      }

      searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') updateURL();
      });

      categoryFilter.addEventListener('change', updateURL);

      // Delete Confirmation
      document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function() {
          const transportId = this.getAttribute('data-id');
          Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
          }).then((result) => {
            if (result.isConfirmed) {
              window.location.href = `delete-transportation.php?id=${transportId}`;
            }
          });
        });
      });
    });
  </script>

  <?php include 'includes/js-links.php'; ?>
</body>

</html>