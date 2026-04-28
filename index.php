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

$activePage = 'home';
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

    <?php include 'header_sidebar.php'; ?>

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
        <?php else: ?>
        <div class="login-prompt">
            <div class="login-prompt-left">
                <h2>...<br>...</h2>
                <div class="login-prompt-buttons">
                    <button class="btn-register" id="loginPromptBtn">Register</button>
                    <button class="btn-login" id="loginPromptBtnLogin">Login</button>
                </div>
            </div>
            <div class="login-prompt-right">
                <button class="game-category-btn">🎮 Games</button>
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
                <a href="blackjack.php" class="game-card" style="text-decoration:none;">
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

            <button class="carousel-btn next" id="nextBtn">&#10095;</button>
        </div>
    </section>

    <script>
        const loginModal = document.getElementById('loginModal');
        const loginBtn = document.getElementById('loginBtn');
        const closeLogin = document.getElementById('closeLogin');
        const logoutBtn = document.getElementById('logoutBtn');
        const loginPromptBtn = document.getElementById('loginPromptBtn');
        const loginPromptBtnLogin = document.getElementById('loginPromptBtnLogin');
        const loginTabs = document.querySelectorAll('.login-tab');
        const loginForms = document.querySelectorAll('.login-form');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');

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

    if (!progressBar || !progressText) return;

    const percent = Math.min(100, Math.round((current / total) * 100));

    progressBar.style.width = percent + '%';
    progressText.textContent =
        `$${Number(current).toFixed(2)} / $${Number(total).toFixed(2)} Wagered (${percent}%)`;
}

document.addEventListener('DOMContentLoaded', function () {
    setProgressBar(<?php echo (float)$total_wagered; ?>, 1000);
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
    </script>
</body>
</html>
