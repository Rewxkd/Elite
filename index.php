<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elite</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <aside class="side" id="side" aria-hidden="true">
        <button class="toggle" id="toggle" aria-label="Toggle navigation" aria-expanded="false">☰</button>
        <nav class="nav">
            <a href="#" class="item" id="active"><span class="icon">🏠</span><span class="text">Home</span></a>
            <a href="#" class="item"><span class="icon">❤️</span><span class="text">Favourites</span></a>
            <a href="#" class="item"><span class="icon">🕒</span><span class="text">Recent</span></a>
            <br>
            <div class="menu">
                <div class="menu-item">
                    <button class="menu-btn" aria-haspopup="true" aria-expanded="false">
                        <span class="menu-icon">⁉️</span>
                        <span class="menu-text">placeholder</span>
                        <span class="menu-arrow">▼</span>
                    </button>
                    <div class="menu-dropdown" aria-hidden="true">
                        <a href="#">text</a>
                        <a href="#">text</a>
                        <a href="#">text</a>
                    </div>
                </div>
                <div class="menu-item">
                    <button class="menu-btn" aria-haspopup="true" aria-expanded="false">
                        <span class="menu-icon">⁉️</span>
                        <span class="menu-text">placeholder</span>
                        <span class="menu-arrow">▼</span>
                    </button>
                    <div class="menu-dropdown" aria-hidden="true">
                        <a href="#">text</a>
                        <a href="#">text</a>
                        <a href="#">text</a>
                    </div>
                </div>
                <div class="menu-item">
                    <button class="menu-btn" aria-haspopup="true" aria-expanded="false">
                        <span class="menu-icon">⁉️</span>
                        <span class="menu-text">placeholder</span>
                        <span class="menu-arrow">▼</span>
                    </button>
                    <div class="menu-dropdown" aria-hidden="true">
                        <a href="#">text</a>
                        <a href="#">text</a>
                        <a href="#">text</a>
                    </div>
                </div>
            </div>
        </nav>
    </aside>

    <header class="hdr">
        <div class="hdr-box">
            <div class="logo">
                <span class="logo-txt"><img src="Elite-logo.png" alt=""></span>
            </div>
            <div class="bal">
                <span class="bal-amt">$0.00</span>
            </div>
            <div class="acts">
                <button class="btn icon" id="search" aria-label="Search">
                    <span>🔍</span>
                </button>
                <div class="group" role="group" aria-label="Profile and notifications">
                    <button class="btn icon notif" id="notif" aria-label="Notifications" aria-expanded="false">
                        <span>🔔</span>
                        <span class="badge" id="badge">3</span>
                    </button>
                    <button class="btn icon" id="prof" aria-label="Profile" aria-expanded="false">
                        <span>👤</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="cnt">
        <div class="cnt-box">
        </div>
    </main>

    <script>
        (function(){
            const btn = document.getElementById('toggle');
            const sidebar = document.getElementById('side');
            if (btn) btn.addEventListener('click', function(){
                const isOpen = document.body.classList.toggle('open');
                if (sidebar) sidebar.setAttribute('aria-hidden', (!isOpen).toString());
                btn.setAttribute('aria-expanded', isOpen.toString());
            });

            const profileBtn = document.getElementById('prof');
            const notifBtn = document.getElementById('notif');
            const notificationsPanel = document.getElementById('notifPnl');
            const notifBadge = document.getElementById('badge');

            function closeAllPanels() {
                if (notificationsPanel) notificationsPanel.setAttribute('aria-hidden', 'true');
                if (notifBtn) notifBtn.setAttribute('aria-expanded', 'false');
            }

            // Menu dropdown functionality
            const menuBtns = document.querySelectorAll('.menu-btn');
            menuBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    // If sidebar is closed, open it and show dropdown
                    if (!document.body.classList.contains('open')) {
                        document.body.classList.add('open');
                        sidebar.setAttribute('aria-hidden', 'false');
                        btn.setAttribute('aria-expanded', 'true');
                        // Show dropdown only after sidebar is open
                        setTimeout(() => {
                            const dropdown = this.parentElement.querySelector('.menu-dropdown');
                            dropdown.setAttribute('aria-hidden', 'false');
                        }, 0);
                        return;
                    }
                    e.stopPropagation();
                    const dropdown = this.parentElement.querySelector('.menu-dropdown');
                    const isOpen = dropdown.getAttribute('aria-hidden') === 'true';
                    
                    // Close all other dropdowns
                    menuBtns.forEach(otherBtn => {
                        if (otherBtn !== this) {
                            const otherDropdown = otherBtn.parentElement.querySelector('.menu-dropdown');
                            otherDropdown.setAttribute('aria-hidden', 'true');
                            otherBtn.setAttribute('aria-expanded', 'false');
                        }
                    });
                    
                    // Toggle current dropdown
                    dropdown.setAttribute('aria-hidden', (!isOpen).toString());
                    this.setAttribute('aria-expanded', isOpen.toString());
                });
            });
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                menuBtns.forEach(btn => {
                    const dropdown = btn.parentElement.querySelector('.menu-dropdown');
                    if (!btn.contains(e.target)) {
                        dropdown.setAttribute('aria-hidden', 'true');
                        btn.setAttribute('aria-expanded', 'false');
                    }
                });
                closeAllPanels();
            });
        })();
            // Hide all dropdowns if sidebar is closed
            function updateDropdownVisibility() {
                const isSidebarOpen = document.body.classList.contains('open');
                document.querySelectorAll('.menu-dropdown').forEach(dropdown => {
                    dropdown.setAttribute('aria-hidden', (!isSidebarOpen).toString());
                });
            }
            // Listen for sidebar toggle
            btn.addEventListener('click', updateDropdownVisibility);
            // Initial state
            updateDropdownVisibility();
    </script>
</body>
</html>