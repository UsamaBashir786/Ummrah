<?php
session_start();
include 'connection/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  // Store return URL for later login
  $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];

  // Optionally, display a message instead of redirecting

  // Set a default user_id for non-logged-in users (optional)
  $user_id = null;
} else {
  $user_id = $_SESSION['user_id'];
}


// Function to get taxi routes
function getTaxiRoutes()
{
  global $conn;
  $sql = "SELECT * FROM taxi_routes WHERE year = 2024 ORDER BY route_number";
  $result = $conn->query($sql);
  return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get rent a car routes
function getRentacarRoutes()
{
  global $conn;
  $sql = "SELECT * FROM rentacar_routes WHERE year = 2024 ORDER BY route_number";
  $result = $conn->query($sql);
  return $result->fetch_all(MYSQLI_ASSOC);
}

// Get data
$taxi_routes = getTaxiRoutes();
$rentacar_routes = getRentacarRoutes();

// Handle booking form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_transportation'])) {
  $service_type = $_POST['service_type'];
  $route_id = $_POST['route_id'];
  $route_name = $_POST['route_name'];
  $vehicle_type = $_POST['vehicle_type'];
  $vehicle_name = $_POST['vehicle_name'];
  $price = $_POST['price'];

  // Redirect to transportation booking page with all parameters
  header("Location: transportation-booking.php?user_id=$user_id&service_type=$service_type&route_id=$route_id&route_name=" . urlencode($route_name) . "&vehicle_type=$vehicle_type&vehicle_name=" . urlencode($vehicle_name) . "&price=$price");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <link href="https://unpkg.com/boxicons/css/boxicons.min.css" rel="stylesheet">
  <script PKRc="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.1/gsap.min.js"></script>
  <style>
    .price-table {
      border-collapse: separate;
      border-spacing: 0;
      width: 100%;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      background-color: white;
    }

    .price-table th {
      background-color: #0d9488;
      color: white;
      font-weight: 600;
      text-align: center;
      padding: 12px;
    }

    .price-table td {
      border-top: 1px solid #e2e8f0;
      padding: 12px;
      text-align: center;
    }

    .price-table tr:nth-child(even) {
      background-color: #f8fafc;
    }

    .price-table tr:hover {
      background-color: #e6fffa;
    }

    .book-btn {
      background-color: #0d9488;
      color: white;
      padding: 8px 16px;
      border-radius: 6px;
      font-weight: 500;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
    }

    .book-btn:hover {
      background-color: #0f766e;
      transform: translateY(-2px);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .price-heading {
      background: linear-gradient(90deg, #0d9488 0%, #0f766e 100%);
      color: white;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .tab-buttons {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
    }

    .tab-btn {
      padding: 10px 20px;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      border: none;
    }

    .tab-btn.active {
      background-color: #0d9488;
      color: white;
    }

    .tab-btn:not(.active) {
      background-color: #e2e8f0;
      color: #1e293b;
    }

    .tab-btn:hover:not(.active) {
      background-color: #cbd5e1;
    }

    .tab-content {
      display: none;
    }

    .tab-content.active {
      display: block;
      animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .rentacar-header {
      background: linear-gradient(90deg, #1e40af 0%, #1d4ed8 100%);
    }

    .rentacar-th {
      background-color: #1d4ed8;
    }

    .rentacar-btn {
      background-color: #1d4ed8;
    }

    .rentacar-btn:hover {
      background-color: #1e40af;
    }

    .rentacar-row:hover {
      background-color: #eff6ff !important;
    }
  </style>
</head>

<body class="bg-gray-50">
  <?php include 'includes/navbar.php'; ?>

  <section class="py-10 bg-gray-50">
    <div class="container mx-auto px-4">
      <br><br>
      <br><br>
      <h1 class="text-4xl font-bold text-center mb-8">Transportation Price Lists 2024</h1>

      <div class="tab-buttons flex justify-center">
        <button class="tab-btn active" onclick="switchTab('taxi')">Taxi Services</button>
        <button class="tab-btn" onclick="switchTab('rentacar')">Rent A Car Services</button>
      </div>

      <!-- Taxi Price List -->
      <div id="taxi-tab" class="tab-content active">
        <div class="price-heading">
          <h2 class="text-2xl font-bold">Best Taxi Service for Umrah and Hajj</h2>
          <p class="text-white opacity-90">Camry, Sonata, Starex, Staria, Hiace - 2024 Price List</p>
        </div>

        <div class="overflow-x-auto">
          <table class="price-table">
            <thead>
              <tr>
                <th class="w-16">No.</th>
                <th class="text-left">Routes</th>
                <th>Camry / Sonata</th>
                <th>Starex / Staria</th>
                <th>Hiace</th>
                <th>Book Now</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($taxi_routes as $route): ?>
                <tr>
                  <td><?php echo $route['route_number']; ?></td>
                  <td class="text-left font-medium"><?php echo htmlspecialchars($route['route_name']); ?></td>
                  <td><?php echo $route['camry_sonata_price']; ?> PKR</td>
                  <td><?php echo $route['starex_staria_price']; ?> PKR</td>
                  <td><?php echo $route['hiace_price']; ?> PKR</td>
                  <td>
                    <div class="flex flex-wrap justify-center gap-2">
                      <form method="POST" action="">
                        <input type="hidden" name="book_transportation" value="1">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        <input type="hidden" name="service_type" value="taxi">
                        <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
                        <input type="hidden" name="route_name" value="<?php echo htmlspecialchars($route['route_name']); ?>">
                        <input type="hidden" name="vehicle_type" value="camry">
                        <input type="hidden" name="vehicle_name" value="Camry/Sonata">
                        <input type="hidden" name="price" value="<?php echo $route['camry_sonata_price']; ?>">
                        <button type="submit" class="book-btn">
                          <i class="bx bxs-car mr-1"></i> Camry
                        </button>
                      </form>

                      <form method="POST" action="">
                        <input type="hidden" name="book_transportation" value="1">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        <input type="hidden" name="service_type" value="taxi">
                        <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
                        <input type="hidden" name="route_name" value="<?php echo htmlspecialchars($route['route_name']); ?>">
                        <input type="hidden" name="vehicle_type" value="starex">
                        <input type="hidden" name="vehicle_name" value="Starex/Staria">
                        <input type="hidden" name="price" value="<?php echo $route['starex_staria_price']; ?>">
                        <button type="submit" class="book-btn">
                          <i class="bx bxs-car mr-1"></i> Starex
                        </button>
                      </form>

                      <form method="POST" action="">
                        <input type="hidden" name="book_transportation" value="1">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        <input type="hidden" name="service_type" value="taxi">
                        <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
                        <input type="hidden" name="route_name" value="<?php echo htmlspecialchars($route['route_name']); ?>">
                        <input type="hidden" name="vehicle_type" value="hiace">
                        <input type="hidden" name="vehicle_name" value="Hiace">
                        <input type="hidden" name="price" value="<?php echo $route['hiace_price']; ?>">
                        <button type="submit" class="book-btn">
                          <i class="bx bxs-car mr-1"></i> Hiace
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Rent A Car Price List -->
      <div id="rentacar-tab" class="tab-content">
        <div class="price-heading rentacar-header">
          <h2 class="text-2xl font-bold">Best Umrah and Hajj Rent A Car Services</h2>
          <p class="text-white opacity-90">GMC & Coaster - 2024 Price List</p>
        </div>

        <div class="overflow-x-auto">
          <table class="price-table">
            <thead>
              <tr>
                <th class="w-16 rentacar-th">No.</th>
                <th class="text-left rentacar-th">Routes</th>
                <th class="rentacar-th">GMC 16-19</th>
                <th class="rentacar-th">GMC 22-23</th>
                <th class="rentacar-th">COASTER</th>
                <th class="rentacar-th">Book Now</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rentacar_routes as $route): ?>
                <tr class="rentacar-row">
                  <td><?php echo $route['route_number']; ?></td>
                  <td class="text-left font-medium"><?php echo htmlspecialchars($route['route_name']); ?></td>
                  <td><?php echo $route['gmc_16_19_price']; ?> PKR</td>
                  <td><?php echo $route['gmc_22_23_price']; ?> PKR</td>
                  <td><?php echo $route['coaster_price']; ?> PKR</td>
                  <td>
                    <div class="flex flex-wrap justify-center gap-2">
                      <form method="POST" action="">
                        <input type="hidden" name="book_transportation" value="1">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        <input type="hidden" name="service_type" value="rentacar">
                        <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
                        <input type="hidden" name="route_name" value="<?php echo htmlspecialchars($route['route_name']); ?>">
                        <input type="hidden" name="vehicle_type" value="gmc16">
                        <input type="hidden" name="vehicle_name" value="GMC 16-19 Seater">
                        <input type="hidden" name="price" value="<?php echo $route['gmc_16_19_price']; ?>">
                        <button type="submit" class="book-btn rentacar-btn">
                          <i class="bx bxs-bus mr-1"></i> GMC 16-19
                        </button>
                      </form>

                      <form method="POST" action="">
                        <input type="hidden" name="book_transportation" value="1">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        <input type="hidden" name="service_type" value="rentacar">
                        <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
                        <input type="hidden" name="route_name" value="<?php echo htmlspecialchars($route['route_name']); ?>">
                        <input type="hidden" name="vehicle_type" value="gmc22">
                        <input type="hidden" name="vehicle_name" value="GMC 22-23 Seater">
                        <input type="hidden" name="price" value="<?php echo $route['gmc_22_23_price']; ?>">
                        <button type="submit" class="book-btn rentacar-btn">
                          <i class="bx bxs-bus mr-1"></i> GMC 22-23
                        </button>
                      </form>

                      <form method="POST" action="">
                        <input type="hidden" name="book_transportation" value="1">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        <input type="hidden" name="service_type" value="rentacar">
                        <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
                        <input type="hidden" name="route_name" value="<?php echo htmlspecialchars($route['route_name']); ?>">
                        <input type="hidden" name="vehicle_type" value="coaster">
                        <input type="hidden" name="vehicle_name" value="Coaster">
                        <input type="hidden" name="price" value="<?php echo $route['coaster_price']; ?>">
                        <button type="submit" class="book-btn rentacar-btn">
                          <i class="bx bxs-bus mr-1"></i> Coaster
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>

  <section class="py-10 bg-white">
    <div class="container mx-auto px-4 text-center">
      <h2 class="text-3xl font-bold mb-4">Need Assistance?</h2>
      <p class="text-lg text-gray-600 mb-6">Our team is ready to help you with your transportation needs</p>
      <div class="flex flex-wrap justify-center gap-4">
        <a href="contact.php" class="bg-teal-600 text-white px-6 py-3 rounded-lg hover:bg-teal-700 transition duration-300">
          <i class="bx bx-envelope mr-2"></i> Contact Us
        </a>
        <a href="tel:+966123456789" class="bg-gray-100 text-gray-800 px-6 py-3 rounded-lg hover:bg-gray-200 transition duration-300">
          <i class="bx bx-phone mr-2"></i> Call Now
        </a>
      </div>
    </div>
  </section>

  <?php include 'includes/footer.php'; ?>

  <script>
    function switchTab(tabName) {
      // Hide all tabs
      document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
      });

      // Remove active class from all buttons
      document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
      });

      // Show selected tab
      document.getElementById(tabName + '-tab').classList.add('active');

      // Add active class to clicked button
      event.target.classList.add('active');
    }
  </script>
</body>

</html>