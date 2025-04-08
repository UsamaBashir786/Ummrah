<?php
require_once 'connection/connection.php';

// Fetch all users
$sql = "SELECT * FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
</head>

<body class="bg-gray-100">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main flex-1 flex flex-col">
      <!-- Navbar -->
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <button class="md:hidden text-gray-800" id="menu-btn">
          <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-xl font-semibold"><i class="text-teal-600 fa fa-user mx-2"></i> All Users</h1>
      </div>

      <div class="container mx-auto p-4 sm:p-6">
        <div class="overflow-x-auto bg-white rounded-lg shadow max-h-[500px] overflow-y-auto">
          <!-- For small screens - Card view -->
          <div class="block md:hidden max-h-[500px] overflow-y-auto">
            <div class="space-y-4 p-4">
              <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($user = $result->fetch_assoc()): ?>
                  <div class="bg-white rounded-lg shadow p-4 hover:bg-gray-50">
                    <div class="flex items-center space-x-4 mb-3">
                      <img class="h-10 w-10 rounded-full object-cover"
                        src="../<?php echo isset($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'user/uploads/default.png'; ?>"
                        alt="User avatar" />

                      <div>
                        <div class="text-sm font-medium text-gray-900">
                          <?php echo isset($user['full_name']) ? htmlspecialchars($user['full_name']) : 'Unknown'; ?>
                        </div>
                        <div class="text-sm text-gray-500">
                          <?php echo isset($user['email']) ? htmlspecialchars($user['email']) : 'No email'; ?>
                        </div>
                      </div>
                    </div>
                    <div class="space-y-2">
                      <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Phone:</span>
                        <span class="text-sm text-gray-900">
                          <?php echo isset($user['phone_number']) ? htmlspecialchars($user['phone_number']) : 'No phone'; ?>
                        </span>
                      </div>
                      <div class="flex justify-end space-x-3 mt-3">
                        <button class="text-indigo-600 hover:text-indigo-900"
                          onclick="editUser(<?php echo isset($user['id']) ? $user['id'] : 0; ?>)">Edit</button>
                        <button class="text-red-600 hover:text-red-900"
                          onclick="deleteUser(<?php echo isset($user['id']) ? $user['id'] : 0; ?>)">Delete</button>
                      </div>
                    </div>
                  </div>
                <?php endwhile; ?>
              <?php else: ?>
                <div class="text-center p-6 text-gray-500">No users found</div>
              <?php endif; ?>
            </div>
          </div>

          <!-- For medium and larger screens - Table view -->
          <table class="min-w-full hidden md:table">
            <thead class="bg-gray-50 sticky top-0">
              <tr>
                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php
              // Reset the result pointer
              if ($result && $result->num_rows > 0) {
                $result->data_seek(0);
                while ($user = $result->fetch_assoc()):
              ?>
                  <tr class="hover:bg-gray-50">
                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <div class="h-10 w-10">
                          <img class="h-10 w-10 rounded-full object-cover"
                            src="../<?php echo isset($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'user/uploads/default.png'; ?>"
                            alt="User avatar" />
                        </div>
                        <div class="ml-4">
                          <div class="text-sm font-medium text-gray-900">
                            <?php echo isset($user['full_name']) ? htmlspecialchars($user['full_name']) : 'Unknown'; ?>
                          </div>
                          <div class="text-sm text-gray-500">
                            <?php echo isset($user['gender']) ? htmlspecialchars($user['gender']) : 'Unknown'; ?>
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900">
                        <?php echo isset($user['email']) ? htmlspecialchars($user['email']) : 'No email'; ?>
                      </div>
                    </td>
                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900">
                        <?php echo isset($user['phone_number']) ? htmlspecialchars($user['phone_number']) : 'No phone'; ?>
                      </div>
                    </td>
                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <button class="text-indigo-600 hover:text-indigo-900 mr-3"
                        onclick="editUser(<?php echo isset($user['id']) ? $user['id'] : 0; ?>)">Edit</button>
                      <button class="text-red-600 hover:text-red-900 mr-3"
                        onclick="deleteUser(<?php echo isset($user['id']) ? $user['id'] : 0; ?>)">Delete</button>
                      <?php if (isset($user['id']) && $user['id'] > 0): ?>
                        <a href="user-details.php?id=<?php echo $user['id']; ?>" class="text-blue-600 hover:text-blue-900">View Details</a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php
                endwhile;
              } else {
                ?>
                <tr>
                  <td colspan="4" class="px-4 sm:px-6 py-4 text-center text-gray-500">
                    No users found
                  </td>
                </tr>
              <?php
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php include 'includes/js-links.php'; ?>
    </div>
  </div>
  <script>
    function editUser(userId) {
      if (userId > 0) {
        window.location.href = `edit-user.php?id=${userId}`;
      } else {
        Swal.fire({
          title: 'Error',
          text: 'Invalid user ID',
          icon: 'error'
        });
      }
    }

    function deleteUser(userId) {
      if (userId <= 0) {
        Swal.fire({
          title: 'Error',
          text: 'Invalid user ID',
          icon: 'error'
        });
        return;
      }

      Swal.fire({
        title: 'Are you sure?',
        text: "This will delete the user and all their associated data (bookings, etc.). This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading state
          Swal.fire({
            title: 'Deleting...',
            text: 'Please wait while we delete the user and associated data',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          // Add console.log to debug
          console.log('Deleting user with ID:', userId);

          fetch(`delete-user.php?id=${userId}`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              }
            })
            .then(response => {
              if (!response.ok) {
                throw new Error('Network response was not ok');
              }
              return response.json();
            })
            .then(data => {
              if (data.success) {
                Swal.fire({
                  title: 'Deleted!',
                  text: 'User and all associated data have been deleted.',
                  icon: 'success',
                  showConfirmButton: false,
                  timer: 1500
                }).then(() => {
                  window.location.reload();
                });
              } else {
                throw new Error(data.message || 'Failed to delete user');
              }
            })
            .catch(error => {
              console.error('Error:', error);
              Swal.fire({
                title: 'Error!',
                text: error.message || 'An error occurred while deleting the user',
                icon: 'error',
                confirmButtonColor: '#3085d6'
              });
            });
        }
      });
    }
  </script>
</body>

</html>