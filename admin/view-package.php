<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Umrah Packages - Table View</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-100">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main flex-1 flex flex-col">
      <!-- Navbar -->
      <div class="bg-white shadow-md py-4 px-4 sm:px-6 flex justify-between items-center">
        <button class="md:hidden text-gray-800" id="menu-btn">
          <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-lg sm:text-xl font-semibold"><i class="text-teal-600 fas fa-box mx-2"></i> Add Packages</h1>
      </div>

      <div class="overflow-auto container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="mx-auto bg-white p-4 sm:p-8 rounded-lg shadow-lg">
          <!-- Table Header -->
          <div class="p-4 sm:p-6 border-b">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Umrah Packages</h2>
          </div>

          <!-- Table Actions -->
          <div class="p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center border-b space-y-4 sm:space-y-0">
            <div class="flex flex-wrap gap-2">
              <button class="bg-teal-600 text-white px-3 sm:px-4 py-2 rounded hover:bg-teal-500 text-sm sm:text-base">
                <i class="fas fa-plus mr-2"></i>Add Package
              </button>
              <button class="bg-gray-500 text-white px-3 sm:px-4 py-2 rounded hover:bg-gray-400 text-sm sm:text-base">
                <i class="fas fa-filter mr-2"></i>Filter
              </button>
            </div>
            <div class="relative w-full sm:w-auto">
              <input type="search" placeholder="Search packages..."
                class="w-full sm:w-auto pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:border-teal-500">
              <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            </div>
          </div>

          <!-- Table Content -->
          <div class="overflow-x-auto -mx-4 sm:mx-0">
            <table class="min-w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Package</th>
                  <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                  <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                  <th class="hidden sm:table-cell px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                  <th class="hidden sm:table-cell px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <!-- Mobile-friendly row structure -->
                <tr class="hover:bg-gray-50">
                  <td class="px-3 sm:px-6 py-4">
                    <div class="flex items-center">
                      <img class="h-8 w-8 sm:h-10 sm:w-10 rounded-full object-cover" src="https://images.unsplash.com/photo-1565552645632-d725f8bfc19a?ixlib=rb-4.0.3" alt="">
                      <div class="ml-2 sm:ml-4">
                        <div class="text-sm font-medium text-gray-900">Deluxe Umrah Package</div>
                        <div class="text-xs sm:text-sm text-gray-500">Emirates Airlines</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-3 sm:px-6 py-4">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-teal-100 text-teal-800">VIP</span>
                  </td>
                  <td class="px-3 sm:px-6 py-4">
                    <div class="text-sm text-gray-900">$4,999</div>
                  </td>
                  <td class="hidden sm:table-cell px-3 sm:px-6 py-4">
                    <div class="text-sm text-gray-900">15 Days</div>
                  </td>
                  <td class="hidden sm:table-cell px-3 sm:px-6 py-4">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                  </td>
                  <td class="px-3 sm:px-6 py-4 text-sm font-medium">
                    <button class="text-teal-600 hover:text-teal-900 mr-3"><i class="fas fa-edit"></i></button>
                    <button class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Mobile-friendly pagination -->
          <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
              <div class="text-sm text-gray-700 w-full sm:w-auto text-center sm:text-left">
                Showing <span class="font-medium">1</span> to <span class="font-medium">2</span> of <span class="font-medium">8</span> results
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
<?php include 'includes/js-links.php'; ?>
</body>

</html>