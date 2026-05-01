const indexScript = document.currentScript;
const indexConfig = {
    totalWagered: Number(indexScript?.dataset.totalWagered || 0),
    loginUrl: indexScript?.dataset.loginUrl || 'api/login.php'
};

const loginTabs = document.querySelectorAll('.login-tab');
const loginForms = document.querySelectorAll('.login-form');
const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const gamesRow = document.getElementById('gamesRow');
const wagerRanks = [
    { name: 'Unranked', threshold: 0 },
    { name: 'Bronze', threshold: 10000 },
    { name: 'Silver', threshold: 50000 },
    { name: 'Gold', threshold: 100000 },
    { name: 'Platinum I', threshold: 250000 },
    { name: 'Platinum II', threshold: 500000 },
    { name: 'Platinum III', threshold: 1000000 },
    { name: 'Platinum IV', threshold: 2500000 },
    { name: 'Platinum V', threshold: 5000000 },
    { name: 'Platinum VI', threshold: 10000000 },
    { name: 'Diamond I', threshold: 25000000 },
    { name: 'Diamond II', threshold: 50000000 },
    { name: 'Diamond III', threshold: 100000000 },
    { name: 'Diamond IV', threshold: 250000000 },
    { name: 'Diamond V', threshold: 500000000 },
    { name: 'Obsidian', threshold: 1000000000 }
];

loginTabs.forEach(tab => {
    tab.addEventListener('click', () => {
        const tabName = tab.getAttribute('data-tab');
        loginTabs.forEach(t => t.classList.remove('active'));
        loginForms.forEach(f => f.classList.remove('active'));
        tab.classList.add('active');

        const form = document.getElementById(tabName + 'Form');
        if (form) form.classList.add('active');
    });
});

function openAuthModal(tabName = 'login') {
    const tab = document.querySelector(`.login-tab[data-tab="${tabName}"]`);
    const modal = document.getElementById('loginModal');
    if (tab) tab.click();
    if (modal) modal.style.display = 'flex';
}

function reloadWithoutLoginPrompt() {
    const url = new URL(window.location.href);
    url.searchParams.delete('login');
    window.location.replace(url.toString());
}

function clearLegacyLoginPromptParam() {
    const url = new URL(window.location.href);
    if (!url.searchParams.has('login')) return;

    url.searchParams.delete('login');
    window.history.replaceState({}, '', url.toString());
}

function formatProgressCurrency(amount) {
    return `$${Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function getWagerRank(current) {
    const wagered = Number(current) || 0;
    let rankIndex = 0;

    for (let i = 0; i < wagerRanks.length; i++) {
        if (wagered >= wagerRanks[i].threshold) {
            rankIndex = i;
        }
    }

    const currentRank = wagerRanks[rankIndex];
    const nextRank = wagerRanks[Math.min(rankIndex + 1, wagerRanks.length - 1)];
    const rankStart = currentRank.threshold;
    const rankTarget = nextRank.threshold;
    const span = Math.max(1, rankTarget - rankStart);
    const percent = currentRank === nextRank ? 100 : Math.min(100, Math.max(0, Math.round(((wagered - rankStart) / span) * 100)));

    return { currentRank, nextRank, rankTarget, percent };
}

function getRankTier(name) {
    const rankName = String(name || '').toLowerCase();
    if (rankName.includes('bronze')) return 'bronze';
    if (rankName.includes('silver')) return 'silver';
    if (rankName.includes('gold')) return 'gold';
    if (rankName.includes('platinum')) return 'platinum';
    if (rankName.includes('diamond')) return 'diamond';
    if (rankName.includes('obsidian')) return 'obsidian';
    return 'unranked';
}

function setRankTierClass(element, rankName) {
    if (!element) return;
    element.classList.remove('rank-unranked', 'rank-bronze', 'rank-silver', 'rank-gold', 'rank-platinum', 'rank-diamond', 'rank-obsidian');
    element.classList.add(`rank-${getRankTier(rankName)}`);
}

function setProgressBar(current) {
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const progressCurrentAmount = document.getElementById('progressCurrentAmount');
    const progressTargetText = document.getElementById('progressTargetText');
    const progressPercent = document.getElementById('progressPercent');
    const currentRankWrap = document.getElementById('currentRankWrap');
    const nextRankWrap = document.getElementById('nextRankWrap');
    const currentRankEl = document.getElementById('currentRank');
    const nextRankEl = document.getElementById('nextRank');

    if (!progressBar || !progressText) return;

    const wagered = Number(current) || 0;
    const progress = getWagerRank(wagered);
    const isMaxRank = progress.currentRank === progress.nextRank;
    progressBar.style.width = progress.percent + '%';

    if (progressCurrentAmount && progressTargetText) {
        progressCurrentAmount.textContent = formatProgressCurrency(wagered);
        progressTargetText.textContent = isMaxRank ? ' Max level reached' : ` / ${formatProgressCurrency(progress.rankTarget)} Wagered`;
    } else {
        progressText.textContent = isMaxRank
            ? `${formatProgressCurrency(wagered)} Max level reached`
            : `${formatProgressCurrency(wagered)} / ${formatProgressCurrency(progress.rankTarget)} Wagered`;
    }

    if (progressPercent) progressPercent.textContent = `${progress.percent}%`;
    if (currentRankEl) currentRankEl.textContent = progress.currentRank.name;
    if (nextRankEl) nextRankEl.textContent = isMaxRank ? 'Max level reached' : progress.nextRank.name;
    setRankTierClass(currentRankWrap, progress.currentRank.name);
    setRankTierClass(nextRankWrap, isMaxRank ? progress.currentRank.name : progress.nextRank.name);
}

document.addEventListener('DOMContentLoaded', function () {
    setProgressBar(indexConfig.totalWagered);
    clearLegacyLoginPromptParam();

    const shouldShowLogin = window.sessionStorage.getItem('eliteOpenLoginModal') === '1';
    if (shouldShowLogin) {
        window.sessionStorage.removeItem('eliteOpenLoginModal');
        openAuthModal('login');
    }

    const promptRegisterButton = document.getElementById('loginPromptBtn');
    const promptLoginButton = document.getElementById('loginPromptBtnLogin');

    if (promptRegisterButton) {
        promptRegisterButton.addEventListener('click', () => openAuthModal('register'));
    }

    if (promptLoginButton) {
        promptLoginButton.addEventListener('click', () => openAuthModal('login'));
    }
});

document.querySelectorAll('[data-requires-login="true"]').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        openAuthModal('login');
    });
});

if (prevBtn && nextBtn && gamesRow) {
    const scrollAmount = () => {
        const card = gamesRow.querySelector('.game-card');
        return card ? (card.offsetWidth + 16) * 5 : 400;
    };

    const updateCarouselButtons = () => {
        const maxScroll = gamesRow.scrollWidth - gamesRow.clientWidth;
        const atStart = gamesRow.scrollLeft <= 1;
        const atEnd = gamesRow.scrollLeft >= maxScroll - 1;

        prevBtn.disabled = atStart;
        nextBtn.disabled = maxScroll <= 1 || atEnd;
    };

    prevBtn.addEventListener('click', () => {
        gamesRow.scrollBy({ left: -scrollAmount(), behavior: 'smooth' });
    });

    nextBtn.addEventListener('click', () => {
        gamesRow.scrollBy({ left: scrollAmount(), behavior: 'smooth' });
    });

    gamesRow.addEventListener('scroll', updateCarouselButtons);
    window.addEventListener('resize', updateCarouselButtons);
    updateCarouselButtons();
}

if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(loginForm);
        formData.append('action', 'login');

        const response = await fetch(indexConfig.loginUrl, { method: 'POST', body: formData });
        const data = await response.json();
        const messageEl = document.getElementById('loginMessage');

        messageEl.textContent = data.message || (data.success ? 'Login successful!' : 'Login failed');
        messageEl.style.color = data.success ? '#00ff00' : '#ff6666';

        if (data.success) {
            setTimeout(reloadWithoutLoginPrompt, 700);
        }
    });
}

if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(registerForm);
        formData.append('action', 'register');

        const response = await fetch(indexConfig.loginUrl, { method: 'POST', body: formData });
        const data = await response.json();
        const messageEl = document.getElementById('registerMessage');

        messageEl.textContent = data.message || (data.success ? 'Registration successful!' : 'Registration failed');
        messageEl.style.color = data.success ? '#00ff00' : '#ff6666';

        if (data.success) {
            setTimeout(reloadWithoutLoginPrompt, 700);
        }
    });
}
