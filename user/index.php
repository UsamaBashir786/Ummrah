<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../connection/connection.php';

// Get user details
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if profile image exists and is accessible
$profile_image = $user['profile_image'];
if (!empty($profile_image) && file_exists("../" . $profile_image)) {
    $profile_image = "../" . $profile_image;
}

// Fetch flights data
$sql = "SELECT * FROM flights ORDER BY departure_date, departure_time";
$result = $conn->query($sql);

// Handle booking request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flight_id'])) {
    $flight_id = $_POST['flight_id'];
    $user_id = $_SESSION['user_id'];

    // Check if flight is already booked
    $check_sql = "SELECT * FROM flight_book WHERE user_id = ? AND flight_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $flight_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        $booking_sql = "INSERT INTO flight_book (user_id, flight_id) VALUES (?, ?)";
        $stmt = $conn->prepare($booking_sql);
        $stmt->bind_param("ii", $user_id, $flight_id);
        if ($stmt->execute()) {
            echo "<script>alert('Flight booked successfully!');</script>";
        } else {
            echo "<script>alert('Booking failed. Please try again.');</script>";
        }
    } else {
        echo "<script>alert('You have already booked this flight!');</script>";
    }
}

// Fetch user's booked flights
$booked_flights_sql = "SELECT f.*, fb.booking_time, fb.flight_status 
                      FROM flights f 
                      INNER JOIN flight_book fb ON f.id = fb.flight_id 
                      WHERE fb.user_id = ?
                      ORDER BY f.departure_date, f.departure_time";
$booked_stmt = $conn->prepare($booked_flights_sql);
$booked_stmt->bind_param("i", $user_id);
$booked_stmt->execute();
$booked_flights = $booked_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/css-links.php' ?>
    <link rel="stylesheet" href="../assets/css/output.css">
    <title>User Dashboard</title>
</head>
<body class="bg-gray-100">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content p-8">
        <div class="container mx-auto px-4 py-8">
            <!-- Booked Flights Section -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h2 class="text-2xl font-bold mb-4">Your Booked Flights</h2>
                <div class="overflow-x-auto">
                    <table class="w-full border border-gray-200 shadow-md rounded-lg">
                        <thead>
                            <tr class="bg-blue-500 text-white">
                                <th class="p-4 text-left">Flight</th>
                                <th class="p-4 text-left">Route</th>
                                <th class="p-4 text-left">Departure</th>
                                <th class="p-4 text-left">Time Remaining</th>
                                <th class="p-4 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <?php while ($booked = $booked_flights->fetch_assoc()) { ?>
                                <tr class="border-b hover:bg-gray-100 transition">
                                    <td class="p-4">
                                        <?php echo htmlspecialchars($booked['airline_name'] . ' ' . $booked['flight_number']); ?>
                                    </td>
                                    <td class="p-4">
                                        <?php echo htmlspecialchars($booked['departure_city'] . ' → ' . $booked['arrival_city']); ?>
                                    </td>
                                    <td class="p-4">
                                        <?php echo htmlspecialchars($booked['departure_date'] . ' ' . $booked['departure_time']); ?>
                                    </td>
                                    <td class="p-4">
                                        <div class="countdown" 
                                             data-departure="<?php echo $booked['departure_date'] . ' ' . $booked['departure_time']; ?>"
                                             data-flight-id="<?php echo $booked['id']; ?>">
                                            Calculating...
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <span class="status-badge px-3 py-1 rounded-full text-white
                                            <?php
                                            switch ($booked['flight_status']) {
                                                case 'upcoming':
                                                    echo 'bg-yellow-500';
                                                    break;
                                                case 'in-progress':
                                                    echo 'bg-green-500';
                                                    break;
                                                case 'completed':
                                                    echo 'bg-gray-500';
                                                    break;
                                            }
                                            ?>">
                                            <?php echo ucfirst($booked['flight_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Available Flights Section -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold mb-4">Available Flights</h2>
                <div class="overflow-x-auto">
                    <table class="w-full border border-gray-200 shadow-md rounded-lg">
                        <thead>
                            <tr class="bg-blue-500 text-white">
                                <th class="p-4 text-left">ID</th>
                                <th class="p-4 text-left">Airline Name</th>
                                <th class="p-4 text-left">Flight Number</th>
                                <th class="p-4 text-left">From</th>
                                <th class="p-4 text-left">To</th>
                                <th class="p-4 text-left">Departure Date</th>
                                <th class="p-4 text-left">Departure Time</th>
                                <th class="p-4 text-left">Economy Price</th>
                                <th class="p-4 text-left">Business Price</th>
                                <th class="p-4 text-left">First Class Price</th>
                                <th class="p-4 text-left">Economy Seats</th>
                                <th class="p-4 text-left">Business Seats</th>
                                <th class="p-4 text-left">First Class Seats</th>
                                <th class="p-4 text-left">Flight Notes</th>
                                <th class="p-4 text-left">Created At</th>
                                <th class="p-4 text-left">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <?php 
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) { 
                            ?>
                                <tr class="border-b hover:bg-gray-100 transition">
                                    <td class="p-4"><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td class="p-4"><?php echo htmlspecialchars($row['airline_name']); ?></td>
                                    <td class="p-4"><?php echo htmlspecialchars($row['flight_number']); ?></td>
                                    <td class="p-4"><?php echo htmlspecialchars($row['departure_city']); ?></td>
                                    <td class="p-4"><?php echo htmlspecialchars($row['arrival_city']); ?></td>
                                    <td class="p-4"><?php echo htmlspecialchars($row['departure_date']); ?></td>
                                    <td class="p-4"><?php echo htmlspecialchars($row['departure_time']); ?></td>
                                    <td class="p-4 text-green-600">$<?php echo htmlspecialchars($row['economy_price']); ?></td>
                                    <td class="p-4 text-blue-600">$<?php echo htmlspecialchars($row['business_price']); ?></td>
                                    <td class="p-4 text-purple-600">$<?php echo htmlspecialchars($row['first_class_price']); ?></td>
                                    <td class="p-4"><?php echo htmlspecialchars($row['economy_seats']); ?></td>
                                    <td class="p-4"><?php echo htmlspecialchars($row['business_seats']); ?></td>
                                    <td class="p-4"><?php echo htmlspecialchars($row['first_class_seats']); ?></td>
                                    <td class="p-4"><?php echo htmlspecialchars($row['flight_notes']); ?></td>
                                    <td class="p-4"><?php echo htmlspecialchars($row['created_at']); ?></td>
                                    <td class="p-4">
                                        <form method="POST">
                                            <input type="hidden" name="flight_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" 
                                                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                                Book
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php 
                                }
                            } else {
                                echo '<tr><td colspan="16" class="p-4 text-center">No flights available</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateCountdown() {
            document.querySelectorAll('.countdown').forEach(timer => {
                const departureDateStr = timer.dataset.departure;
                const departureDate = new Date(departureDateStr);
                const now = new Date();
                const timeLeft = departureDate - now;

                if (timeLeft <= 0) {
                    timer.innerHTML = 'Departed';
                    return;
                }

                const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

                timer.innerHTML = `${days}d ${hours}h ${minutes}m ${seconds}s`;
            });
        }

        // Update countdown every second
        setInterval(updateCountdown, 1000);
        updateCountdown(); // Initial call

        // Auto update flight status
        function updateFlightStatus() {
            const countdowns = document.querySelectorAll('.countdown');
            countdowns.forEach(timer => {
                const departureDateStr = timer.dataset.departure;
                const departureDate = new Date(departureDateStr);
                const now = new Date();
                const timeLeft = departureDate - now;

                if (timeLeft <= 0) {
                    const flightId = timer.dataset.flightId;
                    // You can add an AJAX call here to update the flight status in the database
                }
            });
        }

        // Update flight status every minute
        setInterval(updateFlightStatus, 60000);
    </script>
</body>
</html>