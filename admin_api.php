<?php
header('Content-Type: application/json');
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'hua2';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { http_response_code(500); echo json_encode(['error'=>'DB']); exit; }

$action = $_GET['action'] ?? '';

if ($action === 'save_order') {
  $payload = json_decode(file_get_contents('php://input'), true) ?? [];
  $conn->begin_transaction();
  try {
    foreach ($payload as $row) {
      $img = $row['img'] ?? '';
      $pos = (int)($row['position'] ?? 0);
      if (!$img || !$pos) continue;
      // Use explicit value for update to avoid deprecated VALUES() usage
      $stmt = $conn->prepare('INSERT INTO panorama_order (img, position) VALUES (?, ?) ON DUPLICATE KEY UPDATE position=?');
      $stmt->bind_param('sii', $img, $pos, $pos);
      $stmt->execute();
      $stmt->close();
    }
    $conn->commit();
    echo json_encode(['status'=>'ok']);
  } catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error'=>'save_failed']);
  }
  exit;
}

if ($action === 'save_hotspot') {
  $payload = json_decode(file_get_contents('php://input'), true) ?? [];
  $id = (int)($payload['id'] ?? 0);
  if ($id <= 0) { http_response_code(400); echo json_encode(['error'=>'missing_id']); exit; }

  $hasNaam = array_key_exists('naam', $payload);
  $hasBeschrijving = array_key_exists('beschrijving', $payload);
  $hasX = array_key_exists('x_coord', $payload);
  $hasY = array_key_exists('y_coord', $payload);

  if ($hasX && $hasY) {
    // Full update with coordinates (used by drag/save in admin mode on panorama)
    $naam = $payload['naam'] ?? '';
    $beschrijving = $payload['beschrijving'] ?? '';
    $x = (float)$payload['x_coord'];
    $y = (float)$payload['y_coord'];
    $stmt = $conn->prepare('UPDATE hotspots SET naam=?, beschrijving=?, x_coord=?, y_coord=? WHERE id=?');
    $stmt->bind_param('ssdsi', $naam, $beschrijving, $x, $y, $id);
    $ok = $stmt->execute();
    echo json_encode(['status'=>$ok ? 'ok':'err']);
    exit;
  }

  if ($hasNaam || $hasBeschrijving) {
    // Partial update (no coords): do not touch x/y
    if ($hasNaam && $hasBeschrijving) {
      $naam = $payload['naam'];
      $beschrijving = $payload['beschrijving'];
      $stmt = $conn->prepare('UPDATE hotspots SET naam=?, beschrijving=? WHERE id=?');
      $stmt->bind_param('ssi', $naam, $beschrijving, $id);
    } elseif ($hasNaam) {
      $naam = $payload['naam'];
      $stmt = $conn->prepare('UPDATE hotspots SET naam=? WHERE id=?');
      $stmt->bind_param('si', $naam, $id);
    } else { // only description
      $beschrijving = $payload['beschrijving'];
      $stmt = $conn->prepare('UPDATE hotspots SET beschrijving=? WHERE id=?');
      $stmt->bind_param('si', $beschrijving, $id);
    }
    $ok = $stmt->execute();
    echo json_encode(['status'=>$ok ? 'ok':'err']);
    exit;
  }

  http_response_code(400);
  echo json_encode(['error'=>'no_fields_to_update']);
  exit;
}

echo json_encode(['error'=>'unknown_action']);
