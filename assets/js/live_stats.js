function setupLiveStatsFilters() {
    const tabs = document.querySelectorAll('[data-bets-filter]');
    const rows = document.querySelectorAll('[data-bet-row]');
    const emptyMineRow = document.querySelector('[data-bets-empty="mine"]');

    if (!tabs.length || !rows.length) return;

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const filter = tab.dataset.betsFilter;
            let visibleRows = 0;

            tabs.forEach(otherTab => {
                const isActive = otherTab === tab;
                otherTab.classList.toggle('active', isActive);
                otherTab.setAttribute('aria-selected', isActive.toString());
            });

            rows.forEach(row => {
                const shouldShow = filter === 'live' || row.dataset.isMine === 'true';
                row.hidden = !shouldShow;
                if (shouldShow) visibleRows += 1;
            });

            if (emptyMineRow) {
                emptyMineRow.hidden = filter !== 'mine' || visibleRows > 0;
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', setupLiveStatsFilters);
