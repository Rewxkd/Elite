<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = intval($_SESSION['user_id']);
$is_logged_in = true;
$activePage = 'blackjack';
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
    $result = $stmt->get_result();
    $wallet = $result->fetch_assoc();
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

    if ($api === 'load_round') {
        $savedRound = $_SESSION['blackjack_round'][$user_id] ?? null;
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'round' => $savedRound]);
        exit;
    }

    if ($api === 'save_round') {
        $round = $payload['round'] ?? null;
        if (!is_array($round)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid round state']);
            exit;
        }

        if (!isset($_SESSION['blackjack_round']) || !is_array($_SESSION['blackjack_round'])) {
            $_SESSION['blackjack_round'] = [];
        }

        $_SESSION['blackjack_round'][$user_id] = $round;
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    if ($api === 'clear_round') {
        if (isset($_SESSION['blackjack_round'][$user_id])) {
            unset($_SESSION['blackjack_round'][$user_id]);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    if ($api === 'update_wallet') {
        $delta = floatval($payload['delta'] ?? 0);
        $roundWager = floatval($payload['wager'] ?? 0);

        $newBalance = $balance + $delta;
        if ($newBalance < 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Insufficient balance to apply update']);
            exit;
        }

        $newTotal = $total_wagered + abs($roundWager);

        $updateStmt = $conn->prepare('UPDATE wallets SET balance = ?, total_wagered = ? WHERE user_id = ?');
        $updateStmt->bind_param('ddi', $newBalance, $newTotal, $user_id);

        if ($updateStmt->execute()) {
            $updateStmt->close();
            $payoutAmount = max(0, $roundWager + $delta);
            $betStmt = $conn->prepare('INSERT INTO latest_bets (user_id, game_type, game_name, wager_amount, payout_amount, net_result) VALUES (?, ?, ?, ?, ?, ?)');
            if ($betStmt) {
                $gameType = 'blackjack';
                $gameName = 'Blackjack';
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
$result = $stmt->get_result();
$wallet = $result->fetch_assoc();
$stmt->close();

$balance = floatval($wallet['balance'] ?? 0);
$total_wagered = floatval($wallet['total_wagered'] ?? 0);

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
    <title>Blackjack | Elite</title>
    <link rel="icon" type="image/png" href="../assets/img/Elite-letter_logo.png" />
    <link rel="apple-touch-icon" href="../assets/img/Elite-letter_logo.png" />
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="../assets/css/live_stats.css" />
    <link rel="stylesheet" href="../assets/css/blackjack.css" />
</head>
<body class="blackjack-game-body">
    <?php include '../includes/header_sidebar.php'; ?>

    <main class="container page-game-main blackjack-page-main">
        <section class="game-overlay blackjack-overlay">
            <div class="game-overlay-body blackjack-area">
                <div class="blackjack-wrapper">
                    <aside class="left-panel">
                        <div class="panel">
                            <div class="blackjack-panel-header">
                                <span class="blackjack-kicker">Elite table</span>
                                <h3>Blackjack</h3>
                            </div>
                            <p class="table-info">Dealer stands on soft 17. Blackjack pays 3:2.</p>
                            <div class="bet-widget">
                                <div class="bet-widget-head">
                                    <label for="betInput">Bet Amount</label>
                                    <span id="betAmountPreview">$10.00</span>
                                </div>
                                <div class="bet-control">
                                    <span class="bet-prefix">$</span>
                                    <input id="betInput" type="number" min="1" step="1" value="10" aria-label="Bet amount">
                                    <button class="bet-adjust" id="halfBetBtn" type="button">1/2</button>
                                    <button class="bet-adjust" id="doubleBetBtn" type="button">2x</button>
                                </div>
                            </div>
                            <div class="action-grid">
                                <button id="hitBtn" disabled>Hit</button>
                                <button id="standBtn" disabled>Stand</button>
                                <button id="splitBtn" disabled>Split</button>
                                <button id="doubleBtn" disabled>Double</button>
                            </div>
                            <div class="bet-actions">
                                <button id="betBtn">Place Bet</button>
                            </div>
                        </div>
                    </aside>

                    <section class="table-panel" id="blackjackTable">
                        <div class="table-felt"></div>
                        <div class="deck-shoe" id="deckShoe" aria-hidden="true">
                            <div class="deck-card deck-card-shadow"></div>
                            <div class="deck-card deck-card-mid"></div>
                            <div class="deck-card deck-card-top"></div>
                        </div>

                        <div class="dealer-panel hand-zone" id="dealerPanel">
                            <h4>Dealer</h4>
                            <p id="dealerValue"></p>
                            <div class="card-row" id="dealerCards"></div>
                        </div>

                        <div class="table-rules">
                            <span>Blackjack pays 3 to 2</span>
                            <span>Dealer stands on soft 17</span>
                        </div>

                        <div class="player-panel hand-zone" id="singlePlayerPanel">
                            <h4>Player</h4>
                            <p id="playerValue"></p>
                            <div class="card-row" id="playerCards"></div>
                        </div>

                        <div class="split-layout" id="splitHandsSection" style="display:none;"></div>
                    </section>
                </div>
            </div>
            <footer class="game-overlay-footer">
                <div class="game-overlay-left">Blackjack</div>
                <div id="footer-favorite-container"></div>
            </footer>
        </section>
    </main>

    <?php include '../includes/live_stats.php'; ?>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/favorite_button.js"></script>
    <script src="../assets/js/blackjack.js" data-balance="<?php echo number_format($balance, 2, '.', ''); ?>" data-total-wagered="<?php echo number_format($total_wagered, 2, '.', ''); ?>"></script>
</body>
</html>
