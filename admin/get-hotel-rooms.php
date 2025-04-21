<?php
session_name("admin_session");
session_start();
include 'connection/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Not authorized']);
  exit();
}

// Get hotel_id from request
if (!isset($_GET['hotel_id']) || empty($_GET['hotel_id'])) {
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Hotel ID is required']);
  exit();
}

$hotel_id = $_GET['hotel_id'];

// Get hotel by ID
function getHotelById($hotel_id) {
  global $conn;
  $sql = "SELECT * FROM hotels WHERE id = ?";