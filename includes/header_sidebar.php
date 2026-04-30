<?php
$activePage = $activePage ?? '';
$is_logged_in = $is_logged_in ?? false;
$balance = $balance ?? 0;
$notification_count = $notification_count ?? 0;
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$siteBase = preg_replace('#/pages$#', '', rtrim($scriptDir, '/'));
if ($siteBase === '/' || $siteBase === '.') {
    $siteBase = '';
}

if (!function_exists('elite_url')) {
    function elite_url($path) {
        global $siteBase;
        return htmlspecialchars(($siteBase === '' ? '' : $siteBase) . '/' . ltrim($path, '/'), ENT_QUOTES, 'UTF-8');
    }
}
?>
<aside class="sidebar" id="side" aria-hidden="true">
    <div class="sidebar-top">
    <button class="toggle" id="toggle" aria-label="Toggle navigation" aria-expanded="false">☰</button>
        <a href="<?php echo elite_url('pages/games.php'); ?>" class="sidebar-games-button" <?php echo $activePage === 'games' ? 'aria-current="page"' : ''; ?>>Games</a>
    </div>
    <nav class="navigation">
        <a href="<?php echo elite_url('index.php'); ?>" class="item" <?php echo $activePage === 'home' ? 'id="active"' : ''; ?>><span class="icon">🏠</span><span class="text">Home</span></a>
        <a href="<?php echo elite_url('pages/favorites.php'); ?>" class="item" <?php echo $activePage === 'favourites' ? 'id="active"' : ''; ?>><span class="icon">❤️</span><span class="text">Favourites</span></a>
        <a href="#" class="item" <?php echo $activePage === 'recent' ? 'id="active"' : ''; ?>><span class="icon">🕒</span><span class="text">Recent</span></a>
        <div class="dropdown">
            <div class="dropdown-item">
                <button class="dropdown-button" aria-haspopup="true" aria-expanded="false">
                    <span class="menu-icon">⁉️</span><span class="menu-text">placeholder</span><span class="menu-arrow">▼</span>
                </button>
                <div class="dropdown-items" aria-hidden="true">
                    <a href="#">text</a><a href="#">text</a><a href="#">text</a>
                </div>
            </div>
            <div class="dropdown-item">
                <button class="dropdown-button" aria-haspopup="true" aria-expanded="false">
                    <span class="menu-icon">⁉️</span><span class="menu-text">placeholder</span><span class="menu-arrow">▼</span>
                </button>
                <div class="dropdown-items" aria-hidden="true">
                    <a href="#">text</a><a href="#">text</a><a href="#">text</a>
                </div>
            </div>
            <div class="dropdown-item">
                <button class="dropdown-button" aria-haspopup="true" aria-expanded="false">
                    <span class="menu-icon">⁉️</span><span class="menu-text">placeholder</span><span class="menu-arrow">▼</span>
                </button>
                <div class="dropdown-items" aria-hidden="true">
                    <a href="#">text</a><a href="#">text</a><a href="#">text</a>
                </div>
            </div>
        </div>
    </nav>
</aside>

<header class="header">
    <div class="header-box">
        <div class="logo">
            <a href="<?php echo elite_url('index.php'); ?>"><img src="<?php echo elite_url('assets/img/Elite-logo.png'); ?>" alt="Elite"></a>
        </div>
        <?php if ($is_logged_in): ?>
        <div class="balance">
            <span class="balance-amount" id="balanceAmount">$<?php echo number_format((float)$balance, 2); ?></span>
        </div>
        <?php endif; ?>
        <div class="header-buttons">
            <button class="button icon" id="search" aria-label="Search"><span>&#128269;</span></button>
            <div class="group" role="group" aria-label="Profile and notifications">
                <button class="button icon notif" id="notif" aria-label="Notifications"><span>&#128276;</span><span class="badge" id="badge"><?php echo $notification_count; ?></span></button>
                <?php if ($is_logged_in): ?>
                    <div class="profile-menu-wrap" id="profileMenuWrap">
                        <button class="button icon" id="prof" type="button" aria-label="Profile" aria-haspopup="true" aria-expanded="false"><span>&#128100;</span></button>
                        <div class="profile-menu" id="profileMenu" aria-hidden="true">
                            <button class="profile-menu-item" type="button">Placeholder</button>
                            <button class="profile-menu-item" type="button">Placeholder</button>
                            <button class="profile-menu-item" type="button">Placeholder</button>
                            <button class="profile-menu-item logout" id="logoutBtn" type="button"><span>&#128682;</span><span>Logout</span></button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (!$is_logged_in): ?>
                <button class="auth-button" id="loginBtn"><span class="auth-icon">&#128272;</span><span class="auth-text">Login</span></button>
            <?php endif; ?>
        </div>
    </div>
</header>

<script src="<?php echo elite_url('assets/js/header_sidebar.js'); ?>" data-login-url="<?php echo elite_url('api/login.php'); ?>"></script>

