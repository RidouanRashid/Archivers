<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_user'])) { header('Location: inlog.php'); exit; }

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'hua2';

$feedback = '';
$createdId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $naam = trim($_POST['naam'] ?? '');
  $beschrijving = trim($_POST['beschrijving'] ?? '');
  $beschrijving_english = trim($_POST['beschrijving_english'] ?? '');
  $x = isset($_POST['x_coord']) && $_POST['x_coord'] !== '' ? (float)$_POST['x_coord'] : null;
  $y = isset($_POST['y_coord']) && $_POST['y_coord'] !== '' ? (float)$_POST['y_coord'] : null;

  if ($naam === '') {
    $feedback = 'Naam is verplicht.';
  } else {
    $mysqli = @new mysqli($host, $user, $pass, $db);
    if ($mysqli->connect_error) {
      $feedback = 'Database fout: ' . htmlspecialchars($mysqli->connect_error);
    } else {
      $stmt = $mysqli->prepare('INSERT INTO hotspots (naam, beschrijving, beschrijving_english, x_coord, y_coord) VALUES (?, ?, ?, ?, ?)');
      $xv = $x ?? 0.0; $yv = $y ?? 0.0; // store 0 when empty
      $stmt->bind_param('sssdd', $naam, $beschrijving, $beschrijving_english, $xv, $yv);
      if ($stmt->execute()) {
        $createdId = $stmt->insert_id;
        $feedback = 'Hotspot toegevoegd.';
      } else {
        $feedback = 'Opslaan mislukt.';
      }
      $stmt->close();
      $mysqli->close();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Hotspot toevoegen</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <?php include 'includes/header.php'; ?>
  <h1 class="page-title">Nieuwe hotspot toevoegen</h1>
  <main class="admin-main">
    <section class="admin-section">
      <?php if (!empty($feedback)): ?>
        <div class="login-error" style="max-width:800px; margin: 0 auto 12px;">
          <?php echo htmlspecialchars($feedback); ?>
        </div>
      <?php endif; ?>
      <form action="add_hotspot.php" method="post" style="max-width:800px; margin:0 auto;">
        <div class="admin-card">
          <label for="naam">Naam</label>
          <input type="text" id="naam" name="naam" required />

          <strong style="display:block; margin-top:8px;">Beschrijving (NL)</strong>
          <textarea id="beschrijving" name="beschrijving" rows="4"></textarea>

          <strong style="display:block; margin-top:8px;">Beschrijving (EN)</strong>
          <textarea id="beschrijving_english" name="beschrijving_english" rows="4"></textarea>

          <div style="display:flex; gap:8px; align-items:center; margin-top:8px;">
            <label for="x_coord">X</label>
            <input type="number" id="x_coord" name="x_coord" step="1" style="width:140px;">
            <label for="y_coord">Y</label>
            <input type="number" id="y_coord" name="y_coord" step="1" style="width:140px;">
          </div>
          <div style="display:flex; gap:8px; margin-top:12px;">
            <button type="submit" class="mode">Hotspot toevoegen</button>
            <a class="mode" href="admin.php">Terug naar admin</a>
            <?php if ($createdId): ?>
              <a class="mode" href="hotspot.php?id=<?php echo (int)$createdId; ?>">Bekijk hotspot</a>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </section>
  </main>
  <?php include 'includes/footer.php'; ?>
  <script src="script.js"></script>
</body>
</html>
