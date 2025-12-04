<?php
// Simple JSON endpoint to return hotspots from MySQL
header('Content-Type: application/json');

$host = '127.0.0.1';
$user = 'root'; // adjust if needed
$pass = '';     // adjust if needed
$db   = 'hua2';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connect failed', 'detail' => $conn->connect_error]);
    exit;
}

$sql = "SELECT id, naam, beschrijving, x_coord, y_coord FROM hotspots";
$result = $conn->query($sql);
if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed', 'detail' => $conn->error]);
    exit;
}

$rows = [];
while ($row = $result->fetch_assoc()) {
    // ensure numeric types
    $row['x_coord'] = isset($row['x_coord']) ? (float)$row['x_coord'] : 0.0;
    $row['y_coord'] = isset($row['y_coord']) ? (float)$row['y_coord'] : 0.0;
    $rows[] = $row;
}

$conn->close();

echo json_encode(['hotspots' => $rows]);
