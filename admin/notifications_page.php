<?php
// Database connection (adjust credentials as needed)
$conn = new mysqli('127.0.0.1', 'root', '', 'ummrah');
if ($conn->connect_error) {
  die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

// Handle AJAX request for marking as read (single notification)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
  $id = $_POST['id'] ?? null;
  if ($id) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    echo json_encode(['success' => $affected_rows > 0, 'id' => $id, 'affected_rows' => $affected_rows]);
  } else {
    echo json_encode(['success' => false, 'error' => 'No ID provided']);
  }
  $conn->close();
  exit;
}

// Handle AJAX request for marking all as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_all_read') {
  $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
  $stmt->execute();
  $affected_rows = $stmt->affected_rows;
  $stmt->close();
  echo json_encode(['success' => $affected_rows >= 0, 'affected_rows' => $affected_rows]);
  $conn->close();
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications - Umrah & Hajj Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    .notification-card {
      transition: all 0.3s ease;
    }

    .notification-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
    }

    .filter-btn.active {
      background-color: #059669 !important;
      color: white !important;
    }
  </style>
</head>

<body class="bg-gray-50 font-sans">
  <!-- Navbar -->
  <header class="bg-white border-b border-gray-200 sticky top-0 z-30 shadow-sm">
    <div class="px-6 py-4 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <h1 class="text-2xl font-bold text-emerald-700">Umrah & Hajj Admin</h1>
      </div>
      <div>
        <a href="index.php" class="text-emerald-600 hover:text-emerald-800 font-medium transition-colors">Back to Dashboard</a>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <div class="container mx-auto p-6 max-w-4xl">
    <!-- Header Section -->
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-3xl font-semibold text-gray-800">Notifications</h2>
      <div class="flex gap-3">
        <button id="filter-all" class="filter-btn px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">All</button>
        <button id="filter-unread" class="filter-btn px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">Unread</button>
        <button id="filter-read" class="filter-btn px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">Read</button>
        <button id="mark-all-read" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors hidden">
          <i class="fas fa-check-circle mr-1"></i> Mark All as Read
        </button>
      </div>
    </div>

    <!-- Notification List -->
    <div id="notification-list" class="space-y-4">
      <!-- Notifications will be appended here -->
    </div>

    <!-- Error Message -->
    <div id="error-message" class="hidden p-4 bg-red-100 text-red-700 rounded-lg mt-4">
      <p id="error-text"></p>
    </div>
  </div>

  <script>
    let currentFilter = 'all';

    // Show error message
    function showError(message) {
      const errorDiv = document.getElementById('error-message');
      const errorText = document.getElementById('error-text');
      errorText.textContent = message;
      errorDiv.classList.remove('hidden');
      setTimeout(() => errorDiv.classList.add('hidden'), 5000);
    }

    // Fetch notifications
    function fetchNotifications() {
      fetch('notifications.php?filter=all', {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          },
          cache: 'no-store'
        })
        .then(response => {
          if (!response.ok) throw new Error('Network response was not ok: ' + response.statusText);
          return response.json();
        })
        .then(data => {
          console.log('Fetched notifications:', data);
          const notifList = document.getElementById('notification-list');
          notifList.innerHTML = '';

          // Handle error response
          if (data.error) {
            showError('Failed to fetch notifications: ' + data.error);
            return;
          }

          // Handle empty or invalid data
          const notifications = Array.isArray(data) ? data : (data.id ? [data] : []);
          if (notifications.length === 0) {
            notifList.innerHTML = '<p class="text-gray-500 text-center py-4">No notifications found.</p>';
            document.getElementById('mark-all-read').classList.add('hidden');
            return;
          }

          // Filter notifications based on current filter
          const filteredNotifs = notifications.filter(notif => {
            console.log('Filtering notification:', notif);
            if (currentFilter === 'all') return true;
            if (currentFilter === 'unread') return notif.is_read == 0;
            if (currentFilter === 'read') return notif.is_read == 1;
          });

          console.log('Filtered notifications for ' + currentFilter + ':', filteredNotifs);

          // Show/hide "Mark All as Read" button based on unread count
          const unreadCount = notifications.filter(notif => notif.is_read == 0).length;
          const markAllBtn = document.getElementById('mark-all-read');
          if (unreadCount > 0) {
            markAllBtn.classList.remove('hidden');
          } else {
            markAllBtn.classList.add('hidden');
          }

          if (filteredNotifs.length === 0) {
            notifList.innerHTML = `<p class="text-gray-500 text-center py-4">No ${currentFilter} notifications.</p>`;
            return;
          }

          filteredNotifs.forEach(notif => {
            const div = document.createElement('div');
            div.className = `notification-card p-4 rounded-lg shadow-md flex justify-between items-center ${notif.is_read ? 'bg-gray-100' : 'bg-white border-l-4 border-emerald-500'}`;
            div.dataset.id = notif.id;
            div.innerHTML = `
            <div class="flex-1">
              <p class="text-gray-800 font-medium">${notif.message}</p>
              <p class="text-sm text-gray-500">${new Date(notif.created_at).toLocaleString()}</p>
            </div>
            <div class="flex items-center gap-2">
              ${notif.is_read ? 
                '<span class="text-xs text-gray-500"><i class="fas fa-check-circle mr-1"></i>Read</span>' : 
                `<button onclick="markAsRead(${notif.id})" class="text-emerald-600 hover:text-emerald-800 font-medium text-sm transition-colors">Mark as Read</button>`}
            </div>
          `;
            notifList.appendChild(div);
          });
        })
        .catch(error => {
          console.error('Error fetching notifications:', error);
          showError('Failed to load notifications. Please try again later.');
        });
    }

    // Mark a single notification as read
    function markAsRead(id) {
      fetch('<?php echo basename(__FILE__); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: `action=mark_read&id=${id}`
        })
        .then(response => {
          if (!response.ok) throw new Error('Network response was not ok: ' + response.statusText);
          return response.json();
        })
        .then(data => {
          console.log('Mark as read response:', data);
          if (data.success) {
            // Add a longer delay to ensure the database update is reflected
            setTimeout(() => {
              fetchNotifications();
            }, 1000);
          } else {
            showError('Failed to mark notification as read: ' + (data.error || 'Unknown error'));
          }
        })
        .catch(error => {
          console.error('Error marking as read:', error);
          showError('Failed to mark notification as read. Please try again.');
        });
    }

    // Mark all notifications as read
    function markAllAsRead() {
      fetch('<?php echo basename(__FILE__); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: `action=mark_all_read`
        })
        .then(response => {
          if (!response.ok) throw new Error('Network response was not ok: ' + response.statusText);
          return response.json();
        })
        .then(data => {
          console.log('Mark all as read response:', data);
          if (data.success) {
            fetchNotifications();
          } else {
            showError('Failed to mark all notifications as read.');
          }
        })
        .catch(error => {
          console.error('Error marking all as read:', error);
          showError('Failed to mark all notifications as read. Please try again.');
        });
    }

    // Filter button event listeners
    document.getElementById('filter-all').addEventListener('click', () => {
      currentFilter = 'all';
      toggleFilterButtons('filter-all');
      fetchNotifications();
    });
    document.getElementById('filter-unread').addEventListener('click', () => {
      currentFilter = 'unread';
      toggleFilterButtons('filter-unread');
      fetchNotifications();
    });
    document.getElementById('filter-read').addEventListener('click', () => {
      currentFilter = 'read';
      toggleFilterButtons('filter-read');
      fetchNotifications();
    });

    // Mark all as read button
    document.getElementById('mark-all-read').addEventListener('click', () => {
      markAllAsRead();
    });

    // Toggle filter buttons
    function toggleFilterButtons(activeId) {
      const buttons = ['filter-all', 'filter-unread', 'filter-read'];
      buttons.forEach(id => {
        const btn = document.getElementById(id);
        if (id === activeId) {
          btn.classList.add('active');
        } else {
          btn.classList.remove('active');
        }
      });
    }

    // Initial fetch and set default filter
    toggleFilterButtons('filter-all');
    fetchNotifications();

    // Poll every 10 seconds for updates
    setInterval(fetchNotifications, 10000);
  </script>
</body>

</html>