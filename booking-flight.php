<?php
session_start();
include 'connection/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// Get user_id from session
$user_id = $_SESSION['user_id'];

// Check if flight ID is provided
if (!isset($_GET['flight_id']) || empty($_GET['flight_id'])) {
  header("Location: flights.php");
  exit();
}

$flight_id = $_GET['flight_id'];
$error_message = "";
$success_message = "";
$flight_details = null;
$seats_data = null;
$booked_seats = [];

// Fetch flight details
$sql = "SELECT * FROM flights WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
  $flight_details = $result->fetch_assoc();
  
  // Parse seats data directly from flights table
  if (isset($flight_details['seats']) && !empty($flight_details['seats'])) {
    $seats_data = json_decode($flight_details['seats'], true);
  }
  
  // Check if flight_bookings table exists
  $check_table = $conn->query("SHOW TABLES LIKE 'flight_bookings'");
  
  if ($check_table->num_rows > 0) {
    // Check if seats column exists
    $check_seats_column = $conn->query("SHOW COLUMNS FROM flight_bookings LIKE 'seats'");
    
    if ($check_seats_column->num_rows > 0) {
        // Use new seats JSON column
        $booked_sql = "SELECT seats FROM flight_bookings WHERE flight_id = ?";
        $booked_stmt = $conn->prepare($booked_sql);
        $booked_stmt->bind_param("i", $flight_id);
        $booked_stmt->execute();
        $booked_result = $booked_stmt->get_result();
        
        $booked_seats = [];
        while ($booked_row = $booked_result->fetch_assoc()) {
            $seats = json_decode($booked_row['seats'], true);
            $booked_seats = array_merge($booked_seats, $seats);
        }
    } else {
        // Alter table to add seats column and migrate data
        $alter_table_sql = "ALTER TABLE flight_bookings 
            ADD COLUMN seats JSON NULL AFTER children_count";
        $conn->query($alter_table_sql);
        
        // Initialize empty booked_seats array since no seats are in JSON format yet
        $booked_seats = [];
        
        // Optionally, you could migrate existing seat_id data to JSON format:
        $migrate_sql = "SELECT id, seat_id FROM flight_bookings WHERE flight_id = ? AND seat_id IS NOT NULL";
        $migrate_stmt = $conn->prepare($migrate_sql);
        $migrate_stmt->bind_param("i", $flight_id);
        $migrate_stmt->execute();
        $migrate_result = $migrate_stmt->get_result();
        
        while ($row = $migrate_result->fetch_assoc()) {
            $seats_array = [$row['seat_id']];
            $seats_json = json_encode($seats_array);
            
            $update_sql = "UPDATE flight_bookings SET seats = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $seats_json, $row['id']);
            $update_stmt->execute();
            
            $booked_seats[] = $row['seat_id'];
        }
    }
  } else {
    $error_message = "Flight not found.";
  }
}

// Process booking form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_flight'])) {
  // Get form data
  $passenger_name = isset($_POST['passenger_name']) ? $_POST['passenger_name'] : '';
  $passenger_email = isset($_POST['passenger_email']) ? $_POST['passenger_email'] : '';
  $passenger_phone = isset($_POST['passenger_phone']) ? $_POST['passenger_phone'] : '';
  $selected_cabin = isset($_POST['cabin_class']) ? $_POST['cabin_class'] : '';
  $adult_count = isset($_POST['adult_count']) ? intval($_POST['adult_count']) : 1;
  $children_count = isset($_POST['children_count']) ? intval($_POST['children_count']) : 0;
  $selected_seats = isset($_POST['selected_seats']) ? $_POST['selected_seats'] : [];
  
  // Validate form data
  if (empty($passenger_name) || empty($passenger_email) || empty($passenger_phone) || empty($selected_cabin)) {
    $error_message = "All passenger information fields are required.";
  } elseif (empty($selected_seats) || count($selected_seats) != ($adult_count + $children_count)) {
    $error_message = "Please select seats for all passengers.";
  } else {
    // Start transaction
    $conn->begin_transaction();
    try {
      // Verify selected seats exist in the flight's seats data
      $seats_data = json_decode($flight_details['seats'], true);
      $valid_seats = [];
      foreach ($seats_data as $cabin => $cabin_data) {
        $valid_seats = array_merge($valid_seats, $cabin_data['seat_ids']);
      }
      
      // Validate that all selected seats are valid
      foreach ($selected_seats as $seat) {
        if (!in_array($seat, $valid_seats)) {
          throw new Exception("Invalid seat selection detected.");
        }
      }
      
      // Check if flight_bookings table exists
      $check_table = $conn->query("SHOW TABLES LIKE 'flight_bookings'");
      
      if ($check_table->num_rows == 0) {
        // Create the flight_bookings table if it doesn't exist
        $create_table_sql = "CREATE TABLE flight_bookings (
          id INT AUTO_INCREMENT PRIMARY KEY,
          flight_id INT NOT NULL,
          user_id INT NOT NULL,
          passenger_name VARCHAR(255) NOT NULL,
          passenger_email VARCHAR(255) NOT NULL,
          passenger_phone VARCHAR(50) NOT NULL,
          cabin_class VARCHAR(50) NOT NULL,
          adult_count INT NOT NULL DEFAULT 1,
          children_count INT NOT NULL DEFAULT 0,
          seats JSON NOT NULL,
          booking_date DATETIME NOT NULL,
          FOREIGN KEY (flight_id) REFERENCES flights(id),
          FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->query($create_table_sql);
      }
      
      // Check if seats are still available
      if (!empty($selected_seats)) {
        $check_seats_sql = "SELECT seats FROM flight_bookings WHERE flight_id = ?";
        $check_seats_stmt = $conn->prepare($check_seats_sql);
        $check_seats_stmt->bind_param("i", $flight_id);
        $check_seats_stmt->execute();
        $check_result = $check_seats_stmt->get_result();
        
        $booked_seats = [];
        while ($row = $check_result->fetch_assoc()) {
          $seats = json_decode($row['seats'], true);
          $booked_seats = array_merge($booked_seats, $seats);
        }
        
        // Check if any selected seat is already booked
        foreach ($selected_seats as $seat) {
          if (in_array($seat, $booked_seats)) {
            throw new Exception("Some selected seats have been booked by another user. Please try again.");
          }
        }
      }
      
      // Instead of looping through seats, create one booking with all seats
      $seats_json = json_encode($selected_seats);
      
      $booking_sql = "INSERT INTO flight_bookings (
        flight_id, 
        user_id,
        passenger_name, 
        passenger_email, 
        passenger_phone, 
        cabin_class, 
        adult_count, 
        children_count, 
        seats, 
        booking_date
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
      
      $booking_stmt = $conn->prepare($booking_sql);
      $booking_stmt->bind_param(
        "iissssiss", 
        $flight_id,
        $user_id,
        $passenger_name, 
        $passenger_email, 
        $passenger_phone, 
        $selected_cabin, 
        $adult_count, 
        $children_count, 
        $seats_json
      );
      $booking_stmt->execute();
      
      // Commit transaction
      $conn->commit();
      $success_message = "Flight booked successfully!";
      
      // Redirect to thank-you page with booking details
      header("Location: thankyou.php?flight_id=" . $flight_id);
      exit();
      
    } catch (Exception $e) {
      // Rollback transaction on error
      $conn->rollback();
      $error_message = "Error booking flight: " . $e->getMessage();
    }
  }
}

// Calculate flight duration
function calculateFlightDuration($departure_time, $arrival_time) {
  try {
    $departure = new DateTime($departure_time);
    $arrival = new DateTime($arrival_time);
    $interval = $departure->diff($arrival);
    
    $hours = $interval->h + ($interval->days * 24);
    $minutes = $interval->i;
    
    return $hours . 'h ' . $minutes . 'm';
  } catch (Exception $e) {
    return 'N/A';
  }
}

// Format date to be more readable
function formatDate($date) {
  try {
    $dateObj = new DateTime($date);
    return $dateObj->format('D, M j, Y'); // e.g., Mon, Jan 15, 2025
  } catch (Exception $e) {
    return $date;
  }
}

// Format time to be more readable
function formatTime($time) {
  try {
    $timeObj = new DateTime($time);
    return $timeObj->format('g:i A'); // e.g., 2:30 PM
  } catch (Exception $e) {
    return $time;
  }
}

// Determine the flight duration if available
$flight_duration = '';
if (isset($flight_details['departure_time']) && isset($flight_details['arrival_time'])) {
  $flight_duration = calculateFlightDuration($flight_details['departure_time'], $flight_details['arrival_time']);
}

// Format dates for display
$departure_date_formatted = isset($flight_details['departure_date']) ? formatDate($flight_details['departure_date']) : '';
$departure_time_formatted = isset($flight_details['departure_time']) ? formatTime($flight_details['departure_time']) : '';
$arrival_time_formatted = isset($flight_details['arrival_time']) ? formatTime($flight_details['arrival_time']) : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <style>
    .progress-bar {
      height: 4px;
      background-color: #e5e7eb;
      width: 100%;
      overflow: hidden;
    }
    
    .progress {
      height: 100%;
      background-color: #3b82f6;
      transition: width 0.3s ease;
    }
    
    .section-heading {
      position: relative;
      padding-left: 15px;
      margin-bottom: 1rem;
    }
    
    .section-heading::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      height: 100%;
      width: 4px;
      background-color: #3b82f6;
      border-radius: 2px;
    }
    
    .seat {
      width: 45px;
      height: 45px;
      margin: 5px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      border: 1px solid #ccc;
      border-radius: 8px;
      background-color: #f9fafb;
      transition: all 0.2s ease;
      font-weight: 500;
    }

    .seat.available {
      border-color: #10b981;
      background-color: #ecfdf5;
    }

    .seat.selected {
      background-color: #10b981;
      color: white;
      border-color: #059669;
      transform: scale(1.05);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .seat.booked {
      background-color: #f3f4f6;
      color: #9ca3af;
      border-color: #d1d5db;
      cursor: not-allowed;
      text-decoration: line-through;
    }

    .seat.first_class {
      background-color: #eff6ff;
      border-color: #93c5fd;
    }

    .seat.business {
      background-color: #eef2ff;
      border-color: #a5b4fc;
    }

    .seat.economy {
      background-color: #f0fdfa;
      border-color: #99f6e4;
    }

    .seat.first_class.selected {
      background-color: #3b82f6;
      border-color: #2563eb;
      color: white;
    }

    .seat.business.selected {
      background-color: #6366f1;
      border-color: #4f46e5;
      color: white;
    }

    .seat.economy.selected {
      background-color: #14b8a6;
      border-color: #0d9488;
      color: white;
    }
    
    .tooltip {
      position: relative;
    }

    .tooltip .tooltip-text {
      visibility: hidden;
      width: 120px;
      background-color: #333;
      color: #fff;
      text-align: center;
      border-radius: 6px;
      padding: 5px;
      position: absolute;
      z-index: 1;
      bottom: 125%;
      left: 50%;
      transform: translateX(-50%);
      opacity: 0;
      transition: opacity 0.3s;
      font-size: 12px;
      pointer-events: none;
    }

    .tooltip:hover .tooltip-text {
      visibility: visible;
      opacity: 1;
    }
    
    .flight-card {
      border-radius: 12px;
      overflow: hidden;
      transition: all 0.3s ease;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    .flight-card:hover {
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      transform: translateY(-2px);
    }
    
    .cabin-tab {
      padding: 10px 20px;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .cabin-tab.active {
      background-color: #3b82f6;
      color: white;
      font-weight: 500;
    }
    
    .form-input {
      transition: all 0.3s ease;
      border-radius: 8px;
    }
    
    .form-input:focus {
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
      border-color: #3b82f6;
    }
    
    .flight-path-line {
      position: absolute;
      left: 20px;
      top: 30px;
      bottom: 30px;
      width: 2px;
      background-color: #e5e7eb;
      z-index: 0;
    }
    
    .flight-path-dot {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background-color: #3b82f6;
      position: absolute;
      left: 15px;
      z-index: 1;
    }
    
    .flight-path-dot.departure {
      top: 30px;
    }
    
    .flight-path-dot.arrival {
      bottom: 30px;
    }
    
    .fancy-checkbox {
      display: flex;
      align-items: center;
      cursor: pointer;
    }
    
    .fancy-checkbox input {
      position: absolute;
      opacity: 0;
      cursor: pointer;
      height: 0;
      width: 0;
    }
    
    .checkmark {
      height: 22px;
      width: 22px;
      background-color: #fff;
      border: 2px solid #d1d5db;
      border-radius: 4px;
      margin-right: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
    }
    
    .fancy-checkbox:hover .checkmark {
      border-color: #3b82f6;
    }
    
    .fancy-checkbox input:checked ~ .checkmark {
      background-color: #3b82f6;
      border-color: #3b82f6;
    }
    
    .checkmark:after {
      content: "";
      display: none;
      width: 5px;
      height: 10px;
      border: solid white;
      border-width: 0 2px 2px 0;
      transform: rotate(45deg);
    }
    
    .fancy-checkbox input:checked ~ .checkmark:after {
      display: block;
    }
    
    .booking-steps {
      display: flex;
      justify-content: space-between;
      margin-bottom: 30px;
      position: relative;
    }
    
    .booking-step {
      display: flex;
      flex-direction: column;
      align-items: center;
      z-index: 1;
      flex: 1;
    }
    
    .step-number {
      width: 35px;
      height: 35px;
      border-radius: 50%;
      background-color: #e5e7eb;
      color: #6b7280;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      margin-bottom: 8px;
      transition: all 0.3s ease;
    }
    
    .step-title {
      font-size: 14px;
      color: #6b7280;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .booking-step.active .step-number {
      background-color: #3b82f6;
      color: white;
    }
    
    .booking-step.active .step-title {
      color: #3b82f6;
      font-weight: 600;
    }
    
    .booking-step.completed .step-number {
      background-color: #10b981;
      color: white;
    }
    
    .step-line {
      position: absolute;
      top: 17px;
      left: 50px;
      right: 50px;
      height: 2px;
      background-color: #e5e7eb;
      z-index: 0;
    }
    
    .number-input {
      display: flex;
      align-items: center;
      border-radius: 8px;
      overflow: hidden;
      border: 1px solid #d1d5db;
    }
    
    .number-input button {
      width: 40px;
      height: 40px;
      background-color: #f3f4f6;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    
    .number-input button:hover {
      background-color: #e5e7eb;
    }
    
    .number-input input {
      width: 60px;
      height: 40px;
      border: none;
      text-align: center;
      font-weight: 500;
    }
    
    .card-flip {
      perspective: 1000px;
    }

    .card-flip-inner {
      transition: transform 0.6s;
      transform-style: preserve-3d;
    }

    .card-flip.flipped .card-flip-inner {
      transform: rotateY(180deg);
    }

    .card-front, .card-back {
      backface-visibility: hidden;
    }

    .card-back {
      transform: rotateY(180deg);
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
    }
  </style>
</head>

<body class="bg-gray-50 min-h-screen py-6 px-4 sm:px-6 lg:px-8">
  <?php include 'includes/navbar.php'; ?>
  
  <div class="max-w-5xl mx-auto">
    <br><br><br>
    <br><br><br>
    <!-- Progress Tracker -->
    <div class="booking-steps mb-8 px-4">
      <div class="step-line"></div>
      <div class="booking-step active" id="step1">
        <div class="step-number">1</div>
        <div class="step-title">Flight Details</div>
      </div>
      <div class="booking-step" id="step2">
        <div class="step-number">2</div>
        <div class="step-title">Passenger Info</div>
      </div>
      <div class="booking-step" id="step3">
        <div class="step-number">3</div>
        <div class="step-title">Seat Selection</div>
      </div>
      <div class="booking-step" id="step4">
        <div class="step-number">4</div>
        <div class="step-title">Confirmation</div>
      </div>
    </div>
  
    <?php if (!empty($error_message)): ?>
      <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg mb-6 shadow-sm animate__animated animate__fadeIn">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-9v4a1 1 0 11-2 0v-4a1 1 0 112 0zm-1-5a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-red-700"><?php echo htmlspecialchars($error_message); ?></p>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
      <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg mb-6 shadow-sm animate__animated animate__fadeIn">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-green-700"><?php echo htmlspecialchars($success_message); ?></p>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($flight_details): ?>
      <!-- Multistep Form Container -->
      <div class="bg-white shadow-md rounded-xl overflow-hidden">
        <!-- Step 1: Flight Details (Always visible) -->
        <div id="flight-details-section" class="p-6 border-b border-gray-200">
          <h2 class="section-heading text-xl font-semibold text-gray-900 mb-4">Flight Details</h2>
          
          <div class="flight-card bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center">
                <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center bg-blue-100 rounded-full">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($flight_details['airline'] ?? 'Airline'); ?></p>
                  <p class="text-sm text-gray-600">Flight #<?php echo htmlspecialchars($flight_details['flight_number']); ?></p>
                </div>
              </div>
              <?php if (!empty($flight_details['stops'])): ?>
                <?php 
                  $stops = json_decode($flight_details['stops'], true);
                  $stop_count = is_array($stops) ? count($stops) : 0;
                ?>
                <div class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-sm font-medium">
                  <?php echo $stop_count . ' ' . ($stop_count == 1 ? 'Stop' : 'Stops'); ?>
                </div>
              <?php else: ?>
                <div class="px-3 py-1 rounded-full bg-green-50 text-green-700 text-sm font-medium">
                  Direct Flight
                </div>
              <?php endif; ?>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
              <!-- Departure -->
              <div class="col-span-1">
                <p class="text-sm text-gray-500 mb-1">Departure</p>
                <div class="flex items-center">
                  <div class="text-xl font-bold"><?php echo $departure_time_formatted; ?></div>
                  <div class="mx-2 text-gray-400">•</div>
                  <div class="text-gray-600"><?php echo $departure_date_formatted; ?></div>
                </div>
                <p class="text-gray-700 mt-1"><?php echo htmlspecialchars($flight_details['departure_city']); ?></p>
              </div>
              
              <!-- Flight Duration -->
              <div class="col-span-1 flex flex-col items-center justify-center">
                <p class="text-sm text-gray-500 mb-1">Duration</p>
                <div class="flex items-center">
                  <div class="h-1 w-2 bg-gray-300 rounded-full"></div>
                  <div class="h-1 flex-1 mx-1 bg-gray-300"></div>
                  <div>✈️</div>
                  <div class="h-1 flex-1 mx-1 bg-gray-300"></div>
                  <div class="h-1 w-2 bg-gray-300 rounded-full"></div>
                </div>
                <p class="text-gray-700 font-medium mt-1"><?php echo $flight_duration; ?></p>
              </div>
              
              <!-- Arrival -->
              <div class="col-span-1 text-right">
                <p class="text-sm text-gray-500 mb-1">Arrival</p>
                <div class="flex items-center justify-end">
                  <div class="text-xl font-bold"><?php echo $arrival_time_formatted; ?></div>
                  <div class="mx-2 text-gray-400">•</div>
                  <div class="text-gray-600"><?php echo $departure_date_formatted; ?></div>
                </div>
                <p class="text-gray-700 mt-1"><?php echo htmlspecialchars($flight_details['arrival_city']); ?></p>
              </div>
            </div>
            
            <!-- Cabin Classes & Prices -->
            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
              <h3 class="text-lg font-medium text-gray-800 mb-3">Available Fares</h3>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php
                if (isset($flight_details['prices']) && !empty($flight_details['prices'])) {
                  $prices = json_decode($flight_details['prices'], true);
                  if (is_array($prices)) {
                    foreach ($prices as $class => $price) {
                      $display_class = ucwords(str_replace('_', ' ', $class));
                      $class_icon = '';
                      $bg_color = '';
                      $text_color = '';
                      
                      if ($class == 'economy') {
                        $class_icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>';
                        $bg_color = 'bg-green-50';
                        $text_color = 'text-green-700';
                      } else if ($class == 'business') {
                        $class_icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>';
                        $bg_color = 'bg-purple-50';
                        $text_color = 'text-purple-700';
                      } else if ($class == 'first_class') {
                        $class_icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>';
                        $bg_color = 'bg-blue-50';
                        $text_color = 'text-blue-700';
                      }
                      ?>
                      <div class="<?php echo $bg_color; ?> p-3 rounded-lg border border-gray-200 transition-transform transform hover:scale-105 hover:shadow-sm">
                        <div class="flex justify-between items-center">
                          <div class="flex items-center">
                            <div class="mr-2 <?php echo $text_color; ?>">
                              <?php echo $class_icon; ?>
                            </div>
                            <span class="font-medium"><?php echo htmlspecialchars($display_class); ?></span>
                          </div>
                          <span class="text-lg font-bold <?php echo $text_color; ?>">$<?php echo htmlspecialchars($price); ?></span>
                        </div>
                      </div>
                      <?php
                    }
                  }
                }
                ?>
              </div>
            </div>
          </div>
          
          <div class="mt-6 flex justify-end">
            <button type="button" class="next-step px-6 py-2 bg-blue-600 text-white rounded-lg shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition-colors" data-next="passenger-info-section">
              Continue to Passenger Information
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block ml-1" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Booking Form -->
        <form method="post" action="" id="booking-form" class="divide-y divide-gray-200">
          <!-- Step 2: Passenger Information -->
          <div id="passenger-info-section" class="p-6 hidden">
            <h2 class="section-heading text-xl font-semibold text-gray-900 mb-6">Passenger Information</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="passenger_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                <input
                  type="text"
                  id="passenger_name"
                  name="passenger_name"
                  required
                  placeholder="Enter your full name"
                  class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none">
              </div>
              
              <div>
                <label for="passenger_email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                <input
                  type="email"
                  id="passenger_email"
                  name="passenger_email"
                  required
                  placeholder="your.email@example.com"
                  class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none">
              </div>
              
              <div>
                <label for="passenger_phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                <input
                  type="tel"
                  id="passenger_phone"
                  name="passenger_phone"
                  required
                  placeholder="(123) 456-7890"
                  class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none">
              </div>
              
              <div>
                <label for="cabin_class" class="block text-sm font-medium text-gray-700 mb-2">Select Cabin Class</label>
                <select
                  id="cabin_class"
                  name="cabin_class"
                  required
                  class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none">
                  <?php
                  if (isset($flight_details['prices']) && !empty($flight_details['prices'])) {
                    $prices = json_decode($flight_details['prices'], true);
                    if (is_array($prices)) {
                      foreach ($prices as $class => $price) {
                        if (is_string($class) && is_numeric($price)) {
                          $display_class = ucwords(str_replace('_', ' ', $class));
                          echo "<option value=\"" . htmlspecialchars($class) . "\">" . htmlspecialchars($display_class) . " ($" . htmlspecialchars($price) . ")</option>";
                        }
                      }
                    }
                  } else {
                    echo "<option value=\"economy\">Economy</option>";
                    echo "<option value=\"business\">Business</option>";
                    echo "<option value=\"first_class\">First Class</option>";
                  }
                  ?>
                </select>
              </div>
            </div>
            
            <!-- Passenger Counter Section -->
            <div class="mt-8">
              <h3 class="text-lg font-medium text-gray-800 mb-4">Number of Passengers</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Adults Counter -->
                <div class="p-4 bg-gray-50 rounded-lg">
                  <label class="block text-sm font-medium text-gray-700 mb-3">Adults (12+ years)</label>
                  <div class="number-input">
                    <button type="button" class="decrease-adults">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                      </svg>
                    </button>
                    <input
                      type="number"
                      name="adult_count"
                      id="adult_count"
                      value="1"
                      min="1"
                      max="8"
                      readonly>
                    <button type="button" class="increase-adults">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                      </svg>
                    </button>
                  </div>
                </div>

                <!-- Children Counter -->
                <div class="p-4 bg-gray-50 rounded-lg">
                  <label class="block text-sm font-medium text-gray-700 mb-3">Children (2-11 years)</label>
                  <div class="number-input">
                    <button type="button" class="decrease-children">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                      </svg>
                    </button>
                    <input
                      type="number"
                      name="children_count"
                      id="children_count"
                      value="0"
                      min="0"
                      max="8"
                      readonly>
                    <button type="button" class="increase-children">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
              <p class="text-sm text-gray-500 mt-2">Maximum 8 passengers per booking.</p>
            </div>
            
            <div class="mt-8 flex justify-between">
              <button type="button" class="prev-step px-6 py-2 bg-gray-200 text-gray-700 rounded-lg shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-50 transition-colors" data-prev="flight-details-section">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Back
              </button>
              <button type="button" class="next-step px-6 py-2 bg-blue-600 text-white rounded-lg shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition-colors" data-next="seat-selection-section">
                Continue to Seat Selection
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block ml-1" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </button>
            </div>
          </div>
          
          <!-- Step 3: Seat Selection -->
          <div id="seat-selection-section" class="p-6 hidden">
            <h2 class="section-heading text-xl font-semibold text-gray-900 mb-4">Seat Selection</h2>
            <div class="bg-blue-50 p-4 rounded-lg mb-6">
              <div class="flex items-start">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm text-blue-700">Please select <span id="seats-to-select" class="font-bold">1</span> seat(s) for your passengers.</p>
                </div>
              </div>
            </div>
            
            <!-- Cabin Class Tabs -->
            <div class="mb-6">
              <div class="flex space-x-4 overflow-x-auto pb-2">
                <?php if ($seats_data): ?>
                  <?php foreach (array_keys($seats_data) as $index => $cabin_class): ?>
                    <div class="cabin-tab <?php echo $index === 0 ? 'active' : ''; ?>" data-cabin="<?php echo htmlspecialchars($cabin_class); ?>">
                      <?php echo ucwords(str_replace('_', ' ', $cabin_class)); ?>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
            
            <!-- Seat Legend -->
            <div class="mb-6 flex flex-wrap gap-4 bg-gray-50 p-4 rounded-lg">
              <div class="flex items-center">
                <div class="w-5 h-5 bg-gray-100 border border-gray-300 rounded mr-2"></div>
                <span class="text-sm">Unavailable</span>
              </div>
              <div class="flex items-center">
                <div class="w-5 h-5 bg-f0fdfa border border-99f6e4 rounded mr-2"></div>
                <span class="text-sm">Economy</span>
              </div>
              <div class="flex items-center">
                <div class="w-5 h-5 bg-eef2ff border border-a5b4fc rounded mr-2"></div>
                <span class="text-sm">Business</span>
              </div>
              <div class="flex items-center">
                <div class="w-5 h-5 bg-eff6ff border border-93c5fd rounded mr-2"></div>
                <span class="text-sm">First Class</span>
              </div>
              <div class="flex items-center">
                <div class="w-5 h-5 bg-green-500 border border-green-600 rounded mr-2"></div>
                <span class="text-sm">Selected</span>
              </div>
            </div>
            
            <!-- Seat Map Container -->
            <div id="seats-container" class="mb-8">
              <?php if ($seats_data): ?>
                <?php foreach ($seats_data as $cabin_class => $cabin_data): ?>
                  <div class="seat-cabin <?php echo $cabin_class !== array_key_first($seats_data) ? 'hidden' : ''; ?>" data-cabin="<?php echo htmlspecialchars($cabin_class); ?>">
                    <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
                      <h4 class="font-medium text-lg text-gray-800 mb-4"><?php echo ucwords(str_replace('_', ' ', $cabin_class)); ?> Cabin</h4>
                      
                      <div class="flex flex-wrap justify-center">
                        <?php foreach ($cabin_data['seat_ids'] as $seat_id): ?>
                          <?php 
                            $is_booked = in_array($seat_id, $booked_seats);
                            $seat_status = $is_booked ? 'booked' : 'available';
                          ?>
                          <div 
                            class="seat <?php echo $seat_status; ?> <?php echo $cabin_class; ?> tooltip" 
                            data-seat-id="<?php echo htmlspecialchars($seat_id); ?>"
                            data-cabin-class="<?php echo htmlspecialchars($cabin_class); ?>"
                            <?php if ($is_booked): ?>disabled<?php endif; ?>
                          >
                            <?php echo htmlspecialchars($seat_id); ?>
                            <span class="tooltip-text"><?php echo $is_booked ? 'Booked' : 'Available'; ?></span>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="text-center p-8 bg-red-50 rounded-lg">
                  <svg class="h-12 w-12 text-red-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  <p class="text-red-500 font-medium">Seat information not available.</p>
                </div>
              <?php endif; ?>
            </div>
            
            <!-- Selected Seats Summary -->
            <div class="mb-6 bg-gray-50 p-4 rounded-lg">
              <h4 class="font-medium text-gray-800 mb-2">Your Selected Seats</h4>
              <div id="selected-seats-chips" class="flex flex-wrap gap-2">
                <span id="no-seats-selected" class="text-gray-500">No seats selected yet</span>
              </div>
              <!-- Hidden inputs for selected seats -->
              <div id="selected-seats-inputs"></div>
            </div>
            
            <div class="mt-8 flex justify-between">
              <button type="button" class="prev-step px-6 py-2 bg-gray-200 text-gray-700 rounded-lg shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-50 transition-colors" data-prev="passenger-info-section">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Back
              </button>
              <button type="button" class="next-step px-6 py-2 bg-blue-600 text-white rounded-lg shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition-colors" data-next="confirmation-section" id="to-confirmation-btn">
                Continue to Confirmation
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block ml-1" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </button>
            </div>
          </div>
          
          <!-- Step 4: Confirmation -->
          <div id="confirmation-section" class="p-6 hidden">
            <h2 class="section-heading text-xl font-semibold text-gray-900 mb-6">Booking Confirmation</h2>
            
            <div class="p-6 bg-gray-50 rounded-lg mb-6">
              <h3 class="text-lg font-medium text-gray-800 mb-4">Flight Summary</h3>
              
              <div class="bg-white rounded-lg p-4 mb-4 shadow-sm">
                <div class="flex justify-between items-center mb-2">
                  <div class="font-bold"><?php echo htmlspecialchars($flight_details['airline'] ?? 'Airline'); ?></div>
                  <div class="text-sm text-gray-500">Flight #<?php echo htmlspecialchars($flight_details['flight_number']); ?></div>
                </div>
                
                <div class="flex items-start space-x-4">
                  <div class="w-2/5">
                    <div class="text-sm text-gray-500">From</div>
                    <div class="font-medium"><?php echo htmlspecialchars($flight_details['departure_city']); ?></div>
                    <div class="text-sm"><?php echo $departure_time_formatted; ?>, <?php echo $departure_date_formatted; ?></div>
                  </div>
                  
                  <div class="w-1/5 flex flex-col items-center justify-center">
                    <div class="text-sm text-gray-500">Duration</div>
                    <div class="text-sm font-medium"><?php echo $flight_duration; ?></div>
                  </div>
                  
                  <div class="w-2/5 text-right">
                    <div class="text-sm text-gray-500">To</div>
                    <div class="font-medium"><?php echo htmlspecialchars($flight_details['arrival_city']); ?></div>
                    <div class="text-sm"><?php echo $arrival_time_formatted; ?>, <?php echo $departure_date_formatted; ?></div>
                  </div>
                </div>
              </div>
              
              <h3 class="text-lg font-medium text-gray-800 mb-4">Passenger Information</h3>
              
              <div class="bg-white rounded-lg p-4 mb-4 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <div class="text-sm text-gray-500">Passenger</div>
                    <div class="font-medium" id="summary-passenger-name">-</div>
                  </div>
                  
                  <div>
                    <div class="text-sm text-gray-500">Email</div>
                    <div class="font-medium" id="summary-passenger-email">-</div>
                  </div>
                  
                  <div>
                    <div class="text-sm text-gray-500">Phone</div>
                    <div class="font-medium" id="summary-passenger-phone">-</div>
                  </div>
                  
                  <div>
                    <div class="text-sm text-gray-500">Cabin Class</div>
                    <div class="font-medium" id="summary-cabin-class">-</div>
                  </div>
                </div>
              </div>
              
              <h3 class="text-lg font-medium text-gray-800 mb-4">Seat Information</h3>
              
              <div class="bg-white rounded-lg p-4 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <div class="text-sm text-gray-500">Adults</div>
                    <div class="font-medium" id="summary-adult-count">1</div>
                  </div>
                  
                  <div>
                    <div class="text-sm text-gray-500">Children</div>
                    <div class="font-medium" id="summary-children-count">0</div>
                  </div>
                </div>
                
                <div class="mt-4">
                  <div class="text-sm text-gray-500">Selected Seats</div>
                  <div class="font-medium" id="summary-selected-seats">-</div>
                </div>
              </div>
            </div>
            
            <div class="mt-8 flex justify-between">
              <button type="button" class="prev-step px-6 py-2 bg-gray-200 text-gray-700 rounded-lg shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-50 transition-colors" data-prev="seat-selection-section">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Back
              </button>
              <button
                type="submit"
                name="book_flight"
                class="px-6 py-2 bg-green-600 text-white rounded-lg shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50 transition-colors"
                id="confirm-booking-btn">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                </svg>
                Confirm and Pay
              </button>
            </div>
          </div>
        </form>
      </div>
      
      <div class="text-center mt-8">
        <a
          href="flights.php"
          class="text-blue-600 hover:text-blue-800 text-sm transition-colors flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
          </svg>
          Back to Flight Search
        </a>
      </div>
    <?php else: ?>
      <div class="text-center p-12 bg-white rounded-xl shadow-md">
        <svg class="h-16 w-16 text-red-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-red-600 font-bold text-xl mb-2">Flight not found</p>
        <p class="text-gray-600 mb-8">We couldn't find the flight you're looking for. Please try searching again.</p>
        <a href="flights.php" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
          </svg>
          Return to Flight Search
        </a>
      </div>
    <?php endif; ?>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize step navigation
      const nextButtons = document.querySelectorAll('.next-step');
      const prevButtons = document.querySelectorAll('.prev-step');
      const steps = ['step1', 'step2', 'step3', 'step4'];
      const sections = ['flight-details-section', 'passenger-info-section', 'seat-selection-section', 'confirmation-section'];
      let currentStep = 0;
      
      // Counter variables
      const adultCount = document.getElementById('adult_count');
      const childrenCount = document.getElementById('children_count');
      const seatsToSelect = document.getElementById('seats-to-select');
      const increaseAdults = document.querySelector('.increase-adults');
      const decreaseAdults = document.querySelector('.decrease-adults');
      const increaseChildren = document.querySelector('.increase-children');
      const decreaseChildren = document.querySelector('.decrease-children');
      
      // Seat selection variables
      const cabinTabs = document.querySelectorAll('.cabin-tab');
      const seatCabins = document.querySelectorAll('.seat-cabin');
      const availableSeats = document.querySelectorAll('.seat.available');
      const selectedSeatsInputs = document.getElementById('selected-seats-inputs');
      const selectedSeatsChips = document.getElementById('selected-seats-chips');
      const noSeatsSelected = document.getElementById('no-seats-selected');
      
      // Summary elements
      const summaryPassengerName = document.getElementById('summary-passenger-name');
      const summaryPassengerEmail = document.getElementById('summary-passenger-email');
      const summaryPassengerPhone = document.getElementById('summary-passenger-phone');
      const summaryCabinClass = document.getElementById('summary-cabin-class');
      const summaryAdultCount = document.getElementById('summary-adult-count');
      const summaryChildrenCount = document.getElementById('summary-children-count');
      const summarySelectedSeats = document.getElementById('summary-selected-seats');
      
      // Form elements
      const passengerNameInput = document.getElementById('passenger_name');
      const passengerEmailInput = document.getElementById('passenger_email');
      const passengerPhoneInput = document.getElementById('passenger_phone');
      const cabinClassSelect = document.getElementById('cabin_class');
      
      let selectedSeats = [];
      
      // Function to show a specific step
      function showStep(stepIndex) {
        currentStep = stepIndex;
        
        // Update step indicators
        steps.forEach((step, index) => {
          const stepElement = document.getElementById(step);
          if (index < stepIndex) {
            stepElement.classList.remove('active');
            stepElement.classList.add('completed');
          } else if (index === stepIndex) {
            stepElement.classList.add('active');
            stepElement.classList.remove('completed');
          } else {
            stepElement.classList.remove('active', 'completed');
          }
        });
        
        // Show current section, hide others
        sections.forEach((section, index) => {
          const sectionElement = document.getElementById(section);
          if (index === stepIndex) {
            sectionElement.classList.remove('hidden');
          } else {
            sectionElement.classList.add('hidden');
          }
        });
        
        // Scroll to top
        window.scrollTo({top: 0, behavior: 'smooth'});
        
        // If we're on the confirmation step, update the summary
        if (stepIndex === 3) {
          updateSummary();
        }
      }
      
      // Navigate to next step
      nextButtons.forEach(button => {
        button.addEventListener('click', function() {
          const nextSection = this.dataset.next;
          const nextIndex = sections.indexOf(nextSection);
          
          // Validate before proceeding
          if (nextIndex === 2) { // Before seat selection
            if (!validatePassengerInfo()) {
              return;
            }
          } else if (nextIndex === 3) { // Before confirmation
            if (!validateSeatSelection()) {
              return;
            }
          }
          
          showStep(nextIndex);
        });
      });
      
      // Navigate to previous step
      prevButtons.forEach(button => {
        button.addEventListener('click', function() {
          const prevSection = this.dataset.prev;
          const prevIndex = sections.indexOf(prevSection);
          showStep(prevIndex);
        });
      });
      
      // Update seats to select
      function updateSeatsToSelect() {
        const total = parseInt(adultCount.value) + parseInt(childrenCount.value);
        seatsToSelect.textContent = total;
      }
      
      // Handle counter buttons
      if (increaseAdults) {
        increaseAdults.addEventListener('click', function() {
          const current = parseInt(adultCount.value);
          if (current < 8) {
            adultCount.value = current + 1;
            updateSeatsToSelect();
          }
        });
      }
      
      if (decreaseAdults) {
        decreaseAdults.addEventListener('click', function() {
          const current = parseInt(adultCount.value);
          if (current > 1) {
            adultCount.value = current - 1;
            updateSeatsToSelect();
          }
        });
      }
      
      if (increaseChildren) {
        increaseChildren.addEventListener('click', function() {
          const current = parseInt(childrenCount.value);
          if (current < 8) {
            childrenCount.value = current + 1;
            updateSeatsToSelect();
          }
        });
      }
      
      if (decreaseChildren) {
        decreaseChildren.addEventListener('click', function() {
          const current = parseInt(childrenCount.value);
          if (current > 0) {
            childrenCount.value = current - 1;
            updateSeatsToSelect();
          }
        });
      }
      
      // Switch cabin tabs
      cabinTabs.forEach(tab => {
        tab.addEventListener('click', function() {
          const cabinClass = this.dataset.cabin;
          
          // Update tab active state
          cabinTabs.forEach(t => t.classList.remove('active'));
          this.classList.add('active');
          
          // Show corresponding cabin
          seatCabins.forEach(cabin => {
            if (cabin.dataset.cabin === cabinClass) {
              cabin.classList.remove('hidden');
            } else {
              cabin.classList.add('hidden');
            }
          });
        });
      });
      
      // Handle seat selection
      availableSeats.forEach(seat => {
        seat.addEventListener('click', function() {
          const seatId = this.dataset.seatId;
          const cabinClass = this.dataset.cabinClass;
          const requiredSeats = parseInt(adultCount.value) + parseInt(childrenCount.value);
          
          if (this.classList.contains('selected')) {
            // Deselect seat
            this.classList.remove('selected');
            selectedSeats = selectedSeats.filter(id => id !== seatId);
          } else {
            // Check if we already have enough seats
            if (selectedSeats.length >= requiredSeats) {
              // Remove the first selected seat
              const firstSelected = document.querySelector(`.seat[data-seat-id="${selectedSeats[0]}"]`);
              if (firstSelected) {
                firstSelected.classList.remove('selected');
              }
              selectedSeats.shift();
            }
            
            // Select the new seat
            this.classList.add('selected');
            selectedSeats.push(seatId);
          }
          
          // Update selected seats UI
          updateSelectedSeatsUI();
        });
      });
      
      // Update selected seats UI
      function updateSelectedSeatsUI() {
        // Update hidden inputs for form submission
        selectedSeatsInputs.innerHTML = '';
        selectedSeats.forEach(id => {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'selected_seats[]';
          input.value = id;
          selectedSeatsInputs.appendChild(input);
        });
        
        // Update visible chips
        selectedSeatsChips.innerHTML = '';
        if (selectedSeats.length > 0) {
          noSeatsSelected.style.display = 'none';
          selectedSeats.forEach(id => {
            const chip = document.createElement('div');
            chip.className = 'px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium flex items-center';
            chip.innerHTML = `
              ${id}
              <button type="button" class="ml-1 text-blue-500 hover:text-blue-700 focus:outline-none" data-seat-id="${id}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
              </button>
            `;
            selectedSeatsChips.appendChild(chip);
            
            // Add click event to remove button
            const removeButton = chip.querySelector('button');
            removeButton.addEventListener('click', function() {
              const seatId = this.dataset.seatId;
              const seat = document.querySelector(`.seat[data-seat-id="${seatId}"]`);
              if (seat) {
                seat.classList.remove('selected');
              }
              selectedSeats = selectedSeats.filter(id => id !== seatId);
              updateSelectedSeatsUI();
            });
          });
        } else {
          noSeatsSelected.style.display = 'block';
          selectedSeatsChips.appendChild(noSeatsSelected);
        }
      }
      
      // Form validation functions
      function validatePassengerInfo() {
        if (!passengerNameInput.value) {
          alert('Please enter passenger name');
          passengerNameInput.focus();
          return false;
        }
        
        if (!passengerEmailInput.value) {
          alert('Please enter passenger email');
          passengerEmailInput.focus();
          return false;
        }
        
        if (!passengerPhoneInput.value) {
          alert('Please enter passenger phone');
          passengerPhoneInput.focus();
          return false;
        }
        
        return true;
      }
      
      function validateSeatSelection() {
        const requiredSeats = parseInt(adultCount.value) + parseInt(childrenCount.value);
        
        if (selectedSeats.length !== requiredSeats) {
          alert(`Please select exactly ${requiredSeats} seat(s) for your passengers.`);
          return false;
        }
        
        return true;
      }
      
      // Update summary
      function updateSummary() {
        summaryPassengerName.textContent = passengerNameInput.value || '-';
        summaryPassengerEmail.textContent = passengerEmailInput.value || '-';
        summaryPassengerPhone.textContent = passengerPhoneInput.value || '-';
        
        const cabinClassText = cabinClassSelect.options[cabinClassSelect.selectedIndex].text;
        summaryCabinClass.textContent = cabinClassText || '-';
        
        summaryAdultCount.textContent = adultCount.value || '0';
        summaryChildrenCount.textContent = childrenCount.value || '0';
        
        if (selectedSeats.length > 0) {
          summarySelectedSeats.textContent = selectedSeats.join(', ');
        } else {
          summarySelectedSeats.textContent = '-';
        }
      }
      
      // Form validation
      document.getElementById('booking-form').addEventListener('submit', function(e) {
        if (!validatePassengerInfo() || !validateSeatSelection()) {
          e.preventDefault();
          return false;
        }
        
        return true;
      });
      
      // Initialize Select2 for cabin class
      if ($.fn.select2) {
        $(document).ready(function() {
          $('#cabin_class').select2({
            placeholder: 'Select cabin class',
            width: '100%'
          });
        });
      }
      
      // Initialize tooltips (if supported)
      if (typeof tippy !== 'undefined') {
        tippy('[data-tippy-content]');
      }
    });
  </script>
</body>

</html>