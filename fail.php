<?php
include "connection/connection.php";
session_start();
echo "<script>`
  alert('Payment was canceled. Your booking is still pending.');
  window.location.href = 'index.php';
</script>";
?>
