<?php
session_start();
include '../includes/db_connect.php';

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
$is_logged_in = $user_id !== null;
$activePage = 'favourites';
$notification_count = 0;
$balance = 0.00;
$total_wagered = 0.00;
$favorites = [];

$game_assets = [
    'blackjack' => [
        'name' => 'Blackjack',
        'href' => 'blackjack.php',
        'image' => '../assets/img/blackjack-logo.png',
        'tagline' => 'Beat the dealer and keep your hot streak alive.',
    ],
    'mines' => [
        'name' => 'Mines',
        'href' => 'mines.php',
        'image' => '../assets/img/mines-logo.png',
        'tagline' => 'Reveal safe tiles and cash out before the blast.',
    ],
    'plinko' => [
        'name' => 'Plinko',
        'href' => 'plinko.php',
        'image' => '../assets/img/plinko-logo.png',
        'tagline' => 'Drop the ball and watch it bounce toward a multiplier.',
    ],
    'dice' => [
        'name' => 'Dice',
        'href' => 'dice.php',
        'image' => '../assets/img/dice-logo.png',
        'tagline' => 'Pick your odds and roll for a clean hit.',
    ],
];

if ($is_logged_in) {
    $notif_count_query = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = FALSE");
    if ($notif_count_query && $notif_count_query->num_rows > 0) {
        $notif = $notif_count_query->fetch_assoc();
        $notification_count = $notif['count'];
    }

    $stmt = $conn->prepare('SELECT balance, total_wagered FROM wallets WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $wallet = $result->fetch_assoc();
    $stmt->close();

    $balance = floatval($wallet['balance'] ?? 0);
    $total_wagered = floatval($wallet['total_wagered'] ?? 0);

    $stmt = $conn->prepare('SELECT game_type FROM favorites WHERE user_id = ? ORDER BY game_type ASC');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $favorites[] = $row['game_type'];
    }
    $stmt->close();
}

function favorite_game_meta($game, $game_assets) {
    $key = strtolower(trim((string)$game));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $key);
    $slug = trim($slug, '-');

    if (isset($game_assets[$key])) {
        return $game_assets[$key];
    }

    return [
        'name' => $game,
        'href' => ($slug === '' ? '#' : $slug . '.php'),
        'image' => '',
        'tagline' => 'Saved to your personal games shelf.',
    ];
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
    <title>Favourites | Elite</title>
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
                <span class="favourites-kicker">Elite collection</span>
                <h1>Your Favourite Games</h1>
                <p><?php echo $is_logged_in ? 'Keep your go-to tables close and jump back into the action fast.' : 'Login or register to save games here. You can still try every live game in demo mode.'; ?></p>
            </div>
            <div class="favourites-summary" aria-label="Favourite games summary">
                <span><?php echo count($favorites); ?></span>
                <small><?php echo count($favorites) === 1 ? 'saved game' : 'saved games'; ?></small>
            </div>
        </section>

        <?php if (empty($favorites)): ?>
            <section class="favourites-empty compact">
                <div class="favourites-empty-mark">+</div>
                <h2><?php echo $is_logged_in ? 'No favourites yet' : 'Save favourites after login'; ?></h2>
                <p><?php echo $is_logged_in ? 'Find a game you like, tap the star, and it will appear here for quick access.' : 'Demo play is open now. Create an account when you want favourites, balance play, and recent history.'; ?></p>
                <?php if ($is_logged_in): ?>
                    <a class="favourites-cta" href="games.php">Browse games</a>
                <?php else: ?>
                    <div class="favourites-empty-actions">
                        <a class="favourites-cta" href="games.php">Play demos</a>
                        <button class="favourites-cta is-secondary" type="button" data-auth-tab="register">Register</button>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if (!empty($favorites)): ?>
            <section class="favourites-grid" aria-label="Favourite games">
                <?php foreach ($favorites as $game): ?>
                    <?php
                        $meta = favorite_game_meta($game, $game_assets);
                        $displayName = $meta['name'];
                    ?>
                    <a href="<?php echo htmlspecialchars($meta['href'], ENT_QUOTES, 'UTF-8'); ?>" class="favourite-card">
                        <div class="favourite-card-art <?php echo $meta['image'] ? 'has-image' : ''; ?>">
                            <?php if ($meta['image']): ?>
                                <img src="<?php echo htmlspecialchars($meta['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php else: ?>
                                <span><?php echo htmlspecialchars(strtoupper(substr($displayName, 0, 2)), ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="favourite-card-body">
                            <div>
                                <h2><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h2>
                                <p><?php echo htmlspecialchars($meta['tagline'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                            <span class="favourite-card-action">Play</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <?php include '../includes/live_stats.php'; ?>
    </main>
    <?php include '../includes/footer.php'; ?>

</body>
</html>
