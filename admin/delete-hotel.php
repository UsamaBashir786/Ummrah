<?php
require_once 'includes/db-connection.php';

header('Content-Type: application/json');

try {
  // Verify request method
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new Exception('Invalid request method');
  }

  // Get and validate input
  $data = json_decode(file_get_contents('php://input'), true);
  $hotelId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

  if (!$hotelId) {
    throw new Exception('Valid hotel ID is required');
  }

  // Begin transaction
  $pdo->beginTransaction();

  // First delete associated images
  $stmt = $pdo->prepare("DELETE FROM hotel_images WHERE hotel_id = ?");
  $stmt->execute([$hotelId]);

  // Then delete the hotel
  $stmt = $pdo->prepare("DELETE FROM hotels WHERE id = ?");
  $stmt->execute([$hotelId]);

  // Commit transaction
  $pdo->commit();

  echo json_encode([
    'success' => true,
    'message' => 'Hotel deleted successfully'
  ]);
} catch (Exception $e) {
  // Rollback on error
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }

  echo json_encode([
    'success' => false,
    'error' => $e->getMessage()
  ]);
}
