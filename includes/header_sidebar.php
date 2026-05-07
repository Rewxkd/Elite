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
            <a href="<?php echo elite_url('index.php'); ?>" aria-label="Elite home">
                <picture>
                    <source media="(max-width: 980px)" srcset="<?php echo elite_url('assets/img/Elite-letter_logo.png'); ?>">
                    <img src="<?php echo elite_url('assets/img/Elite-logo.png'); ?>" alt="Elite">
                </picture>
            </a>
        </div>
        <?php if ($is_logged_in): ?>
        <div class="balance">
            <span class="balance-amount" id="balanceAmount">$<?php echo number_format((float)$balance, 2); ?></span>
        </div>
        <?php endif; ?>
        <div class="header-buttons">
            <div class="group" role="group" aria-label="Profile and notifications">
                <button class="button icon notif header-action" id="notif" aria-label="Notifications">
                    <svg class="header-icon header-icon-filled" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 22a2.75 2.75 0 0 0 2.68-2.13H9.32A2.75 2.75 0 0 0 12 22Z"></path>
                        <path d="M19.45 16.25c-.93-.98-1.45-2.35-1.45-3.83V9.75a6 6 0 0 0-12 0v2.67c0 1.48-.52 2.85-1.45 3.83-.7.74-.18 1.95.84 1.95h13.22c1.02 0 1.54-1.21.84-1.95Z"></path>
                    </svg>
                    <span class="badge" id="badge"><?php echo $notification_count; ?></span>
                </button>
                <?php if ($is_logged_in): ?>
                    <div class="profile-menu-wrap" id="profileMenuWrap">
                        <button class="button icon header-action" id="prof" type="button" aria-label="Profile" aria-haspopup="true" aria-expanded="false">
                            <svg class="header-icon header-icon-filled" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 12.25a4.75 4.75 0 1 0 0-9.5 4.75 4.75 0 0 0 0 9.5Z"></path>
                                <path d="M4.2 20.1c.96-4.16 3.58-6.35 7.8-6.35s6.84 2.19 7.8 6.35c.16.7-.38 1.4-1.1 1.4H5.3c-.72 0-1.26-.7-1.1-1.4Z"></path>
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

<nav class="mobile-bottom-nav" aria-label="Mobile navigation">
    <a href="<?php echo elite_url('index.php'); ?>" class="mobile-nav-item" <?php echo $activePage === 'home' ? 'aria-current="page"' : ''; ?>>
        <span class="mobile-nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M4.5 10.75 12 4l7.5 6.75"></path><path d="M6.75 9.25v9.25h10.5V9.25"></path><path d="M10 18.5v-5h4v5"></path></svg>
        </span>
        <span>Home</span>
    </a>
    <a href="<?php echo elite_url('pages/games.php'); ?>" class="mobile-nav-item" <?php echo in_array($activePage, ['games', 'blackjack', 'mines', 'plinko', 'dice'], true) ? 'aria-current="page"' : ''; ?>>
        <span class="mobile-nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M7.25 5.5h9.5a2.75 2.75 0 0 1 2.75 2.75v7.5a2.75 2.75 0 0 1-2.75 2.75h-9.5a2.75 2.75 0 0 1-2.75-2.75v-7.5A2.75 2.75 0 0 1 7.25 5.5Z"></path><path d="M8.5 12h4"></path><path d="M10.5 10v4"></path><path d="M15.75 10.7h.01"></path><path d="M17.35 13.3h.01"></path></svg>
        </span>
        <span>Games</span>
    </a>
    <a href="<?php echo elite_url('pages/favorites.php'); ?>" class="mobile-nav-item" <?php echo $activePage === 'favourites' ? 'aria-current="page"' : ''; ?>>
        <span class="mobile-nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M12 19.25s-7.25-4.25-7.25-9.15A3.85 3.85 0 0 1 12 8.28a3.85 3.85 0 0 1 7.25 1.82c0 4.9-7.25 9.15-7.25 9.15Z"></path></svg>
        </span>
        <span>Saved</span>
    </a>
    <a href="<?php echo elite_url('pages/recent.php'); ?>" class="mobile-nav-item" <?php echo $activePage === 'recent' ? 'aria-current="page"' : ''; ?>>
        <span class="mobile-nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M5.25 12a6.75 6.75 0 1 0 2-4.8"></path><path d="M5.25 5.25V9h3.75"></path><path d="M12 8.75v3.75l2.5 1.5"></path></svg>
        </span>
        <span>Recent</span>
    </a>
</nav>

<script src="<?php echo elite_url('assets/js/header_sidebar.js'); ?>" data-login-url="<?php echo elite_url('api/login.php'); ?>"></script>

