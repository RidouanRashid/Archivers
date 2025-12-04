<?php
// Admin panel (requires login)
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_user'])) {
  header('Location: inlog.php');
  exit;
}
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'hua2';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die('DB connect failed: '.$conn->connect_error);

$conn->query("CREATE TABLE IF NOT EXISTS panorama_order (
  id INT AUTO_INCREMENT PRIMARY KEY,
  img VARCHAR(255) NOT NULL UNIQUE,
  position INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$res = $conn->query("SELECT COUNT(*) AS c FROM panorama_order");
$row = $res ? $res->fetch_assoc() : ['c'=>0];
if ((int)$row['c'] === 0) {
  for ($i=1; $i<=33; $i++) {
    $img = 'img/'.$i.'.jpg';
    $pos = $i;
    $stmt = $conn->prepare('INSERT IGNORE INTO panorama_order (img, position) VALUES (?, ?)');
    $stmt->bind_param('si', $img, $pos);
    $stmt->execute();
    $stmt->close();
  }
}

$images = [];
$r = $conn->query('SELECT img, position FROM panorama_order ORDER BY position ASC');
while ($rr = $r->fetch_assoc()) $images[] = $rr;

$hotspots = [];
$hr = $conn->query('SELECT id, naam, beschrijving, x_coord, y_coord FROM hotspots ORDER BY id ASC');
if ($hr) { while ($h = $hr->fetch_assoc()) $hotspots[] = $h; }
$conn->close();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin - Panorama & Hotspots</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="admin-toolbar">
    <strong>Admin (ingelogd als <?php echo htmlspecialchars($_SESSION['admin_user']); ?>)</strong>
    <a class="mode" href="index.php">Terug naar panorama</a>
    <a class="mode" href="logout.php">Uitloggen</a>
  </div>

  <main class="admin-main">
    <section class="admin-section">
      <h2>Volgorde van afbeeldingen</h2>
      <p>Sleep om de volgorde aan te passen en klik op Opslaan.</p>
      <div class="reorder-list" id="reorderList">
        <?php foreach ($images as $im): ?>
          <div class="reorder-item" draggable="true" data-img="<?php echo htmlspecialchars($im['img']); ?>">
            <div style="display:flex; align-items:center; gap:8px;">
              <button class="mode arrow-left" type="button" title="Naar links">←</button>
              <img src="<?php echo htmlspecialchars($im['img']); ?>" alt="" style="height:60px;display:block;">
              <button class="mode arrow-right" type="button" title="Naar rechts">→</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <button id="saveOrder" class="mode">Opslaan</button>
      <a class="mode" href="add_image.php" style="margin-left:8px;">Afbeelding toevoegen</a>
      <button type="button" class="mode" id="enterDeleteImageMode" style="margin-left:8px;">Afbeelding verwijderen</button>
    </section>

    <section class="admin-section" style="margin-top:24px;">
      <h2>Hotspots bewerken (op panorama)</h2>
      <p>Klik hieronder om hotspots te verplaatsen op de panorama pagina.</p>
      <button class="mode" id="toggleDrag">Hotspots verplaatsen</button>
      <a class="mode" href="add_hotspot.php" style="margin-left:8px;">Hotspot toevoegen</a>
    </section>

    <section id="new-hotspot" class="admin-section" style="margin-top:24px;">
      <h2>Hotspot beschrijvingen</h2>
      <p>Pas hier de beschrijvingen van hotspots aan. Wijzigingen worden direct opgeslagen.</p>
      <div id="hotspotList" class="admin-grid">
        <?php foreach ($hotspots as $hs): ?>
          <div class="admin-card">
            <label for="naam_<?php echo (int)$hs['id']; ?>">Naam</label>
            <input type="text" id="naam_<?php echo (int)$hs['id']; ?>" class="hs-naam" data-id="<?php echo (int)$hs['id']; ?>" value="<?php echo htmlspecialchars($hs['naam'] ?: ('Hotspot '.$hs['id'])); ?>" style="width:100%; padding:8px;">
            <textarea data-id="<?php echo (int)$hs['id']; ?>">
<?php echo htmlspecialchars($hs['beschrijving']); ?>
            </textarea>
            <div style="display:flex; gap:8px; align-items:center; margin-top:8px;">
              <label for="x_<?php echo (int)$hs['id']; ?>">X</label>
              <input type="number" id="x_<?php echo (int)$hs['id']; ?>" class="coord-x" data-id="<?php echo (int)$hs['id']; ?>" value="<?php echo htmlspecialchars((string)($hs['x_coord'] ?? 0)); ?>" step="1" style="width:100px;">
              <label for="y_<?php echo (int)$hs['id']; ?>">Y</label>
              <input type="number" id="y_<?php echo (int)$hs['id']; ?>" class="coord-y" data-id="<?php echo (int)$hs['id']; ?>" value="<?php echo htmlspecialchars((string)($hs['y_coord'] ?? 0)); ?>" step="1" style="width:100px;">
            </div>
            <div style="display:flex; gap:8px; margin-top:8px;">
              <button class="mode saveHotspot" data-id="<?php echo (int)$hs['id']; ?>">Opslaan</button>
              <button class="mode deleteHotspot" data-id="<?php echo (int)$hs['id']; ?>" style="background:#f7e7e7;">Verwijderen</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </main>
  <script src="script.js"></script>
  <script>
    document.getElementById('addImageBtn')?.addEventListener('click', async () => {
      const img = document.getElementById('newImg').value.trim();
      const posVal = document.getElementById('newPos').value.trim();
      const position = posVal ? parseInt(posVal, 10) : undefined;
      if (!img) { alert('Voer een afbeeldingspad of URL in.'); return; }
      const res = await fetch('admin_api.php?action=add_image', {
        method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ img, position })
      });
      const out = await res.json();
      if (out.status === 'ok') {
        alert('Afbeelding toegevoegd.');
        window.location.reload();
      } else {
        alert('Toevoegen mislukt.');
      }
    });

    (function(){
      const list = document.getElementById('reorderList');
      const btn = document.getElementById('enterDeleteImageMode');
      if (!list || !btn) return;
      let active = false;
      const hint = document.createElement('div');
      hint.textContent = 'Verwijdermodus actief: klik op een afbeelding om te verwijderen';
      hint.style.margin = '8px 0';
      hint.style.padding = '8px 12px';
      hint.style.border = '1px solid #e7b2b2';
      hint.style.background = '#fff0f0';
      hint.style.borderRadius = '8px';

      btn.addEventListener('click', () => {
        active = !active;
        list.classList.toggle('delete-mode', active);
        btn.textContent = active ? 'Verwijdermodus stoppen' : 'Afbeelding verwijderen';
        if (active) list.parentElement.insertBefore(hint, list);
        else if (hint.parentElement) hint.parentElement.removeChild(hint);
      });

      list.addEventListener('click', async (e) => {
        if (!active) return;
        if (e.target.closest('.arrow-left') || e.target.closest('.arrow-right')) return;
        const item = e.target.closest('.reorder-item');
        if (!item) return;
        const img = item.dataset.img;
        if (!img) return;
        if (!confirm('Verwijder deze afbeelding uit het panorama?')) return;
        const res = await fetch('admin_api.php?action=delete_image', {
          method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ img })
        });
        const out = await res.json();
        if (out.status === 'ok') {
          item.remove();
        } else {
          alert('Verwijderen mislukt.');
        }
      });
    })();

  </script>
</body>
</html>
