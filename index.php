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
$latest_bets = [];
$game_assets = [
    'blackjack' => [
        'name' => 'Blackjack',
        'href' => 'pages/blackjack.php',
        'image' => 'assets/img/cards-logo.png',
    ],
];
$coming_soon_games = [
    ['name' => 'Mines', 'code' => 'MI'],
    ['name' => 'Plinko', 'code' => 'PL'],
    ['name' => 'Limbo', 'code' => 'LI'],
    ['name' => 'Tower', 'code' => 'TO'],
    ['name' => 'Baccarat', 'code' => 'BA'],
    ['name' => 'Crash', 'code' => 'CR'],
    ['name' => 'Dice', 'code' => 'DI'],
    ['name' => 'Keno', 'code' => 'KE'],
];

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

$latestBetQuery = $conn->query("
    SELECT lb.game_type, lb.game_name, lb.wager_amount, lb.payout_amount, lb.net_result, lb.created_at, u.username
    FROM latest_bets lb
    JOIN users u ON u.user_id = lb.user_id
    ORDER BY lb.created_at DESC
    LIMIT 12
");

if ($latestBetQuery) {
    while ($row = $latestBetQuery->fetch_assoc()) {
        $latest_bets[] = $row;
    }
}

function mask_username($username) {
    $username = trim((string)$username);
    if ($username === '') {
        return 'Hidden';
    }

    return strlen($username) > 6 ? substr($username, 0, 6) . '...' : $username;
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

    <section class="latest-bets-section">
        <div class="latest-bets-header">
            <h2>Latest Bets</h2>
        </div>
        <div class="latest-bets-row">
            <?php if (empty($latest_bets)): ?>
                <div class="latest-bets-empty">No bets yet.</div>
            <?php else: ?>
                <?php foreach ($latest_bets as $bet): ?>
                    <?php
                        $gameType = strtolower($bet['game_type']);
                        $gameAsset = $game_assets[$gameType] ?? null;
                        $gameHref = $gameAsset['href'] ?? ('pages/' . $gameType . '.php');
                        $amount = (float)$bet['wager_amount'];
                    ?>
                    <article class="latest-bet-card">
                        <a class="latest-bet-game <?php echo $gameAsset ? 'blackjack-game-img' : ''; ?>" href="<?php echo htmlspecialchars($gameHref, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php if ($gameAsset): ?>
                                <img src="<?php echo htmlspecialchars($gameAsset['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($gameAsset['name'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php else: ?>
                                <span class="latest-bet-game-code"><?php echo strtoupper(substr($bet['game_name'], 0, 2)); ?></span>
                                <span class="latest-bet-game-name"><?php echo htmlspecialchars($bet['game_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="latest-bet-player">
                            <span><?php echo htmlspecialchars(mask_username($bet['username']), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="latest-bet-amount">$<?php echo number_format($amount, 2); ?></div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="games-carousel">
        <div class="games-header">
            <div class="games-title-row">
                <h2>Games</h2>
                <a class="games-all-link" href="pages/games.php">All games</a>
            </div>
            <div class="carousel-controls" aria-label="Games carousel controls">
                <button class="carousel-btn prev" id="prevBtn" type="button" aria-label="Previous games">&#8249;</button>
                <button class="carousel-btn next" id="nextBtn" type="button" aria-label="Next games">&#8250;</button>
            </div>
        </div>
        <div class="games-container">
            <div class="games-row" id="gamesRow">
                <a href="<?php echo htmlspecialchars($game_assets['blackjack']['href'], ENT_QUOTES, 'UTF-8'); ?>" class="game-card" <?php echo $is_logged_in ? '' : 'data-requires-login="true"'; ?>>
                    <div class="game-img blackjack-game-img"><img src="<?php echo htmlspecialchars($game_assets['blackjack']['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($game_assets['blackjack']['name'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div class="game-card-body">
                        <div class="game-title"><?php echo htmlspecialchars($game_assets['blackjack']['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <span class="game-status is-live">Play</span>
                    </div>
                </a>

                <?php foreach ($coming_soon_games as $game): ?>
                    <article class="game-card is-coming-soon">
                        <div class="game-img">
                            <span><?php echo htmlspecialchars($game['code'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="game-card-body">
                            <div class="game-title"><?php echo htmlspecialchars($game['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <span class="game-status">Soon</span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/index.js" data-total-wagered="<?php echo htmlspecialchars((string)$total_wagered, ENT_QUOTES, 'UTF-8'); ?>" data-login-url="api/login.php"></script>
</body>
</html>
