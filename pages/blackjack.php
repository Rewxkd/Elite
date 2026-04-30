<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?login=1');
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Blackjack | Elite</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="../assets/css/blackjack.css" />
</head>
<body>
    <?php include '../includes/header_sidebar.php'; ?>

    <main class="container page-game-main">
        <section class="game-overlay">
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

                    <section class="table-panel">
                        <div class="round-status">
                            <div class="deck-preview"><img src="https://deckofcardsapi.com/static/img/back.png" alt="Card deck"></div>
                            <div id="roundStatus">Ready to play. Place your bet to start a round.</div>
                        </div>

                        <div class="dealer-panel hand-zone">
                            <h4>Dealer</h4>
                            <div class="card-row" id="dealerCards"></div>
                            <p id="dealerValue"></p>
                        </div>

                        <div class="table-rules">
                            <span>Blackjack pays 3 to 2</span>
                            <span>Dealer stands on soft 17</span>
                        </div>

                        <div class="player-panel hand-zone" id="singlePlayerPanel">
                            <h4>Player</h4>
                            <div class="card-row" id="playerCards"></div>
                            <p id="playerValue"></p>
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

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/favorite_button.js"></script>
    <script src="../assets/js/blackjack.js" data-balance="<?php echo number_format($balance, 2, '.', ''); ?>" data-total-wagered="<?php echo number_format($total_wagered, 2, '.', ''); ?>"></script>
</body>
</html>
