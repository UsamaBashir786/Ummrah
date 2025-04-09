<!-- Sidebar -->
<div class="sidebar bg-gradient-to-b from-gray-900 to-gray-800 w-64 text-white shadow-xl transition-all duration-300 ease-in-out">
  <div class="p-6">
    <div class="flex items-center space-x-4 mb-6 pb-4 border-b border-gray-700">
      <div class="relative">
        <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="w-14 h-14 rounded-full object-cover border-2 border-blue-400">
        <div class="absolute bottom-0 right-0 bg-green-500 h-3 w-3 rounded-full border-2 border-gray-900"></div>
      </div>
      <div>
        <p class="font-medium text-lg text-white"><?php echo htmlspecialchars($user['full_name']); ?></p>
        <p class="text-xs text-blue-300">User Account</p>
      </div>
    </div>

    <div class="menu-section mb-4">
      <p class="text-xs uppercase tracking-wider text-gray-500 mb-2 pl-2">Main Menu</p>
      <nav class="space-y-1">
        <a href="index.php" class="sidebar-link flex items-center py-3 px-4 rounded-lg transition-all duration-200 hover:bg-blue-700 hover:pl-6">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          <span>Dashboard</span>
        </a>
        <a href="profile.php" class="sidebar-link flex items-center py-3 px-4 rounded-lg transition-all duration-200 hover:bg-blue-700 hover:pl-6">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          <span>My Profile</span>
        </a>
      </nav>
    </div>

    <!-- <div class="menu-section mb-4">
      <p class="text-xs uppercase tracking-wider text-gray-500 mb-2 pl-2">Bookings</p>
      <nav class="space-y-1">
        <a href="bookings.php" class="sidebar-link flex items-center py-3 px-4 rounded-lg transition-all duration-200 hover:bg-blue-700 hover:pl-6">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
          </svg>
          <span>My Bookings</span>
        </a>
        <a href="packages.php" class="sidebar-link flex items-center py-3 px-4 rounded-lg transition-all duration-200 hover:bg-blue-700 hover:pl-6">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>
          <span>Packages</span>
        </a>
      </nav>
    </div> -->
    <div class="menu-section mb-4">
      <p class="text-xs uppercase tracking-wider text-gray-500 mb-2 pl-2">Bookings</p>
      <nav class="space-y-1">
        <a href="bookings-flights.php" class="sidebar-link flex items-center py-3 px-4 rounded-lg transition-all duration-200 hover:bg-blue-700 hover:pl-6">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
          </svg>
          <span>Flight Bookings</span>
        </a>
        <a href="bookings-hotels.php" class="sidebar-link flex items-center py-3 px-4 rounded-lg transition-all duration-200 hover:bg-blue-700 hover:pl-6">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          <span>Hotel Bookings</span>
        </a>
        <a href="bookings-transport.php" class="sidebar-link flex items-center py-3 px-4 rounded-lg transition-all duration-200 hover:bg-blue-700 hover:pl-6">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
          </svg>
          <span>Transportations</span>
        </a>
        <a href="bookings-packages.php" class="sidebar-link flex items-center py-3 px-4 rounded-lg transition-all duration-200 hover:bg-blue-700 hover:pl-6">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
          </svg>
          <span>Package Bookings</span>
        </a>
      </nav>
    </div>
    <div class="menu-section mb-4">
      <p class="text-xs uppercase tracking-wider text-gray-500 mb-2 pl-2">Account</p>
      <nav class="space-y-1">
        <a href="under-development.php" class="sidebar-link flex items-center py-3 px-4 rounded-lg transition-all duration-200 hover:bg-blue-700 hover:pl-6">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
          </svg>
          <span>Payments</span>
        </a>
        <a href="under-development.php" class="sidebar-link flex items-center py-3 px-4 rounded-lg transition-all duration-200 hover:bg-blue-700 hover:pl-6">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
          <span>Notifications</span>
          <span class="ml-auto bg-blue-500 text-white text-xs font-bold px-2 py-1 rounded-full">3</span>
        </a>
        <a href="under-development.php" class="sidebar-link flex items-center py-3 px-4 rounded-lg transition-all duration-200 hover:bg-blue-700 hover:pl-6">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <span>Settings</span>
        </a>
      </nav>
    </div>

    <div class="border-t border-gray-700 pt-4 mt-6">
      <a href="../index.php" class="sidebar-link flex items-center py-3 px-4 rounded-lg transition-all duration-200 hover:bg-blue-700 hover:pl-6">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
        </svg>
        <span>Visit Website</span>
      </a>
      <a href="../logout.php" class="sidebar-link flex items-center py-3 px-4 rounded-lg transition-all duration-200 text-red-400 hover:bg-red-500 hover:text-white hover:pl-6">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
        <span>Logout</span>
      </a>
    </div>
  </div>

  <div class="p-4 bg-opacity-30 bg-blue-900 mt-auto">
    <div class="flex items-center p-4 bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg shadow">
      <div class="mr-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <div>
        <p class="text-sm font-medium text-white">Need Help?</p>
        <a href="under-development.php" class="text-xs text-blue-200 hover:text-white">Contact Support</a>
      </div>
    </div>
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
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(156, 163, 175, 0.5) rgba(31, 41, 55, 0.8);
  }

  .sidebar::-webkit-scrollbar {
    width: 5px;
  }

  .sidebar::-webkit-scrollbar-track {
    background: rgba(31, 41, 55, 0.8);
  }

  .sidebar::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.5);
    border-radius: 10px;
  }

  .active-link {
    background-color: #3B82F6;
    box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
  }

  .sidebar-link {
    position: relative;
  }

  .sidebar-link:hover {
    box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.2);
  }

  .main-content {
    margin-left: 260px;
    padding-top: 60px;
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
      margin-left: -280px;
    }

    .main-content {
      margin-left: 0;
    }

    .sidebar.active {
      margin-left: 0;
      box-shadow: 0 0 50px rgba(0, 0, 0, 0.5);
    }

    .sidebar.active~.main-content {
      margin-left: 0;
      opacity: 0.3;
      pointer-events: none;
    }

    .topbar {
      display: block;
    }
  }

  /* Add nice animation for menu links */
  .sidebar-link::after {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 3px;
    background-color: #3B82F6;
    transform: scaleY(0);
    transition: transform 0.2s;
  }

  .sidebar-link:hover::after,
  .active-link::after {
    transform: scaleY(1);
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Determine current page and highlight appropriate sidebar link
    const currentPath = window.location.pathname;
    const filename = currentPath.substring(currentPath.lastIndexOf('/') + 1);

    document.querySelectorAll('.sidebar-link').forEach(link => {
      const href = link.getAttribute('href');
      if (href === filename) {
        link.classList.add('active-link');
      }
    });

    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const overlay = document.createElement('div');

    overlay.classList.add('fixed', 'inset-0', 'bg-black', 'opacity-0', 'z-30', 'pointer-events-none', 'transition-opacity');
    document.body.appendChild(overlay);

    sidebarToggle.addEventListener('click', function() {
      sidebar.classList.toggle('active');

      if (sidebar.classList.contains('active')) {
        overlay.classList.add('opacity-50', 'pointer-events-auto');
        overlay.classList.remove('opacity-0', 'pointer-events-none');
      } else {
        overlay.classList.remove('opacity-50', 'pointer-events-auto');
        overlay.classList.add('opacity-0', 'pointer-events-none');
      }
    });

    overlay.addEventListener('click', function() {
      sidebar.classList.remove('active');
      overlay.classList.remove('opacity-50', 'pointer-events-auto');
      overlay.classList.add('opacity-0', 'pointer-events-none');
    });

    // Close sidebar when pressing escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && sidebar.classList.contains('active')) {
        sidebar.classList.remove('active');
        overlay.classList.remove('opacity-50', 'pointer-events-auto');
        overlay.classList.add('opacity-0', 'pointer-events-none');
      }
    });

    // Adjust for window resizing
    window.addEventListener('resize', function() {
      if (window.innerWidth > 768) {
        overlay.classList.remove('opacity-50', 'pointer-events-auto');
        overlay.classList.add('opacity-0', 'pointer-events-none');
      }
    });
  });
</script>
<!-- Mobile Menu Button -->
<div class="md:hidden fixed top-4 left-4 z-55">
  <button id="sidebarToggle" class="bg-white p-2 rounded-full shadow-md">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
    </svg>
  </button>
</div>