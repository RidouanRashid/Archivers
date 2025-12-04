<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
$lang = $_SESSION['lang'] ?? 'nl';

$host = '127.0.0.1';
$user = 'root'; 
$pass = '';     
$db   = 'hua2';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connect failed', 'detail' => $conn->connect_error]);
    exit;
}

$sql = "SELECT id, naam, beschrijving, x_coord, y_coord, 
          beschrijving_english
      FROM hotspots";
$result = $conn->query($sql);
if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed', 'detail' => $conn->error]);
    exit;
}

$rows = [];
while ($row = $result->fetch_assoc()) {
    $row['x_coord'] = isset($row['x_coord']) ? (float)$row['x_coord'] : 0.0;
    $row['y_coord'] = isset($row['y_coord']) ? (float)$row['y_coord'] : 0.0;
    if ($lang === 'en') {
        $eng = $row['beschrijving_english'] ?? '';
        if (is_string($eng) && $eng !== '') {
            $row['beschrijving'] = $eng;
        }
    }
    unset($row['beschrijving_english']);
    $rows[] = $row;
}

$conn->close();

echo json_encode(['hotspots' => $rows]);
