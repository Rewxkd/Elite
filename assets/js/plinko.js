document.addEventListener('DOMContentLoaded', function() {
    const footerFavoriteContainer = document.getElementById('footer-favorite-container');
    if (footerFavoriteContainer) {
        new FavoriteButton('plinko', 'Plinko', footerFavoriteContainer);
    }
});

const plinkoScript = document.currentScript;
const plinkoState = {
    balance: Number(plinkoScript?.dataset.balance || 0),
    totalWagered: Number(plinkoScript?.dataset.totalWagered || 0),
    rows: 16,
    difficulty: 'easy',
    activeBalls: 0,
    lastMultiplier: null
};

const payoutPresets = {
    easy: [16, 9, 2, 1.4, 1.4, 1.2, 1.1, 1, 0.5, 1, 1.1, 1.2, 1.4, 1.4, 2, 9, 16],
    medium: [110, 41, 10, 5, 3, 1.5, 1, 0.5, 0.3, 0.5, 1, 1.5, 3, 5, 10, 41, 110],
    hard: [1000, 130, 26, 9, 4, 2, 0.2, 0.2, 0.2, 0.2, 0.2, 2, 4, 9, 26, 130, 1000]
};

const betInput = document.getElementById('betInput');
const betAmountPreview = document.getElementById('betAmountPreview');
const halfBetBtn = document.getElementById('halfBetBtn');
const doubleBetBtn = document.getElementById('doubleBetBtn');
const difficultySelect = document.getElementById('difficultySelect');
const dropBtn = document.getElementById('dropBtn');
const boardEl = document.getElementById('plinkoBoard');
const bucketsEl = document.getElementById('plinkoBuckets');
const boardPanel = document.querySelector('.plinko-board-panel');
const displayBalance = document.getElementById('balanceAmount');
const lastMultiplierText = document.getElementById('lastMultiplierText');
const profitText = document.getElementById('profitText');

let pendingBoardRender = false;
let walletSyncQueue = Promise.resolve();

function formatCurrency(amount) {
    return `$${Number(amount).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })}`;
}

function formatMultiplier(value) {
    if (value >= 1000) return '1K';
    if (Number.isInteger(value)) return String(value);
    return value.toFixed(1);
}

function getBetAmount() {
    const bet = parseFloat(betInput?.value || 0);
    return Number.isFinite(bet) ? Math.max(1, bet) : 1;
}

function setBetAmount(amount) {
    if (!betInput) return;
    const nextBet = Math.max(1, Math.min(Number(amount) || 1, Math.max(1, plinkoState.balance)));
    betInput.value = Number.isInteger(nextBet) ? String(nextBet) : nextBet.toFixed(2);
    updatePanel();
}

function getPayouts() {
    return payoutPresets[plinkoState.difficulty].slice();
}

function updatePanel() {
    if (displayBalance) displayBalance.textContent = formatCurrency(plinkoState.balance);
    if (betAmountPreview) betAmountPreview.textContent = formatCurrency(getBetAmount());
    if (lastMultiplierText) {
        lastMultiplierText.textContent = plinkoState.lastMultiplier
            ? `${formatMultiplier(plinkoState.lastMultiplier)}x`
            : '-';
    }
}

function setControls() {
    const hasActiveBalls = plinkoState.activeBalls > 0;

    // Keep drop button enabled so users can spam/drop multiple balls.
    if (dropBtn) dropBtn.disabled = false;

    // Lock settings while balls are falling so payout/difficulty cannot change mid-drop.
    if (betInput) betInput.disabled = false;
    if (halfBetBtn) halfBetBtn.disabled = false;
    if (doubleBetBtn) doubleBetBtn.disabled = false;
    if (difficultySelect) difficultySelect.disabled = hasActiveBalls;
}

function randomBit() {
    if (window.crypto && window.crypto.getRandomValues) {
        const array = new Uint32Array(1);
        window.crypto.getRandomValues(array);
        return array[0] % 2;
    }
    return Math.random() < 0.5 ? 0 : 1;
}

function randomPath(rows) {
    const path = [];
    let slot = 0;

    for (let i = 0; i < rows; i++) {
        const right = randomBit();
        slot += right;
        path.push(slot);
    }

    return { path, slot };
}

function getBoardMetrics() {
    const width = boardEl?.clientWidth || 620;
    const height = boardEl?.clientHeight || 445;
    const rows = plinkoState.rows;
    const horizontalStep = width / (rows + 2);
    const verticalStep = height / (rows + 0.45);
    const top = verticalStep * 0.58;

    return { width, height, rows, horizontalStep, verticalStep, top };
}

function getPegPoint(row, index, metrics) {
    const pegsInRow = row + 3;
    const rowWidth = (pegsInRow - 1) * metrics.horizontalStep;
    const left = (metrics.width - rowWidth) / 2;

    return {
        x: left + index * metrics.horizontalStep,
        y: metrics.top + row * metrics.verticalStep
    };
}

function renderBoard() {
    if (!boardEl || !bucketsEl || !boardPanel) return;

    if (plinkoState.activeBalls > 0) {
        pendingBoardRender = true;
        return;
    }

    const payouts = getPayouts();
    const metrics = getBoardMetrics();

    boardEl.innerHTML = '';
    bucketsEl.innerHTML = '';
    bucketsEl.style.setProperty('--bucket-count', String(payouts.length));
    boardPanel.dataset.risk = plinkoState.difficulty;

    boardEl.style.setProperty('--peg-size', `${Math.max(5.2, Math.min(8.2, metrics.width / 58))}px`);

    for (let row = 0; row < plinkoState.rows; row++) {
        const pegsInRow = row + 3;

        for (let index = 0; index < pegsInRow; index++) {
            const point = getPegPoint(row, index, metrics);
            const peg = document.createElement('span');
            peg.className = 'plinko-peg';
            peg.dataset.row = String(row);
            peg.dataset.index = String(index);
            peg.style.left = `${point.x}px`;
            peg.style.top = `${point.y}px`;
            boardEl.appendChild(peg);
        }
    }

    payouts.forEach((payout, index) => {
        const bucket = document.createElement('div');
        bucket.className = 'plinko-bucket';
        bucket.dataset.index = String(index);
        bucket.textContent = formatMultiplier(payout);
        bucketsEl.appendChild(bucket);
    });
}

function showPegHit(row, index) {
    const peg = boardEl?.querySelector(`.plinko-peg[data-row="${row}"][data-index="${index}"]`);
    if (!peg) return;

    peg.classList.remove('is-hit');
    void peg.offsetWidth;
    peg.classList.add('is-hit');

    window.setTimeout(() => {
        peg.classList.remove('is-hit');
    }, 420);
}

function animateBall(pathResult) {
    return new Promise(resolve => {
        if (!boardEl) {
            resolve();
            return;
        }

        const metrics = getBoardMetrics();
        const ball = document.createElement('span');
        ball.className = 'plinko-ball';
        boardEl.appendChild(ball);

        const start = {
            x: metrics.width / 2,
            y: Math.max(12, metrics.top - metrics.verticalStep * 0.75)
        };

        const points = [start];

        pathResult.path.forEach((slot, row) => {
            const centerIndex = slot + 1;
            const point = getPegPoint(row, centerIndex, metrics);
            const wobble = (randomBit() ? 1 : -1) * Math.min(7, metrics.horizontalStep * 0.16);

            points.push({
                x: point.x + wobble,
                y: point.y,
                pegRow: row,
                pegIndex: centerIndex
            });
        });

        points.push({
            x: ((pathResult.slot + 0.5) / (plinkoState.rows + 1)) * metrics.width,
            y: metrics.height + 12
        });

        let current = 0;
        const stepDuration = Math.max(52, Math.min(82, 980 / points.length));

        function moveNext() {
            if (current >= points.length) {
                ball.remove();
                resolve();
                return;
            }

            const point = points[current];
            ball.style.transition = current === 0
                ? 'none'
                : `left ${stepDuration}ms ease-in, top ${stepDuration}ms ease-in`;

            ball.style.left = `${point.x}px`;
            ball.style.top = `${point.y}px`;

            if (Number.isInteger(point.pegRow) && Number.isInteger(point.pegIndex)) {
                window.setTimeout(() => showPegHit(point.pegRow, point.pegIndex), Math.max(20, stepDuration - 24));
            }

            current += 1;
            window.setTimeout(moveNext, current === 1 ? 30 : stepDuration);
        }

        moveNext();
    });
}

async function syncWallet(netChange, wagered) {
    try {
        const response = await fetch('plinko.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                api: 'update_wallet',
                delta: netChange,
                wager: wagered
            })
        });

        const data = await response.json();

        if (data.success) {
            plinkoState.balance = Number(data.balance);
            plinkoState.totalWagered = Number(data.total_wagered);
            window.dispatchEvent(new CustomEvent('elite:bet-complete'));
            window.notifyBetFeedsUpdated?.();
        }
    } catch (error) {
        // Keep local display if sync fails.
    }
}

function queueWalletSync(netChange, wagered) {
    walletSyncQueue = walletSyncQueue
        .then(() => syncWallet(netChange, wagered))
        .then(updatePanel)
        .catch(() => {});

    return walletSyncQueue;
}

async function dropBall() {
    const bet = getBetAmount();

    if (!Number.isFinite(bet) || bet <= 0 || bet > plinkoState.balance) {
        return;
    }

    const payouts = getPayouts();
    const pathResult = randomPath(plinkoState.rows);
    const multiplier = payouts[pathResult.slot] || 0;
    const payout = bet * multiplier;
    const net = payout - bet;

    plinkoState.balance -= bet;
    plinkoState.activeBalls += 1;

    setControls();
    updatePanel();

    animateBall(pathResult).then(() => {
        const bucket = bucketsEl?.querySelector(`[data-index="${pathResult.slot}"]`);

        if (bucket) {
            bucket.classList.add('is-hit');
            window.setTimeout(() => bucket.classList.remove('is-hit'), 650);
        }

        plinkoState.balance += payout;
        plinkoState.lastMultiplier = multiplier;

        if (profitText) profitText.textContent = formatCurrency(net);

        updatePanel();

        queueWalletSync(net, bet).finally(() => {
            plinkoState.activeBalls = Math.max(0, plinkoState.activeBalls - 1);
            setControls();

            if (plinkoState.activeBalls === 0 && pendingBoardRender) {
                pendingBoardRender = false;
                renderBoard();
            }

            updatePanel();
        });
    });
}

async function refreshWallet() {
    try {
        const response = await fetch('plinko.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ api: 'get_wallet' })
        });

        const data = await response.json();

        if (data.success) {
            plinkoState.balance = Number(data.balance);
            plinkoState.totalWagered = Number(data.total_wagered);
            updatePanel();
        }
    } catch (error) {
        // Keep local display if refresh fails.
    }
}

if (dropBtn) dropBtn.addEventListener('click', dropBall);
if (betInput) betInput.addEventListener('input', updatePanel);
if (halfBetBtn) halfBetBtn.addEventListener('click', () => setBetAmount(getBetAmount() / 2));
if (doubleBetBtn) doubleBetBtn.addEventListener('click', () => setBetAmount(getBetAmount() * 2));

if (difficultySelect) {
    difficultySelect.addEventListener('change', () => {
        if (plinkoState.activeBalls > 0) return;

        plinkoState.difficulty = difficultySelect.value;
        renderBoard();
        updatePanel();
    });
}

window.addEventListener('resize', () => {
    window.clearTimeout(window.plinkoResizeTimer);
    window.plinkoResizeTimer = window.setTimeout(renderBoard, 120);
});

plinkoState.rows = 16;
plinkoState.difficulty = difficultySelect?.value || 'easy';

renderBoard();
setControls();
updatePanel();
refreshWallet();
