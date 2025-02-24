<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php' ?>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 font-sans">
  <?php include 'includes/navbar.php' ?>>

  <section class="bg-teal-100 py-16 my-10" style="background-image: url('assets/images/flight.jpg'); background-size: cover; background-position: center; background-attachment: fixed;">
    <div class="container mx-auto text-center">

      <!-- Flight Search Form -->
      <div class="mt-8 flex flex-wrap justify-center space-x-6 bg-white bg-opacity-80 p-10 rounded-lg shadow-xl">
        <div class="w-full sm:w-1/3 mb-6 sm:mb-0 px-4">
          <label for="departure-city" class="block text-left text-gray-700 font-semibold text-lg">Departure City</label>
          <input type="text" id="departure-city" name="departure-city" class="w-full p-4 mt-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" placeholder="Enter city">
        </div>
        <div class="w-full sm:w-1/3 mb-6 sm:mb-0 px-4">
          <label for="arrival-city" class="block text-left text-gray-700 font-semibold text-lg">Arrival City</label>
          <input type="text" id="arrival-city" name="arrival-city" class="w-full p-4 mt-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" placeholder="Enter city">
        </div>
        <div class="w-full sm:w-1/3 mb-6 sm:mb-0 px-4">
          <label for="travel-date" class="block text-left text-gray-700 font-semibold text-lg">Travel Date</label>
          <input type="date" id="travel-date" name="travel-date" class="w-full p-4 mt-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
        </div>
      </div>

      <!-- Search Button -->
      <button id="search-btn" class="mt-6 px-8 py-4 bg-teal-600 text-white font-semibold text-lg rounded-lg hover:bg-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-300">
        Search Flights
      </button>
    </div>
  </section>



  <!-- Available Flights Section -->
  <section class="py-16">
    <div class="container mx-auto text-center">
      <h2 class="text-3xl font-bold text-teal-600">Available Umrah Flights from Pakistan</h2>
      <p class="mt-4 text-gray-700">Book your flight to Jeddah, Saudi Arabia for Umrah.</p>

      <!-- Flight Table -->
      <div class="mt-8 overflow-x-auto">
        <table id="flight-table" class="min-w-full table-auto bg-white border border-gray-200 rounded-lg shadow-md">
          <thead>
            <tr class="bg-teal-600 text-white">
              <th class="px-6 py-3 text-left">Flight</th>
              <th class="px-6 py-3 text-left">From</th>
              <th class="px-6 py-3 text-left">To</th>
              <th class="px-6 py-3 text-left">Departure Date</th>
              <th class="px-6 py-3 text-left">Price</th>
              <th class="px-6 py-3 text-left">Action</th>
            </tr>
          </thead>
          <tbody>
            <!-- Pre-populated Flight Data -->
            <tr class="border-b">
              <td class="px-6 py-4">PIA Airlines</td>
              <td class="px-6 py-4">Karachi</td>
              <td class="px-6 py-4">Jeddah</td>
              <td class="px-6 py-4">2025-03-10</td>
              <td class="px-6 py-4">$850</td>
              <td class="px-6 py-4">
                <button class="px-4 py-2 bg-teal-600 text-white font-semibold rounded-lg hover:bg-teal-500">
                  Book Now
                </button>
              </td>
            </tr>
            <tr class="border-b">
              <td class="px-6 py-4">Emirates Airlines</td>
              <td class="px-6 py-4">Lahore</td>
              <td class="px-6 py-4">Jeddah</td>
              <td class="px-6 py-4">2025-03-12</td>
              <td class="px-6 py-4">$900</td>
              <td class="px-6 py-4">
                <button class="px-4 py-2 bg-teal-600 text-white font-semibold rounded-lg hover:bg-teal-500" onclick="window.location.href='flight-book-now.php';">
                  Book Now
                </button>
              </td>
            </tr>
            <tr class="border-b">
              <td class="px-6 py-4">Qatar Airways</td>
              <td class="px-6 py-4">Islamabad</td>
              <td class="px-6 py-4">Jeddah</td>
              <td class="px-6 py-4">2025-03-15</td>
              <td class="px-6 py-4">$950</td>
              <td class="px-6 py-4">
                <button class="px-4 py-2 bg-teal-600 text-white font-semibold rounded-lg hover:bg-teal-500">
                  Book Now
                </button>
              </td>
            </tr>
            <tr class="border-b">
              <td class="px-6 py-4">Saudi Airlines</td>
              <td class="px-6 py-4">Karachi</td>
              <td class="px-6 py-4">Jeddah</td>
              <td class="px-6 py-4">2025-03-18</td>
              <td class="px-6 py-4">$980</td>
              <td class="px-6 py-4">
                <button class="px-4 py-2 bg-teal-600 text-white font-semibold rounded-lg hover:bg-teal-500" onclick="window.location.href='flight-book-now.php';">
                  Book Now
                </button>
              </td>
            </tr>
            <tr class="border-b">
              <td class="px-6 py-4">Flynas Airlines</td>
              <td class="px-6 py-4">Lahore</td>
              <td class="px-6 py-4">Jeddah</td>
              <td class="px-6 py-4">2025-03-20</td>
              <td class="px-6 py-4">$890</td>
              <td class="px-6 py-4">
                <button class="px-4 py-2 bg-teal-600 text-white font-semibold rounded-lg hover:bg-teal-500" onclick="window.location.href='flight-book-now.php';">
                  Book Now
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <style>

  </style>


  <!-- Footer -->
  <footer class="bg-teal-600 py-6 text-white">
    <div class="container mx-auto text-center">
      <p>&copy; 2025 Umrah Journey. All Rights Reserved.</p>
    </div>
  </footer>

  <script>
    document.getElementById('search-btn').addEventListener('click', function() {
      const departureCity = document.getElementById('departure-city').value.trim().toLowerCase();
      const arrivalCity = document.getElementById('arrival-city').value.trim().toLowerCase();
      const travelDate = document.getElementById('travel-date').value;

      // Basic validation
      if (!departureCity || !arrivalCity || !travelDate) {
        Swal.fire('Please fill in all fields.');
        return;
      }

      // Filter the flights based on the search criteria
      const flights = [{
          flight: 'PIA Airlines',
          from: 'karachi',
          to: 'jeddah',
          date: '2025-03-10',
          price: '$850'
        },
        {
          flight: 'Emirates Airlines',
          from: 'lahore',
          to: 'jeddah',
          date: '2025-03-12',
          price: '$900'
        },
        {
          flight: 'Qatar Airways',
          from: 'islamabad',
          to: 'jeddah',
          date: '2025-03-15',
          price: '$950'
        },
        {
          flight: 'Saudi Airlines',
          from: 'karachi',
          to: 'jeddah',
          date: '2025-03-18',
          price: '$980'
        },
        {
          flight: 'Flynas Airlines',
          from: 'lahore',
          to: 'jeddah',
          date: '2025-03-20',
          price: '$890'
        }
      ];

      // Filter the flights that match the search criteria
      const filteredFlights = flights.filter(flight =>
        flight.from.includes(departureCity) &&
        flight.to.includes(arrivalCity) &&
        flight.date >= travelDate
      );

      // Display the filtered flights
      const flightTable = document.getElementById('flight-table').getElementsByTagName('tbody')[0];
      flightTable.innerHTML = '';

      if (filteredFlights.length > 0) {
        filteredFlights.forEach(flight => {
          const row = flightTable.insertRow();
          row.innerHTML = `
            <td class="px-6 py-4">${flight.flight}</td>
            <td class="px-6 py-4">${flight.from.charAt(0).toUpperCase() + flight.from.slice(1)}</td>
            <td class="px-6 py-4">${flight.to.charAt(0).toUpperCase() + flight.to.slice(1)}</td>
            <td class="px-6 py-4">${flight.date}</td>
            <td class="px-6 py-4">${flight.price}</td>
            <td class="px-6 py-4"><button class="px-4 py-2 bg-teal-600 text-white font-semibold rounded-lg hover:bg-teal-500">Book Now</button></td>
          `;
        });
      } else {
        flightTable.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-700">No flights found</td></tr>';
      }
    });
  </script>

</body>

</html>