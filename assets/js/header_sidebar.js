const headerScript = document.currentScript;
const headerConfig = {
    loginUrl: headerScript?.dataset.loginUrl || 'api/login.php',
    profileUrl: headerScript?.dataset.profileUrl || 'api/profile.php',
    notificationsUrl: headerScript?.dataset.notificationsUrl || 'api/notifications.php'
};

const loginModal = document.getElementById('loginModal');
const loginBtn = document.getElementById('loginBtn');
const registerBtn = document.getElementById('registerBtn');
const closeLogin = document.getElementById('closeLogin');
const loginTabs = loginModal ? loginModal.querySelectorAll('.login-tab[data-tab]') : [];
const loginForms = loginModal ? loginModal.querySelectorAll('.login-form') : [];
const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const logoutBtn = document.getElementById('logoutBtn');
const notifBtn = document.getElementById('notif');
const notificationBadge = document.getElementById('badge');
const notificationsWrap = document.getElementById('notificationsWrap');
const notificationsPanel = document.getElementById('notificationsPanel');
const notificationsList = document.getElementById('notificationsList');
const notificationsCountText = document.getElementById('notificationsCountText');
const markNotificationsReadBtn = document.getElementById('markNotificationsRead');
const profileBtn = document.getElementById('prof');
const profileMenuWrap = document.getElementById('profileMenuWrap');
const profileMenu = document.getElementById('profileMenu');
const accountModal = document.getElementById('accountModal');
const closeAccountModalBtn = document.getElementById('closeAccountModal');
const accountModalTitle = document.getElementById('accountModalTitle');
const accountModalSubtitle = document.getElementById('accountModalSubtitle');
const accountOpenBtns = document.querySelectorAll('[data-account-open]');
const accountTabs = document.querySelectorAll('[data-account-tab]');
const accountPanels = document.querySelectorAll('[data-account-panel]');
const accountMessage = document.querySelector('[data-account-message]');
const toggleBtn = document.getElementById('toggle');
const sidebar = document.getElementById('side');
const appContent = document.getElementById('appContent');
const menuBtns = document.querySelectorAll('.dropdown-button');
const mobileSidebarQuery = window.matchMedia('(max-width: 980px)');
const accountPanelCopy = {
    profile: ['Profile', 'Elite account overview'],
    bets: ['Latest Bets', 'Your recent game activity'],
    vip: ['VIP', 'Claim bonuses and rakeback'],
    settings: ['Settings', 'Account preferences']
};
let accountSummary = null;
let accountFetchPromise = null;
let notificationsFetchPromise = null;
let hourlyCountdownTimer = null;
const modalTransitionMs = 180;

function syncModalBodyState() {
    const hasOpenModal = [loginModal, accountModal].some(modal => modal?.classList.contains('is-open'));
    document.body.classList.toggle('modal-open', hasOpenModal);
}

function showModal(modal) {
    if (!modal) return;

    if (modal.eliteCloseTimer) {
        window.clearTimeout(modal.eliteCloseTimer);
        modal.eliteCloseTimer = null;
    }

    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');

    window.requestAnimationFrame(() => {
        modal.classList.add('is-open');
        syncModalBodyState();
    });
}

function hideModal(modal) {
    if (!modal) return;

    if (modal.eliteCloseTimer) {
        window.clearTimeout(modal.eliteCloseTimer);
    }

    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');

    const finishClose = () => {
        if (!modal.classList.contains('is-open')) {
            modal.style.display = 'none';
        }
        modal.eliteCloseTimer = null;
        syncModalBodyState();
    };

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        finishClose();
        return;
    }

    modal.eliteCloseTimer = window.setTimeout(finishClose, modalTransitionMs);
}

function setLoginModalTab(tabName = 'login') {
    const nextTab = ['login', 'register'].includes(tabName) ? tabName : 'login';
    loginTabs.forEach(tab => {
        const isActive = tab.dataset.tab === nextTab;
        tab.classList.toggle('active', isActive);
        tab.setAttribute('aria-selected', isActive.toString());
    });

    loginForms.forEach(form => {
        const isActive = form.id === `${nextTab}Form`;
        form.classList.toggle('active', isActive);
    });

    setStatusMessage(document.getElementById('loginMessage'));
    setStatusMessage(document.getElementById('registerMessage'));
}

function openLoginModal(tabName = 'login') {
    setLoginModalTab(tabName);
    showModal(loginModal);
}

function closeLoginModal() {
    hideModal(loginModal);
}

window.openEliteAuthModal = openLoginModal;
window.closeEliteAuthModal = closeLoginModal;

function syncSidebarState(isOpen) {
    if (sidebar) sidebar.setAttribute('aria-hidden', (!isOpen).toString());
    if (toggleBtn) toggleBtn.setAttribute('aria-expanded', isOpen.toString());
    if (appContent) {
        appContent.classList.toggle('sidebar-open', isOpen);
        appContent.classList.toggle('sidebar-closed', !isOpen);
    }
}

function toggleSidebar() {
    const isOpen = document.body.classList.toggle('open');
    syncSidebarState(isOpen);
}

function closeProfileMenu() {
    if (profileMenu && profileBtn) {
        profileMenu.setAttribute('aria-hidden', 'true');
        profileBtn.setAttribute('aria-expanded', 'false');
    }
}

function closeNotificationsPanel() {
    if (notificationsPanel && notifBtn) {
        notificationsPanel.setAttribute('aria-hidden', 'true');
        notifBtn.setAttribute('aria-expanded', 'false');
    }
}

function setNotificationBadge(count) {
    if (!notificationBadge) return;

    const nextCount = Math.max(0, Number(count) || 0);
    notificationBadge.textContent = nextCount > 99 ? '99+' : String(nextCount);
    notificationBadge.classList.toggle('is-empty', nextCount === 0);
}

function formatAccountCurrency(amount) {
    return `$${Number(amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function formatAccountDate(value) {
    if (!value) return 'Just now';

    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return String(value);

    return date.toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatDuration(totalSeconds) {
    const seconds = Math.max(0, Number(totalSeconds) || 0);
    const minutes = Math.floor(seconds / 60);
    const remainder = seconds % 60;

    return `${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`;
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function setStatusMessage(messageEl, message = '', state = 'neutral') {
    if (!messageEl) return;

    const nextMessage = String(message || '').trim();
    messageEl.textContent = nextMessage;
    messageEl.classList.remove('is-visible', 'is-success', 'is-error', 'is-neutral');

    if (!nextMessage) return;

    messageEl.classList.add('is-visible', `is-${state}`);
}

function getNotificationMark(type) {
    const normalized = String(type || '').toLowerCase();
    if (normalized.includes('rakeback')) return '%';
    if (normalized.includes('welcome')) return '+';
    if (normalized.includes('vip')) return '$';
    return '!';
}

function getNotificationActionLabel(actionKey) {
    if (actionKey === 'vip' || actionKey === 'rakeback') return 'Open VIP';
    if (actionKey === 'wallet') return 'Wallet';
    return '';
}

function renderNotifications(data) {
    if (!notificationsList) return;

    const notifications = Array.isArray(data?.notifications) ? data.notifications : [];
    const unreadCount = Number(data?.unread_count || 0);

    setNotificationBadge(unreadCount);

    if (notificationsCountText) {
        notificationsCountText.textContent = unreadCount > 0 ? `${unreadCount} unread` : 'All caught up';
    }

    if (markNotificationsReadBtn) {
        markNotificationsReadBtn.disabled = unreadCount === 0;
    }

    if (notifications.length === 0) {
        notificationsList.innerHTML = '<div class="notifications-empty">No notifications yet.</div>';
        return;
    }

    notificationsList.innerHTML = notifications.map(notification => {
        const id = Number(notification.notification_id || 0);
        const actionKey = String(notification.action_key || '');
        const actionLabel = getNotificationActionLabel(actionKey);
        const amount = notification.amount !== null && notification.amount !== undefined
            ? `<span class="notification-amount">${formatAccountCurrency(notification.amount)}</span>`
            : '';
        const actionButton = actionLabel
            ? `<button class="notification-action" type="button" data-notification-action="${escapeHtml(actionKey)}">${escapeHtml(actionLabel)}</button>`
            : '';

        return `
            <div class="notification-item${notification.is_read ? '' : ' is-unread'}" data-notification-id="${id}" role="button" tabindex="0">
                <span class="notification-mark" aria-hidden="true">${escapeHtml(getNotificationMark(notification.type))}</span>
                <div class="notification-body">
                    <div class="notification-title-row">
                        <strong>${escapeHtml(notification.title || 'Notification')}</strong>
                        ${amount}
                    </div>
                    <p>${escapeHtml(notification.message || '')}</p>
                    <div class="notification-meta">
                        <span>${escapeHtml(formatAccountDate(notification.created_at))}</span>
                        ${actionButton}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

async function fetchNotifications(force = false) {
    if (!notificationsList) return null;
    if (notificationsFetchPromise && !force) return notificationsFetchPromise;

    notificationsFetchPromise = fetch(`${headerConfig.notificationsUrl}?t=${Date.now()}`, {
        credentials: 'same-origin'
    })
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Could not load notifications');
            renderNotifications(data);
            return data;
        })
        .catch(error => {
            notificationsList.innerHTML = `<div class="notifications-empty">${escapeHtml(error.message || 'Could not load notifications.')}</div>`;
            return null;
        })
        .finally(() => {
            notificationsFetchPromise = null;
        });

    return notificationsFetchPromise;
}

async function postNotificationAction(action, values = {}) {
    if (!notificationsList) return null;

    const formData = new FormData();
    formData.append('action', action);

    Object.entries(values).forEach(([key, value]) => {
        formData.append(key, value);
    });

    try {
        const response = await fetch(headerConfig.notificationsUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        const data = await response.json();
        if (data.success) renderNotifications(data);
        return data;
    } catch (error) {
        return null;
    }
}

function openNotificationsPanel() {
    if (!notificationsPanel || !notifBtn) return;

    closeProfileMenu();
    notificationsPanel.setAttribute('aria-hidden', 'false');
    notifBtn.setAttribute('aria-expanded', 'true');
    fetchNotifications(true);
}

function toggleNotificationsPanel() {
    if (!notificationsPanel) return;

    const isOpen = notificationsPanel.getAttribute('aria-hidden') === 'false';

    if (isOpen) {
        closeNotificationsPanel();
    } else {
        openNotificationsPanel();
    }
}

function openNotificationAction(actionKey) {
    if (actionKey === 'vip' || actionKey === 'rakeback') {
        openAccountModal('vip');
        return;
    }

    if (actionKey === 'wallet') {
        openAccountModal('profile');
    }
}

function getAccountField(name) {
    return accountModal?.querySelector(`[data-account-field="${name}"]`) || null;
}

function getAccountRankTier(name) {
    const rankName = String(name || '').toLowerCase();
    if (rankName.includes('bronze')) return 'bronze';
    if (rankName.includes('silver')) return 'silver';
    if (rankName.includes('gold')) return 'gold';
    if (rankName.includes('platinum')) return 'platinum';
    if (rankName.includes('diamond')) return 'diamond';
    if (rankName.includes('obsidian')) return 'obsidian';
    return 'unranked';
}

function setAccountRankClass(element, rankName) {
    if (!element) return;
    element.classList.remove('rank-unranked', 'rank-bronze', 'rank-silver', 'rank-gold', 'rank-platinum', 'rank-diamond', 'rank-obsidian');
    element.classList.add(`rank-${getAccountRankTier(rankName)}`);
}

function setAccountPanel(panelName) {
    const nextPanel = accountPanelCopy[panelName] ? panelName : 'profile';
    const [title, subtitle] = accountPanelCopy[nextPanel];

    if (accountModalTitle) accountModalTitle.textContent = title;
    if (accountModalSubtitle) accountModalSubtitle.textContent = subtitle;

    accountTabs.forEach(tab => {
        const isActive = tab.dataset.accountTab === nextPanel;
        tab.classList.toggle('active', isActive);
        tab.setAttribute('aria-selected', isActive.toString());
    });

    accountPanels.forEach(panel => {
        panel.classList.toggle('active', panel.dataset.accountPanel === nextPanel);
    });
}

function openAccountModal(panelName = 'profile') {
    if (!accountModal) return;

    closeProfileMenu();
    closeNotificationsPanel();
    setAccountPanel(panelName);
    showModal(accountModal);
    fetchAccountSummary();
}

function closeAccountModal() {
    hideModal(accountModal);
}

async function fetchAccountSummary(force = false) {
    if (!accountModal) return null;
    if (accountSummary && !force) {
        renderAccountSummary(accountSummary);
        return accountSummary;
    }
    if (accountFetchPromise) return accountFetchPromise;

    accountFetchPromise = fetch(`${headerConfig.profileUrl}?t=${Date.now()}`, {
        credentials: 'same-origin'
    })
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Could not load profile');
            accountSummary = data.summary;
            renderAccountSummary(accountSummary);
            return accountSummary;
        })
        .catch(error => {
            if (accountMessage) {
                setStatusMessage(accountMessage, error.message || 'Could not load profile.', 'error');
            }
            return null;
        })
        .finally(() => {
            accountFetchPromise = null;
        });

    return accountFetchPromise;
}

function renderAccountBets(bets) {
    const list = document.querySelector('[data-account-bets-list]');
    if (!list) return;

    if (!Array.isArray(bets) || bets.length === 0) {
        list.innerHTML = '<div class="account-empty-state">No bets yet.</div>';
        return;
    }

    list.innerHTML = bets.map(bet => {
        const net = Number(bet.net_result || 0);
        const netClass = net >= 0 ? 'is-positive' : 'is-negative';
        const code = String(bet.game_name || bet.game_type || 'EL').slice(0, 2).toUpperCase();

        return `
            <div class="account-bet-row">
                <span class="account-bet-mark">${escapeHtml(code)}</span>
                <div class="account-bet-meta">
                    <strong>${escapeHtml(bet.game_name || 'Game')}</strong>
                    <small>${escapeHtml(formatAccountDate(bet.created_at))}</small>
                </div>
                <div class="account-bet-values">
                    <strong>${formatAccountCurrency(bet.wager_amount)}</strong>
                    <small class="${netClass}">${net >= 0 ? '+' : ''}${formatAccountCurrency(net)}</small>
                </div>
            </div>
        `;
    }).join('');
}

function updateHourlyCountdown(seconds) {
    const status = getAccountField('hourly_status');
    const button = document.querySelector('[data-vip-claim="hourly"]');
    let remaining = Math.max(0, Number(seconds) || 0);

    if (hourlyCountdownTimer) {
        window.clearInterval(hourlyCountdownTimer);
        hourlyCountdownTimer = null;
    }

    const render = () => {
        if (remaining <= 0) {
            if (status) status.textContent = 'Ready to claim';
            if (button) button.disabled = false;
            if (hourlyCountdownTimer) {
                window.clearInterval(hourlyCountdownTimer);
                hourlyCountdownTimer = null;
            }
            return;
        }

        if (status) status.textContent = `Available in ${formatDuration(remaining)}`;
        if (button) button.disabled = true;
        remaining -= 1;
    };

    render();

    if (remaining > 0) {
        hourlyCountdownTimer = window.setInterval(render, 1000);
    }
}

function renderAccountSummary(summary) {
    if (!summary) return;

    const user = summary.user || {};
    const wallet = summary.wallet || {};
    const stats = summary.stats || {};
    const ranks = summary.ranks || {};
    const vip = summary.vip || {};

    const fieldValues = {
        username: user.username || 'Player',
        member_since: `Member since ${user.member_since || 'today'}`,
        avatar: String(user.username || 'E').slice(0, 1).toUpperCase(),
        rank_remaining: Number(ranks.remaining || 0) > 0 ? formatAccountCurrency(ranks.remaining) : 'Max rank',
        current_rank: ranks.current || 'Unranked',
        next_rank: ranks.next || 'Bronze I',
        total_bets: Number(stats.total_bets || 0).toLocaleString(),
        total_wagered: formatAccountCurrency(wallet.total_wagered),
        balance: formatAccountCurrency(wallet.balance),
        net_result: formatAccountCurrency(stats.net_result),
        bets_count: `${Number(stats.total_bets || 0).toLocaleString()} ${Number(stats.total_bets || 0) === 1 ? 'bet' : 'bets'}`,
        hourly_amount: formatAccountCurrency(vip.hourly_amount || 1000),
        rakeback_available: formatAccountCurrency(vip.rakeback_available),
        rakeback_rate: `${Math.round(Number(vip.rakeback_rate || 0.08) * 100)}%`
    };

    Object.entries(fieldValues).forEach(([name, value]) => {
        const el = getAccountField(name);
        if (el) el.textContent = value;
    });

    const progress = getAccountField('rank_progress');
    if (progress) progress.style.width = `${Math.min(100, Math.max(0, Number(ranks.percent || 0)))}%`;

    const rankEls = accountModal?.querySelectorAll('.account-rank') || [];
    setAccountRankClass(rankEls[0], ranks.current);
    setAccountRankClass(rankEls[1], ranks.next);

    const netEl = getAccountField('net_result');
    if (netEl) {
        netEl.classList.toggle('is-positive', Number(stats.net_result || 0) >= 0);
        netEl.classList.toggle('is-negative', Number(stats.net_result || 0) < 0);
    }

    const balanceEl = document.getElementById('balanceAmount');
    if (balanceEl) balanceEl.textContent = formatAccountCurrency(wallet.balance);

    const usernameInput = document.querySelector('[data-settings-field="username"]');
    const emailInput = document.querySelector('[data-settings-field="email"]');
    if (usernameInput) usernameInput.value = user.username || '';
    if (emailInput) emailInput.value = user.email || '';

    const rakebackButton = document.querySelector('[data-vip-claim="rakeback"]');
    if (rakebackButton) rakebackButton.disabled = Number(vip.rakeback_available || 0) < 0.01;

    updateHourlyCountdown(vip.hourly_remaining_seconds || 0);
    renderAccountBets(summary.bets || []);
}

async function claimVipReward(type) {
    const button = document.querySelector(`[data-vip-claim="${type}"]`);
    const action = type === 'hourly' ? 'claim_hourly' : 'claim_rakeback';
    const formData = new FormData();
    formData.append('action', action);

    if (button) button.disabled = true;
    setStatusMessage(accountMessage);

    try {
        const response = await fetch(headerConfig.profileUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        const data = await response.json();

        if (data.summary) {
            accountSummary = data.summary;
            renderAccountSummary(accountSummary);
        } else {
            await fetchAccountSummary(true);
        }

        setStatusMessage(accountMessage, data.message || (data.success ? 'Claimed.' : 'Could not claim.'), data.success ? 'success' : 'error');

        if (data.success) {
            fetchNotifications(true);
        }
    } catch (error) {
        setStatusMessage(accountMessage, 'Could not claim right now.', 'error');
        await fetchAccountSummary(true);
    }
}

function reloadAfterAuth() {
    location.reload();
}

function setAuthMessage(messageEl, message, isSuccess) {
    setStatusMessage(messageEl, message, isSuccess ? 'success' : 'error');
}

async function submitAuthForm(form, action, messageId) {
    if (!form) return;

    const messageEl = document.getElementById(messageId);
    const submitBtn = form.querySelector('button[type="submit"]');
    const formData = new FormData(form);
    formData.append('action', action);

    if (submitBtn) submitBtn.disabled = true;
    setAuthMessage(messageEl, '', false);

    try {
        const response = await fetch(headerConfig.loginUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        const data = await response.json();
        const successMessage = action === 'register' ? 'Registration successful!' : 'Login successful!';
        const failureMessage = action === 'register' ? 'Registration failed' : 'Login failed';

        setAuthMessage(messageEl, data.message || (data.success ? successMessage : failureMessage), Boolean(data.success));

        if (data.success) {
            window.setTimeout(reloadAfterAuth, 700);
        }
    } catch (error) {
        setAuthMessage(messageEl, 'Could not connect right now.', false);
    } finally {
        if (submitBtn) submitBtn.disabled = false;
    }
}

loginTabs.forEach(tab => {
    tab.addEventListener('click', () => {
        setLoginModalTab(tab.dataset.tab || 'login');
    });
});

if (loginForm) {
    loginForm.addEventListener('submit', (e) => {
        e.preventDefault();
        submitAuthForm(loginForm, 'login', 'loginMessage');
    });
}

if (registerForm) {
    registerForm.addEventListener('submit', (e) => {
        e.preventDefault();
        submitAuthForm(registerForm, 'register', 'registerMessage');
    });
}

document.addEventListener('click', (e) => {
    const authTrigger = e.target.closest('[data-auth-tab]');
    if (!authTrigger) return;

    e.preventDefault();
    openLoginModal(authTrigger.dataset.authTab || 'login');
});

if (loginBtn) {
    loginBtn.addEventListener('click', () => {
        openLoginModal('login');
    });
}

if (registerBtn) {
    registerBtn.addEventListener('click', () => {
        openLoginModal('register');
    });
}

if (closeLogin) {
    closeLogin.addEventListener('click', () => {
        closeLoginModal();
    });
}

if (loginModal) {
    loginModal.addEventListener('click', (e) => {
        if (e.target === loginModal) {
            closeLoginModal();
        }
    });
}

if (logoutBtn) {
    logoutBtn.addEventListener('click', async () => {
        const formData = new FormData();
        formData.append('action', 'logout');

        await fetch(headerConfig.loginUrl, {
            method: 'POST',
            body: formData
        });

        location.reload();
    });
}

if (notifBtn) {
    notifBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleNotificationsPanel();
    });
}

if (notificationsWrap) {
    notificationsWrap.addEventListener('click', (e) => {
        e.stopPropagation();
    });
}

if (notificationsList) {
    notificationsList.addEventListener('click', async (e) => {
        const item = e.target.closest('[data-notification-id]');
        if (!item) return;

        const actionButton = e.target.closest('[data-notification-action]');
        const notificationId = item.dataset.notificationId;

        await postNotificationAction('mark_read', { notification_id: notificationId });

        if (actionButton) {
            closeNotificationsPanel();
            openNotificationAction(actionButton.dataset.notificationAction || '');
        }
    });

    notificationsList.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter' && e.key !== ' ') return;

        const item = e.target.closest('[data-notification-id]');
        if (!item) return;

        e.preventDefault();
        postNotificationAction('mark_read', { notification_id: item.dataset.notificationId });
    });
}

if (markNotificationsReadBtn) {
    markNotificationsReadBtn.addEventListener('click', () => {
        postNotificationAction('mark_all_read');
    });
}

if (profileBtn && profileMenu) {
    profileBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        closeNotificationsPanel();
        const isOpen = profileMenu.getAttribute('aria-hidden') === 'false';
        profileMenu.setAttribute('aria-hidden', isOpen.toString());
        profileBtn.setAttribute('aria-expanded', (!isOpen).toString());
    });
}

if (profileMenuWrap) {
    profileMenuWrap.addEventListener('click', (e) => {
        e.stopPropagation();
    });
}

accountOpenBtns.forEach(button => {
    button.addEventListener('click', () => {
        openAccountModal(button.dataset.accountOpen || 'profile');
    });
});

accountTabs.forEach(tab => {
    tab.addEventListener('click', () => {
        setAccountPanel(tab.dataset.accountTab || 'profile');
    });
});

if (closeAccountModalBtn) {
    closeAccountModalBtn.addEventListener('click', closeAccountModal);
}

if (accountModal) {
    accountModal.addEventListener('click', (e) => {
        if (e.target === accountModal) {
            closeAccountModal();
        }
    });
}

document.querySelectorAll('[data-vip-claim]').forEach(button => {
    button.addEventListener('click', () => {
        claimVipReward(button.dataset.vipClaim);
    });
});

if (notifBtn) {
    fetchNotifications(true);
    window.setInterval(() => fetchNotifications(true), 60000);
    window.addEventListener('elite:bet-complete', () => fetchNotifications(true));
}

if (toggleBtn) {
    toggleBtn.addEventListener('click', function() {
        toggleSidebar();
    });
}

menuBtns.forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (!document.body.classList.contains('open')) {
            document.body.classList.add('open');
            syncSidebarState(true);
            this.setAttribute('aria-expanded', 'true');
            setTimeout(() => {
                const dropdown = this.parentElement.querySelector('.dropdown-items');
                if (dropdown) dropdown.setAttribute('aria-hidden', 'false');
            }, 0);
            return;
        }

        e.stopPropagation();
        const dropdown = this.parentElement.querySelector('.dropdown-items');
        const isOpen = dropdown && dropdown.getAttribute('aria-hidden') === 'true';

        menuBtns.forEach(otherBtn => {
            if (otherBtn !== this) {
                const otherDropdown = otherBtn.parentElement.querySelector('.dropdown-items');
                if (otherDropdown) otherDropdown.setAttribute('aria-hidden', 'true');
                otherBtn.setAttribute('aria-expanded', 'false');
            }
        });

        if (dropdown) dropdown.setAttribute('aria-hidden', (!isOpen).toString());
        this.setAttribute('aria-expanded', isOpen.toString());
    });
});

document.addEventListener('click', function(event) {
    if (mobileSidebarQuery.matches && document.body.classList.contains('open') && sidebar && !sidebar.contains(event.target)) {
        document.body.classList.remove('open');
        syncSidebarState(false);
    }

    if (profileMenu && profileBtn && profileMenu.getAttribute('aria-hidden') === 'false') closeProfileMenu();
    if (notificationsPanel && notifBtn && notificationsPanel.getAttribute('aria-hidden') === 'false') closeNotificationsPanel();

    menuBtns.forEach(btn => {
        const dropdown = btn.parentElement.querySelector('.dropdown-items');
        if (dropdown) dropdown.setAttribute('aria-hidden', 'true');
        btn.setAttribute('aria-expanded', 'false');
    });
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && accountModal && accountModal.getAttribute('aria-hidden') === 'false') {
        closeAccountModal();
    }

    if (event.key === 'Escape') {
        closeProfileMenu();
        closeNotificationsPanel();
    }
});
