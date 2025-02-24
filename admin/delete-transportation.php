<?php
session_start();
include 'connection/connection.php'; // Ensure database connection is established

if (isset($_GET['id'])) {
    $transport_id = intval($_GET['id']); // Ensure ID is an integer
    
    // Prepare a statement to prevent SQL injection
    $stmt = $conn->prepare("DELETE FROM transportation WHERE transport_id = ?");
    $stmt->bind_param("i", $transport_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        echo "<!DOCTYPE html><html><head>";
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "</head><body>";
        echo "<script>
            Swal.fire({
                title: 'Deleted!',
                text: 'Transportation record deleted successfully.',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = 'view-transportation.php';
            });
        </script>";
        echo "</body></html>";
        exit();
    } else {
        $stmt->close();
        $conn->close();
        echo "<!DOCTYPE html><html><head>";
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "</head><body>";
        echo "<script>
            Swal.fire({
                title: 'Error!',
                text: 'Error deleting record.',
                icon: 'error',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = 'view-transportation.php';
            });
        </script>";
        echo "</body></html>";
        exit();
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
    exit();
}
?>
