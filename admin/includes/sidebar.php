<div class="bg-gray-900 text-white w-64 space-y-6 py-7 px-2 hidden md:block relative" id="sidebar">
  <div class="flex items-center justify-between px-4 mb-6">
    <h2 class="text-2xl font-bold text-teal-400">Admin Panel</h2>
    <button class="md:hidden text-gray-300 hover:text-white transition-colors" id="close-sidebar">
      <i class="fas fa-times text-xl"></i>
    </button>
  </div>

  <nav class="space-y-2">
    <!-- Dashboard -->
    <a href="index.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition-colors group">
      <i class="fas fa-tachometer-alt text-gray-400 group-hover:text-teal-400"></i>
      <span class="group-hover:text-teal-400">Dashboard</span>
    </a>

    <!-- Users Section -->
    <div class="sidebar-section">
      <button class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-800 transition-colors group"
        onclick="toggleDropdown('users-dropdown')">
        <div class="flex items-center space-x-3">
          <i class="fas fa-users text-gray-400 group-hover:text-teal-400"></i>
          <span class="group-hover:text-teal-400">Users</span>
        </div>
        <i class="fas fa-chevron-down text-sm transition-transform duration-200"></i>
      </button>
      <div class="hidden sidebar-dropdown pl-12 mt-2 space-y-2" id="users-dropdown">
        <a href="all-users.php" class="block py-2 text-gray-400 hover:text-teal-400 transition-colors">All Users</a>
      </div>
    </div>

    <!-- Flight Section -->
    <div class="sidebar-section">
      <button class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-800 transition-colors group"
        onclick="toggleDropdown('flight-dropdown')">
        <div class="flex items-center space-x-3">
          <i class="fas fa-plane text-gray-400 group-hover:text-teal-400"></i>
          <span class="group-hover:text-teal-400">Flights</span>
        </div>
        <i class="fas fa-chevron-down text-sm transition-transform duration-200"></i>
      </button>
      <div class="hidden sidebar-dropdown pl-12 mt-2 space-y-2" id="flight-dropdown">
        <a href="view-flight.php" class="block py-2 text-gray-400 hover:text-teal-400 transition-colors">View Flights</a>
        <a href="add-flight.php" class="block py-2 text-gray-400 hover:text-teal-400 transition-colors">Add Flight</a>
        <a href="booked-flight.php" class="block py-2 text-gray-400 hover:text-teal-400 transition-colors">Booked Flights</a>
      </div>
    </div>

    <!-- Transportation Section -->
    <div class="sidebar-section">
      <button class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-800 transition-colors group"
        onclick="toggleDropdown('transportation-dropdown')">
        <div class="flex items-center space-x-3">
          <i class="fas fa-bus text-gray-400 group-hover:text-teal-400"></i>
          <span class="group-hover:text-teal-400">Transportation</span>
        </div>
        <i class="fas fa-chevron-down text-sm transition-transform duration-200"></i>
      </button>
      <div class="hidden sidebar-dropdown pl-12 mt-2 space-y-2" id="transportation-dropdown">
        <a href="view-transportation.php" class="block py-2 text-gray-400 hover:text-teal-400 transition-colors">Manage Transportation</a>
        <!-- <a href="add-transportation.php" class="block py-2 text-gray-400 hover:text-teal-400 transition-colors">Add Transportation</a> -->
        <a href="booked-transportation.php" class="block py-2 text-gray-400 hover:text-teal-400 transition-colors">Booked Transportation</a>
      </div>
    </div>

    <!-- Hotels Section -->
    <div class="sidebar-section">
      <button class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-800 transition-colors group"
        onclick="toggleDropdown('hotels-dropdown')">
        <div class="flex items-center space-x-3">
          <i class="fas fa-hotel text-gray-400 group-hover:text-teal-400"></i>
          <span class="group-hover:text-teal-400">Hotels</span>
        </div>
        <i class="fas fa-chevron-down text-sm transition-transform duration-200"></i>
      </button>
      <div class="hidden sidebar-dropdown pl-12 mt-2 space-y-2" id="hotels-dropdown">
        <a href="view-hotels.php" class="block py-2 text-gray-400 hover:text-teal-400 transition-colors">View Hotels</a>
        <a href="add-hotels.php" class="block py-2 text-gray-400 hover:text-teal-400 transition-colors">Add Hotel</a>
        <a href="booked-hotels.php" class="block py-2 text-gray-400 hover:text-teal-400 transition-colors">Booked Hotels</a>
      </div>
    </div>


    <!-- Packages Section -->
    <div class="sidebar-section">
      <button class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-800 transition-colors group"
        onclick="toggleDropdown('packages-dropdown')">
        <div class="flex items-center space-x-3">
          <i class="fas fa-box text-gray-400 group-hover:text-teal-400"></i>
          <span class="group-hover:text-teal-400">Packages</span>
        </div>
        <i class="fas fa-chevron-down text-sm transition-transform duration-200"></i>
      </button>
      <div class="hidden sidebar-dropdown pl-12 mt-2 space-y-2" id="packages-dropdown">
        <a href="view-package.php" class="block py-2 text-gray-400 hover:text-teal-400 transition-colors">View Packages</a>
        <a href="add-packages.php" class="block py-2 text-gray-400 hover:text-teal-400 transition-colors">Add Package</a>
        <a href="booked-packages.php" class="block py-2 text-gray-400 hover:text-teal-400 transition-colors">Booked Packaes</a>
      </div>
    </div>





    <!-- Assigning Section -->
    <div class="sidebar-section">
      <button class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-800 transition-colors group"
        onclick="toggleDropdown('assign-dropdown')">
        <div class="flex items-center space-x-3">
          <i class="fas fa-tasks text-gray-400 group-hover:text-teal-400"></i>
          <span class="group-hover:text-teal-400">Assigning</span>
        </div>
        <i class="fas fa-chevron-down text-sm transition-transform duration-200"></i>
      </button>
      <div class="hidden sidebar-dropdown pl-12 mt-2 space-y-2" id="assign-dropdown">
        <a href="transportation-assign.php" class="block py-2 text-gray-400 hover:text-teal-400 transition-colors">Assign Transportation</a>
        <a href="assign-flight.php" class="block py-2 text-gray-400 hover:text-teal-400 transition-colors">Assign Flight</a>
        <a href="hotel-assign.php" class="block py-2 text-gray-400 hover:text-teal-400 transition-colors">Assign Hotel</a>
      </div>
    </div>

    <a href="table-view.php"
      class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition-colors group mt-4">
      <i class="fas fa-chart-line text-gray-400 group-hover:text-red-400"></i>
      <span class="group-hover:text-yellow-400">Advance Analysis</span>
    </a>

    <!-- Logout -->
    <a href="logout.php" onclick="confirmLogout(even8t)"
      class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition-colors group mt-4">
      <i class="fas fa-sign-out-alt text-gray-400 group-hover:text-red-400"></i>
      <span class="group-hover:text-red-400">Logout</span>
    </a>

  </nav>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const dropdowns = document.querySelectorAll('.sidebar-dropdown');
    const dropdownButtons = document.querySelectorAll('.sidebar-section button');

    function toggleDropdown(id) {
      const dropdown = document.getElementById(id);
      const button = dropdown.previousElementSibling;
      const icon = button.querySelector('.fa-chevron-down');

      dropdowns.forEach(d => {
        if (d.id !== id) {
          d.classList.add('hidden');
          d.previousElementSibling.querySelector('.fa-chevron-down').style.transform = 'rotate(0deg)';
        }
      });

      dropdown.classList.toggle('hidden');
      icon.style.transform = dropdown.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
    }

    // Mobile sidebar toggle
    const sidebar = document.getElementById('sidebar');
    const closeBtn = document.getElementById('close-sidebar');
    const menuBtn = document.getElementById('menu-btn');

    if (menuBtn) {
      menuBtn.addEventListener('click', () => {
        sidebar.classList.add('active');
      });
    }

    if (closeBtn) {
      closeBtn.addEventListener('click', () => {
        sidebar.classList.remove('active');
      });
    }

    // Enhanced logout confirmation
    window.confirmLogout = function(event) {
      event.preventDefault();
      Swal.fire({
        title: 'Ready to Leave?',
        text: 'Select "Logout" below if you want to end your current session.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Logout',
        cancelButtonText: 'Cancel',
        customClass: {
          confirmButton: 'btn btn-primary',
          cancelButton: 'btn btn-secondary'
        }
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = 'logout.php';
        }
      });
    }
  });

  function toggleDropdown(id) {
    let dropdown = document.getElementById(id);
    dropdown.classList.toggle('hidden');
  }
</script>





<style>
  #sidebar {
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #4fd1c5 #1a202c;
  }

  /* For Webkit browsers (Chrome, Safari) */
  #sidebar::-webkit-scrollbar {
    width: 6px;
  }

  #sidebar::-webkit-scrollbar-track {
    background: #1a202c;
    border-radius: 10px;
  }

  #sidebar::-webkit-scrollbar-thumb {
    background: #4fd1c5;
    border-radius: 10px;
  }

  #sidebar::-webkit-scrollbar-thumb:hover {
    background: #38b2ac;
  }

  #sidebar-dropdown {
    transition: all 0.3s ease;
  }

  @media screen and (max-width: 425px) {
    #sidebar {
      position: fixed;
      width: 100% !important;
      height: 100vh;
      z-index: 50;
      transform: translateX(-100%);
      transition: transform 0.3s ease-in-out;
    }

    #sidebar.active {
      transform: translateX(0);
    }
  }
</style>