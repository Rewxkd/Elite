<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$is_logged_in = true;
$activePage = 'recent';
$balance = 0.00;
$total_wagered = 0.00;
$notification_count = 0;
$recent_games = [];

$game_assets = [
    'blackjack' => [
        'name' => 'Blackjack',
        'href' => 'blackjack.php',
        'image' => '../assets/img/cards-logo.png',
        'tagline' => 'Beat the dealer and keep your hot streak alive.',
    ],
];

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

$stmt = $conn->prepare("
    SELECT game_type, MAX(game_name) AS game_name, MAX(created_at) AS last_played, COUNT(*) AS rounds, SUM(wager_amount) AS total_wagered
    FROM latest_bets
    WHERE user_id = ?
    GROUP BY LOWER(game_type)
    ORDER BY last_played DESC
    LIMIT 12
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_games[] = $row;
}
$stmt->close();

function recent_game_meta($game, $game_assets) {
    $key = strtolower(trim((string)$game['game_type']));
    $name = $game['game_name'] ?: $game['game_type'];
    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
    $slug = trim($slug, '-');
    $meta = $game_assets[$key] ?? [
        'name' => $name,
        'href' => ($slug === '' ? '#' : $slug . '.php'),
        'image' => '',
        'tagline' => 'Recently played on Elite.',
    ];

    $meta['rounds'] = (int)$game['rounds'];
    $meta['total_wagered'] = (float)$game['total_wagered'];
    $meta['last_played'] = $game['last_played'];
    return $meta;
}

function format_recent_time($value) {
    if (!$value) {
        return 'Recently';
    }

    $timestamp = strtotime($value);
    if (!$timestamp) {
        return 'Recently';
    }

    return date('M j, H:i', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Recent Games | Elite</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body>
    <?php include '../includes/header_sidebar.php'; ?>

    <main class="container favourites-page">
        <section class="favourites-hero">
            <div>
                <span class="favourites-kicker">Elite history</span>
                <h1>Recently Played</h1>
                <p>Jump back into the games you have played most recently.</p>
            </div>
        </section>

        <?php if (empty($recent_games)): ?>
            <section class="favourites-empty compact">
                <div class="favourites-empty-mark">+</div>
                <h2>No recent games yet</h2>
                <p>Play a round and your recent games will appear here for quick access.</p>
                <a class="favourites-cta" href="games.php">Browse games</a>
            </section>
        <?php else: ?>
            <section class="favourites-grid" aria-label="Recently played games">
                <?php foreach ($recent_games as $game): ?>
                    <?php $meta = recent_game_meta($game, $game_assets); ?>
                    <a href="<?php echo htmlspecialchars($meta['href'], ENT_QUOTES, 'UTF-8'); ?>" class="favourite-card">
                        <div class="favourite-card-art <?php echo $meta['image'] ? 'has-image' : ''; ?>">
                            <?php if ($meta['image']): ?>
                                <img src="<?php echo htmlspecialchars($meta['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($meta['name'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php else: ?>
                                <span><?php echo htmlspecialchars(strtoupper(substr($meta['name'], 0, 2)), ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="favourite-card-body">
                            <div>
                                <div class="favourite-card-label"><?php echo htmlspecialchars(format_recent_time($meta['last_played']), ENT_QUOTES, 'UTF-8'); ?></div>
                                <h2><?php echo htmlspecialchars($meta['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                <p><?php echo $meta['rounds']; ?> rounds played · $<?php echo number_format($meta['total_wagered'], 2); ?> wagered</p>
                            </div>
                            <span class="favourite-card-action">Play again</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
