<?php

/**
 * Function to check if a hotel is already booked for a package
 * Include this file where you need to display the hotel booking status for packages
 */

/**
 * Check if a hotel is already assigned to a package booking
 * 
 * @param PDO $pdo Database connection
 * @param int $package_booking_id The package booking ID to check
 * @return array|false Returns booking details if found, false otherwise
 */
function getPackageHotelBooking($pdo, $package_booking_id)
{
  // First check in package_assign table
  $stmt = $pdo->prepare("
        SELECT pa.*, h.hotel_name, h.location, h.rating, h.price_per_night
        FROM package_assign pa
        JOIN hotels h ON pa.hotel_id = h.id
        WHERE pa.booking_id = ? AND pa.hotel_id IS NOT NULL
    ");
  $stmt->execute([$package_booking_id]);
  $assignment = $stmt->fetch();

  if (!$assignment) {
    return false;
  }

  // Now get the details from hotel_bookings if available
  $stmt = $pdo->prepare("
        SELECT hb.*, h.hotel_name, h.location, h.rating
        FROM hotel_bookings hb
        JOIN hotels h ON hb.hotel_id = h.id
        WHERE hb.package_booking_id = ?
        AND hb.status != 'cancelled'
        LIMIT 1
    ");
  $stmt->execute([$package_booking_id]);
  $booking = $stmt->fetch();

  // If we have a full booking record, return it
  if ($booking) {
    return $booking;
  }

  // If we only have an assignment but no booking details yet, return what we have
  return $assignment;
}

/**
 * Display hotel booking status for a package
 * 
 * @param PDO $pdo Database connection
 * @param int $package_booking_id The package booking ID to check
 * @param string $user_type 'admin' or 'user' to determine display options
 * @return void Echoes the HTML directly
 */
function displayHotelBookingStatus($pdo, $package_booking_id, $user_type = 'user')
{
  $booking = getPackageHotelBooking($pdo, $package_booking_id);

  echo '<div class="hotel-booking-status">';

  if ($booking) {
    // Hotel is assigned, show details
    echo '<div class="flex items-center">';
    echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mr-2">';
    echo '<i class="fas fa-check-circle mr-1"></i> Booked';
    echo '</span>';

    // Show hotel details if we have them
    if (isset($booking['hotel_name'])) {
      echo '<span class="text-sm text-gray-600">' . htmlspecialchars($booking['hotel_name']) . '</span>';
    }

    echo '</div>';

    // Add more details if we have them
    if (isset($booking['check_in_date']) && isset($booking['check_out_date'])) {
      echo '<div class="text-xs text-gray-500 mt-1">';
      echo '<i class="far fa-calendar-alt mr-1"></i> ';
      echo date('M d, Y', strtotime($booking['check_in_date'])) . ' - ';
      echo date('M d, Y', strtotime($booking['check_out_date']));

      // Calculate nights
      $checkin = new DateTime($booking['check_in_date']);
      $checkout = new DateTime($booking['check_out_date']);
      $interval = $checkin->diff($checkout);
      echo ' (' . $interval->days . ' night' . ($interval->days > 1 ? 's' : '') . ')';

      echo '</div>';
    }

    // If this is for admin view, provide a link to edit/view the details
    if ($user_type === 'admin') {
      echo '<div class="mt-2">';
      if (isset($booking['id']) && isset($booking['check_in_date'])) {
        // We have a full booking, show view details link
        echo '<a href="view-hotel-booking.php?id=' . $booking['id'] . '" class="text-sm text-blue-600 hover:text-blue-800">';
        echo '<i class="fas fa-eye mr-1"></i> View Details';
        echo '</a>';
      } else {
        // We only have an assignment, show complete booking link
        echo '<a href="admin-book-hotel.php?booking_id=' . $package_booking_id . '&action=edit" class="text-sm text-orange-600 hover:text-orange-800">';
        echo '<i class="fas fa-edit mr-1"></i> Complete Booking Details';
        echo '</a>';
      }
      echo '</div>';
    }
  } else {
    // Hotel is not assigned yet
    echo '<div class="flex items-center">';
    echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mr-2">';
    echo '<i class="fas fa-times-circle mr-1"></i> Not Booked';
    echo '</span>';

    // If this is for admin view, provide a button to assign a hotel
    if ($user_type === 'admin') {
      echo '<a href="admin-book-hotel.php?tab=unassigned&booking_id=' . $package_booking_id . '#hotel-section" ';
      echo 'class="ml-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium py-1 px-2 rounded">';
      echo '<i class="fas fa-hotel mr-1"></i> Book';
      echo '</a>';
    }

    echo '</div>';
  }

  echo '</div>';
}
