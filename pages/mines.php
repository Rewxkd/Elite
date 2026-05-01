<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = intval($_SESSION['user_id']);
$is_logged_in = true;
$activePage = 'mines';
$notification_count = 0;

$notif_count_query = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = FALSE");
if ($notif_count_query && $notif_count_query->num_rows > 0) {
    $notif = $notif_count_query->fetch_assoc();
    $notification_count = $notif['count'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        $payload = $_POST;
    }

    $api = $payload['api'] ?? '';

    if (!$api) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No API command']);
        exit;
    }

    $stmt = $conn->prepare('SELECT balance, total_wagered FROM wallets WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $wallet = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$wallet) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Wallet not found']);
        exit;
    }

    $balance = floatval($wallet['balance']);
    $total_wagered = floatval($wallet['total_wagered']);

    if ($api === 'get_wallet') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'balance' => $balance, 'total_wagered' => $total_wagered]);
        exit;
    }

    if ($api === 'update_wallet') {
        $delta = floatval($payload['delta'] ?? 0);
        $roundWager = max(0, floatval($payload['wager'] ?? 0));

        $newBalance = $balance + $delta;
        if ($newBalance < 0) {
            header('Content-Type: application/json');
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

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'balance' => $newBalance, 'total_wagered' => $newTotal]);
            exit;
        }

        $updateStmt->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update wallet']);
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid API command']);
    exit;
}

$stmt = $conn->prepare('SELECT balance, total_wagered FROM wallets WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$wallet = $stmt->get_result()->fetch_assoc();
$stmt->close();

$balance = floatval($wallet['balance'] ?? 0);
$total_wagered = floatval($wallet['total_wagered'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Mines | Elite</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="../assets/css/live_stats.css" />
    <link rel="stylesheet" href="../assets/css/mines.css" />
</head>
<body>
    <?php include '../includes/header_sidebar.php'; ?>

    <main class="container page-game-main mines-page-main">
        <section class="game-overlay mines-overlay">
            <div class="game-overlay-body mines-area">
                <aside class="mines-control-panel">
                    <div class="mines-panel-header">
                        <span class="mines-kicker">Elite originals</span>
                        <h1>Mines</h1>
                    </div>

                    <div class="bet-widget">
                        <div class="bet-widget-head">
                            <label for="betInput">Bet Amount</label>
                            <span id="betAmountPreview">$10.00</span>
                        </div>
                        <div class="bet-control mines-bet-control">
                            <span class="bet-prefix">$</span>
                            <input id="betInput" type="number" min="1" step="1" value="10" aria-label="Bet amount">
                            <button class="bet-adjust" id="halfBetBtn" type="button">1/2</button>
                            <button class="bet-adjust" id="doubleBetBtn" type="button">2x</button>
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

                    <button class="mines-primary-btn" id="startBtn" type="button">Place Bet</button>
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
    <script src="../assets/js/mines.js" data-balance="<?php echo number_format($balance, 2, '.', ''); ?>" data-total-wagered="<?php echo number_format($total_wagered, 2, '.', ''); ?>"></script>
</body>
</html>
