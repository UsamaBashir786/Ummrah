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

// Helper function to display flight row - defined BEFORE it's used
function displayFlightRow($flight) {
?>
    <tr class="border-b hover:bg-gray-100 transition">
        <td class="p-4">
            <?php echo htmlspecialchars($flight['airline_name']); ?>
        </td>
        <td class="p-4">
            <?php echo htmlspecialchars($flight['flight_number']); ?>
        </td>
        <td class="p-4">
            <?php echo htmlspecialchars($flight['departure_city'] . ' → ' . $flight['arrival_city']); ?>
        </td>
        <td class="p-4">
            <?php echo htmlspecialchars($flight['departure_date'] . ' ' . $flight['departure_time']); ?>
        </td>
        <td class="p-4">
            <?php 
            if (!empty($flight['seat_number'])) {
                echo htmlspecialchars($flight['seat_number']);
            } elseif (!empty($flight['seat_type'])) {
                echo htmlspecialchars(ucfirst($flight['seat_type']));
            } else {
                echo "Not assigned";
            }
            ?>
        </td>
        <td class="p-4">
            <span class="status-badge px-3 py-1 rounded-full text-white
                <?php
                switch ($flight['flight_status']) {
                    case 'upcoming':
                        echo 'bg-yellow-500';
                        break;
                    case 'in-progress':
                        echo 'bg-green-500';
                        break;
                    case 'completed':
                        echo 'bg-gray-500';
                        break;
                    case 'cancelled':
                        echo 'bg-red-500';
                        break;
                    default:
                        echo 'bg-blue-500';
                }
                ?>">
                <?php echo ucfirst($flight['flight_status']); ?>
            </span>
        </td>
        <td class="p-4">
            <div class="countdown" 
                data-departure="<?php echo $flight['departure_date'] . ' ' . $flight['departure_time']; ?>"
                data-flight-id="<?php echo $flight['id']; ?>">
                Calculating...
            </div>
        </td>
        <td class="p-4">
            <span class="px-2 py-1 rounded text-xs
                <?php
                switch ($flight['booking_type']) {
                    case 'direct':
                        echo 'bg-blue-100 text-blue-800';
                        break;
                    case 'package':
                        echo 'bg-purple-100 text-purple-800';
                        break;
                    case 'standalone':
                        echo 'bg-green-100 text-green-800';
                        break;
                }
                ?>">
                <?php
                switch ($flight['booking_type']) {
                    case 'direct':
                        echo 'Direct Booking';
                        break;
                    case 'package':
                        echo 'Package Booking';
                        break;
                    case 'standalone':
                        echo 'Flight Booking';
                        break;
                }
                ?>
            </span>
        </td>
    </tr>
<?php
}

// Approach 1: Separate queries for different flight booking types
// Fetch direct flight bookings
$direct_flights_sql = "
    SELECT 
        f.id, f.airline_name, f.flight_number, f.departure_city, f.arrival_city, 
        f.departure_date, f.departure_time, 
        fb.booking_time, fb.flight_status, 
        'direct' AS booking_type,
        NULL AS seat_type,
        NULL AS seat_number,
        NULL AS package_id
    FROM flights f 
    INNER JOIN flight_book fb ON f.id = fb.flight_id 
    WHERE fb.user_id = ?
";
$direct_stmt = $conn->prepare($direct_flights_sql);
$direct_stmt->bind_param("i", $user_id);
$direct_stmt->execute();
$direct_flights = $direct_stmt->get_result();

// Fetch package assigned flights
$package_flights_sql = "
    SELECT 
        f.id, f.airline_name, f.flight_number, f.departure_city, f.arrival_city, 
        f.departure_date, f.departure_time,
        pb.booking_date AS booking_time, 
        CASE 
            WHEN fa.status = 'assigned' THEN 'upcoming'
            WHEN fa.status = 'completed' THEN 'completed'
            WHEN fa.status = 'cancelled' THEN 'cancelled'
            ELSE 'upcoming'
        END AS flight_status,
        'package' AS booking_type,
        fa.seat_type,
        fa.seat_number,
        pb.id AS package_id
    FROM flights f 
    INNER JOIN flight_assign fa ON f.id = fa.flight_id 
    INNER JOIN package_booking pb ON fa.booking_id = pb.id
    WHERE fa.user_id = ?
";
$package_stmt = $conn->prepare($package_flights_sql);
$package_stmt->bind_param("i", $user_id);
$package_stmt->execute();
$package_flights = $package_stmt->get_result();

// Fetch standalone flight bookings
$standalone_flights_sql = "
    SELECT 
        f.id, f.airline_name, f.flight_number, f.departure_city, f.arrival_city, 
        f.departure_date, f.departure_time,
        fb.booking_date AS booking_time, 
        CASE 
            WHEN CURDATE() < f.departure_date THEN 'upcoming'
            WHEN CURDATE() = f.departure_date THEN 'in-progress'
            ELSE 'completed'
        END AS flight_status,
        'standalone' AS booking_type,
        fb.cabin_class AS seat_type,
        fb.seat_id AS seat_number,
        NULL AS package_id
    FROM flights f 
    INNER JOIN flight_bookings fb ON f.id = fb.flight_id 
    WHERE fb.user_id = ?
";
$standalone_stmt = $conn->prepare($standalone_flights_sql);
$standalone_stmt->bind_param("i", $user_id);
$standalone_stmt->execute();
$standalone_flights = $standalone_stmt->get_result();

// Fetch user's hotel bookings
$hotel_bookings_sql = "
    SELECT 
        h.*, 
        hb.id as booking_id,
        hb.room_id,
        hb.check_in_date,
        hb.check_out_date,
        hb.status as booking_status,
        'direct' as booking_type
    FROM hotels h 
    INNER JOIN hotel_bookings hb ON h.id = hb.hotel_id 
    WHERE hb.user_id = ?
    ORDER BY hb.check_in_date
";
$hotel_stmt = $conn->prepare($hotel_bookings_sql);
$hotel_stmt->bind_param("i", $user_id);
$hotel_stmt->execute();
$hotel_bookings = $hotel_stmt->get_result();

// Fetch user's transportation bookings
$transport_bookings_sql = "
    SELECT 
        tb.*,
        ta.driver_name,
        ta.driver_contact,
        ta.status as assign_status,
        ta.vehicle_id
    FROM transportation_bookings tb
    LEFT JOIN transportation_assign ta ON tb.id = ta.booking_id AND tb.booking_reference = ta.booking_reference
    WHERE tb.user_id = ?
    ORDER BY tb.booking_date, tb.booking_time
";
$transport_stmt = $conn->prepare($transport_bookings_sql);
$transport_stmt->bind_param("i", $user_id);
$transport_stmt->execute();
$transport_bookings = $transport_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/css-links.php' ?>
    <link rel="stylesheet" href="../assets/css/output.css">
    <title>My Bookings</title>
    <style>
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .tab {
            cursor: pointer;
            padding: 10px 20px;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .tab.active {
            border-bottom: 2px solid #3B82F6;
            color: #3B82F6;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content p-8">
        <div class="container mx-auto px-4 py-8">
            <h1 class="text-3xl font-bold mb-6">My Bookings</h1>
            
            <!-- Tabs Navigation -->
            <div class="flex border-b border-gray-200 mb-8">
                <div class="tab active" data-target="flights">Flight Bookings</div>
                <div class="tab" data-target="hotels">Hotel Bookings</div>
                <div class="tab" data-target="transport">Transportation Bookings</div>
            </div>
            
            <!-- Flights Tab Content -->
            <div id="flights" class="tab-content active">
                <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                    <h2 class="text-2xl font-bold mb-4">Your Booked Flights</h2>
                    
                    <?php 
                    $has_flights = ($direct_flights->num_rows > 0 || $package_flights->num_rows > 0 || $standalone_flights->num_rows > 0);
                    if ($has_flights) { 
                    ?>
                        <div class="overflow-x-auto">
                            <table class="w-full border border-gray-200 shadow-md rounded-lg">
                                <thead>
                                    <tr class="bg-blue-500 text-white">
                                        <th class="p-4 text-left">Airline</th>
                                        <th class="p-4 text-left">Flight</th>
                                        <th class="p-4 text-left">Route</th>
                                        <th class="p-4 text-left">Departure</th>
                                        <th class="p-4 text-left">Seat</th>
                                        <th class="p-4 text-left">Status</th>
                                        <th class="p-4 text-left">Countdown</th>
                                        <th class="p-4 text-left">Type</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    <?php 
                                    // Display direct flights
                                    while ($flight = $direct_flights->fetch_assoc()) { 
                                        displayFlightRow($flight);
                                    }
                                    
                                    // Display package flights
                                    while ($flight = $package_flights->fetch_assoc()) {
                                        displayFlightRow($flight);
                                    }
                                    
                                    // Display standalone flights
                                    while ($flight = $standalone_flights->fetch_assoc()) {
                                        displayFlightRow($flight);
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } else { ?>
                        <div class="bg-gray-100 p-6 rounded-lg text-center">
                            <p class="text-xl text-gray-600">You haven't booked any flights yet.</p>
                            <a href="book-flight.php" class="mt-4 inline-block bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Book a Flight
                            </a>
                        </div>
                    <?php } ?>
                </div>
            </div>
            
            <!-- Hotels Tab Content -->
            <div id="hotels" class="tab-content">
                <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                    <h2 class="text-2xl font-bold mb-4">Your Hotel Bookings</h2>
                    
                    <?php if ($hotel_bookings->num_rows > 0) { ?>
                        <div class="overflow-x-auto">
                            <table class="w-full border border-gray-200 shadow-md rounded-lg">
                                <thead>
                                    <tr class="bg-blue-500 text-white">
                                        <th class="p-4 text-left">Hotel</th>
                                        <th class="p-4 text-left">Location</th>
                                        <th class="p-4 text-left">Room</th>
                                        <th class="p-4 text-left">Check-in</th>
                                        <th class="p-4 text-left">Check-out</th>
                                        <th class="p-4 text-left">Status</th>
                                        <th class="p-4 text-left">Price/Night</th>
                                        <th class="p-4 text-left">Rating</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    <?php while ($hotel = $hotel_bookings->fetch_assoc()) { ?>
                                        <tr class="border-b hover:bg-gray-100 transition">
                                            <td class="p-4 font-medium">
                                                <?php echo htmlspecialchars($hotel['hotel_name']); ?>
                                            </td>
                                            <td class="p-4">
                                                <?php echo ucfirst(htmlspecialchars($hotel['location'])); ?>
                                            </td>
                                            <td class="p-4">
                                                <?php echo htmlspecialchars($hotel['room_id']); ?>
                                            </td>
                                            <td class="p-4">
                                                <?php echo htmlspecialchars($hotel['check_in_date']); ?>
                                            </td>
                                            <td class="p-4">
                                                <?php echo htmlspecialchars($hotel['check_out_date']); ?>
                                            </td>
                                            <td class="p-4">
                                                <span class="status-badge px-3 py-1 rounded-full text-white
                                                    <?php
                                                    switch ($hotel['booking_status']) {
                                                        case 'pending':
                                                            echo 'bg-yellow-500';
                                                            break;
                                                        case 'confirmed':
                                                            echo 'bg-green-500';
                                                            break;
                                                        case 'cancelled':
                                                            echo 'bg-red-500';
                                                            break;
                                                        case 'completed':
                                                            echo 'bg-gray-500';
                                                            break;
                                                    }
                                                    ?>">
                                                    <?php echo ucfirst($hotel['booking_status']); ?>
                                                </span>
                                            </td>
                                            <td class="p-4 text-green-600">
                                                $<?php echo htmlspecialchars($hotel['price_per_night']); ?>
                                            </td>
                                            <td class="p-4">
                                                <?php 
                                                $rating = (int)$hotel['rating'];
                                                for ($i = 0; $i < $rating; $i++) {
                                                    echo '<span class="text-yellow-400">★</span>';
                                                }
                                                for ($i = $rating; $i < 5; $i++) {
                                                    echo '<span class="text-gray-300">★</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } else { ?>
                        <div class="bg-gray-100 p-6 rounded-lg text-center">
                            <p class="text-xl text-gray-600">You haven't booked any hotels yet.</p>
                            <a href="book-hotel.php" class="mt-4 inline-block bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Book a Hotel
                            </a>
                        </div>
                    <?php } ?>
                </div>
            </div>
            
            <!-- Transportation Tab Content -->
            <div id="transport" class="tab-content">
                <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                    <h2 class="text-2xl font-bold mb-4">Your Transportation Bookings</h2>
                    
                    <?php if ($transport_bookings->num_rows > 0) { ?>
                        <div class="overflow-x-auto">
                            <table class="w-full border border-gray-200 shadow-md rounded-lg">
                                <thead>
                                    <tr class="bg-blue-500 text-white">
                                        <th class="p-4 text-left">Booking Ref</th>
                                        <th class="p-4 text-left">Service Type</th>
                                        <th class="p-4 text-left">Route</th>
                                        <th class="p-4 text-left">Vehicle</th>
                                        <th class="p-4 text-left">Date & Time</th>
                                        <th class="p-4 text-left">Price</th>
                                        <th class="p-4 text-left">Status</th>
                                        <th class="p-4 text-left">Driver</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    <?php while ($transport = $transport_bookings->fetch_assoc()) { ?>
                                        <tr class="border-b hover:bg-gray-100 transition">
                                            <td class="p-4 font-medium">
                                                <?php echo htmlspecialchars($transport['booking_reference']); ?>
                                            </td>
                                            <td class="p-4">
                                                <?php echo ucfirst(htmlspecialchars($transport['service_type'])); ?>
                                            </td>
                                            <td class="p-4">
                                                <?php 
                                                if (!empty($transport['route_name'])) {
                                                    echo htmlspecialchars($transport['route_name']);
                                                } else {
                                                    echo htmlspecialchars($transport['pickup_location'] . ' to ' . $transport['dropoff_location']);
                                                }
                                                ?>
                                            </td>
                                            <td class="p-4">
                                                <?php echo htmlspecialchars($transport['vehicle_name'] . ' (' . ucfirst($transport['vehicle_type']) . ')'); ?>
                                            </td>
                                            <td class="p-4">
                                                <?php echo htmlspecialchars($transport['booking_date'] . ' ' . $transport['booking_time']); ?>
                                            </td>
                                            <td class="p-4 text-green-600">
                                                $<?php echo htmlspecialchars($transport['price']); ?>
                                            </td>
                                            <td class="p-4">
                                                <span class="status-badge px-3 py-1 rounded-full text-white
                                                    <?php
                                                    switch ($transport['booking_status']) {
                                                        case 'pending':
                                                            echo 'bg-yellow-500';
                                                            break;
                                                        case 'confirmed':
                                                            echo 'bg-blue-500';
                                                            break;
                                                        case 'completed':
                                                            echo 'bg-green-500';
                                                            break;
                                                        case 'cancelled':
                                                            echo 'bg-red-500';
                                                            break;
                                                    }
                                                    ?>">
                                                    <?php echo ucfirst($transport['booking_status']); ?>
                                                </span>
                                            </td>
                                            <td class="p-4">
                                                <?php 
                                                if (!empty($transport['driver_name'])) {
                                                    echo htmlspecialchars($transport['driver_name']);
                                                    if (!empty($transport['driver_contact'])) {
                                                        echo '<br><span class="text-xs text-gray-500">' . htmlspecialchars($transport['driver_contact']) . '</span>';
                                                    }
                                                } else {
                                                    echo '<span class="text-gray-400">Not assigned yet</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } else { ?>
                        <div class="bg-gray-100 p-6 rounded-lg text-center">
                            <p class="text-xl text-gray-600">You haven't booked any transportation yet.</p>
                            <a href="book-transport.php" class="mt-4 inline-block bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Book Transportation
                            </a>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs and tab contents
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab and its content
                this.classList.add('active');
                document.getElementById(this.dataset.target).classList.add('active');
            });
        });

        // Flight countdown function
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
    </script>
</body>
</html>