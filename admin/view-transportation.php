<?php
session_start();
include 'connection/connection.php';

// Add search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// Build the query with search and filter
$query = "SELECT * FROM transportation WHERE 1=1";
if ($search) {
  $query .= " AND (transport_name LIKE '%$search%' OR transport_id LIKE '%$search%' OR location LIKE '%$search%')";
}
if ($category_filter) {
  $query .= " AND category = '$category_filter'";
}
$query .= " ORDER BY transport_id DESC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Transportation List</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
          <!-- Search and Filter Section -->
          <div class="mb-6 flex flex-col sm:flex-row gap-4 sm:items-center sm:justify-between">
            <h1 class="text-2xl font-bold text-gray-800">Vehicle Management</h1>
            <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
              <input type="text" id="searchInput" placeholder="Search vehicles..."
                class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 w-full sm:w-auto"
                value="<?php echo htmlspecialchars($search); ?>">
              <select id="categoryFilter"
                class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 w-full sm:w-auto">
                <option value="">All Categories</option>
                <option value="luxury" <?php echo $category_filter == 'luxury' ? 'selected' : ''; ?>>Luxury</option>
                <option value="standard" <?php echo $category_filter == 'standard' ? 'selected' : ''; ?>>Standard</option>
                <option value="economy" <?php echo $category_filter == 'economy' ? 'selected' : ''; ?>>Economy</option>
              </select>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seats</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Available Time</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                  <tr class="hover:bg-gray-50">
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
                        <img src="<?php echo htmlspecialchars($row['transport_image']); ?>"
                          alt="Vehicle" class="h-12 w-16 object-cover rounded">
                        <div class="ml-4">
                          <div class="font-medium text-gray-900"><?php echo htmlspecialchars($row['transport_name']); ?></div>
                          <div class="text-sm text-gray-500"><?php echo htmlspecialchars($row['transport_id']); ?></div>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($row['location']); ?></td>
                    <td class="px-6 py-4 text-gray-600">
                      <div class="truncate max-w-xs" title="<?php echo htmlspecialchars($row['details']); ?>">
                        <?php echo htmlspecialchars($row['details']); ?>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-gray-600"><?php echo $row['seats']; ?></td>
                    <td class="px-6 py-4 text-gray-600">
                      <?php echo date('h:i A', strtotime($row['time_from'])); ?> -
                      <?php echo date('h:i A', strtotime($row['time_to'])); ?>
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
                          data-id="<?php echo $row['transport_id']; ?>">
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

  <script>
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