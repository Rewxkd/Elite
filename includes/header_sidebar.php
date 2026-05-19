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

if (!function_exists('elite_icon')) {
    function elite_icon($name, $class = '') {
        $icons = [
            'home' => '<path d="M4.25 10.65 12 3.8l7.75 6.85c.5.44.19 1.27-.48 1.27h-.77v6.58a2 2 0 0 1-2 2h-2.2a.8.8 0 0 1-.8-.8v-4.2h-3v4.2a.8.8 0 0 1-.8.8H7.5a2 2 0 0 1-2-2v-6.58h-.77c-.67 0-.98-.83-.48-1.27Z"></path>',
            'heart' => '<path d="M12 20.65a1.1 1.1 0 0 1-.58-.16C7.07 17.78 4 14.52 4 10.65A4.42 4.42 0 0 1 8.42 6.2c1.5 0 2.82.7 3.58 1.8a4.3 4.3 0 0 1 3.58-1.8A4.42 4.42 0 0 1 20 10.65c0 3.87-3.07 7.13-7.42 9.84a1.1 1.1 0 0 1-.58.16Z"></path>',
            'recent' => '<path d="M12 4.25a7.75 7.75 0 1 1-7.31 5.17.9.9 0 1 1 1.7.6A5.95 5.95 0 1 0 8.4 7.16h1.02a.9.9 0 0 1 0 1.8H5.8a.9.9 0 0 1-.9-.9V4.45a.9.9 0 0 1 1.8 0v1.38A7.73 7.73 0 0 1 12 4.25Zm.9 4.35v3.1l2.25 1.35a.9.9 0 0 1-.92 1.55l-2.69-1.61a.9.9 0 0 1-.44-.77V8.6a.9.9 0 0 1 1.8 0Z"></path>',
            'gift' => '<path d="M8.05 4.25c1.22 0 2.2.7 2.88 1.6.25.34.47.7.65 1.08h.84c.18-.38.4-.74.65-1.08.68-.9 1.66-1.6 2.88-1.6A2.55 2.55 0 0 1 18.5 6.8c0 .62-.21 1.16-.55 1.6H19a1.75 1.75 0 0 1 1.75 1.75v1.05c0 .45-.35.8-.8.8H13v-3.4h-2V12H4.05a.8.8 0 0 1-.8-.8v-1.05A1.75 1.75 0 0 1 5 8.4h1.05a2.5 2.5 0 0 1-.55-1.6 2.55 2.55 0 0 1 2.55-2.55ZM8.05 6a.8.8 0 0 0 0 1.6h1.75A3.55 3.55 0 0 0 9.53 7c-.4-.54-.88-1-1.48-1Zm6.15 1.6h1.75a.8.8 0 0 0 0-1.6c-.6 0-1.08.46-1.48 1-.12.17-.22.37-.27.6ZM5 13h6v6.75H6.75A1.75 1.75 0 0 1 5 18v-5Zm8 0h6v5a1.75 1.75 0 0 1-1.75 1.75H13V13Z"></path>',
            'games' => '<path d="M7.15 6.1h9.7A3.15 3.15 0 0 1 20 9.25v5.5a3.15 3.15 0 0 1-3.15 3.15h-.55c-.86 0-1.49-.45-2.03-1.02l-.72-.76h-3.1l-.72.76c-.54.57-1.17 1.02-2.03 1.02h-.55A3.15 3.15 0 0 1 4 14.75v-5.5A3.15 3.15 0 0 1 7.15 6.1Zm1.25 4.05a.85.85 0 0 0-.85.85v.75H6.8a.85.85 0 0 0 0 1.7h.75v.75a.85.85 0 0 0 1.7 0v-.75H10a.85.85 0 0 0 0-1.7h-.75V11a.85.85 0 0 0-.85-.85Zm6.95.45a1.05 1.05 0 1 0 0 2.1 1.05 1.05 0 0 0 0-2.1Zm2.15 2.25a1.05 1.05 0 1 0 0 2.1 1.05 1.05 0 0 0 0-2.1Z"></path>',
            'chart' => '<path d="M5.75 5.25A1.75 1.75 0 0 0 4 7v10a1.75 1.75 0 0 0 1.75 1.75h12.5A1.75 1.75 0 0 0 20 17V7a1.75 1.75 0 0 0-1.75-1.75H5.75Zm2 10.5a.9.9 0 0 1-.9-.9v-2.6a.9.9 0 0 1 1.8 0v2.6a.9.9 0 0 1-.9.9Zm4.25 0a.9.9 0 0 1-.9-.9v-5.7a.9.9 0 1 1 1.8 0v5.7a.9.9 0 0 1-.9.9Zm4.25 0a.9.9 0 0 1-.9-.9v-3.9a.9.9 0 0 1 1.8 0v3.9a.9.9 0 0 1-.9.9Z"></path>',
            'help' => '<path d="M12 3.5a8.5 8.5 0 1 0 0 17 8.5 8.5 0 0 0 0-17Zm0 13.45a1.05 1.05 0 1 1 0-2.1 1.05 1.05 0 0 1 0 2.1Zm.1-3.55a.85.85 0 0 1-.85-.85c0-1.22.82-1.85 1.43-2.31.54-.41.87-.69.87-1.18 0-.69-.6-1.15-1.45-1.15-.8 0-1.37.38-1.79.9a.85.85 0 0 1-1.33-1.06 3.9 3.9 0 0 1 3.12-1.54c1.85 0 3.15 1.15 3.15 2.85 0 1.35-.9 2.03-1.56 2.53-.52.39-.74.59-.74.96a.85.85 0 0 1-.85.85Z"></path>',
            'user' => '<path d="M12 12.25a4.75 4.75 0 1 0 0-9.5 4.75 4.75 0 0 0 0 9.5Z"></path><path d="M4.2 20.1c.96-4.16 3.58-6.35 7.8-6.35s6.84 2.19 7.8 6.35c.16.7-.38 1.4-1.1 1.4H5.3c-.72 0-1.26-.7-1.1-1.4Z"></path>',
            'bets' => '<path d="M5.75 4.75h12.5A1.75 1.75 0 0 1 20 6.5v11a1.75 1.75 0 0 1-1.75 1.75H5.75A1.75 1.75 0 0 1 4 17.5v-11a1.75 1.75 0 0 1 1.75-1.75Zm1.75 4.1a.85.85 0 0 0 0 1.7h7.9a.85.85 0 0 0 0-1.7H7.5Zm0 4.6a.85.85 0 0 0 0 1.7h4.85a.85.85 0 0 0 0-1.7H7.5Zm9.1.1a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z"></path>',
            'vip' => '<path d="M12 3.75a1.2 1.2 0 0 1 .97.49l1.82 2.48 2.94-.94a1.2 1.2 0 0 1 1.5 1.5l-.94 2.94 2.48 1.82a1.2 1.2 0 0 1 0 1.94l-2.48 1.82.94 2.94a1.2 1.2 0 0 1-1.5 1.5l-2.94-.94-1.82 2.48a1.2 1.2 0 0 1-1.94 0L9.21 19.3l-2.94.94a1.2 1.2 0 0 1-1.5-1.5l.94-2.94-2.48-1.82a1.2 1.2 0 0 1 0-1.94l2.48-1.82-.94-2.94a1.2 1.2 0 0 1 1.5-1.5l2.94.94 1.82-2.48a1.2 1.2 0 0 1 .97-.49Zm-2.7 6.1 1.2 4.73a.85.85 0 0 0 .82.64h1.36a.85.85 0 0 0 .82-.64l1.2-4.73a.8.8 0 0 0-1.33-.76L12 10.43l-1.37-1.34a.8.8 0 0 0-1.33.76Z"></path>',
            'settings' => '<path d="M13.2 3.4a1.2 1.2 0 0 0-2.4 0l-.14 1.26a7.23 7.23 0 0 0-1.15.48l-1-.78a1.2 1.2 0 0 0-1.7 1.7l.78 1c-.18.37-.34.75-.47 1.15L5.85 8.35a1.2 1.2 0 0 0 0 2.4l1.26.14c.13.4.29.78.47 1.15l-.78 1a1.2 1.2 0 0 0 1.7 1.7l1-.78c.37.18.75.34 1.15.48l.14 1.26a1.2 1.2 0 0 0 2.4 0l.14-1.26c.4-.14.78-.3 1.15-.48l1 .78a1.2 1.2 0 0 0 1.7-1.7l-.78-1c.18-.37.34-.75.48-1.15l1.26-.14a1.2 1.2 0 0 0 0-2.4l-1.26-.14a7.23 7.23 0 0 0-.48-1.15l.78-1a1.2 1.2 0 0 0-1.7-1.7l-1 .78a7.23 7.23 0 0 0-1.15-.48L13.2 3.4ZM12 8.75a3.25 3.25 0 1 1 0 6.5 3.25 3.25 0 0 1 0-6.5Z"></path>',
            'logout' => '<path d="M5.75 4.25h6.1A1.75 1.75 0 0 1 13.6 6v1.25a.9.9 0 0 1-1.8 0V6.05H5.95v11.9h5.85v-1.2a.9.9 0 1 1 1.8 0V18a1.75 1.75 0 0 1-1.75 1.75h-6.1A1.75 1.75 0 0 1 4 18V6a1.75 1.75 0 0 1 1.75-1.75Z"></path><path d="M16.78 8.92a.9.9 0 0 1 1.27 0l2.35 2.35a.9.9 0 0 1 0 1.27l-2.35 2.35a.9.9 0 0 1-1.27-1.27l.81-.82h-6.64a.9.9 0 1 1 0-1.8h6.64l-.81-.81a.9.9 0 0 1 0-1.27Z"></path>',
            'coins' => '<path d="M12 4.25c3.31 0 6 1.12 6 2.5s-2.69 2.5-6 2.5-6-1.12-6-2.5 2.69-2.5 6-2.5Zm-6 4.5c1.13 1.1 3.42 1.75 6 1.75s4.87-.65 6-1.75v1.5c0 1.38-2.69 2.5-6 2.5s-6-1.12-6-2.5v-1.5Zm0 3.5c1.13 1.1 3.42 1.75 6 1.75s4.87-.65 6-1.75v1.5c0 1.38-2.69 2.5-6 2.5s-6-1.12-6-2.5v-1.5Zm0 3.5c1.13 1.1 3.42 1.75 6 1.75s4.87-.65 6-1.75v1.5c0 1.38-2.69 2.5-6 2.5s-6-1.12-6-2.5v-1.5Z"></path>',
            'wallet' => '<path d="M5.75 5h10.5A2.75 2.75 0 0 1 19 7.75v1H7.2a2.1 2.1 0 0 0 0 4.2H19v3.3A2.75 2.75 0 0 1 16.25 19H5.75A2.75 2.75 0 0 1 3 16.25v-8.5A2.75 2.75 0 0 1 5.75 5Zm1.45 5.35H20a1 1 0 0 1 1 1v1.05a1 1 0 0 1-1 1H7.2a1.52 1.52 0 0 1 0-3.05Zm10.6 1.52a.85.85 0 1 0 0 .01v-.01Z"></path>',
            'trend' => '<path d="M5 17.75a1 1 0 0 1-1-1V7.25a1 1 0 0 1 2 0v7.5h13a1 1 0 1 1 0 2H5Zm13.7-9.96a.9.9 0 0 1 .3.67v3.35a.9.9 0 1 1-1.8 0v-1.17l-3.22 3.22a1 1 0 0 1-1.35.06l-2.15-1.74-2.1 2.1a.9.9 0 1 1-1.27-1.27l2.67-2.67a1 1 0 0 1 1.34-.06l2.15 1.74 2.65-2.66h-1.17a.9.9 0 0 1 0-1.8h3.3a.9.9 0 0 1 .65.23Z"></path>',
        ];

        $path = $icons[$name] ?? $icons['help'];
        $classAttr = trim('elite-ui-icon ' . $class);

        return '<svg class="' . htmlspecialchars($classAttr, ENT_QUOTES, 'UTF-8') . '" viewBox="0 0 24 24" aria-hidden="true">' . $path . '</svg>';
    }
}

?>
<aside class="sidebar" id="side" aria-hidden="true">
    <div class="sidebar-top">
        <button class="toggle" id="toggle" aria-label="Toggle navigation" aria-expanded="false">
            <span class="burger-icon" aria-hidden="true">
                <span></span>
                <span></span>
                <span></span>
            </span>
        </button>
        <a href="<?php echo elite_url('pages/games.php'); ?>" class="sidebar-games-button" <?php echo $activePage === 'games' ? 'aria-current="page"' : ''; ?>>Games</a>
    </div>
    <nav class="navigation">
        <a href="<?php echo elite_url('index.php'); ?>" class="item" <?php echo $activePage === 'home' ? 'id="active"' : ''; ?>><span class="icon"><?php echo elite_icon('home'); ?></span><span class="text">Home</span></a>
        <a href="<?php echo elite_url('pages/favorites.php'); ?>" class="item" <?php echo $activePage === 'favourites' ? 'id="active"' : ''; ?>><span class="icon"><?php echo elite_icon('heart'); ?></span><span class="text">Favourites</span></a>
        <a href="<?php echo elite_url('pages/recent.php'); ?>" class="item" <?php echo $activePage === 'recent' ? 'id="active"' : ''; ?>><span class="icon"><?php echo elite_icon('recent'); ?></span><span class="text">Recent</span></a>

    </nav>
</aside>

<div class="app-content sidebar-closed" id="appContent">
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
            <?php if ($is_logged_in): ?>
            <div class="group" role="group" aria-label="Profile and notifications">
                <button class="button icon notif header-action" id="notif" aria-label="Notifications">
                    <svg class="header-icon header-icon-filled" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 22a2.75 2.75 0 0 0 2.68-2.13H9.32A2.75 2.75 0 0 0 12 22Z"></path>
                        <path d="M19.45 16.25c-.93-.98-1.45-2.35-1.45-3.83V9.75a6 6 0 0 0-12 0v2.67c0 1.48-.52 2.85-1.45 3.83-.7.74-.18 1.95.84 1.95h13.22c1.02 0 1.54-1.21.84-1.95Z"></path>
                    </svg>
                    <span class="badge" id="badge"><?php echo $notification_count; ?></span>
                </button>
                <div class="profile-menu-wrap" id="profileMenuWrap">
                    <button class="button icon header-action" id="prof" type="button" aria-label="Profile" aria-haspopup="true" aria-expanded="false">
                        <svg class="header-icon header-icon-filled" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 12.25a4.75 4.75 0 1 0 0-9.5 4.75 4.75 0 0 0 0 9.5Z"></path>
                            <path d="M4.2 20.1c.96-4.16 3.58-6.35 7.8-6.35s6.84 2.19 7.8 6.35c.16.7-.38 1.4-1.1 1.4H5.3c-.72 0-1.26-.7-1.1-1.4Z"></path>
                        </svg>
                    </button>
                    <div class="profile-menu" id="profileMenu" aria-hidden="true">
                        <button class="profile-menu-item" type="button" data-account-open="profile"><span class="profile-menu-icon"><?php echo elite_icon('user'); ?></span><span>Profile</span></button>
                        <button class="profile-menu-item" type="button" data-account-open="bets"><span class="profile-menu-icon"><?php echo elite_icon('bets'); ?></span><span>Bets</span></button>
                        <button class="profile-menu-item" type="button" data-account-open="vip"><span class="profile-menu-icon"><?php echo elite_icon('vip'); ?></span><span>VIP</span></button>
                        <button class="profile-menu-item" type="button" data-account-open="settings"><span class="profile-menu-icon"><?php echo elite_icon('settings'); ?></span><span>Settings</span></button>
                        <button class="profile-menu-item logout" id="logoutBtn" type="button"><span class="profile-menu-icon"><?php echo elite_icon('logout'); ?></span><span>Logout</span></button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php if (!$is_logged_in): ?>
                <button class="auth-button auth-login" id="loginBtn" type="button">
                    <span class="auth-text">Login</span>
                </button>
                <button class="auth-button auth-register" id="registerBtn" type="button">
                    <span class="auth-text">Register</span>
                </button>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if (!$is_logged_in): ?>
<div class="login-modal" id="loginModal" style="display: none;" aria-hidden="true">
    <div class="login-container" role="dialog" aria-modal="true" aria-labelledby="authModalTitle">
        <button class="login-close" id="closeLogin" type="button" aria-label="Close login">&times;</button>
        <div class="login-tabs" role="tablist" aria-label="Account access">
            <button class="login-tab active" type="button" data-tab="login" role="tab" aria-selected="true">Login</button>
            <button class="login-tab" type="button" data-tab="register" role="tab" aria-selected="false">Register</button>
        </div>

        <form id="loginForm" class="login-form active">
            <h2 id="authModalTitle">Login to your Account</h2>
            <div class="form-group">
                <input type="text" placeholder="Username" name="username" autocomplete="username" required>
            </div>
            <div class="form-group">
                <input type="password" placeholder="Password" name="password" autocomplete="current-password" required>
            </div>
            <button type="submit" class="submit-btn">Login</button>
            <p class="form-message" id="loginMessage"></p>
        </form>

        <form id="registerForm" class="login-form">
            <h2>Create an Account</h2>
            <div class="form-group">
                <input type="text" placeholder="Username" name="username" autocomplete="username" required>
            </div>
            <div class="form-group">
                <input type="email" placeholder="Email" name="email" autocomplete="email" required>
            </div>
            <div class="form-group">
                <input type="password" placeholder="Password" name="password" autocomplete="new-password" required>
            </div>
            <div class="form-group">
                <input type="password" placeholder="Confirm Password" name="confirm_password" autocomplete="new-password" required>
            </div>
            <button type="submit" class="submit-btn">Register</button>
            <p class="form-message" id="registerMessage"></p>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($is_logged_in): ?>
<div class="login-modal account-modal" id="accountModal" style="display: none;" aria-hidden="true">
    <div class="login-container account-modal-container" role="dialog" aria-modal="true" aria-labelledby="accountModalTitle">
        <button class="login-close account-modal-close" id="closeAccountModal" type="button" aria-label="Close account modal">&times;</button>

        <div class="account-modal-head">
            <h2 id="accountModalTitle">Profile</h2>
            <p id="accountModalSubtitle">Elite account overview</p>
        </div>

        <div class="login-tabs account-modal-tabs" role="tablist" aria-label="Account sections">
            <button class="login-tab account-tab active" type="button" data-account-tab="profile" role="tab" aria-selected="true"><?php echo elite_icon('user', 'account-tab-icon'); ?><span>Profile</span></button>
            <button class="login-tab account-tab" type="button" data-account-tab="bets" role="tab" aria-selected="false"><?php echo elite_icon('bets', 'account-tab-icon'); ?><span>Bets</span></button>
            <button class="login-tab account-tab" type="button" data-account-tab="vip" role="tab" aria-selected="false"><?php echo elite_icon('vip', 'account-tab-icon'); ?><span>VIP</span></button>
            <button class="login-tab account-tab" type="button" data-account-tab="settings" role="tab" aria-selected="false"><?php echo elite_icon('settings', 'account-tab-icon'); ?><span>Settings</span></button>
        </div>

        <div class="account-panel active" data-account-panel="profile">
            <div class="account-profile-main">
                <div class="account-avatar" data-account-field="avatar">E</div>
                <div class="account-profile-text">
                    <h3 data-account-field="username">Player</h3>
                    <p data-account-field="member_since">Member since</p>
                </div>
                <div class="account-profile-balance">
                    <strong data-account-field="rank_remaining">$0.00</strong>
                    <span>remaining</span>
                </div>
            </div>

            <div class="account-progress-track" aria-label="VIP rank progress">
                <span data-account-field="rank_progress" style="width: 0%"></span>
            </div>

            <div class="account-rank-row">
                <div class="account-rank rank-unranked">
                    <span class="progress-rank-icon"></span>
                    <span data-account-field="current_rank">Unranked</span>
                </div>
                <div class="account-rank rank-bronze">
                    <span class="progress-rank-icon"></span>
                    <span data-account-field="next_rank">Bronze I</span>
                </div>
            </div>

            <div class="account-stat-grid">
                <div class="account-stat-card">
                    <span class="account-stat-icon is-purple"><?php echo elite_icon('bets'); ?></span>
                    <span>Total Bets</span>
                    <strong data-account-field="total_bets">0</strong>
                </div>
                <div class="account-stat-card">
                    <span class="account-stat-icon is-green"><?php echo elite_icon('coins'); ?></span>
                    <span>Total Wagered</span>
                    <strong data-account-field="total_wagered">$0.00</strong>
                </div>
                <div class="account-stat-card">
                    <span class="account-stat-icon is-blue"><?php echo elite_icon('wallet'); ?></span>
                    <span>Balance</span>
                    <strong data-account-field="balance">$0.00</strong>
                </div>
                <div class="account-stat-card">
                    <span class="account-stat-icon is-gold"><?php echo elite_icon('trend'); ?></span>
                    <span>Net Result</span>
                    <strong data-account-field="net_result">$0.00</strong>
                </div>
            </div>
        </div>

        <div class="account-panel" data-account-panel="bets">
            <div class="account-section-title">
                <h3>Latest Bets</h3>
                <span data-account-field="bets_count">0 bets</span>
            </div>
            <div class="account-bets-list" data-account-bets-list>
                <div class="account-empty-state">Loading bets...</div>
            </div>
        </div>

        <div class="account-panel" data-account-panel="vip">
            <div class="account-vip-hero">
                <span>Elite VIP</span>
                <h3>$1,000.00 every hour</h3>
                <p>Plus 8% rakeback on losses.</p>
            </div>

            <div class="account-vip-grid">
                <div class="account-vip-card">
                    <span>Hourly Bonus</span>
                    <strong data-account-field="hourly_amount">$1,000.00</strong>
                    <small data-account-field="hourly_status">Ready to claim</small>
                    <button class="submit-btn account-action-btn" type="button" data-vip-claim="hourly">Claim Bonus</button>
                </div>
                <div class="account-vip-card">
                    <span>Rakeback</span>
                    <strong data-account-field="rakeback_available">$0.00</strong>
                    <small><span data-account-field="rakeback_rate">8%</span> returned from losses</small>
                    <button class="submit-btn account-action-btn is-secondary" type="button" data-vip-claim="rakeback">Claim Rakeback</button>
                </div>
            </div>

            <p class="form-message account-message" data-account-message></p>
        </div>

        <div class="account-panel" data-account-panel="settings">
            <div class="account-settings-grid">
                <label class="account-field">
                    <span>Username</span>
                    <input type="text" data-settings-field="username" readonly value="Player">
                </label>
                <label class="account-field">
                    <span>Email</span>
                    <input type="email" data-settings-field="email" readonly value="">
                </label>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<nav class="mobile-bottom-nav" aria-label="Mobile navigation">
    <a href="<?php echo elite_url('index.php'); ?>" class="mobile-nav-item" <?php echo $activePage === 'home' ? 'aria-current="page"' : ''; ?>>
        <span class="mobile-nav-icon" aria-hidden="true">
            <?php echo elite_icon('home'); ?>
        </span>
        <span>Home</span>
    </a>
    <a href="<?php echo elite_url('pages/games.php'); ?>" class="mobile-nav-item" <?php echo in_array($activePage, ['games', 'blackjack', 'mines', 'plinko', 'dice'], true) ? 'aria-current="page"' : ''; ?>>
        <span class="mobile-nav-icon" aria-hidden="true">
            <?php echo elite_icon('games'); ?>
        </span>
        <span>Games</span>
    </a>
    <a href="<?php echo elite_url('pages/favorites.php'); ?>" class="mobile-nav-item" <?php echo $activePage === 'favourites' ? 'aria-current="page"' : ''; ?>>
        <span class="mobile-nav-icon" aria-hidden="true">
            <?php echo elite_icon('heart'); ?>
        </span>
        <span>Saved</span>
    </a>
    <a href="<?php echo elite_url('pages/recent.php'); ?>" class="mobile-nav-item" <?php echo $activePage === 'recent' ? 'aria-current="page"' : ''; ?>>
        <span class="mobile-nav-icon" aria-hidden="true">
            <?php echo elite_icon('recent'); ?>
        </span>
        <span>Recent</span>
    </a>
</nav>

<script src="<?php echo elite_url('assets/js/header_sidebar.js'); ?>" data-login-url="<?php echo elite_url('api/login.php'); ?>" data-profile-url="<?php echo elite_url('api/profile.php'); ?>"></script>
