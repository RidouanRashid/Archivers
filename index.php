<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css" />
 </head>
 <body>
 <?php include 'includes/header.php'; ?>

    <?php if (session_status() === PHP_SESSION_NONE) session_start(); $lang = $_SESSION['lang'] ?? 'nl'; ?>
    <h1 class="page-title"><?php echo $lang === 'en' ? 'Welcome to Utrecht Archives' : 'Welkom bij Het Utrechts Archief'; ?></h1>
    <main>
        <?php
        // Fetch panorama order from DB; fallback to default 1..33
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
            <!-- Hotspots overlay inside panorama to follow scaling -->
            <div class="hotspots-layer" aria-label="Hotspots"></div>
        </div>
        </div>
        <!-- Minimap under the panorama -->
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