document.addEventListener('DOMContentLoaded', function() {
    const footerFavoriteContainer = document.getElementById('footer-favorite-container');
    if (footerFavoriteContainer) {
        new FavoriteButton('blackjack', 'Blackjack', footerFavoriteContainer);
    }
});
const blackjackScript = document.currentScript;
const userState = {
    balance: Number(blackjackScript?.dataset.balance || 0),
    totalWagered: Number(blackjackScript?.dataset.totalWagered || 0),
    initialBet: 0
};

        const deckSuits = ['♠', '♥', '♦', '♣'];
        const deckRanks = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];

        const betInput = document.getElementById('betInput');
        const betAmountPreview = document.getElementById('betAmountPreview');
        const halfBetBtn = document.getElementById('halfBetBtn');
        const doubleBetBtn = document.getElementById('doubleBetBtn');
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
        let actionLocked = false;

        function formatCurrency(amount) {
            return `$${amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }

        function getBetAmount() {
            const bet = parseFloat(betInput?.value || 0);
            return Number.isFinite(bet) ? Math.max(1, bet) : 1;
        }

        function setBetAmount(amount) {
            if (!betInput) return;
            const nextBet = Math.max(1, Math.min(Number(amount) || 1, Math.max(1, userState.balance)));
            betInput.value = Number.isInteger(nextBet) ? String(nextBet) : nextBet.toFixed(2);
            updateBetPreview();
        }

        function updateBetPreview() {
            if (betAmountPreview) {
                betAmountPreview.textContent = formatCurrency(getBetAmount());
            }
        }

        function updateUI() {
            if (displayBalance) {
                displayBalance.textContent = formatCurrency(userState.balance);
            }
            updateBetPreview();
        }

        function createDeck() {
            const cards = [];
            for (let d = 0; d < 6; d++) {
                deckSuits.forEach(suit => {
                    deckRanks.forEach(rank => {
                        cards.push({ suit, rank });
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
                dealerValueEl.textContent = handValue(dealerHand);
            } else if (dealerHand.length > 0) {
                const visibleValue = handValue([dealerHand[0]]);
                dealerValueEl.textContent = `${visibleValue} + ?`;
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
                playerValueEl.textContent = handValue(playerHands[0].cards);
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

            if (hitBtn) hitBtn.disabled = actionLocked || !isPlaying;
            if (standBtn) standBtn.disabled = actionLocked || !isPlaying;
            if (doubleBtn) doubleBtn.disabled = actionLocked || !isPlaying || !canDouble;
            if (splitBtn) splitBtn.disabled = actionLocked || !isPlaying || !canSplit;
            if (betBtn) betBtn.disabled = actionLocked || isPlaying;
            if (betInput) betInput.disabled = actionLocked || isPlaying;
            if (halfBetBtn) halfBetBtn.disabled = actionLocked || isPlaying;
            if (doubleBetBtn) doubleBetBtn.disabled = actionLocked || isPlaying;
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
            roundStatusEl.className = cls;
        }

        function withActionLock(action) {
            return async function() {
                if (actionLocked) return;

                actionLocked = true;
                refreshActionButtons();

                try {
                    await action();
                } finally {
                    actionLocked = false;
                    refreshActionButtons();
                }
            };
        }

        async function beginRound() {
            if (inRound) return;

            const bet = getBetAmount();
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

        if (betBtn) betBtn.addEventListener('click', withActionLock(beginRound));
        if (betInput) betInput.addEventListener('input', updateBetPreview);
        if (halfBetBtn) halfBetBtn.addEventListener('click', () => setBetAmount(getBetAmount() / 2));
        if (doubleBetBtn) doubleBetBtn.addEventListener('click', () => setBetAmount(getBetAmount() * 2));
        if (hitBtn) hitBtn.addEventListener('click', withActionLock(playerHit));
        if (standBtn) standBtn.addEventListener('click', withActionLock(playerStand));
        if (doubleBtn) doubleBtn.addEventListener('click', withActionLock(playerDouble));
        if (splitBtn) splitBtn.addEventListener('click', withActionLock(playerSplit));

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
                // Keep the local display if the background wallet refresh fails.
            }
        }

        refreshWallet();
