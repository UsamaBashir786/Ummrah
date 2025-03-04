<?php
include 'includes/db-config.php';

$id = $_GET['id'];
$response = ['success' => false];

try {
    $stmt = $pdo->prepare("DELETE FROM packages WHERE id = ?");
    $result = $stmt->execute([$id]);
    $response['success'] = $result;
} catch (PDOException $e) {
    $response['error'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
