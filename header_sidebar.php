<?php $activePage = $activePage ?? ''; ?>
<aside class="sidebar" id="side" aria-hidden="true">
    <button class="toggle" id="toggle" aria-label="Toggle navigation" aria-expanded="false">☰</button>
    <nav class="navigation">
        <a href="index.php" class="item" <?php echo $activePage === 'home' ? 'id="active"' : ''; ?>><span class="icon">🏠</span><span class="text">Home</span></a>
        <a href="#" class="item" <?php echo $activePage === 'favourites' ? 'id="active"' : ''; ?>><span class="icon">❤️</span><span class="text">Favourites</span></a>
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
            <img src="Elite-logo.png" alt="">
        </div>
        <?php if ($is_logged_in): ?>
        <div class="balance">
            <span class="balance-amount">$<?php echo number_format($balance, 2); ?></span>
        </div>
        <?php endif; ?>
        <div class="header-buttons">
            <button class="button icon" id="search" aria-label="Search"><span>🔍</span></button>
            <div class="group" role="group" aria-label="Profile and notifications">
                <button class="button icon notif" id="notif" aria-label="Notifications"><span>🔔</span><span class="badge" id="badge"><?php echo $notification_count; ?></span></button>
                <?php if ($is_logged_in): ?>
                    <button class="button icon" id="prof" aria-label="Profile"><span>👤</span></button>
                <?php endif; ?>
            </div>
            <?php if ($is_logged_in): ?>
                <button class="auth-button" id="logoutBtn"><span class="auth-icon">🚪</span><span class="auth-text">Logout</span></button>
            <?php else: ?>
                <button class="auth-button" id="loginBtn"><span class="auth-icon">🔐</span><span class="auth-text">Login</span></button>
            <?php endif; ?>
        </div>
    </div>
</header>

<script>
    const loginModal = document.getElementById('loginModal');
    const loginBtn = document.getElementById('loginBtn');
    const closeLogin = document.getElementById('closeLogin');
    const logoutBtn = document.getElementById('logoutBtn');
    const loginPromptBtn = document.getElementById('loginPromptBtn');
    const loginPromptBtnLogin = document.getElementById('loginPromptBtnLogin');
    const toggleBtn = document.getElementById('toggle');
    const sidebar = document.getElementById('side');
    const menuBtns = document.querySelectorAll('.dropdown-button');

    if (loginBtn) {
        loginBtn.addEventListener('click', () => {
            if (loginModal) loginModal.style.display = 'flex';
        });
    }

    if (loginPromptBtn) {
        loginPromptBtn.addEventListener('click', () => {
            if (loginModal) loginModal.style.display = 'flex';
        });
    }

    if (loginPromptBtnLogin) {
        loginPromptBtnLogin.addEventListener('click', () => {
            if (loginModal) {
                loginModal.style.display = 'flex';
                const loginTab = document.querySelector('.login-tab[data-tab="login"]');
                if (loginTab) loginTab.click();
            }
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

            await fetch('login.php', {
                method: 'POST',
                body: formData
            });

            location.reload();
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
        menuBtns.forEach(btn => {
            if (!btn.contains(e.target)) {
                const dropdown = btn.parentElement.querySelector('.dropdown-items');
                if (dropdown) dropdown.setAttribute('aria-hidden', 'true');
                btn.setAttribute('aria-expanded', 'false');
            }
        });
    });
</script>
