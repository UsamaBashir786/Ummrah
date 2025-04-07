<?php
session_name("admin_session");
session_start();
include 'connection/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
  header("Location: admin-login.php");
  exit();
}

// Check if booking ID and reference are provided
if (!isset($_GET['id']) || !isset($_GET['reference'])) {
  header("Location: transportation-bookings.php");
  exit();
}

$booking_id = $_GET['id'];
$booking_reference = $_GET['reference'];

// Get booking details
$booking_query = "SELECT tb.*, u.full_name, u.email, u.phone_number 
                 FROM transportation_bookings tb
                 JOIN users u ON tb.user_id = u.id
                 WHERE tb.id = ? AND tb.booking_reference = ?";
$stmt = $conn->prepare($booking_query);
$stmt->bind_param("is", $booking_id, $booking_reference);
$stmt->execute();
$booking_result = $stmt->get_result();

if ($booking_result->num_rows === 0) {
  // Booking not found
  header("Location: transportation-bookings.php");
  exit();
}

$booking = $booking_result->fetch_assoc();

// Format dates for display
$booking_date = date('F j, Y', strtotime($booking['booking_date']));
$booking_time = date('h:i A', strtotime($booking['booking_time']));
$created_date = date('F j, Y h:i A', strtotime($booking['created_at']));
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Booking #<?php echo $booking_reference; ?> - Print</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      line-height: 1.6;
      color: #333;
      margin: 0;
      padding: 0;
      background-color: #f9fafb;
    }
    .container {
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
      background-color: white;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .header {
      text-align: center;
      padding-bottom: 20px;
      border-bottom: 1px solid #e5e7eb;
      margin-bottom: 20px;
    }
    .logo {
      max-width: 150px;
      margin-bottom: 10px;
    }
    .booking-id {
      font-size: 1.2rem;
      color: #4b5563;
    }
    .status {
      display: inline-block;
      padding: 0.25rem 0.5rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: capitalize;
      margin-top: 5px;
    }
    .status-pending {
      background-color: #FEF3C7;
      color: #92400E;
    }
    .status-confirmed {
      background-color: #D1FAE5;
      color: #065F46;
    }
    .status-completed {
      background-color: #DBEAFE;
      color: #1E40AF;
    }
    .status-cancelled {
      background-color: #FEE2E2;
      color: #991B1B;
    }
    .panel {
      border: 1px solid #e5e7eb;
      border-radius: 0.375rem;
      margin-bottom: 20px;
    }
    .panel-header {
      background-color: #f9fafb;
      padding: 10px 15px;
      border-bottom: 1px solid #e5e7eb;
      font-weight: 600;
    }
    .panel-body {
      padding: 15px;
    }
    .grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
    }
    .field {
      margin-bottom: 10px;
    }
    .field-label {
      font-size: 0.875rem;
      color: #6b7280;
      margin-bottom: 2px;
    }
    .field-value {
      font-weight: 500;
    }
    .price {
      font-size: 1.25rem;
      font-weight: 600;
      color: #047857;
    }
    .footer {
      text-align: center;
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid #e5e7eb;
      font-size: 0.875rem;
      color: #6b7280;
    }
    .notes {
      font-style: italic;
      color: #6b7280;
      padding: 10px;
      background-color: #f9fafb;
      border-radius: 0.375rem;
      margin-top: 10px;
    }
    .print-button {
      background-color: #0f766e;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      font-size: 0.875rem;
    }
    .print-button:hover {
      background-color: #115e59;
    }
    .print-button i {
      margin-right: 6px;
    }
    .print-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    .print-title {
      font-size: 1.5rem;
      font-weight: bold;
    }
    @media print {
      .no-print {
        display: none;
      }
      body {
        background-color: white;
      }
      .container {
        box-shadow: none;
        padding: 0;
      }
      .panel {
        break-inside: avoid;
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="print-header no-print">
      <h1 class="print-title">Booking Details</h1>
      <button onclick="window.print()" class="print-button">
        <i class="fas fa-print"></i> Print
      </button>
    </div>

    <div class="header">
      <h2>Transportation Booking</h2>
      <div class="booking-id">Reference: <?php echo $booking_reference; ?></div>
      <div>
        <span class="status 
          <?php 
            switch ($booking['booking_status']) {
              case 'pending':
                echo 'status-pending';
                break;
              case 'confirmed':
                echo 'status-confirmed';
                break;
              case 'completed':
                echo 'status-completed';
                break;
              case 'cancelled':
                echo 'status-cancelled';
                break;
            }
          ?>">
          <?php echo ucfirst($booking['booking_status']); ?>
        </span>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header">Booking Information</div>
      <div class="panel-body">
        <div class="grid">
          <div>
            <div class="field">
              <div class="field-label">Service Type</div>
              <div class="field-value"><?php echo ucfirst($booking['service_type']); ?> Service</div>
            </div>
            
            <div class="field">
              <div class="field-label">Route</div>
              <div class="field-value"><?php echo htmlspecialchars($booking['route_name'] ?? 'N/A'); ?></div>
            </div>
            
            <div class="field">
              <div class="field-label">Vehicle</div>
              <div class="field-value"><?php echo htmlspecialchars($booking['vehicle_name'] ?? 'N/A'); ?></div>
            </div>
            
            <div class="field">
              <div class="field-label">Price</div>
              <div class="field-value price"><?php echo $booking['price']; ?> SR</div>
            </div>
          </div>
          
          <div>
            <div class="field">
              <div class="field-label">Booking Date</div>
              <div class="field-value"><?php echo $booking_date; ?></div>
            </div>
            
            <div class="field">
              <div class="field-label">Pickup Time</div>
              <div class="field-value"><?php echo $booking_time; ?></div>
            </div>
            
            <div class="field">
              <div class="field-label">Passengers</div>
              <div class="field-value"><?php echo $booking['passengers']; ?> person(s)</div>
            </div>
            
            <div class="field">
              <div class="field-label">Payment Status</div>
              <div class="field-value"><?php echo ucfirst($booking['payment_status']); ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header">Trip Details</div>
      <div class="panel-body">
        <div class="grid">
          <div>
            <div class="field">
              <div class="field-label">Pickup Location</div>
              <div class="field-value"><?php echo htmlspecialchars($booking['pickup_location']); ?></div>
            </div>
          </div>
          
          <div>
            <div class="field">
              <div class="field-label">Drop-off Location</div>
              <div class="field-value"><?php echo htmlspecialchars($booking['dropoff_location']); ?></div>
            </div>
          </div>
        </div>
        
        <?php if (!empty($booking['special_requests'])): ?>
          <div class="field" style="margin-top: 15px;">
            <div class="field-label">Special Requests</div>
            <div class="notes"><?php echo htmlspecialchars($booking['special_requests']); ?></div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header">Customer Information</div>
      <div class="panel-body">
        <div class="grid">
          <div>
            <div class="field">
              <div class="field-label">Name</div>
              <div class="field-value"><?php echo htmlspecialchars($booking['full_name']); ?></div>
            </div>
            
            <div class="field">
              <div class="field-label">Email</div>
              <div class="field-value"><?php echo htmlspecialchars($booking['email']); ?></div>
            </div>
          </div>
          
          <div>
            <?php if (!empty($booking['phone_number'])): ?>
              <div class="field">
                <div class="field-label">Phone</div>
                <div class="field-value"><?php echo htmlspecialchars($booking['phone_number']); ?></div>
              </div>
            <?php endif; ?>
            
            <div class="field">
              <div class="field-label">Booking Created</div>
              <div class="field-value"><?php echo $created_date; ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php if (!empty($booking['admin_notes'])): ?>
      <div class="panel">
        <div class="panel-header">Admin Notes</div>
        <div class="panel-body">
          <div class="notes"><?php echo htmlspecialchars($booking['admin_notes']); ?></div>
        </div>
      </div>
    <?php endif; ?>

    <div class="footer">
      <p>This is a computer-generated document and does not require a signature.</p>
      <p>For any queries regarding this booking, please contact our customer service.</p>
    </div>
  </div>

  <script>
    // Auto-print when page loads
    window.onload = function() {
      // Wait a moment to ensure everything is loaded
      setTimeout(function() {
        window.print();
      }, 500);
    };
  </script>
</body>
</html>