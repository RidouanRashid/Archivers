<?php
$host = '127.0.0.1';
$user = 'root'; // adjust
$pass = '';
$db   = 'hua2';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    die('DB connect failed: ' . htmlspecialchars($conn->connect_error));
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$hotspot = null;
if ($id > 0) {
    $stmt = $conn->prepare('SELECT id, naam, beschrijving, beschrijving_english AS beschrijving_en, x_coord, y_coord FROM hotspots WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $hotspot = $res->fetch_assoc();
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php if (session_status() === PHP_SESSION_NONE) session_start(); $lang = $_SESSION['lang'] ?? 'nl'; ?>
    <title><?php echo $lang === 'en' ? 'Hotspot' : 'Hotspot'; ?><?php echo $hotspot ? ' – ' . htmlspecialchars($hotspot['naam']) : ''; ?></title>
    <link rel="stylesheet" href="style.css" />
 </head>
 <body>
 <?php include 'includes/header.php'; ?>
        <h1 class="page-title"><?php echo $lang === 'en' ? 'Hotspot details' : 'Hotspotdetails'; ?></h1>
    <main>
        <?php if ($hotspot): ?>
        <section class="hotspot-detail">
            <h2><?php echo htmlspecialchars($hotspot['naam']); ?></h2>
                        <?php
                            $desc = $hotspot['beschrijving'] ?? '';
                            if ($lang === 'en' && !empty($hotspot['beschrijving_en'])) {
                                $desc = $hotspot['beschrijving_en'];
                            }
                        ?>
                        <p><?php echo nl2br(htmlspecialchars($desc)); ?></p>
            <p><small>Positie: x=<?php echo htmlspecialchars($hotspot['x_coord']); ?>, y=<?php echo htmlspecialchars($hotspot['y_coord']); ?></small></p>
        </section>
        <?php else: ?>
        <section class="hotspot-detail">
                        <p><?php echo $lang === 'en' ? 'Hotspot not found.' : 'Hotspot niet gevonden.'; ?></p>
        </section>
        <?php endif; ?>
    </main>
    <?php include 'includes/footer.php'; ?>
    <script src="script.js"></script>
</body>
</html>
