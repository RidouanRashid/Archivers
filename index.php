<!DOCTYPE html>
<!-- Panorama pagina: hoofd interactieve interface
  - Zoom/pan: muis scroll, drag, trackpad Ctrl+scroll, touch 2-finger pinch
  - Minimap: navigatie en positie-feedback
  - Hotspots: interactief met popups
  - Admin mode: ingeschakeld via ?admin parameter
  - Laadt afbeeldingen/hotspots uit database
-->
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leporello Panorama</title>
    <link rel="stylesheet" href="style.css" />
 </head>
 <body>
 <?php
 // Standaard: toon tutorial content zonder header/footer op de homepage
 // Override: voeg ?view=panorama toe om de normale index te tonen
 if (!isset($_GET['view']) || $_GET['view'] !== 'panorama') {
     include 'includes/tutorial_content.php';
     echo '<script src="script.js"></script>';
     echo '</body></html>';
     return;
 }
 ?>
 <?php include 'includes/header.php'; ?>

    <?php if (session_status() === PHP_SESSION_NONE) session_start(); $lang = $_SESSION['lang'] ?? 'nl'; ?>
    <main>
        <?php
        $images = [];
        $host = '127.0.0.1';
        $user = 'root';
        $pass = '';
        $db   = 'hua2';
        $mysqli = @new mysqli($host, $user, $pass, $db);
        if (!$mysqli->connect_error) {
            if ($res = $mysqli->query("SELECT img FROM panorama_order ORDER BY position ASC")) {
                while ($row = $res->fetch_assoc()) {
                    if (!empty($row['img'])) { $images[] = $row['img']; }
                }
                $res->close();
            }
            $mysqli->close();
        }
        if (count($images) === 0) {
            for ($i = 1; $i <= 33; $i++) { $images[] = 'img/' . $i . '.jpg'; }
        }
        ?>
        <div class="panorama-toolbar">
            <button type="button" class="zoom-out" aria-label="Zoom out">-</button>
            <button type="button" class="zoom-reset" aria-label="Reset zoom">Reset</button>
            <button type="button" class="zoom-in" aria-label="Zoom in">+</button>
        </div>

        <?php if(isset($_GET['admin'])): ?>
        <div class="admin-toolbar"><strong>Admin modus</strong><a class="mode" href="admin.php">Naar admin</a></div>
        <?php endif; ?>
        <div class="panorama-viewport">
        <div class="panorama<?php echo isset($_GET['admin']) ? ' admin' : '';?>" data-overlap="42">
            <?php foreach ($images as $idx => $src): ?>
                <img src="<?php echo htmlspecialchars($src); ?>" alt="Panorama Image <?php echo $idx+1; ?>">
            <?php endforeach; ?>
            <div class="hotspots-layer" aria-label="Hotspots"></div>
        </div>
        </div>
        <div class="minimap" aria-label="Panorama minimap">
            <div class="minimap-track">
                <?php foreach ($images as $idx => $src): ?>
                    <img src="<?php echo htmlspecialchars($src); ?>" alt="Mini <?php echo $idx+1; ?>">
                <?php endforeach; ?>
            </div>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
    <script src="script.js"></script>
</body>
</html>