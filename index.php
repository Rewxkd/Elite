<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elite</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Sidebar navigation -->
    <aside class="sidebar" id="side" aria-hidden="true">
        <button class="toggle" id="toggle" aria-label="Toggle navigation" aria-expanded="false">☰</button>
        <nav class="navigation">
            <a href="#" class="item" id="active"><span class="icon">🏠</span><span class="text">Home</span></a>
            <a href="#" class="item"><span class="icon">❤️</span><span class="text">Favourites</span></a>
            <a href="#" class="item"><span class="icon">🕒</span><span class="text">Recent</span></a>
            <div class="dropdown">
                <div class="dropdown-item">
                    <button class="dropdown-button" aria-haspopup="true" aria-expanded="false">
                        <span class="menu-icon">⁉️</span><span class="menu-text">placeholder</span><span class="menu-arrow">▼</span>
                    </button>
                    <div class="dropdown-items" aria-hidden="true">
                        <a href="#">text</a><a href="#">text</a><a href="#">text</a>
                    </div>
                </div>
                <div class="dropdown-item">
                    <button class="dropdown-button" aria-haspopup="true" aria-expanded="false">
                        <span class="menu-icon">⁉️</span><span class="menu-text">placeholder</span><span class="menu-arrow">▼</span>
                    </button>
                    <div class="dropdown-items" aria-hidden="true">
                        <a href="#">text</a><a href="#">text</a><a href="#">text</a>
                    </div>
                </div>
                <div class="dropdown-item">
                    <button class="dropdown-button" aria-haspopup="true" aria-expanded="false">
                        <span class="menu-icon">⁉️</span><span class="menu-text">placeholder</span><span class="menu-arrow">▼</span>
                    </button>
                    <div class="dropdown-items" aria-hidden="true">
                        <a href="#">text</a><a href="#">text</a><a href="#">text</a>
                    </div>
                </div>
            </div>
        </nav>
    </aside>

    <!-- Header with logo, balance, and controls -->
    <header class="header">
        <div class="header-box">
            <div class="logo">
                <img src="Elite-logo.png" alt="">
            </div>
            <div class="balance">
                <span class="balance-amount">$100,000,000,000.00</span>
            </div>
            <div class="header-buttons">
                <button class="button icon" id="search" aria-label="Search"><span>🔍</span></button>
                <div class="group" role="group" aria-label="Profile and notifications">
                    <button class="button icon notif" id="notif" aria-label="Notifications"><span>🔔</span><span class="badge" id="badge">3</span></button>
                    <button class="button icon" id="prof" aria-label="Profile"><span>👤</span></button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main content -->
    <main class="container">
        <div class="container-box">
            <h1>Welcome <span style="color: #90beff;">(User)</span>!</h1>
        </div>
        <div class="container-progress">
            <div class="progress-top"><p>Your Progress</p></div>
            <div class="progress-bottom">
                <p id="progressText">$0.00 / $1000.00 Wagered</p>
                <div class="progress-bar-container">
                    <div class="progress-bar" id="progressBar" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Update progress bar display
        function setProgressBar(current, total) {
            const percent = Math.min(100, Math.round((current / total) * 100));
            document.getElementById('progressBar').style.width = percent + '%';
            document.getElementById('progressText').textContent = `$${current.toFixed(2)} / $${total.toFixed(2)} Wagered (${percent}%)`;
        }
        setProgressBar(350, 1000);

        // Initialize sidebar and dropdown functionality
        (function(){
            const btn = document.getElementById('toggle');
            const sidebar = document.getElementById('side');
            const menuBtns = document.querySelectorAll('.dropdown-button');

            // Toggle sidebar open/closed
            if (btn) btn.addEventListener('click', function(){
                const isOpen = document.body.classList.toggle('open');
                sidebar.setAttribute('aria-hidden', (!isOpen).toString());
                btn.setAttribute('aria-expanded', isOpen.toString());
            });

            // Handle dropdown menu clicks
            menuBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    if (!document.body.classList.contains('open')) {
                        document.body.classList.add('open');
                        sidebar.setAttribute('aria-hidden', 'false');
                        btn.setAttribute('aria-expanded', 'true');
                        setTimeout(() => {
                            this.parentElement.querySelector('.dropdown-items').setAttribute('aria-hidden', 'false');
                        }, 0);
                        return;
                    }
                    
                    e.stopPropagation();
                    const dropdown = this.parentElement.querySelector('.dropdown-items');
                    const isOpen = dropdown.getAttribute('aria-hidden') === 'true';
                    
                    menuBtns.forEach(otherBtn => {
                        if (otherBtn !== this) {
                            otherBtn.parentElement.querySelector('.dropdown-items').setAttribute('aria-hidden', 'true');
                            otherBtn.setAttribute('aria-expanded', 'false');
                        }
                    });
                    
                    dropdown.setAttribute('aria-hidden', (!isOpen).toString());
                    this.setAttribute('aria-expanded', isOpen.toString());
                });
            });
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                menuBtns.forEach(btn => {
                    if (!btn.contains(e.target)) {
                        btn.parentElement.querySelector('.dropdown-items').setAttribute('aria-hidden', 'true');
                        btn.setAttribute('aria-expanded', 'false');
                    }
                });
            });

            // Update dropdowns visibility with sidebar
            btn.addEventListener('click', function() {
                const isSidebarOpen = document.body.classList.contains('open');
                document.querySelectorAll('.dropdown-items').forEach(dropdown => {
                    dropdown.setAttribute('aria-hidden', (!isSidebarOpen).toString());
                });
            });
        })();
    </script>
</body>
</html>