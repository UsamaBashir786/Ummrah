<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}

require_once '../connection/connection.php';

if (isset($_GET['package_id'])) {
  $package_id = $_GET['package_id'];
  $user_id = $_SESSION['user_id'];
  
  // Fetch detailed package booking information
  $sql = "
    SELECT 
      pb.id as booking_id,
      pb.package_id,
      pb.booking_date,
      pb.status as booking_status,
      pb.payment_status,
      pb.total_price,
      pb.payment_method,
      p.title as package_title,
      p.description,
      p.package_type,
      p.price,
      p.airline,
      p.flight_class,
      p.departure_city,
      p.departure_time,
      p.departure_date,
      p.arrival_city,
      p.return_time,
      p.return_date,
      p.inclusions,
      p.package_image,
      pa.hotel_id,
      pa.transport_id,
      pa.flight_id,
      pa.seat_type,
      pa.seat_number,
      pa.transport_seat_number,
      h.hotel_name,
      h.location as hotel_location,
      h.rating as hotel_rating,
      h.description as hotel_description,
      f.airline_name,
      f.flight_number,
      f.departure_time as flight_departure_time,
      f.departure_date as flight_departure_date,
      f.flight_duration,
      t.transport_name,
      t.category as transport_category,
      t.details as transport_details
    FROM package_booking pb 
    INNER JOIN packages p ON pb.package_id = p.id
    LEFT JOIN package_assign pa ON pb.id = pa.booking_id
    LEFT JOIN hotels h ON pa.hotel_id = h.id
    LEFT JOIN flights f ON pa.flight_id = f.id
    LEFT JOIN transportation t ON pa.transport_id = t.id
    WHERE pb.id = ? AND pb.user_id = ?
  ";
  
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $package_id, $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  
  if ($result->num_rows > 0) {
    $package = $result->fetch_assoc();
    
    // Fetch hotel images if hotel is assigned
    $hotel_images = [];
    if (!empty($package['hotel_id'])) {
      $img_sql = "SELECT image_path FROM hotel_images WHERE hotel_id = ? LIMIT 5";
      $img_stmt = $conn->prepare($img_sql);
      $img_stmt->bind_param("i", $package['hotel_id']);
      $img_stmt->execute();
      $img_result = $img_stmt->get_result();
      
      while ($img = $img_result->fetch_assoc()) {
        $hotel_images[] = $img['image_path'];
      }
    }
?>
<div class="bg-white rounded-lg p-6">
  <div class="border-b pb-4 mb-4">
    <div class="flex justify-between items-start mb-3">
      <div>
        <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($package['package_title']); ?></h3>
        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($package['package_type']); ?> Package</p>
      </div>
      <div class="flex items-center">
        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
          <?php
          switch ($package['booking_status']) {
            case 'pending':
              echo 'bg-yellow-100 text-yellow-800';
              break;
            case 'confirmed':
              echo 'bg-blue-100 text-blue-800';
              break;
            case 'canceled':
              echo 'bg-red-100 text-red-800';
              break;
          }
          ?>">
          <?php echo ucfirst($package['booking_status']); ?>
        </span>
      </div>
    </div>
    
    <div class="mb-3">
      <p class="text-sm text-gray-600"><?php echo nl2br(htmlspecialchars($package['description'])); ?></p>
    </div>
    
    <div class="flex flex-wrap gap-2 mb-2">
      <div class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-xs">
        <span class="font-medium">Booking ID:</span> <?php echo $package['booking_id']; ?>
      </div>
      <div class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">
        <span class="font-medium">Booked:</span> <?php echo date('M d, Y', strtotime($package['booking_date'])); ?>
      </div>
      <div class="px-3 py-1 <?php echo $package['payment_status'] == 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?> rounded-full text-xs">
        <span class="font-medium">Payment:</span> <?php echo ucfirst($package['payment_status']); ?>
      </div>
    </div>
  </div>
  
  <!-- Price and flight information section -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
    <div class="border rounded-lg p-4">
      <h4 class="font-bold text-gray-700 mb-3">Price & Payment</h4>
      <div class="space-y-2">
        <div class="flex justify-between">
          <span class="text-gray-600">Package Price:</span>
          <span class="font-medium text-green-700">$<?php echo number_format($package['price'], 2); ?></span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Total Paid:</span>
          <span class="font-medium <?php echo $package['payment_status'] == 'paid' ? 'text-green-700' : 'text-yellow-600'; ?>">
            $<?php echo $package['payment_status'] == 'paid' ? number_format($package['total_price'], 2) : '0.00'; ?>
          </span>
        </div>
        <?php if (!empty($package['payment_method'])): ?>
        <div class="flex justify-between">
          <span class="text-gray-600">Payment Method:</span>
          <span class="font-medium"><?php echo htmlspecialchars($package['payment_method']); ?></span>
        </div>
        <?php endif; ?>
      </div>
    </div>
    
    <div class="border rounded-lg p-4">
      <h4 class="font-bold text-gray-700 mb-3">Travel Details</h4>
      <div class="space-y-2">
        <div class="flex justify-between">
          <span class="text-gray-600">Airline:</span>
          <span class="font-medium"><?php echo htmlspecialchars($package['airline']); ?></span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Class:</span>
          <span class="font-medium"><?php echo htmlspecialchars($package['flight_class']); ?></span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Departure:</span>
          <span class="font-medium">
            <?php 
              echo htmlspecialchars(date('M d, Y', strtotime($package['departure_date'])));
              if (!empty($package['departure_time'])) {
                echo ' at ' . htmlspecialchars(date('g:i A', strtotime($package['departure_time'])));
              }
            ?>
          </span>
        </div>
        <?php if (!empty($package['return_date'])): ?>
        <div class="flex justify-between">
          <span class="text-gray-600">Return:</span>
          <span class="font-medium">
            <?php 
              echo htmlspecialchars(date('M d, Y', strtotime($package['return_date'])));
              if (!empty($package['return_time'])) {
                echo ' at ' . htmlspecialchars(date('g:i A', strtotime($package['return_time'])));
              }
            ?>
          </span>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <!-- Inclusions section -->
  <?php if (!empty($package['inclusions'])): ?>
  <div class="border rounded-lg p-4 mb-4">
    <h4 class="font-bold text-gray-700 mb-3">Package Inclusions</h4>
    <?php 
      $inclusions = explode(',', $package['inclusions']);
      echo '<ul class="list-disc list-inside space-y-1 text-gray-600">';
      foreach ($inclusions as $inclusion) {
        echo '<li>' . htmlspecialchars(trim($inclusion)) . '</li>';
      }
      echo '</ul>';
    ?>
  </div>
  <?php endif; ?>
  
  <!-- Assigned services section -->
  <div class="border rounded-lg p-4 mb-4">
    <h4 class="font-bold text-gray-700 mb-3">Assigned Services</h4>
    
    <?php if (empty($package['hotel_id']) && empty($package['flight_id']) && empty($package['transport_id'])): ?>
      <p class="text-orange-500 text-sm">No services have been assigned to this package yet.</p>
    <?php else: ?>
    
      <!-- Flight assignment -->
      <?php if (!empty($package['flight_id'])): ?>
      <div class="mb-4">
        <div class="flex items-center mb-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
          </svg>
          <h5 class="font-medium text-gray-800">Flight Assignment</h5>
        </div>
        <div class="ml-7 space-y-1 text-sm text-gray-600">
          <?php if (!empty($package['airline_name'])): ?>
            <p><span class="font-medium">Airline:</span> <?php echo htmlspecialchars($package['airline_name']); ?></p>
          <?php endif; ?>
          
          <?php if (!empty($package['flight_number'])): ?>
            <p><span class="font-medium">Flight Number:</span> <?php echo htmlspecialchars($package['flight_number']); ?></p>
          <?php endif; ?>
          
          <?php if (!empty($package['flight_departure_date']) && !empty($package['flight_departure_time'])): ?>
            <p><span class="font-medium">Departure:</span> <?php echo htmlspecialchars(date('M d, Y g:i A', strtotime($package['flight_departure_date'] . ' ' . $package['flight_departure_time']))); ?></p>
          <?php endif; ?>
          
          <?php if (!empty($package['flight_duration'])): ?>
            <p><span class="font-medium">Duration:</span> <?php echo htmlspecialchars($package['flight_duration']); ?></p>
          <?php endif; ?>
          
          <?php if (!empty($package['seat_type']) || !empty($package['seat_number'])): ?>
            <p>
              <span class="font-medium">Seat:</span> 
              <?php
                if (!empty($package['seat_type'])) echo htmlspecialchars(ucfirst($package['seat_type']));
                if (!empty($package['seat_number'])) echo ' - ' . htmlspecialchars($package['seat_number']);
              ?>
            </p>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- Hotel assignment -->
      <?php if (!empty($package['hotel_id'])): ?>
      <div class="mb-4">
        <div class="flex items-center mb-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          <h5 class="font-medium text-gray-800">Hotel Assignment</h5>
        </div>
        <div class="ml-7 space-y-1 text-sm text-gray-600">
          <p><span class="font-medium">Hotel:</span> <?php echo htmlspecialchars($package['hotel_name']); ?></p>
          
          <?php if (!empty($package['hotel_location'])): ?>
            <p><span class="font-medium">Location:</span> <?php echo htmlspecialchars(ucfirst($package['hotel_location'])); ?></p>
          <?php endif; ?>
          
          <?php if (!empty($package['hotel_rating'])): ?>
            <p>
              <span class="font-medium">Rating:</span> 
              <?php
                $rating = (int)$package['hotel_rating'];
                for ($i = 0; $i < $rating; $i++) {
                  echo '<span class="text-yellow-400">★</span>';
                }
                for ($i = $rating; $i < 5; $i++) {
                  echo '<span class="text-gray-300">★</span>';
                }
              ?>
            </p>
          <?php endif; ?>
          
          <?php if (!empty($package['hotel_description'])): ?>
            <p class="mt-2 text-xs text-gray-500"><?php echo nl2br(htmlspecialchars($package['hotel_description'])); ?></p>
          <?php endif; ?>
          
          <?php if (!empty($hotel_images)): ?>
            <div class="mt-3 grid grid-cols-3 gap-2">
              <?php foreach ($hotel_images as $image): ?>
                <div class="rounded overflow-hidden h-20 bg-gray-100">
                  <img src="<?php echo htmlspecialchars('../' . $image); ?>" alt="Hotel Image" class="w-full h-full object-cover">
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- Transportation assignment -->
      <?php if (!empty($package['transport_id'])): ?>
      <div class="mb-4">
        <div class="flex items-center mb-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
          </svg>
          <h5 class="font-medium text-gray-800">Transportation Assignment</h5>
        </div>
        <div class="ml-7 space-y-1 text-sm text-gray-600">
          <?php if (!empty($package['transport_name'])): ?>
            <p><span class="font-medium">Transport:</span> <?php echo htmlspecialchars($package['transport_name']); ?></p>
          <?php endif; ?>
          
          <?php if (!empty($package['transport_category'])): ?>
            <p><span class="font-medium">Category:</span> <?php echo htmlspecialchars($package['transport_category']); ?></p>
          <?php endif; ?>
          
          <?php if (!empty($package['transport_seat_number'])): ?>
            <p><span class="font-medium">Seat Number:</span> <?php echo htmlspecialchars($package['transport_seat_number']); ?></p>
          <?php endif; ?>
          
          <?php if (!empty($package['transport_details'])): ?>
            <p class="mt-2 text-xs text-gray-500"><?php echo nl2br(htmlspecialchars($package['transport_details'])); ?></p>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  
  <!-- Package image if available -->
  <?php if (!empty($package['package_image']) && file_exists('../' . $package['package_image'])): ?>
  <div class="mt-4">
    <h4 class="font-bold text-gray-700 mb-3">Package Image</h4>
    <div class="rounded-lg overflow-hidden max-h-96">
      <img src="<?php echo htmlspecialchars('../' . $package['package_image']); ?>" alt="Package Image" class="w-full object-cover">
    </div>
  </div>
  <?php endif; ?>
  
  <div class="mt-6 flex justify-end">
    <button onclick="closeModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg mr-2">
      Close
    </button>
    <?php if ($package['booking_status'] != 'canceled'): ?>
    <!-- <a href="view-package.php?id=<?php echo $package['package_id']; ?>" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg">
      View Package Details
    </a> -->
    <?php endif; ?>
  </div>
</div>
<?php
  } else {
    // No package found or not authorized
    echo '<div class="bg-red-100 p-4 rounded-lg text-red-700">';
    echo '<p>Package details could not be found or you are not authorized to view this booking.</p>';
    echo '</div>';
  }
} else {
  // No package ID provided
  echo '<div class="bg-red-100 p-4 rounded-lg text-red-700">';
  echo '<p>No package booking specified.</p>';
  echo '</div>';
}
?>