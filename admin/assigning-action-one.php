<?php
session_name("admin_session");
session_start();
include 'connection/connection.php';

if (!isset($_SESSION['admin_email'])) {
  header("Location: admin/login.php");
  exit();
}

if (!isset($_GET['booking_id'])) {
  header("Location: assigning-main-page.php");
  exit();
}

$booking_id = $_GET['booking_id'];

// Fetch booking details
$query = "SELECT pb.*, u.full_name, u.email, u.id as user_id, p.title, p.package_type, p.airline, p.flight_class, 
                 p.departure_city, p.departure_date, p.departure_time, p.arrival_city, p.price, p.package_image 
          FROM package_booking pb
          JOIN users u ON pb.user_id = u.id
          JOIN packages p ON pb.package_id = p.id
          WHERE pb.id = $booking_id";

$result = mysqli_query($conn, $query);
$booking = mysqli_fetch_assoc($result);
$user_id = $booking['user_id'];

// Fetch available hotels, transportation, and flights
$hotels_query = "SELECT h.*, h.room_count - COUNT(pa.id) AS available_rooms
                 FROM hotels h
                 LEFT JOIN package_assign pa ON h.id = pa.hotel_id
                 GROUP BY h.id
                 HAVING available_rooms > 0";
$hotels_result = mysqli_query($conn, $hotels_query);
$hotels = [];

if ($hotels_result) {
  while ($row = mysqli_fetch_assoc($hotels_result)) {
    $hotels[] = $row;
  }
}

$transport_query = "SELECT t.*, t.seats - COUNT(pa.id) AS available_seats
                    FROM transportation t
                    LEFT JOIN package_assign pa ON t.id = pa.transport_id
                    GROUP BY t.id
                    HAVING available_seats > 0";
$transport_result = mysqli_query($conn, $transport_query);
$transportations = [];

if ($transport_result) {
  while ($row = mysqli_fetch_assoc($transport_result)) {
    $transportations[] = $row;
  }
}

$flights_query = "SELECT f.*, 
                         f.economy_seats - COUNT(CASE WHEN pa.seat_type = 'economy' THEN 1 END) AS available_economy_seats,
                         f.business_seats - COUNT(CASE WHEN pa.seat_type = 'business' THEN 1 END) AS available_business_seats,
                         f.first_class_seats - COUNT(CASE WHEN pa.seat_type = 'first_class' THEN 1 END) AS available_first_class_seats
                  FROM flights f
                  LEFT JOIN package_assign pa ON f.id = pa.flight_id
                  GROUP BY f.id
                  HAVING available_economy_seats > 0 OR available_business_seats > 0 OR available_first_class_seats > 0";
$flights_result = mysqli_query($conn, $flights_query);
$flights = [];

if ($flights_result) {
  while ($row = mysqli_fetch_assoc($flights_result)) {
    $flights[] = $row;
  }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $hotel_id = $_POST['hotel'];
  $transport_id = $_POST['transport'];
  $flight_id = $_POST['flight'];
  $seat_type = $_POST['seat_type']; // New field for seat type
  $seat_number = $_POST['seat_number']; // New field for seat number
  $transport_seat_number = $_POST['transport_seat_number']; // New field for transport seat number

  // Check hotel availability
  $check_hotel_query = "SELECT * FROM package_assign WHERE hotel_id = '$hotel_id' AND user_id = '$user_id'";
  $hotel_result = mysqli_query($conn, $check_hotel_query);
  if (mysqli_num_rows($hotel_result) > 0) {
    $error = "The selected hotel is already assigned to this user.";
  } else {
    // Check flight seat availability
    $check_flight_query = "SELECT * FROM package_assign WHERE flight_id = '$flight_id' AND seat_type = '$seat_type' AND seat_number = '$seat_number'";
    $flight_result = mysqli_query($conn, $check_flight_query);
    if (mysqli_num_rows($flight_result) > 0) {
      $error = "The selected flight seat is already assigned to another user.";
    } else {
      // Check transportation seat availability
      $check_transport_query = "SELECT * FROM package_assign WHERE transport_id = '$transport_id' AND transport_seat_number = '$transport_seat_number'";
      $transport_result = mysqli_query($conn, $check_transport_query);
      if (mysqli_num_rows($transport_result) > 0) {
        $error = "The selected transportation seat is already assigned to another user.";
      } else {
        // Insert assigned data into package_assign table
        $insert_query = "INSERT INTO package_assign (booking_id, user_id, hotel_id, transport_id, flight_id, seat_type, seat_number, transport_seat_number)
                         VALUES ('$booking_id', '$user_id', '$hotel_id', '$transport_id', '$flight_id', '$seat_type', '$seat_number', '$transport_seat_number')";

        if (mysqli_query($conn, $insert_query)) {
          header("Location: assigning-main-page.php");
          exit();
        } else {
          $error = "Error inserting assignment: " . mysqli_error($conn);
        }
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assigning Action</title>
  <link rel="stylesheet" href="../assets/css/output.css">
  <link rel="stylesheet" href="assets/css/output.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
</head>

<body class="bg-gray-50 font-sans">
  <div class="flex flex-col md:flex-row h-screen">
    <?php include 'includes/sidebar.php'; ?>
    <div class="overflow-y-scroll main flex-1 flex flex-col">
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-teal-600 mb-4">Assigning Action</h1>
      </div>
      <br>
      <div class="container mx-auto  shadow-lg rounded-lg p-6">
        <?php if (isset($error)) : ?>
          <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
            <strong class="font-bold">Error! </strong>
            <span class="block"><?php echo htmlspecialchars($error); ?></span>
          </div>
        <?php endif; ?>

        <h2 class="text-xl font-bold text-gray-800 mb-6">Assign Services</h2>

        <form action="" method="POST" class="space-y-4">
          <div>
            <label for="hotel" class="block text-gray-700 font-semibold">Assign Hotel:</label>
            <select name="hotel" id="hotel"
              class="mt-1 block w-full bg-gray-100 border border-gray-300 rounded-lg py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
              <?php foreach ($hotels as $hotel) : ?>
                <option value="<?php echo $hotel['id']; ?>">
                  <?php echo htmlspecialchars($hotel['hotel_name']) . ' - Available Rooms: ' . htmlspecialchars($hotel['available_rooms']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label for="transport" class="block text-gray-700 font-semibold">Assign Transportation:</label>
            <select name="transport" id="transport"
              class="mt-1 block w-full bg-gray-100 border border-gray-300 rounded-lg py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
              <?php foreach ($transportations as $transport) : ?>
                <option value="<?php echo $transport['id']; ?>">
                  <?php echo htmlspecialchars($transport['transport_name']) . ' - Available Seats: ' . htmlspecialchars($transport['available_seats']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label for="transport_seat_number" class="block text-gray-700 font-semibold">Assign Transportation Seat Number:</label>
            <select name="transport_seat_number" id="transport_seat_number"
              class="mt-1 block w-full bg-gray-100 border border-gray-300 rounded-lg py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
              <?php foreach ($transportations as $transport) : ?>
                <?php for ($i = 1; $i <= $transport['available_seats']; $i++) : ?>
                  <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php endfor; ?>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label for="flight" class="block text-gray-700 font-semibold">Assign Flight:</label>
            <select name="flight" id="flight"
              class="mt-1 block w-full bg-gray-100 border border-gray-300 rounded-lg py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
              <?php foreach ($flights as $flight) : ?>
                <option value="<?php echo $flight['id']; ?>">
                  <?php echo htmlspecialchars($flight['flight_number']) . ' - Economy: ' . htmlspecialchars($flight['available_economy_seats']) . 
                                                        ', Business: ' . htmlspecialchars($flight['available_business_seats']) . 
                                                        ', First Class: ' . htmlspecialchars($flight['available_first_class_seats']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label for="seat_type" class="block text-gray-700 font-semibold">Select Seat Type:</label>
            <select name="seat_type" id="seat_type"
              class="mt-1 block w-full bg-gray-100 border border-gray-300 rounded-lg py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
              <option value="Null">Select Seat Type</option>
              <option value="economy">Economy</option>
              <option value="business">Business</option>
              <option value="first_class">First Class</option>
            </select>
          </div>

          <div>
            <label for="seat_number" class="block text-gray-700 font-semibold">Assign Seat Number:</label>
            <select name="seat_number" id="seat_number"
              class="mt-1 block w-full bg-gray-100 border border-gray-300 rounded-lg py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
              <!-- Options will be populated by JavaScript based on seat type -->
            </select>
          </div>

          <div class="flex justify-between items-center mt-6">
            <!-- Assign Button -->
            <button type="submit"
              class="flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white font-bold py-2 px-6 rounded-lg shadow-md transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-teal-400">
              <i class="fas fa-check-circle"></i> Assign
            </button>

            <!-- Cancel Button -->
            <a href="assigning-main-page.php"
              class="flex items-center gap-2 text-red-500 hover:text-red-600 font-semibold transition-all duration-300">
              <i class="fas fa-times-circle"></i> Cancel
            </a>
          </div>

        </form>
      </div>

    </div>
  </div>

  <script>
    document.getElementById('seat_type').addEventListener('change', function() {
      var seatType = this.value;
      var flightId = document.getElementById('flight').value;
      var seatNumberSelect = document.getElementById('seat_number');

      // Clear existing options
      seatNumberSelect.innerHTML = '';

      // Fetch available seats based on seat type and flight ID
      fetch(`get_available_seats.php?seat_type=${seatType}&flight_id=${flightId}`)
        .then(response => response.json())
        .then(data => {
          data.availableSeats.forEach(seat => {
            var option = document.createElement('option');
            option.value = seat;
            option.textContent = seat;
            seatNumberSelect.appendChild(option);
          });
        });
    });
  </script>
</body>

</html>