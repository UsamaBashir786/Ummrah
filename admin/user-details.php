<?php
require_once 'connection/connection.php';

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: all-users.php');
    exit();
}

$userId = (int)$_GET['id'];

// Fetch user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: all-users.php');
    exit();
}

// Fetch user's flight bookings
$stmt = $conn->prepare("
    SELECT fb.*, f.flight_number, f.departure_city, f.arrival_city, f.departure_date
    FROM flight_bookings fb
    JOIN flights f ON fb.flight_id = f.id
    WHERE fb.user_id = ?
    ORDER BY fb.booking_date DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$bookingsResult = $stmt->get_result();
$bookings = $bookingsResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .profile-header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .booking-card {
            margin-bottom: 15px;
            transition: transform 0.2s;
        }
        .booking-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="profile-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2>User Details</h2>
                        <div>
                            <a href="all-users.php" class="btn btn-secondary">Back to Users</a>
                            <button onclick="deleteUser(<?php echo $userId; ?>)" class="btn btn-danger">Delete User</button>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Personal Information</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                <p><strong>Registration Date:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Account Information</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>User ID:</strong> <?php echo $user['id']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <h3>Flight Bookings</h3>
                    <?php if (empty($bookings)): ?>
                        <div class="alert alert-info">
                            No flight bookings found for this user.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($bookings as $booking): ?>
                                <div class="col-md-6">
                                    <div class="card booking-card">
                                        <div class="card-header">
                                            <h5>Booking #<?php echo $booking['id']; ?></h5>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>Flight:</strong> <?php echo htmlspecialchars($booking['flight_number']); ?></p>
                                            <p><strong>Route:</strong> <?php echo htmlspecialchars($booking['departure_city']); ?> to <?php echo htmlspecialchars($booking['arrival_city']); ?></p>
                                            <p><strong>Departure:</strong> <?php echo date('F j, Y', strtotime($booking['departure_date'])); ?></p>
                                            <p><strong>Booking Date:</strong> <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function deleteUser(userId) {
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
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait while we delete the user and associated data',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch(`delete-user.php?id=${userId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: 'User has been deleted.',
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                window.location.href = 'all-users.php';
                            });
                        } else {
                            throw new Error(data.message || 'Failed to delete user');
                        }
                    })
                    .catch(error => {
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
