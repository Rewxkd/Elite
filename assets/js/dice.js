document.addEventListener('DOMContentLoaded', function() {
    const footerFavoriteContainer = document.getElementById('footer-favorite-container');
    if (footerFavoriteContainer) {
        new FavoriteButton('dice', 'Dice', footerFavoriteContainer);
    }
});

const diceScript = document.currentScript;
const diceState = {
    balance: Number(diceScript?.dataset.balance || 0),
    totalWagered: Number(diceScript?.dataset.totalWagered || 0),
    winChance: 61,
    isOver: true,
    actionLocked: false,
    history: []
};

const houseEdge = 0.99;
const minChance = 1;
const maxChance = 95;

const betInput = document.getElementById('betInput');
const betAmountPreview = document.getElementById('betAmountPreview');
const halfBetBtn = document.getElementById('halfBetBtn');
const doubleBetBtn = document.getElementById('doubleBetBtn');
const profitInput = document.getElementById('profitInput');
const rollBtn = document.getElementById('rollBtn');
const historyEl = document.getElementById('diceHistory');
const lossRange = document.getElementById('lossRange');
const winRange = document.getElementById('winRange');
const thresholdHandle = document.getElementById('thresholdHandle');
const diceTrack = document.querySelector('.dice-track');
const rollBubble = document.getElementById('rollBubble');
const multiplierInput = document.getElementById('multiplierInput');
const thresholdInput = document.getElementById('thresholdInput');
const directionLabel = document.getElementById('directionLabel');
const directionToggle = document.getElementById('directionToggle');
const winChanceInput = document.getElementById('winChanceInput');
const displayBalance = document.getElementById('balanceAmount');

function formatCurrency(amount) {
    return `$${Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
}

function getBetAmount() {
    const bet = parseFloat(betInput?.value || 0);
    return Number.isFinite(bet) ? Math.max(1, bet) : 1;
}

function setBetAmount(amount) {
    if (!betInput) return;
    const nextBet = Math.max(1, Math.min(Number(amount) || 1, Math.max(1, diceState.balance)));
    betInput.value = Number.isInteger(nextBet) ? String(nextBet) : nextBet.toFixed(2);
    updatePanel();
}

function getMultiplier() {
    return houseEdge * (100 / diceState.winChance);
}

function getThreshold() {
    return diceState.isOver ? 100 - diceState.winChance : diceState.winChance;
}

function isWinningRoll(roll) {
    const threshold = getThreshold();
    return diceState.isOver ? roll > threshold : roll < threshold;
}

function randomRoll() {
    if (window.crypto && window.crypto.getRandomValues) {
        const array = new Uint32Array(1);
        window.crypto.getRandomValues(array);
        return (array[0] % 10000) / 100;
    }
    return Math.floor(Math.random() * 10000) / 100;
}

function updateRanges() {
    const threshold = getThreshold();
    const lossWidth = diceState.isOver ? threshold : 100 - threshold;
    const winWidth = diceState.isOver ? 100 - threshold : threshold;

    if (thresholdHandle) thresholdHandle.style.left = `${threshold}%`;

    if (diceState.isOver) {
        if (lossRange) {
            lossRange.style.left = '10px';
            lossRange.style.right = `${100 - threshold}%`;
            lossRange.style.width = 'auto';
        }
        if (winRange) {
            winRange.style.left = `${threshold}%`;
            winRange.style.right = '10px';
            winRange.style.width = 'auto';
        }
    } else {
        if (winRange) {
            winRange.style.left = '10px';
            winRange.style.right = `${100 - threshold}%`;
            winRange.style.width = 'auto';
        }
        if (lossRange) {
            lossRange.style.left = `${threshold}%`;
            lossRange.style.right = '10px';
            lossRange.style.width = 'auto';
        }
    }

    if (lossWidth <= 0 || winWidth <= 0) {
        return;
    }
}

function renderHistory() {
    if (!historyEl) return;
    historyEl.innerHTML = '';

    diceState.history.slice(-13).forEach(item => {
        const chip = document.createElement('span');
        chip.className = `dice-history-chip${item.win ? ' is-win' : ''}`;
        chip.textContent = item.roll.toFixed(2);
        historyEl.appendChild(chip);
    });
}

function setControls() {
    const locked = diceState.actionLocked;
    if (rollBtn) rollBtn.disabled = locked;
    if (betInput) betInput.disabled = locked;
    if (halfBetBtn) halfBetBtn.disabled = locked;
    if (doubleBetBtn) doubleBetBtn.disabled = locked;
    if (winChanceInput) winChanceInput.disabled = locked;
    if (directionToggle) directionToggle.disabled = locked;
}

function updatePanel() {
    const bet = getBetAmount();
    const multiplier = getMultiplier();
    const profit = bet * multiplier - bet;
    const threshold = getThreshold();

    if (displayBalance) displayBalance.textContent = formatCurrency(diceState.balance);
    if (betAmountPreview) betAmountPreview.textContent = formatCurrency(bet);
    if (profitInput) profitInput.value = Math.max(0, profit).toFixed(2);
    if (multiplierInput) multiplierInput.value = multiplier.toFixed(4);
    if (thresholdInput) thresholdInput.value = threshold.toFixed(2);
    if (winChanceInput) winChanceInput.value = diceState.winChance.toFixed(2);
    if (directionLabel) directionLabel.textContent = diceState.isOver ? 'Roll Over' : 'Roll Under';
    updateRanges();
}

function setRollBubble(roll, visible = true, didWin = null) {
    if (!rollBubble) return;
    rollBubble.innerHTML = `<span>${roll.toFixed(2)}</span>`;
    rollBubble.style.left = `${clamp(roll, 0, 100)}%`;
    rollBubble.classList.toggle('is-visible', visible);
    rollBubble.classList.toggle('is-win', didWin === true);
    rollBubble.classList.toggle('is-loss', didWin === false);
}

function setWinChanceFromPointer(event) {
    const track = diceTrack;
    if (!track) return;

    const rect = track.getBoundingClientRect();
    const trackPadding = 10;
    const usableLeft = rect.left + trackPadding;
    const usableWidth = Math.max(1, rect.width - trackPadding * 2);
    const pointerX = event.clientX ?? event.touches?.[0]?.clientX ?? usableLeft;
    const percent = clamp(((pointerX - usableLeft) / usableWidth) * 100, 0, 100);
    const nextChance = diceState.isOver ? 100 - percent : percent;

    diceState.winChance = clamp(nextChance, minChance, maxChance);
    updatePanel();
}

function startThresholdDrag(event) {
    event.preventDefault();
    setWinChanceFromPointer(event);

    const move = moveEvent => setWinChanceFromPointer(moveEvent);
    const stop = () => {
        window.removeEventListener('pointermove', move);
        window.removeEventListener('pointerup', stop);
        window.removeEventListener('pointercancel', stop);
    };

    window.addEventListener('pointermove', move);
    window.addEventListener('pointerup', stop);
    window.addEventListener('pointercancel', stop);
}

async function syncWallet(netChange, wagered) {
    try {
        const response = await fetch('dice.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ api: 'update_wallet', delta: netChange, wager: wagered })
        });
        const data = await response.json();
        if (data.success) {
            diceState.balance = Number(data.balance);
            diceState.totalWagered = Number(data.total_wagered);
            window.dispatchEvent(new CustomEvent('elite:bet-complete'));
            window.notifyBetFeedsUpdated?.();
        }
    } catch (error) {
        // Keep the local display if the wallet sync fails.
    }
}

async function rollDice() {
    if (diceState.actionLocked) return;

    const bet = getBetAmount();
    if (!Number.isFinite(bet) || bet <= 0 || bet > diceState.balance) {
        return;
    }

    diceState.actionLocked = true;
    diceState.balance -= bet;
    setControls();
    updatePanel();

    const roll = randomRoll();
    const didWin = isWinningRoll(roll);
    const payout = didWin ? bet * getMultiplier() : 0;
    const net = payout - bet;

    window.setTimeout(() => setRollBubble(roll, true, didWin), 40);
    await new Promise(resolve => window.setTimeout(resolve, 520));

    diceState.balance += payout;
    diceState.history.push({ roll, win: didWin });
    diceState.history = diceState.history.slice(-13);
    renderHistory();
    updatePanel();

    await syncWallet(net, bet);
    diceState.actionLocked = false;
    setControls();
    updatePanel();
}

async function refreshWallet() {
    try {
        const response = await fetch('dice.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ api: 'get_wallet' })
        });
        const data = await response.json();
        if (data.success) {
            diceState.balance = Number(data.balance);
            diceState.totalWagered = Number(data.total_wagered);
            updatePanel();
        }
    } catch (error) {
        // Keep the local display if the background wallet refresh fails.
    }
}

if (rollBtn) rollBtn.addEventListener('click', rollDice);
if (betInput) betInput.addEventListener('input', updatePanel);
if (halfBetBtn) halfBetBtn.addEventListener('click', () => setBetAmount(getBetAmount() / 2));
if (doubleBetBtn) doubleBetBtn.addEventListener('click', () => setBetAmount(getBetAmount() * 2));
if (directionToggle) {
    directionToggle.addEventListener('click', () => {
        diceState.isOver = !diceState.isOver;
        updatePanel();
    });
}
if (thresholdHandle) thresholdHandle.addEventListener('pointerdown', startThresholdDrag);
if (diceTrack) diceTrack.addEventListener('pointerdown', startThresholdDrag);
if (winChanceInput) {
    winChanceInput.addEventListener('input', () => {
        const chance = parseFloat(winChanceInput.value || diceState.winChance);
        diceState.winChance = clamp(Number.isFinite(chance) ? chance : diceState.winChance, minChance, maxChance);
        updatePanel();
    });

    winChanceInput.addEventListener('blur', updatePanel);
}

renderHistory();
setRollBubble(50, false, null);
setControls();
updatePanel();
refreshWallet();
