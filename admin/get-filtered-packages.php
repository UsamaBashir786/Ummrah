<?php
include 'includes/db-config.php';

$conditions = [];
$params = [];

if (!empty($_GET['package_type'])) {
    $conditions[] = "package_type = :package_type";
    $params[':package_type'] = $_GET['package_type'];
}

if (!empty($_GET['min_price'])) {
    $conditions[] = "price >= :min_price";
    $params[':min_price'] = $_GET['min_price'];
}

if (!empty($_GET['max_price'])) {
    $conditions[] = "price <= :max_price";
    $params[':max_price'] = $_GET['max_price'];
}

$sql = "SELECT * FROM packages";
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($packages);
