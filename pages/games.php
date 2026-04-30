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
        'image' => '../assets/img/cards-logo.png',
        'tagline' => 'Beat the dealer and keep your hot streak alive.',
        'status' => 'Play',
        'is_live' => true,
    ],
    ['name' => 'Mines', 'tagline' => 'Reveal safe tiles and cash out before the blast.', 'code' => 'MI'],
    ['name' => 'Plinko', 'tagline' => 'Drop the ball and watch it bounce toward a multiplier.', 'code' => 'PL'],
    ['name' => 'Limbo', 'tagline' => 'Pick a target multiplier and see how high it lands.', 'code' => 'LI'],
    ['name' => 'Tower', 'tagline' => 'Climb level by level and decide when to lock it in.', 'code' => 'TO'],
    ['name' => 'Baccarat', 'tagline' => 'A sleek card classic with simple choices.', 'code' => 'BA'],
    ['name' => 'Crash', 'tagline' => 'Cash out before the multiplier disappears.', 'code' => 'CR'],
    ['name' => 'Dice', 'tagline' => 'Pick your odds and roll for a clean hit.', 'code' => 'DI'],
    ['name' => 'Keno', 'tagline' => 'Mark your numbers and watch the board light up.', 'code' => 'KE'],
];

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Games | Elite</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body>
    <?php include '../includes/header_sidebar.php'; ?>

    <main class="container favourites-page">
        <section class="favourites-hero">
            <div>
                <span class="favourites-kicker">Elite library</span>
                <h1>All Games</h1>
                <p>Browse every Elite game in one place, from live tables to upcoming releases.</p>
            </div>
            <div class="favourites-summary" aria-label="Games summary">
                <span><?php echo count($games); ?></span>
                <small>games</small>
            </div>
        </section>

        <section class="favourites-grid" aria-label="All games">
            <?php foreach ($games as $game): ?>
                <?php
                    $isLive = !empty($game['is_live']);
                    $href = $is_logged_in ? ($game['href'] ?? '#') : '../index.php?login=1';
                    $tag = $isLive ? 'a' : 'article';
                    $class = 'favourite-card' . ($isLive ? '' : ' is-placeholder');
                ?>
                <<?php echo $tag; ?> <?php echo $isLive ? 'href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"' : ''; ?> class="<?php echo $class; ?>">
                    <div class="favourite-card-art <?php echo !empty($game['image']) ? 'has-image' : ''; ?>">
                        <?php if (!empty($game['image'])): ?>
                            <img src="<?php echo htmlspecialchars($game['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($game['name'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php else: ?>
                            <span><?php echo htmlspecialchars($game['code'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="favourite-card-body">
                        <div>
                            <?php if (!$isLive): ?>
                                <div class="favourite-card-label">Coming soon</div>
                            <?php endif; ?>
                            <h2><?php echo htmlspecialchars($game['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                            <p><?php echo htmlspecialchars($game['tagline'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <span class="favourite-card-action <?php echo $isLive ? '' : 'muted'; ?>"><?php echo $isLive ? 'Play' : 'Coming soon'; ?></span>
                    </div>
                </<?php echo $tag; ?>>
            <?php endforeach; ?>
        </section>
    </main>

    <?php if (!$is_logged_in): ?>
        <script>
            const loginBtn = document.getElementById('loginBtn');
            if (loginBtn) {
                loginBtn.addEventListener('click', () => {
                    window.location.href = '../index.php?login=1';
                });
            }
        </script>
    <?php endif; ?>
</body>
</html>
