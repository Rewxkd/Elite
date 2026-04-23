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
$activePage = 'home';

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
    <title>Main Page - Elite</title>
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
                <button class="game-category-btn">� Games</button>
            </div>
        </div>
        <?php endif; ?>
    </main>


    <script>
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

        document.getElementById('closeLogin').addEventListener('click', () => {
            document.getElementById('loginModal').style.display = 'none';
        });

        document.getElementById('loginPromptBtn').addEventListener('click', () => {
            document.getElementById('loginModal').style.display = 'flex';
            document.querySelector('[data-tab="register"]').click();
        });

        document.getElementById('loginPromptBtnLogin').addEventListener('click', () => {
            document.getElementById('loginModal').style.display = 'flex';
            document.querySelector('[data-tab="login"]').click();
        });


                return card ? card.offsetWidth + 16 : 200; // 16px gap
            };

            });

            });
        }
    </script>
</body>
</html>
