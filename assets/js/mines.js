document.addEventListener('DOMContentLoaded', function() {
    const footerFavoriteContainer = document.getElementById('footer-favorite-container');
    if (footerFavoriteContainer) {
        new FavoriteButton('mines', 'Mines', footerFavoriteContainer);
    }
});

const minesScript = document.currentScript;
const minesState = {
    balance: Number(minesScript?.dataset.balance || 0),
    totalWagered: Number(minesScript?.dataset.totalWagered || 0),
    bet: 0,
    mines: 5,
    safeRevealed: 0,
    mineTiles: new Set(),
    revealedTiles: new Set(),
    inRound: false,
    actionLocked: false
};

const totalTiles = 25;
const houseEdge = 0.99;

const betInput = document.getElementById('betInput');
const betAmountPreview = document.getElementById('betAmountPreview');
const halfBetBtn = document.getElementById('halfBetBtn');
const doubleBetBtn = document.getElementById('doubleBetBtn');
const minesInput = document.getElementById('minesInput');
const lessMinesBtn = document.getElementById('lessMinesBtn');
const moreMinesBtn = document.getElementById('moreMinesBtn');
const startBtn = document.getElementById('startBtn');
const cashoutBtn = document.getElementById('cashoutBtn');
const boardEl = document.getElementById('minesBoard');
const displayBalance = document.getElementById('balanceAmount');
const roundStatusEl = document.getElementById('roundStatus');
const safeChanceText = document.getElementById('safeChanceText');
const multiplierText = document.getElementById('multiplierText');
const profitText = document.getElementById('profitText');
const safeCountText = document.getElementById('safeCountText');

function formatCurrency(amount) {
    return `$${Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function formatMultiplier(value) {
    return `${value.toFixed(2)}x`;
}

function showWinResult(multiplier, profit) {
    const overlay = document.querySelector('.mines-board-panel') || document.querySelector('.game-overlay');
    if (!overlay) return;

    const existing = overlay.querySelector('.win-result-popover');
    if (existing) {
        existing.remove();
    }

    const popup = document.createElement('div');
    popup.className = 'win-result-popover';
    popup.setAttribute('role', 'status');
    popup.innerHTML = `
        <strong>${formatMultiplier(multiplier)}</strong>
        <span class="win-result-line"></span>
        <span class="win-result-amount">${formatCurrency(profit)}</span>
    `;
    overlay.appendChild(popup);

    window.setTimeout(() => {
        popup.classList.add('is-hiding');
        window.setTimeout(() => popup.remove(), 220);
    }, 2600);
}

function getBetAmount() {
    const bet = parseFloat(betInput?.value || 0);
    return Number.isFinite(bet) ? Math.max(1, bet) : 1;
}

function setBetAmount(amount) {
    if (!betInput) return;
    const nextBet = Math.max(1, Math.min(Number(amount) || 1, Math.max(1, minesState.balance)));
    betInput.value = Number.isInteger(nextBet) ? String(nextBet) : nextBet.toFixed(2);
    updatePanel();
}

function getMineCount() {
    const mines = parseInt(minesInput?.value || 5, 10);
    return Math.max(1, Math.min(24, Number.isFinite(mines) ? mines : 5));
}

function setMineCount(amount) {
    if (!minesInput || minesState.inRound) return;
    const nextMines = Number(amount);
    minesState.mines = Math.max(1, Math.min(24, Number.isFinite(nextMines) ? nextMines : 5));
    minesInput.value = String(minesState.mines);
    updatePanel();
}

function getSurvivalProbability(safeClicks) {
    let probability = 1;
    for (let i = 0; i < safeClicks; i++) {
        probability *= (totalTiles - minesState.mines - i) / (totalTiles - i);
    }
    return Math.max(probability, Number.EPSILON);
}

function getCurrentMultiplier() {
    if (minesState.safeRevealed === 0) {
        return 1;
    }
    return (1 / getSurvivalProbability(minesState.safeRevealed)) * houseEdge;
}

function getNextSafeChance() {
    const unrevealed = totalTiles - minesState.safeRevealed;
    const safeLeft = totalTiles - minesState.mines - minesState.safeRevealed;
    if (unrevealed <= 0 || safeLeft <= 0) return 0;
    return (safeLeft / unrevealed) * 100;
}

function updatePanel() {
    const displayBet = minesState.inRound ? minesState.bet : getBetAmount();
    const multiplier = getCurrentMultiplier();
    const payout = minesState.safeRevealed > 0 ? displayBet * multiplier : 0;
    const profit = Math.max(0, payout - displayBet);

    if (displayBalance) displayBalance.textContent = formatCurrency(minesState.balance);
    if (betAmountPreview) betAmountPreview.textContent = formatCurrency(displayBet);
    if (safeChanceText) safeChanceText.textContent = `${getNextSafeChance().toFixed(2)}%`;
    if (multiplierText) multiplierText.textContent = formatMultiplier(multiplier);
    if (profitText) profitText.textContent = formatCurrency(profit);
    if (safeCountText) safeCountText.textContent = String(minesState.safeRevealed);

    const canCashOut = minesState.inRound && minesState.safeRevealed > 0 && !minesState.actionLocked;
    if (cashoutBtn) {
        cashoutBtn.disabled = !canCashOut;
        cashoutBtn.textContent = canCashOut ? `Cash Out ${formatCurrency(payout)}` : 'Cash Out';
    }
}

function setControls() {
    const locked = minesState.actionLocked;
    if (startBtn) startBtn.disabled = locked || minesState.inRound;
    if (betInput) betInput.disabled = locked || minesState.inRound;
    if (halfBetBtn) halfBetBtn.disabled = locked || minesState.inRound;
    if (doubleBetBtn) doubleBetBtn.disabled = locked || minesState.inRound;
    if (minesInput) minesInput.disabled = locked || minesState.inRound;
    if (lessMinesBtn) lessMinesBtn.disabled = locked || minesState.inRound;
    if (moreMinesBtn) moreMinesBtn.disabled = locked || minesState.inRound;
    updatePanel();
}

function updateStatus(message, tone = '') {
    if (!roundStatusEl) return;
    roundStatusEl.textContent = message;
    roundStatusEl.className = tone;
}

function randomTileIndex(max) {
    if (window.crypto && window.crypto.getRandomValues) {
        const array = new Uint32Array(1);
        window.crypto.getRandomValues(array);
        return array[0] % max;
    }
    return Math.floor(Math.random() * max);
}

function generateMines(count) {
    const mines = new Set();
    while (mines.size < count) {
        mines.add(randomTileIndex(totalTiles));
    }
    return mines;
}

function renderBoard(revealAll = false) {
    if (!boardEl) return;
    boardEl.innerHTML = '';

    for (let i = 0; i < totalTiles; i++) {
        const tile = document.createElement('button');
        const isMine = minesState.mineTiles.has(i);
        const isRevealed = minesState.revealedTiles.has(i);
        tile.className = 'mines-tile';
        tile.type = 'button';
        tile.dataset.index = String(i);
        tile.disabled = !minesState.inRound || isRevealed || minesState.actionLocked;
        tile.setAttribute('aria-label', `Tile ${i + 1}`);

        if (isRevealed && !isMine) {
            tile.classList.add('is-safe');
            tile.textContent = '';
        } else if ((isRevealed || revealAll) && isMine) {
            tile.classList.add('is-mine');
            tile.textContent = '';
        } else if (revealAll && !isMine) {
            tile.classList.add('is-muted-safe');
            tile.textContent = '';
        } else {
            tile.textContent = '';
        }

        tile.addEventListener('click', () => revealTile(i));
        boardEl.appendChild(tile);
    }
}

function beginRound() {
    if (minesState.inRound || minesState.actionLocked) return;

    const bet = getBetAmount();
    if (!Number.isFinite(bet) || bet <= 0) {
        updateStatus('Enter a valid bet amount.', 'outcome-negative');
        return;
    }

    if (bet > minesState.balance) {
        updateStatus('Not enough balance for that bet.', 'outcome-negative');
        return;
    }

    minesState.bet = bet;
    minesState.mines = getMineCount();
    minesState.safeRevealed = 0;
    minesState.mineTiles = generateMines(minesState.mines);
    minesState.revealedTiles = new Set();
    minesState.inRound = true;
    minesState.balance -= bet;

    updateStatus('Pick a tile. You can cash out after any safe reveal.');
    renderBoard();
    setControls();
}

async function revealTile(index) {
    if (!minesState.inRound || minesState.actionLocked || minesState.revealedTiles.has(index)) return;

    minesState.revealedTiles.add(index);

    if (minesState.mineTiles.has(index)) {
        updateStatus(`Mine hit. You lost ${formatCurrency(minesState.bet)}.`, 'outcome-negative');
        minesState.inRound = false;
        renderBoard(true);
        await finishRound(-minesState.bet);
        return;
    }

    minesState.safeRevealed += 1;
    renderBoard();

    if (minesState.safeRevealed >= totalTiles - minesState.mines) {
        await cashOut(true);
        return;
    }

    const multiplier = getCurrentMultiplier();
    updateStatus(`Safe tile. Current multiplier is ${formatMultiplier(multiplier)}.`);
    updatePanel();
}

async function cashOut(auto = false) {
    if (!minesState.inRound || minesState.safeRevealed === 0 || minesState.actionLocked) return;

    const payout = minesState.bet * getCurrentMultiplier();
    const net = payout - minesState.bet;
    minesState.balance += payout;
    minesState.inRound = false;
    updateStatus(`${auto ? 'Board cleared' : 'Cashed out'} for ${formatCurrency(payout)}.`, 'outcome-positive');
    if (net > 0) {
        showWinResult(getCurrentMultiplier(), net);
    }
    renderBoard(true);
    updatePanel();
    await finishRound(net);
}

async function finishRound(netChange) {
    minesState.actionLocked = true;
    setControls();

    try {
        const response = await fetch('mines.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ api: 'update_wallet', delta: netChange, wager: minesState.bet })
        });
        const data = await response.json();
        if (data.success) {
            minesState.balance = Number(data.balance);
            minesState.totalWagered = Number(data.total_wagered);
        } else {
            updateStatus(`Wallet update failed: ${data.message}`, 'outcome-negative');
        }
    } catch (error) {
        updateStatus('Failed to sync wallet. Please refresh.', 'outcome-negative');
    } finally {
        minesState.actionLocked = false;
        minesState.bet = 0;
        setControls();
        updatePanel();
    }
}

async function refreshWallet() {
    try {
        const response = await fetch('mines.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ api: 'get_wallet' })
        });
        const data = await response.json();
        if (data.success) {
            minesState.balance = Number(data.balance);
            minesState.totalWagered = Number(data.total_wagered);
            updatePanel();
        }
    } catch (error) {
        // Keep the local display if the background wallet refresh fails.
    }
}

if (startBtn) startBtn.addEventListener('click', beginRound);
if (cashoutBtn) cashoutBtn.addEventListener('click', () => cashOut(false));
if (betInput) betInput.addEventListener('input', updatePanel);
if (halfBetBtn) halfBetBtn.addEventListener('click', () => setBetAmount(getBetAmount() / 2));
if (doubleBetBtn) doubleBetBtn.addEventListener('click', () => setBetAmount(getBetAmount() * 2));
if (minesInput) minesInput.addEventListener('input', () => setMineCount(getMineCount()));
if (lessMinesBtn) lessMinesBtn.addEventListener('click', () => setMineCount(getMineCount() - 1));
if (moreMinesBtn) moreMinesBtn.addEventListener('click', () => setMineCount(getMineCount() + 1));

minesState.mines = getMineCount();
renderBoard();
setControls();
refreshWallet();
