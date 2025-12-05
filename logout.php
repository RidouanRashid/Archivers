<?php
// Logout: wis sessie en redirect naar login
// - Clear $_SESSION array (inhoud verwijderen)
// - Verwijder session cookie als deze bestaat
// - Destroy server-side session data
// - Redirect naar inlog.php
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
header('Location: inlog.php');
exit;