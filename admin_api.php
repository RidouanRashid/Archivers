<?php
// Admin API: JSON endpoints voor panorama afbeeldingen en hotspot beheer
// Acties: save_order, save_hotspot, create_hotspot, add_image, delete_image, delete_hotspot
header('Content-Type: application/json');
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'hua2';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { http_response_code(500); echo json_encode(['error'=>'DB']); exit; }

$action = $_GET['action'] ?? '';

// Sla de volgorde van panorama afbeeldingen op in de database
// Body: array van { img: string (pad/URL), position: number (1, 2, 3...) }
// Gebruikt transactie voor atomaire update van alle posities
if ($action === 'save_order') {
  $payload = json_decode(file_get_contents('php://input'), true) ?? [];
  $conn->begin_transaction();
  try {
    foreach ($payload as $row) {
      $img = $row['img'] ?? '';
      $pos = (int)($row['position'] ?? 0);
      if (!$img || !$pos) continue;
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

// Werk hotspot gegevens bij (naam, beschrijving, coördinaten)
// Body: { id: number (verplicht), naam?: string, beschrijving?: string, x_coord?: float, y_coord?: float }
// Ondersteunt partiële updates - alleen opgegeven velden worden bijgewerkt
if ($action === 'save_hotspot') {
  $payload = json_decode(file_get_contents('php://input'), true) ?? [];
  $id = (int)($payload['id'] ?? 0);
  if ($id <= 0) { http_response_code(400); echo json_encode(['error'=>'missing_id']); exit; }

  $hasNaam = array_key_exists('naam', $payload);
  $hasBeschrijving = array_key_exists('beschrijving', $payload);
  $hasX = array_key_exists('x_coord', $payload);
  $hasY = array_key_exists('y_coord', $payload);

  if ($hasX && $hasY) {
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

// Maak een nieuwe hotspot aan met Nederlandse en Engelse beschrijving
// Body: { naam: string (verplicht), beschrijving: string, beschrijving_english: string, x_coord?: float, y_coord?: float }
// Retourneert nieuwe hotspot ID als beschrijving correct wordt opgeslagen
if ($action === 'create_hotspot') {
  $payload = json_decode(file_get_contents('php://input'), true) ?? [];
  $naam = trim($payload['naam'] ?? '');
  $beschrijving = trim($payload['beschrijving'] ?? '');
  $beschrijving_en = trim($payload['beschrijving_english'] ?? '');
  $x = isset($payload['x_coord']) ? (float)$payload['x_coord'] : 0.0;
  $y = isset($payload['y_coord']) ? (float)$payload['y_coord'] : 0.0;
  if ($naam === '') { http_response_code(400); echo json_encode(['error'=>'missing_name']); exit; }
  $stmt = $conn->prepare('INSERT INTO hotspots (naam, beschrijving, beschrijving_english, x_coord, y_coord) VALUES (?, ?, ?, ?, ?)');
  $stmt->bind_param('sssdd', $naam, $beschrijving, $beschrijving_en, $x, $y);
  $ok = $stmt->execute();
  $id = $ok ? $stmt->insert_id : 0;
  $stmt->close();
  echo json_encode(['status'=>$ok ? 'ok' : 'err', 'id'=>$id]);
  exit;
}

// Voeg een afbeelding toe aan de panorama volgorde
// Body: { img: string (pad/URL, verplicht), position?: number (optioneel, automatisch berekend als niet gegeven) }
// Als afbeelding al bestaat: update alleen de positie (DUPLICATE KEY)
if ($action === 'add_image') {
  $payload = json_decode(file_get_contents('php://input'), true) ?? [];
  $img = trim($payload['img'] ?? '');
  $position = isset($payload['position']) ? (int)$payload['position'] : 0;
  if ($img === '') { http_response_code(400); echo json_encode(['error'=>'missing_img']); exit; }
  if ($position <= 0) {
    $res = $conn->query('SELECT COALESCE(MAX(position),0) AS maxp FROM panorama_order');
    $row = $res ? $res->fetch_assoc() : ['maxp'=>0];
    $position = ((int)$row['maxp']) + 1;
  }
  $stmt = $conn->prepare('INSERT INTO panorama_order (img, position) VALUES (?, ?) ON DUPLICATE KEY UPDATE position=?');
  $stmt->bind_param('sii', $img, $position, $position);
  $ok = $stmt->execute();
  $stmt->close();
  echo json_encode(['status'=>$ok ? 'ok' : 'err', 'img'=>$img, 'position'=>$position]);
  exit;
}

// Verwijder een afbeelding uit de panorama volgorde
if ($action === 'delete_image') {
  $payload = json_decode(file_get_contents('php://input'), true) ?? [];
  $img = trim($payload['img'] ?? '');
  if ($img === '') { http_response_code(400); echo json_encode(['error'=>'missing_img']); exit; }
  $stmt = $conn->prepare('DELETE FROM panorama_order WHERE img = ?');
  $stmt->bind_param('s', $img);
  $ok = $stmt->execute();
  $stmt->close();
  echo json_encode(['status'=>$ok ? 'ok' : 'err']);
  exit;
}

// Verwijder een hotspot volledig uit de database
// Body: { id: number (hotspot ID te verwijderen) }
// Verwijdert alle gegevens: naam, beschrijvingen, coördinaten, alles
if ($action === 'delete_hotspot') {
  $payload = json_decode(file_get_contents('php://input'), true) ?? [];
  $id = (int)($payload['id'] ?? 0);
  if ($id <= 0) { http_response_code(400); echo json_encode(['error'=>'missing_id']); exit; }
  $stmt = $conn->prepare('DELETE FROM hotspots WHERE id = ?');
  $stmt->bind_param('i', $id);
  $ok = $stmt->execute();
  $stmt->close();
  echo json_encode(['status'=>$ok ? 'ok' : 'err']);
  exit;
}

echo json_encode(['error'=>'unknown_action']);
