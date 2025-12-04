<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<?php

if (isset($_GET['lang'])) {
    $lang = strtolower($_GET['lang']) === 'en' ? 'en' : 'nl';
    $_SESSION['lang'] = $lang;
}
$lang = $_SESSION['lang'] ?? 'nl';

$L = [
    'nl' => [
        'discover' => 'Ontdekken',
        'education' => 'Onderwijs',
        'professionals' => 'Vakgenoten',
        'about' => 'Over ons',
        'contact' => 'Contact',
        'english' => 'English',
        'login' => 'Inloggen',
        'admin' => 'Admin',
        'logout' => 'Uitloggen',
        'toggle' => 'EN',
        'toggle_aria' => 'Schakel naar Engels'
    ],
    'en' => [
        'discover' => 'Discover',
        'education' => 'Education',
        'professionals' => 'Professionals',
        'about' => 'About us',
        'contact' => 'Contact',
        'english' => 'Nederlands',
        'login' => 'Log in',
        'admin' => 'Admin',
        'logout' => 'Log out',
        'toggle' => 'NL',
        'toggle_aria' => 'Switch to Dutch'
    ]
][$lang];

function currentUrlNoLang(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = $_SERVER['REQUEST_URI'] ?? '/';
    $parts = parse_url($path);
    $query = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
        unset($query['lang']);
    }
    $base = $parts['path'] ?? '/';
    $qs = http_build_query($query);
    $urlPath = $qs ? "$base?$qs" : $base;
    return "$scheme://$host$urlPath";
}
function withLang(string $lang): string { return currentUrlNoLang() . (strpos(currentUrlNoLang(), '?') !== false ? "&lang=$lang" : "?lang=$lang"); }
?>
<header class="site-header" aria-label="Hoofdnavigatie">
    <div class="header-inner">
        <div class="logo"><a href="index.php"><img src="img/HUA-logo.png" alt="HUA Logo" id="huaLogo" data-small="img/HUA-logo.png" data-large="img/HUA-groot.png"></a></div>
        <nav class="main-nav" aria-label="Hoofdmenu">
            <ul>
                <li><a href="index.php"><?php echo htmlspecialchars($L['discover']); ?></a></li>
                <li><a href="#"><?php echo htmlspecialchars($L['education']); ?></a></li>
                <li><a href="#"><?php echo htmlspecialchars($L['professionals']); ?></a></li>
                <li><a href="#"><?php echo htmlspecialchars($L['about']); ?></a></li>
                <li><a href="#"><?php echo htmlspecialchars($L['contact']); ?></a></li>
                <li><a href="colofon.php">Colofon</a></li>
                <?php if (!empty($_SESSION['admin_user'])): ?>
                    <li><a href="admin.php"><?php echo htmlspecialchars($L['admin']); ?></a></li>
                    <li><a href="logout.php"><?php echo htmlspecialchars($L['logout']); ?></a></li>
                <?php else: ?>
                    <li><a href="inlog.php"><?php echo htmlspecialchars($L['login']); ?></a></li>
                <?php endif; ?>
                <li>
                    <a class="mode" href="<?php echo htmlspecialchars(withLang($lang === 'nl' ? 'en' : 'nl')); ?>" aria-label="<?php echo htmlspecialchars($L['toggle_aria']); ?>">
                        <?php echo htmlspecialchars($L['toggle']); ?>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="header-search">
            <button type="button" aria-label="Zoeken" class="search-btn">🔍</button>
        </div>
    </div>
</header>

    