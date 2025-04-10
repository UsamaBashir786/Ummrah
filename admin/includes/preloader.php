<?php

/**
 * Admin Preloader Component
 * 
 * A one-time preloader for admin panel that shows only once per session
 * using HTML, CSS, JavaScript, PHP, and Tailwind CSS
 */

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Check if the preloader has been shown before in this session
$showPreloader = true;
if (isset($_SESSION['preloader_shown']) && $_SESSION['preloader_shown'] === true) {
  $showPreloader = false;
} else {
  // Mark the preloader as shown for this session
  $_SESSION['preloader_shown'] = true;
}
?>

<!-- Preloader Component - Include this file in your admin panel pages -->
<?php if ($showPreloader): ?>
  <div id="admin-preloader" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-90">
    <div class="text-center">
      <!-- Logo/Brand -->
      <div class="mb-6">
        <svg class="w-16 h-16 mx-auto text-blue-500 animate-pulse" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
        </svg>
      </div>

      <!-- Loading Text -->
      <h2 class="text-xl font-semibold text-white mb-3">Loading Admin Panel</h2>

      <!-- Loading Bar -->
      <div class="w-64 h-2 mx-auto bg-gray-700 rounded-full overflow-hidden">
        <div id="progress-bar" class="h-full bg-blue-500 rounded-full w-0 transition-all duration-300"></div>
      </div>

      <!-- Loading Percentage -->
      <div class="mt-3 text-blue-400 text-sm font-mono">
        <span id="progress-percentage">0%</span> Complete
      </div>
    </div>
  </div>

  <style>
    /* Additional styles if needed beyond Tailwind */
    @keyframes fadeOut {
      from {
        opacity: 1;
      }

      to {
        opacity: 0;
        visibility: hidden;
      }
    }

    .preloader-fade-out {
      animation: fadeOut 0.5s ease-in-out forwards;
    }
  </style>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Elements
      const preloader = document.getElementById('admin-preloader');
      const progressBar = document.getElementById('progress-bar');
      const progressPercentage = document.getElementById('progress-percentage');

      // Progress simulation
      let progress = 0;
      const interval = setInterval(function() {
        progress += Math.floor(Math.random() * 10) + 1;

        if (progress >= 100) {
          progress = 100;
          clearInterval(interval);

          // Add a small delay before hiding the preloader
          setTimeout(function() {
            preloader.classList.add('preloader-fade-out');

            // Remove preloader from DOM after animation completes
            setTimeout(function() {
              preloader.remove();
            }, 500);
          }, 300);
        }

        // Update visuals
        progressBar.style.width = progress + '%';
        progressPercentage.textContent = progress + '%';
      }, 150);

      // Store in localStorage to prevent showing on page refresh
      localStorage.setItem('admin_preloader_shown', 'true');
    });

    // Handle page refresh - check localStorage in addition to PHP session
    if (localStorage.getItem('admin_preloader_shown') === 'true') {
      const preloader = document.getElementById('admin-preloader');
      if (preloader) {
        preloader.remove();
      }
    }
  </script>
<?php endif; ?>