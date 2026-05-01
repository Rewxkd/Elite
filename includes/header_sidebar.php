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
        <button class="toggle" id="toggle" aria-label="Toggle navigation" aria-expanded="false">&#9776;</button>
        <a href="<?php echo elite_url('pages/games.php'); ?>" class="sidebar-games-button" <?php echo $activePage === 'games' ? 'aria-current="page"' : ''; ?>>Games</a>
    </div>
    <nav class="navigation">
        <a href="<?php echo elite_url('index.php'); ?>" class="item" <?php echo $activePage === 'home' ? 'id="active"' : ''; ?>><span class="icon">&#8962;</span><span class="text">Home</span></a>
        <a href="<?php echo elite_url('pages/favorites.php'); ?>" class="item" <?php echo $activePage === 'favourites' ? 'id="active"' : ''; ?>><span class="icon">&#9829;</span><span class="text">Favourites</span></a>
        <a href="<?php echo elite_url('pages/recent.php'); ?>" class="item" <?php echo $activePage === 'recent' ? 'id="active"' : ''; ?>><span class="icon">&#8635;</span><span class="text">Recent</span></a>
        <div class="dropdown">
            <div class="dropdown-item">
                <button class="dropdown-button" aria-haspopup="true" aria-expanded="false">
                    <span class="menu-icon">&#63;</span><span class="menu-text">placeholder</span><span class="menu-arrow">&#9662;</span>
                </button>
                <div class="dropdown-items" aria-hidden="true">
                    <a href="#">text</a><a href="#">text</a><a href="#">text</a>
                </div>
            </div>
            <div class="dropdown-item">
                <button class="dropdown-button" aria-haspopup="true" aria-expanded="false">
                    <span class="menu-icon">&#63;</span><span class="menu-text">placeholder</span><span class="menu-arrow">&#9662;</span>
                </button>
                <div class="dropdown-items" aria-hidden="true">
                    <a href="#">text</a><a href="#">text</a><a href="#">text</a>
                </div>
            </div>
            <div class="dropdown-item">
                <button class="dropdown-button" aria-haspopup="true" aria-expanded="false">
                    <span class="menu-icon">&#63;</span><span class="menu-text">placeholder</span><span class="menu-arrow">&#9662;</span>
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
            <button class="button icon header-action" id="search" aria-label="Search">
                <svg class="header-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M10.75 18.5a7.75 7.75 0 1 1 0-15.5 7.75 7.75 0 0 1 0 15.5Z"></path>
                    <path d="m16.5 16.5 4 4"></path>
                </svg>
            </button>
            <div class="group" role="group" aria-label="Profile and notifications">
                <button class="button icon notif header-action" id="notif" aria-label="Notifications">
                    <svg class="header-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M18 9.5a6 6 0 0 0-12 0c0 7-3 7-3 8.8 0 1 2.3 1.7 9 1.7s9-.7 9-1.7c0-1.8-3-1.8-3-8.8Z"></path>
                        <path d="M9.8 21a2.35 2.35 0 0 0 4.4 0"></path>
                    </svg>
                    <span class="badge" id="badge"><?php echo $notification_count; ?></span>
                </button>
                <?php if ($is_logged_in): ?>
                    <div class="profile-menu-wrap" id="profileMenuWrap">
                        <button class="button icon header-action" id="prof" type="button" aria-label="Profile" aria-haspopup="true" aria-expanded="false">
                            <svg class="header-icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 12.25a4.25 4.25 0 1 0 0-8.5 4.25 4.25 0 0 0 0 8.5Z"></path>
                                <path d="M4.75 20.25c.9-3.65 3.25-5.5 7.25-5.5s6.35 1.85 7.25 5.5"></path>
                            </svg>
                        </button>
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
                <button class="auth-button" id="loginBtn">
                    <span class="auth-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M8 10V7.75a4 4 0 0 1 8 0V10"></path>
                            <path d="M6.75 10h10.5A1.75 1.75 0 0 1 19 11.75v6.5A1.75 1.75 0 0 1 17.25 20H6.75A1.75 1.75 0 0 1 5 18.25v-6.5A1.75 1.75 0 0 1 6.75 10Z"></path>
                            <path d="M12 14v2"></path>
                        </svg>
                    </span>
                    <span class="auth-text">Login</span>
                </button>
            <?php endif; ?>
        </div>
    </div>
</header>

<script src="<?php echo elite_url('assets/js/header_sidebar.js'); ?>" data-login-url="<?php echo elite_url('api/login.php'); ?>"></script>

