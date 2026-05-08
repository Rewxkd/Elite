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

const deckSuits = ['S', 'H', 'D', 'C'];
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
const tablePanelEl = document.getElementById('blackjackTable');
const deckShoeEl = document.getElementById('deckShoe');
const dealerPanelEl = document.getElementById('dealerPanel');
const dealerCardsEl = document.getElementById('dealerCards');
const playerPanelEl = document.getElementById('singlePlayerPanel');
const playerCardsEl = document.getElementById('playerCards');
const playerValueEl = document.getElementById('playerValue');
const dealerValueEl = document.getElementById('dealerValue');
const splitHandsSection = document.getElementById('splitHandsSection');

const INITIAL_DEAL_DELAY = 660;
const INITIAL_HOLE_CARD_DELAY = 580;
const ACTION_DEAL_DELAY = 760;
const ACTION_DEAL_DURATION = 420;
const DEALER_REVEAL_DELAY = 520;
const DEFAULT_DEAL_DURATION = 460;
const DEAL_EASING = 'cubic-bezier(0.18, 0.86, 0.2, 1)';

let deck = [];
let dealerHand = [];
let playerHands = [];
let currentHandIndex = 0;
let inRound = false;
let actionLocked = false;
let nextCardId = 1;
let restoringRound = false;
const cardElements = new Map();

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
                cards.push({ id: nextCardId++, suit, rank, faceUp: true });
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
    return deck.pop();
}

function delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

function cleanCardForSave(card) {
    return {
        id: card.id,
        suit: card.suit,
        rank: card.rank,
        faceUp: Boolean(card.faceUp)
    };
}

function serializeRoundState() {
    return {
        deck: deck.map(cleanCardForSave),
        dealerHand: dealerHand.map(cleanCardForSave),
        dealerReveal: Boolean(dealerHand.reveal),
        playerHands: playerHands.map(hand => ({
            cards: hand.cards.map(cleanCardForSave),
            bet: hand.bet,
            isStand: Boolean(hand.isStand),
            isBust: Boolean(hand.isBust),
            blackjack: Boolean(hand.blackjack),
            result: hand.result || ''
        })),
        currentHandIndex,
        inRound,
        nextCardId,
        userState: {
            balance: userState.balance,
            totalWagered: userState.totalWagered,
            initialBet: userState.initialBet
        },
        message: roundStatusEl?.textContent || '',
    };
}

async function saveRoundState() {
    if (!inRound || restoringRound) return;

    try {
        await fetch('blackjack.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ api: 'save_round', round: serializeRoundState() })
        });
    } catch (e) {
        // The active hand remains playable in memory if a background save fails.
    }
}

async function clearSavedRound() {
    try {
        await fetch('blackjack.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ api: 'clear_round' })
        });
    } catch (e) {
        // A later completed round will overwrite stale saved state.
    }
}

async function loadSavedRound() {
    try {
        const resp = await fetch('blackjack.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ api: 'load_round' })
        });
        const data = await resp.json();
        return data.success ? data.round : null;
    } catch (e) {
        return null;
    }
}

function restoreSavedRound(round) {
    if (!round || !round.inRound || !Array.isArray(round.deck) || !Array.isArray(round.dealerHand) || !Array.isArray(round.playerHands)) {
        return false;
    }

    restoringRound = true;
    deck = round.deck.map(cleanCardForSave);
    dealerHand = round.dealerHand.map(cleanCardForSave);
    dealerHand.reveal = Boolean(round.dealerReveal);
    playerHands = round.playerHands.map(hand => ({
        cards: Array.isArray(hand.cards) ? hand.cards.map(cleanCardForSave) : [],
        bet: Number(hand.bet) || 0,
        isStand: Boolean(hand.isStand),
        isBust: Boolean(hand.isBust),
        blackjack: Boolean(hand.blackjack),
        result: hand.result || ''
    }));
    currentHandIndex = Number.isInteger(round.currentHandIndex) ? round.currentHandIndex : 0;
    inRound = true;
    nextCardId = Math.max(Number(round.nextCardId) || 1, 1);

    if (round.userState) {
        const savedBalance = Number(round.userState.balance);
        const savedTotalWagered = Number(round.userState.totalWagered);
        const savedInitialBet = Number(round.userState.initialBet);
        if (Number.isFinite(savedBalance)) userState.balance = savedBalance;
        if (Number.isFinite(savedTotalWagered)) userState.totalWagered = savedTotalWagered;
        if (Number.isFinite(savedInitialBet)) userState.initialBet = savedInitialBet;
    }

    cardElements.clear();
    updateUI();
    renderHands();
    refreshActionButtons();

    const savedClass = typeof round.messageClass === 'string' ? round.messageClass.replace(/\bis-hiding\b/g, '').trim() : '';
    restoringRound = false;
    return true;
}

async function dealCardToHand(hand, faceUp = true, options = {}) {
    const card = drawCard();
    card.faceUp = faceUp;
    card.justDealt = true;
    card.dealDuration = options.durationMs || DEFAULT_DEAL_DURATION;
    hand.push(card);
    saveRoundState();
    renderHands();
    await delay(options.delayMs || (faceUp ? INITIAL_DEAL_DELAY : INITIAL_HOLE_CARD_DELAY));
    card.justDealt = false;
    card.dealDuration = null;
    await saveRoundState();
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

function splitValue(card) {
    if (!card) return 0;
    return ['10', 'J', 'Q', 'K'].includes(card.rank) ? 10 : card.rank === 'A' ? 11 : Number(card.rank);
}

function canCardsSplit(cards) {
    return cards.length === 2 && splitValue(cards[0]) === splitValue(cards[1]);
}

function getSuitEntity(suit) {
    return {
        S: '&spades;',
        H: '&hearts;',
        D: '&diams;',
        C: '&clubs;'
    }[suit] || suit;
}

function getCardEl(card) {
    if (cardElements.has(card.id)) {
        return cardElements.get(card.id);
    }

    const cardEl = document.createElement('div');
    cardEl.className = 'card is-face-down';
    cardEl.dataset.cardId = card.id;
    cardEl.setAttribute('aria-label', `${card.rank} ${card.suit}`);
    cardEl.innerHTML = `
        <div class="card-inner">
            <div class="card-face card-front">
                <span class="card-corner card-corner-top">
                    <span class="card-rank"></span>
                </span>
                <span class="card-pip"></span>
                <span class="card-corner card-corner-bottom">
                    <span class="card-rank"></span>
                </span>
            </div>
            <div class="card-face card-back"></div>
        </div>
    `;

    cardElements.set(card.id, cardEl);
    return cardEl;
}

function updateCardFace(card, cardEl) {
    const suitEntity = getSuitEntity(card.suit);
    const rankEls = cardEl.querySelectorAll('.card-rank');
    const pipEl = cardEl.querySelector('.card-pip');

    rankEls.forEach(rankEl => {
        rankEl.textContent = card.rank;
    });
    pipEl.innerHTML = suitEntity;
    cardEl.classList.toggle('is-red', card.suit === 'H' || card.suit === 'D');

    if (card.faceUp) {
        if (restoringRound) {
            cardEl.classList.remove('is-face-down');
        } else if (!card.justDealt) {
            flipCardFaceUp(cardEl);
        }
    } else {
        cardEl.classList.add('is-face-down');
    }
}

function flipCardFaceUp(cardEl, delayMs = 0) {
    if (!cardEl.classList.contains('is-face-down')) return;
    if (cardEl.dataset.flipQueued === 'true') return;

    cardEl.dataset.flipQueued = 'true';
    window.setTimeout(() => {
        requestAnimationFrame(() => {
            cardEl.classList.remove('is-face-down');
            window.setTimeout(() => {
                delete cardEl.dataset.flipQueued;
            }, 390);
        });
    }, delayMs);
}

function getDealOrigin() {
    const originRect = deckShoeEl?.getBoundingClientRect();
    if (originRect) {
        return {
            x: originRect.left + originRect.width / 2,
            y: originRect.top + originRect.height / 2
        };
    }

    const tableRect = tablePanelEl?.getBoundingClientRect();
    return {
        x: (tableRect?.right || window.innerWidth) - 72,
        y: (tableRect?.top || 0) + 72
    };
}

function captureCardRects() {
    const rects = new Map();
    cardElements.forEach((el, id) => {
        if (el.isConnected) rects.set(id, el.getBoundingClientRect());
    });
    return rects;
}

function animateCardIntoPlace(card, cardEl, oldRects) {
    const animationToken = String((Number(cardEl.dataset.dealToken || 0) + 1));
    cardEl.dataset.dealToken = animationToken;
    if (cardEl.dealTimer) {
        window.clearTimeout(cardEl.dealTimer);
    }
    if (cardEl.flipTimer) {
        window.clearTimeout(cardEl.flipTimer);
        cardEl.flipTimer = null;
    }

    const newRect = cardEl.getBoundingClientRect();
    const oldRect = oldRects.get(card.id);
    const origin = oldRect
        ? { x: oldRect.left + oldRect.width / 2, y: oldRect.top + oldRect.height / 2 }
        : getDealOrigin();
    const target = {
        x: newRect.left + newRect.width / 2,
        y: newRect.top + newRect.height / 2
    };

    const dx = origin.x - target.x;
    const dy = origin.y - target.y;
    const finalTilt = getComputedStyle(cardEl).getPropertyValue('--tilt').trim() || '0deg';
    const startTilt = oldRect ? finalTilt : '90deg';
    const duration = oldRect ? 160 : (card.dealDuration || DEFAULT_DEAL_DURATION);
    const settledTransform = `translate3d(0, 0, 0) rotate(${finalTilt}) scale(1)`;

    cardEl.style.transition = 'none';
    cardEl.style.opacity = '1';
    cardEl.style.transform = `translate3d(${dx}px, ${dy}px, 0) rotate(${startTilt}) scale(0.94)`;
    cardEl.getBoundingClientRect();

    if (!oldRect && cardEl.animate) {
        const turnTilt = dx > 0 ? '18deg' : '-18deg';
        const animation = cardEl.animate([
            {
                transform: `translate3d(${dx}px, ${dy}px, 0) rotate(90deg) scale(0.94)`,
                filter: 'drop-shadow(0 16px 16px rgba(0, 0, 0, 0.34))',
                offset: 0
            },
            {
                transform: `translate3d(${dx * 0.52}px, ${dy * 0.52}px, 0) rotate(${turnTilt}) scale(1.02)`,
                filter: 'drop-shadow(0 24px 24px rgba(0, 0, 0, 0.32))',
                offset: 0.62
            },
            {
                transform: settledTransform,
                filter: 'drop-shadow(0 14px 14px rgba(0, 0, 0, 0.26))'
            }
        ], {
            duration,
            easing: DEAL_EASING,
            fill: 'forwards'
        });
        cardEl.dealAnimation = animation;
    } else {
        requestAnimationFrame(() => {
            if (cardEl.dataset.dealToken !== animationToken) return;
            requestAnimationFrame(() => {
                if (cardEl.dataset.dealToken !== animationToken) return;
                cardEl.style.transition = `transform ${duration}ms ${DEAL_EASING}, opacity 120ms ease, filter 160ms ease`;
                cardEl.style.transform = settledTransform;
            });
        });
    }

    if (!oldRect && card.faceUp && card.justDealt) {
        cardEl.flipTimer = window.setTimeout(() => {
            if (cardEl.dataset.dealToken !== animationToken) return;
            flipCardFaceUp(cardEl);
            cardEl.flipTimer = null;
        }, Math.round(duration * 0.74));
    }

    cardEl.dealTimer = window.setTimeout(() => {
        if (cardEl.dataset.dealToken !== animationToken) return;
        if (cardEl.dealAnimation) {
            cardEl.dealAnimation.cancel();
            cardEl.dealAnimation = null;
        }
        cardEl.classList.add('is-dealt');
        cardEl.style.transition = '';
        cardEl.style.opacity = '';
        cardEl.style.transform = settledTransform;
        if (card.faceUp && card.justDealt && cardEl.classList.contains('is-face-down')) {
            flipCardFaceUp(cardEl, 20);
        }
        cardEl.dealTimer = null;
    }, duration + 40);
}

function placeCard(card, parent, index, oldRects) {
    const cardEl = getCardEl(card);
    updateCardFace(card, cardEl);
    cardEl.style.setProperty('--tilt', `${(index - 1) * 2.6}deg`);
    parent.appendChild(cardEl);

    const finalTilt = getComputedStyle(cardEl).getPropertyValue('--tilt').trim() || '0deg';
    if (restoringRound) {
        cardEl.classList.add('is-dealt');
        cardEl.style.transition = '';
        cardEl.style.opacity = '';
        cardEl.style.transform = `translate3d(0, 0, 0) rotate(${finalTilt}) scale(1)`;
        return;
    }

    if (card.justDealt || card.justSplit || !cardEl.classList.contains('is-dealt')) {
        animateCardIntoPlace(card, cardEl, oldRects);
    } else {
        cardEl.style.transform = `translate3d(0, 0, 0) rotate(${finalTilt}) scale(1)`;
    }
}

function setHandResultClass(element, result) {
    element.classList.remove('hand-result-win', 'hand-result-lose', 'hand-result-push', 'hand-active');
    if (result === 'win') element.classList.add('hand-result-win');
    if (result === 'lose') element.classList.add('hand-result-lose');
    if (result === 'push') element.classList.add('hand-result-push');
}

function renderHands() {
    const oldRects = captureCardRects();
    dealerCardsEl.innerHTML = '';
    playerCardsEl.innerHTML = '';
    splitHandsSection.innerHTML = '';
    setHandResultClass(dealerPanelEl, '');
    setHandResultClass(playerPanelEl, playerHands[0]?.result || '');

    dealerHand.forEach((card, index) => placeCard(card, dealerCardsEl, index, oldRects));

    if (dealerHand.reveal) {
        dealerValueEl.textContent = handValue(dealerHand);
    } else if (dealerHand.length > 0) {
        dealerValueEl.textContent = `${handValue([dealerHand[0]])} + ?`;
    } else {
        dealerValueEl.textContent = '';
    }

    if (playerHands.length === 0) {
        playerValueEl.textContent = '';
        splitHandsSection.style.display = 'none';
        playerPanelEl.style.display = 'block';
        return;
    }

    if (playerHands.length === 1) {
        splitHandsSection.style.display = 'none';
        playerPanelEl.style.display = 'block';
        playerPanelEl.classList.remove('hand-active');
        playerHands[0].cards.forEach((card, index) => placeCard(card, playerCardsEl, index, oldRects));
        playerValueEl.textContent = handValue(playerHands[0].cards);
        return;
    }

    playerPanelEl.style.display = 'none';
    splitHandsSection.style.display = 'flex';
    playerHands.forEach((hand, idx) => {
        const handCard = document.createElement('div');
        handCard.className = 'split-hand';
        handCard.classList.toggle('is-active', idx === currentHandIndex && !hand.isStand && !hand.isBust && inRound);
        setHandResultClass(handCard, hand.result || '');

        const header = document.createElement('h5');
        header.textContent = `Hand ${idx + 1}`;

        const body = document.createElement('div');
        body.className = 'card-row';

        const info = document.createElement('p');
        const suffix = hand.isBust ? 'Bust' : hand.isStand ? 'Stand' : formatCurrency(hand.bet);
        info.textContent = `${handValue(hand.cards)} ${suffix}`;

        handCard.appendChild(header);
        handCard.appendChild(body);
        handCard.appendChild(info);
        splitHandsSection.appendChild(handCard);
        hand.cards.forEach((card, cardIndex) => placeCard(card, body, cardIndex, oldRects));
    });
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
    const canSplit = canCardsSplit(current.cards) && userState.balance >= current.bet && playerHands.length < 4 && !current.isStand && !current.isBust;
    setButtons('playing', { canSplit, canDouble });
}

function updateMessage(text, cls = '') {
    if (!roundStatusEl) return;
    roundStatusEl.textContent = text;
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

    cardElements.clear();
    deck = shuffleCards(createDeck());
    dealerHand = [];
    playerHands = [{ cards: [], bet: bet, isStand: false, isBust: false, blackjack: false, result: '' }];
    currentHandIndex = 0;
    inRound = true;
    dealerHand.reveal = false;

    updateMessage('Dealing...');
    renderHands();
    await saveRoundState();
    await delay(80);
    await dealCardToHand(playerHands[0].cards, true);
    await dealCardToHand(dealerHand, true);
    await dealCardToHand(playerHands[0].cards, true);
    await dealCardToHand(dealerHand, false);

    const firstHand = playerHands[0];
    firstHand.blackjack = isBlackjack(firstHand.cards);
    firstHand.isStand = firstHand.blackjack;

    const canSplit = canCardsSplit(firstHand.cards) && userState.balance >= bet;
    const canDouble = userState.balance >= bet;

    if (firstHand.blackjack) {
        updateMessage('Blackjack. Dealer reveals.', 'outcome-positive');
        setButtons('idle');
        await finalizeDealer();
        return;
    }

    setButtons('playing', { canSplit, canDouble });
    updateMessage('Your move.');
    renderHands();
    await saveRoundState();
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

    await dealCardToHand(current.cards, true, {
        delayMs: ACTION_DEAL_DELAY,
        durationMs: ACTION_DEAL_DURATION
    });
    const currentValue = handValue(current.cards);

    if (currentValue > 21) {
        current.isBust = true;
        current.isStand = true;
        updateMessage(`Hand ${currentHandIndex + 1} busts.`, 'outcome-negative');
    } else if (currentValue === 21) {
        current.isStand = true;
        updateMessage(`Hand ${currentHandIndex + 1} has 21.`, 'outcome-positive');
    } else {
        updateMessage(`Hand ${currentHandIndex + 1}: ${currentValue}.`);
    }

    if (playerHands.every(h => h.isStand || h.isBust)) {
        await finalizeDealer();
        return;
    }

    if (current.isStand) moveToNextHand();
    renderHands();
    refreshActionButtons();
    await saveRoundState();
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
    updateMessage(`Playing hand ${currentHandIndex + 1}.`);
    await saveRoundState();
}

async function playerDouble() {
    if (!inRound) return;
    const current = playerHands[currentHandIndex];
    if (current.cards.length !== 2 || userState.balance < current.bet) return;

    userState.balance -= current.bet;
    current.bet *= 2;
    updateUI();
    await dealCardToHand(current.cards, true, {
        delayMs: ACTION_DEAL_DELAY,
        durationMs: ACTION_DEAL_DURATION
    });

    const currentValue = handValue(current.cards);
    current.isStand = true;

    if (currentValue > 21) {
        current.isBust = true;
        updateMessage(`Double busts on hand ${currentHandIndex + 1}.`, 'outcome-negative');
    } else {
        updateMessage(`Doubled to ${currentValue}.`);
    }

    if (playerHands.every(h => h.isStand || h.isBust)) {
        await finalizeDealer();
        return;
    }

    moveToNextHand();
    renderHands();
    refreshActionButtons();
    await saveRoundState();
}

async function playerSplit() {
    if (!inRound) return;
    const current = playerHands[currentHandIndex];
    if (!canCardsSplit(current.cards) || userState.balance < current.bet) {
        return;
    }

    userState.balance -= current.bet;
    updateUI();

    const splitCard = current.cards.pop();
    splitCard.justSplit = true;
    const nextHand = { cards: [splitCard], bet: current.bet, isStand: false, isBust: false, blackjack: false, result: '' };
    playerHands.splice(currentHandIndex + 1, 0, nextHand);
    current.blackjack = false;
    nextHand.blackjack = false;

    updateMessage('Splitting...');
    renderHands();
    await saveRoundState();
    await delay(260);
    splitCard.justSplit = false;

    await dealCardToHand(current.cards, true, {
        delayMs: ACTION_DEAL_DELAY,
        durationMs: ACTION_DEAL_DURATION
    });
    await dealCardToHand(nextHand.cards, true, {
        delayMs: ACTION_DEAL_DELAY,
        durationMs: ACTION_DEAL_DURATION
    });

    updateMessage(`Playing hand ${currentHandIndex + 1}.`);
    renderHands();
    refreshActionButtons();
    await saveRoundState();
}

function revealDealerHoleCard() {
    dealerHand.reveal = true;
    dealerHand.forEach(card => {
        if (!card.faceUp) card.justDealt = false;
        card.faceUp = true;
    });
    renderHands();
}

async function finalizeDealer() {
    setButtons('idle');
    revealDealerHoleCard();
    await delay(DEALER_REVEAL_DELAY);

    const liveHands = playerHands.filter(hand => !hand.isBust);
    while (liveHands.length > 0 && handValue(dealerHand) < 17) {
        await dealCardToHand(dealerHand, true, {
            delayMs: ACTION_DEAL_DELAY,
            durationMs: ACTION_DEAL_DURATION
        });
        await delay(180);
    }

    let totalPayout = 0;
    let totalWager = 0;
    const results = [];
    const dealerVal = handValue(dealerHand);
    const dealerBJ = isBlackjack(dealerHand);

    playerHands.forEach(hand => {
        totalWager += hand.bet;
        const playerVal = handValue(hand.cards);

        if (hand.isBust || playerVal > 21) {
            hand.result = 'lose';
            results.push({ text: 'Lost (bust)', delta: 0 - hand.bet });
            totalPayout -= hand.bet;
            return;
        }

        if (hand.blackjack && !dealerBJ) {
            const gain = hand.bet * 2.5;
            hand.result = 'win';
            results.push({ text: 'Blackjack', delta: gain - hand.bet });
            totalPayout += gain - hand.bet;
            return;
        }

        if (dealerBJ && !hand.blackjack) {
            hand.result = 'lose';
            results.push({ text: 'Lost (dealer blackjack)', delta: 0 - hand.bet });
            totalPayout -= hand.bet;
            return;
        }

        if (dealerVal > 21 || playerVal > dealerVal) {
            hand.result = 'win';
            results.push({ text: 'Win', delta: hand.bet });
            totalPayout += hand.bet;
            return;
        }

        if (playerVal === dealerVal) {
            hand.result = 'push';
            results.push({ text: 'Push', delta: 0 });
            totalPayout += hand.bet;
            return;
        }

        hand.result = 'lose';
        results.push({ text: 'Lose', delta: 0 - hand.bet });
        totalPayout -= hand.bet;
    });

    const net = totalPayout;
    userState.balance += (totalWager + net);
    userState.totalWagered += totalWager;
    inRound = false;
    currentHandIndex = -1;
    renderHands();

    const resultsText = results.map((r, i) => `Hand ${i + 1}: ${r.text}`).join(' | ');
    const endText = net > 0 ? `You win ${formatCurrency(net)}.` : net < 0 ? `You lose ${formatCurrency(Math.abs(net))}.` : 'Push.';
    updateMessage(`${resultsText} ${endText}`, net > 0 ? 'outcome-positive' : net < 0 ? 'outcome-negative' : 'outcome-push');

    const walletSynced = await syncWallet(net, totalWager);
    if (walletSynced) {
        await clearSavedRound();
    }
    setButtons('idle');
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
            window.dispatchEvent(new CustomEvent('elite:bet-complete'));
            window.notifyBetFeedsUpdated?.();
            return true;
        } else {
            updateMessage(`Wallet update failed: ${data.message}`, 'outcome-negative');
        }
    } catch (err) {
        updateMessage('Failed to sync wallet. Please refresh.', 'outcome-negative');
    }
    return false;
}

if (betBtn) betBtn.addEventListener('click', withActionLock(beginRound));
if (betInput) betInput.addEventListener('input', updateBetPreview);
if (halfBetBtn) halfBetBtn.addEventListener('click', () => setBetAmount(getBetAmount() / 2));
if (doubleBetBtn) doubleBetBtn.addEventListener('click', () => setBetAmount(getBetAmount() * 2));
if (hitBtn) hitBtn.addEventListener('click', withActionLock(playerHit));
if (standBtn) standBtn.addEventListener('click', withActionLock(playerStand));
if (doubleBtn) doubleBtn.addEventListener('click', withActionLock(playerDouble));
if (splitBtn) splitBtn.addEventListener('click', withActionLock(playerSplit));
window.addEventListener('beforeunload', () => {
    if (!inRound || restoringRound) return;
    const payload = JSON.stringify({ api: 'save_round', round: serializeRoundState() });
    if (navigator.sendBeacon) {
        navigator.sendBeacon('blackjack.php', new Blob([payload], { type: 'application/json' }));
    } else {
        fetch('blackjack.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: payload,
            keepalive: true
        });
    }
});

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

async function initializeBlackjack() {
    setButtons('idle');
    updateUI();
    renderHands();

    const savedRound = await loadSavedRound();
    if (restoreSavedRound(savedRound)) {
        return;
    }

    await refreshWallet();
}

initializeBlackjack();
