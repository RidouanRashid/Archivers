<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['admin_user'])) {
    header('Location: admin.php');
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = '127.0.0.1';
    $user = 'root';
    $pass = '';
    $db   = 'hua2';
    $mysqli = @new mysqli($host, $user, $pass, $db);
    if ($mysqli->connect_error) {
        $error = 'Database fout.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $stmt = $mysqli->prepare('SELECT id, username, password_hash FROM admin_users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        $mysqli->close();
        if ($row && password_verify($password, $row['password_hash'])) {
            $_SESSION['admin_user'] = $row['username'];
            $_SESSION['admin_user_id'] = (int)$row['id'];
            header('Location: admin.php');
            exit;
        } else {
            $error = 'Onjuiste gegevens.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Inloggen - Het Utrechts Archief</title>
    <link rel="stylesheet" href="style.css" />
        <style>
      @media (min-width:901px){ main .login-wrapper{ min-height:calc(100vh - 280px); display:flex; align-items:center; } }
        </style>
</head>
<body>
    <h1 class="page-title">Inloggen</h1>
    <main>
        <div class="login-wrapper">
            <form action="inlog.php" method="post" class="login-form" autocomplete="off">
                <label for="username">Gebruikersnaam</label>
                <input type="text" id="username" name="username" required autofocus />
                <label for="password">Wachtwoord</label>
                <input type="password" id="password" name="password" required />
                <?php if ($error): ?><div class="login-error" aria-live="polite"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <button type="submit" class="mode">Inloggen</button>
            </form>
        </div>
    </main>
    <script src="script.js"></script>
</body>
</html>
