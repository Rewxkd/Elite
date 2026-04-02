<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = intval($_SESSION['user_id']);

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

// GET and render page
$stmt = $conn->prepare('SELECT balance, total_wagered FROM wallets WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$wallet = $result->fetch_assoc();
$stmt->close();

$balance = floatval($wallet['balance'] ?? 0);
$total_wagered = floatval($wallet['total_wagered'] ?? 0);

// now render HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Blackjack | Elite</title>
    <link rel="stylesheet" href="style.css" />
    <style>
        .blackjack-area { max-width: 1200px; margin: 1rem auto 3rem; padding: 1rem; background: rgba(4, 11, 26, 0.85); border: 1px solid rgba(70, 142, 216, 0.3); border-radius: 20px; backdrop-filter: blur(10px); box-shadow: 0 12px 28px rgba(0, 0, 0, 0.55); }
        .blackjack-wrapper { display: grid; grid-template-columns: 280px 1fr; gap: 1rem; align-items: start; }
        .left-panel, .table-panel { background: rgba(8, 19, 40, 0.65); border: 1px solid rgba(56, 125, 211, 0.25); border-radius: 14px; padding: 1rem; }
        .left-panel .panel { background: transparent; border: none; padding:0; }
        .table-panel { min-height: 520px; }
        .hand-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; align-items: start; margin-top: 0.75rem; }
        .panel { background: rgba(8,19,40,0.7); border: 1px solid rgba(56,125,211,0.25); border-radius: 12px; padding: 0.9rem; min-width: 180px; flex: 1; }
        .cards { display: flex; gap: 0.5rem; flex-wrap: wrap; min-height: 90px; }

        .card {
            width: 84px;
            height: 118px;
            border-radius: 10px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.45);
            overflow: hidden;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.3s ease, box-shadow 0.2s ease;
            border: 1px solid rgba(193, 220, 255, 0.8);
            background: #ffffff;
        }

        .card:hover {
            transform: translateY(-4px) scale(1.03);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.45);
        }

        .card-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .card[data-flip="true"] {
            transform: rotateY(180deg);
        }

        .card-face {
            position: absolute;
            inset: 0;
            backface-visibility: hidden;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-back {
            background: linear-gradient(45deg, #172f6f, #104ea2);
            color: #fff;
            transform: rotateY(180deg);
            font-size: 1.2rem;
        }

        .card-front {
            background: linear-gradient(135deg, #fff, #f8f8f8);
            color: #1a2030;
        }

        .card.animate {
            animation: card-enter 0.25s ease forwards;
        }

        @keyframes card-enter {
            0% { transform: translateY(-20px) scale(0.95); opacity: 0; }
            100% { transform: translateY(0) scale(1); opacity: 1; }
        }

        .status { color:#fff; font-weight:700; font-size: 1rem; min-height: 1.2rem; }
        .round-status { display:flex; align-items:center; gap:1rem; margin-bottom:1rem; padding:0.75rem; border:1px solid rgba(74, 158, 245,0.3); border-radius:12px; background: rgba(17,35,68, 0.65); }
        .deck-preview img { width: 46px; height: 62px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.35); }
        #roundStatus { color:#c5d8ff; font-size:0.95rem; font-weight: 600; }
        .controls { width: 100%; }
        .action-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 8px; }
        .action-grid button, .bet-actions button { width: 100%; padding: 12px 0; border-radius: 10px; border: 1px solid rgba(118, 156, 255, 0.6); background: rgba(45, 84, 140, 0.6); color: #fff; font-size: 1rem; font-weight: 700; cursor: pointer; transition: transform 0.2s ease, background 0.2s ease; }
        #betInput { width: 100%; max-width: 150px; padding: 0.65rem 0.75rem; border-radius: 10px; border: 1px solid rgba(118, 156, 255, 0.55); background: rgba(24, 37, 74, 0.75); color: #fff; font-size: 1.07rem; font-weight: 600; }
        #betInput:focus { outline: none; box-shadow: 0 0 0 2px rgba(84, 147, 255, 0.55); border-color: rgba(129, 175, 255, 0.8); }
        .action-grid button:hover:not(:disabled), .bet-actions button:hover:not(:disabled) { transform: translateY(-2px); background: rgba(65, 115, 213, 0.8); }
        .action-grid button:disabled, .bet-actions button:disabled { opacity: 0.45; cursor: not-allowed; }
        .bet-actions { margin-top: 10px; }
        .split-layout { display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 0.75rem; }
        .split-hand { flex: 1 1 48%; min-width: 180px; border: 1px solid rgba(70, 142, 216, 0.35); background: rgba(8, 17, 31, 0.65); border-radius: 12px; padding: 0.75rem; }
        .dealer-panel, .player-panel { background: rgba(8, 16, 28, 0.65); padding: 0.9rem; border: 1px solid rgba(75, 140, 232, 0.3); border-radius: 12px; margin-bottom: 0.75rem; }
        .dealer-panel h4, .player-panel h4 { margin: 0 0 0.5rem; color: #a2c4ff; }
        .card-row { display: flex; gap: 0.6rem; flex-wrap: wrap; justify-content: center; }
        .outcome-positive { color:#7fffd4; }
        .outcome-negative { color:#ff7272; }
        .table-info { font-size:0.95rem; }
    </style>
</head>
<body>
    <aside class="sidebar" id="side" aria-hidden="true">
        <button class="toggle" id="toggle" aria-label="Toggle navigation" aria-expanded="false">☰</button>
        <nav class="navigation">
            <a href="index.php" class="item"><span class="icon">🏠</span><span class="text">Home</span></a>
            <a href="blackjack.php" class="item" id="active"><span class="icon">♠️</span><span class="text">Blackjack</span></a>
            <a href="#" class="item"><span class="icon">🎰</span><span class="text">Other</span></a>
        </nav>
    </aside>

    <header class="header">
        <div class="header-box">
            <div class="logo"><img src="Elite-logo.png" alt="Elite"></div>
            <div class="balance" id="balancePanel"><span class="balance-amount" id="balanceAmount">$<?php echo number_format($balance,2); ?></span></div>
            <div class="header-buttons"><a href="index.php" class="button">Back</a></div>
        </div>
    </header>

    <main class="container blackjack-area">
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
    </main>

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
        const message = document.getElementById('message');
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
            displayBalance.textContent = formatCurrency(userState.balance);
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

            hitBtn.disabled = !isPlaying;
            standBtn.disabled = !isPlaying;
            doubleBtn.disabled = !isPlaying || !canDouble;
            splitBtn.disabled = !isPlaying || !canSplit;
            betBtn.disabled = isPlaying;
            betInput.disabled = isPlaying;
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
            updateRoundStatus(text);
        }

        function updateRoundStatus(text) {
            roundStatusEl.textContent = text;
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

            // Deal sequence with delay
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
            updateMessage(`Standing on hand ${currentHandIndex}.`, '');
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

        betBtn.addEventListener('click', beginRound);
        hitBtn.addEventListener('click', playerHit);
        standBtn.addEventListener('click', playerStand);
        doubleBtn.addEventListener('click', playerDouble);
        splitBtn.addEventListener('click', playerSplit);

        setButtons('idle');
        updateUI();

        // Initialize by fetching wallet (sometimes updated elsewhere)
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

        const toggle = document.getElementById('toggle');
        toggle.addEventListener('click', () => {
            const isOpen = document.body.classList.toggle('open');
            document.getElementById('side').setAttribute('aria-hidden', (!isOpen).toString());
            toggle.setAttribute('aria-expanded', isOpen.toString());
        });
    </script>
</body>
</html>
