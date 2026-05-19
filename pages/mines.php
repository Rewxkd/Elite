<?php
session_start();
include '../includes/db_connect.php';

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
$is_logged_in = $user_id !== null;
$is_demo = !$is_logged_in;
$activePage = 'mines';
$notification_count = 0;

if ($is_logged_in) {
    $notif_count_query = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = FALSE");
    if ($notif_count_query && $notif_count_query->num_rows > 0) {
        $notif = $notif_count_query->fetch_assoc();
        $notification_count = $notif['count'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!$is_logged_in) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Login required for wallet play']);
        exit;
    }

    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        $payload = $_POST;
    }

    $api = $payload['api'] ?? '';

    if (!$api) {
        echo json_encode(['success' => false, 'message' => 'No API command']);
        exit;
    }

    $stmt = $conn->prepare('SELECT balance, total_wagered FROM wallets WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $wallet = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$wallet) {
        echo json_encode(['success' => false, 'message' => 'Wallet not found']);
        exit;
    }

    $balance = floatval($wallet['balance']);
    $total_wagered = floatval($wallet['total_wagered']);

    if ($api === 'get_wallet') {
        echo json_encode(['success' => true, 'balance' => $balance, 'total_wagered' => $total_wagered]);
        exit;
    }

    if ($api === 'update_wallet') {
        $delta = floatval($payload['delta'] ?? 0);
        $roundWager = max(0, floatval($payload['wager'] ?? 0));

        $newBalance = $balance + $delta;
        if ($newBalance < 0) {
            echo json_encode(['success' => false, 'message' => 'Insufficient balance to apply update']);
            exit;
        }

        $newTotal = $total_wagered + $roundWager;
        $updateStmt = $conn->prepare('UPDATE wallets SET balance = ?, total_wagered = ? WHERE user_id = ?');
        $updateStmt->bind_param('ddi', $newBalance, $newTotal, $user_id);

        if ($updateStmt->execute()) {
            $updateStmt->close();
            $payoutAmount = max(0, $roundWager + $delta);
            $betStmt = $conn->prepare('INSERT INTO latest_bets (user_id, game_type, game_name, wager_amount, payout_amount, net_result) VALUES (?, ?, ?, ?, ?, ?)');
            if ($betStmt) {
                $gameType = 'mines';
                $gameName = 'Mines';
                $betStmt->bind_param('issddd', $user_id, $gameType, $gameName, $roundWager, $payoutAmount, $delta);
                $betStmt->execute();
                $betStmt->close();
            }

            echo json_encode(['success' => true, 'balance' => $newBalance, 'total_wagered' => $newTotal]);
            exit;
        }

        $updateStmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to update wallet']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid API command']);
    exit;
}

$balance = 0.00;
$total_wagered = 0.00;

if ($is_logged_in) {
    $stmt = $conn->prepare('SELECT balance, total_wagered FROM wallets WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $wallet = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $balance = floatval($wallet['balance'] ?? 0);
    $total_wagered = floatval($wallet['total_wagered'] ?? 0);
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
    <title>Mines | Elite</title>
    <link rel="icon" type="image/png" href="../assets/img/Elite-letter_logo.png" />
    <link rel="apple-touch-icon" href="../assets/img/Elite-letter_logo.png" />
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="../assets/css/live_stats.css" />
    <link rel="stylesheet" href="../assets/css/mines.css" />
</head>
<body class="<?php echo $is_demo ? 'demo-mode' : ''; ?>">
    <?php include '../includes/header_sidebar.php'; ?>

    <main class="container page-game-main mines-page-main">
        <section class="game-overlay mines-overlay">
            <div class="game-overlay-body mines-area">
                <aside class="mines-control-panel">
                    <div class="mines-panel-header">
                        <span class="mines-kicker">Elite originals</span>
                        <h1>Mines</h1>
                    </div>

                    <div class="bet-widget <?php echo $is_demo ? 'is-demo-locked' : ''; ?>">
                        <div class="bet-widget-head">
                            <label for="betInput">Bet Amount</label>
                            <span id="betAmountPreview">$10.00</span>
                        </div>
                        <div class="bet-control mines-bet-control">
                            <span class="bet-prefix">$</span>
                            <input id="betInput" type="number" min="1" step="1" value="10" aria-label="Bet amount" <?php echo $is_demo ? 'disabled' : ''; ?>>
                            <button class="bet-adjust" id="halfBetBtn" type="button" <?php echo $is_demo ? 'disabled' : ''; ?>>1/2</button>
                            <button class="bet-adjust" id="doubleBetBtn" type="button" <?php echo $is_demo ? 'disabled' : ''; ?>>2x</button>
                        </div>
                    </div>

                    <div class="mines-field">
                        <label for="minesInput">Mines</label>
                        <div class="mines-stepper">
                            <button id="lessMinesBtn" type="button" aria-label="Decrease mines">-</button>
                            <input id="minesInput" type="number" min="1" max="24" step="1" value="5">
                            <button id="moreMinesBtn" type="button" aria-label="Increase mines">+</button>
                        </div>
                    </div>

                    <div class="mines-stats">
                        <div>
                            <span>Next Tile</span>
                            <strong id="safeChanceText">80.00%</strong>
                        </div>
                        <div>
                            <span>Multiplier</span>
                            <strong id="multiplierText">1.00x</strong>
                        </div>
                        <div>
                            <span>Profit</span>
                            <strong id="profitText">$0.00</strong>
                        </div>
                    </div>

                    <div class="game-play-action">
                        <button class="mines-primary-btn" id="startBtn" type="button"><?php echo $is_demo ? 'Demo Bet' : 'Place Bet'; ?></button>
                    </div>
                    <button class="mines-cashout-btn" id="cashoutBtn" type="button" disabled>Cash Out</button>
                </aside>

                <section class="mines-board-panel">
                    <div class="mines-board" id="minesBoard" aria-label="Mines board"></div>
                </section>
            </div>
            <footer class="game-overlay-footer">
                <div class="game-overlay-left">Mines</div>
                <div id="footer-favorite-container"></div>
            </footer>
        </section>
    </main>

    <?php include '../includes/live_stats.php'; ?>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/favorite_button.js"></script>
    <script src="../assets/js/mines.js" data-demo="<?php echo $is_demo ? 'true' : 'false'; ?>" data-balance="<?php echo number_format($balance, 2, '.', ''); ?>" data-total-wagered="<?php echo number_format($total_wagered, 2, '.', ''); ?>"></script>
</body>
</html>
