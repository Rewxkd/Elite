<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?login=1');
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
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="blackjack.css" />
</head>
<body>
    <?php include 'header_sidebar.php'; ?>

    <main class="container page-game-main">
        <section class="game-overlay">
            <div class="game-overlay-body blackjack-area">
                <div class="blackjack-wrapper">
                    <aside class="left-panel">
                        <div class="panel">
                            <h3>Blackjack</h3>
                            <p class="table-info">Dealer stands on soft 17. Blackjack pays 3:2.</p>
                            <div style="display:flex;justify-content:space-between;align-items:center;margin:0.8rem 0 0.4rem;">
                                <label for="betInput" style="color:#cfdcff;font-weight:600;">Bet:</label>
                                <input id="betInput" type="number" min="1" step="1" value="10">
                            </div>
                            <div class="action-grid" style="margin-top:0.75rem;">
                                <button id="hitBtn" disabled>Hit</button>
                                <button id="standBtn" disabled>Stand</button>
                                <button id="splitBtn" disabled>Split</button>
                                <button id="doubleBtn" disabled>Double</button>
                            </div>
                            <div class="bet-actions">
                                <button id="betBtn" style="background: linear-gradient(90deg, #1f6aed, #2550de);">Place Bet</button>
                            </div>
                        </div>
                    </aside>

                    <section class="table-panel">
                        <div class="round-status" style="margin-bottom:1rem;">
                            <div class="deck-preview"><img src="https://deckofcardsapi.com/static/img/back.png" alt="Card deck"></div>
                            <div id="roundStatus">Ready to play. Place your bet to start a round.</div>
                        </div>

                        <div class="dealer-panel">
                            <h4>Dealer</h4>
                            <div class="card-row" id="dealerCards"></div>
                            <p id="dealerValue" style="color:#c5d8ff;margin:0.5rem 0 0;"></p>
                        </div>

                        <div class="player-panel" id="singlePlayerPanel">
                            <h4>Player</h4>
                            <div class="card-row" id="playerCards"></div>
                            <p id="playerValue" style="color:#c5d8ff;margin:0.5rem 0 0;"></p>
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

    <script src="favorite_button.js"></script>
    <script>
        // Initialize Footer Favorite Button
        document.addEventListener('DOMContentLoaded', function() {
            const footerFavoriteContainer = document.getElementById('footer-favorite-container');
            if (footerFavoriteContainer) {
                new FavoriteButton('blackjack', 'Blackjack', footerFavoriteContainer);
            }
        });
    </script>

    <!-- JavaScript kods Blackjack spēles loģikai -->
    <script>
        const userState = {
            balance: <?php echo number_format($balance,2,'.',''); ?>,
            totalWagered: <?php echo number_format($total_wagered,2,'.',''); ?>,
            initialBet: 0,
            lock: false,
            nextId: 1
        };

        const deckSuits = ['♠', '♥', '♦', '♣'];
        const deckRanks = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];

        const betInput = document.getElementById('betInput');
        const betBtn = document.getElementById('betBtn');
        const hitBtn = document.getElementById('hitBtn');
        const standBtn = document.getElementById('standBtn');
        const doubleBtn = document.getElementById('doubleBtn');
        const splitBtn = document.getElementById('splitBtn');
        const displayBalance = document.getElementById('balanceAmount');
        const roundStatusEl = document.getElementById('roundStatus');
        const dealerCardsEl = document.getElementById('dealerCards');
        const playerCardsEl = document.getElementById('playerCards');
        const playerValueEl = document.getElementById('playerValue');
        const dealerValueEl = document.getElementById('dealerValue');
        const splitHandsSection = document.getElementById('splitHandsSection');

        let deck = [];
        let dealerHand = [];
        let playerHands = [];
        let currentHandIndex = 0;
        let inRound = false;

        function formatCurrency(amount) {
            return `$${amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }

        function updateUI() {
            if (displayBalance) {
                displayBalance.textContent = formatCurrency(userState.balance);
            }
        }

        function createDeck() {
            const cards = [];
            for (let d = 0; d < 6; d++) {
                deckSuits.forEach(suit => {
                    deckRanks.forEach(rank => {
                        cards.push({ suit, rank, code: `${rank}${suit}` });
                    });
                });
            }
            return cards;
        }

        function shuffleCards(cards) {
            for (let i = cards.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [cards[i], cards[j]] = [cards[j], cards[i]];
            }
            return cards;
        }

        function drawCard() {
            if (deck.length === 0) {
                deck = shuffleCards(createDeck());
            }
            const card = deck.pop();
            card.faceUp = true;
            return card;
        }

        function delay(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

        async function dealCardToHand(hand, faceUp = true, withRender = true) {
            const card = drawCard();
            card.faceUp = faceUp;
            hand.push(card);
            if (withRender) {
                renderHands();
            }
            await delay(220);
            return card;
        }

        function handValue(hand) {
            let val = 0;
            let aceCount = 0;

            for (const card of hand) {
                const r = card.rank;
                if (r === 'A') {
                    aceCount += 1;
                    val += 11;
                } else if (['K', 'Q', 'J'].includes(r)) {
                    val += 10;
                } else {
                    val += Number(r);
                }
            }

            while (val > 21 && aceCount > 0) {
                val -= 10;
                aceCount -= 1;
            }

            return val;
        }

        function isBlackjack(hand) {
            return hand.length === 2 && handValue(hand) === 21;
        }

        function getCardImageUrl(rank, suit) {
            const suitMap = { '♠': 'S', '♥': 'H', '♦': 'D', '♣': 'C' };
            const rankMap = { 'A': 'A', 'J': 'J', 'Q': 'Q', 'K': 'K', '10': '0' };
            const r = rankMap[rank] || rank;
            return `https://deckofcardsapi.com/static/img/${r}${suitMap[suit]}.png`;
        }

        function renderCardEl(card) {
            const cardEl = document.createElement('div');
            cardEl.classList.add('card', 'animate');

            const img = document.createElement('img');
            img.classList.add('card-img');
            img.alt = `${card.rank}${card.suit}`;
            img.src = card.faceUp ? getCardImageUrl(card.rank, card.suit) : 'https://deckofcardsapi.com/static/img/back.png';

            cardEl.appendChild(img);

            if (!card.faceUp) {
                cardEl.dataset.flip = 'true';
            }
            return cardEl;
        }

        function renderHands() {
            dealerCardsEl.innerHTML = '';
            playerCardsEl.innerHTML = '';
            splitHandsSection.innerHTML = '';

            dealerHand.forEach(card => {
                const c = renderCardEl(card);
                dealerCardsEl.appendChild(c);
            });

            if (dealerHand.reveal) {
                dealerValueEl.textContent = `Dealer: ${handValue(dealerHand)}`;
            } else if (dealerHand.length > 0) {
                const visibleValue = handValue([dealerHand[0]]);
                dealerValueEl.textContent = `Dealer: ${visibleValue} + ?`;
            } else {
                dealerValueEl.textContent = '';
            }

            if (playerHands.length === 0) {
                playerValueEl.textContent = '';
                playerCardsEl.innerHTML = '';
                splitHandsSection.style.display = 'none';
            } else if (playerHands.length === 1) {
                splitHandsSection.style.display = 'none';
                document.getElementById('singlePlayerPanel').style.display = 'block';
                playerCardsEl.innerHTML = '';
                playerHands[0].cards.forEach(card => playerCardsEl.appendChild(renderCardEl(card)));
                playerValueEl.textContent = `Value: ${handValue(playerHands[0].cards)} | Bet: ${formatCurrency(playerHands[0].bet)}`;
            } else {
                document.getElementById('singlePlayerPanel').style.display = 'none';
                splitHandsSection.style.display = 'flex';
                playerHands.forEach((hand, idx) => {
                    const handCard = document.createElement('div');
                    handCard.className = 'split-hand';
                    if (idx === currentHandIndex) {
                        handCard.style.boxShadow = '0 0 0 2px rgba(85, 188, 255,0.8)';
                    }
                    const header = document.createElement('h5');
                    header.style.margin = '0 0 0.4rem';
                    header.style.fontSize = '0.95rem';
                    header.textContent = `Hand ${idx + 1} ${idx === currentHandIndex ? '(Active)' : ''}`;
                    const body = document.createElement('div');
                    body.className = 'card-row';
                    hand.cards.forEach(card => body.appendChild(renderCardEl(card)));
                    const info = document.createElement('p');
                    info.style.margin = '0.5rem 0 0';
                    info.textContent = `Value: ${handValue(hand.cards)} | Bet: ${formatCurrency(hand.bet)} ${hand.isBust ? '(BUST)' : hand.isStand ? '(Stand)' : ''}`;
                    handCard.appendChild(header);
                    handCard.appendChild(body);
                    handCard.appendChild(info);
                    splitHandsSection.appendChild(handCard);
                });
            }
        }

        function setButtons(state, options = {}) {
            const isPlaying = state === 'playing';
            const canSplit = options.canSplit || false;
            const canDouble = options.canDouble || false;

            if (hitBtn) hitBtn.disabled = !isPlaying;
            if (standBtn) standBtn.disabled = !isPlaying;
            if (doubleBtn) doubleBtn.disabled = !isPlaying || !canDouble;
            if (splitBtn) splitBtn.disabled = !isPlaying || !canSplit;
            if (betBtn) betBtn.disabled = isPlaying;
            if (betInput) betInput.disabled = isPlaying;
        }

        function refreshActionButtons() {
            if (!inRound) {
                setButtons('idle');
                return;
            }
            const current = playerHands[currentHandIndex];
            if (!current) {
                setButtons('idle');
                return;
            }
            const canDouble = current.cards.length === 2 && userState.balance >= current.bet && !current.isBust && !current.isStand;
            const canSplit = current.cards.length === 2 && current.cards[0].rank === current.cards[1].rank && userState.balance >= current.bet && playerHands.length < 4 && !current.isStand && !current.isBust;
            setButtons('playing', { canSplit, canDouble });
        }

        function updateMessage(text, cls = '') {
            updateRoundStatus(text, cls);
        }

        function updateRoundStatus(text, cls = '') {
            if (!roundStatusEl) return;
            roundStatusEl.textContent = text;
            roundStatusEl.className = 'round-status' + (cls ? ' ' + cls : '');
        }

        async function beginRound() {
            if (inRound) return;

            const bet = Math.max(1, parseFloat(betInput.value));
            if (!Number.isFinite(bet) || bet <= 0) {
                updateMessage('Enter a valid bet amount.', 'outcome-negative');
                return;
            }

            if (bet > userState.balance) {
                updateMessage('Not enough balance for that bet.', 'outcome-negative');
                return;
            }

            userState.balance -= bet;
            userState.initialBet = bet;
            userState.totalWagered += bet;
            updateUI();

            deck = shuffleCards(createDeck());
            dealerHand = [];
            playerHands = [{ cards: [], bet: bet, isStand: false, isBust: false, blackjack: false }];
            currentHandIndex = 0;
            inRound = true;
            dealerHand.reveal = false;

            await dealCardToHand(dealerHand, true);
            await dealCardToHand(playerHands[0].cards, true);
            await dealCardToHand(dealerHand, false);
            await dealCardToHand(playerHands[0].cards, true);

            const firstHand = playerHands[0];
            firstHand.blackjack = isBlackjack(firstHand.cards);
            firstHand.isStand = firstHand.blackjack;

            const canSplit = firstHand.cards.length === 2 && firstHand.cards[0].rank === firstHand.cards[1].rank && userState.balance >= bet;
            const canDouble = userState.balance >= bet;

            if (firstHand.blackjack) {
                updateMessage('Blackjack! Waiting for dealer.', 'outcome-positive');
                setButtons('idle');
                await finalizeDealer();
                return;
            }

            setButtons('playing', { canSplit, canDouble });
            updateMessage('Round started. Play your hand.', '');
            renderHands();
        }

        function moveToNextHand() {
            for (let i = currentHandIndex + 1; i < playerHands.length; i++) {
                if (!playerHands[i].isStand && !playerHands[i].isBust) {
                    currentHandIndex = i;
                    return;
                }
            }
            for (let i = 0; i <= currentHandIndex; i++) {
                if (!playerHands[i].isStand && !playerHands[i].isBust) {
                    currentHandIndex = i;
                    return;
                }
            }
            currentHandIndex = playerHands.length - 1;
        }

        async function playerHit() {
            if (!inRound) return;
            const current = playerHands[currentHandIndex];
            if (current.isStand || current.isBust) return;

            const newCard = drawCard();
            newCard.faceUp = true;
            current.cards.push(newCard);

            const currentValue = handValue(current.cards);
            if (currentValue > 21) {
                current.isBust = true;
                current.isStand = true;
                updateMessage(`Hand ${currentHandIndex + 1} busted!`, 'outcome-negative');
            } else if (currentValue === 21) {
                current.isStand = true;
                updateMessage(`Hand ${currentHandIndex + 1} hit 21, auto-standing.`, 'outcome-positive');
            }

            if (playerHands.every(h => h.isStand || h.isBust)) {
                await finalizeDealer();
                return;
            }

            if (!current.isStand) {
                updateMessage(`Hand ${currentHandIndex + 1} is ${currentValue}.`, '');
            } else {
                moveToNextHand();
            }

            renderHands();
            refreshActionButtons();
        }

        async function playerStand() {
            if (!inRound) return;
            const current = playerHands[currentHandIndex];
            current.isStand = true;
            if (playerHands.every(h => h.isStand || h.isBust)) {
                await finalizeDealer();
                return;
            }
            moveToNextHand();
            renderHands();
            refreshActionButtons();
            updateMessage(`Standing on hand ${currentHandIndex + 1}.`, '');
        }

        async function playerDouble() {
            if (!inRound) return;
            const current = playerHands[currentHandIndex];
            if (current.cards.length !== 2 || userState.balance < current.bet) {
                return;
            }

            userState.balance -= current.bet;
            current.bet *= 2;
            const newCard = drawCard();
            newCard.faceUp = true;
            current.cards.push(newCard);
            const currentValue = handValue(current.cards);
            current.isStand = true;

            if (currentValue > 21) {
                current.isBust = true;
                updateMessage(`Double busted on hand ${currentHandIndex + 1}.`, 'outcome-negative');
            } else {
                updateMessage(`Doubled down to ${currentValue} on hand ${currentHandIndex + 1}.`, '');
            }

            if (playerHands.every(h => h.isStand || h.isBust)) {
                await finalizeDealer();
                return;
            }
            moveToNextHand();
            renderHands();
            refreshActionButtons();
        }

        function playerSplit() {
            if (!inRound) return;
            const current = playerHands[currentHandIndex];
            if (current.cards.length !== 2 || current.cards[0].rank !== current.cards[1].rank || userState.balance < current.bet) {
                return;
            }

            userState.balance -= current.bet;
            const splitCard = current.cards.pop();
            const newPairCard = drawCard();
            newPairCard.faceUp = true;
            playerHands.push({ cards: [splitCard, newPairCard], bet: current.bet, isStand: false, isBust: false, blackjack: false });

            const newCardForCurrent = drawCard();
            newCardForCurrent.faceUp = true;
            current.cards.push(newCardForCurrent);

            current.blackjack = false;
            playerHands[1].blackjack = false;
            playerHands[1].isStand = false;

            updateMessage('Split hand created. Continue with first hand.', '');
            renderHands();
            refreshActionButtons();
        }

        async function finalizeDealer() {
            dealerHand.reveal = true;
            dealerHand.forEach(c => c.faceUp = true);
            renderHands();
            await delay(350);

            while (handValue(dealerHand) < 17) {
                const newCard = drawCard();
                newCard.faceUp = true;
                dealerHand.push(newCard);
                renderHands();
                await delay(450);
            }

            renderHands();

            let totalPayout = 0;
            let totalWager = 0;
            const results = [];

            const dealerVal = handValue(dealerHand);
            const dealerBJ = isBlackjack(dealerHand);

            playerHands.forEach(hand => {
                totalWager += hand.bet;
                const playerVal = handValue(hand.cards);
                if (hand.isBust) {
                    results.push({ text: 'Lost (bust)', delta: 0 - hand.bet });
                    totalPayout -= hand.bet;
                    return;
                }

                if (hand.blackjack && !dealerBJ) {
                    const gain = hand.bet * 2.5;
                    results.push({ text: 'Blackjack! Win', delta: gain - hand.bet });
                    totalPayout += gain - hand.bet;
                    return;
                }

                if (dealerBJ && !hand.blackjack) {
                    results.push({ text: 'Lost (dealer blackjack)', delta: 0 - hand.bet });
                    totalPayout -= hand.bet;
                    return;
                }

                if (handValue(hand.cards) > 21) {
                    results.push({ text: 'Lost (bust)', delta: 0 - hand.bet });
                    totalPayout -= hand.bet;
                    return;
                }

                if (dealerVal > 21) {
                    results.push({ text: 'Dealer bust, win', delta: hand.bet });
                    totalPayout += hand.bet;
                    return;
                }

                if (playerVal > dealerVal) {
                    results.push({ text: 'Win', delta: hand.bet });
                    totalPayout += hand.bet;
                    return;
                }

                if (playerVal === dealerVal) {
                    results.push({ text: 'Push', delta: 0 });
                    totalPayout += hand.bet;
                    return;
                }

                results.push({ text: 'Lose', delta: 0 - hand.bet });
                totalPayout -= hand.bet;
            });

            const net = totalPayout;
            userState.balance += (totalWager + net);
            userState.totalWagered += totalWager;

            const resultsText = results.map((r, i) => `Hand ${i + 1}: ${r.text}`).join(' | ');
            const endText = net > 0 ? `You win $${net.toFixed(2)}!` : net < 0 ? `You lose $${Math.abs(net).toFixed(2)}.` : 'Push.';
            updateMessage(`${resultsText} ${endText}`, net > 0 ? 'outcome-positive' : net < 0 ? 'outcome-negative' : '');

            await syncWallet(net, totalWager);
            inRound = false;
            setButtons('idle');
            renderHands();
            updateUI();
        }

        async function syncWallet(netChange, wagered) {
            try {
                const resp = await fetch('blackjack.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ api: 'update_wallet', delta: netChange, wager: wagered })
                });
                const data = await resp.json();
                if (data.success) {
                    userState.balance = Number(data.balance);
                    userState.totalWagered = Number(data.total_wagered);
                    updateUI();
                } else {
                    updateMessage(`Wallet update failed: ${data.message}`, 'outcome-negative');
                }
            } catch (err) {
                updateMessage('Failed to sync wallet. Please refresh.', 'outcome-negative');
            }
        }

        if (betBtn) betBtn.addEventListener('click', beginRound);
        if (hitBtn) hitBtn.addEventListener('click', playerHit);
        if (standBtn) standBtn.addEventListener('click', playerStand);
        if (doubleBtn) doubleBtn.addEventListener('click', playerDouble);
        if (splitBtn) splitBtn.addEventListener('click', playerSplit);

        setButtons('idle');
        updateUI();

        async function refreshWallet() {
            try {
                const resp = await fetch('blackjack.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ api: 'get_wallet' })
                });
                const data = await resp.json();
                if (data.success) {
                    userState.balance = Number(data.balance);
                    userState.totalWagered = Number(data.total_wagered);
                    updateUI();
                }
            } catch (e) {
                console.warn('Could not refresh wallet', e);
            }
        }

        refreshWallet();
    </script>
</body>
</html>
