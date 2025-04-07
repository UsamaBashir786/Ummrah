<?php
session_name("admin_session");
session_start();
include 'connection/connection.php';

if (!isset($_SESSION['admin_email'])) {
  header("Location: admin/login.php");
  exit();
}

if (isset($_GET['id'])) {
  $flight_id = $_GET['id'];

  $query = "DELETE FROM flights WHERE id = ?";
  $stmt = mysqli_prepare($conn, $query);
  mysqli_stmt_bind_param($stmt, "i", $flight_id);

  if (mysqli_stmt_execute($stmt)) {
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Deleted!',
                    text: 'Flight has been successfully deleted.',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = 'view-flight.php';
                });
            });
        </script>";
  } else {
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to delete flight. Try again later.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = 'view-flight.php';
                });
            });
        </script>";
  }
  mysqli_stmt_close($stmt);
} else {
  header("Location: view-flight.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
</body>

</html>