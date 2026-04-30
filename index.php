<?php
session_start();
include 'includes/db_connect.php';

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$username = '';
$balance = 0.00;
$total_wagered = 0.00;
$notification_count = 0;
$is_logged_in = false;
$activePage = 'home';

if ($user_id) {
    $is_logged_in = true;

    $stmt = $conn->prepare('SELECT username FROM users WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $username = $user['username'] ?? '';

    $stmt = $conn->prepare('SELECT balance, total_wagered FROM wallets WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $wallet = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($wallet) {
        $balance = (float)$wallet['balance'];
        $total_wagered = (float)$wallet['total_wagered'];
    }

    $stmt = $conn->prepare('SELECT COUNT(*) AS count FROM notifications WHERE user_id = ? AND is_read = FALSE');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $notif = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $notification_count = (int)($notif['count'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elite</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-modal" id="loginModal" style="display: none;">
        <div class="login-container">
            <button class="login-close" id="closeLogin" aria-label="Close login">&times;</button>
            <div class="login-tabs">
                <button class="login-tab active" type="button" data-tab="login">Login</button>
                <button class="login-tab" type="button" data-tab="register">Register</button>
            </div>

            <form id="loginForm" class="login-form active">
                <h2>Login to your Account</h2>
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

    <?php include 'includes/header_sidebar.php'; ?>

    <main class="container">
        <?php if ($is_logged_in): ?>
            <div class="container-box">
                <h1>Welcome <span style="color: #90beff;"><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></span>!</h1>
            </div>

            <div class="container-progress">
                <div class="progress-top"><p>Your Progress</p></div>
                <div class="progress-bottom">
                    <p id="progressText">$<?php echo number_format($total_wagered, 2); ?> / $1,000.00 Wagered</p>
                    <br>
                    <div class="progress-bar-container">
                        <div class="progress-bar" id="progressBar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="login-prompt">
                <div class="login-prompt-left">
                    <h2>Play casino games<br>and track your progress</h2>
                    <div class="login-prompt-buttons">
                        <button class="btn-register" id="loginPromptBtn" type="button">Register</button>
                        <button class="btn-login" id="loginPromptBtnLogin" type="button">Login</button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <section class="games-carousel">
        <div class="games-header">
            <h2>Games</h2>
            <div class="carousel-controls" aria-label="Games carousel controls">
                <button class="carousel-btn prev" id="prevBtn" type="button" aria-label="Previous games">&#8249;</button>
                <button class="carousel-btn next" id="nextBtn" type="button" aria-label="Next games">&#8250;</button>
            </div>
        </div>
        <div class="games-container">
            <div class="games-row" id="gamesRow">
                <a href="pages/blackjack.php" class="game-card" <?php echo $is_logged_in ? '' : 'data-requires-login="true"'; ?> style="text-decoration:none;">
                    <div class="game-img" style="background: linear-gradient(135deg, #0d123a, #1f2d58); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700;">BJ</div>
                    <div class="game-title">Blackjack</div>
                </a>

                <?php for ($i = 2; $i <= 20; $i++): ?>
                    <div class="game-card">
                        <div class="game-img"></div>
                        <div class="game-title">Game <?php echo $i; ?></div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <script>
        const loginTabs = document.querySelectorAll('.login-tab');
        const loginForms = document.querySelectorAll('.login-form');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const gamesRow = document.getElementById('gamesRow');

        loginTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabName = tab.getAttribute('data-tab');
                loginTabs.forEach(t => t.classList.remove('active'));
                loginForms.forEach(f => f.classList.remove('active'));
                tab.classList.add('active');

                const form = document.getElementById(tabName + 'Form');
                if (form) form.classList.add('active');
            });
        });

        function openAuthModal(tabName = 'login') {
            const tab = document.querySelector(`.login-tab[data-tab="${tabName}"]`);
            const modal = document.getElementById('loginModal');
            if (tab) tab.click();
            if (modal) modal.style.display = 'flex';
        }

        function setProgressBar(current, total) {
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');

            if (!progressBar || !progressText) return;

            const percent = Math.min(100, Math.round((Number(current) / Number(total)) * 100));
            progressBar.style.width = percent + '%';
            progressText.textContent = `$${Number(current).toFixed(2)} / $${Number(total).toFixed(2)} Wagered (${percent}%)`;
        }

        document.addEventListener('DOMContentLoaded', function () {
            setProgressBar(<?php echo json_encode($total_wagered); ?>, 1000);

            const shouldShowLogin = new URLSearchParams(window.location.search).get('login') === '1';
            if (shouldShowLogin) openAuthModal('login');

            const promptRegisterButton = document.getElementById('loginPromptBtn');
            const promptLoginButton = document.getElementById('loginPromptBtnLogin');

            if (promptRegisterButton) {
                promptRegisterButton.addEventListener('click', () => openAuthModal('register'));
            }

            if (promptLoginButton) {
                promptLoginButton.addEventListener('click', () => openAuthModal('login'));
            }
        });

        document.querySelectorAll('[data-requires-login="true"]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                openAuthModal('login');
            });
        });

        if (prevBtn && nextBtn && gamesRow) {
            const scrollAmount = () => {
                const card = gamesRow.querySelector('.game-card');
                return card ? (card.offsetWidth + 16) * 5 : 400;
            };

            const updateCarouselButtons = () => {
                const maxScroll = gamesRow.scrollWidth - gamesRow.clientWidth;
                const atStart = gamesRow.scrollLeft <= 1;
                const atEnd = gamesRow.scrollLeft >= maxScroll - 1;

                prevBtn.disabled = atStart;
                nextBtn.disabled = maxScroll <= 1 || atEnd;
            };

            prevBtn.addEventListener('click', () => {
                gamesRow.scrollBy({ left: -scrollAmount(), behavior: 'smooth' });
            });

            nextBtn.addEventListener('click', () => {
                gamesRow.scrollBy({ left: scrollAmount(), behavior: 'smooth' });
            });

            gamesRow.addEventListener('scroll', updateCarouselButtons);
            window.addEventListener('resize', updateCarouselButtons);
            updateCarouselButtons();
        }

        if (loginForm) {
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(loginForm);
                formData.append('action', 'login');

                const response = await fetch('api/login.php', { method: 'POST', body: formData });
                const data = await response.json();
                const messageEl = document.getElementById('loginMessage');

                messageEl.textContent = data.message || (data.success ? 'Login successful!' : 'Login failed');
                messageEl.style.color = data.success ? '#00ff00' : '#ff6666';

                if (data.success) {
                    setTimeout(() => location.reload(), 700);
                }
            });
        }

        if (registerForm) {
            registerForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(registerForm);
                formData.append('action', 'register');

                const response = await fetch('api/login.php', { method: 'POST', body: formData });
                const data = await response.json();
                const messageEl = document.getElementById('registerMessage');

                messageEl.textContent = data.message || (data.success ? 'Registration successful!' : 'Registration failed');
                messageEl.style.color = data.success ? '#00ff00' : '#ff6666';

                if (data.success) {
                    setTimeout(() => location.reload(), 700);
                }
            });
        }
    </script>
</body>
</html>
