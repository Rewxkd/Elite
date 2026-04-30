const headerScript = document.currentScript;
const headerConfig = {
    loginUrl: headerScript?.dataset.loginUrl || 'api/login.php'
};

const loginModal = document.getElementById('loginModal');
const loginBtn = document.getElementById('loginBtn');
const closeLogin = document.getElementById('closeLogin');
const logoutBtn = document.getElementById('logoutBtn');
const profileBtn = document.getElementById('prof');
const profileMenuWrap = document.getElementById('profileMenuWrap');
const profileMenu = document.getElementById('profileMenu');
const toggleBtn = document.getElementById('toggle');
const sidebar = document.getElementById('side');
const menuBtns = document.querySelectorAll('.dropdown-button');

if (loginBtn) {
    loginBtn.addEventListener('click', () => {
        const loginTab = document.querySelector('.login-tab[data-tab="login"]');
        if (loginTab) loginTab.click();
        if (loginModal) loginModal.style.display = 'flex';
    });
}

if (closeLogin) {
    closeLogin.addEventListener('click', () => {
        if (loginModal) loginModal.style.display = 'none';
    });
}

if (loginModal) {
    loginModal.addEventListener('click', (e) => {
        if (e.target === loginModal) {
            loginModal.style.display = 'none';
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

if (profileBtn && profileMenu) {
    profileBtn.addEventListener('click', (e) => {
        e.stopPropagation();
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

if (toggleBtn) {
    toggleBtn.addEventListener('click', function() {
        const isOpen = document.body.classList.toggle('open');
        if (sidebar) sidebar.setAttribute('aria-hidden', (!isOpen).toString());
        this.setAttribute('aria-expanded', isOpen.toString());
    });
}

menuBtns.forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (!document.body.classList.contains('open')) {
            document.body.classList.add('open');
            if (sidebar) sidebar.setAttribute('aria-hidden', 'false');
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

document.addEventListener('click', function() {
    if (profileMenu && profileBtn && profileMenu.getAttribute('aria-hidden') === 'false') {
        profileMenu.setAttribute('aria-hidden', 'true');
        profileBtn.setAttribute('aria-expanded', 'false');
    }

    menuBtns.forEach(btn => {
        const dropdown = btn.parentElement.querySelector('.dropdown-items');
        if (dropdown) dropdown.setAttribute('aria-hidden', 'true');
        btn.setAttribute('aria-expanded', 'false');
    });
});
