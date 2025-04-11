<?php
// Start the session or database connection if not already included
$conn = new mysqli('127.0.0.1', 'root', '', 'ummrah');
if ($conn->connect_error) {
  die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
  header('Content-Type: application/json');

  // Check if a filter parameter is provided
  $filter = $_GET['filter'] ?? 'latest_unread';

  if ($filter === 'latest_unread') {
    // For the popup: Fetch the latest unread notification
    $query = "SELECT id, type, reference_id, message, created_at 
              FROM notifications 
              WHERE is_read = 0 
              ORDER BY created_at DESC 
              LIMIT 1";
    $result = $conn->query($query);
    $notification = $result->fetch_assoc();
    echo json_encode($notification ?: []);
  } else {
    // For the bell and notifications_page.php: Fetch all notifications
    $query = "SELECT id, type, reference_id, message, is_read, created_at 
              FROM notifications 
              ORDER BY created_at DESC";
    $result = $conn->query($query);

    if (!$result) {
      echo json_encode(['error' => 'Query failed: ' . $conn->error]);
      $conn->close();
      exit;
    }

    $notifications = [];
    while ($row = $result->fetch_assoc()) {
      $notifications[] = $row;
    }
    echo json_encode($notifications);
  }

  $conn->close();
  exit;
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<style>
  #live-notif {
    z-index: 9999;
  }
</style>

<body>
  <!-- Notification Popup -->
  <div id="live-notif" class="fixed bottom-4 right-4 hidden bg-green-500 text-white p-4 rounded-lg shadow-lg transition-all duration-300">
    <p id="notif-message" class="font-semibold"></p>
    <p id="notif-time" class="text-sm"></p>
    <button id="close-notif" class="mt-2 text-sm underline">Close</button>
  </div>

  <script>
    let lastNotifId = 0;

    function showNotification(message, time) {
      const notifDiv = document.getElementById('live-notif');
      document.getElementById('notif-message').innerText = message;
      document.getElementById('notif-time').innerText = new Date(time).toLocaleString();
      notifDiv.classList.remove('hidden');
      setTimeout(() => notifDiv.classList.add('hidden'), 5000);
    }

    function checkForNewNotifications() {
      fetch('<?php echo basename(__FILE__); ?>?filter=latest_unread', {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(response => response.json())
        .then(data => {
          console.log('Popup notification data:', data);
          if (data && data.id && data.id > lastNotifId) {
            lastNotifId = data.id;
            showNotification(data.message, data.created_at);
          }
        })
        .catch(error => console.error('Error fetching notifications:', error));
    }

    document.getElementById('close-notif').addEventListener('click', () => {
      document.getElementById('live-notif').classList.add('hidden');
    });

    setInterval(checkForNewNotifications, 5000);
    checkForNewNotifications();
  </script>
</body>

</html>