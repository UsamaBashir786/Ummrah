<?php
// search_flights.php - Handles AJAX search requests for flights
include 'connection/connection.php';

// Get search parameters
$departure_city = mysqli_real_escape_string($conn, strtolower($_POST['departure_city']));
$arrival_city = mysqli_real_escape_string($conn, strtolower($_POST['arrival_city']));
$travel_date = mysqli_real_escape_string($conn, $_POST['travel_date']);

// Build the query
$query = "SELECT * FROM flights WHERE 1=1";

if (!empty($departure_city)) {
  $query .= " AND LOWER(departure_city) LIKE '%$departure_city%'";
}

if (!empty($arrival_city)) {
  $query .= " AND LOWER(arrival_city) LIKE '%$arrival_city%'";
}

if (!empty($travel_date)) {
  $query .= " AND departure_date >= '$travel_date'";
}

// Execute the query
$result = mysqli_query($conn, $query);

// Check if there are results
if (mysqli_num_rows($result) > 0) {
  // Output data of each row
  while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr class='border-b'>";
    echo "<td class='px-6 py-4'>" . htmlspecialchars($row['airline_name']) . "</td>";
    echo "<td class='px-6 py-4'>" . htmlspecialchars($row['flight_number']) . "</td>";
    echo "<td class='px-6 py-4'>" . htmlspecialchars($row['departure_city']) . "</td>";
    echo "<td class='px-6 py-4'>" . htmlspecialchars($row['arrival_city']) . "</td>";
    echo "<td class='px-6 py-4'>" . htmlspecialchars($row['departure_date']) . "</td>";
    echo "<td class='px-6 py-4'>" . htmlspecialchars($row['departure_time']) . "</td>";
    echo "<td class='px-6 py-4'>$" . htmlspecialchars($row['economy_price']) . "</td>";
    echo "<td class='px-6 py-4'>";
    echo "<button class='px-4 py-2 bg-teal-600 text-white font-semibold rounded-lg hover:bg-teal-500' ";
    echo "onclick=\"window.location.href='flight-book-now.php?flight_id=" . $row['id'] . "';\">Book Now</button>";
    echo "</td>";
    echo "</tr>";
  }
} else {
  echo "<tr><td colspan='8' class='px-6 py-4 text-center text-gray-700'>No flights found matching your criteria</td></tr>";
}

mysqli_close($conn);
