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
    <button class="toggle" id="toggle" aria-label="Toggle navigation" aria-expanded="false">☰</button>
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
            <a href="<?php echo elite_url('index.php'); ?>"><img src="<?php echo elite_url('assets/img/Elite-logo.png'); ?>" alt=""></a>
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

<script>
    const loginModal = document.getElementById('loginModal');
    const loginBtn = document.getElementById('loginBtn');
    const closeLogin = document.getElementById('closeLogin');
    const logoutBtn = document.getElementById('logoutBtn');
    const profileBtn = document.getElementById('prof');
    const profileMenuWrap = document.getElementById('profileMenuWrap');
    const profileMenu = document.getElementById('profileMenu');
    const toggleBtn = document.getElementById('toggle');
    const sidebar = document.getElementById('side');
    const menuBtns = document.querySelectorAll('.dropdown-button');

    if (loginBtn) {
        loginBtn.addEventListener('click', () => {
            const loginTab = document.querySelector('.login-tab[data-tab="login"]');
            if (loginTab) loginTab.click();
            if (loginModal) loginModal.style.display = 'flex';
        });
    }

    if (closeLogin) {
        closeLogin.addEventListener('click', () => {
            if (loginModal) loginModal.style.display = 'none';
        });
    }

    if (loginModal) {
        loginModal.addEventListener('click', (e) => {
            if (e.target === loginModal) {
                loginModal.style.display = 'none';
            }
        });
    }

    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            const formData = new FormData();
            formData.append('action', 'logout');

            await fetch('<?php echo elite_url('api/login.php'); ?>', {
                method: 'POST',
                body: formData
            });

            location.reload();
        });
    }

    if (profileBtn && profileMenu) {
        profileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = profileMenu.getAttribute('aria-hidden') === 'false';
            profileMenu.setAttribute('aria-hidden', isOpen.toString());
            profileBtn.setAttribute('aria-expanded', (!isOpen).toString());
        });
    }

    if (profileMenuWrap) {
        profileMenuWrap.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            const isOpen = document.body.classList.toggle('open');
            if (sidebar) sidebar.setAttribute('aria-hidden', (!isOpen).toString());
            this.setAttribute('aria-expanded', isOpen.toString());
        });
    }

    menuBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!document.body.classList.contains('open')) {
                document.body.classList.add('open');
                if (sidebar) sidebar.setAttribute('aria-hidden', 'false');
                this.setAttribute('aria-expanded', 'true');
                setTimeout(() => {
                    const dropdown = this.parentElement.querySelector('.dropdown-items');
                    if (dropdown) dropdown.setAttribute('aria-hidden', 'false');
                }, 0);
                return;
            }

            e.stopPropagation();
            const dropdown = this.parentElement.querySelector('.dropdown-items');
            const isOpen = dropdown && dropdown.getAttribute('aria-hidden') === 'true';

            menuBtns.forEach(otherBtn => {
                if (otherBtn !== this) {
                    const otherDropdown = otherBtn.parentElement.querySelector('.dropdown-items');
                    if (otherDropdown) otherDropdown.setAttribute('aria-hidden', 'true');
                    otherBtn.setAttribute('aria-expanded', 'false');
                }
            });

            if (dropdown) dropdown.setAttribute('aria-hidden', (!isOpen).toString());
            this.setAttribute('aria-expanded', isOpen.toString());
        });
    });

    document.addEventListener('click', function(e) {
        if (profileMenu && profileBtn && profileMenu.getAttribute('aria-hidden') === 'false') {
            profileMenu.setAttribute('aria-hidden', 'true');
            profileBtn.setAttribute('aria-expanded', 'false');
        }

        menuBtns.forEach(btn => {
            if (!btn.contains(e.target)) {
                const dropdown = btn.parentElement.querySelector('.dropdown-items');
                if (dropdown) dropdown.setAttribute('aria-hidden', 'true');
                btn.setAttribute('aria-expanded', 'false');
            }
        });
    });
</script>

