<?php
session_name("admin_session");
session_start();
include 'connection/connection.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
  // Get the transport ID and sanitize it
  $transport_id = mysqli_real_escape_string($conn, $_GET['id']);

  // First verify the record exists
  $check_query = "SELECT transport_id FROM transportation WHERE transport_id = '$transport_id' LIMIT 1";
  $check_result = mysqli_query($conn, $check_query);

  if (mysqli_num_rows($check_result) > 0) {
    // Record exists, proceed with deletion
    $delete_query = "DELETE FROM transportation WHERE transport_id = '$transport_id' LIMIT 1";

    if (mysqli_query($conn, $delete_query)) {
      echo "<!DOCTYPE html><html><head>";
      echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
      echo "</head><body>";
      echo "<script>
                Swal.fire({
                    title: 'Deleted!',
                    text: 'Vehicle record deleted successfully.',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = 'view-transportation.php';
                });
            </script>";
      echo "</body></html>";
    } else {
      echo "<!DOCTYPE html><html><head>";
      echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
      echo "</head><body>";
      echo "<script>
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to delete the record: " . mysqli_error($conn) . "',
                    icon: 'error',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = 'view-transportation.php';
                });
            </script>";
      echo "</body></html>";
    }
  } else {
    echo "<!DOCTYPE html><html><head>";
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
    echo "</head><body>";
    echo "<script>
            Swal.fire({
                title: 'Not Found!',
                text: 'The vehicle record was not found.',
                icon: 'warning',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = 'view-transportation.php';
            });
        </script>";
    echo "</body></html>";
  }
} else {
  echo "<!DOCTYPE html><html><head>";
  echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
  echo "</head><body>";
  echo "<script>
        Swal.fire({
            title: 'Invalid Request!',
            text: 'No valid ID provided.',
            icon: 'warning',
            confirmButtonText: 'OK'
        }).then(() => {
            window.location.href = 'view-transportation.php';
        });
    </script>";
  echo "</body></html>";
}

mysqli_close($conn);
