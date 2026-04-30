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

function setProgressBar(current, total) {
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');

    if (!progressBar || !progressText) return;

    const percent = Math.min(100, Math.round((Number(current) / Number(total)) * 100));
    progressBar.style.width = percent + '%';
    progressText.textContent = `$${Number(current).toFixed(2)} / $${Number(total).toFixed(2)} Wagered (${percent}%)`;
}

document.addEventListener('DOMContentLoaded', function () {
    setProgressBar(indexConfig.totalWagered, 1000);

    const shouldShowLogin = new URLSearchParams(window.location.search).get('login') === '1';
    if (shouldShowLogin) openAuthModal('login');

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
            setTimeout(() => location.reload(), 700);
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
            setTimeout(() => location.reload(), 700);
        }
    });
}
