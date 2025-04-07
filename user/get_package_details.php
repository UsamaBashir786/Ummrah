<?php
session_start();
require_once '../connection/connection.php';

if (!isset($_GET['booking_id']) || !isset($_SESSION['user_id'])) {
    die('Invalid request');
}

$booking_id = $_GET['booking_id'];
$user_id = $_SESSION['user_id'];

// Fetch package details
$sql = "SELECT * FROM package_booking pb 
        JOIN packages p ON pb.package_id = p.id 
        WHERE pb.id = ? AND pb.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$package = $result->fetch_assoc();

// Output package details
?>
<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <h3 class="font-bold text-gray-700">Package Title</h3>
            <p><?php echo htmlspecialchars($package['title']); ?></p>
        </div>
        <div>
            <h3 class="font-bold text-gray-700">Booking Date</h3>
            <p><?php echo htmlspecialchars($package['booking_date']); ?></p>
        </div>
    </div>
    <!-- Add more package details as needed -->
</div> 