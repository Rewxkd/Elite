<?php
session_start();
include '../includes/db_connect.php';

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$is_logged_in = false;
$activePage = 'games';
$balance = 0.00;
$total_wagered = 0.00;
$notification_count = 0;

$games = [
    [
        'name' => 'Blackjack',
        'href' => 'blackjack.php',
        'image' => '../assets/img/blackjack-logo.png',
        'tagline' => 'Beat the dealer and keep your hot streak alive.',
        'status' => 'Play',
        'is_live' => true,
    ],
    [
        'name' => 'Mines',
        'href' => 'mines.php',
        'image' => '../assets/img/mines-logo.png',
        'tagline' => 'Reveal safe tiles and cash out before the blast.',
        'code' => 'MI',
        'status' => 'Play',
        'is_live' => true,
    ],
    [
        'name' => 'Plinko',
        'href' => 'plinko.php',
        'image' => '../assets/img/plinko-logo.png',
        'tagline' => 'Drop the ball and watch it bounce toward a multiplier.',
        'code' => 'PL',
        'status' => 'Play',
        'is_live' => true,
    ],
    [
        'name' => 'Dice',
        'href' => 'dice.php',
        'image' => '../assets/img/dice-logo.png',
        'tagline' => 'Pick your odds and roll for a clean hit.',
        'code' => 'DI',
        'status' => 'Play',
        'is_live' => true,
    ],
    ['name' => 'Limbo', 'tagline' => 'Pick a target multiplier and see how high it lands.', 'code' => 'LI'],
    ['name' => 'Tower', 'tagline' => 'Climb level by level and decide when to lock it in.', 'code' => 'TO'],
    ['name' => 'Baccarat', 'tagline' => 'A sleek card classic with simple choices.', 'code' => 'BA'],
    ['name' => 'Crash', 'tagline' => 'Cash out before the multiplier disappears.', 'code' => 'CR'],
    ['name' => 'Keno', 'tagline' => 'Mark your numbers and watch the board light up.', 'code' => 'KE'],
];

$live_games = [];
$upcoming_games = [];

foreach ($games as $game) {
    if (!empty($game['is_live'])) {
        $live_games[] = $game;
        continue;
    }

    $upcoming_games[] = $game;
}

if ($user_id) {
    $is_logged_in = true;

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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="theme-color" content="#151d3b" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <meta name="color-scheme" content="dark" />
    <title>Games | Elite</title>
    <link rel="icon" type="image/png" href="../assets/img/Elite-letter_logo.png" />
    <link rel="apple-touch-icon" href="../assets/img/Elite-letter_logo.png" />
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="../assets/css/live_stats.css" />
</head>
<body>
    <?php include '../includes/header_sidebar.php'; ?>

    <main class="container favourites-page">
        <section class="favourites-hero">
            <div>
                <span class="favourites-kicker">Elite library</span>
                <h1>All Games</h1>
                <p><?php echo $is_logged_in ? 'Browse every Elite game in one place, from live tables to upcoming releases.' : 'Try live games in demo mode, then register or login when you want to play with balance.'; ?></p>
            </div>
            <div class="favourites-summary" aria-label="Games summary">
                <span><?php echo count($games); ?></span>
                <small>games</small>
            </div>
        </section>

        <section class="games-library-section" aria-labelledby="live-games-heading">
            <div class="games-library-heading">
                <h2 id="live-games-heading">Live Games</h2>
                <span><?php echo count($live_games); ?> available</span>
            </div>
            <div class="favourites-grid live-games-grid">
            <?php foreach ($live_games as $game): ?>
                <?php
                    $href = $game['href'] ?? '#';
                ?>
                <a href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>" class="favourite-card is-live-game">
                    <div class="favourite-card-art <?php echo !empty($game['image']) ? 'has-image' : ''; ?>">
                        <?php if (!empty($game['image'])): ?>
                            <img src="<?php echo htmlspecialchars($game['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($game['name'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php else: ?>
                            <span><?php echo htmlspecialchars($game['code'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="favourite-card-body">
                        <div>
                            <h2><?php echo htmlspecialchars($game['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                            <p><?php echo htmlspecialchars($game['tagline'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <span class="favourite-card-action"><?php echo $is_logged_in ? 'Play' : 'Play demo'; ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
            </div>
        </section>

        <section class="games-library-section upcoming-games-section" aria-labelledby="upcoming-games-heading">
            <div class="games-library-heading">
                <h2 id="upcoming-games-heading">Coming Soon</h2>
                <span><?php echo count($upcoming_games); ?> upcoming</span>
            </div>
            <div class="favourites-grid upcoming-games-grid">
            <?php foreach ($upcoming_games as $game): ?>
                <article class="favourite-card is-placeholder is-upcoming-game">
                    <div class="favourite-card-art <?php echo !empty($game['image']) ? 'has-image' : ''; ?>">
                        <?php if (!empty($game['image'])): ?>
                            <img src="<?php echo htmlspecialchars($game['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($game['name'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php else: ?>
                            <span><?php echo htmlspecialchars($game['code'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="favourite-card-body">
                        <div>
                            <div class="favourite-card-label">Coming soon</div>
                            <h2><?php echo htmlspecialchars($game['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                            <p><?php echo htmlspecialchars($game['tagline'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <span class="favourite-card-action muted">Coming soon</span>
                    </div>
                </article>
            <?php endforeach; ?>
            </div>
        </section>

        <?php include '../includes/live_stats.php'; ?>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
