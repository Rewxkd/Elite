<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = intval($_SESSION['user_id']);
$is_logged_in = true;
$activePage = 'favourites';
$notification_count = 0;

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

// Get user's favorites
$favorites = [];
$stmt = $conn->prepare("SELECT g.name FROM favorites f JOIN games g ON LOWER(f.game_type) = LOWER(g.name) WHERE f.user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $favorites[] = $row['name'];
}
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Favourites | Elite</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body>
    <?php include '../includes/header_sidebar.php'; ?>

    <main class="container">
        <h1 style="margin-bottom: 2rem; color: #cfdcff;">Your Favourite Games</h1>
        <?php if (empty($favorites)): ?>
            <p style="color: #888; text-align: center; margin-top: 4rem;">You haven't favourited any games yet. Go to the home page to favourite some games!</p>
        <?php else: ?>
            <div class="games-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1rem;">
                <?php foreach ($favorites as $game): ?>
                    <a href="<?php echo strtolower($game); ?>.php" class="game-card" style="text-decoration: none;">
                        <div class="game-img" style="background: linear-gradient(135deg, #0d123a, #1f2d58); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700;">
                            <?php echo strtoupper(substr($game, 0, 2)); ?>
                        </div>
                        <div class="game-title"><?php echo htmlspecialchars($game); ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
