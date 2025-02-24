  <!-- Mobile Top Bar -->
  <div class="topbar bg-gray-800 text-white p-4 shadow-md">
    <div class="flex justify-between items-center">
      <button id="sidebarToggle" class="text-white focus:outline-none">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
      <span class="font-medium text-lg">Dashboard</span>
      <div class="w-6"></div>
    </div>
  </div>

  <!-- Sidebar -->
  <div class="sidebar bg-gray-800 w-64 text-white shadow-lg">
    <div class="p-4 md:p-6 lg:p-8">
      <div class="flex items-center space-x-4 mb-4">
        <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover border-2 border-teal-500">
        <div>
          <p class="font-medium text-lg"><?php echo htmlspecialchars($user['full_name']); ?></p>
          <p class="text-sm text-gray-400">User Dashboard</p>
        </div>
      </div>

      <nav class="space-y-1">
        <a href="index.php" class="block py-2 px-3 md:px-4 rounded transition duration-200 hover:bg-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg> Dashboard
        </a>
        <a href="profile.php" class="block py-2 px-3 md:px-4 rounded transition duration-200 hover:bg-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg> My Profile
        </a>
        <a href="bookings.php" class="block py-2 px-3 md:px-4 rounded transition duration-200 hover:bg-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg> My Bookings
        </a>
        <a href="packages.php" class="block py-2 px-3 md:px-4 rounded transition duration-200 hover:bg-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg> Packages
        </a>
        <a href="payments.php" class="block py-2 px-3 md:px-4 rounded transition duration-200 hover:bg-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
          </svg> Payments
        </a>
        <a href="notifications.php" class="block py-2 px-3 md:px-4 rounded transition duration-200 hover:bg-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg> Notifications
        </a>
        <a href="settings.php" class="block py-2 px-3 md:px-4 rounded transition duration-200 hover:bg-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg> Settings
        </a>
        <a href="../logout.php" class="block py-2 px-3 md:px-4 rounded transition duration-200 hover:bg-gray-700 text-red-400">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg> Logout
        </a>
      </nav>
    </div>
  </div>
  <style>
    .sidebar {
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      padding-top: 60px;
      transition: all 0.3s;
      z-index: 40;
      box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    }

    .main-content {
      margin-left: 250px;
      padding-top: 60px;
      padding: 1rem;
      transition: all 0.3s;
    }

    @media (min-width: 768px) {
      .main-content {
        padding: 1.5rem;
      }
    }

    @media (min-width: 1024px) {
      .main-content {
        padding: 2rem;
      }
    }

    .topbar {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 50;
    }

    @media (max-width: 768px) {
      .sidebar {
        margin-left: -250px;
      }

      .main-content {
        margin-left: 0;
      }

      .sidebar.active {
        margin-left: 0px;
        width: 100% !important;
        height: 100vh;
        overflow-y: auto !important;
        scrollbar-width: thin;
        scrollbar-color: rgba(156, 163, 175, 0.5) rgba(31, 41, 55, 0.8);
      }

      .sidebar.active::-webkit-scrollbar {
        width: 8px;
      }

      .sidebar.active::-webkit-scrollbar-track {
        background: rgba(31, 41, 55, 0.8);
      }

      .sidebar.active::-webkit-scrollbar-thumb {
        background-color: rgba(156, 163, 175, 0.5);
        border-radius: 4px;
      }

      .sidebar.active~.main-content {
        display: none !important;
      }

      .topbar {
        display: block;
      }
    }
  </style>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const sidebarToggle = document.getElementById('sidebarToggle');
      const sidebar = document.querySelector('.sidebar');
      const mainContent = document.querySelector('.main-content');

      sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        if (window.innerWidth <= 768) {
          mainContent.style.marginLeft = sidebar.classList.contains('active') ? '250px' : '0';
        }
      });

      document.addEventListener('click', function(event) {
        if (window.innerWidth <= 768) {
          const isClickInsideSidebar = sidebar.contains(event.target);
          const isClickOnToggle = sidebarToggle.contains(event.target);

          if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
            mainContent.style.marginLeft = '0';
          }
        }
      });

      window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
          mainContent.style.marginLeft = '250px';
        } else {
          mainContent.style.marginLeft = sidebar.classList.contains('active') ? '250px' : '0';
        }
      });
    });
  </script>