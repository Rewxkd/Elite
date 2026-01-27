<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elite</title>
</head>
<body>
    <aside class="sidebar">
        <button class="toggle">☰</button>
        <nav class="navigation">
            <a href="#" class="item">❓<span class="text">text</span></a>
            <a href="#" class="item">❓<span class="text">text</span></a>
            <a href="#" class="item">❓<span class="text">text</span></a>
            <br>
            <div class="dropdown">
                <!-- DROPDOWN 1 -->
                <div class="dropdown-item">
                    <button class="dropdown-button">
                        <span class="menu-icon">⁉️</span>
                        <span class="menu-text">placeholder</span>
                        <span class="menu-arrow">▼</span>
                    </button>
                    <div class="dropdown-items">
                        <a href="#">text</a>
                        <a href="#">text</a>
                        <a href="#">text</a>
                    </div>
                </div>

                <!-- DROPDOWN 2 -->
                <div class="dropdown-item">
                    <button class="dropdown-button">
                        <span class="menu-icon">⁉️</span>
                        <span class="menu-text">placeholder</span>
                        <span class="menu-arrow">▼</span>
                    </button>
                    <div class="dropdown-items">
                        <a href="#">text</a>
                        <a href="#">text</a>
                        <a href="#">text</a>
                    </div>
                </div>

                <!-- DROPDOWN 3 -->
                <div class="dropdown-item">
                    <button class="dropdown-button">
                        <span class="menu-icon">⁉️</span>
                        <span class="menu-text">placeholder</span>
                        <span class="menu-arrow">▼</span>
                    </button>
                    <div class="dropdown-items">
                        <a href="#">text</a>
                        <a href="#">text</a>
                        <a href="#">text</a>
                    </div>
                </div>

            </div>
        </nav>
    </aside>

    <header class="header">
        <div class="header-box">
            <div class="logo">
                <img src="Elite-logo.png" alt="">
            </div>
            <div class="balance">
                <span class="balance-amount">$100,000,000,000.00</span>
            </div>
            
            <div class="header-buttons">
                <!-- search button -->
                <button class="button">
                    <span>🔍</span>
                </button>

                <!-- notification button -->
                <button class="button">
                    <span>🔔</span>
                    <!-- notification badge -->
                    <span class="badge" id="badge">3</span>
                </button>

                <!-- profile button -->
                 <button class="button">
                    <span>👤</span>
                 </button>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="container-box">
            <h1>Welcome<span style="color: #90beff;">(User)</span>!</h1>
        </div>
        <br>
        <div class="container-progress">
            <p>Your Progress</p>
        </div>

        <div class="progress-button">
            <p class="progress-text">$0.00 / $1000.00 Wagered</p>
            <br>
            <div class="progress-bar-contaiener">
                <div class="progress-bar" style="width: 0%"></div>
            </div>
        </div>
    </main>

    <script>
        /**
         * Function: setProgressBar
         * Updates the progress bar width and text based on current/total values
         * @param {number} current - Current wagering amount
         * @param {number} total - Total wagering target
         */
        function setProgressBar(current, total) {
            // Calculate percentage, max 100%
            const percent = Math.min(100, Math.round((current / total) * 100));
            // Update progress bar width
            document.getElementById('progressBar').style.width = percent + '%';
            // Update progress text with amounts and percentage
            document.getElementById('progressText').textContent = `$${current.toFixed(2)} / $${total.toFixed(2)} Wagered (${percent}%)`;
        }

        // Initialize progress bar with $350 of $1000 wagered
        setProgressBar(350, 1000);

        /**
         * IIFE (Immediately Invoked Function Expression)
         * Contains all event listeners for sidebar, menus, and buttons
         */
        (function(){
            // Get references to key elements
            const btn = document.getElementById('toggle');
            const sidebar = document.getElementById('side');
            
            /**
             * SIDEBAR TOGGLE FUNCTIONALITY
             * Opens/closes sidebar when hamburger button is clicked
             */
            if (btn) btn.addEventListener('click', function(){
                // Toggle 'open' class on body
                const isOpen = document.body.classList.toggle('open');
                // Update sidebar accessibility attributes
                if (sidebar) sidebar.setAttribute('aria-hidden', (!isOpen).toString());
                btn.setAttribute('aria-expanded', isOpen.toString());
            });

            // Get references to profile and notification buttons
            const profileBtn = document.getElementById('prof');
            const notifBtn = document.getElementById('notif');
            const notificationsPanel = document.getElementById('notifPnl');
            const notifBadge = document.getElementById('badge');

            /**
             * Function: closeAllPanels
             * Closes all open panels (notifications, etc)
             */
            function closeAllPanels() {
                if (notificationsPanel) notificationsPanel.setAttribute('aria-hidden', 'true');
                if (notifBtn) notifBtn.setAttribute('aria-expanded', 'false');
            }

            // Get all menu buttons in the sidebar
            const menuBtns = document.querySelectorAll('.menu-btn');
            
            /**
             * MENU BUTTON CLICK HANDLER
             * Toggles dropdown menus and manages their visibility
             */
            menuBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    // If sidebar is closed, open it first
                    if (!document.body.classList.contains('open')) {
                        document.body.classList.add('open');
                        sidebar.setAttribute('aria-hidden', 'false');
                        btn.setAttribute('aria-expanded', 'true');
                        setTimeout(() => {
                            const dropdown = this.parentElement.querySelector('.menu-dropdown');
                            dropdown.setAttribute('aria-hidden', 'false');
                        }, 0);
                        return;
                    }
                    
                    e.stopPropagation();
                    // Get the dropdown for this menu button
                    const dropdown = this.parentElement.querySelector('.menu-dropdown');
                    // Check if dropdown is currently hidden
                    const isOpen = dropdown.getAttribute('aria-hidden') === 'true';
                    
                    // Close all other dropdowns (only show one at a time)
                    menuBtns.forEach(otherBtn => {
                        if (otherBtn !== this) {
                            const otherDropdown = otherBtn.parentElement.querySelector('.menu-dropdown');
                            otherDropdown.setAttribute('aria-hidden', 'true');
                            otherBtn.setAttribute('aria-expanded', 'false');
                        }
                    });
                    
                    // Toggle current dropdown visibility
                    dropdown.setAttribute('aria-hidden', (!isOpen).toString());
                    this.setAttribute('aria-expanded', isOpen.toString());
                });
            });
            
            /**
             * DOCUMENT CLICK HANDLER
             * Closes dropdowns when clicking outside them
             */
            document.addEventListener('click', function(e) {
                menuBtns.forEach(btn => {
                    const dropdown = btn.parentElement.querySelector('.menu-dropdown');
                    // If click is outside the button, hide the dropdown
                    if (!btn.contains(e.target)) {
                        dropdown.setAttribute('aria-hidden', 'true');
                        btn.setAttribute('aria-expanded', 'false');
                    }
                });
                closeAllPanels();
            });
        })();
        
        /**
         * Function: updateDropdownVisibility
         * Updates dropdown menu visibility based on sidebar state
         */
        function updateDropdownVisibility() {
            // Check if sidebar is open
            const isSidebarOpen = document.body.classList.contains('open');
            // Update all dropdowns to match sidebar state
            document.querySelectorAll('.menu-dropdown').forEach(dropdown => {
                dropdown.setAttribute('aria-hidden', (!isSidebarOpen).toString());
            });
        }
        
        // Update dropdowns when sidebar toggle is clicked
        btn.addEventListener('click', updateDropdownVisibility);
    </script>
</body>
</html>