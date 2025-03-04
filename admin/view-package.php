<?php
include 'includes/db-config.php';

// Fetch all packages from the database
$sql = "SELECT * FROM packages";
$stmt = $pdo->query($sql);
$packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Umrah Packages - Detailed View</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-100">
  <!-- Filter Modal -->
  <div id="filterModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <h3 class="text-lg font-medium text-gray-900">Filter Packages</h3>
        <form id="filterForm" class="mt-4">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Package Type</label>
            <select name="package_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
              <option value="">All Types</option>
              <option value="Economy">Economy</option>
              <option value="Standard">Standard</option>
              <option value="Premium">Premium</option>
            </select>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Price Range</label>
            <div class="flex gap-2">
              <input type="number" name="min_price" placeholder="Min" class="mt-1 block w-full rounded-md border-gray-300">
              <input type="number" name="max_price" placeholder="Max" class="mt-1 block w-full rounded-md border-gray-300">
            </div>
          </div>
          <div class="flex justify-end gap-2 mt-4">
            <button type="button" onclick="closeFilterModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded-md">Apply Filter</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="flex h-screen">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main flex-1 flex flex-col">
      <div class="bg-white shadow-md py-4 px-4 sm:px-6 flex justify-between items-center">
        <!-- Menu Button (Left) -->
        <button class="md:hidden text-gray-800" id="menu-btn">
          <i class="fas fa-bars"></i>
        </button>

        <!-- Title -->
        <h1 class="text-lg sm:text-xl font-semibold">
          <i class="text-teal-600 fas fa-box mx-2"></i> Umrah Packages
        </h1>

        <!-- Back Button (Right) -->
        <a href="add-packages.php" class="flex items-center text-gray-700 hover:text-gray-900">
          <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
      </div>


      <div class="overflow-auto container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="mx-auto bg-white p-4 sm:p-8 rounded-lg shadow-lg">
          <div class="p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center border-b space-y-4 sm:space-y-0">
            <div class="flex flex-wrap gap-2">
              <button class="bg-teal-600 text-white px-3 sm:px-4 py-2 rounded hover:bg-teal-500 text-sm sm:text-base" onclick="window.location.href='add-packages.php'">
                <i class="fas fa-plus mr-2"></i>Add Package
              </button>
              <button onclick="openFilterModal()" class="bg-gray-500 text-white px-3 sm:px-4 py-2 rounded hover:bg-gray-400 text-sm sm:text-base">
                <i class="fas fa-filter mr-2"></i>Filter
              </button>
            </div>
            <div class="relative w-full sm:w-auto">
              <input type="search" id="searchInput" placeholder="Search packages..."
                class="w-full sm:w-auto pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:border-teal-500">
              <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            </div>
          </div>

          <!-- Table Content -->
          <div class="overflow-x-auto" id="packageTableContainer">
            <table class="min-w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Package Info</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Departure & Arrival City</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($packages as $package): ?>
                  <tr class="hover:bg-gray-50">
                    <td class="px-4 py-4 text-sm text-gray-900">
                      #<?= htmlspecialchars($package['id']) ?>
                    </td>
                    <td class="px-4 py-4">
                      <div class="flex items-center">
                        <img class="h-10 w-10 rounded-full object-cover"
                          src="<?= htmlspecialchars($package['package_image']) ?>"
                          alt="Package Image">
                        <div class="ml-4">
                          <div class="text-sm font-medium text-gray-900">
                            <?= htmlspecialchars($package['title']) ?>
                          </div>
                          <div class="text-sm text-gray-500">
                            <?= nl2br(htmlspecialchars(substr($package['description'], 0, 100))) ?>...
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="px-4 py-4">
                      <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-teal-100 text-teal-800">
                        <?= htmlspecialchars($package['package_type']) ?>
                      </span>
                    </td>
                    <td class="px-4 py-4">
                      <div class="text-sm">
                        <div class="text-gray-900">
                          <i class="fas fa-plane-departure text-gray-400 mr-1"></i>
                          <?= htmlspecialchars($package['departure_city']) ?>
                        </div>
                        <div class="text-gray-900">
                          <i class="fas fa-plane-arrival text-gray-400 mr-1"></i>
                          <?= htmlspecialchars($package['arrival_city']) ?>
                        </div>
                      </div>
                    </td>
                    <td class="px-4 py-4">
                      <div class="text-sm font-medium text-gray-900">
                        $<?= number_format($package['price'], 2) ?>
                      </div>
                    </td>
                    <td class="px-4 py-4 text-sm font-medium">
                      <button onclick="window.location.href='view-package-details.php?id=<?= $package['id'] ?>'" class="text-teal-600 hover:text-teal-900 mr-2">
                        <i class="fas fa-eye"></i>
                      </button>

                      <button onclick="editPackage(<?= $package['id'] ?>)"
                        class="text-blue-600 hover:text-blue-900 mr-2">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button onclick="deletePackage(<?= $package['id'] ?>)"
                        class="text-red-600 hover:text-red-900">
                        <i class="fas fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>


          <!-- Pagination -->
          <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
              <div class="text-sm text-gray-700 w-full sm:w-auto text-center sm:text-left">
                Showing <span class="font-medium">1</span> to
                <span class="font-medium"><?= count($packages) ?></span> of
                <span class="font-medium"><?= count($packages) ?></span> results
              </div>
              <div class="flex justify-center w-full sm:w-auto">
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                  <button class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <i class="fas fa-chevron-left"></i>
                  </button>
                  <button class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</button>
                  <button class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">2</button>
                  <button class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <i class="fas fa-chevron-right"></i>
                  </button>
                </nav>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    function editPackage(id) {
      window.location.href = `edit-package.php?id=${id}`;
    }

    function deletePackage(id) {
      Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          fetch(`delete-package.php?id=${id}`, {
              method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                Swal.fire({
                  title: 'Deleted!',
                  text: 'Package has been deleted.',
                  icon: 'success'
                }).then(() => {
                  window.location.reload(); // This will refresh the page
                });
              }
            });
        }
      });
    }

    // Modal functions
    function openFilterModal() {
      document.getElementById('filterModal').classList.remove('hidden');
    }

    function closeFilterModal() {
      document.getElementById('filterModal').classList.add('hidden');
    }

    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function(e) {
      const searchValue = e.target.value.toLowerCase();
      const tableRows = document.querySelectorAll('tbody tr');

      tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchValue) ? '' : 'none';
      });
    });

    // Filter form submission
    document.getElementById('filterForm').addEventListener('submit', function(e) {
      e.preventDefault();
      // Add your filter logic here
      closeFilterModal();
    });
  </script>
</body>

</html>