<?php
session_start();
require_once 'connection/connection.php';

if (!isset($_SESSION['admin_email'])) {
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
  $userId = $_GET['id'];

  $stmt = $conn->prepare("DELETE FROM user WHERE id = ?");
  $stmt->bind_param("i", $userId);

  if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
  } else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error']);
  }

  $stmt->close();
  $conn->close();
}
