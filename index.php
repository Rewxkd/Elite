<?php
session_start();
include 'db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
$username = '';
$balance = 0;
$total_wagered = 0;
$notification_count = 0;
$notifications = array();
$is_logged_in = false;

if ($user_id) {
    $is_logged_in = true;
    $user_query = $conn->query("SELECT username FROM users WHERE user_id = $user_id");
    if ($user_query && $user_query->num_rows > 0) {
        $user = $user_query->fetch_assoc();
        $username = $user['username'];
    }

    $wallet_query = $conn->query("SELECT balance, total_wagered FROM wallets WHERE user_id = $user_id");
    if ($wallet_query && $wallet_query->num_rows > 0) {
        $wallet = $wallet_query->fetch_assoc();
        $balance = $wallet['balance'];
        $total_wagered = $wallet['total_wagered'];
    }

    $notif_count_query = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = FALSE");
    if ($notif_count_query && $notif_count_query->num_rows > 0) {
        $notif = $notif_count_query->fetch_assoc();
        $notification_count = $notif['count'];
    }

    $notifications_query = $conn->query("SELECT notification_id, message, is_read, created_at FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 10");
    if ($notifications_query && $notifications_query->num_rows > 0) {
        while ($notif = $notifications_query->fetch_assoc()) {
            $notifications[] = $notif;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elite</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-modal" id="loginModal" style="display: <?php echo $is_logged_in ? 'none' : 'flex'; ?>;">
        <div class="login-container">
            <button class="login-close" id="closeLogin" aria-label="Close login">&times;</button>
            <div class="login-tabs">
                <button class="login-tab active" data-tab="login">Login</button>
                <button class="login-tab" data-tab="register">Register</button>
            </div>

            <form id="loginForm" class="login-form active">
                <h2>Login In to your Account</h2>
                <div class="form-group">
                    <input type="text" placeholder="Username" name="username" required>
                </div>
                <div class="form-group">
                    <input type="password" placeholder="Password" name="password" required>
                </div>
                <button type="submit" class="submit-btn">Login</button>
                <p class="form-message" id="loginMessage"></p>
            </form>

            <form id="registerForm" class="login-form">
                <h2>Create an Account</h2>
                <div class="form-group">
                    <input type="text" placeholder="Username" name="username" required>
                </div>
                <div class="form-group">
                    <input type="email" placeholder="Email" name="email" required>
                </div>
                <div class="form-group">
                    <input type="password" placeholder="Password" name="password" required>
                </div>
                <div class="form-group">
                    <input type="password" placeholder="Confirm Password" name="confirm_password" required>
                </div>
                <button type="submit" class="submit-btn">Register</button>
                <p class="form-message" id="registerMessage"></p>
            </form>
        </div>
    </div>
    <aside class="sidebar" id="side" aria-hidden="true">
        <button class="toggle" id="toggle" aria-label="Toggle navigation" aria-expanded="false">☰</button>
        <nav class="navigation">
            <a href="#" class="item" id="active"><span class="icon">🏠</span><span class="text">Home</span></a>
            <a href="#" class="item"><span class="icon">❤️</span><span class="text">Favourites</span></a>
            <a href="#" class="item"><span class="icon">🕒</span><span class="text">Recent</span></a>
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

    <main class="container">
        <?php if ($is_logged_in): ?>
        <div class="container-box">
            <h1>Welcome <span style="color: #90beff;"><?php echo htmlspecialchars($username); ?></span>!</h1>
        </div>
        <div class="container-progress">
            <div class="progress-top"><p>Your Progress</p></div>
            <div class="progress-bottom">
                <p id="progressText">$<?php echo number_format($total_wagered, 2); ?> / $1000.00 Wagered</p>
                <br>
                <div class="progress-bar-container">
                    <div class="progress-bar" id="progressBar" style="width: 0%"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <section class="games-carousel">
        <div class="games-header">
            <h2>Games</h2>
        </div>
        <div class="games-container">
            <button class="carousel-btn prev" id="prevBtn">&#10094;</button>
            <div class="games-row" id="gamesRow">
                <?php for ($i = 1; $i <= 20; $i++): ?>
                    <div class="game-card">
                        <div class="game-img"></div>
                        <div class="game-title">Game <?php echo $i; ?></div>
                    </div>
                <?php endfor; ?>
            </div>
            <button class="carousel-btn next" id="nextBtn">&#10095;</button>
        </div>
    </section>

    <script>
        const loginModal = document.getElementById('loginModal');
        const loginBtn = document.getElementById('loginBtn');
        const closeLogin = document.getElementById('closeLogin');
        const logoutBtn = document.getElementById('logoutBtn');
        const loginTabs = document.querySelectorAll('.login-tab');
        const loginForms = document.querySelectorAll('.login-form');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');

        if (loginBtn) {
            loginBtn.addEventListener('click', () => {
                loginModal.style.display = 'flex';
            });
        }

        if (closeLogin) {
            closeLogin.addEventListener('click', () => {
                loginModal.style.display = 'none';
            });
        }

        if (loginModal) {
            loginModal.addEventListener('click', (e) => {
                if (e.target === loginModal) {
                    loginModal.style.display = 'none';
                }
            });
        }

        loginTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabName = tab.getAttribute('data-tab');
                
                loginTabs.forEach(t => t.classList.remove('active'));
                loginForms.forEach(f => f.classList.remove('active'));
                
                tab.classList.add('active');
                document.getElementById(tabName + 'Form').classList.add('active');
            });
        });

        function setProgressBar(current, total) {
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            if (!progressBar || !progressText) {
                return;
            }
            const percent = Math.min(100, Math.round((current / total) * 100));
            progressBar.style.width = percent + '%';
            progressText.textContent = `$${current.toFixed(2)} / $${total.toFixed(2)} Wagered (${percent}%)`;
        }

        if (document.getElementById('progressBar') && document.getElementById('progressText')) {
            setProgressBar(<?php echo $total_wagered; ?>, 1000);
        }

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(loginForm);
            formData.append('action', 'login');

            const response = await fetch('login.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            const messageEl = document.getElementById('loginMessage');
            
            if (data.success) {
                messageEl.textContent = 'Login successful!';
                messageEl.style.color = '#00ff00';
                setTimeout(() => location.reload(), 1500);
            } else {
                messageEl.textContent = data.message;
                messageEl.style.color = '#ff0000';
            }
        });

        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(registerForm);
            formData.append('action', 'register');

            const response = await fetch('login.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            const messageEl = document.getElementById('registerMessage');
            
            if (data.success) {
                messageEl.textContent = 'Registration successful!';
                messageEl.style.color = '#00ff00';
                setTimeout(() => location.reload(), 1500);
            } else {
                messageEl.textContent = data.message;
                messageEl.style.color = '#ff0000';
            }
        });

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

        (function(){
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const gamesRow = document.getElementById('gamesRow');
            if (prevBtn && nextBtn && gamesRow) {
                const scrollAmount = () => {
                    const card = gamesRow.querySelector('.game-card');
                    if (!card) return 400;
                    return (card.offsetWidth + 16) * 5;
                };
                prevBtn.addEventListener('click', () => {
                    gamesRow.scrollBy({ left: -scrollAmount(), behavior: 'smooth' });
                });
                nextBtn.addEventListener('click', () => {
                    gamesRow.scrollBy({ left: scrollAmount(), behavior: 'smooth' });
                });
            }
            const btn = document.getElementById('toggle');
            const sidebar = document.getElementById('side');
            const menuBtns = document.querySelectorAll('.dropdown-button');

            if (btn) btn.addEventListener('click', function(){
                const isOpen = document.body.classList.toggle('open');
                sidebar.setAttribute('aria-hidden', (!isOpen).toString());
                btn.setAttribute('aria-expanded', isOpen.toString());
            });

            menuBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    if (!document.body.classList.contains('open')) {
                        document.body.classList.add('open');
                        sidebar.setAttribute('aria-hidden', 'false');
                        btn.setAttribute('aria-expanded', 'true');
                        setTimeout(() => {
                            this.parentElement.querySelector('.dropdown-items').setAttribute('aria-hidden', 'false');
                        }, 0);
                        return;
                    }
                    
                    e.stopPropagation();
                    const dropdown = this.parentElement.querySelector('.dropdown-items');
                    const isOpen = dropdown.getAttribute('aria-hidden') === 'true';
                    
                    menuBtns.forEach(otherBtn => {
                        if (otherBtn !== this) {
                            otherBtn.parentElement.querySelector('.dropdown-items').setAttribute('aria-hidden', 'true');
                            otherBtn.setAttribute('aria-expanded', 'false');
                        }
                    });
                    
                    dropdown.setAttribute('aria-hidden', (!isOpen).toString());
                    this.setAttribute('aria-expanded', isOpen.toString());
                });
            });
            
            document.addEventListener('click', function(e) {
                menuBtns.forEach(btn => {
                    if (!btn.contains(e.target)) {
                        btn.parentElement.querySelector('.dropdown-items').setAttribute('aria-hidden', 'true');
                        btn.setAttribute('aria-expanded', 'false');
                    }
                });
            });

            btn.addEventListener('click', function() {
                const isSidebarOpen = document.body.classList.contains('open');
                document.querySelectorAll('.dropdown-items').forEach(dropdown => {
                    dropdown.setAttribute('aria-hidden', (!isSidebarOpen).toString());
                });
            });
        })();
    </script>
</body>
</html>