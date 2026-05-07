const liveStatsScript = document.currentScript;
const siteRoot = new URL('../../', liveStatsScript?.src || window.location.href);
const betsApiUrl = new URL('api/bets.php', siteRoot);
let refreshInFlight = null;
let refreshQueued = false;
let latestFeedSignature = '';

function formatBetCurrency(amount) {
    return `$${Number(amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));
}

function assetUrl(path) {
    return path ? new URL(path, siteRoot).href : '';
}

function pageUrl(path) {
    return path ? new URL(path, siteRoot).href : '#';
}

function currentBetsFilter() {
    return document.querySelector('[data-bets-filter].active')?.dataset.betsFilter || 'live';
}

function captureRects(elements) {
    const rects = new Map();
    elements.forEach(element => {
        if (element.dataset.betId) {
            rects.set(element.dataset.betId, element.getBoundingClientRect());
        }
    });
    return rects;
}

function animateMovedElements(container, selector, oldRects, axis, animateNew = true) {
    const elements = Array.from(container.querySelectorAll(selector));

    elements.forEach(element => {
        const oldRect = oldRects.get(element.dataset.betId);
        const newRect = element.getBoundingClientRect();

        if (!oldRect) {
            if (!animateNew) return;
            element.classList.add(axis === 'x' ? 'feed-item-new-x' : 'feed-item-new-y');
            window.setTimeout(() => element.classList.remove('feed-item-new-x', 'feed-item-new-y'), 520);
            return;
        }

        const dx = oldRect.left - newRect.left;
        const dy = oldRect.top - newRect.top;
        if (Math.abs(dx) < 1 && Math.abs(dy) < 1) return;

        element.style.transition = 'none';
        element.style.transform = `translate(${dx}px, ${dy}px)`;
        element.style.opacity = '0.94';
        element.getBoundingClientRect();

        requestAnimationFrame(() => {
            element.style.transition = 'transform 360ms cubic-bezier(0.2, 0.82, 0.2, 1), opacity 220ms ease';
            element.style.transform = 'translate(0, 0)';
            element.style.opacity = '';
            window.setTimeout(() => {
                element.style.transition = '';
                element.style.transform = '';
                element.style.opacity = '';
            }, 390);
        });
    });
}

function applyLiveStatsFilter() {
    const filter = currentBetsFilter();
    const rows = document.querySelectorAll('[data-bet-row]');
    const emptyMineRow = document.querySelector('[data-bets-empty="mine"]');
    let visibleRows = 0;

    rows.forEach(row => {
        const shouldShow = filter === 'live' || row.dataset.isMine === 'true';
        row.hidden = !shouldShow;
        if (shouldShow) visibleRows += 1;
    });

    if (emptyMineRow) {
        emptyMineRow.hidden = filter !== 'mine' || visibleRows > 0;
    }
}

function setupLiveStatsFilters() {
    const tabs = document.querySelectorAll('[data-bets-filter]');
    if (!tabs.length) return;

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(otherTab => {
                const isActive = otherTab === tab;
                otherTab.classList.toggle('active', isActive);
                otherTab.setAttribute('aria-selected', isActive.toString());
            });
            applyLiveStatsFilter();
        });
    });
}

function renderGameMark(bet) {
    if (bet.image) {
        return `<span class="bets-game-mark has-logo"><img src="${assetUrl(bet.image)}" alt=""></span>`;
    }
    return `<span class="bets-game-mark">${escapeHtml(String(bet.code || bet.game_name || '').slice(0, 2).toUpperCase())}</span>`;
}

function renderLiveActivity(bets) {
    const wrap = document.querySelector('.bets-stats-table-wrap');
    if (!wrap) return;

    const oldElements = Array.from(wrap.querySelectorAll('[data-bet-row]'));
    const oldRects = captureRects(oldElements);
    const shouldAnimateNew = oldRects.size > 0 || oldElements.length === 0;

    if (!bets.length) {
        wrap.innerHTML = '<div class="latest-bets-empty">No bets yet.</div>';
        return;
    }

    const rows = bets.map(bet => `
        <tr data-bet-row data-bet-id="${bet.bet_id}" data-is-mine="${bet.is_mine ? 'true' : 'false'}">
            <td data-label="Game">
                <span class="bets-game-cell">
                    ${renderGameMark(bet)}
                    <span>${escapeHtml(bet.game_name)}</span>
                </span>
            </td>
            <td data-label="User">${escapeHtml(bet.username)}</td>
            <td data-label="Bet Amount">${formatBetCurrency(bet.wager_amount)}</td>
            <td data-label="Multiplier">${Number(bet.multiplier || 0).toFixed(2)}x</td>
            <td data-label="Payout" class="${Number(bet.payout_amount) > 0 ? 'is-positive' : 'is-muted'}">${formatBetCurrency(bet.payout_amount)}</td>
        </tr>
    `).join('');

    wrap.innerHTML = `
        <table class="bets-stats-table">
            <thead>
                <tr>
                    <th>Game</th>
                    <th>User</th>
                    <th>Bet Amount</th>
                    <th>Multiplier</th>
                    <th>Payout</th>
                </tr>
            </thead>
            <tbody>
                ${rows}
                <tr class="bets-empty-row" data-bets-empty="mine" hidden>
                    <td colspan="5">No bets from you yet.</td>
                </tr>
            </tbody>
        </table>
    `;

    applyLiveStatsFilter();
    requestAnimationFrame(() => animateMovedElements(wrap, '[data-bet-row]', oldRects, 'y', shouldAnimateNew));
}

function renderLatestBets(bets) {
    const row = document.querySelector('.latest-bets-row');
    if (!row) return;

    const oldElements = Array.from(row.querySelectorAll('.latest-bet-card'));
    const oldRects = captureRects(oldElements);
    const shouldAnimateNew = oldRects.size > 0 || oldElements.length === 0;

    if (!bets.length) {
        row.innerHTML = '<div class="latest-bets-empty">No bets yet.</div>';
        return;
    }

    row.innerHTML = bets.map(bet => `
        <article class="latest-bet-card" data-bet-id="${bet.bet_id}">
            <a class="latest-bet-game ${bet.image ? 'blackjack-game-img' : ''}" href="${pageUrl(bet.href)}">
                ${bet.image
                    ? `<img src="${assetUrl(bet.image)}" alt="${escapeHtml(bet.game_name)}"><span class="latest-bet-game-name">${escapeHtml(bet.game_name)}</span>`
                    : `<span class="latest-bet-game-code">${escapeHtml(String(bet.code || bet.game_name || '').slice(0, 2).toUpperCase())}</span><span class="latest-bet-game-name">${escapeHtml(bet.game_name)}</span>`
                }
            </a>
            <div class="latest-bet-player">
                <span>${escapeHtml(bet.username)}</span>
            </div>
            <div class="latest-bet-amount">${formatBetCurrency(bet.wager_amount)}</div>
        </article>
    `).join('');

    requestAnimationFrame(() => animateMovedElements(row, '.latest-bet-card', oldRects, 'x', shouldAnimateNew));
}

async function refreshBetFeeds() {
    if (refreshInFlight) {
        refreshQueued = true;
        return refreshInFlight;
    }

    refreshInFlight = (async () => {
        try {
            const response = await fetch(`${betsApiUrl.href}?t=${Date.now()}&r=${Math.random().toString(36).slice(2)}`, {
                cache: 'no-store',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });
            const data = await response.json();
            if (!data.success || !Array.isArray(data.bets)) return;

            const nextSignature = JSON.stringify(data.bets.map(bet => [
                bet.bet_id,
                bet.created_at,
                bet.user_id,
                bet.game_type,
                bet.wager_amount,
                bet.payout_amount,
                bet.net_result
            ]));

            if (nextSignature === latestFeedSignature) return;
            latestFeedSignature = nextSignature;
            renderLiveActivity(data.bets);
            renderLatestBets(data.bets);
        } catch (error) {
            // Keep the current server-rendered feed if the refresh fails.
        } finally {
            refreshInFlight = null;
            if (refreshQueued) {
                refreshQueued = false;
                window.setTimeout(refreshBetFeeds, 80);
            }
        }
    })();

    return refreshInFlight;
}

function notifyBetFeedsUpdated() {
    refreshBetFeeds();
    window.setTimeout(refreshBetFeeds, 350);
    window.setTimeout(refreshBetFeeds, 1000);
}

window.refreshBetFeeds = refreshBetFeeds;
window.notifyBetFeedsUpdated = notifyBetFeedsUpdated;

document.addEventListener('DOMContentLoaded', () => {
    setupLiveStatsFilters();
    window.setTimeout(refreshBetFeeds, 250);
    window.setInterval(refreshBetFeeds, 10000);
});

window.addEventListener('elite:bet-complete', notifyBetFeedsUpdated);
