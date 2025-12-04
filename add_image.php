<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_user'])) {
    header('Location: inlog.php');
    exit;
}

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'hua2';

$feedback = '';

function insert_panorama_image(mysqli $conn, string $imgPath, ?int $position): bool
{
    if (!$position || $position <= 0) {
        $res = $conn->query('SELECT COALESCE(MAX(position),0) AS maxp FROM panorama_order');
        $row = $res ? $res->fetch_assoc() : ['maxp' => 0];
        $position = ((int)$row['maxp']) + 1;
    }
    $stmt = $conn->prepare('INSERT INTO panorama_order (img, position) VALUES (?, ?) ON DUPLICATE KEY UPDATE position=?');
    $stmt->bind_param('sii', $imgPath, $position, $position);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mysqli = @new mysqli($host, $user, $pass, $db);
    if ($mysqli->connect_error) {
        $feedback = 'Database fout: ' . htmlspecialchars($mysqli->connect_error);
    } else {
        $position = isset($_POST['position']) ? (int)$_POST['position'] : 0;
        if (!empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
            $name = basename($_FILES['image']['name']);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowed, true)) {
                $feedback = 'Alleen afbeeldingen (jpg, png, webp) toegestaan.';
            } else {
                $targetDir = __DIR__ . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR;
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0777, true);
                }
                $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
                $targetFs = $targetDir . $safeName;
                if (@move_uploaded_file($_FILES['image']['tmp_name'], $targetFs)) {
                    $relativePath = 'img/' . $safeName;
                    if (insert_panorama_image($mysqli, $relativePath, $position)) {
                        $feedback = 'Afbeelding geüpload en toegevoegd aan panorama.';
                    } else {
                        $feedback = 'Toevoegen aan panorama mislukt.';
                    }
                } else {
                    $feedback = 'Uploaden mislukt.';
                }
            }
        }
        elseif (!empty($_POST['url'])) {
            $url = trim($_POST['url']);
            if (insert_panorama_image($mysqli, $url, $position)) {
                $feedback = 'Afbeelding toegevoegd aan panorama.';
            } else {
                $feedback = 'Toevoegen mislukt.';
            }
        } else {
            $feedback = 'Kies een bestand of voer een URL in.';
        }
        $mysqli->close();
    }
}
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Afbeelding toevoegen</title>
    <link rel="stylesheet" href="style.css" />
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <h1 class="page-title">Afbeelding toevoegen aan panorama</h1>
    <main class="admin-main">
        <section class="admin-section">
            <p>Kies een afbeelding uit je verkenner of voeg toe via URL/pad. Positie is optioneel.</p>
            <?php if (!empty($feedback)): ?>
                <div class="login-error" style="max-width:800px; margin: 0 auto 12px;">
                    <?php echo htmlspecialchars($feedback); ?>
                </div>
            <?php endif; ?>
            <form action="add_image.php" method="post" enctype="multipart/form-data" style="max-width:800px; margin:0 auto;">
                <div style="display:flex; flex-wrap:wrap; gap:16px;">
                    <div style="flex:1 1 320px;">
                        <label for="image">Upload afbeelding (jpg, png, webp)</label>
                        <input type="file" id="image" name="image" accept="image/*" style="width:100%; padding:8px;" />
                    </div>
                    <div style="flex:1 1 320px;">
                        <label for="url">Of URL/pad</label>
                        <input type="text" id="url" name="url" placeholder="bijv. img/34.jpg of https://..." style="width:100%; padding:8px;" />
                    </div>
                    <div style="width:160px;">
                        <label for="position">Positie (optioneel)</label>
                        <input type="number" id="position" name="position" min="1" style="width:100%; padding:8px;" />
                    </div>
                </div>
                <div style="margin-top:12px; display:flex; gap:8px;">
                    <button type="submit" class="mode">Toevoegen</button>
                    <a href="admin.php" class="mode">Terug naar admin</a>
                </div>
            </form>
        </section>
    </main>
    <?php include 'includes/footer.php'; ?>
    <script src="script.js"></script>

</body>

</html>